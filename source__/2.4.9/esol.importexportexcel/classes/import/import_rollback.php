<?php
use Bitrix\Main\Loader,
	Bitrix\Main\Entity\Query,
	Bitrix\Main\Entity\ExpressionField,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CKDAImportExcelRollback extends CKDAImportExcel {
	var $PROFILE_ID = false;
	var $PROFILE_EXEC_ID = false;
	var $getListParams = array();
	var $totalCount = 0;
	var $currentCount = 0;
	var $currentID = 0;
	
	function __construct($arParams)
	{
		if(isset($arParams['NS']['totalCount'])) $this->totalCount = (int)$arParams['NS']['totalCount'];
		if(isset($arParams['NS']['currentCount'])) $this->currentCount = (int)$arParams['NS']['currentCount'];
		if(isset($arParams['NS']['currentID'])) $this->currentID = (int)$arParams['NS']['currentID'];
		if(strlen($arParams['PROFILE_ID']) > 0) $this->PROFILE_ID = (int)$arParams['PROFILE_ID'] + 1;
		if(strlen($arParams['PROFILE_EXEC_ID']) > 0) $this->PROFILE_EXEC_ID = $arParams['PROFILE_EXEC_ID'];
		if(strlen($arParams['currentCount']) > 0) $this->currentCount = $arParams['currentCount'];
	
		if($this->PROFILE_ID > 0 && $this->PROFILE_EXEC_ID > 0)
		{
			$this->getListParams = array(
				'filter' => array(
					'PROFILE_ID' => $this->PROFILE_ID, 
					'>=PROFILE_EXEC_ID' => $this->PROFILE_EXEC_ID
				),
				'select' => array('ID', 'TYPE', 'ENTITY_ID', 'FIELDS'),
				'order' => array('ID' => 'DESC'),
				'limit' => 1000
			);
			
			if($this->totalCount==0)
			{
				$countQuery = new Query(\Bitrix\KdaImportexcel\ProfileExecStatTable::getEntity());
				$countQuery->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
				$countQuery->setFilter($this->getListParams['filter']);
				$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
				unset($countQuery);
				$this->totalCount = (int)$totalCount['CNT'];
			}
		}
		
		$this->params = array();
		$this->logger = new CKDAImportLogger();
		$cm = new \Bitrix\KdaImportexcel\ClassManager($this);
		$this->pricer = $cm->GetPricer();
		
		AddEventHandler('iblock', 'OnBeforeIBlockElementUpdate', array($this, 'OnBeforeIBlockElementUpdateHandler'), 999999);
	}
	
	public function OnBeforeIBlockElementUpdateHandler(&$arFields)
	{
		if(isset($arFields['PROPERTY_VALUES'])) unset($arFields['PROPERTY_VALUES']);
	}
	
	public function Proccess()
	{
		if($this->totalCount==0) return array('status'=>'end');
		
		$timeStart = time();
		$break = $finish = false;
		while(!$break)
		{
			if($this->currentID > 0)
			{
				$this->getListParams['filter']['<ID'] = $this->currentID;
			}
			$dbRes = \Bitrix\KdaImportexcel\ProfileExecStatTable::getList($this->getListParams);
			while(!$break && ($arRecord = $dbRes->Fetch()))
			{
				$this->RestoreRecord($arRecord);
				$this->currentCount++;
				$this->currentID = $arRecord['ID'];
				if(time() - $timeStart > 5)
				{
					$break = true;
				}
			}
			if(!$break && $dbRes->getSelectedRowsCount() < $this->getListParams['limit'])
			{
				$finish = $break = true;
			}
		}
		return array(
			'NS'=>array(
				'totalCount'=>$this->totalCount, 
				'currentID'=>$this->currentID,
				'currentCount'=>$this->currentCount
			),
			'STATUS'=>($finish ? 'END' : 'PROGRESS')
		);
	}
	
	public function RestoreRecord($arRecord)
	{
		if(!$arRecord['ENTITY_ID'] || !in_array($arRecord['TYPE'], array('ELEMENT_ADD', 'ELEMENT_UPDATE', 'SECTION_ADD', 'SECTION_UPDATE'))) return;
		
		$arFields = unserialize($arRecord['FIELDS']);
		foreach($arFields as $k=>$v)
		{
			if(isset($v['OLDVALUE']))
			{
				$arFields[$k] = $v['OLDVALUE'];
			}
			elseif(preg_match('/^ICAT_STORE\d+_AMOUNT$/', $k))
			{
				$arFields[$k] = '-';
			}
			else
			{
				unset($arFields[$k]);
			}
		}
		
		if($arRecord['TYPE']=='SECTION_ADD')
		{
			$this->RestoreSectionAdd($arRecord['ENTITY_ID']);
		}
		elseif($arRecord['TYPE']=='SECTION_UPDATE')
		{
			$this->RestoreSectionUpdate($arRecord['ENTITY_ID'], $arFields);
		}
		elseif($arRecord['TYPE']=='ELEMENT_ADD')
		{
			$this->RestoreElementAdd($arRecord['ENTITY_ID']);
		}
		elseif($arRecord['TYPE']=='ELEMENT_UPDATE')
		{
			$this->RestoreElementUpdate($arRecord['ENTITY_ID'], $arFields);
		}
	}
	
	public function RestoreSectionAdd($ID)
	{
		if(class_exists('\Bitrix\Iblock\SectionElementTable'))
		{
			$arSection = CIblockSection::GetList(array(), array('ID'=>$ID), false, false, array('ID', 'IBLOCK_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'))->Fetch();
			if(!$arSection) return;
			$IBLOCK_ID = $arSection['IBLOCK_ID'];
			$arFilterSE = array(
				'IBLOCK_SECTION.IBLOCK_ID' => $IBLOCK_ID,
				'>=IBLOCK_SECTION.LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
				'<=IBLOCK_SECTION.RIGHT_MARGIN' => $arSection['RIGHT_MARGIN']
			);
			
			$dbRes = \Bitrix\Iblock\SectionElementTable::GetList(array('filter'=>$arFilterSE, 'group'=>array('IBLOCK_SECTION_ID'), 'select'=>array('IBLOCK_SECTION_ID'), 'limit'=>1));
			if($arr = $dbRes->Fetch()) return;
		}
		CIblockSection::Delete($ID);
	}
	
	public function RestoreSectionUpdate($ID, $arFields)
	{
		if(empty($arFields)) return;
		$bs = new CIBlockSection;
		$bs->Update($ID, $arFields, true, true, true);
		if(!empty($arFields['IPROPERTY_TEMPLATES']) || $arFields['NAME'])
		{
			$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($IBLOCK_ID, $ID);
			$ipropValues->clearValues();
		}
	}
	
	public function RestoreElementAdd($ID)
	{
		CIblockElement::Delete($ID);
	}
	
	public function RestoreElementUpdate($ID, $arFields)
	{
		if(empty($arFields)) return;
		$arElement = CIblockElement::GetList(array(), array('ID'=>$ID), false, false, array('ID', 'IBLOCK_ID'))->Fetch();
		if(!$arElement) return;
		$IBLOCK_ID = $arElement['IBLOCK_ID'];
	
		$arFieldsElement = array();
		$arFieldsPrices = array();
		$arFieldsProduct = array();
		$arFieldsProductStores = array();
		$arFieldsProductDiscount = array();
		$arFieldsProps = array();
		$arFieldsIpropTemp = array();
		foreach($arFields as $field=>$value)
		{
			if(strpos($field, 'IE_')===0)
			{
				$arFieldsElement[substr($field, 3)] = $value;
			}
			elseif(strpos($field, 'ICAT_PRICE')===0)
			{
				$arPrice = explode('_', substr($field, 10), 2);
				$arFieldsPrices[$arPrice[0]][$arPrice[1]] = $value;
			}
			elseif(strpos($field, 'ICAT_STORE')===0)
			{
				$arStore = explode('_', substr($field, 10), 2);
				$arFieldsProductStores[$arStore[0]][$arStore[1]] = $value;
			}
			elseif(strpos($field, 'ICAT_DISCOUNT_')===0)
			{
				$arFieldsProductDiscount[substr($field, 14)] = $value;
			}
			elseif(strpos($field, 'ICAT_')===0)
			{
				$arFieldsProduct[substr($field, 5)] = $value;
			}
			elseif(strpos($field, 'IP_PROP')===0)
			{
				$arFieldsProps[substr($field, 7)] = $value;
			}
			elseif(strpos($field, 'IPROP_TEMP_')===0)
			{
				$arFieldsIpropTemp[substr($field, 11)] = $value;
			}
		}
		
		if(!empty($arFieldsProps))
		{
			CIBlockElement::SetPropertyValuesEx($ID, $IBLOCK_ID, $arFieldsProps);
		}
		
		if(!empty($arFieldsProduct))
		{
			CCatalogProduct::Update($ID, $arFieldsProduct);
		}
		
		if(!empty($arFieldsPrices))
		{
			$this->pricer->SavePrice($ID, $arFieldsPrices);
		}
		
		if(!empty($arFieldsProductStores))
		{
			foreach($arFieldsProductStores as $sid=>$arFieldsStore)
			{
				unset($arFieldsStore['PRODUCT_ID'], $arFieldsStore['STORE_ID']);
				$dbRes = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID'=>$ID, 'STORE_ID'=>$sid), false, false, array('ID'));
				while($arPStore = $dbRes->Fetch())
				{
					if(strlen(trim($arFieldsStore['AMOUNT']))==0 || $arFieldsStore['AMOUNT']=='-')
					{
						CCatalogStoreProduct::Delete($arPStore["ID"]);
					}
					else
					{
						CCatalogStoreProduct::Update($arPStore["ID"], $arFieldsStore);
					}
				}
			}
		}
		
		if(!empty($arFieldsIpropTemp))
		{
			$arFieldsElement['IPROPERTY_TEMPLATES'] = $arFieldsIpropTemp;
		}
		if(!isset($arFieldsElement['IBLOCK_SECTION_ID']) && isset($arFieldsElement['IBLOCK_SECTION']) && is_array($arFieldsElement['IBLOCK_SECTION']) && count($arFieldsElement['IBLOCK_SECTION']) > 0)
		{
			reset($arFieldsElement['IBLOCK_SECTION']);
			$arFieldsElement['IBLOCK_SECTION_ID'] = current($arFieldsElement['IBLOCK_SECTION']);
		}
		if(isset($arFieldsElement['IBLOCK_SECTION_ID']) && $arFieldsElement['IBLOCK_SECTION_ID'] > 0 && (!isset($arFieldsElement['IBLOCK_SECTION']) || empty($arFieldsElement['IBLOCK_SECTION'])))
		{
			$arFieldsElement['IBLOCK_SECTION'] = array($arFieldsElement['IBLOCK_SECTION_ID']);
		}
		if(isset($arFieldsElement['PREVIEW_PICTURE'])) unset($arFieldsElement['PREVIEW_PICTURE']);
		if(isset($arFieldsElement['DETAIL_PICTURE'])) unset($arFieldsElement['DETAIL_PICTURE']);
		
		$el = new CIblockElement();
		$el->Update($ID, $arFieldsElement);
	}
}	
?>