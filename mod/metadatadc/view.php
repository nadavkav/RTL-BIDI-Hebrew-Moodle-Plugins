<script LANGUAGE="JavaScript">
<!--
        function openWindow(url) {
        window.open(url,"_blank","width=600,height=500,status=no,toolbar=yes,menubar=no,location=no,resizable=yes,directories=no,scrollbars=yes")
        }
//  End -->		
</script>
<script language="JavaScript">
<!--
function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}

function MM_findObj(n, d) { //v3.0
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document); return x;
}

function MM_jumpMenuGo(selName,targ,restore){ //v3.0
  var selObj = MM_findObj(selName); if (selObj) MM_jumpMenu(targ,selObj,restore);
}
//-->
</script>

<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of metadatadc
/// (Replace metadatadc with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");
	
	
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // metadatadc ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $metadatadc = get_record("metadatadc", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $metadatadc = get_record("metadatadc", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $metadatadc->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("metadatadc", $metadatadc->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "metadatadc", "view", "view.php?id=$cm->id", "$metadatadc->id");


			
/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strmetadatadcs = get_string("modulenameplural", "metadatadc");
    $strmetadatadc  = get_string("modulename", "metadatadc");

    print_header("$course->shortname: $metadatadc->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strmetadatadcs</a> -> $metadatadc->name", 
                  "", "", true, update_module_button($cm->id, $course->id, $strmetadatadc), 
                  navmenu($course, $cm));

/// Print the main part of the page
	print_simple_box_start('center', '', '', 5, 'generalbox', $module->name);
	

///If is adding, identifier is null -> redirect to update

if (($metadatadc->resource == NULL) || ($metadatadc->resource == 0) || ($metadatadc->resource == 'id')) {
$link = $CFG->wwwroot.'/course/mod.php?update='.$cm->id.'&amp;sesskey='.$USER->sesskey.'&amp;sr='.$cm->section;
$msg = get_string('msgredirect0', 'metadatalom');
redirect($link,$msg,'3');
} elseif ($metadatadc->identifier == NULL) {
$link = $CFG->wwwroot.'/course/mod.php?update='.$cm->id.'&amp;sesskey='.$USER->sesskey;
$msg = get_string('msgredirect1', 'metadatadc');
redirect($link,$msg,'0');	
} elseif (($metadatadc->type == 'newvalue') || ($metadatadc->format == 'newvalue') || ($metadatadc->language == 'newvalue') || ($metadatadc->audience == 'newvalue')) {
$link = $CFG->wwwroot.'/course/mod.php?update='.$cm->id.'&amp;sesskey='.$USER->sesskey;
$msg = get_string('msgredirect2', 'metadatadc');
redirect($link,$msg,'0');	
}
?>

<table cellpadding="5">
<tr valign="top">
    <td align="right" width="40%"><b><?php  print_string("name") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->name; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("resource","metadatadc") ?>:</b></td>
    <td>
        <?php  
			require_once('../../course/lib.php');
			$lines = get_array_of_activities($course->id); 
			foreach ($lines as $key => $line) {
			$modfname = get_string("modulename", "$line->mod");
			// resources or activities id-name and module name in course
			$lo[$key] = trim(strip_tags(urldecode($line->name))) . ' (' . $modfname . ')'; 
			}
			echo $lo[$metadatadc->resource];
		?>	
		<?php
		//get_field($table, $return, $field1, $value1, $field2='', $value2='', $field3='', $value3='')
		//$resource_name = get_field("resource", "name", "id", $metadatadc->resource, "course", $course->id);
		//echo $metadatadc->resource;
		//echo " - ";
		//echo $resource_name;
		?>
    </td>
</tr>
<tr valign="top">
    <td align="right">&nbsp;</td>
    <td>&nbsp;</td>
</tr>
<tr bgcolor="#CCCCCC">
    <td align="left"><font size="3"><b>Simple Dublin Core Metadata</b></font></td>
	<td align="right">
    <select name="lmenu" onChange="MM_jumpMenu('self',this,0)">
      <option value="view_qdc.php?id=<?php echo $cm->id ?>" selected>Qualified DC</option>
      <option value="view_qdclom.php?id=<?php echo $cm->id ?>">Qualified DC + LOM</option>	 
      <option value="view.php?id=<?php echo $cm->id ?>">Simple DC</option>	     	  	  
    </select>
    <input type="button" name="goto" value=" > " onClick="MM_jumpMenuGo('lmenu','self',0)">  	
	</td>	
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("title","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->title; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("creator","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->creator; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("subject","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->subject; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("description","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->description; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("publisher","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->publisher; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("contributor","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->contributor; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("date","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->date; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("type","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->type; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("format","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->format; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("identifier","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->identifier; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("source","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->source; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("language","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->language; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("relation","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->relation; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("rights","metadatadc") ?>:</b></td>
    <td>
        <?php  echo $metadatadc->rights; ?>
    </td>
</tr>
</table>


<?
    if (! (isteacher($course->id) or ($course->showreports and $USER->id == $user->id))) {
        echo "<br><hr>";
		print_string("Comment_student","metadatadc");
		echo "<br><hr>";
    }
	else {
	$genxml = get_string("genmetadataxml","metadatadc");
	$viewxml = get_string("viewmetadataxml","metadatadc");	
	$genrdf = get_string("genmetadatardf","metadatadc");
	$viewrdf = get_string("viewmetadatardf","metadatadc");	
	echo "<div align='center'>";
	echo "<input type='button' name='Gerarxml' value='$genxml' 
	onclick='javascript:openWindow(&quot;dc/sdc.php?loid=$metadatadc->id&amp;cid=$course->id&quot;)' />&nbsp;";	
	echo "<input type='button' name='Verxml' value='$viewxml' 
	onclick='javascript:openWindow(&quot;" . $CFG->wwwroot . "/file.php/" . $course->id . "/metadata/sdcmetadata_" . $course->id . "_" . $metadatadc->id . ".xml&quot;)' /><br />";
	echo "<input type='button' name='Gerarrdf' value='$genrdf' 
	onclick='javascript:openWindow(&quot;dc/sdcrdf.php?loid=$metadatadc->id&amp;cid=$course->id&quot;)' />&nbsp;";
	echo "<input type='button' name='Verrdf' value='$viewrdf' 
	onclick='javascript:openWindow(&quot;" . $CFG->wwwroot . "/file.php/" . $course->id . "/metadata/sdcrdfmetadata_" . $course->id . "_" . $metadatadc->id . ".rdf&quot;)' /></div>";
	}
    print_simple_box_end();
	
    $strlastmodified = get_string("lastmodified");
    echo "<center><p><font size=\"1\">$strlastmodified: ".userdate($metadatadc->timemodified)."</font></p></center>";
			
/// Finish the page
    print_footer($course);

?>
