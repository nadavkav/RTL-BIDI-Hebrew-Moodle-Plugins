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

/// This page prints a particular instance of metadatalom
/// (Replace metadatalom with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // metadatalom ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $metadatalom = get_record("metadatalom", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $metadatalom = get_record("metadatalom", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $metadatalom->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("metadatalom", $metadatalom->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "metadatalom", "view", "view.php?id=$cm->id", "$metadatalom->id");

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strmetadataloms = get_string("modulenameplural", "metadatalom");
    $strmetadatalom  = get_string("modulename", "metadatalom");

    print_header("$course->shortname: $metadatalom->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strmetadataloms</a> -> $metadatalom->name", 
                  "", "", true, update_module_button($cm->id, $course->id, $strmetadatalom), 
                  navmenu($course, $cm));

/// Print the main part of the page

	print_simple_box_start('center', '', '', 5, 'generalbox', $module->name);


///If is adding, identifier is null -> redirect to update

if (($metadatalom->resource == NULL) || ($metadatalom->resource == 0) || ($metadatalom->resource == 'id')) {
$link = $CFG->wwwroot.'/course/mod.php?update='.$cm->id.'&amp;sesskey='.$USER->sesskey.'&amp;sr='.$cm->section;
$msg = get_string('msgredirect0', 'metadatalom');
redirect($link,$msg,'3');
} elseif ($metadatalom->General_Identifier_Entry == NULL) {
$link = $CFG->wwwroot.'/course/mod.php?update='.$cm->id.'&amp;sesskey='.$USER->sesskey.'&amp;sr='.$cm->section;
$msg = get_string('msgredirect1', 'metadatalom');
redirect($link,$msg,'3');
} elseif (($metadatalom->General_Identifier_Catalog == 'newvalue') || ($metadatalom->General_Language == 'newvalue') || ($metadatalom->General_Structure == 'newvalue') || ($metadatalom->General_AggregationLevel == 'newvalue') || ($metadatalom->LifeCycle_Status == 'newvalue') || ($metadatalom->LifeCycle_Contribute_Role == "newvalue") || ($form->MetaMetadata_Identifier_Catalog == 'newvalue') || ($form->MetaMetadata_Contribute_Role == 'newvalue') || ($form->MetaMetadata_Language == 'newvalue') || ($metadatalom->Technical_Format == 'newvalue') || ($metadatalom->Technical_Requirement_Type == 'newvalue') || ($metadatalom->Technical_Requirement_Name == 'newvalue') || ($metadatalom->Educational_InteractivityType == 'newvalue') || ($metadatalom->Educational_LearningResourceType == 'newvalue') || ($metadatalom->Educational_InteractivityLevel == 'newvalue') || ($metadatalom->Educational_SemanticDensity == 'newvalue') || ($metadatalom->Educational_IntendedEndUserRole == 'newvalue') || ($metadatalom->Educational_Context == 'newvalue') || ($metadatalom->Educational_Difficulty == 'newvalue') || ($metadatalom->Educational_Language == 'newvalue') || ($metadatalom->Relation_Kind == 'newvalue') || ($metadatalom->Relation_Resource_Identifier_Catalog == 'newvalue') || ($metadatalom->Classification_Purpose == 'newvalue') || ($metadatalom->Classification_TaxonPath_Source == 'newvalue')) {
$link = $CFG->wwwroot.'/course/mod.php?update='.$cm->id.'&amp;sesskey='.$USER->sesskey.'&amp;sr='.$cm->section;
$msg = get_string('msgredirect2', 'metadatalom');
redirect($link,$msg,'3');
}
?>
<table cellpadding="5">
<tr valign="top">
    <td align="right" width="40%"><b><?php  print_string("name","metadatalom") ?>:</b></td>
    <td>
        <?php  echo $metadatalom->name; ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("resource","metadatalom") ?>:</b></td>
    <td>
        <?php
			require_once('../../course/lib.php');
			$lines = get_array_of_activities($course->id); 
			foreach ($lines as $key => $line) {
			$modfname = get_string("modulename", "$line->mod");
			// resources or activities id-name and module name in course
			$lo[$key] = trim(strip_tags(urldecode($line->name))) . ' (' . $modfname . ')'; 
			}
			echo $lo[$metadatalom->resource];
			//function choose_from_menu ($options, $name, $selected='', $nothing='choose', $script='', $nothingvalue='0', $return=false, $disabled=false, $tabindex=0)
			//choose_from_menu($lo, "resource", $metadatalom->resource, 'choose', '', '0', $return=false, $disabled=true, $tabindex=0);
		?>	


        <?php  
		//get_field($table, $return, $field1, $value1, $field2='', $value2='', $field3='', $value3='')
		//$resource_name = get_field("resource", "name", "id", $metadatalom->resource, "course", $course->id);
		//echo $metadatalom->resource;
		//echo " - ";
		//echo $resource_name;
		?>
    </td>
