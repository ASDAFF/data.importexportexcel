<?php
IncludeModuleLangFile(__FILE__);

class CKDAImportProfileDB extends CKDAImportProfileAll {
	protected static $moduleId = 'esol.importexportexcel';
	private $errors = array();
	private $entity = false;
	private $importEntity = false;
	private $pid = null;
	private $importMode = null;
	
	function __construct($suffix='')
	{
		$this->suffix = $suffix;
		$this->pathProfiles = dirname(__FILE__).'/../../profiles'.(strlen($suffix) > 0 ? '_'.$suffix : '').'/';
		$this->CheckStorage();
		
		$this->tmpdir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/import/';
		CheckDirPath($this->tmpdir);
		$this->uploadDir = $_SERVER["DOCUMENT_ROOT"].'/upload/'.static::$moduleId.'/';
		CheckDirPath($this->uploadDir);
		
		foreach(array($this->tmpdir, $this->uploadDir) as $k=>$v)
		{
			$i = 0;
			while(++$i < 10 && strlen($v) > 0 && !file_exists($v) && dirname($v)!=$v)
			{
				$v = dirname($v);
			}
			if(strlen($v) > 0 && file_exists($v) && !is_writable($v))
			{
				$this->errors[] = sprintf(GetMessage('KDA_IE_DIR_NOT_WRITABLE'), $v);
			}
		}
		
		$this->tmpdir = realpath($this->tmpdir).'/';
		$this->uploadDir = realpath($this->uploadDir).'/';
		
		/*if(!is_writable($this->tmpdir)) $this->errors[] = sprintf(GetMessage('KDA_IE_DIR_NOT_WRITABLE'), $this->tmpdir);
		if(!is_writable($this->uploadDir)) $this->errors[] = sprintf(GetMessage('KDA_IE_DIR_NOT_WRITABLE'), $this->uploadDir);*/
	}
	
	public function GetErrors()
	{
		if(!isset($this->errors) || !is_array($this->errors)) $this->errors = array();
		return implode('<br>', array_unique($this->errors));
	}
	
	public function CheckStorage()
	{
		$optionName = 'DB_STRUCT_VERSION_'.(strlen($this->suffix) > 0 ? ToUpper($this->suffix) : 'IBLOCK');
		$moduleVersion = false;
		if(is_callable(array('\Bitrix\Main\ModuleManager', 'getVersion')))
		{
			$moduleVersion = \Bitrix\Main\ModuleManager::getVersion(static::$moduleId);
			if($moduleVersion==\Bitrix\Main\Config\Option::get(static::$moduleId, $optionName)) return;
		}
		
		$profileEntity = $this->GetEntity();
		$tblName = $profileEntity->getTableName();
		$conn = $profileEntity->getEntity()->getConnection();
		if(!$conn->isTableExists($tblName))
		{
			$profileEntity->getEntity()->createDbTable();
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `PARAMS` `PARAMS` mediumtext DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_START` `DATE_START` datetime DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_FINISH` `DATE_FINISH` datetime DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `SORT` `SORT` int(11) NOT NULL DEFAULT "500"');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `FILE_HASH` `FILE_HASH` varchar(255) DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `GROUP_ID` `GROUP_ID` int(11) DEFAULT NULL');
			
			$this->CheckTableEncoding($conn, $tblName);
			
			if(file_exists($this->pathProfiles))
			{
				$profileFs = new CKDAImportProfileFS($this->suffix);
				$arProfiles = $profileFs->GetList();
				foreach($arProfiles as $profileId=>$profileName)
				{
					$arParams = $profileFs->GetByID($profileId);
					$profileEntity::Add(array(
						'ID' => ($profileId + 1),
						'NAME' => substr($profileName, 0, 255),
						'PARAMS' => serialize($arParams)
					));
				}
			}
		}
		else
		{
			$isNewFields = false;
			$arDbFields = array();
			$dbRes = $conn->query("SHOW COLUMNS FROM `" . $tblName . "`");
			while($arr = $dbRes->Fetch())
			{
				$arDbFields[] = $arr['Field'];
			}
			$fields = $profileEntity->getEntity()->getScalarFields();
			$helper = $conn->getSqlHelper();
			$prevField = 'ID';
			foreach($fields as $columnName => $field)
			{
				$realColumnName = $field->getColumnName();
				if(!in_array($realColumnName, $arDbFields))
				{
					$conn->query('ALTER TABLE '.$helper->quote($tblName).' ADD COLUMN '.$helper->quote($realColumnName).' '.$helper->getColumnTypeByField($field).' DEFAULT NULL AFTER '.$helper->quote($prevField));
					if($field->getDefaultValue())
					{
						$conn->query('ALTER TABLE '.$helper->quote($tblName).' CHANGE COLUMN '.$helper->quote($realColumnName).' '.$helper->quote($realColumnName).' '.$helper->getColumnTypeByField($field).' DEFAULT "'.$helper->forSql($field->getDefaultValue()).'"');
						$conn->query('UPDATE '.$helper->quote($tblName).' SET '.$helper->quote($realColumnName).'="'.$helper->forSql($field->getDefaultValue()).'"');
					}
					$isNewFields = true;
				}
				$prevField = $realColumnName;
			}
			if($isNewFields)
			{
				$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_FINISH` `DATE_FINISH` datetime DEFAULT NULL');
				$this->CheckTableEncoding($conn, $tblName);
			}
		}
		
		/*profile_element*/
		$peEntity = $this->GetImportEntity();
		$tblName = $peEntity->getTableName();
		$conn = $peEntity->getEntity()->getConnection();
		if(!$conn->isTableExists($tblName))
		{
			$peEntity->getEntity()->createDbTable();
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `ID` `ID` int(18) NOT NULL AUTO_INCREMENT');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `TYPE` `TYPE` varchar(1) NOT NULL');
			$conn->createIndex($tblName, 'ix_profile_element', array('PROFILE_ID', 'ELEMENT_ID', 'TYPE'));
			$this->CheckTableEncoding($conn, $tblName);
		}
		/*/profile_element*/
		
		/*profile_exec*/
		$tEntity = new Bitrix\KdaImportexcel\ProfileExecTable();
		$tblName = $tEntity->getTableName();
		$conn = $tEntity->getEntity()->getConnection();
		if(!$conn->isTableExists($tblName))
		{
			$tEntity->getEntity()->createDbTable();
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `ID` `ID` int(18) NOT NULL AUTO_INCREMENT');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_START` `DATE_START` datetime DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_FINISH` `DATE_FINISH` datetime DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `RUNNED_BY` `RUNNED_BY` int(18) DEFAULT NULL');
			$conn->createIndex($tblName, 'ix_profile_id', array('PROFILE_ID'));
			$this->CheckTableEncoding($conn, $tblName);
		}
		/*/profile_exec*/
		
		/*profile_exec_stat*/
		$tEntity = new Bitrix\KdaImportexcel\ProfileExecStatTable();
		$tblName = $tEntity->getTableName();
		$conn = $tEntity->getEntity()->getConnection();
		if(!$conn->isTableExists($tblName))
		{
			$tEntity->getEntity()->createDbTable();
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `ID` `ID` int(18) NOT NULL AUTO_INCREMENT');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_EXEC` `DATE_EXEC` datetime DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `FIELDS` `FIELDS` longtext DEFAULT NULL');
			$conn->createIndex($tblName, 'ix_entity_id', array('ENTITY_ID'));
			$conn->createIndex($tblName, 'ix_profile_id_profile_exec_id', array('PROFILE_ID', 'PROFILE_EXEC_ID'));
			$this->CheckTableEncoding($conn, $tblName);
		}
		/*/profile_exec_stat*/
		
		if($moduleVersion)
		{
			\Bitrix\Main\Config\Option::set(static::$moduleId, $optionName, $moduleVersion);
		}
	}
	
