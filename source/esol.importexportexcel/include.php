<?php
include_once(dirname(__FILE__).'/install/demo.php');

$moduleId = 'esol.importexportexcel';
$pathJS = '/bitrix/js/'.$moduleId;
$pathCSS = '/bitrix/panel/'.$moduleId;
$pathLang = BX_ROOT.'/modules/'.$moduleId.'/lang/'.LANGUAGE_ID;
CModule::AddAutoloadClasses(
	$moduleId,
	array(
		'CKDAFieldList' => 'classes/import/field_list.php',
		'CKDAImportProfile' => 'classes/import/profile.php',
		'CKDAImportProfileAll' => 'classes/import/profile.php',
		'CKDAImportProfileDB' => 'classes/import/profile_db.php',
		'CKDAImportProfileFS' => 'classes/import/profile_fs.php',
		'CKDAImportExcel' => 'classes/import/import.php',
		'CKDAImportExcelRollback' => 'classes/import/import_rollback.php',
		'CKDAImportExcelHighload' => 'classes/import/import_highload.php',
		'CKDAImportExtraSettings' => 'classes/import/extrasettings.php',
		'CKDAImportUtils' => 'classes/import/utils.php',
		'CKDAImportLogger' => 'classes/import/logger.php',
		//'CKDAImportMail' => 'classes/import/mail.php',
		'CKDAEEFieldList' => 'classes/export/field_list.php',
		'CKDAExportProfile' => 'classes/export/profile.php',
		'CKDAExportProfileAll' => 'classes/export/profile.php',
		'CKDAExportProfileDB' => 'classes/export/profile_db.php',
		'CKDAExportProfileFS' => 'classes/export/profile_fs.php',
		'CKDAExportExcel' => 'classes/export/export.php',
		'CKDAExportExcelStatic' => 'classes/export/export.php',
		'CKDAExportExcelHighload' => 'classes/export/export_highload.php',
		'CKDAExportExcelWriterXlsx' => 'classes/export/export_writer_xlsx.php',
		'CKDAExportExcelWriterCsv' => 'classes/export/export_writer_csv.php',
		'CKDAExportExcelWriterDbf' => 'classes/export/export_writer_dbf.php',
		'CKDAExportExtraSettings' => 'classes/export/extrasettings.php',
		'CKDAExportUtils' => 'classes/export/utils.php',
		'\Bitrix\KdaImportexcel\ProfileTable' => "lib/profile_import.php",
		'\Bitrix\KdaImportexcel\ProfileHlTable' => "lib/profile_import_hl.php",
		'\Bitrix\KdaImportexcel\ProfileElementTable' => "lib/profile_element.php",
		'\Bitrix\KdaImportexcel\ProfileElementHlTable' => "lib/profile_element_hl.php",
		'\Bitrix\KdaImportexcel\ProfileExecTable' => "lib/profile_exec.php",
		'\Bitrix\KdaImportexcel\ProfileExecStatTable' => "lib/profile_exec_stat.php",
		'\Bitrix\KdaImportexcel\Sftp' => "lib/sftp.php",
		'\Bitrix\KdaImportexcel\Conversion' => "lib/conversion.php",
		'\Bitrix\KdaImportexcel\Cloud' => "lib/cloud.php",
		'\Bitrix\KdaImportexcel\Cloud\MailRu' => "lib/cloud/mail_ru.php",
		'\Bitrix\KdaImportexcel\ZipArchive' => "lib/zip_archive.php",
		'\Bitrix\KdaImportexcel\Imap' => "lib/mail/imap.php",
		'\Bitrix\KdaImportexcel\SMail' => "lib/mail/mail.php",
		'\Bitrix\KdaImportexcel\MailHeader' => "lib/mail/mail_header.php",
		'\Bitrix\KdaImportexcel\MailMessage' => "lib/mail/mail_message.php",
		'\Bitrix\KdaImportexcel\MailUtil' => "lib/mail/mail_util.php",
		'\Bitrix\KdaImportexcel\DataManager\Discount' => "lib/datamanager/discount.php",
		'\Bitrix\KdaImportexcel\DataManager\DiscountProductTable' => "lib/datamanager/discount_product_table.php",
		'\Bitrix\KdaImportexcel\DataManager\Price' => "lib/datamanager/price.php",
		'\Bitrix\KdaImportexcel\DataManager\PriceD7' => "lib/datamanager/price_d7.php",
		'\Bitrix\KdaImportexcel\ClassManager' => "lib/class_manager.php",
		'\Bitrix\KdaImportexcel\Api' => "lib/api.php",
		'\Bitrix\KdaExportexcel\ProfileTable' => "lib/profile_export.php",
		'\Bitrix\KdaExportexcel\ProfileHlTable' => "lib/profile_export_hl.php"
	)
);

$initFile = $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/php_interface/include/'.$moduleId.'/init.php';
if(file_exists($initFile)) include_once($initFile);

$arJSEsolIBlockConfig = array(
	'esol_importexcel' => array(
		'js' => $pathJS.'/script_import.js',
		'css' => $pathCSS.'/import/styles.css',
		'rel' => array('jquery', 'esol_ieexcel_chosen'),
		'lang' => $pathLang.'/js_admin_import.php',
	),
	'esol_importexcel_highload' => array(
		'js' => $pathJS.'/script_import_highload.js',
		'css' => $pathCSS.'/import/styles.css',
		'rel' => array('jquery', 'esol_ieexcel_chosen'),
		'lang' => $pathLang.'/js_admin_import_hlbl.php',
	),
	'esol_exportexcel' => array(
		'js' => $pathJS.'/script_export.js',
		'css' => $pathCSS.'/export/styles.css',
		'rel' => array('jquery', 'esol_ieexcel_chosen'),
		'lang' => $pathLang.'/js_admin_export.php'
	),
	'esol_exportexcel_highload' => array(
		'js' => $pathJS.'/script_export_highload.js',
		'css' => $pathCSS.'/export/styles.css',
		'rel' => array('jquery', 'esol_ieexcel_chosen'),
		'lang' => $pathLang.'/js_admin_export_hlbl.php',
	),
	'esol_ieexcel_chosen' => array(
		'js' => $pathJS.'/chosen/chosen.jquery.min.js',
		'css' => $pathJS.'/chosen/chosen.min.css',
		'rel' => array('jquery')
	),
);

foreach ($arJSEsolIBlockConfig as $ext => $arExt) {
	CJSCore::RegisterExt($ext, $arExt);
}
?>