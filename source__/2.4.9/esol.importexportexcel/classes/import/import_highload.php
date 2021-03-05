<?php
require_once(dirname(__FILE__).'/../../lib/PHPExcel/PHPExcel.php');
require_once(dirname(__FILE__).'/import.php');
IncludeModuleLangFile(__FILE__);

class CKDAImportExcelHighload {
	protected static $moduleId = 'esol.importexportexcel';
	protected static $moduleSubDir = 'import/';
	var $rcurrencies = array('#USD#', '#EUR#');
	var $extraConvParams = array();
	
	function __construct($filename, $params, $fparams, $stepparams, $pid = false)
	{
		$this->filename = $_SERVER['DOCUMENT_ROOT'].$filename;
		$this->params = $params;
		$this->fparams = $fparams;
		$this->maxReadRows = 500;
		$this->sections = array();
		$this->propVals = array();
		$this->hlbl = array();
		$this->errors = array();
		$this->breakWorksheet = false;
		$this->fl = new CKDAFieldList();
		$this->stepparams = $stepparams;
		$this->stepparams['total_read_line'] = intval($this->stepparams['total_read_line']);
		$this->stepparams['total_line'] = intval($this->stepparams['total_line']);
		$this->stepparams['correct_line'] = intval($this->stepparams['correct_line']);
		$this->stepparams['error_line'] = intval($this->stepparams['error_line']);
		$this->stepparams['killed_line'] = intval($this->stepparams['killed_line']);
		$this->stepparams['element_added_line'] = intval($this->stepparams['element_added_line']);
		$this->stepparams['element_updated_line'] = intval($this->stepparams['element_updated_line']);
		$this->stepparams['element_removed_line'] = intval($this->stepparams['element_removed_line']);
		$this->stepparams['sku_added_line'] = intval($this->stepparams['sku_added_line']);
		$this->stepparams['sku_updated_line'] = intval($this->stepparams['sku_updated_line']);
		$this->stepparams['section_added_line'] = intval($this->stepparams['section_added_line']);
		$this->stepparams['section_updated_line'] = intval($this->stepparams['section_updated_line']);
		$this->stepparams['zero_stock_line'] = intval($this->stepparams['zero_stock_line']);
		$this->stepparams['worksheetCurrentRow'] = intval($this->stepparams['worksheetCurrentRow']);
		$this->stepparams['total_file_line'] = 0;
		if(is_array($this->params['LIST_LINES']))
		{
			foreach($this->params['LIST_ACTIVE'] as $k=>$v)
			{
				if($v=='Y')
				{
					$this->stepparams['total_file_line'] += $this->params['LIST_LINES'][$k];
				}
			}
		}
		if(!$this->params['SECTION_UID']) $this->params['SECTION_UID'] = 'NAME';
		
		$this->cloud = new \Bitrix\KdaImportexcel\Cloud();
		
		$this->SetZipClass();
		
		/*Temp folders*/
		$this->filecnt = 0;
		$dir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		CheckDirPath($dir);
		if(!$this->stepparams['tmpdir'])
		{
			$i = 0;
			while(($tmpdir = $dir.$i.'/') && file_exists($tmpdir)){$i++;}
			$this->stepparams['tmpdir'] = $tmpdir;
			CheckDirPath($tmpdir);
		}
		$this->tmpdir = $this->stepparams['tmpdir'];
		$this->imagedir = $this->stepparams['tmpdir'].'images/';
		CheckDirPath($this->imagedir);
		
		$this->tmpfile = $this->tmpdir.'params.txt';
		$oProfile = CKDAImportProfile::getInstance();
		$oProfile->SetImportParams($pid, $this->tmpdir, $stepparams);
		/*/Temp folders*/
		
		if(file_exists($this->tmpfile))
		{
			$this->stepparams = array_merge($this->stepparams, unserialize(file_get_contents($this->tmpfile)));
		}
		
		if(!isset($this->stepparams['curstep'])) $this->stepparams['curstep'] = 'import';
		
		if(!isset($this->params['MAX_EXECUTION_TIME']) || $this->params['MAX_EXECUTION_TIME']!==0)
		{
			if(COption::GetOptionString(static::$moduleId, 'SET_MAX_EXECUTION_TIME')=='Y' && is_numeric(COption::GetOptionString(static::$moduleId, 'MAX_EXECUTION_TIME')))
			{
				$this->params['MAX_EXECUTION_TIME'] = intval(COption::GetOptionString(static::$moduleId, 'MAX_EXECUTION_TIME'));
				if(ini_get('max_execution_time') && $this->params['MAX_EXECUTION_TIME'] > ini_get('max_execution_time') - 5) $this->params['MAX_EXECUTION_TIME'] = ini_get('max_execution_time') - 5;
				if($this->params['MAX_EXECUTION_TIME'] < 5) $this->params['MAX_EXECUTION_TIME'] = 5;
				if($this->params['MAX_EXECUTION_TIME'] > 300) $this->params['MAX_EXECUTION_TIME'] = 300;
			}
			else
			{
				$this->params['MAX_EXECUTION_TIME'] = intval(ini_get('max_execution_time')) - 10;
				if($this->params['MAX_EXECUTION_TIME'] < 10) $this->params['MAX_EXECUTION_TIME'] = 15;
				if($this->params['MAX_EXECUTION_TIME'] > 50) $this->params['MAX_EXECUTION_TIME'] = 50;
			}
		}		
		
		if($pid!==false)
		{
			$this->procfile = $dir.$pid.'_highload.txt';
			$this->errorfile = $dir.$pid.'_highload_error.txt';
			if($this->stepparams['total_line'] < 1)
			{
				$oProfile = CKDAImportProfile::getInstance();
				$oProfile->OnStartImport();
				
				if(file_exists($this->procfile)) unlink($this->procfile);
				if(file_exists($this->errorfile)) unlink($this->errorfile);
			}
			$this->pid = $pid;
		}
	}
	
	public function SetZipClass()
	{
		if($this->params['OPTIMIZE_RAM']!='Y' && !isset($this->stepparams['optimizeRam']))
		{
			$this->stepparams['optimizeRam'] = 'N';
			$origFileSize = filesize($this->filename);
			if((class_exists('XMLReader') && $origFileSize > 2*1024*1024) && ToLower(CKDAImportUtils::GetFileExtension($this->filename))=='xlsx')
			{
				$timeBegin = microtime(true);
				$needSize = $origFileSize*10;
				$tempPath = \CFile::GetTempName('', 'test_size.txt');
				CheckDirPath($tempPath);

				$fileSize = 0;
				$handle = fopen($tempPath, 'a');
				while($fileSize < $needSize && microtime(true) - $timeBegin < 3)
				{
					$partSize = min(5*1024*1024, $needSize - $fileSize);
					fwrite($handle, str_repeat('0', $partSize));
					$fileSize += $partSize;
				}
				fclose($handle);
				if($fileSize <= filesize($tempPath))
				{
					$this->stepparams['optimizeRam'] = 'Y';
				}
				unlink($tempPath);
				$dir = dirname($tempPath);
				if(count(array_diff(scandir($dir), array('.', '..')))==0)
				{
					rmdir($dir);
				}
			}
		}
		if($this->params['OPTIMIZE_RAM']=='Y' || $this->stepparams['optimizeRam']=='Y')
		{
			KDAPHPExcel_Settings::setZipClass(KDAPHPExcel_Settings::KDAIEZIPARCHIVE);
		}
	}
	
	public function CheckTimeEnding($time)
	{
		return ($this->params['MAX_EXECUTION_TIME'] && (time()-$time >= $this->params['MAX_EXECUTION_TIME']));
	}
	
	public function Import()
	{
		register_shutdown_function(array($this, 'OnShutdown'));
		set_error_handler(array($this, "HandleError"));
		set_exception_handler(array($this, "HandleException"));
		
		$time = time();
		if($this->stepparams['curstep'] == 'import')
		{
			$this->InitImport();
			while($arItem = $this->GetNextRecord($time))
			{
				if(is_array($arItem)) $this->SaveRecord($arItem);
				if($this->CheckTimeEnding($time))
				{
					return $this->GetBreakParams();
				}
			}
			if($this->CheckTimeEnding($time)) return $this->GetBreakParams();
		}
		
		return $this->EndOfLoading($time);
	}
	
