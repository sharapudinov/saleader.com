<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

CModule::IncludeModule("iblock");

$arParams["ID"] = intval($arParams["ID"]);
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);

$arParams["DEPTH_LEVEL"] = intval($arParams["DEPTH_LEVEL"]);
if($arParams["DEPTH_LEVEL"]<=0)
	$arParams["DEPTH_LEVEL"]=1;

$arResult["SECTIONS"] = array();
$arResult["ELEMENT_LINKS"] = array();

$obCache = new CPHPCache; 

if ($obCache->InitCache($arParams["CACHE_TIME"], false, "/")) {
   $aMenuLinksNew= $obCache->GetVars(); 
} 
elseif ($obCache->StartDataCache()) {

	$arFilter = array(
		"IBLOCK_ID"=>$arParams["IBLOCK_ID"],
		"GLOBAL_ACTIVE"=>"Y",
		"IBLOCK_ACTIVE"=>"Y",
		"<="."DEPTH_LEVEL" => $arParams["DEPTH_LEVEL"],
		"CNT_ACTIVE" => "Y",
	);
	$arOrder = array(
		"left_margin"=>"asc",
	);

	$rsSections = CIBlockSection::GetList($arOrder, $arFilter, true, array(
		"ID",
		"DEPTH_LEVEL",
		"NAME",
		"SECTION_PAGE_URL",
		"SORT",
		"PICTURE",
		"ELEMENT_CNT",
		"DETAIL_PICTURE",
		"UF_PHOTO",

	));
	if($arParams["IS_SEF"] !== "Y")
		$rsSections->SetUrlTemplates("", $arParams["SECTION_URL"]);
	else
		$rsSections->SetUrlTemplates("", $arParams["SEF_BASE_URL"].$arParams["SECTION_PAGE_URL"]);
	while($arSection = $rsSections->GetNext()){
		if ($arSection["ELEMENT_CNT"]==0) continue;
		$bigPic = "";
		$detailPicture = "";
		if(!empty($arSection["UF_PHOTO"])){
			$bigPic = CFile::ResizeImageGet($arSection["UF_PHOTO"], array("width" => 1920, "height" => 1080), BX_RESIZE_IMAGE_PROPORTIONAL, false);
		}
		if($arSection["DEPTH_LEVEL"] == 1){
			$detailPicture = CFile::ResizeImageGet($arSection["DETAIL_PICTURE"], array("width" => 200, "height" => 130), BX_RESIZE_IMAGE_PROPORTIONAL, false);
		}
		$arResult["SECTIONS"][] = array(
			"ID" => $arSection["ID"],
			"DEPTH_LEVEL" => $arSection["DEPTH_LEVEL"],
			"~NAME" => $arSection["~NAME"],
			"~SECTION_PAGE_URL" => $arSection["~SECTION_PAGE_URL"],
			"SORT" => $arSection["SORT"],
			"PICTURE" => $arSection["PICTURE"],
			"DETAIL_PICTURE" => $arSection["DEPTH_LEVEL"] == 1 ? $detailPicture : $arSection["DETAIL_PICTURE"],
			"ELEMENT_CNT" => $arSection["ELEMENT_CNT"],
			"BIG_PICTURE" => $bigPic
		);
		$arResult["ELEMENT_LINKS"][$arSection["ID"]] = array();
	}

	if(($arParams["ID"] > 0) && (intval($arVariables["SECTION_ID"]) <= 0) && CModule::IncludeModule("iblock"))
	{
		$arSelect = array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "IBLOCK_SECTION_ID");
		$arFilter = array(
			"ID" => $arParams["ID"],
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		);
		$rsElements = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
		if(($arParams["IS_SEF"] === "Y") && (strlen($arParams["DETAIL_PAGE_URL"]) > 0))
			$rsElements->SetUrlTemplates($arParams["SEF_BASE_URL"].$arParams["DETAIL_PAGE_URL"]);
		while($arElement = $rsElements->GetNext())
		{
			$arResult["ELEMENT_LINKS"][$arElement["IBLOCK_SECTION_ID"]][] = $arElement["~DETAIL_PAGE_URL"];
		}
	}



	$aMenuLinksNew = array();
	$menuIndex = 0;
	$previousDepthLevel = 1;
	foreach($arResult["SECTIONS"] as $arSection){

		$arPicture = NULL;

		if ($menuIndex > 0)
			$aMenuLinksNew[$menuIndex - 1][3]["IS_PARENT"] = $arSection["DEPTH_LEVEL"] > $previousDepthLevel;
			$previousDepthLevel = $arSection["DEPTH_LEVEL"];

		$arResult["ELEMENT_LINKS"][$arSection["ID"]][] = urldecode($arSection["~SECTION_PAGE_URL"]);
		
		if($arSection["DEPTH_LEVEL"] == 1 && !empty($arSection["PICTURE"])){
			$arPicture = CFile::ResizeImageGet(
		        CFile::GetFileArray($arSection["PICTURE"]),
		        array("width" => 40, "height" => 40),
		        BX_RESIZE_IMAGE_PROPORTIONAL,
		        true
		    );
		}

		if($arSection["DEPTH_LEVEL"] == 2 && !empty($arSection["DETAIL_PICTURE"])){
			$arPicture = CFile::ResizeImageGet(
		        CFile::GetFileArray($arSection["DETAIL_PICTURE"]),
		        array("width" => 80, "height" => 60),
		        BX_RESIZE_IMAGE_PROPORTIONAL,
		        false
		    );
		}

		$aMenuLinksNew[$menuIndex++] = array(
			htmlspecialcharsbx($arSection["~NAME"]),
			$arSection["~SECTION_PAGE_URL"],
			$arResult["ELEMENT_LINKS"][$arSection["ID"]],
			array(
				"ID"		  => $arSection["ID"],
				"IBLOCK_ID"   => $arParams["IBLOCK_ID"],
				"FROM_IBLOCK" => $arSection["SORT"],
				"DEPTH_LEVEL" => $arSection["DEPTH_LEVEL"],
				"PICTURE" 	  => $arPicture,
				"BIG_PICTURE" => $arSection["BIG_PICTURE"],
				"DETAIL_PICTURE" => $arSection["DETAIL_PICTURE"],
				"ELEMENT_CNT" => $arSection["ELEMENT_CNT"],
				"IS_PARENT"   => false,
			),
		);
	}

   $this->EndResultCache();
   $obCache->EndDataCache($aMenuLinksNew); 
} 
return $aMenuLinksNew;

?>
