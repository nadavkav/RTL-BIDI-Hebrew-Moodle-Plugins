<?php
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/kaltura/lib.php');


$type="video";

if (isset($_POST["type"]))
{
  $type=$_POST["type"];
}

if ($type=='ppt')
{
  check_ppt_status($_POST["downloadUrl"]);
}
else
{
  check_video_status($_POST["entryid"]);
}

function check_video_status($entryId)
{
  try
  {
	  $client = KalturaHelpers::getKalturaClient();

	  $entry = $client -> baseEntry -> get ($entryId);

	  if ($entry -> status == KalturaEntryStatus::READY)
	  {
		  echo 'y:<img src="'. KalturaHelpers::getThumbnailUrl(null, $entryId, 140, 105) .'" />';
	  }
	  else
	  {
		  echo 'n:';
	  }
  }
  catch(Exception $exp)
  {
	  die('e:' . $exp->getMessage());
  }
}

function check_ppt_status($url)
{
	// change $url to verify swf exists on kaltura
	$random_hit = time()+ rand(0,2000);
	if (strpos($url, 'cdn.kaltura.com'))
	{
		$url = str_replace('cdn.kaltura.com','www.kaltura.com',$url);
	}
	// added random query string to avoid caching
	$ch = curl_init($url.'?'.$random_hit);
 
	curl_setopt($ch, CURLOPT_HEADER, 1); // get the header 
  curl_setopt($ch, CURLOPT_NOBODY, 1); // and *only* get the header 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // get the response as a string from curl_exec(), rather than echoing it 
  curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1); // don't use a cached version of the url 
	
	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	
	echo $info["http_code"];
}

?>