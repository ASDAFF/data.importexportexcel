<?php
namespace Bitrix\KdaImportexcel\DataManager;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class IblockElementIdTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TMP_ID int mandatory
 * </ul>
 *
 * @package Bitrix\KdaImportexcel
 **/

class IblockElementIdTable extends Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return \Bitrix\Iblock\ElementTable::getTableName();
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('TMP_ID', array(
				'primary' => true,
				'required' => true
			)),
			new Entity\IntegerField('ID', array(
				'required' => true
			)),
			new Entity\IntegerField('XML_ID', array(
				'required' => true
			))
		);
	}
	
	public static function Update($ID, array $arFields)
	{
		$result = parent::Update($ID, $arFields);
		
		$arr = \Bitrix\Iblock\ElementTable::getList(array('select'=>array('MAX_ID'), 'runtime'=>array(
			'MAX_ID' => array(
				"data_type" => "float",
				"expression" => array("max(%s)", 'ID')
			)
		)))->Fetch();
		$conn = \Bitrix\Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->query('ALTER TABLE '.$helper->quote(self::getTableName()).' AUTO_INCREMENT = '.((int)$arr['MAX_ID'] + 1));
		
		return $result;
	}
}