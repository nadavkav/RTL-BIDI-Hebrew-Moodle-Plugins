<?php
// arrange.php  brian@mediagonal.ch / free.as.in.speech@gmail.com

echo <<<HTMLHEAD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="rtl" lang="he" xml:lang="he">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="direction:ltr; text-align:left;">
HTMLHEAD;

require_once("../../../config.php");
require_once("$CFG->dirroot/lib/questionlib.php");
require_once("$CFG->dirroot/question/type/dragdrop/dragdrop.php");
require_once("$CFG->dirroot/question/editlib.php");

$id = required_param('id', PARAM_INT);  // question id
$courseid = optional_param('courseid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);
$process = optional_param('process', '', PARAM_ALPHA);
                                // savereturn:save & return to question editing,
                                // savecontinue: save and continue back to overview,
                                // cancel: return to question editing without saving
if ($cmid){
    list($module, $cm) = get_module_from_cmid($cmid);
    require_login($cm->course, false, $cm);
	if (!$returnurl) {
        $returnurl = "{$CFG->wwwroot}/question/edit.php?cmid={$cm->id}";
    }
} elseif ($courseid) {
    require_login($courseid, false);
    if (!$returnurl) {
        $returnurl = "{$CFG->wwwroot}/question/edit.php?courseid={$COURSE->id}";
    }
    $cm = null;
} else {
    print_error('needcmidorcourseid', 'qtype_dragdrop');
}

// Validate the question id
if (!$question = get_record('question', 'id', $id)) {
    print_error('questiondoesnotexist', 'question', $returnurl);
}
get_question_options($question);

if(!question_has_capability_on($question, 'edit')) {
    print_error('noeditingright', 'qtype_dragdrop');
}

$dd = new dragdrop($CFG, $id, $courseid, $cmid, $returnurl);

if ($process) {
    $dd->process($process);
} else {
    $dd->edit_positions();
}
	echo '</body></html>';
?>