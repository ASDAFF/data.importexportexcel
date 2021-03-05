<?php
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/prolog.php");
$moduleId = 'esol.importexportexcel';
$moduleFilePrefix = 'esol_import_excel';
$moduleJsId = 'esol_importexcel';
$moduleJsId2 = str_replace('.', '_', $moduleId);
$moduleDemoExpiredFunc = $moduleJsId2.'_demo_expired';
$moduleShowDemoFunc = $moduleJsId2.'_show_demo';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
CJSCore::Init(array($moduleJsId));
IncludeModuleLangFile(__FILE__);

include_once(dirname(__FILE__).'/../install/demo.php');
if ($moduleDemoExpiredFunc()) {
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	$moduleShowDemoFunc();
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

if(!$USER->CanDoOperation('view_event_log'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$oProfile = new CKDAImportProfile();
$arProfiles = $oProfile->GetList();
$logger = new CKDAImportLogger(false);

$sTableID = "tbl_kda_importexcel_view_stat";
$oSort = new CAdminSorting($sTableID, "ID", "DESC");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterFields = array(
	"find",
	"find_type",
	"find_exec_id",
	"find_timestamp_x_1",
	"find_timestamp_x_2",
	"find_profile_id",
	"find_item_id",
	"find_user_id",
);

$arFilter = array();
$lAdmin->InitFilter($arFilterFields);
InitSorting();

$find = $_REQUEST["find"];
$find_exec_id = $_REQUEST["find_exec_id"];
$find_type = $_REQUEST["find_type"];
$find_profile_id = $_REQUEST["find_profile_id"];
$find_timestamp_x_1 = $_REQUEST["find_timestamp_x_1"];
$find_timestamp_x_2 = $_REQUEST["find_timestamp_x_2"];
$find_item_id = $_REQUEST["find_item_id"];

if(strlen($find_profile_id) > 0) $arFilter['PROFILE_ID'] = $find_profile_id;
if(strlen($find_exec_id) > 0) $arFilter['PROFILE_EXEC_ID'] = $find_exec_id;
if(strlen($find_timestamp_x_1) > 0) $arFilter['>=DATE_EXEC'] = $find_timestamp_x_1;
if(strlen($find_timestamp_x_2) > 0) $arFilter['<=DATE_EXEC'] = $find_timestamp_x_2;
if(strlen($find_type) > 0) $arFilter['TYPE'] = $find_type;
if(strlen($find_item_id) > 0) $arFilter['ENTITY_ID'] = $find_item_id;
if(strlen($find_user_id) > 0) $arFilter['PROFILE_EXEC.RUNNED_BY'] = $find_user_id;


if(($arID = $lAdmin->GroupAction()))
{
	$removedCnt = 0;
	if($_REQUEST['action_target']=='selected')
	{
		$arID = Array();
		$dbResultList = \Bitrix\KdaImportexcel\ProfileExecStatTable::getList(array('filter'=>$arFilter, 'select'=>array('ID')));
		while($arResult = $dbResultList->Fetch())
			$arID[] = $arResult['ID'];
	}

	foreach ($arID as $ID)
	{
		if(strlen($ID) <= 0)
			continue;

		switch ($_REQUEST['action'])
		{
			case "delete":
				$dbRes = \Bitrix\KdaImportexcel\ProfileExecStatTable::delete($ID);
				if($dbRes->isSuccess())
				{
					$removedCnt++;
				}				
				else
				{
					$error = '';
					if($dbRes->getErrors())
					{
						foreach($dbRes->getErrors() as $errorObj)
						{
							$error .= $errorObj->getMessage().'. ';
						}
					}
					if($error)
						$lAdmin->AddGroupError($error, $ID);
					else
						$lAdmin->AddGroupError(GetMessage("KDA_IE_ERROR_DELETING_TYPE"), $ID);
				}
				break;
		}
	}
	
	if($removedCnt > 0)
	{
		$dbRes = \Bitrix\KdaImportexcel\ProfileExecTable::getList(array(
			'select' => array(
				'ID', 
				'PROFILE_EXEC_STAT_CNT'
			),
			'runtime' => array(
				'PROFILE_EXEC_STAT_CNT' => array(
					"data_type" => "integer",
					"expression" => array("COUNT(%s)", 'PROFILE_EXEC_STAT.ID')
				)
			),
			'filter' => array('PROFILE_EXEC_STAT_CNT'=>0)
		));
		while($arProfileExec = $dbRes->Fetch())
		{
			\Bitrix\KdaImportexcel\ProfileExecTable::delete($arProfileExec['ID']);
		}
	}
}

	
$usePageNavigation = true;
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'excel')
{
	$usePageNavigation = false;
}
else
{
	$navyParams = CDBResult::GetNavParams(CAdminResult::GetNavSize(
		$sTableID,
		array('nPageSize' => 20, 'sNavID' => $APPLICATION->GetCurPage())
	));
	if ($navyParams['SHOW_ALL'])
	{
		$usePageNavigation = false;
	}
	else
	{
		$navyParams['PAGEN'] = (int)$navyParams['PAGEN'];
		$navyParams['SIZEN'] = (int)$navyParams['SIZEN'];
	}
}

$getListParams = array(
	'order'=>array($by => $order), 
	'filter'=>$arFilter, 
	'select'=>array(
		'ID', 
		'DATE_EXEC', 
		'PROFILE_ID', 
		'PROFILE_NAME'=>'PROFILE.NAME', 
		'PROFILE_EXEC_ID', 
		'TYPE', 
		'ENTITY_ID', 
		'RUNNED_BY_USER'=>'PROFILE_EXEC.RUNNED_BY_USER.LOGIN', 
		'RUNNED_BY_USER_ID'=>'PROFILE_EXEC.RUNNED_BY_USER.ID', 
		'FIELDS',
		'IBLOCK_ELEMENT_ID'=>'IBLOCK_ELEMENT.ID',
		'IBLOCK_ELEMENT_NAME'=>'IBLOCK_ELEMENT.NAME',
		'IBLOCK_ELEMENT_IBLOCK_ID'=>'IBLOCK_ELEMENT.IBLOCK_ID',
		'IBLOCK_ELEMENT_IBLOCK_TYPE_ID'=>'IBLOCK_ELEMENT.IBLOCK.IBLOCK_TYPE_ID',
		'IBLOCK_SECTION_ID'=>'IBLOCK_SECTION.ID',
		'IBLOCK_SECTION_NAME'=>'IBLOCK_SECTION.NAME',
		'IBLOCK_SECTION_IBLOCK_ID'=>'IBLOCK_SECTION.IBLOCK_ID',
		'IBLOCK_SECTION_IBLOCK_TYPE_ID'=>'IBLOCK_SECTION.IBLOCK.IBLOCK_TYPE_ID',
	)
);

if ($usePageNavigation)
{
	$getListParams['limit'] = $navyParams['SIZEN'];
	$getListParams['offset'] = $navyParams['SIZEN']*($navyParams['PAGEN']-1);
}

if ($usePageNavigation)
{
	$countQuery = new Query(\Bitrix\KdaImportexcel\ProfileExecStatTable::getEntity());
	$countQuery->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
	$countQuery->setFilter($getListParams['filter']);
	$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
	unset($countQuery);
	$totalCount = (int)$totalCount['CNT'];
	if ($totalCount > 0)
	{
		$totalPages = ceil($totalCount/$navyParams['SIZEN']);
		if ($navyParams['PAGEN'] > $totalPages)
			$navyParams['PAGEN'] = $totalPages;
		$getListParams['limit'] = $navyParams['SIZEN'];
		$getListParams['offset'] = $navyParams['SIZEN']*($navyParams['PAGEN']-1);
	}
	else
	{
		$navyParams['PAGEN'] = 1;
		$getListParams['limit'] = $navyParams['SIZEN'];
		$getListParams['offset'] = 0;
	}
}
$rsData = new CAdminResult(\Bitrix\KdaImportexcel\ProfileExecStatTable::getList($getListParams), $sTableID);
if ($usePageNavigation)
{
	$rsData->NavStart($getListParams['limit'], $navyParams['SHOW_ALL'], $navyParams['PAGEN']);
	$rsData->NavRecordCount = $totalCount;
	$rsData->NavPageCount = $totalPages;
	$rsData->NavPageNomer = $navyParams['PAGEN'];
}
else
{
	$rsData->NavStart();
}

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("KDA_IE_EVENTLOG_LIST_PAGE")));

