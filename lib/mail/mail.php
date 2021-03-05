<?php
namespace Bitrix\KdaImportexcel;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SMail
{
	protected $paramsChecked = false;
	protected $paramsCheckRes = false;

	public function __construct($params=array())
	{
		$this->params = $params;
		
		//Cyrillic domain
		$arEmailParts = explode('@', $this->params['EMAIL']);
		if(preg_match('/[^A-Za-z0-9\-\.]/', $arEmailParts[1]))
		{
			if(!class_exists('\idna_convert')) require_once(dirname(__FILE__).'/../idna_convert.class.php');
			if(class_exists('\idna_convert'))
			{
				$idn = new \idna_convert();
				$oldHost = $arEmailParts[1];
				if(!\CUtil::DetectUTF8($oldHost)) $oldHost = \CKDAImportUtils::Win1251Utf8($oldHost);
				$this->params['EMAIL'] = str_replace($arEmailParts[1], $idn->encode($oldHost), $this->params['EMAIL']);
			}
		}
	}
	
	public function CheckParams()
	{
		if(!$this->paramsChecked)
		{
			$tls = false;
			$port = 143;
			if($this->params['SECURITY']=='ssl')
			{
				$port = 993;
				$tls = true;
			}
			elseif($this->params['SECURITY']=='tls')
			{
				$port = 143;
				$tls = true;
			}
			$charset = (defined('BX_UTF') && BX_UTF ? 'UTF-8' : 'CP1251');
			
			$this->imap = new \Bitrix\KdaImportexcel\Imap($this->params['SERVER'], $port, $tls, false, $this->params['EMAIL'], $this->params['PASSWORD'], $charset);
			$this->paramsCheckRes = $this->imap->singin(($error = ''));
			$this->paramsChecked = true;
		}
		return $this->paramsCheckRes;
	}
	
	public function GetListingFolders()
	{
		$arFolders = array();
		if($this->CheckParams())
		{
			if($mailboxes = $this->imap->listMailboxes('*', ($error='')))
			{
				foreach($mailboxes as $mailbox)
				{
					if(strpos($mailbox['key'], 'INBOX')!==false)
					{
						$arFolders[$mailbox['key']] = $mailbox['name'];
						if(is_array($mailbox['children']))
						{
							foreach($mailbox['children'] as $mailbox2)
							{
								$arFolders[$mailbox2['key']] = '.'.end(explode('/', $mailbox2['name']));
							}
						}
					}
				}
				foreach($mailboxes as $mailbox)
				{
					if(strpos($mailbox['key'], 'INBOX')===false)
					{
						$arFolders[$mailbox['key']] = $mailbox['name'];
						if(is_array($mailbox['children']))
						{
							foreach($mailbox['children'] as $mailbox2)
							{
								$arFolders[$mailbox2['key']] = '.'.end(explode('/', $mailbox2['name']));
							}
						}
					}
				}
			}
		}
		foreach($arFolders as $k=>$v)
		{
			$arFolders[$k] = str_replace('INBOX', Loc::getMessage('KDA_IE_INBOX_FOLDER'), $v);
		}
		if(!isset($arFolders['INBOX']))
		{
			$arFolders['INBOX'] = Loc::getMessage('KDA_IE_INBOX_FOLDER');
		}
		return $arFolders;
	}
	