	public function EndOfLoading($time)
	{
		if($this->params['ELEMENT_MISSING_REMOVE_ELEMENT']=='Y')
		{
			if($this->stepparams['curstep'] == 'import' || $this->stepparams['curstep'] == 'import_end')
			{
				$this->stepparams['curstep'] = 'deactivate_elements';				
				$oProfile = CKDAImportProfile::getInstance();
				$this->stepparams['deactivate_element_last'] = $oProfile->GetLastImportId('E');
				$this->stepparams['deactivate_element_first'] = 0;
				$this->SaveStatusImport();
				if($this->CheckTimeEnding($time)) return $this->GetBreakParams();
			}
			
			$HIGHLOADBLOCK_ID = $this->params['HIGHLOADBLOCK_ID'];
			$entityDataClass = $this->GetHighloadBlockClass($HIGHLOADBLOCK_ID);
		
			while($this->stepparams['deactivate_element_first'] < $this->stepparams['deactivate_element_last'])
			{
				$oProfile = CKDAImportProfile::getInstance();
				$arUpdatedIds = $oProfile->GetUpdatedIds('E', $this->stepparams['deactivate_element_first']);
				if(empty($arUpdatedIds))
				{
					$this->stepparams['deactivate_element_first'] = $this->stepparams['deactivate_element_last'];
					continue;
				}
				$lastElement = end($arUpdatedIds);
				$arFields = array();
				CKDAImportUtils::AddFilterHighload($arFields, $this->params['ELEMENT_MISSING_FILTER'], $HIGHLOADBLOCK_ID);
				$arFields['!ID'] = $arUpdatedIds;
				if($this->stepparams['deactivate_element_first'] > 0) $arFields['>ID'] = $this->stepparams['deactivate_element_first'];
				if($lastElement < $this->stepparams['deactivate_element_last']) $arFields['<=ID'] = $lastElement;
				
				$dbRes = $entityDataClass::getList(array('filter'=>$arFields, 'order'=>array('ID'=>'ASC'), 'select'=>array('ID')));
				while($arElement = $dbRes->Fetch())
				{
					if($this->params['ELEMENT_MISSING_REMOVE_ELEMENT']=='Y')
					{
						$entityDataClass::delete($arElement['ID']);
						$this->stepparams['element_removed_line']++;
						continue;
					}
				}				
				$this->stepparams['deactivate_element_first'] = $lastElement;
			}
		}
		
		$this->SaveStatusImport(true);

		$oProfile = CKDAImportProfile::getInstance();
		$arEventData = $oProfile->OnEndImport($this->filename, $this->stepparams);
		
		foreach(GetModuleEvents(static::$moduleId, "OnEndImport", true) as $arEvent)
		{
			$bEventRes = ExecuteModuleEventEx($arEvent, array('H'.$this->pid, array()));
			if($bEventRes['ACTION']=='REDIRECT')
			{
				$this->stepparams['redirect_url'] = $bEventRes['LOCATION'];
			}
		}
		\Bitrix\KdaImportexcel\ZipArchive::RemoveFileDir($this->filename);
		
		return $this->GetBreakParams('finish');
	}
	
	public function InitImport()
	{
		$this->objReader = KDAPHPExcel_IOFactory::createReaderForFile($this->filename);
		$this->worksheetNames = array();
		if(is_callable(array($this->objReader, 'listWorksheetNames')))
		{
			$this->worksheetNames = $this->objReader->listWorksheetNames($this->filename);
		}		
		if($this->params['ELEMENT_NOT_LOAD_STYLES']=='Y' && $this->params['ELEMENT_NOT_LOAD_FORMATTING']=='Y')
		{
			$this->objReader->setReadDataOnly(true);
		}
		if(isset($this->params['CSV_PARAMS']))
		{
			$this->objReader->setCsvParams($this->params['CSV_PARAMS']);
		}
		$this->chunkFilter = new KDAChunkReadFilter();
		$this->objReader->setReadFilter($this->chunkFilter);
		
		$this->worksheetNum = (isset($this->stepparams['worksheetNum']) ? intval($this->stepparams['worksheetNum']) : 0);
		$this->worksheetCurrentRow = intval($this->stepparams['worksheetCurrentRow']);
		$this->GetNextWorksheetNum();
	}
	
	public function GetBreakParams($action = 'continue')
	{
		$arStepParams = array(
			'params'=> array_merge($this->stepparams, array(
				'worksheetNum' => intval($this->worksheetNum),
				'worksheetCurrentRow' => $this->worksheetCurrentRow
			)),
			'action' => $action,
			'errors' => $this->errors,
			'sessid' => bitrix_sessid()
		);
		
		if($action == 'continue')
		{
			file_put_contents($this->tmpfile, serialize($arStepParams['params']));
			if(file_exists($this->imagedir))
			{
				DeleteDirFilesEx(substr($this->imagedir, strlen($_SERVER['DOCUMENT_ROOT'])));
			}
		}
		elseif(file_exists($this->tmpdir))
		{
			DeleteDirFilesEx(substr($this->tmpdir, strlen($_SERVER['DOCUMENT_ROOT'])));
			unlink($this->procfile);
		}
		
		return $arStepParams;
	}
	
	public function SetWorksheet($worksheetNum, $worksheetCurrentRow)
	{
		$this->chunkFilter->setRows($worksheetCurrentRow, $this->maxReadRows);
		if($this->efile) $this->efile->__destruct();
		if($this->worksheetNames[$worksheetNum]) $this->objReader->setLoadSheetsOnly($this->worksheetNames[$worksheetNum]);
		if($this->stepparams['csv_position'] && is_callable(array($this->objReader, 'setStartFilePosRow')))
		{
			$this->objReader->setStartFilePosRow($this->stepparams['csv_position']);
		}
		$this->efile = $this->objReader->load($this->filename);
		$this->worksheetIterator = $this->efile->getWorksheetIterator();
		$this->worksheet = $this->worksheetIterator->current();

		$filedList = $this->params['FIELDS_LIST'][$this->worksheetNum];
		$iblockId = $this->params['IBLOCK_ID'][$this->worksheetNum];
		if(((is_array($this->params['ELEMENT_UID']) && count(array_diff($this->params['ELEMENT_UID'], $filedList)) > 0)
			|| (!is_array($this->params['ELEMENT_UID']) && !in_array($this->params['ELEMENT_UID'], $filedList)))
			&& (!$this->params['SECTION_UID'] || count(preg_grep('/^ISECT\d+_'.$this->params['SECTION_UID'].'$/', $filedList))==0))
		{
			if($this->worksheet->getHighestDataRow() > 0)
			{
				$nofields = (is_array($this->params['ELEMENT_UID']) ? array_diff($this->params['ELEMENT_UID'], $filedList) : array($this->params['ELEMENT_UID']));
				$fieldNames = $this->fl->GetHigloadBlockFields($iblockId);
				foreach($nofields as $k=>$field)
				{
					$nofields[$k] = '"'.$fieldNames[$field]['NAME_LANG'].'"';
				}
				$nofields = implode(', ', $nofields);
				$this->errors[] = sprintf(GetMessage("KDA_IE_NOT_SET_UID"), $this->worksheetNum+1, $nofields);
			}
			if(!$this->GetNextWorksheetNum(true))
			{
				$this->worksheet = false;
				return false;
			}
			$pos = $this->GetNextLoadRow(1, $this->worksheetNum);
			$this->SetWorksheet($this->worksheetNum, $pos);
			return;
		}
		
		$this->fieldSettings = array();
		$this->fieldSettingsExtra = array();
		$this->fieldOnlyNew = array();
		$this->fieldOnlyNewOffer = array();
		$this->fieldsForSkuGen = array();
		foreach($filedList as $k=>$field)
		{
			$this->fieldSettings[$field] = $this->fparams[$this->worksheetNum][$k];
			if(strpos($field, '|')!==false) $this->fieldSettings[substr($field, 0, strpos($field, '|'))] = $this->fparams[$this->worksheetNum][$k];
			$this->fieldSettingsExtra[$k] = $this->fparams[$this->worksheetNum][$k];
			if($this->fieldSettings[$field]['SET_NEW_ONLY']=='Y')
			{
				if(strpos($field, 'OFFER_')===0) $this->fieldOnlyNewOffer[] = substr($field, 6);
				else $this->fieldOnlyNew[] = $field;
			}
			if(strpos($field, 'OFFER_')===0 && $this->fieldSettings[$field]['USE_FOR_SKU_GENERATE']=='Y')
			{
				$this->fieldsForSkuGen[] = $k;
			}
		}
		
		if(!isset($this->params['ELEMENT_NOT_LOAD_STYLES_ORIG']))
		{
			$this->params['ELEMENT_NOT_LOAD_STYLES_ORIG'] = $this->params['ELEMENT_NOT_LOAD_STYLES'];
		}
		else
		{
			$this->params['ELEMENT_NOT_LOAD_STYLES'] = $this->params['ELEMENT_NOT_LOAD_STYLES_ORIG'];
		}
		$this->sectionstyles = array();
		if($this->params['ELEMENT_NOT_LOAD_STYLES']!='Y')
		{
			if(is_array($this->params['LIST_SETTINGS'][$this->worksheetNum]))
			{
				foreach($this->params['LIST_SETTINGS'][$this->worksheetNum] as $k2=>$v2)
				{
					if(strpos($k2, 'SET_SECTION_')===0)
					{
						$this->sectionstyles[md5($v2)] = intval(substr($k2, 12));
					}
				}
			}
			if(empty($this->sectionstyles)) $this->params['ELEMENT_NOT_LOAD_STYLES'] = 'Y';
		}
		
		$this->sectioncolumn = false;
		if(isset($this->params['LIST_SETTINGS'][$this->worksheetNum]['SECTION_NAME_CELL']))
		{
			$this->sectioncolumn = (int)$this->params['LIST_SETTINGS'][$this->worksheetNum]['SECTION_NAME_CELL'] - 1;
		}

		$this->draws = array();
		if($this->params['ELEMENT_LOAD_IMAGES']=='Y')
		{
			$drawCollection = $this->worksheet->getDrawingCollection();
			if($drawCollection)
			{
				foreach($drawCollection as $drawItem)
				{
					if(is_callable(array($drawItem, 'getPath')))
					{
						$this->draws[$drawItem->getCoordinates()] = $drawItem->getPath();
					}
				}
			}
		}
		
		$this->useHyperlinks = false;
		foreach($this->fieldSettingsExtra as $k=>$v)
		{
			if(is_array($v['CONVERSION']))
			{
				foreach($v['CONVERSION'] as $k2=>$v2)
				{
					if(strpos($v2['TO'], '#CLINK#')!==false)
					{
						$this->useHyperlinks = true;
					}
				}
			}
		}
		
		$this->worksheetColumns = KDAPHPExcel_Cell::columnIndexFromString($this->worksheet->getHighestDataColumn());
		$this->worksheetRows = min($this->maxReadRows, $this->worksheet->getHighestDataRow());
		$this->worksheetCurrentRow = $worksheetCurrentRow;
		if($this->worksheet)
		{
			$this->worksheetRows = min($worksheetCurrentRow+$this->maxReadRows, $this->worksheet->getHighestDataRow());
		}
	}
	
