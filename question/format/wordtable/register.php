<?php  // register.php - allows admin to register account on YAWC Online to enable Word to XML conversion

require_once('../../../config.php');
require_once('register_form.php');
require_once('version.php');
require_once($CFG->libdir.'/dmllib.php');

require_login();

require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

if (!$site = get_site()) {
    redirect("index.php");
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

if (!$admin = get_admin()) {
    error("No admins");
}

if (!$admin->country and $CFG->country) {
    $admin->country = $CFG->country;
}

// Get the course ID so that we can return to the import page after registration
$courseid = optional_param('courseid', 0, PARAM_INT);
$returnurl = $CFG->wwwroot . ($courseid)? '/question/import.php?courseid=' . $courseid : "";

$stradministration = get_string("registration_administration", 'qformat_wordtable');
$strregistration = get_string("registration", 'qformat_wordtable');
$strregistrationinfo = get_string("registrationinfo", 'qformat_wordtable');
$navlinks = array();
$navlinks[] = array('name' => $stradministration, 'link' => "../$CFG->admin/index.php", 'type' => 'misc');
$navlinks[] = array('name' => $strregistration, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);

$thispageurl = new moodle_url();
$reg_form = new wordtable_register_form($thispageurl);

// Get the number of users on this site to classify site by size
// Just send the general classification, not the actual number of users
// Administrators can override the value if they want, too
$count = count_records('user', 'deleted', 0);
$sizeclass = 0;
if ($count > 500) $sizeclass = 1;
if ($count > 5000) $sizeclass = 2;

$site_defaults = array(
    'sitename' => format_string($site->fullname),
    'yolusername' => "mcq@" . $_SERVER["HTTP_HOST"],
    'courseid' => $courseid,
    'sitesize' => $sizeclass,
    'country' => $admin->country,
    'adminemail' => $admin->email,
    'adminname' => fullname($admin, true),
    'version' => $CFG->version,
    'release' => $CFG->release,
    'mailme' => 1,
    'public' => 2
);
/// Print the form
print_header("$site->shortname: $strregistration", $site->fullname, $navigation);
print_heading($strregistration);

if ($from_form = $reg_form->get_data()) {
    // Send the data to Moodle2Word website for registration
    $m2w_registration_string = "http://www.moodle2word.net/m2w_register.php?";
    $m2w_registration_string .= "yolusername=" . urlencode($from_form->yolusername);
    $m2w_registration_string .= "&password=" . urlencode($from_form->password);
    $m2w_registration_string .= "&sitename=" . urlencode($from_form->sitename);
    $m2w_registration_string .= "&adminname=" . urlencode($from_form->adminname);
    $m2w_registration_string .= "&adminemail=" . urlencode($from_form->adminemail);
    $m2w_registration_string .= "&public=" . $from_form->public;
    $m2w_registration_string .= "&country=" . $from_form->country;
    $m2w_registration_string .= "&sitesize=" . $from_form->sitesize;
    $m2w_registration_string .= "&mailme=" . $from_form->mailme;
    $m2w_registration_string .= "&version=" . urlencode($from_form->version);
    $m2w_registration_string .= "&lang=" . $from_form->lang;
    $m2w_registration_string .= "&release=" . urlencode($from_form->release);

    //notify($m2w_registration_string);
    $reg_result = file_get_contents($m2w_registration_string);
    //notify($reg_result);
    if (!$reg_result || preg_match("/HTTP\/1.0 403 Forbidden/", $reg_result)) {
        notify(get_string('registrationincomplete', 'qformat_wordtable'));
    } else {
        notify(get_string('registrationcomplete', 'qformat_wordtable'));
        // Account is added, so safe to store the version, username and password
        $new = new stdClass();
        $new->name = 'qformat_wordtable_version';
        $new->value = $module->version;
        insert_record('config', $new);

        $new->name = 'qformat_wordtable_username';
        $new->value = $from_form->yolusername;
        insert_record('config', $new);

        $new->name = 'qformat_wordtable_password';
        $new->value = base64_encode($from_form->password);
        insert_record('config', $new);

        // Return to the calling page so that Administrator can continue uploading a Word file
        redirect($returnurl);
    }


    print_footer();
} else {
    print_simple_box($strregistrationinfo, "center", "70%");
    $reg_form->set_data($site_defaults);
    $reg_form->display();
}

?>
