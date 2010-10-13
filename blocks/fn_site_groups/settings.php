<?php  //$Id: settings.php,v 1.4 2009/08/12 19:47:08 mchurch Exp $

/// Create a group manager role, if one doesn't exist:
$context = get_system_context();
if (!($role = get_record('role', 'shortname', FNGRPMANROLESNAME))) {
    $gmroleid = create_role(FNGRPMANROLENAME, FNGRPMANROLESNAME, FNGRPMANROLEDESC);
    assign_capability('block/fn_site_groups:managegroups', CAP_ALLOW, $gmroleid, $context->id);
    assign_capability('block/fn_site_groups:managegroupmembers', CAP_ALLOW, $gmroleid, $context->id);
    assign_capability('block/fn_site_groups:managestudents', CAP_ALLOW, $gmroleid, $context->id);
    assign_capability('block/fn_site_groups:markallgroups', CAP_ALLOW, $gmroleid, $context->id);
    assign_capability('block/fn_site_groups:assignallusers', CAP_INHERIT, $gmroleid, $context->id);
    assign_capability('block/fn_site_groups:assignowngroupusers', CAP_ALLOW, $gmroleid, $context->id);
    assign_capability('block/fn_site_groups:createnewgroups', CAP_ALLOW, $gmroleid, $context->id);
} else {
    $gmroleid = $role->id;
}

$roles = get_records_menu('role', '', '', 'sortorder ASC', 'id,name');
if (empty($roles)) {
    $roles = array();
}
$course = get_site();
$role = get_default_course_role($course);
$defaultroleid = $role->id;

$item = new admin_setting_configcheckbox('block_fn_site_groups_enabled', get_string('fn_site_groups_enabled', 'block_fn_site_groups'),
                                         get_string('fn_config_site_groups_enabled', 'block_fn_site_groups'), '0');
$item->set_updatedcallback('fn_sg_set_site_group_mode');
$settings->add($item);
$settings->add(new admin_setting_configselect('block_fn_site_groups_defaultroleid', get_string('fn_site_groups_defaultroleid', 'block_fn_site_groups'),
                                              get_string('fn_config_site_groups_defaultroleid', 'block_fn_site_groups'), $defaultroleid , $roles));

$settings->add(new admin_setting_configmulticheckbox('block_fn_site_groups_roles', get_string('fn_site_groups_roles', 'block_fn_site_groups'),
                                                    get_string('fn_config_site_groups_roles', 'block_fn_site_groups'), '0', $roles));

/// These settings don't actually use the $CFG variable, but are used to manage capabilities.
$caps = role_context_capabilities($gmroleid, $context);

$sgusers = array();
if (!empty($caps['block/fn_site_groups:assignowngroupusers'])) {
    $sgusers[] = 1;
}
if (!empty($caps['block/fn_site_groups:assignallusers'])) {
    $sgusers[] = 2;
}
$CFG->block_fn_site_groups_users = implode(',', $sgusers);

$users = array(1 => get_string('fn_site_groups:assignowngroupusers', 'block_fn_site_groups'),
               2 => get_string('fn_site_groups:assignallusers', 'block_fn_site_groups'));
$item = new admin_setting_configmulticheckbox('block_fn_site_groups_users', get_string('fn_site_groups_users', 'block_fn_site_groups'),
                                              get_string('fn_config_site_groups_users', 'block_fn_site_groups'), '1', $users);
$item->set_updatedcallback('fn_sg_set_user_capability');
$settings->add($item);


if (!empty($caps['block/fn_site_groups:createnewgroups'])) {
    $CFG->block_fn_site_groups_creategroups = 1;
} else {
    $CFG->block_fn_site_groups_creategroups = 0;
}

$item = new admin_setting_configselect('block_fn_site_groups_creategroups', get_string('fn_site_groups_creategroups', 'block_fn_site_groups'),
                                       get_string('fn_config_site_groups_creategroups', 'block_fn_site_groups'), 1,
                                       array(0 => get_string('no'), 1 => get_string('yes')));
$item->set_updatedcallback('fn_sg_set_user_capability');
$settings->add($item);
?>