	public function SetFilePosition($pos)
	{
		if($this->breakWorksheet)
		{
			$this->breakWorksheet = false;
			if(!$this->GetNextWorksheetNum(true)) return false;
			$pos = $this->GetNextLoadRow(1, $this->worksheetNum);
			$this->SetWorksheet($this->worksheetNum, $pos);
		}
		else
		{
			$pos = $this->GetNextLoadRow($pos, $this->worksheetNum);
			$this->worksheetCurrentRow = $pos;
			if(($this->worksheetCurrentRow >= $this->worksheetRows) || !$this->worksheet)
			{
				if(!$this->GetNextWorksheetNum()) return false;
				$this->SetWorksheet($this->worksheetNum, $pos);
				if($this->worksheetCurrentRow > $this->worksheetRows)
				{
					if(!$this->GetNextWorksheetNum(true)) return false;
					$pos = $this->GetNextLoadRow(1, $this->worksheetNum);
					$this->SetWorksheet($this->worksheetNum, $pos);
				}
				$this->SaveStatusImport();
			}
		}
		$this->stepparams['csv_position'] = $this->chunkFilter->getFilePosRow($this->worksheetCurrentRow);
	}
	
	public function GetNextWorksheetNum($inc = false)
	{
		if($inc) $this->worksheetNum++;
		$arLists = $this->params['LIST_ACTIVE'];
		while(isset($arLists[$this->worksheetNum]) && $arLists[$this->worksheetNum]!='Y')
		{
			$this->worksheetNum++;
		}
		if(!isset($arLists[$this->worksheetNum]))
		{
			$this->worksheet = false;
			return false;
		}
		return true;
	}
	
	public function CheckSkipLine($currentRow, $worksheetNum, $checkValue = true)
	{
		$load = true;
		
		if($this->breakWorksheet ||
			(!$this->params['CHECK_ALL'][$worksheetNum] && !isset($this->params['IMPORT_LINE'][$worksheetNum][$currentRow - 1])) || 
			(isset($this->params['IMPORT_LINE'][$worksheetNum][$currentRow - 1]) && !$this->params['IMPORT_LINE'][$worksheetNum][$currentRow - 1]))
		{
			$load = false;
		}
				
		if($load && !empty($this->params['ADDITIONAL_SETTINGS'][$worksheetNum]['LOADING_RANGE']))
		{
			$load = false;
			$arRanges = $this->params['ADDITIONAL_SETTINGS'][$worksheetNum]['LOADING_RANGE'];
			foreach($arRanges as $k=>$v)
			{
				$row = $currentRow;
				if(($v['FROM'] || $v['TO']) && ($row >= $v['FROM'] || !$v['FROM']) && ($row <= $v['TO'] || !$v['TO']))
				{
					$load = true;
				}
			}
		}
		
		if($load && $checkValue && is_array($this->fparams[$worksheetNum]))
		{
			foreach($this->fparams[$worksheetNum] as $k=>$v)
			{
				if(!is_array($v)) continue;
				if(is_array($v['UPLOAD_VALUES']) || is_array($v['NOT_UPLOAD_VALUES']) || $v['FILTER_EXPRESSION'])
				{
					$val = $this->worksheet->getCellByColumnAndRow($k, $currentRow);
					$valOrig = $this->GetCalculatedValue($val);
					$val = $this->ApplyConversions($valOrig, $v['CONVERSION'], array());
					$val = ToLower(trim($val));
				}
				else
				{
					$val = '';
				}
				
				if(is_array($v['UPLOAD_VALUES']))
				{
					$subload = false;
					foreach($v['UPLOAD_VALUES'] as $needval)
					{
						if(ToLower(trim($needval))==$val)
						{
							$subload = true;
						}
					}
					$load = ($load && $subload);
				}
				
				if(is_array($v['NOT_UPLOAD_VALUES']))
				{
					$subload = true;
					foreach($v['NOT_UPLOAD_VALUES'] as $needval)
					{
						if(ToLower(trim($needval))==$val)
						{
							$subload = false;
						}
					}
					$load = ($load && $subload);
				}
				
				if($v['FILTER_EXPRESSION'])
				{
					$load = ($load && $this->ExecuteFilterExpression($valOrig, $v['FILTER_EXPRESSION']));
				}
			}
		}
		if(!$load && isset($this->stepparams['currentelement']))
		{
			unset($this->stepparams['currentelement']);
		}
		return !$load;
	}
	
	public function ExecuteFilterExpression($val, $expression, $altReturn = true)
	{
		$expression = trim($expression);
		try{				
			if(stripos($expression, 'return')===0)
			{
				return eval($expression.';');
			}
			elseif(preg_match('/\$val\s*=/', $expression))
			{
				eval($expression.';');
				return $val;
			}
			else
			{
				return eval('return '.$expression.';');
			}
		}catch(Exception $ex){
			return $altReturn;
		}
	}
	
	public function ExecuteOnAfterSaveHandler($handler, $ID)
	{
		try{				
			eval($handler.';');
		}catch(Exception $ex){}
	}
	
	public function GetNextLoadRow($row, $worksheetNum)
	{
		$nextRow = $row;
		if(isset($this->params['LIST_ACTIVE'][$worksheetNum]))
		{
			while($this->CheckSkipLine($nextRow, $worksheetNum, false))
			{
				$nextRow++;
				if($nextRow - $row > 30000)
				{
					return $nextRow;
				}
			}
		}
		return $nextRow;
	}
	
	public function GetNextRecord($time)
	{
		$this->SetFilePosition($this->worksheetCurrentRow + 1);
		while($this->worksheet && $this->CheckSkipLine($this->worksheetCurrentRow, $this->worksheetNum))
		{
			if($this->CheckTimeEnding($time)) return false;
			$this->SetFilePosition($this->worksheetCurrentRow + 1);
		}

		if(!$this->worksheet)
		{
			return false;
		}
		
		$arItem = array();
		$this->hyperlinks = array();
		for($column = 0; $column < $this->worksheetColumns; $column++) 
		{
			$val = $this->worksheet->getCellByColumnAndRow($column, $this->worksheetCurrentRow);
			$valText = $this->GetCalculatedValue($val);			
			$arItem[$column] = trim($valText);
			$arItem['~'.$column] = $valText;
			if($this->params['ELEMENT_NOT_LOAD_STYLES']!='Y' && !isset($arItem['STYLE']) && strlen(trim($valText))>0)
			{
				$arItem['STYLE'] = md5(CUtil::PhpToJSObject(self::GetCellStyle($val)));
			}
			if($this->params['ELEMENT_LOAD_IMAGES']=='Y')
			{
				if($this->draws[$val->getCoordinate()])
				{
					$arItem[$column] = $this->draws[$val->getCoordinate()];
					$arItem['~'.$column] = $this->draws[$val->getCoordinate()];
				}
			}
			if($this->useHyperlinks)
			{
				$this->hyperlinks[$column] = $val->getHyperlink()->getUrl();
			}
		}

		$this->worksheetNumForSave = $this->worksheetNum;
		return $arItem;
	}
	
