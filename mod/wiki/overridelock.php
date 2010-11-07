<?php
/**
 * Handles what happens when a user with appropriate permission attempts to 
 * override a wiki page editing lock.
 *
 * @copyright &copy; 2006 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package page_lock_system
 *//** */

require_once('../../config.php');
require_once('./lib/wiki_manager.php');

$id          = required_param('id',          PARAM_INT);
$cmid        = optional_param('cmid', 0,     PARAM_INT);
$topage      = required_param('topage',      PARAM_RAW);
$lockedpages = required_param('lockedpages', PARAM_RAW);

//get correct path whether is a course or activity
$redir = ($cmid == 0)?"":"../../course/";
//get correct id whether is a course or activity
$cmid = ($cmid == 0)?$id:$cmid;

if (! $cm = get_coursemodule_from_id('wiki', $cmid)) {
    error("Course Module ID was incorrect");
}
if (! $course = get_record("course", "id", $cm->course)) {
    error("Course is misconfigured");
}
if (! $wiki = get_record("wiki", "id", $cm->instance)) {
    error("Course module is incorrect");
}

if(!confirm_sesskey()) {
    error("Session key not set");
}
if(!data_submitted()) {
    error("Only POST requests accepted");
}

require_course_login($course, true, $cm);

$modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
if(!has_capability('mod/wiki:overridelock', $modcontext)) {
    error("You do not have the capability to override editing locks");
}

// remove lock(s)
foreach ($lockedpages as $lockedpage) {
    $lockedpage = urldecode($lockedpage);
    $lockedpage = addslashes($lockedpage);
    if (!delete_records('wiki_locks', 'pagename', $lockedpage, 'wikiid', $wiki->id))
        error('Unable to delete lock record');
}

redirect($redir."view.php?id=$id&page=".$topage);

?>
