<?php
IncludeModuleLangFile(__FILE__);

$storage = 'fs';
if(class_exists('\Bitrix\Main\Entity\DataManager'))
{
	$profileDB = new \Bitrix\KdaImportexcel\ProfileTable();
	$conn = $profileDB->getEntity()->getConnection();
	if($conn->getType()=='mysql')
	{
		$storage = 'db';
	}
}

if($storage=='db')
{
	class CKDAImportProfile extends CKDAImportProfileDB {}
	if(is_callable(array($conn, 'queryExecute')))
	{
		$conn->queryExecute('SET wait_timeout=900');
	}
}
else
{
	class CKDAImportProfile extends CKDAImportProfileFS {}
}

class CKDAImportProfileAll {
	protected static $moduleId = 'esol.importexportexcel';
	protected static $instance = null;
	private $importTmpDir = null;
	private $pid = null;
	private $errors = array();
	private $params = array();
	
	public static function getInstance($suffix='')
	{
		if (!isset(static::$instance))
			static::$instance = new static($suffix);

		return static::$instance;
	}
	
	public function GetErrors()
	{
		if(!isset($this->errors) || !is_array($this->errors)) $this->errors = array();
		return implode('<br>', array_unique($this->errors));
	}
	
	public function ShowProfileList($fname)
	{
		$arProfiles = $this->GetList();
		?><select name="<?echo $fname;?>" id="<?echo $fname;?>" onchange="EProfile.Choose(this)" style="max-width: 300px;"><?
			?><option value=""><?echo GetMessage("KDA_IE_NO_PROFILE"); ?></option><?
			?><option value="new" <?if($_REQUEST[$fname]=='new'){echo 'selected';}?>><?echo GetMessage("KDA_IE_NEW_PROFILE"); ?></option><?
			foreach($arProfiles as $k=>$profile)
			{
				?><option value="<?echo $k;?>" <?if(strlen($_REQUEST[$fname])>0 && strval($_REQUEST[$fname])===strval($k)){echo 'selected';}?>><?echo $profile; ?></option><?
			}
		?></select><?
	}
	
	public function UpdateFileSettings(&$params, &$extraParams, $file, $ID)
	{
		$arProfile = $this->GetByID($ID);
		if(!isset($arProfile['SETTINGS']) || !is_array($arProfile['SETTINGS'])) return false;
		
		$titlesLine = array();
		foreach($arProfile['SETTINGS']['LIST_SETTINGS'] as $lk=>$ls)
		{
			if($ls['BIND_FIELDS_TO_HEADERS']==1 && isset($ls['SET_TITLES']))
			{
				$titlesLine[$lk] = (int)$ls['SET_TITLES'];
			}
		}
		if(!empty($titlesLine))
		{
			$maxLine = max($titlesLine);
			if(is_array($file)) $arWorksheets = $file;
			else $arWorksheets = CKDAImportExcel::GetPreviewData($file, max(10, $maxLine+1), $arProfile['SETTINGS_DEFAULT'], $COUNT_COLUMNS);
			foreach($titlesLine as $listkey=>$lineKey)
			{
				if(!isset($arWorksheets[$listkey]['lines'][$lineKey])) continue;
				$arLine = $arWorksheets[$listkey]['lines'][$lineKey];
				$arOldTitles = $arProfile['SETTINGS']['TITLES_LIST'][$listkey];
				$arOldFields = $arProfile['SETTINGS']['FIELDS_LIST'][$listkey];
				$arOldExtra = $arProfile['EXTRASETTINGS'][$listkey];
				$IBLOCK_ID = $arProfile['SETTINGS']['IBLOCK_ID'][$listkey];
				$arTitles = array();
				$arTitlesOrig = array();
				foreach($arLine as $k=>$v)
				{
					$arTitles[$k] = ToLower($v['VALUE']);
					$arTitlesOrig[$k] = $v['VALUE'];
				}
				$arFields = array();
				$arExtra = array();
				foreach($arOldFields as $k=>$v)
				{
					$key = $k;
					if(strpos($k, '_')!==false) $key = current(explode('_', $k));
					$newKey = array_search($arOldTitles[$key], $arTitles);
					if($newKey===false) continue;
					if(strpos($k, '_')!==false) $newKey .= '_'.end(explode('_', $k, 2));
					$arFields[$newKey] = $v;
					$arExtra[$newKey] = $arOldExtra[$k];
				}
				foreach($arOldExtra as $k=>$v)
				{
					if(!isset($arExtra[$k])) $arExtra[$k] = $v;
				}
				if($arProfile['SETTINGS_DEFAULT']['AUTO_CREATION_PROPERTIES']=='Y')
				{
					$this->AddNewPropsToFile($arFields, $arTitlesOrig, $IBLOCK_ID);
				}
				uksort($arFields, create_function('$a,$b', '$a1=current(explode("_", $a));$b1=current(explode("_", $b)); if($a1==$b1){$a2=(int)substr($a, strlen($a1)+1);$b2=(int)substr($b, strlen($b1)+1); return ($a2 < $b2 ? -1 : 1);} return ($a1 < $b1 ? -1 : 1);'));
				uksort($arExtra, create_function('$a,$b', '$a1=current(explode("_", $a));$b1=current(explode("_", $b)); if($a1==$b1){$a2=(int)substr($a, strlen($a1)+1);$b2=(int)substr($b, strlen($b1)+1); return ($a2 < $b2 ? -1 : 1);} return ($a1 < $b1 ? -1 : 1);'));
				$arProfile['SETTINGS']['TITLES_LIST'][$listkey] = $arTitles;
				$arProfile['SETTINGS']['FIELDS_LIST'][$listkey] = $arFields;
				$arProfile['EXTRASETTINGS'][$listkey] = $arExtra;
			}
			$params = array_merge($params, $arProfile['SETTINGS']);
			$extraParams = $arProfile['EXTRASETTINGS'];
			$this->Update($ID, $arProfile['SETTINGS_DEFAULT'], $arProfile['SETTINGS']);
			$this->UpdateExtra($ID, $arProfile['EXTRASETTINGS']);
		}
	}
	
