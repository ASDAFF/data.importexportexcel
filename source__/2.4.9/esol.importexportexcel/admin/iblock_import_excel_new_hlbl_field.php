<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.importexportexcel';
CModule::IncludeModule('highloadblock');
CModule::IncludeModule($moduleId);
IncludeModuleLangFile(__FILE__);

$HLBL_ID = (int)$_REQUEST['HLBL_ID'];

$message = '';
if($_POST['action']=='change_type')
{
	
}
if($_POST['action']=='save')
{
	define('PUBLIC_AJAX_MODE', 'Y');
	$arFields = array(
		"ENTITY_ID" => $_REQUEST["ENTITY_ID"],
		"FIELD_NAME" => $_REQUEST["FIELD_NAME"],
		"USER_TYPE_ID" => $_REQUEST["USER_TYPE_ID"],
		"XML_ID" => $_REQUEST["XML_ID"],
		"SORT" => $_REQUEST["SORT"],
		"MULTIPLE" => $_REQUEST["MULTIPLE"],
		"MANDATORY" => $_REQUEST["MANDATORY"],
		"SHOW_FILTER" => $_REQUEST["SHOW_FILTER"],
		"SHOW_IN_LIST" => $_REQUEST["SHOW_IN_LIST"],
		"EDIT_IN_LIST" => $_REQUEST["EDIT_IN_LIST"],
		"IS_SEARCHABLE" => $_REQUEST["IS_SEARCHABLE"],
		"SETTINGS" => $_REQUEST["SETTINGS"],
		"EDIT_FORM_LABEL" => $_REQUEST["EDIT_FORM_LABEL"],
		"LIST_COLUMN_LABEL" => $_REQUEST["LIST_COLUMN_LABEL"],
		"LIST_FILTER_LABEL" => $_REQUEST["LIST_FILTER_LABEL"],
		"ERROR_MESSAGE" => $_REQUEST["ERROR_MESSAGE"],
		"HELP_MESSAGE" => $_REQUEST["HELP_MESSAGE"],
	);
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$arFields = $APPLICATION->ConvertCharsetArray($arFields, 'UTF-8', 'CP1251');
	}
	
	$obUserField  = new CUserTypeEntity;
	$ID = $obUserField->Add($arFields);
	if($ID)
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
	
		$nameLang = ($arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] ? $arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] : $arFields['FIELD_NAME']);
		echo '<script>EList.OnAfterAddNewProperty("'.htmlspecialcharsex($_REQUEST['PARENT_FIELD_NAME']).'", "'.htmlspecialcharsex($arFields['FIELD_NAME']).'", "'.htmlspecialcharsex($nameLang).'", "'.$HLBL_ID.'");</script>';
		die();
	}
	else
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("KDA_IE_USER_TYPE_SAVE_ERROR"), $e);
	}
}