	private function CheckTableEncoding($conn, $tblName)
	{
		$res = $conn->query('SHOW VARIABLES LIKE "character_set_database"');
		$f = $res->fetch();
		$charset = trim($f['Value']);

		$res = $conn->query('SHOW VARIABLES LIKE "collation_database"');
		$f = $res->fetch();
		$collation = trim($f['Value']);
		
		$res0 = $conn->query('SHOW CREATE TABLE `' . $tblName . '`');
		$f0 = $res0->fetch();
		
		if (preg_match('/DEFAULT CHARSET=([a-z0-9\-_]+)/i', $f0['Create Table'], $regs))
		{
			$t_charset = $regs[1];
			if (preg_match('/COLLATE=([a-z0-9\-_]+)/i', $f0['Create Table'], $regs))
				$t_collation = $regs[1];
			else
			{
				$res0 = $conn->query('SHOW CHARSET LIKE "' . $t_charset . '"');
				$f0 = $res0->fetch();
				$t_collation = $f0['Default collation'];
			}
		}
		else
		{
			$res0 = $conn->query('SHOW TABLE STATUS LIKE "' . $tblName . '"');
			$f0 = $res0->fetch();
			if (!$t_collation = $f0['Collation'])
				return;
			$t_charset = $this->GetCharsetByCollation($conn, $t_collation);
		}
		
		if ($charset != $t_charset)
		{
			$conn->query('ALTER TABLE `' . $tblName . '` CHARACTER SET ' . $charset);
		}
		elseif ($t_collation != $collation)
		{	// table collation differs
			$conn->query('ALTER TABLE `' . $tblName . '` COLLATE ' . $collation);
		}
		
		$arFix = array();
		$res0 = $conn->query("SHOW FULL COLUMNS FROM `" . $tblName . "`");
		while($f0 = $res0->fetch())
		{
			$f_collation = $f0['Collation'];
			if ($f_collation === NULL || $f_collation === "NULL")
				continue;

			$f_charset = $this->GetCharsetByCollation($conn, $f_collation);
			if ($charset != $f_charset)
			{
				$arFix[] = ' MODIFY `'.$f0['Field'].'` '.$f0['Type'].' CHARACTER SET '.$charset.($f0['Null'] == 'YES' ? ' NULL' : ' NOT NULL').
						($f0['Default'] === NULL ? ($f0['Null'] == 'YES' ? ' DEFAULT NULL ' : '') : ' DEFAULT '.($f0['Type'] == 'timestamp' && $f0['Default'] == 'CURRENT_TIMESTAMP' ? $f0['Default'] : '"'.$conn->getSqlHelper()->forSql($f0['Default']).'"')).' '.$f0['Extra'];
			}
			elseif ($collation != $f_collation)
			{
				$arFix[] = ' MODIFY `'.$f0['Field'].'` '.$f0['Type'].' COLLATE '.$collation.($f0['Null'] == 'YES' ? ' NULL' : ' NOT NULL').
						($f0['Default'] === NULL ? ($f0['Null'] == 'YES' ? ' DEFAULT NULL ' : '') : ' DEFAULT '.($f0['Type'] == 'timestamp' && $f0['Default'] == 'CURRENT_TIMESTAMP' ? $f0['Default'] : '"'.$conn->getSqlHelper()->forSql($f0['Default']).'"')).' '.$f0['Extra'];
			}
		}

		if(count($arFix))
		{
			$conn->query('ALTER TABLE `'.$tblName.'` '.implode(",\n", $arFix));
		}
	}
	
