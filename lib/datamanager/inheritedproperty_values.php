<?php
namespace Bitrix\KdaImportexcel\DataManager;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class InterhitedpropertyValues
{	
	public static function ClearSectionValues($IBLOCK_ID, $SECTION_ID, $arSection)
	{
		$SECTION_ID = (int)$SECTION_ID;
		if($SECTION_ID <= 0) return;
		
		$arTemplates = (isset($arSection['IPROPERTY_TEMPLATES']) ? $arSection['IPROPERTY_TEMPLATES'] : array());
		if(!is_array($arTemplates)) $arTemplates = array();
		$isElementFields = (bool)(count(preg_grep('/^ELEMENT_/', array_keys($arTemplates))) > 0);
		
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		
		$tblName = '';
		if(is_callable('\Bitrix\Iblock\InheritedProperty\SectionValues', 'getValueTableName')) $tblName = \Bitrix\Iblock\InheritedProperty\SectionValues::getValueTableName();
		
		if($connection->getType()=='mysql' && strlen($tblName) > 0 && !$isElementFields)
		{
			$dbRes = \Bitrix\Iblock\SectionTable::GetList(array(
				'filter'=>array('ID'=>$SECTION_ID),
				'runtime' => array(new \Bitrix\Main\Entity\ReferenceField(
					'SECTION2',
					'\Bitrix\Iblock\SectionTable',
					array(
						'<=this.LEFT_MARGIN' => 'ref.LEFT_MARGIN',
						'>=this.RIGHT_MARGIN' => 'ref.RIGHT_MARGIN',
						'this.IBLOCK_ID' => 'ref.IBLOCK_ID'
					)
				)), 
				'select'=>array('SID'=>'SECTION2.ID'), 
				/*'order'=>array('SECTION2.DEPTH_LEVEL'=>'ASC')*/
			));
			$arSectionIds = array();
			while($arr = $dbRes->Fetch())
			{
				$arSectionIds[] = (int)$arr['SID'];
			}
			if(count($arSectionIds) > 0)
			{
				$sql = "DELETE FROM ".$helper->quote($tblName)." WHERE ".$helper->quote('SECTION_ID')." IN (".implode(',', $arSectionIds).")";
				$connection->queryExecute($sql);
				if(class_exists('\Bitrix\Iblock\InheritedProperty\ValuesQueue') && is_callable('\Bitrix\Iblock\InheritedProperty\ValuesQueue', 'deleteAll')) \Bitrix\Iblock\InheritedProperty\ValuesQueue::deleteAll();
			}
		}
		else
		{
			$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($IBLOCK_ID, $SECTION_ID);
			$ipropValues->clearValues();
		}
	}
	
	public static function ClearElementValues($IBLOCK_ID, $ELEMENT_ID)
	{
		$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($IBLOCK_ID, $ELEMENT_ID);
		$ipropValues->clearValues();
	}
}