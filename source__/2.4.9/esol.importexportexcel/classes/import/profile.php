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
		$conn->queryExecute('SET sql_mode=""');
	}
}
else
{
	class CKDAImportProfile extends CKDAImportProfileFS {}
}

class CKDAImportProfileAll {
	protected static $moduleId = 'esol.importexportexcel';
	protected static $instance = null;
	protected static $arChangedCols = array();
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
	
	public function UpdateFileSettings(&$params, &$extraParams, $file, $ID, $bCron=false)
	{
		$arProfile = $this->GetByID($ID);
		if(!isset($arProfile['SETTINGS']) || !is_array($arProfile['SETTINGS'])) return false;
		$cronBreak = (bool)($bCron && \Bitrix\Main\Config\Option::get(static::$moduleId, 'CRON_BREAK_WITH_CHANGE_TITLES', 'N')=='Y');
		
		$titlesLine = array();
		$titlesLineForSave = array();
		if(isset($arProfile['SETTINGS']['LIST_SETTINGS']) && is_array($arProfile['SETTINGS']['LIST_SETTINGS']))
		{
			foreach($arProfile['SETTINGS']['LIST_SETTINGS'] as $lk=>$ls)
			{
				if(isset($ls['SET_TITLES']))
				{
					if($ls['BIND_FIELDS_TO_HEADERS']==1 || $cronBreak)
					{
						$titlesLine[$lk] = (int)$ls['SET_TITLES'];
					}
					if($ls['BIND_FIELDS_TO_HEADERS']==1)
					{
						$titlesLineForSave[$lk] = (int)$ls['SET_TITLES'];
					}
				}
			}
		}
		
		if(!empty($titlesLine))
		{
			if(!isset($arProfile['SETTINGS']['OLDBINDPARAMS']))
			{
				$arProfile['SETTINGS']['OLDBINDPARAMS'] = array(
					'TITLES_LIST' => $arProfile['SETTINGS']['TITLES_LIST'],
					'FIELDS_LIST' => $arProfile['SETTINGS']['FIELDS_LIST'],
					'EXTRASETTINGS' => $arProfile['EXTRASETTINGS'],
				);
			}
			$isChanges = false;
			self::$arChangedCols = array();
			$maxLine = max($titlesLine);
			if(is_array($file)) $arWorksheets = $file;
			else $arWorksheets = CKDAImportExcel::GetPreviewData($file, max(10, $maxLine+1), $arProfile['SETTINGS_DEFAULT'], $COUNT_COLUMNS, $ID);
			foreach($titlesLine as $listkey=>$lineKey)
			{
				if(!isset($arWorksheets[$listkey]['lines'][$lineKey])) continue;
				$arLine = $arWorksheets[$listkey]['lines'][$lineKey];
				/*$arOldTitles = array_map(array($this, 'Trim'), $arProfile['SETTINGS']['TITLES_LIST'][$listkey]);
				$arOldFields = $arProfile['SETTINGS']['FIELDS_LIST'][$listkey];
				$arOldExtra = $arProfile['EXTRASETTINGS'][$listkey];*/
				if(true /*isset($arProfile['SETTINGS']['OLDBINDPARAMS'])*/)
				{
					$arOldTitles = array_map(array($this, 'Trim'), $arProfile['SETTINGS']['OLDBINDPARAMS']['TITLES_LIST'][$listkey]);
					$arOldFields = $arProfile['SETTINGS']['OLDBINDPARAMS']['FIELDS_LIST'][$listkey];
					$arOldExtra = $arProfile['SETTINGS']['OLDBINDPARAMS']['EXTRASETTINGS'][$listkey];
				}
				$IBLOCK_ID = $arProfile['SETTINGS']['IBLOCK_ID'][$listkey];
				$arTitles = array();
				$arTitlesOrig = array();
				foreach($arLine as $k=>$v)
				{
					$arTitles[$k] = $this->Trim(preg_replace('/[\r\n\s]+/', ' ', ToLower($v['VALUE'])));
					$arTitlesOrig[$k] = $v['VALUE'];
				}
				foreach(GetModuleEvents(static::$moduleId, "OnBeforeCheckTitles", true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, array(&$arTitles, &$arOldTitles));
				}
				$arFields = array();
				$arExtra = array();
				$arChangeKeys = array();
				foreach($arOldFields as $k=>$v)
				{
					$key = $k;
					if(strpos($k, '_')!==false) $key = current(explode('_', $k));
					if($arTitles[$key]===$arOldTitles[$key]) $newKey = $key;
					else $newKey = array_search($arOldTitles[$key], $arTitles);
					if(strlen($v) > 0 && ($newKey===false || $key!=$newKey))
					{
						$isChanges = true;
						self::$arChangedCols[$key + 1] = array('OLD'=>$arOldTitles[$key], 'NEW'=>$arTitles[$key]);
					}
					if($newKey===false || (array_key_exists($newKey, $arFields) && strlen($v)==0)) continue;
					if($key!=$newKey) $arChangeKeys[$key] = $newKey;
					if(strpos($k, '_')!==false) $newKey .= '_'.end(explode('_', $k, 2));
					$arFields[$newKey] = $v;
					$arExtra[$newKey] = $arOldExtra[$k];
				}
				foreach($arOldExtra as $k=>$v)
				{
					if(!isset($arExtra[$k])) $arExtra[$k] = $v;
				}

				/*update conversions*/
				if(count($arChangeKeys) > 0)
				{
					$arChangeKeys1 = array();
					$arChangeKeys2 = array();
					$arChangeKeys3 = array();
					foreach($arChangeKeys as $oldKey=>$newKey)
					{
						$arChangeKeys1[$oldKey + 1] = $newKey + 1;
						$arChangeKeys2['CELL'.($oldKey + 1)] = 'CELL'.($newKey + 1);
						$arChangeKeys3['#CELL'.($oldKey + 1).'#'] = '#CELL'.($newKey + 1).'#';
					}
					foreach($arExtra as $k=>$v)
					{
						if(isset($v['CONVERSION']) && is_array($v['CONVERSION']))
						{
							foreach($v['CONVERSION'] as $k2=>$v2)
							{
								if(isset($v2['CELL']) && !is_array($v2['CELL']) && array_key_exists($v2['CELL'], $arChangeKeys1)) $arExtra[$k]['CONVERSION'][$k2]['CELL'] = $arChangeKeys1[$v2['CELL']];
								if(isset($v2['FROM']) && !is_array($v2['FROM'])) $arExtra[$k]['CONVERSION'][$k2]['FROM'] = strtr($v2['FROM'], $arChangeKeys3);
								if(isset($v2['TO']) && !is_array($v2['TO'])) $arExtra[$k]['CONVERSION'][$k2]['TO'] = strtr($v2['TO'], $arChangeKeys3);
							}
						}
						if(isset($v['EXTRA_CONVERSION']) && is_array($v['EXTRA_CONVERSION']))
						{
							foreach($v['EXTRA_CONVERSION'] as $k2=>$v2)
							{
								if(isset($v2['CELL']) && !is_array($v2['CELL']) && array_key_exists($v2['CELL'], $arChangeKeys2)) $arExtra[$k]['EXTRA_CONVERSION'][$k2]['CELL'] = $arChangeKeys2[$v2['CELL']];
								if(isset($v2['FROM']) && !is_array($v2['FROM'])) $arExtra[$k]['EXTRA_CONVERSION'][$k2]['FROM'] = strtr($v2['FROM'], $arChangeKeys3);
								if(isset($v2['TO']) && !is_array($v2['TO'])) $arExtra[$k]['EXTRA_CONVERSION'][$k2]['TO'] = strtr($v2['TO'], $arChangeKeys3);
							}
						}
					}
				}
				/*/update conversions*/

				if(isset($titlesLineForSave[$listkey]))
				{
					if($arProfile['SETTINGS_DEFAULT']['AUTO_CREATION_PROPERTIES']=='Y')
					{
						$this->AddNewPropsToFile($arFields, $arTitlesOrig, $IBLOCK_ID);
					}
					uksort($arFields, array(__CLASS__, 'SortFieldsByIndex'));
					uksort($arExtra, array(__CLASS__, 'SortFieldsByIndex'));
					$arProfile['SETTINGS']['TITLES_LIST'][$listkey] = $arTitles;
					$arProfile['SETTINGS']['FIELDS_LIST'][$listkey] = $arFields;
					$arProfile['EXTRASETTINGS'][$listkey] = $arExtra;
				}
			}
			$params = array_merge($params, $arProfile['SETTINGS']);
			$extraParams = $arProfile['EXTRASETTINGS'];
			
			if($isChanges && $cronBreak) return false;
			$this->Update($ID, $arProfile['SETTINGS_DEFAULT'], $arProfile['SETTINGS']);
			$this->UpdateExtra($ID, $arProfile['EXTRASETTINGS']);
		}
		return true;
	}
	
