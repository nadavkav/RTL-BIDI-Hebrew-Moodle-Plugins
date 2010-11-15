<?php //$Id: index.php,v 1.2 2009/03/20 13:31:24 argentum Exp $
/**
* miniscript for bulk user email activation/deactivation
*/

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');

$confirm  = optional_param('confirm', 0, PARAM_BOOL);
$mailstop = optional_param('mailstop', false, PARAM_RAW);

admin_externalpage_setup('userbulk');
check_action_capabilities('emailactive', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';
$langdir = $CFG->dirroot.'/admin/user/actions/emailactive/lang/';
$pluginname = 'bulkuseractions_emailactive';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

admin_externalpage_print_header();

if ($confirm and confirm_sesskey()) {
    foreach ($SESSION->bulk_users as $user) {
        set_field('user', 'emailstop', $mailstop, 'id', $user);
    }
    redirect($return, get_string('changessaved'));
}

if ($mailstop !== false) {
    $in = implode(',', $SESSION->bulk_users);
    $userlist = get_records_select_menu('user', "id IN ($in)", 'fullname', 'id,'.sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    $confstr = get_string( 'confirm1', $pluginname, NULL, $langdir );
    if ($mailstop == 0) {
        $confstr .= get_string( 'activate', $pluginname, NULL, $langdir );
    } else {
        $confstr .= get_string( 'deactivate', $pluginname, NULL, $langdir );
    }
    $confstr .= get_string( 'confirm2', $pluginname, NULL, $langdir ) . '<br />';
    $confstr .= $usernames . '?';
    $optionsyes = array();
    $optionsyes['confirm'] = 1;
    $optionsyes['mailstop'] = $mailstop;
    $optionsyes['sesskey'] = sesskey();
    print_heading(get_string('confirmation', 'admin'));
    notice_yesno($confstr, 'index.php', $return, $optionsyes, NULL, 'post', 'get');
} else {
?>
<div id="addmembersform" align=center>
    <form id="emailedform" method="post" action="index.php">
    <label for="mailstop"><?php echo get_string( 'pluginname', $pluginname, NULL, $langdir ) ?></label>
    <br />
    <select name="mailstop" id="mailstop" size="1" >
        <option value=0><?php echo get_string('emailenable') ?></option>
        <option value=1><?php echo get_string('emaildisable') ?></option>
    </select>
    <br />
    <input type="submit" name="accept" value="<?php echo get_string( 'go' ) ?>" />
    </form>
</div>
<?php
}

admin_externalpage_print_footer();
?>
