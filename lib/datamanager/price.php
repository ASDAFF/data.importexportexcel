<?php
namespace Bitrix\KdaImportexcel\DataManager;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Price
{
	protected $ie = null;
	protected $logger = null;
	protected $params = null;
	
	public function __construct($ie=false)
	{
		$this->ie = $ie;
		$this->logger = $this->ie->logger;
		$this->params = $this->ie->params;
	}
	
	public function GetFloatVal($val, $precision=0)
	{
		return $this->ie->GetFloatVal($val, $precision);
	}
	
	public function SavePrice($ID, $arPrices, $isOffer = false)
	{
		$basePriceId = $this->GetBasePriceId();
		if(count($arPrices) > 1 && isset($arPrices[$basePriceId]))
		{
			$arPricesOld = $arPrices;
			$arPrices = array($basePriceId => $arPricesOld[$basePriceId]);
			foreach($arPricesOld as $gid=>$arFieldsPrice)
			{
				if($gid!=$basePriceId)
				{
					$arPrices[$gid] = $arFieldsPrice;
				}
			}
		}
		
		foreach($arPrices as $gid=>$arFieldsPrice)
		{
			$pKey = ($isOffer ? 'OFFER_' : '').'ICAT_PRICE'.$gid.'_PRICE';
			$noCurrency = false;
			$extKeys = preg_grep('/^PRICE\|.*QUANTITY_/', array_keys($arFieldsPrice));
			$bPriceExt = (bool)(isset($arFieldsPrice['PRICE_EXT']) && !empty($arFieldsPrice['PRICE_EXT']));
						
			$arFieldsPriceExtra = array();
			if(!isset($arFieldsPrice['PRICE']) && !$bPriceExt && empty($extKeys))
			{
				foreach($arFieldsPrice as $k=>$v)
				{
					if(strpos($k, 'EXTRA')===0)
					{
						if($k=='EXTRA') $arFieldsPriceExtra['PERCENTAGE'] = $v;
						else $arFieldsPriceExtra[substr($k, 6)] = $v;
					}
				}
			}
			else
			{
				foreach($arFieldsPrice as $k=>$v)
				{
					if(strpos($k, 'EXTRA')===0)
					{
						unset($arFieldsPrice[$k]);
					}
				}
			}
			if(!empty($arFieldsPriceExtra))
			{
				$arFilter = array();
				if($arFieldsPriceExtra['ID']) $arFilter = array('ID' => $arFieldsPriceExtra['ID']);
				else
				{
					if(!$arFieldsPriceExtra['NAME'] && $arFieldsPriceExtra['PERCENTAGE']) $arFieldsPriceExtra['NAME'] = $arFieldsPriceExtra['PERCENTAGE'].'%';
					if($arFieldsPriceExtra['NAME']) $arFilter = array('NAME' => $arFieldsPriceExtra['NAME']);
				}	
				if(!empty($arFilter))
				{
					if(!isset($this->arPriceExtras)) $this->arPriceExtras = array();
					if(class_exists('\Bitrix\Catalog\ExtraTable'))
					{
						$dbRes = \Bitrix\Catalog\ExtraTable::GetList(array('filter'=>$arFilter, 'select'=>array('ID'), 'limit'=>1));
					}
					else
					{
						$dbRes = \CExtra::GetList(array(), $arFilter, false, array('nTopCount'=>1), array('ID'));
					}
					if($arExtra = $dbRes->Fetch())
					{
						if(count($arFieldsPriceExtra) > 0)
						{
							if(class_exists('\Bitrix\Catalog\ExtraTable'))
							{
								\Bitrix\Catalog\ExtraTable::Update($arExtra['ID'], $arFieldsPriceExtra);						
							}
							else
							{
								\CExtra::Update($arExtra['ID'], $arFieldsPriceExtra);
							}
						}
						$arFieldsPrice['EXTRA_ID'] = $this->arPriceExtras[$arFieldsPrice['EXTRA']] = $arExtra['ID'];
					}
					else
					{
						if(class_exists('\Bitrix\Catalog\ExtraTable'))
						{
							$result = \Bitrix\Catalog\ExtraTable::Add($arFieldsPriceExtra);
							$pid = (int)$result->getId();							
						}
						else
						{
							$pid = \CExtra::Add($arFieldsPriceExtra);
						}
						if($pid > 0)
						{
							$arFieldsPrice['EXTRA_ID'] = $this->arPriceExtras[$arFieldsPrice['EXTRA']] = $pid;
						}
					}
				}
			}

			if((!isset($arFieldsPrice['PRICE'])/* || $arFieldsPrice['PRICE']===''*/)
				&& (!isset($arFieldsPrice['CURRENCY']) || !$arFieldsPrice['CURRENCY'])
				&& (!isset($arFieldsPrice['EXTRA_ID'])) && empty($extKeys)
				&& !$bPriceExt) continue;

			$recalcPrice = (bool)($gid!=$basePriceId && isset($arFieldsPrice['EXTRA_ID']));
			$recalcPrice2 = (bool)($gid==$basePriceId);
			if(!$arFieldsPrice['CURRENCY'])
			{
				$arFieldsPrice['CURRENCY'] = $this->params['DEFAULT_CURRENCY'];
				$noCurrency = true;
			}
			$arFieldsPrice['CURRENCY'] = $this->GetCurrencyVal($arFieldsPrice['CURRENCY']);
			
			$arSubPrices = array();
			if($bPriceExt)
			{
				$arParts = array_map('trim', explode(';', $arFieldsPrice['PRICE_EXT']));
				foreach($arParts as $part)
				{
					if(strpos($part, ':')!==false)
					{
						list($qf, $qt, $p, $c) = explode(':', $part);
						$arSubPrices[] = array(
							'QUANTITY_FROM' => $this->GetFloatVal($qf),
							'QUANTITY_TO' => $this->GetFloatVal($qt),
							'PRICE' => $this->GetFloatVal($p),
							'CURRENCY' => $c
						);
					}
					else
					{
						$arSubPrices[] = array(
							'PRICE' => $this->GetFloatVal($part)
						);
					}
				}
				unset($arFieldsPrice['PRICE_EXT']);
				$noCurrency = false;
			}
			elseif(!empty($extKeys))
			{
				foreach($extKeys as $extKey)
				{
					$arPriceKeys = explode('|', $extKey);
					$arSubPrice = array(array_shift($arPriceKeys) => $arFieldsPrice[$extKey]);
					foreach($arPriceKeys as $v)
					{
						$arVal = explode('=', $v);
						$arSubPrice[$arVal[0]] = $arVal[1];
					}
					if(!array_key_exists('CURRENCY', $arSubPrice)) $arSubPrice['CURRENCY'] = $arFieldsPrice['CURRENCY'];
					$arSubPrice['CURRENCY'] = $this->GetCurrencyVal($arSubPrice['CURRENCY']);
					$arSubPrices[] = $arSubPrice;
					unset($arFieldsPrice[$extKey]);
				}
				if(array_key_exists('CURRENCY', $arFieldsPrice)) unset($arFieldsPrice['CURRENCY']);
			}
			if(!$bPriceExt || empty($arSubPrices))
			{
				if(isset($arFieldsPrice['PRICE']) || (isset($arFieldsPrice['CURRENCY']) && !isset($arFieldsPrice['EXTRA_ID'])))
				{
					$arSubPrices[] = array_intersect_key($arFieldsPrice, array('PRICE'=>'', 'CURRENCY'=>''));
				}
				elseif(isset($arFieldsPrice['EXTRA_ID']))
				{
					//$arSubPrices[] = array('EXTRA_ID' => $arFieldsPrice['EXTRA_ID']);
					$arSubPrices = array();
					$dbRes = $this->GetList(array('ID'=>'ASC'), array('PRODUCT_ID'=>$ID, 'CATALOG_GROUP_ID'=>$basePriceId), false, false, array('ID', 'QUANTITY_FROM', 'QUANTITY_TO'));
					while($arr = $dbRes->Fetch())
					{
						$arSubPrices[] = array('EXTRA_ID'=>$arFieldsPrice['EXTRA_ID'], 'QUANTITY_FROM'=>$arr['QUANTITY_FROM'], 'QUANTITY_TO'=>$arr['QUANTITY_TO']);
					}
				}
			}

			$arFieldsPriceOrig = $arFieldsPrice;
			$arUpdatedIds = array();
			$bDeleteOld = true;
			foreach($arSubPrices as $arSubPrice)
			{
				$arFieldsPrice = array_merge($arFieldsPriceOrig, $arSubPrice);
				if(!isset($arFieldsPrice['QUANTITY_FROM'])) $arFieldsPrice['QUANTITY_FROM'] = false;
				if(!isset($arFieldsPrice['QUANTITY_TO'])) $arFieldsPrice['QUANTITY_TO'] = false;
				if(isset($arFieldsPrice['PRICE']))
				{
					if(strlen(trim($arFieldsPrice['PRICE']))==0) $arFieldsPrice['PRICE'] = '-';
					if($arFieldsPrice['PRICE']!=='-') $arFieldsPrice['PRICE'] = $this->GetFloatVal($arFieldsPrice['PRICE'], 2);
				}
				
				$arKeys = array_merge(array('ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'QUANTITY_FROM', 'QUANTITY_TO', 'CURRENCY', 'PRICE', 'EXTRA_ID'), array_keys($arFieldsPrice));
				$dbRes = $this->GetList(array('ID'=>'ASC'), array('PRODUCT_ID'=>$ID, 'CATALOG_GROUP_ID'=>$gid, '!ID' => $arUpdatedIds), false, false, $arKeys);
				if($arPrice = $dbRes->Fetch())
				{
					if($arPrice['EXTRA_ID'] > 0 && !isset($arFieldsPrice['EXTRA_ID'])) $arFieldsPrice['EXTRA_ID'] = 0;
					if($arFieldsPrice['PRICE']!=='-')
					{
						if($recalcPrice)
						{
							$arFieldsPrice['PRODUCT_ID'] = $ID;
							$arFieldsPrice['CATALOG_GROUP_ID'] = $gid;
						}
						else
						{
							/*Delete unchanged data*/
							if($noCurrency) unset($arFieldsPrice['CURRENCY']);
							if($this->params['ELEMENT_IMAGES_FORCE_UPDATE']!='Y')
							{
								foreach($arFieldsPrice as $k=>$v)
								{
									if($v==$arPrice[$k] && $k!='QUANTITY_FROM' && $k!='QUANTITY_TO')
									{
										unset($arFieldsPrice[$k]);
									}
								}
								if(isset($arFieldsPrice['QUANTITY_FROM']) && isset($arFieldsPrice['QUANTITY_TO']) && $arFieldsPrice['QUANTITY_FROM']==$arPrice['QUANTITY_FROM'] && $arFieldsPrice['QUANTITY_TO']==$arPrice['QUANTITY_TO'])
								{
									unset($arFieldsPrice['QUANTITY_FROM'], $arFieldsPrice['QUANTITY_TO']);
								}
							}
							/*/Delete unchanged data*/
						}
						if(!empty($arFieldsPrice))
						{
							$this->logger->AddElementChanges("ICAT_PRICE".$gid.'_', $arFieldsPrice, $arPrice);
							if($recalcPrice2)
							{
								$arFieldsPrice = array_merge($arPrice, $arFieldsPrice);
								unset($arFieldsPrice['ID']);
							}
							$arFieldsPrice['PRODUCT_ID'] = $ID;
							$this->Update($arPrice["ID"], $arFieldsPrice, ($recalcPrice || $recalcPrice2));
						}
					}
					else
					{
						$this->Delete($arPrice["ID"]);
						$this->logger->AddElementChanges("ICAT_PRICE".$gid.'_', $arFieldsPrice, $arPrice);
					}
					$arUpdatedIds[] = $arPrice["ID"];
				}
				else
				{
					$bDeleteOld = false;
					if($arFieldsPrice['PRICE']!=='-')
					{
						$arFieldsPrice['PRODUCT_ID'] = $ID;
						$arFieldsPrice['CATALOG_GROUP_ID'] = $gid;
						$priceId = $this->Add($arFieldsPrice, ($recalcPrice || $recalcPrice2));
						$this->logger->AddElementChanges("ICAT_PRICE".$gid.'_', $arFieldsPrice);
						if($priceId) $arUpdatedIds[] = $priceId;
					}
				}
			}
			
			if($bDeleteOld)
			{
				$dbRes = $this->GetList(array('ID'=>'ASC'), array('PRODUCT_ID'=>$ID, 'CATALOG_GROUP_ID'=>$gid, '!ID' => $arUpdatedIds), false, false, array('ID'));
				while($arPrice = $dbRes->Fetch())
				{
					$this->Delete($arPrice["ID"]);
				}
			}
		}
	}
	
	public function GetBasePriceId()
	{
		if(!$this->catalogBasePriceId)
		{
			$arBasePrice = \CCatalogGroup::GetBaseGroup();
			$this->catalogBasePriceId = $arBasePrice['ID'];
		}
		return $this->catalogBasePriceId;
	}
	
	public function GetCurrencyVal($val)
	{
		if(!isset($this->arCurrencies))
		{
			$this->arCurrencies = array();
			if(Loader::includeModule('currency'))
			{
				$dbRes = \CCurrency::GetList(($by="sort"), ($order="asc"), LANGUAGE_ID);
				while($arr = $dbRes->Fetch())
				{
					$this->arCurrencies[$arr['CURRENCY']] = array(
						'FULL_NAME' => ToLower($arr['FULL_NAME']),
						'FORMAT_STRING' => ToLower(trim($arr['FORMAT_STRING'], '#. ')),
					);
				}
			}
		}
		if(!isset($this->arCurrencies[$val]))
		{
			if($val=='RUR' && isset($this->arCurrencies['RUB'])) $val = 'RUB';
			elseif($val=='€' && isset($this->arCurrencies['EUR'])) $val = 'EUR';
			elseif($val=='$' && isset($this->arCurrencies['USD'])) $val = 'USD';
			else
			{
				$compVal = ToLower(trim($val, '#. '));
				foreach($this->arCurrencies as $k=>$v)
				{
					if(in_array($compVal, $v))
					{
						$val = $k;
						break;
					}
				}
			}
		}
		if(!isset($this->arCurrencies[$val]))
		{
			$val = $this->params['DEFAULT_CURRENCY'];
		}
		return $val;
	}
	
	public function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		return \CPrice::GetList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
	}
	
	public function Add($arFields, $boolRecalc = false)
	{
		return \CPrice::Add($arFields, $boolRecalc);
	}
	
	public function Update($ID, $arFields, $boolRecalc = false)
	{
		return \CPrice::Update($ID, $arFields, $boolRecalc);
	}
	
	public function Delete($ID)
	{
		return \CPrice::Delete($ID);
	}
}