	public function AddNewPropsToFile(&$arFields, $arTitles, $IBLOCK_ID)
	{
		$arPropNames = array();
		$arPropCodes = array();
		$dbRes = \CIBlockProperty::GetList(array(), array('IBLOCK_ID'=>$IBLOCK_ID));
		while($arr = $dbRes->Fetch())
		{
			$arPropNames[ToLower($arr['NAME'])] = $arr['ID'];
			$arPropCodes[ToLower($arr['CODE'])] = $arr['ID'];
		}
		
		foreach($arTitles as $k=>$v)
		{
			$arKeys = preg_grep('/^'.$k.'(_|$)/', array_keys($arFields));
			$isField = false;
			foreach($arKeys as $k2)
			{
				if(strlen(trim($arFields[$k2])) > 0) $isField = true;
			}
			if(!$isField)
			{
				$maxLen = 50;
				$name = trim($v);
				$lowerName = ToLower($name);
				$propId = 0;
				if(isset($arPropNames[$lowerName])) $propId = $arPropNames[$lowerName];
				
				if($propId==0)
				{
					$arParams = array(
						'max_len' => $maxLen,
						'change_case' => 'U',
						'replace_space' => '_',
						'replace_other' => '_',
						'delete_repeat_replace' => 'Y',
					);
					$code = \CUtil::translit($name, LANGUAGE_ID, $arParams);
					$code = preg_replace('/[^a-zA-Z0-9_]/', '', $code);
					$code = preg_replace('/^[0-9_]+/', '', $code);
					$lowerCode = ToLower($code);
					if(isset($arPropCodes[$lowerCode])) $propId = $arPropCodes[$lowerCode];
				}
				
				if($propId==0)
				{			
					$arPropFields = Array(
						"NAME" => $name,
						"ACTIVE" => "Y",
						"CODE" => $code,
						"PROPERTY_TYPE" => "S",
						"IBLOCK_ID" => $IBLOCK_ID
					);
					$ibp = new \CIBlockProperty;
					$newPropId = $ibp->Add($arPropFields);
					if($newPropId > 0)
					{
						$propId = $newPropId;
						$arPropCodes[$lowerCode] = $propId;
						$arPropNames[$lowerName] = $propId;
					}
				}
				
				if($propId > 0)
				{
					$arFields[$k] = 'IP_PROP'.$propId;
				}
			}
		}
	}
	
