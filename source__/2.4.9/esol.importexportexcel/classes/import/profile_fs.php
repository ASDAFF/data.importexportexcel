<?php
IncludeModuleLangFile(__FILE__);

class CKDAImportProfileFS extends CKDAImportProfileAll {
	protected static $moduleId = 'esol.importexportexcel';
	protected static $moduleSubDir = 'import/';
	private $errors = array();
	
	function __construct($suffix='')
	{
		$this->suffix = $suffix;
		$this->pathProfiles = dirname(__FILE__).'/../../profiles'.(strlen($suffix) > 0 ? '_'.$suffix : '').'/';
		CheckDirPath($this->pathProfiles);
		$this->tmpdir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		CheckDirPath($this->tmpdir);
		$this->uploadDir = $_SERVER["DOCUMENT_ROOT"].'/upload/'.static::$moduleId.'/';
		CheckDirPath($this->uploadDir);
		
		$this->pathProfiles = realpath($this->pathProfiles).'/';
		$this->tmpdir = realpath($this->tmpdir).'/';
		$this->uploadDir = realpath($this->uploadDir).'/';
		$this->fileProfiles = $this->pathProfiles.'profiles.txt';
		
		if(!is_writable($this->pathProfiles)) $this->errors[] = sprintf(GetMessage('KDA_IE_DIR_NOT_WRITABLE'), $this->pathProfiles);
		if(!is_writable($this->tmpdir)) $this->errors[] = sprintf(GetMessage('KDA_IE_DIR_NOT_WRITABLE'), $this->tmpdir);
		if(!is_writable($this->uploadDir)) $this->errors[] = sprintf(GetMessage('KDA_IE_DIR_NOT_WRITABLE'), $this->uploadDir);
		if(file_exists($this->fileProfiles) && !is_writable($this->fileProfiles)) $this->errors[] = sprintf(GetMessage('KDA_IE_FILE_NOT_WRITABLE'), $this->fileProfiles);
	}
	
	public function GetList()
	{
		if(!file_exists($this->fileProfiles))
		{
			$arProfiles = array();
		}
		else
		{
			$arProfiles = unserialize(file_get_contents($this->fileProfiles));
			if(!is_array($arProfiles))
			{
				$arProfiles = array();
			}
		}
		
		return $arProfiles;
	}
	
	public function GetByID($ID)
	{
		$arProfiles = $this->GetList();
		$fn = $this->pathProfiles.$ID.'.txt';
		if($arProfiles[$ID] && file_exists($fn))
		{
			$arProfile = unserialize(file_get_contents($fn));
			if(!is_writable($fn)) $this->errors[] = sprintf(GetMessage('KDA_IE_FILE_NOT_WRITABLE'), $fn);
		}
		if(!isset($arProfile) || !is_array($arProfile))
		{
			$arProfile = array();
		}
		
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
		
		$arProfiles = $this->GetList();
		
		if(in_array($name, $arProfiles))
		{
			$APPLICATION->throwException(GetMessage("KDA_IE_PROFILE_NAME_EXISTS"));
			return false;
		}
		
		$arProfiles[] = $name;
		file_put_contents($this->fileProfiles, serialize($arProfiles));
		
		$ID = array_search($name, $arProfiles);
		
		return $ID;
	}
	
	public function Update($ID, $settigs_default, $settings)
	{
		$arProfile = $this->GetByID($ID);
		if(is_array($settigs_default))
		{
			$arProfile['SETTINGS_DEFAULT'] = $settigs_default;
		}
		if(is_array($settings))
		{
			$arProfile['SETTINGS'] = $settings;
		}
		$fn = $this->pathProfiles.'/'.$ID.'.txt';
		file_put_contents($fn, serialize($arProfile));
	}
	
	public function UpdateExtra($ID, $extrasettings)
	{
		$arProfile = $this->GetByID($ID);
		if(!is_array($extrasettings)) $extrasettings = array();
		$arProfile['EXTRASETTINGS'] = $extrasettings;
		$fn = $this->pathProfiles.'/'.$ID.'.txt';
		file_put_contents($fn, serialize($arProfile));
	}
	
