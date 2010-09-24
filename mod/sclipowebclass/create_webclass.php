<?php // $Id: create_webclass.php,v 1.4 2009/09/23 14:49:51 alexsclipo Exp $

//  Moves, adds, updates, duplicates or deletes modules in a course

    require_once("../../config.php");
    require_once("lib.php");
	require_once($CFG->dirroot.'/calendar/lib.php');
	require_once("sclipoapi.php");

    require_login();

	if (!isset($form->name)) {
    $form->name = '';
}

$cssfile=$CFG->wwwroot;
$cssfile.="/mod/sclipowebclass/css/";
$scimg=$CFG->wwwroot."/mod/sclipowebclass/scimg/";

// Check security
if (!sclipo_checkLogin("notusedanymore", $USER->username)){
	echo "Error: Need to be logged in to Sclipo";
	exit();
}

// Get user timezone information

$timezone = sclipo_gettimezone("notused", $USER->username);
$date = date("Y-m-d h:iA e");
$cur_time = date("h:iA");
$confirmed = sclipo_isTimezoneConfirmed("notused", $USER->username);

// Check if user just created a new webclass
if (isset($_POST['submitted'])) {

	// This is for the sclipo table
	class SclipoWebClass {
		var $teacherid;
		var $title;
		var $description;
		var $tags;
		var $class_date;
		var $time;
		var $duration;
		var $max_students;
	}

	// This is for the event table (used by calendar)
	class Event {
      var $name;
      var $description;
      var $format=0;
      var $courseid;

      var $userid;
      var $modulename;
      var $timestart;
      var $timeduration;
      var $visible;

	  var $instance;
      var $eventtype;
      var $timemodified;
      var $sequence=1;
    }

	if (isset($SESSION->modform)) {   // Variables are stored in the session

        $mod = $SESSION->modform;


        unset($SESSION->modform);
    } else {
        $mod = (object)$_POST;
    }

	if (isset($course) && !course_allowed_module($course,$mod->modulename)) {
        error("This module ($mod->modulename) has been disabled for this particular course");
    }

    if (!isset($mod->name) || trim($mod->name) == '') {
        $mod->name = get_string("modulename", $mod->modulename);
	}

	$addinstancefunction    = $mod->modulename."_add_instance";
    $updateinstancefunction = $mod->modulename."_update_instance";
    $deleteinstancefunction = $mod->modulename."_delete_instance";

	// Finally create the actual webclass at Sclipo
	$webclass->title = stripslashes($_POST["title"]);
	$webclass->description = stripslashes($_POST["description"]);
	$webclass->tags = stripslashes($_POST["tags"]);
	$webclass->class_date = $_POST["method_face_date"];
	$webclass->time = $_POST["timeEntry"];
	$webclass->duration = $_POST["duration"];
	$webclass->max_students = $_POST["max_students"];
	if(isset($_POST['publicwebclass']))
		$webclass->public_class = 1;
	else
		$webclass->public_class = 0;
	$reference = sclipo_createWebClass($_SESSION["sclipo_id"], $USER->username, $webclass);

	$newClass = new SclipoWebClass;
	$newClass->course = $mod->course;
	$newClass->teacherid = sclipo_getUserIDFromSession($_SESSION["sclipo_id"], $USER->username);
	$newClass->name = $_POST["title"];
	$newClass->description = $_POST["description"];
	$newClass->tags = $_POST["tags"];
	$newClass->class_date = $_POST["method_face_date"];
	$newClass->time = $_POST["timeEntry"];
	$newClass->duration = $_POST["duration"];
	$newClass->max_students = $_POST["max_students"];
	$newClass->reference = $reference;
	$newClass->teacherinfo = $USER->id;

	$instanceid = $addinstancefunction($newClass);	// Add a record to the sclipo table

	$mod->instance = $instanceid;

	list($month, $day, $year)=split('/', $_POST["method_face_date"]);
	list($hour, $min)=split(':', $_POST["timeEntry"]);
	$month = intval($month);
	$day = intval($day);
	$year = intval($year);
	$hour = intval($hour);
	$min = intval($min);
	$tz = sclipo_gettimezone($_SESSION["sclipo_id"], $USER->username);

	$event->name=$newClass->name;
    $event->description='תאור המפגש';
    $event->courseid=$mod->course;
    $event->userid=intval($USER->id);
    $event->modulename="sclipowebclass";
    $event->instance=$instanceid;
    $event->timestart=make_timestamp($year, $month, $day, $hour, $min, $tz);
    $event->timeduration=0;
    $event->eventtype="open";
    $event->visible=1;

	sclipowebclass_add_event($event);	// So calendar can find this web class

    // course_modules and course_sections each contain a reference
    // to each other, so we have to update one of them twice.

    if (! $mod->coursemodule = add_course_module($mod) ) {
		error("Could not add a new course module");

	}
    if (! $sectionid = add_mod_to_section($mod) ) {
        error("Could not add the new course module to that section");
	}

    if (! set_field("course_modules", "section", $sectionid, "id", $mod->coursemodule)) {
        error("Could not update the course module with the correct section");
    }

    if (!isset($mod->visible)) {   // We get the section's visible field status
        $mod->visible = get_field("course_sections","visible","id",$sectionid);
    }
     // make sure visibility is set correctly (in particular in calendar)
    set_coursemodule_visible($mod->coursemodule, $mod->visible);

	redirect("$CFG->wwwroot/mod/$mod->modulename/view.php?id=$mod->coursemodule","Web class created: Redirecting, please wait ...",0);

	exit();
}

    $sectionreturn = optional_param('sr', '', PARAM_INT);
    $add           = optional_param('add','', PARAM_ALPHA);
    $type          = optional_param('type', '', PARAM_ALPHA);
    $indent        = optional_param('indent', 0, PARAM_INT);
    $update        = optional_param('update', 0, PARAM_INT);
    $hide          = optional_param('hide', 0, PARAM_INT);
    $show          = optional_param('show', 0, PARAM_INT);
    $copy          = optional_param('copy', 0, PARAM_INT);
    $moveto        = optional_param('moveto', 0, PARAM_INT);
    $movetosection = optional_param('movetosection', 0, PARAM_INT);
    $delete        = optional_param('delete', 0, PARAM_INT);
    $course        = optional_param('course', 0, PARAM_INT);
    $groupmode     = optional_param('groupmode', -1, PARAM_INT);
    $duplicate     = optional_param('duplicate', 0, PARAM_INT);
    $cancel        = optional_param('cancel', 0, PARAM_BOOL);
    $cancelcopy    = optional_param('cancelcopy', 0, PARAM_BOOL);

	require_once("sclipoapi.php");

    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $mod = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $mod = (object)$_POST;
    }

    if (!empty($add) and confirm_sesskey()) {

        $id = required_param('id',PARAM_INT);
        $section = required_param('section',PARAM_INT);

        if (! $course = get_record("course", "id", $id)) {
            error("This course doesn't exist");
        }

        if (! $module = get_record("modules", "name", $add)) {
            error("This module type doesn't exist");
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:manageactivities', $context);

        if (!course_allowed_module($course,$module->id)) {
            error("This module has been disabled for this particular course");
        }

        require_login($course->id); // needed to setup proper $COURSE

        $form->section    = $section;         // The section number itself
        $form->course     = $course->id;
        $form->module     = $module->id;
        $form->modulename = $module->name;
        $form->instance   = "";
        $form->coursemodule = "";
        $form->mode       = "add";
        $form->sesskey    = !empty($USER->id) ? $USER->sesskey : '';
        if (!empty($type)) {
            $form->type = $type;
        }

        $sectionname    = get_section_name($course->format);
        $fullmodulename = get_string("modulename", $module->name);

        $CFG->pagepath = 'mod/'.$module->name;
        if (!empty($type)) {
            $CFG->pagepath .= '/' . $type;
        }
        else {
            $CFG->pagepath .= '/mod';
        }

    } else {
        error("No action was specified");
    }

	$courseid = $course->id;

    require_login($course->id); // needed to setup proper $COURSE
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/course:manageactivities', $context);

    $streditinga = get_string("editinga", "moodle", $fullmodulename);
    $strmodulenameplural = get_string("modulenameplural", $module->name);

    if ($module->name == "label") {
        $focuscursor = "form.content";
    } else {
        $focuscursor = "form.name";
    }

    $navlinks = array();
    $navlinks[] = array('name' => "Sclipo Live Web Class", 'link' => "$CFG->wwwroot/mod/$module->name/index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => "Create & Schedule Web Classes", 'link' => '', 'type' => 'action');
    $navigation = build_navigation($navlinks);

    print_header_simple("Sclipo Live Web Class", '', $navigation, "", "", false);

    if (!empty($cm->id)) {
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $currenttab = 'update';
        $overridableroles = get_overridable_roles($context);
        $assignableroles  = get_assignable_roles($context);
        include_once($CFG->dirroot.'/'.$CFG->admin.'/roles/tabs.php');
    }

    unset($SESSION->modform); // Clear any old ones that may be hanging around.


    $modform = $CFG->dirroot."/mod/$module->name/mod.html";
    if (file_exists($modform)) {

        if ($usehtmleditor = can_use_html_editor()) {
            $defaultformat = FORMAT_HTML;
            $editorfields = '';
        } else {
            $defaultformat = FORMAT_MOODLE;
        }

        $icon = '<img class="icon" src="'.$CFG->modpixpath.'/'.$module->name.'/icon.gif" alt="'.get_string('modulename',$module->name).'"/>';

        print_heading_with_help("Create & Schedule Your Sclipo Web Classes", "mods", $module->name, $icon);
        print_simple_box_start('center', '', '', 5, 'generalbox', $module->name);

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
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/jquery.charcounter.js";?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/ui.core.js";?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/ui.datepicker.js";?>"></script>
<link href="<?php echo $CFG->wwwroot."/mod/sclipowebclass/css/facebox.css"; ?>" media="screen" rel="stylesheet" type="text/css"/>
<link href="<?php echo $CFG->wwwroot."/mod/sclipowebclass/css/ui.datepicker.css"; ?>" media="screen" rel="stylesheet" type="text/css"/>
<link href="<?php echo $CFG->wwwroot."/mod/sclipowebclass/css/ui.theme.css"; ?>" media="screen" rel="stylesheet" type="text/css"/>
<link href="<?php echo $CFG->wwwroot."/mod/sclipowebclass/css/ui.core.css"; ?>" media="screen" rel="stylesheet" type="text/css"/>
<script src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/facebox.js"; ?>" type="text/javascript"></script>
<script type="text/javascript">

jQuery(document).ready(function($) {
  $('a[rel*=facebox]').facebox();

  var webclass_start = new Date(2009,1,27); // new Date(year, month-1,day)
  var minDate = new Date(); // today
  var currentYear = minDate.getFullYear();
  if (webclass_start - minDate > 0) minDate = webclass_start;
  $('#start_date').datepicker({minDate:minDate, maxDate: '+1y'});

  $("#description:visible").charCounter(1000, {
            container: "<p></p>",
            classname: "note",
            format: "%1/1000",
            pulse: false,
            delay: 100
        });
})



</script>
<script type="text/javascript">
function scheduleNow()
{
		$.get("<?php echo $CFG->wwwroot.'/mod/sclipowebclass/sclipoapi.php?do=getcurrentdateandtime&id='.$_SESSION["sclipo_id"].'&moodleuser='.$USER->username; ?>&_=" + (+new Date()), function(data) {
		var temp = new Array();
		temp = data.split(' ');	// Get rid of the <xml> tag
		document.getElementById("start_date").value=temp[0];
		document.getElementById("timeEntry").value=temp[1];
	}, "json");	/*
		var now = new Date();
		var hour        = now.getHours();
		var minute      = now.getMinutes();

		var monthnumber = now.getMonth();
		var monthday    = now.getDate();
		var year        = now.getYear();
		if(year < 2000) { year = year + 1900; }
		monthnumber = monthnumber + 1;
		if (monthnumber < 10)
			monthnumber = "0"+monthnumber;
		if (hour < 10)
			hour = "0"+hour;
		if (minute < 10)
			minute = "0"+minute;
		document.getElementById("start_date").value=monthnumber+"/"+monthday+"/"+year;
		document.getElementById("timeEntry").value=hour+":"+minute;*/

}

function isDate (month, day, year)
{

	if (isNaN(year) || year > 2500 || year < 1900)
		return false;
	if (isNaN(month) || month > 12 || month < 0)
		return false;
	if (isNaN(day) || day > 31 || day < 0)
		return false;
	return true;
}

function isTime (intHour, intMinute)
{

	if (isNaN(intHour) || intHour > 23 || intHour < 0)
		return false;
	if (isNaN(intMinute) || intMinute > 59 || intMinute < 0)
		return false;
	return true;
}


function validateForm(form)
{
	var ret = true;
	document.getElementById("errtitle").style.display="none";
	document.getElementById("errdescription").style.display="none";
	document.getElementById("errtags").style.display="none";
	document.getElementById("errstart_date").style.display="none";
	document.getElementById("errduration").style.display="none";
	document.getElementById("errmax_students").style.display="none";
	document.getElementById("errconfirm_your_tz").style.display="none";

	var timeFormat = form.timeEntry.value.split(":");
	var dateFormat = form.start_date.value.split("/");

		if (form.title.value == '') {
			document.getElementById("errtitle").style.display="inline";
			location.href="#top";
			ret = false;
		}
		if (form.description.value == '') {
			document.getElementById("errdescription").style.display="inline";
			location.href="#top";
			ret = false;
		}
		if (form.tags.value.indexOf(' ') == -1) {
			document.getElementById("errtags").style.display="inline";
			location.href="#top";
			ret = false;
		}
		if (form.start_date.value == '' || !isDate(dateFormat[0],dateFormat[1],dateFormat[2]) || !isTime(timeFormat[0],timeFormat[1])) {
			document.getElementById("errstart_date").style.display="inline";
			location.href="#top";
			ret = false;
		}
		if (form.duration.value == '' || isNaN(form.duration.value)) {
			document.getElementById("errduration").style.display="inline";
			location.href="#top";
			ret = false;
		}
		if (form.max_students.value == '' || isNaN(form.max_students.value) || form.max_students.value>99) {
			document.getElementById("errmax_students").style.display="inline";
			location.href="#top";
			ret = false;
		}
		if (form.confirm_your_tz.value == '') {
			document.getElementById("errconfirm_your_tz").style.display="inline";
			location.href="#top";
			ret = false;
		}

		return ret;
}

function changeTimeZone()
{
	var zone = $(".tzones:last").attr("value");

	$.get("<?php echo $CFG->wwwroot.'/mod/sclipowebclass/sclipoapi.php?do=settimezone&id='.$_SESSION["sclipo_id"].'&moodleuser='.$USER->username.'&zone='; ?>"+zone, function(data) {
		var temp = new Array();
		temp = data.split('>');	// Get rid of the <xml> tag
		document.getElementById("current_datetime").innerHTML=temp[1];
		document.getElementById("confirm_your_tz").value = "1";
		$("#span-change-tz").css("display","inline");
		$("#span-confirm-tz").css("display","none");
		var foo = new Array();
		foo = temp[1].split(" ");
		$(".change_my_time_zone_timezone").html(foo[2]);
		$(".change_my_time_zone_time").html(foo[1]);
		$(".tzones option:selected").removeAttr("selected");
		$('#new_tz').val(foo[2]);
	});
}

function confirmTimeZone()
{
	$.get("<?php echo $CFG->wwwroot.'/mod/sclipowebclass/sclipoapi.php?do=confirmtimezone&id='.$_SESSION["sclipo_id"].'&moodleuser='.$USER->username; ?>", function(data) {
		document.getElementById("confirm_your_tz").value = "1";
		$("#span-change-tz").css("display","none");
		$("#span-confirm-tz").css("display","inline");
	});
}

function load_tz() {
	$.facebox( { div: '#change_my_time_zone' } );
	$('#facebox .tzones option[value="'+$('#new_tz').val()+'"]').attr('selected', 'selected');
}
</script>
<link media="screen" type="text/css" rel="stylesheet" href="<?php echo $cssfile."main.css"; ?>">
</link>
<link media="screen" type="text/css" rel="stylesheet" href="<?php echo $cssfile."forms.css"; ?>">
</link>
<link media="screen" type="text/css" rel="stylesheet" href="<?php echo $cssfile."rich_calendar.css"; ?>">
</link>

<a name="top"></a>
<form method="post" class="standard" action="<?php echo $CFG->wwwroot.'/mod/sclipowebclass/create_webclass.php'; ?>" id="create-webclass" onsubmit="return validateForm(this);">
            <input type="hidden" name="submitted" value="TRUE" />
			<input type="hidden" name="course"        value="<?php   echo $form->course; ?>" />
			<input type="hidden" name="sesskey"     value="<?php  echo $form->sesskey; ?>" />
			<input type="hidden" name="coursemodule"  value="<?php  echo $form->coursemodule; ?>" />
			<input type="hidden" name="section"       value="<?php  echo $form->section; ?>" />
			<input type="hidden" name="module"        value="<?php  echo $form->module; ?>" />
			<input type="hidden" name="modulename"    value="<?php  echo $form->modulename; ?>" />
			<input type="hidden" name="instance"      value="<?php  echo $form->instance; ?>" />
			<input type="hidden" name="mode"          value="<?php  echo $form->mode; ?>" />
			<input type="hidden" id="new_tz"        value="" />

			<table>
			<tr valign="top">
			<td style="width: 70%;">
            <div id="main-extended">

                <div>
					<ul>
						<li id="errtitle" style="display: none;" ><label class="error" >Please enter a title.</label></li>
						<li id="errdescription" style="display: none;" ><label class="error" >Please enter a description.</label></li>
                        <li style="display: none;" id="errtags"><label  class="error">Please enter at least two tags.</label></li>
                        <li style="display: none;" id="errstart_date"><label class="error">Please select the start time of your web class.</label></li>
                        <li style="display: none;" id="errduration"><label class="error">Please define the live web class duration.</label></li>
						<li style="display: none;" id="errmax_students"><label class="error">Please define the maximum number of students (max. 100).</label></li>
						<li style="display: none;" id="errconfirm_your_tz"><label class="error">You must confirm your time zone before continuing.</label></li>
						<!--li><label for="registration" class="error">Please select the type of web class privacy.</label></li-->
						<li>&nbsp;</li>
					</ul>
				</div>
                    <fieldset>

                        <label for="title">תאור המפגש <em>*</em></label>
                        <p style="margin-bottom: 0px ! important; padding-top: 5px;">A good title helps understand what the Web Class is about.</p>
                        <input style="background-color: #FFFFFF"; type="text" value="" class="required" id="title" name="title"/>
                                                                        <label for="description">תאור (1000 תווים) <em>*</em></label>
                        <p style="margin-bottom: 0px ! important; padding-top: 5px;">Keep your description clear and concise.</p>
                        <textarea style="background-color: #FFFFFF"; class="expanding" cols="40" id="description" name="description" style="overflow: hidden; min-height: 80px; display: block;"></textarea>

						<label for="tags">Tags <em>*</em> (Write at least 2 tags, space separated)</label>
						<p style="margin-bottom: 0px ! important; padding-top: 5px;">Tags are keywords that help understand and find the Course.</p>
                        <input style="background-color: #FFFFFF"; type="text" value="" class="required {maxlength: 255}" id="tags" name="tags" maxlength="255"/>


                		<p class="clear"/>
                        <input type="text" value="<?php if ($confirmed) echo '1'; else echo '0'; ?>" style="display: none;" class="required" id="confirm_your_tz" name="confirm_your_tz"/>


						<label for="method_face_date">Starting Date & Time<em>*</em></label>
						<p for="time_zone" style="margin-bottom: 0px ! important; padding-top: 5px;">Your Time Zone:

						<span id="current_datetime"><?php echo $date; ?></span>.
						<span style="<?php if (!$confirmed) echo 'display: inline;'; else echo 'display: none;';?> border: medium double ; padding: 2px; color: rgb(255, 255, 255); background-color: rgb(204, 0, 0); clear: none; font-size: 12px; font-weight: bold;" id="span-confirm-tz">
							<a style="color: rgb(255, 255, 255); text-decoration: none;" rel="facebox" href="#change_my_time_zone">Confirm Your Time Zone</a>
						</span>
						<span style="<?php if (!$confirmed) echo 'display: none;'; else echo 'display: inline;';?> padding: 2px; color: rgb(255, 255, 255); background-color: rgb(192, 192, 192); clear: none;" id="span-change-tz">
							<a style="color: rgb(255, 255, 255); text-decoration: none;"  href="#change_my_time_zone" onclick="load_tz();">Change Your Time Zone</a>
						</span>
						</p>

						<a name="zone"></a>
						<div id="change_my_time_zone" style="background-color: #D4D4D4; display: none; margin-top: 10px; padding: 10px;">
							<h2>Your Time Zone <a href="#" class="back" style="float: right;" onclick="$(document).trigger('close.facebox'); return false;"> close</a></h2>
							<div id="select_tz" style="padding: 10px;">
							Your time zone is <span class="change_my_time_zone_timezone"><?php echo $timezone; ?></span>, where the current time is <span class="change_my_time_zone_time"><?php echo $cur_time; ?></span>.<br />
							<br />
							Select your time zone:<br>
							<select name="tzones" class="tzones">
								<option value="Asia/Almaty" <?php if ($timezone == "Asia/Almaty") echo "SELECTED"; ?>>Almaty - [GMT +6]</option>
								<option value="America/Anchorage" <?php if ($timezone == "America/Anchorage") echo "SELECTED"; ?>>Anchorage - [GMT -9]</option>
								<option value="Europe/Istanbul" <?php if ($timezone == "Europe/Istanbul") echo "SELECTED"; ?>>Ankara - [GMT +2]</option>
								<option value="Asia/Ashgabat" <?php if ($timezone == "Asia/Ashgabat") echo "SELECTED"; ?>>Ashgabad - [GMT +5]</option>
								<option value="Atlantic/Azores" <?php if ($timezone == "Atlantic/Azores") echo "SELECTED"; ?>>Azores - [GMT -1]</option>
								<option value="Asia/Baghdad" <?php if ($timezone == "Asia/Baghdad") echo "SELECTED"; ?>>Bagdad - [GMT +3]</option>
								<option value="Asia/Bangkok" <?php if ($timezone == "Asia/Bangkok") echo "SELECTED"; ?>>Bangkok - [GMT +7]</option>
								<option value="Asia/Hong_Kong" <?php if ($timezone == "Asia/Hong_Kong") echo "SELECTED"; ?>>Beijing - [GMT +8]</option>
								<option value="Europe/Berlin" <?php if ($timezone == "Europe/Berlin") echo "SELECTED"; ?>>Berlin - [GMT +1]</option>
								<option value="America/Buenos_Aires" <?php if ($timezone == "America/Buenos_Aires") echo "SELECTED"; ?>>Buenos Aires - [GMT -3]</option>
								<option value="Atlantic/Cape_Verde" <?php if ($timezone == "Atlantic/Cape_Verde") echo "SELECTED"; ?>>Cabo Verde - [GMT -1]</option>
								<option value="Africa/Cairo" <?php if ($timezone == "Africa/Cairo") echo "SELECTED"; ?>>Cairo - [GMT +2]</option>
								<option value="Asia/Calcutta" <?php if ($timezone == "Asia/Calcutta") echo "SELECTED"; ?>>Calcuta - [GMT +5.5]</option>
								<option value="America/Caracas" <?php if ($timezone == "America/Caracas") echo "SELECTED"; ?>>Caracas - [GMT -4.5]</option>
								<option value="America/Chicago" <?php if ($timezone == "America/Chicago") echo "SELECTED"; ?>>Chicago - [GMT -6]</option>
								<option value="Africa/Dakar" <?php if ($timezone == "Africa/Dakar") echo "SELECTED"; ?>>Dakar - [GMT]</option>
								<option value="Pacific/Galapagos" <?php if ($timezone == "Pacific/Galapagos") echo "SELECTED"; ?>>Darwin - [GMT -6]</option>
								<option value="America/Denver" <?php if ($timezone == "America/Denver") echo "SELECTED"; ?>>Denver - [GMT -7]</option>
								<option value="Europe/Istanbul" <?php if ($timezone == "Europe/Istanbul") echo "SELECTED"; ?>>Estambul - [GMT +2]</option>
								<option value="Europe/Stockholm" <?php if ($timezone == "Europe/Stockholm") echo "SELECTED"; ?>>Estocolmo - [GMT +1]</option>
								<option value="America/Anchorage" <?php if ($timezone == "America/Anchorage") echo "SELECTED"; ?>>Fairbanks - [GMT -9]</option>
								<option value="Europe/Helsinki" <?php if ($timezone == "Europe/Helsinki") echo "SELECTED"; ?>>Helsinki - [GMT +2]</option>
								<option value="Asia/Hong_Kong" <?php if ($timezone == "Asia/Hong_Kong") echo "SELECTED"; ?>>Hongkong - [GMT +8]</option>
								<option value="Pacific/Honolulu" <?php if ($timezone == "Pacific/Honolulu") echo "SELECTED"; ?>>Honolulu - [GMT -10]</option>
								<option value="Atlantic/Canary" <?php if ($timezone == "Atlantic/Canary") echo "SELECTED"; ?>>Islas Canarias - [GMT]</option>
								<option value="Asia/Jakarta" <?php if ($timezone == "Asia/Jakarta") echo "SELECTED"; ?>>Jakarta - [GMT +7]</option>
								<option value="Africa/Johannesburg" <?php if ($timezone == "Africa/Johannesburg") echo "SELECTED"; ?>>Johannesburg - [GMT +2]</option>
								<option value="Asia/Karachi" <?php if ($timezone == "Asia/Karachi") echo "SELECTED"; ?>>Karachi - [GMT +5]</option>
								<option value="Europe/Kiev" <?php if ($timezone == "Europe/Kiev") echo "SELECTED"; ?>>Kiev - [GMT +2]</option>
								<option value="America/La_Paz" <?php if ($timezone == "America/La_Paz") echo "SELECTED"; ?>>La Paz - [GMT -4]</option>
								<option value="Europe/Lisbon" <?php if ($timezone == "Europe/Lisbon") echo "SELECTED"; ?>>Lisboa - [GMT]</option>
								<option value="Europe/London" <?php if ($timezone == "Europe/London") echo "SELECTED"; ?>>Londres - [GMT]</option>
								<option value="America/Los_Angeles" <?php if ($timezone == "America/Los_Angeles") echo "SELECTED"; ?>>Los Angeles - [GMT -8]</option>
								<option value="Europe/Madrid" <?php if ($timezone == "Europe/Madrid") echo "SELECTED"; ?>>Madrid - [GMT +1]</option>
								<option value="Indian/Mauritius" <?php if ($timezone == "Indian/Mauritius") echo "SELECTED"; ?>>Mauritius - [GMT +4]</option>
								<option value="Australia/Melbourne" <?php if ($timezone == "Australia/Melbourne") echo "SELECTED"; ?>>Melbourne - [GMT +11]</option>
								<option value="America/Mexico_City" <?php if ($timezone == "America/Mexico_City") echo "SELECTED"; ?>>Mexico DF - [GMT -6]</option>
								<option value="America/Montreal" <?php if ($timezone == "America/Montreal") echo "SELECTED"; ?>>Montreal - [GMT -5]</option>
								<option value="Europe/Moscow" <?php if ($timezone == "Europe/Moscow") echo "SELECTED"; ?>>Moscu - [GMT +3]</option>
								<option value="Africa/Nairobi" <?php if ($timezone == "Africa/Nairobi") echo "SELECTED"; ?>>Nairobi - [GMT +3]</option>
								<option value="America/New_York" <?php if ($timezone == "America/New_York") echo "SELECTED"; ?>>New York - [GMT -5]</option>
								<option value="Pacific/Noumea" <?php if ($timezone == "Pacific/Noumea") echo "SELECTED"; ?>>New-Caledonia - [GMT +11]</option>
								<option value="Asia/Novosibirsk" <?php if ($timezone == "Asia/Novosibirsk") echo "SELECTED"; ?>>Novosibirsk - [GMT +6]</option>
								<option value="Asia/Calcutta" <?php if ($timezone == "Asia/Calcutta") echo "SELECTED"; ?>>Nueva Delhi - [GMT +5.5]</option>
								<option value="Europe/Paris" <?php if ($timezone == "Europe/Paris") echo "SELECTED"; ?>>Paris - [GMT +1]</option>
								<option value="Australia/Perth" <?php if ($timezone == "Australia/Perth") echo "SELECTED"; ?>>Perth - [GMT +9]</option>
								<option value="Atlantic/Reykjavik" <?php if ($timezone == "Atlantic/Reykjavik") echo "SELECTED"; ?>>Reykjavik - [GMT]</option>
								<option value="America/Bahia" <?php if ($timezone == "America/Bahia") echo "SELECTED"; ?>>Rio de Janeiro - [GMT -3]</option>
								<option value="Europe/Rome" <?php if ($timezone == "Europe/Rome") echo "SELECTED"; ?>>Roma - [GMT +1]</option>
								<option value="America/Los_Angeles" <?php if ($timezone == "America/Los_Angeles") echo "SELECTED"; ?>>San Francisco - [GMT -8]</option>
								<option value="America/Santiago" <?php if ($timezone == "America/Santiago") echo "SELECTED"; ?>>Santiago de Chile - [GMT -3]</option>
								<option value="Asia/Seoul" <?php if ($timezone == "Asia/Seoul") echo "SELECTED"; ?>>Seul - [GMT +9]</option>
								<option value="Asia/Singapore" <?php if ($timezone == "Asia/Singapore") echo "SELECTED"; ?>>Singapore - [GMT +8]</option>
								<option value="Australia/Sydney" <?php if ($timezone == "Australia/Sydney") echo "SELECTED"; ?>>Sydney - [GMT +11]</option>
								<option value="Asia/Tokyo" <?php if ($timezone == "Asia/Tokyo") echo "SELECTED"; ?>>Tokyo - [GMT +9]</option>
								<option value="Africa/Tripoli" <?php if ($timezone == "Africa/Tripoli") echo "SELECTED"; ?>>Tripoli - [GMT +2]</option>
								<option value="Africa/Tunis" <?php if ($timezone == "Africa/Tunis") echo "SELECTED"; ?>>Tunez - [GMT +1]</option>
								<option value="Asia/Vladivostok" <?php if ($timezone == "Asia/Vladivostok") echo "SELECTED"; ?>>Vladivostok - [GMT +10]</option>
								<option value="America/New_York" <?php if ($timezone == "America/New_York") echo "SELECTED"; ?>>Washington - [GMT -5]</option>
								<option value="Pacific/Auckland" <?php if ($timezone == "Pacific/Auckland") echo "SELECTED"; ?>>Wellington - [GMT +13]</option>
								<option value="Pacific/Samoa" <?php if ($timezone == "Pacific/Samoa") echo "SELECTED"; ?>>Westsamoa - [GMT -11]</option>
								<option value="Africa/Windhoek" <?php if ($timezone == "Africa/Windhoek") echo "SELECTED"; ?>>Windhoek - [GMT +2]</option>
							</select>
							<br />
							<input type="button" onclick="changeTimeZone(); $(document).trigger('close.facebox');" id="changetz" value="Save time zone" />
							</div>
							<div id="saved_ok" style="padding: 10px; text-align: center; font-size: 14px; font-weight: bold; display: none;">
							Your Time Zone has been updated.
							</div>
						</div>

						<br/>



						<input style="background-color: #FFFFFF;" readonly="readonly" type="text" id="start_date" class="date required" name="method_face_date" />    starts at

						<span class="timeEntry_wrap"><input value="13:00" style="vertical-align: baseline; width: 45px; background-color: #FFFFFF;" size="5" name="timeEntry" id="timeEntry"/></span>
						<span class="subtitle" id="schedule-now"> <a class="wc_start_now" href="#" onclick="scheduleNow();">Schedule for right now!</a></span>
						<label for="duration">Duration: <em>*</em></label>
                        <input style="background-color: #FFFFFF"; type="text" value="30" class="number required {min:5,max: 300}" id="duration" name="duration"/> minutes

						 <label for="max_students">Number of Students (maximum is 100) <em>*</em></label>
                        <p style="margin-bottom: 0px ! important; padding-top: 5px;">Indicate how many students can attend this live web class..</p>
                        <input style="background-color: #FFFFFF"; type="text" value="" class="number required {max: 100}" id="max_students" name="max_students"/>


						<br /><br />
						<input id="submit" type="submit" value="Create & Schedule Web Class" />
						</fieldset>
						<div style="padding-left: 100px;">
						<img src="<?php echo $scimg;?>orange-info-icon.png" />
						<span style="font-weight: bold;">Present Documents During Your Web Class</span>
						<br />
						Show one or many documents in all common formats (PDFs, Open Office, MS
Office, plain text, ...) during this live web class.
You can add your documents after creating this web class.

						</div>
						</div>

                    	<br />


            <!-- #main-extended -->
            </div>
            </td>
			<td>
			<br /><br /><br /><br />
			<div style="background-color: #D4D4D4; width: 200px; padding: 10px;">
						<input type="checkbox" name="publicwebclass" value="public" style="font-weight: bold;"><span style="font-weight: bold;"> Make Web Web Class Public</span></input><br /><br />
						Mark this option if you allow anybody to participate in this class. Public
web classes are listed in Sclipo's web class directory and <span style="color:#FF671C;">your Sclipo
Academy</span>. Your web class can also be found in public search engines like
Google.

						</div>

			</td>
			</tr></table>



        </form>
<?php


		print_simple_box_end();

        if ($usehtmleditor and empty($nohtmleditorneeded)) {
            use_html_editor($editorfields);
        }

    } else {
        notice("This module cannot be added to this course yet! (No file found at: $modform)", "$CFG->wwwroot/course/view.php?id=$course->id");
    }


?>

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