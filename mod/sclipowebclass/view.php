<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Prints a particular instance of sclipo
 *
 * You can have a rather longer description of the file as well,gdv
 * if you like, and it can span multiple lines.
 *
 * @package   mod-sclipo
 * @copyright 2009 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// (Replace sclipo with the name of your module and remove this line)

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot.'/calendar/lib.php');

$cssfile=$CFG->wwwroot;
$cssfile.="/mod/sclipowebclass/css/main.css";
$formfile=$CFG->wwwroot."/mod/sclipowebclass/css/forms.css";
$scimg=$CFG->wwwroot."/mod/sclipowebclass/scimg/";
$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // sclipo instance ID

if ($id) {
    if (! $cm = get_coursemodule_from_id('sclipowebclass', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

    if (! $sclipo = get_record("sclipowebclass", "id", $cm->instance)) {
         error("Course module is incorrect");
    }
	$courseid = $course->id;

} else if ($a) {
    if (! $sclipo = get_record('sclipowebclass', array('id' => $a))) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', array('id' => $sclipo->course))) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('sclipowebclass', $sclipo->id, $course->id)) {
        error('Course Module ID was incorrect');
    }
	$courseid = $course->id;

} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

add_to_log($course->id, "sclipowebclass", "view", "view.php?id=$cm->id", "$sclipo->id");

$module->name = "sclipowebclass";

require_login($course->id); // needed to setup proper $COURSE
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('mod/sclipowebclass:view', $context);

//$streditinga = get_string("editinga", "moodle", $fullmodulename);
$strmodulenameplural = get_string("modulenameplural", $module->name);

if ($module->name == "label") {
    $focuscursor = "form.content";
} else {
        $focuscursor = "form.name";
}

$navlinks = array();
$navlinks[] = array('name' => $strmodulenameplural, 'link' => "$CFG->wwwroot/mod/$module->name/index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => $sclipo->name, 'link' => '', 'type' => 'action');
$navigation = build_navigation($navlinks);

print_header_simple("Sclipo Live Web Class", '', $navigation, $focuscursor, "", false);

print_simple_box_start('center', '', '', 5, 'generalbox', $module->name);

?>
<link media="screen" type="text/css" rel="stylesheet" href="<?php echo $cssfile; ?>">
</link>
<?php

// Work starts here
// Check login
require_once("sclipoapi.php");

$_SESSION["sclipo_id"] = isset($_SESSION["sclipo_id"]) ? $_SESSION["sclipo_id"] : 0;
$ret = sclipo_checkLogin($_SESSION["sclipo_id"], $USER->username);
if ($ret == 0)
	$not_logged_in = 1;
else
	$not_logged_in = 0;

$webclass = sclipo_getWebClassInfo($_SESSION["sclipo_id"], $USER->username, $sclipo->reference);
$webclass["description"] = nl2br($webclass["description"]);

if (! $teacher_info = get_record("user", "id", $sclipo->teacherinfo) ) {
        error("Could not retrieve teacher information");
}

if ($not_logged_in && ($USER->id == $teacher_info->id))
	$teacher_not_logged_in = 1;
else
	$teacher_not_logged_in = 0;

/* // EDIT : We allow students to view class info without being logged in
if ($ret == 0) {

    $form->course     = $course->id;
    $form->module     = $module->id;
    $form->modulename = $module->name;
    $form->instance   = "";
    $form->coursemodule = "";
    $form->mode       = "add";
    $form->sesskey    = !empty($USER->id) ? $USER->sesskey : '';

	// Not logged in
	$redirectpage = "view.php?id=$id";
	$form->modulename = "sclipo";
	$form->sesskey = $USER->sesskey;
	include("join.php");
	print_simple_box_end();
	print_footer($course);
	exit();
}
*/

// Retrieve web class information
if ($sclipo->teacherid == sclipo_getUserIDFromSession($_SESSION["sclipo_id"], $USER->username))
	$is_teacher = true;
else
	$is_teacher = false;

	/*
if ($not_logged_in)
	$timezone = "America/New York";
else
	$timezone = sclipo_gettimezone($_SESSION["sclipo_id"], $USER->username);*/

	$timezone = sclipo_getteachertimezone($sclipo->reference);

	$view = optional_param('view', 'upcoming', PARAM_ALPHA);
    $day  = optional_param('cal_d', 0, PARAM_INT);
    $mon  = optional_param('cal_m', 0, PARAM_INT);
    $yr   = optional_param('cal_y', 0, PARAM_INT);


 // Initialize the session variables
    calendar_session_vars();

    //add_to_log($course->id, "course", "view", "view.php?id=$course->id", "$course->id");
    $now = usergetdate(time());
    $pagetitle = '';

    $nav = calendar_get_link_tag(get_string('calendar', 'calendar'), CALENDAR_URL.'view.php?view=upcoming&amp;course='.$course->id.'&amp;', $now['mday'], $now['mon'], $now['year']);


    if(!checkdate($mon, $day, $yr)) {
        $day = intval($now['mday']);
        $mon = intval($now['mon']);
        $yr = intval($now['year']);
    }
    $time = make_timestamp($yr, $mon, $day);
	switch($view) {
        case 'day':
            $nav .= ' -> '.userdate($time, get_string('strftimedate'));
            $pagetitle = get_string('dayview', 'calendar');
        break;
        case 'month':
            $nav .= ' -> '.userdate($time, get_string('strftimemonthyear'));
            $pagetitle = get_string('detailedmonthview', 'calendar');
        break;
        case 'upcoming':
            $pagetitle = get_string('upcomingevents', 'calendar');
        break;
    }

    // If a course has been supplied in the URL, change the filters to show that one
    if (!empty($course->id)) {
        if ($course = get_record('course', 'id', $course->id)) {
            if ($course->id == SITEID) {
                // If coming from the home page, show all courses
                $SESSION->cal_courses_shown = calendar_get_default_courses(true);
                calendar_set_referring_course(0);

            } else {
                // Otherwise show just this one
                $SESSION->cal_courses_shown = $course->id;
                calendar_set_referring_course($SESSION->cal_courses_shown);
            }
        }
    } else {
        $course = null;
    }

    if (empty($USER->id) or isguest()) {
        $defaultcourses = calendar_get_default_courses();
        calendar_set_filters($courses, $groups, $users, $defaultcourses, $defaultcourses);

    } else {
        calendar_set_filters($courses, $groups, $users);
    }

    // Let's see if we are supposed to provide a referring course link
    // but NOT for the "main page" course
    if ($SESSION->cal_course_referer != SITEID &&
       ($shortname = get_field('course', 'shortname', 'id', $SESSION->cal_course_referer)) !== false) {
        // If we know about the referring course, show a return link and ALSO require login!
        require_login();
        $nav = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$SESSION->cal_course_referer.'">'.$shortname.'</a> -> '.$nav;
        if (empty($course)) {
            $course = get_record('course', 'id', $SESSION->cal_course_referer); // Useful to have around
        }
    }

    $strcalendar = get_string('calendar', 'calendar');
    $prefsbutton = calendar_preferences_button();

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }


    echo calendar_overlib_html();
     // Layout the whole page as three big columns.
    echo '<table id="calendar" style="height:100%; margin: auto;">';
    echo '<tr>';

    // START: Main column

    /// Print the main part of the pageecho $user;
 echo '<td class="maincalendar">';
    echo '<div class="heightcontainer">';


?>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/domready.js";?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/jquery.js";?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/rich_calendar.js";?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/rc_lang_en.js";?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/jquery.blockUI.js";?>"></script>
<link href="<?php echo $CFG->wwwroot."/mod/sclipowebclass/css/facebox.css"; ?>" media="screen" rel="stylesheet" type="text/css"/>
<script src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/facebox.js"; ?>" type="text/javascript"></script>
<script type="text/javascript">
function validateForm(form) {
		if (form.title.value == '') {
			alert("You need to fill in a title");
			return false;
		}
		if (form.description.value == '') {
			alert("You need to fill in a description");
			return false;
		}
		if (form.tags.value == '') {
			alert("You need to fill in some tags");
			return false;
		}

		return true;
}

function removecontent(ref, content_id)
{
	if (!confirm("Are you sure you want to delete the content?"))
		return;

	$.get("sclipoapi.php?do=removecontent&ref="+ref+"&cid="+content_id, function(data) {
		$("."+content_id).css("display", "none");
	});

	var num = $("#num-docs").val();
    num--;
	if (num == 0)
		$("#button-edit-doc").hide();
	$("#num-docs").val(num);
}

function doWait() {
	$.blockUI({ message: '<h1><img src="/scimg/ajax-loader.gif" /> Uploading ...</h1>' });
}

jQuery(document).ready(function($) {
  $('a[rel*=facebox]').facebox();
  //$('table a').facebox();

  <?php if (isset($_REQUEST["showadd"]) && $is_teacher) echo '$("#button-adddoc").click();'; ?>

})

var countDesc = "1000";
var countTags = "200";

function limiterDesc(e) {
	var tex = e.value;
	var len = tex.length;

	if (len > countDesc){
        tex = tex.substring(0,countDesc);
        e.value = tex;
        return false;
	}

	$(".limiter").html(countDesc-len);
}

function limiterTags(e) {
	var tex = e.value;
	var len = tex.length;
	if (len > countTags){
        tex = tex.substring(0,countDesc);
        e.value = tex;
        return false;
	}
}


</script>
<script type="text/javascript">
// Title: Tigra Calendar
// URL: http://www.softcomplex.com/products/tigra_calendar/
// Version: 3.3 (American date format)
// Date: 09/01/2005 (mm/dd/yyyy)
// Note: Permission given to use this script in ANY kind of applications if
//    header lines are left unchanged.
// Note: Script consists of two files: calendar?.js and calendar.html

// if two digit year input dates after this year considered 20 century.
var NUM_CENTYEAR = 30;
// is time input control required by default
var BUL_TIMECOMPONENT = false;
// are year scrolling buttons required by default
var BUL_YEARSCROLL = true;

var calendars = [];
var RE_NUM = /^\-?\d+$/;

function calendar2(obj_target) {

	// assigning methods
	this.gen_date = cal_gen_date2;
	this.gen_time = cal_gen_time2;
	this.gen_tsmp = cal_gen_tsmp2;
	this.prs_date = cal_prs_date2;
	this.prs_time = cal_prs_time2;
	this.prs_tsmp = cal_prs_tsmp2;
	this.popup    = cal_popup2;

	// validate input parameters
	if (!obj_target)
		return cal_error("Error calling the calendar: no target control specified");
	if (obj_target.value == null)
		return cal_error("Error calling the calendar: parameter specified is not valid target control");
	this.target = obj_target;
	this.time_comp = BUL_TIMECOMPONENT;
	this.year_scroll = BUL_YEARSCROLL;

	// register in global collections
	this.id = calendars.length;
	calendars[this.id] = this;
}

function cal_popup2 (str_datetime) {
	if (str_datetime) {
		this.dt_current = this.prs_tsmp(str_datetime);
	}
	else {
		this.dt_current = this.prs_tsmp(this.target.value);
		this.dt_selected = this.dt_current;
	}
	if (!this.dt_current) return;

	var obj_calwindow = window.open(
		'calendar.html?datetime=' + this.dt_current.valueOf()+ '&id=' + this.id,
		'Calendar', 'width=230,height='+(this.time_comp ? 315 : 250)+
		',status=no,resizable=no,top=200,left=200,dependent=yes,alwaysRaised=yes'
	);
	obj_calwindow.opener = window;
	obj_calwindow.focus();
}

// timestamp generating function
function cal_gen_tsmp2 (dt_datetime) {
	return(this.gen_date(dt_datetime) + ' ' + this.gen_time(dt_datetime));
}

// date generating function
function cal_gen_date2 (dt_datetime) {
	return (
		(dt_datetime.getMonth() < 9 ? '0' : '') + (dt_datetime.getMonth() + 1) + "/"
		+ (dt_datetime.getDate() < 10 ? '0' : '') + dt_datetime.getDate() + "/"
		+ dt_datetime.getFullYear()
	);
}
// time generating function
function cal_gen_time2 (dt_datetime) {
	return (
		(dt_datetime.getHours() < 10 ? '0' : '') + dt_datetime.getHours() + ":"
		+ (dt_datetime.getMinutes() < 10 ? '0' : '') + (dt_datetime.getMinutes()) + ":"
		+ (dt_datetime.getSeconds() < 10 ? '0' : '') + (dt_datetime.getSeconds())
	);
}

// timestamp parsing function
function cal_prs_tsmp2 (str_datetime) {
	// if no parameter specified return current timestamp
	if (!str_datetime)
		return (new Date());

	// if positive integer treat as milliseconds from epoch
	if (RE_NUM.exec(str_datetime))
		return new Date(str_datetime);

	// else treat as date in string format
	var arr_datetime = str_datetime.split(' ');
	return this.prs_time(arr_datetime[1], this.prs_date(arr_datetime[0]));
}

// date parsing function
function cal_prs_date2 (str_date) {

	var arr_date = str_date.split('/');

	if (arr_date.length != 3) return alert ("Invalid date format: '" + str_date + "'.\nFormat accepted is dd-mm-yyyy.");
	if (!arr_date[1]) return alert ("Invalid date format: '" + str_date + "'.\nNo day of month value can be found.");
	if (!RE_NUM.exec(arr_date[1])) return alert ("Invalid day of month value: '" + arr_date[1] + "'.\nAllowed values are unsigned integers.");
	if (!arr_date[0]) return alert ("Invalid date format: '" + str_date + "'.\nNo month value can be found.");
	if (!RE_NUM.exec(arr_date[0])) return alert ("Invalid month value: '" + arr_date[0] + "'.\nAllowed values are unsigned integers.");
	if (!arr_date[2]) return alert ("Invalid date format: '" + str_date + "'.\nNo year value can be found.");
	if (!RE_NUM.exec(arr_date[2])) return alert ("Invalid year value: '" + arr_date[2] + "'.\nAllowed values are unsigned integers.");

	var dt_date = new Date();
	dt_date.setDate(1);

	if (arr_date[0] < 1 || arr_date[0] > 12) return alert ("Invalid month value: '" + arr_date[0] + "'.\nAllowed range is 01-12.");
	dt_date.setMonth(arr_date[0]-1);

	if (arr_date[2] < 100) arr_date[2] = Number(arr_date[2]) + (arr_date[2] < NUM_CENTYEAR ? 2000 : 1900);
	dt_date.setFullYear(arr_date[2]);

	var dt_numdays = new Date(arr_date[2], arr_date[0], 0);
	dt_date.setDate(arr_date[1]);
	if (dt_date.getMonth() != (arr_date[0]-1)) return alert ("Invalid day of month value: '" + arr_date[1] + "'.\nAllowed range is 01-"+dt_numdays.getDate()+".");

	return (dt_date)
}

// time parsing function
function cal_prs_time2 (str_time, dt_date) {

	if (!dt_date) return null;
	var arr_time = String(str_time ? str_time : '').split(':');

	if (!arr_time[0]) dt_date.setHours(0);
	else if (RE_NUM.exec(arr_time[0]))
		if (arr_time[0] < 24) dt_date.setHours(arr_time[0]);
		else return cal_error ("Invalid hours value: '" + arr_time[0] + "'.\nAllowed range is 00-23.");
	else return cal_error ("Invalid hours value: '" + arr_time[0] + "'.\nAllowed values are unsigned integers.");

	if (!arr_time[1]) dt_date.setMinutes(0);
	else if (RE_NUM.exec(arr_time[1]))
		if (arr_time[1] < 60) dt_date.setMinutes(arr_time[1]);
		else return cal_error ("Invalid minutes value: '" + arr_time[1] + "'.\nAllowed range is 00-59.");
	else return cal_error ("Invalid minutes value: '" + arr_time[1] + "'.\nAllowed values are unsigned integers.");

	if (!arr_time[2]) dt_date.setSeconds(0);
	else if (RE_NUM.exec(arr_time[2]))
		if (arr_time[2] < 60) dt_date.setSeconds(arr_time[2]);
		else return cal_error ("Invalid seconds value: '" + arr_time[2] + "'.\nAllowed range is 00-59.");
	else return cal_error ("Invalid seconds value: '" + arr_time[2] + "'.\nAllowed values are unsigned integers.");

	dt_date.setMilliseconds(0);
	return dt_date;
}

function cal_error (str_message) {
	alert (str_message);
	return null;
}
</script>
<link media="screen" type="text/css" rel="stylesheet" href="<?php echo $cssfile; ?>">
</link>
<link media="screen" type="text/css" rel="stylesheet" href="<?php echo $formfile; ?>">
</link>
<div id="webclass" style="margin: 3px;">
<h2 class="titleholder">Sclipo Live Web Class</h2>
				<ul>
					<li>
						<div class="col-left">
						<table style="width: 100%"><tr>
						<td>
							<h3 style="text-align: left; color: #FF671C;"><?php echo $webclass["title"]; ?></h3>
						</td>
						<td style="text-align: right;">
							<ul style="border-top: none; float: right;">

<?php
	$timespan = sclipo_remaining_time($webclass["class_date"].' '.$webclass["time"].':00', $webclass["duration"]);
	if ($timespan == "S")
		echo '<li><form><input type="button" value=" Class Started. Enter Now! " onClick="window.open(\'http://sclipo.com/webclasses/enter/'.$sclipo->reference.'\')" /></form>';
	else if ($timespan == "F")
		echo '<li><strong>Class Finished</strong>';
	else
		echo '<li id="course-starts" style="height: 25px;"><span style=" font-size: 14px; font-weight: bold; color: #545454;">Starts in:</span><strong> '.$timespan.'</strong>';
?>
</li>
</ul>
</td></tr></table>

							<em class="date">Web class by: <?php echo '<a style="color:#FF671C;" href="'.$CFG->wwwroot.'/user/view.php?id='.$teacher_info->id.'">'.$teacher_info->firstname.' '.$teacher_info->lastname.'</a>'; ?> </em><br />
                            <em class="date">Starts at <?php
							list($month,$day,$year) = split("/",$webclass["class_date"]);
							$months = array ("01" => "January", "02" => "February", "03" => "March", "04" => "April", "05" => "May", "06" => "June", "07" => "July", "08" => "August", "09" => "September", "10" => "October", "11" => "November", "12" => "December");
							echo $webclass["time"]." (".$timezone.") - ".$months[$month]." ".$day.", ".$year; ?></em><br />
							<em class="date">Duration: <?php echo $webclass["duration"]; ?> minutes</em>
							<h4 style="margin-top: 15px;">Tags: <em><?php echo $webclass["tags"]; ?></em></h4>


							<table style="width: 100%">
							<tr valign="top">
							<td width="60%">
							<ul style="border-top: none;">



								<li>
									<h4>Description</h4>
									<?php echo $webclass["description"]; ?>
								</li>

								 <li>

                                           </li>

							</ul>
							</td>
							<td width="40%" style="padding-left: 10px;">
							<div style=
   "border: solid 0 black; border-left-width:2px; margin-left: 25px; padding-left:0.5ex">

							<ul style="border-top: none; ">



								<li>
									<h4>Documents in this Web Class</h4>
									The following document(s) will be presented during the web class. <br />Click on title to view the document:
								</li>
								<li>
									<ul style="border: none; list-style-type:circle; padding-left: 15px;">
								<?php
									$documents = sclipo_getWebClassContent($sclipo->reference);
									if (sizeof($documents) == 0) {
										echo "<i>No documents added</i>";
										if ($is_teacher && !$not_logged_in) {
											echo "<br />";
											echo '<a style="color: #FF671C;" href="#adddocument" rel="facebox[.adddocument]"><i>Add your first document</i></a>';
										}
									}
									foreach ($documents as $doc) {
										echo '<li class="'.$doc["content_id"].'">';
										if ($doc["content_type"] == 'D')
											echo '<a target="_blank" href="http://sclipo.com/documents/view/'.$doc["content_id"].'" >'.$doc["title"].'</a>';
										else if ($doc["content_type"] == 'V')
											echo '<a target="_blank" href="http://sclipo.com/videos/view/'.$doc["pretty_url"].'" >'.$doc["title"].'</a>';
										else if ($doc["content_type"] == 'I' || $doc["content_type"] == 'A')
											echo '<a target="_blank" href="http://sclipo.com/content/view/'.$doc["content_id"].'" >'.$doc["title"].'</a>';
										else print_r($doc);
										echo "</li>";
									}
								?>
									</ul>
								</li>
								<li>&nbsp;</li>


							</ul>
							</div>
							</td>
							</tr>
							</table>
							<?php
								if ($is_teacher || $teacher_not_logged_in) {
								if ($teacher_not_logged_in)
									$redirect = 'view.php?id='.$id;
								echo '
							<table style="width: 100%;">
							<tr valign="top">
							<td align="right" style="width: 60%;">
							<form>
							<input type="button" value="Edit Web Class" onClick="window.location.href= \''.$CFG->wwwroot.'/course/mod.php?update='.$cm->id.'&sesskey='.sesskey().'&sr='.($cm->section-1).'\'" />
							</form><br /><br />
								<h4 style="text-align: left;">Web Class URL <span style="font-size: 12px; color: rgb(128, 128, 128); font-weight: normal;" id="teacher_url">(you can invite students from outside of Moodle to attend this
class by sending them this URL)</span></h4>
								<input type="text" style="width: 100%; clear: both; margin-bottom: 5px; margin-top: 5px;" readonly="" value="http://sclipo.com/webclasses/enter/'.$sclipo->reference.'"/>
                            </td>
							<td align="right">';
							if ($teacher_not_logged_in)
								echo '<a href="'.$CFG->wwwroot.'/course/mod.php?redirect='.$redirect.'&showadd=1&update='.$cm->id.'&sesskey='.sesskey().'&sr='.($cm->section-1).'"> <input type="button" onclick="$(\'.limiter\').html(\'1000\');" value="Add New Document " /></a>';
							else {
							echo '
							<a id="button-adddoc" href="#adddocument" rel="facebox[.adddocument]"> <input type="button" onclick="$(\'.limiter\').html(\'1000\');" value="Add New Document " /></a>
								<br /><br />';
								if (sizeof($documents) > 0) echo '
								<a id="button-edit-doc" href="#editdocumentlist" style="color:#FF671C"rel="facebox"> Edit Document List</a>
								';
							}
							echo '
							</td>
							</tr>
							</table>
							';}
							?>
							<input type="hidden" id="num-docs" value="<?php echo sizeof($documents); ?>" />
							<div style="background-color: #D4D4D4; width: 400px; padding: 5px; display: <?php if ($webclass["public"] == 1) echo "block;"; else echo "none;"; ?>">
							<span style="font-weight: bold;">Public Web Class</span>
															<br /><br />
															Anybody can participate in this
									class. This web class is listed in
									Sclipo's web class directory and this
									teachers Sclipo Academy. This web
									class can also be found in public
									search engines like Google.
															</div>
															<ul>
										  <h4>System Requirements</h4>
									Sclipo Live Web Classes work on most any PC and Mac with Internet
									connection. No downloads or installations are needed.<br/><br/>
									The minimum requirements are:<br/><br/>

									<em>All Participants:</em><br/>
									 1) Browser with Flash installed: Firefox 3 or later (recommended), Safari 4
									or later, Chrome, IE 7 or later<br/>
									 2) Flash: version 10 or later<br/>
									 3) Internet Connection: Broadband, 1 MB upload and download speed
									recommended.<br/>
									 4) RAM: 1 GB or higher<br/><br/>

									<em>Teachers (required):</em><br/>
									Webcam: Please use only good and up-to-date webcam, micro and headset. This
									is very important to achieve good video and audio quality.<br/><br/>

									<em>Students (optional):</em><br/>
									1) Microphone & headset: Needed if student wants to speak during class.<br/>
									2) Webcam: Needed if student wants to be seen in class.
									</ul>


															<br /><br />
															<!-- .center -->
							</div>

					</li>
				</ul>

			<!-- #webclass -->
			</div>
			<div id="editdocumentlist" style="display: none;">
			<h3>Edit Document List</h3>
			<ul class="receiver">
			<?php
				$documents = sclipo_getWebClassContent($sclipo->reference);

				foreach ($documents as $doc) {
					echo '<li rel="documents" class="'.$doc["content_id"].'">'.$doc["title"];
					echo ' (<a href="#" onclick=removecontent("'.$sclipo->reference.'","'.$doc["content_id"].'");>Remove</a>)';
				}
			?>
			</ul>
			</div>

			<div id="adddocument" style="display: none;">
			<div id="info_text">
				<h3>Select your file, fill out the description and press "Add Content"</h3>
				<form id="documentform" enctype="multipart/form-data" action="http://sclipo.com/api_rest/doupload" method="POST" onsubmit="var ret = validateForm(this); if (ret == false) return ret; doWait(); return true;">
				<input type="hidden" name="return" value="<?php echo "http://" . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI']; ?>" />
				<input type="hidden" name="sessionid" value="<?php echo $_SESSION["sclipo_id"]; ?>" />
				<input type="hidden" name="reference" value="<?php echo $sclipo->reference; ?>" />
				<input type="hidden" name="moodleuser" value="<?php echo $USER->username; ?>" />

				 <input name="mycontent" type="file" value=" Choose file ... "/>

				<p>The maximum file size is 200 MB for videos and 20 MB for documents. <br/>
					Add only content for educational purposes such as lectures, classes, tutorials, How-tos or DIYs.				</p>
					<ul>
						<li id="errtitle" style="display: none;" ><label class="error" >Please enter a title.</label></li>
						<li id="errdescription" style="display: none;" ><label class="error" >Please enter a description.</label></li>
                        <li style="display: none;" id="errtags"><label  class="error">Please enter at least two tags.</label></li>

						<li>&nbsp;</li>
					</ul>
				<ul>
						<li>
							<label for="title">Title</label>
							<p>A good title helps find the content!</p>
							<input type="text" name="title" id="title"/>
						</li>
						<li>
							<br />
							<label for="title">Description (maximum 1000 characters):</label>
							<p>A good description is key to understands the content. Keep it clear and concise.</p>
							<textarea style="min-height: 40px; min-width: 200px; display: block;" wrap="soft" onkeyup="limiterDesc(this);" class="expanding" id="description" name="description"></textarea>
							<span class="limiter">1000</span>/1000
						</li>
						<li>
							<br />
							<label>Tags (Write at least 2 tags, space separated)</label>
							<p>Tags are keywords that summarize the content.</p>
							<input type="text" id="tags" name="tags" onkeyup="limiterTags(this);"/>
						</li>
						<li>
							<br />
							<label>Privacy</label>

							<p>
							<input type="checkbox" name="public" value=" Public Document" /> Public Document -
							Mark this option if anybody can find and view your document on Sclipo and the whole Internet.</p>
						</li>
				</ul>
				<input type="submit" class="button" id="button1" value="Add Content"/>
				</form>
			</div>
			</div>

