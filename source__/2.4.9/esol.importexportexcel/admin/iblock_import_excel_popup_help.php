<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.importexportexcel';
$moduleEmail = 'app@esolutions.su';
$imgPath = '/bitrix/panel/'.$moduleId.'/import/images/video_icons/';
CModule::IncludeModule($moduleId);
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>

<div class="kda-ie-tabs" id="kda-ie-help-tabs">
	<div class="kda-ie-tabs-heads">
		<a href="javascript:void(0)" onclick="EHelper.SetTab(this);" class="active" title="<?echo htmlspecialcharsex(GetMessage("KDA_IE_HELP_TAB1_ALT"));?>"><?echo GetMessage("KDA_IE_HELP_TAB1");?></a>
		<a href="javascript:void(0)" onclick="EHelper.SetTab(this);" title="<?echo htmlspecialcharsex(GetMessage("KDA_IE_HELP_TAB2_ALT"));?>"><?echo GetMessage("KDA_IE_HELP_TAB2");?></a>
	</div>
	<div class="kda-ie-tabs-bodies">
		<div class="active">
			<div>&nbsp;</div>
			<div class="kda-ie-video-list">
				<a href="https://www.youtube.com/watch?v=9-tvoyGDXoI" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_COMMON"));?>">
					<img src="<?echo $imgPath;?>common.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_COMMON"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_COMMON"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_COMMON");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=jfgaadkLQGU" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_ELEMENT_SECTIONS"));?>">
					<img src="<?echo $imgPath;?>element_sections.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_ELEMENT_SECTIONS"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_ELEMENT_SECTIONS"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_ELEMENT_SECTIONS");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=E1qywfQaQnE" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_SEP_LINES"));?>">
					<img src="<?echo $imgPath;?>sections_sep_lines.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_SEP_LINES"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_SEP_LINES"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_SEP_LINES");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=9WSIgK0dDus" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_WO_ELEMENTS"));?>">
					<img src="<?echo $imgPath;?>sections_wo_elements.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_WO_ELEMENTS"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_WO_ELEMENTS"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_SECTIONS_WO_ELEMENTS");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=uvw9iB4ZE4I" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMPORT_OFFERS"));?>">
					<img src="<?echo $imgPath;?>import_offers.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMPORT_OFFERS"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMPORT_OFFERS"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_IMPORT_OFFERS");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=yerNTHHFUDg" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMPORT_EXTERNAL_FIELDS"));?>">
					<img src="<?echo $imgPath;?>import_external_fields.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMPORT_EXTERNAL_FIELDS"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMPORT_EXTERNAL_FIELDS"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_IMPORT_EXTERNAL_FIELDS");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=DkYCpAIOqXI" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_DIFFERENT_PRODUCERS"));?>">
					<img src="<?echo $imgPath;?>different_producers.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_DIFFERENT_PRODUCERS"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_DIFFERENT_PRODUCERS"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_DIFFERENT_PRODUCERS");?></span>
				</a>
				<a href="http://www.youtube.com/watch?v=MPIjAEAYGxA" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PRICE_AND_QUANTITIES"));?>">
					<img src="<?echo $imgPath;?>price_and_quantities.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PRICE_AND_QUANTITIES"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PRICE_AND_QUANTITIES"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_PRICE_AND_QUANTITIES");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=UPXmYaalt2Q" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_MULTIPLE_PROPERTIES"));?>">
					<img src="<?echo $imgPath;?>multiple_properties.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_MULTIPLE_PROPERTIES"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_MULTIPLE_PROPERTIES"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_MULTIPLE_PROPERTIES");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=vkQQTlrJKN4" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMAGES"));?>">
					<img src="<?echo $imgPath;?>images_import.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMAGES"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_IMAGES"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_IMAGES");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=xtXaWUQAqos" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PROPERTIES_SEPARATE"));?>">
					<img src="<?echo $imgPath;?>props_separate.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PROPERTIES_SEPARATE"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PROPERTIES_SEPARATE"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_PROPERTIES_SEPARATE");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=wXh6QYU5QZA" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_EMAIL_FTP_CRON"));?>">
					<img src="<?echo $imgPath;?>email_ftp_cron.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_EMAIL_FTP_CRON"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_EMAIL_FTP_CRON"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_EMAIL_FTP_CRON");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=AkPAsxToi6o" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_MASS_PROP_CREATE"));?>">
					<img src="<?echo $imgPath;?>mass_prop_create.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_MASS_PROP_CREATE"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_MASS_PROP_CREATE"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_MASS_PROP_CREATE");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=M8LmvMd2RxA" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_CLOUD_SERVISES"));?>">
					<img src="<?echo $imgPath;?>cloud_services.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_CLOUD_SERVISES"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_CLOUD_SERVISES"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_CLOUD_SERVISES");?></span>
				</a>
				<a href="https://www.youtube.com/watch?v=_Q_xNPHgtBc" target="_blank" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PRICES_EXT"));?>">
					<img src="<?echo $imgPath;?>prices_ext.jpg" width="196px" height="110px" alt="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PRICES_EXT"));?>" title="<?echo htmlspecialcharsbx(GetMessage("KDA_IE_HELP_VIDEO_PRICES_EXT"));?>">
					<span><?echo GetMessage("KDA_IE_HELP_VIDEO_PRICES_EXT");?></span>
				</a>
			</div>
			<div>&nbsp;</div>
		</div>
		<div>
			<div>&nbsp;</div>
			<p class="kda-ie-help-faq-prolog"><i><?echo sprintf(GetMessage("KDA_IE_FAQ_PROLOG"), $moduleEmail, $moduleEmail);?></i></p>
			<ol id="kda-ie-help-faq">
				<li>
					<a href="#"><?echo GetMessage("KDA_IE_FAQ_QUEST_PICTURES");?></a>
					<div><?echo GetMessage("KDA_IE_FAQ_ANS_PICTURES");?></div>
				</li>
				<li>
					<a href="#"><?echo GetMessage("KDA_IE_FAQ_QUEST_SLOW_IMPORT");?></a>
					<div><?echo GetMessage("KDA_IE_FAQ_ANS_SLOW_IMPORT");?></div>
				</li>
				<li>
					<a href="#"><?echo GetMessage("KDA_IE_FAQ_QUEST_MULTI_PICTURES");?></a>
					<div><?echo GetMessage("KDA_IE_FAQ_ANS_MULTI_PICTURES");?></div>
				</li>
				<li>
					<a href="#"><?echo GetMessage("KDA_IE_FAQ_QUEST_MULTI_SECTIONS");?></a>
					<div><?echo GetMessage("KDA_IE_FAQ_ANS_MULTI_SECTIONS");?></div>
				</li>
				<li>
					<a href="#"><?echo GetMessage("KDA_IE_FAQ_QUEST_CRON");?></a>
					<div><?echo GetMessage("KDA_IE_FAQ_ANS_CRON");?></div>
				</li>
				<li>
					<a href="#"><?echo GetMessage("KDA_IE_FAQ_QUEST_BOOL");?></a>
					<div><?echo GetMessage("KDA_IE_FAQ_ANS_BOOL");?></div>
				</li>
			</ol>
			<div>&nbsp;</div>
		</div>
	</div>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>