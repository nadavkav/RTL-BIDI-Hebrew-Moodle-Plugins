<?php

require_once($CFG->dirroot.'/mod/forumng/forum.php');

$module = new stdClass;
require($CFG->dirroot.'/mod/forumng/version.php');

$settings->add(new admin_setting_heading('forumng_version', '',
    get_string('displayversion', 'forumng', $module->displayversion)));

$settings->add(new admin_setting_configcheckbox('forumng_replytouser',
    get_string('replytouser', 'forumng'),
    get_string('configreplytouser', 'forumng'), 1));

$settings->add(new admin_setting_configtext('forumng_usebcc',
    get_string('usebcc', 'forumng'),
    get_string('configusebcc', 'forumng'), 0, PARAM_INT));

$settings->add(new admin_setting_configtext('forumng_donotmailafter',
    get_string('donotmailafter', 'forumng'),
    get_string('configdonotmailafter', 'forumng'), 48, PARAM_INT));

    // Number of discussions on a page
$settings->add(new admin_setting_configtext('forumng_discussionsperpage',
    get_string('discussionsperpage', 'forumng'),
    get_string('configdiscussionsperpage', 'forumng'), 20, PARAM_INT));

$sizes=get_max_upload_sizes($CFG->maxbytes);
unset($sizes[0]);
$sizes[-1]=get_string('forbidattachments','forumng');

$settings->add(new admin_setting_configselect('forumng_attachmentmaxbytes',
    get_string('attachmentmaxbytes', 'forumng'),
    get_string('configattachmentmaxbytes', 'forumng'), 512000, $sizes));

// Option about read tracking
$settings->add(new admin_setting_configcheckbox('forumng_trackreadposts',
    get_string('trackreadposts', 'forumng'), get_string('configtrackreadposts', 'forumng'), 1));

// Number of days that a post is considered old and we don't store unread data
$settings->add(new admin_setting_configtext('forumng_readafterdays',
    get_string('readafterdays', 'forumng'),
    get_string('configreadafterdays', 'forumng'), 60, PARAM_INT));

// RSS feeds
if (empty($CFG->enablerssfeeds)) {
    $options = array(0 => get_string('rssglobaldisabled', 'admin'));
    $str = get_string('configenablerssfeeds', 'forumng').'<br />'.
        get_string('configenablerssfeedsdisabled2', 'admin');
} else {
    $options = array(0=>get_string('no'), 1=>get_string('yes'));
    $str = get_string('configenablerssfeeds', 'forumng');
}
$settings->add(new admin_setting_configselect('forumng_enablerssfeeds',
    get_string('enablerssfeeds', 'admin'), $str, 0, $options));

$options=forum::get_subscription_options();
$options[-1]=get_string('perforumoption','forumng');
$settings->add(new admin_setting_configselect('forumng_subscription',
    get_string('subscription', 'forumng'),
    get_string('configsubscription', 'forumng'), -1, $options));

// In Moodle 2.0, or OU Moodle 1.9, we use a new admin setting type to let you
// select roles with checkboxes. Otherwise you have to type in role IDs. Ugh.
$defaultroles=array('moodle/legacy:student','moodle/legacy:teacher');
if(class_exists('admin_setting_pickroles')) {
    $settings->add(new admin_setting_pickroles('forumng_subscriberoles',
        get_string('subscriberoles','forumng'),
        get_string('configsubscriberoles','forumng'),$defaultroles));
} else {
    $default='';
    if(!isset($CFG->forumng_subscriberoles)) {
        $result=array();
        foreach($defaultroles as $capability) {
            if ($caproles = get_roles_with_capability($capability, CAP_ALLOW)) {
                foreach ($caproles as $caprole) {
                    if(!in_array($caprole->id,$result)) {
                        $result[] = $caprole->id;
                    }
                }
            }
        }
        $default=implode(',',$result);
    }

    $settings->add(new admin_setting_configtext('forumng_subscriberoles',
        get_string('subscriberoles', 'forumng'),
        get_string('configsubscriberoles', 'forumng'), $default, PARAM_SEQUENCE));
}

