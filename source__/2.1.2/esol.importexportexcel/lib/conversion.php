<?php
namespace Bitrix\KdaImportexcel;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Conversion
{
	protected $loadElemFields = array();
	protected $updateElemFields = array();
	protected $disableConv = false;
	protected $fieldSettings = array();
	protected $fieldPrefixes = array('IE_', 'IP_PROP', 'ICAT_');
	protected $iblockId = 0;
	protected $offersIblockId = 0;
	protected $elementId = 0;
	protected $elementFields = array();
	protected $isSku = false;
	protected $allowLoad = true;
	
	public function __construct($ie=false, $iblockId=0, $fieldSettings = array())
	{
		$this->ie = $ie;
		$this->iblockId = $iblockId;
		$this->fieldSettings = $fieldSettings;
		
		$prefixPattern = '/^('.implode('|', $this->fieldPrefixes).')[A-Z0-9_]+$/';
		$prefixPattern2 = '/#(('.implode('|', $this->fieldPrefixes).')[A-Z0-9_]+)#/';
		foreach($fieldSettings as $fieldName=>$fieldSet)
		{
			if(isset($fieldSet['EXTRA_CONVERSION']) && !empty($fieldSet['EXTRA_CONVERSION']))
			{
				if(!in_array($fieldName, $this->updateElemFields)) $this->updateElemFields[] = $fieldName;
				foreach($fieldSet['EXTRA_CONVERSION'] as $k=>$v)
				{
					$loadElemFields = array();
					if(preg_match_all($prefixPattern2, $v['TO'], $m)) $loadElemFields = array_merge($loadElemFields, $m[1]);
					if(preg_match_all($prefixPattern2, $v['FROM'], $m)) $loadElemFields = array_merge($loadElemFields, $m[1]);
					if(preg_match($prefixPattern, $v['CELL'])) $loadElemFields[] = $v['CELL'];
					foreach($loadElemFields as $loadElemField)
					{
						if(!in_array($loadElemField, $this->loadElemFields)) $this->loadElemFields[] = $loadElemField;
					}
				}
			}
		}
	}
	
	public function Enable()
	{
		$this->disableConv = false;
	}
	
	public function Disable()
	{
		$this->disableConv = true;
	}
	
	public function SetSkuMode($isSku, $offersIblockId = 0)
	{
		if($offersIblockId > 0) $this->offersIblockId = $offersIblockId;
		$this->isSku = (bool)$isSku;
	}
	
	public function GetSkuMode()
	{
		return (bool)$this->isSku;
	}
	
	public function SetAllowLoad($allow = true)
	{
		$allow = (bool)$allow;
		$this->allowLoad = $allow;
	}
	
	public function GetAllowLoad()
	{
		return $this->allowLoad;
	}
	
	public function BeginUpdateFields()
	{
		$this->SetAllowLoad(true);
	}
	
	public function EndUpdateFields()
	{
		return $this->GetAllowLoad();
	}

	public function UpdateElementFields(&$arFieldsElement, $ID)
	{
		if($this->disableConv || empty($this->updateElemFields)) return;
		$this->BeginUpdateFields();
		foreach($arFieldsElement as $fieldKey=>$fieldVal)
		{
			$fKey = 'IE_'.$fieldKey;
			$arFieldsElement[$fieldKey] = $this->UpdateElementField($fKey, $fieldVal, $ID);
			if($arFieldsElement[$fieldKey]===false) unset($arFieldsElement[$fieldKey]);
		}
		return $this->EndUpdateFields();
	}
	
	public function UpdateSectionFields(&$arSections, $ID)
	{
		if($this->disableConv || empty($this->updateElemFields)) return;
		$this->BeginUpdateFields();
		foreach($arSections as $k=>$arSection)
		{
			foreach($arSection as $fieldKey=>$fieldVal)
			{
				$fKey = 'ISECT'.$k.'_'.$fieldKey;
				if(count($arSections)==1 && $k==0 && !isset($this->fieldSettings[$fKey]['EXTRA_CONVERSION']))
				{
					$fKey2 = 'ISECT_'.$fieldKey;
					if(isset($this->fieldSettings[$fKey2]['EXTRA_CONVERSION'])) $fKey = $fKey2;
				}
				$arSections[$k][$fieldKey] = $this->UpdateElementField($fKey, $fieldVal, $ID);
				if($arSections[$k][$fieldKey]===false) unset($arSections[$k][$fieldKey]);
			}
		}
		return $this->EndUpdateFields();
	}
	
