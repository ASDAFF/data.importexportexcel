<?php
require_once(dirname(__FILE__).'/../../lib/PhpImap/__autoload.php');
IncludeModuleLangFile(__FILE__);

class CKDAImportMail {
	protected static $moduleId = 'esol.importexportexcel';
	protected static $moduleSubDir = 'import/';
	
	public function __construct($params=array())
	{
		$this->params = $params;
	}
	
	public function GetImapPath()
	{
		$post = 143;
		$security = '';
		if($this->params['SECURITY']=='ssl')
		{
			$post = 993;
			$security = '/ssl/novalidate-cert';
		}
		elseif($this->params['SECURITY']=='tls')
		{
			$post = 143;
			$security = '/tls/novalidate-cert';
		}
		$imapPath = '{'.$this->params['SERVER'].':993/imap'.$security.'}';
		return $imapPath;
	}
	
	public function CheckParams($tmpdir)
	{
		$mailbox = new PhpImap\Mailbox($this->GetImapPath(), $this->params['EMAIL'], $this->params['PASSWORD'], ($tmpdir ? $tmpdir : null));
		$mailbox->setConnectionArgs(OP_READONLY, 0, array());
		$this->mailbox = $mailbox;
		return ($mailbox->getImapStream() ? true : false);
	}
	
	public function GetListingFolders()
	{
		$arFolders = array();
		if($this->CheckParams())
		{
			$mailbox = $this->mailbox;
			$mailbox->switchMailbox($this->GetImapPath());
			$arFolders = $mailbox->getListingFolders();
		}
		foreach($arFolders as $k=>$v)
		{
			$arFolders[$k] = str_replace('INBOX', GetMessage('KDA_IE_INBOX_FOLDER'), $v);
		}
		if(!isset($arFolders['INBOX']))
		{
			$arFolders['INBOX'] = GetMessage('KDA_IE_INBOX_FOLDER');
		}
		return $arFolders;
	}
	
	public function GetFileId(&$arParams, $maxTime=0, $extId='')
	{
		$dir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		CheckDirPath($dir);
		$i = 0;
		while(($tmpdir = $dir.'attachments_'.$i.'/') && file_exists($tmpdir)){$i++;}
		CheckDirPath($tmpdir);
	
		$fid = 0;
		if($this->CheckParams($tmpdir))
		{
			$mailbox = $this->mailbox;
			
			//$arFolders = $mailbox->getListingFolders();
			if($this->params['FOLDER'])
			{
				$arFolders = array($this->GetImapPath().$this->params['FOLDER']);
			}
			else
			{
				$mailbox->switchMailbox($this->GetImapPath());
				$arFolders = imap_list($mailbox->getImapStream(), $this->GetImapPath(), "*");
				if(!is_array($arFolders)) $arFolders = array();
				$inboxFolder = preg_grep('/\}INBOX/i', $arFolders);
				if(count($inboxFolder) > 0)
				{
					$inboxFolder = current($inboxFolder);
					$arFolders = array_diff($arFolders, array($inboxFolder));
					array_unshift($arFolders, $inboxFolder);
				}
			}
			
			$time = time() - 30*24*60*60;
			if($this->params['LAST_DATE'])
			{
				$time1 = strtotime($this->params['LAST_DATE']);
				if($time1 > $time) $time = $time1;
			}
			$time = mktime(0, 0, 0, date('n', $time), date('j', $time), date('Y', $time));
			$criteria = 'UNSEEN';
			$criteria .= ' SINCE "'.date('r', $time).'"';
			
			while(!empty($arFolders) && !$fid)
			{
				$folder = array_shift($arFolders);
				$mailbox->switchMailbox($folder);
				$mailsIds = $mailbox->searchMailbox($criteria);

				if(!empty($mailsIds))
				{
					$break = false;
					$i = count($mailsIds) - 1;
					while($i>=0 && !$break)
					{
						$mailId = $mailsIds[$i];
						$mail = $mailbox->getMail($mailId);
						if((!$this->params['FROM'] || $this->params['FROM']==$mail->fromAddress)
							&& (!$this->params['SUBJECT'] || strpos(ToLower($mail->subject), ToLower($this->params['SUBJECT']))!==false)
							&& (!$this->params['LAST_DATE'] || ($mail->date > $this->params['LAST_DATE'])))
						{
							$attachments = $mail->getAttachments();
							if(is_array($attachments))
							{
								foreach($attachments as $attach)
								{
									if((!$this->params['FILENAME'] || strpos(ToLower($attach->name), ToLower($this->params['FILENAME']))!==false)
										&& in_array(ToLower(GetFileExtension($attach->name)), array('txt', 'csv', 'xls', 'xlsx', 'xlsm')))
									{
										$break = true;
										break;
									}
								}
							}
						}
						if(!$break)
						{
							unset($mail);
							$this->ClearTmpDir($tmpdir);
						}
						$i--;
					}
					

					if($mail && $attach->filePath)
					{
						$arFile = CKDAImportUtils::MakeFileArray($attach->filePath, $maxTime);
						$arFile['name'] = $attach->name;
						if(strpos($arFile['name'], '.')===false) $arFile['name'] .= '.csv';
						$arFile['external_id'] = $extId;
						$arFile['del_old'] = 'Y';
						$fid = CKDAImportUtils::SaveFile($arFile);
						$arParams['LAST_DATE'] = $mail->date;
					}
				}
			}
		}
		$this->ClearTmpDir($tmpdir);
		rmdir($tmpdir);

		if($fid > 0) return $fid;
		else return false;
	}
	
	public function ClearTmpDir($tmpdir)
	{
		$arFiles = scandir($tmpdir);
		foreach($arFiles as $fn)
		{
			if($fn!='.' && $fn!='..') unlink($tmpdir.$fn);
		}
	}
	
	public static function GetNewFile(&$json, $maxTime=0, $extId='')
	{
		$arParams = CUtil::JsObjectToPhp($json);
		if(!is_array($arParams)) $arParams = array();
		$mail = new CKDAImportMail($arParams);
		$fileId = $mail->GetFileId($arParams, $maxTime, $extId);
		$json = CUtil::PhpToJSObject($arParams);
		return $fileId;
	}
}
?>