	public function Delete($ID)
	{
		$arProfiles = $this->GetList();
		unset($arProfiles[$ID]);
		file_put_contents($this->fileProfiles, serialize($arProfiles));
		
		$fn = $this->pathProfiles.'/'.$ID.'.txt';
		unlink($fn);
	}
	
	public function Copy($ID)
	{
		$arProfiles = $this->GetList();
		$newId = $this->Add($arProfiles[$ID].GetMessage("KDA_IE_PROFILE_COPY"));
		$fn = $this->pathProfiles.'/'.$newId.'.txt';
		copy($this->pathProfiles.'/'.$ID.'.txt', $fn);
		$arParams = unserialize(file_get_contents($fn));
		if($arParams['SETTINGS_DEFAULT']['DATA_FILE'])
		{
			$arParams['SETTINGS_DEFAULT']['DATA_FILE'] = CFile::CopyFile($arParams['SETTINGS_DEFAULT']['DATA_FILE']);
			$arFile = CFile::GetFileArray($arParams['SETTINGS_DEFAULT']['DATA_FILE']);
			$arParams['SETTINGS_DEFAULT']['URL_DATA_FILE'] = $arFile['SRC'];
			file_put_contents($fn, serialize($arParams));
		}
		return $newId;
	}
	
	public function Rename($ID, $name)
	{
		$arProfiles = $this->GetList();
		$arProfiles[$ID] = $name;
		file_put_contents($this->fileProfiles, serialize($arProfiles));
	}
	
	public function ApplyToLists($ID, $listFrom, $listTo)
	{
		if(!is_numeric($listFrom) || !is_array($listTo) || count($listTo)==0) return;
		$listTo = preg_grep('/^\d+$/', $listTo);
		if(count($listTo)==0) return;
		
		$fn = $this->pathProfiles.'/'.$ID.'.txt';
		$arParams = unserialize(file_get_contents($fn));
		foreach($listTo as $key)
		{
			$arParams['SETTINGS']['FIELDS_LIST'][$key] = $arParams['SETTINGS']['FIELDS_LIST'][$listFrom];
			$arParams['EXTRASETTINGS'][$key] = $arParams['EXTRASETTINGS'][$listFrom];
		}
		file_put_contents($fn, serialize($arParams));
	}
	
	public function GetProcessedProfiles()
	{
		$arProfiles = $this->GetList();
		foreach($arProfiles as $k=>$v)
		{
			$tmpfile = $this->tmpdir.$k.($this->suffix ? '_'.$this->suffix : '').'.txt';
			if(!file_exists($tmpfile) || filesize($tmpfile)>10*1024 || (time() - filemtime($tmpfile) < 4*60) || filemtime($tmpfile) < mktime(0, 0, 0, 12, 24, 2015))
			{
				unset($arProfiles[$k]);
				continue;
			}
			
			$arParams = CUtil::JsObjectToPhp(file_get_contents($tmpfile));
			$percent = round(((int)$arParams['total_read_line'] / (int)$arParams['total_file_line']) * 100);
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
			$arParams = CUtil::JsObjectToPhp(file_get_contents($tmpfile));
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
			$arParams = CUtil::JsObjectToPhp(file_get_contents($tmpfile));
			$paramFile = $arParams['tmpdir'].'params.txt';
			$arParams = unserialize(file_get_contents($paramFile));
			return $arParams;
		}
		return false;
	}
	
	public function GetProccessParamsFromPidFile($id)
	{
		$tmpfile = $this->tmpdir.$id.'.txt';
		if(file_exists($tmpfile))
		{
			if(time() - filemtime($tmpfile) < 3*60)
			{
				return false;
			}
			$arParams = CUtil::JsObjectToPhp(file_get_contents($tmpfile));
			return $arParams;
		}
		return array();
	}
}