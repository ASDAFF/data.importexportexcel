<?php
namespace Bitrix\KdaImportexcel;

class ZipArchive
{
	protected static $moduleId = 'esol.importexportexcel';
	private $tmpDir = '';
	private $removeOnClose = false;
	private $sStringFile = false;
	private $strIndexes = array();
	
	public function __construct()
	{

	}
	
	public function __destruct()
	{
		$this->close();
	}
	
	public function close()
	{
		if(strlen($this->tmpDir) > 0 && file_exists($this->tmpDir) && $this->removeOnClose)
		{
			static::RemoveFileDir($this->tmpDir);
		}
		$this->removeOnClose = false;
		$this->sStringFile = false;
		$this->strIndexes = array();
		$this->tmpDir = '';
	}
	
	public static function RemoveFileDir($dir)
	{
		if(is_file($dir)) $dir = static::GetFileDir($dir);
		elseif(is_numeric($dir)) $dir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/import/_archives/'.$dir.'/';
		if($dir && is_dir($dir))
		{
			DeleteDirFilesEx(substr($dir, strlen($_SERVER['DOCUMENT_ROOT'])));
			$pDir = dirname($dir);
			if(($arFiles = scandir($pDir)) && is_array($arFiles) && count(array_diff($arFiles, array('.', '..')))==0) rmdir($pDir);
		}
	}
	
	public static function GetFileDir($pFilename)
	{
		if(($pos = strpos($pFilename, '/'.static::$moduleId.'/'))!==false)
		{
			$filePath = \Bitrix\Main\IO\Path::convertPhysicalToLogical(substr($pFilename, $pos + 1));
			$fileName = basename($filePath);
			$subDir = substr($filePath, 0, -strlen($fileName) - 1);
			if(strlen($fileName) > 0 && strlen($subDir) > 0 && ($arFile = \CFile::GetList(array(), array('SUBDIR'=>$subDir, 'FILE_NAME'=>$fileName))->Fetch()))
			{
				return $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/import/_archives/'.$arFile['ID'].'/';
			}
		}
		return false;
	}
	
	public function open($pFilename)
	{
		$this->tmpDir = '';
		$this->removeOnClose = false;
		$this->sStringFile = false;
		$this->strIndexes = array();
		if($dir = static::GetFileDir($pFilename))
		{
			$this->tmpDir = $dir;
			if(file_exists($this->tmpDir))
			{
				if(filemtime($this->tmpDir) < max(filemtime($pFilename), time()-24*60*60) || $this->calcCheckSum()!=$this->getCheckSum())
				{
					DeleteDirFilesEx(substr($this->tmpDir, strlen($_SERVER['DOCUMENT_ROOT'])));
					rmdir(dirname($this->tmpDir));
				}
				else
				{
					return true;
				}
			}
			if(!file_exists($this->tmpDir))
			{
				\Bitrix\Main\IO\Directory::createDirectory($this->tmpDir);
			}
		}
				
		if(strlen($this->tmpDir)==0)
		{
			$this->removeOnClose = true;
			$temp_path = \CFile::GetTempName('', bx_basename($pFilename));
			$tmpDir = \Bitrix\Main\IO\Path::getDirectory($temp_path);
			\Bitrix\Main\IO\Directory::createDirectory($tmpDir);
			while(($this->tmpDir = $tmpDir.'/'.md5(mt_rand()).'/') && file_exists($this->tmpDir) && $i<1000)
			{
				$i++;
			}
		}
		
		if(class_exists('\ZipArchive'))
		{
			$zipObj = new \ZipArchive;
			if ($zipObj->open($pFilename) === true)
			{
				$zipObj->extractTo($this->tmpDir);
				$zipObj->close();
				$this->setCheckSum();
				return true;
			}
		}
		else
		{
			$io = \CBXVirtualIo::GetInstance();
			$pFilename2 = $io->GetLogicalName($pFilename);
			$zipObj = \CBXArchive::GetArchive($pFilename2, 'ZIP');
			if($zipObj->Unpack($this->tmpDir)!==false)
			{
				$this->setCheckSum();
				return true;
			}
		}
		return false;
	}
	
