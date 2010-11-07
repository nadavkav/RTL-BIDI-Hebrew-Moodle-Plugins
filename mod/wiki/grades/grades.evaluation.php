<?php

/**
 * Page that reports the grades of a wiki.
 *
 * @author Javier 
 * @author Gonzalo Serrano
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: grades.evaluation.php,v 1.9 2008/06/07 19:10:32 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

    require_once('../../../config.php'); 

    global $USER, $CFG;

    // internal grades lib
    require_once($CFG->dirroot.'/mod/wiki/grades/grades.lib.php');

    //html functions
    require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

    $cid      = required_param('cid', PARAM_INT); // Course Id
    $cmid     = required_param('cmid', PARAM_INT); // Course Module Id
    $selecteduser = optional_param('user', 0, PARAM_INT); // Selected user
    $selectedgroup = optional_param('group', 0, PARAM_INT); // Selected group

    require_login($cid);

/*
 *print_object ("selecteduser={$selecteduser}\n"); 
 *print_object ("selectedgroup={$selectedgroup}\n");
 */

//only for teachers and admins:
$context = get_context_instance(CONTEXT_MODULE, $cmid);
require_capability('mod/wiki:mainreview', $context);

//check Moodle version(higher or equal than 1.8)
if($CFG->version < 2007021534){
    error("Error: WikiGrades only for Moodle 1.9 version or higher.");
}

if (! $course = get_record("course", "id", $cid)) {
      error("Course ID is incorrect");
}

if (!isset($uid)) { $uid = $USER->id; }


