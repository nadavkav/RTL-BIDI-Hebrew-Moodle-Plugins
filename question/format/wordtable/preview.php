<?
/** preview.php
 *
 * Provides a simple mechanism to preview a question based on the course ID and a unique question name.
 * Copyright 2010 Eoin Campbell
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/questionlib.php');
require_once($CFG->libdir.'/dmllib.php');

// Get the assigned temporary question name
$qname = required_param('qname', PARAM_TEXT);
// Get the course id
$courseid = required_param('courseid', PARAM_INT);

// Get the question ID by searching for the unique name, and redirect to the preview page
if(($question = get_record('question', 'name', $qname))) {
    // Figure out the proper URL, allowing for an installation in a subfolder
    $moodle_root_folder_path = parse_url($CFG->wwwroot, PHP_URL_PATH);
    $redirect_url = $moodle_root_folder_path . "/question/preview.php?id=" . $question->id . "&courseid=" . $courseid;
    redirect($redirect_url);
} else {   // No question found, report an error message so the reader isn't looking at a blank screen
    notify(get_string('preview_question_not_found', 'qformat_wordtable', $qname . " / " . $courseid));
}
?>