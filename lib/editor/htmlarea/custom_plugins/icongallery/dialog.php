<?php // $Id: insert_table.php,v 1.4 2007/01/27 23:23:44 skodak Exp $
    require_once("../../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);

    @header('Content-Type: text/html; charset=utf-8');

    // Setup a link to a public folder "icongallerys" on the system's public course 1
    //echo symlink ("$CFG->datadir/1/icongalleries","$CFG->libdir/editor/htmlarea/custom_plugins/icongallery/galleries/custom");

    $translang = $CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/icongallery/lang/';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php print_string('inserticon',"icongallery",'',$translang); ?></title>
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
      window.ipreview.location.replace('preview.php?id='+ <?php print(id);?> +'&imageurl='+ param.f_url);
  }
*/
  //document.getElementById('objective').focus();
};

function attr(name, value) {
    if (!value || value == "") return "";
    return ' ' + name + '="' + value + '"';
}

function copy_image_and_finish(img,text,filename,courseid) {
  var http = new XMLHttpRequest();
  var url = "<?php echo "$CFG->wwwroot/lib/editor/htmlarea/custom_plugins/icongallery/copy_image_into_course.php"; ?>";
  var params = "filename="+filename+"&courseid="+courseid;
  http.open("GET", url+"?"+params, true);
  http.onreadystatechange = function() {//Call a function when the state changes.
    if(http.readyState == 4 && http.status == 200) {
      //alert(http.responseText);
      insert(img,text);
    }
  }
  http.send(null);

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
<tr><td><table width="100%"><tr><td class="title" nowrap="nowrap"><h3><?php echo get_string("chooseicongallery","icongallery",'',$translang); ?></h3></td></tr></table></td></tr>
<tr>
<td>

<?php
  // If this is the first time, copy all icons from this plugins's folder
  // Into a similar name folder under Moodledata/1 public area folder
  if (!is_dir("$CFG->dataroot/1/icongalleries")) {
    copy_directory( "$CFG->dirroot/lib/editor/htmlarea/custom_plugins/icongallery/galleries","$CFG->dataroot/1/icongalleries");
  }

  $iconsfolders = get_directory_list("$CFG->dataroot/1/icongalleries", '',true,true,false);
  foreach($iconsfolders as $folder) {
    $iconsfolders_list[$folder] = $folder;
  }

  echo '<div class="modulefilter" style="margins:auto;text-align:center;">';
    echo '<form action="dialog.php" method="get">';
      echo get_string("choosenewgallery","icongallery",'',$translang);
      choose_from_menu ($iconsfolders_list, "showiconfolder", "",get_string("choosegallery","icongallery",'',$translang), "self.location='dialog.php?showiconfolder='+document.getElementById('showiconfolder').options[document.getElementById('showiconfolder').selectedIndex].value;", "0", false,false,"0","showiconfolder");
    echo '</form><br/>';

  echo '</div>';

  if (empty($_GET['showiconfolder']) or $_GET['showiconfolder']=='') {
    $iconsfolder='default-icons';
  } else {
    $iconsfolder=$_GET['showiconfolder'];
  }

 // get all the images from the folder
    $directory = opendir("{$CFG->dataroot}/1/icongalleries/{$iconsfolder}");
    $imagelist = array();
    while (false !== ($file = readdir($directory))) {
        if ($file == "." || $file == "..") {
          continue;
        }
        // notice : function mime-content-type() is depricated in php 5.3
        // http://us3.php.net/manual/en/function.mime-content-type.php
        // this IF should change in the near future :-)
        //$filemime = mime_content_type("{$CFG->libdir}/editor/htmlarea/popups/icons/{$iconsfolder}/{$file}");
        if ( is_file("{$CFG->dataroot}/1/icongalleries/{$iconsfolder}/{$file}") and  preg_match("/(png|jpeg|jpg)/i",$file) ) {
          $imagelist[] = $iconsfolder."/".$file;
        }

    }
    closedir($directory);


  foreach ($imagelist as $image) {
    //echo "<img alt=\"$image\" class=\"icon\" src=\"{$CFG->wwwroot}/file.php/1/icongalleries/$image\" onclick=\"insert('{$CFG->wwwroot}/file.php/1/icongalleries/$image','$image')\" />";
    echo "<img alt=\"$image\" class=\"icon\" src=\"{$CFG->wwwroot}/file.php/1/icongalleries/$image\" onclick=\"copy_image_and_finish('{$CFG->wwwroot}/file.php/$id/icongalleries/$image','$image','$image','$id')\" />";
  }

?>

    </td>
  </tr>
<tr><td><table width="100%"><tr><td valign="middle" width="90%"><hr width="100%" /></td></tr></table></td></tr>
<tr><td align="right">
    <button type="button" onclick="return cancel();"><?php echo get_string("cancel","icongallery",'',$translang);?></button></td></tr>
</table>
</body>
</html>

<?php
// Thank you CodesTips, for the copy_directory function
//    http://codestips.com/php-copy-directory-from-source-to-destination/

function copy_directory( $source, $destination ) {
	if ( is_dir( $source ) ) {
		@mkdir( $destination );
		$directory = dir( $source );
		while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
			if ( $readdirectory == '.' || $readdirectory == '..' ) {
				continue;
			}
			$PathDir = $source . '/' . $readdirectory;
			if ( is_dir( $PathDir ) ) {
				copy_directory( $PathDir, $destination . '/' . $readdirectory );
				continue;
			}
			copy( $PathDir, $destination . '/' . $readdirectory );
		}

		$directory->close();
	}else {
		copy( $source, $destination );
	}
}

?>