<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
$moduleId = 'esol.importexportexcel';
$moduleJsId = 'esol_importexcel';
Loader::includeModule($moduleId);
CJSCore::Init(array($moduleJsId));

if($USER->IsAdmin())
{
	Loc::loadMessages(__FILE__);

	$aTabs = array(
		array("DIV" => "edit0", "TAB" => Loc::getMessage("KDA_IE_SETTINGS"), "ICON" => "", "TITLE" => Loc::getMessage("KDA_IE_SETTINGS_TITLE")),
		array("DIV" => "edit2", "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"), "ICON" => "form_settings", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")),
	);
	$tabControl = new CAdminTabControl("esolImportexportexcelTabControl", $aTabs, true, true);

	if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['RestoreDefaults']) && !empty($_GET['RestoreDefaults']) && check_bitrix_sessid())
	{
		COption::RemoveOption($moduleId);
		$arGROUPS = array();
		$z = CGroup::GetList($v1, $v2, array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
		{
			$ar = array();
			$ar["ID"] = intval($zr["ID"]);
			$ar["NAME"] = htmlspecialcharsbx($zr["NAME"])." [<a title=\"".GetMessage("MAIN_USER_GROUP_TITLE")."\" href=\"/bitrix/admin/group_edit.php?ID=".intval($zr["ID"])."&lang=".LANGUAGE_ID."\">".intval($zr["ID"])."</a>]";
			$groups[$zr["ID"]] = "[".$zr["ID"]."] ".$zr["NAME"];
			$arGROUPS[] = $ar;
		}
		reset($arGROUPS);
		while (list(,$value) = each($arGROUPS))
			$APPLICATION->DelGroupRight($moduleId, array($value["ID"]));
	
		LocalRedirect($APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID.'&mid_menu=1&mid='.$moduleId);
	}

	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		if(isset($_POST['Update']) && $_POST['Update'] === 'Y' && is_array($_POST['SETTINGS']))
		{
			foreach($_POST['SETTINGS'] as $k=>$v)
			{
				if(in_array($k, array('TRANS_PARAMS')))
				{
					$v = serialize($v);
				}
				COption::SetOptionString($moduleId, $k, (is_array($v) ? serialize($v) : $v));
			}

			//LocalRedirect($APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID.'&mid_menu=1&mid='.$moduleId.'&'.$tabControl->ActiveTabParam());
		}
	}


	$tabControl->Begin();
	?>
	<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?echo LANGUAGE_ID?>&mid_menu=1&mid=<?=$moduleId?>" name="esol_importexportexcel_settings">
	<? echo bitrix_sessid_post();

	$arTransParams = COption::GetOptionString($moduleId, 'TRANS_PARAMS', '');
	if(is_string($arTransParams) && !empty($arTransParams)) $arTransParams = unserialize($arTransParams);
	if(!is_array($arTransParams)) $arTransParams = array();

	$tabControl->BeginNextTab();
	$setMaxExecutionTime = (bool)(COption::GetOptionString($moduleId, 'SET_MAX_EXECUTION_TIME')=='Y');
	?>
	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_COMMON_SETTINGS'); ?></td>
	</tr>
	<tr>
		<td width="50%"><? echo Loc::getMessage('KDA_IE_SET_MAX_EXECUTION_TIME'); ?></td>
		<td width="50%">
			<input type="hidden" name="SETTINGS[SET_MAX_EXECUTION_TIME]" value="N">
			<input type="checkbox" name="SETTINGS[SET_MAX_EXECUTION_TIME]" value="Y" onchange="document.getElementById('MAX_EXECUTION_TIME').style.display=document.getElementById('EXECUTION_DELAY').style.display=(this.checked ? '' : 'none')" <?if($setMaxExecutionTime){echo 'checked';}?>>
		</td>
	</tr>
	<tr id="MAX_EXECUTION_TIME" <?if(!$setMaxExecutionTime){echo 'style="display: none;"';}?>>
		<td width="50%"><? echo Loc::getMessage('KDA_IE_MAX_EXECUTION_TIME'); ?></td>
		<td width="50%">
			<input type="text" name="SETTINGS[MAX_EXECUTION_TIME]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'MAX_EXECUTION_TIME'));?>" size="3" maxlength="3">
		</td>
	</tr>
	<tr id="EXECUTION_DELAY" <?if(!$setMaxExecutionTime){echo 'style="display: none;"';}?>>
		<td width="50%"><? echo Loc::getMessage('KDA_IE_EXECUTION_DELAY'); ?></td>
		<td width="50%">
			<input type="text" name="SETTINGS[EXECUTION_DELAY]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'EXECUTION_DELAY'));?>" size="3" maxlength="3">
		</td>
	</tr>
	<tr>
		<td width="50%"><? echo Loc::getMessage('KDA_IE_AUTO_CONTINUE_IMPORT'); ?></td>
		<td width="50%">
			<input type="hidden" name="SETTINGS[AUTO_CONTINUE_IMPORT]" value="N">
			<input type="checkbox" name="SETTINGS[AUTO_CONTINUE_IMPORT]" value="Y" <?if(COption::GetOptionString($moduleId, 'AUTO_CONTINUE_IMPORT', 'N')=='Y'){echo 'checked';}?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_IMAGES_PATH'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[IMAGES_PATH]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'IMAGES_PATH', ''))?>" size="35">
		</td>
	</tr>
	
	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_CRON_SETTINGS'); ?></td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_CRON_NEED_CHECKSIZE'); ?> <span id="hint_CRON_NEED_CHECKSIZE"></span><script>BX.hint_replace(BX('hint_CRON_NEED_CHECKSIZE'), '<?echo Loc::getMessage("KDA_IE_OPTIONS_CRON_NEED_CHECKSIZE_HINT"); ?>');</script></td>
		<td>
			<input type="hidden" name="SETTINGS[CRON_NEED_CHECKSIZE]" value="N">
			<input type="checkbox" name="SETTINGS[CRON_NEED_CHECKSIZE]" value="Y" <?if(COption::GetOptionString($moduleId, 'CRON_NEED_CHECKSIZE', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_CRON_CONTINUE_LOADING'); ?></td>
		<td>
			<input type="hidden" name="SETTINGS[CRON_CONTINUE_LOADING]" value="N">
			<input type="checkbox" name="SETTINGS[CRON_CONTINUE_LOADING]" value="Y" <?if(COption::GetOptionString($moduleId, 'CRON_CONTINUE_LOADING', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_CRON_REMOVE_LOADED_FILE'); ?></td>
		<td>
			<input type="hidden" name="SETTINGS[CRON_REMOVE_LOADED_FILE]" value="N">
			<input type="checkbox" name="SETTINGS[CRON_REMOVE_LOADED_FILE]" value="Y" <?if(COption::GetOptionString($moduleId, 'CRON_REMOVE_LOADED_FILE', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_CRON_BREAK_WITH_CHANGE_TITLES'); ?> <span id="hint_CRON_BREAK_WITH_CHANGE_TITLES"></span><script>BX.hint_replace(BX('hint_CRON_BREAK_WITH_CHANGE_TITLES'), '<?echo Loc::getMessage("KDA_IE_OPTIONS_CRON_BREAK_WITH_CHANGE_TITLES_HINT"); ?>');</script></td>
		<td>
			<input type="hidden" name="SETTINGS[CRON_BREAK_WITH_CHANGE_TITLES]" value="N">
			<input type="checkbox" name="SETTINGS[CRON_BREAK_WITH_CHANGE_TITLES]" value="Y" <?if(COption::GetOptionString($moduleId, 'CRON_BREAK_WITH_CHANGE_TITLES', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY'); ?></td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_MODE'); ?>:</td>
		<td>
			<label><input type="radio" name="SETTINGS[NOTIFY_MODE]" value="NONE" <?if(COption::GetOptionString($moduleId, 'NOTIFY_MODE', 'NONE')=='NONE') echo 'checked';?>> <? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_MODE_NONE'); ?></label><br>
			<label><input type="radio" name="SETTINGS[NOTIFY_MODE]" value="CRON" <?if(COption::GetOptionString($moduleId, 'NOTIFY_MODE', 'NONE')=='CRON') echo 'checked';?>> <? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_MODE_CRON'); ?></label><br>
			<label><input type="radio" name="SETTINGS[NOTIFY_MODE]" value="ALL" <?if(COption::GetOptionString($moduleId, 'NOTIFY_MODE', 'NONE')=='ALL') echo 'checked';?>> <? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_MODE_ALL'); ?></label>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_WITH_FILE'); ?>:</td>
		<td>
			<input type="hidden" name="SETTINGS[NOTIFY_WITH_FILE]" value="N">
			<input type="checkbox" name="SETTINGS[NOTIFY_WITH_FILE]" value="Y" <?if(COption::GetOptionString($moduleId, 'NOTIFY_WITH_FILE', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_EMAIL'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[NOTIFY_EMAIL]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'NOTIFY_EMAIL'));?>">
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_BEGIN_IMPORT'); ?>:</td>
		<td>
			<input type="hidden" name="SETTINGS[NOTIFY_BEGIN_IMPORT]" value="N">
			<input type="checkbox" name="SETTINGS[NOTIFY_BEGIN_IMPORT]" value="Y" <?if(COption::GetOptionString($moduleId, 'NOTIFY_BEGIN_IMPORT', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_END_IMPORT'); ?>:</td>
		<td>
			<input type="hidden" name="SETTINGS[NOTIFY_END_IMPORT]" value="N">
			<input type="checkbox" name="SETTINGS[NOTIFY_END_IMPORT]" value="Y" <?if(COption::GetOptionString($moduleId, 'NOTIFY_END_IMPORT', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_BREAK_IMPORT'); ?>:</td>
		<td>
			<input type="hidden" name="SETTINGS[NOTIFY_BREAK_IMPORT]" value="N">
			<input type="checkbox" name="SETTINGS[NOTIFY_BREAK_IMPORT]" value="Y" <?if(COption::GetOptionString($moduleId, 'NOTIFY_BREAK_IMPORT', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_BEGIN_EXPORT'); ?>:</td>
		<td>
			<input type="hidden" name="SETTINGS[NOTIFY_BEGIN_EXPORT]" value="N">
			<input type="checkbox" name="SETTINGS[NOTIFY_BEGIN_EXPORT]" value="Y" <?if(COption::GetOptionString($moduleId, 'NOTIFY_BEGIN_EXPORT', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_NOTIFY_END_EXPORT'); ?>:</td>
		<td>
			<input type="hidden" name="SETTINGS[NOTIFY_END_EXPORT]" value="N">
			<input type="checkbox" name="SETTINGS[NOTIFY_END_EXPORT]" value="Y" <?if(COption::GetOptionString($moduleId, 'NOTIFY_END_EXPORT', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	
	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_ELEMENTS'); ?></td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_CHECK_REQUIRED_PROPS'); ?>:</td>
		<td>
			<input type="hidden" name="SETTINGS[CHECK_REQUIRED_PROPS]" value="N">
			<input type="checkbox" name="SETTINGS[CHECK_REQUIRED_PROPS]" value="Y" <?if(COption::GetOptionString($moduleId, 'CHECK_REQUIRED_PROPS', 'N')=='Y') echo 'checked';?>>
		</td>
	</tr>
	
	<?/*?><tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_DISCOUNT'); ?></td>
	</tr><?*/?>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_DISCOUNT_MODE'); ?>:</td>
		<td>
			<label><input type="radio" name="SETTINGS[DISCOUNT_MODE]" value="SPLIT" <?if(COption::GetOptionString($moduleId, 'DISCOUNT_MODE', 'SPLIT')=='SPLIT') echo 'checked';?>> <? echo Loc::getMessage('KDA_IE_OPTIONS_DISCOUNT_MODE_SPLIT'); ?></label><br>
			<label><input type="radio" name="SETTINGS[DISCOUNT_MODE]" value="JOIN" <?if(COption::GetOptionString($moduleId, 'DISCOUNT_MODE', 'SPLIT')=='JOIN') echo 'checked';?>> <? echo Loc::getMessage('KDA_IE_OPTIONS_DISCOUNT_MODE_JOIN'); ?></label><br>
		</td>
	</tr>
	
	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TITLE'); ?><span id="hint_OPTIONS_TRANSLIT"></span><script>BX.hint_replace(BX('hint_OPTIONS_TRANSLIT'), '<?echo Loc::getMessage("KDA_IE_OPTIONS_TRANSLIT_TITLE_HINT"); ?>');</script></td>
	</tr>
	<tr>
		<td width="50%"><? echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_LEN'); ?></td>
		<td width="50%">
			<input type="text" name="SETTINGS[TRANS_PARAMS][TRANS_LEN]" value="<?echo htmlspecialcharsex(array_key_exists('TRANS_LEN', $arTransParams) ? $arTransParams['TRANS_LEN'] : 100);?>">
		</td>
	</tr>
	<tr>
		<td width="50%"><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_CASE'); ?></td>
		<td width="50%">
			<?
			$val = (array_key_exists('TRANS_CASE', $arTransParams) ? $arTransParams['TRANS_CASE'] : 'L');
			if(!in_array($val, array('', 'L', 'U'))){$val = 'L';}
			?>
			<select name="SETTINGS[TRANS_PARAMS][TRANS_CASE]">
				<option value=""<?if($val==''){echo ' selected';}?>><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_CASE_SAVE'); ?></option>
				<option value="L"<?if($val=='L'){echo ' selected';}?>><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_CASE_LOWER'); ?></option>
				<option value="U"<?if($val=='U'){echo ' selected';}?>><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_CASE_UPPER'); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td width="50%"><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_SPACE'); ?></td>
		<td width="50%">
			<input type="text" name="SETTINGS[TRANS_PARAMS][TRANS_SPACE]" value="<?echo htmlspecialcharsex(array_key_exists('TRANS_SPACE', $arTransParams) ? $arTransParams['TRANS_SPACE'] : '-');?>">
		</td>
	</tr>
	<tr>
		<td width="50%"><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_OTHER'); ?></td>
		<td width="50%">
			<input type="text" name="SETTINGS[TRANS_PARAMS][TRANS_OTHER]" value="<?echo htmlspecialcharsex(array_key_exists('TRANS_OTHER', $arTransParams) ? $arTransParams['TRANS_OTHER'] : '-');?>">
		</td>
	</tr>
	<tr>
		<td width="50%"><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_TRANS_EAT'); ?></td>
		<td width="50%">
			<input type="hidden" name="SETTINGS[TRANS_PARAMS][TRANS_EAT]" value="N"><input type="checkbox" name="SETTINGS[TRANS_PARAMS][TRANS_EAT]" value="Y"<?if(!array_key_exists('TRANS_EAT', $arTransParams) || $arTransParams['TRANS_EAT']=='Y'){echo 'checked';}?>>
		</td>
	</tr>
	<tr>
		<td width="50%"><?echo Loc::getMessage('KDA_IE_OPTIONS_TRANSLIT_USE_GOOGLE'); ?></td>
		<td width="50%">
			<input type="hidden" name="SETTINGS[TRANS_PARAMS][USE_GOOGLE]" value="N"><input type="checkbox" name="SETTINGS[TRANS_PARAMS][USE_GOOGLE]" value="Y"<?if(array_key_exists('USE_GOOGLE', $arTransParams) && $arTransParams['USE_GOOGLE']=='Y'){echo 'checked';}?>>
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_PROXY'); ?></td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_PROXY_HOST'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[PROXY_HOST]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'PROXY_HOST', ''))?>" size="35">
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_PROXY_PORT'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[PROXY_PORT]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'PROXY_PORT', ''))?>" size="35">
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_PROXY_USER'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[PROXY_USER]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'PROXY_USER', ''))?>" size="35">
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_PROXY_PASSWORD'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[PROXY_PASSWORD]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'PROXY_PASSWORD', ''))?>" size="35">
		</td>
	</tr>
	
	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_EXTERNAL_SERVICES'); ?></td>
	</tr>
	<tr>
		<td colspan="2" align="center"><b><? echo Loc::getMessage('KDA_IE_OPTIONS_YANDEX_DISC'); ?></b></td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_YANDEX_DISC_APIKEY'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[YANDEX_APIKEY]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'YANDEX_APIKEY', ''))?>" size="35">
			&nbsp; <a href="https://oauth.yandex.ru/authorize?response_type=token&client_id=30e9fb3edb184522afaf5e72ee255cbc" target="_blank"><? echo Loc::getMessage('KDA_IE_OPTIONS_YANDEX_DISC_APIKEY_GET'); ?></a>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center"><br><b><? echo Loc::getMessage('KDA_IE_OPTIONS_CLOUD_MAILRU'); ?></b></td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_CLOUD_MAILRU_LOGIN'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[CLOUD_MAILRU_LOGIN]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'CLOUD_MAILRU_LOGIN', ''))?>" size="35">
		</td>
	</tr>
	<tr>
		<td><? echo Loc::getMessage('KDA_IE_OPTIONS_CLOUD_MAILRU_PASSWORD'); ?>:</td>
		<td>
			<input type="text" name="SETTINGS[CLOUD_MAILRU_PASSWORD]" value="<?echo htmlspecialcharsex(COption::GetOptionString($moduleId, 'CLOUD_MAILRU_PASSWORD', ''))?>" size="35">
		</td>
	</tr>
	
	<?
	if(!Loader::includeModule('catalog') && Loader::includeModule('iblock'))
	{
		$fl = new CKDAFieldList(array());
		$arIblocks = $fl->GetIblocks();
		$arIblockNames = array();
		foreach($arIblocks as $type)
		{
			if(!is_array($type['IBLOCKS'])) continue;
			foreach($type['IBLOCKS'] as $iblock)
			{
				$arIblockNames[$iblock["ID"]] = $iblock["NAME"];
			}
		}
		$arProps = array();
		$dbRes = CIBlockProperty::GetList(array('IBLOCK_ID'=>'ASC', 'SORT'=>'ASC', 'ID'=>'ASC'), array('PROPERTY_TYPE'=>'E', 'ACTIVE'=>'Y'));
		while($arr = $dbRes->Fetch())
		{
			if(!isset($arProps[$arr['IBLOCK_ID']]))
			{
				$arProps[$arr['IBLOCK_ID']] = array('NAME' => $arIblockNames[$arr['IBLOCK_ID']], 'PROPS' => array());
			}
			$arProps[$arr['IBLOCK_ID']]['PROPS'][$arr['ID']] = $arr;
		}
		$arRels = unserialize(COption::GetOptionString($moduleId, 'CATALOG_RELS'));
		if(!is_array($arRels)) $arRels = array();
		if(count($arRels)==0) $arRels[] = array('IBLOCK_ID'=>'', 'OFFERS_IBLOCK_ID'=>'', 'OFFERS_PROP_ID'=>'');
		?>
		<tr class="heading">
			<td colspan="2"><? echo Loc::getMessage('KDA_IE_OPTIONS_CATALOG_RELS');?></td>
		</tr>
		
		<tr>
			<td colspan="2" align="center">
			<table border="1" cellpadding="5" class="kda-options-rels-table">
				<tr>
					<th><?echo GetMessage("KDA_IE_OPTIONS_IBLOCK_PRODUCTS");?></th>
					<th><?echo GetMessage("KDA_IE_OPTIONS_IBLOCK_OFFERS");?></th>
					<th><?echo GetMessage("KDA_IE_OPTIONS_IBLOCK_OFFERS_PROP");?></th>
					<th></th>
				</tr>
				<?foreach($arRels as $relKey=>$arRel){?>
				<tr data-index="<?echo $relKey?>">
					<td>
						<select name="SETTINGS[CATALOG_RELS][0][IBLOCK_ID]">
							<option value=""><?echo GetMessage("KDA_IE_OPTIONS_NO_CHOOSEN"); ?></option>
							<?
							foreach($arIblocks as $type)
							{
								?><optgroup label="<?echo $type['NAME']?>"><?
								foreach($type['IBLOCKS'] as $iblock)
								{
									?><option value="<?echo $iblock["ID"];?>" <?if($iblock["ID"]==$arRel['IBLOCK_ID']){echo 'selected';}?>><?echo htmlspecialcharsbx($iblock["NAME"].' ['.$iblock["ID"].']'); ?></option><?
								}
								?></optgroup><?
							}
							?>
						</select>
					</td>
					<td>
						<select name="SETTINGS[CATALOG_RELS][0][OFFERS_IBLOCK_ID]" onchange="KdaOptions.ReloadProps(this);">
							<option value=""><?echo GetMessage("KDA_IE_OPTIONS_NO_CHOOSEN"); ?></option>
							<?
							foreach($arIblocks as $type)
							{
								?><optgroup label="<?echo $type['NAME']?>"><?
								foreach($type['IBLOCKS'] as $iblock)
								{
									?><option value="<?echo $iblock["ID"];?>" <?if($iblock["ID"]==$arRel['OFFERS_IBLOCK_ID']){echo 'selected';}?>><?echo htmlspecialcharsbx($iblock["NAME"].' ['.$iblock["ID"].']'); ?></option><?
								}
								?></optgroup><?
							}
							?>
						</select>
					</td>
					<td>
						<select name="SETTINGS[CATALOG_RELS][0][OFFERS_PROP_ID]">
							<option value=""><?echo GetMessage("KDA_IE_OPTIONS_NO_CHOOSEN"); ?></option>
							<?
							foreach($arProps as $iblockId=>$iblock)
							{
								if($arRel['OFFERS_IBLOCK_ID'] > 0 && $iblockId!=$arRel['OFFERS_IBLOCK_ID']) continue;
								?><optgroup label="<?echo $iblock['NAME']?>" data-id="<?echo $iblockId;?>"><?
								foreach($iblock['PROPS'] as $prop)
								{
									?><option value="<?echo $prop["ID"];?>" <?if($prop["ID"]==$arRel['OFFERS_PROP_ID']){echo 'selected';}?>><?echo htmlspecialcharsbx($prop["NAME"].' ['.$prop["ID"].']'); ?></option><?
								}
								?></optgroup><?
							}
							?>
						</select>
					</td>
					<td>
						<a href="javascript:void(0)" onclick="KdaOptions.RemoveRel(this);" class="kda-options-rels-delete" title="<?echo GetMessage("KDA_IE_OPTIONS_REMOVE"); ?>"></a>
					</td>
				</tr>
				<?}?>
			</table>
			<div class="kda-options-rels">
				<select name="OFFERS_PROP_ID">
					<option value=""><?echo GetMessage("KDA_IE_OPTIONS_NO_CHOOSEN"); ?></option>
					<?
					foreach($arProps as $iblockId=>$iblock)
					{
						?><optgroup label="<?echo $iblock['NAME']?>" data-id="<?echo $iblockId;?>"><?
						foreach($iblock['PROPS'] as $prop)
						{
							?><option value="<?echo $prop["ID"];?>" <?if($prop["ID"]==$iblockId){echo 'selected';}?>><?echo htmlspecialcharsbx($prop["NAME"].' ['.$prop["ID"].']'); ?></option><?
						}
						?></optgroup><?
					}
					?>
				</select>
				<a href="javascript:void(0)" onclick="KdaOptions.AddRels(this);"><?echo GetMessage("KDA_IE_OPTIONS_ADD_RELS"); ?></a>
			</div>
			</td>
		</tr>
		<?
	}
	?>

	<?$tabControl->BeginNextTab();?>
	<?
	$module_id = $moduleId;
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
	?>
	<?
	$tabControl->Buttons();?>
<script type="text/javascript">
function RestoreDefaults()
{
	if (confirm('<? echo CUtil::JSEscape(Loc::getMessage("KDA_IE_OPTIONS_BTN_HINT_RESTORE_DEFAULT_WARNING")); ?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?lang=<? echo LANGUAGE_ID; ?>&mid_menu=1&mid=<? echo $moduleId; ?>&RestoreDefaults=Y&<?=bitrix_sessid_get()?>";
}
</script>
	<input type="submit" name="Update" value="<?echo Loc::getMessage("KDA_IE_OPTIONS_BTN_SAVE")?>">
	<input type="hidden" name="Update" value="Y">
	<input type="reset" name="reset" value="<?echo Loc::getMessage("KDA_IE_OPTIONS_BTN_RESET")?>">
	<input type="button" title="<?echo Loc::getMessage("KDA_IE_OPTIONS_BTN_HINT_RESTORE_DEFAULT")?>" onclick="RestoreDefaults();" value="<?echo Loc::getMessage("KDA_IE_OPTIONS_BTN_RESTORE_DEFAULT")?>">
	<?$tabControl->End();?>
	</form>
<?
}
?>