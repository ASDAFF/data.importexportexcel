<?php
namespace Bitrix\KdaImportexcel\DataManager;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

/*
CREATE INDEX `ix_iblock_element_prop_val` ON b_iblock_element_property (`VALUE`(255),`IBLOCK_PROPERTY_ID`)
*/

class ElementPropertyTable extends Entity\DataManager
{
	protected static $isValIndex = null;
	
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		if(is_callable(array('\Bitrix\Iblock\ORM\ElementV1Entity', 'getSingleValueTableName')))
		{
			return \Bitrix\Iblock\ORM\ElementV1Entity::getSingleValueTableName();
		}
		return 'b_iblock_element_property';
	}
	
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'IBLOCK_PROPERTY_ID' => new Entity\IntegerField('IBLOCK_PROPERTY_ID', array()),
			'IBLOCK_ELEMENT_ID' => new Entity\IntegerField('IBLOCK_ELEMENT_ID', array()),
			'VALUE' => new Entity\TextField('VALUE', array(
				'default_value' => ''
			)),
			'VALUE_TYPE' => new Entity\StringField('VALUE_TYPE', array()),
			'VALUE_ENUM' => new Entity\IntegerField('VALUE_ENUM', array()),
			'VALUE_NUM' => new Entity\FloatField('VALUE_NUM', array()),
			'DESCRIPTION' => new Entity\StringField('DESCRIPTION', array()),
			'SP' => new Entity\ReferenceField(
				'SP',
				'\Bitrix\KdaImportexcel\DataManager\ElementPropertyTable',
				array('=this.IBLOCK_ELEMENT_ID' => 'ref.IBLOCK_ELEMENT_ID'),
				array('join_type' => 'LEFT')
			),
			'PROP_ENUM_VAL' => new Entity\ReferenceField(
				'PROP_ENUM_VAL',
				'\Bitrix\Iblock\PropertyEnumerationTable',
				array(
					'=this.VALUE_ENUM' => 'ref.ID',
					'=this.IBLOCK_PROPERTY_ID' => 'ref.PROPERTY_ID'
				),
				array('join_type' => 'LEFT')
			),
		);
	}
	
	public static function getMapForIblock($iblockId)
	{
		$arMap = self::getMap();
		
		$dbRes = \CIBlockProperty::GetList(array(), array('IBLOCK_ID'=>$iblockId));
		while($arProp = $dbRes->Fetch())
		{
			$propId = $arProp['ID'];
			$arMap['P'.$propId] = new Entity\ReferenceField(
				'P'.$propId,
				'\Bitrix\KdaImportexcel\DataManager\ElementPropertyTable',
				array(
					'=this.IBLOCK_ELEMENT_ID' => 'ref.IBLOCK_ELEMENT_ID',
					'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $propId)
				),
				array('join_type' => 'LEFT')
			);
		}
		return $arMap;
	}
	
	public static function issetValueIndex()
	{
		if(!isset(self::$isValIndex))
		{
			$conn = \Bitrix\Main\Application::getConnection();
			$helper = $conn->getSqlHelper();
			$row = $conn->query("SHOW CREATE TABLE ".$helper->quote(self::getTableName()))->Fetch();
			$createTable = $row['Create Table'];
			$pattern = '/KEY\s*'.$helper->quote('\S+').'\s*\(\s*('.$helper->quote('VALUE').'\s*\(\d+\)\s*,\s*'.$helper->quote('IBLOCK_PROPERTY_ID').'|'.$helper->quote('IBLOCK_PROPERTY_ID').'\s*,\s*'.$helper->quote('VALUE').'\s*\(\d+\))\s*\)/Uis';
			self::$isValIndex = (bool)(preg_match($pattern, $createTable));
		}
		return self::$isValIndex;
	}
}