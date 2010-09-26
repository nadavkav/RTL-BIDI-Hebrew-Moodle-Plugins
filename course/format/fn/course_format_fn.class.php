<?php // $Id: course_format_fn.class.php,v 1.4 2009/04/28 18:55:37 mchurch Exp $
/**
 * course_format is the base class for all course formats
 *
 * This class provides all the functionality for a course format
 */

define ('FNMAXTABS', 10);

/**
 * Standard base class for all course formats
 */
class course_format_fn extends course_format {

/**
 * Contructor
 *
 * @param $course object The pre-defined course object. Passed by reference, so that extended info can be added.
 *
 */
    function course_format_fn(&$course) {
        global $mods, $modnames, $modnamesplural, $modnamesused, $sections;

        parent::course_format($course);

        $this->mods             = &$mods;
        $this->modnames         = &$modnames;
        $this->modnamesplural   = &$modnamesplural;
        $this->modnamesused     = &$modnamesused;
        $this->sections         = &$sections;
    }

/******************************************************************************/
/*   MAIN DATA FUNCTIONS:                                                     */
/******************************************************************************/
/**
 * Get any additional course data and return it. Also add it to the course property, and the optional
 * course parameter.
 *
 * @param $course object Optional object to add the new data to.
 * @return object The extra course data.
 *
 */
    function get_course($course=null) {
        if (!empty($course->id)) {
            $extradata = get_records('course_config_fn', 'courseid', $course->id);
        } else if (!empty($this->course->id)) {
            $extradata = get_records('course_config_fn', 'courseid', $this->course->id);
        } else {
            $extradata = false;
        }

        if (is_null($course)) {
            $course = new Object();
        }

        if ($extradata) {
            foreach ($extradata as $extra) {
                $this->course->{$extra->variable} = $extra->value;
                $course->{$extra->variable} = $extra->value;
            }
        }

        $this->course->uselogo = !empty($course->logo);
        $course->uselogo = !empty($course->logo);

        return $course;
    }

/**
 * Update extra course data. Check if this is a new course, and create it if so.
 *
 * @param $data object The extra course data to update.
 * @param $editform object The form object. This is needed if there is uploaded file processing.
 *
 * @return boolean Success or failure.
 *
 */
    function update_course($data, $editform=null) {
        global $CFG;

        if (!empty($data)) {
            $courseid = $data->id;
            /// Unset things we don't need.
            unset($data->id);
            unset($data->formatsettings);
            unset($data->newcourse);
            unset($data->submitbutton);
            unset($data->MAX_FILE_SIZE);

            /// Handle any logo logic:
            if (empty($data->uselogo)) {
                $data->logo = '';
            }

            $variables = get_object_vars($data);
            foreach ($variables as $variable => $value) {
                if ($id = get_field('course_config_fn', 'id', 'courseid', $courseid, 'variable', $variable)) {
                    set_field('course_config_fn', 'value', $value, 'id', $id);
                } else {
                    $record = new Object();
                    $record->courseid = $courseid;
                    $record->variable = clean_param($variable, PARAM_CLEAN);
                    $record->value = clean_param($value, PARAM_CLEAN);
                    insert_record('course_config_fn', $record);
                }
            }
        }
        return true;
    }

/******************************************************************************/
/*   MAIN DISPLAY FUNCTIONS:                                                  */
/******************************************************************************/
/**
 * Print out the header and any pre-page content information.
 *
 */
    function print_header() {
        global $CFG, $PAGE, $USER, $COURSE, $course;

        // AJAX-capable course format?
        $CFG->useajax = false;
        $ajaxformatfile = $CFG->dirroot.'/course/format/'.$course->format.'/ajax.php';
        $bodytags = '';

        if (file_exists($ajaxformatfile)) {      // Needs to exist otherwise no AJAX by default

            $CFG->ajaxcapable = false;           // May be overridden later by ajaxformatfile
            $CFG->ajaxtestedbrowsers = array();  // May be overridden later by ajaxformatfile

            require_once($ajaxformatfile);

            if (!empty($USER->editing) && $CFG->ajaxcapable) {   // Course-based switches

                if (ajaxenabled($CFG->ajaxtestedbrowsers)) {     // rowser, user and site-based switches

                    require_js(array('yui_yahoo',
                                     'yui_dom',
                                     'yui_event',
                                     'yui_dragdrop',
                                     'yui_connection',
                                     'ajaxcourse_blocks',
                                     'ajaxcourse_sections'));

                    if (debugging('', DEBUG_DEVELOPER)) {
                        require_js(array('yui_logger'));

                        $bodytags = 'onload = "javascript:
                        show_logger = function() {
                            var logreader = new YAHOO.widget.LogReader();
                            logreader.newestOnTop = false;
                            logreader.setTitle(\'Moodle Debug: YUI Log Console\');
                        };
                        show_logger();
                        "';
                    }

                    // Okay, global variable alert. VERY UGLY. We need to create
                    // this object here before the <blockname>_print_block()
                    // function is called, since that function needs to set some
                    // stuff in the javascriptportal object.
                    $COURSE->javascriptportal = new jsportal();
                    $CFG->useajax = true;
                }
            }
        }

        $CFG->blocksdrag = $CFG->useajax;   // this will add a new class to the header so we can style differently

/// *** The only part we are really changing is here....
        $breadcrumbs = array($this->course->shortname => $CFG->wwwroot.'/course/view.php?id='.$this->course->id);
        $total     = count($breadcrumbs);
        $current   = 1;
        $crumbtext = '';
        foreach($breadcrumbs as $text => $href) {
            if($current++ == $total) {
                $crumbtext .= ' '.$text;
            }
            else {
                $crumbtext .= ' <a href="'.$href.'">'.$text.'</a> ->';
            }
        }

        // The "Editing On" button will be appearing only in the "main" course screen
        // (i.e., no breadcrumbs other than the default one added inside this function)
        $buttons = switchroles_form($this->course->id) . update_course_icon($this->course->id );

        $title = get_string('course').': '.$this->course->fullname;
        if (empty($this->course->logo)) {
            $heading = $this->course->fullname;
        } else {
            $heading = '<img src="'.$CFG->wwwroot.'/file.php/'.$this->course->id.'/'.$this->course->logo.'" '.
                       'alt="'.$this->course->fullname.'" />';
        }

        print_header($title, $heading, $crumbtext, '', '', true, $buttons,
                     user_login_string($this->course, $USER), false, $bodytags);

        echo '<div class="accesshide"><a href="#startofcontent">'.get_string('skiptomaincontent').'</a></div>';
    }

/**
 * Overload for now just to move the globals into this object's structure.
 * (Eventually, these should be part of the standard object)
 */
    function print_body () {
        parent::print_body();
    }

/******************************************************************************/
/*   MAIN EDIT FUNCTIONS:                                                     */
/******************************************************************************/

    function edit_form($action='edit.php', $params='') {
        global $CFG;

        require_once($CFG->dirroot.'/course/format/fn/edit_form.php');

        return new course_fn_edit_form($action, $params);
    }

/******************************************************************************/
/*   OTHER DISPLAY FUNCTIONS:                                                 */
/******************************************************************************/
/**
 * If used, this will just call the library function (for now). Replace this with your own to make it
 * do what you want.
 *
 */
    function print_section($section, $absolute=false, $width="100%", $return=false) {
        if ($return) {
            ob_start();
        }
        print_section($this->course, $section, $this->mods, $this->modnamesused, $absolute, $width);
        if ($return) {
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        } else {
            return '';
        }
    }
/**
 * If used, this will just call the library function (for now). Replace this with your own to make it
 * do what you want.
 *
 */
    function print_section_add_menus($section, $vertical=false, $return=false) {
        return print_section_add_menus($this->course, $section, $this->modnames, $vertical, $return);
    }

/******************************************************************************/
/*   LIBRARY REPLACEMENTS:                                                    */
/******************************************************************************/

    function print_section_fn(&$section, $absolute=false, $width="100%") {
    /// Prints a section full of activity modules
        global $CFG, $USER, $THEME;

        static $groupbuttons;
        static $groupbuttonslink;
        static $isteacher, $isteacheredit;
        static $isediting;
        static $ismoving;
        static $strmovehere;
        static $strmovefull;

        $labelformatoptions = New stdClass;

        if (!isset($isteacher)) {
            $groupbuttons     = $this->course->groupmode;
            $groupbuttonslink = (!$this->course->groupmodeforce);
            $isteacher = has_capability('moodle/grade:viewall', $this->context);
            $isteacheredit = has_capability('moodle/course:manageactivities', $this->context);
            $isediting = isediting($this->course->id);
            $ismoving = ismoving($this->course->id);
            if ($ismoving) {
                $strmovehere = get_string("movehere");
                $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
            }
        }

    //  Replace this with language file changes (eventually).
        $link_title = array (
                    'resource'  => 'Lesson',
                    'choice'    => 'Opinion',
                    'lesson'    => 'Reading'
                 );

        $labelformatoptions->noclean = true;

        $modinfo = unserialize($this->course->modinfo);

        echo "<table cellpadding=\"1\" cellspacing=\"0\" align=\"center\" width=\"$width\">\n";
        if (!empty($section->sequence)) {

            $sectionmods = explode(",", $section->sequence);

            foreach ($sectionmods as $modnumber) {
                if (empty($this->mods[$modnumber])) {
                    continue;
                }
                $mod = $this->mods[$modnumber];

    /// mrc - 20042312 - Begin G8 First Nations School Customization:
    ///     Added check for 'teacheredit' in order to hide invisible activities from
    ///     non-editing teachers.
    ///            if ($mod->visible or $isteacher) {
                if ($mod->visible or $isteacheredit) {
    /// mrc - 20042312 - End G8 First Nations School Customization:
                    if (right_to_left()){$tdalign = 'right';} else {$tdalign = 'left';}
                    echo "<tr><td align=\"$tdalign\" class=\"activity$mod->modname\" width=\"$width\">";
                    if ($ismoving) {
                        if ($mod->id == $USER->activitycopy) {
                            continue;
                        }
                        echo "<a title=\"$strmovefull\"".
                             " href=\"$CFG->wwwroot/course/mod.php?moveto=$mod->id&amp;sesskey=$USER->sesskey\">".
                             "<img height=\"16\" width=\"80\" src=\"$CFG->pixpath/movehere.gif\" ".
                             " alt=\"$strmovehere\" border=\"0\"></a><br />\n";
                    }
                    $instancename = urldecode($modinfo[$modnumber]->name);
                    if (!empty($CFG->filterall)) {
                        $instancename = filter_text("<nolink>$instancename</nolink>", $this->course->id);
                    }

                    if (!empty($modinfo[$modnumber]->extra)) {
                        $extra = urldecode($modinfo[$modnumber]->extra);
                    } else {
                        $extra = "";
                    }

                    if (!empty($modinfo[$modnumber]->icon)) {
                        $icon = "$CFG->pixpath/".urldecode($modinfo[$modnumber]->icon);
                    } else {
                        $icon = "$CFG->modpixpath/$mod->modname/icon.gif";
                    }

                    if ($mod->indent) {
                        print_spacer(12, 20 * $mod->indent, false);
                    }

    //                /// If the questionnaire is mandatory
    //                if (($mod->modname == 'questionnaire') && empty($mandatorypopup)) {
    //                    $mandatorypopup = is_mod_mandatory($mod, $USER->id);
    //                }

                    if ($mod->modname == "label") {
                        if (empty($this->course->usemandatory) || empty($mod->mandatory)) {
                            if (!$mod->visible) {
                                echo "<span class=\"dimmed_text\">";
                            }
                            echo format_text($extra, FORMAT_HTML, $labelformatoptions);
                            if (!$mod->visible) {
                                echo "</span>";
                            }
                        } else {
                            if ($isediting) {
                                $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                                $alttext = isset($link_title[$mod->modname]) ? $link_title[$mod->modname] : $mod->modfullname;
                                echo "<img src=\"$icon\"".
                                     " height=16 width=16 alt=\"$alttext\">".
                                     " <font size=2><a title=\"$alttext\" $linkcss $extra".
                                     " href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">$instancename</a></font>";
                            }
                        }
                    }
                    /// G8 - 20040907
                    /// Questionnaire custom code:
                    /// If the questionnaire isn't eligible, don't display it.
    //                else if ($mod->modname == 'questionnaire' &&
    //                         (!$isteacheredit && !is_activity_eligible($mod, $USER->id))) {
    //                        /// do nothing - don't display.
    //                }

                    else if (!$isediting && ($mod->modname == 'forum') && isset($this->course->expforumsec) &&
                             ($this->course->expforumsec == $section->section)) {
                        $page = optional_param('page', 0, PARAM_INT);
                        $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;  // Group change requested?
                        $forum = get_record("forum", "id", $mod->instance);
                        $groupmode = groupmode($this->course, $mod);   // Groups are being used
                        $currentgroup = get_and_set_current_group($this->course, $groupmode, $changegroup);
                        forum_print_latest_discussions($this->course, $forum, $CFG->forum_manydiscussions, 'header', '', $currentgroup, $groupmode, $page);
                    }

                    else { // Normal activity

                        if (!$isteacher && !empty($this->course->activitytracking)) {
                            $act_compl = is_activity_complete($mod, $USER->id);
                            if ($act_compl === false) {
                                echo ' <img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.'/pix/incomplete.gif" '.
                                     'height="16" width="16" alt="Activity Not Completed" hspace="10" '.
                                     'title="Activity Not Completed">';
                            }
                            else if (($act_compl === true) || (is_int($act_compl) && ($act_compl >= 50))) {
                                echo ' <img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.'/pix/completed.gif" '.
                                     'height="16" width="16" alt="Activity Completed" hspace="10" '.
                                     'title="Activity Completed">';
                            }
                            else if (is_int($act_compl)) {
                                echo ' <img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.'/pix/completedpoor.gif" '.
                                     'height="16" width="16" alt="Activity Completed Poorly" hspace="10" '.
                                     'title="Activity Completed Poorly">';
                            }
                            else if ($act_compl == 'submitted') {
                                echo ' <img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.'/pix/submitted.gif" '.
                                     'height="16" width="16" alt="Activity Submitted" hspace="10" '.
                                     'title="Activity Submitted">';
                            }
                        }

                        $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                        $alttext = isset($link_title[$mod->modname]) ? $link_title[$mod->modname] : $mod->modfullname;
                        echo "<img src=\"$icon\"".
                             " height=16 width=16 alt=\"$alttext\">".
                             " <font size=2><a title=\"$alttext\" $linkcss $extra".
                             " href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">$instancename</a></font>";
                    }
                    if ($isediting) {
                        // TODO: we must define this as mod property!
                        if ($groupbuttons and $mod->modname != 'label' and $mod->modname != 'resource' and $mod->modname != 'glossary') {
                            if (! $mod->groupmodelink = $groupbuttonslink) {
                                $mod->groupmode = $course->groupmode;
                            }

                        } else {
                            $mod->groupmode = false;
                        }
                        echo "&nbsp;&nbsp;";
                        echo make_editing_buttons($mod, $absolute, true, $mod->indent, $section->section);
//                        echo make_editing_buttons($mod, $absolute, true, $mod->indent);

                        if (isadmin()) {
                            if (empty($THEME->custompix)) {
                                $pixpath = $CFG->wwwroot.'/pix';
                            } else {
                                $pixpath = $CFG->wwwroot.'/theme/'.$CFG->theme.'/pix';
                            }
                            if ($mod->hideingradebook) {
                                echo '<a title="Show Grades" href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.
                                     '&hidegrades=0&mid='.$mod->id.'&amp;sesskey='.$USER->sesskey.'">'.
                                     '<img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.
                                     '/pix/hidegrades.gif" hspace="2" height="11" width="11" border="0" /></a>';
                            }
                            else {
                                echo '<a title="Hide Grades" href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.
                                     '&hidegrades=1&mid='.$mod->id.'&amp;sesskey='.$USER->sesskey.'">'.
                                     '<img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.
                                     '/pix/showgrades.gif" hspace="2" height="11" width="11" border="0" /></a>';
                            }

                            if (!empty($this->course->usemandatory)) {
                                if ($mod->mandatory) {
                                    echo '<a title="Mandatory off" href="'.$CFG->wwwroot.'/course/format/'.
                                         $this->course->format.'/mod.php?mandatory=0&id='.$mod->id.
                                         '&amp;sesskey='.$USER->sesskey.'">'.
                                         '<img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.
                                         '/pix/lock.gif" hspace="2" height="11" width="11" border="0" /></a>';
                                } else {
                                    echo '<a title="Mandatory on" href="'.$CFG->wwwroot.'/course/format/'.
                                         $this->course->format.'/mod.php?mandatory=1&id='.$mod->id.
                                         '&amp;sesskey='.$USER->sesskey.'">'.
                                         '<img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.
                                         '/pix/unlock.gif" hspace="2" height="11" width="11" border="0" /></a>';
                                }
                            }
                        }
                    }
                    echo "</td>";
                    echo "</tr>";
                }
            }
        }
        if ($ismoving) {
            echo "<tr><td><a title=\"$strmovefull\"".
                 " href=\"mod.php?movetosection=$section->id".
                 '&amp;sesskey='.$USER->sesskey.'">'.
                 "<img height=\"16\" width=\"80\" src=\"$CFG->pixpath/movehere.gif\" ".
                 " alt=\"$strmovehere\" border=\"0\"></a></td></tr>\n";
        }
        echo "</table>\n\n";

    //    return $mandatorypopup;
    }

/******************************************************************************/
/*   CUSTOM FUNCTIONS:                                                        */
/******************************************************************************/

    function handle_extra_actions() {
        global $USER, $CFG;

///     Handle activity complete.
///
        if (($resid = optional_param('rescomplete', 0, PARAM_INT)) && confirm_sesskey()) {
            if (! $cm = get_record("course_modules", "id", optional_param('id', 0, PARAM_INT))) {
                error("This course module doesn't exist");
            }
            set_resource_complete($resid, $USER->id);

        } else if ((($hide = optional_param('hidegrades', false, PARAM_INT)) !== false) && confirm_sesskey()) {
            if (! $cm = get_record("course_modules", "id", optional_param('mid', 0, PARAM_INT))) {
                error("This course module doesn't exist");
            }
            /// Replace with a capability...
            if (!isadmin()) {
                error("You can't modify the gradebook settings!");
            }
            $this->set_gradebook_for_module($cm->id, $hide);

        } else if (isset($_GET['mandatory']) and confirm_sesskey()) {

            if (! $cm = get_record("course_modules", "id", $_GET['id'])) {
                error("This course module doesn't exist");
            }

            if (!isadmin()) {
                error("You can't modify the mandatory settings!");
            }

            fn_set_mandatory_for_module($cm->id, $_GET['mandatory']);
        } else if (isset($_POST['sec0title'])) {
            if (!$course = get_record('course', 'id', $_POST['id'])) {
                error('This course doesn\'t exist.');
            }
            FN_get_course($course);
            $course->sec0title = $_POST['sec0title'];
            FN_update_course($course);
            $cm->course = $course->id;
        } else if (isset($_GET['openchat'])) {
            if ($varrec = get_record('course_config_FN', 'courseid', $this->course->id, 'variable', 'classchatopen')) {
                $varrec->value = $_GET['openchat'];
                update_record('course_config_fn', $varrec);
            } else {
                $varrec->courseid = $this->course->id;
                $varrec->variable = 'classchatopen';
                $varrec->value = $_GET['openchat'];
                insert_record('course_config_fn', $varrec);
            }
            $this->course->classchatopen = $varrec->value;
            $cm->course = $tgis->course->id;
        }
    }

    function add_extra_module_info() {
        $modsextra = get_records('fn_coursemodule_extra', 'courseid', $this->course->id, 'cmid', 'cmid,hideingradebook,mandatory');
        if (empty($this->mods)) {
            return;
        }
        foreach ($this->mods as $id => $mod) {
            $this->mods[$id]->hideingradebook = isset($modsextra[$id]->hideingradebook) ? $modsextra[$id]->hideingradebook : 0;
            $this->mods[$id]->mandatory = isset($modsextra[$id]->mandatory) ? $modsextra[$id]->mandatory : 0;
        }
    }

    function set_gradebook_for_module($id, $hidegrades) {
        if ($oldrec = get_record('fn_coursemodule_extra', 'cmid', $id)) {
            $oldrec->hideingradebook = $hidegrades;
            return update_record('fn_coursemodule_extra', $oldrec);
        } else {
            $newrec = new Object();
            $newrec->courseid = $this->course->id;
            $newrec->cmid = $id;
            $newrec->hideingradebook = $hidegrades;
            return insert_record('fn_coursemodule_extra', $newrec);
        }
    }

    function all_mandatory_completed() {
        global $USER;

        if (isteacheredit($this->course->id)) {
            return true;
        }

        foreach ($this->mods as $mod) {
            if ($mod->mandatory && $mod->visible && (is_activity_complete($mod, $USER->id) === false)) {
                return false;
            }
        }
        return true;
    }

    function print_mandatory_section() {
        global $CFG, $USER, $THEME;

        $labeltext = '';
        $activitytext = '';

        /// Determine order using all sections.
        $orderedmods = array();
        foreach ($this->sections as $section) {
            $modseq = explode(",", $section->sequence);
            if (!empty($modseq)) {
                foreach ($modseq as $modnum) {
                    if (!empty($this->mods[$modnum]) && $this->mods[$modnum]->mandatory &&
                        $this->mods[$modnum]->visible) {
                        $orderedmods[] = $this->mods[$modnum];
                    }
                }
            }
        }

        $modinfo = unserialize($this->course->modinfo);
        foreach ($orderedmods as $mod) {
            if ($mod->mandatory && $mod->visible) {
                $instancename = urldecode($modinfo[$mod->id]->name);
                if (!empty($CFG->filterall)) {
                    $instancename = filter_text("<nolink>$instancename</nolink>", $this->course->id);
                }

                if (!empty($modinfo[$mod->id]->extra)) {
                    $extra = urldecode($modinfo[$mod->id]->extra);
                } else {
                    $extra = "";
                }

                if (!empty($modinfo[$mod->id]->icon)) {
                    $icon = "$CFG->pixpath/".urldecode($modinfo[$mod->id]->icon);
                } else {
                    $icon = "$CFG->modpixpath/$mod->modname/icon.gif";
                }

                if ($mod->indent) {
                    print_spacer(12, 20 * $mod->indent, false);
                }

                if ($mod->modname == "label") {
                    if (!$mod->visible) {
                        $labeltext .= "<span class=\"dimmed_text\">";
                    }
                    $labeltext .= format_text($extra, FORMAT_HTML);
                    if (!$mod->visible) {
                        $labeltext .= "</span>";
                    }
                    $labeltext .= '<br />';
                } else if ($mod->modname == "resource") {
                    $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                    $alttext = isset($link_title[$mod->modname]) ? $link_title[$mod->modname] : $mod->modfullname;
                    $labeltext .= "<img src=\"$icon\"".
                         " height=16 width=16 alt=\"$alttext\">".
                         " <font size=2><a title=\"$alttext\" $linkcss $extra".
                         " href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">$instancename</a></font><br />";
                } else { // Normal activity

                    $act_compl = is_activity_complete($mod, $USER->id);
                    if ($act_compl === false) {
                        $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                        $alttext = isset($link_title[$mod->modname]) ? $link_title[$mod->modname] : $mod->modfullname;
                        $activitytext .= "<img src=\"$icon\"".
                             " height=16 width=16 alt=\"$alttext\">".
                             " <font size=2><a title=\"$alttext\" $linkcss $extra".
                             " href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">$instancename</a></font><br />";
                    }
                }
            }
        }

        print_simple_box('<div align="left">'.$labeltext.'</div>', 'center', '100%');
    }

    function print_weekly_activities_bar($week=0, $tabrange=0) {
        global $THEME, $FULLME, $CFG;

        list($tablow, $tabhigh, $week) = $this->get_week_info($tabrange, $week);

        $timenow = time();
        $weekdate = $this->course->startdate;    // this should be 0:00 Monday of that week
        $weekdate += 7200;                 // Add two hours to avoid possible DST problems
        $weekofseconds = 604800;

        if (isset($this->course->topicheading) && !empty($this->course->topicheading)) {
            $strtopicheading = $this->course->topicheading;
        } else {
            $strtopicheading = get_string('topics','format_fn');
        }

        $isteacher = has_capability('moodle/course:manageactivities', $this->context);
        $url = preg_replace('/(^.*)(&selected_week\=\d+)(.*)/', '$1$3', $FULLME);

        $actbar = '';
        $actbar .= '<table cellpadding="0" cellspacing="0" width="100%" class="fnweeklynav"><tr>';
        $width = (int)(100 / ($tabhigh-$tablow+3));
        $actbar .= '<td width="4" align="center" height="25"></td>';

        if ($tablow <= 1) {
            $actbar .= '<td height="25">'.$strtopicheading.':&nbsp;</td>';
        } else {
            $prv = ($tablow - FNMAXTABS) * 1000;
            if ($prv < 0) {
                $prv = 1000;
            }
            $actbar .= '<td id="fn_tab_previous" height="25"><a href="'.$url.'&selected_week='.$prv.'">Previous</a></td>';
//            $actbar .= '<td class="fnweeklynavnorm" width="'.$width.'%" height="25">' .
//                       '<a href="'.$url.'&selected_week='.$prv.'">Previous</a></td>';
        }

        for ($i = $tablow; $i <= $tabhigh; $i++) {
            if (empty($this->sections[$i]->visible) || ($timenow < $weekdate)) {
                if ($i == $week) {
                    $css = 'fnweeklynavdisabledselected';
                } else {
                    $css = 'fnweeklynavdisabled';
                }

                if ($isteacher) {
                    $f = '<a href="'.$url.'&selected_week='.$i.'" ><span class="'.$css.'">'.$this->sections[$i]->summary.'</span></a>';
                } else {
                    $f = ' '.$i.' ';
                }
                $actbar .= '<td class="'.$css.'" height="25" width="'.$width.'%">'.$f.'</td>';
            }
            else if ($i == $week) {
                if (!$isteacher && !empty($this->course->activitytracking) &&
                    $this->is_section_finished($this->sections[$i], $this->mods)) {
                    $f = '<img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.'/pix/sectcompleted.gif" '.
                         'height="18" width="16" alt="Section Completed" title="Section Completed" align="right" hspace="0" vspace="0">';
                } else {
                    $f = '';
                }
                $actbar .= '<td class="fnweeklynavselected" width="'.$width.'%" height="25">'.$f.$this->sections[$i]->summary.'</td>';
            }
            else {
                if (!$isteacher && !empty($this->course->activitytracking) &&
                    $this->is_section_finished($this->sections[$i], $this->mods)) {
                    $f = '<img src="'.$CFG->wwwroot.'/course/format/'.$this->course->format.'/pix/sectcompleted.gif" '.
                         'height="18" width="16" alt="Section Completed" title="Section Completed" align="right" hspace="0" vspace="0">';
                } else {
                    $f = '';
                }
                $actbar .= '<td class="fnweeklynavnorm" width="'.$width.'%" height="25">'.
                           $f.'<a href="'.$url.'&selected_week='.$i.'">'.$this->sections[$i]->summary.'</a>'.'</td>';
            }
            $weekdate += ($weekofseconds);
            $actbar .= '<td align="center" height="25" style="width: 2px;">'.
                       '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" height="1" width="1" alt="" /></td>';
        }
        if (($week == 0) && ($tabhigh >= $this->course->numsections)) {
            $actbar .= '<td class="fnweeklynavselected" width="'.$width.'%" height="25">'.get_string('all','format_fn').'</td>';
        } else if ($tabhigh >= $this->course->numsections) {
            $actbar .= '<td class="fnweeklynavnorm" width="'.$width.'%" height="25">' .
                       '<a href="'.$url.'&selected_week=0">'.get_string('all','format_fn').'</a></td>';
        } else {
            $nxt = ($tabhigh + 1) * 1000;
            $actbar .= '<td id="fn_tab_next" height="25"><a href="'.$url.'&selected_week='.$nxt.'">Next</a></td>';
//            $actbar .= '<td class="fnweeklynavnorm" width="'.$width.'%" height="25">' .
//                       '<a href="'.$url.'&selected_week='.$nxt.'">Next</a></td>';
        }
        $actbar .= '<td width="1" align="center" height="25"></td>';
        $actbar .= '</tr>';
        $actbar .= '<tr>';
        $actbar .= '<td height="3" colspan="2"></td>';
//        if ($tablow > 1) {
//            $actbar .= '<td height="3"></td>';
//        }
        for ($i = $tablow; $i <= $tabhigh; $i++) {
            if ($i == $week) {
                $actbar .= '<td height="3" class="fnweeklynavselected"></td>';
            } else {
                $actbar .= '<td height="3"></td>';
            }
            $actbar .= '<td height="3"></td>';
        }
        $actbar .= '<td height="3" colspan="2"></td>';
        $actbar .= '</tr>';
        $actbar .= '</table>';

        return $actbar;
    }

    function is_section_finished(&$section) {
        global $USER;

        if ($modnums = explode(',', $section->sequence)) {
            foreach($modnums as $modnum) {
                if (isset($this->mods[$modnum]) && $this->mods[$modnum]->visible) {
                    $act_compl = is_activity_complete($this->mods[$modnum], $USER->id);
                    if (($act_compl === false) || (is_int($act_compl) && ($act_compl < 50)) ||
                        ($act_compl == 'submitted')) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    function first_unfinished_section() {
        if (is_array($this->sections) && is_array($this->mods)) {
            foreach ($this->sections as $section) {
                if ($section->section > 0) {
                    if (!is_section_finished($section, $this->mods)) {
                        return $section->section;
                    }
                }
            }
        }
        return false;
    }

    function get_week_info($tabrange, $week) {
        global $SESSION;

        if ($this->course->numsections == FNMAXTABS) {
            $tablow = 1;
            $tabhigh = FNMAXTABS;
        } else if ($tabrange > 1000) {
            $tablow = $tabrange / 1000;
            $tabhigh = $tablow + FNMAXTABS - 1;
        } else if (($tabrange == 0) && ($week == 0)) {
            $tablow = ((int)((int)($this->course->numsections-1) / (int)FNMAXTABS) * FNMAXTABS) + 1;
            $tabhigh = $tablow + FNMAXTABS - 1;
        } else if ($tabrange == 0) {
            $tablow = ((int)((int)$week / (int)FNMAXTABS) * FNMAXTABS) + 1;
            $tabhigh = $tablow + FNMAXTABS - 1;
        } else {
            $tablow = 1;
            $tabhigh = FNMAXTABS;
        }
        $tabhigh = MIN($tabhigh, $this->course->numsections);


        /// Normalize the tabs to always display FNMAXTABS...
        if (($tabhigh - $tablow + 1) < FNMAXTABS) {
            $tablow = $tabhigh - FNMAXTABS + 1;
        }


        /// Save the low and high week in SESSION variables... If they already exist, and the selected
        /// week is in their range, leave them as is.
        if (($tabrange >= 1000) || !isset($SESSION->FN_tablow[$this->course->id]) || !isset($SESSION->FN_tabhigh[$this->course->id]) ||
            ($week < $SESSION->FN_tablow[$this->course->id]) || ($week > $SESSION->FN_tabhigh[$this->course->id])) {
            $SESSION->FN_tablow[$this->course->id] = $tablow;
            $SESSION->FN_tabhigh[$this->course->id] = $tabhigh;
        } else {
            $tablow = $SESSION->FN_tablow[$this->course->id];
            $tabhigh = $SESSION->FN_tabhigh[$this->course->id];
        }
        $tablow = MAX($tablow, 1);
        $tabhigh = MIN($tabhigh, $this->course->numsections);

        /// If selected week in a different set of tabs, move it to the current set...
        if (($week != 0) && ($week < $tablow)) {
            $week = $SESSION->G8_selected_week[$this->course->id] = $tablow;
        } else if ($week > $tabhigh) {
            $week = $SESSION->G8_selected_week[$this->course->id] = $tabhigh;
        }

        return array($tablow, $tabhigh, $week);
    }
}
?>