$arHeaders = array(
	array(
		"id" => "ID",
		"content" => GetMessage("KDA_IE_EVENTLOG_ID"),
		"sort" => "ID",
		"default" => true,
		"align" => "right",
	),
	array(
		"id" => "DATE_EXEC",
		"content" => GetMessage("KDA_IE_EVENTLOG_TIMESTAMP_X"),
		"sort" => "DATE_EXEC",
		"default" => true,
		"align" => "right",
	),
	array(
		"id" => "PROFILE_ID",
		"content" => GetMessage("KDA_IE_EVENTLOG_PROFILE_ID"),
		"default" => true,
	),
	array(
		"id" => "PROFILE_EXEC_ID",
		"content" => GetMessage("KDA_IE_EVENTLOG_PROFILE_EXEC_ID"),
		"default" => true,
	),
	array(
		"id" => "TYPE",
		"content" => GetMessage("KDA_IE_EVENTLOG_TYPE"),
		"default" => true,
	),
	array(
		"id" => "ENTITY_ID",
		"content" => GetMessage("KDA_IE_EVENTLOG_ITEM_ID"),
		"default" => true,
	),
	array(
		"id" => "RUNNED_BY",
		"content" => GetMessage("KDA_IE_EVENTLOG_USER_ID"),
		"default" => true,
	),
	array(
		"id" => "FIELDS",
		"content" => GetMessage("KDA_IE_EVENTLOG_DESCRIPTION"),
		"default" => true,
	),
);