	public function setCheckSum()
	{
		$sum = $this->calcCheckSum();
		file_put_contents($this->tmpDir.'/.checksum', $sum);
	}
	
	public function getCheckSum()
	{
		if(!file_exists($this->tmpDir.'/.checksum')) return '';
		return file_get_contents($this->tmpDir.'/.checksum');
	}
	
	public function calcCheckSum($dir='')
	{
		if(strlen($dir)==0) $dir = $this->tmpDir;
		$dir = rtrim($dir, '/').'/';
		$arFiles = scandir($dir);
		$arFiles = array_diff($arFiles, preg_grep('/^(\.+|\.checksum|.*\.cache)$/i', $arFiles));
		$sum = implode('#', $arFiles);
		foreach($arFiles as $k=>$v)
		{
			if(is_dir($dir.$v))
			{
				$sum .= '###'.$this->calcCheckSum($dir.$v);
			}
		}
		return md5($sum);
	}
	
	public function getFromName($name, $length=0, $flags=0)
	{
		$content = file_get_contents($this->tmpDir.$name);
		if($length > 0) $content = substr($content, 0, $length);
		return $content;
	}
	
	public function getSimpleXmlForSheet($name, $readFilter = null)
	{
		$fn = $this->tmpDir.$name;

		if(!file_exists($fn))
		{
			return new \SimpleXMLElement('<d></d>');
		}
		
		$xml = new \XMLReader();
		$res = $xml->open($fn);

		$firstRow = (is_callable(array($readFilter, 'getStartRow')) ? $readFilter->getStartRow() : 1);
		$lastRow = (is_callable(array($readFilter, 'getEndRow')) ? $readFilter->getEndRow() : 999999);
		
		$xmlObj = new \SimpleXMLElement('<d></d>');
		$arObjects = array();
		$arObjectNames = array();
		$curDepth = 0;
		$arObjects[$curDepth] = &$xmlObj;
		$rowNum = 0;
		$isRead = false;
		while (($isRead || $xml->read())) {
			$isRead = false;
			if($xml->nodeType == \XMLReader::ELEMENT) 
			{
				if($arObjectNames[1]=='sheetData' && $xml->name=='row' && $xml->depth==2)
				{
					$arObjectNames[$xml->depth] = $xml->name;
					$rowNum++;
					if($rowNum > 1)
					{
						while($rowNum < $firstRow-1 && ($isRead = true) && $xml->next('row'))
						{
							$rowNum++;
						}
						if($rowNum > $lastRow)
						{
							while($xml->read() && ($xml->nodeType != \XMLReader::ELEMENT || $xml->depth > 1)){}
							$isRead = true;
							continue;
						}
					}
				}
				/*if($arObjectNames[1]=='sheetData' && $arObjectNames[2]=='row' && $xml->depth>=2)
				{
					if(is_callable(array($readFilter, 'readCell')) && !$readFilter->readCell(1, $rowNum)) continue;
				}*/

				$arAttributes = array();
				if($xml->moveToFirstAttribute())
				{
					$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
					while($xml->moveToNextAttribute ())
					{
						$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
					}
				}
				$xml->moveToElement();

				if($xml->depth > 0)
				{
					$curDepth = $xml->depth;
					$arObjectNames[$curDepth] = $xml->name;
					$curName = $xml->name;
					$curValue = null;
					$curNamespace = ($xml->namespaceURI ? $xml->namespaceURI : null);

					$xml->read();
					if($xml->nodeType == \XMLReader::TEXT)
					{
						$curValue = $xml->value;
					}
					else
					{
						$isRead = true;
					}

					$curValue = str_replace('&', '&amp;', $curValue);
					$arObjects[$curDepth] = $arObjects[$curDepth - 1]->addChild($curName, $curValue, $curNamespace);
				}

				foreach($arAttributes as $arAttr)
				{
					if(strpos($arAttr['name'], ':')!==false && $arAttr['namespaceURI']) $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value'], $arAttr['namespaceURI']);
					else $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value']);
				}
			}
		}
		$xml->close();
		
		$strIndexes = array();
		if(isset($xmlObj->sheetData) && isset($xmlObj->sheetData->row))
		{
			foreach($xmlObj->sheetData->row as $row)
			{
				if(isset($row->c))
				{
					foreach($row->c as $cell)
					{
						if(isset($cell->v))
						{
							$strIndexes[(int)$cell->v] = (int)$cell->v;
						}
					}
				}
			}
		}
		$this->strIndexes = $strIndexes;

		return $xmlObj;
	}
	
