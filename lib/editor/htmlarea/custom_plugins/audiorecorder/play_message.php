<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/14/11 Time: 12:10 AM
 *
 * Description:
 *
 */

require_once("../../../../../config.php");

?>
<body onload="closeMe();">
  <div style="text-align:center;">
    <?php echo get_string('saveedsuccessfully','audiorecorder','',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/audiorecorder/lang/"); ?>

    <br/><input type="button" onclick="onOK();" value="<?php echo get_string('closewindow','audiorecorder','',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/audiorecorder/lang/"); ?>">
  </div>
</body>

 <script type="text/javascript">
  function onOK() {

  var param = new Object();
  //param["audiofile"] = '<a href="<?php echo $_GET['AudioFile'].'.mp3' ?>"><?php echo get_string('clicktoplay','audiorecorder','',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/audiorecorder/lang/"); ?></a>';
  // see remarked code at the end of the file (nadavkav)

  <?php if (file_exists('/usr/bin/ffmpeg')) {
      $audiofile = $_GET['AudioFile'].'.mp3';
  }else{
      $audiofile = $_GET['AudioFile'];
  }  ?>

  param["audioplayer"] = <?php echo "'<a target=\"_new\" href=\"".$audiofile."\">".get_string('clicktoplay','audiorecorder','',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/audiorecorder/lang/')."</a>';"; ?>



  parent.opener.nbWin.retFunc(param);
  parent.window.close();
  return false;
};


var howLong = 5000;
var t = null;
function closeMe(){
  t = setTimeout("onOK()",howLong);
}

</script>

<?php
/* $timecode = time(); // some unique code for the mp3flash player, in case several are in the page.

    echo "<span id=\"mp2player_$timecode;\"></span><script type=\"text/javascript\">
var FO = { movie:\"{$CFG->wwwroot}/lib/editor/htmlarea/custom_plugins/audiorecorder/mp3player.swf?src=".$_GET['AudioFile'].".mp3\",
width:\"90\", height:\"15\", majorversion:\"6\", build:\"40\", flashvars:\"bgColour=000000&btnColour=ffffff&btnBorderColour=cccccc&iconColour=000000&iconOverColour=00cc00&trackColour=cccccc&handleColour=ffffff&loaderColour=ffffff&waitForPlay=yes\", quality: \"high\" };
UFO.create(FO, \"mp3player_$timecode\");
<\/script>";

 */
?>