// Wiki of the course module
if (! $wikimain = get_record_sql('SELECT *
                                  FROM '.$CFG->prefix.'wiki w, '.$CFG->prefix.'course_modules cm
                                  WHERE w.course=cm.course AND cm.course='.$cid.' AND cm.instance=w.id AND cm.id='.$cmid.''))
{ error('grades.evaluation: There isn\'t any wiki'); }



$strwikis = get_string("modulenameplural", 'wiki');

//Print header
$navlinks[] = array('name' => $strwikis, 'link' => "{$CFG->wwwroot}/mod/wiki/index.php?id={$course->id}", 'type' => 'misc');
$navlinks[] = array('name' => $wikimain->name, 'link' => "{$CFG->wwwroot}/mod/wiki/view.php?id=$cmid", 'type' => 'misc');
$navlinks[] = array('name' => get_string('eval_reports', 'wiki'), 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);
print_header($course->shortname .': '. get_string('eval_reports', 'wiki'), $course->fullname, $navigation);

$cmodule = get_record('course_modules', 'id', $cmid); // course module instance

$scale = get_record('scale', 'id', (int)$wikimain->notetype); // Instance scale

$wiki = get_record('wiki', 'id', $wikimain->instance); // Instance wiki

///////////// MAKE A SELECTORS STUDENT - GROUP - EVERYBODY

    /// Setup for group handling.
    if ($cmodule->groupmode == 0 ) {
        $showgroups = false;
    }
    //else if ($cmodule->groupmode = 1 || $cmodule->groupmode = 2) {
    else if ($cmodule->groupmode == 1 || $cmodule->groupmode == 2) {
        $showgroups = true;
    }

    // We're interested in all our site users
    if ($selectedgroup != 0) {   // If using a group, only get users in that group.
        $courseusers = get_group_users($selectedgroup, 'u.lastname ASC', '', 'u.id, u.firstname, u.lastname, u.idnumber'); 
        //print_object("Porgrupo");
    } else {
        $courseusers = get_course_users($course->id, 'u.lastname ASC', '', 'u.id, u.firstname, u.lastname, u.idnumber'); 
        //print_object("Porcurso");
    }

    // Get all the possible users
    $users = array();

    if ($courseusers) {
        foreach ($courseusers as $courseuser) {
            $users[$courseuser->id] = fullname($courseuser, has_capability('moodle/site:viewfullnames', $context));
        }
    }


///////////////////////////////////////////////////////////////////////
///////////////// POST-EVALUATION (GRADEBOOK) /////////////////////////
///////////////////////////////////////////////////////////////////////

if ( optional_param('gradebook',NULL,PARAM_ALPHA) === get_string('set','wiki')  )
{
    $evaluateduser = optional_param('select_users_grades', 0, PARAM_INT);
    $evaluatedgrade = optional_param('grade_evaluation_page', 0,PARAM_INT);

    $scl = explode(',', $scale->scale); 

    $itemnumber = 0; 

    if (wiki_grade_item_exist($course->id, "$wiki->name", $wiki->id)) // exist the grade_item for the page wiki
    {

        $grademax = count($scl); 
        $grademin = (count($scl)>0?1.0:0.0);

        $grades = array();
        $grades['userid'] = $evaluateduser; // Valuated user/s 
        $grades['rawgrade'] = (float)$evaluatedgrade;
        $grades['finalgrade'] = (float)$evaluatedgrade;
        $grades['usermodified'] = $USER->id;

        grade_update('mod/wiki', $course->id, 'mod', 'wiki', $wiki->id, $itemnumber, $grades);

    }
    else // Don't exist the grade item -> Don't exist the grade
    {
        $itemdetails = array();
        $itemdetails['itemname'] = "$wiki->name";
        $itemdetails['idnumber'] = 0;
        $itemdetails['gradetype'] = 2;

        $grademax = count($scl); 
        $grademin = (count($scl)>0?1.0:0.0);
                    
        $itemdetails['grademax'] = (float)$grademax;
        $itemdetails['grademin'] = (float)$grademin;
        $itemdetails['scaleid'] = $scale->id;
        //$itemdetails['deleted'] = 0;
 
        $grades = array();
        $grades['userid'] = $evaluateduser; // Valuated user/s 
        $grades['rawgrade'] = (float)$evaluatedgrade;
        $grades['rawgrademax'] = $itemdetails['grademax'];
        $grades['rawgrademin'] = $itemdetails['grademin'];
        $grades['rawscaleid'] = $itemdetails['scaleid'];
        $grades['finalgrade'] = (float)$evaluatedgrade;
        $grades['usermodified'] = $USER->id;

        grade_update('mod/wiki', $course->id, 'mod', 'wiki', $wiki->id, $itemnumber, $grades, $itemdetails);
    }

    unset($prop);
    $prop->style  = "text-align: center; color:green;";
    wiki_paragraph(get_string('eval_the_user', 'wiki')."\"{$users[$evaluateduser]}\"".
                   get_string('eval_with_the_grade','wiki').'"'.trim($scl[$evaluatedgrade-1]).'"',
                   $prop);
    unset($prop);
    $prop->style  = "text-align: center;";
    wiki_paragraph('&nbsp;'.get_string('check').
                   '&nbsp;<a href="'."$CFG->wwwroot".'/grade/report/index.php?id='."$cid".'">'.
                    strtolower(get_string('coursegrades')).'</a>', $prop
                    );

}

wiki_br();

print_box_start();

echo ('<h2 class="headingblock header outline">');

    unset($prop);
    $prop->class = "nwikileft";
    wiki_table_start($prop);

// TO PRINT MENU USER AND GROUP (OR EVERYTHING OF THIS WIKI)

    wiki_b(get_string('eval_reports', 'wiki'));

    wiki_b('&nbsp;&nbsp;-&nbsp;&nbsp;');

    wiki_change_column();

    if ($showgroups) {
        if ($cgroups = groups_get_all_groups($course->id)) {
            foreach ($cgroups as $cgroup) {
                $groups[$cgroup->id] = $cgroup->name;
            }
        }
        else {
            $groups = array();
        }

        $opt = '';
        unset($prop);
        $prop -> value = "{$CFG->wwwroot}/mod/wiki/grades/grades.evaluation.php?cid={$cid}&amp;cmid={$cmid}&amp;";
        $opt = wiki_option('<i>'.get_string("allgroups").'</i>', $prop, true);

        foreach ($groups as $key => $gname)
        {
            unset($prop);
            $prop -> value = "{$CFG->wwwroot}/mod/wiki/grades/grades.evaluation.php?cid={$cid}&amp;cmid={$cmid}&amp;group={$key}";
            if ($selectedgroup == $key) { $prop->selected = 'selected'; } 
            $opt .= wiki_option($gname, $prop, true);

        }

        unset($prop);
        $prop -> id = "select_groups";
        $prop -> events = "onchange=\"if (document.getElementById('select_groups').selectedIndex >= 0 ) {self.location=document.getElementById('select_groups').options[document.getElementById('select_groups').selectedIndex].value;}\"";
        wiki_select($opt,$prop);


        wiki_b('&nbsp;&nbsp;-&nbsp;&nbsp;');
    }

/// PRINT THE SELECT OF THE USERS

//print_object($user);

    if (isset($evaluateduser))
        $selecteduser = $evaluateduser;

    $key = null;
    if (isset($selecteduser)) {

        $opt = '';

        unset($prop);
        $prop -> value = "{$CFG->wwwroot}/mod/wiki/grades/grades.evaluation.php?cid={$cid}&amp;cmid={$cmid}&amp;group={$selectedgroup}";
        $opt = wiki_option('<i>'.get_string("allparticipants").'</i>', $prop, true);

        foreach ($users as $key => $user)
        {
            unset($prop);
            $prop -> value = "{$CFG->wwwroot}/mod/wiki/grades/grades.evaluation.php?cid={$cid}&amp;cmid={$cmid}&amp;group={$selectedgroup}&amp;user={$key}";
            if ($selecteduser == $key) { $prop -> selected = true; }
            $opt .= wiki_option($user, $prop, true);

        }

        unset($prop);
        $prop -> id = "select_users";
        $prop -> events = "onchange=\"if (document.getElementById('select_users').selectedIndex >= 0 ) {self.location=document.getElementById('select_users').options[document.getElementById('select_users').selectedIndex].value;}\"";
        wiki_select($opt,$prop);


        wiki_b('&nbsp;&nbsp;-&nbsp;&nbsp;');
    }

    unset($prop);
    $prop -> value = "{$CFG->wwwroot}/mod/wiki/grades/grades.evaluation.php?cid={$cid}&amp;cmid={$cmid}";
    $prop -> id = 'start';
    wiki_input_hidden($prop);

    unset($prop);
    $prop->name = 'all_wiki_pages';
    $prop->value = get_string('eval_all','wiki');
    $prop->events = "onclick=\"self.location=document.getElementById('start').value\"";
    wiki_input_button($prop);
 
    wiki_table_end();

//wiki_div_end();
echo('</h2>');

//// USER INFO
if (isset($selecteduser) && $selecteduser != 0) {
    echo ('<br/><h3 class="headingblock header outline">'.get_string('eval_user_info', 'wiki').'</h3><br/>');

    $grade = grade_get_grades($cid, 'mod', 'wiki', $wikimain->instance, $selecteduser);
    if (isset($grade->items[0]->grades[$selecteduser]))
        $grade = $grade->items[0]->grades[$selecteduser]->str_grade;
    else
        $grade = null;

    wiki_grade_print_user_info($selecteduser, $grade);
} else {
    echo ('<br/><h3 class="headingblock header outline">'.get_string('eval_users_info', 'wiki').'</h3><br/>');
    echo (wiki_grade_get_users_info($cid, $wiki->id));
}


////////// PRINT TABLE WIKI PAGES AND EDITIONS /////////////////////////////////////////////////////////////////
wiki_grade_print_tables($selectedgroup, $selecteduser, $wiki, $cmodule, $scale, $course);

///////// SECTION TO VALUE THE USERS //////////////////////////////////////////////////////////////////////////

echo('<h3 class="headingblock header outline">'.get_string('eval_user', 'wiki').'</h3><br/>');

unset($prop);
$prop->class = "box boxaligncenter";
wiki_div_start($prop);

if (count($users) > 0)
{
    unset($prop);
    $prop->id = "form_gradebook";
    $prop->method = "post";
    $prop->action = "{$CFG->wwwroot}/mod/wiki/grades/grades.evaluation.php?cid={$cid}&amp;cmid={$cmid}&amp;group={$selectedgroup}&amp;user={$key}";
    wiki_form_start($prop);

    wiki_b(get_string('user').':');
    
    if (isset($selecteduser)) 
    {
        $opt = '';

        foreach ($users as $key => $user)
        {
            unset($prop);
            $prop -> value = "$key";
            $prop -> name = "$user";
            if ($selecteduser == $key) { $prop->selected = 'selected'; }
            $opt .= wiki_option($user, $prop, true);
        }

        unset($prop);
        $prop -> id = "select_users_grades";
        $prop -> name = $prop -> id;  
        wiki_select($opt,$prop);
    }

    $scale = wiki_grade_scale_box($scale);

    if ($scale) {
        unset($prop);
        $prop->name = 'gradebook';
        $prop->value = get_string('set','wiki');
        wiki_input_submit($prop); 
    }

    wiki_form_end();
    wiki_div_end();

    if (!$scale) {
        echo('<br/>');
        wiki_div_start();
        echo('grades.lib.php: setting a wiki evaluation type and defining a scale is needed before value any user.');
        wiki_div_end();
    }
} else
    echo('grades.lib.php: you can\'t value any users because there is not any user in this course');

/// Finish the page
print_box_end();
print_footer($course);

/*
///////////// MAKE A SELECTORS STUDENT - GROUP - EVERYBODY

    /// Setup for group handling.
    if ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
        $selectedgroup = get_current_group($course->id);
        $showgroups = false;
    }
    else if ($course->groupmode) {
        $selectedgroup = ($selectedgroup == -1) ? get_current_group($course->id) : $selectedgroup;
        $showgroups = true;
    }
    else {
        $selectedgroup = 0;
        $showgroups = false;
    }

    // Get all the possible users
    $users = array();

    // If looking at a different host, we're interested in all our site users
    if ($hostid == $CFG->mnet_localhost_id && $course->id != SITEID) {
        if ($selectedgroup) {   // If using a group, only get users in that group.
            $courseusers = get_group_users($selectedgroup, 'u.lastname ASC', '', 'u.id, u.firstname, u.lastname, u.idnumber');
        } else {
            $courseusers = get_course_users($course->id, '', '', 'u.id, u.firstname, u.lastname, u.idnumber');
        }
    } else {
        $courseusers = get_site_users("u.lastaccess DESC", "u.id, u.firstname, u.lastname, u.idnumber");
    }

    if ($showusers) {
        if ($courseusers) {
            foreach ($courseusers as $courseuser) {
                $users[$courseuser->id] = fullname($courseuser, has_capability('moodle/site:viewfullnames', $context));
            }
        }
        if ($guest = get_guest()) {
            $users[$guest->id] = fullname($guest);
        }
    }


// TO PRINT MENU USER AND GROUP
    if ($showgroups) {
        if ($cgroups = groups_get_all_groups($course->id)) {
            foreach ($cgroups as $cgroup) {
                $groups[$cgroup->id] = $cgroup->name;
            }
        }
        else {
            $groups = array();
        }
        choose_from_menu ($groups, "group", $selectedgroup, get_string("allgroups") );
    }

    if ($showusers) {
        choose_from_menu ($users, "user", $selecteduser, get_string("allparticipants") );
    }
    else {
        $users = array();
        if (!empty($selecteduser)) {
            $user = get_record('user','id',$selecteduser);
            $users[$selecteduser] = fullname($user);
        }
        else {
            $users[0] = get_string('allparticipants');
        }
        choose_from_menu($users, 'user', $selecteduser, false);
        $a->url = "$CFG->wwwroot/course/report/log/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
            ."&id=$course->id&date=$selecteddate&modid=$selectedactivity&showusers=1&showcourses=$showcourses";
        print_string('logtoomanyusers','moodle',$a);
    }

*/
?>
