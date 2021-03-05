<?php
namespace Bitrix\KdaImportexcel\DataManager;

use Bitrix\Main\Entity, 
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class IblockElementTable extends Entity\DataManager
{
	protected static $elemListHash = array();
	protected static $arTblFields = null;
	protected $optimizeApi = false;
	
	public function __construct($arParams)
	{
		if($arParams['ELEM_API_OPTIMIZE']=='Y') $this->optimizeApi = true;
	}
	
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return \Bitrix\Iblock\ElementTable::getTableName();
	}

	public static function getMap()
	{
		return \Bitrix\Iblock\ElementTable::getMap();
	}
	
    public function AddComp($arFields, $bWorkFlow=false, $bUpdateSearch=true, $bResizePictures=false)
    {
		clearstatcache();
		if(!$this->optimizeApi)
		{
			$el = new \CIblockElement();
			$result = $el->Add($arFields, $bWorkFlow, $bUpdateSearch, $bResizePictures);
			$this->LAST_ERROR = $el->LAST_ERROR;
			return $result;
		}
		
        global $DB, $USER;
		$this->LAST_ERROR = '';

        $arIBlock = \CIBlock::GetArrayByID($arFields["IBLOCK_ID"]);

        if(array_key_exists("IBLOCK_SECTION_ID", $arFields))
        {
            if (!array_key_exists("IBLOCK_SECTION", $arFields))
            {
                $arFields["IBLOCK_SECTION"] = array($arFields["IBLOCK_SECTION_ID"]);
            }
            elseif (is_array($arFields["IBLOCK_SECTION"]) && !in_array($arFields["IBLOCK_SECTION_ID"], $arFields["IBLOCK_SECTION"]))
            {
                unset($arFields["IBLOCK_SECTION_ID"]);
            }
        }

        $strWarning = "";
        if($bResizePictures)
        {
            $arDef = $arIBlock["FIELDS"]["PREVIEW_PICTURE"]["DEFAULT_VALUE"];

            if(
                $arDef["FROM_DETAIL"] === "Y"
                && is_array($arFields["DETAIL_PICTURE"])
                && $arFields["DETAIL_PICTURE"]["size"] > 0
                && (
                    $arDef["UPDATE_WITH_DETAIL"] === "Y"
                    || $arFields["PREVIEW_PICTURE"]["size"] <= 0
                )
            )
            {
                $arNewPreview = $arFields["DETAIL_PICTURE"];
                $arNewPreview["COPY_FILE"] = "Y";
                if (
                    isset($arFields["PREVIEW_PICTURE"])
                    && is_array($arFields["PREVIEW_PICTURE"])
                    && isset($arFields["PREVIEW_PICTURE"]["description"])
                )
                {
                    $arNewPreview["description"] = $arFields["PREVIEW_PICTURE"]["description"];
                }

                $arFields["PREVIEW_PICTURE"] = $arNewPreview;
            }

            if(
                array_key_exists("PREVIEW_PICTURE", $arFields)
                && is_array($arFields["PREVIEW_PICTURE"])
                && $arDef["SCALE"] === "Y"
            )
            {
                $arNewPicture = \CIBlock::ResizePicture($arFields["PREVIEW_PICTURE"], $arDef);
                if(is_array($arNewPicture))
                {
                    $arNewPicture["description"] = $arFields["PREVIEW_PICTURE"]["description"];
                    $arFields["PREVIEW_PICTURE"] = $arNewPicture;
                }
                /*elseif($arDef["IGNORE_ERRORS"] !== "Y")
                {
                    unset($arFields["PREVIEW_PICTURE"]);
                    $strWarning .= Loc::getMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
                }*/
            }

            if(
                array_key_exists("PREVIEW_PICTURE", $arFields)
                && is_array($arFields["PREVIEW_PICTURE"])
                && $arDef["USE_WATERMARK_FILE"] === "Y"
            )
            {
                if(
                    strlen($arFields["PREVIEW_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["PREVIEW_PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
                        || ($arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PREVIEW_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["PREVIEW_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["PREVIEW_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["PREVIEW_PICTURE"]["copy"] = true;
                    $arFields["PREVIEW_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["PREVIEW_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_FILE_POSITION"],
                    "type" => "file",
                    "size" => "real",
                    "alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
                    "file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
                ));
            }

            if(
                array_key_exists("PREVIEW_PICTURE", $arFields)
                && is_array($arFields["PREVIEW_PICTURE"])
                && $arDef["USE_WATERMARK_TEXT"] === "Y"
            )
            {
                if(
                    strlen($arFields["PREVIEW_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["PREVIEW_PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
                        || ($arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PREVIEW_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["PREVIEW_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["PREVIEW_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["PREVIEW_PICTURE"]["copy"] = true;
                    $arFields["PREVIEW_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["PREVIEW_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_TEXT_POSITION"],
                    "type" => "text",
                    "coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
                    "text" => $arDef["WATERMARK_TEXT"],
                    "font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
                    "color" => $arDef["WATERMARK_TEXT_COLOR"],
                ));
            }

            $arDef = $arIBlock["FIELDS"]["DETAIL_PICTURE"]["DEFAULT_VALUE"];

            if(
                array_key_exists("DETAIL_PICTURE", $arFields)
                && is_array($arFields["DETAIL_PICTURE"])
                && $arDef["SCALE"] === "Y"
            )
            {
                $arNewPicture = \CIBlock::ResizePicture($arFields["DETAIL_PICTURE"], $arDef);
                if(is_array($arNewPicture))
                {
                    $arNewPicture["description"] = $arFields["DETAIL_PICTURE"]["description"];
                    $arFields["DETAIL_PICTURE"] = $arNewPicture;
                }
                /*elseif($arDef["IGNORE_ERRORS"] !== "Y")
                {
                    unset($arFields["DETAIL_PICTURE"]);
                    $strWarning .= Loc::getMessage("IBLOCK_FIELD_DETAIL_PICTURE").": ".$arNewPicture."<br>";
                }*/
            }

            if(
                array_key_exists("DETAIL_PICTURE", $arFields)
                && is_array($arFields["DETAIL_PICTURE"])
                && $arDef["USE_WATERMARK_FILE"] === "Y"
            )
            {
                if(
                    strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PREVIEW_PICTURE"]["tmp_name"]
                        || ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["DETAIL_PICTURE"]["copy"] = true;
                    $arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_FILE_POSITION"],
                    "type" => "file",
                    "size" => "real",
                    "alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
                    "file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
                ));
            }

            if(
                array_key_exists("DETAIL_PICTURE", $arFields)
                && is_array($arFields["DETAIL_PICTURE"])
                && $arDef["USE_WATERMARK_TEXT"] === "Y"
            )
            {
                if(
                    strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PREVIEW_PICTURE"]["tmp_name"]
                        || ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["DETAIL_PICTURE"]["copy"] = true;
                    $arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_TEXT_POSITION"],
                    "type" => "text",
                    "coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
                    "text" => $arDef["WATERMARK_TEXT"],
                    "font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
                    "color" => $arDef["WATERMARK_TEXT_COLOR"],
                ));
            }
        }

        $ipropTemplates = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($arFields["IBLOCK_ID"], 0);
        if(is_set($arFields, "PREVIEW_PICTURE"))
        {
            if(is_array($arFields["PREVIEW_PICTURE"]))
            {
                if(strlen($arFields["PREVIEW_PICTURE"]["name"])<=0 && strlen($arFields["PREVIEW_PICTURE"]["del"])<=0)
                {
                    unset($arFields["PREVIEW_PICTURE"]);
                }
                else
                {
                    $arFields["PREVIEW_PICTURE"]["MODULE_ID"] = "iblock";
                    $arFields["PREVIEW_PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
                        $ipropTemplates
                        ,"ELEMENT_PREVIEW_PICTURE_FILE_NAME"
                        ,$arFields
                        ,$arFields["PREVIEW_PICTURE"]
                    );
                }
            }
            else
            {
                if(intval($arFields["PREVIEW_PICTURE"]) <= 0)
                    unset($arFields["PREVIEW_PICTURE"]);
            }
        }

        if(is_set($arFields, "DETAIL_PICTURE"))
        {
            if(is_array($arFields["DETAIL_PICTURE"]))
            {
                if(strlen($arFields["DETAIL_PICTURE"]["name"])<=0 && strlen($arFields["DETAIL_PICTURE"]["del"])<=0)
                {
                    unset($arFields["DETAIL_PICTURE"]);
                }
                else
                {
                    $arFields["DETAIL_PICTURE"]["MODULE_ID"] = "iblock";
                    $arFields["DETAIL_PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
                        $ipropTemplates
                        ,"ELEMENT_DETAIL_PICTURE_FILE_NAME"
                        ,$arFields
                        ,$arFields["DETAIL_PICTURE"]
                    );
                }
            }
            else
            {
                if(intval($arFields["DETAIL_PICTURE"]) <= 0)
                    unset($arFields["DETAIL_PICTURE"]);
            }
        }

        if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
            $arFields["ACTIVE"]="N";

        if(is_set($arFields, "PREVIEW_TEXT_TYPE") && $arFields["PREVIEW_TEXT_TYPE"]!="html")
            $arFields["PREVIEW_TEXT_TYPE"]="text";

        if(is_set($arFields, "DETAIL_TEXT_TYPE") && $arFields["DETAIL_TEXT_TYPE"]!="html")
            $arFields["DETAIL_TEXT_TYPE"]="text";

        if(is_set($arFields, "DATE_ACTIVE_FROM"))
            $arFields["ACTIVE_FROM"] = $arFields["DATE_ACTIVE_FROM"];
        if(is_set($arFields, "DATE_ACTIVE_TO"))
            $arFields["ACTIVE_TO"] = $arFields["DATE_ACTIVE_TO"];
        if(is_set($arFields, "EXTERNAL_ID"))
            $arFields["XML_ID"] = $arFields["EXTERNAL_ID"];

        $arFields["SEARCHABLE_CONTENT"] = $arFields["NAME"];
        if(isset($arFields["PREVIEW_TEXT"]))
        {
            if(isset($arFields["PREVIEW_TEXT_TYPE"]) && $arFields["PREVIEW_TEXT_TYPE"] == "html")
                $arFields["SEARCHABLE_CONTENT"] .= "\r\n".HTMLToTxt($arFields["PREVIEW_TEXT"]);
            else
                $arFields["SEARCHABLE_CONTENT"] .= "\r\n".$arFields["PREVIEW_TEXT"];
        }
        if(isset($arFields["DETAIL_TEXT"]))
        {
            if(isset($arFields["DETAIL_TEXT_TYPE"]) && $arFields["DETAIL_TEXT_TYPE"] == "html")
                $arFields["SEARCHABLE_CONTENT"] .= "\r\n".HTMLToTxt($arFields["DETAIL_TEXT"]);
            else
                $arFields["SEARCHABLE_CONTENT"] .= "\r\n".$arFields["DETAIL_TEXT"];
        }
        $arFields["SEARCHABLE_CONTENT"] = ToUpper($arFields["SEARCHABLE_CONTENT"]);

		self::CheckFieldsComp($strWarning, $arFields);
        if(strlen($strWarning))
        {
			$this->LAST_ERROR = $strWarning;
            $Result = false;
            $arFields["RESULT_MESSAGE"] = $strWarning;
        }
        else
        {
            if(array_key_exists("PREVIEW_PICTURE", $arFields))
            {
                $SAVED_PREVIEW_PICTURE = $arFields["PREVIEW_PICTURE"];
                if(is_array($arFields["PREVIEW_PICTURE"]))
                    \CFile::SaveForDB($arFields, "PREVIEW_PICTURE", "iblock");
            }

            if(array_key_exists("DETAIL_PICTURE", $arFields))
            {
                $SAVED_DETAIL_PICTURE = $arFields["DETAIL_PICTURE"];
                if(is_array($arFields["DETAIL_PICTURE"]))
                    \CFile::SaveForDB($arFields, "DETAIL_PICTURE", "iblock");
            }

            unset($arFields["ID"]);
            if(is_object($USER))
            {
                if(!isset($arFields["CREATED_BY"]) || intval($arFields["CREATED_BY"]) <= 0)
                    $arFields["CREATED_BY"] = intval($USER->GetID());
                if(!isset($arFields["MODIFIED_BY"]) || intval($arFields["MODIFIED_BY"]) <= 0)
                    $arFields["MODIFIED_BY"] = intval($USER->GetID());
            }
            //$arFields["~TIMESTAMP_X"] = $arFields["~DATE_CREATE"] = \Bitrix\Main\Application::getConnection()->getSqlHelper()->getCurrentDateTimeFunction();
			$arFields["TIMESTAMP_X"] = $arFields["DATE_CREATE"] = new \Bitrix\Main\Type\DateTime();

            foreach (GetModuleEvents("iblock", "OnIBlockElementAdd", true) as $arEvent)
                ExecuteModuleEventEx($arEvent, array($arFields));

            $IBLOCK_SECTION_ID = $arFields["IBLOCK_SECTION_ID"];
            unset($arFields["IBLOCK_SECTION_ID"]);

            //$ID = $DB->Add("b_iblock_element", $arFields, array("DETAIL_TEXT", "SEARCHABLE_CONTENT"), "iblock");
			$dbRes = self::add(self::PrepareTblFields($arFields));
			if($dbRes->isSuccess()) $ID = $dbRes->getID();
			else $this->LAST_ERROR = implode('<br>', $dbRes->GetErrorMessages());

            if(array_key_exists("PREVIEW_PICTURE", $arFields))
            {
                $arFields["PREVIEW_PICTURE_ID"] = $arFields["PREVIEW_PICTURE"];
                $arFields["PREVIEW_PICTURE"] = $SAVED_PREVIEW_PICTURE;
            }

            if(array_key_exists("DETAIL_PICTURE", $arFields))
            {
                $arFields["DETAIL_PICTURE_ID"] = $arFields["DETAIL_PICTURE"];
                $arFields["DETAIL_PICTURE"] = $SAVED_DETAIL_PICTURE;
            }

            if(\CIBlockElement::GetIBVersion($arFields["IBLOCK_ID"])==2)
                $DB->Query("INSERT INTO b_iblock_element_prop_s".$arFields["IBLOCK_ID"]."(IBLOCK_ELEMENT_ID)VALUES(".$ID.")");

            if($ID > 0 && (!isset($arFields["XML_ID"]) || strlen($arFields["XML_ID"]) <= 0))
            {
                $arFields["XML_ID"] = $ID;
				self::update($ID, array('XML_ID'=>$ID));
            }

            if(is_set($arFields, "PROPERTY_VALUES"))
                \CIBlockElement::SetPropertyValues($ID, $arFields["IBLOCK_ID"], $arFields["PROPERTY_VALUES"]);

            if(is_set($arFields, "IBLOCK_SECTION"))
                \CIBlockElement::SetElementSection($ID, $arFields["IBLOCK_SECTION"], true, $arIBlock["RIGHTS_MODE"] === "E"? $arIBlock["ID"]: 0, $IBLOCK_SECTION_ID);

            if($arIBlock["RIGHTS_MODE"] === "E")
            {
                $obElementRights = new \CIBlockElementRights($arIBlock["ID"], $ID);
                if(!is_set($arFields, "IBLOCK_SECTION") || empty($arFields["IBLOCK_SECTION"]))
                    $obElementRights->ChangeParents(array(), array(0));
                if(array_key_exists("RIGHTS", $arFields) && is_array($arFields["RIGHTS"]))
                    $obElementRights->SetRights($arFields["RIGHTS"]);
            }

            if (array_key_exists("IPROPERTY_TEMPLATES", $arFields))
            {
                $ipropTemplates = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($arIBlock["ID"], $ID);
                $ipropTemplates->set($arFields["IPROPERTY_TEMPLATES"]);
            }

            if($bUpdateSearch)
                \CIBlockElement::UpdateSearch($ID);

            \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($arIBlock["ID"], $ID);

            /*if(
                !isset($arFields["WF_PARENT_ELEMENT_ID"])
                && $arIBlock["FIELDS"]["LOG_ELEMENT_ADD"]["IS_REQUIRED"] == "Y"
            )
            {
                $USER_ID = is_object($USER)? intval($USER->GetID()) : 0;
                $arEvents = GetModuleEvents("main", "OnBeforeEventLog", true);
                if(
                    empty($arEvents)
                    || ExecuteModuleEventEx($arEvents[0], array($USER_ID))===false
                )
                {
                    $rsElement = \CIBlockElement::GetList(array(), array("=ID"=>$ID), false, false, array("LIST_PAGE_URL", "NAME", "CODE"));
                    $arElement = $rsElement->GetNext();
                    $res = array(
                        "ID" => $ID,
                        "CODE" => $arElement["CODE"],
                        "NAME" => $arElement["NAME"],
                        "ELEMENT_NAME" => $arIBlock["ELEMENT_NAME"],
                        "USER_ID" => $USER_ID,
                        "IBLOCK_PAGE_URL" => $arElement["LIST_PAGE_URL"],
                    );
                    \CEventLog::Log(
                        "IBLOCK",
                        "IBLOCK_ELEMENT_ADD",
                        "iblock",
                        $arIBlock["ID"],
                        serialize($res)
                    );
                }
            }*/

            $Result = $ID;
            $arFields["ID"] = &$ID;
            $_SESSION["SESS_RECOUNT_DB"] = "Y";
        }

        if(
            isset($arFields["PREVIEW_PICTURE"])
            && is_array($arFields["PREVIEW_PICTURE"])
            && isset($arFields["PREVIEW_PICTURE"]["COPY_FILE"])
            && $arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y"
            && $arFields["PREVIEW_PICTURE"]["copy"]
        )
        {
            @unlink($arFields["PREVIEW_PICTURE"]["tmp_name"]);
            @rmdir(dirname($arFields["PREVIEW_PICTURE"]["tmp_name"]));
        }

        if(
            isset($arFields["DETAIL_PICTURE"])
            && is_array($arFields["DETAIL_PICTURE"])
            && isset($arFields["DETAIL_PICTURE"]["COPY_FILE"])
            && $arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y"
            && $arFields["DETAIL_PICTURE"]["copy"]
        )
        {
            @unlink($arFields["DETAIL_PICTURE"]["tmp_name"]);
            @rmdir(dirname($arFields["DETAIL_PICTURE"]["tmp_name"]));
        }

        $arFields["RESULT"] = &$Result;

        foreach (GetModuleEvents("iblock", "OnAfterIBlockElementAdd", true) as $arEvent)
            ExecuteModuleEventEx($arEvent, array(&$arFields));

        return $Result;
    }
	
    function UpdateComp($ID, $arFields, $bWorkFlow=false, $bUpdateSearch=true, $bResizePictures=false, $bCheckDiskQuota=true)
    {
		clearstatcache();
		if(!$this->optimizeApi)
		{
			$el = new \CIblockElement();
			$result = $el->Update($ID, $arFields, $bWorkFlow, $bUpdateSearch, $bResizePictures, $bCheckDiskQuota);
			$this->LAST_ERROR = $el->LAST_ERROR;
			return $result;
		}
		
        global $DB, $USER;
        $ID = (int)$ID;
		$this->LAST_ERROR = '';

        $db_element = \CIBlockElement::GetList(array(), array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false,
            array(
                "ID",
                "TIMESTAMP_X",
                "MODIFIED_BY",
                "DATE_CREATE",
                "CREATED_BY",
                "IBLOCK_ID",
                "IBLOCK_SECTION_ID",
                "ACTIVE",
                "ACTIVE_FROM",
                "ACTIVE_TO",
                "SORT",
                "NAME",
                "PREVIEW_PICTURE",
                "PREVIEW_TEXT",
                "PREVIEW_TEXT_TYPE",
                "DETAIL_PICTURE",
                "DETAIL_TEXT",
                "DETAIL_TEXT_TYPE",
                "WF_STATUS_ID",
                "WF_PARENT_ELEMENT_ID",
                "WF_NEW",
                "WF_COMMENTS",
                "IN_SECTIONS",
                "CODE",
                "TAGS",
                "XML_ID",
                "TMP_ID",
            )
        );
        if(!($ar_element = $db_element->Fetch()))
            return false;

        $arIBlock = \CIBlock::GetArrayByID($ar_element["IBLOCK_ID"]);
        $ar_wf_element = $ar_element;

        if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
            $arFields["ACTIVE"]="N";

        if(is_set($arFields, "PREVIEW_TEXT_TYPE") && $arFields["PREVIEW_TEXT_TYPE"]!="html")
            $arFields["PREVIEW_TEXT_TYPE"]="text";

        if(is_set($arFields, "DETAIL_TEXT_TYPE") && $arFields["DETAIL_TEXT_TYPE"]!="html")
            $arFields["DETAIL_TEXT_TYPE"]="text";

        $strWarning = "";
        if($bResizePictures)
        {
            $arDef = $arIBlock["FIELDS"]["PREVIEW_PICTURE"]["DEFAULT_VALUE"];

            if(
                $arDef["DELETE_WITH_DETAIL"] === "Y"
                && is_array($arFields["DETAIL_PICTURE"])
                && $arFields["DETAIL_PICTURE"]["del"] === "Y"
            )
            {
                $arFields["PREVIEW_PICTURE"]["del"] = "Y";
            }

            if(
                $arDef["FROM_DETAIL"] === "Y"
                && (
                    (is_array($arFields["PREVIEW_PICTURE"]) && $arFields["PREVIEW_PICTURE"]["size"] <= 0)
                    || $arDef["UPDATE_WITH_DETAIL"] === "Y"
                )
                && is_array($arFields["DETAIL_PICTURE"])
                && $arFields["DETAIL_PICTURE"]["size"] > 0
            )
            {
                if(
                    $arFields["PREVIEW_PICTURE"]["del"] !== "Y"
                    && $arDef["UPDATE_WITH_DETAIL"] !== "Y"
                )
                {
                    $rsElement = \CIBlockElement::GetList(Array("ID" => "DESC"), Array("ID" => $ar_wf_element["ID"], "IBLOCK_ID" => $ar_wf_element["IBLOCK_ID"], "SHOW_HISTORY"=>"Y"), false, false, Array("ID", "PREVIEW_PICTURE"));
                    $arOldElement = $rsElement->Fetch();
                }
                else
                {
                    $arOldElement = false;
                }

                if(!$arOldElement || !$arOldElement["PREVIEW_PICTURE"])
                {
                    $arNewPreview = $arFields["DETAIL_PICTURE"];
                    $arNewPreview["COPY_FILE"] = "Y";
                    if (
                        isset($arFields["PREVIEW_PICTURE"])
                        && is_array($arFields["PREVIEW_PICTURE"])
                        && isset($arFields["PREVIEW_PICTURE"]["description"])
                    )
                    {
                        $arNewPreview["description"] = $arFields["PREVIEW_PICTURE"]["description"];
                    }

                    $arFields["PREVIEW_PICTURE"] = $arNewPreview;
                }
            }

            if(
                array_key_exists("PREVIEW_PICTURE", $arFields)
                && is_array($arFields["PREVIEW_PICTURE"])
                && $arFields["PREVIEW_PICTURE"]["size"] > 0
                && $arDef["SCALE"] === "Y"
            )
            {
                $arNewPicture = \CIBlock::ResizePicture($arFields["PREVIEW_PICTURE"], $arDef);
                if(is_array($arNewPicture))
                {
                    $arNewPicture["description"] = $arFields["PREVIEW_PICTURE"]["description"];
                    $arFields["PREVIEW_PICTURE"] = $arNewPicture;
                }
                /*elseif($arDef["IGNORE_ERRORS"] !== "Y")
                {
                    unset($arFields["PREVIEW_PICTURE"]);
                    $strWarning .= Loc::getMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
                }*/
            }

            if(
                array_key_exists("PREVIEW_PICTURE", $arFields)
                && is_array($arFields["PREVIEW_PICTURE"])
                && $arDef["USE_WATERMARK_FILE"] === "Y"
            )
            {
                if(
                    strlen($arFields["PREVIEW_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["PREVIEW_PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
                        || ($arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PREVIEW_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["PREVIEW_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["PREVIEW_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["PREVIEW_PICTURE"]["copy"] = true;
                    $arFields["PREVIEW_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["PREVIEW_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_FILE_POSITION"],
                    "type" => "file",
                    "size" => "real",
                    "alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
                    "file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
                ));
            }

            if(
                array_key_exists("PREVIEW_PICTURE", $arFields)
                && is_array($arFields["PREVIEW_PICTURE"])
                && $arDef["USE_WATERMARK_TEXT"] === "Y"
            )
            {
                if(
                    strlen($arFields["PREVIEW_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["PREVIEW_PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
                        || ($arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PREVIEW_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["PREVIEW_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["PREVIEW_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["PREVIEW_PICTURE"]["copy"] = true;
                    $arFields["PREVIEW_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["PREVIEW_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_TEXT_POSITION"],
                    "type" => "text",
                    "coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
                    "text" => $arDef["WATERMARK_TEXT"],
                    "font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
                    "color" => $arDef["WATERMARK_TEXT_COLOR"],
                ));
            }

            $arDef = $arIBlock["FIELDS"]["DETAIL_PICTURE"]["DEFAULT_VALUE"];

            if(
                array_key_exists("DETAIL_PICTURE", $arFields)
                && is_array($arFields["DETAIL_PICTURE"])
                && $arDef["SCALE"] === "Y"
            )
            {
                $arNewPicture = \CIBlock::ResizePicture($arFields["DETAIL_PICTURE"], $arDef);
                if(is_array($arNewPicture))
                {
                    $arNewPicture["description"] = $arFields["DETAIL_PICTURE"]["description"];
                    $arFields["DETAIL_PICTURE"] = $arNewPicture;
                }
                /*elseif($arDef["IGNORE_ERRORS"] !== "Y")
                {
                    unset($arFields["DETAIL_PICTURE"]);
                    $strWarning .= Loc::getMessage("IBLOCK_FIELD_DETAIL_PICTURE").": ".$arNewPicture."<br>";
                }*/
            }

            if(
                array_key_exists("DETAIL_PICTURE", $arFields)
                && is_array($arFields["DETAIL_PICTURE"])
                && $arDef["USE_WATERMARK_FILE"] === "Y"
            )
            {
                if(
                    strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PREVIEW_PICTURE"]["tmp_name"]
                        || ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["DETAIL_PICTURE"]["copy"] = true;
                    $arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_FILE_POSITION"],
                    "type" => "file",
                    "size" => "real",
                    "alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
                    "file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
                ));
            }

            if(
                array_key_exists("DETAIL_PICTURE", $arFields)
                && is_array($arFields["DETAIL_PICTURE"])
                && $arDef["USE_WATERMARK_TEXT"] === "Y"
            )
            {
                if(
                    strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
                    && (
                        $arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PREVIEW_PICTURE"]["tmp_name"]
                        || ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
                    )
                )
                {
                    $tmp_name = \CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
                    CheckDirPath($tmp_name);
                    copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
                    $arFields["DETAIL_PICTURE"]["copy"] = true;
                    $arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
                }

                \CIBlock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
                    "name" => "watermark",
                    "position" => $arDef["WATERMARK_TEXT_POSITION"],
                    "type" => "text",
                    "coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
                    "text" => $arDef["WATERMARK_TEXT"],
                    "font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
                    "color" => $arDef["WATERMARK_TEXT_COLOR"],
                ));
            }
        }

        $ipropTemplates = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($ar_element["IBLOCK_ID"], $ar_element["ID"]);
        if(isset($arFields["PREVIEW_PICTURE"]) && is_array($arFields["PREVIEW_PICTURE"]))
        {
            if(
                strlen($arFields["PREVIEW_PICTURE"]["name"])<=0
                && strlen($arFields["PREVIEW_PICTURE"]["del"])<=0
                && !is_set($arFields["PREVIEW_PICTURE"], "description")
            )
            {
                unset($arFields["PREVIEW_PICTURE"]);
            }
            else
            {
                $arFields["PREVIEW_PICTURE"]["MODULE_ID"] = "iblock";
                $arFields["PREVIEW_PICTURE"]["old_file"] = $ar_wf_element["PREVIEW_PICTURE"];
                $arFields["PREVIEW_PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
                    $ipropTemplates
                    ,"ELEMENT_PREVIEW_PICTURE_FILE_NAME"
                    ,array_merge($ar_element, $arFields)
                    ,$arFields["PREVIEW_PICTURE"]
                );
            }
        }

        if(isset($arFields["DETAIL_PICTURE"]) && is_array($arFields["DETAIL_PICTURE"]))
        {
            if(
                strlen($arFields["DETAIL_PICTURE"]["name"])<=0
                && strlen($arFields["DETAIL_PICTURE"]["del"])<=0
                && !is_set($arFields["DETAIL_PICTURE"], "description")
            )
            {
                unset($arFields["DETAIL_PICTURE"]);
            }
            else
            {
                $arFields["DETAIL_PICTURE"]["MODULE_ID"] = "iblock";
                $arFields["DETAIL_PICTURE"]["old_file"] = $ar_wf_element["DETAIL_PICTURE"];
                $arFields["DETAIL_PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
                    $ipropTemplates
                    ,"ELEMENT_DETAIL_PICTURE_FILE_NAME"
                    ,array_merge($ar_element, $arFields)
                    ,$arFields["DETAIL_PICTURE"]
                );
            }
        }

        if(is_set($arFields, "DATE_ACTIVE_FROM"))
            $arFields["ACTIVE_FROM"] = $arFields["DATE_ACTIVE_FROM"];
        if(is_set($arFields, "DATE_ACTIVE_TO"))
            $arFields["ACTIVE_TO"] = $arFields["DATE_ACTIVE_TO"];
        if(is_set($arFields, "EXTERNAL_ID"))
            $arFields["XML_ID"] = $arFields["EXTERNAL_ID"];

        $PREVIEW_tmp = is_set($arFields, "PREVIEW_TEXT")? $arFields["PREVIEW_TEXT"]: $ar_wf_element["PREVIEW_TEXT"];
        $PREVIEW_TYPE_tmp = is_set($arFields, "PREVIEW_TEXT_TYPE")? $arFields["PREVIEW_TEXT_TYPE"]: $ar_wf_element["PREVIEW_TEXT_TYPE"];
        $DETAIL_tmp = is_set($arFields, "DETAIL_TEXT")? $arFields["DETAIL_TEXT"]: $ar_wf_element["DETAIL_TEXT"];
        $DETAIL_TYPE_tmp = is_set($arFields, "DETAIL_TEXT_TYPE")? $arFields["DETAIL_TEXT_TYPE"]: $ar_wf_element["DETAIL_TEXT_TYPE"];

        $arFields["SEARCHABLE_CONTENT"] = ToUpper(
            (is_set($arFields, "NAME")? $arFields["NAME"]: $ar_wf_element["NAME"])."\r\n".
            ($PREVIEW_TYPE_tmp=="html"? HTMLToTxt($PREVIEW_tmp): $PREVIEW_tmp)."\r\n".
            ($DETAIL_TYPE_tmp=="html"? HTMLToTxt($DETAIL_tmp): $DETAIL_tmp)
        );

        if(array_key_exists("IBLOCK_SECTION_ID", $arFields))
        {
            if (!array_key_exists("IBLOCK_SECTION", $arFields))
            {
                $arFields["IBLOCK_SECTION"] = array($arFields["IBLOCK_SECTION_ID"]);
            }
            elseif (is_array($arFields["IBLOCK_SECTION"]) && !in_array($arFields["IBLOCK_SECTION_ID"], $arFields["IBLOCK_SECTION"]))
            {
                unset($arFields["IBLOCK_SECTION_ID"]);
            }
        }

        $arFields["IBLOCK_ID"] = $ar_element["IBLOCK_ID"];
		
		self::CheckFieldsComp($strWarning, $arFields, $ID, $bCheckDiskQuota);
        if(strlen($strWarning))
        {
            $this->LAST_ERROR = $strWarning;
            $Result = false;
            $arFields["RESULT_MESSAGE"] = $strWarning;
        }
        else
        {
            unset($arFields["ID"]);

            if(array_key_exists("PREVIEW_PICTURE", $arFields))
            {
                $SAVED_PREVIEW_PICTURE = $arFields["PREVIEW_PICTURE"];
            }
            else
            {
                $SAVED_PREVIEW_PICTURE = false;
            }

            if(array_key_exists("DETAIL_PICTURE", $arFields))
            {
                $SAVED_DETAIL_PICTURE = $arFields["DETAIL_PICTURE"];
            }
            else
            {
                $SAVED_DETAIL_PICTURE = false;
            }

			if(array_key_exists("PREVIEW_PICTURE", $arFields))
				\CFile::SaveForDB($arFields, "PREVIEW_PICTURE", "iblock");
			if(array_key_exists("DETAIL_PICTURE", $arFields))
				\CFile::SaveForDB($arFields, "DETAIL_PICTURE", "iblock");

            $newFields = $arFields;
            $newFields["ID"] = $ID;
            $IBLOCK_SECTION_ID = $arFields["IBLOCK_SECTION_ID"];
            unset($arFields["IBLOCK_ID"], $arFields["WF_NEW"], $arFields["IBLOCK_SECTION_ID"]);

            $bTimeStampNA = false;
            if(is_set($arFields, "TIMESTAMP_X") && ($arFields["TIMESTAMP_X"] === NULL || $arFields["TIMESTAMP_X"]===false))
            {
                $bTimeStampNA = true;
                unset($arFields["TIMESTAMP_X"]);
                unset($newFields["TIMESTAMP_X"]);
            }
			if(!$bTimeStampNA)
			{
				$arFields["TIMESTAMP_X"] = new \Bitrix\Main\Type\DateTime();
			}

            foreach (GetModuleEvents("iblock", "OnIBlockElementUpdate", true) as $arEvent)
                ExecuteModuleEventEx($arEvent, array($newFields, $ar_wf_element));
            unset($newFields);

           /* $strUpdate = $DB->PrepareUpdate("b_iblock_element", $arFields, "iblock");
            if(!empty($strUpdate))
                $strUpdate .= ", ";
            $strSql = "UPDATE b_iblock_element SET ".$strUpdate.($bTimeStampNA?"TIMESTAMP_X=TIMESTAMP_X":"TIMESTAMP_X=now()")." WHERE ID=".$ID;
            $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);*/
			$dbRes = self::update($ID, self::PrepareTblFields($arFields));

            if(
                isset($arFields["PROPERTY_VALUES"])
                && is_array($arFields["PROPERTY_VALUES"])
                && !empty($arFields["PROPERTY_VALUES"])
            )
                \CIBlockElement::SetPropertyValues($ID, $ar_element["IBLOCK_ID"], $arFields["PROPERTY_VALUES"]);

            if(is_set($arFields, "IBLOCK_SECTION"))
                \CIBlockElement::SetElementSection($ID, $arFields["IBLOCK_SECTION"], false, $arIBlock["RIGHTS_MODE"] === "E"? $arIBlock["ID"]: 0, $IBLOCK_SECTION_ID);

            if($arIBlock["RIGHTS_MODE"] === "E")
            {
                $obElementRights = new \CIBlockElementRights($arIBlock["ID"], $ID);
                if(array_key_exists("RIGHTS", $arFields) && is_array($arFields["RIGHTS"]))
                    $obElementRights->SetRights($arFields["RIGHTS"]);
            }

            if (array_key_exists("IPROPERTY_TEMPLATES", $arFields))
            {
                $ipropTemplates = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($arIBlock["ID"], $ID);
                $ipropTemplates->set($arFields["IPROPERTY_TEMPLATES"]);
            }

            if($bUpdateSearch)
            {
                \CIBlockElement::UpdateSearch($ID, true);
            }

            \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($arIBlock["ID"], $ID);

            //Restore saved values
            if($SAVED_PREVIEW_PICTURE !== false)
            {
                $arFields["PREVIEW_PICTURE_ID"] = $arFields["PREVIEW_PICTURE"];
                $arFields["PREVIEW_PICTURE"] = $SAVED_PREVIEW_PICTURE;
            }
            else
            {
                unset($arFields["PREVIEW_PICTURE"]);
            }

            if($SAVED_DETAIL_PICTURE !== false)
            {
                $arFields["DETAIL_PICTURE_ID"] = $arFields["DETAIL_PICTURE"];
                $arFields["DETAIL_PICTURE"] = $SAVED_DETAIL_PICTURE;
            }
            else
            {
                unset($arFields["DETAIL_PICTURE"]);
            }

            /*if($arIBlock["FIELDS"]["LOG_ELEMENT_EDIT"]["IS_REQUIRED"] == "Y")
            {
                $USER_ID = is_object($USER)? intval($USER->GetID()) : 0;
                $arEvents = GetModuleEvents("main", "OnBeforeEventLog", true);
                if(empty($arEvents) || ExecuteModuleEventEx($arEvents[0], array($USER_ID))===false)
                {
                    $rsElement = \CIBlockElement::GetList(
                        array(),
                        array("=ID" => $ID, "CHECK_PERMISSIONS" => "N", "SHOW_NEW" => "Y"),
                        false, false,
                        array("ID", "NAME", "LIST_PAGE_URL", "CODE")
                    );
                    $arElement = $rsElement->GetNext();
                    $res = array(
                        "ID" => $ID,
                        "CODE" => $arElement["CODE"],
                        "NAME" => $arElement["NAME"],
                        "ELEMENT_NAME" => $arIBlock["ELEMENT_NAME"],
                        "USER_ID" => $USER_ID,
                        "IBLOCK_PAGE_URL" => $arElement["LIST_PAGE_URL"],
                    );
                    \CEventLog::Log(
                        "IBLOCK",
                        "IBLOCK_ELEMENT_EDIT",
                        "iblock",
                        $arIBlock["ID"],
                        serialize($res)
                    );
                }
            }*/
            $Result = true;

            /************* QUOTA *************/
            $_SESSION["SESS_RECOUNT_DB"] = "Y";
            /************* QUOTA *************/
        }

        $arFields["ID"] = $ID;
        $arFields["IBLOCK_ID"] = $ar_element["IBLOCK_ID"];
        $arFields["RESULT"] = &$Result;

        if(
            isset($arFields["PREVIEW_PICTURE"])
            && is_array($arFields["PREVIEW_PICTURE"])
            && $arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y"
            && $arFields["PREVIEW_PICTURE"]["copy"]
        )
        {
            @unlink($arFields["PREVIEW_PICTURE"]["tmp_name"]);
            @rmdir(dirname($arFields["PREVIEW_PICTURE"]["tmp_name"]));
        }

        if(
            isset($arFields["DETAIL_PICTURE"])
            && is_array($arFields["DETAIL_PICTURE"])
            && $arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y"
            && $arFields["DETAIL_PICTURE"]["copy"]
        )
        {
            @unlink($arFields["DETAIL_PICTURE"]["tmp_name"]);
            @rmdir(dirname($arFields["DETAIL_PICTURE"]["tmp_name"]));
        }

        foreach (GetModuleEvents("iblock", "OnAfterIBlockElementUpdate", true) as $arEvent)
            ExecuteModuleEventEx($arEvent, array(&$arFields));

        return $Result;
    }
	
	public static function CheckFieldsComp(&$strWarning, &$arFields, $ID=false, $bCheckDiskQuota=true)
	{
		$el = new \CIBlockElement;
		if(!$el->CheckFields($arFields, $ID, $bCheckDiskQuota))
		{
			$arErrors = preg_split('/<br(>|\s[^>]*>)/is', $el->LAST_ERROR);
			foreach($arErrors as $k=>$v)
			{
				if(strlen(trim($v))==0 || stripos($v, 'webp')!==false) unset($arErrors[$k]);
			}
			if(count($arErrors) > 0) $strWarning = implode('<br>', $arErrors).'<br>'.$strWarning;
		}
	}
	
	public static function PrepareTblFields($arFields)
	{
		$arTblFields = self::GetTblFields();
		foreach($arFields as $k=>$v)
		{
			if(!in_array($k, $arTblFields)) unset($arFields[$k]);
		}
		return $arFields;
	}
	
	public static function GetTblFields()
	{
		if(!isset(self::$arTblFields) || !is_array(self::$arTblFields))
		{
			$arTblFields = array();
			$arMap = self::getMap();
			foreach($arMap as $k=>$v)
			{
				if((is_object($v) && ($v instanceof \Bitrix\Main\Entity\ReferenceField))
					|| is_array($v) && isset($v['reference']) && isset($v['data_type'])) continue;
				if(is_callable(array($v, 'getColumnName'))) $arTblFields[] = $v->getColumnName();
				//elseif(is_callable(array($v, 'getTitle'))) $arTblFields[] = $v->getTitle();
				elseif(!is_numeric($k)) $arTblFields[] = $k;
			}
			self::$arTblFields = $arTblFields;
		}
		return self::$arTblFields;
	}
	
	public static function GetListComp($arFilter, $arKeys, $arOrder=array(), $limit=false)
	{
		if(empty($arOrder)) $arOrder = array('ID'=>'ASC');
		if(!isset($arFilter['CHECK_PERMISSIONS'])) $arFilter['CHECK_PERMISSIONS'] = 'N';
		$arFilterKeys = array_keys($arFilter);
		$hash = md5(serialize(array($arFilterKeys, $arKeys, $arOrder, $limit)));
		if(!isset(self::$elemListHash[$hash]))
		{
			$mtype = '';
			if(class_exists('\Bitrix\Iblock\ElementTable'))
			{
				$arNeedKeys = array_merge($arKeys, array_keys($arOrder));
				$arNeedFilterKeys = array();
				foreach($arFilter as $key=>$val)
				{
					$needKey = preg_replace('/^[^\d\w]*([\d\w]|$)/', '$1', $key);
					if($needKey!='CHECK_PERMISSIONS') $arNeedFilterKeys[] = $needKey;
				}
				$arFields = array_keys(\Bitrix\Iblock\ElementTable::getMap());

				if(count(array_diff(array_merge($arNeedKeys, $arNeedFilterKeys), $arFields))==0)
				{
					$mtype = 'd7';
				}
				elseif(\Bitrix\KdaImportexcel\DataManager\ElementPropertyTable::issetValueIndex() && count(preg_grep('/^(IBLOCK_ID|CHECK_PERMISSIONS|[=%]PROPERTY_\d+|[=%]PROPERTY_\d+_VALUE)$/', $arFilterKeys))==count($arFilterKeys) && isset($arFilter['IBLOCK_ID']) && is_numeric($arFilter['IBLOCK_ID']) && $limit===false)
				{
					$arFilter['IBLOCK_ID'] = (int)$arFilter['IBLOCK_ID'];
					$arIblock = \Bitrix\Iblock\IblockTable::getList(array('filter'=>array('ID'=>$arFilter['IBLOCK_ID']), 'select'=>array('VERSION')))->Fetch();
					if($arIblock['VERSION']==1)
					{
						eval('namespace Bitrix\KdaImportexcel\DataManager;'."\r\n".
							'class ElementProperty'.$arFilter['IBLOCK_ID'].'Table extends ElementPropertyTable{'."\r\n".
								'public static function getMap(){return parent::getMapForIblock('.$arFilter['IBLOCK_ID'].');}'.
							'}');
						if(count(array_diff($arNeedKeys, $arFields))==0)
						{
							$mtype = 'd7_props';
						}
						else $mtype = 'props';
					}
				}
			}
			self::$elemListHash[$hash] = $mtype;
		}
		$mtype = self::$elemListHash[$hash];
		
		$dbResult = false;
		if($mtype=='d7')
		{
			if(isset($arFilter['CHECK_PERMISSIONS'])) unset($arFilter['CHECK_PERMISSIONS']);
			$arKeys = array_diff($arKeys, array('IBLOCK_SECTION'));
			$arParams = array('filter'=>$arFilter, 'select'=>$arKeys);
			if(!empty($arOrder)) $arParams['order'] = $arOrder;
			if($limit!==false) $arParams['limit'] = $limit;
			$dbResult = \Bitrix\Iblock\ElementTable::getList($arParams);
		}
		elseif(in_array($mtype, array('d7_props', 'props')))
		{
			$iblockId = (int)$arFilter['IBLOCK_ID'];
			$className = '\Bitrix\KdaImportexcel\DataManager\ElementProperty'.$iblockId.'Table';
			$arNewFilter = array();
			$i = 0;
			foreach($arFilter as $k=>$v)
			{
				$emptyVal = !(strlen(is_array($v) ? implode('', $v) : $v) > 0);
				if(preg_match('/^([=%])PROPERTY_(\d+)$/', $k, $m) || preg_match('/^([=%])PROPERTY_(\d+)_(VALUE)$/', $k, $m))
				{
					$op = $m[1];
					$propId = $m[2];
					if($emptyVal)
					{
						$arNewFilter[] = array('LOGIC'=>'OR', array('=P'.$propId.'.VALUE'=>''), array('=P'.$propId.'.ID'=>false));
					}
					else
					{
						$prefix = str_repeat('SP.', $i++);
						$arNewFilter['='.$prefix.'IBLOCK_PROPERTY_ID'] = $propId;
						if($m[3]=='VALUE') $arNewFilter[$op.$prefix.'PROP_ENUM_VAL.VALUE'] = $v;
						else $arNewFilter[$op.$prefix.'VALUE'] = $v;
					}
					unset($arFilter[$k]);
				}
			}
			
			if(!empty($arNewFilter))
			{
				$arIds = array();
				$dbRes = $className::getList(array('filter'=>$arNewFilter, 'select'=>array('IBLOCK_ELEMENT_ID')));
				while($arr = $dbRes->Fetch())
				{
					$arIds[] = $arr['IBLOCK_ELEMENT_ID'];
				}
				if(!empty($arIds)) $arFilter['=ID'] = $arIds;
				else  $arFilter['=ID'] = -1;
				if($mtype=='d7_props')
				{
					if(isset($arFilter['CHECK_PERMISSIONS'])) unset($arFilter['CHECK_PERMISSIONS']);
					$arKeys = array_diff($arKeys, array('IBLOCK_SECTION'));
					$arParams = array('filter'=>$arFilter, 'select'=>$arKeys);
					if(!empty($arOrder)) $arParams['order'] = $arOrder;
					if($limit!==false) $arParams['limit'] = $limit;
					$dbResult = \Bitrix\Iblock\ElementTable::getList($arParams);
				}
				else
				{
					$dbResult = \CIblockElement::GetList($arOrder, $arFilter, false, ($limit===false ? false : array('nTopCount'=>$limit)), $arKeys);
				}
			}
		}
		
		if($dbResult===false)
		{
			$dbResult = \CIblockElement::GetList($arOrder, $arFilter, false, ($limit===false ? false : array('nTopCount'=>$limit)), $arKeys);
		}
		return $dbResult;
	}
	
	public static function SelectedRowsCountComp($dbRes)
	{
		if(is_callable(array($dbRes, 'getSelectedRowsCount'))) return $dbRes->getSelectedRowsCount();
		elseif(is_callable(array($dbRes, 'SelectedRowsCount'))) return $dbRes->SelectedRowsCount();
		else return 0;
	}
	
	public static function ExistsElement($arFilter)
	{
		if(class_exists('\Bitrix\Iblock\ElementTable'))
		{
			if(\Bitrix\Iblock\ElementTable::getList(array('filter'=>array($arFilter), 'select'=>array('ID'), 'limit'=>1))->Fetch()) return true;
			else return false;
		}
		else
		{
			return (bool)(\CIblockElement::GetList(array(), array_merge($arFilter, array('CHECK_PERMISSIONS' => 'N')), array()) > 0);
		}
		return false;
	}
}