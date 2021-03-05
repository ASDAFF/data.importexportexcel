<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.importexportexcel';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
$bCurrency = CModule::IncludeModule("currency");
IncludeModuleLangFile(__FILE__);

$IBLOCK_ID = (int)$_REQUEST['IBLOCK_ID'];
$OFFERS_IBLOCK_ID = CKDAImportUtils::GetOfferIblock($IBLOCK_ID);

/*$obJSPopup = new CJSPopup();
$obJSPopup->ShowTitlebar(GetMessage("KDA_IE_NP_TITLE"));*/

$error = '';
if($_POST['action']=='save' && $_POST['FIELD'])
{
	define('PUBLIC_AJAX_MODE', 'Y');
	$arFields = $_POST['FIELD'];
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$arFields = $APPLICATION->ConvertCharsetArray($arFields, 'UTF-8', 'CP1251');
	}
	$arFields['IBLOCK_ID'] = $IBLOCK_ID;
	$sFieldPrefix = 'IP_PROP';
	if($_POST['PROPS_FOR']==1)
	{
		$arFields['IBLOCK_ID'] = $OFFERS_IBLOCK_ID;
		$sFieldPrefix = 'OFFER_IP_PROP';
	}
	if(strpos($arFields['PROPERTY_TYPE'], ':')!==false)
	{
		list($ptype, $utype) = explode(':', $arFields['PROPERTY_TYPE'], 2);
		$arFields['PROPERTY_TYPE'] = $ptype;
		$arFields['USER_TYPE'] = $utype;
	}
	if($arFields['SMART_FILTER'] == 'Y')
	{
		if(CIBlock::GetArrayByID($arFields["IBLOCK_ID"], "SECTION_PROPERTY") != "Y")
		{
			$ib = new CIBlock;
			$ib->Update($arFields["IBLOCK_ID"], array('SECTION_PROPERTY'=>'Y'));
		}
	}
	
	if(strlen($arFields['CODE']) > 0)
	{
		$dbRes = CIBlockProperty::GetList(array(), array('CODE'=>$arFields['CODE'], 'IBLOCK_ID'=>$arFields['IBLOCK_ID']));
		if($dbRes->Fetch())
		{
			$error = GetMessage("KDA_IE_NP_PROP_CODE_EXISTS");
		}
	}
	
	if(strlen($error)==0)
	{
		$ibp = new CIBlockProperty;
		$PropID = $ibp->Add($arFields);
		
		if($PropID)
		{
			$APPLICATION->RestartBuffer();
			ob_end_clean();
		
			echo '<script>EList.OnAfterAddNewProperty("'.htmlspecialcharsex($_REQUEST['FIELD_NAME']).'", "'.$sFieldPrefix.$PropID.'", "'.htmlspecialcharsex($arFields['NAME']).'", "'.$IBLOCK_ID.'");</script>';
			die();
		}
		else
		{
			$error = $ibp->LAST_ERROR;
		}
	}
}

