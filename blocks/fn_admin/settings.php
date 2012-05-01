<?php  //$Id: settings.php,v 1.1 2009/02/26 16:39:44 mchurch Exp $

$choices = array(0 => get_string('hide'), 1 => get_string('show'));

$settings->add(new admin_setting_configselect('block_fn_admin_showadminmenu', get_string('showadminmenu', 'block_fn_admin'),
                   get_string('showadminmenu', 'block_fn_admin'), 0, $choices));

$settings->add(new admin_setting_configselect('block_fn_admin_showunenrol', get_string('showunenrol', 'block_fn_admin'),
                   get_string('showunenrol', 'block_fn_admin'), 0, $choices));

$settings->add(new admin_setting_configselect('block_fn_admin_showprofile', get_string('showprofile', 'block_fn_admin'),
                   get_string('showprofile', 'block_fn_admin'), 0, $choices));

?>