	private function GetCharsetByCollation($conn, $collation)
	{
		static $CACHE;
		if (!$c = &$CACHE[$collation])
		{
			$res0 = $conn->query('SHOW COLLATION LIKE "' . $collation . '"');
			$f0 = $res0->Fetch();
			$c = $f0['Charset'];
		}
		return $c;
	}
	
	private function GetEntity()
	{
		if(!$this->entity)
		{
			if($this->suffix=='highload')
			{
				$this->entity = new \Bitrix\KdaImportexcel\ProfileHlTable();
			}
			else
			{
				$this->entity = new \Bitrix\KdaImportexcel\ProfileTable();
			}
		}
		return $this->entity;
	}
	
	private function GetImportEntity()
	{
		if(!$this->importEntity)
		{
			if($this->suffix=='highload')
			{
				$this->importEntity = new \Bitrix\KdaImportexcel\ProfileElementHlTable();
			}
			else
			{
				$this->importEntity = new \Bitrix\KdaImportexcel\ProfileElementTable();
			}
		}
		return $this->importEntity;
	}
	
	public function GetList()
	{
		$arProfiles = array();
		$profileEntity = $this->GetEntity();
		$dbRes = $profileEntity::getList(array('select'=>array('ID', 'NAME'), 'order'=>array('SORT'=>'ASC', 'ID'=>'ASC'), 'filter'=>array('ACTIVE'=>'Y')));
		while($arr = $dbRes->Fetch())
		{
			$arProfiles[$arr['ID'] - 1] = $arr['NAME'];
		}
		
		return $arProfiles;
	}
	
	public function GetByID($ID)
	{
		$profileEntity = $this->GetEntity();
		$arProfile = $profileEntity::getList(array('filter'=>array('ID'=>($ID + 1)), 'select'=>array('PARAMS')))->fetch();
		if($arProfile && $arProfile['PARAMS'])
		{
			$arProfile = unserialize($arProfile['PARAMS']);
		}
		if(!is_array($arProfile)) $arProfile = array();
		
		return $arProfile;
	}
	
	public function GetFieldsByID($ID)
	{
		$profileEntity = $this->GetEntity();
		$arProfile = $profileEntity::getList(array('filter'=>array('ID'=>($ID + 1))))->fetch();
		unset($arProfile['PARAMS']);
		
		return $arProfile;
	}
	
	public function Add($name)
	{
		global $APPLICATION;
		$APPLICATION->ResetException();
		
		$name = trim($name);
		if(strlen($name)==0)
		{
			$APPLICATION->throwException(GetMessage("KDA_IE_NOT_SET_PROFILE_NAME"));
			return false;
		}
		
		$profileEntity = $this->GetEntity();
		
		if($arProfile = $profileEntity::getList(array('filter'=>array('NAME'=>$name), 'select'=>array('ID')))->fetch())
		{
			$APPLICATION->throwException(GetMessage("KDA_IE_PROFILE_NAME_EXISTS"));
			return false;
		}
		
		$dbRes = $profileEntity::add(array('NAME'=>$name));
		if(!$dbRes->isSuccess())
		{
			$error = '';
			if($dbRes->getErrors())
			{
				foreach($dbRes->getErrors() as $errorObj)
				{
					$error .= $errorObj->getMessage().'. ';
				}
				$APPLICATION->throwException($error);
			}
			return false;
		}
		else
		{
			$ID = $dbRes->getId() - 1;			
			return $ID;
		}
	}
	
