<?php
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class CKDAImportUtils {
	protected static $moduleId = 'esol.importexportexcel';
	protected static $moduleSubDir = 'import/';
	protected static $colLetters = array();
	protected static $arAgents = array();
	protected static $countAgents = 0;
	protected static $offerIblockProps = array();
	protected static $fileSystemEncoding = null;
	
	public static function GetOfferIblock($IBLOCK_ID, $retarray=false)
	{
		if(!$IBLOCK_ID) return false;
		if(!Loader::includeModule('catalog'))
		{
			$arFields = array();
			$arRels = unserialize(COption::GetOptionString(static::$moduleId, 'CATALOG_RELS'));
			if(!is_array($arRels)) $arRels = array();
			foreach($arRels as $arRel)
			{
				if($arRel['IBLOCK_ID']==$IBLOCK_ID)
				{
					$arIblock = \CIblock::GetById($IBLOCK_ID)->Fetch();
					$arFields = Array(
						'IBLOCK_ID' => $arRel['IBLOCK_ID'],
						'YANDEX_EXPORT' => 'N',
						'SUBSCRIPTION' => 'N',
						'VAT_ID' => 0,
						'PRODUCT_IBLOCK_ID' => 0,
						'SKU_PROPERTY_ID' => 0,
						'OFFERS_PROPERTY_ID' => $arRel['OFFERS_PROP_ID'],
						'OFFERS_IBLOCK_ID' => $arRel['OFFERS_IBLOCK_ID'],
						'ID' => $arRel['IBLOCK_ID'],
						'IBLOCK_TYPE_ID' => $arIblock['IBLOCK_TYPE_ID'],
						'IBLOCK_ACTIVE' => $arIblock['ACTIVE'],
						'LID' => $arIblock['LID'],
						'NAME' => $arIblock['NAME']
					);
				}
			}
		}
		else
		{
			$dbRes = CCatalog::GetList(array(), array('IBLOCK_ID'=>$IBLOCK_ID));
			$arFields = $dbRes->Fetch();
			if(!$arFields['OFFERS_IBLOCK_ID'])
			{
				$dbRes = CCatalog::GetList(array(), array('PRODUCT_IBLOCK_ID'=>$IBLOCK_ID));
				if($arFields2 = $dbRes->Fetch())
				{
					$arFields = Array(
						'IBLOCK_ID' => $arFields2['PRODUCT_IBLOCK_ID'],
						'YANDEX_EXPORT' => $arFields2['YANDEX_EXPORT'],
						'SUBSCRIPTION' => $arFields2['SUBSCRIPTION'],
						'VAT_ID' => $arFields2['VAT_ID'],
						'PRODUCT_IBLOCK_ID' => 0,
						'SKU_PROPERTY_ID' => 0,
						'OFFERS_PROPERTY_ID' => $arFields2['SKU_PROPERTY_ID'],
						'OFFERS_IBLOCK_ID' => $arFields2['IBLOCK_ID'],
						'ID' => $arFields2['IBLOCK_ID'],
						'IBLOCK_TYPE_ID' => $arFields2['IBLOCK_TYPE_ID'],
						'IBLOCK_ACTIVE' => $arFields2['IBLOCK_ACTIVE'],
						'LID' => $arFields2['LID'],
						'NAME' => $arFields2['NAME']
					);
				}
			}
		}
		if(isset($arFields['OFFERS_IBLOCK_ID']) && $arFields['OFFERS_IBLOCK_ID'])
		{
			if($retarray) return $arFields;
			else return $arFields['OFFERS_IBLOCK_ID'];
		}
		return false;
	}
	
	public static function GetOfferIblockByOfferIblock($IBLOCK_ID)
	{
		if(!$IBLOCK_ID) return false;
		if(!isset(self::$offerIblockProps[$IBLOCK_ID]))
		{
			self::$offerIblockProps[$IBLOCK_ID] = array();
			if(Loader::includeModule('catalog'))
			{
				$dbRes = \CCatalog::GetList(array(), array('IBLOCK_ID'=>$IBLOCK_ID));
				if($arCatalog = $dbRes->Fetch())
				{
					self::$offerIblockProps[$IBLOCK_ID] = array(
						'IBLOCK_ID' => $arCatalog['PRODUCT_IBLOCK_ID'],
						'OFFERS_IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
						'OFFERS_PROPERTY_ID' => $arCatalog['SKU_PROPERTY_ID']
					);
				}
			}
		}
		return self::$offerIblockProps[$IBLOCK_ID];
	}
	
	public static function GetFileName($fn)
	{
		global $APPLICATION;
		if(file_exists($_SERVER['DOCUMENT_ROOT'].$fn)) return $fn;
		
		if(defined("BX_UTF")) $tmpfile = $APPLICATION->ConvertCharsetArray($fn, LANG_CHARSET, 'CP1251');
		else $tmpfile = $APPLICATION->ConvertCharsetArray($fn, LANG_CHARSET, 'UTF-8');
		
		if(file_exists($_SERVER['DOCUMENT_ROOT'].$tmpfile)) return $tmpfile;
		
		return false;
	}
	
	public static function Win1251Utf8($str)
	{
		global $APPLICATION;
		return $APPLICATION->ConvertCharset($str, "Windows-1251", "UTF-8");
	}
	
	public static function GetFileLinesCount($fn)
	{
		if(!file_exists($fn)) return 0;
		
		$cnt = 0;
		$handle = fopen($fn, 'r');
		while (!feof($handle)) {
			$buffer = trim(fgets($handle));
			if($buffer) $cnt++;
		}
		fclose($handle);
		return $cnt;
	}
	
	public static function SortFileIds($fn)
	{
		if(!file_exists($fn)) return 0;

		$arIds = array();
		$handle = fopen($fn, 'r');
		while (!feof($handle)) {
			$buffer = trim(fgets($handle, 128));
			if($buffer) $arIds[] = (int)$buffer;
		}
		fclose($handle);
		sort($arIds, SORT_NUMERIC);

		unlink($fn);

		$handle = fopen($fn, 'a');
		$cnt = count($arIds);
		$step = 10000;
		for($i=0; $i<$cnt; $i+=$step)
		{
			fwrite($handle, implode("\r\n", array_slice($arIds, $i, $step))."\r\n");
		}
		fclose($handle);
		
		if($cnt > 0) return end($arIds);
		else return 0;
	}
	
	public static function GetPartIdsFromFile($fn, $min)
	{
		if(!file_exists($fn)) return array();

		$cnt = 0;
		$maxCnt = 5000;
		$arIds = array();
		$handle = fopen($fn, 'r');
		while (!feof($handle) && $maxCnt>$cnt) {
			$buffer = (int)trim(fgets($handle, 128));
			if($buffer > $min)
			{
				$arIds[] = (int)$buffer;
				$cnt++;
			}
		}
		fclose($handle);
		return $arIds;
	}
	
	public static function GetFileArray($id)
	{
		if(class_exists('\Bitrix\Main\FileTable'))
		{
			$arFile = \Bitrix\Main\FileTable::getList(array('filter'=>array('ID'=>$id)))->fetch();
			if(is_callable(array($arFile['TIMESTAMP_X'], 'toString'))) $arFile['TIMESTAMP_X'] = $arFile['TIMESTAMP_X']->toString();
			$arFile['SRC'] = \CFile::GetFileSRC($arFile, false, false);
		}
		else
		{
			$arFile = \CFile::GetFileArray($id);
		}
		return $arFile;
	}
	
	public static function SaveFile($arFile, $strSavePath=false, $bForceMD5=false, $bSkipExt=false)
	{
		if($arFile['type']=='text/html')
		{
			$arFile = self::MakeFileArray($arFile);
		}

		if($strSavePath===false) $strSavePath = static::$moduleId;
		$isUtf = (bool)(defined("BX_UTF") && BX_UTF);
		if(CUtil::DetectUTF8($arFile["name"]))
		{
			if(!$isUtf) $arFile["name"] = \Bitrix\Main\Text\Encoding::convertEncoding($arFile["name"], 'utf-8', LANG_CHARSET);
		}
		else
		{
			if($isUtf) $arFile["name"] = \Bitrix\Main\Text\Encoding::convertEncoding($arFile["name"], 'windows-1251', LANG_CHARSET);
		}
		$strFileName = GetFileName($arFile["name"]);	/* filename.gif */
		if(strpos($strFileName, '.')===0) $strFileName = '_'.$strFileName;

		if(isset($arFile["del"]) && $arFile["del"] <> '')
		{
			CFile::DoDelete($arFile["old_file"]);
			if($strFileName == '')
				return "NULL";
		}

		if($arFile["name"] == '')
		{
			if(isset($arFile["description"]) && intval($arFile["old_file"])>0)
			{
				CFile::UpdateDesc($arFile["old_file"], $arFile["description"]);
			}
			return false;
		}

		if (isset($arFile["content"]))
		{
			if (!isset($arFile["size"]))
			{
				$arFile["size"] = CUtil::BinStrlen($arFile["content"]);
			}
		}
		else
		{
			try
			{
				$file = new \Bitrix\Main\IO\File(\Bitrix\Main\IO\Path::convertPhysicalToLogical($arFile["tmp_name"]));
				$arFile["size"] = $file->getSize();
			}
			catch(IO\IoException $e)
			{
				$arFile["size"] = 0;
			}
		}

		$arFile["ORIGINAL_NAME"] = $strFileName;

		//translit, replace unsafe chars, etc.
		$strFileName = self::transformName($strFileName, $bForceMD5, $bSkipExt);

		//transformed name must be valid, check disk quota, etc.
		if (self::validateFile($strFileName, $arFile) !== "")
		{
			return false;
		}

		if($arFile["type"] == "image/pjpeg" || $arFile["type"] == "image/jpg")
		{
			$arFile["type"] = "image/jpeg";
		}

		$bExternalStorage = false;
		/*foreach(GetModuleEvents("main", "OnFileSave", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arFile, $strFileName, $strSavePath, $bForceMD5, $bSkipExt)))
			{
				$bExternalStorage = true;
				break;
			}
		}*/

		if(!$bExternalStorage)
		{
			$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");
			$io = CBXVirtualIo::GetInstance();
			if($bForceMD5 != true)
			{
				$dir_add = '';
				$i=0;
				while(true)
				{
					$dir_add = substr(md5(uniqid("", true)), 0, 3);
					if(!$io->FileExists($_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/".$dir_add."/".$strFileName))
					{
						break;
					}
					if($i >= 25)
					{
						$j=0;
						while(true)
						{
							$dir_add = substr(md5(mt_rand()), 0, 3)."/".substr(md5(mt_rand()), 0, 3);
							if(!$io->FileExists($_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/".$dir_add."/".$strFileName))
							{
								break;
							}
							if($j >= 25)
							{
								$dir_add = substr(md5(mt_rand()), 0, 3)."/".md5(mt_rand());
								break;
							}
							$j++;
						}
						break;
					}
					$i++;
				}
				if(substr($strSavePath, -1, 1) <> "/")
					$strSavePath .= "/".$dir_add;
				else
					$strSavePath .= $dir_add."/";
			}
			else
			{
				$strFileExt = ($bSkipExt == true || ($ext = self::GetFileExtension($strFileName)) == ''? '' : ".".$ext);
				while(true)
				{
					if(substr($strSavePath, -1, 1) <> "/")
						$strSavePath .= "/".substr($strFileName, 0, 3);
					else
						$strSavePath .= substr($strFileName, 0, 3)."/";

					if(!$io->FileExists($_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/".$strFileName))
						break;

					//try the new name
					$strFileName = md5(uniqid("", true)).$strFileExt;
				}
			}

			$arFile["SUBDIR"] = $strSavePath;
			$arFile["FILE_NAME"] = $strFileName;
			$strDirName = $_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/";
			$strDbFileNameX = $strDirName.$strFileName;
			$strPhysicalFileNameX = $io->GetPhysicalName($strDbFileNameX);

			CheckDirPath($strDirName);

			if(is_set($arFile, "content"))
			{
				$f = fopen($strPhysicalFileNameX, "ab");
				if(!$f)
					return false;
				if(fwrite($f, $arFile["content"]) === false)
					return false;
				fclose($f);
			}
			elseif(
				!copy($arFile["tmp_name"], $strPhysicalFileNameX)
				&& !move_uploaded_file($arFile["tmp_name"], $strPhysicalFileNameX)
			)
			{
				CFile::DoDelete($arFile["old_file"]);
				return false;
			}

			if(isset($arFile["old_file"]))
				CFile::DoDelete($arFile["old_file"]);

			@chmod($strPhysicalFileNameX, BX_FILE_PERMISSIONS);

			//flash is not an image
			$flashEnabled = !CFile::IsImage($arFile["ORIGINAL_NAME"], $arFile["type"]);

			$imgArray = CFile::GetImageSize($strDbFileNameX, false, $flashEnabled);

			if(is_array($imgArray))
			{
				$arFile["WIDTH"] = $imgArray[0];
				$arFile["HEIGHT"] = $imgArray[1];

				if($imgArray[2] == IMAGETYPE_JPEG)
				{
					$exifData = CFile::ExtractImageExif($io->GetPhysicalName($strDbFileNameX));
					if ($exifData  && isset($exifData['Orientation']))
					{
						//swap width and height
						if ($exifData['Orientation'] >= 5 && $exifData['Orientation'] <= 8)
						{
							$arFile["WIDTH"] = $imgArray[1];
							$arFile["HEIGHT"] = $imgArray[0];
						}

						$properlyOriented = CFile::ImageHandleOrientation($exifData['Orientation'], $io->GetPhysicalName($strDbFileNameX));
						if ($properlyOriented)
						{
							$jpgQuality = intval(COption::GetOptionString('main', 'image_resize_quality', '95'));
							if($jpgQuality <= 0 || $jpgQuality > 100)
								$jpgQuality = 95;
							imagejpeg($properlyOriented, $io->GetPhysicalName($strDbFileNameX), $jpgQuality);
						}
					}
				}
			}
			else
			{
				$arFile["WIDTH"] = 0;
				$arFile["HEIGHT"] = 0;
			}
		}

		if($arFile["WIDTH"] == 0 || $arFile["HEIGHT"] == 0)
		{
			//mock image because we got false from CFile::GetImageSize()
			if(strpos($arFile["type"], "image/") === 0)
			{
				$arFile["type"] = "application/octet-stream";
			}
		}

		if($arFile["type"] == '' || !is_string($arFile["type"]))
		{
			$arFile["type"] = "application/octet-stream";
		}

		/****************************** QUOTA ******************************/
		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			CDiskQuota::updateDiskQuota("file", $arFile["size"], "insert");
		}
		/****************************** QUOTA ******************************/

		$NEW_IMAGE_ID = CFile::DoInsert(array(
			"HEIGHT" => $arFile["HEIGHT"],
			"WIDTH" => $arFile["WIDTH"],
			"FILE_SIZE" => $arFile["size"],
			"CONTENT_TYPE" => $arFile["type"],
			"SUBDIR" => $arFile["SUBDIR"],
			"FILE_NAME" => $arFile["FILE_NAME"],
			"MODULE_ID" => $arFile["MODULE_ID"],
			"ORIGINAL_NAME" => $arFile["ORIGINAL_NAME"],
			"DESCRIPTION" => isset($arFile["description"])? $arFile["description"]: '',
			"HANDLER_ID" => isset($arFile["HANDLER_ID"])? $arFile["HANDLER_ID"]: '',
			"EXTERNAL_ID" => isset($arFile["external_id"])? $arFile["external_id"]: md5(mt_rand()),
		));

		CFile::CleanCache($NEW_IMAGE_ID);
		
		if($arFile["del_old"]=='Y' && strpos($strSavePath, static::$moduleId)===0 && isset($arFile["external_id"]) && strlen($arFile["external_id"]) > 0)
		{
			self::DeleteFilesByExtId($arFile["external_id"], $NEW_IMAGE_ID);
		}
			
		return $NEW_IMAGE_ID;
	}
	
	public static function DeleteFilesByExtId($extId, $id='')
	{
		$dbRes = \CFile::GetList(array(), array('EXTERNAL_ID'=>$extId));
		while($arr = $dbRes->Fetch())
		{
			if($arr['ID']!=$id)
			{
				self::DeleteFile($arr['ID']);
			}
		}
	}
	
	public static function DeleteFile($FILE_ID)
	{
		CFile::Delete($FILE_ID);
		\Bitrix\KdaImportexcel\ZipArchive::RemoveFileDir($FILE_ID);
	}
	
	public static function CopyFile($FILE_ID, $bRegister = true, $newPath = "")
	{
		global $DB;

		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		$z = CFile::GetByID($FILE_ID);
		if($zr = $z->Fetch())
		{
			/****************************** QUOTA ******************************/
			if (COption::GetOptionInt("main", "disk_space") > 0)
			{
				$quota = new CDiskQuota();
				if (!$quota->checkDiskQuota($zr))
					return false;
			}
			/****************************** QUOTA ******************************/

			$strNewFile = '';
			$bSaved = false;
			$bExternalStorage = false;
			foreach(GetModuleEvents("main", "OnFileCopy", true) as $arEvent)
			{
				if($bSaved = ExecuteModuleEventEx($arEvent, array(&$zr, $newPath)))
				{
					$bExternalStorage = true;
					break;
				}
			}

			$io = CBXVirtualIo::GetInstance();

			if(!$bExternalStorage)
			{
				$strDirName = $_SERVER["DOCUMENT_ROOT"]."/".(COption::GetOptionString("main", "upload_dir", "upload"));
				$strDirName = rtrim(str_replace("//","/",$strDirName), "/");

				$zr["SUBDIR"] = trim($zr["SUBDIR"], "/");
				$zr["FILE_NAME"] = ltrim($zr["FILE_NAME"], "/");

				$strOldFile = $strDirName."/".$zr["SUBDIR"]."/".$zr["FILE_NAME"];

				if(strlen($newPath))
					$strNewFile = $strDirName."/".ltrim($newPath, "/");
				else
				{
					$i = 1;
					while(($strNewFile = $strDirName."/".$zr["SUBDIR"]."/".preg_replace('/(\.[^\.]*)$/', '['.$i.']$1', $zr["FILE_NAME"])) && $io->FileExists($strNewFile) && $i<1000)
					{
						$i++;
					}
				}

				$zr["FILE_NAME"] = bx_basename($strNewFile);
				$zr["SUBDIR"] = substr($strNewFile, strlen($strDirName)+1, -(strlen(bx_basename($strNewFile)) + 1));

				if(strlen($newPath))
					CheckDirPath($strNewFile);

				$bSaved = copy($io->GetPhysicalName($strOldFile), $io->GetPhysicalName($strNewFile));
			}

			if($bSaved)
			{
				if($bRegister)
				{
					$arFields = array(
						"TIMESTAMP_X" => $DB->GetNowFunction(),
						"MODULE_ID" => "'".$DB->ForSql($zr["MODULE_ID"], 50)."'",
						"HEIGHT" => intval($zr["HEIGHT"]),
						"WIDTH" => intval($zr["WIDTH"]),
						"FILE_SIZE" => intval($zr["FILE_SIZE"]),
						"ORIGINAL_NAME" => "'".$DB->ForSql($zr["ORIGINAL_NAME"], 255)."'",
						"DESCRIPTION" => "'".$DB->ForSql($zr["DESCRIPTION"], 255)."'",
						"CONTENT_TYPE" => "'".$DB->ForSql($zr["CONTENT_TYPE"], 255)."'",
						"SUBDIR" => "'".$DB->ForSql($zr["SUBDIR"], 255)."'",
						"FILE_NAME" => "'".$DB->ForSql($zr["FILE_NAME"], 255)."'",
						"HANDLER_ID" => $zr["HANDLER_ID"]? intval($zr["HANDLER_ID"]): "null",
						"EXTERNAL_ID" => $zr["EXTERNAL_ID"] != ""? "'".$DB->ForSql($zr["EXTERNAL_ID"], 50)."'": "null",
					);
					$NEW_FILE_ID = $DB->Insert("b_file",$arFields, $err_mess.__LINE__);

					if (COption::GetOptionInt("main", "disk_space") > 0)
						CDiskQuota::updateDiskQuota("file", $zr["FILE_SIZE"], "copy");

					CFile::CleanCache($NEW_FILE_ID);

					return $NEW_FILE_ID;
				}
				else
				{
					if(!$bExternalStorage)
						return substr($strNewFile, strlen(rtrim($_SERVER["DOCUMENT_ROOT"], "/")));
					else
						return $bSaved;
				}
			}
			else
			{
				return false;
			}
		}
		return 0;
	}
	
	public static function SaveStat()
	{
		$lastTime = COption::GetOptionInt('main', 'IE_STAT_TIME', 0);
		if($lastTime > time()-24*60*60) return;
		if(0 && class_exists('\Bitrix\Main\Web\HttpClient') && is_callable(array('CUpdateClientPartner', 'GetUpdatesList')))
		{
			include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");
			$arUpdateList = CUpdateClientPartner::GetUpdatesList($errorMessage, LANG, 'Y', array(static::$moduleId), Array("fullmoduleinfo" => "Y"));
			$arModuleData = array();
			
			if(is_array($arUpdateList['MODULE']))
			{
				foreach($arUpdateList['MODULE'] as $arModule)
				{
					if($arModule['@']['ID']==static::$moduleId)
					{
						$arModuleData = array(
							'FREE_MODULE' => $arModule['@']['FREE_MODULE'],
							'DATE_FROM' => $arModule['@']['DATE_FROM'],
							'DATE_TO' => $arModule['@']['DATE_TO'],
							'UPDATE_END' => $arModule['@']['UPDATE_END'],
							'UPDATES_LOADED' => (empty($arModule['#']) ? 'N' : 'Y')
						);
					}
				}
			}
			
			$arPostData = array(
				'MODULE_ID' => static::$moduleId,
				'REFERER' => $_SERVER['HTTP_HOST'],
				'ENCODING_UTF8' => ((defined('BX_UTF') && BX_UTF) ? 'Y' : 'N')
			);
			$DemoMode = CModule::IncludeModuleEx(static::$moduleId);
			$arPostData['DEMO_MODE'] = ($DemoMode==MODULE_DEMO ? 'Y' : 'N');
			//$arPostData['DEMO_EXPIRE'] = (defined("kda_importexcel_OLDSITEEXPIREDATE") ? kda_importexcel_OLDSITEEXPIREDATE : 0);
			$arPostData = array_merge($arPostData, $arModuleData);
			$client = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>3, 'disableSslVerification'=>true));
			$client->post('http://esolutions.su/marketplace/stat.php', $arPostData);
			
			if($arUpdateList && !$arUpdateList['ERROR'] && $arPostData['DEMO_MODE']!='Y' && !$arPostData['DATE_FROM'])
			{
				/*kda_importexcel_show_demo(true);
				die();*/
			}
		}
		COption::SetOptionInt('main', 'IE_STAT_TIME', time());
	}
	
	public function transformName($name, $bForceMD5=false, $bSkipExt=false)
	{
		//safe filename without path
		$fileName = GetFileName($name);

		$originalName = ($bForceMD5 != true);
		if($originalName)
		{
			//transforming original name:

			//transliteration
			if(COption::GetOptionString("main", "translit_original_file_name", "N") == "Y")
			{
				$fileName = CUtil::translit($fileName, LANGUAGE_ID, array("max_len"=>1024, "safe_chars"=>".", "replace_space" => '-'));
			}

			//replace invalid characters
			if(COption::GetOptionString("main", "convert_original_file_name", "Y") == "Y")
			{
				$io = CBXVirtualIo::GetInstance();
				$fileName = $io->RandomizeInvalidFilename($fileName);
			}
		}

		//.jpe is not image type on many systems
		if($bSkipExt == false && strtolower(self::GetFileExtension($fileName)) == "jpe")
		{
			$fileName = substr($fileName, 0, -4).".jpg";
		}

		//double extension vulnerability
		$fileName = RemoveScriptExtension($fileName);

		if(!$originalName)
		{
			//name is md5-generated:
			$fileName = md5(uniqid("", true)).($bSkipExt == true || ($ext = self::GetFileExtension($fileName)) == ''? '' : ".".$ext);
		}

		return $fileName;
	}

	protected function validateFile($strFileName, $arFile)
	{
		if($strFileName == '')
			return GetMessage("FILE_BAD_FILENAME");

		$io = CBXVirtualIo::GetInstance();
		if(!$io->ValidateFilenameString($strFileName))
			return GetMessage("MAIN_BAD_FILENAME1");

		if(strlen($strFileName) > 255)
			return GetMessage("MAIN_BAD_FILENAME_LEN");

		//check .htaccess etc.
		if(IsFileUnsafe($strFileName))
			return GetMessage("FILE_BAD_TYPE");

		//nginx returns octet-stream for .jpg
		if(GetFileNameWithoutExtension($strFileName) == '')
			return GetMessage("FILE_BAD_FILENAME");

		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$quota = new CDiskQuota();
			if (!$quota->checkDiskQuota($arFile))
				return GetMessage("FILE_BAD_QUOTA");
		}

		return "";
	}
	
	public static function GetFilesByExt($path, $arExt=array(), $checkSubdirs=true)
	{
		$arFiles = array();
		$arDirFiles = array_diff(scandir($path), array('.', '..'));
		foreach($arDirFiles as $file)
		{
			if(is_file($path.$file) && (empty($arExt) || preg_match('/\.('.implode('|', $arExt).')$/i', ToLower($file))))
			{
				$arFiles[] = $path.$file;
			}
		}
		if(!empty($arFiles)) return $arFiles;
		if($checkSubdirs===true || $checkSubdirs>0)
		{
			foreach($arDirFiles as $file)
			{
				if(is_dir($path.$file))
				{
					$arFiles = array_merge($arFiles, self::GetFilesByExt($path.$file.'/', $arExt, ($checkSubdirs===true ? $checkSubdirs : $checkSubdirs -1)));
				}
			}
		}
		return $arFiles;
	}
	
	public static function GetFileSystemEncoding()
	{
		if(!isset(static::$fileSystemEncoding))
		{
			$fileSystemEncoding = strtolower(defined("BX_FILE_SYSTEM_ENCODING") ? BX_FILE_SYSTEM_ENCODING : "");

			if (empty($fileSystemEncoding))
			{
				if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN")
					$fileSystemEncoding =  "windows-1251";
				else
					$fileSystemEncoding = "utf-8";
			}
			static::$fileSystemEncoding = $fileSystemEncoding;
		}
		return static::$fileSystemEncoding;
	}
	
	public static function CorrectEncodingForExtractDir($path)
	{
		$fileSystemEncoding = self::GetFileSystemEncoding();
		$arFiles = array();
		$arDirFiles = array_diff(scandir($path), array('.', '..'));
		foreach($arDirFiles as $file)
		{
			if(preg_match('/[^A-Za-z0-9_\-\.\s]/', $file) && ($fileSystemEncoding!='utf-8' || preg_match('/[^A-Za-z0-9_\-\p{Cyrillic}\.\s]/u', $file)))
			{
				$newfile = \Bitrix\Main\Text\Encoding::convertEncoding($file, $fileSystemEncoding, "cp866");
				$isUtf8 = CUtil::DetectUTF8($newfile);
				if($isUtf8 && $fileSystemEncoding!='utf-8')
				{
					$newfile = \Bitrix\Main\Text\Encoding::convertEncoding($newfile, 'utf-8', $fileSystemEncoding);
				}
				elseif(!$isUtf8 && $fileSystemEncoding=='utf-8')
				{
					$newfile = \Bitrix\Main\Text\Encoding::convertEncoding($newfile, 'windows-1251', $fileSystemEncoding);
				}
				$res = rename($path.$file, $path.$newfile);
				$file = $newfile;
			}
			if(is_dir($path.$file))
			{
				self::CorrectEncodingForExtractDir($path.$file.'/');
			}
		}
	}
	
	public static function GetDateFormat($m)
	{
		$format = str_replace('_', ' ', $m[1]);
		return ToLower(CIBlockFormatProperties::DateFormat($format, time()));
	}
	
	public static function MergeCookie(&$arCookies, $arNewCookies)
	{
		if(!is_array($arCookies)) $arCookies = array();
		if(!is_array($arNewCookies)) $arNewCookies = array();
		foreach($arNewCookies as $k=>$v)
		{
			/*if(!isset($arCookies[$k]) || strpos(Tolower($k), 'session')===false)
			{
				$arCookies[$k] = $v;
			}*/
			$arCookies[$k] = $v;
		}
	}
	
	public static function GetNewLocation(&$location, $newLoc)
	{
		$arUrl = parse_url($location);
		$newLoc = trim($newLoc);
		$location = $newLoc;
		if(strlen($newLoc) > 0 && stripos($newLoc, 'http')!==0)
		{
			if(strpos($newLoc, '/')===0)
			{
				$location = $arUrl['scheme'].'://'.$arUrl['host'].$newLoc;
			}
			else
			{
				$dir = preg_replace('/[\/]+/', '/', preg_replace('/(^|\/)[^\/]*$/', '', $arUrl['path']).'/');
				$location = $arUrl['scheme'].'://'.$arUrl['host'].$dir.$newLoc;
			}
		}
	}
	
	public static function MakeFileArray($path, $maxTime = 0)
	{
		$userAgent = self::GetUserAgent();
		$arExt = array('csv', 'xls', 'xlsx', 'xlsm', 'dbf');
		if(is_array($path))
		{
			$arFile = $path;
			$temp_path = CFile::GetTempName('', \Bitrix\Main\IO\Path::convertLogicalToPhysical($arFile["name"]));
			CheckDirPath($temp_path);
			if(!copy($arFile["tmp_name"], $temp_path)
				&& !move_uploaded_file($arFile["tmp_name"], $temp_path))
			{
				return false;
			}
			$arFile = CFile::MakeFileArray($temp_path);
			if(isset($path['type'])) $arFile['type'] = $path['type'];
		}
		else
		{
			$path = trim($path);
			
			$arCookies = array();
			$arHeaders = array('User-Agent' => $userAgent);
			if(preg_match('/^\{.*\}$/s', $path))
			{
				$arParams = CUtil::JsObjectToPhp($path);
				if(isset($arParams['FILELINK']))
				{
					$path = $arParams['FILELINK'];
					
					if(is_array($arParams['VARS']) && $arParams['PAGEAUTH'])
					{
						$redirectCount = 0;
						$location = $arParams['PAGEAUTH'];
						while(strlen($location)>0 && $redirectCount<=5)
						{
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true, 'redirect'=>false));
							$client->setCookies($arCookies);
							foreach($arHeaders as $hk=>$hv) $client->setHeader($hk, $hv);
							$res = $client->get($location);
							$arHeaders['Referer'] = $location;
							self::MergeCookie($arCookies, $client->getCookies()->toArray());
							self::GetNewLocation($location, $client->getHeaders()->get("Location"));
							$status = $client->getStatus();
							if($status != 302 && $status != 303) $location = '';
							$redirectCount++;
						}
						$needEncoding = $siteEncoding = self::getSiteEncoding();
						if(preg_match('/charset=(.*)(;|$)/', $client->getHeaders()->get("Content-Type"), $m) && strlen(trim($m[1])) > 0)
						{
							$needEncoding = ToLower(trim($m[1]));
						}
						foreach($arParams['VARS'] as $k=>$v)
						{
							if(strlen(trim($v)) > 0 && $needEncoding!=$siteEncoding)
							{
								$arParams['VARS'][$k] = \Bitrix\Main\Text\Encoding::convertEncoding($v, $siteEncoding, $needEncoding);
							}
							if(strlen(trim($v))==0 
								&& preg_match('/<input[^>]*name=[\'"]'.addcslashes($k, '-').'[\'"][^>]*>/Uis', $res, $m1)
								&& preg_match('/value=[\'"]([^\'"]*)[\'"]/Uis', $m1[0], $m2))
							{
									$arParams['VARS'][$k] = html_entity_decode($m2[1], ENT_COMPAT, $siteEncoding);
							}
						}
						
						$redirectCount = 0;
						$location = ($arParams['POSTPAGEAUTH'] ? $arParams['POSTPAGEAUTH'] : $arParams['PAGEAUTH']);
						while(strlen($location)>0 && $redirectCount<=5)
						{
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true, 'redirect'=>false));
							$client->setCookies($arCookies);
							foreach($arHeaders as $hk=>$hv) $client->setHeader($hk, $hv);
							$res = $client->post($location, $arParams['VARS']);
							$status = $client->getStatus();
							if($status==404)
							{
								$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true, 'redirect'=>false));
								$client->setCookies($arCookies);
								foreach($arHeaders as $hk=>$hv) $client->setHeader($hk, $hv);
								$res = $client->get($location);
								$status = $client->getStatus();
							}
							$arHeaders['Referer'] = $location;
							self::MergeCookie($arCookies, $client->getCookies()->toArray());
							self::GetNewLocation($location, $client->getHeaders()->get("Location"));
							if($status != 302 && $status != 303) $location = '';
							$redirectCount++;
						}
						
						if(($path==($arParams['POSTPAGEAUTH'] ? $arParams['POSTPAGEAUTH'] : $arParams['PAGEAUTH'])) && preg_match('/<meta[^>]*http\-equiv=[\'"]?refresh[\'"]?[^>]*>/Uis', $res, $m1) && preg_match('/content=[\'"]\d*\s*;\s*url=([^\'"]*)[\'"]/', $m1[0], $m2))
						{
							$path = trim($m2[1]);
						}
					}
					
					if(strlen($arParams['HANDLER_FOR_LINK_BASE64']) > 0) $handler = base64_decode(trim($arParams['HANDLER_FOR_LINK_BASE64']));
					else $handler = trim($arParams['HANDLER_FOR_LINK']);
					if(strlen($handler) > 0)
					{
						$val = '';
						if($path)
						{
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true));
							$client->setCookies($arCookies);
							$client->setHeader('User-Agent', $userAgent);				
							$val = $client->get($path);
						}
						$res = self::ExecuteFilterExpression($val, $handler, '', $arCookies);
						if(is_array($res))
						{
							if(isset($res['PATH'])) $path = $res['PATH'];
							if(isset($res['COOKIES']) && is_array($res['COOKIES'])) $arCookies = array_merge($arCookies, $res['COOKIES']);
						}
						else
						{
							$path = $res;
						}
					}
				}
			}
			
			$path = preg_replace_callback('/\{DATE_(\S*)\}/', array('CKDAImportUtils', 'GetDateFormat'), $path);
			if(!$maxTime) $maxTime = min(intval(ini_get('max_execution_time')) - 5, 300);
			if($maxTime<=0) $maxTime=55;
			$cloud = new \Bitrix\KdaImportexcel\Cloud();
			if($service = $cloud->GetService($path))
			{
				$arFile = $cloud->MakeFileArray($service, $path);
			}
			elseif(($maxTime > 15 || !empty($arCookies)) && preg_match("#^(http[s]?)://#", $path) && class_exists('\Bitrix\Main\Web\HttpClient'))
			{
				$temp_path = '';
				$bExternalStorage = false;
				/*foreach(GetModuleEvents("main", "OnMakeFileArray", true) as $arEvent)
				{
					if(ExecuteModuleEventEx($arEvent, array($path, &$temp_path)))
					{
						$bExternalStorage = true;
						break;
					}
				}*/
				
				if(!$bExternalStorage)
				{
					$urlComponents = parse_url($path);
					if ($urlComponents && strlen($urlComponents["path"]) > 0)
						$temp_path = CFile::GetTempName('', bx_basename($urlComponents["path"]));
					else
						$temp_path = CFile::GetTempName('', bx_basename($path));

					$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>$maxTime, 'streamTimeout'=>$maxTime, 'disableSslVerification'=>true));
					$ob->setCookies($arCookies);
					$ob->setHeader('User-Agent', $userAgent);
					$download = false;
					if($ob->download($path, $temp_path) && !in_array($ob->getStatus(), array(404, 504)))
					{
						$download = true;
					}
					if($ob->getStatus()==404 || filesize($temp_path)==0)
					{
						$path = preg_replace_callback('/[^:@\/?=&#%!$\{\}]+/', create_function('$m', 'return rawurlencode($m[0]);'), $path);
						$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>$maxTime, 'streamTimeout'=>$maxTime, 'disableSslVerification'=>true));
						$ob->setCookies($arCookies);
						$ob->setHeader('User-Agent', $userAgent);
						$download = false;
						if($ob->download($path, $temp_path) && !in_array($ob->getStatus(), array(404, 504)))
						{
							$download = true;
						}
					}
					
					if(!$download && ($location = $ob->getHeaders()->get('location')))
					{
						if(((!defined('BX_UTF') || !BX_UTF)) && \CUtil::DetectUTF8($location)) $location = urlencode($GLOBALS['APPLICATION']->ConvertCharset($location, 'UTF-8', 'CP1251'));
						$arUrl = parse_url($location);
						if($arUrl['host'])
						{
							$path = $location;
						}
						else
						{
							if(strpos($location, '/')===0)
							{
								$arUrlPath = parse_url($path);
								$path = $arUrlPath['scheme'].'://'.$arUrlPath['host'].$location;
							}
							else
							{
								//$path = rtrim($path, '/').'/'.$location;
								$path = rtrim(preg_replace('/\/[^\/]*$/', '/', $path), '/').'/'.$location;
							}
						}
						
						$temp_path = CFile::GetTempName('', bx_basename($path));
						if($ob->download($path, $temp_path))
						{
							$download = true;
						}
					}

					if($download)
					{
						$hcd = $ob->getHeaders()->get('content-disposition');
						$hct = ToLower($ob->getHeaders()->get('content-type'));
						$ext = ToLower(self::GetFileExtension($temp_path));
						if($hcd && preg_match('/filename\*?=/',$hcd))
						{
							$hcdParts = array_map('trim', explode(';', $hcd));
							$hcdParts1 = preg_grep('/filename\*=UTF\-8\'\'/i', $hcdParts);
							$hcdParts2 = preg_grep('/filename=/i', $hcdParts);
							if(count($hcdParts1) > 0)
							{
								$hcdParts1 = explode("''", current($hcdParts1));
								$fn = urldecode(trim(end($hcdParts1), '"\' '));
								if((!defined('BX_UTF') || !BX_UTF)) $fn = $GLOBALS['APPLICATION']->ConvertCharset($fn, 'UTF-8', 'CP1251');
								$fn = \Bitrix\Main\IO\Path::convertLogicalToPhysical($fn);
								if(self::IsHtmlFile($temp_path, $fn)) $fn = $fn.'.html';
								if(strpos($temp_path, $fn)===false)
								{
									$temp_path = self::ReplaceFile($temp_path, preg_replace('/\/[^\/]+$/', '/'.$fn, $temp_path));
								}
							}
							elseif(count($hcdParts2) > 0)
							{
								$hcdParts2 = explode('=', current($hcdParts2));
								$fn = trim(end($hcdParts2), '"\' ');
								if(self::IsHtmlFile($temp_path, $fn)) $fn = $fn.'.html';
								if(strpos($temp_path, $fn)===false)
								{
									$temp_path = self::ReplaceFile($temp_path, preg_replace('/\/[^\/]+$/', '/'.$fn, $temp_path));
								}
							}
						}
						elseif(ToLower(substr($temp_path, -4))=='.php' && strpos(ToLower($path), 'csv')!==false)
						{
							$temp_path = self::ReplaceFile($temp_path, substr($temp_path, 0, -4).'.csv');
						}
						elseif((!$ext || $ext=='php' || $ext=='htm' || $ext=='html') && $hct && strpos($hct, 'text/html')!==false && strpos(ToLower($path), 'htm')!==false)
						{
							$siteEncoding = $fileEncoding = self::getSiteEncoding();
							if(preg_match('/charset=(.+)(;|$)/Uis', $hct, $m)) $fileEncoding = ToLower(trim($m[1]));
							$temp_path_new = self::GetNewFile(preg_replace('/\.[^\/\.]*$/Uis', '', $temp_path).'.html');
							$handle = fopen($temp_path, 'r');
							$handle2 = fopen($temp_path_new, 'a');
							$i = 0;
							while(!feof($handle))
							{
								$buffer2 = fread($handle, 1024*1024);
								$buffer2 = preg_replace('/(<\/tr>)\s*(<td)/Uis', '$1<tr>$2', $buffer2);
								if($siteEncoding!=$fileEncoding)
								{
									if($i==0)
									{
										$buffer2 = preg_replace('/(<meta[^>]*charset=)([^\s"\';]*)([^>]*>)/is', '$1'.$siteEncoding.'$3', $buffer2);
									}
									$buffer2 = \Bitrix\Main\Text\Encoding::convertEncoding($buffer2, $fileEncoding, $siteEncoding);
								}
								fwrite($handle2, $buffer2);
								$i++;
							}
							self::RemoveOldFile($temp_path);
							$temp_path = $temp_path_new;
						}
						elseif((!$ext || $ext=='php') && $hct && (strpos($hct, 'text/csv')!==false || strpos($hct, 'text/plain')!==false))
						{
							$temp_path = self::ReplaceFile($temp_path, $temp_path.'.csv');
						}
						elseif((!$ext || $ext=='php') && $hct && in_array($hct, array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')))
						{
							$temp_path = self::ReplaceFile($temp_path, $temp_path.'.xlsx');
						}
						elseif((!$ext || $ext=='php') && $hct && strpos($hct, 'text/html')!==false)
						{
							$content = file_get_contents($temp_path, false, null, 0, 65536);
							if(preg_match('/<meta[^>]*http\-equiv=[\'"]?refresh[\'"]?[^>]*>/Uis', $content, $m1) && preg_match('/content=[\'"]\d*\s*;\s*url=([^\'"]*)[\'"]/', $m1[0], $m2))
							{
								return self::MakeFileArray(trim($m2[1]), $maxTime);
							}
							elseif(preg_match('/<script[^>]*>\s*window\.location\.href\s*=\s*([^\r\n;]*)[\s\r\n;]*<\/script>/Uis', $content, $m1))
							{
								$path = preg_replace('/[\'"\s\+]+/', '', strtr($m1[1], array('window.location.href'=>$path, 'window.location'=>$path, 'screen.width'=>'1024', 'screen.height'=>'768')));
								return self::MakeFileArray($path, $maxTime);
							}
						}
						$arFile = CFile::MakeFileArray($temp_path);
					}
				}
				elseif($temp_path)
				{
					$arFile = CFile::MakeFileArray($temp_path);
				}
				
				if(strlen($arFile["type"])<=0)
					$arFile["type"] = "unknown";
			}
			elseif(preg_match('/ftp(s)?:\/\//', $path))
			{
				$sftp = new \Bitrix\KdaImportexcel\Sftp();
				$arFile = $sftp->MakeFileArray($path, $maxTime);
			}
			else
			{
				if(self::PathContainsMask($path) && !file_exists($path) && !file_exists($_SERVER['DOCUMENT_ROOT'].$path))
				{
					$arTmpFiles = self::GetFilesByMask($path);
					if(count($arTmpFiles) > 0)
					{
						$path = current($arTmpFiles);
					}
				}
				$arFile = CFile::MakeFileArray($path);
			}
		}
		
		$ext = ToLower(self::GetFileExtension($arFile['tmp_name']));
		if(in_array($arFile['type'], array('application/zip', 'application/x-zip-compressed', 'application/gzip', 'application/x-gzip', 'application/rar', 'application/x-rar', 'application/x-rar-compressed', 'application/octet-stream')) && !in_array($ext, $arExt))
		{
			$tmpsubdir = dirname($arFile['tmp_name']).'/zip/';
			CheckDirPath($tmpsubdir);	
			if(substr($ext, -3)=='.gz' && $ext!='tar.gz' && function_exists('gzopen'))
			{
				$handle1 = gzopen($arFile['tmp_name'], 'rb');
				$handle2 = fopen($tmpsubdir.substr(basename($arFile['tmp_name']), 0, -3), 'wb');
				while(!gzeof($handle1)) {
					fwrite($handle2, gzread($handle1, 4096));
				}
				fclose($handle2);
				gzclose($handle1);
			}
			elseif($ext=='rar' && class_exists('RarArchive'))
			{
				$rar = RarArchive::open($arFile['tmp_name']);
				$entries = $rar->getEntries();
				foreach($entries as $entry)
				{
					$entry->extract($tmpsubdir);
				}
				$rar->close();
			}
			else
			{
				$type = (in_array($ext, array('tar.gz', 'tgz')) ? 'TAR.GZ' : 'ZIP');
				$zipObj = CBXArchive::GetArchive($arFile['tmp_name'], $type);
				$zipObj->Unpack($tmpsubdir);
			}
			if($arFile['type']=='application/zip' && isset($service) && $service=='yadisk') self::CorrectEncodingForExtractDir($tmpsubdir);
			$arFile = array();
			if(!is_array($path)) $urlComponents = parse_url($path);
			else $urlComponents = array();
			if(isset($urlComponents['fragment']) && strlen($urlComponents['fragment']) > 0)
			{
				$fn = $tmpsubdir.ltrim($urlComponents['fragment'], '/');
				$arFiles = array($fn);
				if((strpos($fn, '*')!==false || (strpos($fn, '{')!==false && strpos($fn, '}')!==false)) && !file_exists($fn))
				{
					$arFiles = glob($fn, GLOB_BRACE);
				}
			}
			else
			{
				$arFiles = self::GetFilesByExt($tmpsubdir, $arExt);
			}
			if(count($arFiles) > 0)
			{
				$tmpfile = current($arFiles);
				$temp_path = CFile::GetTempName('', bx_basename($tmpfile));
				$dir = \Bitrix\Main\IO\Path::getDirectory($temp_path);
				\Bitrix\Main\IO\Directory::createDirectory($dir);
				copy($tmpfile, $temp_path);
				$arFile = CFile::MakeFileArray($temp_path);
			}
			DeleteDirFilesEx(substr($tmpsubdir, strlen($_SERVER['DOCUMENT_ROOT'])));
		}
		
		self::CheckHtmlFile($arFile, $path);
		return $arFile;
	}
	
	public static function IsHtmlFile($temp_path, $fn)
	{
		return (bool)(ToLower(self::GetFileExtension($fn))=='xls' && strpos(ToLower(file_get_contents($temp_path, false, null, 0, 1024)), '<html')!==false);
	}
	
	public static function PathContainsMask($path)
	{
		return (bool)((strpos($path, '*')!==false || (strpos($path, '{')!==false && strpos($path, '}')!==false)));
	}
	
	public static function GetFilesByMask($mask)
	{
		$arFiles = array();
		$prefix = (strpos($mask, $_SERVER['DOCUMENT_ROOT'])===0 ? '' : $_SERVER['DOCUMENT_ROOT']);
		if(strpos($mask, '/*/')===false)
		{
			$arFiles = glob($prefix.$mask, GLOB_BRACE);
		}
		else
		{
			$i = 1;
			while(empty($arFiles) && $i<8)
			{
				$arFiles = glob($prefix.str_replace('/*/', str_repeat('/*', $i).'/', $mask), GLOB_BRACE);
				$i++;
			}
		}
		if(empty($arFiles)) return array();
		
		usort($arFiles, create_function('$a,$b', 'return filemtime($a)>filemtime($b) ? -1 : 1;'));
		
		$arFiles = array_map(create_function('$n', 'return substr($n, strlen($_SERVER["DOCUMENT_ROOT"]));'), $arFiles);
		return $arFiles;
	}
	
	public static function GetNewFile($newName)
	{
		$temp_path = CFile::GetTempName('', bx_basename($newName));
		$temp_dir = \Bitrix\Main\IO\Path::getDirectory($temp_path);
		\Bitrix\Main\IO\Directory::createDirectory($temp_dir);
		return $temp_path;
	}
	
	public static function RemoveOldFile($old_temp_path)
	{
		unlink($old_temp_path);
		$dir = dirname($old_temp_path);
		if(count(array_diff(scandir($dir), array('.', '..')))==0)
		{
			rmdir($dir);
		}
	}
	
	public static function ReplaceFile($old_temp_path, $newName)
	{
		$temp_path = self::GetNewFile(\Bitrix\Main\IO\Path::convertLogicalToPhysical($newName));
		copy($old_temp_path, $temp_path);
		self::RemoveOldFile($old_temp_path);
		return $temp_path;
	}
	
	
	public static function CheckHtmlFile(&$arFile, $path)
	{
		if(is_array($path)) $path = '';
		$ext = ToLower(self::GetFileExtension($arFile['tmp_name']));
		if(in_array($ext, array('htm', 'html')) && class_exists('DOMDocument'))
		{
			/*Bom UTF-8*/
			$content = file_get_contents($arFile['tmp_name']);
			if(CUtil::DetectUTF8(substr($content, 0, 10000)) && (substr($content, 0, 3)!="\xEF\xBB\xBF"))
			{
				file_put_contents($arFile['tmp_name'], "\xEF\xBB\xBF".$content);
			}
			/*/Bom UTF-8*/
			
			$doc = new DOMDocument();
			$doc->preserveWhiteSpace = false;
			$doc->formatOutput = true;
			$doc->loadHTMLFile($arFile['tmp_name']);
			$tbl = $doc->getElementsByTagName('table');
			if($tbl->length > 0)
			{
				$withTags = false;
				$arParams = array();
				$arUrl = parse_url($path);
				if($arUrl['fragment'])
				{
					$arFragments = explode('&', $arUrl['fragment']);
					foreach($arFragments as $fragment)
					{
						$arVar = explode('=', $fragment, 2);
						if(count($arVar)==2)
						{
							$arParams[$arVar[0]] = $arVar[1];
						}
						elseif($fragment=='withtags')
						{
							$withTags = true;
						}
					}
				}
				$find = false;
				if(!empty($arParams))
				{
					$key = 0;
					while(!$find && $key<$tbl->length)
					{
						$tbl1 = $tbl->item($key);
						$subfind = true;
						foreach($arParams as $k=>$v)
						{
							if($tbl1->getAttribute($k)!=$v)
							{
								$subfind = false;
							}
						}
						$find = $subfind;
						if(!$find) $key++;
					}
				}
				if($find) $tbl = $tbl->item($key);
				else $tbl = $tbl->item(0);

				require_once(dirname(__FILE__).'/../../lib/PHPExcel/PHPExcel.php');
				$objKDAPHPExcel = new KDAPHPExcel();
				$worksheet = $objKDAPHPExcel->getActiveSheet();
				$arCols = range('A', 'Z');
				foreach(range('A', 'Z') as $v1)
				{
					foreach(range('A', 'Z') as $v2)
					{
						$arCols[] = $v1.$v2;
					}
				}
				$row = 1;
				
				foreach($tbl->childNodes as $node1)
				{
					if($node1->nodeName=='tr')
					{
						$col = 0;
						foreach($node1->childNodes as $node2)
						{
							if($node2->nodeName=='td')
							{
								$innerHTML = $node2->nodeValue;
								//value with tags
								if($withTags)
								{
									$innerHTML = '';
									$children = $node2->childNodes;
									foreach($children as $child)
									{
										$innerHTML .= $child->ownerDocument->saveXML($child);
									}
								}
								$worksheet->setCellValueExplicit($arCols[$col++].$row, self::GetCellValueCsv($innerHTML));
							}
						}
						$row++;
					}
				}
				
				$writerType = 'CSV';
				$objWriter = KDAPHPExcel_IOFactory::createWriter($objKDAPHPExcel, $writerType);
				$objWriter->setDelimiter(';');
				$objWriter->setUseBOM(true);
				
				$arFile['tmp_name'] = $arFile['tmp_name'].'.csv';
				$arFile['name'] = $arFile['name'].'.csv';
				$objWriter->save($arFile['tmp_name']);
			}
		}
	}
	
	public static function GetFileExtension($filename)
	{
		$filename = end(explode('/', $filename));
		$arParts = explode('.', $filename);
		if(count($arParts) > 1) 
		{
			$ext = trim(array_pop($arParts));
			if(strlen($ext)==0 || strlen($ext)>4 || preg_match('/^(\d+)$/', $ext)) return '';
			if(ToLower($ext)=='gz' && count($arParts) > 1)
			{
				$ext = array_pop($arParts).'.'.$ext;
			}
			return $ext;
		}
		else return '';
	}
	
	public static function GetShowFileBySettings($SETTINGS_DEFAULT)
	{
		$path = $link = '';
		if($SETTINGS_DEFAULT["EXT_DATA_FILE"])
		{
			if(preg_match('/^\{.*\}$/s', $SETTINGS_DEFAULT["EXT_DATA_FILE"]))
			{
				$arParams = CUtil::JsObjectToPhp($SETTINGS_DEFAULT["EXT_DATA_FILE"]);
				if(isset($arParams['FILELINK']))
				{
					$path = $arParams['FILELINK'];
				}
			}
			else
			{
				$path = $SETTINGS_DEFAULT["EXT_DATA_FILE"];
			}
			if($path) $link = $path;
		}
		elseif($SETTINGS_DEFAULT["EMAIL_DATA_FILE"])
		{
			$json = $SETTINGS_DEFAULT["EMAIL_DATA_FILE"];
			if(strlen($json) > 0 && strpos($json, '{')===false) $json = base64_decode($json);
			$arParams = CUtil::JsObjectToPhp($json);
			if(isset($arParams['EMAIL']))
			{
				$path = $arParams['EMAIL'];
			}
			if($SETTINGS_DEFAULT["URL_DATA_FILE"] && ($basename = bx_basename($SETTINGS_DEFAULT["URL_DATA_FILE"])))
			{
				$path = $basename.' <'.$path.'>';
			}
		}
		return array('link'=>$link, 'path'=>$path);
	}
	
	public static function GetCellValueCsv($val)
	{
		if((!defined('BX_UTF') || !BX_UTF) && !CUtil::DetectUTF8($val))
		{
			$val = $GLOBALS['APPLICATION']->ConvertCharset($val, 'CP1251', 'UTF-8');
		}
		return $val;
	}
	
	public static function AddFileInputActions()
	{
		//AddEventHandler("main", "OnEndBufferContent", Array("CKDAImportUtils", "AddFileInputActionsHandler"));
	}
	
	public static function AddFileInputActionsHandler(&$content)
	{
		return;
		//if(!function_exists('imap_open')) return;
		
		$comment = 'KDA_IE_CHOOSE_FILE';
		$commentBegin = '<!--'.$comment.'-->';
		$commentEnd = '<!--/'.$comment.'-->';
		$pos1 = strpos($content, $commentBegin);
		$pos2 = strpos($content, $commentEnd);
		if($pos1!==false && $pos2!==false)
		{
			$partContent = substr($content, $pos1, $pos2 + strlen($commentEnd) - $pos1);
			if(preg_match_all('/<script[^>]*>.*<\/script>/Uis', $partContent, $m))
			{
				$arScripts = preg_grep('/BX\.file_input\((\{.*\'bx_file_data_file\'.*\})\)[;<]/Uis', $m[0]);
				while(count($arScripts) > 1)
				{
					$script = array_pop($arScripts);
					if($pos = strrpos($partContent, $script))
					{
						$newPartContent = substr($partContent, 0, $pos).substr($partContent, $pos+strlen($script));
						$content = str_replace($partContent, $newPartContent, $content);
						$partContent = $newPartContent;
					}
				}
			}
			if(preg_match('/BX\.file_input\((\{.*\})\)\s*[:;<]/Us', $partContent, $m))
			{
				$json = $m[1];
				$arConfig = CUtil::JsObjectToPhp($json);
				array_walk_recursive($arConfig, create_function('&$n, $k', 'if($n=="true"){$n=true;}elseif($n=="false"){$n=false;}'));
				$arConfigEmail = array(
					'TEXT' => GetMessage("KDA_IE_FILE_SOURCE_EMAIL"),
					'GLOBAL_ICON' => 'adm-menu-upload-email',
					'ONCLICK' => 'EProfile.ShowEmailForm();'
				);
				$arConfig['menuNew'][] = $arConfigEmail;
				$arConfig['menuExist'][] = $arConfigEmail;
				$arConfigLinkAuth = array(
					'TEXT' => GetMessage("KDA_IE_FILE_SOURCE_LINKAUTH"),
					'GLOBAL_ICON' => 'adm-menu-upload-linkauth',
					'ONCLICK' => 'EProfile.ShowFileAuthForm();'
				);
				$arConfig['menuNew'][] = $arConfigLinkAuth;
				$arConfig['menuExist'][] = $arConfigLinkAuth;
				$newJson = CUtil::PHPToJSObject($arConfig);
				$newPartContent = str_replace($json, $newJson, $partContent);
				$content = str_replace($partContent, $newPartContent, $content);
			}
		}
	}
	
	public static function GetColLetterByIndex($index)
	{
		if(empty(static::$colLetters))
		{
			$arLetters = range('A', 'Z');
			foreach(range('A', 'Z') as $v1)
			{
				foreach(range('A', 'Z') as $v2)
				{
					$arLetters[] = $v1.$v2;
				}
			}
			foreach(range('A', 'Z') as $v1)
			{
				foreach(range('A', 'Z') as $v2)
				{
					foreach(range('A', 'Z') as $v3)
					{
						$arLetters[] = $v1.$v2.$v3;
					}
				}
			}
			static::$colLetters = $arLetters;
		}
		return static::$colLetters[$index];
	}
	
	public static function ExecuteFilterExpression($val, $expression, $altReturn = true, $arCookies=array())
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
	
	public static function ShowFilter($sTableID, $IBLOCK_ID, $FILTER)
	{
		global $APPLICATION;
		\CJSCore::Init('file_input');
		$sf = 'FILTER';

		Loader::includeModule('iblock');
		$bCatalog = Loader::includeModule('catalog');
		if($bCatalog)
		{
			$arCatalog = CCatalog::GetByID($IBLOCK_ID);
			if($arCatalog)
			{
				if(is_callable(array('CCatalogAdminTools', 'getIblockProductTypeList')))
				{
					$productTypeList = CCatalogAdminTools::getIblockProductTypeList($IBLOCK_ID, true);
				}
				
				$arStores = array();
				$dbRes = CCatalogStore::GetList(array("SORT"=>"ID"), array(), false, false, array("ID", "TITLE", "ADDRESS"));
				while($arStore = $dbRes->Fetch())
				{
					if(strlen($arStore['TITLE'])==0 && $arStore['ADDRESS']) $arStore['TITLE'] = $arStore['ADDRESS'];
					$arStores[] = $arStore;
				}
				
				$arPrices = array();
				$dbPriceType = CCatalogGroup::GetList(array("SORT" => "ASC"));
				while($arPriceType = $dbPriceType->Fetch())
				{
					if(strlen($arPriceType["NAME_LANG"])==0 && $arPriceType['NAME']) $arPriceType['NAME_LANG'] = $arPriceType['NAME'];
					$arPrices[] = $arPriceType;
				}
			}
			if(!$arCatalog) $bCatalog = false;
		}
		
		$arFields = (is_array($FILTER) ? $FILTER : array());
		$dbrFProps = CIBlockProperty::GetList(
			array(
				"SORT"=>"ASC",
				"NAME"=>"ASC"
			),
			array(
				"IBLOCK_ID"=>$IBLOCK_ID,
				"CHECK_PERMISSIONS"=>"N",
			)
		);

		$arProps = array();
		while ($arProp = $dbrFProps->GetNext())
		{
			if ($arProp["ACTIVE"] == "Y")
			{
				$arProp["PROPERTY_USER_TYPE"] = ('' != $arProp["USER_TYPE"] ? CIBlockProperty::GetUserType($arProp["USER_TYPE"]) : array());
				$arProps[] = $arProp;
			}
		}
		
		?>
		<script>var arClearHiddenFields = [];</script>
		<!--<form method="GET" name="find_form" id="find_form" action="">-->
		<div class="find_form_inner">
		<?
		$arFindFields = Array();
		//$arFindFields["IBEL_A_F_ID"] = GetMessage("KDA_IE_IBEL_A_F_ID");
		$arFindFields["IBEL_A_F_PARENT"] = GetMessage("KDA_IE_IBEL_A_F_PARENT");

		$arFindFields["IBEL_A_F_MODIFIED_WHEN"] = GetMessage("KDA_IE_IBEL_A_F_MODIFIED_WHEN");
		$arFindFields["IBEL_A_F_MODIFIED_BY"] = GetMessage("KDA_IE_IBEL_A_F_MODIFIED_BY");
		$arFindFields["IBEL_A_F_CREATED_WHEN"] = GetMessage("KDA_IE_IBEL_A_F_CREATED_WHEN");
		$arFindFields["IBEL_A_F_CREATED_BY"] = GetMessage("KDA_IE_IBEL_A_F_CREATED_BY");

		$arFindFields["IBEL_A_F_ACTIVE_FROM"] = GetMessage("KDA_IE_IBEL_A_ACTFROM");
		$arFindFields["IBEL_A_F_ACTIVE_TO"] = GetMessage("KDA_IE_IBEL_A_ACTTO");
		$arFindFields["IBEL_A_F_ACT"] = GetMessage("KDA_IE_IBEL_A_F_ACT");
		$arFindFields["IBEL_A_F_NAME"] = GetMessage("KDA_IE_IBEL_A_F_NAME");
		$arFindFields["IBEL_A_F_DESC"] = GetMessage("KDA_IE_IBEL_A_F_DESC");
		$arFindFields["IBEL_A_CODE"] = GetMessage("KDA_IE_IBEL_A_CODE");
		$arFindFields["IBEL_A_EXTERNAL_ID"] = GetMessage("KDA_IE_IBEL_A_EXTERNAL_ID");
		$arFindFields["IBEL_A_PREVIEW_PICTURE"] = GetMessage("KDA_IE_IBEL_A_PREVIEW_PICTURE");
		$arFindFields["IBEL_A_DETAIL_PICTURE"] = GetMessage("KDA_IE_IBEL_A_DETAIL_PICTURE");
		$arFindFields["IBEL_A_TAGS"] = GetMessage("KDA_IE_IBEL_A_TAGS");
		
		if ($bCatalog)
		{
			if(is_array($productTypeList)) $arFindFields["CATALOG_TYPE"] = GetMessage("KDA_IE_CATALOG_TYPE");
			$arFindFields["CATALOG_BUNDLE"] = GetMessage("KDA_IE_CATALOG_BUNDLE");
			$arFindFields["CATALOG_AVAILABLE"] = GetMessage("KDA_IE_CATALOG_AVAILABLE");
			$arFindFields["CATALOG_QUANTITY"] = GetMessage("KDA_IE_CATALOG_QUANTITY");
			if(is_array($arStores))
			{
				foreach($arStores as $arStore)
				{
					$arFindFields["CATALOG_STORE".$arStore['ID']."_QUANTITY"] = sprintf(GetMessage("KDA_IE_CATALOG_STORE_QUANTITY"), $arStore['TITLE']);
				}
			}
			if(is_array($arPrices))
			{
				foreach($arPrices as $arPrice)
				{
					$arFindFields["CATALOG_PRICE_".$arPrice['ID']] = sprintf(GetMessage("KDA_IE_CATALOG_PRICE"), $arPrice['NAME_LANG']);
				}
			}
		}

		foreach($arProps as $arProp)
			if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F")
				$arFindFields["IBEL_A_PROP_".$arProp["ID"]] = $arProp["NAME"];
		
		$oFilter = new CAdminFilter($sTableID."_filter", $arFindFields);
		
		$oFilter->Begin();
		?>
			<?/*?><tr>
				<td><?echo GetMessage("KDA_IE_FILTER_FROMTO_ID")?>:</td>
				<td nowrap>
					<input type="text" name="<?echo $sf;?>[find_el_id_start]" size="10" value="<?echo htmlspecialcharsex($arFields['find_el_id_start'])?>">
					...
					<input type="text" name="<?echo $sf;?>[find_el_id_end]" size="10" value="<?echo htmlspecialcharsex($arFields['find_el_id_end'])?>">
				</td>
			</tr><?*/?>

			<tr>
				<td><?echo GetMessage("KDA_IE_FIELD_SECTION_ID")?>:</td>
				<td>
					<select name="<?echo $sf;?>[find_section_section][]" multiple size="5">
						<option value="-1"<?if((is_array($arFields['find_section_section']) && in_array("-1", $arFields['find_section_section'])) || $arFields['find_section_section']=="-1")echo" selected"?>><?echo GetMessage("KDA_IE_VALUE_ANY")?></option>
						<option value="0"<?if((is_array($arFields['find_section_section']) && in_array("0", $arFields['find_section_section'])) || $arFields['find_section_section']=="0")echo" selected"?>><?echo GetMessage("KDA_IE_UPPER_LEVEL")?></option>
						<?
						$bsections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$IBLOCK_ID), array("ID", "NAME", "DEPTH_LEVEL"));
						while($ar = $bsections->GetNext()):
							?><option value="<?echo $ar["ID"]?>"<?if((is_array($arFields['find_section_section']) && in_array($ar["ID"], $arFields['find_section_section'])) || $ar["ID"]==$arFields['find_section_section'])echo " selected"?>><?echo str_repeat("&nbsp;.&nbsp;", $ar["DEPTH_LEVEL"])?><?echo $ar["NAME"]?></option><?
						endwhile;
						?>
					</select><br>
					<input type="checkbox" name="<?echo $sf;?>[find_el_subsections]" value="Y"<?if($arFields['find_el_subsections']=="Y")echo" checked"?>> <?echo GetMessage("KDA_IE_INCLUDING_SUBSECTIONS")?>
				</td>
			</tr>

			<tr>
				<td><?echo GetMessage("KDA_IE_FIELD_TIMESTAMP_X")?>:</td>
				<td><?echo CalendarPeriod($sf."[find_el_timestamp_from]", htmlspecialcharsex($arFields['find_el_timestamp_from']), $sf."[find_el_timestamp_to]", htmlspecialcharsex($arFields['find_el_timestamp_to']), "filter_form", "Y")?></font></td>
			</tr>

			<tr>
				<td><?=GetMessage("KDA_IE_FIELD_MODIFIED_BY")?>:</td>
				<td>
					<?echo FindUserID(
						$sf."[find_el_modified_user_id]",
						$arFields['find_el_modified_user_id'],
						"",
						"filter_form",
						"5",
						"",
						" ... ",
						"",
						""
					);?>
				</td>
			</tr>

			<tr>
				<td><?echo GetMessage("KDA_IE_EL_ADMIN_DCREATE")?>:</td>
				<td><?echo CalendarPeriod($sf."[find_el_created_from]", htmlspecialcharsex($arFields['find_el_created_from']), $sf."[find_el_created_to]", htmlspecialcharsex($arFields['find_el_created_to']), "filter_form", "Y")?></td>
			</tr>

			<tr>
				<td><?echo GetMessage("KDA_IE_EL_ADMIN_WCREATE")?></td>
				<td>
					<?echo FindUserID(
						$sf."[find_el_created_user_id]",
						$arFields['find_el_created_user_id'],
						"",
						"filter_form",
						"5",
						"",
						" ... ",
						"",
						""
					);?>
				</td>
			</tr>

			<tr>
				<td><?echo GetMessage("KDA_IE_EL_A_ACTFROM")?>:</td>
				<td><?echo CalendarPeriod($sf."[find_el_date_active_from_from]", htmlspecialcharsex($arFields['find_el_date_active_from_from']), $sf."[find_el_date_active_from_to]", htmlspecialcharsex($arFields['find_el_date_active_from_to']), "filter_form")?></td>
			</tr>

			<tr>
				<td><?echo GetMessage("KDA_IE_EL_A_ACTTO")?>:</td>
				<td><?echo CalendarPeriod($sf."[find_el_date_active_to_from]", htmlspecialcharsex($arFields['find_el_date_active_to_from']), $sf."[find_el_date_active_to_to]", htmlspecialcharsex($arFields['find_el_date_active_to_to']), "filter_form")?></td>
			</tr>

			<tr>
				<td><?echo GetMessage("KDA_IE_FIELD_ACTIVE")?>:</td>
				<td>
					<select name="<?echo $sf;?>[find_el_active]">
						<option value=""><?=htmlspecialcharsex(GetMessage('KDA_IE_VALUE_ANY'))?></option>
						<option value="Y"<?if($arFields['find_el_active']=="Y")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_YES"))?></option>
						<option value="N"<?if($arFields['find_el_active']=="N")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_NO"))?></option>
					</select>
				</td>
			</tr>

			<tr>
				<td><?echo GetMessage("KDA_IE_FIELD_NAME")?>:</td>
				<td><input type="text" name="<?echo $sf;?>[find_el_name]" value="<?echo htmlspecialcharsex($arFields['find_el_name'])?>" size="30"></td>
			</tr>
			<tr>
				<td><?echo GetMessage("KDA_IE_EL_ADMIN_DESC")?></td>
				<td><input type="text" name="<?echo $sf;?>[find_el_intext]" value="<?echo htmlspecialcharsex($arFields['find_el_intext'])?>" size="30"></td>
			</tr>

			<tr>
				<td><?=GetMessage("KDA_IE_EL_A_CODE")?>:</td>
				<td><input type="text" name="<?echo $sf;?>[find_el_code]" value="<?echo htmlspecialcharsex($arFields['find_el_code'])?>" size="30"></td>
			</tr>
			<tr>
				<td><?=GetMessage("KDA_IE_EL_A_EXTERNAL_ID")?>:</td>
				<td><input type="text" name="<?echo $sf;?>[find_el_external_id]" value="<?echo htmlspecialcharsex($arFields['find_el_external_id'])?>" size="30"></td>
			</tr>
			<tr>
				<td><?=GetMessage("KDA_IE_EL_A_PREVIEW_PICTURE")?>:</td>
				<td>
					<select name="<?echo $sf;?>[find_el_preview_picture]">
						<option value=""><?=htmlspecialcharsex(GetMessage('KDA_IE_VALUE_ANY'))?></option>
						<option value="Y"<?if($arFields['find_el_preview_picture']=="Y")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_IS_NOT_EMPTY"))?></option>
						<option value="N"<?if($arFields['find_el_preview_picture']=="N")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_IS_EMPTY"))?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?=GetMessage("KDA_IE_EL_A_DETAIL_PICTURE")?>:</td>
				<td>
					<select name="<?echo $sf;?>[find_el_detail_picture]">
						<option value=""><?=htmlspecialcharsex(GetMessage('KDA_IE_VALUE_ANY'))?></option>
						<option value="Y"<?if($arFields['find_el_detail_picture']=="Y")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_IS_NOT_EMPTY"))?></option>
						<option value="N"<?if($arFields['find_el_detail_picture']=="N")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_IS_EMPTY"))?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?=GetMessage("KDA_IE_EL_A_TAGS")?>:</td>
				<td>
					<input type="text" name="<?echo $sf;?>[find_el_tags]" value="<?echo htmlspecialcharsex($arFields['find_el_tags'])?>" size="30">
				</td>
			</tr>
			<?
			if ($bCatalog)
			{
				if(is_array($productTypeList))
				{
				?><tr>
					<td><?=GetMessage("KDA_IE_CATALOG_TYPE"); ?>:</td>
					<td>
						<select name="<?echo $sf;?>[find_el_catalog_type][]" multiple>
							<option value=""><?=htmlspecialcharsex(GetMessage('KDA_IE_VALUE_ANY'))?></option>
							<?
							$catalogTypes = (!empty($arFields['find_el_catalog_type']) ? $arFields['find_el_catalog_type'] : array());
							foreach ($productTypeList as $productType => $productTypeName)
							{
								?>
								<option value="<? echo $productType; ?>"<? echo (in_array($productType, $catalogTypes) ? ' selected' : ''); ?>><? echo htmlspecialcharsex($productTypeName); ?></option><?
							}
							unset($productType, $productTypeName, $catalogTypes);
							?>
						</select>
					</td>
				</tr>
				<?
				}
				?>
				<tr>
					<td><?echo GetMessage("KDA_IE_CATALOG_BUNDLE")?>:</td>
					<td>
						<select name="<?echo $sf;?>[find_el_catalog_bundle]">
							<option value=""><?=htmlspecialcharsex(GetMessage('KDA_IE_VALUE_ANY'))?></option>
							<option value="Y"<?if($arFields['find_el_catalog_bundle']=="Y")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_YES"))?></option>
							<option value="N"<?if($arFields['find_el_catalog_bundle']=="N")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_NO"))?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td><?echo GetMessage("KDA_IE_CATALOG_AVAILABLE")?>:</td>
					<td>
						<select name="<?echo $sf;?>[find_el_catalog_available]">
							<option value=""><?=htmlspecialcharsex(GetMessage('KDA_IE_VALUE_ANY'))?></option>
							<option value="Y"<?if($arFields['find_el_catalog_available']=="Y")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_YES"))?></option>
							<option value="N"<?if($arFields['find_el_catalog_available']=="N")echo " selected"?>><?=htmlspecialcharsex(GetMessage("KDA_IE_NO"))?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td><?echo GetMessage("KDA_IE_CATALOG_QUANTITY")?>:</td>
					<td>
						<select name="<?echo $sf;?>[find_el_catalog_quantity_comp]">
							<option value="eq" <?if($arFields['find_el_catalog_quantity_comp']=='eq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_EQ')?></option>
							<option value="gt" <?if($arFields['find_el_catalog_quantity_comp']=='gt'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_GT')?></option>
							<option value="geq" <?if($arFields['find_el_catalog_quantity_comp']=='geq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_GEQ')?></option>
							<option value="lt" <?if($arFields['find_el_catalog_quantity_comp']=='lt'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_LT')?></option>
							<option value="leq" <?if($arFields['find_el_catalog_quantity_comp']=='leq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_LEQ')?></option>
						</select>
						<input type="text" name="<?echo $sf;?>[find_el_catalog_quantity]" value="<?echo htmlspecialcharsex($arFields['find_el_catalog_quantity'])?>" size="10">
					</td>
				</tr>
				
				<?
				if(is_array($arStores))
				{
					foreach($arStores as $arStore)
					{
						?>
						<tr>
							<td><?echo sprintf(GetMessage("KDA_IE_CATALOG_STORE_QUANTITY"), $arStore['TITLE'])?>:</td>
							<td>
								<select name="<?echo $sf;?>[find_el_catalog_store<?echo $arStore['ID'];?>_quantity_comp]">
									<option value="eq" <?if($arFields['find_el_catalog_store'.$arStore['ID'].'_quantity_comp']=='eq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_EQ')?></option>
									<option value="gt" <?if($arFields['find_el_catalog_store'.$arStore['ID'].'_quantity_comp']=='gt'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_GT')?></option>
									<option value="geq" <?if($arFields['find_el_catalog_store'.$arStore['ID'].'_quantity_comp']=='geq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_GEQ')?></option>
									<option value="lt" <?if($arFields['find_el_catalog_store'.$arStore['ID'].'_quantity_comp']=='lt'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_LT')?></option>
									<option value="leq" <?if($arFields['find_el_catalog_store'.$arStore['ID'].'_quantity_comp']=='leq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_LEQ')?></option>
								</select>
								<input type="text" name="<?echo $sf;?>[find_el_catalog_store<?echo $arStore['ID'];?>_quantity]" value="<?echo htmlspecialcharsex($arFields['find_el_catalog_store'.$arStore['ID'].'_quantity'])?>" size="10">
							</td>
						</tr>
						<?
					}
				}
				
				if(is_array($arPrices))
				{
					foreach($arPrices as $arPrice)
					{
						?>
						<tr>
							<td><?echo sprintf(GetMessage("KDA_IE_CATALOG_PRICE"), $arPrice['NAME_LANG'])?>:</td>
							<td>
								<select name="<?echo $sf;?>[find_el_catalog_price_<?echo $arPrice['ID'];?>_comp]">
									<option value="eq" <?if($arFields['find_el_catalog_price_'.$arPrice['ID'].'_comp']=='eq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_EQ')?></option>
									<option value="empty" <?if($arFields['find_el_catalog_price_'.$arPrice['ID'].'_comp']=='empty'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_EMPTY')?></option>
									<option value="gt" <?if($arFields['find_el_catalog_price_'.$arPrice['ID'].'_comp']=='gt'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_GT')?></option>
									<option value="geq" <?if($arFields['find_el_catalog_price_'.$arPrice['ID'].'_comp']=='geq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_GEQ')?></option>
									<option value="lt" <?if($arFields['find_el_catalog_price_'.$arPrice['ID'].'_comp']=='lt'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_LT')?></option>
									<option value="leq" <?if($arFields['find_el_catalog_price_'.$arPrice['ID'].'_comp']=='leq'){echo 'selected';}?>><?=GetMessage('KDA_IE_COMPARE_LEQ')?></option>
								</select>
								<input type="text" name="<?echo $sf;?>[find_el_catalog_price_<?echo $arPrice['ID'];?>]" value="<?echo htmlspecialcharsex($arFields['find_el_catalog_price_'.$arPrice['ID']])?>" size="10">
							</td>
						</tr>
						<?
					}
				}
			}
			
		foreach($arProps as $arProp):
			if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F"):
		?>
		<tr>
			<td><?=$arProp["NAME"]?>:</td>
			<td>
				<?if(array_key_exists("GetAdminFilterHTML", $arProp["PROPERTY_USER_TYPE"])):
					$fieldName = "filter1_find_el_property_".$arProp["ID"];
					if(isset($arFields["find_el_property_".$arProp["ID"]."_from"])) $GLOBALS[$fieldName."_from"] = $arFields["find_el_property_".$arProp["ID"]."_from"];
					if(isset($arFields["find_el_property_".$arProp["ID"]."_to"])) $GLOBALS[$fieldName."_to"] = $arFields["find_el_property_".$arProp["ID"]."_to"];
					$GLOBALS[$fieldName] = $arFields["find_el_property_".$arProp["ID"]];
					$GLOBALS['set_filter'] = 'Y';
					echo call_user_func_array($arProp["PROPERTY_USER_TYPE"]["GetAdminFilterHTML"], array(
						$arProp,
						array(
							"VALUE" => $fieldName,
							"TABLE_ID" => $sTableID,
						),
					));
				elseif($arProp["PROPERTY_TYPE"]=='S'):?>
					<select class="esol-ix-filter-chval" name="<?echo $sf;?>[find_el_vtype_property_<?=$arProp["ID"]?>]">
						<option value=""><?echo Loc::getMessage("KDA_IE_IS_VALUE")?></option>
						<option value="contain"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='contain'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_VTYPE_CONTAIN")?></option>
						<option value="not_contain"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='not_contain'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_VTYPE_NOT_CONTAIN")?></option>
						<option value="begin_with"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='begin_with'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_VTYPE_BEGIN_WITH")?></option>
						<option value="end_on"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='end_on'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_VTYPE_END_ON")?></option>
						<option value="empty"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='empty'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_IS_EMPTY")?></option>
						<option value="not_empty"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='not_empty'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_IS_NOT_EMPTY")?></option>
					</select>
					<input type="text" name="<?echo $sf;?>[find_el_property_<?=$arProp["ID"]?>]" value="<?echo htmlspecialcharsex($arFields["find_el_property_".$arProp["ID"]])?>" size="30">
				<?elseif($arProp["PROPERTY_TYPE"]=='N' || $arProp["PROPERTY_TYPE"]=='E'):?>
					<select class="esol-ix-filter-chval" name="<?echo $sf;?>[find_el_vtype_property_<?=$arProp["ID"]?>]"><option value=""><?echo Loc::getMessage("KDA_IE_IS_VALUE")?></option><option value="empty"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='empty'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_IS_EMPTY")?></option><option value="not_empty"<?if($arFields["find_el_vtype_property_".$arProp["ID"]]=='not_empty'){echo ' selected';}?>><?echo Loc::getMessage("KDA_IE_IS_NOT_EMPTY")?></option></select><input type="text" name="<?echo $sf;?>[find_el_property_<?=$arProp["ID"]?>]" value="<?echo htmlspecialcharsex($arFields["find_el_property_".$arProp["ID"]])?>" size="30">
				<?elseif($arProp["PROPERTY_TYPE"]=='L'):?>
					<?
					$propVal = $arFields["find_el_property_".$arProp["ID"]];
					if(!is_array($propVal)) $propVal = array($propVal);
					?>
					<select name="<?echo $sf;?>[find_el_property_<?=$arProp["ID"]?>][]" multiple size="5">
						<option value=""><?echo GetMessage("KDA_IE_VALUE_ANY")?></option>
						<option value="NOT_REF"<?if(in_array("NOT_REF", $propVal))echo " selected"?>><?echo GetMessage("KDA_IE_ELEMENT_EDIT_NOT_SET")?></option><?
						$dbrPEnum = CIBlockPropertyEnum::GetList(Array("SORT"=>"ASC", "NAME"=>"ASC"), Array("PROPERTY_ID"=>$arProp["ID"]));
						while($arPEnum = $dbrPEnum->GetNext()):
						?>
							<option value="<?=$arPEnum["ID"]?>"<?if(in_array($arPEnum["ID"], $propVal))echo " selected"?>><?=$arPEnum["VALUE"]?></option>
						<?
						endwhile;
				?></select>
				<?
				elseif($arProp["PROPERTY_TYPE"]=='G'):
					echo self::ShowGroupPropertyField2($sf.'[find_el_property_'.$arProp["ID"].']', $arProp, $arFields["find_el_property_".$arProp["ID"]]);
				elseif(array_key_exists("GetPropertyFieldHtml", $arProp["PROPERTY_USER_TYPE"])):
					$inputHTML = call_user_func_array($arProp["PROPERTY_USER_TYPE"]["GetPropertyFieldHtml"], array(
						$arProp,
						array(
							"VALUE" => $arFields["find_el_property_".$arProp["ID"]],
							"DESCRIPTION" => '',
						),
						array(
							"VALUE" => "filter1_find_el_property_".$arProp["ID"],
							"DESCRIPTION" => '',
							"MODE"=>"iblock_element_admin",
							"FORM_NAME"=>"filter_form"
						),
					));
					$inputHTML = '<table style="margin: 0 0 5px 12px;"><tr id="tr_PROPERTY_'.$arProp["ID"].'"><td>'.$inputHTML.'</td></tr></table>';
					//$inputHTML = '<span class="adm-select-wrap">'.$inputHTML.'</span>';
					if(class_exists('\Bitrix\Main\Page\Asset') && class_exists('\Bitrix\Main\Page\AssetShowTargetType'))
					{
						$inputHTML = \Bitrix\Main\Page\Asset::getInstance()->GetJs(\Bitrix\Main\Page\AssetShowTargetType::TEMPLATE_PAGE).\Bitrix\Main\Page\Asset::getInstance()->GetCss(\Bitrix\Main\Page\AssetShowTargetType::TEMPLATE_PAGE).$inputHTML;
					}
					echo $inputHTML;
				endif;
				?>
			</td>
		</tr>
		<?
			endif;
		endforeach;

		$oFilter->Buttons();
		/*?><span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="set_filter" value="<? echo GetMessage("admin_lib_filter_set_butt"); ?>" title="<? echo GetMessage("admin_lib_filter_set_butt_title"); ?>" onClick="return EProfile.ApplyFilter(this);"></span>
		<span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="del_filter" value="<? echo GetMessage("admin_lib_filter_clear_butt"); ?>" title="<? echo GetMessage("admin_lib_filter_clear_butt_title"); ?>" onClick="return EList.DeleteFilter(this);"></span>
		<?*/
		$oFilter->End();
		
		?>
		<!--</form>-->
		</div>
		<?
	}
	
	public static function ShowFilterHighload($sTableID, $HLBL_ID, $FILTER)
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		\CJSCore::Init('file_input');
		$sf = 'FILTER';

		$arFields = (is_array($FILTER) ? $FILTER : array());
		$ufEntityId = 'HLBLOCK_'.$HLBL_ID;
		?>
		<script>var arClearHiddenFields = [];</script>
		<!--<form method="GET" name="find_form" id="find_form" action="">-->
		<div class="find_form_inner">
		<?
		$filterValues = array();
		$arFindFields = array('ID');
		
		$USER_FIELD_MANAGER->AdminListAddFilterFields($ufEntityId, $filterFields);
		//$USER_FIELD_MANAGER->AddFindFields($ufEntityId, $arFindFields);
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ufEntityId, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME=>$arUserField)
		{
			if(/*$arUserField["SHOW_FILTER"]!="N" &&*/ $arUserField["USER_TYPE"]["BASE_TYPE"]!="file")
			{
				$arFindFields[$FIELD_NAME] = (strlen(trim($arUserField['LIST_FILTER_LABEL'])) > 0 ? $arUserField['LIST_FILTER_LABEL'] : $FIELD_NAME);
			}
		}
		
		$oFilter = new CAdminFilter($sTableID."_filter", $arFindFields);
		
		$oFilter->Begin();
		
		?>
		<tr>
			<td>ID</td>
			<td><input type="text" name="<?echo $sf?>[find_ID]" size="47" value="<?echo htmlspecialcharsbx($arFields['find_ID'])?>"></td>
		</tr>
		<?
		foreach($arUserFields as $FIELD_NAME=>$arUserField)
		{
			if(/*$arUserField["SHOW_FILTER"]!="N" &&*/ $arUserField["USER_TYPE"]["BASE_TYPE"]!="file")
			{
				if(in_array($arUserField["USER_TYPE_ID"], array('date', 'datetime')))
				{
					$GLOBALS[$sf."[find_".$FIELD_NAME."]_from"] = $arFields['find_'.$FIELD_NAME.'_from'];
					$GLOBALS[$sf."[find_".$FIELD_NAME."]_to]"] = $arFields['find_'.$FIELD_NAME.'_to'];
					$GLOBALS[$sf."[find_".$FIELD_NAME."]_from_FILTER_PERIOD"] = $arFields['find_'.$FIELD_NAME.'_from_FILTER_PERIOD'];
					$GLOBALS[$sf."[find_".$FIELD_NAME."]_from_FILTER_DIRECTION"] = $arFields['find_'.$FIELD_NAME.'_from_FILTER_DIRECTION'];
					$inputHTML = $USER_FIELD_MANAGER->GetFilterHTML($arUserField, $sf.'[find_'.$FIELD_NAME.']', $arFields['find_'.$FIELD_NAME]);
				}
				else
				{
					$inputHTML = $USER_FIELD_MANAGER->GetFilterHTML($arUserField, $sf.'[find_'.$FIELD_NAME.']', $arFields['find_'.$FIELD_NAME]);
				}
				echo $inputHTML;
			}
		}
	
		$oFilter->Buttons();
		/*?><span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="set_filter" value="<? echo Loc::getMessage("admin_lib_filter_set_butt"); ?>" title="<? echo Loc::getMessage("admin_lib_filter_set_butt_title"); ?>" onClick="return EList.ApplyFilter(this);"></span>
		<span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="del_filter" value="<? echo Loc::getMessage("admin_lib_filter_clear_butt"); ?>" title="<? echo Loc::getMessage("admin_lib_filter_clear_butt_title"); ?>" onClick="return EList.DeleteFilter(this);"></span>
		<?*/
		$oFilter->End();

		?>
		<!--</form>-->
		</div>
		<?
	}
	
	public static function ShowGroupPropertyField2($name, $property_fields, $values)
	{
		if(!is_array($values)) $values = Array();

		$res = "";
		$result = "";
		$bWas = false;
		$sections = \CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$property_fields["LINK_IBLOCK_ID"]), array("ID", "NAME", "DEPTH_LEVEL"));
		while($ar = $sections->GetNext())
		{
			$res .= '<option value="'.$ar["ID"].'"';
			if(in_array($ar["ID"], $values))
			{
				$bWas = true;
				$res .= ' selected';
			}
			$res .= '>'.str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"].'</option>';
		}
		$result .= '<select name="'.$name.'[]" size="'.($property_fields["MULTIPLE"]=="Y" ? "5":"1").'" '.($property_fields["MULTIPLE"]=="Y"?"multiple":"").'>';
		$result .= '<option value=""'.(!$bWas?' selected':'').'>'.Loc::getMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</option>';
		$result .= $res;
		$result .= '</select>';
		return $result;
	}
	
	public static function AddFilter(&$arFilter, $arAddFilter)
	{
		$arAddFilter = unserialize(base64_decode($arAddFilter));
		if(!is_array($arFilter) || !is_array($arAddFilter)) return;
		
		$dbrFProps = CIBlockProperty::GetList(array(), array("IBLOCK_ID"=>$arFilter['IBLOCK_ID'],"CHECK_PERMISSIONS"=>"N"));
		$arProps = array();
		while ($arProp = $dbrFProps->GetNext())
		{
			if ($arProp["ACTIVE"] == "Y")
			{
				$arProp["PROPERTY_USER_TYPE"] = ('' != $arProp["USER_TYPE"] ? CIBlockProperty::GetUserType($arProp["USER_TYPE"]) : array());
				$arProps[] = $arProp;
			}
		}
		
		if(is_array($arAddFilter['find_section_section']))
		{
			if(count(array_diff($arAddFilter['find_section_section'], array('', '0' ,'-1'))) > 0)
			{
				$arFilter['SECTION_ID'] = array_diff($arAddFilter['find_section_section'], array('','-1'));
			}
			elseif(in_array('-1', $arAddFilter['find_section_section']))
			{
				unset($arFilter["SECTION_ID"]);
			}
		}
		elseif(strlen($arAddFilter['find_section_section']) > 0 && (int)$arAddFilter['find_section_section'] >= 0) 
			$arFilter['SECTION_ID'] = $arAddFilter['find_section_section'];
		if($arAddFilter['find_el_subsections']=='Y')
		{
			if($arFilter['SECTION_ID']==0) unset($arFilter["SECTION_ID"]);
			else $arFilter["INCLUDE_SUBSECTIONS"] = "Y";
		}
		if(strlen($arAddFilter['find_el_modified_user_id']) > 0) $arFilter['MODIFIED_USER_ID'] = $arAddFilter['find_el_modified_user_id'];
		if(strlen($arAddFilter['find_el_modified_by']) > 0) $arFilter['MODIFIED_BY'] = $arAddFilter['find_el_modified_by'];
		if(strlen($arAddFilter['find_el_created_user_id']) > 0) $arFilter['CREATED_USER_ID'] = $arAddFilter['find_el_created_user_id'];
		if(strlen($arAddFilter['find_el_active']) > 0) $arFilter['ACTIVE'] = $arAddFilter['find_el_active'];
		if(strlen($arAddFilter['find_el_code']) > 0) $arFilter['?CODE'] = $arAddFilter['find_el_code'];
		if(strlen($arAddFilter['find_el_external_id']) > 0) $arFilter['EXTERNAL_ID'] = $arAddFilter['find_el_external_id'];
		if(strlen($arAddFilter['find_el_tags']) > 0) $arFilter['?TAGS'] = $arAddFilter['find_el_tags'];
		if(strlen($arAddFilter['find_el_name']) > 0) $arFilter['?NAME'] = $arAddFilter['find_el_name'];
		if(strlen($arAddFilter['find_el_intext']) > 0) $arFilter['?DETAIL_TEXT'] = $arAddFilter['find_el_intext'];
		if($arAddFilter['find_el_preview_picture']=='Y') $arFilter['!PREVIEW_PICTURE'] =  false;
		elseif($arAddFilter['find_el_preview_picture']=='N') $arFilter['PREVIEW_PICTURE'] =  false;
		if($arAddFilter['find_el_detail_picture']=='Y') $arFilter['!DETAIL_PICTURE'] =  false;
		elseif($arAddFilter['find_el_detail_picture']=='N') $arFilter['DETAIL_PICTURE'] =  false;
		
		if(!empty($arAddFilter['find_el_id_start'])) $arFilter[">=ID"] = $arAddFilter['find_el_id_start'];
		if(!empty($arAddFilter['find_el_id_end'])) $arFilter["<=ID"] = $arAddFilter['find_el_id_end'];
		if(!empty($arAddFilter['find_el_timestamp_from'])) $arFilter["DATE_MODIFY_FROM"] = $arAddFilter['find_el_timestamp_from'];
		if(!empty($arAddFilter['find_el_timestamp_to'])) $arFilter["DATE_MODIFY_TO"] = CIBlock::isShortDate($arAddFilter['find_el_timestamp_to'])? ConvertTimeStamp(AddTime(MakeTimeStamp($arAddFilter['find_el_timestamp_to']), 1, "D"), "FULL"): $arAddFilter['find_el_timestamp_to'];
		if(!empty($arAddFilter['find_el_created_from'])) $arFilter[">=DATE_CREATE"] = $arAddFilter['find_el_created_from'];
		if(!empty($arAddFilter['find_el_created_to'])) $arFilter["<=DATE_CREATE"] = CIBlock::isShortDate($arAddFilter['find_el_created_to'])? ConvertTimeStamp(AddTime(MakeTimeStamp($arAddFilter['find_el_created_to']), 1, "D"), "FULL"): $arAddFilter['find_el_created_to'];
		if(!empty($arAddFilter['find_el_created_by']) && strlen($arAddFilter['find_el_created_by'])>0) $arFilter["CREATED_BY"] = $arAddFilter['find_el_created_by'];
		if(!empty($arAddFilter['find_el_date_active_from_from'])) $arFilter[">=DATE_ACTIVE_FROM"] = $arAddFilter['find_el_date_active_from_from'];
		if(!empty($arAddFilter['find_el_date_active_from_to'])) $arFilter["<=DATE_ACTIVE_FROM"] = $arAddFilter['find_el_date_active_from_to'];
		if(!empty($arAddFilter['find_el_date_active_to_from'])) $arFilter[">=DATE_ACTIVE_TO"] = $arAddFilter['find_el_date_active_to_from'];
		if(!empty($arAddFilter['find_el_date_active_to_to'])) $arFilter["<=DATE_ACTIVE_TO"] = $arAddFilter['find_el_date_active_to_to'];
		if (!empty($arAddFilter['find_el_catalog_type'])) $arFilter['CATALOG_TYPE'] = $arAddFilter['find_el_catalog_type'];
		if (!empty($arAddFilter['find_el_catalog_available'])) $arFilter['CATALOG_AVAILABLE'] = $arAddFilter['find_el_catalog_available'];
		if (!empty($arAddFilter['find_el_catalog_bundle'])) $arFilter['CATALOG_BUNDLE'] = $arAddFilter['find_el_catalog_bundle'];
		if (strlen($arAddFilter['find_el_catalog_quantity']) > 0)
		{
			$op = static::GetNumberOperation($arAddFilter['find_el_catalog_quantity'], $arAddFilter['find_el_catalog_quantity_comp']);
			$arFilter[$op.'CATALOG_QUANTITY'] = $arAddFilter['find_el_catalog_quantity'];
		}
		
		$arStoreKeys = preg_grep('/^find_el_catalog_store\d+_/', array_keys($arAddFilter));
		$arStoreKeys = array_unique(array_map(create_function('$n', 'return preg_replace("/^find_el_catalog_store(\d+)_.*$/", "$1", $n);'), $arStoreKeys));
		if(!empty($arStoreKeys))
		{
			foreach($arStoreKeys as $storeKey)
			{
				if(strlen($arAddFilter['find_el_catalog_store'.$storeKey.'_quantity']) > 0)
				{
					$op = static::GetNumberOperation($arAddFilter['find_el_catalog_store'.$storeKey.'_quantity'], $arAddFilter['find_el_catalog_store'.$storeKey.'_quantity_comp']);
					$arFilter[$op.'CATALOG_STORE_AMOUNT_'.$storeKey] = $arAddFilter['find_el_catalog_store'.$storeKey.'_quantity'];
				}
			}
		}
		
		$arPriceKeys = preg_grep('/^find_el_catalog_price_\d+$/', array_keys($arAddFilter));
		$arPriceKeys = array_unique(array_map(create_function('$n', 'return preg_replace("/^find_el_catalog_price_(\d+)$/", "$1", $n);'), $arPriceKeys));
		if(!empty($arPriceKeys))
		{
			foreach($arPriceKeys as $priceKey)
			{
				if(strlen($arAddFilter['find_el_catalog_price_'.$priceKey]) > 0
					|| $arAddFilter['find_el_catalog_price_'.$priceKey.'_comp']=='empty')
				{
					$op = static::GetNumberOperation($arAddFilter['find_el_catalog_price_'.$priceKey], $arAddFilter['find_el_catalog_price_'.$priceKey.'_comp']);
					$arFilter[$op.'CATALOG_PRICE_'.$priceKey] = $arAddFilter['find_el_catalog_price_'.$priceKey];
				}
			}
		}
		
		foreach ($arProps as $arProp)
		{
			if ('Y' == $arProp["FILTRABLE"] && 'F' != $arProp["PROPERTY_TYPE"])
			{
				if (!empty($arProp['PROPERTY_USER_TYPE']) && isset($arProp["PROPERTY_USER_TYPE"]["AddFilterFields"]))
				{
					$fieldName = "filter_".$listIndex."_find_el_property_".$arProp["ID"];
					$GLOBALS[$fieldName] = $arAddFilter["find_el_property_".$arProp["ID"]];
					$GLOBALS['set_filter'] = 'Y';
					call_user_func_array($arProp["PROPERTY_USER_TYPE"]["AddFilterFields"], array(
						$arProp,
						array("VALUE" => $fieldName),
						&$arFilter,
						&$filtered,
					));
				}
				else
				{
					$value = $arAddFilter["find_el_property_".$arProp["ID"]];
					$vtype = $arAddFilter["find_el_vtype_property_".$arProp["ID"]];
					if(is_array($value)) $value = array_diff(array_map('trim', $value), array(''));
					if(strlen($vtype) > 0)
					{
						if($vtype=='empty') $arFilter["PROPERTY_".$arProp["ID"]] = false;
						elseif($vtype=='not_empty') $arFilter["!PROPERTY_".$arProp["ID"]] = false;
						elseif($vtype=='contain') $arFilter["%PROPERTY_".$arProp["ID"]] = $value;
						elseif($vtype=='not_contain') $arFilter["!%PROPERTY_".$arProp["ID"]] = $value;
						elseif($vtype=='begin_with') $arFilter["PROPERTY_".$arProp["ID"]] = (is_array($value) ? array_map(create_function('$n', 'return $n."%";'), $value) : $value.'%');
						elseif($vtype=='end_on') $arFilter["PROPERTY_".$arProp["ID"]] = (is_array($value) ? array_map(create_function('$n', 'return "%".$n;'), $value) : '%'.$value);
					}
					elseif((is_array($value) && count($value)>0) || (!is_array($value) && strlen($value)))
					{
						if(is_array($value))
						{
							foreach($value as $k=>$v)
							{
								if($v === "NOT_REF") $value[$k] = false;
							}
						}
						elseif($value === "NOT_REF") $value = false;
						if($arProp["PROPERTY_TYPE"]=='E' && $arProp["USER_TYPE"]=='')
						{
							$value = trim($value);
							if(preg_match('/[,;\s\|]/', $value)) $arFilter["PROPERTY_".$arProp["ID"]] = array_diff(array_map('trim', preg_split('/[,;\s\|]/', $value)), array(''));
							else $arFilter["=PROPERTY_".$arProp["ID"]] = $value;
						}
						else
						{
							$arFilter["=PROPERTY_".$arProp["ID"]] = $value;
						}
					}
				}
			}
		}
	}
	
	public static function AddFilterHighload(&$arFilter, $arAddFilter, $HLBL_ID)
	{
		global $USER_FIELD_MANAGER;
		$arAddFilter = unserialize(base64_decode($arAddFilter));
		if(!is_array($arAddFilter)) return;
		
		$ufEntityId = 'HLBLOCK_'.$HLBL_ID;
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ufEntityId, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME=>$arUserField)
		{
			$key = 'find_'.$FIELD_NAME;
			if(array_key_exists($key, $arAddFilter))
			{
				$val = $arAddFilter[$key];
				$isVal = false;
				if(is_array($val))
				{
					$val = array_diff(array_map('trim', $val), array(''));
					if(!empty($val)) $isVal = true;
				}
				elseif(strlen(trim($val)) > 0) $isVal = true;

				if(in_array($arUserField["USER_TYPE_ID"], array('date', 'datetime')))
				{
					self::AddDateFilter($arFilter, $arAddFilter, '>='.$FIELD_NAME, '<='.$FIELD_NAME, "find_".$FIELD_NAME);
				}
				elseif($isVal)
				{
					if($arUserField["SHOW_FILTER"]=="I")
						$arFilter["=".$FIELD_NAME]=$val;
					elseif($arUserField["SHOW_FILTER"]=="S")
						$arFilter["%".$FIELD_NAME]=$val;
					else
						$arFilter[$FIELD_NAME]=$val;
				}
			}
		}	
	}
	
	public static function AddDateFilter(&$arFilter, $arAddFilter, $field1, $field2, $addField)
	{
		if($arAddFilter[$addField.'_from_FILTER_PERIOD']=='last_days'
			&& isset($arAddFilter[$addField.'_from_FILTER_LAST_DAYS']) && strlen(trim($arAddFilter[$addField.'_from_FILTER_LAST_DAYS'])) > 0)
		{
			$days = (int)trim($arAddFilter[$addField.'_from_FILTER_LAST_DAYS']);
			$arFilter[$field1] = $arAddFilter[$addField.'_from'] = ConvertTimeStamp(time()-$days*24*60*60, "FULL");
		}
		else
		{
			if(!empty($arAddFilter[$addField.'_from'])) $arFilter[$field1] = $arAddFilter[$addField.'_from'];
			if(!empty($arAddFilter[$addField.'_to'])) $arFilter[$field2] = \CIBlock::isShortDate($arAddFilter[$addField.'_to'])? ConvertTimeStamp(AddTime(MakeTimeStamp($arAddFilter[$addField.'_to']), 1, "D"), "FULL"): $arAddFilter[$addField.'_to'];
		}
	}
	
	public static function GetNumberOperation(&$val, $op)
	{
		if($op=='eq') return '=';
		elseif($op=='gt') return '>';
		elseif($op=='geq') return '>=';
		elseif($op=='lt') return '<';
		elseif($op=='leq') return '<=';
		elseif($op=='empty')
		{
			$val = false;
			return '';
		}
		else return '';
	}
	
	public static function ExportCsv($arResult)
	{
		require_once(dirname(__FILE__).'/../../lib/PHPExcel/PHPExcel.php');
		$objPHPExcel = new \KDAPHPExcel();
		$arCols = range('A', 'Z');
		
		$row = 1;
		$worksheet = $objPHPExcel->getActiveSheet();
		foreach($arResult as $k=>$arFields)
		{
			$col = 0;
			foreach($arFields as $k=>$field)
			{
				$worksheet->setCellValueExplicit($arCols[$col++].$row, self::GetCsvCellValue($field, 'UTF-8'));
			}
			$row++;
		}
		$objWriter = KDAPHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
		$objWriter->setDelimiter(';');
		$objWriter->setEnclosure('"');
		$objWriter->setUseBOM(true);
		
		$tempPath = CFile::GetTempName('', 'export.csv');
		$dir = \Bitrix\Main\IO\Path::getDirectory($tempPath);
		\Bitrix\Main\IO\Directory::createDirectory($dir);
		$objWriter->save($tempPath);
		
		$GLOBALS['APPLICATION']->RestartBuffer();
		ob_end_clean();
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=export.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		readfile($tempPath);
		die();
	}
	
	public static function ImportCsv($file)
	{
		$maxLine = 10000;
		$arLines = array();
		if(class_exists('\CKDAImportExcel')){}
		$selfobj = new \CKDAImportExcelStatic(array(), $file);
		$objReader = \KDAPHPExcel_IOFactory::createReaderForFile($file);
		$efile = $objReader->load($file);
		foreach($efile->getWorksheetIterator() as $worksheet) 
		{
			$columns_count = max(KDAPHPExcel_Cell::columnIndexFromString($worksheet->getHighestDataColumn()), $maxDrawCol);
			$columns_count = min($columns_count, 5000);
			$rows_count = $worksheet->getHighestDataRow();

			for ($row = 0; ($row < $rows_count && count($arLines) < $maxLine); $row++) 
			{
				$arLine = array();
				for($column = 0; $column < $columns_count; $column++) 
				{
					$val = $worksheet->getCellByColumnAndRow($column, $row+1);					
					$valText = $selfobj->GetCalculatedValue($val);
					$arLine[] = $valText;
				}

				if(count(array_diff($arLine, array(''))) > 0)
				{
					$arLines[] = $arLine;
				}
			}
		}
		return $arLines;
	}
	
	public static function GetCsvCellValue($val, $encoding='CP1251')
	{
		if($encoding=='CP1251')
		{
			if(defined('BX_UTF') && BX_UTF)
			{
				$val = $GLOBALS['APPLICATION']->ConvertCharset($val, 'UTF-8', 'CP1251');
			}
		}
		return $val;
	}
	
	public static function RemoveTmpFiles($maxTime = 5)
	{
		$oProfile = CKDAImportProfile::getInstance();
		$timeBegin = time();
		$docRoot = $_SERVER["DOCUMENT_ROOT"];
		$tmpDir = $docRoot.'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		$arOldDirs = array();
		$arActDirs = array('_archives');
		if(file_exists($tmpDir) && ($dh = opendir($tmpDir))) 
		{
			while(($file = readdir($dh)) !== false) 
			{
				if(in_array($file, array('.', '..'))) continue;
				if(is_dir($tmpDir.$file))
				{
					if(!in_array($file, $arActDirs) && (time() - filemtime($tmpDir.$file) > 24*60*60))
					{
						$arOldDirs[] = $file;
					}
				}
				elseif(substr($file, -4)=='.txt')
				{
					$arParams = $oProfile->GetProfileParamsByFile($tmpDir.$file);
					if(is_array($arParams) && isset($arParams['tmpdir']))
					{
						$actDir = preg_replace('/^.*\/([^\/]+)$/', '$1', trim($arParams['tmpdir'], '/'));
						$arActDirs[] = $actDir;
					}
				}
			}
			$arOldDirs = array_diff($arOldDirs, $arActDirs);
			foreach($arOldDirs as $subdir)
			{
				$oldDir = substr($tmpDir, strlen($docRoot)).$subdir;
				DeleteDirFilesEx($oldDir);
				if(($maxTime > 0) && (time() - $timeBegin >= $maxTime)) return;
			}
			closedir($dh);
		}
		
		$tmpDir = $docRoot.'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir.'_archives/';
		if(file_exists($tmpDir) && ($dh = opendir($tmpDir))) 
		{
			while(($file = readdir($dh)) !== false) 
			{
				if(in_array($file, array('.', '..'))) continue;
				if(is_dir($tmpDir.$file))
				{
					if((time() - filemtime($tmpDir.$file) > 2*24*60*60))
					{
						$arOldDirs[] = $file;
					}
				}
			}
			foreach($arOldDirs as $subdir)
			{
				$oldDir = substr($tmpDir, strlen($docRoot)).$subdir;
				DeleteDirFilesEx($oldDir);
				if(($maxTime > 0) && (time() - $timeBegin >= $maxTime)) return;
			}
			closedir($dh);
		}
		
		$tmpDir = $docRoot.'/upload/tmp/';
		if(file_exists($tmpDir) && ($dh = opendir($tmpDir))) 
		{
			while(($file = readdir($dh)) !== false) 
			{
				if(!preg_match('/^[0-9a-f]{3}$/', $file)) continue;
				$subdir = $tmpDir.$file;
				if(is_dir($subdir))
				{
					$subdir .= '/';
					if(time() - filemtime($subdir) > 24*60*60)
					{
						if($dh2 = opendir($subdir))
						{
							$emptyDir = true;
							while(($file2 = readdir($dh2)) !== false)
							{
								if(in_array($file2, array('.', '..'))) continue;
								if(time() - filemtime($subdir) > 24*60*60)
								{
									if(is_dir($subdir.$file2))
									{
										$oldDir = substr($subdir.$file2, strlen($docRoot));
										DeleteDirFilesEx($oldDir);
									}
									else
									{
										unlink($subdir.$file2);
									}
								}
								else
								{
									$emptyDir = false;
								}
							}
							closedir($dh2);
							if($emptyDir)
							{
								//unlink($subdir);
								rmdir($subdir);
							}
						}
						
						if(($maxTime > 0) && (time() - $timeBegin >= $maxTime)) return;
					}
				}
			}
			closedir($dh);
		}
	}
	
	public static function GetIniAbsVal($param)
	{
		$val = ToUpper(ini_get($param));
		if(substr($val, -1)=='K') $val = (float)$val*1024;
		elseif(substr($val, -1)=='M') $val = (float)$val*1024*1024;
		elseif(substr($val, -1)=='G') $val = (float)$val*1024*1024*1024;
		else $val = (float)$val;
		return $val;
	}
	
	public static function getSiteEncoding()
	{
		if (defined('BX_UTF'))
			$logicalEncoding = "utf-8";
		elseif (defined("SITE_CHARSET") && (strlen(SITE_CHARSET) > 0))
			$logicalEncoding = SITE_CHARSET;
		elseif (defined("LANG_CHARSET") && (strlen(LANG_CHARSET) > 0))
			$logicalEncoding = LANG_CHARSET;
		elseif (defined("BX_DEFAULT_CHARSET"))
			$logicalEncoding = BX_DEFAULT_CHARSET;
		else
			$logicalEncoding = "windows-1251";

		return strtolower($logicalEncoding);
	}
	
	public function GetUserAgent()
	{
		if(empty(self::$arAgents))
		{
			self::$arAgents = array(
				'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:77.0) Gecko/20100101 Firefox/77.0',
				'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:62.0) Gecko/20100101 Firefox/62.0',
				'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0',
				'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0',
				'Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0'
			);
			self::$countAgents = count(self::$arAgents);
		}
		return self::$arAgents[rand(0, self::$countAgents - 1)];
	}
}
?>