<?php
/**
 * List of wikis on course. (Not used in OU. I ripped it entirely off 
 * from another module, deleting module-specific bits.)
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */
 
require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record("course", "id", $id)) {
    error("Course ID is incorrect");
}

// Support for OU shared activities system, if installed 
$grabindex=$CFG->dirroot.'/course/format/sharedactv/grabindex.php';
if(file_exists($grabindex)) {
    require_once($grabindex);
}

require_course_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

add_to_log($course->id, "ouwiki", "view all", "index.php?id=$course->id", "");

$strweek = get_string('week');
$strtopic = get_string('topic');
$strname = get_string('name');
$strouwiki= get_string('modulename','ouwiki');
  
$navigation = build_navigation(array(array('name' => $strouwiki, 'link' => '', 'type' => 'activity')));
print_header_simple($strouwiki, '', $navigation, '', '', true, "", navmenu($course));
  
if (! ($ouwikis = get_all_instances_in_course("ouwiki", $course))) {
    notice("There are no wikis", "$CFG->wwwroot/course/view.php?id=$course->id");
}

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');
$strdescription = get_string("description");

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname, $strdescription);
    $table->align = array ('center', 'center', 'center');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname, $strdescription);
    $table->align = array ('center', 'center', 'center');
} else {
    $table->head  = array ($strname, $strdescription);
    $table->align = array ('center', 'center');
}

$currentsection = "";

foreach ($ouwikis as $ouwiki) {

    $printsection = "";

    //Calculate the href
    if (!$ouwiki->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$ouwiki->coursemodule\">".format_string($ouwiki->name,true)."</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$ouwiki->coursemodule\">".format_string($ouwiki->name,true)."</a>";
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        if ($ouwiki->section !== $currentsection) {
            if ($ouwiki->section) {
                $printsection = $ouwiki->section;
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $ouwiki->section;
        }
        $row = array ($printsection, $link, $ouwiki->summary);

    } else {
        $row = array ($link, $ouwiki->summary);
    }

    $table->data[] = $row;
}

echo "<br />";
print_table($table);
print_footer($course);
    
?>