	public function Apply(&$settigs_default, &$settings, $ID)
	{
		$arProfile = $this->GetByID($ID);
		if(!is_array($settigs_default) && is_array($arProfile['SETTINGS_DEFAULT']))
		{
			$settigs_default = $arProfile['SETTINGS_DEFAULT'];
		}
		if(!is_array($settings) && is_array($arProfile['SETTINGS']))
		{
			$settings = $arProfile['SETTINGS'];
		}
		if(is_array($settings))
		{
			if($settings['ADDITIONAL_SETTINGS'])
			{
				foreach($settings['ADDITIONAL_SETTINGS'] as $k=>$v)
				{
					if($v && !is_array($v))
					{
						$v = CUtil::JsObjectToPhp($v);
					}
					if(!is_array($v)) $v = array();
					$settings['ADDITIONAL_SETTINGS'][$k] = $v;
				}
			}
		}
		
		$instance = static::getInstance();
		$instance->SetParams($settigs_default);
	}
	
	public function ApplyExtra(&$extrasettings, $ID)
	{
		$arProfile = $this->GetByID($ID);
		if(!is_array($extrasettings) && is_array($arProfile['EXTRASETTINGS']))
		{
			$extrasettings = $arProfile['EXTRASETTINGS'];
		}
	}
	
	public function ProfileExists($ID)
	{
		return false;
	}
	
	public function UpdateFields($ID, $arFields)
	{
		return false;
	}
	
	public function GetProfilesCronPool()
	{
		return array();
	}
	
	public function GetLastImportProfiles($arParams = array())
	{
		return array();
	}
	
	public function GetFieldsByID($ID)
	{
		return array();
	}
	
	public function GetStatus($id)
	{
		return '';
	}
	
	public function SetImportParams($pid, $tmpdir, $arParams)
	{
		$this->pid = $pid;
		$this->importTmpDir = $tmpdir;
		$this->fileElementsId = $this->importTmpDir.'elements_id.txt';
		$this->fileOffersId = $this->importTmpDir.'offers_id.txt';
	}
	
	public function SaveElementId($ID, $type)
	{
		$fn = $this->fileElementsId;
		if($type=='O') $fn = $this->fileOffersId;
		$handle = fopen($fn, 'a');
		fwrite($handle, $ID."\r\n");
		fclose($handle);
		return true;
	}
	
	public function GetLastImportId($type)
	{
		if($type=='E') return CKDAImportUtils::SortFileIds($this->fileElementsId);
		elseif($type=='O') return CKDAImportUtils::SortFileIds($this->fileOffersId);
	}
	
	public function GetUpdatedIds($type, $first)
	{
		if($type=='E') return CKDAImportUtils::GetPartIdsFromFile($this->fileElementsId, $first);
		elseif($type=='O') return CKDAImportUtils::GetPartIdsFromFile($this->fileOffersId, $first);
	}
	
	public function IsAlreadyLoaded($ID, $type)
	{
		$fn = $this->fileElementsId;
		if($type=='O') $fn = $this->fileOffersId;
		
		$find = false;
		if($fn && file_exists($fn))
		{
			$handle = fopen($fn, 'r');
			while(!feof($handle) && !$find)
			{
				$buffer = trim(fgets($handle, 128));
				if($buffer && ($ID == (int)$buffer))
				{
					$find = true;
				}
			}
			fclose($handle);
		}
		
		return $find;
	}
	
	public function SetParams($params=array())
	{
		$this->params = $params;
	}
	
	public function GetParam($name)
	{
		if(isset($this->params[$name])) return $this->params[$name];
		return null;
	}
	
	public function OnStartImport()
	{
		return false;
	}
	
	public function OnEndImport($filename, $arParams)
	{
		return array();
	}
	
	public function OutputBackup()
	{
		return false;
	}
	
	public function RestoreBackup($arFiles, $arParams)
	{
		return false;
	}
}