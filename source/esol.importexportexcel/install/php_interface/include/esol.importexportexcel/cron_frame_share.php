<?
$arProfiles = array_map('trim', explode(',', $argv[1]));
foreach($arProfiles as $profileId)
{
	if(strtolower(substr($profileId, 0, 2))=='ix')
	{
		$argv[1] = (int)substr($profileId, 2);
		$fn = dirname(__FILE__).'/../esol.importxml/cron_frame.php';
		if(file_exists($fn)) include($fn);
	}
	elseif(strtolower(substr($profileId, 0, 1))=='i')
	{
		$argv[1] = (int)substr($profileId, 1);
		include(dirname(__FILE__).'/cron_frame_import.php');
	}
	elseif(strtolower(substr($profileId, 0, 1))=='e')
	{
		$argv[1] = (int)substr($profileId, 1);
		include(dirname(__FILE__).'/cron_frame_export.php');
	}
}
?>