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

$arGet = $_POST;

if($_POST['action']=='save' && $_POST['ADDITIONAL_SETTINGS'])
{
	define('PUBLIC_AJAX_MODE', 'Y');
	
	$arRemoveProps = array();
	if(is_array($_POST['ADDITIONAL_SETTINGS']['ELEMENT_PROPERTIES_REMOVE']))
	{
		foreach($_POST['ADDITIONAL_SETTINGS']['ELEMENT_PROPERTIES_REMOVE'] as $k=>$v)
		{
			if($v=='Y') $arRemoveProps[] = $k;
		}
	}
	if(!empty($arRemoveProps)) $_POST['ADDITIONAL_SETTINGS']['ELEMENT_PROPERTIES_REMOVE'] = $arRemoveProps;
	else unset($_POST['ADDITIONAL_SETTINGS']['ELEMENT_PROPERTIES_REMOVE']);
	
	if(is_array($_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_FROM']))
	{
		$arRanges = array();
		foreach($_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_FROM'] as $k=>$v)
		{
			if($_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_FROM'][$k] || $_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_TO'][$k])
			{
				$arRanges[] = array(
					'FROM' => $_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_FROM'][$k],
					'TO' => $_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_TO'][$k]
				);
			}
		}
		$_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE'] = $arRanges;
	}
	unset($_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_FROM'], $_POST['ADDITIONAL_SETTINGS']['LOADING_RANGE_TO']);
	
	$APPLICATION->RestartBuffer();
	ob_end_clean();
	
	echo '<input type="hidden" name="from_list_settings" id="from_list_settings" value="'.htmlspecialcharsex(CUtil::PhpToJSObject($_POST['ADDITIONAL_SETTINGS'])).'">';
	echo '<script>';
	echo '$(\'table.kda-ie-tbl[data-list-index='.htmlspecialcharsex($arGet['list_index']).'] input[name^="SETTINGS[ADDITIONAL_SETTINGS]"]\').val($("#from_list_settings").val());';
	echo 'BX.WindowManager.Get().Close();';
	echo '</script>';
	die();
}

/*$obJSPopup = new CJSPopup();
$obJSPopup->ShowTitlebar(GetMessage("KDA_IE_LIST_SETTING_TITLE"));*/

$IBLOCK_ID = (int)$arGet['SETTINGS']['IBLOCK_ID'][$arGet['list_index']];
$OFFERS_IBLOCK_ID = CKDAImportUtils::GetOfferIblock($IBLOCK_ID);
$SECTION_ID = (int)$arGet['SETTINGS']['SECTION_ID'][$arGet['list_index']];
$ADDITIONAL_SETTINGS = $arGet['SETTINGS']['ADDITIONAL_SETTINGS'][$arGet['list_index']];
$arLoadFields = $arGet['SETTINGS']['FIELDS_LIST'][$arGet['list_index']];
$arFindFields = $arGet['FIND_FIELDS'];

if($ADDITIONAL_SETTINGS) $ADDITIONAL_SETTINGS = CUtil::JsObjectToPhp($ADDITIONAL_SETTINGS);
if(!is_array($ADDITIONAL_SETTINGS)) $ADDITIONAL_SETTINGS= array();

$arLoadProps = array();
foreach($arLoadFields as $v)
{
	if(strpos($v, 'IP_PROP')===0)
	{
		$arLoadProps[] = substr($v, 7);
	}
}
/*if(is_array($arFindFields))
{
	foreach($arFindFields as $v)
	{
		if(strpos($v, 'IP_PROP')===0)
		{
			$arLoadProps[] = substr($v, 7);
		}
	}
}*/
$arLoadProps = array_unique($arLoadProps);

$sectionProps = CIBlockSectionPropertyLink::GetArray($IBLOCK_ID, $SECTION_ID);
$propIds = array();
foreach($sectionProps as $prop)
{
	if(!in_array($prop['PROPERTY_ID'], $arLoadProps))
	{
		$propIds[] = $prop['PROPERTY_ID'];
	}
}

$arRemoveProps = array();
$arDefaultProps = array();
if(!empty($propIds))
{
	$dbRes = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", 'IBLOCK_ID'=>$IBLOCK_ID));
	while($arr = $dbRes->Fetch())
	{
		if(in_array($arr['ID'], $propIds))
		{
			$arRemoveProps[] = $arr;
		}
		$arDefaultProps[$arr['ID']] = $arr;
	}
}

$arDefaultPropsOffer = array();
if($OFFERS_IBLOCK_ID > 0)
{
	$dbRes = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", 'IBLOCK_ID'=>$OFFERS_IBLOCK_ID));
	while($arr = $dbRes->Fetch())
	{
		$arDefaultPropsOffer[$arr['ID']] = $arr;
	}
}

$arDefaultCatFields = CKDAFieldList::GetCatalogDefaultFields($IBLOCK_ID);
$arDefaultElFields = CKDAFieldList::GetIblockElementDefaultFields();

function ShowPostData($post, $key='')
{
	foreach($post as $k=>$v)
	{
		$k2 = ($key ? $key.'['.$k.']' : $k);
		if(is_array($v))
		{
			ShowPostData($v, $k2);
		}
		else
		{
			echo '<input type="hidden" name="'.$k2.'" value="'.htmlspecialcharsex($v).'">';
		}
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<?ShowPostData($_POST);?>
	<table width="100%" class="kda-ie-list-settings">
		<col width="50%">
		<col width="50%">
		<tr class="heading">
			<td colspan="2">
				<?echo GetMessage("KDA_IE_LIST_SETTING_LOADING_RANGE"); ?>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_LIST_SETTING_BREAK_LOADING");?></script></td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="ADDITIONAL_SETTINGS[BREAK_LOADING]" value="Y" <?if($ADDITIONAL_SETTINGS['BREAK_LOADING']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		<tr>
			<td class="kda-ie-ls-range" colspan="2">
				<?
				if(!is_array($ADDITIONAL_SETTINGS['LOADING_RANGE'])) $ADDITIONAL_SETTINGS['LOADING_RANGE'] = array();
				$ADDITIONAL_SETTINGS['LOADING_RANGE'][] = array('FROM'=>'', 'TO'=>'');
				$cnt = count($ADDITIONAL_SETTINGS['LOADING_RANGE']);
				foreach($ADDITIONAL_SETTINGS['LOADING_RANGE'] as $k=>$v)
				{
					?>
					<div class="kda-ie-ls-range-item"<?if($k==$cnt-1){echo ' style="display: none;"';}?>>
						<?echo GetMessage("KDA_IE_LIST_SETTING_LOADING_RANGE_FROM")?>
						<input type="text" name="ADDITIONAL_SETTINGS[LOADING_RANGE_FROM][]" value="<?echo htmlspecialcharsex($v['FROM'])?>">
						<?echo GetMessage("KDA_IE_LIST_SETTING_LOADING_RANGE_TO")?>
						<input type="text" name="ADDITIONAL_SETTINGS[LOADING_RANGE_TO][]" value="<?echo htmlspecialcharsex($v['TO'])?>">
						<?echo GetMessage("KDA_IE_LIST_SETTING_LOADING_RANGE_STRING")?>
						<a class="delete" href="javascript:void(0)" onclick="ESettings.RemoveLoadingRange(this);" title="<?echo GetMessage("KDA_IE_LIST_SETTING_DELETE"); ?>"></a>
					</div>
					<?
				}
				?>
				<a href="javascript:void(0)" onclick="ESettings.AddNewLoadingRange(this);"><?echo GetMessage("KDA_IE_LIST_SETTING_NEW_LOADING_RANGE")?></a>
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2">
				<?echo GetMessage("KDA_IE_LIST_SETTING_PROPERTIES_DEFAULT"); ?>
				<?/*?><span id="hint_PROPERTIES_DEFAULT"></span><script>BX.hint_replace(BX('hint_PROPERTIES_DEFAULT'), '<?echo GetMessage("KDA_IE_LIST_SETTING_PROPERTIES_DEFAULT_HINT"); ?>');</script><?*/?>
			</td>
		</tr>
		<?
		$ELEMENT_PROPERTIES_DEFAULT = $ADDITIONAL_SETTINGS['ELEMENT_PROPERTIES_DEFAULT'];
		if(is_array($ELEMENT_PROPERTIES_DEFAULT))
		{
			foreach($ELEMENT_PROPERTIES_DEFAULT as $k=>$v)
			{
				if(isset($arDefaultProps[$k])) $fieldName = $arDefaultProps[$k]['NAME'];
				elseif(isset($arDefaultCatFields[$k])) $fieldName = $arDefaultCatFields[$k]['NAME'];
				else $fieldName = $arDefaultElFields[$k]['NAME'];
				?>
				<tr class="kda-ie-list-settings-defaults">
					<td class="adm-detail-content-cell-l"><?echo $fieldName;?>:</td>
					<td class="adm-detail-content-cell-r">
						<input type="text" name="ADDITIONAL_SETTINGS[ELEMENT_PROPERTIES_DEFAULT][<?echo $k;?>]" value="<?echo htmlspecialcharsex($v);?>">
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
				<select name="prop_default" style="min-width: 200px;" class="kda-chosen-multi" onchange="ESettings.AddDefaultProp(this)">
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
				</select>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" align="center">
				<?
				echo BeginNote();
				echo GetMessage("KDA_IE_LIST_SETTING_PROPERTIES_DEFAULT_NOTE");
				echo EndNote();
				?>
			</td>
		</tr>
		
		<?if($OFFERS_IBLOCK_ID > 0){?>
			<tr class="heading">
				<td colspan="2">
					<?echo GetMessage("KDA_IE_LIST_SETTING_PROPERTIES_DEFAULT_OFFER"); ?>
				</td>
			</tr>
			<?
			$OFFER_PROPERTIES_DEFAULT = $ADDITIONAL_SETTINGS['OFFER_PROPERTIES_DEFAULT'];
			if(is_array($OFFER_PROPERTIES_DEFAULT))
			{
				foreach($OFFER_PROPERTIES_DEFAULT as $k=>$v)
				{
					if(isset($arDefaultPropsOffer[$k])) $fieldName = $arDefaultPropsOffer[$k]['NAME'];
					elseif(isset($arDefaultCatFields[$k])) $fieldName = $arDefaultCatFields[$k]['NAME'];
					else $fieldName = $arDefaultElFields[$k]['NAME'];
					?>
					<tr class="kda-ie-list-settings-defaults">
						<td class="adm-detail-content-cell-l"><?echo $fieldName;?>:</td>
						<td class="adm-detail-content-cell-r">
							<input type="text" name="ADDITIONAL_SETTINGS[OFFER_PROPERTIES_DEFAULT][<?echo $k;?>]" value="<?echo htmlspecialcharsex($v);?>">
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
					<select name="prop_default" style="min-width: 200px;" class="kda-chosen-multi" onchange="ESettings.AddDefaultProp(this, 'offer')">
						<option value=""><?echo GetMessage('KDA_IE_PLACEHOLDER_CHOOSE');?></option>
						<optgroup label="<?echo GetMessage('KDA_IE_LIST_SETTING_ELEMENT');?>">
							<?
							foreach($arDefaultElFields as $elKey=>$elField)
							{
								echo '<option value="'.$elKey.'">'.$elField['NAME'].'</option>';
							}
							?>
						</optgroup>
						<?if(!empty($arDefaultPropsOffer)){?>
							<optgroup label="<?echo GetMessage('KDA_IE_LIST_SETTING_PROPERTIES');?>">
								<?
								foreach($arDefaultPropsOffer as $prop)
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
					</select>
				</td>
			</tr>
			
			<tr>
				<td colspan="2" align="center">
					<?
					echo BeginNote();
					echo GetMessage("KDA_IE_LIST_SETTING_PROPERTIES_DEFAULT_OFFER_NOTE");
					echo EndNote();
					?>
				</td>
			</tr>
		<?}?>
		
		<!--<tr class="heading">
			<td colspan="2">
				<?echo GetMessage("KDA_IE_LIST_SETTING_OTHER_SETTINGS"); ?>
			</td>
		</tr>-->
		<tr class="heading">
			<td colspan="2">
				<?echo GetMessage("KDA_IE_LIST_SETTING_PROPERTIES_REMOVE"); ?>
				<span id="hint_ELEMENT_PROPERTIES_REMOVE"></span><script>BX.hint_replace(BX('hint_ELEMENT_PROPERTIES_REMOVE'), '<?echo GetMessage("KDA_IE_LIST_SETTING_PROPERTIES_REMOVE_HINT"); ?>');</script>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-r" colspan="2">
				<div class="kda-ie-prop-remove">
				<?
				$ELEMENT_PROPERTIES_REMOVE = $ADDITIONAL_SETTINGS['ELEMENT_PROPERTIES_REMOVE'];
				if(!is_array($ELEMENT_PROPERTIES_REMOVE)) $ELEMENT_PROPERTIES_REMOVE = array();
				foreach($arRemoveProps as $prop)
				{
					echo '<div class="kda-ie-prop-remove-item"><input type="checkbox" name="ADDITIONAL_SETTINGS[ELEMENT_PROPERTIES_REMOVE]['.$prop['ID'].']" value="Y"'.(in_array($prop['ID'], $ELEMENT_PROPERTIES_REMOVE) ? ' checked' : '').' id="element_properties_remove_'.$prop['ID'].'"> <label for="element_properties_remove_'.$prop['ID'].'">'.$prop['NAME'].'</label></div>';
				}
				?>	
				</div>
			</td>
		</tr>
		
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>