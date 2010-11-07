<?php //$Id: user_bulk_message.php,v 1.2.2.1 2007/11/13 09:02:12 skodak Exp $
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
//require_once($CFG->dirroot.'/message/lib.php');
require_once('user_courselist_form.php');

//$msg     = optional_param('msg', '', PARAM_CLEAN);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$courses = optional_param('courses', '', PARAM_CLEAN);

admin_externalpage_setup('userbulk');
require_capability('moodle/site:readallmessages', get_context_instance(CONTEXT_SYSTEM));

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

//TODO: add support for large number of users

if ( $confirm and confirm_sesskey() and !empty($courses) ) {
    $in = implode(',', $SESSION->bulk_users);
    if ($rs = get_recordset_select('user', "id IN ($in)")) {
        while ($user = rs_fetch_next_record($rs)) {
            //message_post_message($USER, $user, $msg, FORMAT_HTML, 'direct');
            $roleid = 5; // student
            $courselist = explode(',',$courses);
            echo 'רושם את המשתמש '.$user->firstname.' כתלמיד בקורסים הנבחרים<br/>';
            foreach ($courselist as $courseid) {
              $coursecontext = get_context_instance(CONTEXT_COURSE,$courseid);
              echo $course->shortname;
              if (! role_assign($roleid, $user->id, 0, $coursecontext->id)) {
                $errors[] = "Could not add user {$user->firstname} {$user->lastname} with id {$user->id} to this role!";
              }
            }

        }
    }
    redirect($return);
}

$msgform = new user_courselist_form('user_bulk_enroll.php');

if ($msgform->is_cancelled()) {
    redirect($return);

} else if ($formdata = $msgform->get_data(false)) {
    $options = new object();
    $options->para     = false;
    $options->newlines = true;
    $options->smiley   = false;

    $inu = implode(',', $SESSION->bulk_users);
    $userlist = get_records_select_menu('user', "id IN ($inu)", 'fullname', 'id,'.sql_fullname().' AS fullname');
    $inc = implode(',',$formdata->courses);
    $courselist = get_records_select_menu('course', "id IN ($inc)", 'shortname', 'id,shortname');

    $optionsyes = array();
    $optionsyes['confirm'] = 1;
    $optionsyes['sesskey'] = sesskey();
    //$optionsyes['msg']     = $msg;
    $optionsyes['courses']     = implode(',',$formdata->courses);
    admin_externalpage_print_header();
    print_heading(get_string('confirmation', 'admin'));
    print_box('רישום המשתמשים:<br/>'.implode(',',$userlist).'<br/><br/>בתפקיד "תלמיד"<br/><br/> לקורסים הבאים:<br/>'.implode(',',$courselist), 'boxwidthnarrow boxaligncenter generalbox', 'preview');
    notice_yesno('האם אתם מאשרים?', 'user_bulk_enroll.php', 'user_bulk.php', $optionsyes, NULL, 'post', 'get');
    admin_externalpage_print_footer();
    die;
}

admin_externalpage_print_header();
$msgform->display();
admin_externalpage_print_footer();
?>