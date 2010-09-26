<?php
/**
 * Main hook from moodle into the course format
 *
 * @author Jeff Graham, Mark Nielsen
 * @version $Id: format.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @todo Swich to the use of $PAGE->user_allowed_editing()
 * @todo Next/Previous breaks when three columns are not printed - Perhaps they should not be part of the main table
 * @todo Core changes wish list:
 *           - Remove hard-coded left/right block position references
 *           - Provide a better way for formats to say, "Hey, backup these blocks" or open up the block instance backup routine and have the format backup its own blocks.
 *           - With the above two, we could have three columns and multiple independent pages that are compatible with core routines.
 *           - http://tracker.moodle.org/browse/MDL-10265 these would help with performance and control
 */
    if (empty($CFG)) {
        // This page has not been called by course/view.php
        require_once('../../../config.php');
        require_once($CFG->libdir.'/blocklib.php'); // This includes lib/pagelib.php and course/lib.php
        require_once($CFG->dirroot.'/mod/forum/lib.php');
    }
    require_once($CFG->dirroot.'/course/format/page/lib.php');

    $id     = optional_param('id', SITEID, PARAM_INT);    // Course ID
    $pageid = optional_param('page', 0, PARAM_INT);       // format_page record ID
    $action = optional_param('action', 'layout', PARAM_ALPHA);  // What the user is doing

/// Could be set by course/view.php
    if (!isset($course)) {
        if (!$course = get_record('course', 'id', $id)) {
            error(get_string('invalidcourseid', 'format_page'));
        }
        if ($course->id != SITEID or $CFG->forcelogin) {
            require_login($course->id);
        }
    }

/// Load up the context for calling has_capability later
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

/// Set course display
    if ($pageid > 0) {
        $pageid = page_set_current_page($course->id, $pageid);
    } else {
        if ($page = page_get_current_page($course->id)) {
            $displayid = $page->id;
        } else {
            $displayid = 0;
        }
        $pageid = page_set_current_page($course->id, $displayid);
    }

/// Check out the $pageid - set? valid? belongs to this course?
    if (!empty($pageid)) {
        if (empty($page) or $page->id != $pageid) {
            // Didn't get the page above or we got the wrong one...
            if (!$page = page_get($pageid)) {
                error('Invalid page ID');
            }
        }
        // Ensure this page is with this course
        if ($page->courseid != $course->id) {
            error(get_string('invalidpageid', 'format_page', $pageid));
        }
    } else {
        // We don't have a page ID to work with
        if (has_capability('format/page:editpages', $context)) {
            $action = 'editpage';
            $page = new stdClass;
            $page->id = 0;
        } else {
            // Nothing this person can do about it, error out
            error(get_string('nopageswithcontent', 'format_page'));
        }
    }

/// There are a couple processes that need some help via the session... take care of those.
    $action = page_handle_session_hacks($course->id, $action);

/// Override PAGE_COURSE_VIEW class mapping
    page_import_types('course/format/page');
    $PAGE = page_create_object(PAGE_COURSE_VIEW, $course->id);
    $PAGE->set_formatpage($page);

/// Handle format actions
    page_format_execute_url_action($action);
?>