<?php
  echo '</div>';
    echo '</td>';

    // END: Main column
    // START: Last column (3-month display)
    echo '<td class="sidecalendar">';
    echo '<div class="header">'.get_string('monthlyview', 'calendar').'</div>';

    list($prevmon, $prevyr) = calendar_sub_month($mon, $yr);
    list($nextmon, $nextyr) = calendar_add_month($mon, $yr);
    $getvars = 'id='.$course->id.'&amp;cal_d='.$day.'&amp;cal_m='.$mon.'&amp;cal_y='.$yr; // For filtering

    echo '<div class="filters">';
    echo calendar_filter_controls($view, $getvars);
    echo '</div>';

    echo '<div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $courseid, 'm' => $prevmon, 'y' => $prevyr));
    echo calendar_get_mini($courses, $groups, $users, $prevmon, $prevyr);
    echo '</div><div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $courseid, 'm' => $mon, 'y' => $yr));
    echo calendar_get_mini($courses, $groups, $users, $mon, $yr);
    echo '</div><div class="minicalendarblock">';
    echo calendar_top_controls('display', array('id' => $courseid, 'm' => $nextmon, 'y' => $nextyr));
    echo calendar_get_mini($courses, $groups, $users, $nextmon, $nextyr);
    echo '</div>';

    echo '</td>';

    echo '</tr></table>';

