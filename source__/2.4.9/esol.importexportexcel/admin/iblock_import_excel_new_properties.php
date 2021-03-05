<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.importexportexcel';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$listIndex = $_GET['list_index'];
$IBLOCK_ID = $_GET['IBLOCK_ID'];
$OFFERS_IBLOCK_ID = CKDAImportUtils::GetOfferIblock($IBLOCK_ID);

$arFeatures = array();
if(is_callable(array('\Bitrix\Iblock\Model\PropertyFeature', 'isEnabledFeatures')) && \Bitrix\Iblock\Model\PropertyFeature::isEnabledFeatures())
{
	$arFeatures = \Bitrix\Iblock\Model\PropertyFeature::getPropertyFeatureList(array());
}

function KdaGetPropFeatureFields(&$arPropFields, $arFeatures)
{
	$arFeaturesFields = array();
	foreach($arFeatures as $arFeature)
	{
		$featureKey = $arFeature['MODULE_ID'].':'.$arFeature['FEATURE_ID'];
		if(!array_key_exists($featureKey, $arPropFields)) continue;
		$arFeaturesFields[$featureKey] = array(
			'PROPERTY_ID' => $arr['ID'],	
			'MODULE_ID' => $arFeature['MODULE_ID'],	
			'FEATURE_ID' => $arFeature['FEATURE_ID'],	
			'IS_ENABLED' => $arPropFields[$featureKey]
		);
		unset($arPropFields[$featureKey]);
	}
	return $arFeaturesFields;
}

$arPost = $_POST;
if(!defined('BX_UTF') || !BX_UTF)
{
	$arPost = $APPLICATION->ConvertCharsetArray($arPost, 'UTF-8', 'CP1251');
}

