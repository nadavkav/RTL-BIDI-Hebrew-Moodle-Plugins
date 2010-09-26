<?php // $Id: course_format.class.php,v 1.1 2009/04/17 20:45:22 mchurch Exp $
/**
 * course_format is the base class for all course formats
 *
 * This class provides all the functionality for a course format
 */

/**
 * Standard base class for all course formats
 */
class course_format {

    var $course;        // The course record, with all data fields.
    var $page;          // The page object.
    var $blocks;        // The pageblocks object.

/**
 * Contructor
 *
 * @param $course object The pre-defined course object. Passed by reference, so that extended info can be added.
 *
 */
    function course_format(&$course) {
        if (empty($this->course) && is_object($course)) {
            $this->course = clone($course);
        }
        /// Method should load any other course data into the course property.
        $this->get_course();
    }

/******************************************************************************/
/*   MAIN DATA FUNCTIONS:                                                     */
/******************************************************************************/
/**
 * Override these if you have any format-specific data needs.
 *
 */

/**
 * Get any additional course data and return it. Also add it to the course property, and the optional
 * course parameter.
 *
 * @param $course object Optional object to add the new data to.
 * @return object The extra course data.
 *
 */
    function get_course($course=null) {
        return $course;

    /// Sample Code:
//        if (!empty($course->id)) {
//            $extradata = get_records('course_config_[format]', 'courseid', $course->id);
//        } else if (!empty($this->course->id)) {
//            $extradata = get_records('course_config_[format]', 'courseid', $this->course->id);
//        } else {
//            $extradata = false;
//        }
//
//        if (is_null($course)) {
//            $course = new Object();
//        }
//
//        if ($extradata) {
//            foreach ($extradata as $extra) {
//                $this->course->{$extra->variable} = $extra->value;
//                $course->{$extra->variable} = $extra->value;
//            }
//        }
//
//        return $course;
    }

/**
 * Update extra course data. Check if this is a new course, and create it if so.
 *
 * @param $data object The extra course data to update.
 *
 * @return boolean Success or failure.
 *
 */
    function update_course($data) {
        return false;

    /// Sample Code:
//        if (!empty($data)) {
//            $variables = get_object_vars($data);
//            foreach ($variables as $variable => $value) {
//                if ($id = get_field('course_config_[format]', 'id', 'courseid', $data->id, 'variable', $variable)) {
//                    set_field('course_config_[format]', $variable, $value, 'id', $id);
//                } else {
//                    $record = new Object();
//                    $record->courseid = $data->id;
//                    $record->variable = clean_param($variable, PARAM_CLEAN);
//                    $record->value = clean_param($value, PARAM_CLEAN);
//                    insert_record('course_config_[format]', $record);
//                }
//            }
//        }
//        return true;
    }

/******************************************************************************/
/*   PRE DISPLAY FUNCTIONS:                                                   */
/******************************************************************************/

/**
 * Setup page requirements, and some globals used by the format files. Ideally, these globals should be
 * moved to within the object.
 *
 */
    function page_setup() {
        global $CFG, $course;
        global $PAGE, $pageblocks;  /// These are needed in various library functions.

        require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER

        add_to_log($course->id, 'course', 'view', "view.php?id=$course->id", "$course->id");

        if (!isset($PAGE)) {
            $this->page = page_create_object(PAGE_COURSE_VIEW, $this->course->id);
            $GLOBALS['PAGE']       = &$this->page;
        } else {
            $this->page = $PAGE;
        }

        if (!isset($pageblocks)) {
            $this->blocks = blocks_setup($this->page, BLOCKS_PINNED_BOTH);
            $GLOBALS['pageblocks'] = &$this->blocks;
        } else {
            $this->blocks = $pageblocks;
        }

        /// $PAGE is expecting to have its courserecord loaded at some point (usually in print_header).
        /// This means it will do another database call, which we don't need. Force the courserecord to be
        /// loaded and indicated here. (this really needs to be a $PAGE method).
        $this->page->courserecord = &$this->course;
        $this->page->full_init_done = true;

    }
/**
 * Handle any pre-display arguments for the course. Make legacy variables global.
 * TODO - Move arguments into object structure.
 *
 */
    function handle_args() {
        global $CFG, $USER, $PAGE, $SESSION, $course;
        global $section, $marker; /// These are needed in various library functions.

        $section     = optional_param('section', 0, PARAM_INT);
        $marker      = optional_param('marker',-1 , PARAM_INT);
        $edit        = optional_param('edit', -1, PARAM_BOOL);
        $hide        = optional_param('hide', 0, PARAM_INT);
        $show        = optional_param('show', 0, PARAM_INT);
        $move        = optional_param('move', 0, PARAM_INT);

        if (!isset($USER->editing)) {
            $USER->editing = 0;
        }
        if ($PAGE->user_allowed_editing()) {
            if (($edit == 1) and confirm_sesskey()) {
                $USER->editing = 1;
            } else if (($edit == 0) and confirm_sesskey()) {
                $USER->editing = 0;
                if(!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
                    $USER->activitycopy       = false;
                    $USER->activitycopycourse = NULL;
                }
            }

            if ($hide && confirm_sesskey()) {
                set_section_visible($course->id, $hide, '0');
            }

            if ($show && confirm_sesskey()) {
                set_section_visible($course->id, $show, '1');
            }

            if (!empty($section)) {
                if (!empty($move) and confirm_sesskey()) {
                    if (!move_section($course, $section, $move)) {
                        notify('An error occurred while moving a section');
                    }
                }
            }
        } else {
            $USER->editing = 0;
        }

        $SESSION->fromdiscussion = $CFG->wwwroot .'/course/view.php?id='. $course->id;


        if ($course->id == SITEID) {
            // This course is not a real course.
            redirect($CFG->wwwroot .'/');
        }
    }

/******************************************************************************/
/*   MAIN DISPLAY FUNCTIONS:                                                  */
/******************************************************************************/

/**
 * The main view function. Handle all of the format's page construction and display.
 *
 */
    function view() {
        $this->print_header();
        $this->print_body();
        $this->print_footer();
    }

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

