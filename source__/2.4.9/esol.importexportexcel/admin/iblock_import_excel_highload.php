<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$moduleId = 'esol.importexportexcel';
$moduleFilePrefix = 'esol_import_excel';
$moduleJsId = 'esol_importexcel';
$moduleJsId2 = str_replace('.', '_', $moduleId);
$moduleDemoExpiredFunc = $moduleJsId2.'_demo_expired';
$moduleShowDemoFunc = $moduleJsId2.'_show_demo';
$moduleRunnerClass = 'CEsolImpExpExcelRunner';
CModule::IncludeModule("iblock");
CModule::IncludeModule('highloadblock');
CModule::IncludeModule($moduleId);
CJSCore::Init(array($moduleJsId.'_highload'));
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);

include_once(dirname(__FILE__).'/../install/demo.php');
if ($moduleDemoExpiredFunc()) {
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	$moduleShowDemoFunc();
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$oProfile = CKDAImportProfile::getInstance('highload');
if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!=='new')
{
	$oProfile->Apply($SETTINGS_DEFAULT, $SETTINGS, $PROFILE_ID);
	$oProfile->ApplyExtra($EXTRASETTINGS, $PROFILE_ID);
	
	/*New file storage*/
	if($SETTINGS_DEFAULT['URL_DATA_FILE'] && !$SETTINGS_DEFAULT["DATA_FILE"])
	{
		$filepath = $_SERVER["DOCUMENT_ROOT"].$SETTINGS_DEFAULT['URL_DATA_FILE'];
		if(!file_exists($filepath))
		{
			if(defined("BX_UTF")) $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'CP1251');
			else $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'UTF-8');
		}
		$arFile = CFile::MakeFileArray($filepath);
		$arFile['external_id'] = 'kda_import_hl'.$PROFILE_ID;
		$arFile['del_old'] = 'Y';
		$fid = CKDAImportUtils::SaveFile($arFile);
		$SETTINGS_DEFAULT["DATA_FILE"] = $fid;
		$oProfile->Update($PROFILE_ID, $SETTINGS_DEFAULT, $SETTINGS);
	}
	/*/New file storage*/
}

$SHOW_FIRST_LINES = 10;
$SETTINGS_DEFAULT['HIGHLOADBLOCK_ID'] = intval($SETTINGS_DEFAULT['HIGHLOADBLOCK_ID']);
if(!isset($HIGHLOADBLOCK_ID)) $HIGHLOADBLOCK_ID = $SETTINGS_DEFAULT['HIGHLOADBLOCK_ID'];
$STEP = intval($STEP);
if ($STEP <= 0)
	$STEP = 1;

$notRewriteFile = false;
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	if(isset($_POST["backButton"]) && strlen($_POST["backButton"]) > 0) $STEP = $STEP - 2;
	if(isset($_POST["backButton2"]) && strlen($_POST["backButton2"]) > 0) $STEP = 1;
	if(isset($_POST["saveConfigButton"]) && strlen($_POST["saveConfigButton"]) > 0 && $STEP > 2)
	{
		$STEP = $STEP - 1;
		$notRewriteFile = true;
	}
}

$strError = $oProfile->GetErrors();
$io = CBXVirtualIo::GetInstance();

function ShowTblLine($data, $list, $line, $checked = true)
{
	?><tr><td class="line-settings">
			<input type="hidden" name="SETTINGS[IMPORT_LINE][<?echo $list;?>][<?echo $line;?>]" value="0">
			<input type="checkbox" name="SETTINGS[IMPORT_LINE][<?echo $list;?>][<?echo $line;?>]" value="1" <?if($checked){echo 'checked';}?>>
		</td><?
		foreach($data as $row)
		{
			$style = $parentStyle = $dataStyle = '';
			$parentStyle = '';
			if($row['STYLE'])
			{
				if($row['STYLE']['BACKGROUND'])
				{
					$style .= 'background-color:#'.$row['STYLE']['BACKGROUND'].';';
					$parentStyle .= 'background-color:#'.$row['STYLE']['BACKGROUND'].';';
				}
				if($row['STYLE']['COLOR']) $style .= 'color:#'.$row['STYLE']['COLOR'].';';
				if($row['STYLE']['FONT-WEIGHT']) $style .= 'font-weight:bold;';
				if($row['STYLE']['FONT-STYLE']) $style .= 'font-style:italic;';
				if($row['STYLE']['TEXT-DECORATION']=='single') $style .= 'text-decoration:underline;';
				$dataStyle = 'data-style="'.htmlspecialcharsex(CUtil::PhpToJSObject($row['STYLE'])).'"';
			}
			$style = ($style ? 'style="'.$style.'"' : '');
			$parentStyle = ($parentStyle ? 'style="'.$parentStyle.'"' : '');
		?><td <?echo $parentStyle;?>><div class="cell" <?echo $parentStyle;?>><div class="cell_inner" <?echo $style;?> <?echo $dataStyle;?>><?echo nl2br(htmlspecialcharsex($row['VALUE']));?></div></div></td><?
		}
	?></tr><?
}
/////////////////////////////////////////////////////////////////////
if ($REQUEST_METHOD == "POST" && $MODE=='AJAX')
{
	define('PUBLIC_AJAX_MODE', 'Y');
	
	if($ACTION=='REMOVE_PROCESS_PROFILE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$oProfile = CKDAImportProfile::getInstance('highload');
		$oProfile->RemoveProcessedProfile($PROCCESS_PROFILE_ID);
		die();
	}
	
	if($ACTION=='GET_SECTION_LIST')
	{
		$fl = new CKDAFieldList($SETTINGS_DEFAULT);
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		?><div><?
		//$fl->ShowSelectSections($IBLOCK_ID, 'sections');
		$fl->ShowSelectFieldsHighload($HLBL_ID, 'fields');
		?></div><?
		die();
	}
	
	if($ACTION=='GET_UID')
	{
		$fl = new CKDAFieldList($SETTINGS_DEFAULT);
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		?><div><?
		$fl->ShowSelectUidFieldsHighload($HIGHLOADBLOCK_ID, 'fields[]');
		?></div><?
		die();
	}
	
	if($ACTION=='DELETE_PROFILE')
	{
		$fl = CKDAImportProfile::getInstance('highload');
		$fl->Delete($_REQUEST['ID']);
		die();
	}
	
	if($ACTION=='COPY_PROFILE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$fl = CKDAImportProfile::getInstance('highload');
		$id = $fl->Copy($_REQUEST['ID']);
		echo CUtil::PhpToJSObject(array('id'=>$id));
		die();
	}
	
	if($ACTION=='RENAME_PROFILE')
	{
		$fl = CKDAImportProfile::getInstance('highload');
		$fl->Rename($_REQUEST['ID'], $_REQUEST['NAME']);
		die();
	}
	
	if($ACTION=='APPLY_TO_LISTS')
	{
		$fl = CKDAImportProfile::getInstance('highload');
		$fl->ApplyToLists($_REQUEST['PROFILE_ID'], $_REQUEST['LIST_FROM'], $_REQUEST['LIST_TO']);
		die();
	}
}

