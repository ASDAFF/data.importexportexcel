<?
IncludeModuleLangFile(__FILE__);

function esol_importexportexcel_demo_expired() {
	$DemoMode = CModule::IncludeModuleEx("esol.importexportexcel");
	if ($DemoMode==MODULE_DEMO) {
		$now=time();
		if (defined("esol_importexportexcel_OLDSITEEXPIREDATE")) {
			if ($now>=esol_importexportexcel_OLDSITEEXPIREDATE || esol_importexportexcel_OLDSITEEXPIREDATE>$now+3000000) {
				return true;
			}
		} else{ 
			return true;
		}
	} elseif ($DemoMode==MODULE_DEMO_EXPIRED) {
		return true;
	}
	return false;
}

function esol_importexportexcel_show_demo($bAjax = false) {
	$moduleId = 'esol.importexportexcel';
	$pathJS = '/bitrix/js/'.$moduleId;
	$pathCSS = '/bitrix/panel/'.$moduleId;
	$pathLang = BX_ROOT.'/modules/'.$moduleId.'/lang/'.LANGUAGE_ID;
	$arJSEsolDemoConfig = array(
		'esol_importexportexcel_demo' => array(
			'js' => array($pathJS.'/chosen/chosen.jquery.min.js', $pathJS.'/script_import.js'),
			'css' => array($pathJS.'/chosen/chosen.min.css', $pathCSS.'/import/styles.css'),
			'rel' => array('jquery'),
			'lang' => $pathLang.'/js_admin_import.php',
		),
	);
	foreach ($arJSEsolDemoConfig as $ext => $arExt) {
		\CJSCore::RegisterExt($ext, $arExt);
	}

	$DemoMode = CModule::IncludeModuleEx($moduleId);
	$activateText = GetMessage("ESOL_IMPORTEXPORTEXCEL_DEMO_MESSAGE_ACTIVATE_MODULE",array("#LANG#"=>LANGUAGE_ID));
	if ($DemoMode==MODULE_DEMO) {
		$now=time();
		if (defined("esol_importexportexcel_OLDSITEEXPIREDATE")) {
			if ($now<esol_importexportexcel_OLDSITEEXPIREDATE) {
				print BeginNote();
				$expire_arr = getdate(esol_importexportexcel_OLDSITEEXPIREDATE);
				$expire_date = gmmktime($expire_arr["hours"],$expire_arr["minutes"],$expire_arr["seconds"],$expire_arr["mon"],$expire_arr["mday"],$expire_arr["year"]);
				$now_arr = getdate($now);
				$now_date = gmmktime($expire_arr["hours"],$expire_arr["minutes"],$expire_arr["seconds"],$now_arr["mon"],$now_arr["mday"],$now_arr["year"]);
				$days = ($expire_date-$now_date)/86400; 
				print GetMessage("ESOL_IMPORTEXPORTEXCEL_DEMO_MESSAGE_DAYS_REMAIN",array("#DAYS#"=>$days, "#ACTIVATE#"=>$activateText));
				print EndNote();
			} else {
				\CJSCore::Init(array('esol_importexportexcel_demo'));
				print BeginNote();
				print GetMessage("ESOL_IMPORTEXPORTEXCEL_DEMO_MESSAGE_EXPIRED", array("#ACTIVATE#"=>$activateText));
				print EndNote();
			}
		} else{
			\CJSCore::Init(array('esol_importexportexcel_demo'));
			print BeginNote();
			print GetMessage("ESOL_IMPORTEXPORTEXCEL_DEMO_MESSAGE_EXPIRED", array("#ACTIVATE#"=>$activateText));
			print EndNote();
		}
	} elseif ($DemoMode==MODULE_DEMO_EXPIRED) {
		\CJSCore::Init(array('esol_importexportexcel_demo'));
		print BeginNote();
		print GetMessage("ESOL_IMPORTEXPORTEXCEL_DEMO_MESSAGE_EXPIRED", array("#ACTIVATE#"=>$activateText));
		print EndNote();
	}
	else
	{
		if(!$bAjax)
		{
			print BeginNote('id="kda-ie-updates-message"');
			print '<div id="kda-ie-updates-message-inner"></div>';
			print EndNote();
		}
		else
		{
			$updateEnd = false;
			
			$updateCache = \Bitrix\Main\Config\Option::get($moduleId, 'UPDATE_END', '');
			if(strlen($updateCache) > 0) $updateCache = unserialize($updateCache);
			if(!is_array($updateCache)) $updateCache = array();
			$ucp = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php";
			if(file_exists($ucp)) include_once($ucp);
			$logFile = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/updater_partner.log";
			if(file_exists($logFile)) $logSize = filesize($logFile);
			else $logSize = 0;
			if(!isset($updateCache['LOG_SIZE']) || $logSize!=$updateCache['LOG_SIZE'] || !isset($updateCache['TIME']) || $updateCache['TIME']<time()-24*60*60)
			{
				if(is_callable(array('CUpdateClientPartner', 'GetUpdatesList')))
				{
					$arUpdateList = CUpdateClientPartner::GetUpdatesList($errorMessage, LANG, 'Y', $moduleId, Array("fullmoduleinfo" => "Y"));
					
					if(is_array($arUpdateList['MODULE']))
					{
						foreach($arUpdateList['MODULE'] as $arModule)
						{
							if($arModule['@']['ID']==$moduleId)
							{
								$updateEnd = (bool)($arModule['@']['UPDATE_END']=='Y');
							}
						}
					}
				}
				if(file_exists($logFile)) $logSize = filesize($logFile);
				else $logSize = 0;
				$updateCache = array(
					'LOG_SIZE' => $logSize,
					'TIME' => time(),
					'UPDATE_END' => ($updateEnd ? 'Y' : 'N')
				);
				\Bitrix\Main\Config\Option::set($moduleId, 'UPDATE_END', serialize($updateCache));
			}
			elseif(isset($updateCache['UPDATE_END']))
			{
				$updateEnd = (bool)($updateCache['UPDATE_END']=='Y');
			}
			
			if($updateEnd && is_callable(array('CUpdateClientPartner', 'GetLicenseKey')))
			{
				$lckey = md5("BITRIX".CUpdateClientPartner::GetLicenseKey()."LICENCE");
				echo '<div id="kda-ie-updates-message-inner">'.GetMessage("ESOL_IMPORTEXPORTEXCEL_DEMO_MESSAGE_UPDATE_END", array("#LCKEY#"=>$lckey, "#LANG#"=>LANGUAGE_ID)).'</div>';
			}
		}
	}
	?><script>BX.ready(function(){BX.bind(BX('kda_module_activate'), 'click', function(){var div = BX.findNextSibling(this, {tag: 'DIV'}); BX.style(div, 'display', (BX.style(div, 'display')=='none' ? '' : 'none'));});});</script><?
}

?>