<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/result_modifier.php")));
__IncludeLang($file);

$arArrays = array();
$arElements = array();
$arSections = array();

$CURRENT_USER_ID = $GLOBALS["USER"]->GetID();
$CURRENT_USER_GROUPS = $GLOBALS["USER"]->GetUserGroupArray();
$backUrl = $APPLICATION->GetCurPageParam();
foreach($arResult["ELEMENTS_ROWS"] as $i => $arRow)
{
	if($arResult["BIZPROC"] == "Y")
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			BizProcDocument::generateDocumentComplexType($arParams["IBLOCK_TYPE_ID"], $arResult["IBLOCK_ID"]),
			BizProcDocument::getDocumentComplexId($arParams["IBLOCK_TYPE_ID"], $arRow["data"]["ID"])
		);

		$USER_GROUPS = $CURRENT_USER_GROUPS;
		if($arRow["data"]["~CREATED_BY"] == $CURRENT_USER_ID)
			$USER_GROUPS[] = "Author";

		$ii = 0;
		$html = "";
		$proccesses = false;
		if($arResult["PROCESSES"] && $arResult["USE_COMMENTS"])
		{
			$proccesses = true;
			$workflows = array();
		}
		foreach ($arDocumentStates as $kk => $vv)
		{
			if($vv["ID"] && $proccesses && !empty($arRow["data"]["WORKFLOW_ID"]))
				$workflows[] = 'WF_'.$vv["ID"];

			$canViewWorkflow = CIBlockDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::ViewWorkflow,
				$CURRENT_USER_ID,
				$arRow["data"]["ID"],
				array(
					"IBlockPermission" => $arResult["IBLOCK_PERM"],
					"AllUserGroups" => $USER_GROUPS,
					"DocumentStates" => $arDocumentStates,
					"WorkflowId" => $kk,
				)
			);
			if (!$canViewWorkflow)
				continue;

			if(strlen($vv["TEMPLATE_NAME"]) > 0)
				$html .= "<b>".$vv["TEMPLATE_NAME"]."</b>:<br />";
			else
				$html .= "<b>".(++$ii)."</b>:<br />";

			$url = CHTTP::urlAddParams(str_replace(
				array("#list_id#", "#document_state_id#", "#group_id#"),
				array($arResult["IBLOCK_ID"], $vv["ID"], $arParams["SOCNET_GROUP_ID"]),
				$arParams["~BIZPROC_LOG_URL"]
			),
				array("back_url" => $backUrl),
				array("skip_empty" => true, "encode" => true)
			);

			$html .= "<a href=\"".htmlspecialcharsbx($url)."\">".(strlen($vv["STATE_TITLE"]) > 0 ? $vv["STATE_TITLE"] : $vv["STATE_NAME"])."</a><br />";
		}

		if ($proccesses)
		{
			$workflows = array_unique($workflows);
			if ($workflows)
			{
				$iterator = CForumTopic::getList(array(), array("@XML_ID" => $workflows));
				while ($row = $iterator->fetch())
					$arResult["COMMENTS_COUNT"][$row["XML_ID"]] = $row["POSTS"];
				$arRow["data"]["COMMENTS"] = '<div class="bp-comments"><a href="#" onclick="if (BX.Bizproc.showWorkflowInfoPopup) return BX.Bizproc.showWorkflowInfoPopup(\''.$arRow["data"]["WORKFLOW_ID"].'\')"><span class="bp-comments-icon"></span>'
					.(!empty($arResult["COMMENTS_COUNT"]['WF_'.$arRow["data"]["WORKFLOW_ID"]]) ? (int) $arResult["COMMENTS_COUNT"]['WF_'.$arRow["data"]["WORKFLOW_ID"]] : '0')
					.'</a></div>';
			}
		}

		$arRow["data"]["BIZPROC"] = $html;
	}

	foreach($arRow["data"] as $FIELD_ID => $value)
	{
		$arField = $arResult["FIELDS"][$FIELD_ID];

		if($FIELD_ID == "PREVIEW_PICTURE" || $FIELD_ID == "DETAIL_PICTURE")
		{
			$obFile = new CListFile(
				$arResult["IBLOCK_ID"],
				0, //section_id
				$arRow["data"]["ID"],
				$FIELD_ID,
				$value
			);
			$obFile->SetSocnetGroup($arParams["SOCNET_GROUP_ID"]);

			$obFileControl = new CListFileControl($obFile, $FIELD_ID);

			$value = '<nobr>'.$obFileControl->GetHTML(array(
					'show_input' => false,
					'max_size' => 1024000,
					'max_width' => 50,
					'max_height' => 50,
					'url_template' => $arParams["~LIST_FILE_URL"],
					'a_title' => GetMessage("CT_BLL_ENLARGE"),
					'download_text' => GetMessage("CT_BLL_DOWNLOAD"),
				)).'</nobr>';
		}
		elseif($FIELD_ID == "IBLOCK_SECTION_ID")
		{
			if(array_key_exists($value, $arResult["SECTIONS"]))
			{
				$value = '<a href="'.str_replace(
						array("#list_id#", "#section_id#", "#group_id#"),
						array($arResult["IBLOCK_ID"], $value, $arParams["SOCNET_GROUP_ID"]),
						$arParams['LIST_URL']
					).'">'.$arResult["SECTIONS"][$value]["NAME"].'</a>';
			}
			else
			{
				$value = "";
			}
		}
		elseif($arField["TYPE"] == "F")
		{
			if(is_array($value))
			{
				foreach($value as $ii => $file)
				{
					$obFile = new CListFile(
						$arResult["IBLOCK_ID"],
						0, //section_id
						$arRow["data"]["ID"],
						$FIELD_ID,
						$file
					);
					$obFile->SetSocnetGroup($arParams["SOCNET_GROUP_ID"]);

					$obFileControl = new CListFileControl($obFile, $FIELD_ID);

					$value[$ii] = '<nobr>'.$obFileControl->GetHTML(array(
							'show_input' => false,
							'max_size' => 1024000,
							'max_width' => 50,
							'max_height' => 50,
							'url_template' => $arParams["~LIST_FILE_URL"],
							'a_title' => GetMessage("CT_BLL_ENLARGE"),
							'download_text' => GetMessage("CT_BLL_DOWNLOAD"),
						)).'</nobr>';
				}
			}
			else
			{
				$obFile = new CListFile(
					$arResult["IBLOCK_ID"],
					0, //section_id
					$arRow["data"]["ID"],
					$FIELD_ID,
					$value
				);
				$obFile->SetSocnetGroup($arParams["SOCNET_GROUP_ID"]);

				$obFileControl = new CListFileControl($obFile, $FIELD_ID);

				$value = '<nobr>'.$obFileControl->GetHTML(array(
						'show_input' => false,
						'max_size' => 1024000,
						'max_width' => 50,
						'max_height' => 50,
						'url_template' => $arParams["~LIST_FILE_URL"],
						'a_title' => GetMessage("CT_BLL_ENLARGE"),
						'download_text' => GetMessage("CT_BLL_DOWNLOAD"),
					)).'</nobr>';
			}
		}
		elseif(preg_match("/^(E|E:)/", $arField["TYPE"]))
		{
			if(is_array($value))
			{
				foreach($value as $ii => $id)
				{
					if($id > 0)
						$arElements[] = &$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID][$ii];
				}
				$arArrays[$i."_".$FIELD_ID] = &$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID];
			}
			elseif($value > 0)
			{
				$arElements[] = &$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID];
			}
			continue;
		}
		elseif($arField["TYPE"] == "G")
		{
			if(is_array($value))
			{
				foreach($value as $ii => $id)
				{
					if($id > 0)
						$arSections[] = &$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID][$ii];
				}
				$arArrays[$i."_".$FIELD_ID] = &$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID];
			}
			elseif($value > 0)
			{
				$arSections[] = &$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID];
			}
			continue;
		}

		$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID] = $value;

		if(is_array($value))
		{
			if(count($value) > 1)
				$arArrays[$i."_".$FIELD_ID] = &$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID];
			else
				$arResult["ELEMENTS_ROWS"][$i]["data"][$FIELD_ID] = $value[0];
		}
	}
}

