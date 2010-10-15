<?php
require_once("../../config.php");
require_once('lib.php');

function convert_ppt($entryId)
{
  try
  {
    $kClient = KalturaHelpers::getKalturaClient();

    $result = $kClient-> document -> convertPptToSwf($entryId);
    return 'y:http://www.kaltura.com' . $result;

  }
  catch(Exception $exp)
  {
    return 'n:' . $exp->getMessage();
  }
}


function create_swfdoc($ppt_id,$video_id,$name,$path)
{
  global $USER;
  
  $kClient = KalturaHelpers::getKalturaClient();
	  		
  $real_path = $kClient->getConfig()->serviceUrl . '/index.php/extwidget/raw/entry_id/' . $ppt_id . '/p/' . $kClient->getConfig()->partnerId . '/sp/' . $kClient->getConfig()->partnerId*100 . '/type/download/format/swf/direct_serve/1';
      
	$entry_id = $video_id;
//	$path = $_SESSION[$_POST["ppt"]];
	if (strpos($kClient->getConfig()->serviceUrl, 'www.kaltura.com') &&
	    strpos($path, 'www.kaltura.com'))
	{
		$real_path = str_replace('www.kaltura.com','cdn.kaltura.com',$real_path);
	}
	
	$xml = '<sync><video><entryId>'.$entry_id.'</entryId></video><slide><path>'.$real_path.'</path></slide>';
	$xml .= '<times></times></sync>';
    
	$entry = new KalturaDataEntry();
	$entry->dataContent = $xml;
	$entry->mediaType = KalturaEntryType::DOCUMENT;
	$result = $kClient -> data -> add($entry);
	
	// Insert the entry into kaltura_entries
/*	$entry = new stdClass;
	$entry->media_type = '10';
	$entry->entry_id = $result["result"]["entry"]["id"];
	$entry->parent_id = $entry->entry_id;
	$entry->title = mysql_real_escape_string($name);
	$entry->user_id = $USER->id;
	$entry->created = time();
	$newId = insert_record('kaltura_entries', $entry);
*/
	return $result->id;
}

if ($_POST["action"] == "ppt")
{
    if (isset($_POST['ppt']))
    {
      $entryId = $_POST['ppt'];
      die(convert_ppt($entryId));
    }
    else
    {
  	  die('n:' . get_string('missingfile','kaltura'));
    }
}
else if ($_POST["action"] == "swfdoc")
{
    if (isset($_POST['ppt']))
    {
	    $ppt_id = $_POST["ppt"];
	    $video_id = $_POST["video"];
	    $name = $_POST["name"];
      $url = $_POST["downloadUrl"];
      die(create_swfdoc($ppt_id,$video_id,$name,$url));
    }
    else
    {
  	  die('n:' . get_string('missingfile','kaltura'));
    }
}

?>