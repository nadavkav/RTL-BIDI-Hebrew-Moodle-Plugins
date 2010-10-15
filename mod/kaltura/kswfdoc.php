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
$context=0;

if (isset($_GET['entry_id']))
{
  $id = $_GET['entry_id'];
}

if (isset($_GET['context']))
{
  $context = $_GET['context'];
}

if (empty($id)) 
{
  die('missing id');
}

$closeBut = get_string("close","kaltura");
$c_context = get_context_instance(CONTEXT_COURSE, $context);
if (has_capability('moodle/course:manageactivities',$c_context)) //check if admin of this widget
{
  $closeBut = get_string("saveclose","kaltura");
}

echo embed_kswfdoc($id,780,358,$context);

echo '<div style="margin-top:7px; width:780px; text-align:center;"><input type="button"  value="' . $closeBut . '" onclick="window.parent.kalturaCloseModalBox();" /><div>'; 


print_footer();
?>
