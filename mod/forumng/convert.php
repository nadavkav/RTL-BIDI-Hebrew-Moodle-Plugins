<?php
require_once('../../config.php');
require_once('forum.php');
require_once($CFG->libdir.'/formslib.php');

class mod_forumng_convert_form extends moodleform {

    function definition() {

        global $CFG, $USER;
        $mform = $this->_form;
        $course = $this->_customdata;

        // Query for supported forums
        $forums = forum_utils::get_convertible_forums($course);

        $forumoptions = array();
        foreach($forums as $forum) {
            $forumoptions[$forum->id] = $forum->name;
        }

        $mform->addElement('static', '', '', get_string('convert_info', 'forumng'));

        $select = $mform->addElement('select', 'forums', get_string('modulenameplural', 'forum'), 
            $forumoptions);
        $select->setMultiple(true);

        $mform->addElement('checkbox', 'nodata', '', get_string('convert_nodata', 'forumng'));

        $mform->addElement('static', '', '', get_string('convert_warning', 'forumng'));
        $mform->addElement('checkbox', 'hide', '', get_string('convert_hide', 'forumng'));

        $this->add_action_buttons(true, get_string('convert_title', 'forumng'));

        $mform->addElement('hidden', 'course', $this->_customdata->id);
    }
}

$courseid = required_param('course', PARAM_INT);
try {
    $course = forum_utils::get_record('course', 'id', $courseid);
    require_login($course);
    require_capability('moodle/course:manageactivities', 
        get_context_instance(CONTEXT_COURSE, $courseid));

    $pagename = get_string('convert_title', 'forumng');
    $navigation = array();
    $navigation[] = array(
        'name'=>$pagename, 'type'=>'forumng');

    print_header_simple($pagename,
        "", build_navigation($navigation), "", "", true, '', navmenu($course));

    $mform = new mod_forumng_convert_form('convert.php', $course);
    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
    } else if ($fromform = $mform->get_data()) {
        print_heading($pagename);
        if (empty($fromform->forums) || count($fromform->forums)==0) {
            print '<p>' . get_string('convert_noneselected', 'forumng') . '</p>';
            print_continue($CFG->wwwroot . '/mod/forumng/convert.php?course=' . $course->id);
        } else {
            foreach ($fromform->forums as $forumid) {
                forum::create_from_old_forum($course, $forumid, true,
                    optional_param('hide', 0, PARAM_INT) ? true : false,
                    optional_param('nodata', 0, PARAM_INT) ? true : false);
            }
            print_continue($CFG->wwwroot . '/course/view.php?id=' . $course->id);
        }
    } else {
        $mform->display();
    }

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>