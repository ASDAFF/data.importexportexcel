<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.importexportexcel';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
$bCurrency = CModule::IncludeModule("currency");
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$IBLOCK_ID = $_REQUEST['IBLOCK_ID'];
$fieldName = htmlspecialcharsex($_GET['field_name']);

$fl = new CKDAFieldList();
$arFieldGroups = $fl->GetFields($IBLOCK_ID);
$arFields = array();
if(is_array($arFieldGroups))
{
	foreach($arFieldGroups as $arGroup)
	{
		if(is_array($arGroup['items']))
		{
			$arFields = array_merge($arFields, $arGroup['items']);
		}
	}
}

$isOffer = false;
$field = $_REQUEST['field'];
$OFFER_IBLOCK_ID = 0;
if(strpos($field, 'OFFER_')===0)
{
	$OFFER_IBLOCK_ID = CKDAImportUtils::GetOfferIblock($IBLOCK_ID);
	$field = substr($field, 6);
	$isOffer = true;
}

$addField = '';
if(strpos($field, '|')!==false)
{
	list($field, $addField) = explode('|', $field);
}

/*$obJSPopup = new CJSPopup();
$obJSPopup->ShowTitlebar(GetMessage("KDA_IE_SETTING_UPLOAD_FIELD").($arFields[$field] ? ' "'.$arFields[$field].'"' : ''));*/

$oProfile = new CKDAImportProfile();
$oProfile->ApplyExtra($PEXTRASETTINGS, $_REQUEST['PROFILE_ID']);
if(isset($_POST['POSTEXTRA']))
{
	$arFieldParams = $_POST['POSTEXTRA'];
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$arFieldParams = $APPLICATION->ConvertCharset($arFieldParams, 'UTF-8', 'CP1251');
	}
	$arFieldParams = CUtil::JsObjectToPhp($arFieldParams);
	if(!$arFieldParams) $arFieldParams = array();
	$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName);
	$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
	eval('$arFieldsParamsInArray = &$P'.$fNameEval.';');
	$arFieldsParamsInArray = $arFieldParams;
}

if($_POST['action']=='save_margin_template')
{
	$arPost = $_POST;
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$arPost = $APPLICATION->ConvertCharsetArray($arPost, 'UTF-8', 'CP1251');
	}
	$arMarginTemplates = CKDAImportExtrasettings::SaveMarginTemplate($arPost);
}
elseif($_POST['action']=='delete_margin_template')
{
	$arMarginTemplates = CKDAImportExtrasettings::DeleteMarginTemplate($_POST['template_id']);
}
elseif($_POST['action']=='save' && is_array($_POST['EXTRASETTINGS']))
{
	$APPLICATION->RestartBuffer();
	ob_end_clean();

	CKDAImportExtrasettings::HandleParams($PEXTRASETTINGS, $_POST['EXTRASETTINGS']);
	preg_match_all('/\[([_\d]+)\]/', $fieldName, $keys);
	$oid = 'field_settings_'.$keys[1][0].'_'.$keys[1][1];
	
	if($_GET['return_data'])
	{
		$returnJson = (empty($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]]) ? '""' : CUtil::PhpToJSObject($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]]));
		echo '<script>EList.SetExtraParams("'.$oid.'", '.$returnJson.')</script>';
	}
	else
	{
		$oProfile->UpdateExtra($_REQUEST['PROFILE_ID'], $PEXTRASETTINGS);
		if(!empty($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]])) echo '<script>$("#'.$oid.'").removeClass("inactive");</script>';
		else echo '<script>$("#'.$oid.'").addClass("inactive");</script>';
		echo '<script>BX.WindowManager.Get().Close();</script>';
	}
	die();
}

$oProfile = new CKDAImportProfile();
$arProfile = $oProfile->GetByID($_REQUEST['PROFILE_ID']);
$SETTINGS_DEFAULT = $arProfile['SETTINGS_DEFAULT'];

$bPrice = false;
if((strncmp($field, "ICAT_PRICE", 10) == 0 && substr($field, -6)=='_PRICE') || $field=="ICAT_PURCHASING_PRICE")
{
	$bPrice = true;
	if($bCurrency)
	{
		$arCurrency = array();
		$lcur = CCurrency::GetList(($by="sort"), ($order1="asc"), LANGUAGE_ID);
		while($arr = $lcur->Fetch())
		{
			$arCurrency[] = array(
				'CURRENCY' => $arr['CURRENCY'],
				'FULL_NAME' => $arr['FULL_NAME']
			);
		}
	}
}

$bPicture = false;
$bIblockElement = false;
$bIblockSection = false;
$bIblockElementSet = false;
$bCanUseForSKUGenerate = false;
$bTextHtml = false;
$bMultipleProp = $bMultipleField = false;
$bPropTypeList = false;
if(strncmp($field, "IP_PROP", 7) == 0 && is_numeric(substr($field, 7)))
{
	$propId = intval(substr($field, 7));
	$dbRes = CIBlockProperty::GetList(array(), array('ID'=>$propId));
	if($arProp = $dbRes->Fetch())
	{
		if($arProp['PROPERTY_TYPE']=='F')
		{
			$bPicture = true;
		}
		elseif($arProp['PROPERTY_TYPE']=='L')
		{
			$bPropTypeList = true;
		}
		elseif($arProp['PROPERTY_TYPE']=='E')
		{
			$bIblockElement = true;
			$iblockElementIblock = ($arProp['LINK_IBLOCK_ID'] ? $arProp['LINK_IBLOCK_ID'] : $IBLOCK_ID);
		}
		elseif($arProp['PROPERTY_TYPE']=='G')
		{
			$bIblockSection = true;
			$iblockSectionIblock = ($arProp['LINK_IBLOCK_ID'] ? $arProp['LINK_IBLOCK_ID'] : $IBLOCK_ID);
		}
		elseif($arProp['PROPERTY_TYPE']=='S' && $arProp['USER_TYPE']=='HTML')
		{
			$bTextHtml = true;
		}
		if($isOffer && in_array($arProp['PROPERTY_TYPE'], array('S', 'N', 'L', 'E', 'G')))
		{
			$bCanUseForSKUGenerate = true;
		}
		if($arProp['MULTIPLE']=='Y') $bMultipleProp = true;
	}
}

$bSectionUid = false;
if(preg_match('/^ISECT\d+_'.$SETTINGS_DEFAULT['SECTION_UID'].'$/', $field))
{
	$bSectionUid = true;
}

if(preg_match('/^ISECT\d*_(UF_.*)$/', $field, $m))
{
	$fieldCode = $m[1];
	$dbRes = CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'IBLOCK_'.$IBLOCK_ID.'_SECTION', 'FIELD_NAME'=>$fieldCode));
	if($arUserField = $dbRes->Fetch())
	{
		if($arUserField['MULTIPLE']=='Y') $bMultipleField = true;
		if($arUserField['USER_TYPE_ID']=='iblock_element')
		{
			$bIblockElement = true;
		}
	}
}

if(preg_match('/^ICAT_SET2?_/', $field))
{
	$bMultipleField = true;
	if($field=='ICAT_SET_ITEM_ID' || $field=='ICAT_SET2_ITEM_ID')
	{
		$bIblockElement = true;
		$bIblockElementSet = true;
		$iblockElementIblock = $IBLOCK_ID;
	}
}

$bUid = false;
if(!$isOffer && is_array($SETTINGS_DEFAULT['ELEMENT_UID']) && in_array($field, $SETTINGS_DEFAULT['ELEMENT_UID']))
{
	$bUid = true;
}

$bOfferUid = false;
if($isOffer && is_array($SETTINGS_DEFAULT['ELEMENT_UID_SKU']) && in_array('OFFER_'.$field, $SETTINGS_DEFAULT['ELEMENT_UID_SKU']))
{
	$bOfferUid = true;
}

