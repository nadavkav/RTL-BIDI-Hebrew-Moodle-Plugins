<?php

/**
 * This class builds a marking block on the front page which loads assignments and submissions
 * dynamically into a tree structure using AJAX. All marking occurs in pop-up windows and each node
 * removes itself from the tree after its pop up is graded.
 */

class block_ajax_marking extends block_base {

    function init() {
        $this->title   = get_string('ajaxmarking', 'block_ajax_marking');
        $this->version = 2012070300;
    }

    function specialization() {
        $this->title = get_string('marking', 'block_ajax_marking');
    }

    function get_content() {

        if ($this->content != NULL) {
            return $this->content;
        }

        global $CFG, $USER;


        // admins will have a problem as they will see all the courses on the entire site
        // retrieve the teacher role id (3)
        $teacher_role     =  get_field('role','id','shortname','editingteacher');
        // retrieve the non-editing teacher role id (4)
        $ne_teacher_role  =  get_field('role','id','shortname','teacher');

        // check to see if any roles allow grading of assessments
        $coursecheck = 0;
        $courses = get_my_courses($USER->id, 'fullname', 'id, visible');

        foreach ($courses as $course) {

            // exclude the front page
            if ($course->id == 1) {
                continue;
            }

            // role check bit borrowed from block_narking, thanks to Mark J Tyers [ZANNET]
            $context = get_context_instance(CONTEXT_COURSE, $course->id);

            // check for editing teachers
            $teachers = get_role_users($teacher_role, $context, true);
            $correct_role = false;
            if ($teachers) {
                foreach($teachers as $teacher) {
                    if ($teacher->id == $USER->id) {
                            $correct_role = true;
                    }
                }
            }
            // check for non-editing teachers
            $teachers_ne = get_role_users($ne_teacher_role, $context, true);
            if ($teachers_ne) {
                foreach($teachers_ne as $teacher) {
                    if ($teacher->id == $USER->id) {
                        $correct_role = true;
                    }
                }
            }
            // skip this course if no teacher or teacher_non_editing role
            if (!$correct_role) {
                continue;
            }

            $coursecheck++;

        }
        if ($coursecheck>0) {
            // Grading permissions exist in at least one course, so display the block

            //start building content output
            $this->content = new stdClass;

            // make the non-ajax list whatever happens. Then allow the AJAX tree to usurp it if
            // necessary
            require_once($CFG->dirroot.'/blocks/ajax_marking/html_list.php');
            $AMB_html_list_object = new AMB_html_list;
            $this->content->text .= '<div id="AMB_html_list">';
            $this->content->text .= $AMB_html_list_object->make_html_list();
            $this->content->text .= '</div>';
            $this->content->footer = '';

            // Build the AJAX stuff on top of the plain HTML list

            // Add a style to hide the HTML list and prevent flicker
            $s  = '<script type="text/javascript" defer="defer">';
            $s .= '/* <![CDATA[ */ var styleElement = document.createElement("style");';
            $s .= 'styleElement.type = "text/css";';
            $s .= 'if (styleElement.styleSheet) {';
            $s .=     'styleElement.styleSheet.cssText = "#AMB_html_list { display: none; }";';
            $s .= '} else {';
            $s .=     'styleElement.appendChild(document.createTextNode("#AMB_html_list {display: none;}"));';
            $s .= '}';
            $s .= 'document.getElementsByTagName("head")[0].appendChild(styleElement);';
            $s .= '/* ]]> */</script>';
            $this->content->text .=  $s;

            $variables  = array(
                    'wwwroot'             => $CFG->wwwroot,
                    'totalMessage'        => get_string('total',              'block_ajax_marking'),
                    'userid'              => $USER->id,
                    'instructions'        => get_string('instructions',       'block_ajax_marking'),
                    'configNothingString' => get_string('config_nothing',     'block_ajax_marking'),
                    'nothingString'       => get_string('nothing',            'block_ajax_marking'),
                    'refreshString'       => get_string('refresh',            'block_ajax_marking'),
                    'configureString'     => get_string('configure',          'block_ajax_marking'),
                    'forumSaveString'     => get_string('sendinratings',      'forum'),
                    'quizSaveString'      => get_string('savechanges'),
                    'journalSaveString'   => get_string('saveallfeedback',    'journal'),
                    'connectFail'         => get_string('connect_fail',       'block_ajax_marking'),
                    'nogroups'            => get_string('nogroups',           'block_ajax_marking'),
                    'headertext'          => get_string('headertext',         'block_ajax_marking'),
                    'fullname'            => fullname($USER),
                    'confAssessmentShow'  => get_string('confAssessmentShow', 'block_ajax_marking'),
                    'confCourseShow'      => get_string('confCourseShow',     'block_ajax_marking'),
                    'confGroups'          => get_string('confGroups',         'block_ajax_marking'),
                    'confAssessmentHide'  => get_string('confAssessmentHide', 'block_ajax_marking'),
                    'confCourseHide'      => get_string('confCourseHide',     'block_ajax_marking'),
                    'confDefault'         => get_string('confDefault',        'block_ajax_marking'));

            // for integrating the block_marking stuff, this stuff (divs) should all be created
            // by javascript.
            $this->content->text .= "
                <div id='total'>
                    <div id='totalmessage'></div>
                    <div id='count'></div>
                    <div id='mainIcon'></div>
                </div>
                <div id='status'> </div>
                <div id='treediv' class='yui-skin-sam'>";

            // Don't warn about javascript if the sreenreader option is set - it was deliberate
            if (!$USER->screenreader) {
                $this->content->text .= "<noscript><p>AJAX marking block requires javascript, ";
                $this->content->text .= "but you have it turned off.</p></noscript>";
            }

            // Add a script that makes all of the PHP variables available to javascript
            $this->content->text .= '</div><div id="javaValues"><script type="text/javascript"';
            $this->content->text .= '>/* <![CDATA[ */ var amVariables = {';

            // loop through the PHP $variables above, making them into the right format
            $check = 0;
            foreach ($variables as $variable => $value) {
                if ($check > 0) {
                    // no initial comma, but one before all the others
                    $this->content->text .= ", ";
                }
                $this->content->text .= $variable.": '".$value."'";
                $check ++;
            }

            $this->content->text .=    '};
                    /* ]]> */</script>
                </div>';



            // Add all of the javascript libraries that the above script depends on
            $scripts = array(
                    'yui_yahoo',
                    'yui_event',
                    'yui_dom',
                    'yui_logger',
                    $CFG->wwwroot.'/lib/yui/treeview/treeview-debug.js',
                    'yui_connection',
                    'yui_dom-event',
                    'yui_container',
                    'yui_utilities',
                    $CFG->wwwroot.'/lib/yui/container/container_core-min.js',
                    $CFG->wwwroot.'/lib/yui/menu/menu-min.js',
                    'yui_json',
                    'yui_button',
                    $CFG->wwwroot.'/blocks/ajax_marking/javascript.js'
                );

            // also need to add any js from individual modules
            foreach ($AMB_html_list_object->modulesettings as $modname => $module) {
                // echo "{$CFG->dirroot}{$module->dir}/{$modname}_grading.php ";

                $file_in_mod_directory  = file_exists("{$CFG->dirroot}{$module->dir}/{$modname}_grading.js");
                $file_in_block_directory = file_exists("{$CFG->dirroot}/blocks/ajax_marking/{$modname}_grading.js");
                                     //   echo "{$CFG->dirroot}blocks/ajax_marking/{$modname}_grading.js";

                if ($file_in_mod_directory) {
                    $scripts[] = "{$CFG->wwwroot}{$module->dir}/{$modname}_grading.js";
                } elseif ($file_in_block_directory) {
                    $scripts[] = "{$CFG->dirroot}/blocks/ajax_marking/{$modname}_grading.js";
                }

            }


            $this->content->text .= require_js($scripts)."";
            // Add the script that will initialise the main AJAX tree widget
            //$this->content->text .= '<script type="text/javascript" defer="defer" '
             //   .'src="'.$CFG->wwwroot.'/blocks/ajax_marking/javascript.js"></script>';

            $this->content->text .= '<script type="text/javascript" defer="defer" >YAHOO.ajax_marking_block.initialise();</script>';

            // Add footer, which will have button added dynamically (not needed if javascript is
            // enabled)
            $this->content->footer .= '<div id="conf_left"></div><div id="conf_right"></div>';


        } else {
            // no grading permissions in any courses - don't display the block. Exception for
            // when the block is just installed and editing is on. Might look broken otherwise.
            if (isediting()) {
                $this->content->text .= get_string('config_nothing', 'block_ajax_marking');
                $this->content->footer = '';
            }

        }
        return $this->content;
    }

    function instance_allow_config() {
        return false;
    }

    /**
     * Runs the check for plugins after the first install.
     */
    function after_install() {

        global $CFG;

        require_once($CFG->dirroot.'/blocks/ajax_marking/db/upgrade.php');
        AMB_update_modules();

    }
}