$arUserTypeList = CIBlockProperty::GetUserType();
\Bitrix\Main\Type\Collection::sortByColumn($arUserTypeList, array('DESCRIPTION' => SORT_STRING));
$boolUserPropExist = !empty($arUserTypeList);
$PROPERTY_TYPE = 'S';
if($_POST['FIELD']['PROPERTY_TYPE']) $PROPERTY_TYPE = $_POST['FIELD']['PROPERTY_TYPE'];
if(!$_POST['FIELD']['NAME'] && $_REQUEST['PROP_NAME'])
{
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$_REQUEST['PROP_NAME'] = $APPLICATION->ConvertCharset($_REQUEST['PROP_NAME'], 'UTF-8', 'CP1251');
	}
	$_POST['FIELD']['NAME'] = $_REQUEST['PROP_NAME'];
	if(!$_POST['FIELD']['CODE'])
	{
		$arParams = array(
			'max_len' => 50,
			'change_case' => 'U',
			'replace_space' => '_',
			'replace_other' => '_',
			'delete_repeat_replace' => 'Y',
		);
		$propCode = $codePrefix.CUtil::translit($_POST['FIELD']['NAME'], LANGUAGE_ID, $arParams);
		$propCode = preg_replace('/[^a-zA-Z0-9_]/', '', $propCode);
		$propCode = preg_replace('/^[0-9_]+/', '', $propCode);
		$_POST['FIELD']['CODE'] = $propCode;
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="new_property" id="newPropertyForm">
	<input type="hidden" name="action" value="save">
	<?if($error){
		ShowError($error);
		?><script>
			EList.NewPropDialogButtonsSet(true);
		</script><?
	}?>
	
	<table width="100%">
		<col width="50%">
		<col width="50%">
		
		<?
		if($OFFERS_IBLOCK_ID)
		{
			?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_PROPS_FOR");?>:</td>
				<td class="adm-detail-content-cell-r">
					<select name="PROPS_FOR">
						<option value="0"><?echo GetMessage("KDA_IE_NP_PROPS_FOR_GOODS");?></option>
						<option value="1" <?echo ($_POST['PROPS_FOR']=='1' ? ' selected' : '')?>><?echo GetMessage("KDA_IE_NP_PROPS_FOR_OFFERS");?></option>
					</select>
				</td>
			</tr>
			<?
		}
		?>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_TYPE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<select name="FIELD[PROPERTY_TYPE]">
				<?
					if ($boolUserPropExist)
					{
						?><optgroup label="<? echo GetMessage('KDA_IE_NP_PROPERTY_BASE_TYPE_GROUP'); ?>"><?
					}
					?>
					<option value="S" <?if($PROPERTY_TYPE=="S")echo " selected"?>><?echo GetMessage("KDA_IE_NP_IBLOCK_PROP_S")?></option>
					<option value="N" <?if($PROPERTY_TYPE=="N")echo " selected"?>><?echo GetMessage("KDA_IE_NP_IBLOCK_PROP_N")?></option>
					<option value="L" <?if($PROPERTY_TYPE=="L")echo " selected"?>><?echo GetMessage("KDA_IE_NP_IBLOCK_PROP_L")?></option>
					<option value="F" <?if($PROPERTY_TYPE=="F")echo " selected"?>><?echo GetMessage("KDA_IE_NP_IBLOCK_PROP_F")?></option>
					<option value="G" <?if($PROPERTY_TYPE=="G")echo " selected"?>><?echo GetMessage("KDA_IE_NP_IBLOCK_PROP_G")?></option>
					<option value="E" <?if($PROPERTY_TYPE=="E")echo " selected"?>><?echo GetMessage("KDA_IE_NP_IBLOCK_PROP_E")?></option>
					<?
					if ($boolUserPropExist)
					{
					?></optgroup><optgroup label="<? echo GetMessage('KDA_IE_NP_PROPERTY_USER_TYPE_GROUP'); ?>"><?
					}
					foreach($arUserTypeList as  $ar)
					{
						?><option value="<?=htmlspecialcharsbx($ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"])?>" <?if($PROPERTY_TYPE==$ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"])echo " selected"?>><?=htmlspecialcharsbx($ar["DESCRIPTION"])?></option>
						<?
					}
					if ($boolUserPropExist)
					{
						?></optgroup><?
					}
					?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_ACTIVE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[ACTIVE]" value="Y" <?if(!isset($_POST['FIELD']['ACTIVE']) || $_POST['FIELD']['ACTIVE']=='Y'){?>checked<?}?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_SORT");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD[SORT]" value="<?echo ($_POST['FIELD']['SORT'] ? htmlspecialcharsex($_POST['FIELD']['SORT']) : '500')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><b><?echo GetMessage("KDA_IE_NP_NAME");?></b>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD[NAME]" value="<?echo ($_POST['FIELD']['NAME'] ? htmlspecialcharsex($_POST['FIELD']['NAME']) : '')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_CODE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD[CODE]" value="<?echo ($_POST['FIELD']['CODE'] ? htmlspecialcharsex($_POST['FIELD']['CODE']) : '')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_MULTIPLE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[MULTIPLE]" value="Y" <?if(isset($_POST['FIELD']['MULTIPLE']) && $_POST['FIELD']['MULTIPLE']=='Y'){?>checked<?}?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_IS_REQUIRED");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[IS_REQUIRED]" value="Y" <?if(isset($_POST['FIELD']['IS_REQUIRED']) && $_POST['FIELD']['IS_REQUIRED']=='Y'){?>checked<?}?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_SEARCHABLE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[SEARCHABLE]" value="Y" <?if(isset($_POST['FIELD']['SEARCHABLE']) && $_POST['FIELD']['SEARCHABLE']=='Y'){?>checked<?}?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_FILTRABLE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[FILTRABLE]" value="Y" <?if(isset($_POST['FIELD']['FILTRABLE']) && $_POST['FIELD']['FILTRABLE']=='Y'){?>checked<?}?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_SMART_FILTER");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[SMART_FILTER]" value="Y" <?if(isset($_POST['FIELD']['SMART_FILTER']) && $_POST['FIELD']['SMART_FILTER']=='Y'){?>checked<?}?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_SHOW_IN_FORM");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="hidden" name="FIELD[SECTION_PROPERTY]" value="N">
				<input type="checkbox" name="FIELD[SECTION_PROPERTY]" value="Y" <?if(!isset($_POST['FIELD']['SECTION_PROPERTY']) || $_POST['FIELD']['SECTION_PROPERTY']!='N'){?>checked<?}?>>
			</td>
		</tr>
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>