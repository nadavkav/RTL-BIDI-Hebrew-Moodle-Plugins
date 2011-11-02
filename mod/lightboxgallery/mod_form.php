<?php

    require_once($CFG->dirroot . '/course/moodleform_mod.php');

    class mod_lightboxgallery_mod_form extends moodleform_mod {

        function definition() {

            global $CFG,$COURSE;

            $mform =& $this->_form;

            // General options

            $mform->addElement('header', 'general', get_string('general', 'form'));

            $mform->addElement('text', 'name', get_string('name'), array('size' => '48', 'maxlength' => '255'));
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', null, 'required', null, 'client');
            $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

            $mform->addElement('htmleditor', 'description', get_string('description'), array('rows' => '33'));
            $mform->setType('content', PARAM_RAW);

            $mform->addElement('select', 'folder', get_string('imagedirectory', 'lightboxgallery'), $this->get_course_directories());
            $mform->setType('folder', PARAM_TEXT);
            $mform->setHelpButton('folder', array('folder', get_string('imagedirectory', 'lightboxgallery'), 'lightboxgallery'));

            $mform->addElement('static', 'linktofilesandfolders', get_string('linktofilesandfolders','lightboxgallery'),
                "<a target=\"_new\" href=\"$CFG->wwwroot/files/index.php?id=$COURSE->id\">".get_string('openinnewwindow','lightboxgallery')."</a>");
            $mform->setType('linktofilesandfolders', PARAM_TEXT);

            // Advanced options

            $mform->addElement('header', 'galleryoptions', get_string('advanced'));

            $mform->addElement('select', 'perpage', get_string('imagesperpage', 'lightboxgallery'), $this->get_perpage_options());
            $mform->setType('perpage', PARAM_INTEGER);
            $mform->setAdvanced('perpage');

            $autoresizegroup = array();
            $autoresizegroup[] = &$mform->createElement('select', 'autoresize', get_string('autoresize', 'lightboxgallery'), $this->get_autoresize_options());
            $autoresizegroup[] = &$mform->createElement('checkbox', 'autoresizedisabled', null, get_string('disable'));
            $mform->addGroup($autoresizegroup, 'autoresizegroup', get_string('autoresize', 'lightboxgallery'), ' ', false);
            $mform->setType('autoresize', PARAM_INTEGER);
			$mform->setDefault('autoresize', 3);
            $mform->disabledIf('autoresizegroup', 'autoresizedisabled', 'checked');
            $mform->setAdvanced('autoresizegroup');
            $mform->setHelpButton('autoresizegroup', array('autoresize', get_string('autoresize', 'lightboxgallery'), 'lightboxgallery'));

            $mform->addElement('select', 'resize', sprintf('%s (%s)', get_string('edit_resize', 'lightboxgallery'), strtolower(get_string('upload'))), lightboxgallery_resize_options());
            $mform->setType('resize', PARAM_INTEGER);
			$mform->setDefault('resize', 3);
            $mform->setAdvanced('resize');
            $mform->disabledIf('resize', 'autoresize', 'eq', 1);
            $mform->disabledIf('resize', 'autoresizedisabled', 'checked');

            $yesno = array(0 => get_string('no'), 1 => get_string('yes'));

            $mform->addElement('select', 'comments', get_string('allowcomments', 'lightboxgallery'), $yesno);
            $mform->setType('comments', PARAM_INTEGER);
            $mform->setAdvanced('comments');

            $mform->addElement('select', 'public', get_string('makepublic', 'lightboxgallery'), $yesno);
            $mform->setType('public', PARAM_INTEGER);
            $mform->setAdvanced('public');

            if (lightboxgallery_rss_enabled()) {
                $mform->addElement('select', 'rss', get_string('allowrss', 'lightboxgallery'), $yesno);
                $mform->setType('rss', PARAM_INTEGER);
                $mform->setAdvanced('rss');
            } else {
                $mform->addElement('static', 'rssdisabled', get_string('allowrss', 'lightboxgallery'), get_string('rssglobaldisabled', 'admin'));
                $mform->setAdvanced('rssdisabled');
            }

            $mform->addElement('select', 'extinfo', get_string('extendedinfo', 'lightboxgallery'), $yesno);
            $mform->setType('extinfo', PARAM_INTEGER);
            $mform->setAdvanced('extinfo');

            $mform->addElement('select', 'coursefp', get_string('coursefp', 'lightboxgallery'), $yesno); // show a lightbox widget on the course's front page (nadavkav patch)
            $mform->setType('coursefp', PARAM_INTEGER);
            $mform->setAdvanced('coursefp');


            // Module options

            $features = array('groups' => false, 'groupings' => false, 'groupmembersonly' => false,
                              'outcomes' => false, 'gradecat' => false, 'idnumber' => false);

            $this->standard_coursemodule_elements($features);

	    //$mform->addElement('modvisible', 'visible', get_string('visible'));
	    $mform->setType('visible', PARAM_INT);
	    $mform->setDefault('visible', 1);

            $this->add_action_buttons();

        }

        function data_preprocessing(&$defaults){
            $defaults['autoresizedisabled'] = 0;//($defaults['autoresize'] ? 0 : 1);
        }

        // Custom functions

        function get_course_directories() {
            global $CFG, $COURSE;
            $dirs = get_directory_list($CFG->dataroot . '/' . $COURSE->id, array($CFG->moddata, 'backupdata', '_thumb'), true, true, false);
            $result = array('' => get_string('maindirectory', 'resource'));
            foreach ($dirs as $dir) {
                $result[$dir] = $dir;
            }
            return $result;
        }

        function get_perpage_options() {
            $perpages = array(10, 25, 50, 100, 200);
            $result = array(0 => get_string('showall', 'lightboxgallery'));
            foreach ($perpages as $perpage) {
                $result[$perpage] = $perpage;
            }
            return $result;
        }

        function get_autoresize_options() {
            $screen = get_string('screen', 'lightboxgallery');
            $upload = get_string('upload', 'lightboxgallery');
            return array(AUTO_RESIZE_SCREEN => $screen,
                         AUTO_RESIZE_UPLOAD => $upload,
                         AUTO_RESIZE_BOTH   => $screen . ' &amp; ' . $upload);
        }

    }
?>
