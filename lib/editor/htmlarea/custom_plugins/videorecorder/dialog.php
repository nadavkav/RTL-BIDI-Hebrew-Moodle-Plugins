<?php 
/**
 * Created by Nadavk Kavalerchik.
 * Email: nadavkav@gmail.com
 * Date: 7/6/2011
 *
 * Description:
 * 	Record Video and save it (MP4 file format with h.264 Video track and AAC audio track) inside the course (or user's folder)
 *
 */

  require_once("../../../../../config.php");

  $id = optional_param('id', SITEID, PARAM_INT);

  require_course_login($id);
  @header('Content-Type: text/html; charset=utf-8');

  $langpath = $CFG->dirroot."/lib/editor/htmlarea/custom_plugins/videorecorder/lang/";

  //print_header_simple();
  //print_footer();
  $filename = 'videorecorder_'.strftime("%H%M%S",time()).'.mp4';
  $uploads_dir = $COURSE->id."/users/".$USER->id;
  $url = $CFG->wwwroot.'/file.php/'.$uploads_dir.'/mp4/'.$filename;

  // create a folder for the audio files, if none exist.
  $path = make_upload_directory($uploads_dir,false);
?>
<html>
<head>
  <title><?php echo get_string('title','videorecorder','',$langpath); ?></title>

<SCRIPT language="JavaScript">
  function vision()	
  {
    document.getElementById("loading").style.visibility="hidden";
    document.getElementById("loaded").style.visibility="visible";
  }

  function setStatus(num, str)	{
    // Handle status changes
    //**********************
    // Status codes:
    // StartUpload = 0;
    // UploadDone = 1;
    // StartRecord = 2;
    // StartPlay = 3;
    // PauseSet = 4;
    // Stopped = 5;
    document.Gui_RP.Status.value = str;
  }



  function setTimer(str)	{
    document.Gui_RP.Timer.value = str;
  }

  function RECORD_RP()	{
    document.VimasVideoApplet.RECORD_VIDEO();
  }


  function PLAYBACK_RP()	{
    document.VimasVideoApplet.PLAY_VIDEO();
  }

  function PAUSE_RP()	{
    document.VimasVideoApplet.PAUSE_VIDEO();
  }

  function STOP_RP()	{
    document.VimasVideoApplet.STOP_VIDEO();
  }

  function UPLOAD_RP()	{
    document.VimasVideoApplet.UPLOAD_VIDEO(String(document.Gui_RP.FileName.value));
  }

</SCRIPT>