</tr>
<tr valign="top">
    <td align="left">&nbsp;</td>
    <td>&nbsp;</td>
</tr>
<tr bgcolor="#FF6600">
    <td align="left"><font size="3"><b>Simple LOM (Learning Object Metadata)</b></font></td>
	<td align="right">
    <select name="lmenu" onChange="MM_jumpMenu('self',this,0)">
      <option value="view_lom.php?id=<?php echo $cm->id ?>" selected>Complete LOM</option>
      <option value="view_imslrm.php?id=<?php echo $cm->id ?>">IMS-LRM to LOM</option>	 
      <option value="view.php?id=<?php echo $cm->id ?>">Simple LOM</option>	     	  	  
    </select>
    <input type="button" name="goto" value=" > " onClick="MM_jumpMenuGo('lmenu','self',0)">  	
	</td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("General","metadatalom") ?></b></td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("General_Identifier","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("General_Identifier_Entry","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->General_Identifier_Entry; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Title","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->General_Title; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Language","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->General_Language; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Description","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->General_Description; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Keyword","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->General_Keyword; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Coverage","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->General_Coverage; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("LifeCycle","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("LifeCycle_Version","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->LifeCycle_Version; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("LifeCycle_Status","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->LifeCycle_Status; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("LifeCycle_Contribute","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("LifeCycle_Contribute_Entity","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->LifeCycle_Contribute_Entity; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("MetaMetadata","metadatalom") ?>:</b></td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("MetaMetadata_Identifier","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("MetaMetadata_Identifier_Entry","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->MetaMetadata_Identifier_Entry; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("MetaMetadata_MetadataScheme","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->MetaMetadata_MetadataScheme; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("MetaMetadata_Language","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->MetaMetadata_Language; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Technical","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_Format","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Technical_Format; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_Location","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Technical_Location; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_InstalationRemarks","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Technical_InstalationRemarks; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Educational","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_InteractivityType","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_InteractivityType; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_LearningResourceType","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_LearningResourceType; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_InteractivityLevel","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_InteractivityLevel; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_SemanticDensity","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_SemanticDensity; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_IntendedEndUserRole","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_IntendedEndUserRole; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Context","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_Context; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_TypicalAgeRange","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_TypicalAgeRange; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Difficulty","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_Difficulty; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_TypicalLearningTime","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_TypicalLearningTime; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Description","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_Description; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Language","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Educational_Language; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Rights","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Rights_Cost","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Rights_Cost; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Rights_CopyrightAndOtherRestrictions","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Rights_CopyrightAndOtherRestrictions; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Rights_Description","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Rights_Description; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Relation","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Relation_Kind","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Relation_Kind; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("Relation_Resource","metadatalom") ?></b></td>
</tr>
<tr>
    <td colspan="2"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Relation_Resource_Identifier","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Relation_Resource_Identifier_Entry","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Relation_Resource_Identifier_Entry; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Relation_Resource_Description","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Relation_Resource_Description; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Annotation","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Annotation_Entity","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Annotation_Entity; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Annotation_Date","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Annotation_Date; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Annotation_Description","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Annotation_Description; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Classification","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Classification_Purpose","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Classification_Purpose; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("Classification_TaxonPath","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Classification_TaxonPath_Source","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Classification_TaxonPath_Source; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Classification_Description","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Classification_Description; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Classification_Keyword","metadatalom") ?>:</b></td>
    <td align="right">
        <?php  echo $metadatalom->Classification_Keyword; ?>
    </td>
</tr>
<tr valign="top">
<td colspan="2"><hr /></td>
</tr>
</table>
<?
    if (! (isteacher($course->id) or ($course->showreports and $USER->id == $user->id))) {
        echo "<br><hr>";
		print_string("Comment_student","metadatalom");
		echo "<br><hr>";
    }
	else {
	$genxml = get_string("genmetadataxml","metadatalom");
	$viewxml = get_string("viewmetadataxml","metadatalom");
	echo "<div align='center'>";
	echo "<input type='button' name='Gerarxml' value='$genxml' 
	onclick='javascript:openWindow(&quot;lom/slom.php?loid=$metadatalom->id&cid=$course->id&quot;)' />&nbsp;";
	echo "<input type='button' name='Verxml' value='$viewxml' 
	onclick='javascript:openWindow(&quot;" . $CFG->wwwroot . "/file.php/" . $course->id . "/metadata/lommetadata_" . $course->id . "_" . $metadatalom->id . ".xml&quot;)' /><br />";
	//echo "<input type='button' name='Verxml2html' value='$viewxml' 
	//onclick='javascript:openWindow(&quot;" . $CFG->wwwroot . "/mod/metadatalom/viewxml.php&quot;)' /><br />";	
	}
    print_simple_box_end();
	
    $strlastmodified = get_string("lastmodified");
    echo "<center><p><font size=\"1\">$strlastmodified: ".userdate($metadatalom->timemodified)."</font></p></center>";
			
/// Finish the page
    print_footer($course);

?>