if($arPost['action']=='save')
{	
	define('PUBLIC_AJAX_MODE', 'Y');
	$codePrefix = $arPost['CODE_PREFIX'];
	$arFields = $arPost['FIELD'];
	$arFields['ACTIVE'] = 'Y';
	$arFields['IBLOCK_ID'] = $IBLOCK_ID;
	$sFieldPrefix = 'IP_PROP';
	if($arPost['PROPS_FOR']==1)
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
	
	$arResult = array();
	if(is_array($arPost['NAMES']))
	{
		foreach($arPost['NAMES'] as $propIndex)
		{
			$propName = '';
			if(is_array($arPost['items']))
			{
				foreach($arPost['items'] as $arItem)
				{
					if($arItem['index']==$propIndex)
					{
						$propName = trim($arItem['text']);
					}
				}
			}
			if(strlen($propName)==0) continue;
			
			$propCode = false;
			if($arPost['CODES_FROM_EXPORT']=='Y')
			{
				$propName = trim(preg_replace('/\{[^\{]*\}\s*$/Uis', '', $propName));
				if(preg_match('/\[([^\[]*)\]\s*$/Uis', $propName, $m))
				{
					$propCode = trim($m[1]);
					$propName = trim(substr($propName, 0, -strlen($m[0])));
				}
			}
			if($propCode===false)
			{
				$arParams = array(
					'max_len' => 50,
					'change_case' => 'U',
					'replace_space' => '_',
					'replace_other' => '_',
					'delete_repeat_replace' => 'Y',
				);
				$propCode = $codePrefix.CUtil::translit($propName, LANGUAGE_ID, $arParams);
			}
			else $propCode = $codePrefix.$propCode;
			$propCode = preg_replace('/[^a-zA-Z0-9_]/', '', $propCode);
			$propCode = preg_replace('/^[0-9_]+/', '', $propCode);
			$propCode = substr($propCode, 0, 50);
			$arPropFields = array_merge($arFields, array('NAME'=>$propName, 'CODE'=>$propCode));
			
			$arPropFilter = array('IBLOCK_ID'=>$arFields['IBLOCK_ID']);
			if($arPost['CODES_FROM_EXPORT']=='Y' && strlen($propCode) > 0 && ($arPost['FORCE_UPDATE']!='Y' || !in_array('CODE', $arPost['FIELDS_FOR_UPDATE']))) $arPropFilter['CODE'] = $propCode;
			else $arPropFilter['NAME'] = $propName;

			if($arPost['FORCE_CREATE']!='Y'
				&& ($dbRes = CIBlockProperty::GetList(array(), $arPropFilter))
				&& ($arr = $dbRes->Fetch()))
			{
				if($arPost['FORCE_UPDATE']=='Y' && is_array($arPost['FIELDS_FOR_UPDATE']))
				{
					foreach($arPropFields as $k=>$v)
					{
						$key = $k;
						if($k=='USER_TYPE') $key = 'PROPERTY_TYPE';
						if($key=='IBLOCK_ID') continue;
						if(!in_array($key, $arPost['FIELDS_FOR_UPDATE']))
						{
							unset($arPropFields[$k]);
						}
					}
					if(class_exists('\Bitrix\Iblock\PropertyTable') && isset($arPropFields['CODE']) && strlen($arPropFields['CODE']) > 0)
					{
						$index = 0;
						while($arr2 = \Bitrix\Iblock\PropertyTable::getList(array('filter'=>array('=CODE'=>$arPropFields['CODE'], 'IBLOCK_ID'=>$arPropFields['IBLOCK_ID'], '!ID'=>$arr['ID']), 'select'=>array('ID')))->Fetch())
						{
							$index++;
							$arPropFields['CODE'] = substr($arPropFields['CODE'], 0, 50 - strlen($index)).$index;
						}
					}
					$arFeaturesFields = KdaGetPropFeatureFields($arPropFields, $arFeatures);
					$ibp = new CIBlockProperty;
					$ibp->Update($arr['ID'], $arPropFields);
					if(isset($arPropFields['SMART_FILTER']))
					{
						$dbRes2 = \Bitrix\Iblock\SectionPropertyTable::getList(array("select" => array("SECTION_ID", "PROPERTY_ID"), "filter" => array("=IBLOCK_ID" => $arFields['IBLOCK_ID'] ,"=PROPERTY_ID" => $arr['ID'])));
						while($arr2 = $dbRes2->Fetch())
						{
							$arSectionProp = array('SMART_FILTER'=>$arPropFields['SMART_FILTER']);
							if(isset($arPropFields['DISPLAY_EXPANDED'])) $arSectionProp['DISPLAY_EXPANDED'] = $arPropFields['DISPLAY_EXPANDED'];							
							CIBlockSectionPropertyLink::Set($arr2['SECTION_ID'], $arr2['PROPERTY_ID'], $arSectionProp);
						}
					}
					if(!empty($arFeaturesFields)) \Bitrix\Iblock\Model\PropertyFeature::setFeatures($arr['ID'], $arFeaturesFields);
				}
				$arResult[$propIndex] = array('ID'=>$sFieldPrefix.$arr['ID'], 'NAME'=>$propName);
			}
			else
			{
				if(class_exists('\Bitrix\Iblock\PropertyTable') && strlen($arPropFields['CODE']) > 0)
				{
					$index = 0;
					while($arr2 = \Bitrix\Iblock\PropertyTable::getList(array('filter'=>array('=CODE'=>$arPropFields['CODE'], 'IBLOCK_ID'=>$arPropFields['IBLOCK_ID']), 'select'=>array('ID')))->Fetch())
					{
						$index++;
						$arPropFields['CODE'] = substr($arPropFields['CODE'], 0, 50 - strlen($index)).$index;
					}
				}
				$arFeaturesFields = KdaGetPropFeatureFields($arPropFields, $arFeatures);
				$ibp = new CIBlockProperty;
				$PropID = $ibp->Add($arPropFields);
				if(!empty($arFeaturesFields)) \Bitrix\Iblock\Model\PropertyFeature::setFeatures($PropID, $arFeaturesFields);
				$arResult[$propIndex] = array('ID'=>$sFieldPrefix.$PropID, 'NAME'=>$propName);
			}
		}
	}
	
	if(1)
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
	
		echo '<script>EList.OnAfterAddNewProperties("'.$listIndex.'", "'.$IBLOCK_ID.'", '.CUtil::PhpToJSObject($arResult).');</script>';
		die();
	}
}

$arUserTypeList = CIBlockProperty::GetUserType();
\Bitrix\Main\Type\Collection::sortByColumn($arUserTypeList, array('DESCRIPTION' => SORT_STRING));
$boolUserPropExist = !empty($arUserTypeList);
$PROPERTY_TYPE = 'S';
if($arPost['FIELD']['PROPERTY_TYPE']) $PROPERTY_TYPE = $arPost['FIELD']['PROPERTY_TYPE'];