/// Finish the page
    print_footer($course);


    function calendar_show_day($d, $m, $y, $courses, $groups, $users, $courseid) {
    global $CFG, $USER;

    if (!checkdate($m, $d, $y)) {
        $now = usergetdate(time());
        list($d, $m, $y) = array(intval($now['mday']), intval($now['mon']), intval($now['year']));
    }

    $getvars = 'from=day&amp;cal_d='.$d.'&amp;cal_m='.$m.'&amp;cal_y='.$y; // For filtering

    $starttime = make_timestamp($y, $m, $d);
    $endtime   = make_timestamp($y, $m, $d + 1) - 1;

    $events = calendar_get_upcoming($courses, $groups, $users, 1, 100, $starttime);

    $text = '';
    if (!isguest() && !empty($USER->id) && calendar_user_can_add_event()) {
        $text.= '<div class="buttons">';
        $text.= '<form action="'.CALENDAR_URL.'event.php" method="get">';
        $text.= '<div>';
        $text.= '<input type="hidden" name="action" value="new" />';
        $text.= '<input type="hidden" name="course" value="'.$courseid.'" />';
        $text.= '<input type="hidden" name="cal_d" value="'.$d.'" />';
        $text.= '<input type="hidden" name="cal_m" value="'.$m.'" />';
        $text.= '<input type="hidden" name="cal_y" value="'.$y.'" />';
        $text.= '<input type="submit" value="'.get_string('newevent', 'calendar').'" />';
        $text.= '</div></form></div>';
    }

    $text .= get_string('dayview', 'calendar').': '.calendar_course_filter_selector($getvars);

    echo '<div class="header">'.$text.'</div>';

    echo '<div class="controls">'.calendar_top_controls('day', array('id' => $courseid, 'd' => $d, 'm' => $m, 'y' => $y)).'</div>';

    if (empty($events)) {
        // There is nothing to display today.
        echo '<h3>'.get_string('daywithnoevents', 'calendar').'</h3>';

    } else {

        echo '<div class="eventlist">';

        $underway = array();

        // First, print details about events that start today
        foreach ($events as $event) {

            $event->calendarcourseid = $courseid;

            if ($event->timestart >= $starttime && $event->timestart <= $endtime) {  // Print it now


/*
                $dayend = calendar_day_representation($event->timestart + $event->timeduration);
                $timeend = calendar_time_representation($event->timestart + $event->timeduration);
                $enddate = usergetdate($event->timestart + $event->timeduration);
                // Set printable representation
                echo calendar_get_link_tag($dayend, CALENDAR_URL.'view.php?view=day'.$morehref.'&amp;', $enddate['mday'], $enddate['mon'], $enddate['year']).' ('.$timeend.')';
*/
                //unset($event->time);
                $event->time = calendar_format_event_time($event, time(), '', false);
                calendar_print_event($event);

            } else {                                                                 // Save this for later
                $underway[] = $event;
            }
        }

        // Then, show a list of all events that just span this day
        if (!empty($underway)) {
            echo '<h3>'.get_string('spanningevents', 'calendar').':</h3>';
            foreach ($underway as $event) {
                $event->time = calendar_format_event_time($event, time(), '', false);
                calendar_print_event($event);
            }
        }

        echo '</div>';

    }
}

