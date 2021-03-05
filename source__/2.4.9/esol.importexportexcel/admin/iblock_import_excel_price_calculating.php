<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.importexportexcel';
CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');
CModule::IncludeModule($moduleId);
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$arGet = $_GET;
$IBLOCK_ID = (int)$arGet['IBLOCK_ID'];
$isOffers = (bool)(\CKDAImportUtils::GetOfferIblock($IBLOCK_ID) > 0);

if(!isset($MAP) || !is_array($MAP)) $MAP = array();
if(!isset($MAP[$IBLOCK_ID]) || !is_array($MAP[$IBLOCK_ID])) $MAP[$IBLOCK_ID] = array();
if(!isset($DEFAULTS) || !is_array($DEFAULTS)) $DEFAULTS = array();

if($_POST['action']=='save')
{
	\CUtil::JSPostUnescape();
	define('PUBLIC_AJAX_MODE', 'Y');
	
	\Bitrix\Main\Config\Option::set('iblock', 'KDA_IBLOCK'.$IBLOCK_ID.'_PRICEQNT_CONFORMITY', serialize(array('MAP'=>$MAP[$IBLOCK_ID], 'PARAMS'=>$DEFAULTS)));
	
	$APPLICATION->RestartBuffer();
	ob_end_clean();
	
	echo '<script>';
	echo 'BX.WindowManager.Get().Close();';
	echo '</script>';
	die();
}

if(empty($MAP[$IBLOCK_ID]))
{
	$arParams = unserialize(\Bitrix\Main\Config\Option::get('iblock', 'KDA_IBLOCK'.$IBLOCK_ID.'_PRICEQNT_CONFORMITY'));
	$MAP[$IBLOCK_ID] = $arParams['MAP'];
	if(!is_array($MAP[$IBLOCK_ID])) $MAP[$IBLOCK_ID] = array();
	if(empty($MAP[$IBLOCK_ID])) $MAP[$IBLOCK_ID][] = array('price'=>'', 'quantity'=>'');
	$DEFAULTS = $arParams['PARAMS'];
	if(!is_array($DEFAULTS)) $DEFAULTS = array();
}

$arFieldsPrice = array();
$dbRes = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", "IBLOCK_ID" => $IBLOCK_ID, "CHECK_PERMISSIONS" => "N"));
while($arr = $dbRes->Fetch())
{
	if((($arr["PROPERTY_TYPE"]=='S' || $arr["PROPERTY_TYPE"]=='N') && !$arr['USER_TYPE'] && $arr['MULTIPLE']=='N'))
	{
		$arFieldsPrice['IP_PROP'.$arr['ID']] = $arr["NAME"].' ['.$arr["CODE"].']';
	}
}
$arFieldsQnt = $arFieldsPrice;

