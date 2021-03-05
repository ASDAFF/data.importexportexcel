<?php
IncludeModuleLangFile(__FILE__);

class CKDAImportExtrasettings {
	function __construct()
	{

	}
	
	public static function GetMarginTemplates(&$pfile)
	{
		$pdir = dirname(__FILE__).'/../../profiles/';
		CheckDirPath($pdir);
		$pfile = $pdir.'margins.txt';
		if(file_exists($pfile)) $arTemplates = unserialize(file_get_contents($pfile));
		if(!is_array($arTemplates)) $arTemplates = array();
		return $arTemplates;
	}
	
	public static function SaveMarginTemplate($arPost)
	{
		$pfile = '';
		$arTemplates = self::GetMarginTemplates($pfile);
		$PEXTRASETTINGS = array();
		self::HandleParams($PEXTRASETTINGS, $arPost['EXTRASETTINGS']);
		$arMargins = self::GetMargins($PEXTRASETTINGS);
		if(strlen($arPost['template_id']) > 0 && is_numeric($arPost['template_id']))
		{
			$arTemplates[$arPost['template_id']]['MARGINS'] = $arMargins;
		}
		elseif(strlen($arPost['template_name']) > 0)
		{
			$arTemplates[] = array(
				'TITLE' => $arPost['template_name'],
				'MARGINS' => $arMargins
			);
		}
		file_put_contents($pfile, serialize($arTemplates));
		return $arTemplates;
	}
	
	public static function DeleteMarginTemplate($tid)
	{
		$pfile = '';
		$arTemplates = self::GetMarginTemplates($pfile);
		unset($arTemplates[$tid]);
		$arTemplates = array_values($arTemplates);
		file_put_contents($pfile, serialize($arTemplates));
		return $arTemplates;
	}
	
	public static function GetMargins($PEXTRASETTINGS)
	{
		foreach($PEXTRASETTINGS as $k1=>$v1)
		{
			foreach($v1 as $k2=>$v2)
			{
				return $v2['MARGINS'];
			}
		}
	}
	
	public static function HandleParams(&$PEXTRASETTINGS, $arParams)
	{
		global $APPLICATION;
		if(!defined('BX_UTF') || !BX_UTF)
		{
			$arParams = $APPLICATION->ConvertCharsetArray($arParams, "UTF-8", "Windows-1251");
		}

		foreach($arParams as $k1=>$v1)
		{
			foreach($v1 as $k2=>$v2)
			{
				$PEXTRASETTINGS[$k1][$k2] = array();
				foreach($v2 as $k3=>$v3)
				{
					if($k3=='MARGINS')
					{
						$arMargins = array();
						foreach($v3['PERCENT'] as $k4=>$v4)
						{
							$v4 = str_replace(',', '.', $v4);
							$v3['PRICE_FROM'][$k4] = str_replace(',', '.', $v3['PRICE_FROM'][$k4]);
							$v3['PRICE_TO'][$k4] = str_replace(',', '.', $v3['PRICE_TO'][$k4]);
							if(floatval($v4) > 0)
							{
								$margin = array(
									'TYPE' => $v3['TYPE'][$k4],
									'PERCENT' => floatval($v4),
									'PERCENT_TYPE' => $v3['PERCENT_TYPE'][$k4],
									'PRICE_FROM' => (strlen(trim($v3['PRICE_FROM'][$k4])) > 0 ? floatval($v3['PRICE_FROM'][$k4]) : false),
									'PRICE_TO' => (strlen(trim($v3['PRICE_TO'][$k4])) > 0 ? floatval($v3['PRICE_TO'][$k4]) : false)
								);
								$arMargins[] = $margin;
							}
						}
						if(!empty($arMargins)) $PEXTRASETTINGS[$k1][$k2][$k3] = $arMargins;
						else unset($PEXTRASETTINGS[$k1][$k2][$k3]);
						continue;
					}
					
					if($k3=='CONVERSION' || $k3=='EXTRA_CONVERSION')
					{
						$arConversions = array();
						foreach($v3['WHEN'] as $k4=>$v4)
						{
							if(strlen($v3['FROM'][$k4]) > 0 || strlen($v3['TO'][$k4]) > 0 
								|| in_array($v3['CELL'][$k4], array('ELSE', 'LOADED'))
								|| in_array($v3['WHEN'][$k4], array('ANY', 'EMPTY', 'NOT_EMPTY'))
								|| in_array($v3['THEN'][$k4], array('NOT_LOAD', 'NOT_LOAD_ELEMENT', 'MATH_ROUND', 'TRANSLIT', 'STRIP_TAGS', 'CLEAR_TAGS')))
							{
								$arConversion = array(
									'CELL' => $v3['CELL'][$k4],
									'WHEN' => $v4,
									'FROM' => $v3['FROM'][$k4],
									'THEN' => $v3['THEN'][$k4],
									'TO' => $v3['TO'][$k4]
								);
								$arConversions[] = $arConversion;
							}
						}
						if(!empty($arConversions)) $PEXTRASETTINGS[$k1][$k2][$k3] = $arConversions;
						else unset($PEXTRASETTINGS[$k1][$k2][$k3]);
						continue;
					}
					
					if(is_array($v3))
					{
						$v3 = array_map('trim', $v3);
						$v3 = array_diff($v3, array(''));
					}
					
					if($k3=='USE_FILTER_FOR_DEACTIVATE' && $v3!='Y') $v3 = '';
					if($k3=='SET_NEW_ONLY' && $v3!='Y') $v3 = '';
					if($k3=='NOT_TRIM' && $v3!='Y') $v3 = '';
					if($k3=='USE_FOR_SKU_GENERATE' && $v3!='Y') $v3 = '';
					if($k3=='CHANGE_MULTIPLE_SEPARATOR' && $v3!='Y') $v3 = '';
					if($k3=='SECTION_UID_SEPARATED' && $v3!='Y') $v3 = '';
					if($k3=='SECTION_SEARCH_IN_SUBSECTIONS' && $v3!='Y') $v3 = '';
					if($k3=='COPY_CELL_ON_OFFERS' && $v3!='Y') $v3 = '';
					if($k3=='SEARCH_SINGLE_OFFERS' && $v3!='Y') $v3 = '';
					if($k3=='MULTIPLE_SAVE_OLD_VALUES' && $v3!='Y') $v3 = '';

					if(!empty($v3))
					{
						$PEXTRASETTINGS[$k1][$k2][$k3] = $v3;
					}
					else
					{
						unset($PEXTRASETTINGS[$k1][$k2][$k3]);
					}
				}
			}
		}
	}
}