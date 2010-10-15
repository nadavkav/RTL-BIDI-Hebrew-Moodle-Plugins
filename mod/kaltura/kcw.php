<?php
require_once("../../config.php");
require_once('lib.php');

// Hide Kampyle feedback button
$CFG->kampyle_hide_button = true;

// Report all errors except E_NOTICE
// This is the default value set in php.ini
error_reporting(E_ALL ^ E_NOTICE);
$meta = '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/kaltura/styles.php" />'."\n";
//$meta = '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/kaltura/css/kaltura.css" />'."\n";

print_header('Kaltura Uploader','','','',$meta);

$upload_type = 'video';
$id='';
$mod='video_resource';

if (isset($_GET['upload_type']))
{
	$upload_type = $_GET['upload_type'];
}

if (isset($_GET['mod']))
{
	$mod = $_GET['mod'];
}

if (isset($_GET['id']))
{
  $id = $_GET['id'];
}

if (!empty($id)) 
{
  $entry = get_record('kaltura_entries','id',"$id");
}
else
{
  $last_entry_id = get_field('kaltura_entries','max(id)', 'id', 'id');
  if (!empty($last_entry_id))
  {
    $entry = get_record('kaltura_entries','id',"$last_entry_id");
    $default_entry->title = "";
  }
  else
  {
    $entry = new kaltura_entry;
  }
}

if ($mod == 'video_resource')
{
  echo get_cw_properties_pane($entry, $upload_type == 'video' ? KalturaEntryType::MEDIA_CLIP : KalturaEntryType::MIX);
  echo get_cw_props_player("divClip", 400,332);
}

echo get_cw_wizard("divKalturaCw", 760, 402, $upload_type == 'video' ? KalturaEntryType::MEDIA_CLIP : KalturaEntryType::MIX);

if ($mod == 'video_resource')
{
  echo get_cw_js_functions($upload_type == 'video' ? KalturaEntryType::MEDIA_CLIP : KalturaEntryType::MIX, "divKalturaCw","id_alltext","divClipProps");
}
else if ($mod == 'ppt_resource')
{
  echo get_cw_js_functions($upload_type == 'video' ? KalturaEntryType::MEDIA_CLIP : KalturaEntryType::MIX, "divKalturaCw","id_video_input");
}
else if ($mod == 'assignment')
{
  echo get_cw_js_functions($upload_type == 'video' ? KalturaEntryType::MEDIA_CLIP : KalturaEntryType::MIX, "divKalturaCw","id_widget");
}


print_footer();
?>
