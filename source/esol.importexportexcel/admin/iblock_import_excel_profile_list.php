<?
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$moduleId = 'esol.importexportexcel';
$moduleFilePrefix = 'esol_import_excel';
$moduleJsId = 'esol_importexcel';
$moduleJsId2 = str_replace('.', '_', $moduleId);
$moduleDemoExpiredFunc = $moduleJsId2.'_demo_expired';
$moduleShowDemoFunc = $moduleJsId2.'_show_demo';
CModule::IncludeModule($moduleId);
CJSCore::Init(array('fileinput', $moduleJsId));
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);

include_once(dirname(__FILE__).'/../install/demo.php');
if ($moduleDemoExpiredFunc()) {
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	$moduleShowDemoFunc();
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$oProfile = new CKDAImportProfile();
$sTableID = "tbl_kdaimportexcel_profile";
$instance = \Bitrix\Main\Application::getInstance();
$context = $instance->getContext();
$request = $context->getRequest();

if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'export')
{
	$oProfile->OutputBackup();
}

$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterFields = array(
	"filter_name"
);

$lAdmin->InitFilter($arFilterFields);

$filter = array();

if (strlen($filter_name) > 0)
	$filter["%NAME"] = trim($filter_name);

if($lAdmin->EditAction())
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$ID = (int)$ID;

		if ($ID <= 0 || !$lAdmin->IsUpdated($ID))
			continue;
		
		$dbRes = \Bitrix\KdaImportexcel\ProfileTable::update($ID, $arFields);
		if(!$dbRes->isSuccess())
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
				$lAdmin->AddUpdateError($error, $ID);
			else
				$lAdmin->AddUpdateError(GetMessage("KDA_IE_ERROR_UPDATING_REC")." (".$arFields["ID"].", ".$arFields["NAME"].", ".$arFields["SORT"].")", $ID);
		}
	}
}

if(($arID = $lAdmin->GroupAction()))
{
	if($_REQUEST['action_target']=='selected')
	{
		$arID = Array();
		$dbResultList = \Bitrix\KdaImportexcel\ProfileTable::getList(array('filter'=>$filter, 'select'=>array('ID')));
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
				$dbRes = \Bitrix\KdaImportexcel\ProfileTable::delete($ID);
				if(!$dbRes->isSuccess())
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
}

$usePageNavigation = true;
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

$getListParams = array(
	'select' => array(
		'ID', 
		'ACTIVE', 
		'NAME', 
		'DATE_START', 
		'DATE_FINISH', 
		'SORT',
		'PROFILE_EXEC_ID'
	),
	'runtime' => array(
		'PROFILE_EXEC_ID' => array(
			"data_type" => "integer",
			"expression" => array("MAX(%s)", 'PROFILE_EXEC.ID')
		)
	),
	'filter' => $filter
);

if ($usePageNavigation)
{
	$getListParams['limit'] = $navyParams['SIZEN'];
	$getListParams['offset'] = $navyParams['SIZEN']*($navyParams['PAGEN']-1);
}

if ($usePageNavigation)
{
	$countQuery = new Query(\Bitrix\KdaImportexcel\ProfileTable::getEntity());
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

$getListParams['order'] = array(ToUpper($by) => ToUpper($order));

$rsData = new CAdminResult(\Bitrix\KdaImportexcel\ProfileTable::getList($getListParams), $sTableID);
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

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("KDA_IE_PROFILE_LIST")));

$lAdmin->AddHeaders(array(
	array("id"=>"ID", "content"=>"ID", 	"sort"=>"ID", "default"=>true),
	array("id"=>"ACTIVE", "content"=>GetMessage("KDA_IE_PL_ACTIVE"), "sort"=>"ACTIVE", "default"=>true),
	array("id"=>"NAME", "content"=>GetMessage("KDA_IE_PL_NAME"), "sort"=>"NAME", "default"=>true),
	array("id"=>"DATE_START", "content"=>GetMessage("KDA_IE_PL_DATE_START"), "sort"=>"DATE_START", "default"=>true),
	array("id"=>"DATE_FINISH", "content"=>GetMessage("KDA_IE_PL_DATE_FINISH"), "sort"=>"DATE_FINISH", "default"=>true),
	array("id"=>"SORT", "content"=>GetMessage("KDA_IE_PL_SORT"), "sort"=>"SORT", "default"=>true),
));

