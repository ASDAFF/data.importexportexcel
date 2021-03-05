<?php
IncludeModuleLangFile(__FILE__);

class CKDAImportLogger {
	protected static $moduleId = 'esol.importexportexcel';
	private $execId = 0;
	private $saveLog = false;
	private $removeOldStat = false;
	private $countLastSaveExec = 0;
	private $rowNumber = 0;
	
	function __construct($saveLog = false, $profileId = 0)
	{
		if(is_array($saveLog))
		{
			$this->saveLog = (bool)($saveLog['STAT_SAVE']=='Y');
			//$this->removeOldStat = (bool)($saveLog['STAT_DELETE_OLD']=='Y');
			$this->removeOldStat = true;
			if($this->removeOldStat)
			{
				$this->countLastSaveExec = max(1, (int)$saveLog['STAT_SAVE_LAST_N']) - 1;
				if($this->countLastSaveExec==0 && (int)$saveLog['STAT_SAVE_LAST_N'] < 1)
				{
					$this->countLastSaveExec = ($this->saveLog ? 30 : 10);
				}
			}
		}
		else
		{
			$this->saveLog = (bool)$saveLog;
		}
		$this->profileId = (int)$profileId + 1;
	}
	
	public function SetExecId(&$execId)
	{
		$execId = (int)$execId;
		if($execId < 1 /*&& $this->saveLog*/)
		{
			if($this->removeOldStat)
			{
				$arLastIds = array();
				if($this->countLastSaveExec > 0)
				{
					$dbRes = \Bitrix\KdaImportexcel\ProfileExecTable::getList(array('filter'=>array('PROFILE_ID'=>intval($this->profileId)), 'order'=>array('ID'=>'DESC'), 'select'=>array('ID'), 'limit'=>$this->countLastSaveExec));
					while($arr = $dbRes->Fetch())
					{
						$arLastIds[] = $arr['ID'];
					}
				}
				
				\Bitrix\KdaImportexcel\ProfileExecTable::deleteByProfile($this->profileId, $arLastIds);
				\Bitrix\KdaImportexcel\ProfileExecStatTable::deleteByProfile($this->profileId, $arLastIds);
			}
			else
			{
				$dbRes = \Bitrix\KdaImportexcel\ProfileExecTable::getList(array('filter'=>array('PROFILE_ID'=>intval($this->profileId), 'PROFILE_EXEC_STAT.ID'=>false, array('LOGIC'=>'OR', array('!DATE_FINISH'=>false), array('<DATE_START'=>ConvertTimeStamp(time()-7*24*60*60, 'FULL')))), 'order'=>array('ID'=>'DESC'), 'select'=>array('ID'), 'limit'=>999, 'offset'=>10));
				while($arr = $dbRes->Fetch())
				{
					\Bitrix\KdaImportexcel\ProfileExecTable::delete($arr['ID']);
				}
			}
			
			$dbRes = \Bitrix\KdaImportexcel\ProfileExecTable::add(array(
				'PROFILE_ID' => $this->profileId,
				'DATE_START' => new \Bitrix\Main\Type\DateTime(),
				'DATE_FINISH' => false,
				'RUNNED_BY' => $GLOBALS['USER']->GetID()
			));
			if($dbRes->isSuccess())
			{
				$execId = $dbRes->getId();
			}
		}
		$this->execId = $execId;
	}
	
	public function FinishExec()
	{
		if($this->execId < 1 /*|| !$this->saveLog*/) return;
		
		\Bitrix\KdaImportexcel\ProfileExecTable::update($this->execId, array(
			'DATE_FINISH' => new \Bitrix\Main\Type\DateTime()
		));
	}
	
	public function SetNewElement($ID, $type="update", $rowNumber)
	{
		$this->isChanges = false;
		if(!$this->saveLog) return false;
		
		$this->elementID = $ID;
		$this->typeChanges = $type;
		$this->elemFields = array();
		$this->rowNumber = $rowNumber;
	}
	
	public function SetNewSection($ID, $type="update", $rowNumber)
	{
		$this->isSectionChanges = false;
		if(!$this->saveLog) return false;
		
		$this->sectionID = $ID;
		$this->sectionTypeChanges = $type;
		$this->sectionFields = array();
		$this->rowNumber = $rowNumber;
	}
	
