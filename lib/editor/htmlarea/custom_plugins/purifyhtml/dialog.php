<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 8/6/11
 *
 * Description:
 *	Clear redundant HTML TAGs and code, which distorts the HTML code and the visual display of the content
 *  (Usually, when coping content from MS Word(tm) documents)
 */

  require("../../../../../config.php");

  $id = optional_param('id', SITEID, PARAM_INT);

  require_course_login($id);

  $langpath = $CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/purifyhtml/lang/';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title><?php echo get_string("title","purifyhtml",'',$langpath); ?></title>

<script type="text/javascript">
//<![CDATA[

function Init() {

  parent_object      = opener.HTMLArea._object;
  <?php if (empty($_POST['purifyhtml'])) {?>
  document.getElementById('purifyhtml').value = opener.htmlarea_body.innerHTML;
  document.getElementById('preview').innerHTML = opener.htmlarea_body.innerHTML;
  <?php } ?>
  document.getElementById('purifyhtml').focus();
};

function onOK() {

  var fields = ["purifyhtml"];
  var param = new Object();
  for (var i in fields) {
    var id = fields[i];
    var el = document.getElementById(id);
    param[id] = el.value;
  }

  opener.nbWin.retFunc(param);
  window.close();
  return false;
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
<?php if (right_to_left()) { echo "direction:rtl;text-align:right;"; } else { echo "direction:ltr;"; } ?>
}
button { width: 100px; }
.space { padding: 2px; }
.title { direction:rtl; text-align:center; font-size: 22px;}
.instructions { direction:rtl; text-align:center; font-size: 12px;}
form { margin-bottom: 0px; margin-top: 0px; }
</style>

</head>
<body onload="Init()">

<div class="title"><?php echo get_string("title","purifyhtml",'',$langpath); ?></div>
<div class="instructions"><?php echo get_string("instructions","purifyhtml",'',$langpath); ?></div>

<form action="dialog.php" method="post">

<table width="100%" border="0" cellspacing="0" cellpadding="12">
  <tr>
    <td width="40%" valign="top">
	  <?php echo get_string("code","purifyhtml",'',$langpath); ?>
    </td>
	<td width="60%" valign="top">
	  <?php echo get_string("preview","purifyhtml",'',$langpath); ?>
	</dt>
  </tr>
  <tr>
    <td width="40%" valign="top">
		<?php

		  if (!empty($_POST['purifyhtml']) ) {
			//$str = purify_html($_POST['purifyhtml']);
			$str = $_POST['purifyhtml'];

			//$str .= str_replace('class="\"MsoNormal\""','',$str);
			//$str .= str_replace('class="\&quot;MsoNormal\&quot;"','',$str);

			$CFG->enablehtmlpurifier = true;
			$str .= clean_text($_POST['purifyhtml']);
			$str .= str_replace("\\\\",'',$str);
			$str .= str_replace("\\\"",'',$str);
			//$str .= str_replace('"','',$str);
		  } else {
			$str = '';
		  }

		?>

        <textarea name="purifyhtml" id="purifyhtml" cols="40" rows="12" style="direction:ltr;"><?php echo $str; ?></textarea><br><hr>
        <button type="button" name="ok" onclick="return onOK();"><?php echo get_string("ok","purifyhtml",'',$langpath); ?></button>
        <button type="button" name="cancel" onclick="return onCancel();"><?php echo get_string("cancel","purifyhtml",'',$langpath); ?></button>
		<button type="submit" name="purify"><?php echo get_string("purify","purifyhtml",'',$langpath); ?></button>
    </td>
	<td width="60%" valign="top">
		<div id="preview"><?php echo $str; ?></div>
	</dt>
  </tr>
</table>

</form>

</body>
</html>
