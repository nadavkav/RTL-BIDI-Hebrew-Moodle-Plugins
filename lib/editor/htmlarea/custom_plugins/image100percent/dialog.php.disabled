<?php
    require_once("../../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);

    @header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php get_string('title', 'cellwidth','',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/cellwidth/lang/'); ?></title>
<link rel="stylesheet" href="dialog.css" type="text/css" />

<script type="text/javascript">
//<![CDATA[

function Init() {
  //var param = window.dialogArguments;
  /*
  if (param) {
      var alt = param["f_url"].substring(param["f_url"].lastIndexOf('/') + 1);
      document.getElementById("f_url").value = param["f_url"];
      document.getElementById("f_alt").value = param["f_alt"] ? param["f_alt"] : alt;
      document.getElementById("f_border").value = parseInt(param["f_border"] || 0);
      window.ipreview.location.replace('preview.php?id='+ <?php echo $id;?> +'&imageurl='+ param.f_url);
  }
*/
  //document.getElementById('objective').focus();
};

function onOK() {

 var required = {
    "cellwidth": "<?php get_string("mustentercellwidth", "cellwidth",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/cellwidth/lang/');?>",
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
  var param = new Object();
  var el = document.getElementById('cellwidth');
  param['cellwidth'] = el.value;


  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

function cancel() {
  window.close();
  return false;
};
//]]>
</script>

</head>

<body onload="Init()">

<?php if (right_to_left() ) { ?>
<style>
body {
direction:rtl;
text-align:right;
}
</style>
<?php } ?>

<table class="dlg" cellpadding="0" cellspacing="2" width="100%">
<tr><td><table width="100%"><tr><td class="title" nowrap="nowrap"><h3><?php echo get_string("cellwidth","cellwidth",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/cellwidth/lang/'); ?></h3></td></tr></table></td></tr>
<tr>
<td>

<?php

  echo '<div class="cellwidth" style="margins:auto;text-align:center;">';
    echo '<form action="dialog.php" method="get">';
      echo get_string("choosewidth","cellwidth",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/cellwidth/lang/');
      echo '  <input name="cellwidth" id="cellwidth" size="5" value="'.$_GET['cellwidth'].'">';
    echo '</form><br/>';

  echo '</div>';

?>

    </td>
  </tr>
<tr><td><hr width="100%" /></td></tr>
<tr>
  <td align="right">
    <button type="button" onclick="return cancel();"><?php echo get_string("cancel","cellwidth",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/cellwidth/lang/');?></button>
    <button type="button" onclick="return onOK();"><?php echo get_string("set","cellwidth",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/cellwidth/lang/');?></button>
  </td>
</tr>
</table>
</body>
</html>