	public function SaveRecord($arItem)
	{
		$this->stepparams['total_read_line']++;
		if(count(array_diff(array_map('trim', $arItem), array('')))==0)
		{
			if($this->params['ADDITIONAL_SETTINGS'][$this->worksheetNum]['BREAK_LOADING']=='Y')
			{
				$this->breakWorksheet = true;
			}
			return false;
		}
		$this->stepparams['total_line']++;
		
		$filedList = $this->params['FIELDS_LIST'][$this->worksheetNumForSave];
		$HIGHLOADBLOCK_ID = $this->params['HIGHLOADBLOCK_ID'];
		$entityDataClass = $this->GetHighloadBlockClass($HIGHLOADBLOCK_ID);
		
		$iblockFields = $this->fl->GetHigloadBlockFields($HIGHLOADBLOCK_ID);
		$arFieldsElement = array();
		$arFieldsElementOrig = array();
		foreach($filedList as $key=>$field)
		{
			$k = $key;
			if(strpos($k, '_')!==false) $k = substr($k, 0, strpos($k, '_'));
			$value = $arItem[$k];
			$origValue = $arItem['~'.$k];
			if(!isset($iblockFields[$field])) continue;
			
			$fieldSettingsExtra = (isset($this->fieldSettingsExtra[$key]) ? $this->fieldSettingsExtra[$key] : $this->fieldSettings[$field]);
			$conversions = $fieldSettingsExtra['CONVERSION'];
			if(!empty($conversions))
			{
				$value = $this->ApplyConversions($value, $conversions, $arItem, array('KEY'=>$k, 'NAME'=>$field));
				$origValue = $this->ApplyConversions($origValue, $conversions, $arItem, array('KEY'=>$k, 'NAME'=>$field));
				if($value===false) continue;
			}
			
			$this->GetHLField($arFieldsElement, $arFieldsElementOrig, $fieldSettingsExtra, $iblockFields[$field], $field, $value, $origValue);
			
			/*if($iblockFields[$field]['MULTIPLE']=='Y' && isset($arFieldsElement[$field]))
			{
				if(!is_array($arFieldsElement[$field]))
				{
					$arFieldsElement[$field] = array($arFieldsElement[$field]);
					$arFieldsElementOrig[$field] = array($arFieldsElementOrig[$field]);
				}
				$arFieldsElement[$field][] = $value;
				$arFieldsElementOrig[$field][] = $origValue;
			}
			else
			{
				$arFieldsElement[$field] = $value;
				$arFieldsElementOrig[$field] = $origValue;
			}*/
		}

		$arUid = array();
		if(!is_array($this->params['ELEMENT_UID'])) $this->params['ELEMENT_UID'] = array($this->params['ELEMENT_UID']);
		foreach($this->params['ELEMENT_UID'] as $tuid)
		{
			$uid = $valUid = $nameUid = '';
			$canSubstring = true;
			
			
			$uid = $tuid;
			$nameUid = $iblockFields[$tuid]['NAME_LANG'];
			$valUid = $arFieldsElementOrig[$uid];
			
			if($iblockFields[$uid]['USER_TYPE_ID']=='hlblock')
			{
				$valUid = $this->GetHighloadBlockValue($iblockFields[$uid], $valUid);
				$canSubstring = false;
			}
			elseif($iblockFields[$uid]['USER_TYPE_ID']=='iblock_element')
			{
				$valUid = $this->GetIblockElementValue($iblockFields[$uid], $valUid, $this->fieldSettings[$tuid]);
				$canSubstring = false;
			}
			elseif($iblockFields[$uid]['USER_TYPE_ID']=='enumeration')
			{
				$valUid = $this->GetHighloadBlockEnum($iblockFields[$uid], $valUid);
				$canSubstring = false;
			}

			if($uid)
			{
				$arUid[] = array(
					'uid' => $uid,
					'nameUid' => $nameUid,
					'valUid' => $valUid,
					'substring' => ($this->fieldSettings[$tuid]['UID_SEARCH_SUBSTRING']=='Y' && $canSubstring)
				);
			}
		}

		$emptyFields = array();
		foreach($arUid as $k=>$v)
		{
			if(!trim($v['valUid'])) $emptyFields[] = $v['nameUid'];
		}
		
		if(!empty($emptyFields) || empty($arUid))
		{
			$this->errors[] = sprintf(GetMessage("KDA_IE_NOT_SET_FIELD"), implode(', ', $emptyFields), $this->worksheetNumForSave+1, $this->worksheetCurrentRow);
			$this->stepparams['error_line']++;
			return false;
		}
		
		foreach($arFieldsElement as $k=>$v)
		{
			if($iblockFields[$k]['MULTIPLE']=='Y')
			{
				if(!is_array($v))
				{
					$separator = $this->params['ELEMENT_MULTIPLE_SEPARATOR'];
					if($this->fieldSettings[$k]['CHANGE_MULTIPLE_SEPARATOR']=='Y')
					{
						$separator = $this->fieldSettings[$k]['MULTIPLE_SEPARATOR'];
					}
					$v = explode($separator, $v);
				}
				$arFieldsElement[$k] = array();
				foreach($v as $v2)
				{
					$arFieldsElement[$k][] = $this->GetElementFieldValue($v2, $iblockFields[$k], $k);
				}
			}
			else
			{
				$arFieldsElement[$k] = $this->GetElementFieldValue($v, $iblockFields[$k], $k);
			}
		}
		
		$arKeys = array_merge(array('ID'), array_keys($arFieldsElement));
		
		$arFilter = array();
		foreach($arUid as $v)
		{
			if(!$v['substring'])
			{
				if(strlen($v['valUid']) != strlen(trim($v['valUid'])))
				{
					$arFilter[] = array('LOGIC'=>'OR', array($v['uid']=>trim($v['valUid'])), array($v['uid']=>$v['valUid']));
				}
				else
				{
					$arFilter[$v['uid']] = trim($v['valUid']);
				}
			}
			else
			{
				$arFilter['%'.$v['uid']] = trim($v['valUid']);
			}
		}

		$dbRes = $entityDataClass::GetList(array('filter'=>$arFilter, 'select'=>$arKeys));
		while($arElement = $dbRes->Fetch())
		{
			$ID = $arElement['ID'];
			if($this->params['ONLY_DELETE_MODE']=='Y')
			{
				$entityDataClass::delete($ID);
				$this->stepparams['element_removed_line']++;
				unset($ID);
				continue;
			}
			
			$updated = false;
			if($this->params['ONLY_CREATE_MODE']!='Y')
			{
				foreach($arElement as $k=>$v)
				{
					$action = $this->fieldSettings['IE_'.$k]['LOADING_MODE'];
					if($action)
					{
						if($action=='ADD_BEFORE') $arFieldsElement[$k] = $arFieldsElement[$k].$v;
						elseif($action=='ADD_AFTER') $arFieldsElement[$k] = $v.$arFieldsElement[$k];
					}
				}

				/*Delete old files*/
				foreach($arFieldsElement as $k=>$v)
				{
					if($iblockFields[$k]['USER_TYPE_ID']=='file' && $arElement[$k])
					{
						if(!is_array($arFieldsElement[$k])) $arFieldsElement[$k] = array('del'=>'Y', 'old_id'=>$arElement[$k]);
						elseif(isset($arFieldsElement[$k][0]))
						{
							$arFieldsElement[$k][0]['del'] = 'Y';
							$arFieldsElement[$k][0]['old_id'] = $arElement[$k];
						}
						else
						{
							$arFieldsElement[$k]['del'] = 'Y';
							$arFieldsElement[$k]['old_id'] = $arElement[$k];
						}
					}
				}
				/*/Delete old files*/
				
				if($this->params['ELEMENT_NOT_UPDATE_WO_CHANGES']=='Y')
				{
					/*Delete unchanged data*/
					foreach($arFieldsElement as $k=>$v)
					{
						if($v==$arElement[$k])
						{
							unset($arFieldsElement[$k]);
						}
					}
					/*/Delete unchanged data*/
				}
				
				if(!empty($this->fieldOnlyNew))
				{
					$this->UnsetExcessFields($this->fieldOnlyNew, $arFieldsElement);
				}
				
				if(!empty($arFieldsElement))
				{
					$dbRes2 = $entityDataClass::Update($ID, $arFieldsElement);
					if($dbRes2->isSuccess())
					{
						//$this->SetTimeBegin($ID);
					}
					else
					{
						$this->stepparams['error_line']++;
						$this->errors[] = sprintf(GetMessage("KDA_IE_UPDATE_ELEMENT_ERROR"), implode(', ',$dbRes2->GetErrorMessages()), $this->worksheetNumForSave+1, $this->worksheetCurrentRow);
					}
				}
				
				$updated = true;
			}
			if($this->SaveElementId($ID) && $updated)
			{
				$this->stepparams['element_updated_line']++;
			}
			$this->SaveElementId($ID);
		}
		
		if($dbRes->getSelectedRowsCount()==0 && $this->params['ONLY_UPDATE_MODE']!='Y' && $this->params['ONLY_DELETE_MODE']!='Y')
		{
			$dbRes2 = $entityDataClass::Add($arFieldsElement, false, true, true);
			$ID = $dbRes2->GetID();
			
			if($dbRes2->isSuccess())
			{
				$ID = $dbRes2->GetID();
				//$this->SetTimeBegin($ID);
				$this->stepparams['element_added_line']++;
				$this->SaveElementId($ID);
			}
			else
			{
				$this->stepparams['error_line']++;
				$this->errors[] = sprintf(GetMessage("KDA_IE_ADD_ELEMENT_ERROR"), implode(', ',$dbRes2->GetErrorMessages()), $this->worksheetNumForSave+1, $this->worksheetCurrentRow);
				return false;
			}
		}
		
		if($ID)
		{
			if($this->params['ONAFTERSAVE_HANDLER'])
			{
				$this->ExecuteOnAfterSaveHandler($this->params['ONAFTERSAVE_HANDLER'], $ID);
			}
		}
		
		$this->stepparams['correct_line']++;
		
		$this->SaveStatusImport();
		$this->RemoveTmpImageDirs();
	}
	
	public function GetHLField(&$arFieldsElement, &$arFieldsElementOrig, $fieldSettingsExtra, $propDef, $fieldName, $value, $origValue)
	{
		if(!isset($arFieldsElement[$fieldName])) $arFieldsElement[$fieldName] = null;
		if(!isset($arFieldsElementOrig[$fieldName])) $arFieldsElementOrig[$fieldName] = null;
		$arFieldsElementItem = &$arFieldsElement[$fieldName];
		$arFieldsElementOrigItem = &$arFieldsElementOrig[$fieldName];
		
		if($propDef	&& $propDef['USER_TYPE_ID']=='hlblock')
		{
			if($fieldSettingsExtra['HLBL_FIELD']) $key2 = $fieldSettingsExtra['HLBL_FIELD'];
			else $key2 = 'ID';
			if(!isset($arFieldsElementItem[$key2])) $arFieldsElementItem[$key2] = null;
			if(!isset($arFieldsElementOrigItem[$key2])) $arFieldsElementOrigItem[$key2] = null;
			$arFieldsElementItem = &$arFieldsElementItem[$key2];
			$arFieldsElementOrigItem = &$arFieldsElementOrigItem[$key2];
		}
		
		if($propDef['MULTIPLE']=='Y' && !is_null($arFieldsElementItem))
		{
			if(is_array($arFieldsElementItem))
			{
				$arFieldsElementItem[] = $value;
				$arFieldsElementOrigItem[] = $origValue;
			}
			else
			{
				$arFieldsElementItem = array($arFieldsElementItem, $value);
				$arFieldsElementOrigItem = array($arFieldsElementOrigItem, $origValue);
			}
		}
		else
		{
			$arFieldsElementItem = $value;
			$arFieldsElementOrigItem = $origValue;
		}
	}
	