	public function GetChangedColsTbl()
	{
		if(!is_array(self::$arChangedCols) || empty(self::$arChangedCols)) return '';
		$tbl = '<table border="1"><tr><th colspan="3">'.GetMessage("KDA_IE_CHANGE_FILE").'</th></tr><tr><th>'.GetMessage("KDA_IE_CHANGE_COLUMN_NUMBER").'</th><th>'.GetMessage("KDA_IE_CHANGE_COLUMN_OLD_VAL").'</th><th>'.GetMessage("KDA_IE_CHANGE_COLUMN_NEW_VAL").'</th></tr>';
		foreach(self::$arChangedCols as $k=>$v)
		{
			$tbl .= '<tr><td>'.$k.'</td><td>'.$v['OLD'].'</td><td>'.$v['NEW'].'</td></tr>';
		}
		$tbl .= '</table>';
		return $tbl;
	}
	
	public function Trim($str)
	{
		$str = trim($str);
		$str = preg_replace('/(^(\xC2\xA0|\s)+|(\xC2\xA0|\s)+$)/s', '', $str);
		return $str;
	}
	
	public static function SortFieldsByIndex($a, $b)
	{
		$a1=current(explode("_", $a));
		$b1=current(explode("_", $b)); 
		if($a1==$b1)
		{
			$a2=(int)substr($a, strlen($a1)+1);
			$b2=(int)substr($b, strlen($b1)+1); 
			return ($a2 < $b2 ? -1 : 1);
		}
		return ($a1 < $b1 ? -1 : 1);
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
			if(is_array($settings['FIELDS_LIST']))
			{
				foreach($settings['FIELDS_LIST'] as $listkey=>$arFields)
				{
					uksort($arFields, array(__CLASS__, 'SortFieldsByIndex'));
					$settings['FIELDS_LIST'][$listkey] = $arFields;
				}
			}
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
	
	public function SetImportParams($pid, $tmpdir, $arParams, $arImportParams=array())
	{
		$this->pid = $pid;
		$this->importTmpDir = $tmpdir;
		$this->fileElementsId = $this->importTmpDir.'elements_id.txt';
		$this->fileOffersId = $this->importTmpDir.'offers_id.txt';
		$this->importParams = $arImportParams;
	}
	
	public function GetImportParam($pname)
	{
		if(isset($this->importParams) && is_array($this->importParams) && array_key_exists($pname, $this->importParams)) return $this->importParams[$pname];
		else return false;
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
	
	public static function EncodeProfileParams($arParams)
	{
		return '='.base64_encode(serialize($arParams));
	}
	
	public static function DecodeProfileParams($paramStr)
	{
		$paramStr = trim($paramStr);
		if(substr($paramStr, 0, 1)=='=') $paramStr = base64_decode(substr($paramStr, 1));
		$arParams = unserialize($paramStr);
		if(!is_array($arParams)) $arParams = array();
		return $arParams;
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