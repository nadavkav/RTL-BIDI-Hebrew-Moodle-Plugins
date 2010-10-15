<?php // Code by Amr Hourani [a.hourani@gmail.com]

    require_once("../../config.php");
    require_once("lib.php");

    $id         = required_param('id', PARAM_INT);                 // Course Module ID


    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    require_course_login($course, false, $cm);

    if (!$skype = skype_get_skype($cm->instance)) {
        error("Course module is incorrect");
    }

    $strskype = get_string("modulename", "skype");
    $strskypes = get_string("modulenameplural", "skype");




/// Display the skype and possibly results

    add_to_log($course->id, "skype", "view", "view.php?id=$cm->id", $skype->id, $cm->id);

    print_header_simple(format_string($skype->name), "",
                 "<a href=\"index.php?id=$course->id\">$strskypes</a> -> ".format_string($skype->name), "", "", true,
                  update_module_button($cm->id, $course->id, $strskype), navmenu($course, $cm));

/// Print the form
    if(isadmin()) {
	 	$isadm="<br>
		<b>Notes:</b><br>1- Users with no registered skype IDs in their profiles will not be shown the list above.
		<br>2- Higher lever users (i.e: admins) will not follow a group, so they will see all users regardless of their group in this course.
		<hr>";
	}
	$isadm .= $skype->description."<hr>";
	

	echo "

	<script language=\"JavaScript\" type=\"text/javascript\">

	function loopSelected()
	{

		// var txtSelectedValuesObj = document.getElementById('txtSelectedValues');
		var selectedArray = new Array();
		var selObj = document.getElementById('calll');
		var i;
		var count = 0;
		for (i=0; i<selObj.options.length; i++) {
			if (selObj.options[i].selected) {
				selectedArray[count] = selObj.options[i].value;
				count++;
			}
		}
		//txtSelectedValuesObj.value = selectedArray;

		if(count == 0) {
			alert(\"".get_string("choosealert", "skype")."\");
		}
		else {
			window.open('call.php?id='+selectedArray, 'Skype',
		'scrollbars=no,menubar=no,resizable=no,toolbar=no,width=500,height=15');
		}


		}

	</script>

	<form name=our method=get>
	<table border=0 align=center width='90%'>
	<tr><td colspan=3>$isadm</td></tr>
	<tr>
	<td valign=top><br />
	<select name=calls id = calll multiple size=30 >
	".skype_show($skype, $USER, $cm, "form")."
	</select>
	</td>
	<td valign=top><br /><input type=button value = \"<< ".get_string("choose", "skype")."\" onClick=\"loopSelected();\"></td>
	<td valign=top>
	<!-- hide skypecasts coz skype is no more offering that! 
	<iframe style=\"border:0px\" frameborder=0 src=\"cast.php?id=$id\" width='600' height=\"400\">
	-->
	";
	include "note.html";
	echo"
	<br><br><font size=-2>Note: Skypecasts is no more available by skype.</font></td></tr>

	</table>
	</form>
	";

    print_footer($course);


?>