	public function Update($ID, $settigs_default, $settings)
	{
		$arProfile = $this->GetByID($ID);
		$oldIblockId = $arProfile['SETTINGS_DEFAULT']['IBLOCK_ID'];
		$oldIblockIds = $arProfile['SETTINGS']['IBLOCK_ID'];
		if(is_array($settigs_default))
		{
			$arProfile['SETTINGS_DEFAULT'] = $settigs_default;
		}
		if(is_array($settings))
		{
			$arProfile['SETTINGS'] = $settings;
		}
		if($oldIblockId != $arProfile['SETTINGS_DEFAULT']['IBLOCK_ID'])
		{
			foreach($arProfile['SETTINGS']['IBLOCK_ID'] as $k=>$v)
			{
				if($oldIblockIds[$k]==$v && $oldIblockIds[$k]==$oldIblockId)
				{
					$arProfile['SETTINGS']['IBLOCK_ID'][$k] = $arProfile['SETTINGS_DEFAULT']['IBLOCK_ID'];
				}
			}
		}
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('PARAMS'=>serialize($arProfile)));
	}
	
	public function UpdateExtra($ID, $extrasettings)
	{
		$arProfile = $this->GetByID($ID);
		if(!is_array($extrasettings)) $extrasettings = array();
		$arProfile['EXTRASETTINGS'] = $extrasettings;
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('PARAMS'=>serialize($arProfile)));
	}
	
	public function Delete($ID)
	{
		$profileEntity = $this->GetEntity();
		$profileEntity::delete($ID+1);
	}
	
	public function Copy($ID)
	{
		$profileEntity = $this->GetEntity();
		$arProfile = $profileEntity::getList(array('filter'=>array('ID'=>($ID + 1)), 'select'=>array('NAME', 'PARAMS')))->fetch();
		if(!$arProfile) return false;
		
		$newName = $arProfile['NAME'].GetMessage("KDA_IE_PROFILE_COPY");
		$arParams = unserialize($arProfile['PARAMS']);
		if($arParams['SETTINGS_DEFAULT']['DATA_FILE'] > 0)
		{
			$arParams['SETTINGS_DEFAULT']['DATA_FILE'] = CKDAImportUtils::CopyFile($arParams['SETTINGS_DEFAULT']['DATA_FILE'], true);
			$arProfile['PARAMS'] = serialize($arParams);
		}
		$dbRes = $profileEntity::add(array('NAME'=>$newName, 'PARAMS'=>$arProfile['PARAMS']));
		if(!$dbRes->isSuccess())
		{
			$error = '';
			if($dbRes->getErrors())
			{
				foreach($dbRes->getErrors() as $errorObj)
				{
					$error .= $errorObj->getMessage().'. ';
				}
				$APPLICATION->throwException($error);
			}
			return false;
		}
		else
		{
			$newId = $dbRes->getId() - 1;			
			return $newId;
		}
	}
	
	public function Rename($ID, $name)
	{
		global $APPLICATION;
		$APPLICATION->ResetException();
		
		$name = trim($name);
		if(strlen($name)==0)
		{
			$APPLICATION->throwException(GetMessage("KDA_IE_NOT_SET_PROFILE_NAME"));
			return false;
		}
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('NAME'=>$name));
	}
	
	public function ProfileExists($ID)
	{
		if(!is_numeric($ID)) return false;
		$profileEntity = $this->GetEntity();
		if($arProfile = $profileEntity::getList(array('filter'=>array('ID'=>($ID + 1)), 'select'=>array('ID')))->fetch()) return true;
		else return false;
	}
	
	public function UpdateFields($ID, $arFields)
	{
		if(!is_numeric($ID)) return false;
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), $arFields);
	}
	
	public function GetProfilesCronPool()
	{
		$arIds = array();
		$profileEntity = $this->GetEntity();
		$dbRes = $profileEntity::getList(array('filter'=>array('NEED_RUN'=>'Y'), 'select'=>array('ID'), 'order'=>array('DATE_START'=>'ASC')));
		while($arr = $dbRes->Fetch())
		{
			$arIds[] = (int)$arr['ID'] - 1;
		}
		return $arIds;
	}
	
	public function GetLastImportProfiles($arParams = array())
	{
		$arProfiles = array();
		$limit = (int)$arParams["PROFILES_COUNT"];
		if($limit<=0) $limit = 10;
		$profileEntity = $this->GetEntity();
		$arFilter = array('!DATE_START'=>false);
		if($arParams["PROFILES_SHOW_INACTIVE"]!='Y') $arFilter['ACTIVE'] = 'Y';
		$dbRes = $profileEntity::getList(array('filter'=>$arFilter, 'select'=>array('ID', 'NAME', 'DATE_START', 'DATE_FINISH'), 'order'=>array('DATE_START'=>'DESC'), 'limit'=>$limit));
		while($arr = $dbRes->Fetch())
		{
			$arr['ID'] = (int)$arr['ID'] - 1;
			$arProfiles[] = $arr;
		}
		return $arProfiles;
	}
	
	public function ApplyToLists($ID, $listFrom, $listTo)
	{
		if(!is_numeric($listFrom) || !is_array($listTo) || count($listTo)==0) return;
		$listTo = preg_grep('/^\d+$/', $listTo);
		if(count($listTo)==0) return;
		
		$arParams = $this->GetByID($ID);
		foreach($listTo as $key)
		{
			$arParams['SETTINGS']['FIELDS_LIST'][$key] = $arParams['SETTINGS']['FIELDS_LIST'][$listFrom];
			$arParams['EXTRASETTINGS'][$key] = $arParams['EXTRASETTINGS'][$listFrom];
		}
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('PARAMS'=>serialize($arParams)));
	}
	
	public function GetStatus($id, $bImported=false)
	{
		$arProfile = array();
		if(is_array($id))
		{
			$arProfile = $id;
			$id = $arProfile['ID'];
		}
		$tmpfile = $this->tmpdir.$id.($this->suffix ? '_'.$this->suffix : '').'.txt';
		if(!file_exists($tmpfile))
		{
			if($bImported)
			{
				if(empty($arProfile) || !empty($arProfile['DATE_FINISH'])) return array('STATUS'=>'OK', 'MESSAGE'=>GetMessage("KDA_IE_STATUS_COMPLETE"));
				else return array('STATUS'=>'ERROR', 'MESSAGE'=>GetMessage("KDA_IE_STATUS_FILE_ERROR"));
			}
			else return array('STATUS'=>'OK', 'MESSAGE'=>'');
		}
		$arParams = $this->GetProfileParamsByFile($tmpfile);
		$percent = round(((int)$arParams['total_read_line'] / (int)$arParams['total_file_line']) * 100);
		$percent = min($percent, 99);
		$status = 'OK';
		if((time() - filemtime($tmpfile) < 4*60)) $statusText = GetMessage("KDA_IE_STATUS_PROCCESS");
		else 
		{
			$statusText = GetMessage("KDA_IE_STATUS_BREAK");
			$status = 'ERROR';
		}
		return array('STATUS'=>'ERROR', 'MESSAGE'=>$statusText.' ('.$percent.'%)');
	}
	
	public function GetProfileParamsByFile($tmpfile)
	{
		$content = file_get_contents($tmpfile);
		$maxLength = 10*1024;
		if(strlen($content) > $maxLength)
		{
			$arParams = array();
			$content = preg_replace('/(.)\{[^\}]*\}(.)/Uis', '$1$2', $content);
			if(preg_match_all("/'([^']*)':'([^']*)'/", $content, $m))
			{
				foreach($m[1] as $k2=>$v2)
				{
					$arParams[$v2] = $m[2][$k2];
				}
			}
		}
		else
		{
			$arParams = CUtil::JsObjectToPhp(file_get_contents($tmpfile));
		}
		return $arParams;
	}
	
	public function GetProcessedProfiles()
	{
		$arProfiles = $this->GetList();
		foreach($arProfiles as $k=>$v)
		{
			$tmpfile = $this->tmpdir.$k.($this->suffix ? '_'.$this->suffix : '').'.txt';
			if(!file_exists($tmpfile) || filesize($tmpfile)>500*1024 || (time() - filemtime($tmpfile) < 4*60) || filemtime($tmpfile) < mktime(0, 0, 0, 12, 24, 2015))
			{
				unset($arProfiles[$k]);
				continue;
			}
			
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			$percent = round(((int)$arParams['total_read_line'] / max((int)$arParams['total_file_line'], 1)) * 100);
			$percent = min($percent, 99);
			$arProfiles[$k] = array(
				'key' => $k,
				'name' => $v,
				'percent' => $percent
			);
		}
		if(!is_array($arProfiles)) $arProfiles = array();
		return $arProfiles;
	}
	
	public function RemoveProcessedProfile($id)
	{
		$tmpfile = $this->tmpdir.$id.($this->suffix ? '_'.$this->suffix : '').'.txt';
		if(file_exists($tmpfile))
		{
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			if($arParams['tmpdir'])
			{
				DeleteDirFilesEx(substr($arParams['tmpdir'], strlen($_SERVER['DOCUMENT_ROOT'])));
			}
			unlink($tmpfile);
		}
	}
	
	public function GetProccessParams($id)
	{
		$tmpfile = $this->tmpdir.$id.($this->suffix ? '_'.$this->suffix : '').'.txt';
		if(file_exists($tmpfile))
		{
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			$paramFile = $arParams['tmpdir'].'params.txt';
			$arParams = unserialize(file_get_contents($paramFile));
			return $arParams;
		}
		return false;
	}
	
	public function GetProccessParamsFromPidFile($id)
	{
		$tmpfile = $this->tmpdir.$id.($this->suffix ? '_'.$this->suffix : '').'.txt';
		if(file_exists($tmpfile))
		{
			if(time() - filemtime($tmpfile) < 3*60)
			{
				return false;
			}
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			return $arParams;
		}
		return array();
	}
	
	public function SetImportParams($pid, $tmpdir, $arParams)
	{
		$this->pid = $pid;
		$this->importMode = ($arParams['IMPORT_MODE']=='CRON' ? 'CRON' : 'USER');
	}
	
	public function SaveElementId($ID, $type)
	{
		$entity = $this->GetImportEntity();
		$arFields = array('PROFILE_ID'=>$this->pid, 'ELEMENT_ID'=>$ID, 'TYPE'=>$type);
		$dbRes = $entity::getList(array('filter'=>$arFields, 'select'=>array('ID')));
		if($dbRes->Fetch())
		{
			return false;
		}
		else
		{
			$entity::add($arFields);
			return true;
		}
	}
	
	public function GetLastImportId($type)
	{
		$entity = $this->GetImportEntity();
		$dbRes = $entity::getList(array('filter'=>array('PROFILE_ID'=>$this->pid, 'TYPE'=>$type), 'runtime' => array('MAX_ID' => array('data_type'=>'float', 'expression' => array('max(%s)', 'ELEMENT_ID'))), 'select'=>array('MAX_ID')));
		if($arr = $dbRes->Fetch()) return $arr['MAX_ID'];
		else return 0;
	}
	
	public function GetUpdatedIds($type, $first)
	{
		$entity = $this->GetImportEntity();
		$arIds = array();
		$dbRes = $entity::getList(array('filter'=>array('PROFILE_ID'=>$this->pid, 'TYPE'=>$type, '>ELEMENT_ID'=>(int)$first), 'select'=>array('ELEMENT_ID'), 'order'=>array('ELEMENT_ID'=>'ASC'), 'limit'=>5000));
		while($arr = $dbRes->Fetch())
		{
			$arIds[] = $arr['ELEMENT_ID'];
		}
		return $arIds;
	}
	
	public function IsAlreadyLoaded($ID, $type)
	{
		$fn = $this->fileElementsId;
		if($type=='O') $fn = $this->fileOffersId;
		
		$entity = $this->GetImportEntity();
		$arFields = array('PROFILE_ID'=>$this->pid, 'ELEMENT_ID'=>$ID, 'TYPE'=>$type);
		$dbRes = $entity::getList(array('filter'=>$arFields, 'select'=>array('ID')));
		if($dbRes->Fetch())
		{
			return true;
		}
		return false;
	}
	
	public function OnStartImport()
	{
		$this->UpdateFields($this->pid, array(
			'DATE_START' => new \Bitrix\Main\Type\DateTime(),
			'DATE_FINISH' => false
		));
		$this->DeleteImportTmpData();
		
		if($this->suffix!='highload')
		{
			$this->SetActiveImport(true);
			if(COption::GetOptionString(static::$moduleId, 'NOTIFY_BEGIN_IMPORT', 'N')=='Y'
				&& (COption::GetOptionString(static::$moduleId, 'NOTIFY_MODE', 'NONE')=='ALL'
					|| (COption::GetOptionString(static::$moduleId, 'NOTIFY_MODE', 'NONE')=='CRON' && $this->importMode=='CRON')))
			{
				$this->CheckEventOnBeginImport();
				$arEventData = array();
				$arProfile = $this->GetFieldsByID($this->pid);
				$arEventData['PROFILE_NAME'] = $arProfile['NAME'];
				$arEventData['IMPORT_START_DATETIME'] = (is_callable(array($arProfile['DATE_START'], 'toString')) ? $arProfile['DATE_START']->toString() : '');
				$arEventData['EMAIL_TO'] = COption::GetOptionString(static::$moduleId, 'NOTIFY_EMAIL');
				CEvent::Send('KDA_IMPORT_START', $this->GetDefaultSiteId(), $arEventData);
			}
		}
	}
	
	public function OnEndImport($file, $arParams)
	{
		$hash = md5_file($file);
		$this->UpdateFields($this->pid, array(
			'FILE_HASH'=>$hash,
			'DATE_FINISH'=>new \Bitrix\Main\Type\DateTime()
		));		
		$this->DeleteImportTmpData();
		
		if($this->suffix!='highload')
		{
			if(!$this->IsActiveProcesses())
			{
				$this->SetActiveImport(false);
			}
			
			$arEventData = array();
			if(is_array($arParams))
			{
				foreach($arParams as $k=>$v)
				{
					if(!is_array($v)) $arEventData[ToUpper($k)] = $v;
				}
			}
			$arProfile = $this->GetFieldsByID($this->pid);
			$arEventData['PROFILE_NAME'] = $arProfile['NAME'];
			$arEventData['FILE_PATH'] = $file;
			$arEventData['IMPORT_START_DATETIME'] = (is_callable(array($arProfile['DATE_START'], 'toString')) ? $arProfile['DATE_START']->toString() : '');
			$arEventData['IMPORT_FINISH_DATETIME'] = (is_callable(array($arProfile['DATE_FINISH'], 'toString')) ? $arProfile['DATE_FINISH']->toString() : '');
			if(COption::GetOptionString(static::$moduleId, 'NOTIFY_END_IMPORT', 'N')=='Y'
				&& (COption::GetOptionString(static::$moduleId, 'NOTIFY_MODE', 'NONE')=='ALL'
					|| (COption::GetOptionString(static::$moduleId, 'NOTIFY_MODE', 'NONE')=='CRON' && $this->importMode=='CRON')))
			{
				$this->CheckEventOnEndImport();
				$arEventData['EMAIL_TO'] = COption::GetOptionString(static::$moduleId, 'NOTIFY_EMAIL');
				//нужен вывод ошибок
				$arEventData['STAT_BLOCK'] = '';
				foreach(array('TOTAL_LINE', 'CORRECT_LINE', 'ERROR_LINE', 'ELEMENT_ADDED_LINE', 'ELEMENT_UPDATED_LINE', 'SECTION_ADDED_LINE', 'SECTION_UPDATED_LINE', 'SKU_ADDED_LINE', 'SKU_UPDATED_LINE', 'KILLED_LINE', 'ZERO_STOCK_LINE', 'OLD_REMOVED_LINE', 'OFFER_KILLED_LINE', 'OFFER_ZERO_STOCK_LINE', 'OFFER_OLD_REMOVED_LINE') as $k=>$v)
				{
					if($k < 3 || $arEventData[$v] > 0)
					{
						$arEventData['STAT_BLOCK'] .= GetMessage("KDA_IE_EVENT_".$v).": ".$arEventData[$v]."\r\n";
					}
				}
				CEvent::Send('KDA_IMPORT_END', $this->GetDefaultSiteId(), $arEventData);
			}
		}
		return $arEventData;
	}
	
	public function SetActiveImport($on = true)
	{
		if($on)
		{
			\Bitrix\Main\Config\Option::set(static::$moduleId, 'IS_ACTIVE_IMPORT', 'Y');
			foreach(GetModuleEvents(static::$moduleId, "OnBeginImportGlobal", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array());
			}
		}
		else
		{
			\Bitrix\Main\Config\Option::set(static::$moduleId, 'IS_ACTIVE_IMPORT', 'N');
			foreach(GetModuleEvents(static::$moduleId, "OnEndImportGlobal", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array());
			}
		}
	}
	
	public function DeleteImportTmpData()
	{
		$entity = $this->GetImportEntity();
		$tblName = $entity->getTableName();
		$conn = $entity->getEntity()->getConnection();
		$conn->queryExecute('DELETE FROM `'.$tblName.'` WHERE PROFILE_ID='.intval($this->pid));
	}
	
	public function IsActiveProcesses()
	{
		$profileEntity = $this->GetEntity();
		$dbRes = $profileEntity::getList(array('select'=>array('ID'), 'order'=>array('SORT'=>'ASC', 'ID'=>'ASC'), 'filter'=>array('>DATE_START'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()-30*24*60*60), 'DATE_FINISH'=>false), 'limit'=>1));
		while($arProfile = $dbRes->Fetch())
		{
			$tmpfile = $this->tmpdir.$arProfile['ID'].($this->suffix ? '_'.$this->suffix : '').'.txt';
			if(file_exists($tmpfile) && (time() - filemtime($tmpfile) < 4*60) && filemtime($tmpfile) > mktime(0, 0, 0, 12, 24, 2015))
			{
				return true;
			}
		}
		return false;
	}
	
	public function GetDefaultSiteId()
	{
		$arSite = CSite::GetList(($by='sort'), ($order='asc'), array('DEFAULT'=>'Y'))->Fetch();
		return $arSite['ID'];
	}
	
	public function CheckEventOnBeginImport()
	{
		$eventName = 'KDA_IMPORT_START';
		$dbRes = CEventType::GetList(array('TYPE_ID'=>$eventName));
		if(!$dbRes->Fetch())
		{
			$et = new CEventType();
			$et->Add(array(
				"LID"           => "ru",
				"EVENT_NAME"    => $eventName,
				"NAME"          => GetMessage("KDA_IE_EVENT_IMPORT_START"),
				"DESCRIPTION"   => 
					"#PROFILE_NAME# - ".GetMessage("KDA_IE_EVENT_PROFILE_NAME")."\r\n".
					"#IMPORT_START_DATETIME# - ".GetMessage("KDA_IE_EVENT_TIME_BEGIN")
				));
		}
		$dbRes = CEventMessage::GetList(($by='id'), ($order='desc'), array('TYPE_ID'=>$eventName));
		if(!$dbRes->Fetch())
		{
			$emess = new CEventMessage();
			$emess->Add(array(
				'ACTIVE' => 'Y',
				'EVENT_NAME' => $eventName,
				'LID' => $this->GetDefaultSiteId(),
				'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO' => '#EMAIL_TO#',
				'SUBJECT' => '#SITE_NAME#: '.GetMessage("KDA_IE_EVENT_BEGIN_PROFILE").' "#PROFILE_NAME#"',
				'MESSAGE' => 
					GetMessage("KDA_IE_EVENT_PROFILE_NAME").": #PROFILE_NAME#\r\n".
					GetMessage("KDA_IE_EVENT_TIME_BEGIN").": #IMPORT_START_DATETIME#"
			));
		}
	}
	
	public function CheckEventOnEndImport()
	{
		$eventName = 'KDA_IMPORT_END';
		$dbRes = CEventType::GetList(array('TYPE_ID'=>$eventName));
		if(!$dbRes->Fetch())
		{
			$et = new CEventType();
			$et->Add(array(
				"LID"           => "ru",
				"EVENT_NAME"    => $eventName,
				"NAME"          => GetMessage("KDA_IE_EVENT_IMPORT_END"),
				"DESCRIPTION"   => 
					"#PROFILE_NAME# - ".GetMessage("KDA_IE_EVENT_PROFILE_NAME")."\r\n".
					"#IMPORT_START_DATETIME# - ".GetMessage("KDA_IE_EVENT_TIME_BEGIN")."\r\n".
					"#IMPORT_FINISH_DATETIME# - ".GetMessage("KDA_IE_EVENT_TIME_END")."\r\n".
					"#STAT_BLOCK# - ".GetMessage("KDA_IE_EVENT_STAT_BLOCK")."\r\n".
					"#TOTAL_LINE# - ".GetMessage("KDA_IE_EVENT_TOTAL_LINE")."\r\n".
					"#CORRECT_LINE# - ".GetMessage("KDA_IE_EVENT_CORRECT_LINE")."\r\n".
					"#ERROR_LINE# - ".GetMessage("KDA_IE_EVENT_ERROR_LINE")."\r\n".
					"#ELEMENT_ADDED_LINE# - ".GetMessage("KDA_IE_EVENT_ELEMENT_ADDED_LINE")."\r\n".
					"#ELEMENT_UPDATED_LINE# - ".GetMessage("KDA_IE_EVENT_ELEMENT_UPDATED_LINE")."\r\n".
					"#SECTION_ADDED_LINE# - ".GetMessage("KDA_IE_EVENT_SECTION_ADDED_LINE")."\r\n".
					"#SECTION_UPDATED_LINE# - ".GetMessage("KDA_IE_EVENT_SECTION_UPDATED_LINE")."\r\n".
					"#SKU_ADDED_LINE# - ".GetMessage("KDA_IE_EVENT_SKU_ADDED_LINE")."\r\n".
					"#SKU_UPDATED_LINE# - ".GetMessage("KDA_IE_EVENT_SKU_UPDATED_LINE")."\r\n".
					"#KILLED_LINE# - ".GetMessage("KDA_IE_EVENT_KILLED_LINE")."\r\n".
					"#ZERO_STOCK_LINE# - ".GetMessage("KDA_IE_EVENT_ZERO_STOCK_LINE")."\r\n".
					"#OLD_REMOVED_LINE# - ".GetMessage("KDA_IE_EVENT_OLD_REMOVED_LINE")."\r\n".
					"#OFFER_KILLED_LINE# - ".GetMessage("KDA_IE_EVENT_OFFER_KILLED_LINE")."\r\n".
					"#OFFER_ZERO_STOCK_LINE# - ".GetMessage("KDA_IE_EVENT_OFFER_ZERO_STOCK_LINE")."\r\n".
					"#OFFER_OLD_REMOVED_LINE# - ".GetMessage("KDA_IE_EVENT_OFFER_OLD_REMOVED_LINE")
				));
		}
		$dbRes = CEventMessage::GetList(($by='id'), ($order='desc'), array('TYPE_ID'=>$eventName));
		if(!$dbRes->Fetch())
		{
			$emess = new CEventMessage();
			$emess->Add(array(
				'ACTIVE' => 'Y',
				'EVENT_NAME' => $eventName,
				'LID' => $this->GetDefaultSiteId(),
				'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO' => '#EMAIL_TO#',
				'SUBJECT' => '#SITE_NAME#: '.GetMessage("KDA_IE_EVENT_END_PROFILE").' "#PROFILE_NAME#"',
				'MESSAGE' => 
					GetMessage("KDA_IE_EVENT_PROFILE_NAME").": #PROFILE_NAME#\r\n".
					GetMessage("KDA_IE_EVENT_TIME_BEGIN").": #IMPORT_START_DATETIME#\r\n".
					GetMessage("KDA_IE_EVENT_TIME_END").": #IMPORT_FINISH_DATETIME#\r\n".
					"\r\n".
					GetMessage("KDA_IE_EVENT_STAT_BLOCK").": \r\n#STAT_BLOCK#"
			));
		}
	}
	
	public function OutputBackup()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		
		$fileName = 'profiles_'.date('Y_m_d_H_i_s');
		$tempPath = \CFile::GetTempName('', bx_basename($fileName.'.zip'));
		$dir = rtrim(\Bitrix\Main\IO\Path::getDirectory($tempPath), '/').'/'.$fileName;
		\Bitrix\Main\IO\Directory::createDirectory($dir);
		
		$arFiles = array(
			'config' => $dir.'/config.txt',
			'data' => $dir.'/data.txt'
		);
		
		file_put_contents($arFiles['config'], base64_encode(serialize(
			array(
				'domain' => $_SERVER['HTTP_HOST'],
				'encoding' => \CKDAImportUtils::getSiteEncoding()
			)
		)));
		
		$handle = fopen($arFiles['data'], 'a');
		$profileEntity = $this->GetEntity();
		$dbRes = $profileEntity::getList(array('order'=>array('ID'=>'ASC'), 'select'=>array('ID', 'ACTIVE', 'NAME', 'PARAMS', 'SORT')));
		while($arProfile = $dbRes->Fetch())
		{
			foreach($arProfile as $k=>$v)
			{
				$arProfile[$k] = base64_encode($v);
			}
			fwrite($handle, base64_encode(serialize($arProfile))."\r\n");
		}
		fclose($handle);
		
		$zipObj = \CBXArchive::GetArchive($tempPath, 'ZIP');
		$zipObj->SetOptions(array(
			"COMPRESS" =>true,
			"ADD_PATH" => false,
			"REMOVE_PATH" => $dir.'/',
		));
		$zipObj->Pack($dir.'/');
		
		foreach($arFiles as $file) unlink($file);
		rmdir($dir);
		
		header('Content-type: application/zip');
		header('Content-Transfer-Encoding: Binary');
		header('Content-length: '.filesize($tempPath));
		header('Content-disposition: attachment; filename="'.basename($tempPath).'"');
		readfile($tempPath);
		
		die();
	}
	
	public function RestoreBackup($arPFile, $arParams)
	{
		if(!isset($arPFile) || !is_array($arPFile) || $arPFile['error'] > 0 || $arPFile['size'] < 1)
		{
			return array('TYPE'=>'ERROR', 'MESSAGE'=>GetMessage("KDA_IE_RESTORE_NOT_LOAD_FILE"));
		}
		$filename = $arPFile['name'];
		if(ToLower(\CKDAImportUtils::GetFileExtension($filename))!=='zip')
		{
			return array('TYPE'=>'ERROR', 'MESSAGE'=>GetMessage("KDA_IE_RESTORE_FILE_NOT_VALID"));
		}
		
		$tempPath = \CFile::GetTempName('', bx_basename($filename));
		$subdir = current(explode('.', $filename));
		if(strlen($subdir)==0) $subdir = 'backup';
		$dir = rtrim(\Bitrix\Main\IO\Path::getDirectory($tempPath), '/').'/'.$subdir;
		\Bitrix\Main\IO\Directory::createDirectory($dir);
		
		$zipObj = CBXArchive::GetArchive($arPFile['tmp_name'], 'ZIP');
		$zipObj->Unpack($dir.'/');
		
		$arFiles = array(
			'config' => $dir.'/config.txt',
			'data' => $dir.'/data.txt'
		);
		if(!file_exists($arFiles['config']) || !file_exists($arFiles['data']))
		{
			foreach($arFiles as $file) unlink($file);
			rmdir($dir);
			return array('TYPE'=>'ERROR', 'MESSAGE'=>GetMessage("KDA_IE_RESTORE_FILE_NOT_VALID"));
		}
		
		$profileEntity = $this->GetEntity();
		$encoding = \CKDAImportUtils::getSiteEncoding();
		
		if($arParams['RESTORE_TYPE']=='REPLACE')
		{
			$dbRes = $profileEntity::getList();
			while($arProfile = $dbRes->Fetch())
			{
				$profileEntity::delete($arProfile['ID']);
			}
		}
		
		$arConfig = unserialize(base64_decode(file_get_contents($arFiles['config'])));
		$handle = fopen($arFiles['data'], "r");
		while(!feof($handle))
		{
			$buffer = trim(fgets($handle, 16777216));
			if(strlen($buffer) == 0) continue;			
			$arProfile = unserialize(base64_decode($buffer));
			if(!is_array($arProfile)) continue;
			foreach($arProfile as $k=>$v)
			{
				$v = base64_decode($v);
				if($encoding != $arConfig['encoding']) $v = \Bitrix\Main\Text\Encoding::convertEncoding($v, $arConfig['encoding'], $encoding);
				$arProfile[$k] = $v;
			}
			
			if($arParams['RESTORE_TYPE']=='ADD') unset($arProfile['ID']);
			$dbRes = $profileEntity::add($arProfile);
			/*if(!$dbRes->isSuccess())
			{
				$error = '';
				if($dbRes->getErrors())
				{
					foreach($dbRes->getErrors() as $errorObj)
					{
						$error .= $errorObj->getMessage().'. ';
					}
					$APPLICATION->throwException($error);
				}
			}
			else
			{
				$ID = $dbRes->getId();
			}*/
		}
		fclose($handle);
		foreach($arFiles as $file) unlink($file);
		rmdir($dir);
		
		return array('TYPE'=>'SUCCESS', 'MESSAGE'=>GetMessage("KDA_IE_RESTORE_SUCCESS"));
	}
}