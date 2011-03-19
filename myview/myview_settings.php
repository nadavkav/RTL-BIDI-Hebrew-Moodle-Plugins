<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 3/17/11 Time: 8:31 PM
 *
 * Description:
 *
 */

$temp = new admin_settingpage('myview', get_string('settings', 'myview','',$CFG->dirroot.'/myview/lang/'));
$temp->add(new admin_setting_configtext('myview_supportlink', get_string('supportlink', 'myview','',$CFG->dirroot.'/myview/lang/'),
  get_string('supportlinkinfo', 'myview','',$CFG->dirroot.'/myview/lang/'), '', PARAM_RAW));
$ADMIN->add('appearance', $temp);
?>