if ($REQUEST_METHOD == "POST" && $STEP > 1 && check_bitrix_sessid())
{
	if($ACTION) define('PUBLIC_AJAX_MODE', 'Y');
	
	//*****************************************************************//	
	if ($STEP > 1)
	{
		//*****************************************************************//	
		$sess = $_SESSION;
		session_write_close();
		$_SESSION = $sess;

		if (strlen($strError) <= 0)
		{
			if($STEP==2 && !$notRewriteFile)
			{
				if((!isset($_FILES["DATA_FILE"]) || !$_FILES["DATA_FILE"]["tmp_name"]) && (!isset($_POST['DATA_FILE']) || is_numeric($_POST['DATA_FILE'])))
				{
					if($_POST["EXT_DATA_FILE"]) $_POST['DATA_FILE'] = $_POST["EXT_DATA_FILE"];
					elseif($SETTINGS_DEFAULT["EXT_DATA_FILE"]) $_POST['DATA_FILE'] = $SETTINGS_DEFAULT["EXT_DATA_FILE"];
					elseif($SETTINGS_DEFAULT['EMAIL_DATA_FILE'])
					{
						$fileId = \Bitrix\KdaImportexcel\SMail::GetNewFile($SETTINGS_DEFAULT['EMAIL_DATA_FILE'], 0, 'kda_import_hl'.$PROFILE_ID);
						if($fileId > 0)
						{
							if($_POST['OLD_DATA_FILE'])
							{
								CKDAImportUtils::DeleteFile($_POST['OLD_DATA_FILE']);
							}
							$SETTINGS_DEFAULT["DATA_FILE"] = $_POST['DATA_FILE'] = $fileId;
						}
					}
				}
				elseif($SETTINGS_DEFAULT['EMAIL_DATA_FILE'])
				{
					unset($SETTINGS_DEFAULT['EMAIL_DATA_FILE']);
				}
			}

			$DATA_FILE_NAME = "";
			if((isset($_FILES["DATA_FILE"]) && $_FILES["DATA_FILE"]["tmp_name"]) || (isset($_POST['DATA_FILE']) && $_POST['DATA_FILE'] && !is_numeric($_POST['DATA_FILE'])))
			{
				$extFile = false;
				$fid = 0;
				if(isset($_FILES["DATA_FILE"]) && is_uploaded_file($_FILES["DATA_FILE"]["tmp_name"]))
				{
					//$fid = CKDAImportUtils::SaveFile($_FILES["DATA_FILE"]);
					$arFile = CKDAImportUtils::MakeFileArray($_FILES["DATA_FILE"]);
					$arFile['external_id'] = 'kda_import_hl'.$PROFILE_ID;
					$arFile['del_old'] = 'Y';
					$fid = CKDAImportUtils::SaveFile($arFile);
				}
				elseif(isset($_POST['DATA_FILE']) && strlen($_POST['DATA_FILE']) > 0)
				{
					$extFile = true;
					if(strpos($_POST['DATA_FILE'], '/')===0) 
					{
						$filepath = $_POST['DATA_FILE'];
						if(!file_exists($filepath))
						{
							$filepath = $_SERVER["DOCUMENT_ROOT"].$filepath;
						}
						if(!file_exists($filepath))
						{
							if(defined("BX_UTF")) $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'CP1251');
							else $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'UTF-8');
						}
					}
					else
					{
						//$extFile = true;
						$filepath = $_POST['DATA_FILE'];
						if($filepath && $_POST['OLD_DATA_FILE'])
						{
							$arOldFile = CFIle::GetFileArray($_POST['OLD_DATA_FILE']);
							$oldFileSize = (int)filesize($_SERVER['DOCUMENT_ROOT'].$arOldFile['SRC']);
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true));
							$newFileSize = 0;
							if(is_callable(array($client, 'head')) && ($headers = $client->head($filepath)) && $client->getStatus()!=404) $newFileSize = (int)$headers->get('content-length');
							if($oldFileSize > 0 && $newFileSize > 0 && $oldFileSize==$newFileSize)
							{
								$fid = $_POST['OLD_DATA_FILE'];
							}
						}
					}
					if(!$fid)
					{
						$arFile = CKDAImportUtils::MakeFileArray($filepath);
						if($arFile['name'])
						{
							if(strpos($arFile['name'], '.')===false) $arFile['name'] .= '.csv';
							$arFile['external_id'] = 'kda_import_hl'.$PROFILE_ID;
							$arFile['del_old'] = 'Y';
							$fid = CKDAImportUtils::SaveFile($arFile);
						}
					}
				}
				
				if(!$fid)
				{
					$strError.= GetMessage("KDA_IE_FILE_UPLOAD_ERROR")."<br>";
					if($extFile)
					{
						$SETTINGS_DEFAULT["EXT_DATA_FILE"] = $_POST['DATA_FILE'];
					}
				}
				else
				{
					$SETTINGS_DEFAULT["DATA_FILE"] = $fid;
					if($_POST['OLD_DATA_FILE'] && $_POST['OLD_DATA_FILE']!=$fid)
					{
						CKDAImportUtils::DeleteFile($_POST['OLD_DATA_FILE']);
					}
					$SETTINGS_DEFAULT["EXT_DATA_FILE"] = ($extFile ? $_POST['DATA_FILE'] : false);
				}
			}
			elseif(isset($_FILES["DATA_FILE"]) && is_array($_FILES["DATA_FILE"]) && $_FILES["DATA_FILE"]["error"]==1)
			{
				$strError.= GetMessage("KDA_IE_FILE_UPLOAD_ERROR")."<br>";
				$uploadMaxFilesize = CKDAImportUtils::GetIniAbsVal('upload_max_filesize');
				$postMaxSize = CKDAImportUtils::GetIniAbsVal('post_max_size');
				if($uploadMaxFilesize > 0 || $postMaxSize > 0)
				{
					$partError = '';
					if($uploadMaxFilesize > 0) $partError .= 'upload_max_filesize = '.($uploadMaxFilesize/(1024*1024)).'Mb<br>';
					if($postMaxSize > 0) $partError .= 'post_max_size = '.($postMaxSize/(1024*1024)).'Mb<br>';
					$strError.= '<br>'.sprintf(GetMessage("KDA_IE_FILE_UPLOAD_ERROR_MAX_SIZE"), $partError)."<br>";
				}
			}
		}
		
		if(!$SETTINGS_DEFAULT["DATA_FILE"] && $_POST['OLD_DATA_FILE'])
		{
			$SETTINGS_DEFAULT["DATA_FILE"] = $_POST['OLD_DATA_FILE'];
		}
		
		if($SETTINGS_DEFAULT["DATA_FILE"])
		{
			//$arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"]);
			$i = 0;
			while($i < 2 && !($arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"])))
			{
				\CFile::CleanCache($SETTINGS_DEFAULT["DATA_FILE"]);
				$i++;
			}
			if(stripos($arFile['SRC'], 'http')===0)
			{
				$arFileUrl = parse_url($arFile['SRC']);
				if($arFileUrl['path']) $arFile['SRC'] = $arFileUrl['path'];
			}
			$SETTINGS_DEFAULT['URL_DATA_FILE'] = $arFile['SRC'];
		}
		
		if(strlen($PROFILE_ID)==0)
		{
			$strError.= GetMessage("KDA_IE_PROFILE_NOT_CHOOSE")."<br>";
		}

		if (strlen($strError) <= 0)
		{
			if (strlen($DATA_FILE_NAME) <= 0)
			{
				if (strlen($SETTINGS_DEFAULT['URL_DATA_FILE']) > 0)
				{
					$SETTINGS_DEFAULT['URL_DATA_FILE'] = trim(str_replace("\\", "/", trim($SETTINGS_DEFAULT['URL_DATA_FILE'])) , "/");
					$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$SETTINGS_DEFAULT['URL_DATA_FILE']);
					if (
						(strlen($FILE_NAME) > 1)
						&& ($FILE_NAME === "/".$SETTINGS_DEFAULT['URL_DATA_FILE'])
						&& $io->FileExists($_SERVER["DOCUMENT_ROOT"].$FILE_NAME)
						/*&& ($APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W")*/
					)
					{
						$DATA_FILE_NAME = $FILE_NAME;
					}
				}
			}

			if (strlen($DATA_FILE_NAME) <= 0)
				$strError.= GetMessage("KDA_IE_NO_DATA_FILE")."<br>";
			else
				$SETTINGS_DEFAULT['URL_DATA_FILE'] = $DATA_FILE_NAME;
			
			/*if(ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME))=='xls' && ini_get('mbstring.func_overload')==2)
			{
				$strError.= GetMessage("KDA_IE_FUNC_OVERLOAD_XLS")."<br>";
			}*/
			
			if(!in_array(ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME)), array('txt', 'csv', 'xls', 'xlsx', 'xlsm', 'dbf')))
			{
				$strError.= GetMessage("KDA_IE_FILE_NOT_SUPPORT")."<br>";
				if(in_array(ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME)), array('xml', 'yml')))
				{
					$htmlError.= GetMessage("KDA_IE_USE_XML_MODULE")."<br>";
				}
			}

			if(!$SETTINGS_DEFAULT['HIGHLOADBLOCK_ID'])
			{
				$strError.= GetMessage("KDA_IE_NO_HIGHLOADBLOCK")."<br>";
			}				
			
			if((!$DATA_FILE_NAME = CKDAImportUtils::GetFileName($DATA_FILE_NAME)))
			{
				$strError.= GetMessage("KDA_IE_FILE_NOT_FOUND")."<br>";
			}
			
			if(empty($SETTINGS_DEFAULT['ELEMENT_UID']))
			{
				$strError.= GetMessage("KDA_IE_NO_ELEMENT_UID")."<br>";
			}
		}
		
		if (strlen($strError) <= 0)
		{
			/*Write profile*/
			$oProfile = CKDAImportProfile::getInstance('highload');
			if($PROFILE_ID === 'new')
			{
				$PID = $oProfile->Add($NEW_PROFILE_NAME, $SETTINGS_DEFAULT["DATA_FILE"]);
				if($PID===false)
				{
					if($ex = $APPLICATION->GetException())
					{
						$strError .= $ex->GetString().'<br>';
					}
				}
				else
				{
					$PROFILE_ID = $PID;
				}
			}
			/*/Write profile*/
		}

		if (strlen($strError) > 0)
			$STEP = 1;
		
		if(isset($_POST["saveConfigButton"]) && strlen($_POST["saveConfigButton"]) > 0 && !$notRewriteFile)
			$STEP = 1;
		//*****************************************************************//

	}
	
	if($ACTION == 'SHOW_FULL_LIST')
	{
		$sess = $_SESSION;
		session_write_close();
		$_SESSION = $sess;
		try{
			$pparams = array_merge($SETTINGS_DEFAULT, (isset($SETTINGS) && is_array($SETTINGS) ? $SETTINGS : array()));
			$arWorksheets = CKDAImportExcelHighload::GetPreviewData($DATA_FILE_NAME, $SHOW_FIRST_LINES, $pparams, $COUNT_COLUMNS);
		}catch(Exception $ex){
			$APPLICATION->RestartBuffer();
			ob_end_clean();
			echo GetMessage("KDA_IE_ERROR").$ex->getMessage();
			die();
		}
		
		$oProfile = CKDAImportProfile::getInstance('highload');
		$arProfile = $oProfile->GetByID($PROFILE_ID);
		if(is_array($arProfile['SETTINGS']['IMPORT_LINE']))
		{
			$SETTINGS['IMPORT_LINE'] = $arProfile['SETTINGS']['IMPORT_LINE'];
		}
		
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		
		if(!$arWorksheets) $arWorksheets = array();
		foreach($arWorksheets as $k=>$worksheet)
		{
			if($k==$LIST_NUMBER)
			{
				foreach($worksheet['lines'] as $line=>$arLine)
				{
					$checked = ((!isset($SETTINGS['IMPORT_LINE'][$k][$line]) && (!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k])) || $SETTINGS['IMPORT_LINE'][$k][$line]);
					ShowTblLine($arLine, $k, $line, $checked);
				}
			}
		}
		die();
	}
	
	if($ACTION == 'SHOW_REVIEW_LIST')
	{
		$sess = $_SESSION;
		session_write_close();
		$_SESSION = $sess;
		$fl = new CKDAFieldList($SETTINGS_DEFAULT);
		$arHighloadBlocks = $fl->GetHighloadBlocks();
		try{
			$pparams = array_merge($SETTINGS_DEFAULT, (isset($SETTINGS) && is_array($SETTINGS) ? $SETTINGS : array()));
			$arWorksheets = CKDAImportExcelHighload::GetPreviewData($DATA_FILE_NAME, $SHOW_FIRST_LINES, $pparams);
		}catch(Exception $ex){
			$APPLICATION->RestartBuffer();
			ob_end_clean();
			echo GetMessage("KDA_IE_ERROR").$ex->getMessage();
			die();
		}
		
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		
		if(!$arWorksheets) $arWorksheets = array();
		foreach($arWorksheets as $k=>$worksheet)
		{
			$columns = (count($worksheet['lines']) > 0 ? count($worksheet['lines'][0]) : 1) + 1;
			$bEmptyList = empty($worksheet['lines']);
			$iblockId = $SETTINGS_DEFAULT['HIGHLOADBLOCK_ID'];
		?>
			<table class="kda-ie-tbl <?if($bEmptyList){echo 'empty';}?>" data-list-index="<?echo $k;?>" data-iblock-id=<?echo $iblockId;?>>
				<tr class="heading">
					<td class="left"><?echo GetMessage("KDA_IE_LIST_TITLE"); ?> "<?echo $worksheet['title'];?>" <?if($bEmptyList){echo GetMessage("KDA_IE_EMPTY_LIST");}?> <a href="javascript:void(0)" onclick="EList.ShowListSettings(this)" class="list-settings-link" title="<?echo GetMessage("KDA_IE_LIST_SETTINGS");?>"></a></td>
					<td class="right list-settings">
						<?if(count($worksheet['lines']) > 0){?>
							<input type="hidden" name="SETTINGS[ADDITIONAL_SETTINGS][<?echo $k;?>]" value="<?if($SETTINGS['ADDITIONAL_SETTINGS'][$k])echo htmlspecialcharsex(CUtil::PhpToJSObject($SETTINGS['ADDITIONAL_SETTINGS'][$k]));?>">
							<input type="hidden" name="SETTINGS[LIST_LINES][<?echo $k;?>]" value="<?echo $worksheet['lines_count'];?>">
							<input type="hidden" name="SETTINGS[LIST_ACTIVE][<?echo $k;?>]" value="N">
							<input type="checkbox" name="SETTINGS[LIST_ACTIVE][<?echo $k;?>]" id="list_active_<?echo $k;?>" value="Y" <?=(!isset($SETTINGS['LIST_ACTIVE'][$k]) || $SETTINGS['LIST_ACTIVE'][$k]=='Y' ? 'checked' : '')?>> <label for="list_active_<?echo $k;?>"><small><?echo GetMessage("KDA_IE_DOWNLOAD_LIST"); ?></small></label>
							<a href="javascript:void(0)" class="showlist" onclick="EList.ToggleSettings(this)" title="<?echo GetMessage("KDA_IE_LIST_SHOW"); ?>"></a>
							<?
							if(is_array($SETTINGS['LIST_SETTINGS'][$k]))
							{
								foreach($SETTINGS['LIST_SETTINGS'][$k] as $k2=>$v2)
								{
									?><input type="hidden" name="SETTINGS[LIST_SETTINGS][<?echo $k;?>][<?echo $k2;?>]" value="<?echo htmlspecialcharsex($v2);?>"><?
								}
							}
						}?>
					</td>
				</tr>
				<tr class="settings">
					<td colspan="2">
						<table class="additional">
							<tr>
								<td><?echo GetMessage("KDA_IE_HIGHLOADBLOCK"); ?> </td>
								<td>
									<select name="SETTINGS[IBLOCK_ID][<?echo $k;?>]" onchange="EList.ChooseIblock(this);">
										<!--<option value=""><?echo GetMessage("KDA_IE_CHOOSE_IBLOCK"); ?></option>-->
										<?
										foreach($arHighloadBlocks as $hlBlock)
										{
											?><option value="<?echo $hlBlock["ID"];?>" <?if($hlBlock["ID"]==$iblockId){echo 'selected';}?>><?echo htmlspecialcharsbx($hlBlock["NAME"]); ?></option><?
										}
										?>
									</select>
								</td>
								<?/*?>
								<td width="50px">&nbsp;</td>
								<td><?echo GetMessage("KDA_IE_SECTION"); ?> </td>
								<td><?$fl->ShowSelectSections($iblockId, 'SETTINGS[SECTION_ID]['.$k.']', $SETTINGS['SECTION_ID'][$k]);?></td>
								<?*/?>
							</tr>
						</table>
						<?
						$fileExt = ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME));
						$changeCsvParams = (bool)($SETTINGS['CSV_PARAMS']['CHANGE']=='Y');
						$showAddSettings = (bool)($fileExt=='csv' || $fileExt=='txt');
						?>
						<div class="copysettings" <?if(!$showAddSettings){echo 'style="margin-top: -20px;"';}?>>
							<a href="javascript:void(0)" onclick="EList.ApplyToAllLists(this)"><?echo GetMessage("KDA_IE_APPLY_TO_ALL_LISTS"); ?></a>
						</div>
						<?
						if($showAddSettings)
						{
						?>
						<div class="addsettings">
							<a href="javascript:void(0)" class="addsettings_link" onclick="EList.ToggleAddSettingsBlock(this)"><span><?echo GetMessage("KDA_IE_ADDITIONAL_SETTINGS"); ?></span></a>
							<div class="addsettings_inner">
								<table class="additional">
									<col><col width="400px">
									<?
									$fileExt = ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME));
									$changeCsvParams = (bool)($SETTINGS['CSV_PARAMS']['CHANGE']=='Y');
									if($fileExt=='csv' || $fileExt=='txt')
									{
									?>
										<tr>
											<td><?echo sprintf(GetMessage("KDA_IE_CHANGE_CSV_PARAMS"), $fileExt); ?>:</td>
											<td>
												<input type="hidden" name="SETTINGS[CSV_PARAMS][CHANGE]" value="N">
												<input type="checkbox" name="SETTINGS[CSV_PARAMS][CHANGE]" value="Y" <?if($changeCsvParams){echo 'checked';}?> onchange="EList.ToggleAddSettings(this)">
											</td>
										</tr>

										<tr class="subfield" <?if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?echo GetMessage("KDA_IE_CHANGE_CSV_SEPARATOR"); ?>:</td>
											<td>
												<?
												$val = (isset($SETTINGS['CSV_PARAMS']['SEPARATOR']) && strlen(trim($SETTINGS['CSV_PARAMS']['SEPARATOR'])) > 0 ? trim($SETTINGS['CSV_PARAMS']['SEPARATOR']) : ';');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][SEPARATOR]" value="<?echo htmlspecialcharsex($val)?>" size="3" maxlength="3">
											</td>
										</tr>
										<tr class="subfield" <?if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?echo GetMessage("KDA_IE_CHANGE_CSV_ENCLOSURE"); ?>:</td>
											<td>
												<?
												$val = (isset($SETTINGS['CSV_PARAMS']['ENCLOSURE']) ? trim($SETTINGS['CSV_PARAMS']['ENCLOSURE']) : '"');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][ENCLOSURE]" value="<?echo htmlspecialcharsex($val)?>" size="3" maxlength="3">
											</td>
										</tr>
										<tr class="subfield" <?if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?echo GetMessage("KDA_IE_CHANGE_CSV_ENCODING"); ?>:</td>
											<td>
												<?
												$val = (isset($SETTINGS['CSV_PARAMS']['ENCODING']) && strlen(trim($SETTINGS['CSV_PARAMS']['ENCODING'])) > 0 ? trim($SETTINGS['CSV_PARAMS']['ENCODING']) : '');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][ENCODING]" value="<?echo htmlspecialcharsex($val)?>" size="10" maxlength="50">
											</td>
										</tr>
										<?if($fileExt=='txt'){?>
										<tr class="subfield" <?if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?echo GetMessage("KDA_IE_CHANGE_CSV_ROW_SEPARATOR"); ?>:</td>
											<td>
												<?
												$val = (isset($SETTINGS['CSV_PARAMS']['ROW_SEPARATOR']) && strlen(trim($SETTINGS['CSV_PARAMS']['ROW_SEPARATOR'])) > 0 ? trim($SETTINGS['CSV_PARAMS']['ROW_SEPARATOR']) : '');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][ROW_SEPARATOR]" value="<?echo htmlspecialcharsex($val)?>" size="10" maxlength="50">
											</td>
										</tr>
										<?}?>
									<?}?>
								</table>
							</div>
						</div>
						<?}?>
						<div class="set_scroll">
							<div></div>
						</div>
						<div class="set">						
						<table class="list">
						<?
						if(count($worksheet['lines']) > 0)
						{
							?>
								<tr>
									<td>
										<input type="hidden" name="SETTINGS[CHECK_ALL][<?echo $k;?>]" value="0"> 
										<input type="checkbox" name="SETTINGS[CHECK_ALL][<?echo $k;?>]" id="check_all_<?echo $k;?>" value="1" <?if(!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k]){echo 'checked';}?>> 
										<label for="check_all_<?echo $k;?>"><?echo GetMessage("KDA_IE_CHECK_ALL"); ?></label>
										<?$fl->ShowSelectFieldsHighload($iblockId, 'FIELDS_LIST['.$k.']')?>
									</td>
									<?
									$num_rows = count($worksheet['lines'][0]);
									for($i = 0; $i < $num_rows; $i++)
									{
										$arKeys = array($i);
										if(is_array($SETTINGS['FIELDS_LIST'][$k]))
											$arKeys = array_merge($arKeys, preg_grep('/^'.$i.'_\d+$/', array_keys($SETTINGS['FIELDS_LIST'][$k])));
										?>
										<td class="kda-ie-field-select" title="#CELL<?echo ($i+1);?>#">
											<?foreach($arKeys as $j){?>
												<div>
													<input type="hidden" name="SETTINGS[FIELDS_LIST][<?echo $k?>][<?echo $j?>]" value="<?echo $SETTINGS['FIELDS_LIST'][$k][$j]?>" >
													<input type="text" name="FIELDS_LIST_SHOW[<?echo $k?>][<?echo $j?>]" value="" class="fieldval">
													<a href="javascript:void(0)" class="field_settings <?=(empty($EXTRASETTINGS[$k][$j]) ? 'inactive' : '')?>" id="field_settings_<?=$k?>_<?=$j?>" title="<?echo GetMessage("KDA_IE_SETTINGS_FIELD"); ?>" onclick="EList.ShowFieldSettings(this);"></a>
													<a href="javascript:void(0)" class="field_delete" title="<?echo GetMessage("KDA_IE_SETTINGS_DELETE_FIELD"); ?>" onclick="EList.DeleteUploadField(this);"></a>
												</div>
											<?}?>
											<div class="kda-ie-field-select-btns">
												<div class="kda-ie-field-select-btns-inner">
													<a href="javascript:void(0)" class="kda-ie-add-load-field" title="<?echo GetMessage("KDA_IE_SETTINGS_ADD_FIELD"); ?>" onclick="EList.AddUploadField(this);"></a>
												</div>
											</div>
										</td>
										<?
									}
									?>
								</tr>
							<?
							
						}			
						
						foreach($worksheet['lines'] as $line=>$arLine)
						{
							$checked = ((!isset($SETTINGS['IMPORT_LINE'][$k][$line]) && (!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k])) || $SETTINGS['IMPORT_LINE'][$k][$line]);
							ShowTblLine($arLine, $k, $line, $checked);
						}
						?>
						</table>
						</div>
						<?if($worksheet['show_more']){?>
							<input type="button" value="<?echo GetMessage("KDA_IE_SHOW_LIST"); ?>" onclick="EList.ShowFull(this);">
						<?}?>
						<br><br>
					</td>
				</tr>
			</table>
		<?
		}
		die();
	}
	
	/*Обновление профиля*/
	if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!=='new')
	{
		$oProfile->Update($PROFILE_ID, $SETTINGS_DEFAULT, $SETTINGS);
	}
	/*/Обновление профиля*/
	
	if($ACTION == 'DO_IMPORT')
	{
		$oProfile = CKDAImportProfile::getInstance('highload');
		$oProfile->ApplyExtra($EXTRASETTINGS, $PROFILE_ID);
		$params = array_merge($SETTINGS_DEFAULT, $SETTINGS);
		$stepparams = $_POST['stepparams'];
		$sess = $_SESSION;
		session_write_close();
		$_SESSION = $sess;
		$arResult = $moduleRunnerClass::ImportHighloadblock($DATA_FILE_NAME, $params, $EXTRASETTINGS, $stepparams, $PROFILE_ID);
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		echo CUtil::PhpToJSObject($arResult);
		die();
	}
	//*****************************************************************//

}

