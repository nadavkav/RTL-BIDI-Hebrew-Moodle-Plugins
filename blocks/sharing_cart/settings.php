<?php  //$Id: settings.php,v 1.2 2009/04/24 10:38:29 akiococom Exp $

require_once dirname(__FILE__).'/plugins.php';

sharing_cart_plugins::load();
$plugin_names = sharing_cart_plugins::enum();
if (empty($plugin_names)) {
	$settings->add(
		new admin_setting_heading(
			'sharing_cart_heading',
			get_string('conf_plugins_heading', 'block_sharing_cart'),
			get_string('conf_plugins_nothing', 'block_sharing_cart')
		)
	);
} else {
	$settings->add(
		new admin_setting_heading(
			'sharing_cart_heading',
			get_string('conf_plugins_heading', 'block_sharing_cart'),
			null
		)
	);
	$settings->add(
		new admin_setting_configmultiselect(
			'sharing_cart_plugins',
			get_string('conf_plugins_enabled_head', 'block_sharing_cart'),
			get_string('conf_plugins_enabled_desc', 'block_sharing_cart'),
			$plugin_names,
			array_combine($plugin_names, $plugin_names)
		)
	);
}

?>