        $PAGE->print_header(get_string('course').': %fullname%', NULL, '', $bodytags);
    }
/**
 * Print out the course page. Use the course 'format.php' file. This may make sense to replace with a
 * function at some point, but for now, many things trigger on its existence.
 *
 */
    function print_body() {
        /// These globals are needed for the included 'format.php' file.
        global $CFG, $USER, $SESSION, $COURSE, $THEME, $course;
        global $mods, $modnames, $modnamesplural, $modnamesused, $sections;  /// These are needed in various library functions.
        global $PAGE, $pageblocks, $section, $marker; /// These are needed in various library functions.
        global $preferred_width_left, $preferred_width_right; /// These may be used in blocks.

        // Course wrapper start.
        echo '<div class="course-content">';

        get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);

        if (! $sections = get_all_sections($course->id)) {   // No sections found
            // Double-check to be extra sure
            if (! $section = get_record('course_sections', 'course', $course->id, 'section', 0)) {
                $section->course = $course->id;   // Create a default section.
                $section->section = 0;
                $section->visible = 1;
                $section->id = insert_record('course_sections', $section);
            }
            if (! $sections = get_all_sections($course->id) ) {      // Try again
                error('Error finding or creating section structures for this course');
            }
        }

        if (empty($course->modinfo)) {
            // Course cache was never made.
            rebuild_course_cache($course->id);
            if (! $course = get_record('course', 'id', $course->id) ) {
                error("That's an invalid course id");
            }
        }

        // Include the actual course format.
        require($CFG->dirroot .'/course/format/'. $course->format .'/format.php');
        // Content wrapper end.
        echo "</div>\n\n";
    }
/**
 * Finish the page.
 *
 */
    function print_footer() {
        global $CFG, $COURSE, $course;

        // Use AJAX?
        if ($CFG->useajax) {
            // At the bottom because we want to process sections and activities
            // after the relevant html has been generated. We're forced to do this
            // because of the way in which lib/ajax/ajaxcourse.js is written.
            echo '<script type="text/javascript" ';
            echo "src=\"{$CFG->wwwroot}/lib/ajax/ajaxcourse.js\"></script>\n";

            $COURSE->javascriptportal->print_javascript($course->id);
        }

        print_footer(NULL, $course);
    }

/******************************************************************************/
/*   MAIN EDIT FUNCTIONS:                                                     */
/******************************************************************************/
/**
 * Provide this function if the course format has extra settings that need to be modified for the course.
 * Use the formlib by providing an extra class file that extends moodleform.
 *
 * @param $action string The file that controls the form.
 * @param $params array  An array of any extra hidden inputs that need to be added to the form.
 *
 * @return object The form object.
 */
    function edit_form($action='edit.php', $params='') {
        return false;

    /// Sample Code:
//        global $CFG;
//
//        require_once($CFG->dirroot.'/course/format/[format]/edit_form.php');
//
//        $theform = new course_[format]_edit_form($action, $params, array('course' => $this->course));
//
//        return ($theform);
    }

/******************************************************************************/
/*   OTHER DISPLAY FUNCTIONS:                                                 */
/******************************************************************************/
/**
 * If used, this will just call the library function (for now). Replace this with your own to make it
 * do what you want.
 *
 */
    function print_section($course, $section, $mods, $modnamesused, $absolute=false, $width="100%", $return=false) {
        if ($return) {
            ob_start();
        }
        print_section($course, $section, $mods, $modnamesused, $absolute, $width);
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
    function print_section_add_menus($course, $section, $modnames, $vertical=false, $return=false) {
        return print_section_add_menus($course, $section, $modnames, $vertical, $return);
    }
}

/**
 * Factory function to take a course data record and return a fully loaded course object...
 *
 */
function load_course_object($course = null, $courseid = 0) {
    global $CFG;

    if (empty($course) && empty($courseid)) {
         /// Nothing to do.
         return false;
    }

    if (empty($course)) {
        if (!($course = get_record('course', 'id', $courseid))) {
            return false;
        }
    }

    /// Load extra data from any specific course format functions.
    if (empty($course->format)) {
        $course->format = 'topics';
    }
    require_once($CFG->dirroot.'/course/format/course_format.class.php');
    $formatclassfile = $CFG->dirroot.'/course/format/'.$course->format.'/course_format.class.php';
    if (file_exists($formatclassfile)) {
        require_once($formatclassfile);
        $formatclass = 'course_format_'.$course->format;
    } else {
        $formatclass = 'course_format';
    }
    $thiscourseformat = new $formatclass($course);
    $thiscourseformat->get_course($course);

    /// Load the legacy global $course variable...
    global $course;
    $course = $thiscourseformat->course;

    return $thiscourseformat;
 }
?>