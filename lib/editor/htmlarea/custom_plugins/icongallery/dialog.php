<?php // $Id: insert_table.php,v 1.4 2007/01/27 23:23:44 skodak Exp $
    require_once("../../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);

    @header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php print_string('inserticon', 'editor'); ?></title>
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
      window.ipreview.location.replace('preview.php?id='+ <?php print($course->id);?> +'&imageurl='+ param.f_url);
  }
*/
  //document.getElementById('objective').focus();
};

function attr(name, value) {
    if (!value || value == "") return "";
    return ' ' + name + '="' + value + '"';
}
function insert(img,text) {
    if (img) {
      var strImage = img;
      var strAlt = text;
      var imgString = "<img src=\"" + strImage +"\" alt=\"" + strAlt +"\" title=\"" + strAlt +"\" />";
    }
  // pass data back to the calling window

  opener.nbWin.retFunc(imgString);
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
<tr><td><table width="100%"><tr><td class="title" nowrap="nowrap"><h3><?php echo get_string("chooseicongallery","icongallery",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/icongallery/lang/'); ?></h3></td></tr></table></td></tr>
<tr>
<td>

<?php
  $iconsfolders = get_directory_list("$CFG->libdir/editor/htmlarea/custom_plugins/icongallery/galleries", '',true,true,false);
  foreach($iconsfolders as $folder) {
    $iconsfolders_list[$folder] = $folder;
  }

  echo '<div class="modulefilter" style="margins:auto;text-align:center;">';
    echo '<form action="dialog.php" method="get">';
      echo get_string("choosenewgallery","icongallery",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/icongallery/lang/');
      choose_from_menu ($iconsfolders_list, "showiconfolder", "",get_string("choosegallery","icongallery",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/icongallery/lang/'), "self.location='dialog.php?showiconfolder='+document.getElementById('showiconfolder').options[document.getElementById('showiconfolder').selectedIndex].value;", "0", false,false,"0","showiconfolder");

    echo '</form><br/>';

  echo '</div>';

  if (empty($_GET['showiconfolder']) or $_GET['showiconfolder']=='') {
    $iconsfolder='wiFun_png';
  } else {
    $iconsfolder=$_GET['showiconfolder'];
  }

 // get all the images from the folder
    $directory = opendir("{$CFG->libdir}/editor/htmlarea/custom_plugins/icongallery/galleries/{$iconsfolder}");
    $imagelist = array();
    while (false !== ($file = readdir($directory))) {
        if ($file == "." || $file == "..") {
          continue;
        }
        // notice : function mime-content-type() is depricated in php 5.3
        // http://us3.php.net/manual/en/function.mime-content-type.php
        // this IF should change in the near future :-)
        //$filemime = mime_content_type("{$CFG->libdir}/editor/htmlarea/popups/icons/{$iconsfolder}/{$file}");
        if ( is_file("{$CFG->libdir}/editor/htmlarea/custom_plugins/icongallery/galleries/{$iconsfolder}/{$file}") and  preg_match("/(png|jpeg|jpg)/i",$file) ) {
          $imagelist[] = $iconsfolder."/".$file;
        }

    }
    closedir($directory);


  foreach ($imagelist as $image) {
    echo "<img alt=\"$image\" class=\"icon\" src=\"{$CFG->wwwroot}/lib/editor/htmlarea/custom_plugins/icongallery/galleries/$image\" onclick=\"insert('{$CFG->wwwroot}/lib/editor/htmlarea/custom_plugins/icongallery/galleries/$image','$image')\" />";
  }

?>

    </td>
  </tr>
<tr><td><table width="100%"><tr><td valign="middle" width="90%"><hr width="100%" /></td></tr></table></td></tr>
<tr><td align="right">
    <button type="button" onclick="return cancel();"><?php echo get_string("cancel","icongallery",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/icongallery/lang/');?></button></td></tr>
</table>
</body>
</html>