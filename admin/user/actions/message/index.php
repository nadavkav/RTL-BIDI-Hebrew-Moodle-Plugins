<?php //$Id: index.php,v 1.1 2009/03/10 10:01:55 argentum Exp $
require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
require_once('user_message_form.php');

$msg     = optional_param('msg', '', PARAM_CLEAN);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
check_action_capabilities('message', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

if (empty($CFG->messaging)) {
    error("Messaging is disabled on this site");
}

//TODO: add support for large number of users

if ($confirm and !empty($msg) and confirm_sesskey()) {
    $in = implode(',', $SESSION->bulk_users);
    if ($rs = get_recordset_select('user', "id IN ($in)")) {
        while ($user = rs_fetch_next_record($rs)) {
            message_post_message($USER, $user, $msg, FORMAT_HTML, 'direct');
        }
    }
    redirect($return);
}

// disable html editor if not enabled in preferences
if (!get_user_preferences('message_usehtmleditor', 0)) {
    $CFG->htmleditor = '';
}

$msgform = new user_message_form('index.php');

if ($msgform->is_cancelled()) {
    redirect($return);

} else if ($formdata = $msgform->get_data(false)) {
    $options = new object();
    $options->para     = false;
    $options->newlines = true;
    $options->smiley   = false;

    $msg = format_text($formdata->messagebody, $formdata->format, $options);

    $in = implode(',', $SESSION->bulk_users);
    $userlist = get_records_select_menu('user', "id IN ($in)", 'fullname', 'id,'.sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    $optionsyes = array();
    $optionsyes['confirm'] = 1;
    $optionsyes['sesskey'] = sesskey();
    $optionsyes['msg']     = $msg;
    admin_externalpage_print_header();
    print_heading(get_string('confirmation', 'admin'));
    print_box($msg, 'boxwidthnarrow boxaligncenter generalbox', 'preview');
    notice_yesno(get_string('confirmmessage', 'bulkusers', $usernames), 'index.php', $return, $optionsyes, NULL, 'post', 'get');
    admin_externalpage_print_footer();
    die;
}

admin_externalpage_print_header();
$msgform->display();
admin_externalpage_print_footer();
?>