$defaultroles=array('moodle/legacy:student','moodle/legacy:teacher');
if (class_exists('admin_setting_pickroles')) {
    $settings->add(new admin_setting_pickroles('forumng_monitorroles',
        get_string('monitorroles','forumng'),
        get_string('configmonitorroles','forumng'),$defaultroles));
} else {
    $default='';
    if (!isset($CFG->forumng_monitorroles)) {
        $result=array();
        foreach($defaultroles as $capability) {
            if ($caproles = get_roles_with_capability($capability, CAP_ALLOW)) {
                foreach ($caproles as $caprole) {
                    if(!in_array($caprole->id,$result)) {
                        $result[] = $caprole->id;
                    }
                }
            }
        }
        $default=implode(',',$result);
    }
    $settings->add(new admin_setting_configtext('forumng_monitorroles',
        get_string('monitorroles', 'forumng'),
        get_string('configmonitorroles', 'forumng'), $default, PARAM_SEQUENCE));
}

$options = forum::get_feedtype_options();
$options[-1]=get_string('perforumoption','forumng');
$settings->add(new admin_setting_configselect('forumng_feedtype',
    get_string('feedtype', 'forumng'),
    get_string('configfeedtype', 'forumng'), -1, $options));

$options = forum::get_feeditems_options();
$options[-1]=get_string('perforumoption','forumng');
$settings->add(new admin_setting_configselect('forumng_feeditems',
    get_string('feeditems', 'forumng'),
    get_string('configfeeditems', 'forumng'), -1, $options));

$options=array(
    0=>get_string('permanentdeletion_never','forumng'),
    1=>get_string('permanentdeletion_soon','forumng'),
    1*60*60*24=>'1 '.get_string('day'),
    14*60*60*24=>'14 '.get_string('days'),
    30*60*60*24=>'30 '.get_string('days'),
    365*60*60*24=>'1 '.get_string('year'));
$settings->add(new admin_setting_configselect('forumng_permanentdeletion',
    get_string('permanentdeletion', 'forumng'),
    get_string('configpermanentdeletion', 'forumng'), 30*60*60*24, $options));

//Start hour of deleting or archiving old discussions
$options = array();
for ($i = 0; $i < 24; $i++) {
    $options[$i*3600] = $i;
}
$settings->add(new admin_setting_configselect('forumng_housekeepingstarthour',
    get_string('housekeepingstarthour', 'forumng'),
    get_string('confighousekeepingstarthour', 'forumng'), 0, $options));
    
$settings->add(new admin_setting_configselect('forumng_housekeepingstophour',
    get_string('housekeepingstophour', 'forumng'),
    get_string('confighousekeepingstophour', 'forumng'), 5*3600, $options));
// Option about read tracking
$settings->add(new admin_setting_configcheckbox('forumng_showusername',
    get_string('showusername', 'forumng'),
    get_string('configshowusername', 'forumng'), 0));
$settings->add(new admin_setting_configcheckbox('forumng_showidnumber',
    get_string('showidnumber', 'forumng'),
    get_string('configshowidnumber', 'forumng'), 0));

$settings->add(new admin_setting_configtext('forumng_reportunacceptable', get_string('reportunacceptable', 'forumng'),
                   get_string('configreportunacceptable', 'forumng'), '', PARAM_NOTAGS));

$settings->add(new admin_setting_configtext('forumng_computing_guide', get_string('computingguideurl', 'forumng'),
    get_string('computingguideurlexplained', 'forumng'), '', PARAM_NOTAGS));

$settings->add(new admin_setting_configcheckbox('forumng_enableadvanced',
    get_string('enableadvanced', 'forumng'),
    get_string('configenableadvanced', 'forumng'), 0));

?>