/////////////////////////////////////////////////////////////////////
$APPLICATION->SetTitle(GetMessage("KDA_IE_PAGE_TITLE").$STEP);
require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
/*********************************************************************/
/********************  BODY  *****************************************/
/*********************************************************************/

if (!$moduleDemoExpiredFunc()) {
	$moduleShowDemoFunc();
}

$arSubMenu = array();
/*if($oProfile instanceof CKDAImportProfileDB)
{
	$arSubMenu[] = array(
		"TEXT"=>GetMessage("KDA_IE_MENU_PROFILE_LIST"),
		"TITLE"=>GetMessage("KDA_IE_MENU_PROFILE_LIST"),
		"LINK" => "/bitrix/admin/".$moduleFilePrefix."_profile_list.php?lang=".LANG,
	);
}*/
$arSubMenu[] = array(
	"TEXT"=>GetMessage("KDA_IE_SHOW_CRONTAB"),
	"TITLE"=>GetMessage("KDA_IE_SHOW_CRONTAB"),
	"ONCLICK" => "EProfile.ShowCron();",
);
$arSubMenu[] = array(
	"TEXT" => GetMessage("KDA_IE_TOOLS_IMG_LOADER"),
	"TITLE" => GetMessage("KDA_IE_TOOLS_IMG_LOADER"),
	"ONCLICK" => "EProfile.ShowMassUploader();"
);
$aMenu = array(
	array(
		"TEXT"=>GetMessage("KDA_IE_MENU_VIDEO"),
		"TITLE"=>GetMessage("KDA_IE_MENU_VIDEO"),
		"ONCLICK" => "EHelper.ShowHelp();",
		"ICON" => "",
	),
	array(
		/*"TEXT"=>GetMessage("KDA_IE_MENU_FAQ"),
		"TITLE"=>GetMessage("KDA_IE_MENU_FAQ"),
		"ONCLICK" => "EHelper.ShowHelp(1);",
		"ICON" => "",*/
		"HTML" => '<a href="https://esolutions.su/docs/kda.importexcel/" target="blank" class="adm-btn" title="'.GetMessage("KDA_IE_MENU_DOC").'">'.GetMessage("KDA_IE_MENU_DOC").'</a>'
	),
	array(
		"TEXT"=>GetMessage("KDA_IE_TOOLS_LIST"),
		"TITLE"=>GetMessage("KDA_IE_TOOLS_LIST"),
		"MENU" => $arSubMenu,
		"ICON" => "btn_green",
	)
);
$context = new CAdminContextMenu($aMenu);
$context->Show();


