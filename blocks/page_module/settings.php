<?php
/**
 * Global settings
 *
 * @author Mark Nielsen
 * @version $Id: settings.php,v 1.1 2009/12/21 00:52:57 michaelpenne Exp $
 * @package block_page_module
 **/

$options = array();
if ($mods = get_list_of_plugins('mod')) {
    foreach ($mods as $mod) {
        $options[$mod] = get_string('modulename', $mod);
    }
}

$configs   = array();
$configs[] = new admin_setting_configmulticheckbox('showheaders', get_string('showheadersetting', 'block_page_module'), get_string('showheadersettingdesc', 'block_page_module'), array(), $options);

// Define the config plugin so it is saved to
// the config_plugin table then add to the settings page
foreach ($configs as $config) {
    $config->plugin = 'blocks/page_module';
    $settings->add($config);
}

?>