$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();

while($arProfile = $rsData->NavNext(true, "f_"))
{
	$arProfile['ID'] = $f_ID = $f_ID - 1;
	$row =& $lAdmin->AddRow(($f_ID+1), $arProfile, $moduleFilePrefix.".php?PROFILE_ID=".$f_ID."&lang=".LANG, GetMessage("KDA_IE_TO_PROFILE"));

	$row->AddField("ID", "<a href=\"".$moduleFilePrefix.".php?PROFILE_ID=".$f_ID."&lang=".LANG."\">".$f_ID."</a>");
	$row->AddCheckField("ACTIVE", $f_ACTIVE);
	$row->AddInputField("NAME", $f_NAME);
	$row->AddInputField("SORT", $f_SORT);
	$row->AddField("DATE_START", $f_DATE_START);
	$row->AddField("DATE_FINISH", $f_DATE_FINISH);
	
	$arActions = array();
	$arActions[] = array("ICON"=>"view", "TEXT"=>GetMessage("KDA_IE_TO_PROFILE_ACT"), "ACTION"=>$lAdmin->ActionRedirect($moduleFilePrefix.".php?PROFILE_ID=".$f_ID."&lang=".LANG), "DEFAULT"=>true);
	if($f_PROFILE_EXEC_ID > 0)
	{
		$arActions[] = array("ICON"=>"move", "TEXT"=>GetMessage("KDA_IE_RESTORE_ACT"), "ACTION"=>$lAdmin->ActionRedirect($moduleFilePrefix."_rollback.php?PROFILE_ID=".$f_ID."&lang=".LANG));
	}

	$arActions[] = array("SEPARATOR" => true);
	$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("KDA_IE_PROFILE_DELETE"), "ACTION"=>"if(confirm('".GetMessageJS('KDA_IE_PROFILE_DELETE_CONFIRM')."')) ".$lAdmin->ActionDoGroup(($f_ID+1), "delete"));

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

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("KDA_IE_PROFILE_LIST_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if (!$moduleDemoExpiredFunc()) {
	$moduleShowDemoFunc();
}

$aMenu = array(
	array(
		"TEXT" => GetMessage("KDA_IE_BACK_TO_IMPORT"),
		"ICON" => "btn_list",
		"LINK" => "/bitrix/admin/".$moduleFilePrefix.".php?lang=".LANG
	)
);

if($oProfile instanceof CKDAImportProfileDB)
{
	$aMenu[] = array(
		"TEXT"=>GetMessage("KDA_IE_MENU_EXPORT_IMPORT_PROFILES"),
		"TITLE"=>GetMessage("KDA_IE_MENU_EXPORT_IMPORT_PROFILES"),
		"MENU" => array(
			array(
				"TEXT" => GetMessage("KDA_IE_MENU_EXPORT_PROFILES"),
				"TITLE" => GetMessage("KDA_IE_MENU_EXPORT_PROFILES"),
				"LINK" => "/bitrix/admin/".$moduleFilePrefix."_profile_list.php?mode=export"
			),
			array(
				"TEXT" => GetMessage("KDA_IE_MENU_IMPORT_PROFILES"),
				"TITLE" => GetMessage("KDA_IE_MENU_IMPORT_PROFILES"),
				"ONCLICK" => "EProfileList.ShowRestoreWindow();"
			)
		),
		"ICON" => "btn_green",
	);
}

$context = new CAdminContextMenu($aMenu);
$context->Show();
?>

<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?
$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array(
		GetMessage("SALE_F_PERSON_TYPE"),
	)
);

$oFilter->Begin();
?>
	<tr>
		<td><?echo GetMessage("KDA_IE_F_NAME")?>:</td>
		<td>
			<input type="text" name="filter_name" value="<?echo htmlspecialcharsex($filter_name)?>">
		</td>
	</tr>
<?
$oFilter->Buttons(
	array(
		"table_id" => $sTableID,
		"url" => $APPLICATION->GetCurPage(),
		"form" => "find_form"
	)
);
$oFilter->End();
?>
</form>

<?
$lAdmin->DisplayList();
require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
?>
