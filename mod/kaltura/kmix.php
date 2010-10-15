<?php

require_once("../../config.php");
require_once('lib.php');

try
{

	$kClient = new KalturaClient(KalturaHelpers::getServiceConfiguration());
	$kalturaUser = KalturaHelpers::getPlatformKey("user","");
	$kalturaSecret = KalturaHelpers::getPlatformKey("secret","");


	$ksId = $kClient -> session -> start($kalturaSecret, $kalturaUser, KalturaSessionType::USER);
	$kClient -> setKs($ksId);
	$mix = new KalturaMixEntry();
//	$mix -> name = "Editable video";
	$mix -> name = (empty($_POST["name"]) ? "Editable video" : $_POST["name"]);
	$mix -> editorType = KalturaEditorType::ADVANCED;
	$mix = $kClient -> mixing -> add($mix);

	$arrEntries = explode(',',$_POST['entries']);

	foreach($arrEntries as $index => $entryId)
	{
		if (!empty($entryId))
		{
			$kClient->mixing->appendMediaEntry($mix -> id, $entryId);
		}
	}
	echo 'y:' . $mix -> id;
}
catch(Exception $exp)
{
	die('n:' . $exp->getMessage());
}

?>
