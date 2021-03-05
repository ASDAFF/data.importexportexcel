<?
/**
 * Copyright (c) 5/3/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!CModule::IncludeModule("iblock"))
	return false;

IncludeModuleLangFile(__FILE__);
$moduleId = 'data.importexportexcel';
$moduleIdUl = 'data_importexportexcel';

$aMenu = array();

global $USER;
$bUserIsAdmin = $USER->IsAdmin();

$bHasWRight = false;
$rsIBlocks = CIBlock::GetList(array("SORT"=>"asc", "NAME"=>"ASC"), array("MIN_PERMISSION" => "U"));
if($arIBlock = $rsIBlocks->Fetch())
{
	$bHasWRight = true;
}

if($APPLICATION->GetGroupRight($moduleId) < "W")
{
	$bHasWRight = false;
}

if($bUserIsAdmin || $bHasWRight)
{
	$aSubMenu = array();
	$aSubMenu[] = array(
		"text" => GetMessage("KDA_MENU_IMPORT_TITLE"),
		"url" => "data_import_excel.php?lang=".LANGUAGE_ID,
		"more_url" => array(
			"data_import_excel_profile_list.php", 
			"data_import_excel_rollback.php"
		),
		"title" => GetMessage("KDA_MENU_IMPORT_TITLE"),
		"module_id" => $moduleId,
		"items_id" => "menu_".$moduleIdUl,
		"sort" => 100,
		"section" => $moduleIdUl."_import",
	);
	
	if(CModule::IncludeModule('highloadblock'))
	{
		$aSubMenu[] = array(
			"text" => GetMessage("KDA_MENU_IMPORT_TITLE_HIGHLOAD"),
			"url" => "data_import_excel_highload.php?lang=".LANGUAGE_ID,
			"title" => GetMessage("KDA_MENU_IMPORT_TITLE_HIGHLOAD"),
			"module_id" => $moduleId,
			"items_id" => "menu_".$moduleIdUl."_highload",
			"sort" => 200,
			"section" => $moduleIdUl."_import",
		);			
	}
	
	$aSubMenu[] = array(
		"text" => GetMessage("KDA_MENU_IMPORT_TITLE_STAT"),
		"url" => "data_import_excel_event_log.php?lang=".LANGUAGE_ID,
		"title" => GetMessage("KDA_MENU_IMPORT_TITLE_STAT"),
		"module_id" => $moduleId,
		"items_id" => "menu_".$moduleIdUl,
		"sort" => 300,
		"section" => $moduleIdUl."_import",
	);
	
	$aMenu[] = array(
		"parent_menu" => "global_menu_content",
		"section" => $moduleIdUl."_import",
		"sort" => 1400,
		"text" => GetMessage("KDA_MENU_IMPORT_TITLE_PARENT"),
		"title" => GetMessage("KDA_MENU_IMPORT_TITLE_PARENT"),
		"icon" => "data_importexportexcel_menu_import_icon",
		"items_id" => "menu_".$moduleIdUl."_parent_import",
		"module_id" => $moduleId,
		"items" => $aSubMenu,
	);
	
	
	$aSubMenu = array();
	$aSubMenu[] = array(
		"text" => GetMessage("KDA_MENU_EXPORT_TITLE"),
		"url" => "data_export_excel.php?lang=".LANGUAGE_ID,
		"more_url" => array("data_export_excel_profile_list.php"),
		"title" => GetMessage("KDA_MENU_EXPORT_TITLE"),
		"module_id" => $moduleId,
		"items_id" => "menu_".$moduleIdUl,
		"sort" => 100,
		"section" => $moduleIdUl."_export",
	);
	
	if(CModule::IncludeModule('highloadblock'))
	{
		$aSubMenu[] = array(
			"text" => GetMessage("KDA_MENU_EXPORT_TITLE_HIGHLOAD"),
			"url" => "data_export_excel_highload.php?lang=".LANGUAGE_ID,
			"title" => GetMessage("KDA_MENU_EXPORT_TITLE_HIGHLOAD"),
			"module_id" => $moduleId,
			"items_id" => "menu_".$moduleIdUl."_highload",
			"sort" => 200,
			"section" => $moduleIdUl."_export",
		);			
	}
	
	$aMenu[] = array(
		"parent_menu" => "global_menu_content",
		"section" => $moduleIdUl."_export",
		"sort" => 1401,
		"text" => GetMessage("KDA_MENU_EXPORT_TITLE_PARENT"),
		"title" => GetMessage("KDA_MENU_EXPORT_TITLE_PARENT"),
		"icon" => "data_importexportexcel_menu_import_icon",
		"items_id" => "menu_".$moduleIdUl."_parent_export",
		"module_id" => $moduleId,
		"items" => $aSubMenu,
	);
}

return $aMenu;
?>