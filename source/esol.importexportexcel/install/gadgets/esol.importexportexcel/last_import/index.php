<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$moduleFilePrefix = 'esol_import_excel';
$moduleId = 'esol.importexportexcel';
if(!Loader::includeModule($moduleId)) return;

$arGadgetParams["PROFILES_SHOW_INACTIVE"] = ($arGadgetParams["PROFILES_SHOW_INACTIVE"]=='Y' ? 'Y' : 'N');
$arGadgetParams["PROFILES_COUNT"] = (int)$arGadgetParams["PROFILES_COUNT"];
if ($arGadgetParams["PROFILES_COUNT"] <= 0)
	$arGadgetParams["PROFILES_COUNT"] = 10;

$oProfile = new \CKDAImportProfile();
$arProfiles = $oProfile->GetLastImportProfiles($arGadgetParams);
if(!empty($arProfiles))
{
	echo '<table border="1">'.
		'<tr>'.
			'<th>'.Loc::getMessage('GD_KDA_IE_PROFILE_ID').'</th>'.
			'<th>'.Loc::getMessage('GD_KDA_IE_PROFILE_NAME').'</th>'.
			'<th>'.Loc::getMessage('GD_KDA_IE_PROFILE_DATE_START').'</th>'.
			'<th>'.Loc::getMessage('GD_KDA_IE_PROFILE_DATE_FINISH').'</th>'.
			'<th>'.Loc::getMessage('GD_KDA_IE_PROFILE_STATUS').'</th>'.
			'<th></th>'.
		'</tr>';
	foreach($arProfiles as $arProfile)
	{
		$arStatus = $oProfile->GetStatus($arProfile, true);
		echo '<tr'.($arStatus['STATUS']=='ERROR' ? ' style="background: #ffdddd;"' : '').'>'.
				'<td>'.$arProfile['ID'].'</td>'.
				'<td><a href="/bitrix/admin/'.$moduleFilePrefix.'.php?lang='.LANGUAGE_ID.'&PROFILE_ID='.$arProfile['ID'].'" target="_blank">'.$arProfile['NAME'].'</a></td>'.
				'<td>'.(is_callable(array($arProfile['DATE_START'], 'toString')) ? $arProfile['DATE_START']->toString() : '').'</td>'.
				'<td>'.(is_callable(array($arProfile['DATE_FINISH'], 'toString')) ? $arProfile['DATE_FINISH']->toString() : '').'</td>'.
				'<td>'.$arStatus['MESSAGE'].'</td>'.
				'<td>'.
					'<form method="post" action="/bitrix/admin/'.$moduleFilePrefix.'.php?lang='.LANGUAGE_ID.'" target="_blank">'.
						'<input type="hidden" name="PROFILE_ID" value="'.$arProfile['ID'].'">'.
						'<input type="hidden" name="STEP" value="3">'.
						'<input type="hidden" name="PROCESS_CONTINUE" value="Y">'.
						'<input type="hidden" name="sessid" value="'.bitrix_sessid().'">'.
						'<input type="submit" value="'.Loc::getMessage('GD_KDA_IE_RUN_IMPORT').'">'.
					'</form>'.
				'</td>'.
			'</tr>';
	}
	echo '</table>';
}
else
{
	echo Loc::getMessage('GD_KDA_IE_NO_DATA');
}
?>


