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
<title><?php echo get_string('title', 'lineheight','',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/lineheight/lang/'); ?></title>

<script type="text/javascript">
//<![CDATA[

function Init() {

  var param = window.opener.passparam; //window.dialogArguments;
  if (param) {
      //document.getElementById("lineheight").value = param["lineheight"];
    var selectlist = document.getElementById("lineheight");
    for (i=0;i < selectlist.length;i++) {
      if (selectlist.options[i].value == param["lineheight"]) selectlist.options[i].selected = true;
    }
  }
  
  document.getElementById('lineheight').focus();
};

function onOK() {
/*
 var required = {
    "lineheight": "<?php get_string("mustenterlineheight", "lineheight",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/lineheight/lang/');?>",
  };
  for (var i in required) {
    var el = document.getElementById(i);
    if (!el.value) {
      alert(required[i]);
      el.focus();
      return false;
    }
  }
*/
  // pass data back to the calling window
  var param = new Object();
  var el = document.getElementById('lineheight');
  param['lineheight'] = el.options[el.selectedIndex].value;


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
<tr><td><table width="100%"><tr><td class="title" nowrap="nowrap"><h3><?php echo get_string("lineheight","lineheight",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/lineheight/lang/'); ?></h3></td></tr></table></td></tr>
<tr>
<td>

<?php

  echo '<div class="lineheight" style="margins:auto;text-align:center;">';
    echo '<form action="dialog.php" method="get">';
      echo get_string("chooselineheight","lineheight",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/lineheight/lang/');
      //echo '  <input name="lineheight" id="lineheight" size="5" value="'.$_GET['lineheight'].'">';
      echo '<select id="lineheight" name="lineheight" >';
        echo '<option value="1" id="1">1</option>';
        echo '<option value="1.5" id="2">1.5</option>';
        echo '<option value="2" id="3">2</option>';
        echo '<option value="2.5" id="4">2.5</option>';
      echo '</select>';
    echo '</form><br/>';

  echo '</div>';

?>

    </td>
  </tr>
<tr><td><hr width="100%" /></td></tr>
<tr>
  <td align="right">
    <button type="button" onclick="return cancel();"><?php echo get_string("cancel","lineheight",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/lineheight/lang/');?></button>
    <button type="button" onclick="return onOK();"><?php echo get_string("set","lineheight",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/lineheight/lang/');?></button>
  </td>
</tr>
</table>
</body>
</html>