	public function GetElementFieldValue($val, $fieldParam, $key)
	{
		$ftype = $fieldParam['USER_TYPE_ID'];
		if($ftype=='integer')
		{
			$val = $this->GetIntVal($val);
		}
		elseif($ftype=='double')
		{
			$val = $this->GetFloatVal($val);
		}
		elseif($ftype=='datetime')
		{
			$val = $this->GetDateVal($val);
		}
		elseif($ftype=='date')
		{
			$val = $this->GetDateVal($val, 'PART');
		}
		elseif($ftype=='boolean')
		{
			$val = $this->GetHLBoolValue($val);
		}
		elseif($ftype=='file')
		{
			$picSettings = array();
			if($this->fieldSettings[$key]['PICTURE_PROCESSING'])
			{
				$picSettings = $this->fieldSettings[$key]['PICTURE_PROCESSING'];
			}
			$val = $this->GetFileArray($val, $picSettings);
		}
		elseif($ftype=='enumeration')
		{
			$val = $this->GetHighloadBlockEnum($fieldParam, $val);
		}
		elseif($ftype=='hlblock')
		{
			$val = $this->GetHighloadBlockValue($fieldParam, $val);
		}
		elseif($ftype=='iblock_element')
		{
			$val = $this->GetIblockElementValue($fieldParam, $val, $this->fieldSettings[$key]);
		}
		elseif($ftype=='iblock_section')
		{
			
		}
		elseif($ftype=='crm' && CModule::IncludeModule('crm'))
		{
			if($fieldParam['SETTINGS']['COMPANY']=='Y')
			{
				if(isset($this->fieldSettings[$key]['REL_CRM_COMPANY_FIELD']) && strlen($this->fieldSettings[$key]['REL_CRM_COMPANY_FIELD']) > 0)
				{
					if($arr = \Bitrix\Crm\CompanyTable::getList(array('filter'=>array($this->fieldSettings[$key]['REL_CRM_COMPANY_FIELD']=>$val), 'select'=>array('ID'), 'order'=>array('ID'=>'ASC'), 'limit'=>1))->Fetch()) $val = $arr['ID'];
					else $val = '';
				}
			}
		}
		return $val;
	}
	
	public function GetHighloadBlockEnum($fieldParam, $val)
	{		
		if(!$this->hlblEnum) $this->hlblEnum = array();
		if(!$this->hlblEnum[$fieldParam['ID']])
		{
			$arEnumVals = array();
			$fenum = new CUserFieldEnum();
			$dbRes = $fenum->GetList(array(), array('USER_FIELD_ID'=>$fieldParam['ID']));
			while($arr = $dbRes->Fetch())
			{
				$arEnumVals[trim($arr['VALUE'])] = $arr['ID'];
			}
			$this->hlblEnum[$fieldParam['ID']] = $arEnumVals;
		}
		
		$val = trim($val);
		$arEnumVals = $this->hlblEnum[$fieldParam['ID']];
		if(!isset($arEnumVals[$val]))
		{
			$fenum = new CUserFieldEnum();
			$arEnumValsOrig = array();
			$dbRes = $fenum->GetList(array(), array('USER_FIELD_ID'=>$fieldParam['ID']));
			while($arr = $dbRes->Fetch())
			{
				$arEnumValsOrig[$arr['ID']] = $arr;
			}
			$arEnumValsOrig['n0'] = array('VALUE'=>$val);
			$fenum->SetEnumValues($fieldParam['ID'], $arEnumValsOrig);

			$arEnumVals = array();
			$dbRes = $fenum->GetList(array(), array('USER_FIELD_ID'=>$fieldParam['ID']));
			while($arr = $dbRes->Fetch())
			{
				$arEnumVals[trim($arr['VALUE'])] = $arr['ID'];
			}
			$this->hlblEnum[$fieldParam['ID']] = $arEnumVals;
		}
		return $arEnumVals[$val];
	}
	
	public function SaveStatusImport($end = false)
	{
		if($this->procfile)
		{
			$writeParams = array_merge($this->stepparams, array(
				'worksheetNum' => intval($this->worksheetNum),
				'worksheetCurrentRow' => $this->worksheetCurrentRow
			));
			$writeParams['action'] = ($end ? 'finish' : 'continue');
			file_put_contents($this->procfile, CUtil::PhpToJSObject($writeParams));
		}
	}
	
	public function UnsetExcessFields($fieldsList, &$arFieldsElement)
	{
		foreach($fieldsList as $field)
		{
			unset($arFieldsElement[$field]);
		}
	}
	
	public function SaveElementId($ID, $offer=false)
	{
		$oProfile = CKDAImportProfile::getInstance();
		$isNew = $oProfile->SaveElementId($ID, 'E');
		return $isNew;
	}
	
