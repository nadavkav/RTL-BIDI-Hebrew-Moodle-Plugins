<?php  //$Id: pagelib.php,v 1.11.2.2 2008/05/01 06:03:49 dongsheng Exp $

require_once($CFG->libdir.'/pagelib.php');

class page_myview_moodle extends page_base {

    function get_type() {
        return PAGE_MYVIEW_MOODLE;
    }

    function user_allowed_editing() {
        page_id_and_class($id,$class);
        if ($id == PAGE_MYVIEW_MOODLE) {
            return true;
        } else if (has_capability('moodle/my:manageblocks', get_context_instance(CONTEXT_SYSTEM)) && defined('ADMIN_STICKYBLOCKS')) {
            return true;
        }
        return false;
    }

    function user_is_editing() {
        global $USER;
        if (has_capability('moodle/my:manageblocks', get_context_instance(CONTEXT_SYSTEM)) && defined('ADMIN_STICKYBLOCKS')) {
            return true;
        }
        return (!empty($USER->editing));
    }

    function print_header($title) {

        global $USER;

        $replacements = array('%fullname%' => get_string('mymoodle','my'));
        foreach($replacements as $search => $replace) {
            $title = str_replace($search, $replace, $title);
        }

        $site = get_site();

        $button = update_myviewmoodle_icon($USER->id);
        $nav = get_string('mymoodle','my');
        $header = $site->shortname.': '.$nav;
        $navlinks = array(array('name' => $nav, 'link' => '', 'type' => 'misc'));
        $navigation = build_navigation($navlinks);
        
        $loggedinas = user_login_string($site);
        print_header($title, $header,$navigation,'','',true, $button, $loggedinas);

    }
    
    function url_get_path() {
        global $CFG;
        page_id_and_class($id,$class);
        if ($id == PAGE_MYVIEW_MOODLE) {
            return $CFG->wwwroot.'/myview/index.php';
        } elseif (defined('ADMIN_STICKYBLOCKS')){
            return $CFG->wwwroot.'/'.$CFG->admin.'/stickyblocks.php';
        }
    }

    function url_get_parameters() {
        if (defined('ADMIN_STICKYBLOCKS')) {
            return array('pt' => ADMIN_STICKYBLOCKS);
        } else {
            return array();
        }
    }
       
    function blocks_default_position() {
        return BLOCK_POS_LEFT;
    }

    function blocks_get_positions() {
        return array(BLOCK_POS_LEFT, BLOCK_POS_RIGHT);
    }

    function blocks_move_position(&$instance, $move) {
        if($instance->position == BLOCK_POS_LEFT && $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_RIGHT;
        } else if ($instance->position == BLOCK_POS_RIGHT && $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_LEFT;
        }
        return $instance->position;
    }

    function get_format_name() {
        return MYVIEW_MOODLE_FORMAT;
    }
}

/**
 * Returns a turn edit on/off button for course in a self contained form.
 * Used to be an icon, but it's now a simple form button
 *
 * @uses $CFG
 * @uses $USER
 * @param int $courseid The course  to update by id as found in 'course' table
 * @return string
 */
function update_myviewmoodle_icon() {

    global $CFG, $USER;

    if (!empty($USER->editing)) {
        $string = get_string('updatemymoodleoff');
        $edit = '0';
    } else {
        $string = get_string('updatemymoodleon');
        $edit = '1';
    }

    return "<form $CFG->frametarget method=\"get\" action=\"$CFG->wwwroot/myview/index.php\">".
           "<div>".
           "<input type=\"hidden\" name=\"edit\" value=\"$edit\" />".
           "<input type=\"submit\" value=\"$string\" /></div></form>";
}

define('PAGE_MYVIEW_MOODLE',   'myview-index');
define('MYVIEW_MOODLE_FORMAT', 'myview'); //doing this so we don't run into problems with applicable formats.

page_map_class(PAGE_MYVIEW_MOODLE, 'page_myview_moodle');

?>