$lAdmin->AddHeaders($arHeaders);

$arUsersCache = array();
$arGroupsCache = array();
$arForumCache = array("FORUM" => array(), "TOPIC" => array(), "MESSAGE" => array());
$a_ID = $a_DATE_EXEC = $a_PROFILE_NAME = $a_PROFILE_EXEC_ID = $a_TYPE = $a_ENTITY_ID = $a_RUNNED_BY_USER_ID = $a_RUNNED_BY_USER = $a_FIELDS = '';
while($db_res = $rsData->NavNext(true, "a_"))
{
	$row =& $lAdmin->AddRow($a_ID, $db_res);
	
	$elink = '';
	if($a_TYPE)
	{
		if($a_TYPE=='ELEMENT_NOT_FOUND')
		{
			$a_FIELDS = '<b>'.GetMessage("KDA_IE_EVENTLOG_FILTER_FIELDS").'</b>'.$logger->GetElementDescriptionArray($a_FIELDS);
		}
		elseif(strpos($a_TYPE, 'ELEMENT_')===0 && $a_ENTITY_ID > 0)
		{
			if($a_IBLOCK_ELEMENT_ID)
			{
				$elink = '[<a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$a_IBLOCK_ELEMENT_IBLOCK_ID.'&type='.$a_IBLOCK_ELEMENT_IBLOCK_TYPE_ID.'&ID='.$a_IBLOCK_ELEMENT_ID.'&lang='.LANGUAGE_ID.'">'.$a_IBLOCK_ELEMENT_ID.'</a>] '.$a_IBLOCK_ELEMENT_NAME;
			}
			
			if(strlen($a_FIELDS))
			{
				$a_FIELDS = $logger->GetElementDescription($a_FIELDS);
			}
		}
		elseif(strpos($a_TYPE, 'SECTION_')===0 && $a_ENTITY_ID > 0)
		{
			if($a_IBLOCK_SECTION_ID)
			{
				$elink = '[<a href="/bitrix/admin/iblock_section_edit.php?IBLOCK_ID='.$a_IBLOCK_SECTION_IBLOCK_ID.'&type='.$a_IBLOCK_SECTION_IBLOCK_TYPE_ID.'&ID='.$a_IBLOCK_SECTION_ID.'&lang='.LANGUAGE_ID.'">'.$a_IBLOCK_SECTION_ID.'</a>] '.$a_IBLOCK_SECTION_NAME;
			}
			
			if(strlen($a_FIELDS))
			{
				$a_FIELDS = $logger->GetSectionDescription($a_FIELDS, $a_IBLOCK_SECTION_IBLOCK_ID);
			}
		}
	}
	
	$row->AddViewField("DATE_EXEC", $a_DATE_EXEC);
	$row->AddViewField("PROFILE_ID", '<a href="/bitrix/admin/'.$moduleFilePrefix.'.php?lang=ru&PROFILE_ID='.($a_PROFILE_ID - 1).'">'.$a_PROFILE_NAME.'</a>');
	$row->AddViewField("PROFILE_EXEC_ID", $a_PROFILE_EXEC_ID);
	$row->AddViewField("TYPE", GetMessage("KDA_IE_EVENTLOG_IBLOCK_".$a_TYPE));
	$row->AddViewField("ENTITY_ID", $elink);
	$row->AddViewField("RUNNED_BY", ($a_RUNNED_BY_USER_ID ? '[<a href="user_edit.php?lang='.LANG.'&ID='.$a_RUNNED_BY_USER_ID.'">'.$a_RUNNED_BY_USER_ID.'</a>] '.$a_RUNNED_BY_USER : ''));
	$row->AddViewField("FIELDS", '');
	if(strlen($a_FIELDS))
	{
		if(strncmp("==", $a_FIELDS, 2)===0)
			$FIELDS = htmlspecialcharsbx(base64_decode(substr($a_FIELDS, 2)));
		else
			$FIELDS = $a_FIELDS;
		//htmlspecialcharsback for <br> <BR> <br/>
		$FIELDS = preg_replace("#(&lt;)(\\s*br\\s*/{0,1})(&gt;)#is", "<\\2>", $FIELDS);
		$row->AddViewField("FIELDS", $FIELDS);
	}
	else
	{
		$row->AddViewField("FIELDS", '');
	}
	
	$arActions = array();
	$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("KDA_IE_LOG_RECORD_DELETE"), "ACTION"=>"if(confirm('".GetMessageJS('KDA_IE_LOG_RECORD_DELETE_CONFIRM')."')) ".$lAdmin->ActionDoGroup($a_ID, "delete"));

	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array(
			"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $rsData->SelectedRowsCount()
		),
		array(
			"counter" => true,
			"title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value" => "0"
		),
	)
);

