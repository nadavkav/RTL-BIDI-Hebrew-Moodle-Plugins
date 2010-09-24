<?php

    require_once('../../config.php');
    require_once('lib.php');
    require_once('edittype/base.class.php');

    $id = required_param('id', PARAM_INT);
    $image = required_param('image', PARAM_PATH);
    $tab = optional_param('tab', '', PARAM_TEXT);
    $page = optional_param('page', 0, PARAM_INT);

    if (! $gallery = get_record('lightboxgallery', 'id', $id)) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $gallery->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('lightboxgallery', $gallery->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

    require_login($course->id);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/lightboxgallery:edit', $context);

    $edittypes = lightboxgallery_edit_types();

    $tabs = array();
    foreach ($edittypes as $type => $name) {
        $tabs[] = new tabObject($type, $CFG->wwwroot.'/mod/lightboxgallery/imageedit.php?id='.$gallery->id.'&amp;image='.$image.'&amp;page='.$page.'&amp;tab='.$type, $name);
    }

    if (!in_array($tab, array_keys($edittypes))) {
        $types = array_keys($edittypes);
        $tab = $types[0];
    }

    $galleryurl = $CFG->wwwroot.'/mod/lightboxgallery/view.php?id='.$cm->id.'&amp;page='.$page.'&amp;editing=1';

    $navlinks = array();
    $navlinks[] = array('name' => get_string('editimage', 'lightboxgallery'), 'link' => '', 'type' => 'misc');
    $navlinks[] = array('name' => get_string('edit_' . $tab, 'lightboxgallery'), 'link' => '', 'type' => 'misc');

    $navigation = build_navigation($navlinks, $cm);

    $button = print_single_button($CFG->wwwroot.'/mod/lightboxgallery/view.php', array('id' => $cm->id, 'page' => $page, 'editing' => '1'), get_string('backtogallery', 'lightboxgallery'), 'get', '', true);

    print_header($course->shortname.': '.$gallery->name.': '.$image, $course->fullname, $navigation, '', '', true, $button, navmenu($course, $cm));

    echo('<br />');

    print_tabs(array($tabs), $tab);

    require($CFG->dirroot.'/mod/lightboxgallery/edittype/'.$tab.'/'.$tab.'.class.php');
    $editclass = 'edittype_'.$tab;
    $editinstance = new $editclass($gallery, $image, $tab);

    if (! file_exists($editinstance->imagepath)) {
        error(get_string('errornofile', 'lightboxgallery'), $galleryurl);
    }

    if ($editinstance->processing() && confirm_sesskey()) {
        add_to_log($course->id, 'lightboxgallery', 'editimage', 'view.php?id='.$cm->id, $tab.' '.$image, $cm->id, $USER->id);
        $editinstance->process_form();
        redirect($CFG->wwwroot.'/mod/lightboxgallery/imageedit.php?id='.$gallery->id.'&amp;image='.$image.'&amp;tab='.$tab);
    }

    $table = new object;
    $table->width = '*';

    if ($editinstance->showthumb) {
        $textlib = textlib_get_instance();
        $imagelabel = ($textlib->strlen($image) > MAX_IMAGE_LABEL ? $textlib->substr($image, 0, MAX_IMAGE_LABEL).'...' : $image);

        $table->align = array('center', 'center');
        $table->size = array('*', '*');
        $table->data[] = array(lightboxgallery_image_thumbnail($course->id, $gallery, $image).'<br /><span title="'.$image.'">'.$imagelabel.'</span>', $editinstance->output());
    } else {
        $table->align = array('center');
        $table->size = array('*');
        $table->data[] = array($editinstance->output());
    }

    print_table($table);

    $dataroot = $CFG->dataroot.'/'.$course->id.'/'.$gallery->folder;
    if ($dirimages = lightboxgallery_directory_images($dataroot)) {
        sort($dirimages);
        $options = array();
        foreach ($dirimages as $dirimage) {
            $options[$dirimage] = $dirimage;
        }
        $index = array_search($image, $dirimages);

        echo('<table align="center" class="menubar">
                <tr>');
        if ($index > 0) {
            echo('<td>');
            print_single_button($CFG->wwwroot.'/mod/lightboxgallery/imageedit.php', array('id' => $gallery->id, 'tab' => $tab, 'page' => $page, 'image' => $dirimages[$index - 1]), '←');
            echo('</td>');
        }
        echo('<td>
                <form method="get" action="'.$CFG->wwwroot.'/mod/lightboxgallery/imageedit.php">
                  <input type="hidden" name="id" value="'.$gallery->id.'" />
                  <input type="hidden" name="tab" value="'.$tab.'" />
                  <input type="hidden" name="page" value="'.$page.'" />');
        choose_from_menu($options, 'image', $image, null, 'submit()');
        echo('  </form>
              </td>');
        if ($index < count($dirimages) - 1) {
            echo('<td>');
            print_single_button($CFG->wwwroot.'/mod/lightboxgallery/imageedit.php', array('id' => $gallery->id, 'tab' => $tab, 'page' => $page, 'image' => $dirimages[$index + 1]), '→');
            echo('</td>');
        }
        echo('  </tr>
              </table>');
    }
   
    print_footer($course);

?>
