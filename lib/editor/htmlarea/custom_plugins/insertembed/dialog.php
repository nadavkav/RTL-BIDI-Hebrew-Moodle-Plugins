<?php // $Id: insert_table.php,v 1.4 2007/01/27 23:23:44 skodak Exp $
    require("../../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);

    require_course_login($id);
    @header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title><?php echo get_string("title","insertembed",'',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/insertembed/lang/");?></title>

<script type="text/javascript">
//<![CDATA[

function Init() {

  document.getElementById('embedcode').focus();
};

function onOK() {
  var required = {
    "embedcode": "You should paste some EMBED code here, before we move on..."
  };
  for (var i in required) {
    var el = document.getElementById(i);
    if (!el.value) {
      alert(required[i]);
      el.focus();
      return false;
    }
  }
  var fields = ["embedcode"];
  var param = new Object();
    try{
        for (var i in fields) {
          var id = fields[i];
          var el = document.getElementById(id);
          param[id] = el.value;
        }

        opener.nbWin.retFunc(param);
        window.close();
        return false;

    } catch(e) {
        opener.nbWin.retFunc(param);
        window.close();
        return false;

    }
};

function onCancel() {

  window.close();
  return false;
};
//[[>
</script>

<style type="text/css">
html, body {
margin: 2px;
background-color: rgb(212,208,200);
font-family: Tahoma, Verdana, sans-serif;
font-size: 11px;
}
button { width: 70px; }
.space { padding: 2px; }
.title { direction:rtl; text-align:center; font-size: 22px;}
form { margin-bottom: 0px; margin-top: 0px; }
</style>

</head>
<body onload="Init()">

<div class="title"><?php echo get_string("title","insertembed",'',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/insertembed/lang/");?></div>

<form action="" method="get">

<table width="100%" border="0" cellspacing="0" cellpadding="22">
  <tr>
    <td width="20%" valign="top">
        <textarea name="embedcode" id="embedcode" cols="40" rows="12"></textarea><br><hr>
        <button type="button" name="ok" onclick="return onOK();"><?php echo get_string("ok","insertembed",'',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/insertembed/lang/");?></button>
        <button type="button" name="cancel" onclick="return onCancel();"><?php echo get_string("cancel","insertembed",'',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/insertembed/lang/");?></button>
    </td>
    <td width="80%" align="right">
      <iframe src ="<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/insertembed/"; ?>choose-a-video.php" width="100%" height="600px">
        <p>Your browser does not support iframes.</p>
      </iframe>
    </td>
  </tr>
</table>

</form>

</body>
</html>
