<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$module_id = 'esol.importexportexcel';
CModule::IncludeModule($module_id);
IncludeModuleLangFile(__FILE__);

$success = false;
$error = '';
if($_POST['action']=='save')
{
	define('PUBLIC_AJAX_MODE', 'Y');
	$dir = trim($_POST['folder'], '/');
	if(strlen($dir) > 0) 
	{
		if(CUtil::DetectUTF8($dir)) $dir = utf8win1251($dir);
		$dir = $_SERVER['DOCUMENT_ROOT'].'/'.$dir.'/';
		CheckDirPath($dir);
		$arImages = array();

		if(is_array($_POST['images']))
		{
			$key = 0;
			foreach($_POST['images'] as $k=>$v)
			{
				foreach($v as $k2=>$v2)
				{
					if(isset($arImages[$key]) && isset($arImages[$key][$k2])) $key++;
					$arImages[$key][$k2] = $v2;
				}			
			}
		}

		foreach($arImages as $arImage)
		{
			//if(CUtil::DetectUTF8($arImage['name'])) $arImage['name'] = utf8win1251($arImage['name']);
			$fn = $_SERVER['DOCUMENT_ROOT'].$arImage['tmp_name'];
			if(!file_exists($fn)) $fn = CTempFile::GetAbsoluteRoot().$arImage['tmp_name'];
			if(!file_exists($fn)) continue;
			$imgName = \Bitrix\Main\IO\Path::convertLogicalToPhysical($arImage['name']);
			copy($fn, $dir.$imgName);
			unlink($fn);
		}
		$success = true;
	}
	else
	{
		$error = GetMessage("KDA_IE_MASS_UPLOAD_ERROR_EMPTY_DIR");
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
if($success)
{
	CAdminMessage::ShowMessage(array(
		'TYPE' => 'OK',
		'MESSAGE' => GetMessage("KDA_IE_MASS_UPLOAD_SUCCESS")
	));
}

if($error)
{
	CAdminMessage::ShowMessage(array(
		'TYPE' => 'ERROR',
		'MESSAGE' => $error
	));
}

if($_POST)
{
	?><script>
		EProfile.MassUploaderSetButtons(true);
	</script><?
}

$folder = '/upload/images/';
if(isset($_POST['folder'])) $folder = $_POST['folder'];
elseif(COption::GetOptionString($module_id, 'IMAGES_PATH')) $folder = COption::GetOptionString($module_id, 'IMAGES_PATH');
?>
<form action="<?=$_SERVER['REQUEST_URI']?>" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<table width="100%">
		<col width="50%">
		<col width="50%">
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_MASS_UPLOAD_FOLDER");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="text" name="folder" value="<?echo htmlspecialcharsex($folder);?>" size="30">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?
				$fileInput = new \Bitrix\Main\UI\FileInput(array(
					'name' => 'images[]', 
					'edit' => false, 
					'id' => 'upload_images_'.md5(mt_rand()),
					'description' => false,
					'upload' => true
				));
				echo $fileInput->show();
				?>
			</td>
		</tr>
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>