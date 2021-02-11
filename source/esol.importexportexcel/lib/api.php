<?php
namespace Bitrix\KdaImportexcel;

class Api
{
	public function __construct()
	{
	}
	
	public function RunImport($PROFILE_ID)
	{
		$oProfile = new \CKDAImportProfile();
		if(!$oProfile->ProfileExists($PROFILE_ID)) return false;
		$oProfile->UpdateFields($PROFILE_ID, array('NEED_RUN'=>'Y'));
		return true;
	}
	
	public function GetProfilesPool()
	{
		$oProfile = new \CKDAImportProfile();
		return $oProfile->GetProfilesCronPool();
	}
	
	public function DeleteProfileFromPool($PROFILE_ID)
	{
		$oProfile = new \CKDAImportProfile();
		if(!$oProfile->ProfileExists($PROFILE_ID)) return false;
		$oProfile->UpdateFields($PROFILE_ID, array('NEED_RUN'=>'N'));
		return true;
	}
}