	public function GetFileId(&$arParams, $maxTime = 0, $extId = '')
	{
		if($this->CheckParams($tmpdir))
		{
			//$mailbox = $this->mailbox;
			//$arFolders = $mailbox->getListingFolders();
			if($this->params['FOLDER'])
			{
				$arFolders = array($this->params['FOLDER']);
			}
			else
			{
				$arFolders = array('INBOX');
			}
			
			$time = time() - 30*24*60*60;
			if($this->params['LAST_DATE'])
			{
				$time1 = strtotime($this->params['LAST_DATE']);
				if($time1 > $time) $time = $time1;
			}
			$time = mktime(0, 0, 0, date('n', $time), date('j', $time), date('Y', $time));
			//$arCriterias = array('SINCE' => date('r', $time));
			$arCriterias = array('SINCE' => date('j-M-Y', $time));
			if($this->params['UNSEEN_ONLY']!='N') $arCriterias['UNSEEN'] = 'Y';
			if($this->params['FROM']) $arCriterias['FROM'] = $this->params['FROM'];
			if($this->params['SUBJECT']) $arCriterias['SUBJECT'] = $this->params['SUBJECT'];
			if($this->params['FILENAME']) $arCriterias['FILENAME'] = $this->params['FILENAME'];
			if($this->params['FILENAME_REGEXP']) $arCriterias['FILENAME_REGEXP'] = $this->params['FILENAME_REGEXP'];
			$allowExt = array('txt', 'csv', 'xls', 'xlsx', 'xlsm', 'zip', 'gz', 'tgz', 'rar');
			
			$fid = 0;
			while(!empty($arFolders) && !$fid)
			{
				$folder = array_shift($arFolders);
				$mailsIds = $this->imap->getSearch($folder, $arCriterias, ($error=''));
				if(!empty($mailsIds))
				{
					$break = false;
					$i = count($mailsIds) - 1;
					while($i>=0 && !$break)
					{
						$mailId = $mailsIds[$i];
						if(($arMailFile = $this->imap->getMessageFile($folder, $mailId, $this->params, $allowExt, ($error='')))!==false)
						{
							if(!$this->params['LAST_DATE'] || $arMailFile['DATE']!=$this->params['LAST_DATE'])
							{
								$fn = str_replace(array('/'), '', $arMailFile['FILENAME']);
								$fn = \Bitrix\Main\IO\Path::convertLogicalToPhysical($fn);
								if(strpos($fn, '.')===false) $fn .= '.csv';
								
								$dir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.IUtils::$moduleId.'/'.IUtils::$moduleSubDir;
								CheckDirPath($dir);
								$i = 0;
								while(($tmpdir = $dir.'attachments_'.$i.'/') && file_exists($tmpdir)){$i++;}
								CheckDirPath($tmpdir);
								
								file_put_contents($tmpdir.$fn, $arMailFile['BODY']);
								$arFile = \CKDAImportUtils::MakeFileArray($tmpdir.$fn, $maxTime);
								$arFile['external_id'] = $extId;
								$arFile['del_old'] = 'Y';
								$fid = \CKDAImportUtils::SaveFile($arFile);
								DeleteDirFilesEx(substr($tmpdir, strlen($_SERVER["DOCUMENT_ROOT"])));
								$arParams['LAST_DATE'] = $arMailFile['DATE'];
							}
							
							$this->DeleteOldMail($folder, $mailId);
							$break = true;
						}
						elseif($this->params['FILENAME_REGEXP']=='Y' && (strpos($this->params['FILENAME'], '//')!==false || stripos($this->params['FILENAME'], 'href=')!==false) && ($arMailData = $this->imap->getMessage($folder, $mailId, 'DATA', ($error='')))!==false)
						{
							if(preg_match('/'.str_replace('/', '\/', $arCriterias['FILENAME']).'/is', $arMailData['TEXT'], $m))
							{
								if(!$this->params['LAST_DATE'] || $arMailData['DATE']!=$this->params['LAST_DATE'])
								{
									$path = $m[0];
									if(preg_match('/href=[\'"]([^\'"]+)[\'"]/i', $path, $m2)) $path = $m2[1];
									$arFile = \CKDAImportUtils::MakeFileArray($path, $maxTime);
									if(strpos($arFile['name'], '.')===false) $arFile['name'] .= '.csv';
									if($arFile['size'] > 0 && in_array(\CKDAImportUtils::GetFileExtension($arFile['name']), $allowExt))
									{
										$arFile['external_id'] = $extId;
										$arFile['del_old'] = 'Y';
										$fid = \CKDAImportUtils::SaveFile($arFile);
									}
									$arParams['LAST_DATE'] = $arMailData['DATE'];
								}
								
								$this->DeleteOldMail($folder, $mailId);
								$break = true;
							}
						}
						$i--;
					}
				}
			}
		}
		
		if($fid > 0) return $fid;
		else return false;
	}
	
	public function DeleteOldMail($folder, $mailId)
	{
		if($this->params['DELETE_OLD_MAIL']!='Y') return;
		
		$this->imap->updateMessageFlags($folder, $mailId, array('\Deleted'=>1), ($error=''));
		
		$arAllFolders = $this->getListingFolders();
		$asTrashKeys = preg_grep('/^Trash$/i', array_keys($arAllFolders));
		if(count($asTrashKeys) > 0) $trashKey = current($asTrashKeys);
		else $trashKey = 'Trash';
		if(array_key_exists($trashKey, $arAllFolders))
		{
			$this->imap->moveMessageToFolder($folder, $arAllFolders[$trashKey], $mailId, ($error=''));
		}
	}
	
	public static function GetNewFile(&$json, $maxTime = 0, $extId='')
	{
		if(strlen($json) > 0 && strpos($json, '{')===false) $json = base64_decode($json);
		$arParams = \CUtil::JsObjectToPhp($json);
		if(!is_array($arParams)) $arParams = array();
		$arParams = array_map('stripcslashes', $arParams); //replace \'
		$mail = new \Bitrix\KdaImportexcel\SMail($arParams);
		$fileId = $mail->GetFileId($arParams, $maxTime, $extId);
		$json = \CUtil::PhpToJSObject($arParams);
		return $fileId;
	}
}