$arPriceTypes = array();
$dbPriceType = \CCatalogGroup::GetList(array("BASE"=>"DESC", "SORT" => "ASC"));
while($arPriceType = $dbPriceType->Fetch())
{
	$arPriceTypes[$arPriceType["ID"]] = ($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<div style="display: none;">
		<select name="price">
			<option value=""><?echo GetMessage("KDA_IE_NOT_CHOOSE");?></option><?
			/*foreach($arGroupFields as $k2=>$v2)
			{
				?><optgroup label="<?echo $v2['title']?>"><?*/
				foreach($arFieldsPrice as $k=>$v)
				{
					?><option value="<?echo $k; ?>"><?echo htmlspecialcharsbx($v); ?></option><?
				}
				/*?></optgroup><?
			}*/
			?>
		</select>
		<select name="quantity">
			<option value=""><?echo GetMessage("KDA_IE_NOT_CHOOSE");?></option><?
			/*foreach($arGroupFields as $k2=>$v2)
			{
				?><optgroup label="<?echo $v2['title']?>"><?*/
				foreach($arFieldsQnt as $k=>$v)
				{
					?><option value="<?echo $k; ?>"><?echo htmlspecialcharsbx($v); ?></option><?
				}
				/*?></optgroup><?
			}*/
			?>
		</select>
	</div>
	
	<table width="100%" class="kda-ie-price-calculating">
		<col width="50%">
		<col width="50%">
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_CALC_PRICE_TYPE"); ?></td>
			<td class="adm-detail-content-cell-r">
				<select name="DEFAULTS[PRICE_TYPE]">
					<?
					foreach($arPriceTypes as $k=>$v)
					{
						?><option value="<?echo $k;?>"<?if($DEFAULTS['PRICE_TYPE']==$k){echo ' selected';}?>><?echo htmlspecialcharsbx($v); ?></option><?
					}
					?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_CALC_PRICE_CALC"); ?></td>
			<td class="adm-detail-content-cell-r">
				<select name="DEFAULTS[PRICE_CALC]">
					<option value="MIN"<?if($DEFAULTS['PRICE_CALC']=='MIN'){echo ' selected';}?>><?echo GetMessage("KDA_IE_CALC_PRICE_CALC_MIN");?></option>
					<option value="MAX"<?if($DEFAULTS['PRICE_CALC']=='MAX'){echo ' selected';}?>><?echo GetMessage("KDA_IE_CALC_PRICE_CALC_MAX");?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_CALC_PRICE_ONLY_AVAILABLE"); ?></td>
			<td class="adm-detail-content-cell-r">
				<input type="hidden" name="DEFAULTS[ONLY_AVAILABLE]" value="N">
				<input type="checkbox" name="DEFAULTS[ONLY_AVAILABLE]" value="Y"<?if($DEFAULTS['ONLY_AVAILABLE']!='N'){echo ' checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_CALC_QUANTITY"); ?></td>
			<td class="adm-detail-content-cell-r">
				<select name="DEFAULTS[QUANTITY_CALC]">
					<option value="FROM_PRICE"<?if($DEFAULTS['QUANTITY_CALC']=='FROM_PRICE'){echo ' selected';}?>><?echo GetMessage("KDA_IE_CALC_QUANTITY_FROM_PRICE");?></option>
					<option value="SUM"<?if($DEFAULTS['QUANTITY_CALC']=='SUM'){echo ' selected';}?>><?echo GetMessage("KDA_IE_CALC_QUANTITY_SUM");?></option>
				</select>
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2">
				<?echo GetMessage("KDA_IE_REL_TABLE_PRICES"); ?>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" class="kda-ie-pricing-rels">
				<table width="100%" cellpadding="5" border="1" data-iblock-id="<?echo $IBLOCK_ID?>">
				  <tr>
					<th width="50%"><?echo GetMessage("KDA_IE_REL_TABLE_COL_PRICE"); ?></th>
					<th width="50%"><?echo GetMessage("KDA_IE_REL_TABLE_COL_QNT"); ?></th>
					<th width="30px"></th>
				  </tr>
				<?
				foreach($MAP[$IBLOCK_ID] as $index=>$arMap)
				{
				?>
				  <tr data-index="<?echo $index;?>">
					<td>
					  <div class="kda-ie-select-mapping">
						<input type="hidden" name="MAP[<?echo $IBLOCK_ID;?>][<?echo $index;?>][price]" value="<?echo htmlspecialcharsbx($arMap['price']);?>">
						<a href="javascript:void(0)" onclick="EProfile.RelTablePriceShowSelect(this, 'price')" data-default-text="<?echo GetMessage("KDA_IE_NOT_CHOOSE")?>"><?echo (strlen($arMap['price']) > 0 && isset($arFieldsPrice[$arMap['price']]) ? $arFieldsPrice[$arMap['price']] : GetMessage("KDA_IE_NOT_CHOOSE"))?></a>
					  </div>
					</td>
					<td>
					  <div class="kda-ie-select-mapping">
						<input type="hidden" name="MAP[<?echo $IBLOCK_ID;?>][<?echo $index;?>][quantity]" value="<?echo htmlspecialcharsbx($arMap['quantity']);?>">
						<a href="javascript:void(0)" onclick="EProfile.RelTablePriceShowSelect(this, 'quantity')" data-default-text="<?echo GetMessage("KDA_IE_NOT_CHOOSE")?>"><?echo (strlen($arMap['quantity']) > 0 && isset($arFieldsQnt[$arMap['quantity']]) ? $arFieldsQnt[$arMap['quantity']] : GetMessage("KDA_IE_NOT_CHOOSE"))?></a>
					  </div>
					</td>
					<td>
					  <a href="javascript:void(0)" class="kda-ie-delete-row" onclick="EProfile.RelTablePriceRowRemove(this)" title="<?echo GetMessage("KDA_IE_REL_TABLE_REMOVE_ROW"); ?>"></a>
					</td>
				  </tr>
				<?
				}
				?>
				</table>
				<a href="javascript:void(0)" onclick="EProfile.RelTablePriceRowAdd(this)"><?echo GetMessage("KDA_IE_REL_TABLE_ADD_ROW"); ?></a>
			</td>
		</tr>		
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>