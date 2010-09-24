<?PHP  // $Id: index.php,v 1.2 2004/08/24 16:36:18 cmcclean Exp $

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);           // Course Module ID

if (! $course = get_record('course', 'id', $id)) {
    error("Course ID is incorrect");
}

require_login($course->id);

add_to_log($course->id, 'brainstorm', 'view all', "index?id=$course->id", "");

if ($course->category) {
    $navigation = "<a href=\"../../course/view.php?id={$course->id}\">{$course->shortname}</a> ->";
} 
else {
    $navigation = "";
}

$strbrainstorm = get_string('modulename', 'brainstorm');
$strbrainstorms = get_string('modulenameplural', 'brainstorm');

print_header($course->shortname.': '.format_string($strbrainstorms), format_string($course->fullname),
             "$navigation $strbrainstorms", '', '', true, '', navmenu($course));


if (! $brainstorms = get_all_instances_in_course('brainstorm', $course)) {
    notice("There are no brainstorms", "../../course/view.php?id={$course->id}");
}

if ( $allresponses = get_records('brainstorm_responses', 'userid', $USER->id)) {
    foreach ($allresponses as $aa) {
        $responses[$aa->brainstormid] = $aa;
    }
} 
else {
    $responses = array () ;
}


$timenow = time();

if ($course->format == 'weeks') {
    $table->head  = array (get_string('week'), get_string('question'), get_string('answer'));
    $table->align = array ('CENTER', 'LEFT', 'LEFT');
} 
else if ($course->format == 'topics') {
    $table->head  = array (get_string('topic'), get_string('question'), get_string('answer'));
    $table->align = array ('CENTER', 'LEFT', 'LEFT');
} 
else {
    $table->head  = array (get_string('question'), get_string('answer'));
    $table->align = array ('LEFT', 'LEFT');
}

$currentsection = "";

foreach ($brainstorms as $brainstorm) {
    if (!empty($responses[$brainstorm->id])) {
        $answer = $responses[$brainstorm->id];
    } else {
        $answer = '';
    }
    if (!empty($answer->answer)) {
        $aa = brainstorm_get_answer($brainstorm, $answer->answer);
    } else {
        $aa = '';
    }
    $printsection = '';
    if ($brainstorm->section !== $currentsection) {
        if ($brainstorm->section) {
            $printsection = $brainstorm->section;
        }
        if ($currentsection !== '') {
            $table->data[] = 'hr';
        }
        $currentsection = $brainstorm->section;
    }
    
    //Calculate the href
    if (!$brainstorm->visible) {
        //Show dimmed if the mod is hidden
        $tt_href = "<a class=\"dimmed\" href=\"view.php?id={$brainstorm->coursemodule}\">".format_string($brainstorm->name).'</a>';
    } 
    else {
        //Show normal if the mod is visible
        $tt_href = "<a href=\"view.php?id={$brainstorm->coursemodule}\">".format_string($brainstorm->name).'</a>';
    }
    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = array ($printsection, $tt_href, $aa);
    } 
    else {
        $table->data[] = array ($tt_href, $aa);
    }
}
echo '<br />';
print_table($table);
print_footer($course); 
?>