	public function UpdateProperties(&$arProps, $ID)
	{
		if($this->disableConv || empty($this->updateElemFields)) return;
		$this->BeginUpdateFields();
		foreach($arProps as $propKey=>$propVal)
		{
			$fKey = 'IP_PROP'.$propKey;
			$arProps[$propKey] = $this->UpdateElementField($fKey, $propVal, $ID);
			if($arProps[$propKey]===false) unset($arProps[$propKey]);
			if(is_array($arProps[$propKey]))
			{
				foreach($arProps[$propKey] as $k=>$v)
				{
					if($v===false && $v!==$propVal[$k]) unset($arProps[$propKey][$k]);
					if(empty($arProps[$propKey]) && !empty($propVal)) unset($arProps[$propKey]);
				}
			}
		}
		return $this->EndUpdateFields();
	}
	
	public function UpdateProduct(&$arProduct, &$arPrices, &$arStores, $ID)
	{
		if($this->disableConv || empty($this->updateElemFields)) return;
		$this->BeginUpdateFields();
		foreach($arProduct as $productKey=>$productVal)
		{
			$fKey = 'ICAT_'.$productKey;
			$arProduct[$productKey] = $this->UpdateElementField($fKey, $productVal, $ID);
			if($arProduct[$productKey]===false) unset($arProduct[$productKey]);
		}
		
		foreach($arPrices as $gid=>$arFieldsPrice)
		{
			foreach($arFieldsPrice as $priceKey=>$priceVal)
			{
				$fKey = 'ICAT_PRICE'.$gid.'_'.$priceKey;
				$arPrices[$gid][$priceKey] = $this->UpdateElementField($fKey, $priceVal, $ID);
				if($arPrices[$gid][$priceKey]===false) unset($arPrices[$gid][$priceKey]);
			}
		}
		
		foreach($arStores as $sid=>$arFieldsStore)
		{
			foreach($arFieldsStore as $storeKey=>$storeVal)
			{
				$fKey = 'ICAT_STORE'.$sid.'_'.$storeKey;
				$arStores[$sid][$storeKey] = $this->UpdateElementField($fKey, $storeVal, $ID);
				if($arStores[$sid][$storeKey]===false) unset($arStores[$sid][$storeKey]);
			}
		}
		return $this->EndUpdateFields();
	}
	
	public function UpdateDiscountFields(&$arFieldsProductDiscount, $ID)
	{
		if($this->disableConv || empty($this->updateElemFields)) return;
		$this->BeginUpdateFields();
		foreach($arFieldsProductDiscount as $fieldKey=>$fieldVal)
		{
			$fKey = 'ICAT_DISCOUNT_'.$fieldKey;
			$arFieldsProductDiscount[$fieldKey] = $this->UpdateElementField($fKey, $fieldVal, $ID);
			if($arFieldsProductDiscount[$fieldKey]===false) unset($arFieldsProductDiscount[$fieldKey]);
		}
		return $this->EndUpdateFields();
	}
	
	public function UpdateElementField($fKey, $val, $ID)
	{
		if(is_array($val))
		{
			foreach($val as $k=>$v)
			{
				$val[$k] = $this->ApplyExtraConversions($fKey, $v, $ID);
			}
		}
		else
		{
			$val = $this->ApplyExtraConversions($fKey, $val, $ID);
		}
		return $val;
	}
	