if ($STEP < 2)
{
	$oProfile = CKDAImportProfile::getInstance('highload');
	$arProfiles = $oProfile->GetProcessedProfiles();
	if(!empty($arProfiles))
	{
		$message = '';
		foreach($arProfiles as $k=>$v)
		{
			$message .= '<div class="kda-proccess-item">'.GetMessage("KDA_IE_PROCESSED_PROFILE").': '.$v['name'].' ('.GetMessage("KDA_IE_PROCESSED_PERCENT_LOADED").' '.$v['percent'].'%). &nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(0)" onclick="EProfile.ContinueProccess(this, '.$v['key'].')">'.GetMessage("KDA_IE_PROCESSED_CONTINUE").'</a> &nbsp; <a href="javascript:void(0)" onclick="EProfile.RemoveProccess(this, '.$v['key'].')">'.GetMessage("KDA_IE_PROCESSED_DELETE").'</a></div>';
		}
		CAdminMessage::ShowMessage(array(
			'TYPE' => 'error',
			'MESSAGE' => GetMessage("KDA_IE_PROCESSED_TITLE"),
			'DETAILS' => $message,
			'HTML' => true
		));
	}
}

if($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y')
{
	CAdminMessage::ShowMessage(array(
		'TYPE' => 'ok',
		'MESSAGE' => GetMessage("KDA_IE_DELETE_MODE_TITLE"),
		'DETAILS' => GetMessage("KDA_IE_DELETE_MODE_MESSAGE"),
		'HTML' => true
	));	
}

CAdminMessage::ShowMessage($strError);
?>

<form method="POST" action="<?echo $sDocPath ?>?lang=<?echo LANG ?>" ENCTYPE="multipart/form-data" name="dataload" id="dataload">

<?$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("KDA_IE_TAB1") ,
		"ICON" => "iblock",
		"TITLE" => GetMessage("KDA_IE_TAB1_ALT"),
	) ,
	array(
		"DIV" => "edit2",
		"TAB" => GetMessage("KDA_IE_TAB2") ,
		"ICON" => "iblock",
		"TITLE" => GetMessage("KDA_IE_TAB2_ALT"),
	) ,
	array(
		"DIV" => "edit3",
		"TAB" => GetMessage("KDA_IE_TAB3") ,
		"ICON" => "iblock",
		"TITLE" => GetMessage("KDA_IE_TAB3_ALT"),
	) ,
);

