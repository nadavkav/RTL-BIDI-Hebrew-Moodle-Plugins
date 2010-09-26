<?php //$Id: index.php,v 1.1 2009/03/10 10:01:59 argentum Exp $
/**
* script for bulk user delete operations
*/

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');

$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
check_action_capabilities('confirm', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

admin_externalpage_print_header();

//TODO: add support for large number of users

if ($confirm and confirm_sesskey()) {
    $in = implode(',', $SESSION->bulk_users);
    if ($rs = get_recordset_select('user', "id IN ($in)", '', 'id, username, secret, confirmed, auth, firstname, lastname')) {
        while ($user = rs_fetch_next_record($rs)) {
            if ($user->confirmed) {
                continue;
            }
            $auth = get_auth_plugin($user->auth);
            $result = $auth->user_confirm(addslashes($user->username), addslashes($user->secret));
            if ($result != AUTH_CONFIRM_OK && $result != AUTH_CONFIRM_ALREADY) {
                notify(get_string('usernotconfirmed', '', fullname($user, true)));
            }
        }
        rs_close($rs);
    }
    redirect($return, get_string('changessaved'));

} else {
    $in = implode(',', $SESSION->bulk_users);
    $userlist = get_records_select_menu('user', "id IN ($in)", 'fullname', 'id,'.sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    $optionsyes = array();
    $optionsyes['confirm'] = 1;
    $optionsyes['sesskey'] = sesskey();
    print_heading(get_string('confirmation', 'admin'));
    notice_yesno(get_string('confirmcheckfull', '', $usernames), 'index.php', $return, $optionsyes, NULL, 'post', 'get');
}

admin_externalpage_print_footer();
?>