	public function AddElementMassChanges($arFieldsElement, $arFieldsProps, $arFieldsProduct, $arFieldsProductStores, $arFieldsPrices)
	{
		$this->AddElementChanges('IE_', $arFieldsElement);
		$this->AddElementChanges('IP_PROP', $arFieldsProps);
		$this->AddElementChanges('ICAT_', $arFieldsProduct);
		if(is_array($arFieldsProductStores))
		{
			foreach($arFieldsProductStores as $sid=>$arFieldsProductStore)
			{
				$this->AddElementChanges("ICAT_STORE".$sid."_", $arFieldsProductStore);
			}
		}
		if(is_array($arFieldsPrices))
		{
			foreach($arFieldsPrices as $pid=>$arFieldsPrice)
			{
				$this->AddElementChanges("ICAT_PRICE".$pid."_", $arFieldsPrice);
			}
		}
	}

	public function AddFileError($file)
	{
		if(!$this->saveLog) return false;
		
		if(!isset($this->elemFields['FILE_ERRORS'])) $this->elemFields['FILE_ERRORS'] = array();
		$this->elemFields['FILE_ERRORS'][] = $file;
	}
	
	public function AddElementChanges($type, $arFields, $arOldFields=array())
	{
		if(!empty($arFields)) $this->isChanges = true;
		if(!$this->saveLog) return false;
		if(!is_array($arOldFields)) $arOldFields = array();
		if($type=='IE_' && isset($arFields['IPROPERTY_TEMPLATES']))
		{
			$this->AddElementChanges('IPROP_TEMP_', $arFields['IPROPERTY_TEMPLATES'], $arOldFields['IPROPERTY_TEMPLATES']);
			unset($arFields['IPROPERTY_TEMPLATES']);
		}
		
		if(is_array($arFields))
		{
			foreach($arFields as $k=>$v)
			{
				$key = $type.$k;
				$this->elemFields[$key] = array(
					'OLDVALUE' => (isset($arOldFields[$k]) ? $arOldFields[$k] : ''),
					'VALUE' => $v
				);
			}
		}
	}
	
	public function IsChangedElement()
	{
		return $this->isChanges;
	}
	
	public function AddSectionChanges($arFields, $arOldFields=array())
	{
		if(!empty($arFields)) $this->isSectionChanges = true;
		if(!$this->saveLog) return false;
		if(!is_array($arOldFields)) $arOldFields = array();
		
		if(is_array($arFields))
		{
			foreach($arFields as $k=>$v)
			{
				$key = $k;
				$this->sectionFields[$key] = array(
					'OLDVALUE' => (isset($arOldFields[$k]) ? $arOldFields[$k] : ''),
					'VALUE' => $v
				);
			}
		}
	}
	
	public function IsChangedSection()
	{
		return $this->isSectionChanges;
	}
	
	public function SaveElementChanges($ID)
	{
		if(!$this->saveLog) return false;
		//if((!is_array($this->elemFields) || empty($this->elemFields)) && (ToUpper($this->typeChanges)!='DELETE')) return false;
		if($ID!=$this->elementID) return false;
		if(!$this->execId) return false;
		
		$fields = '';
		if(is_array($this->elemFields) && !empty($this->elemFields)) $fields = serialize($this->elemFields);
		$type = 'ELEMENT_'.ToUpper($this->typeChanges);
		if((strlen($fields)==0 || count(array_diff_key($this->elemFields, array('FILE_ERRORS'=>true)))==0) && ToUpper($this->typeChanges)=='UPDATE') $type = 'ELEMENT_FOUND';
		$dbRes = \Bitrix\KdaImportexcel\ProfileExecStatTable::add(array(
			'PROFILE_ID' => $this->profileId,
			'PROFILE_EXEC_ID' => $this->execId,
			'DATE_EXEC' => new \Bitrix\Main\Type\DateTime(),
			'TYPE' => $type,
			'ENTITY_ID' => $this->elementID,
			'ROW_NUMBER' => $this->rowNumber,
			'FIELDS' => $fields
		));
	}
	
