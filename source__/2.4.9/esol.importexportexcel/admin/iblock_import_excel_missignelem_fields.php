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

$arGet = $_GET;
$INPUT_ID = $arGet['INPUT_ID'];
$IBLOCK_ID = (int)$arGet['IBLOCK_ID'];
$isOffers = false;
if(strpos($INPUT_ID, 'OFFER_')===0)
{
	$IBLOCK_ID = CKDAImportUtils::GetOfferIblock($IBLOCK_ID);
	$isOffers = true;
}

if($_POST['action']=='save')
{
	\CUtil::JSPostUnescape();
	define('PUBLIC_AJAX_MODE', 'Y');
	$APPLICATION->RestartBuffer();
	ob_end_clean();
	
	echo '<script>';
	echo '$("#'.$INPUT_ID.'").val("'.(is_array($_POST['DEFAULTS']) ? base64_encode(serialize($_POST['DEFAULTS'])) : '').'");';
	echo 'BX.WindowManager.Get().Close();';
	echo '</script>';
	die();
}

if($OLDDEFAULTS) $DEFAULTS = unserialize(base64_decode($OLDDEFAULTS));
if(!is_array($DEFAULTS)) $DEFAULTS= array();


$arDefaultProps = array();
$dbRes = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", 'IBLOCK_ID'=>$IBLOCK_ID));
while($arr = $dbRes->Fetch())
{
	$arDefaultProps[$arr['ID']] = $arr;
}

$arDefaultCatDiscountFields = CKDAFieldList::GetCatalogDiscountDefaultFields($IBLOCK_ID);
$arDefaultCatFields = CKDAFieldList::GetCatalogDefaultFields($IBLOCK_ID);
$arDefaultElFields = CKDAFieldList::GetIblockElementDefaultFields();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<table width="100%" class="kda-ie-list-settings">
		<col width="50%">
		<col width="50%">
		
		<tr class="heading">
			<td colspan="2">
				<?echo GetMessage(($isOffers ? "KDA_IE_LIST_SETTING_PROPERTIES_DEFAULT_OFFER" : "KDA_IE_LIST_SETTING_PROPERTIES_DEFAULT")); ?>
			</td>
		</tr>
		<?
		if(is_array($DEFAULTS))
		{
			foreach($DEFAULTS as $k=>$v)
			{
				if(isset($arDefaultProps[$k])) $fieldName = $arDefaultProps[$k]['NAME'];
				elseif(isset($arDefaultCatFields[$k])) $fieldName = $arDefaultCatFields[$k]['NAME'];
				else $fieldName = $arDefaultElFields[$k]['NAME'];
				?>
				<tr class="kda-ie-list-settings-defaults">
					<td class="adm-detail-content-cell-l"><?echo $fieldName;?>:</td>
					<td class="adm-detail-content-cell-r">
						<input type="text" name="DEFAULTS[<?echo $k;?>]" value="<?echo htmlspecialcharsex($v);?>">
						<a class="delete" href="javascript:void(0)" onclick="ESettings.RemoveDefaultProp(this);" title="<?echo GetMessage("KDA_IE_LIST_SETTING_DELETE"); ?>"></a>
					</td>
				</tr>
				<?
			}
		}
		?>		
		<tr class="kda-ie-list-settings-defaults" style="display: none;">
			<td class="adm-detail-content-cell-l"></td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="empty" value="">
				<a class="delete" href="javascript:void(0)" onclick="ESettings.RemoveDefaultProp(this);" title="<?echo GetMessage("KDA_IE_LIST_SETTING_DELETE"); ?>"></a>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="kda-ie-chosen-td">
				<select name="prop_default" style="min-width: 200px;" class="kda-ie-chosen-multi" onchange="ESettings.AddDefaultProp(this, false, 'DEFAULTS')">
					<option value=""><?echo GetMessage('KDA_IE_PLACEHOLDER_CHOOSE');?></option>
					<optgroup label="<?echo GetMessage('KDA_IE_LIST_SETTING_ELEMENT');?>">
						<?
						foreach($arDefaultElFields as $elKey=>$elField)
						{
							echo '<option value="'.$elKey.'">'.$elField['NAME'].'</option>';
						}
						?>
					</optgroup>
					<?if(!empty($arDefaultProps)){?>
						<optgroup label="<?echo GetMessage('KDA_IE_LIST_SETTING_PROPERTIES');?>">
							<?
							foreach($arDefaultProps as $prop)
							{
								echo '<option value="'.$prop['ID'].'">'.$prop['NAME'].'</option>';
							}
							?>
						</optgroup>
					<?}?>
					<?if(!empty($arDefaultCatFields)){?>
						<optgroup label="<?echo GetMessage('KDA_IE_LIST_SETTING_CATALOG');?>">
							<?
							foreach($arDefaultCatFields as $cKey=>$cField)
							{
								echo '<option value="'.$cKey.'">'.$cField['NAME'].'</option>';
							}
							?>
						</optgroup>
					<?}?>
					<?/*if(!empty($arDefaultCatDiscountFields)){?>
						<optgroup label="<?echo GetMessage('KDA_IE_LIST_SETTING_CATALOG_DISCOUNT');?>">
							<?
							foreach($arDefaultCatDiscountFields as $cKey=>$cField)
							{
								echo '<option value="'.$cKey.'">'.$cField['NAME'].'</option>';
							}
							?>
						</optgroup>
					<?}*/?>
				</select>
			</td>
		</tr>		
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>