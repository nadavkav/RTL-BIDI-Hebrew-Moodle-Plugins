<?php  //$Id: filtersettings.php,v 1.1.2.2 2007/12/19 17:38:43 skodak Exp $


$settings->add(new admin_setting_configcheckbox('filter_docview_plugin_enable', get_string('docviewplugin','docviewfilter'), '', 1));

$vieweroptions = array(0 => 'Zoho Viewer', 1 => 'Zoho Writer' , 2=> 'Google Docs', 3=> 'ThinkFree Viewer');

$settings->add(new admin_setting_configselect  ('filter_docview_plugin_document', get_string('docviewservicedoc','docviewfilter'), get_string('docviewchooseservice','docviewfilter') , 0, $vieweroptions));

$settings->add(new admin_setting_configselect  ('filter_docview_plugin_presentation', get_string('docviewservicepresent','docviewfilter'), get_string('docviewchooseservice','docviewfilter'), 0, $vieweroptions));

$settings->add(new admin_setting_configtext('filter_docview_plugin_height', get_string('docviewpluginheight','docviewfilter'), '', '500',PARAM_TEXT));

$settings->add(new admin_setting_configtext('filter_docview_plugin_width', get_string('docviewpluginwidth','docviewfilter'), '', '90%',PARAM_TEXT));

?>
