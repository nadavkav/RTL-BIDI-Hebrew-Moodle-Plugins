<?php

  require_once("../../../../../config.php");

  $courseid = optional_param('id', SITEID, PARAM_INT);
  $image = optional_param('image', SITEID, PARAM_TEXT);

  require_login($id);
  require_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $courseid));

  //$image = "http://groworganic.info/front-page-images/header-Farmhouse.jpg";
  $image = "http://pegasus2.weizmann.ac.il/nadavkav/file.php/114/animals.jpg";
  $pluginpath = "$CFG->wwwroot/lib/editor/htmlarea/custom_plugins/paint";
  date_default_timezone_set('UTC');
  $uniquekey = date('ymdHis',time());
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title><?php echo get_string("title","pagebackground",'',$pluginpath."/lang/");?></title>

    <!--
      Original script, if you want to look at the source, remove if live.
      <script type="text/javascript" src="/_script/pixlr.js"></script>
  -->
  <script type="text/javascript" src="<?php echo $pluginpath."/pixlr/_script/pixlr_minified.js.php?instanceid=$question->id"; ?>"></script>
  <script type="text/javascript">
      //Global setting edit these
      pixlr.settings.target = '<?php echo $pluginpath."/image-upload.php?courseid={$COURSE->id}"; ?>';
      //pixlr.settings.exit = '<?php echo $pluginpath."/pixlr/exit_modal.php?courseid={$COURSE->id}"; ?>';
      pixlr.settings.credentials = true;
      pixlr.settings.method = 'post';
  </script>

<script type="text/javascript">
//<![CDATA[
var preview_window = null;

function Init() {
/*
  var param = window.dialogArguments;
  if (param) {
      var alt = param["f_url"].substring(param["f_url"].lastIndexOf('/') + 1);
      document.getElementById("f_url").value = param["f_url"];
      document.getElementById("f_alt").value = param["f_alt"] ? param["f_alt"] : alt;
      document.getElementById("f_border").value = parseInt(param["f_border"] || 0);
      document.getElementById("f_align").value = param["f_align"];
      document.getElementById("f_vert").value = param["f_vert"] != -1 ? param["f_vert"] : 0;
      document.getElementById("f_horiz").value = param["f_horiz"] != -1 ? param["f_horiz"] : 0;
      document.getElementById("f_width").value = param["f_width"];
      document.getElementById("f_height").value = param["f_height"];
      window.ipreview.location.replace('preview.php?id='+ <?php print($courseid);?> +'&imageurl='+ param.f_url);
  }

  document.getElementById("f_url").focus();
*/
  //alert(opener.outparam['f_url']);
  //<?php echo str_replace('file.php','sendfile.php',$image); ?>

  pixlr.overlay.show({image: opener.outparam['f_url'], title:'<?php echo $USER->sesskey.$uniquekey; ?>',loc:'<?php echo substr(current_language(),0,2); ?>',method:'POST'});
};

function onOK() {
  var required = {
    "f_url": "<?php print_string("mustenterurl", "editor");?>",
    "f_alt": "<?php print_string("pleaseenteralt", "editor");?>"
  };
  for (var i in required) {
    var el = document.getElementById(i);
    if (!el.value) {
      alert(required[i]);
      el.focus();
      return false;
    }
  }
  // pass data back to the calling window
  var fields = ["f_url", "f_alt", "f_align", "f_border",
                "f_horiz", "f_vert","f_width","f_height"];
  var param = new Object();
  for (var i in fields) {
    var id = fields[i];
    var el = document.getElementById(id);
    param[id] = el.value;
  }
  if (preview_window) {
    preview_window.close();
  }

  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

function onCancel() {
  if (preview_window) {
    preview_window.close();
  }

  window.close();
  return false;
};

//]]>
</script>

</head>

<body onload="Init()">

<!--a href="javascript:pixlr.overlay.show({image:'<?php echo str_replace('file.php','sendfile.php',$image); ?>', title:'<?php echo $USER->sesskey.$uniquekey; ?>',loc:'<?php echo substr(current_language(),0,2); ?>',method:'POST'});">
<img id="editme" src="<?php if (isset($imgurl)) { echo $imgurl; } else { echo $image; } ?>" width="250" height="150" title="<?php echo str_replace('file.php','sendfile.php',$image); ?>" /></a-->

</body>
</html>