if(!$USER_TYPE_ID) $USER_TYPE_ID = 'string';
$ENTITY_ID = htmlspecialcharsex('HLBLOCK_'.$HLBL_ID);
if(!isset($FIELD_NAME)) $FIELD_NAME = 'UF_';
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="new_property" id="newPropertyForm">
	<input type="hidden" name="action" value="save">
	<?
	if($message){
		echo $message->Show();
	}
	if($_POST)
	{
		?><script>
			EList.NewPropDialogButtonsSet(true);
		</script><?
	}
	?>
	
	<table width="100%">
		<col width="50%">
		<col width="50%">
		<tr class="adm-detail-required-field">
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_USER_TYPE_ID")?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				$arUserTypes = $USER_FIELD_MANAGER->GetUserType();
				$arr = array("reference"=>array(), "reference_id"=>array());
				foreach($arUserTypes as $arUserType)
				{
					$arr["reference"][] = $arUserType["DESCRIPTION"];
					$arr["reference_id"][] = $arUserType["USER_TYPE_ID"];
				}
				//echo SelectBoxFromArray("USER_TYPE_ID", $arr, $USER_TYPE_ID, "", 'OnChange="'.htmlspecialcharsbx('window.location=\''.CUtil::JSEscape($APPLICATION->GetCurPageParam("", array("USER_TYPE_ID")).'&back_url='.urlencode($back_url).'&list_url='.urlencode($list_url).'&ENTITY_ID='.$ENTITY_ID.'&USER_TYPE_ID=').'\' + this.value').'"');
				echo SelectBoxFromArray("USER_TYPE_ID", $arr, $USER_TYPE_ID, "", 'OnChange="EList.NewPropDialogChangeType(this);"');
				?>
			</td>
		</tr>
		<tr class="adm-detail-required-field">
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_ENTITY_ID")?>:</td>
			<td class="adm-detail-content-cell-r">
				<?=$ENTITY_ID?>
				<input type="hidden" name="ENTITY_ID" value="<?=$ENTITY_ID?>">
			</td>
		</tr>
		<tr class="adm-detail-required-field">
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_FIELD_NAME")?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD_NAME" value="<?=$FIELD_NAME?>" maxlength="20">
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_XML_ID")?>:</td>
			<td class="adm-detail-content-cell-r"><input type="text" name="XML_ID" value="<?=$XML_ID?>" maxlength="255"></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_SORT")?>:</td>
			<td class="adm-detail-content-cell-r"><input type="text" name="SORT" value="<?=$SORT?>"></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_MULTIPLE")?>:</td>
			<td class="adm-detail-content-cell-r">
				<?if($ID>0):?>
					<?=$MULTIPLE == "Y"? GetMessage("KDA_IE_MAIN_YES"): GetMessage("KDA_IE_MAIN_NO")?>
				<?else:?>
					<input type="checkbox" name="MULTIPLE" value="Y"<?if($MULTIPLE == "Y") echo " checked"?> >
				<?endif?>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_MANDATORY")?>:</td>
			<td class="adm-detail-content-cell-r"><input type="checkbox" name="MANDATORY" value="Y"<?if($MANDATORY == "Y") echo " checked"?> ></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_SHOW_FILTER")?>:</td>
			<td class="adm-detail-content-cell-r"><?
				$arr = array(
					"reference" => array(
						GetMessage("KDA_IE_USER_TYPE_FILTER_N"),
						GetMessage("KDA_IE_USER_TYPE_FILTER_I"),
						GetMessage("KDA_IE_USER_TYPE_FILTER_E"),
						GetMessage("KDA_IE_USER_TYPE_FILTER_S"),
					),
					"reference_id" => array(
						"N",
						"I",
						"E",
						"S",
					),
				);
				echo SelectBoxFromArray("SHOW_FILTER", $arr, $SHOW_FILTER);
			?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_SHOW_IN_LIST")?>:</td>
			<td class="adm-detail-content-cell-r"><input type="checkbox" name="SHOW_IN_LIST" value="N"<?if($SHOW_IN_LIST == "N") echo " checked"?> ></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_EDIT_IN_LIST")?>:</td>
			<td class="adm-detail-content-cell-r"><input type="checkbox" name="EDIT_IN_LIST" value="N"<?if($EDIT_IN_LIST == "N") echo " checked"?> ></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?=GetMessage("KDA_IE_USERTYPE_IS_SEARCHABLE")?>:</td>
			<td class="adm-detail-content-cell-r"><input type="checkbox" name="IS_SEARCHABLE" value="Y"<?if($IS_SEARCHABLE == "Y") echo " checked"?> ></td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_USERTYPE_SETTINGS")?></td>
		</tr>
		<?if($ID > 0):
			echo $USER_FIELD_MANAGER->GetSettingsHTML($arUserField, $bVarsFromForm);
		else:
			$arUserType = $USER_FIELD_MANAGER->GetUserType($USER_TYPE_ID);
			if(!$arUserType)
				$arUserType = array_shift($arUserTypes);
			echo $USER_FIELD_MANAGER->GetSettingsHTML($arUserType["USER_TYPE_ID"], $bVarsFromForm);
		endif;?>
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_USERTYPE_LANG_SETTINGS")?></td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<table border="0" cellspacing="10" cellpadding="2">
					<tr>
						<td align="right"><?echo GetMessage("KDA_IE_USER_TYPE_LANG");?></td>
						<td align="center" width="200"><?echo GetMessage("KDA_IE_USER_TYPE_EDIT_FORM_LABEL");?></td>
						<td align="center" width="200"><?echo GetMessage("KDA_IE_USER_TYPE_LIST_COLUMN_LABEL");?></td>
						<td align="center" width="200"><?echo GetMessage("KDA_IE_USER_TYPE_LIST_FILTER_LABEL");?></td>
						<td align="center" width="200"><?echo GetMessage("KDA_IE_USER_TYPE_ERROR_MESSAGE");?></td>
						<td align="center" width="200"><?echo GetMessage("KDA_IE_USER_TYPE_HELP_MESSAGE");?></td>
					</tr>
					<?
					$rsLanguage = CLanguage::GetList($by, $order, array());
					while($arLanguage = $rsLanguage->Fetch()):
						$htmlLID = htmlspecialcharsbx($arLanguage["LID"]);
					?>
					<tr>
						<td align="right"><?echo htmlspecialcharsbx($arLanguage["NAME"])?>:</td>
						<td align="center"><input type="text" name="EDIT_FORM_LABEL[<?echo $htmlLID?>]" size="20" maxlength="255" value="<?echo htmlspecialcharsbx($bVarsFromForm? $_REQUEST["EDIT_FORM_LABEL"][$arLanguage["LID"]]: $arUserField["EDIT_FORM_LABEL"][$arLanguage["LID"]])?>"></td>
						<td align="center"><input type="text" name="LIST_COLUMN_LABEL[<?echo $htmlLID?>]" size="20" maxlength="255" value="<?echo htmlspecialcharsbx($bVarsFromForm? $_REQUEST["LIST_COLUMN_LABEL"][$arLanguage["LID"]]: $arUserField["LIST_COLUMN_LABEL"][$arLanguage["LID"]])?>"></td>
						<td align="center"><input type="text" name="LIST_FILTER_LABEL[<?echo $htmlLID?>]" size="20" maxlength="255" value="<?echo htmlspecialcharsbx($bVarsFromForm? $_REQUEST["LIST_FILTER_LABEL"][$arLanguage["LID"]]: $arUserField["LIST_FILTER_LABEL"][$arLanguage["LID"]])?>"></td>
						<td align="center"><input type="text" name="ERROR_MESSAGE[<?echo $htmlLID?>]" size="20" maxlength="255" value="<?echo htmlspecialcharsbx($bVarsFromForm? $_REQUEST["ERROR_MESSAGE"][$arLanguage["LID"]]: $arUserField["ERROR_MESSAGE"][$arLanguage["LID"]])?>"></td>
						<td align="center"><input type="text" name="HELP_MESSAGE[<?echo $htmlLID?>]" size="20" maxlength="255" value="<?echo htmlspecialcharsbx($bVarsFromForm? $_REQUEST["HELP_MESSAGE"][$arLanguage["LID"]]: $arUserField["HELP_MESSAGE"][$arLanguage["LID"]])?>"></td>
					</tr>
					<?endwhile?>
				</table>
			</td>
		</tr>
		
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>