<?php
use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CKDAFieldList {
	function __construct($params = array())
	{
		$this->uid = $params['ELEMENT_UID'];
		$this->uidSku = $params['ELEMENT_UID_SKU'];
		$this->isSku = !empty($params['ELEMENT_UID_SKU']);
		$this->showStyles = (bool)($params['ELEMENT_NOT_LOAD_STYLES']!='Y');
		$this->iblockId = (int)$params['IBLOCK_ID'];
		
		$this->sectionLevels = (is_numeric($params['MAX_SECTION_LEVEL']) > 0 ? $params['MAX_SECTION_LEVEL'] : 5);
		$this->sectionLevels = max(0, $this->sectionLevels);
		$this->sectionLevels = min(100, $this->sectionLevels);
	}
	
	public static function GetIblockElementFields()
	{
		return array(
			"IE_NAME" => array(
				"uid" => "Y",
				"name" => Loc::getMessage("KDA_IE_FI_NAME"),
			),
			"IE_ID" => array(
				"uid" => "Y",
				"name" => Loc::getMessage("KDA_IE_FI_ID"),
			),
			"IE_XML_ID" => array(
				"uid" => "Y",
				"name" => Loc::getMessage("KDA_IE_FI_UNIXML"),
			),
			"IE_CODE" => array(
				"uid" => "Y",
				"name" => Loc::getMessage("KDA_IE_FI_CODE"),
			),
			"IE_PREVIEW_PICTURE" => array(
				"name" => Loc::getMessage("KDA_IE_FI_CATIMG"),
			),
			"IE_PREVIEW_PICTURE_DESCRIPTION" => array(
				"name" => Loc::getMessage("KDA_IE_FI_CATIMG_DESCRIPTION"),
			),
			"IE_PREVIEW_TEXT" => array(
				"name" => Loc::getMessage("KDA_IE_FI_CATDESCR"),
			),
			"IE_PREVIEW_TEXT|PREVIEW_TEXT_TYPE=text" => array(
				"name" => Loc::getMessage("KDA_IE_FI_CATDESCR").' ('.Loc::getMessage("KDA_IE_TEXTTYPE").')',
			),
			"IE_PREVIEW_TEXT|PREVIEW_TEXT_TYPE=html" => array(
				"name" => Loc::getMessage("KDA_IE_FI_CATDESCR").' (html)',
			),
			"IE_DETAIL_PICTURE" => array(
				"name" => Loc::getMessage("KDA_IE_FI_DETIMG"),
			),
			"IE_DETAIL_PICTURE_DESCRIPTION" => array(
				"name" => Loc::getMessage("KDA_IE_FI_DETIMG_DESCRIPTION"),
			),
			"IE_DETAIL_TEXT" => array(
				"name" => Loc::getMessage("KDA_IE_FI_DETDESCR"),
			),
			"IE_DETAIL_TEXT|DETAIL_TEXT_TYPE=text" => array(
				"name" => Loc::getMessage("KDA_IE_FI_DETDESCR").' ('.Loc::getMessage("KDA_IE_TEXTTYPE").')',
			),
			"IE_DETAIL_TEXT|DETAIL_TEXT_TYPE=html" => array(
				"name" => Loc::getMessage("KDA_IE_FI_DETDESCR").' (html)',
			),
			"IE_ACTIVE" => array(
				"name" => Loc::getMessage("KDA_IE_FI_ACTIV"),
			),
			"IE_ACTIVE_FROM" => array(
				"uid" => "Y",
				"name" => Loc::getMessage("KDA_IE_FI_ACTIVFROM"),
			),
			"IE_ACTIVE_TO" => array(
				"uid" => "Y",
				"name" => Loc::getMessage("KDA_IE_FI_ACTIVTO"),
			),
			"IE_SORT" => array(
				"name" => Loc::getMessage("KDA_IE_FI_SORT"),
			),
			"IE_TAGS" => array(
				"uid" => "Y",
				"name" => Loc::getMessage("KDA_IE_FI_TAGS"),
			),
			"IE_DATE_CREATE" => array(
				"name" => Loc::getMessage("KDA_IE_FI_DATE_CREATE"),
			),
			"IE_CREATED_BY" => array(
				"name" => Loc::getMessage("KDA_IE_FI_CREATED_BY"),
			),
			"IE_SHOW_COUNTER" => array(
				"name" => Loc::getMessage("KDA_IE_FI_SHOW_COUNTER"),
			),
		);
	}
	
	public static function GetIblockElementFieldsForStat()
	{
		$arFields = self::GetIblockElementFields();
		$arFields["IE_MODIFIED_BY"] = array(
			"name" => Loc::getMessage("KDA_IE_FI_MODIFIED_BY"),
		);
		return $arFields;
	}
	
	public static function GetIblockElementDefaultFields()
	{
		return array(
			"IE_XML_ID" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_UNIXML"),
			),
			"IE_CODE" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_CODE"),
			),
			"IE_ACTIVE_FROM" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_ACTIVFROM"),
			),
			"IE_ACTIVE_TO" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_ACTIVTO"),
			),
			"IE_SORT" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_SORT"),
			),
			"IE_TAGS" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_TAGS"),
			),
			"IE_PREVIEW_TEXT" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_CATDESCR"),
			),
			"IE_PREVIEW_TEXT_TYPE" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_CATDESCR_TYPE"),
			),
			"IE_DETAIL_TEXT" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_DETDESCR"),
			),
			"IE_DETAIL_TEXT_TYPE" => array(
				"NAME" => Loc::getMessage("KDA_IE_FI_DETDESCR_TYPE"),
			),
		);
	}
	
	public static function GetCatalogDefaultFields($IBLOCK_ID)
	{
		$arDefaultCatFields = array();
		if(CModule::IncludeModule('catalog'))
		{
			$dbRes = CCatalog::GetList(array("ID"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID));
			if($arCatalog = $dbRes->Fetch())
			{
				$arDefaultCatFields["ICAT_PURCHASING_PRICE"] = array('NAME' => Loc::getMessage("KDA_IE_FI_PURCHASING_PRICE"));
				
				$dbRes = CCatalogStore::GetList(array("SORT"=>"ID"), array(), false, false, array("ID", "TITLE", "ADDRESS"));
				while($arStore = $dbRes->Fetch())
				{
					$arDefaultCatFields["ICAT_STORE".$arStore["ID"]."_AMOUNT"] = array('NAME' => Loc::getMessage("KDA_IE_LIST_QUANTITY_STORE").' "'.(strlen($arStore["TITLE"]) > 0 ? $arStore["TITLE"] : $arStore["ADDRESS"]).'"');
				}
				
				$dbRes = CCatalogGroup::GetList(array("SORT" => "ASC"));
				while($arPriceType = $dbRes->Fetch())
				{
					$arDefaultCatFields["ICAT_PRICE".$arPriceType["ID"]."_PRICE"] = array('NAME' => Loc::getMessage("KDA_IE_LIST_PRICE").' "'.($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]).'"');
				}
				
				$arDefaultCatFields["ICAT_QUANTITY_TRACE"] = array('NAME' => Loc::getMessage("KDA_IE_LIST_QUANTITY_TRACE"));
				$arDefaultCatFields["ICAT_CAN_BUY_ZERO"] = array('NAME' => Loc::getMessage("KDA_IE_LIST_CAN_BUY_ZERO"));
				//$arDefaultCatFields["ICAT_NEGATIVE_AMOUNT_TRACE"] = array('NAME' => Loc::getMessage("KDA_IE_LIST_NEGATIVE_AMOUNT_TRACE"));
				$arDefaultCatFields["ICAT_SUBSCRIBE"] = array('NAME' => Loc::getMessage("KDA_IE_LIST_SUBSCRIBE"));
			}
		}
		return $arDefaultCatFields;
	}
	
	public static function GetCatalogDiscountDefaultFields($IBLOCK_ID)
	{
		$arDiscountFields = array();
		if(Loader::includeModule('catalog'))
		{
			if($IBLOCK_ID!==true)
			{
				$dbRes = CCatalog::GetList(array("ID"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID));
				$arCatalog = $dbRes->Fetch();
			}
			
			if($arCatalog || $IBLOCK_ID===true)
			{
				if((string)Option::get('sale', 'use_sale_discount_only') == 'Y')
				{
					$arDiscountFields = array(
						"ICAT_DISCOUNT_VALUE|VALUE_TYPE=P" => array("NAME" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PERCENT")),
						"ICAT_DISCOUNT_VALUE|VALUE_TYPE=F" => array("NAME" => Loc::getMessage("KDA_IE_FI_DISCOUNT_SUM")),
						"ICAT_DISCOUNT_VALUE|VALUE_TYPE=S" => array("NAME" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PRICE")),
					);
				}
				else
				{
					$arDiscountFields = array(
						"ICAT_DISCOUNT_VALUE|VALUE_TYPE=P" => array("NAME" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PERCENT")),
						"ICAT_DISCOUNT_VALUE|VALUE_TYPE=F" => array("NAME" => Loc::getMessage("KDA_IE_FI_DISCOUNT_SUM")),
						"ICAT_DISCOUNT_VALUE|VALUE_TYPE=S" => array("NAME" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PRICE")),
					);
				}
			}
		}
		return $arDiscountFields;
	}
	
	public static function GetIblockIpropTemplates()
	{
		return array(
			"IPROP_TEMP_ELEMENT_META_TITLE" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_META_TITLE"),
			"IPROP_TEMP_ELEMENT_META_KEYWORDS" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_META_KEYWORDS"),
			"IPROP_TEMP_ELEMENT_META_DESCRIPTION" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_META_DESCRIPTION"),
			"IPROP_TEMP_ELEMENT_PAGE_TITLE" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_PAGE_TITLE"),
			"IPROP_TEMP_ELEMENT_PREVIEW_PICTURE_FILE_ALT" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_PREVIEW_PICTURE_FILE_ALT"),
			"IPROP_TEMP_ELEMENT_PREVIEW_PICTURE_FILE_TITLE" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_PREVIEW_PICTURE_FILE_TITLE"),
			"IPROP_TEMP_ELEMENT_PREVIEW_PICTURE_FILE_NAME" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_PREVIEW_PICTURE_FILE_NAME"),
			"IPROP_TEMP_ELEMENT_DETAIL_PICTURE_FILE_ALT" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_DETAIL_PICTURE_FILE_ALT"),
			"IPROP_TEMP_ELEMENT_DETAIL_PICTURE_FILE_TITLE" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_DETAIL_PICTURE_FILE_TITLE"),
			"IPROP_TEMP_ELEMENT_DETAIL_PICTURE_FILE_NAME" => Loc::getMessage("KDA_IE_IPROP_ELEMENT_DETAIL_PICTURE_FILE_NAME"),
		);
	}
	
	public function GetIblockSectionFields($i, $IBLOCK_ID = false)
	{
		$arSections = array(
			'ISECT'.$i.'_NAME' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_NAME")
			),
			'ISECT'.$i.'_CODE' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_CODE")
			),
			'ISECT'.$i.'_ID' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ID")
			),
			'ISECT'.$i.'_XML_ID' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_XML_ID")
			),
			'ISECT'.$i.'_ACTIVE' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ACTIVE")
			),
			'ISECT'.$i.'_SORT' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SORT")
			),
			'ISECT'.$i.'_PICTURE' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_PICTURE")
			),
			'ISECT'.$i.'_DETAIL_PICTURE' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_DETAIL_PICTURE")
			),
			'ISECT'.$i.'_DESCRIPTION' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_DESCRIPTION"),
			) ,
			'ISECT'.$i.'_DESCRIPTION|DESCRIPTION_TYPE=html' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_DESCRIPTION").' (html)',
			) ,
			'ISECT'.$i.'_IPROP_TEMP_SECTION_META_TITLE' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_META_TITLE"),
			) ,
			'ISECT'.$i.'_IPROP_TEMP_SECTION_META_KEYWORDS' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_META_KEYWORDS"),
			) ,
			'ISECT'.$i.'_IPROP_TEMP_SECTION_META_DESCRIPTION' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_META_DESCRIPTION"),
			) ,
			'ISECT'.$i.'_IPROP_TEMP_SECTION_PAGE_TITLE' => array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_PAGE_TITLE"),
			) ,
		);
		
		if(strlen($i)==0)
		{
			$arSections['ISECT'.$i.'_IPROP_TEMP_SECTION_PICTURE_FILE_ALT'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_PICTURE_FILE_ALT")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_SECTION_PICTURE_FILE_TITLE'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_PICTURE_FILE_TITLE")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_SECTION_PICTURE_FILE_NAME'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_PICTURE_FILE_NAME")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_SECTION_DETAIL_PICTURE_FILE_ALT'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_DETAIL_PICTURE_FILE_ALT")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_SECTION_DETAIL_PICTURE_FILE_TITLE'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_DETAIL_PICTURE_FILE_TITLE")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_SECTION_DETAIL_PICTURE_FILE_NAME'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_DETAIL_PICTURE_FILE_NAME")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_META_TITLE'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_META_TITLE")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_META_KEYWORDS'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_META_KEYWORDS")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_META_DESCRIPTION'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_META_DESCRIPTION")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_PAGE_TITLE'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_PAGE_TITLE")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_PREVIEW_PICTURE_FILE_ALT'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_PREVIEW_PICTURE_FILE_ALT")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_PREVIEW_PICTURE_FILE_TITLE'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_PREVIEW_PICTURE_FILE_TITLE")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_PREVIEW_PICTURE_FILE_NAME'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_PREVIEW_PICTURE_FILE_NAME")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_DETAIL_PICTURE_FILE_ALT'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_DETAIL_PICTURE_FILE_ALT")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_DETAIL_PICTURE_FILE_TITLE'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_DETAIL_PICTURE_FILE_TITLE")
			);
			$arSections['ISECT'.$i.'_IPROP_TEMP_ELEMENT_DETAIL_PICTURE_FILE_NAME'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_ELEMENT_DETAIL_PICTURE_FILE_NAME")
			);
			
			
			$arSections['ISECT'.$i.'_IBLOCK_SECTION_ID'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_IBLOCK_SECTION_ID")
			);
			$arSections['ISECT'.$i.'_SECTION_PROPERTIES'] = array(
				"name" => Loc::getMessage("KDA_ISECT_FI_SECTION_PROPERTIES")
			);
		}
		
		if($IBLOCK_ID)
		{
			if(!isset($this->arSectionsProps)) $this->arSectionsProps = array();
			if(!isset($this->arSectionsProps[$IBLOCK_ID]))
			{
				$dbRes = CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'IBLOCK_'.$IBLOCK_ID.'_SECTION', 'LANG' => LANGUAGE_ID));
				$arProps = array();
				while($arr = $dbRes->Fetch())
				{
					$name = ($arr['EDIT_FORM_LABEL'] ? $arr['EDIT_FORM_LABEL'].' ('.$arr['FIELD_NAME'].')' : $arr['FIELD_NAME']);
					$arProps[$arr['FIELD_NAME']] = array('name' => $name);
				}
				$this->arSectionsProps[$IBLOCK_ID] = $arProps;
			}
			
			if(!empty($this->arSectionsProps[$IBLOCK_ID]))
			{
				foreach($this->arSectionsProps[$IBLOCK_ID] as $k=>$v)
				{
					$arSections['ISECT'.$i.'_'.$k] = $v;
				}
			}
		}
		
		return $arSections;
	}
	
	public static function GetIblockSectionElementFields()
	{
		$arFields = array(
			'IE_SECTION_PATH' => array(
				"name" => Loc::getMessage("KDA_IE_FI_SECTION_PATH")
			)
		);
		return $arFields;
	}
	
	public function GetLineActions()
	{
		$arMenu = array();
		/*$arMenu['SET_TITLES'] = array(
			'TEXT' => Loc::getMessage("KDA_IE_SET_TITLES"),
			'TITLE' => Loc::getMessage("KDA_IE_TITLES_TITLE")
		);
		$arMenu['SET_HINTS'] = array(
			'TEXT' => Loc::getMessage("KDA_IE_SET_HINTS"),
			'TITLE' => Loc::getMessage("KDA_IE_HINTS_TITLE")
		);*/
		if($this->showStyles)
		{
			for($i=1; $i<$this->sectionLevels+1; $i++)
			{
				$arMenu['SECTION']['SET_SECTION_'.$i] = array(
					'TEXT' => sprintf(Loc::getMessage("KDA_IE_SET_SECTION_LEVEL"), $i),
					'TITLE' => sprintf(Loc::getMessage("KDA_IE_SECTION_LEVEL_TITLE"), $i)
				);
			}
			$arMenu['SECTION']['SET_SECTION_PATH'] = array(
				'TEXT' => Loc::getMessage("KDA_IE_SET_SECTION_PATH"),
				'TITLE' => Loc::getMessage("KDA_IE_SECTION_PATH_TITLE")
			);
			
			$arProps = $this->GetIblockProperties($this->iblockId);
			foreach($arProps as $k=>$v)
			{
				if(preg_match('/^IP_PROP(\d+)$/', $v['value'], $m))
				{
					$arMenu['PROPERTY']['SET_PROPERTY_'.$m[1]] = array(
						'TEXT' => $v['name'],
						'TITLE' => $v['name']
					);
				}
			}
		}
		/*$arMenu['REMOVE_ACTION'] = array(
			'TEXT' => Loc::getMessage("KDA_IE_CANCEL_ACTION"),
			'TITLE' => Loc::getMessage("KDA_IE_CANCEL_ACTION")
		);*/
		return $arMenu;
	}
	
	public function GetCatalogFieldsCached()
	{
		if(!Loader::includeModule('catalog')) return array();
		
		if(!isset($this->catalogFieldsCached))
		{
			$arCatalogFieldsOrig = self::GetCatalogFields(true);
			$arCatalogFields = array();
			if(is_array($arCatalogFieldsOrig))
			{
				foreach($arCatalogFieldsOrig as $k=>$v)
				{
					$arCatalogFields[$v['value']] = $v['name'];
				}
			}
			$this->catalogFieldsCached = $arCatalogFields;
		}
		return $this->catalogFieldsCached;
	}
	
	public static function GetCatalogFields($IBLOCK_ID)
	{
		$arCatalogFields = array();
		if(Loader::includeModule('catalog'))
		{
			if($IBLOCK_ID!==true)
			{
				$dbRes = CCatalog::GetList(array("ID"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID));
				$arCatalog = $dbRes->Fetch();
			}
			
			if($arCatalog || $IBLOCK_ID===true)
			{
				$arCatalogFields[] = array(
					"value" => "ICAT_PURCHASING_PRICE",
					"name" => Loc::getMessage("KDA_IE_FI_PURCHASING_PRICE"),
				);
				$arCatalogFields[] = array(
					"value" => "ICAT_PURCHASING_CURRENCY",
					"name" => Loc::getMessage("KDA_IE_FI_PRICE_CURRENCY").' "'.Loc::getMessage("KDA_IE_FI_PURCHASING_PRICE").'"',
				);
			
				$dbPriceType = CCatalogGroup::GetList(array("SORT" => "ASC"));
				while($arPriceType = $dbPriceType->Fetch())
				{
					$arCatalogFields[] = array(
						"value" => "ICAT_PRICE".$arPriceType["ID"]."_PRICE",
						"name" => Loc::getMessage("KDA_IE_FI_PRICE_NAME").' "'.($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]).'"',
					);
					$arCatalogFields[] = array(
						"value" => "ICAT_PRICE".$arPriceType["ID"]."_CURRENCY",
						"name" => Loc::getMessage("KDA_IE_FI_PRICE_CURRENCY").' "'.($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]).'"',
					);
					$arCatalogFields[] = array(
						"value" => "ICAT_PRICE".$arPriceType["ID"]."_PRICE_EXT",
						"name" => Loc::getMessage("KDA_IE_FI_PRICE_NAME").' "'.($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]).'" - '.Loc::getMessage("KDA_IE_FI_PRICE_EXT_MODE"),
						"sortable" => "Y"
					);
					if($arPriceType['BASE']!='Y')
					{
						$arCatalogFields[] = array(
							"value" => "ICAT_PRICE".$arPriceType["ID"]."_EXTRA",
							"name" => Loc::getMessage("KDA_IE_FI_PRICE_EXTRA").' "'.($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]).'" ('.Loc::getMessage("KDA_IE_FI_PRICE_EXTRA_MEASURE").')',
						);
						$arCatalogFields[] = array(
							"value" => "ICAT_PRICE".$arPriceType["ID"]."_EXTRA_NAME",
							"name" => Loc::getMessage("KDA_IE_FI_PRICE_EXTRA_NAME").' "'.($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]).'"',
						);
						$arCatalogFields[] = array(
							"value" => "ICAT_PRICE".$arPriceType["ID"]."_EXTRA_ID",
							"name" => Loc::getMessage("KDA_IE_FI_PRICE_EXTRA_ID").' "'.($arPriceType["NAME_LANG"] ? $arPriceType["NAME_LANG"] : $arPriceType["NAME"]).'"',
						);
					}
				}
				
				$arCatalogFields[] = array(
					"value" => "ICAT_QUANTITY",
					"name" => Loc::getMessage("KDA_IE_FI_QUANTITY"),
				);
				$arCatalogFields[] = array(
					"value" => "ICAT_QUANTITY_RESERVED",
					"name" => Loc::getMessage("KDA_IE_FI_QUANTITY_RESERVED"),
				);
				
				$dbRes = CCatalogStore::GetList(array("SORT"=>"ID"), array(), false, false, array("ID", "TITLE", "ADDRESS"));
				if($dbRes->SelectedRowsCount() > 0)
				{
					$arCatalogFields[] = array(
						"value" => "ICAT_LIST_STORES",
						'name' => Loc::getMessage("KDA_IE_LIST_STORES")
					);
				}
				while($arStore = $dbRes->Fetch())
				{
					if(strlen($arStore['TITLE'])==0 && $arStore['ADDRESS']) $arStore['TITLE'] = $arStore['ADDRESS'];
					$arCatalogFields[] = array(
						"value" => "ICAT_STORE".$arStore["ID"]."_AMOUNT",
						"name" => Loc::getMessage("KDA_IE_FI_QUANTITY_STORE").' "'.$arStore["TITLE"].'"'
					);
				}
				
				$arCatalogFields[] = array(
					"value" => "ICAT_WEIGHT",
					"name" => Loc::getMessage("KDA_IE_FI_WEIGHT"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_LENGTH",
					"name" => Loc::getMessage("KDA_IE_FI_LENGTH"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_WIDTH",
					"name" => Loc::getMessage("KDA_IE_FI_WIDTH"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_HEIGHT",
					"name" => Loc::getMessage("KDA_IE_FI_HEIGHT"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_MEASURE",
					"name" => Loc::getMessage("KDA_IE_FI_MEASURE"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_MEASURE_RATIO",
					"name" => Loc::getMessage("KDA_IE_FI_MEASURE_RATIO"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_VAT_INCLUDED",
					"name" => Loc::getMessage("KDA_IE_FI_VAT_INCLUDED"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_VAT_ID",
					"name" => Loc::getMessage("KDA_IE_FI_VAT_ID"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_BARCODE",
					"name" => Loc::getMessage("KDA_IE_FI_BARCODE"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_QUANTITY_TRACE",
					"name" => Loc::getMessage("KDA_IE_FI_QUANTITY_TRACE"),
				);
				
				$arCatalogFields[] = array(
					"value" => "ICAT_CAN_BUY_ZERO",
					"name" => Loc::getMessage("KDA_IE_FI_CAN_BUY_ZERO"),
				);
				
				/*$arCatalogFields[] = array(
					"value" => "ICAT_NEGATIVE_AMOUNT_TRACE",
					"name" => Loc::getMessage("KDA_IE_FI_NEGATIVE_AMOUNT_TRACE"),
				);*/
				
				$arCatalogFields[] = array(
					"value" => "ICAT_SUBSCRIBE",
					"name" => Loc::getMessage("KDA_IE_FI_SUBSCRIBE"),
				);
			}
		}
		return (!empty($arCatalogFields) ? $arCatalogFields : false);
	}
	
	public static function GetCatalogDiscountFields($IBLOCK_ID)
	{
		$arDiscountFields = array();
		if(Loader::includeModule('catalog'))
		{
			if($IBLOCK_ID!==true)
			{
				$dbRes = CCatalog::GetList(array("ID"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID));
				$arCatalog = $dbRes->Fetch();
			}
			
			if($arCatalog || $IBLOCK_ID===true)
			{
				if((string)Option::get('sale', 'use_sale_discount_only') == 'Y')
				{
					$arDiscountFields = array(
						array(
							"value" => "ICAT_DISCOUNT_NAME",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_NAME"),
						),
						array(
							"value" => "ICAT_DISCOUNT_XML_ID",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_XML_ID"),
						),
						array(
							"value" => "ICAT_DISCOUNT_ACTIVE_FROM",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_ACTIVE_FROM"),
						),
						array(
							"value" => "ICAT_DISCOUNT_ACTIVE_TO",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_ACTIVE_TO"),
						),
						array(
							"value" => "ICAT_DISCOUNT_VALUE|VALUE_TYPE=P",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PERCENT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_VALUE|VALUE_TYPE=F",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_SUM"),
						),
						array(
							"value" => "ICAT_DISCOUNT_VALUE|VALUE_TYPE=S",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PRICE"),
						),
						/*array(
							"value" => "ICAT_DISCOUNT_CURRENCY",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_CURRENCY"),
						),*/
						array(
							"value" => "ICAT_DISCOUNT_MAX_DISCOUNT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_MAX_DISCOUNT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_PRIORITY",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PRIORITY"),
						),
						array(
							"value" => "ICAT_DISCOUNT_SORT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_SORT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_LAST_DISCOUNT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_LAST_DISCOUNT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_LAST_LEVEL_DISCOUNT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_LAST_LEVEL_DISCOUNT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_BRGIFT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_GIFT"),
						),
					);
				}
				else
				{
					$arDiscountFields = array(
						array(
							"value" => "ICAT_DISCOUNT_NAME",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_NAME"),
						),
						array(
							"value" => "ICAT_DISCOUNT_XML_ID",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_XML_ID"),
						),
						array(
							"value" => "ICAT_DISCOUNT_ACTIVE_FROM",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_ACTIVE_FROM"),
						),
						array(
							"value" => "ICAT_DISCOUNT_ACTIVE_TO",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_ACTIVE_TO"),
						),
						array(
							"value" => "ICAT_DISCOUNT_VALUE|VALUE_TYPE=P",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PERCENT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_VALUE|VALUE_TYPE=F",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_SUM"),
						),
						array(
							"value" => "ICAT_DISCOUNT_VALUE|VALUE_TYPE=S",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PRICE"),
						),
						array(
							"value" => "ICAT_DISCOUNT_CURRENCY",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_CURRENCY"),
						),
						array(
							"value" => "ICAT_DISCOUNT_MAX_DISCOUNT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_MAX_DISCOUNT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_RENEWAL",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_RENEWAL"),
						),
						array(
							"value" => "ICAT_DISCOUNT_PRIORITY",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_PRIORITY"),
						),
						array(
							"value" => "ICAT_DISCOUNT_LAST_DISCOUNT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_LAST_DISCOUNT"),
						),
						array(
							"value" => "ICAT_DISCOUNT_NOTES",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_NOTES"),
						),
						array(
							"value" => "ICAT_DISCOUNT_BRGIFT",
							"name" => Loc::getMessage("KDA_IE_FI_DISCOUNT_GIFT"),
						),
					);
				}
			}
		}
		return (!empty($arDiscountFields) ? $arDiscountFields : false);
	}
	
	public static function GetElementRightsFields($IBLOCK_ID)
	{
		if(CIBlock::GetArrayByID($IBLOCK_ID, "RIGHTS_MODE")!='E') return false;
		return false;
	}
	
	public static function GetCatalogSetFields($IBLOCK_ID)
	{
		$arSetFields = array();
		if(Loader::includeModule('catalog') && CBXFeatures::IsFeatureEnabled('CatCompleteSet'))
		{
			if($IBLOCK_ID!==true)
			{
				$dbRes = CCatalog::GetList(array("ID"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID));
				$arCatalog = $dbRes->Fetch();
			}
			
			if($arCatalog || $IBLOCK_ID===true)
			{
				$arSetFields = array(
					array(
						"value" => "ICAT_SET_ITEM_ID",
						"name" => Loc::getMessage("KDA_IE_FI_SET_ITEM"),
					),
					array(
						"value" => "ICAT_SET_QUANTITY",
						"name" => Loc::getMessage("KDA_IE_FI_SET_QUANTITY"),
					),
					array(
						"value" => "ICAT_SET_SORT",
						"name" => Loc::getMessage("KDA_IE_FI_SET_SORT"),
					),
				);
			}
		}
		return (!empty($arSetFields) ? $arSetFields : false);
	}
	
	public static function GetCatalogSet2Fields($IBLOCK_ID)
	{
		$arSetFields = array();
		if(Loader::includeModule('catalog') && CBXFeatures::IsFeatureEnabled('CatCompleteSet'))
		{
			if($IBLOCK_ID!==true)
			{
				$dbRes = CCatalog::GetList(array("ID"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID));
				$arCatalog = $dbRes->Fetch();
			}
			
			if($arCatalog || $IBLOCK_ID===true)
			{
				$arSetFields = array(
					array(
						"value" => "ICAT_SET2_ITEM_ID",
						"name" => Loc::getMessage("KDA_IE_FI_SET2_ITEM"),
					),
					array(
						"value" => "ICAT_SET2_QUANTITY",
						"name" => Loc::getMessage("KDA_IE_FI_SET2_QUANTITY"),
					),
					array(
						"value" => "ICAT_SET2_DISCOUNT_PERCENT",
						"name" => Loc::getMessage("KDA_IE_FI_SET2_DISCOUNT_PERCENT"),
					),
					array(
						"value" => "ICAT_SET2_SORT",
						"name" => Loc::getMessage("KDA_IE_FI_SET2_SORT"),
					),
				);
			}
		}
		return (!empty($arSetFields) ? $arSetFields : false);
	}
	
	public static function GetIblockProperties($IBLOCK_ID)
	{
		$arProperties = array(array(
			"value" => "IP_LIST_PROPS",
			"name" => Loc::getMessage("KDA_IE_FI_PROP_LIST"),
			"uid" => "N",
			"wdesc" => false
		));
		if(Loader::includeModule('iblock'))
		{
			$dbRes = CIBlockProperty::GetList(array(
				"sort" => "asc",
				"name" => "asc",
			) , array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $IBLOCK_ID,
				"CHECK_PERMISSIONS" => "N",
			));
			while($arr = $dbRes->Fetch())
			{
				$bUid = (in_array($arr['PROPERTY_TYPE'], array('S', 'N', 'L', 'E'))/* && $arr['MULTIPLE']=='N'*/);
				$arProperties[] = array(
					"value" => "IP_PROP".$arr["ID"],
					"name" => $arr["NAME"].' ['.$arr["CODE"].']',
					"uid" => ($bUid ? "Y" : "N"),
					"wdesc" => (bool)($arr["WITH_DESCRIPTION"]=='Y' || ($arr['PROPERTY_TYPE']=='E' && $arr['USER_TYPE'] && !(in_array($arr['USER_TYPE'], array('SKU', 'EList', 'EAutocomplete'))))),
					"forsum" => (bool)(($arr["PROPERTY_TYPE"]=='S' || $arr["PROPERTY_TYPE"]=='N') && !$arr['USER_TYPE'] && $arr['MULTIPLE']=='N')
				);
			}
		}
		return (!empty($arProperties) ? $arProperties : false);
	}
	
	public function GetAllIblockProperties()
	{
		if(!$this->allIblockProperties)
		{
			$this->allIblockProperties = array();
			if(Loader::includeModule('iblock'))
			{
				$dbRes = CIBlockProperty::GetList(array(
					"sort" => "asc",
					"name" => "asc",
				) , array(
					"CHECK_PERMISSIONS" => "N",
				));
				while($arr = $dbRes->Fetch())
				{
					//$this->allIblockProperties["IP_PROP".$arr["ID"]] = $arr["NAME"].' ['.$arr["CODE"].']';
					$this->allIblockProperties["IP_PROP".$arr["ID"]] = $arr;
				}
			}
		}
		return $this->allIblockProperties;
	}

	public function GetFields($IBLOCK_ID, $offers = false)
	{
		if(!$this->aFields)
		{
			$this->aFields = array();
		}
		
		if(!$this->aFields[$IBLOCK_ID])
		{
			$this->aFields[$IBLOCK_ID]['element'] = array(
				'title' => ($offers ? Loc::getMessage("KDA_IE_GROUP_OFFER") : Loc::getMessage("KDA_IE_GROUP_ELEMENT")),
				'items' => array()
			);
			foreach(self::GetIblockElementFields() as $k=>$ar)
			{
				if($k=='IE_ID')
				{
					if(!$offers && $this->uid && ((is_array($this->uid) && !in_array('IE_ID', $this->uid)) || (!is_array($this->uid) && $this->uid!='IE_ID'))) continue;
					if($offers && $this->uidSku && ((is_array($this->uidSku) && !in_array('OFFER_IE_ID', $this->uidSku)) || (!is_array($this->uidSku) && $this->uidSku!='OFFER_IE_ID'))) continue;
				}
				if($offers) $k = 'OFFER_'.$k;
				$this->aFields[$IBLOCK_ID]['element']['items'][$k] = $ar["name"];
			}
			
			if($arPropFields = self::GetIblockProperties($IBLOCK_ID))
			{
				$this->aFields[$IBLOCK_ID]['prop'] = array(
					'title' => ($offers ? Loc::getMessage("KDA_IE_GROUP_OFFER").' ('.Loc::getMessage("KDA_IE_GROUP_PROP").')' : Loc::getMessage("KDA_IE_GROUP_PROP")),
					'items' => array()
				);
				foreach($arPropFields as $ar)
				{
					if($offers)
					{
						if(preg_match('/\D'.$offers.'$/', $ar["value"])) continue;
						$ar["value"] = 'OFFER_'.$ar["value"];
					} 
					$this->aFields[$IBLOCK_ID]['prop']['items'][$ar["value"]] = $ar["name"];
					if($ar["wdesc"])
					{
						$this->aFields[$IBLOCK_ID]['prop']['items'][$ar["value"].'_DESCRIPTION'] = $ar["name"].' ('.Loc::getMessage("KDA_IE_PROP_DESCRIPTION").')';
					}
				}
			}
			
			if($arIpropTempFields = self::GetIblockIpropTemplates())
			{
				$this->aFields[$IBLOCK_ID]['iprop_temp'] = array(
					'title' => ($offers ? Loc::getMessage("KDA_IE_GROUP_OFFER").' ('.Loc::getMessage("KDA_IE_IPROP_TEMPLATES").')' : Loc::getMessage("KDA_IE_IPROP_TEMPLATES")),
					'items' => array()
				);
				foreach($arIpropTempFields as $k=>$v)
				{
					if($offers)
					{
						$k = 'OFFER_'.$k;
					} 
					$this->aFields[$IBLOCK_ID]['iprop_temp']['items'][$k] = $v;
				}
			}
			
			if($arCatalogFields = self::GetCatalogFields($IBLOCK_ID))
			{
				$this->aFields[$IBLOCK_ID]['catalog'] = array(
					'title' => ($offers ? Loc::getMessage("KDA_IE_GROUP_OFFER").' ('.Loc::getMessage("KDA_IE_GROUP_CATALOG").')' : Loc::getMessage("KDA_IE_GROUP_CATALOG")),
					'items' => array()
				);
				foreach($arCatalogFields as $ar)
				{
					if($offers) $ar["value"] = 'OFFER_'.$ar["value"];
					$this->aFields[$IBLOCK_ID]['catalog']['items'][$ar["value"]] = $ar["name"];
				}
			}
			
			if(!$offers)
			{
				foreach(self::GetIblockSectionElementFields() as $k=>$ar)
				{
					$this->aFields[$IBLOCK_ID]['element']['items'][$k] = $ar["name"];
				}
				
				$this->aFields[$IBLOCK_ID]['section'] = array(
					'title' => Loc::getMessage("KDA_IE_GROUP_SECTION_PARENT"),
					'items' => array()
				);
				foreach($this->GetIblockSectionFields('', $IBLOCK_ID) as $k=>$ar)
				{
					$this->aFields[$IBLOCK_ID]['section']['items'][$k] = $ar["name"];
				}	
				
				for($i=1; $i<$this->sectionLevels+1; $i++)
				{
					$this->aFields[$IBLOCK_ID]['section'.$i] = array(
						'title' => sprintf(Loc::getMessage("KDA_IE_GROUP_SECTION_LEVEL"), $i),
						'items' => array()
					);
					foreach($this->GetIblockSectionFields($i, $IBLOCK_ID) as $k=>$ar)
					{
						$this->aFields[$IBLOCK_ID]['section'.$i]['items'][$k] = $ar["name"];
					}
				}
			}
			
			if($arDiscountFields = self::GetCatalogDiscountFields($IBLOCK_ID))
			{
				$this->aFields[$IBLOCK_ID]['catalog_discount'] = array(
					'title' => ($offers ? Loc::getMessage("KDA_IE_GROUP_OFFER").' ('.Loc::getMessage("KDA_IE_GROUP_CATALOG_DISCOUNT").')' : Loc::getMessage("KDA_IE_GROUP_CATALOG_DISCOUNT")),
					'items' => array()
				);
				foreach($arDiscountFields as $ar)
				{
					if($offers) $ar["value"] = 'OFFER_'.$ar["value"];
					$this->aFields[$IBLOCK_ID]['catalog_discount']['items'][$ar["value"]] = $ar["name"];
				}
			}
			
			if(/*!$offers &&*/ ($arCatalogSetFields = self::GetCatalogSetFields($IBLOCK_ID)))
			{
				$this->aFields[$IBLOCK_ID]['catalog_set'] = array(
					'title' => ($offers ? Loc::getMessage("KDA_IE_GROUP_OFFER").' ('.Loc::getMessage("KDA_IE_GROUP_CATALOG_SET").')' : Loc::getMessage("KDA_IE_GROUP_CATALOG_SET")),
					'items' => array()
				);
				foreach($arCatalogSetFields as $ar)
				{
					if($offers) $ar["value"] = 'OFFER_'.$ar["value"];
					$this->aFields[$IBLOCK_ID]['catalog_set']['items'][$ar["value"]] = $ar["name"];
				}
			}
			
			if(!$offers && ($arCatalogSetFields = self::GetCatalogSet2Fields($IBLOCK_ID)))
			{
				$this->aFields[$IBLOCK_ID]['catalog_set2'] = array(
					'title' => Loc::getMessage("KDA_IE_GROUP_CATALOG_SET2"),
					'items' => array()
				);
				foreach($arCatalogSetFields as $ar)
				{
					$this->aFields[$IBLOCK_ID]['catalog_set2']['items'][$ar["value"]] = $ar["name"];
				}
			}
			
			if($arRightsFields = self::GetElementRightsFields($IBLOCK_ID))
			{
				$this->aFields[$IBLOCK_ID]['element_rights'] = array(
					'title' => ($offers ? Loc::getMessage("KDA_IE_GROUP_OFFER").' ('.Loc::getMessage("KDA_IE_GROUP_ELEMENT_RIGHTS").')' : Loc::getMessage("KDA_IE_GROUP_ELEMENT_RIGHTS")),
					'items' => array()
				);
				foreach($arRightsFields as $ar)
				{
					if($offers) $ar["value"] = 'OFFER_'.$ar["value"];
					$this->aFields[$IBLOCK_ID]['element_rights']['items'][$ar["value"]] = $ar["name"];
				}
			}
		}
	
		return $this->aFields[$IBLOCK_ID];
	}
	
	public function GetFieldNames($IBLOCK_ID)
	{
		if(!$this->arFieldNames)
		{
			$this->arFieldNames = array();
		}
		
		if(!$this->arFieldNames[$IBLOCK_ID])
		{
			$this->arFieldNames[$IBLOCK_ID] = array();
			$arFields = $this->GetFields($IBLOCK_ID);
			foreach($arFields as $k=>$v)
			{
				if(is_array($v['items']))
				{
					foreach($v['items'] as $k2=>$v2)
					{
						$this->arFieldNames[$IBLOCK_ID][$k2] = $v2;
					}
				}
			}
		}

		return $this->arFieldNames[$IBLOCK_ID];
	}
	
	public function ShowSelectFields($IBLOCK_ID, $fname, $value="")
	{
		$arGroups = $this->GetFields($IBLOCK_ID);
		$arGroupsOffers = array();
		if($this->isSku)
		{
			$arOffer = CKDAImportUtils::GetOfferIblock($IBLOCK_ID, true);
			if($arOffer) $arGroupsOffers = $this->GetFields($arOffer['OFFERS_IBLOCK_ID'], $arOffer['OFFERS_PROPERTY_ID']);
		}
		?><select name="<?echo $fname;?>"><option value=""><?echo Loc::getMessage("KDA_IE_CHOOSE_FIELD");?></option><?
		?><option value="new_prop"><?echo Loc::getMessage("KDA_IE_CREATE_PROPERTY");?></option><?
		foreach($arGroups as $k2=>$v2)
		{
			?><optgroup label="<?echo $v2['title']?>"><?
			foreach($v2['items'] as $k=>$v)
			{
				?><option value="<?echo $k; ?>" <?if($k==$value){echo 'selected';}?>><?echo htmlspecialcharsbx($v); ?></option><?
			}
			?></optgroup><?
		}
		foreach($arGroupsOffers as $k2=>$v2)
		{
			?><optgroup label="<?echo $v2['title']?>"><?
			foreach($v2['items'] as $k=>$v)
			{
				?><option value="<?echo $k; ?>" <?if($k==$value){echo 'selected';}?>><?echo htmlspecialcharsbx($v); ?></option><?
			}
			?></optgroup><?
		}
		?></select><?
	}
	
	public function ShowSelectUidFields($IBLOCK_ID, $fname, $val=false, $prefix='')
	{
		$fields = $this->GetSelectUidFields($IBLOCK_ID, $val, $prefix);
		?><select name="<?echo $fname;?>" class="chosen" multiple><?echo $fields;?></select><?
	}
	
	public function ShowSelectSectionUidFields($IBLOCK_ID, $fname, $val=false)
	{
		?><select name="<?echo $fname;?>"><?
			?><option value="NAME"<?if($val=='NAME') echo ' selected';?>><?echo GetMessage("KDA_IE_SECTION_NAME"); ?></option><?
			?><option value="CODE"<?if($val=='CODE') echo ' selected';?>><?echo GetMessage("KDA_IE_SECTION_CODE"); ?></option><?
			?><option value="ID"<?if($val=='ID') echo ' selected';?>><?echo GetMessage("KDA_IE_SECTION_ID"); ?></option><?
			?><option value="XML_ID"<?if($val=='XML_ID') echo ' selected';?>><?echo GetMessage("KDA_IE_SECTION_XML_ID"); ?></option><?
			
			$dbRes = \CUserTypeEntity::GetList(array('SORT'=>'ASC', 'ID'=>'ASC'), array('ENTITY_ID'=>'IBLOCK_'.$IBLOCK_ID.'_SECTION', 'LANG'=>LANGUAGE_ID));
			while($arField = $dbRes->Fetch())
			{
				if(!in_array($arField['USER_TYPE_ID'], array('string', 'integer', 'double'))) continue;
				$name = trim($arField['LIST_COLUMN_LABEL']);
				if(strlen($name)==0) $name = trim($arField['EDIT_FORM_LABEL']);
				if(strlen($name)==0) $name = trim($arField['FIELD_NAME']);
				?><option value="<?echo htmlspecialcharsbx($arField['FIELD_NAME']);?>"<?if($val==$arField['FIELD_NAME']) echo ' selected';?>><?echo htmlspecialcharsbx($name.' ['.$arField['FIELD_NAME'].']');?></option><?
			}
			
		?></select><?
	}
	
	public function ShowSelectPropertyList($IBLOCK_ID, $fname, $val=false, $prefix='')
	{
		$properties = $this->GetSelectPropertyList($IBLOCK_ID, $val, $prefix);
		$catalogFields = $this->GetSelectCatalogList($IBLOCK_ID, $val, $prefix);
		?><select name="<?echo $fname;?>" class="kda-chosen-multi" multiple data-placeholder="<?echo Loc::getMessage('KDA_IE_PLACEHOLDER_CHOOSE');?>">
		<?
		if(strlen($properties) > 0) echo '<optgroup label="'.Loc::getMessage("KDA_IE_GROUP_PROP").'">'.$properties.'</optgroup>';
		if(strlen($catalogFields) > 0) echo '<optgroup label="'.Loc::getMessage("KDA_IE_GROUP_CATALOG").'">'.$catalogFields.'</optgroup>';
		?></select><?
	}
	
	public function GetSelectPropertyList($IBLOCK_ID, $val=false, $prefix='')
	{
		$IBLOCK_ID = intval($IBLOCK_ID);
		$arProps = self::GetIblockProperties($IBLOCK_ID);
		if(!is_array($val)) $val = array();
		
		$options = '';
		if(is_array($arProps))
		{
			foreach($arProps as $k=>$v)
			{
				if($v['value']=='IP_LIST_PROPS') continue;
				$optval = ($prefix ? $prefix : '').htmlspecialcharsbx($v['value']);
				$options .= '<option value="'.$optval.'"'.(in_array($optval, $val) ? ' selected' : '').'>'.htmlspecialcharsbx($v['name']).'</option>';
			}
		}
		return $options;
	}
	
	public function GetSelectCatalogList($IBLOCK_ID, $val=false, $prefix='')
	{
		$IBLOCK_ID = intval($IBLOCK_ID);
		$arFields = self::GetCatalogFields($IBLOCK_ID);
		if(!is_array($val)) $val = array();
		
		$options = '';
		if(is_array($arFields))
		{
			foreach($arFields as $k=>$v)
			{
				if(!preg_match('/^ICAT_STORE\d+_AMOUNT$/', $v['value'])) continue;
				$optval = ($prefix ? $prefix : '').htmlspecialcharsbx($v['value']);
				$options .= '<option value="'.$optval.'"'.(in_array($optval, $val) ? ' selected' : '').'>'.htmlspecialcharsbx($v['name']).'</option>';
			}
		}
		return $options;
	}
	
	public function ShowSelectStoreListForSum($fname, $val=false)
	{
		echo GetMessage("KDA_IE_PRODUCT_STORES");?>:<br><?
		?><select name="<?echo $fname;?>" class="kda-chosen-multi" multiple data-placeholder="<?echo Loc::getMessage('KDA_IE_PLACEHOLDER_CHOOSE');?>"><?echo $this->GetSelectStoreListForSum($val);?></select><?
		echo '<br><small>'.GetMessage("KDA_IE_PRODUCT_STORES_HINT").'<small>';
	}
	
	public function GetSelectStoreListForSum($val=false)
	{
		$arStores = array();
		$dbRes = \CCatalogStore::GetList(array("SORT"=>"ID"), array(), false, false, array("ID", "TITLE", "ADDRESS"));
		while($arStore = $dbRes->Fetch())
		{
			$arStores[$arStore["ID"]] = (strlen($arStore["TITLE"]) > 0 ? $arStore["TITLE"] : $arStore["ADDRESS"]);
		}
		if(!is_array($val)) $val = array();
		
		$options = '';
		if(is_array($arStores))
		{
			foreach($arStores as $k=>$v)
			{
				$optval = htmlspecialcharsbx($k);
				$options .= '<option value="'.$optval.'"'.(in_array($optval, $val) ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
			}
		}
		return $options;
	}
	
	public function ShowSelectPropertyListForSum($IBLOCK_ID, $fname, $val=false, $isOffer=false)
	{
		if(!$IBLOCK_ID) return;
		if(!$isOffer)
		{
			echo GetMessage("KDA_IE_PRODUCT_PROPERTIES");?>:<br><?
		}
		else
		{
			?><br><?echo GetMessage("KDA_IE_OFFER_PROPERTIES");?>:<br><?
		}
		?><select name="<?echo $fname;?>" class="kda-chosen-multi" multiple data-placeholder="<?echo Loc::getMessage('KDA_IE_PLACEHOLDER_CHOOSE');?>"><?echo $this->GetSelectPropertyListForSum($IBLOCK_ID, $val);?></select><?
	}
	
	public function GetSelectPropertyListForSum($IBLOCK_ID, $val=false)
	{
		$IBLOCK_ID = intval($IBLOCK_ID);
		$arProps = self::GetIblockProperties($IBLOCK_ID);
		if(!is_array($val)) $val = array();
		
		$options = '';
		if(is_array($arProps))
		{
			foreach($arProps as $k=>$v)
			{
				if(!$v['forsum']) continue;
				$optval = htmlspecialcharsbx($v['value']);
				$options .= '<option value="'.$optval.'"'.(in_array($optval, $val) ? ' selected' : '').'>'.htmlspecialcharsbx($v['name']).'</option>';
			}
		}
		return $options;
	}
	
	public function GetSelectUidFields($IBLOCK_ID, $val=false, $prefix='')
	{
		$hash = $IBLOCK_ID.'_'.md5(serialize($val));
		$IBLOCK_ID = intval($IBLOCK_ID);
		if(!$this->UidFields) $this->UidFields = array();
		
		if(!$this->UidFields[$hash])
		{
			ob_start();
			foreach(self::GetIblockElementFields() as $k=>$ar)
			{
				if($ar['uid']=="Y")
				{
					$k = $prefix.$k;
					?><option value="<?echo $k; ?>" <?if((is_array($val) && in_array($k, $val)) || $k==$val){echo 'selected';}?>><?echo htmlspecialcharsbx($ar["name"]); ?></option><?
				}
			}
			
			if($arPropFields = self::GetIblockProperties($IBLOCK_ID))
			{
				foreach($arPropFields as $ar)
				{
					if($ar['uid']=="Y")
					{
						$ar["value"] = $prefix.$ar["value"];
						?><option value="<?echo $ar["value"] ?>" <?if((is_array($val) && in_array($ar["value"], $val)) || $ar["value"]==$val){echo 'selected';}?>><?echo Loc::getMessage("KDA_IE_FI_PROP");?> "<?echo htmlspecialcharsbx($ar["name"]); ?>"</option><?
					}
				}
			}		
			$this->UidFields[$hash] = ob_get_clean();
		}
		return $this->UidFields[$hash];
	}
	
	public function ShowSelectSections($IBLOCK_ID, $fname, $value='', $mutiple=false)
	{
		if(!$this->Sections)
		{
			$this->Sections = array();
		}
		
		if(!$this->Sections[$IBLOCK_ID])
		{
			if($IBLOCK_ID)
			{
				$this->Sections[$IBLOCK_ID][] = array(
					'ID' => '',
					'NAME' => Loc::getMessage("KDA_IE_NO_SECTION")
				);
				
				if(Loader::includeModule('iblock'))
				{
					$arLevels = array();
					$dbRes = CIBlockSection::GetList(array("LEFT_MARGIN"=>"ASC"), array('IBLOCK_ID'=>$IBLOCK_ID), false, array('ID', 'NAME', 'DEPTH_LEVEL'));
					while($arr = $dbRes->Fetch())
					{
						$this->Sections[$IBLOCK_ID][] = array(
							'ID' => $arr['ID'],
							'NAME' => str_repeat(' . ', $arr['DEPTH_LEVEL']).$arr['NAME'],
							'FULL_NAME' => implode(' / ', array_merge(array_slice($arLevels, 0, $arr['DEPTH_LEVEL'] - 1), array($arr['NAME'])))
						);
						$arLevels[$arr['DEPTH_LEVEL']] = $arr['NAME'];
					}
				}
			}
			else
			{
				$this->Sections[$IBLOCK_ID][] = array(
					'ID' => '',
					'NAME' => Loc::getMessage("KDA_IE_CHOOSE_SECTION_FIRST")
				);
			}
		}
	
		?><select name="<?echo $fname;?>"<?if($mutiple){echo ' multiple';}?>><?
		foreach($this->Sections[$IBLOCK_ID] as $arr)
		{
			if($mutiple && strlen($arr['ID'])==0) continue;
			?><option value="<?echo $arr['ID'];?>" <?if((is_array($value) && in_array($arr['ID'], $value)) || (!is_array($value) && $arr['ID']==$value)){echo 'selected';}?> title="<?echo htmlspecialcharsbx($arr['FULL_NAME']); ?>"><?echo htmlspecialcharsbx($arr['NAME']); ?></option><?
		}
		?></select><?
	}
	
	public function GetIblocks()
	{
		$arIblocks = array();
		$dbRes = \CIBlock::GetList(array('NAME'=>'ASC', 'MIN_PERMISSION'=>'W'), array());
		while($arr = $dbRes->Fetch())
		{
			$arIblocks[$arr['IBLOCK_TYPE_ID']][] = array(
				'ID' => $arr['ID'],
				'NAME' => $arr['NAME']
			);
		}
		
		$dbRes = \CIBlockType::GetList(array("SORT"=>"ASC", "NAME"=>"ASC"), array("LANGUAGE_ID" => LANG));
		while($arr = $dbRes->Fetch())
		{
			$arr = array(
				'ID' => $arr['ID'],
				'NAME' => $arr['NAME']
			);
			$arr['IBLOCKS'] = $arIblocks[$arr['ID']];
			$arIblocks[$arr['ID']] = $arr;
		}
		
		/*Added Types without lang*/
		$arNFTypes = array();
		foreach($arIblocks as $k=>$v)
		{
			if(!array_key_exists('ID', $v)) $arNFTypes[] = $k;
		}
		if(!empty($arNFTypes))
		{
			$dbRes = \CIBlockType::GetList(array("SORT"=>"ASC", "NAME"=>"ASC"), array());
			while($arr = $dbRes->Fetch())
			{
				if(in_array($arr['ID'], $arNFTypes))
				{
					$arr = array(
						'ID' => $arr['ID'],
						'NAME' => $arr['NAME']
					);
					$arr['IBLOCKS'] = $arIblocks[$arr['ID']];
					$arIblocks[$arr['ID']] = $arr;
					unset($arNFTypes[$arr['ID']]);
				}
			}
		}
		/*/Added Types without lang*/
		return $arIblocks;
	}
	
	public function GetHighloadBlocks()
	{
		if(!Loader::includeModule('highloadblock')) return array();
		$arHighloadBlocks = array();
		$dbRes = \Bitrix\Highloadblock\HighloadBlockTable::getList(array("order" => array('NAME' => 'ASC')));
		while($arr = $dbRes->Fetch())
		{
			$arHighloadBlocks[] = $arr;
		}
		return $arHighloadBlocks;
	}
	
	public function ShowSelectFieldsHighload($HIGHLOADBLOCK_ID, $fname, $value="")
	{
		$arFields = $this->GetHigloadBlockFields($HIGHLOADBLOCK_ID);
		?><select name="<?echo $fname;?>"><option value=""><?echo Loc::getMessage("KDA_IE_CHOOSE_FIELD");?></option><?
		?><option value="new_prop"><?echo Loc::getMessage("KDA_IE_CREATE_HIGHLOAD_FIELD");?></option><?
		?><optgroup label="<?echo Loc::getMessage("KDA_IE_HIGHLOAD_FIELDS")?>"><?
		foreach($arFields as $k=>$v)
		{
			?><option value="<?echo $k; ?>" <?if($k==$value){echo 'selected';}?>><?echo htmlspecialcharsbx($v['NAME_LANG']); ?></option><?
		}
		?></optgroup><?
		?></select><?
	}
	
	public function GetHigloadBlockFields($HIGHLOADBLOCK_ID)
	{
		if(!isset($this->hlblFields[$HIGHLOADBLOCK_ID]))
		{
			$dbRes = CUserTypeEntity::GetList(array('SORT'=>'ASC', 'ID'=>'ASC'), array('ENTITY_ID'=>'HLBLOCK_'.$HIGHLOADBLOCK_ID, 'LANG'=>LANGUAGE_ID));
			$arHLFields = array();
			if($HIGHLOADBLOCK_ID)
			{
				$arHLFields = array('ID' => array(
					'FIELD_NAME' => 'ID',
					'NAME_LANG' => 'ID',
					'UID' => 'Y'
				));
			}
			while($arHLField = $dbRes->Fetch())
			{
				if($arHLField['MULTIPLE']!='Y' && in_array($arHLField['USER_TYPE_ID'], array('string', 'hlblock', 'integer', 'double', 'datetime', 'date', 'boolean', 'enumeration', 'iblock_section', 'iblock_element')))
				{
					$arHLField['UID'] = 'Y';
				}
				$arHLField['NAME_LANG'] = ($arHLField['EDIT_FORM_LABEL'] ? $arHLField['EDIT_FORM_LABEL'] : $arHLField['FIELD_NAME']);
				$arHLFields[$arHLField['FIELD_NAME']] = $arHLField;
			}
			$this->hlblFields[$HIGHLOADBLOCK_ID] = $arHLFields;
		}
		return $this->hlblFields[$HIGHLOADBLOCK_ID];
	}
	
	public function GetSelectUidFieldsHighload($HIGHLOADBLOCK_ID, $val=false)
	{
		$HIGHLOADBLOCK_ID = intval($HIGHLOADBLOCK_ID);
		if(!$this->UidFieldsHighload)
		{
			$this->UidFieldsHighload = array();
		}
		
		if(!$this->UidFieldsHighload[$HIGHLOADBLOCK_ID])
		{
			ob_start();
			
			if($arHighloadFields = self::GetHigloadBlockFields($HIGHLOADBLOCK_ID))
			{
				foreach($arHighloadFields as $ar)
				{
					if($ar['UID']=="Y")
					{
						?><option value="<?echo $ar["FIELD_NAME"] ?>" <?if((is_array($val) && in_array($ar["FIELD_NAME"], $val)) || $ar["FIELD_NAME"]==$val){echo 'selected';}?>><?echo htmlspecialcharsbx($ar["NAME_LANG"]); ?></option><?
					}
				}
			}		
			$this->UidFieldsHighload[$HIGHLOADBLOCK_ID] = ob_get_clean();
		}
		return $this->UidFieldsHighload[$HIGHLOADBLOCK_ID];
	}
	
	public function ShowSelectUidFieldsHighload($HIGHLOADBLOCK_ID, $fname, $val=false)
	{
		$this->GetSelectUidFieldsHighload($HIGHLOADBLOCK_ID, $val);
		?><select name="<?echo $fname;?>" class="chosen" multiple><?echo $this->UidFieldsHighload[$HIGHLOADBLOCK_ID];?></select><?
	}
	
	public function GetIblockSettingsFields($IBLOCK_ID, $offers = false)
	{
		$arGroups = $this->GetFields($IBLOCK_ID, $offers);
		if(isset($arGroups['catalog_discount'])) unset($arGroups['catalog_discount']);
		if(isset($arGroups['iprop_temp'])) unset($arGroups['iprop_temp']);
		for($i=1; $i<$this->sectionLevels+1; $i++)
		{
			if(isset($arGroups['section'.$i])) unset($arGroups['section'.$i]);
		}
		if(isset($arGroups['prop']))
		{
			if(isset($arGroups['prop']['items']['IP_LIST_PROPS'])) unset($arGroups['prop']['items']['IP_LIST_PROPS']);
		}
		if(isset($arGroups['element']))
		{
			if(isset($arGroups['element']['items']['IE_SECTION_PATH'])) unset($arGroups['element']['items']['IE_SECTION_PATH']);
			$arExtKey = preg_grep('/\|/', array_keys($arGroups['element']['items']));
			if(!empty($arExtKey))
			{
				foreach($arExtKey as $extKey) unset($arGroups['element']['items'][$extKey]);
			}
			$arGroups['element']['items']['IE_IBLOCK_SECTION_ID'] = Loc::getMessage("KDA_IE_FI_IBLOCK_SECTION_ID");
			$arGroups['element']['items']['IE_IBLOCK_SECTION_IDS'] = Loc::getMessage("KDA_IE_FI_IBLOCK_SECTION_IDS");
			$arGroups['element']['items']['IE_IBLOCK_SECTION_PARENT_IDS'] = Loc::getMessage("KDA_IE_FI_IBLOCK_SECTION_PARENT_IDS");
		}		
		return $arGroups;
	}
	
	public function GetSettingsFields($IBLOCK_ID, $PARENT_IBLOCK_ID=0)
	{
		$arGroups = $arGroupsParent = $arGroupsOffers = array();
		if($PARENT_IBLOCK_ID == 0 || $this->isSku)
		{
			$arGroups = $this->GetIblockSettingsFields($IBLOCK_ID);
		}
		else
		{
			$arGroups = $this->GetIblockSettingsFields($IBLOCK_ID, true);
			$arGroupsParent = $this->GetIblockSettingsFields($PARENT_IBLOCK_ID);
		}
		if($this->isSku)
		{
			$arOffer = CKDAImportUtils::GetOfferIblock($IBLOCK_ID, true);
			if($arOffer) $arGroupsOffers = $this->GetIblockSettingsFields($arOffer['OFFERS_IBLOCK_ID'], $arOffer['OFFERS_PROPERTY_ID']);
		}
		
		$arFields = array();
		foreach($arGroups as $k2=>$v2)
		{
			if(strpos($k2, 'catalog_set')===0) continue;
			$key = ToUpper($k2);
			$arFields[$key] = array(
				'TITLE' => $v2['title'],
				'FIELDS' => array()
			); 
			foreach($v2['items'] as $k=>$v)
			{
				if(strpos($k, 'OFFER_')===0) $k = substr($k, 6);
				$arFields[$key]['FIELDS'][$k] = $v;
			}
		}
		foreach($arGroupsOffers as $k2=>$v2)
		{
			if(strpos($k2, 'catalog_set')===0) continue;
			$key = 'OFFERS_'.ToUpper($k2);
			$arFields[$key] = array(
				'TITLE' => $v2['title'],
				'FIELDS' => array()
			); 
			foreach($v2['items'] as $k=>$v)
			{
				$arFields[$key]['FIELDS'][$k] = $v;
			}
		}
		foreach($arGroupsParent as $k2=>$v2)
		{
			if(strpos($k2, 'catalog_set')===0) continue;
			$key = 'PARENT_'.ToUpper($k2);
			$arFields[$key] = array(
				'TITLE' => $v2['title'],
				'FIELDS' => array()
			); 
			foreach($v2['items'] as $k=>$v)
			{
				$k = 'PARENT_'.$k;
				$arFields[$key]['FIELDS'][$k] = $v;
			}
		}
		return $arFields;
	}
}