$bChangeable = false;
$bExtLink = false;
if(in_array($field, array('IE_PREVIEW_TEXT', 'IE_DETAIL_TEXT')))
{
	$bChangeable = true;
	$bExtLink = true;
}

$bDirectory = false;
if($arProp['USER_TYPE']=='directory' && $arProp['USER_TYPE_SETTINGS']['TABLE_NAME'] && CModule::IncludeModule('highloadblock'))
{
	$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('TABLE_NAME'=>$arProp['USER_TYPE_SETTINGS']['TABLE_NAME'])))->fetch();
	$dbRes = CUserTypeEntity::GetList(array('SORT'=>'ASC', 'ID'=>'ASC'), array('ENTITY_ID'=>'HLBLOCK_'.$hlblock['ID'], 'LANG'=>LANGUAGE_ID));
	$arHLFields = array();
	while($arHLField = $dbRes->Fetch())
	{
		$arHLFields[$arHLField['FIELD_NAME']] = ($arHLField['EDIT_FORM_LABEL'] ? $arHLField['EDIT_FORM_LABEL'] : $arHLField['FIELD_NAME']);
	}
	$bDirectory = true;
}

$bPropList = false;
if($field=='IP_LIST_PROPS')
{
	$bPropList = true;
}

$bProductGift = false;
if($field=='ICAT_DISCOUNT_BRGIFT')
{
	$bProductGift = true;
	$iblockElementIblock = $IBLOCK_ID;
}

if($bIblockElementSet)
{
	$arIblocks = $fl->GetIblocks();
}