$lAdmin->AddGroupActionTable(
	array(
		"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
	)
);


$aContext = array();
$lAdmin->AddAdminContextMenu($aContext);

$APPLICATION->SetTitle(GetMessage("KDA_IE_EVENTLOG_PAGE_TITLE"));
$lAdmin->CheckListMode();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

if (!$moduleDemoExpiredFunc()) {
	$moduleShowDemoFunc();
}
?>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<input type="hidden" name="lang" value="<?echo LANG?>">
<?
$arFilterNames = array(
	"find_exec_id" => GetMessage("KDA_IE_EVENTLOG_PROFILE_EXEC_ID"),
	"find_timestamp_x" => GetMessage("KDA_IE_EVENTLOG_TIMESTAMP_X"),
	"find_type" => GetMessage("KDA_IE_EVENTLOG_TYPE"),
	"find_item_id" => GetMessage("KDA_IE_EVENTLOG_ITEM_ID"),
	"find_user_id" => GetMessage("KDA_IE_EVENTLOG_USER_ID"),
);

$oFilter = new CAdminFilter($sTableID."_filter", $arFilterNames);
$oFilter->Begin();
?>
<tr>
	<td><?echo GetMessage("KDA_IE_EVENTLOG_PROFILE_ID")?>:</td>
	<td>
		<select name="find_profile_id" >
			<option value=""><?echo GetMessage("KDA_IE_ALL"); ?></option>
			<?
			foreach($arProfiles as $k=>$profile)
			{
				$key = $k + 1;
				?><option value="<?echo $key;?>" <?if($find_profile_id==$key){echo 'selected';}?>><?echo $profile; ?></option><?
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td><?echo GetMessage("KDA_IE_EVENTLOG_PROFILE_EXEC_ID")?>:</td>
	<td><input type="text" name="find_exec_id" size="47" value="<?echo htmlspecialcharsbx($find_exec_id)?>"></td>
</tr>
<tr>
	<td><?echo GetMessage("KDA_IE_EVENTLOG_TIMESTAMP_X")?>:</td>
	<td><?echo CAdminCalendar::CalendarPeriod("find_timestamp_x_1", "find_timestamp_x_2", $find_timestamp_x_1, $find_timestamp_x_2, false, 15, true)?></td>
</tr>
<?
$arSiteDropdown = array("reference" => array(
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_ELEMENT_ADD"),
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_ELEMENT_UPDATE"),
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_ELEMENT_DELETE"),
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_ELEMENT_NOT_FOUND"),
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_SECTION_ADD"),
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_SECTION_UPDATE"),
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_SECTION_DELETE"),
	GetMessage("KDA_IE_EVENTLOG_IBLOCK_SECTION_NOT_FOUND")
), "reference_id" => array(
	'ELEMENT_ADD',
	'ELEMENT_UPDATE',
	'ELEMENT_DELETE',
	'ELEMENT_NOT_FOUND',
	'SECTION_ADD',
	'SECTION_UPDATE',
	'SECTION_DELETE',
	'SECTION_NOT_FOUND'
));
?>
<tr>
	<td><?echo GetMessage("KDA_IE_EVENTLOG_TYPE")?>:</td>
	<td><?echo SelectBoxFromArray("find_type", $arSiteDropdown, $find_type, GetMessage("KDA_IE_ALL"), "");?></td>
</tr>
<tr>
	<td><?echo GetMessage("KDA_IE_EVENTLOG_ITEM_ID")?>:</td>
	<td><input type="text" name="find_item_id" size="47" value="<?echo htmlspecialcharsbx($find_item_id)?>"></td>
</tr>
<tr>
	<td><?echo GetMessage("KDA_IE_EVENTLOG_USER_ID")?>:</td>
	<td><input type="text" name="find_user_id" size="47" value="<?echo htmlspecialcharsbx($find_user_id)?>"></td>
</tr>
<?
$oFilter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"find_form"));
$oFilter->End();
?>
</form>
<?

$lAdmin->DisplayList();

echo BeginNote();
echo GetMessage("KDA_IE_EVENTLOG_BOTTOM_NOTE");
echo EndNote();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
