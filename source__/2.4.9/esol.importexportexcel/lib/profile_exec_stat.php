<?php
namespace Bitrix\KdaImportexcel;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class ProfileExecStatTable extends Entity\DataManager
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
		return 'b_kdaimportexcel_profile_exec_stat';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'PROFILE_ID' => new Entity\IntegerField('PROFILE_ID', array(
				'required' => true
			)),
			'PROFILE_EXEC_ID' => new Entity\IntegerField('PROFILE_EXEC_ID', array(
				'required' => true
			)),
			'DATE_EXEC' => new Entity\DateTimeField('DATE_EXEC', array(
				'default_value' => ''
			)),
			'TYPE' => new Entity\StringField('TYPE', array(
				'required' => true
			)),
			'ENTITY_ID' => new Entity\IntegerField('ENTITY_ID', array(
				'required' => true
			)),
			'ROW_NUMBER' => new Entity\IntegerField('ROW_NUMBER', array(
				'required' => true
			)),
			'FIELDS' => new Entity\TextField('FIELDS', array()),
			'PROFILE' => new Entity\ReferenceField(
				'PROFILE',
				'\Bitrix\KdaImportexcel\ProfileTable',
				array('=this.PROFILE_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'PROFILE_EXEC' => new Entity\ReferenceField(
				'PROFILE_EXEC',
				'\Bitrix\KdaImportexcel\ProfileExecTable',
				array('=this.PROFILE_EXEC_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'IBLOCK_ELEMENT' => new Entity\ReferenceField(
				'IBLOCK_ELEMENT',
				'\Bitrix\Iblock\ElementTable',
				array('=this.ENTITY_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'IBLOCK_SECTION' => new Entity\ReferenceField(
				'IBLOCK_SECTION',
				'\Bitrix\Iblock\SectionTable',
				array('=this.ENTITY_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
		);
	}
	
	public static function deleteByProfile($PROFILE_ID, $arExcludedIds = array())
	{
		if(!is_array($arExcludedIds)) $arExcludedIds = array($arExcludedIds);
		$entity = new static();
		$tblName = $entity->getTableName();
		$conn = $entity->getEntity()->getConnection();
		$conn->queryExecute('DELETE FROM `'.$tblName.'` WHERE `PROFILE_ID`='.intval($PROFILE_ID).(count($arExcludedIds) > 0 ? ' and `PROFILE_EXEC_ID` NOT IN ('.implode(', ', array_map('intval', $arExcludedIds)).')' : ''));
	}
}