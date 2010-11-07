<?php  //$Id: settings.php,v 1.1.2.5 2009/01/17 19:30:08 stronk7 Exp $

$blocknames = get_records("block");
$options = array();
foreach ($blocknames as $blockname) {
    $options[$blockname->name] = $blockname->name;
}
$settings->add(new admin_setting_configmultiselect('wiki_defaultblocks', get_string('wiki_defaultblocks', 'wiki'),
  get_string('wiki_defaultblocks_info', 'wiki'), array(), $options));

$defaulteditor = array('htmleditor','nwiki','ewiki', 'dfwiki');
$settings->add(new admin_setting_configmultiselect('wiki_defaulteditor', get_string('wiki_defaulteditor', 'wiki'),
  get_string('wiki_defaulteditor_info', 'wiki'), array(), $defaulteditor));

$settings->add(new admin_setting_configcheckbox('wiki_enableblocks', get_string('wiki_enableblocks', 'wiki'),
                   get_string('wiki_enableblocks_info', 'wiki'), 0));
?>