	public function SaveSectionChanges($ID)
	{
		if(!$this->saveLog) return false;
		if((!is_array($this->sectionFields) || empty($this->sectionFields)) && (ToUpper($this->sectionTypeChanges)!='DELETE')) return false;
		if($ID!=$this->sectionID) return false;
		if(!$this->execId) return false;

		$dbRes = \Bitrix\KdaImportexcel\ProfileExecStatTable::add(array(
			'PROFILE_ID' => $this->profileId,
			'PROFILE_EXEC_ID' => $this->execId,
			'DATE_EXEC' => new \Bitrix\Main\Type\DateTime(),
			'TYPE' => 'SECTION_'.ToUpper($this->sectionTypeChanges),
			'ENTITY_ID' => $this->sectionID,
			'ROW_NUMBER' => $this->rowNumber,
			'FIELDS' => serialize($this->sectionFields)
		));
	}
	
	public function SaveElementNotFound($arFilter, $rowNumber)
	{
		if(!$this->saveLog) return false;
		if(!$this->execId) return false;
		$this->rowNumber = $rowNumber;
		if(is_array($this->elemFields) && !empty($this->elemFields)) $fields = serialize($this->elemFields);
		$dbRes = \Bitrix\KdaImportexcel\ProfileExecStatTable::add(array(
			'PROFILE_ID' => $this->profileId,
			'PROFILE_EXEC_ID' => $this->execId,
			'DATE_EXEC' => new \Bitrix\Main\Type\DateTime(),
			'TYPE' => 'ELEMENT_NOT_FOUND',
			'ENTITY_ID' => 0,
			'ROW_NUMBER' => $this->rowNumber,
			'FIELDS' => serialize(array('FILTER'=>$arFilter, 'FIELDS'=>$this->elemFields))
		));
	}
	
	public function PrepareFieldList()
	{
		if(isset($this->fl)) return;
		$this->fl = new CKDAFieldList();
	}
	
	public function GetElementDescriptionArray($description)
	{
		if(!$description) return '';
		$arFields = (!is_array($description) ? unserialize(htmlspecialcharsback($description)) : $description);
		$val = '<pre>'.print_r($arFields, true).'</pre>';
		//$val = str_replace("\t", '<span style="display: inline-block; width: 15px;"></span>', $val);
		return $val;
	}
	
