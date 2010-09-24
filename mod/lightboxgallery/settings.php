<?php 

    require_once($CFG->dirroot . '/mod/lightboxgallery/lib.php');

    /* Disabled Plugins */

    $options = lightboxgallery_edit_types(true);

    $disableplugins = new admin_setting_configmultiselect('disabledplugins', get_string('configdisabledplugins', 'lightboxgallery'), get_string('configdisabledpluginsdesc', 'lightboxgallery'), array(), $options);
    $disableplugins->plugin = 'lightboxgallery';

    $settings->add($disableplugins);

    /* Enable RSS Feeds */

    if (empty($CFG->enablerssfeeds)) {
        $options = array(0 => get_string('rssglobaldisabled', 'admin'));
        $description = get_string('configenablerssfeedsdesc', 'lightboxgallery'). ' (' . get_string('configenablerssfeedsdisabled2', 'admin') . ')';

    } else {
        $options = array(0 => get_string('no'), 1 => get_string('yes'));
        $description = get_string('configenablerssfeedsdesc', 'lightboxgallery');
    }

    $enablerss = new admin_setting_configselect('enablerssfeeds', get_string('configenablerssfeeds', 'lightboxgallery'), $description, 0, $options);
    $enablerss->plugin = 'lightboxgallery';

    $settings->add($enablerss);

?>