<script type="text/javascript">
  function onOK() {

  var param = new Object();

param["videoplayer"] = <?php echo "'<span class=\"mediaplugin mediaplugin_qt\"> \
<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" \
  codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\" width=\"182\" height=\"165\"> \
 <param name=\"pluginspage\" value=\"http://www.apple.com/quicktime/download/\" /> \
 <param name=\"src\" value=\"".$url."\" /> \
 <param name=\"controller\" value=\"true\" /> \
 <param name=\"loop\" value=\"true\" /> \
 <param name=\"autoplay\" value=\"true\" /> \
 <param name=\"autostart\" value=\"true\" /> \
 <param name=\"scale\" value=\"aspect\" /> \
  <object data=\"".$url."\" type=\"video/mp4\" width=\"182\" height=\"165\"> \
   <param name=\"src\" value=\"".$url."\" /> \
   <param name=\"pluginurl\" value=\"http://www.apple.com/quicktime/download/\" /> \
   <param name=\"controller\" value=\"true\" /> \
   <param name=\"loop\" value=\"true\" /> \
   <param name=\"autoplay\" value=\"true\" /> \
   <param name=\"autostart\" value=\"true\" /> \
   <param name=\"scale\" value=\"aspect\" /> \
   </object> \
</object></span>';"; ?>

  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

</script>

</head>

<body onLoad="vision()" style="<?php if (right_to_left()) { echo "direction:rtl;"; } else { echo "direction:ltr;";} ?>">
<TABLE>
  <TR>
    <TD width="375">

      <SPAN ID="loading" style="visibility:visible">
	<div align="left" style="color:#000000;font-family: Verdana, Arial, Helvetica, sans-serif;font-size:14px">
	Loading Java applet...
	</div>
      </SPAN>

      <SPAN ID="loaded" style="visibility:hidden">
      <div align="center" style="color:#000000;font-family: Verdana, Arial, Helvetica, sans-serif;font-size:14px">
	<applet  
	  ID	   = "applet"
	  ARCHIVE  = "VideoApplet.jar"
	  codebase = "VideoApplet"
	  code     = "com.vimas.videoapplet.VimasVideoApplet.class"
	  name     = "VimasVideoApplet"
	  width    = "182"
	  height   = "165"
	  hspace   = "0"
	  vspace   = "0"
	  align    = "middle">

	    <PARAM NAME = "left" 		value="100">
	    <PARAM NAME = "top" 		value="200">
	    <PARAM NAME = "Registration"	VALUE = "demo">
	    <PARAM NAME = "LocalizationFile" 	VALUE = "<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/videorecorder/VideoApplet/Localization/localization.xml"; ?>">
	    <PARAM NAME = "ServerScript"	VALUE = "<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/videorecorder/VideoApplet/retrive_v.php";?>">
	    <PARAM NAME = "VideoServerFolder"	VALUE = "<?php echo $uploads_dir;?>">
	    <PARAM NAME = "TimeLimit"		VALUE = "30">
	    <PARAM NAME = "BlockSize"		VALUE = "10240">
	    <PARAM NAME = "UserServerFolder"	VALUE = "mp4">

	    <PARAM NAME = "LowQuality" 		VALUE = "96,24">
	    <PARAM NAME = "NormalQuality" 	VALUE = "160,32">
	    <PARAM NAME = "HighQuality" 	VALUE = "256,48">

	    <PARAM NAME = "FrameSize"		VALUE = "small">
	    <PARAM NAME = "interface"		VALUE = "compact">

	    <PARAM NAME = "UserPostVariables"	VALUE = "name,country">
	    <PARAM NAME = "name"		VALUE = "Vimas Video Recorder">
	    <PARAM NAME = "country"		VALUE = "Israel">
	</applet>
      </div>

      </SPAN>
      <FORM name="Gui_RP" onsubmit="event.returnValue=false;return false;">
	<TABLE CELLSPACING=1 style="color:#000000;font-family:Tahoma;font-size:10pt" border="0">
	  <TR>
	    <TD width="70"><?php echo get_string('recorder','videorecorder','',$langpath); ?></TD>
	    <TD width="70"><input TYPE=button VALUE="<?php echo get_string('record','videorecorder','',$langpath); ?>" STYLE="width:70;font-family:Tahoma;font-size:10pt" onClick="RECORD_RP();"></TD>
	    <TD width="75"><input TYPE=button VALUE="<?php echo get_string('stop','videorecorder','',$langpath); ?>" STYLE="width:75;font-family:Tahoma;font-size:10pt" onClick="STOP_RP();"></TD>
	    <TD width="70"><input TYPE=button VALUE="<?php echo get_string('play','videorecorder','',$langpath); ?>" STYLE="width:70;font-family:Tahoma;font-size:10pt" onClick="PLAYBACK_RP();"></TD>
	    <TD width="75"><input TYPE=button VALUE="<?php echo get_string('pause','videorecorder','',$langpath); ?>" STYLE="width:75;font-family:Tahoma;font-size:10pt" onClick="PAUSE_RP();"></TD>
	  </TR>
	  <TR>
	    <TD COLSPAN="4"><?php echo get_string('whendone','videorecorder','',$langpath); ?></TD>
	    <!--TD ALIGN=right  width="130"--><input TYPE=hidden NAME="FileName" VALUE="<?php echo $filename; ?>" SIZE=20 MAXLENGTH=16 style="width:150;font-family:Tahoma;font-size:10pt"><!--/TD-->
	    <TD width="75" COLSPAN=2><input TYPE=button VALUE="<?php echo get_string('send','videorecorder','',$langpath); ?>" STYLE="width:75" onClick="UPLOAD_RP();"></TD>
	  </TR>
	  <TR>
	    <TD><?php echo get_string('status','videorecorder','',$langpath); ?></TD>
	    <TD COLSPAN=3><input TYPE=text NAME="Status" VALUE="" SIZE=34 MAXLENGTH=60 style="width:240;font-family:Tahoma;font-size:10pt"></TD>
	    <TD><input TYPE=text NAME="Timer" SIZE=7 style="width:75;font-family:Tahoma;font-size:10pt"></TD>
	  </TR>
	</TABLE>
      </FORM>
    </TD>
  </TR>
</TABLE>

<?php echo get_string('saveedsuccessfully','videorecorder','',$langpath); ?><br/>
<input type="button" onclick="onOK();" value="<?php echo get_string('closewindow','videorecorder','',$langpath); ?>">
</body>
</html>
