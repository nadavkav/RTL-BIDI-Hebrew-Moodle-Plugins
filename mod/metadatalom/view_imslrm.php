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
?>

<table cellpadding="5">
<tr valign="top">
    <td align="right" width="40%"><b><?php  print_string("name") ?>:</b></td>
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
    </td>
</tr>
<tr valign="top">
    <td align="left">&nbsp;</td>
    <td>&nbsp;</td>
</tr>
<tr bgcolor="#FF6600">
    <td align="left"><font size="3"><b>IMS-LRM (Learning Resource Meta-data to IEEE-LOM)</b></font></td>
	<td align="right">
    <select name="lmenu" onChange="MM_jumpMenu('self',this,0)">
      <option value="view_lom.php?id=<?php echo $cm->id ?>" selected>Complete LOM</option> 
      <option value="view.php?id=<?php echo $cm->id ?>">Simple LOM</option>
      <option value="view_imslrm.php?id=<?php echo $cm->id ?>">IMS-LRM to LOM</option>	   	  	  
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
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("General_Identifier_Catalog","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Identifier_Catalog; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("General_Identifier_Entry","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Identifier_Entry; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Title","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Title; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Language","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Language; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Description","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Description; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Keyword","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Keyword; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Coverage","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Coverage; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_Structure","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->General_Structure; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("General_AggregationLevel","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->GeneralAggregationLevel; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("LifeCycle","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("LifeCycle_Version","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->LifeCycle_Version; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("LifeCycle_Status","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->LifeCycle_Status; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("LifeCycle_Contribute","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("LifeCycle_Contribute_Role","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->LifeCycle_Contribute_Role; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("LifeCycle_Contribute_Entity","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->LifeCycle_Contribute_Entity; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("LifeCycle_Contribute_Date","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->LifeCycle_Contribute_Date; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("MetaMetadata","metadatalom") ?>:</b></td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("MetaMetadata_Identifier","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("MetaMetadata_Identifier_Catalog","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->MetaMetadata_Identifier_Catalog; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("MetaMetadata_Identifier_Entry","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->MetaMetadata_Identifier_Entry; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("MetaMetadata_Contribute","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("MetaMetadata_Contribute_Role","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->MetaMetadata_Contribute_Role; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("MetaMetadata_Contribute_Entity","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->MetaMetadata_Contribute_Entity; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("MetaMetadata_Contribute_Date","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->MetaMetadata_Contribute_Date; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("MetaMetadata_MetadataScheme","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->MetaMetadata_MetadataScheme; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("MetaMetadata_Language","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->MetaMetadata_Language; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Technical","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_Format","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Format; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_Size","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Size; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_Location","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Location; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("Technical_Requirement","metadatalom") ?></b></td>
</tr>
<tr>
    <td colspan="2"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Technical_OrComposite","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Technical_Requirement_Type","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Requirement_Type; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Technical_Requirement_Name","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Requirement_Name; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Technical_Requirement_MinimumVersion","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Requirement_MinimumVersion; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Technical_Requirement_MaximumVersion","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Requirement_MaximumVersion; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_InstalationRemarks","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_InstalationRemarks; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_OtherPlatformRequirements","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_OtherPlatformRequirements; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Technical_Duration","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Technical_Duration; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Educational","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_InteractivityType","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_InteractivityType; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_LearningResourceType","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_LearningResourceType; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_InteractivityLevel","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_InteractivityLevel; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_SemanticDensity","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_SemanticDensity; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_IntendedEndUserRole","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_IntendedEndUserRole; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Context","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_Context; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_TypicalAgeRange","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_TypicalAgeRange; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Difficulty","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_Difficulty; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_TypicalLearningTime","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_TypicalLearningTime; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Description","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_Description; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Educational_Language","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Educational_Language; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Rights","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Rights_Cost","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Rights_Cost; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Rights_CopyrightAndOtherRestrictions","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Rights_CopyrightAndOtherRestrictions; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Rights_Description","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Rights_Description; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Relation","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Relation_Kind","metadatalom") ?>:</b></td>
    <td align="left">
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
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Relation_Resource_Identifier_Catalog","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Relation_Resource_Identifier_Catalog; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Relation_Resource_Identifier_Entry","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Relation_Resource_Identifier_Entry; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Relation_Resource_Description","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Relation_Resource_Description; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Annotation","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Annotation_Entity","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Annotation_Entity; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Annotation_Date","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Annotation_Date; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Annotation_Description","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Annotation_Description; ?>
    </td>
</tr>
<tr bgcolor="#FF9900">
    <td colspan="2"><b><?php  print_string("Classification","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Classification_Purpose","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Classification_Purpose; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b><?php  print_string("Classification_TaxonPath","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Classification_TaxonPath_Source","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Classification_TaxonPath_Source; ?>
    </td>
</tr>
<tr>
    <td colspan="2"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Classification_TaxonPath_Taxon","metadatalom") ?>:</b></td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Classification_TaxonPath_Taxon_ID","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Classification_TaxonPath_Taxon_ID; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php  print_string("Classification_TaxonPath_Taxon_Entry","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Classification_TaxonPath_Taxon_Entry; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Classification_Description","metadatalom") ?>:</b></td>
    <td align="left">
        <?php  echo $metadatalom->Classification_Description; ?>
    </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string("Classification_Keyword","metadatalom") ?>:</b></td>
    <td align="left">
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
	onclick='javascript:openWindow(&quot;lom/imslrm.php?loid=$metadatalom->id&cid=$course->id&quot;)' />&nbsp;";
	echo "<input type='button' name='Verxml' value='$viewxml' 
	onclick='javascript:openWindow(&quot;" . $CFG->wwwroot . "/file.php/" . $course->id . "/metadata/imslrmmetadata_" . $course->id . "_" . $metadatalom->id . ".xml&quot;)' /><br />";
	}
    print_simple_box_end();
	
    $strlastmodified = get_string("lastmodified");
    echo "<center><p><font size=\"1\">$strlastmodified: ".userdate($metadatalom->timemodified)."</font></p></center>";
			
/// Finish the page
    print_footer($course);

?>