$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();
?>

<?$tabControl->BeginNextTab();
if ($STEP == 1)
{
	CKDAImportUtils::SaveStat();
	$fl = new CKDAFieldList($SETTINGS_DEFAULT);
	$oProfile = CKDAImportProfile::getInstance('highload');
?>

	<tr class="heading">
		<td colspan="2"><?echo GetMessage("KDA_IE_PROFILE_HEADER"); ?></td>
	</tr>

	<tr>
		<td><?echo GetMessage("KDA_IE_PROFILE"); ?>:</td>
		<td>
			<?$oProfile->ShowProfileList('PROFILE_ID');?>
			
			<?if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!='new'){?>
				<span class="kda-ie-edit-btns">
					<a href="javascript:void(0)" class="adm-table-btn-edit" onclick="EProfile.ShowRename();" title="<?echo GetMessage("KDA_IE_RENAME_PROFILE");?>" id="action_edit_button"></a>
					<a href="javascript:void(0);" class="adm-table-btn-copy" onclick="EProfile.Copy();" title="<?echo GetMessage("KDA_IE_COPY_PROFILE");?>" id="action_copy_button"></a>
					<a href="javascript:void(0);" class="adm-table-btn-delete" onclick="if(confirm('<?echo GetMessage("KDA_IE_DELETE_PROFILE_CONFIRM");?>')){EProfile.Delete();}" title="<?echo GetMessage("KDA_IE_DELETE_PROFILE");?>" id="action_delete_button"></a>
				</span>
			<?}?>
		</td>
	</tr>
	
	<tr id="new_profile_name">
		<td><?echo GetMessage("KDA_IE_NEW_PROFILE_NAME"); ?>:</td>
		<td>
			<input type="text" name="NEW_PROFILE_NAME" value="<?echo htmlspecialcharsbx($NEW_PROFILE_NAME)?>">
		</td>
	</tr>

	<?
	if(strlen($PROFILE_ID) > 0)
	{
	?>
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_DEFAULT_SETTINGS"); ?></td>
		</tr>
		
		<tr>
			<td width="40%"><?echo GetMessage("KDA_IE_URL_DATA_FILE"); ?></td>
			<td width="60%" class="kda-ie-file-choose">
				<!--KDA_IE_CHOOSE_FILE-->
				<?if($SETTINGS_DEFAULT['EMAIL_DATA_FILE']) echo '<input type="hidden" name="SETTINGS_DEFAULT[EMAIL_DATA_FILE]" value="'.htmlspecialcharsbx($SETTINGS_DEFAULT['EMAIL_DATA_FILE']).'">';?>
				<?if($SETTINGS_DEFAULT['EXT_DATA_FILE']) echo '<input type="hidden" name="EXT_DATA_FILE" value="'.htmlspecialcharsbx($SETTINGS_DEFAULT['EXT_DATA_FILE']).'">';?>
				<input type="hidden" name="OLD_DATA_FILE" value="<?echo htmlspecialcharsbx($SETTINGS_DEFAULT['DATA_FILE']); ?>">
				<?
				$arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"]);
				if(stripos($arFile['SRC'], 'http')===0)
				{
					$arFileUrl = parse_url($arFile['SRC']);
					if($arFileUrl['path']) $arFile['SRC'] = $arFileUrl['path'];
				}
				if($arFile['SRC'])
				{
					if(!file_exists($_SERVER['DOCUMENT_ROOT'].$arFile['SRC']))
					{
						if(defined("BX_UTF")) $arFile['SRC'] = $APPLICATION->ConvertCharsetArray($arFile['SRC'], LANG_CHARSET, 'CP1251');
						else $arFile['SRC'] = $APPLICATION->ConvertCharsetArray($arFile['SRC'], LANG_CHARSET, 'UTF-8');
						if(!file_exists($_SERVER['DOCUMENT_ROOT'].$arFile['SRC']))
						{
							unset($SETTINGS_DEFAULT["DATA_FILE"]);
						}
					}
				}
				else
				{
					unset($SETTINGS_DEFAULT["DATA_FILE"]);
				}
				//Cmodule::IncludeModule('fileman');
				echo \Bitrix\KdaImportexcel\CFileInput::Show("DATA_FILE", $SETTINGS_DEFAULT["DATA_FILE"], array(
					"IMAGE" => "N",
					"PATH" => "Y",
					"FILE_SIZE" => "Y",
					"DIMENSIONS" => "N"
				), array(
					'upload' => true,
					'medialib' => false,
					'file_dialog' => true,
					'cloud' => true,
					'email' => true,
					'linkauth' => true,
					'del' => false,
					'description' => false,
				));
				CKDAImportUtils::AddFileInputActions();
				?>
				<!--/KDA_IE_CHOOSE_FILE-->
			</td>
		</tr>

		<tr>
			<td><?echo GetMessage("KDA_IE_HIGHLOADBLOCK"); ?></td>
			<td>
				<select id="SETTINGS_DEFAULT[HIGHLOADBLOCK_ID]" name="SETTINGS_DEFAULT[HIGHLOADBLOCK_ID]" class="adm-detail-iblock-list">
					<option value=""><?echo GetMessage("KDA_IE_CHOOSE_HIGHLOADBLOCK"); ?></option>
					<?
					$arHighloadBlock = $fl->GetHighloadBlocks();
					foreach($arHighloadBlock as $arBlock)
					{
						?><option value="<?echo $arBlock['ID']?>" <?if($SETTINGS_DEFAULT['HIGHLOADBLOCK_ID']==$arBlock['ID']){echo 'selected';}?>><?echo $arBlock['NAME']; ?></option><?
					}
					?>
				</select>
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_PROCESSING"); ?></td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_ELEMENT_UID"); ?>: <span id="hint_ELEMENT_UID"></span><script>BX.hint_replace(BX('hint_ELEMENT_UID'), '<?echo GetMessage("KDA_IE_ELEMENT_UID_HINT"); ?>');</script></td>
			<td>
				<?$fl->ShowSelectUidFieldsHighload($SETTINGS_DEFAULT['HIGHLOADBLOCK_ID'], 'SETTINGS_DEFAULT[ELEMENT_UID][]', $SETTINGS_DEFAULT['ELEMENT_UID']);?>
			</td>
		</tr>

		<tr>
			<td><?echo GetMessage("KDA_IE_ONLY_UPDATE_MODE"); ?>: <span id="hint_ONLY_UPDATE_MODE"></span><script>BX.hint_replace(BX('hint_ONLY_UPDATE_MODE'), '<?echo GetMessage("KDA_IE_ONLY_UPDATE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_UPDATE_MODE]" value="Y" <?if($SETTINGS_DEFAULT['ONLY_UPDATE_MODE']=='Y'){echo 'checked';}?> onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_CREATE_MODE]', 'SETTINGS_DEFAULT[ONLY_DELETE_MODE]'])">
			</td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_ONLY_CREATE_MODE"); ?>: <span id="hint_ONLY_CREATE_MODE"></span><script>BX.hint_replace(BX('hint_ONLY_CREATE_MODE'), '<?echo GetMessage("KDA_IE_ONLY_CREATE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_CREATE_MODE]" value="Y" <?if($SETTINGS_DEFAULT['ONLY_CREATE_MODE']=='Y'){echo 'checked';}?> onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_UPDATE_MODE]', 'SETTINGS_DEFAULT[ONLY_DELETE_MODE]'])">
			</td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_ONLY_DELETE_MODE"); ?>: <span id="hint_ONLY_DELETE_MODE"></span><script>BX.hint_replace(BX('hint_ONLY_DELETE_MODE'), '<?echo GetMessage("KDA_IE_ONLY_DELETE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_DELETE_MODE]" value="Y" <?if($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y'){echo 'checked';}?> onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_UPDATE_MODE]', 'SETTINGS_DEFAULT[ONLY_CREATE_MODE]'], '<?echo htmlspecialcharsex(GetMessage("KDA_IE_ONLY_DELETE_MODE_CONFIRM")); ?>')">
			</td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_ELEMENT_NOT_UPDATE_WO_CHANGES"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_UPDATE_WO_CHANGES]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_NOT_UPDATE_WO_CHANGES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>

		<tr>
			<td><?echo GetMessage("KDA_IE_ELEMENT_MULTIPLE_SEPARATOR"); ?>:</td>
			<td>
				<input type="text" name="SETTINGS_DEFAULT[ELEMENT_MULTIPLE_SEPARATOR]" size="3" value="<?echo ($SETTINGS_DEFAULT['ELEMENT_MULTIPLE_SEPARATOR'] ? htmlspecialcharsbx($SETTINGS_DEFAULT['ELEMENT_MULTIPLE_SEPARATOR']) : ';'); ?>">
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_PROCESSING_MISSING_ELEMENTS"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show"><?echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_ELEMENT_MISSING_REMOVE_ELEMENT"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_MISSING_REMOVE_ELEMENT]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_MISSING_REMOVE_ELEMENT']=='Y'){echo 'checked';}?> data-confirm="<?echo GetMessage("KDA_IE_ELEMENT_MISSING_REMOVE_ELEMENT_CONFIRM"); ?>">
			</td>
		</tr>
		
		<tr>
			<td colspan="2" align="center">
				<input type="hidden" id="ELEMENT_MISSING_FILTER" name="SETTINGS_DEFAULT[ELEMENT_MISSING_FILTER]" value="<?echo htmlspecialcharsbx($SETTINGS_DEFAULT['ELEMENT_MISSING_FILTER']);?>">
				<a href="javascript:void(0)" onclick="EProfile.OpenMissignElementFilter(this)"><?echo GetMessage("KDA_IE_ELEMENT_MISSING_SET_FILTER"); ?></a>
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_ADDITONAL"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show" id="kda-head-more-link"><?echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_NOT_LOAD_STYLES"); ?>: <span id="hint_ELEMENT_NOT_LOAD_STYLES"></span><script>BX.hint_replace(BX('hint_ELEMENT_NOT_LOAD_STYLES'), '<?echo GetMessage("KDA_IE_NOT_LOAD_STYLES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_LOAD_STYLES]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_NOT_LOAD_STYLES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_NOT_LOAD_FORMATTING"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_LOAD_FORMATTING]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_NOT_LOAD_FORMATTING']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?echo GetMessage("KDA_IE_LOAD_IMAGES"); ?>: <span id="hint_ELEMENT_LOAD_IMAGES"></span><script>BX.hint_replace(BX('hint_ELEMENT_LOAD_IMAGES'), '<?echo GetMessage("KDA_IE_LOAD_IMAGES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_LOAD_IMAGES]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_LOAD_IMAGES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td class="kda-ie-settings-margin-container" colspan="2" align="center">
				<a href="javascript:void(0)" onclick="ESettings.ShowPHPExpression(this)"><?echo GetMessage("KDA_IE_ONAFTERSAVE_HANDLER");?></a>
				<div class="kda-ie-settings-phpexpression" style="display: none;">
					<?echo GetMessage("KDA_IE_ONAFTERSAVE_HANDLER_HINT");?>
					<textarea name="SETTINGS_DEFAULT[ONAFTERSAVE_HANDLER]"><?echo $SETTINGS_DEFAULT['ONAFTERSAVE_HANDLER']?></textarea>
				</div>
			</td>
		</tr>
		
	<?
	}
}
$tabControl->EndTab();
?>