function calendar_show_month_detailed($m, $y, $courses, $groups, $users, $courseid) {
    global $CFG, $SESSION, $USER, $CALENDARDAYS;
    global $day, $mon, $yr;

    $getvars = 'from=month&amp;cal_d='.$day.'&amp;cal_m='.$mon.'&amp;cal_y='.$yr; // For filtering

    $display = &New stdClass;
    $display->minwday = get_user_preferences('calendar_startwday', CALENDAR_STARTING_WEEKDAY);
    $display->maxwday = $display->minwday + 6;

    if(!empty($m) && !empty($y)) {
        $thisdate = usergetdate(time()); // Time and day at the user's location
        if($m == $thisdate['mon'] && $y == $thisdate['year']) {
            // Navigated to this month
            $date = $thisdate;
            $display->thismonth = true;
        }
        else {
            // Navigated to other month, let's do a nice trick and save us a lot of work...
            if(!checkdate($m, 1, $y)) {
                $date = array('mday' => 1, 'mon' => $thisdate['mon'], 'year' => $thisdate['year']);
                $display->thismonth = true;
            }
            else {
                $date = array('mday' => 1, 'mon' => $m, 'year' => $y);
                $display->thismonth = false;
            }
        }
    }
    else {
        $date = usergetdate(time());
        $display->thismonth = true;
    }

    // Fill in the variables we 're going to use, nice and tidy
    list($d, $m, $y) = array($date['mday'], $date['mon'], $date['year']); // This is what we want to display
    $display->maxdays = calendar_days_in_month($m, $y);

    $startwday = 0;
    if (get_user_timezone_offset() < 99) {
        // We 'll keep these values as GMT here, and offset them when the time comes to query the db
        $display->tstart = gmmktime(0, 0, 0, $m, 1, $y); // This is GMT
        $display->tend = gmmktime(23, 59, 59, $m, $display->maxdays, $y); // GMT
        $startwday = gmdate('w', $display->tstart); // $display->tstart is already GMT, so don't use date(): messes with server's TZ
    } else {
        // no timezone info specified
        $display->tstart = mktime(0, 0, 0, $m, 1, $y);
        $display->tend = mktime(23, 59, 59, $m, $display->maxdays, $y);
        $startwday = date('w', $display->tstart); // $display->tstart not necessarily GMT, so use date()
    }

    // Align the starting weekday to fall in our display range
    if($startwday < $display->minwday) {
        $startwday += 7;
    }

    // Get events from database
    $whereclause = calendar_sql_where(usertime($display->tstart), usertime($display->tend), $users, $groups, $courses);
    if($whereclause === false) {
        $events = array();
    }
    else {
        $events = get_records_select('event', $whereclause, 'timestart');
    }

    // Extract information: events vs. time
    calendar_events_by_day($events, $m, $y, $eventsbyday, $durationbyday, $typesbyday);

    $text = '';
    if(!isguest() && !empty($USER->id) && calendar_user_can_add_event()) {
        $text.= '<div class="buttons"><form action="'.CALENDAR_URL.'event.php" method="get">';
        $text.= '<div>';
        $text.= '<input type="hidden" name="action" value="new" />';
        $text.= '<input type="hidden" name="course" value="'.$courseid.'" />';
        $text.= '<input type="hidden" name="cal_m" value="'.$m.'" />';
        $text.= '<input type="hidden" name="cal_y" value="'.$y.'" />';
        $text.= '<input type="submit" value="'.get_string('newevent', 'calendar').'" />';
        $text.= '</div></form></div>';
    }

    $text .= get_string('detailedmonthview', 'calendar').': '.calendar_course_filter_selector($getvars);

    echo '<div class="header">'.$text.'</div>';

    echo '<div class="controls">';
    echo calendar_top_controls('month', array('id' => $courseid, 'm' => $m, 'y' => $y));
    echo '</div>';

    // Start calendar display
    echo '<table class="calendarmonth"><tr class="weekdays">'; // Begin table. First row: day names

    // Print out the names of the weekdays
    for($i = $display->minwday; $i <= $display->maxwday; ++$i) {
        // This uses the % operator to get the correct weekday no matter what shift we have
        // applied to the $display->minwday : $display->maxwday range from the default 0 : 6
        echo '<th scope="col">'.get_string($CALENDARDAYS[$i % 7], 'calendar').'</th>';
    }

    echo '</tr><tr>'; // End of day names; prepare for day numbers

    // For the table display. $week is the row; $dayweek is the column.
    $week = 1;
    $dayweek = $startwday;

    // Paddding (the first week may have blank days in the beginning)
    for($i = $display->minwday; $i < $startwday; ++$i) {
        echo '<td>&nbsp;</td>'."\n";
    }

    // Now display all the calendar
    for($day = 1; $day <= $display->maxdays; ++$day, ++$dayweek) {
        if($dayweek > $display->maxwday) {
            // We need to change week (table row)
            echo "</tr>\n<tr>";
            $dayweek = $display->minwday;
            ++$week;
        }

        // Reset vars
        $cell = '';
        $dayhref = calendar_get_link_href(CALENDAR_URL.'view.php?view=day&amp;course='.$courseid.'&amp;', $day, $m, $y);

        if(CALENDAR_WEEKEND & (1 << ($dayweek % 7))) {
            // Weekend. This is true no matter what the exact range is.
            $class = 'weekend';
        }
        else {
            // Normal working day.
            $class = '';
        }

        // Special visual fx if an event is defined
        if(isset($eventsbyday[$day])) {
            if(isset($typesbyday[$day]['startglobal'])) {
                $class .= ' event_global';
            }
            else if(isset($typesbyday[$day]['startcourse'])) {
                $class .= ' event_course';
            }
            else if(isset($typesbyday[$day]['startgroup'])) {
                $class .= ' event_group';
            }
            else if(isset($typesbyday[$day]['startuser'])) {
                $class .= ' event_user';
            }
            if(count($eventsbyday[$day]) == 1) {
                $title = get_string('oneevent', 'calendar');
            }
            else {
                $title = get_string('manyevents', 'calendar', count($eventsbyday[$day]));
            }
            $cell = '<div class="day"><a href="'.$dayhref.'" title="'.$title.'">'.$day.'</a></div>';
        }
        else {
            $cell = '<div class="day">'.$day.'</div>';
        }

        // Special visual fx if an event spans many days
        if(isset($typesbyday[$day]['durationglobal'])) {
            $class .= ' duration_global';
        }
        else if(isset($typesbyday[$day]['durationcourse'])) {
            $class .= ' duration_course';
        }
        else if(isset($typesbyday[$day]['durationgroup'])) {
            $class .= ' duration_group';
        }
        else if(isset($typesbyday[$day]['durationuser'])) {
            $class .= ' duration_user';
        }

        // Special visual fx for today
        if($display->thismonth && $day == $d) {
            $class .= ' today';
        }

        // Just display it
        if(!empty($class)) {
            $class = ' class="'.trim($class).'"';
        }
        echo '<td'.$class.'>'.$cell;

        if(isset($eventsbyday[$day])) {
            echo '<ul class="events-new">';
            foreach($eventsbyday[$day] as $eventindex) {
                echo '<li><a href="'.$dayhref.'#event_'.$events[$eventindex]->id.'">'.format_string($events[$eventindex]->name, true).'</a></li>';
            }
            echo '</ul>';
        }
        if(isset($durationbyday[$day])) {
            echo '<ul class="events-underway">';
            foreach($durationbyday[$day] as $eventindex) {
                echo '<li>['.format_string($events[$eventindex]->name,true).']</li>';
            }
            echo '</ul>';
        }
        echo "</td>\n";
    }

    // Paddding (the last week may have blank days at the end)
    for($i = $dayweek; $i <= $display->maxwday; ++$i) {
        echo '<td>&nbsp;</td>';
    }
    echo "</tr>\n"; // Last row ends

    echo "</table>\n"; // Tabular display of days ends

    // OK, now for the filtering display
    echo '<div class="filters"><table><tr>';

    // Global events
    if($SESSION->cal_show_global) {
        echo '<td class="event_global" style="width: 8px;"></td><td><strong>'.get_string('globalevents', 'calendar').':</strong> ';
        echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showglobal&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
    }
    else {
        echo '<td style="width: 8px;"></td><td><strong>'.get_string('globalevents', 'calendar').':</strong> ';
        echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showglobal&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
    }

    // Course events
    if(!empty($SESSION->cal_show_course)) {
        echo '<td class="event_course" style="width: 8px;"></td><td><strong>'.get_string('courseevents', 'calendar').':</strong> ';
        echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showcourses&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
    }
    else {
        echo '<td style="width: 8px;"></td><td><strong>'.get_string('courseevents', 'calendar').':</strong> ';
        echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showcourses&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
    }

    echo "</tr>\n";

    if(!empty($USER->id) && !isguest()) {
        echo '<tr>';
        // Group events
        if($SESSION->cal_show_groups) {
            echo '<td class="event_group" style="width: 8px;"></td><td><strong>'.get_string('groupevents', 'calendar').':</strong> ';
            echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showgroups&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
        }
        else {
            echo '<td style="width: 8px;"></td><td><strong>'.get_string('groupevents', 'calendar').':</strong> ';
            echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showgroups&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
        }
        // User events
        if($SESSION->cal_show_user) {
            echo '<td class="event_user" style="width: 8px;"></td><td><strong>'.get_string('userevents', 'calendar').':</strong> ';
            echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showuser&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
        }
        else {
            echo '<td style="width: 8px;"></td><td><strong>'.get_string('userevents', 'calendar').':</strong> ';
            echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showuser&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
        }
        echo "</tr>\n";
    }

    echo '</table></div>';
}

   function calendar_show_upcoming_events($courses, $groups, $users, $futuredays, $maxevents, $courseid) {
      global $USER;

    $events = calendar_get_upcoming($courses, $groups, $users, $futuredays, $maxevents);

    $text = '';

    if(!isguest() && !empty($USER->id) && calendar_user_can_add_event()) {
        $text.= '<div class="buttons">';
        $text.= '<form action="'.CALENDAR_URL.'event.php" method="get">';
        $text.= '<div>';
        $text.= '<input type="hidden" name="action" value="new" />';
        $text.= '<input type="hidden" name="course" value="'.$courseid.'" />';
        /*
        $text.= '<input type="hidden" name="cal_m" value="'.$m.'" />';
        $text.= '<input type="hidden" name="cal_y" value="'.$y.'" />';
        */
        $text.= '<input type="submit" value="'.get_string('newevent', 'calendar').'" />';
        $text.= '</div></form></div>';
    }

    $text .= get_string('upcomingevents', 'calendar').': '.calendar_course_filter_selector('from=upcoming');

    echo '<div class="header">'.$text.'</div>';

    if ($events) {
        echo '<div class="eventlist">';
        foreach ($events as $event) {
            $event->calendarcourseid = $courseid;
            calendar_print_event($event);
        }
        echo '</div>';
    } else {
        print_heading(get_string('noupcomingevents', 'calendar'));
    }
}

function calendar_course_filter_selector($getvars = '') {
    global $USER, $SESSION;

    if (empty($USER->id) or isguest()) {
        return '';
    }

    if (has_capability('moodle/calendar:manageentries', get_context_instance(CONTEXT_SYSTEM, SITEID)) && !empty($CFG->calendar_adminseesall)) {
        $courses = get_courses('all', 'c.shortname','c.id,c.shortname');
    } else {
        $courses = get_my_courses($USER->id, 'shortname');
    }

    unset($courses[SITEID]);

    $courseoptions[SITEID] = get_string('fulllistofcourses');
    foreach ($courses as $course) {
        $courseoptions[$course->id] = format_string($course->shortname);
    }

    if (is_numeric($SESSION->cal_courses_shown)) {
        $selected = $SESSION->cal_courses_shown;
    } else {
        $selected = '';
    }

    return popup_form(CALENDAR_URL.'set.php?var=setcourse&amp;'.$getvars.'&amp;id=',
                       $courseoptions, 'cal_course_flt', $selected, '', '', '', true);
}

?>