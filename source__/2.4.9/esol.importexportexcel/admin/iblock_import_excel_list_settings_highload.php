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

$ADDITIONAL_SETTINGS = $arGet['SETTINGS']['ADDITIONAL_SETTINGS'][$arGet['list_index']];
if($ADDITIONAL_SETTINGS) $ADDITIONAL_SETTINGS = CUtil::JsObjectToPhp($ADDITIONAL_SETTINGS);
if(!is_array($ADDITIONAL_SETTINGS)) $ADDITIONAL_SETTINGS= array();


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
		
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>