require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<?
	if(is_array($arPost['items']))
	{
		foreach($arPost['items'] as $k=>$arItem)
		{
			echo '<input type="hidden" name="items['.$k.'][index]" value="'.htmlspecialcharsex($arItem['index']).'">';
			echo '<input type="hidden" name="items['.$k.'][text]" value="'.htmlspecialcharsex($arItem['text']).'">';
		}
	}
	?>
	

	<table width="100%" class="kda-ie-newprops-tbl">
		<col width="50%">
		<col width="50%">
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_FORCE_CREATE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="hidden" name="FORCE_CREATE" value="N">
				<input type="checkbox" name="FORCE_CREATE" value="Y"<?echo ($arPost['FORCE_CREATE']=='Y' ? ' checked' : '')?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_FORCE_UPDATE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<div class="kda-ie-newprops-floatchb">
					<input type="hidden" name="FORCE_UPDATE" value="N">
					<input type="checkbox" name="FORCE_UPDATE" value="Y"<?echo ($arPost['FORCE_UPDATE']=='Y' ? ' checked' : '')?>>
				</div>
				<div class="kda-ie-newprops-mchosen">
					<select multiple name="FIELDS_FOR_UPDATE[]">
						<?/*?><option value=""><?echo GetMessage("KDA_IE_NP_FIELDS_FOR_UPDATE");?></option><?*/?>
						<option value="PROPERTY_TYPE"><?echo GetMessage("KDA_IE_NP_TYPE");?></option>
						<option value="SORT"><?echo GetMessage("KDA_IE_NP_SORT");?></option>
						<option value="CODE"><?echo GetMessage("KDA_IE_NP_CODE");?></option>
						<option value="SMART_FILTER"><?echo GetMessage("KDA_IE_NP_SMART_FILTER");?></option>
						<option value="DISPLAY_EXPANDED"><?echo GetMessage("KDA_IE_NP_DISPLAY_EXPANDED");?></option>
						<option value="SECTION_PROPERTY"><?echo GetMessage("KDA_IE_NP_SHOW_IN_FORM");?></option>
						<?
						foreach($arFeatures as $arFeature)
						{
							echo '<option value="'.htmlspecialcharsex($arFeature['MODULE_ID'].':'.$arFeature['FEATURE_ID']).'">'.$arFeature['FEATURE_NAME'].'</option>';
						}
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<?
		if($OFFERS_IBLOCK_ID)
		{
			?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_PROPS_FOR");?>:</td>
				<td class="adm-detail-content-cell-r">
					<select name="PROPS_FOR">
						<option value="0"><?echo GetMessage("KDA_IE_NP_PROPS_FOR_GOODS");?></option>
						<option value="1" <?echo ($arPost['PROPS_FOR']=='1' ? ' selected' : '')?>><?echo GetMessage("KDA_IE_NP_PROPS_FOR_OFFERS");?></option>
					</select>
				</td>
			</tr>
			<?
		}
		?>
		
		<tr>
			<td class="adm-detail-content-cell-l"><? echo GetMessage('KDA_IE_NP_ALAILABLE_COLUMNS'); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<select name="NAMES[]" multiple>
					<?
					if(is_array($arPost['items']))
					{
						foreach($arPost['items'] as $arItem)
						{
							echo '<option value="'.htmlspecialcharsex($arItem['index']).'">'.$arItem['text'].'</option>';
						}
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_CODES_FROM_EXPORT");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="hidden" name="CODES_FROM_EXPORT" value="N">
				<input type="checkbox" name="CODES_FROM_EXPORT" value="Y"<?echo ($arPost['CODES_FROM_EXPORT']=='Y' ? ' checked' : '')?>>
			</td>
		</tr>
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
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_SORT");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="FIELD[SORT]" value="<?echo ($arPost['FIELD']['SORT'] ? htmlspecialcharsex($arPost['FIELD']['SORT']) : '500')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_CODE_PREFIX");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="CODE_PREFIX" value="<?echo ($arPost['CODE_PREFIX'] ? htmlspecialcharsex($arPost['CODE_PREFIX']) : '')?>">
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_SMART_FILTER");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[SMART_FILTER]" value="Y"<?echo ($arPost['FIELD']['SMART_FILTER']=='Y' ? ' checked' : '')?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_DISPLAY_EXPANDED");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="FIELD[DISPLAY_EXPANDED]" value="Y"<?echo ($arPost['FIELD']['DISPLAY_EXPANDED']=='Y' ? ' checked' : '')?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_NP_SHOW_IN_FORM");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="hidden" name="FIELD[SECTION_PROPERTY]" value="N">
				<input type="checkbox" name="FIELD[SECTION_PROPERTY]" value="Y"<?echo ($arPost['FIELD']['SECTION_PROPERTY']!='N' ? ' checked' : '')?>>
			</td>
		</tr>
		
		<?
		foreach($arFeatures as $arFeature)
		{
		?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo $arFeature['FEATURE_NAME'];?>:</td>
				<td class="adm-detail-content-cell-r">
					<input type="hidden" name="FIELD[<?echo htmlspecialcharsex($arFeature['MODULE_ID'].':'.$arFeature['FEATURE_ID']);?>]" value="N">
					<input type="checkbox" name="FIELD[<?echo htmlspecialcharsex($arFeature['MODULE_ID'].':'.$arFeature['FEATURE_ID']);?>]" value="Y"<?echo ($arPost['FIELD'][$arFeature['MODULE_ID'].':'.$arFeature['FEATURE_ID']]=='Y' ? ' checked' : '')?>>
				</td>
			</tr>
		<?
		}
		?>
	</table>
</form>
<?require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>