<?
@set_time_limit(0);
if(!defined('NOT_CHECK_PERMISSIONS')) define('NOT_CHECK_PERMISSIONS', true);
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
if(!defined('BX_CRONTAB')) define("BX_CRONTAB", true);
if(!defined('ADMIN_SECTION')) define("ADMIN_SECTION", true);
if(!ini_get('date.timezone') && function_exists('date_default_timezone_set')){@date_default_timezone_set("Europe/Moscow");}
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__).'/../../../..');
if(!array_key_exists('REQUEST_URI', $_SERVER)) $_SERVER["REQUEST_URI"] = substr(__FILE__, strlen($_SERVER["DOCUMENT_ROOT"]));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
@set_time_limit(0);

$module_id = 'esol.importexportexcel';
\Bitrix\Main\Loader::includeModule($module_id);

$api = new \Bitrix\KdaImportexcel\Api;
$arProfiles = $api->GetProfilesPool();
if(!is_array($arProfiles) || count($arProfiles)==0) die();
$PROFILE_ID = current($arProfiles);
$api->DeleteProfileFromPool($PROFILE_ID);
$argv[1] = $PROFILE_ID;
include(dirname(__FILE__).'/cron_frame.php');
?>