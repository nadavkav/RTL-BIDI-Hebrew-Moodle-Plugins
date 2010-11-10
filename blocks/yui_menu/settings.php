<?php

/**
 * A little file to prevent the yui_menu_plugin_settings class from being
 * loaded multiple times
 */

require_once $CFG->dirroot.'/blocks/yui_menu/settingslib.php';
$settings->add(new yui_menu_plugin_settings());