	public function GetElementDescription($description)
	{
		if(!$description) return '';
		$this->PrepareFieldList();
		
		$arFields = (!is_array($description) ? unserialize(htmlspecialcharsback($description)) : $description);
		
		$arFieldsElement = array();
		$arFieldsProduct = array();
		$arFieldsProductStores = array();
		$arFieldsProductDiscount = array();
		$arFieldsProps = array();
		$arFieldsSections = array();
		$arFieldsIpropTemp = array();
		$arFileErrors = array();
		foreach($arFields as $fk=>$fv)
		{
			if(strpos($fk, 'IE_')===0)
			{
				$arFieldsElement[$fk] = $fv;
			}
			elseif(strpos($fk, 'ISECT')===0)
			{
				
			}
			elseif(strpos($fk, 'ICAT_DISCOUNT_')===0)
			{
				$arFieldsProductDiscount[$fk] = $fv;
			}
			elseif(strpos($fk, 'ICAT_')===0)
			{
				$arFieldsProduct[$fk] = $fv;
			}
			elseif(strpos($fk, 'IP_PROP')===0)
			{
				$arFieldsProps[$fk] = $fv;
			}
			elseif(strpos($fk, 'IPROP_TEMP_')===0)
			{
				$arFieldsIpropTemp[$fk] = $fv;
			}
			elseif($fk=='FILE_ERRORS')
			{
				$arFileErrors = $fv;
			}
		}
		
		$newDesc = '';
		if(!empty($arFieldsElement))
		{
			$arFieldNames = $this->fl->GetIblockElementFieldsForStat();
			$newDesc .= '<p><b>'.GetMessage("KDA_IE_EVENTRES_GROUP_FIELDS").'</b></p><ul>';
			foreach($arFieldsElement as $k=>$v)
			{
				if(!isset($arFieldNames[$k]))
				{
					if($k=='IE_IBLOCK_SECTION')
					{
						$value = $v['VALUE'];
						if(!is_array($value)) $value = array($value);
						foreach($value as $k2=>$v2)
						{
							if(!is_numeric($v2)) continue;
							$value[$k2] = '['.$v2.'] '.$this->GetPropertySectionValue(array('ID'=>'IBLOCK_SECTION'), $v2);
						}
						$value = implode(', ', $value);
						
						$oldvalue = ($arFieldsElement['IE_IBLOCK_SECTION']['OLDVALUE'] ? $arFieldsElement['IE_IBLOCK_SECTION']['OLDVALUE'] : $arFieldsElement['IE_IBLOCK_SECTION_ID']['OLDVALUE']);
						if(!is_array($oldvalue)) $oldvalue = array($oldvalue);
						foreach($oldvalue as $k2=>$v2)
						{
							if(!is_numeric($v2)) continue;
							$oldvalue[$k2] = '['.$v2.'] '.$this->GetPropertySectionValue(array('ID'=>'IBLOCK_SECTION'), $v2);
						}
						$oldvalue = implode(', ', $oldvalue);
						
						$newDesc .= '<li><b>'.GetMessage("KDA_IE_EVENTRES_SECTION_ID").':</b> ';
						if(strlen($value) > 0) $newDesc .= $value;
						if(strlen($oldvalue) > 0)
						{
							$newDesc .= '<div><b>'.GetMessage("KDA_IE_EVENTRES_OLD_VALUE").'</b> '.$oldvalue.'</div>';
						}
						$newDesc .= '</li>';
					}
					continue;
				}
				$value = (!is_array($v['VALUE']) ? $v['VALUE'] : print_r($v['VALUE'], true));
				$oldvalue = (!is_array($v['OLDVALUE']) ? $v['OLDVALUE'] : print_r($v['OLDVALUE'], true));
				
				$newDesc .= '<li><b>'.$arFieldNames[$k]['name'].':</b> ';
				if(strlen($value) > 0) $newDesc .= $value;
				if(strlen($oldvalue) > 0)
				{
					$newDesc .= '<div><b>'.GetMessage("KDA_IE_EVENTRES_OLD_VALUE").'</b> '.$oldvalue.'</div>';
				}
				$newDesc .= '</li>';
			}
			$newDesc .= '</ul>';
		}
		if(!empty($arFieldsIpropTemp))
		{
			$arFieldNames = $this->fl->GetIblockIpropTemplates();
			$newDesc .= '<p><b>'.GetMessage("KDA_IE_EVENTRES_GROUP_IPROP_TEMP").'</b></p><ul>';
			foreach($arFieldsIpropTemp as $k=>$v)
			{
				if(!isset($arFieldNames[$k])) continue;
				$value = (!is_array($v['VALUE']) ? $v['VALUE'] : print_r($v['VALUE'], true));
				$oldvalue = (!is_array($v['OLDVALUE']) ? $v['OLDVALUE'] : print_r($v['OLDVALUE'], true));
				
				$newDesc .= '<li><b>'.$arFieldNames[$k].':</b> ';
				if(strlen($value) > 0) $newDesc .= $value;
				if(strlen($oldvalue) > 0)
				{
					$newDesc .= '<div><b>'.GetMessage("KDA_IE_EVENTRES_OLD_VALUE").'</b> '.$oldvalue.'</div>';
				}
				$newDesc .= '</li>';
			}
			$newDesc .= '</ul>';
		}
		if(!empty($arFieldsProps))
		{
			$arFieldProps = $this->fl->GetAllIblockProperties();
			$newDesc .= '<p><b>'.GetMessage("KDA_IE_EVENTRES_GROUP_PROPERTIES").'</b></p><ul>';
			foreach($arFieldsProps as $k=>$v)
			{
				if(!isset($arFieldProps[$k])) continue;
				$propName = $arFieldProps[$k]["NAME"].' ['.$arFieldProps[$k]["CODE"].']';
				$arProp = $arFieldProps[$k];
				
				$value = $this->GetPropertyValue($arProp, $v['VALUE']);
				$oldvalue = $this->GetPropertyValue($arProp, $v['OLDVALUE']);
				
				$value = (!is_array($value) ? $value : print_r($value, true));
				$oldvalue = (!is_array($oldvalue) ? $oldvalue : print_r($oldvalue, true));
				
				$newDesc .= '<li><b>'.$propName.':</b> ';
				if(strlen($value) > 0) $newDesc .= $value;
				if(strlen($oldvalue) > 0)
				{
					$newDesc .= '<div><b>'.GetMessage("KDA_IE_EVENTRES_OLD_VALUE").'</b> '.$oldvalue.'</div>';
				}
				$newDesc .= '</li>';
			}
			$newDesc .= '</ul>';
		}
		if(!empty($arFieldsProduct))
		{
			$arFieldNames = $this->fl->GetCatalogFieldsCached();
			$newDesc .= '<p><b>'.GetMessage("KDA_IE_EVENTRES_GROUP_CATALOG").'</b></p><ul>';
			foreach($arFieldsProduct as $k=>$v)
			{
				if(!isset($arFieldNames[$k])) continue;
				$value = (!is_array($v['VALUE']) ? $v['VALUE'] : print_r($v['VALUE'], true));
				$oldvalue = (!is_array($v['OLDVALUE']) ? $v['OLDVALUE'] : print_r($v['OLDVALUE'], true));
				
				$newDesc .= '<li><b>'.$arFieldNames[$k].':</b> ';
				if(strlen($value) > 0) $newDesc .= $value;
				if(strlen($oldvalue) > 0)
				{
					$newDesc .= '<div><b>'.GetMessage("KDA_IE_EVENTRES_OLD_VALUE").'</b> '.$oldvalue.'</div>';
				}
				$newDesc .= '</li>';
			}
			$newDesc .= '</ul>';
		}
		
		if(!empty($arFileErrors))
		{
			$newDesc .= '<p><b>'.GetMessage("KDA_IE_EVENTRES_FILE_ERRORS").'</b></p><ul>';
			foreach($arFileErrors as $k=>$v)
			{
				$newDesc .= '<li>'.$v.'</li>';
			}
			$newDesc .= '</ul>';
		}
		
		if(strlen($newDesc) > 0) $newDesc = '<div style="min-width: 500px;">'.$newDesc.'</div>';
		return $newDesc;
	}
	