	public function setSharedStringsFile($name)
	{
		$fn = $this->tmpDir.$name;

		if(!file_exists($fn))
		{
			$fname = basename($fn);
			$fchar = substr($fname, 0, 1);
			if(strtoupper($fchar) == $fchar) $fchar = strtolower($fchar);
			else $fchar = strtoupper($fchar);
			$fname = $fchar.substr($fname, 1);
			$fn = substr($fn, 0, -strlen($fname)).$fname;
		}
		
		if(file_exists($fn))
		{
			$this->sStringFile = $fn;
		}
	}
	
	public function getSharedStringsFromIndexes($reader)
	{
		$sharedStrings = array();
		if($this->sStringFile===false || !file_exists($this->sStringFile) || !is_array($this->strIndexes) || empty($this->strIndexes)) return $sharedStrings;
		
		$xml = new \XMLReader();
		$res = $xml->open($this->sStringFile);

		$find = false;
		while($xml->read() && !($xml->nodeType==\XMLReader::ELEMENT && $xml->name=='si' && $xml->depth==1 && ($find = true))){}
		if(!$find) return $sharedStrings;
		
		$ind = -1;
		while(++$ind==0 || $xml->next('si'))
		{
			if(!isset($this->strIndexes[$ind])) continue;
			$val = simplexml_load_string($xml->readOuterXml());
		
			if (isset($val->t)) {
				$sharedStrings[$ind] = \KDAPHPExcel_Shared_String::ControlCharacterOOXML2PHP( (string) $val->t );
			} elseif (isset($val->r)) {
				$sharedStrings[$ind] = (is_callable(array($reader, 'publicParseRichText')) ? $reader->publicParseRichText($val) : '');
			}
		}
		$xml->close();

		return $sharedStrings;
	}
	
	public function getSharedStringsFromString($str, $reader)
	{
		$tmpDir = $this->tmpDir;
		$name = 'sharedStrings.xml';
		$tempPath = \CFile::GetTempName('', $name);
		$dir = \Bitrix\Main\IO\Path::getDirectory($tempPath);
		\Bitrix\Main\IO\Directory::createDirectory($dir);
		$this->tmpDir = rtrim($dir, '/').'/';
		file_put_contents($tempPath, $str);
		$sharedStrings = $this->getSharedStrings($name, $reader, false);
		unlink($tempPath);
		if(($arFiles = scandir($dir)) && is_array($arFiles) && count(array_diff($arFiles, array('.', '..')))==0) rmdir($dir);
		$this->tmpDir = $tmpDir;
		return $sharedStrings;
	}
	