<?$tabControl->BeginNextTab();
if ($STEP == 2)
{
?>
	
	<tr>
		<td colspan="2" id="preview_file">
			<div class="kda-ie-file-preloader">
				<?echo GetMessage("KDA_IE_PRELOADING"); ?>
			</div>
		</td>
	</tr>
	
	<?
}
$tabControl->EndTab();
?>


<?$tabControl->BeginNextTab();
if ($STEP == 3)
{
?>
	<tr>
		<td id="resblock" class="kda-ie-result">
		 <table width="100%"><tr><td width="50%">
			<div id="progressbar"><span class="pline"></span><span class="presult load"><b>0%</b><span 
				data-prefix="<?echo GetMessage("KDA_IE_READ_LINES"); ?>" 
				data-import="<?echo GetMessage("KDA_IE_STATUS_IMPORT"); ?>" 
				data-deactivate_elements="<?echo GetMessage("KDA_IE_STATUS_DEACTIVATE_ELEMENTS"); ?>" 
				data-deactivate_sections="<?echo GetMessage("KDA_IE_STATUS_DEACTIVATE_SECTIONS"); ?>" 
			><?echo GetMessage("KDA_IE_IMPORT_INIT"); ?></span></span></div>

			<div id="block_error_import" style="display: none;">
				<?echo CAdminMessage::ShowMessage(array(
					"TYPE" => "ERROR",
					"MESSAGE" => GetMessage("KDA_IE_IMPORT_ERROR_CONNECT"),
					"DETAILS" => '<div><a href="javascript:void(0)" onclick="EProfile.ContinueProccess(this, '.$PROFILE_ID.');">'.GetMessage("KDA_IE_PROCESSED_CONTINUE").'</a><br><br>'.sprintf(GetMessage("KDA_IE_IMPORT_ERROR_CONNECT_COMMENT"), '/bitrix/admin/settings.php?lang=ru&mid='.$moduleId.'&mid_menu=1').'</div>',
					"HTML" => true,
				))?>
			</div>
			
			<div id="block_error" style="display: none;">
				<?echo CAdminMessage::ShowMessage(array(
					"TYPE" => "ERROR",
					"MESSAGE" => GetMessage("KDA_IE_IMPORT_ERROR"),
					"DETAILS" => '<div id="res_error"></div>',
					"HTML" => true,
				))?>
			</div>
		 </td><td>
			<div class="detail_status">
				<?echo CAdminMessage::ShowMessage(array(
					"TYPE" => "PROGRESS",
					"MESSAGE" => '<!--<div id="res_continue">'.GetMessage("KDA_IE_AUTO_REFRESH_CONTINUE").'</div><div id="res_finish" style="display: none;">'.GetMessage("KDA_IE_SUCCESS").'</div>-->',
					"DETAILS" =>

					GetMessage("KDA_IE_SU_ALL").' <b id="total_line">0</b><br>'
					.GetMessage("KDA_IE_SU_CORR").' <b id="correct_line">0</b><br>'
					.GetMessage("KDA_IE_SU_ER").' <b id="error_line">0</b><br>'
					.GetMessage("KDA_IE_SU_ELEMENT_ADDED").' <b id="element_added_line">0</b><br>'
					.GetMessage("KDA_IE_SU_ELEMENT_UPDATED").' <b id="element_updated_line">0</b><br>'
					.($SETTINGS_DEFAULT['ELEMENT_MISSING_REMOVE_ELEMENT']=='Y' || $SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y' ? (GetMessage("KDA_IE_SU_REMOVE_ELEMENT").' <b id="element_removed_line">0</b><br>') : '')
					.'<div id="redirect_message">'.GetMessage("KDA_IE_REDIRECT_MESSAGE").'</div>',
					"HTML" => true,
				))?>
			</div>
		 </td></tr></table>
		</td>
	</tr>
<?
}
$tabControl->EndTab();
?>

<?$tabControl->Buttons();
?>


<?echo bitrix_sessid_post(); ?>
<?
if($STEP > 1)
{
	if(strlen($PROFILE_ID) > 0)
	{
		?><input type="hidden" name="PROFILE_ID" value="<?echo htmlspecialcharsbx($PROFILE_ID) ?>"><?
	}
	else
	{
		foreach($SETTINGS_DEFAULT as $k=>$v)
		{
			?><input type="hidden" name="SETTINGS_DEFAULT[<?echo $k?>]" value="<?echo htmlspecialcharsbx($v) ?>"><?
		}
	}
}
?>


<?
if($STEP == 2){ ?>
<input type="submit" name="backButton" value="&lt;&lt; <?echo GetMessage("KDA_IE_BACK"); ?>">
<input type="submit" name="saveConfigButton" value="<?echo GetMessage("KDA_IE_SAVE_CONFIGURATION"); ?>" style="float: right;">
<?
}

if($STEP < 3)
{
?>
	<input type="hidden" name="STEP" value="<?echo $STEP + 1; ?>">
	<input type="submit" value="<?echo ($STEP == 2) ? GetMessage("KDA_IE_NEXT_STEP_F") : GetMessage("KDA_IE_NEXT_STEP"); ?> &gt;&gt;" name="submit_btn" class="adm-btn-save">
<? 
}
else
{
?>
	<input type="hidden" name="STEP" value="1">
	<input type="submit" name="backButton2" value="&lt;&lt; <?echo GetMessage("KDA_IE_2_1_STEP"); ?>" class="adm-btn-save">
<?
}
?>

<?$tabControl->End();
?>

</form>

<script language="JavaScript">

<?if ($STEP < 2): 
	$arFile = CKDAImportUtils::GetShowFileBySettings($SETTINGS_DEFAULT);
	if($arFile['link'])
	{
		?>
		$('#bx_file_data_file_cont .adm-input-file-name').attr('target', '_blank').attr('href', '<?echo htmlspecialcharsex($arFile['link'])?>');<?
	}
	if($arFile['path'])
	{
		?>
		$('#bx_file_data_file_cont .adm-input-file-name').text('<?echo $arFile['path']?>');<?
	}
?>
tabControl.SelectTab("edit1");
tabControl.DisableTab("edit2");
tabControl.DisableTab("edit3");
<?elseif ($STEP == 2): 
	$fl = new CKDAFieldList($SETTINGS_DEFAULT);
	$arMenu = $fl->GetLineActions();
	foreach($arMenu as $k=>$v)
	{
		$arMenu[$k] = $k.": {text: '".$v['TEXT']."', title: '".$v['TITLE']."'}";
	}
?>
tabControl.SelectTab("edit2");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit3");
<?elseif ($STEP > 2): ?>
tabControl.SelectTab("edit3");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit2");

<?if($_POST['PROCESS_CONTINUE']=='Y'){
	$oProfile = CKDAImportProfile::getInstance('highload');
?>
	EImport.Init(<?=CUtil::PhpToJSObject($_POST);?>, <?=CUtil::PhpToJSObject($oProfile->GetProccessParams($_POST['PROFILE_ID']));?>);
<?}else{?>
	EImport.Init(<?=CUtil::PhpToJSObject($_POST);?>);
<?}?>
<?endif; ?>
//-->
</script>

<?
require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
?>