	public function GetPropertyValue($arProp, $val)
	{
		if(is_array($val))
		{
			if(in_array($arProp['PROPERTY_TYPE'], array('L', 'E', 'G'))
			|| ($arProp['PROPERTY_TYPE']=='S' && $arProp['USER_TYPE']=='directory'))
			{
				foreach($val as $k=>$v)
				{
					$val[$k] = $this->GetPropertyValue($arProp, $v);
				}
			}
		}
		else
		{
			if($arProp['PROPERTY_TYPE']=='L')
			{
				$val = $this->GetPropertyListValue($arProp, $val);
			}
			elseif($arProp['PROPERTY_TYPE']=='E')
			{
				$val = $this->GetPropertyElementValue($arProp, $val);
			}
			elseif($arProp['PROPERTY_TYPE']=='G')
			{
				$val = $this->GetPropertySectionValue($arProp, $val);
			}
			/*elseif($arProp['PROPERTY_TYPE']=='F')
			{
				$val = $this->GetFileValue($val);
			}*/
			elseif($arProp['PROPERTY_TYPE']=='S' && $arProp['USER_TYPE']=='directory')
			{
				$val = $this->GetHighloadBlockValue($arProp, $val);
			}
		}

		return $val;
	}
	
	public function GetHighloadBlockValue($arProp, $val)
	{
		if($val && CModule::IncludeModule('highloadblock') && $arProp['USER_TYPE_SETTINGS']['TABLE_NAME'])
		{
			if(!isset($this->propVals[$arProp['ID']][$val]))
			{
				if(!$this->hlbl[$arProp['ID']])
				{
					$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('TABLE_NAME'=>$arProp['USER_TYPE_SETTINGS']['TABLE_NAME'])))->fetch();
					$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
					$this->hlbl[$arProp['ID']] = $entity->getDataClass();
				}
				$entityDataClass = $this->hlbl[$arProp['ID']];
				
				$dbRes2 = $entityDataClass::GetList(array('filter'=>array("UF_XML_ID"=>$val), 'select'=>array('ID', 'UF_NAME'), 'limit'=>1));
				if($arr2 = $dbRes2->Fetch())
				{
					$this->propVals[$arProp['ID']][$val] = $arr2['UF_NAME'];
				}
				else
				{
					$this->propVals[$arProp['ID']][$val] = '';
				}
			}
			return $this->propVals[$arProp['ID']][$val];
		}
		return $val;
	}
	
	public function GetFileValue($val)
	{
		if($val)
		{
			$arFile = CKDAImportUtils::GetFileArray($val);
			if($arFile)
			{
				$val = $arFile['SRC'];
			}
			else
			{
				$val = '';
			}
		}
		return $val;
	}
	
	public function GetPropertySectionValue($arProp, $val)
	{
		if($val)
		{
			if(!isset($this->propVals[$arProp['ID']][$val]))
			{
				$dbRes = CIBlockSection::GetList(array(), array("ID"=>$val), false, array('NAME'));
				if($arSect = $dbRes->Fetch())
				{
					$this->propVals[$arProp['ID']][$val] = $arSect['NAME'];
				}
				else
				{
					$this->propVals[$arProp['ID']][$val] = '';
				}
			}
			$val = $this->propVals[$arProp['ID']][$val];
		}
		return $val;
	}
	
	public function GetPropertyElementValue($arProp, $val)
	{
		if($val)
		{
			if(!isset($this->propVals[$arProp['ID']][$val]))
			{
				$dbRes = CIBlockElement::GetList(array(), array("ID"=>$val), false, false, array('NAME'));
				if($arElem = $dbRes->Fetch())
				{
					$this->propVals[$arProp['ID']][$val] = $arElem['NAME'];
				}
				else
				{
					$this->propVals[$arProp['ID']][$val] = '';
				}
			}
			$val = $this->propVals[$arProp['ID']][$val];
		}
		return $val;
	}
	
	public function GetPropertyListValue($arProp, $val)
	{
		if($val)
		{
			if(!isset($this->propVals[$arProp['ID']][$val]))
			{
				$dbRes = CIBlockPropertyEnum::GetList(array(), array("PROPERTY_ID"=>$arProp['ID'], "ID"=>$val));
				if($arPropEnum = $dbRes->Fetch())
				{
					$this->propVals[$arProp['ID']][$val] = $arPropEnum['VALUE'];
				}
				else
				{
					$this->propVals[$arProp['ID']][$val] = '';
				}
			}
			$val = $this->propVals[$arProp['ID']][$val];
		}
		return $val;
	}
	
	public function GetSectionDescription($description, $IBLOCK_ID = false)
	{
		if(!$description) return '';
		$this->PrepareFieldList();
		
		$arFields = unserialize(htmlspecialcharsback($description));
		$arFieldsSection = array();
		foreach($arFields as $fk=>$fv)
		{
			$arFieldsSection['ISECT_'.$fk] = $fv;
		}
		
		$newDesc = '';
		if(!empty($arFieldsSection))
		{
			$arFieldNames = $this->fl->GetIblockSectionFields('', $IBLOCK_ID);
			foreach($arFieldsSection as $k=>$v)
			{
				if(!isset($arFieldNames[$k]))continue;
				$value = (!is_array($v['VALUE']) ? $v['VALUE'] : print_r($v['VALUE'], true));
				$oldvalue = (!is_array($v['OLDVALUE']) ? $v['OLDVALUE'] : print_r($v['OLDVALUE'], true));
				
				$newDesc .= '<li><b>'.$arFieldNames[$k]['name'].':</b> ';
				if(strlen($value) > 0) $newDesc .= $value;
				if(strlen($oldvalue) > 0)
				{
					$newDesc .= '<div><b>'.GetMessage("KDA_IE_EVENTRES_OLD_VALUE").'</b> '.$oldvalue.'</div>';
				}
				$newDesc .= '</li>';
			}
			$newDesc .= '</ul>';
		}
		
		if(strlen($newDesc) > 0) $newDesc = '<div style="min-width: 500px;">'.$newDesc.'</div>';
		return $newDesc;
	}
}
?>