$useSaleDiscount = (bool)(CModule::IncludeModule('sale') && (string)COption::GetOptionString('sale', 'use_sale_discount_only') == 'Y');
$bDiscountValue = (bool)(strpos($field, 'ICAT_DISCOUNT_VALUE')===0 && !$useSaleDiscount);
$bSaleDiscountValue = (bool)(strpos($field, 'ICAT_DISCOUNT_VALUE')===0 && $useSaleDiscount);
$countCols = intval($_REQUEST['count_cols']);	

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<table width="100%">
		<col width="50%">
		<col width="50%">
		<?if($bPropList){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_PROPS_SEP");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_PROPS_SEP]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" size="3">
				</td>
			</tr>
			
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_PROPVALS_SEP");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_PROPVALS_SEP]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" size="3">
				</td>
			</tr>
			
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_NEWPROP_SAVE_OLD_VALUES");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_NEWPROP_SAVE_OLD_VALUES]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y"<?if($val=='Y'){echo ' checked';}?>>
				</td>
			</tr>
			
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_CREATE_NEW");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_CREATE_NEW]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					$createNewProps = (bool)($val=='Y');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?> onchange="ESettings.ToggleSubparams(this)">
				</td>
			</tr>
			
			<tr class="subparams" <?if(!$createNewProps){echo 'style="display: none;"';}?>>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_NEWPROP_PREFIX");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_NEWPROP_PREFIX]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>">
				</td>
			</tr>
			
			<tr class="subparams" <?if(!$createNewProps){echo 'style="display: none;"';}?>>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_NEWPROP_SORT");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_NEWPROP_SORT]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>">
				</td>
			</tr>
			
			<tr class="subparams" <?if(!$createNewProps){echo 'style="display: none;"';}?>>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_NEWPROP_TYPE");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_NEWPROP_TYPE]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<select name="<?=$fName?>">
						<option value="S"<?if($val=='S'){echo ' selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_NEWPROP_TYPE_STRING");?></option>
						<option value="N"<?if($val=='N'){echo ' selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_NEWPROP_TYPE_NUMBER");?></option>
						<option value="L"<?if($val=='L'){echo ' selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_NEWPROP_TYPE_LIST");?></option>
					</select>
				</td>
			</tr>
		<?}?>
	
		<?if($bIblockElement || $bProductGift){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_REL_ELEMENT_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[REL_ELEMENT_FIELD]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$val = '';
					if(is_array($PEXTRASETTINGS))
					{
						eval('$val = $P'.$fNameEval.';');
					}
					
					$strOptions = $fl->GetSelectUidFields($iblockElementIblock, $val, '');
					if(preg_match('/<option[^>]+value="IE_ID".*<\/option>/Uis', $strOptions, $m))
					{
						$strOptions = $m[0].str_replace($m[0], '', $strOptions);
					}
					?>
					<select name="<?echo $fName;?>" class="chosen" style="max-width: 450px;"><?echo $strOptions;?></select>
				</td>
			</tr>
		<?}?>
		
		<?if($bIblockSection){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_REL_SECTION_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[REL_SECTION_FIELD]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$val = '';
					if(is_array($PEXTRASETTINGS))
					{
						eval('$val = $P'.$fNameEval.';');
					}
					?>
					<select name="<?echo $fName;?>" class="chosen">
						<option value="ID"<?if($val=='ID') echo ' selected';?>><?echo GetMessage("KDA_IE_SETTINGS_SECTION_ID"); ?></option>
						<option value="NAME"<?if($val=='NAME') echo ' selected';?>><?echo GetMessage("KDA_IE_SETTINGS_SECTION_NAME"); ?></option>
						<option value="CODE"<?if($val=='CODE') echo ' selected';?>><?echo GetMessage("KDA_IE_SETTINGS_SECTION_CODE"); ?></option>
						<option value="XML_ID"<?if($val=='XML_ID') echo ' selected';?>><?echo GetMessage("KDA_IE_SETTINGS_SECTION_XML_ID"); ?></option>
					</select>
				</td>
			</tr>
		<?}?>
		
		<?if($bPropTypeList){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PROPLIST_FIELD]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$val = '';
					if(is_array($PEXTRASETTINGS))
					{
						eval('$val = $P'.$fNameEval.';');
					}
					?>
					<select name="<?echo $fName;?>">
						<option value="VALUE" <?if($val=='VALUE'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_FIELD_VALUE");?></option>
						<option value="XML_ID" <?if($val=='XML_ID'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_FIELD_XML_ID");?></option>
						<option value="SORT" <?if($val=='SORT'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_PROPLIST_FIELD_SORT");?></option>
					</select>
				</td>
			</tr>
		<?}?>
		
		<?if($bDirectory && !empty($arHLFields)){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_HLBL_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[HLBL_FIELD]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$val = '';
					if(is_array($PEXTRASETTINGS))
					{
						eval('$val = $P'.$fNameEval.';');
					}
					?>
					<select name="<?echo $fName;?>" class="chosen">
						<?
						foreach($arHLFields as $k=>$name)
						{
							echo '<option value="'.$k.'"'.(($val==$k || (!$val && $k=='UF_NAME')) ? ' selected' : '').'>'.$name.'</option>';
						}
						?>
					</select>
				</td>
			</tr>
		<?}?>
		
		<?if($bUid){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_ELEMENT_SEARCH_MODE");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[UID_SEARCH_SUBSTRING]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<select name="<?echo $fName;?>">
						<option value=""><?echo GetMessage("KDA_IE_SETTINGS_ELEMENT_SEARCH_FULL");?></option>
						<option value="Y" <?if($val=='Y'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_ELEMENT_SEARCH_SUBSTRING");?></option>
						<option value="B" <?if($val=='B'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_ELEMENT_SEARCH_BEGIN");?></option>
						<option value="E" <?if($val=='E'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_ELEMENT_SEARCH_END");?></option>
					</select>
				</td>
			</tr>
		<?}?>
		
		<?if($bSectionUid){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_SECTION_NAME_SEPARATED");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SECTION_UID_SEPARATED]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_SECTION_SEARCH_IN_SUBSECTIONS");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SECTION_SEARCH_IN_SUBSECTIONS]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_SECTION_SEARCH_WITHOUT_PARENT");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SECTION_SEARCH_WITHOUT_PARENT]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
		<?}?>
		
		<?if(in_array($field, array("IE_SECTION_PATH", "SECTION_SEP_NAME_PATH"))){?>
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_IE_SETTINGS_SECTION_PATH_SEPARATOR");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SECTION_PATH_SEPARATOR]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" placeholder="<?echo GetMessage("KDA_IE_SETTINGS_SECTION_PATH_SEPARATOR_PLACEHOLDER");?>">
				</td>
			</tr>
		<?}?>
		
		<?if($field=="IE_SECTION_PATH"){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_SECTION_PATH_SEPARATED");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SECTION_PATH_SEPARATED]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_SECTION_PATH_NAME_SEPARATED");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SECTION_UID_SEPARATED]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
		<?}?>
		
		<?if($bCanUseForSKUGenerate){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_USE_FOR_SKU_GENERATE");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[USE_FOR_SKU_GENERATE]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
		<?}?>
		<?if($isOffer){?>
			<?/*if($bOfferUid){?>
				<tr>
					<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_SEARCH_SINGLE_OFFERS");?>:</td>
					<td class="adm-detail-content-cell-r">
						<?
						$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SEARCH_SINGLE_OFFERS]';
						$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
						eval('$val = $P'.$fNameEval.';');
						?>
						<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
					</td>
				</tr>
			<?}*/?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_COPY_CELL_ON_OFFERS");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[COPY_CELL_ON_OFFERS]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
		<?}?>
		
		<?if($bMultipleProp || $bMultipleField){?>
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_IE_SETTINGS_CHANGE_MULTIPLE_SEPARATOR");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[CHANGE_MULTIPLE_SEPARATOR]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					$fName2 = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[MULTIPLE_SEPARATOR]';
					$fNameEval2 = strtr($fName2, array("["=>"['", "]"=>"']"));
					eval('$val2 = $P'.$fNameEval2.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?> onchange="$('#multiple_separator').css('display', (this.checked ? '' : 'none'));"><br>
					<input type="text" id="multiple_separator" name="<?=$fName2?>" value="<?=htmlspecialcharsex($val2)?>" placeholder="<?echo GetMessage("KDA_IE_SETTINGS_MULTIPLE_SEPARATOR_PLACEHOLDER");?>" <?=($val!='Y' ? 'style="display: none"' : '')?>>
				</td>
			</tr>
		<?}?>
		<?if($bMultipleProp){?>
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_IE_SETTINGS_MULTIPLE_SAVE_OLD_VALUES");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[MULTIPLE_SAVE_OLD_VALUES]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_IE_SETTINGS_MULTIPLE_FROM_VALUE");?>:<br><small><?echo GetMessage("KDA_IE_SETTINGS_MULTIPLE_FROM_VALUE_COMMENT");?></small></td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName1 = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[MULTIPLE_FROM_VALUE]';
					$fNameEval1 = strtr($fName1, array("["=>"['", "]"=>"']"));
					eval('$val1 = $P'.$fNameEval1.';');
					
					$fName2 = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[MULTIPLE_TO_VALUE]';
					$fNameEval2 = strtr($fName2, array("["=>"['", "]"=>"']"));
					eval('$val2 = $P'.$fNameEval2.';');
					?>
					<input type="text" size="5" name="<?=$fName1?>" value="<?echo htmlspecialcharsex($val1);?>" placeholder="1">
					<?echo GetMessage("KDA_IE_SETTINGS_MULTIPLE_TO_VALUE");?>
					<input type="text" size="5" name="<?=$fName2?>" value="<?echo htmlspecialcharsex($val2);?>">
				</td>
			</tr>
		<?}?>
		
		<?if($bTextHtml){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_HTML_TITLE");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[TEXT_HTML]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$val = '';
					if(is_array($PEXTRASETTINGS))
					{
						eval('$val = $P'.$fNameEval.';');
					}
					?>
					<select name="<?echo $fName;?>">
						<option value=""><?echo GetMessage("KDA_IE_SETTINGS_HTML_NOT_VALUE");?></option>
						<option value="text" <?if($val=='text'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_HTML_TEXT");?></option>
						<option value="html" <?if($val=='html'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_HTML_HTML");?></option>
					</select>
				</td>
			</tr>
		<?}?>
		
		<?if($bDiscountValue){?>
			<?
			$dbPriceType = CCatalogGroup::GetList(array("SORT" => "ASC"));
			$arPriceTypes = array();
			while($arPriceType = $dbPriceType->Fetch())
			{
				$arPriceTypes[] = $arPriceType;
			}
			if(count($arPriceTypes) > 1){
			?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PRICE_TYPE");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[CATALOG_GROUP_IDS]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$val = array();
					if(is_array($PEXTRASETTINGS))
					{
						eval('$val = $P'.$fNameEval.';');
					}
					if(!is_array($val)) $val = array();
					?>
					<select name="<?echo $fName;?>[]" multiple>
						<?foreach($arPriceTypes as $arPriceType){?>
							<option value="<?echo $arPriceType["ID"]?>" <?if(in_array($arPriceType["ID"], $val)){echo 'selected';}?>><?echo ($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]);?></option>
						<?}?>
					</select>
				</td>
			</tr>
			<?}?>
		<?}elseif($bSaleDiscountValue){?>
			<?
			$dbSite = \CIBlock::GetSite($IBLOCK_ID);
			$arSites = array();
			while($arSite = $dbSite->Fetch())
			{
				$arSites[] = $arSite;
			}
			if(count($arSites) > 1){
			?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_SITE_ID");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SITE_IDS]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$val = array();
					if(is_array($PEXTRASETTINGS))
					{
						eval('$val = $P'.$fNameEval.';');
					}
					if(!is_array($val)) $val = array();
					?>
					<select name="<?echo $fName;?>[]" multiple size="3">
						<?foreach($arSites as $arSite){?>
							<option value="<?echo $arSite["SITE_ID"]?>" <?if(in_array($arSite["SITE_ID"], $val)){echo 'selected';}?>><?echo '['.$arSite["SITE_ID"].'] '.$arSite["NAME"];?></option>
						<?}?>
					</select>
				</td>
			</tr>
			<?}?>
		<?}?>
		
		<?if($bIblockElementSet){?>
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_IE_SETTINGS_CHANGE_LINKED_IBLOCK");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[CHANGE_LINKED_IBLOCK]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					$fName2 = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[LINKED_IBLOCK]';
					$fNameEval2 = strtr($fName2, array("["=>"['", "]"=>"']"));
					eval('$val2 = $P'.$fNameEval2.';');
					if(!is_array($val2)) $val2 = array();
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?> onchange="$('#linked_iblock').css('display', (this.checked ? '' : 'none'));"><br>
					<select type="text" id="linked_iblock" name="<?=$fName2?>[]" multiple <?=($val!='Y' ? 'style="display: none"' : '')?>>
						<?
						foreach($arIblocks as $type)
						{
							?><optgroup label="<?echo $type['NAME']?>"><?
							foreach($type['IBLOCKS'] as $iblock)
							{
								?><option value="<?echo $iblock["ID"];?>" <?if(in_array($iblock["ID"], $val2)){echo 'selected';}?>><?echo htmlspecialcharsbx($iblock["NAME"].' ['.$iblock["ID"].']'); ?></option><?
							}
							?></optgroup><?
						}
						?>
					</select>
				</td>
			</tr>
		<?}?>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_CONVERSION_TITLE");?></td>
		</tr>
		<tr>
			<td class="kda-ie-settings-margin-container" colspan="2">
				<?
				$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[CONVERSION]';
				$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
				$arVals = array();
				if(is_array($PEXTRASETTINGS))
				{
					eval('$arVals = $P'.$fNameEval.';');
				}
				$showCondition = true;
				if(!is_array($arVals) || count($arVals)==0)
				{
					$showCondition = false;
					$arVals = array(
						array(
							'CELL' => '',
							'WHEN' => '',
							'FROM' => '',
							'THEN' => '',
							'TO' => ''
						)
					);
				}
				
				$arColLetters = range('A', 'Z');
				foreach(range('A', 'Z') as $v1)
				{
					foreach(range('A', 'Z') as $v2)
					{
						$arColLetters[] = $v1.$v2;
					}
				}
				
				foreach($arVals as $k=>$v)
				{
					$cellsOptions = '<option value="">'.sprintf(GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_CURRENT"), $i).'</option>';
					for($i=1; $i<=$countCols; $i++)
					{
						$cellsOptions .= '<option value="'.$i.'"'.($v['CELL']==$i ? ' selected' : '').'>'.sprintf(GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_NUMBER"), $i, $arColLetters[$i-1]).'</option>';
					}
					$cellsOptions .= /*'<option value="GROUP_AND"'.($v['CELL']=='GROUP_AND' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_GROUP_AND").'</option>'.
						'<option value="GROUP_OR"'.($v['CELL']=='GROUP_OR' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_GROUP_OR").'</option>'.*/
						'<option value="ELSE"'.($v['CELL']=='ELSE' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_ELSE").'</option>';
					echo '<div class="kda-ie-settings-conversion" '.(!$showCondition ? 'style="display: none;"' : '').'>'.
							GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_TITLE").
							' <select name="'.$fName.'[CELL][]" class="field_cell">'.
								$cellsOptions.
							'</select> '.
							' <select name="'.$fName.'[WHEN][]" class="field_when">'.
								'<option value="EQ" '.($v['WHEN']=='EQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_EQ").'</option>'.
								'<option value="NEQ" '.($v['WHEN']=='NEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NEQ").'</option>'.
								'<option value="GT" '.($v['WHEN']=='GT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_GT").'</option>'.
								'<option value="LT" '.($v['WHEN']=='LT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_LT").'</option>'.
								'<option value="GEQ" '.($v['WHEN']=='GEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_GEQ").'</option>'.
								'<option value="LEQ" '.($v['WHEN']=='LEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_LEQ").'</option>'.
								'<option value="CONTAIN" '.($v['WHEN']=='CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_CONTAIN").'</option>'.
								'<option value="NOT_CONTAIN" '.($v['WHEN']=='NOT_CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_CONTAIN").'</option>'.
								'<option value="EMPTY" '.($v['WHEN']=='EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_EMPTY").'</option>'.
								'<option value="NOT_EMPTY" '.($v['WHEN']=='NOT_EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_EMPTY").'</option>'.
								'<option value="REGEXP" '.($v['WHEN']=='REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_REGEXP").'</option>'.
								'<option value="NOT_REGEXP" '.($v['WHEN']=='NOT_REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_REGEXP").'</option>'.
								'<option value="ANY" '.($v['WHEN']=='ANY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_ANY").'</option>'.
							'</select> '.
							'<input type="text" name="'.$fName.'[FROM][]" class="field_from" value="'.htmlspecialcharsex($v['FROM']).'"> '.
							GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_THEN").
							' <select name="'.$fName.'[THEN][]">'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_STRING").'">'.
									'<option value="REPLACE_TO" '.($v['THEN']=='REPLACE_TO' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REPLACE_TO").'</option>'.
									'<option value="REMOVE_SUBSTRING" '.($v['THEN']=='REMOVE_SUBSTRING' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REMOVE_SUBSTRING").'</option>'.
									'<option value="REPLACE_SUBSTRING_TO" '.($v['THEN']=='REPLACE_SUBSTRING_TO' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REPLACE_SUBSTRING_TO").'</option>'.
									'<option value="ADD_TO_BEGIN" '.($v['THEN']=='ADD_TO_BEGIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_ADD_TO_BEGIN").'</option>'.
									'<option value="ADD_TO_END" '.($v['THEN']=='ADD_TO_END' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_ADD_TO_END").'</option>'.
									'<option value="LCASE" '.($v['THEN']=='LCASE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_LCASE").'</option>'.
									'<option value="UCASE" '.($v['THEN']=='UCASE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UCASE").'</option>'.
									'<option value="UFIRST" '.($v['THEN']=='UFIRST' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UFIRST").'</option>'.
									'<option value="UWORD" '.($v['THEN']=='UWORD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UWORD").'</option>'.
									'<option value="TRANSLIT" '.($v['THEN']=='TRANSLIT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_TRANSLIT").'</option>'.
									'<option value="STRIP_TAGS" '.($v['THEN']=='STRIP_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_STRIP_TAGS").'</option>'.
									'<option value="CLEAR_TAGS" '.($v['THEN']=='CLEAR_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_CLEAR_TAGS").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_MATH").'">'.
									'<option value="MATH_ROUND" '.($v['THEN']=='MATH_ROUND' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_ROUND").'</option>'.
									'<option value="MATH_MULTIPLY" '.($v['THEN']=='MATH_MULTIPLY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_MULTIPLY").'</option>'.
									'<option value="MATH_DIVIDE" '.($v['THEN']=='MATH_DIVIDE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_DIVIDE").'</option>'.
									'<option value="MATH_ADD" '.($v['THEN']=='MATH_ADD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_ADD").'</option>'.
									'<option value="MATH_SUBTRACT" '.($v['THEN']=='MATH_SUBTRACT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_SUBTRACT").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_OTHER").'">'.
									'<option value="NOT_LOAD" '.($v['THEN']=='NOT_LOAD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_NOT_LOAD").'</option>'.
									'<option value="EXPRESSION" '.($v['THEN']=='EXPRESSION' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_EXPRESSION").'</option>'.
								'</optgroup>'.
							'</select> '.
							'<input type="text" name="'.$fName.'[TO][]" value="'.htmlspecialcharsex($v['TO']).'">'.
							'<input class="choose_val" value="..." type="button" onclick="ESettings.ShowChooseVal(this, '.$countCols.')">'.
							'<a href="javascript:void(0)" onclick="ESettings.ConversionUp(this)" title="'.GetMessage("KDA_IE_SETTINGS_UP").'" class="up"></a>'.
							'<a href="javascript:void(0)" onclick="ESettings.ConversionDown(this)" title="'.GetMessage("KDA_IE_SETTINGS_DOWN").'" class="down"></a>'.
							'<a href="javascript:void(0)" onclick="ESettings.RemoveConversion(this)" title="'.GetMessage("KDA_IE_SETTINGS_DELETE").'" class="delete"></a>'.
						 '</div>';
				}
				?>
				<a href="javascript:void(0)" onclick="return ESettings.AddConversion(this, event);"><?echo GetMessage("KDA_IE_SETTINGS_CONVERSION_ADD_VALUE");?></a>
			</td>
		</tr>
		
		
		<?if($bPrice){
			$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[MARGINS]';
			$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
			eval('$val = $P'.$fNameEval.';');
			$arMarginTemplates = CKDAImportExtrasettings::GetMarginTemplates(($pfile=''));
			$showMargin = true;
			if($_POST['action']=='load_margin_template' && is_array($arMarginTemplates[$_POST['template_id']]))
			{
				$val = $arMarginTemplates[$_POST['template_id']]['MARGINS'];
			}
			if(!is_array($val) || count($val)==0)
			{
				$showMargin = false;
				$val = array(array(
					'TYPE' => 1,
					'PERCENT' => '',
					'PRICE_FROM' => '',
					'PRICE_TO' => ''
				));
			}
			?>
			<tr class="heading">
				<td colspan="2">
					<div class="kda-ie-settings-header-links">
						<div class="kda-ie-settings-header-links-inner">
							<a href="javascript:void(0)" onclick="ESettings.ShowMarginTemplateBlockLoad(this)"><?echo GetMessage("KDA_IE_SETTINGS_LOAD_TEMPLATE"); ?></a> /
							<a href="javascript:void(0)" onclick="ESettings.ShowMarginTemplateBlock(this)"><?echo GetMessage("KDA_IE_SETTINGS_SAVE_TEMPLATE"); ?></a>
						</div>
						<div class="kda-ie-settings-margin-templates" id="margin_templates">
							<div class="kda-ie-settings-margin-templates-inner">
								<?echo GetMessage("KDA_IE_SETTINGS_MARGIN_CHOOSE_EXISTS_TEMPLATE"); ?><br>
								<select name="MARGIN_TEMPLATE_ID">
									<option value=""><?echo GetMessage("KDA_IE_SETTINGS_MARGIN_NOT_CHOOSE"); ?></option>
									<?
									foreach($arMarginTemplates as $key=>$template)
									{
										?><option value="<?=$key?>"><?=$template['TITLE']?></option><?
									}
									?>
								</select><br>
								<?echo GetMessage("KDA_IE_SETTINGS_MARGIN_NEW_TEMPLATE"); ?><br>
								<input type="text" name="MARGIN_TEMPLATE_NAME" value="" placeholder="<?echo GetMessage("KDA_IE_SETTINGS_MARGIN_TEMPLATE_NAME"); ?>"><br>
								<input type="submit" onclick="return ESettings.SaveMarginTemplate(this, '<?echo GetMessage("KDA_IE_SETTINGS_TEMPLATE_SAVED"); ?>');" name="save" value="<?echo GetMessage("KDA_IE_SETTINGS_SAVE_BTN"); ?>">
							</div>
						</div>
						<div class="kda-ie-settings-margin-templates" id="margin_templates_load">
							<div class="kda-ie-settings-margin-templates-inner">
								<?echo GetMessage("KDA_IE_SETTINGS_MARGIN_CHOOSE_TEMPLATE"); ?><br>
								<select name="MARGIN_TEMPLATE_ID">
									<option value=""><?echo GetMessage("KDA_IE_SETTINGS_MARGIN_NOT_CHOOSE"); ?></option>
									<?
									foreach($arMarginTemplates as $key=>$template)
									{
										?><option value="<?=$key?>"><?=$template['TITLE']?></option><?
									}
									?>
								</select><br>
								<a href="javascript:void(0)" onclick="ESettings.RemoveMarginTemplate(this, '<?echo GetMessage("KDA_IE_SETTINGS_TEMPLATE_DELETED"); ?>')" title="<?echo GetMessage("KDA_IE_SETTINGS_DELETE"); ?>" class="delete"></a>
								<input type="submit" onclick="return ESettings.LoadMarginTemplate(this);" name="save" value="<?echo GetMessage("KDA_IE_SETTINGS_LOAD_BTN"); ?>">
							</div>
						</div>
					</div>
					<?echo GetMessage("KDA_IE_SETTINGS_MARGIN_TITLE"); ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="kda-ie-settings-margin-container">
					<div id="settings_margins">
						<?
						foreach($val as $k=>$v)
						{
						?>
							<div class="kda-ie-settings-margin" style="display: <?=($showMargin ? 'block' : 'none')?>;">
								<?echo GetMessage("KDA_IE_SETTINGS_APPLY"); ?> <select name="<?=$fName?>[TYPE][]"><option value="1" <?=($v['TYPE']==1 ? 'selected' : '')?>><?echo GetMessage("KDA_IE_SETTINGS_APPLY_MARGIN"); ?></option><option value="-1" <?=($v['TYPE']==-1 ? 'selected' : '')?>><?echo GetMessage("KDA_IE_SETTINGS_APPLY_DISCOUNT"); ?></option></select>
								<input type="text" name="<?=$fName?>[PERCENT][]" value="<?=htmlspecialcharsex($v['PERCENT'])?>">
								<select name="<?=$fName?>[PERCENT_TYPE][]"><option value="P" <?=($v['PERCENT_TYPE']=='P' ? 'selected' : '')?>><?echo GetMessage("KDA_IE_SETTINGS_TYPE_PERCENT"); ?></option><option value="F" <?=($v['PERCENT_TYPE']=='F' ? 'selected' : '')?>><?echo GetMessage("KDA_IE_SETTINGS_TYPE_FIX"); ?></option></select>
								<?echo GetMessage("KDA_IE_SETTINGS_AT_PRICE"); ?> <?echo GetMessage("KDA_IE_SETTINGS_FROM"); ?> <input type="text" name="<?=$fName?>[PRICE_FROM][]" value="<?=htmlspecialcharsex($v['PRICE_FROM'])?>">
								<?echo GetMessage("KDA_IE_SETTINGS_TO"); ?> <input type="text" name="<?=$fName?>[PRICE_TO][]" value="<?=htmlspecialcharsex($v['PRICE_TO'])?>">
								<a href="javascript:void(0)" onclick="ESettings.RemoveMargin(this)" title="<?echo GetMessage("KDA_IE_SETTINGS_DELETE"); ?>" class="delete"></a>
							</div>
						<?
						}
						?>
						<input type="button" value="<?echo GetMessage("KDA_IE_SETTINGS_ADD_MARGIN_DISCOUNT"); ?>" onclick="ESettings.AddMargin(this)">
					</div>
				</td>
			</tr>
			
			<tr class="heading">
				<td colspan="2">
					<?echo GetMessage("KDA_IE_SETTINGS_PRICE_PROCESSING"); ?>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PRICE_ROUND");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PRICE_ROUND_RULE]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<select name="<?=$fName?>">
						<option value=""><?echo GetMessage("KDA_IE_SETTINGS_PRICE_ROUND_NOT");?></option>
						<option value="ROUND" <?if($val=='ROUND') echo 'selected';?>><?echo GetMessage("KDA_IE_SETTINGS_PRICE_ROUND_ROUND");?></option>
						<option value="CEIL" <?if($val=='CEIL') echo 'selected';?>><?echo GetMessage("KDA_IE_SETTINGS_PRICE_ROUND_CEIL");?></option>
						<option value="FLOOR" <?if($val=='FLOOR') echo 'selected';?>><?echo GetMessage("KDA_IE_SETTINGS_PRICE_ROUND_FLOOR");?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PRICE_ROUND_COEFFICIENT");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PRICE_ROUND_COEFFICIENT]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="text" name="<?=$fName?>" value="<?echo htmlspecialcharsex($val)?>">
					<span id="hint_PRICE_ROUND_COEFFICIENT"></span><script>BX.hint_replace(BX('hint_PRICE_ROUND_COEFFICIENT'), '<?echo GetMessage("KDA_IE_SETTINGS_PRICE_ROUND_COEFFICIENT_HINT"); ?>');</script>
				</td>
			</tr>
			
			<?if($field!="ICAT_PURCHASING_PRICE"){?>
				<tr>
					<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_PRICE_USE_EXT");?>:</td>
					<td class="adm-detail-content-cell-r">
						<?
						$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PRICE_USE_EXT]';
						$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
						eval('$val = $P'.$fNameEval.';');
						$priceExt = $val;
						?>
						<input type="checkbox" name="<?=$fName?>" value="Y" <?echo ($val=='Y' ? 'checked' : '')?> onchange="$('#price_ext').css('display', (this.checked ? '' : 'none'));">
					</td>
				</tr>
				<tr id="price_ext" <?if($priceExt!='Y'){echo 'style="display: none;"';}?>>
					<td class="adm-detail-content-cell-l"></td>
					<td class="adm-detail-content-cell-r">
						<?
						$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PRICE_QUANTITY_FROM]';
						$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
						eval('$val = $P'.$fNameEval.';');
						?>
						<?echo GetMessage("KDA_IE_SETTINGS_PRICE_QUANTITY_FROM");?>
						<input type="text" name="<?=$fName?>" value="<?echo htmlspecialcharsex($val)?>" size="5">
						<input class="choose_val" value="..." type="button" onclick="ESettings.ShowChooseVal(this, <?echo $countCols?>, true)">
						&nbsp; &nbsp;
						<?
						$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PRICE_QUANTITY_TO]';
						$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
						eval('$val = $P'.$fNameEval.';');
						?>
						<?echo GetMessage("KDA_IE_SETTINGS_PRICE_QUANTITY_TO");?>
						<input type="text" name="<?=$fName?>" value="<?echo htmlspecialcharsex($val)?>" size="5">
						<input class="choose_val" value="..." type="button" onclick="ESettings.ShowChooseVal(this, <?echo $countCols?>, true)">
					</td>
				</tr>
			<?}?>
		<?}
		
		
		
		
		if($bPicture)
		{
			$arFieldNames = array(
				'SCALE',
				'WIDTH',
				'HEIGHT',
				'IGNORE_ERRORS_DIV',
				'IGNORE_ERRORS',
				'METHOD_DIV',
				'METHOD',
				'COMPRESSION',
				'USE_WATERMARK_FILE',
				'WATERMARK_FILE',
				'WATERMARK_FILE_ALPHA',
				'WATERMARK_FILE_POSITION',
				'USE_WATERMARK_TEXT',
				'WATERMARK_TEXT',
				'WATERMARK_TEXT_FONT',
				'WATERMARK_TEXT_COLOR',
				'WATERMARK_TEXT_SIZE',
				'WATERMARK_TEXT_POSITION',
			);
			$arFields = array();
			foreach($arFieldNames as $k=>$field)
			{
				$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PICTURE_PROCESSING]['.$field.']';
				$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
				$arFields[$field] = array(
					'NAME' => 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[PICTURE_PROCESSING]['.$field.']',
					'VALUE' => eval('return $P'.$fNameEval.';')
				);
			}
			?>
			<tr class="heading">
				<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_PICTURE_PROCESSING"); ?></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"></td>
				<td class="adm-detail-content-cell-r">
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['SCALE']['NAME']?>"
							name="<?echo $arFields['SCALE']['NAME']?>"
							<?
							if($arFields['SCALE']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?echo $arFields['WIDTH']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['HEIGHT']['NAME']?>').style.display =
								/*BX('DIV_<?echo $arFields['IGNORE_ERRORS_DIV']['NAME']?>').style.display =*/
								BX('DIV_<?echo $arFields['METHOD_DIV']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['COMPRESSION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['SCALE']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_SCALE")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WIDTH']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WIDTH")?>:&nbsp;<input name="<?echo $arFields['WIDTH']['NAME']?>" type="text" value="<?echo htmlspecialcharsbx($arFields['WIDTH']['VALUE'])?>" size="7">
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['HEIGHT']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_HEIGHT")?>:&nbsp;<input name="<?echo $arFields['HEIGHT']['NAME']?>" type="text" value="<?echo htmlspecialcharsbx($arFields['HEIGHT']['VALUE'])?>" size="7">
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['IGNORE_ERRORS_DIV']['NAME']?>"
					style="padding-left:16px;display:<?
						//echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
						echo 'none';
					?>"
				>
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['IGNORE_ERRORS']['NAME']?>"
							name="<?echo $arFields['IGNORE_ERRORS']['NAME']?>"
							<?
							if($arFields['IGNORE_ERRORS']['VALUE']==="Y")
								echo "checked";
							?>
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['IGNORE_ERRORS']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_IGNORE_ERRORS")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['METHOD_DIV']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['METHOD']['NAME']?>"
							name="<?echo $arFields['METHOD']['NAME']?>"
							<?
								if($arFields['METHOD']['VALUE']==="Y")
									echo "checked";
							?>
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['METHOD']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_METHOD")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['COMPRESSION']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_COMPRESSION")?>:&nbsp;<input
						name="<?echo $arFields['COMPRESSION']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['COMPRESSION']['VALUE'])?>"
						style="width: 30px"
					>
				</div>
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
							name="<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
							<?
							if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_FILE_ALPHA']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_FILE_POSITION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_USE_WATERMARK_FILE")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?CAdminFileDialog::ShowScript(array(
						"event" => "BtnClick".strtr($fieldName, array('['=>'_', ']'=>'_')),
						"arResultDest" => array("ELEMENT_ID" => strtr($arFields['WATERMARK_FILE']['NAME'], array('['=>'_', ']'=>'_'))),
						"arPath" => array("PATH" => GetDirPath($arFields['WATERMARK_FILE']['VALUE'])),
						"select" => 'F',// F - file only, D - folder only
						"operation" => 'O',// O - open, S - save
						"showUploadTab" => true,
						"showAddToMenuTab" => false,
						"fileFilter" => 'jpg,jpeg,png,gif',
						"allowAllFiles" => false,
						"SaveConfig" => true,
					));?>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_FILE")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_FILE']['NAME']?>"
						id="<?echo strtr($arFields['WATERMARK_FILE']['NAME'], array('['=>'_', ']'=>'_'))?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_FILE']['VALUE'])?>"
						size="35"
					>&nbsp;<input type="button" value="..." onClick="BtnClick<?echo strtr($fieldName, array('['=>'_', ']'=>'_'))?>()">
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_FILE_ALPHA']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_FILE_ALPHA")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_FILE_ALPHA']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_FILE_ALPHA']['VALUE'])?>"
						size="3"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_FILE_POSITION']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_POSITION")?>:&nbsp;<?echo SelectBox(
						$arFields['WATERMARK_FILE_POSITION']['NAME'],
						IBlockGetWatermarkPositions(),
						"",
						$arFields['WATERMARK_FILE_POSITION']['VALUE']
					);?>
				</div>
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
							name="<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
							<?
							if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_FONT']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_SIZE']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_POSITION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_USE_WATERMARK_TEXT")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_TEXT")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT']['VALUE'])?>"
						size="35"
					>
					<?CAdminFileDialog::ShowScript(array(
						"event" => "BtnClickFont".strtr($fieldName, array('['=>'_', ']'=>'_')),
						"arResultDest" => array("ELEMENT_ID" => strtr($arFields['WATERMARK_TEXT_FONT']['NAME'], array('['=>'_', ']'=>'_'))),
						"arPath" => array("PATH" => GetDirPath($arFields['WATERMARK_TEXT_FONT']['VALUE'])),
						"select" => 'F',// F - file only, D - folder only
						"operation" => 'O',// O - open, S - save
						"showUploadTab" => true,
						"showAddToMenuTab" => false,
						"fileFilter" => 'ttf',
						"allowAllFiles" => false,
						"SaveConfig" => true,
					));?>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_FONT']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_TEXT_FONT")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT_FONT']['NAME']?>"
						id="<?echo strtr($arFields['WATERMARK_TEXT_FONT']['NAME'], array('['=>'_', ']'=>'_'))?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT_FONT']['VALUE'])?>"
						size="35">&nbsp;<input
						type="button"
						value="..."
						onClick="BtnClickFont<?echo strtr($fieldName, array('['=>'_', ']'=>'_'))?>()"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_TEXT_COLOR")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
						id="<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT_COLOR']['VALUE'])?>"
						size="7"
					><script>
						function EXTRA_WATERMARK_TEXT_COLOR(color)
						{
							BX('<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>').value = color.substring(1);
						}
					</script>&nbsp;<input
						type="button"
						value="..."
						onclick="BX.findChildren(this.parentNode, {'tag': 'IMG'}, true)[0].onclick();"
					><span style="float:left;width:1px;height:1px;visibility:hidden;position:absolute;"><?
						$APPLICATION->IncludeComponent(
							"bitrix:main.colorpicker",
							"",
							array(
								"SHOW_BUTTON" =>"Y",
								"ONSELECT" => "EXTRA_WATERMARK_TEXT_COLOR",
							)
						);
					?></span>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_SIZE']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_SIZE")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT_SIZE']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT_SIZE']['VALUE'])?>"
						size="3"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_POSITION']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['WATERMARK_TEXT_POSITION']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_POSITION")?>:&nbsp;<?echo SelectBox(
						$arFields['WATERMARK_TEXT_POSITION']['NAME'],
						IBlockGetWatermarkPositions(),
						"",
						$arFields['WATERMARK_TEXT_POSITION']['VALUE']
					);?>
				</div>
				</td>
			</tr>
		<?}?>
		
		
		
		
		
		<?/*if($bPrice && !empty($arCurrency)){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_FIELD_CURRENCY");?>:</td>
				<td class="adm-detail-content-cell-r">			
					<select name="CURRENT_CURRENCY">
					<?
					$lcur = CCurrency::GetList(($by="sort"), ($order1="asc"), LANGUAGE_ID);
					foreach($arCurrency as $item)
					{
						?><option value="<?echo $item['CURRENCY']?>">[<?echo $item['CURRENCY']?>] <?echo $item['FULL_NAME']?></option><?
					}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_CONVERT_CURRENCY");?>:</td>
				<td class="adm-detail-content-cell-r">			
					<select name="CONVERT_CURRENCY">
						<option value=""><?echo GetMessage("KDA_IE_CONVERT_NO_CHOOSE");?></option>
					<?
					$lcur = CCurrency::GetList(($by="sort"), ($order1="asc"), LANGUAGE_ID);
					foreach($arCurrency as $item)
					{
						?><option value="<?echo $item['CURRENCY']?>">[<?echo $item['CURRENCY']?>] <?echo $item['FULL_NAME']?></option><?
					}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_PRICE_MARGIN");?>:</td>
				<td class="adm-detail-content-cell-r">			
					<input type="text" name="PRICE_MARGIN" value="0" size="5"> %
				</td>
			</tr>
		<?}*/?>
		
		<?if(1 /*$field!='SECTION_SEP_NAME'*/){?>
			<tr class="heading">
				<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_FILTER"); ?></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_FILTER_UPLOAD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[UPLOAD_VALUES]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$arVals = array();
					if(is_array($PEXTRASETTINGS))
					{
						eval('$arVals = $P'.$fNameEval.';');
					}
					$fName .= '[]';
					if(!is_array($arVals) || count($arVals) == 0)
					{
						$arVals = array('');
					}
					foreach($arVals as $k=>$v)
					{
						$hide = (bool)in_array($v, array('{empty}', '{not_empty}'));
						$select = '<select name="filter_vals" onchange="ESettings.OnValChange(this)">'.
								'<option value="">'.GetMessage("KDA_IE_SETTINGS_FILTER_VAL").'</option>'.
								'<option value="{empty}" '.($v=='{empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_EMPTY").'</option>'.
								'<option value="{not_empty}" '.($v=='{not_empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_NOT_EMPTY").'</option>'.
							'</select>';
						echo '<div>'.$select.' <input type="text" name="'.$fName.'" value="'.htmlspecialcharsex($v).'" '.($hide ? 'style="display: none;"' : '').'></div>';
					}
					?>
					<a href="javascript:void(0)" onclick="ESettings.AddValue(this)"><?echo GetMessage("KDA_IE_ADD_VALUE");?></a>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_FILTER_NOT_UPLOAD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[NOT_UPLOAD_VALUES]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					$arVals = array();
					if(is_array($PEXTRASETTINGS))
					{
						eval('$arVals = $P'.$fNameEval.';');
					}
					$fName .= '[]';
					if(!is_array($arVals) || count($arVals) == 0)
					{
						$arVals = array('');
					}
					foreach($arVals as $k=>$v)
					{
						$hide = (bool)in_array($v, array('{empty}', '{not_empty}'));
						$select = '<select name="filter_vals" onchange="ESettings.OnValChange(this)">'.
								'<option value="">'.GetMessage("KDA_IE_SETTINGS_FILTER_VAL").'</option>'.
								'<option value="{empty}" '.($v=='{empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_EMPTY").'</option>'.
								'<option value="{not_empty}" '.($v=='{not_empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_NOT_EMPTY").'</option>'.
							'</select>';
						echo '<div>'.$select.' <input type="text" name="'.$fName.'" value="'.htmlspecialcharsex($v).'" '.($hide ? 'style="display: none;"' : '').'></div>';
					}
					?>
					<a href="javascript:void(0)" onclick="ESettings.AddValue(this)"><?echo GetMessage("KDA_IE_ADD_VALUE");?></a>
				</td>
			</tr>
			<?if($field!='SECTION_SEP_NAME_PATH' && $field!='SECTION_SEP_NAME'){?>
				<tr>
					<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_USE_FILTER_FOR_DEACTIVATE");?>:</td>
					<td class="adm-detail-content-cell-r">
						<?
						$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[USE_FILTER_FOR_DEACTIVATE]';
						$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
						eval('$val = $P'.$fNameEval.';');
						?>
						<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
					</td>
				</tr>
				<tr>
					<td class="kda-ie-settings-margin-container" colspan="2">
						<a href="javascript:void(0)" onclick="ESettings.ShowPHPExpression(this)"><?echo GetMessage("KDA_IE_SETTINGS_FILTER_EXPRESSION");?></a>
						<?
						$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[FILTER_EXPRESSION]';
						$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
						eval('$val = $P'.$fNameEval.';');
						?>
						<div class="kda-ie-settings-phpexpression" style="display: none;">
							<?echo GetMessage("KDA_IE_SETTINGS_FILTER_EXPRESSION_HINT");?>
							<textarea name="<?echo $fName?>"><?echo $val?></textarea>
						</div>
					</td>
				</tr>
			<?}?>
		<?}?>	

		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_ADDITIONAL"); ?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_ONLY_FOR_NEW");?>:</td>
			<td class="adm-detail-content-cell-r" style="min-width: 30%;">
				<?
				$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[SET_NEW_ONLY]';
				$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
				eval('$val = $P'.$fNameEval.';');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_NOT_TRIM");?>:</td>
			<td class="adm-detail-content-cell-r" style="min-width: 30%;">
				<?
				$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[NOT_TRIM]';
				$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
				eval('$val = $P'.$fNameEval.';');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>		
		
		<?if($bExtLink){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_LOAD_BY_EXTLINK");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[LOAD_BY_EXTLINK]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
		<?}?>
		
		<?if($bChangeable){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_LOADING_MODE");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[LOADING_MODE]';
					$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
					eval('$val = $P'.$fNameEval.';');
					?>
					<select name="<?=$fName?>">
						<option value=""><?echo GetMessage("KDA_IE_SETTINGS_LOADING_MODE_CHANGE");?></option>
						<option value="ADD_BEFORE"<?if($val=='ADD_BEFORE'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_LOADING_MODE_BEFORE");?></option>
						<option value="ADD_AFTER"<?if($val=='ADD_AFTER'){echo 'selected';}?>><?echo GetMessage("KDA_IE_SETTINGS_LOADING_MODE_AFTER");?></option>
					</select>
				</td>
			</tr>
		<?}?>
		
		
		<?if(!in_array($field, array('SECTION_SEP_NAME', 'SECTION_SEP_NAME_PATH'))){?>
		<?
		$arSFields = $fl->GetSettingsFields($isOffer ? $OFFER_IBLOCK_ID : $IBLOCK_ID);
		?>
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_EXTRA_CONVERSION_TITLE");?></td>
		</tr>
		<tr>
			<td class="kda-ie-settings-margin-container" colspan="2">
				<?
				$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName).'[EXTRA_CONVERSION]';
				$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
				$arVals = array();
				if(is_array($PEXTRASETTINGS))
				{
					eval('$arVals = $P'.$fNameEval.';');
				}
				$showCondition = true;
				if(!is_array($arVals) || count($arVals)==0)
				{
					$showCondition = false;
					$arVals = array(
						array(
							'CELL' => '',
							'WHEN' => '',
							'FROM' => '',
							'THEN' => '',
							'TO' => ''
						)
					);
				}
				
				$countCols = intval($_REQUEST['count_cols']);				
				foreach($arVals as $k=>$v)
				{
					$cellsOptions = '<option value="">'.sprintf(GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_CURRENT"), $i).'</option>';
					foreach($arSFields as $k=>$arGroup)
					{
						if(is_array($arGroup['FIELDS']))
						{
							$cellsOptions .= '<optgroup label="'.$arGroup['TITLE'].'">';
							foreach($arGroup['FIELDS'] as $gkey=>$gfield)
							{
								$cellsOptions .= '<option value="'.$gkey.'"'.($v['CELL']==$gkey ? ' selected' : '').'>'.$gfield.'</option>';
							}
							$cellsOptions .= '</optgroup>';
						}
					}
					$cellsOptions .= '<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_GROUP_FILEVALS").'">';
					for($i=1; $i<=$countCols; $i++)
					{
						$cellsOptions .= '<option value="CELL'.$i.'"'.($v['CELL']=='CELL'.$i ? ' selected' : '').'>'.sprintf(GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_NUMBER"), $i, $arColLetters[$i-1]).'</option>';
					}
					$cellsOptions .= '</optgroup>';
					$cellsOptions .= '<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_GROUP_OTHER").'">';
					$cellsOptions .= '<option value="LOADED"'.($v['CELL']=='LOADED' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_LOADED").'</option>';
					$cellsOptions .= '<option value="ELSE"'.($v['CELL']=='ELSE' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_ELSE").'</option>';
					$cellsOptions .= '</optgroup>';
					
					echo '<div class="kda-ie-settings-conversion" '.(!$showCondition ? 'style="display: none;"' : '').'>'.
							GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_TITLE").
							' <select name="'.$fName.'[CELL][]" class="field_cell">'.
								$cellsOptions.
							'</select> '.
							' <select name="'.$fName.'[WHEN][]" class="field_when">'.
								'<option value="EQ" '.($v['WHEN']=='EQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_EQ").'</option>'.
								'<option value="NEQ" '.($v['WHEN']=='NEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NEQ").'</option>'.
								'<option value="GT" '.($v['WHEN']=='GT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_GT").'</option>'.
								'<option value="LT" '.($v['WHEN']=='LT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_LT").'</option>'.
								'<option value="GEQ" '.($v['WHEN']=='GEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_GEQ").'</option>'.
								'<option value="LEQ" '.($v['WHEN']=='LEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_LEQ").'</option>'.
								'<option value="CONTAIN" '.($v['WHEN']=='CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_CONTAIN").'</option>'.
								'<option value="NOT_CONTAIN" '.($v['WHEN']=='NOT_CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_CONTAIN").'</option>'.
								'<option value="EMPTY" '.($v['WHEN']=='EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_EMPTY").'</option>'.
								'<option value="NOT_EMPTY" '.($v['WHEN']=='NOT_EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_EMPTY").'</option>'.
								'<option value="REGEXP" '.($v['WHEN']=='REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_REGEXP").'</option>'.
								'<option value="NOT_REGEXP" '.($v['WHEN']=='NOT_REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_REGEXP").
								'<option value="ANY" '.($v['WHEN']=='ANY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_ANY").'</option>'.
							'</select> '.
							'<input type="text" name="'.$fName.'[FROM][]" class="field_from" value="'.htmlspecialcharsex($v['FROM']).'"> '.
							GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_THEN").
							' <select name="'.$fName.'[THEN][]">'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_STRING").'">'.
									'<option value="REPLACE_TO" '.($v['THEN']=='REPLACE_TO' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REPLACE_TO").'</option>'.
									'<option value="REMOVE_SUBSTRING" '.($v['THEN']=='REMOVE_SUBSTRING' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REMOVE_SUBSTRING").'</option>'.
									'<option value="REPLACE_SUBSTRING_TO" '.($v['THEN']=='REPLACE_SUBSTRING_TO' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REPLACE_SUBSTRING_TO").'</option>'.
									'<option value="ADD_TO_BEGIN" '.($v['THEN']=='ADD_TO_BEGIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_ADD_TO_BEGIN").'</option>'.
									'<option value="ADD_TO_END" '.($v['THEN']=='ADD_TO_END' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_ADD_TO_END").'</option>'.
									'<option value="LCASE" '.($v['THEN']=='LCASE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_LCASE").'</option>'.
									'<option value="UCASE" '.($v['THEN']=='UCASE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UCASE").'</option>'.
									'<option value="UFIRST" '.($v['THEN']=='UFIRST' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UFIRST").'</option>'.
									'<option value="UWORD" '.($v['THEN']=='UWORD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UWORD").'</option>'.
									'<option value="TRANSLIT" '.($v['THEN']=='TRANSLIT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_TRANSLIT").'</option>'.
									'<option value="STRIP_TAGS" '.($v['THEN']=='STRIP_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_STRIP_TAGS").'</option>'.
									'<option value="CLEAR_TAGS" '.($v['THEN']=='CLEAR_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_CLEAR_TAGS").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_MATH").'">'.
									'<option value="MATH_ROUND" '.($v['THEN']=='MATH_ROUND' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_ROUND").'</option>'.
									'<option value="MATH_MULTIPLY" '.($v['THEN']=='MATH_MULTIPLY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_MULTIPLY").'</option>'.
									'<option value="MATH_DIVIDE" '.($v['THEN']=='MATH_DIVIDE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_DIVIDE").'</option>'.
									'<option value="MATH_ADD" '.($v['THEN']=='MATH_ADD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_ADD").'</option>'.
									'<option value="MATH_SUBTRACT" '.($v['THEN']=='MATH_SUBTRACT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_SUBTRACT").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_OTHER").'">'.
									'<option value="NOT_LOAD" '.($v['THEN']=='NOT_LOAD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_NOT_LOAD_FIELD").'</option>'.
									'<option value="NOT_LOAD_ELEMENT" '.($v['THEN']=='NOT_LOAD_ELEMENT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_NOT_LOAD_ELEMENT").'</option>'.
									'<option value="EXPRESSION" '.($v['THEN']=='EXPRESSION' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_EXPRESSION").'</option>'.
								'</optgroup>'.
							'</select> '.
							'<input type="text" name="'.$fName.'[TO][]" value="'.htmlspecialcharsex($v['TO']).'">'.
							'<input class="choose_val" value="..." type="button" onclick="ESettings.ShowExtraChooseVal(this, '.$countCols.')">'.
							'<a href="javascript:void(0)" onclick="ESettings.ConversionUp(this)" title="'.GetMessage("KDA_IE_SETTINGS_UP").'" class="up"></a>'.
							'<a href="javascript:void(0)" onclick="ESettings.ConversionDown(this)" title="'.GetMessage("KDA_IE_SETTINGS_DOWN").'" class="down"></a>'.
							'<a href="javascript:void(0)" onclick="ESettings.RemoveConversion(this)" title="'.GetMessage("KDA_IE_SETTINGS_DELETE").'" class="delete"></a>'.
						 '</div>';
				}
				?>
				<a href="javascript:void(0)" onclick="return ESettings.AddConversion(this, event);"><?echo GetMessage("KDA_IE_SETTINGS_CONVERSION_ADD_VALUE");?></a>
			</td>
		</tr>
		<?}?>
	</table>
</form>
<?
if(!is_array($arSFields)) $arSFields = array();
?>
<script>
var admKDASettingMessages = {
	'CELL_VALUE': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_CELL_VALUE"));?>',
	'CELL_LINK': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_CELL_LINK"));?>',
	'CELL_COMMENT': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_CELL_COMMENT"));?>',
	'HASH_FILEDS': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_HASH_FILEDS"));?>',
	'IFILENAME': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_IFILENAME"));?>',
	'RATE_USD': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_RATE_USD"));?>',
	'RATE_EUR': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_RATE_EUR"));?>',
	'EXTRAFIELDS': <?echo CUtil::PhpToJSObject($arSFields)?>
};
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>