	public function getSharedStrings($name, $reader, $bCache=false)
	{
		$fn = $this->tmpDir.$name;
		$sharedStrings = array();

		if(!file_exists($fn))
		{
			$fname = basename($fn);
			$fchar = substr($fname, 0, 1);
			if(strtoupper($fchar) == $fchar) $fchar = strtolower($fchar);
			else $fchar = strtoupper($fchar);
			$fname = $fchar.substr($fname, 1);
			$fn = substr($fn, 0, -strlen($fname)).$fname;
		}
		
		if(!file_exists($fn))
		{
			return $sharedStrings;
		}
		
		$fnCache = $fn.'.cache';
		if(!$bCache || !file_exists($fnCache) || filemtime($fn) > filemtime($fnCache))
		{
			$xml = new \XMLReader();
			$res = $xml->open($fn);

			while ($xml->read()) {
				if($xml->nodeType == \XMLReader::ELEMENT && $xml->name == 'si' && $xml->depth == 1) 
				{
					$val = new \SimpleXMLElement('<si></si>');
					$arObjects = array();
					$arObjectNames = array();
					$curDepth = $xml->depth;
					$arObjects[$curDepth] = &$val;
					$isRead = false;
					while (($isRead || $xml->read())
						&& !($xml->nodeType == \XMLReader::END_ELEMENT && $xml->name == 'si' && $xml->depth == 1)) {
						$isRead = false;
						if($xml->nodeType == \XMLReader::ELEMENT) 
						{
							$arAttributes = array();
							if($xml->moveToFirstAttribute())
							{
								$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
								while($xml->moveToNextAttribute ())
								{
									$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
								}
							}
							$xml->moveToElement();
					
							if($xml->depth > 1)
							{
								$curDepth = $xml->depth;
								$arObjectNames[$curDepth] = $xml->name;
								$curName = $xml->name;
								$curValue = null;
								$curNamespace = ($xml->namespaceURI ? $xml->namespaceURI : null);

								while($xml->read() && $xml->nodeType == \XMLReader::SIGNIFICANT_WHITESPACE){}
								if($xml->nodeType == \XMLReader::TEXT || $xml->nodeType == \XMLReader::CDATA)
								{
									$curValue = $xml->value;
								}
								else
								{
									$isRead = true;
								}

								$curValue = str_replace('&', '&amp;', $curValue);
								$arObjects[$curDepth] = $arObjects[$curDepth - 1]->addChild($curName, $curValue, $curNamespace);
							}
							
							foreach($arAttributes as $arAttr)
							{
								if(strpos($arAttr['name'], ':')!==false && $arAttr['namespaceURI']) $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value'], $arAttr['namespaceURI']);
								else $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value']);
							}
						}
					}
					
					if (isset($val->t)) {
						$sharedStrings[] = \KDAPHPExcel_Shared_String::ControlCharacterOOXML2PHP( (string) $val->t );
					} elseif (isset($val->r)) {
						$sharedStrings[] = (is_callable(array($reader, 'publicParseRichText')) ? $reader->publicParseRichText($val) : '');
					}
				}
			}
			$xml->close();
			
			if($bCache)
			{
				if(file_exists($fnCache)) unlink($fnCache);
				$handle = fopen($fnCache, 'a');
				foreach($sharedStrings as $k=>$str)
				{
					fwrite($handle, ($k > 0 ? "\r\n" : '').base64_encode(serialize($str)));
				}
				fclose($handle);
			}
		}
		else
		{
			$handle = fopen($fnCache, "r");
			while(!feof($handle))
			{
				$buffer = fgets($handle, 65536);
				$sharedStrings[] = unserialize(base64_decode($buffer));
			}
			fclose($handle);

		}
		return $sharedStrings;
	}
	
	public function locateName($name, $flags=0)
	{
		if(file_exists($this->tmpDir.$name))
		{
			return 1;
		}
		return false;
	}
	
	public function statName($name, $flags=0)
	{
		if(file_exists($this->tmpDir.$name))
		{
			return array(
				'name' => $name,
				'index' => 1,
				'crc' => crc32(file_get_contents($this->tmpDir.$name)),
				'size' => filesize($this->tmpDir.$name),
				'mtime' => filemtime($this->tmpDir.$name),
				'comp_size' => filesize($this->tmpDir.$name),
				'comp_method' => 8
			);
		}
		return false;
	}
	
	public function getZipFilePath($subpath, $createTmp = false)
	{
		$subpath = str_replace('\\', '/', $subpath);
		$subpath = ltrim($subpath, '/');
		$path = $this->tmpDir.$subpath;
		if($createTmp)
		{
			$temp_path = \CFile::GetTempName('', bx_basename($path));
			$dir = \Bitrix\Main\IO\Path::getDirectory($temp_path);
			\Bitrix\Main\IO\Directory::createDirectory($dir);
			copy($path, $temp_path);
			return $temp_path;
		}
		else
		{
			return $path;
		}
	}
}