if(count($arElements))
{
	$rsElements = CIBlockElement::GetList(
		array(),
		array("=ID" => $arElements),
		false,
		false,
		array("ID", "NAME", "IBLOCK_ID")
	);
	$arr = array();
	while($ar = $rsElements->GetNext())
	{
		$urlElement = str_replace(
			array("#list_id#", "#section_id#", "#element_id#", "#group_id#"),
			array($ar["IBLOCK_ID"], 0, $ar["ID"], $arParams["SOCNET_GROUP_ID"]),
			$arParams["LIST_ELEMENT_URL"]
		);
		$arr[$ar["ID"]] = '<a href="'.$urlElement.'">'.$ar["NAME"].'</a>';
	}

	foreach($arElements as $i => $el)
		if(isset($arr[$el]))
			$arElements[$i] = $arr[$el];
}

if(count($arSections))
{
	$rsSections = CIBlockSection::GetList(array(), array("=ID" => $arSections));
	$arr = array();
	while($ar = $rsSections->GetNext())
		$arr[$ar["ID"]] = $ar["NAME"];

	foreach($arSections as $i => $el)
		if(isset($arr[$el]))
			$arSections[$i] = $arr[$el];
}

foreach($arArrays as $i => $ar)
	$arArrays[$i] = implode("&nbsp;/<br>", $ar);
?>