	public function ApplyExtraConversions($fKey, $val, $ID)
	{
		if(is_array($val)) return $val;
		if($this->isSku) $fKey = 'OFFER_'.$fKey;
		$fs = $this->fieldSettings;
		
		if(isset($fs[$fKey]) && isset($fs[$fKey]['EXTRA_CONVERSION']) && is_array($fs[$fKey]['EXTRA_CONVERSION']))
		{
			$arFields = $this->GetElementFields($ID);
			$arFileFields = $this->ie->GetCurrentItemValues();
			$prefixPattern = '/^('.implode('|', $this->fieldPrefixes).')[A-Z0-9_]+$/';
			$prefixPattern2 = '/(\$\{[\'"])?#(('.implode('|', $this->fieldPrefixes).'|CELL)[A-Z0-9_]+)#([\'"]\})?/';
		
			foreach($fs[$fKey]['EXTRA_CONVERSION'] as $k=>$v)
			{
				$condVal = $val;
				if(strlen($v['CELL']) > 0 && preg_match($prefixPattern, $v['CELL']))
				{
					if(isset($arFields[$v['CELL']])) $condVal = (string)$arFields[$v['CELL']];
					else $condVal = '';
				}
				elseif(strlen($v['CELL']) > 0 && preg_match('/^CELL(\d+)$/', $v['CELL'], $m2))
				{
					$cellKey = (int)$m2[1] - 1;
					if(isset($arFileFields[$cellKey])) $condVal = (string)$arFileFields[$cellKey];
					else $condVal = '';
				}

				if(strlen($v['FROM']) > 0) $v['FROM'] = preg_replace_callback($prefixPattern2, array($this, 'ConversionExtraReplaceValues'), $v['FROM']);

				if($v['CELL']=='ELSE' || $v['CELL']=='LOADED') $v['WHEN'] = '';
				$condValNum = $this->GetFloatVal($condVal);
				$fromNum = $this->GetFloatVal($v['FROM']);
				if(($v['CELL']=='ELSE' && !$execConv)
					|| ($v['CELL']=='LOADED' && $this->IsAlreadyLoaded($ID))
					|| ($v['WHEN']=='EQ' && $condVal==$v['FROM'])
					|| ($v['WHEN']=='NEQ' && $condVal!=$v['FROM'])
					|| ($v['WHEN']=='GT' && $condValNum > $fromNum)
					|| ($v['WHEN']=='LT' && $condValNum < $fromNum)
					|| ($v['WHEN']=='GEQ' && $condValNum >= $fromNum)
					|| ($v['WHEN']=='LEQ' && $condValNum <= $fromNum)
					|| ($v['WHEN']=='CONTAIN' && strpos($condVal, $v['FROM'])!==false)
					|| ($v['WHEN']=='NOT_CONTAIN' && strpos($condVal, $v['FROM'])===false)
					|| ($v['WHEN']=='REGEXP' && preg_match('/'.ToLower($v['FROM']).'/', ToLower($condVal)))
					|| ($v['WHEN']=='NOT_REGEXP' && !preg_match('/'.ToLower($v['FROM']).'/', ToLower($condVal)))
					|| ($v['WHEN']=='EMPTY' && strlen(trim($condVal))==0)
					|| ($v['WHEN']=='NOT_EMPTY' && strlen(trim($condVal)) > 0)
					|| ($v['WHEN']=='ANY'))
				{
					if(strlen($v['TO']) > 0) $v['TO'] = preg_replace_callback($prefixPattern2, array($this, 'ConversionExtraReplaceValues'), $v['TO']);
					if($v['THEN']=='REPLACE_TO') $val = $v['TO'];
					elseif($v['THEN']=='REMOVE_SUBSTRING' && strlen($v['TO']) > 0) $val = str_replace($v['TO'], '', $val);
					elseif($v['THEN']=='REPLACE_SUBSTRING_TO' && strlen($v['FROM']) > 0) $val = str_replace($v['FROM'], $v['TO'], $val);
					elseif($v['THEN']=='ADD_TO_BEGIN') $val = $v['TO'].$val;
					elseif($v['THEN']=='ADD_TO_END') $val = $val.$v['TO'];
					elseif($v['THEN']=='LCASE') $val = ToLower($val);
					elseif($v['THEN']=='UCASE') $val = ToUpper($val);
					elseif($v['THEN']=='UFIRST') $val = preg_replace_callback('/^(\s*)(.*)$/', create_function('$m', 'return $m[1].ToUpper(substr($m[2], 0, 1)).ToLower(substr($m[2], 1));'), $val);
					elseif($v['THEN']=='UWORD') $val = implode(' ', array_map(create_function('$m', 'return ToUpper(substr($m, 0, 1)).ToLower(substr($m, 1));'), explode(' ', $val)));
					elseif($v['THEN']=='MATH_ROUND') $val = round($this->GetFloatVal($val));
					elseif($v['THEN']=='MATH_MULTIPLY') $val = $this->GetFloatVal($val) * $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_DIVIDE') $val = $this->GetFloatVal($val) / $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_ADD') $val = $this->GetFloatVal($val) + $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_SUBTRACT') $val = $this->GetFloatVal($val) - $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='EXPRESSION') $val = $this->ExecuteFilterExpression($ID, $val, $v['TO'], '');
					elseif($v['THEN']=='STRIP_TAGS') $val = strip_tags($val);
					elseif($v['THEN']=='CLEAR_TAGS') $val = preg_replace('/<([a-z][a-z0-9:]*)[^>]*(\/?)>/i','<$1$2>', $val);
					elseif($v['THEN']=='TRANSLIT')
					{
						$val = \CUtil::translit($val, LANGUAGE_ID);
					}
					elseif($v['THEN']=='NOT_LOAD') $val = false;
					elseif($v['THEN']=='NOT_LOAD_ELEMENT')
					{
						$val = false;
						$this->SetAllowLoad(false);
					}
					$execConv = true;
				}
			}
		}
		return $val;
	}
	
	public function ConversionExtraReplaceValues($m)
	{
		$value = '';
		$paramName = $m[0];
		$quot = "'";
		$isVar = false;
		if(preg_match('/^\$\{([\'"])(.*)[\'"]\}?$/', $paramName, $m2))
		{
			$quot = $m2[1];
			$paramName = $m2[2];
			$isVar = true;
		}
		$key = substr($paramName, 1, -1);
		if(preg_match('/^CELL(\d+)$/', $key, $m2) && is_callable(array($this->ie, 'GetCurrentItemValues')))
		{
			$arFields = $this->ie->GetCurrentItemValues();
			$cellKey = (int)$m2[1] - 1;
			if(isset($arFields[$cellKey])) $value = $arFields[$cellKey];
		}
		else
		{
			$arFields = $this->GetElementFields();
			if(isset($arFields[$key])) $value = $arFields[$key];
		}
		if($isVar)
		{
			if(!isset($this->ie->extraConvParams)) $this->ie->extraConvParams = array();
			$this->ie->extraConvParams[$paramName] = $value;
			return '$this->extraConvParams['.$quot.$paramName.$quot.']';
		}
		else return $value;
	}
	
	public function IsAlreadyLoaded($ID)
	{
		$oProfile = \CKDAImportProfile::getInstance();
		return $oProfile->IsAlreadyLoaded($ID, ($this->isSku ? 'O' : 'E'));
	}
	
	public function GetElementFields($ID = 0)
	{
		if($ID == 0 && is_array($this->elementFields))
		{
			return $this->elementFields;
		}
		
		$arFilter = array(
			'ID' => $ID, 
			'IBLOCK_ID' => $this->iblockId
		);
		if($this->isSku && $this->offersIblockId > 0) $arFilter['IBLOCK_ID'] = $this->offersIblockId;

		$arElementFields = array('ID', 'IBLOCK_ID');
		$arElementNameFields = array();
		$arPropsFields = array();
		$arFieldsProduct = array();
		$arFieldsPrices = array();
		$arFieldsProductStores = array();
		foreach($this->loadElemFields as $field)
		{
			if(strpos($field, 'IE_')===0)
			{
				$key = substr($field, 3);
				$arElementNameFields[] = $key;
				if($key=='PREVIEW_PICTURE_DESCRIPTION' || $key=='DETAIL_PICTURE_DESCRIPTION')
				{
					$key = substr($key, 0, -12);
				}
				if(!in_array($key, $arElementFields)) $arElementFields[] = $key;
			}
			elseif(strpos($field, 'ICAT_PRICE')===0)
			{
				$arPrice = explode('_', substr($field, 10), 2);
				$arFieldsPrices[$arPrice[0]][] = $arPrice[1];
			}
			elseif(strpos($field, 'ICAT_STORE')===0)
			{
				$arStore = explode('_', substr($field, 10), 2);
				$arFieldsProductStores[$arStore[0]][] = $arStore[1];
			}
			elseif(strpos($field, 'ICAT_')===0)
			{
				$arFieldsProduct[] = substr($field, 5);
			}
			elseif(strpos($field, 'IP_PROP')===0)
			{
				$fieldKey = substr($field, 7);
				$arPropsFields[] = $fieldKey;
			}
		}
		
		$arSelectElementFields = $arElementFields;
		if(in_array('IBLOCK_SECTION_IDS', $arElementNameFields) || in_array('IBLOCK_SECTION_PARENT_IDS', $arElementNameFields))
		{
			$arSelectElementFields[] = 'IBLOCK_SECTION_ID';
		}
		if(!empty($arFieldsPrices))
		{
			$arPriceIds = array();
			foreach($arFieldsPrices as $k=>$v)
			{
				$arPriceIds[] = $k;
			}
			$arPriceCodes = array();
			$dbRes = \CCatalogGroup::GetList(array(), array('ID'=>$arPriceIds), false, false, array('ID', 'NAME'));
			while($arCatalogGroup = $dbRes->Fetch())
			{
				$arPriceCodes[$arCatalogGroup['ID']] = $arCatalogGroup['NAME'];
			}
			$arGroupPrices = \CIBlockPriceTools::GetCatalogPrices($arFilter['IBLOCK_ID'], $arPriceCodes);
			if(!is_array($arGroupPrices)) $arGroupPrices = array();
			foreach($arGroupPrices as $k=>$v)
			{
				$arGroupPrices[$k]['CAN_VIEW'] = 1;
				$arSelectElementFields[] = $v['SELECT'];
			}
		}
		
		$arElement2 = array();
		$dbResElements = \CIblockElement::GetList(array(), $arFilter, false, array('nTopCount'=>1), $arSelectElementFields);
		if($arElement = $dbResElements->GetNext())
		{			
			foreach($arElement as $k=>$v)
			{
				if(in_array($k, $arElementFields))
				{
					if($k=='PREVIEW_PICTURE' || $k=='DETAIL_PICTURE')
					{
						$v = $this->GetFileValue($v);
					}
					$arElement2['IE_'.$k] = $v;
				}
			}

			if(in_array('PREVIEW_PICTURE_DESCRIPTION', $arElementNameFields))
			{
				$arElement2['IE_PREVIEW_PICTURE_DESCRIPTION'] = $this->GetFileDescription($arElement['PREVIEW_PICTURE']);
			}
			if(in_array('DETAIL_PICTURE_DESCRIPTION', $arElementNameFields))
			{
				$arElement2['IE_DETAIL_PICTURE_DESCRIPTION'] = $this->GetFileDescription($arElement['DETAIL_PICTURE']);
			}
			if(in_array('IBLOCK_SECTION_IDS', $arElementNameFields))
			{
				$arSectionIds = array();
				$dbRes2 = \CIBlockElement::GetElementGroups($arElement['ID'], true, array('ID'));
				while($arSection = $dbRes2->Fetch())
				{
					$arSectionIds[] = $arSection['ID'];
				}
				$arElement2['IE_IBLOCK_SECTION_IDS'] = implode(',', $arSectionIds);
			}
			if(in_array('IBLOCK_SECTION_PARENT_IDS', $arElementNameFields))
			{
				$arSectionIds = array();
				$dbRes = \Bitrix\Iblock\SectionTable::GetList(array(
					'filter'=>array('ID'=>$arElement['IBLOCK_SECTION_ID']),
					'runtime' => array(new \Bitrix\Main\Entity\ReferenceField(
						'SECTION2',
						'\Bitrix\Iblock\SectionTable',
						array(
							'>=this.LEFT_MARGIN' => 'ref.LEFT_MARGIN',
							'<=this.RIGHT_MARGIN' => 'ref.RIGHT_MARGIN',
							'this.IBLOCK_ID' => 'ref.IBLOCK_ID'
						)
					)), 
					'select'=>array('SID'=>'SECTION2.ID'), 
					'order'=>array('SECTION2.DEPTH_LEVEL'=>'ASC')
				));
				while($arSection = $dbRes->Fetch())
				{
					$arSectionIds[] = $arSection['SID'];
				}
				$arElement2['IE_IBLOCK_SECTION_PARENT_IDS'] = implode(',', $arSectionIds);
			}
			
			if(!empty($arPropsFields))
			{
				$dbRes2 = \CIBlockElement::GetProperty($arElement['IBLOCK_ID'], $arElement['ID'], array(), array());
				while($arProp = $dbRes2->Fetch())
				{
					if(in_array($arProp['ID'], $arPropsFields))
					{
						$fieldKey = 'IP_PROP'.$arProp['ID'];
						$val = $arProp['VALUE'];
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
							$val = $this->GetPropertySectionValue($arProp, $val, $relField);
						}
						elseif($arProp['PROPERTY_TYPE']=='F')
						{
							$val = $this->GetFileValue($val);
						}
						elseif($arProp['PROPERTY_TYPE']=='S' && $arProp['USER_TYPE']=='directory')
						{
							$val = $this->GetHighloadBlockValue($arProp, $val);
						}
						
						/*if($arProp['MULTIPLE']=='Y')
						{
							if(!isset($arElement2[$fieldKey]))
							{
								$arElement2[$fieldKey] = array();
							}
							$arElement2[$fieldKey][] = $val;
						}
						else
						{
							$arElement2[$fieldKey] = $val;
						}*/
						$arElement2[$fieldKey] = $val;
					}
					
					if(in_array($arProp['ID'].'_DESCRIPTION', $arPropsFields))
					{
						$val = $arProp['DESCRIPTION'];
						$key = 'IP_PROP'.$arProp['ID'].'_DESCRIPTION';
						
						/*if($arProp['MULTIPLE']=='Y')
						{
							if(!isset($arElement2[$key])) $arElement2[$key] = array();
							$arElement2[$key][] = $val;
						}
						else
						{
							$arElement2[$key] = $val;
						}*/
						$arElement2[$key] = $val;
					}
				}
			}
			
			if(!empty($arFieldsProduct))
			{
				$dbRes2 = \CCatalogProduct::GetList(array(), array('ID'=>$arElement['ID']), false, array('nTopCount'=>1), array());
				if($arProduct = $dbRes2->Fetch())
				{
					foreach($arProduct as $k=>$v)
					{
						if($k=='VAT_ID')
						{
							if($v)
							{
								if(!isset($this->catalogVats)) $this->catalogVats = array();
								if(!isset($this->catalogVats[$v]))
								{
									$vatPercent = '';
									$dbRes = \CCatalogVat::GetList(array(), array('ID'=>$v), array('RATE'));
									if($arVat = $dbRes->Fetch())
									{
										$vatPercent = $arVat['RATE'];
									}
									$this->catalogVats[$v] = $vatPercent;
								}
								$v = $this->catalogVats[$v];
							}
							else
							{
								$v = '';
							}
						}
						elseif($k=='MEASURE')
						{
							if(!isset($this->catalogMeasure) || !is_array($this->catalogMeasure))
							{
								$this->catalogMeasure = array();
								$dbRes = \CCatalogMeasure::getList(array(), array());
								while($arr = $dbRes->Fetch())
								{
									$this->catalogMeasure[$arr['ID']] = ($arr['SYMBOL_RUS'] ? $arr['SYMBOL_RUS'] : $arr['SYMBOL_INTL']);
								}
							}
							$v = $this->catalogMeasure[$v];
						}
							
						$elemKey = 'ICAT_'.$k;
						$arElement2[$elemKey] = $v;
					}
					
					if(in_array('MEASURE_RATIO', $arFieldsProduct))
					{
						$dbRes = \CCatalogMeasureRatio::getList(array(), array('PRODUCT_ID' => $arElement['ID']), false, false, array('RATIO'));
						if($arRatio = $dbRes->Fetch())
						{
							$arElement2['ICAT_MEASURE_RATIO'] = $arRatio['RATIO'];
						}
						else
						{
							$arElement2['ICAT_MEASURE_RATIO'] = '';
						}
					}
				}
			}
			
			if(!empty($arFieldsPrices))
			{
				foreach($arFieldsPrices as $key=>$arPriceSelectField)
				{
					if(empty($arPriceSelectField)) continue;
					
					if(in_array('PRICE', $arPriceSelectField) && !in_array('CURRENCY', $arPriceSelectField)) $arPriceSelectField[] = 'CURRENCY';
					if(in_array('EXTRA', $arPriceSelectField)) $arPriceSelectField[] = 'EXTRA_ID';
					$dbRes2 = \CPrice::GetList(array(), array('PRODUCT_ID'=>$arElement['ID'], 'CATALOG_GROUP_ID'=>$key), false, array('nTopCount'=>1), $arPriceSelectField);
					if($arPrice = $dbRes2->Fetch())
					{
						foreach($arPrice as $k=>$v)
						{
							$elemKey = 'ICAT_PRICE'.$key.'_'.$k;
							$arElement2[$elemKey] = $v;
						}
						
						if($arPrice['EXTRA_ID'])
						{
							if(!isset($this->catalogPriceExtra)) $this->catalogPriceExtra = array();
							if(!isset($this->catalogPriceExtra[$arPrice['EXTRA_ID']]))
							{
								$extraPercent = '';
								$dbRes = \CExtra::GetList(array(), array('ID'=>$arPrice['EXTRA_ID']), false, array('nTopCount'=>1), array('PERCENTAGE'));
								if($arExtra = $dbRes->Fetch())
								{
									$extraPercent = $arExtra['PERCENTAGE'];
								}
								$this->catalogPriceExtra[$arPrice['EXTRA_ID']] = $extraPercent;
							}
							$elemKey = 'ICAT_PRICE'.$key.'_EXTRA';
							$arElement2[$elemKey] = $this->catalogPriceExtra[$arPrice['EXTRA_ID']];
						}
					}
				}
			}

			if(!empty($arFieldsProductStores))
			{
				foreach($arFieldsProductStores as $key=>$arStoreSelectField)
				{
					$dbRes2 = \CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID'=>$arElement['ID'], 'STORE_ID'=>$key), false, array('nTopCount'=>1), $arStoreSelectField);
					if($arStore = $dbRes2->Fetch())
					{
						foreach($arStore as $k=>$v)
						{
							$elemKey = 'ICAT_STORE'.$key.'_'.$k;
							$arElement2[$elemKey] = $v;
						}
					}
				}
			}
		}
		
		$this->elementId = $ID;
		$this->elementFields = $arElement2;
		return $this->elementFields;
	}
	
	public function GetFileValue($val)
	{
		if($val)
		{
			$arFile = \CFile::GetFileArray($val);
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
	
	public function GetFileDescription($val)
	{
		if($val)
		{
			$arFile = \CFile::GetFileArray($val);
			if($arFile)
			{
				$val = $arFile['DESCRIPTION'];
			}
			else
			{
				$val = '';
			}
		}
		return $val;
	}
	
	public function GetPropertyListValue($arProp, $val)
	{
		if($val)
		{
			if(!isset($this->propVals[$arProp['ID']][$val]))
			{
				$dbRes = \CIBlockPropertyEnum::GetList(array(), array("PROPERTY_ID"=>$arProp['ID'], "ID"=>$val));
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
	
	public function GetPropertyElementValue($arProp, $val, $relField = '')
	{
		if($val)
		{
			$selectField = 'NAME';
			if($relField)
			{
				if(strpos($relField, 'IE_')===0)
				{
					$selectField = substr($relField, 3);
				}
				elseif(strpos($relField, 'IP_PROP')===0)
				{
					$selectField = 'PROPERTY_'.substr($relField, 7);
				}
			}
			
			if(!isset($this->propVals[$arProp['ID']][$selectField][$val]))
			{
				$dbRes = \CIBlockElement::GetList(array(), array("ID"=>$val), false, false, array($selectField));
				if($arElem = $dbRes->GetNext())
				{
					$selectedField = $selectField;
					if(strpos($selectedField, 'PROPERTY_')===0) $selectedField .= '_VALUE';
					$this->propVals[$arProp['ID']][$selectField][$val] = $arElem[$selectedField];
				}
				else
				{
					$this->propVals[$arProp['ID']][$selectField][$val] = '';
				}
			}
			$val = $this->propVals[$arProp['ID']][$selectField][$val];
		}
		return $val;
	}
	
	public function GetPropertySectionValue($arProp, $val, $relField = '')
	{
		if($val)
		{
			$selectField = 'NAME';
			if($relField)
			{
				$selectField = $relField;
			}
			if(!isset($this->propVals[$arProp['ID']][$selectField][$val]))
			{
				$arFilter = array("ID"=>$val);
				if($arProp['LINK_IBLOCK_ID']) $arFilter['IBLOCK_ID'] = $arProp['LINK_IBLOCK_ID'];
				$dbRes = \CIBlockSection::GetList(array(), $arFilter, false, array($selectField));
				if($arSect = $dbRes->GetNext())
				{
					$this->propVals[$arProp['ID']][$selectField][$val] = $arSect[$selectField];
				}
				else
				{
					$this->propVals[$arProp['ID']][$selectField][$val] = '';
				}
			}
			$val = $this->propVals[$arProp['ID']][$selectField][$val];
		}
		return $val;
	}
	
	public function GetHighloadBlockValue($arProp, $val)
	{
		if($val && \CModule::IncludeModule('highloadblock') && $arProp['USER_TYPE_SETTINGS']['TABLE_NAME'])
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
	
	public function GetFloatVal($val)
	{
		return floatval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
	}
	
	public function ExecuteFilterExpression($ID, $val, $expression, $altReturn = true)
	{
		return $this->ie->ExecuteFilterExpression($val, $expression, $altReturn, array('ID'=>$ID));
	}
}