	public function GetFileArray($file, $arDef=array())
	{
		$file = trim($file);
		if(strpos($file, '/')===0)
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			$arFile = CFile::MakeFileArray($file);
			$file = $tmpsubdir.$arFile['name'];
			copy($arFile['tmp_name'], $file);
		}
		elseif(strpos($file, 'zip://')===0)
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			$oldfile = $file;
			$file = $tmpsubdir.basename($oldfile);
			copy($oldfile, $file);
		}
		elseif($service = $this->cloud->GetService($file))
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			if($arFile = $this->cloud->MakeFileArray($service, $file, true))
			{
				if(is_array($arFile) && count(preg_grep('/^\d+$/', array_keys($arFile))) > 0)
				{
					$arFiles = $arFile;
					if($arParams['MULTIPLE']=='Y' && count($arFiles) > 1)
					{
						foreach($arFiles as $k=>$v)
						{
							$arFiles[$k] = self::GetFileArray($v, $arDef, $arParams);
						}
						return array('VALUES'=>$arFiles);
					}
					elseif(count($arFiles) > 0)
					{
						$tmpfile = current($arFiles);
						return self::GetFileArray($tmpfile, $arDef, $arParams);
					}
				}
				$file = $tmpsubdir.$arFile['name'];
				copy($arFile['tmp_name'], $file);
				$checkSubdirs = 1;
			}
		}
		elseif(preg_match('/http(s)?:\/\//', $file))
		{
			$arUrl = parse_url($file);
			//Cyrillic domain
			if(preg_match('/[^A-Za-z0-9\-\.]/', $arUrl['host']))
			{
				if(!class_exists('idna_convert')) require_once(dirname(__FILE__).'/../../lib/idna_convert.class.php');
				if(class_exists('idna_convert'))
				{
					$idn = new idna_convert();
					$oldHost = $arUrl['host'];
					if(!CUtil::DetectUTF8($oldHost)) $oldHost = CKDAImportUtils::Win1251Utf8($oldHost);
					$file = str_replace($arUrl['host'], $idn->encode($oldHost), $file);
				}
			}
		}
		$arFile = CFile::MakeFileArray($file);
		if(!$arFile['name'] && !CUtil::DetectUTF8($file))
		{
			$file = CKDAImportUtils::Win1251Utf8($file);
			$arFile = CFile::MakeFileArray($file);
		}
		if(strpos($arFile['type'], 'image/')===0)
		{
			$ext = ToLower(str_replace('image/', '', $arFile['type']));
			if(substr($arFile['name'], -(strlen($ext) + 1))!='.'.$ext)
			{
				if($ext!='jpeg' || (($ext='jpg') && substr($arFile['name'], -(strlen($ext) + 1))!='.'.$ext))
				{
					$arFile['name'] = $arFile['name'].'.'.$ext;
				}
			}
		}
		if(!empty($arDef))
		{
			$arFile = $this->PictureProcessing($arFile, $arDef);
		}
		return $arFile;
	}
	
	public function CreateTmpImageDir()
	{
		$tmpsubdir = $this->imagedir.($this->filecnt++).'/';
		CheckDirPath($tmpsubdir);
		$this->arTmpImageDirs[] = $tmpsubdir;
		return $tmpsubdir;
	}
	
	public function RemoveTmpImageDirs()
	{
		if(count($this->arTmpImageDirs) > 20)
		{
			foreach($this->arTmpImageDirs as $k=>$v)
			{
				DeleteDirFilesEx(substr($v, strlen($_SERVER['DOCUMENT_ROOT'])));
			}
			$this->arTmpImageDirs = array();
		}
	}
	
	public function SetTimeBegin($ID)
	{
		if($this->stepparams['begin_time']) return;
		$dbRes = CIblockElement::GetList(array(), array('ID'=>$ID), false, false, array('TIMESTAMP_X'));
		if($arr = $dbRes->Fetch())
		{
			$this->stepparams['begin_time'] = $arr['TIMESTAMP_X'];
		}
	}
	
	public function GetHLBoolValue($val)
	{
		$res = $this->GetBoolValue($val);
		if($res=='Y') return 1;
		else return 0;
	}
	
	public function GetBoolValue($val, $numReturn = false)
	{
		$trueVals = array_map('trim', explode(',', GetMessage("KDA_IE_FIELD_VAL_Y")));
		$falseVals = array_map('trim', explode(',', GetMessage("KDA_IE_FIELD_VAL_N")));
		if(in_array(ToLower($val), $trueVals))
		{
			return ($numReturn ? 1 : 'Y');
		}
		elseif(in_array(ToLower($val), $falseVals))
		{
			return ($numReturn ? 0 : 'N');
		}
		else
		{
			return false;
		}
	}
	
	public function GetIblockProperties($IBLOCK_ID)
	{
		if(!$this->props[$IBLOCK_ID])
		{
			$this->props[$IBLOCK_ID] = array();
			$dbRes = CIBlockProperty::GetList(array(), array('IBLOCK_ID'=>$IBLOCK_ID));
			while($arProp = $dbRes->Fetch())
			{
				$this->props[$IBLOCK_ID][$arProp['ID']] = $arProp;
			}
		}
		return $this->props[$IBLOCK_ID];
	}
	
	public function GetIblockFields($IBLOCK_ID)
	{
		if(!$this->iblockFields[$IBLOCK_ID])
		{
			$this->iblockFields[$IBLOCK_ID] = CIBlock::GetFields($IBLOCK_ID);
		}
		return $this->iblockFields[$IBLOCK_ID];
	}
	
	public function GetIblockElementValue($arProp, $val, $fsettings)
	{
		if($fsettings['REL_ELEMENT_FIELD'] && $fsettings['REL_ELEMENT_FIELD']!='IE_ID' && $arProp['SETTINGS']['IBLOCK_ID'])
		{
			$tuid = $fsettings['REL_ELEMENT_FIELD'];
			$arFilter = array('IBLOCK_ID'=>$arProp['SETTINGS']['IBLOCK_ID']);
			if(strpos($tuid, 'IE_')===0)
			{
				$arFilter[substr($tuid, 3)] = $val;
			}
			elseif(strpos($tuid, 'IP_PROP')===0)
			{
				$uid = substr($tuid, 7);
				if($arProp['PROPERTY_TYPE']=='L')
				{
					$arFilter['PROPERTY_'.$uid.'_VALUE'] = $val;
				}
				else
				{
					if($arProp['PROPERTY_TYPE']=='S' && $arProp['USER_TYPE']=='directory')
					{
						$val = $this->GetDictionaryValue($arProp, $val);
					}
					$arFilter['PROPERTY_'.$uid] = $val;
				}
			}

			$dbRes = CIblockElement::GetList(array(), $arFilter, false, array('nTopCount'=>1), array('ID'));
			if($arRes = $dbRes->Fetch())
			{
				return $arRes['ID'];
			}
		}

		return $val;
	}
	
	public function GetHighloadBlockValue($arProp, $val)
	{
		if($val && CModule::IncludeModule('highloadblock') && $arProp['SETTINGS']['HLBLOCK_ID'])
		{
			$arFields = $val;
			if(!is_array($arFields))
			{
				$arFields = array('UF_NAME'=>$arFields);
			}
			if(count(array_diff($arFields, array('')))==0) return false;
			
			if(count($arFields)==1) $cacheKey = md5(serialize($arFields));
			elseif($arFields['ID']) $cacheKey = 'ID_'.$arFields['ID'];
			elseif($arFields['UF_XML_ID']) $cacheKey = 'UF_XML_ID_'.$arFields['UF_XML_ID'];
			else $cacheKey = 'UF_NAME_'.$arFields['UF_NAME'];

			if(!isset($this->propVals[$arProp['ID']][$cacheKey]))
			{
				if(!$this->hlbl[$arProp['ID']] || !$this->hlblFields[$arProp['ID']])
				{
					$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('ID'=>$arProp['SETTINGS']['HLBLOCK_ID'])))->fetch();
					if(!$this->hlbl[$arProp['ID']])
					{
						$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
						$this->hlbl[$arProp['ID']] = $entity->getDataClass();
					}
					if(!$this->hlblFields[$arProp['ID']])
					{
						$dbRes = CUserTypeEntity::GetList(array(), array('ENTITY_ID'=>'HLBLOCK_'.$hlblock['ID']));
						$arHLFields = array();
						while($arHLField = $dbRes->Fetch())
						{
							$arHLFields[$arHLField['FIELD_NAME']] = $arHLField;
						}
						$this->hlblFields[$arProp['ID']] = $arHLFields;
					}
				}
				$entityDataClass = $this->hlbl[$arProp['ID']];
				$arHLFields = $this->hlblFields[$arProp['ID']];
				
				//if(!$arFields['ID'] && !$arFields['UF_NAME'] && !$arFields['UF_XML_ID']) return false;
				$this->PrepareHighLoadBlockFields($arFields, $arHLFields);
				
				if(count($arFields)==1) $arFilter = $arFields;
				elseif($arFields['ID']) $arFilter = array("ID"=>$arFields['ID']);
				elseif($arFields['UF_XML_ID']) $arFilter = array("UF_XML_ID"=>$arFields['UF_XML_ID']);
				else $arFilter = array("UF_NAME"=>$arFields['UF_NAME']);
				$dbRes2 = $entityDataClass::GetList(array('filter'=>$arFilter, 'select'=>array('ID'), 'limit'=>1));
				if($arr2 = $dbRes2->Fetch())
				{
					if(count($arFields) > 1)
					{
						$entityDataClass::Update($arr2['ID'], $arFields);
					}
					$this->propVals[$arProp['ID']][$cacheKey] = $arr2['ID'];
				}
				else
				{
					/*if(!$arFields['UF_NAME']) return false;
					if(!$arFields['UF_XML_ID']) $arFields['UF_XML_ID'] = $this->Str2Url($arFields['UF_NAME']);*/
					$dbRes3 = $entityDataClass::Add($arFields);
					if($dbRes3->isSuccess())
						$this->propVals[$arProp['ID']][$cacheKey] = $dbRes3->GetId();
					else $this->propVals[$arProp['ID']][$cacheKey] = false;
				}
			}
			return $this->propVals[$arProp['ID']][$cacheKey];
		}
		return $val;
	}
	
	public function GetDictionaryValue($arProp, $val)
	{	
		if($val && CModule::IncludeModule('highloadblock') && $arProp['USER_TYPE_SETTINGS']['TABLE_NAME'])
		{
			$arFields = $val;
			if(!is_array($arFields))
			{
				$arFields = array('UF_NAME'=>$arFields);
			}
			$cacheKey = $arFields['UF_NAME'];

			if(!isset($this->propVals[$arProp['ID']][$cacheKey]))
			{
				if(!$this->hlbl[$arProp['ID']] || !$this->hlblFields[$arProp['ID']])
				{
					$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('TABLE_NAME'=>$arProp['USER_TYPE_SETTINGS']['TABLE_NAME'])))->fetch();
					if(!$this->hlbl[$arProp['ID']])
					{
						$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
						$this->hlbl[$arProp['ID']] = $entity->getDataClass();
					}
					if(!$this->hlblFields[$arProp['ID']])
					{
						$dbRes = CUserTypeEntity::GetList(array(), array('ENTITY_ID'=>'HLBLOCK_'.$hlblock['ID']));
						$arHLFields = array();
						while($arHLField = $dbRes->Fetch())
						{
							$arHLFields[$arHLField['FIELD_NAME']] = $arHLField;
						}
						$this->hlblFields[$arProp['ID']] = $arHLFields;
					}
				}
				$entityDataClass = $this->hlbl[$arProp['ID']];
				$arHLFields = $this->hlblFields[$arProp['ID']];
				
				if(!$arFields['UF_NAME']) return false;
				$this->PrepareHighLoadBlockFields($arFields, $arHLFields);
				
				$dbRes2 = $entityDataClass::GetList(array('filter'=>array("UF_NAME"=>$arFields['UF_NAME']), 'select'=>array('ID', 'UF_XML_ID'), 'limit'=>1));
				if($arr2 = $dbRes2->Fetch())
				{
					if(count($arFields) > 1)
					{
						$entityDataClass::Update($arr2['ID'], $arFields);
					}
					$this->propVals[$arProp['ID']][$cacheKey] = $arr2['ID'];
				}
				else
				{
					$dbRes3 = $entityDataClass::Add($arFields);
					$this->propVals[$arProp['ID']][$cacheKey] = $dbRes3->GetId();
				}
			}
			return $this->propVals[$arProp['ID']][$cacheKey];
		}
		return $val;
	}
	
	public function GetHighloadBlockClass($HIGHLOADBLOCK_ID)
	{
		if(!$this->hlbl[$HIGHLOADBLOCK_ID])
		{
			$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('ID'=>$HIGHLOADBLOCK_ID)))->fetch();
			$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
			$this->hlbl[$HIGHLOADBLOCK_ID] = $entity->getDataClass();
		}
		return $this->hlbl[$HIGHLOADBLOCK_ID];
	}
	
	public function PrepareHighLoadBlockFields(&$arFields, $arHLFields)
	{
		foreach($arFields as $k=>$v)
		{
			if($k == 'ID')
			{
				$arFields[$k] = $this->GetFloatVal($v);
				continue;
			}
			if(!isset($arHLFields[$k]))
			{
				unset($arFields[$k]);
			}
			$type = $arHLFields[$k]['USER_TYPE_ID'];
			if($type=='file')
			{
				$arFields[$k] = $this->GetFileArray($v);
			}
			elseif($type=='integer' || $type=='double')
			{
				$arFields[$k] = $this->GetFloatVal($v);
			}
			elseif($type=='datetime')
			{
				$arFields[$k] = $this->GetDateVal($v);
			}
			elseif($type=='date')
			{
				$arFields[$k] = $this->GetDateVal($v, 'PART');
			}
			elseif($type=='boolean')
			{
				$arFields[$k] = $this->GetHLBoolValue($v);
			}
		}		
	}
	
	public function PictureProcessing($arFile, $arDef)
	{
		if($arDef["SCALE"] === "Y")
		{
			$arNewPicture = CIBlock::ResizePicture($arFile, $arDef);
			if(is_array($arNewPicture))
			{
				$arFile = $arNewPicture;
			}
			/*elseif($arDef["IGNORE_ERRORS"] !== "Y")
			{
				unset($arFile);
				$strWarning .= GetMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
			}*/
		}

		if($arDef["USE_WATERMARK_FILE"] === "Y")
		{
			CIBLock::FilterPicture($arFile["tmp_name"], array(
				"name" => "watermark",
				"position" => $arDef["WATERMARK_FILE_POSITION"],
				"type" => "file",
				"size" => "real",
				"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
				"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
			));
		}

		if($arDef["USE_WATERMARK_TEXT"] === "Y")
		{
			CIBLock::FilterPicture($arFile["tmp_name"], array(
				"name" => "watermark",
				"position" => $arDef["WATERMARK_TEXT_POSITION"],
				"type" => "text",
				"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
				"text" => $arDef["WATERMARK_TEXT"],
				"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
				"color" => $arDef["WATERMARK_TEXT_COLOR"],
			));
		}
		return $arFile;
	}
	
	public function GetCurrencyRates()
	{
		if(!isset($this->currencyRates))
		{
			$arRates = array();
			$currFile = $this->tmpdir.'/currencies.txt';
			if(file_exists($currFile))
			{
				$arRates = unserialize(file_get_contents($currFile));
			}
			else
			{
				$client = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
				$res = $client->get('http://www.cbr.ru/scripts/XML_daily.asp');
				if($res)
				{
					$xml = simplexml_load_string($res);
					if($xml->Valute)
					{
						foreach($xml->Valute as $val)
						{
							$arRates[(string)$val->CharCode] = $this->GetFloatVal((string)$val->Value);
						}
					}
				}
				file_put_contents($currFile, serialize($arRates));
			}
			$this->currencyRates = $arRates;
		}
		return $this->currencyRates;
	}
	
	public function ConversionReplaceValues($m)
	{
		$value = '';
		$paramName = $m[0];
		$quot = "'";
		$isVar = false;
		if(preg_match('/^\$\{([\'"])(.*)[\'"]\}?$/', $paramName, $m2))
		{
			$quot = $m2[1];
			$paramName = $m2[2];
			$isVar = true;
		}
		
		if(preg_match('/#CELL\d+#/', $paramName))
		{
			$k = intval(substr($paramName, 5, -1)) - 1;
			if(is_array($this->currentItemValues) && isset($this->currentItemValues[$k])) $value = $this->currentItemValues[$k];
		}
		elseif($paramName=='#CLINK#')
		{
			if($this->useHyperlinks && $this->currentFieldKey)
			{
				$value = $this->hyperlinks[$this->currentFieldKey];
			}
		}
		elseif($paramName=='#FILENAME#')
		{
			$value = bx_basename($this->filename);
		}
		elseif($paramName=='#SHEETNAME#')
		{
			$value = (is_callable(array($this->worksheet, 'getTitle')) ? $this->worksheet->getTitle() : '');
		}
		elseif(in_array($paramName, $this->rcurrencies))
		{
			$arRates = $this->GetCurrencyRates();
			$k = trim($paramName, '#');
			$value = (isset($arRates[$k]) ? floatval($arRates[$k]) : 1);
		}

		if($isVar)
		{
			$this->extraConvParams[$paramName] = $value;
			return '$this->extraConvParams['.$quot.$paramName.$quot.']';
		}
		else return $value;
	}
	
	public function ApplyConversions($val, $arConv, $arItem, $field=false, $iblockFields=array())
	{
		$fieldName = $fieldKey = false;
		if(!is_array($field))
		{
			$fieldName = $field;
		}
		else
		{
			if($field['NAME']) $fieldName = $field['NAME'];
			if($field['KEY']) $fieldKey = $field['KEY'];
		}
		
		if(is_array($arConv))
		{
			$execConv = false;
			$this->currentItemValues = $arItem;
			$prefixPattern = '/(\$\{[\'"])?(#CELL\d+#|#CLINK#|#FILENAME#|#SHEETNAME#|'.implode('|', $this->rcurrencies).')([\'"]\})?/';
			foreach($arConv as $k=>$v)
			{
				$condVal = $val;
				if((int)$v['CELL'] > 0)
				{
					$numCell = (int)$v['CELL'] - 1;
					if(array_key_exists($numCell, $arItem))
					{
						$condVal = (array_key_exists('~~'.$numCell, $arItem) ? $arItem['~~'.$numCell] : $arItem[$numCell]);
					}
					else
					{
						$condVal = $this->GetCalculatedValue($this->worksheet->getCellByColumnAndRow($numCell, $this->worksheetCurrentRow));
					}
				}
				if(strlen($v['FROM']) > 0) $v['FROM'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['FROM']);
				if($v['CELL']=='ELSE') $v['WHEN'] = '';
				$condValNum = $this->GetFloatVal($condVal);
				$fromNum = $this->GetFloatVal($v['FROM']);
				if(($v['CELL']=='ELSE' && !$execConv)
					|| ($v['WHEN']=='EQ' && $condVal==$v['FROM'])
					|| ($v['WHEN']=='NEQ' && $condVal!=$v['FROM'])
					|| ($v['WHEN']=='GT' && $condValNum > $fromNum)
					|| ($v['WHEN']=='LT' && $condValNum < $fromNum)
					|| ($v['WHEN']=='GEQ' && $condValNum >= $fromNum)
					|| ($v['WHEN']=='LEQ' && $condValNum <= $fromNum)
					|| ($v['WHEN']=='CONTAIN' && strpos($condVal, $v['FROM'])!==false)
					|| ($v['WHEN']=='NOT_CONTAIN' && strpos($condVal, $v['FROM'])===false)
					|| ($v['WHEN']=='REGEXP' && preg_match('/'.ToLower($v['FROM']).'/i', ToLower($condVal)))
					|| ($v['WHEN']=='NOT_REGEXP' && !preg_match('/'.ToLower($v['FROM']).'/i', ToLower($condVal)))
					|| ($v['WHEN']=='EMPTY' && strlen($condVal)==0)
					|| ($v['WHEN']=='NOT_EMPTY' && strlen($condVal) > 0)
					|| ($v['WHEN']=='ANY'))
				{
					$this->currentFieldKey = $fieldKey;
					if($v['TO']) $v['TO'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['TO']);
					if($v['THEN']=='REPLACE_TO') $val = $v['TO'];
					elseif($v['THEN']=='REMOVE_SUBSTRING' && $v['TO']) $val = str_replace($v['TO'], '', $val);
					elseif($v['THEN']=='REPLACE_SUBSTRING_TO' && $v['FROM']) $val = str_replace($v['FROM'], $v['TO'], $val);
					elseif($v['THEN']=='ADD_TO_BEGIN') $val = $v['TO'].$val;
					elseif($v['THEN']=='ADD_TO_END') $val = $val.$v['TO'];
					elseif($v['THEN']=='LCASE') $val = ToLower($val);
					elseif($v['THEN']=='UCASE') $val = ToUpper($val);
					elseif($v['THEN']=='UFIRST') $val = preg_replace_callback('/^(\s*)(.*)$/', create_function('$m', 'return $m[1].ToUpper(substr($m[2], 0, 1)).ToLower(substr($m[2], 1));'), $val);
					elseif($v['THEN']=='UWORD') $val = implode(' ', array_map(create_function('$m', 'return ToUpper(substr($m, 0, 1)).ToLower(substr($m, 1));'), explode(' ', $val)));
					elseif($v['THEN']=='MATH_ROUND') $val = round(doubleval(str_replace(',', '.', $val)));
					elseif($v['THEN']=='MATH_MULTIPLY') $val = doubleval(str_replace(',', '.', $val)) * doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='MATH_DIVIDE') $val = doubleval(str_replace(',', '.', $val)) / doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='MATH_ADD') $val = doubleval(str_replace(',', '.', $val)) + doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='MATH_SUBTRACT') $val = doubleval(str_replace(',', '.', $val)) - doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='NOT_LOAD') $val = false;
					elseif($v['THEN']=='EXPRESSION') $val = $this->ExecuteFilterExpression($val, $v['TO'], '');
					elseif($v['THEN']=='STRIP_TAGS') $val = strip_tags($val);
					elseif($v['THEN']=='CLEAR_TAGS') $val = preg_replace('/<([a-z][a-z0-9:]*)[^>]*(\/?)>/i','<$1$2>', $val);
					elseif($v['THEN']=='TRANSLIT')
					{
						$val = $this->Str2Url($val, $arParams);
					}
					$execConv = true;
				}
			}
		}
		return $val;
	}
	
	public static function GetPreviewData($file, $showLines, $arParams = array(), $colsCount = false)
	{
		$selfobj = new CKDAImportExcelStatic($arParams, $file);
		$file = $_SERVER['DOCUMENT_ROOT'].$file;
		$objReader = KDAPHPExcel_IOFactory::createReaderForFile($file);		
		if($arParams['ELEMENT_NOT_LOAD_STYLES']=='Y' && $arParams['ELEMENT_NOT_LOAD_FORMATTING']=='Y')
		{
			$objReader->setReadDataOnly(true);
		}
		if(isset($arParams['CSV_PARAMS']))
		{
			$objReader->setCsvParams($arParams['CSV_PARAMS']);
		}
		$chunkFilter = new KDAChunkReadFilter();
		$objReader->setReadFilter($chunkFilter);
		if(!$colsCount)
		{
			$chunkFilter->setRows(1, max($showLines, 50));
		}
		else
		{
			$chunkFilter->setRows(1, 1000);
		}
		
		$efile = $objReader->load($file);
		$arWorksheets = array();
		foreach($efile->getWorksheetIterator() as $worksheet) 
		{
			$columns_count = KDAPHPExcel_Cell::columnIndexFromString($worksheet->getHighestDataColumn());
			$rows_count = $worksheet->getHighestDataRow();

			$arLines = array();
			$cntLines = $emptyLines = 0;
			for ($row = 0; ($row < $rows_count && count($arLines) < $showLines+$emptyLines); $row++) 
			{
				$arLine = array();
				$bEmpty = true;
				for ($column = 0; $column < $columns_count; $column++) 
				{
					$val = $worksheet->getCellByColumnAndRow($column, $row+1);
					$valText = $selfobj->GetCalculatedValue($val);
					if(strlen(trim($valText)) > 0) $bEmpty = false;
					
					$curLine = array('VALUE' => $valText);
					if($arParams['ELEMENT_NOT_LOAD_STYLES']!='Y')
					{
						$curLine['STYLE'] = self::GetCellStyle($val);
					}
					$arLine[] = $curLine;
				}

				$arLines[$row] = $arLine;
				if($bEmpty)
				{
					$emptyLines++;
				}
				$cntLines++;
			}
			
			if($colsCount)
			{
				$columns_count = $colsCount;
				$arLines = array();
				$lastEmptyLines = 0;
				for ($row = $cntLines; $row < $rows_count; $row++) 
				{
					$arLine = array();
					$bEmpty = true;
					for ($column = 0; $column < $columns_count; $column++) 
					{
						$val = $worksheet->getCellByColumnAndRow($column, $row+1);
						$valText = $selfobj->GetCalculatedValue($val);
						if(strlen(trim($valText)) > 0) $bEmpty = false;
						
						$curLine = array('VALUE' => $valText);
						if($arParams['ELEMENT_NOT_LOAD_STYLES']!='Y')
						{
							$curLine['STYLE'] = self::GetCellStyle($val);
						}
						$arLine[] = $curLine;
					}
					if($bEmpty) $lastEmptyLines++;
					else $lastEmptyLines = 0;
					$arLines[$row] = $arLine;
				}
				
				if($lastEmptyLines > 0)
				{
					$arLines = array_slice($arLines, 0, -$lastEmptyLines, true);
				}
			}
			
			$arCells = explode(':', $worksheet->getSelectedCells());
			$heghestRow = intval(preg_replace('/\D+/', '', end($arCells)));
			if(is_callable(array($worksheet, 'getRealHighestRow'))) $heghestRow = intval($worksheet->getRealHighestRow());
			elseif($worksheet->getHighestDataRow() > $heghestRow) $heghestRow = intval($worksheet->getHighestDataRow());
			if(stripos($file, '.csv'))
			{
				$heghestRow = CKDAImportUtils::GetFileLinesCount($file);
			}

			$arWorksheets[] = array(
				'title' => self::CorrectCalculatedValue($worksheet->GetTitle()),
				'show_more' => ($row < $rows_count - 1),
				'lines_count' => $heghestRow,
				'lines' => $arLines
			);
		}
		return $arWorksheets;
	}
	
	public function GetCellStyle($val, $modify = false)
	{
		$style = $val->getStyle();
		$arStyle = array(
			'COLOR' => $style->getFont()->getColor()->getRGB(),
			'FONT-FAMILY' => $style->getFont()->getName(),
			'FONT-SIZE' => $style->getFont()->getSize(),
			'FONT-WEIGHT' => $style->getFont()->getBold(),
			'FONT-STYLE' => $style->getFont()->getItalic(),
			'TEXT-DECORATION' => $style->getFont()->getUnderline(),
			'BACKGROUND' => ($style->getFill()->getFillType()=='solid' ? $style->getFill()->getStartColor()->getRGB() : ''),
		);
		$outlineLevel = (int)$val->getWorksheet()->getRowDimension($val->getRow())->getOutlineLevel();
		if($outlineLevel > 0)
		{
			$arStyle['TEXT-INDENT'] = $outlineLevel;
		}
		if($modify)
		{
			$arStyle['EXT'] = array(
				'COLOR' => $style->getFont()->getColor()->getRealRGB(),
				'BACKGROUND' => ($style->getFill()->getFillType()=='solid' ? $style->getFill()->getStartColor()->getRealRGB() : ''),
			);
		}
		
		return $arStyle;
	}
	
	public function GetStyleByColumn($column, $param)
	{
		$val = $this->worksheet->getCellByColumnAndRow($column, $this->worksheetCurrentRow);
		$arStyle = self::GetCellStyle($val);
		if(isset($arStyle[$param])) return $arStyle[$param];
		else return '';
	}
	
	public function GetValueByColumn($column)
	{
		$val = $this->worksheet->getCellByColumnAndRow($column, $this->worksheetCurrentRow);
		$valOrig = $this->GetCalculatedValue($val);
		return $valOrig;
	}
	
	public function GetCalculatedValue($val)
	{
		try{
			if($this->params['ELEMENT_NOT_LOAD_FORMATTING']=='Y') $val = $val->getCalculatedValue();
			else $val = $val->getFormattedValue();
		}catch(Exception $ex){}
		return self::CorrectCalculatedValue($val);
	}
	
	public static function CorrectCalculatedValue($val)
	{
		$val = str_ireplace('_x000D_', '', $val);
		if((!defined('BX_UTF') || !BX_UTF) && CUtil::DetectUTF8($val)/*function_exists('mb_detect_encoding') && (mb_detect_encoding($val) == 'UTF-8')*/)
		{
			$val = strtr($val, array('Ø'=>'&#216;', '™'=>'&#153;', '®'=>'&#174;', '©'=>'&#169;'));
			$val = utf8win1251($val);
		}
		return $val;
	}
	
	public function GetIntVal($val)
	{
		return intval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
	}
	
	public function GetFloatVal($val)
	{
		return floatval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
	}
	
	public function GetDateVal($val, $format = 'FULL')
	{
		$time = strtotime($val);
		if($time > 0)
		{
			return ConvertTimeStamp($time, $format);
		}
		return false;
	}
	
	public function Str2Url($string, $arParams=array())
	{
		if(!is_array($arParams)) $arParams = array();
		if($arParams['TRANSLITERATION']=='Y')
		{
			if(isset($arParams['TRANS_LEN'])) $arParams['max_len'] = $arParams['TRANS_LEN'];
			if(isset($arParams['TRANS_CASE'])) $arParams['change_case'] = $arParams['TRANS_CASE'];
			if(isset($arParams['TRANS_SPACE'])) $arParams['replace_space'] = $arParams['TRANS_SPACE'];
			if(isset($arParams['TRANS_OTHER'])) $arParams['replace_other'] = $arParams['TRANS_OTHER'];
			if(isset($arParams['TRANS_EAT']) && $arParams['TRANS_EAT']=='N') $arParams['delete_repeat_replace'] = false;
		}
		return CUtil::translit($string, LANGUAGE_ID, $arParams);
	}
	
	public function OnShutdown()
	{
		$arError = error_get_last();
		if(!is_array($arError) || !isset($arError['type']) || !in_array($arError['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR))) return;
		
		if($this->worksheetCurrentRow > 0)
		{
			$this->EndWithError(sprintf(Loc::getMessage("KDA_IE_FATAL_ERROR_IN_LINE"), $this->worksheetNumForSave+1, $this->worksheetCurrentRow, $arError['type'], $arError['message'], $arError['file'], $arError['line']));
		}
		else
		{
			$this->EndWithError(sprintf(Loc::getMessage("KDA_IE_FATAL_ERROR"), $arError['type'], $arError['message'], $arError['file'], $arError['line']));
		}
	}
	
	public function HandleError($code, $message, $file, $line)
	{
		return true;
	}
	
	public function HandleException($exception)
	{
		if(is_callable(array('\Bitrix\Main\Diag\ExceptionHandlerFormatter', 'format')))
		{
			$this->EndWithError(\Bitrix\Main\Diag\ExceptionHandlerFormatter::format($exception));
		}
		$this->EndWithError(sprintf(Loc::getMessage("KDA_IE_FATAL_ERROR"), '', $exception->getMessage(), $exception->getFile(), $exception->getLine()));
	}
	
	public function EndWithError($error)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$this->errors[] = $error;
		$this->SaveStatusImport();
		echo '<!--module_return_data-->'.CUtil::PhpToJSObject($this->GetBreakParams());
		die();
	}
}
?>