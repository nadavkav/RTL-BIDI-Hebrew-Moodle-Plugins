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

print_header('Kaltura Preview','','','',$meta);

$id='';

if (isset($_GET['entry_id']))
{
  $id = $_GET['entry_id'];
}

if (empty($id)) 
{
  die('missing id');
}

if (isset($_GET['design']))
{
  $design = $_GET['design'];
}
else
{
  $design = 'light';
}

$entry = new kaltura_entry;

if (isset($_GET['width']) && isset($_GET['dimensions']))
{
  $entry->dimensions = $_GET['dimensions'];
  $entry->custom_width = $_GET['width'];
  $entry->size = KalturaPlayerSize::CUSTOM;
}
else
{
  $entry->dimensions = KalturaPlayerSize::LARGE;
  $entry->custom_width = 400;
  $entry->size = KalturaPlayerSize::CUSTOM;
}


echo embed_kaltura($id,get_width($entry),get_height($entry),KalturaEntryType::MEDIA_CLIP,$design);

echo '<div style="width:400px; margin-top:15px; text-align:center;"><input type="button"  value="' . get_string("close","kaltura") . '" onclick="window.parent.kalturaCloseModalBox();" />';

print_footer();
?>
