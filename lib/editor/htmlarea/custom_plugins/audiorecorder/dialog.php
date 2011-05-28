<?php // $Id: insert_table.php,v 1.4 2007/01/27 23:23:44 skodak Exp $
  require_once("../../../../../config.php");
  //include "lib.php";
  
  $id = optional_param('id', SITEID, PARAM_INT);

  require_course_login($id);
  @header('Content-Type: text/html; charset=utf-8');


  //print_header_simple();
  //echo "<form id=\"audiorecorder\" method=\"post\" action=\"dialog.php\">";

  //echo "<input type=\"button\" onclick=\"onOK();\">";
  //echo "</form>";
  //print_footer();
 //$fullfilename = "{$CFG->dataroot}/{$COURSE->id}/users/{$USER->id}/audiorecorder_".strftime("%H%M%S",now()).".wav";
 $filename = 'audiorecorder_'.strftime("%H%M%S",time()).'.wav';
 //echo $filename;
 $uploads_dir = $COURSE->id."/users/".$USER->id;
 // create a folder for the audio files, if none exist.
 $path = make_upload_directory($uploads_dir,false);
?>
<div style="text-align:center;">
<applet
    CODE="com.softsynth.javasonics.recplay.RecorderUploadApplet"
    CODEBASE="codebase"
    ARCHIVE="JavaSonicsListenUp.jar"
    NAME="JavaSonicRecorderUploader"
    WIDTH="400" HEIGHT="120">

    <!-- Use a low sample rate that is good for voice. -->
    <param name="frameRate" value="11025.0">
    <!-- Most microphones are monophonic so use 1 channel. -->
    <param name="numChannels" value="1">
    <!-- Set maximum message length to whatever you want. -->
    <param name="maxRecordTime" value="60.0">

	<!-- Specify URL and file to be played after upload. -->
  <param name="refreshURL" value="play_message.php?AudioFile=<?php echo $CFG->wwwroot.'/file.php/'.$uploads_dir.'/'.$filename; ?>">

	<!-- Specify name of file uploaded.
	     There are alternatives that allow dynamic naming. -->
  <param name="uploadFileName" value="<?php echo $filename; ?>">

	<!-- Server script to receive the multi-part form data. -->
  <param name="uploadURL" value="handle_upload_file.php?courseid=<?php echo $COURSE->id; ?>&userid=<?php echo $USER->id; ?>">
<?php


	// Pass username and password from server to Applet if required.
	if( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) )
    {
		$authUserName = $_SERVER['PHP_AUTH_USER'];
		echo "    <param name=\"userName\" value=\"$authUserName\">\n";

		$authPassword = $_SERVER['PHP_AUTH_PW'];
		echo "    <param name=\"password\" value=\"$authPassword\">\n";
	}
?>
</applet>
</div>
  
<?php
    //print_footer();
?>