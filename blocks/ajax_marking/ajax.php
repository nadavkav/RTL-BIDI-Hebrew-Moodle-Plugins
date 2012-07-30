<?php


// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * This is the file that is called by all the browser's ajax requests.
 *
 * It first includes the main lib.php fie that contains the base class
 * which has all of the functions in it, then instantiates a new ajax_marking_response
 * object which will process the request.
 *
 * @package   block-ajax_marking
 * @copyright 2008 Matt Gibson                                       
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */

include("../../config.php");
require_login(1, false);
include("lib.php");

/**
 * Wrapper for the main functions library class which adds the parts that deal with the AJAX
 * request process.
 *
 * The block is used in two ways. Firstly when the PHP version is made, necessitating a HTML list
 * of courses + assessment names, and secondly when an AJAX request is made, which requires a JSON
 * response with just one set of nodes e.g. courses OR assessments OR student. The logic is that
 * shared functions go in the base class and this is extended by either the ajax_marking_response
 * class as here, or the HTML_list class in the html_list.php file.
 *
 * @copyright 2008 Matt Gibson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ajax_marking_response extends ajax_marking_functions {

  /**
    * This function takes the POST data, makes variables out of it, then chooses the correct
    * function to deal with the request, before printing the output.
    * @global <type> $CFG
    */
    function ajax_marking_response() {
    // constructor retrieves GET data and works out what type of AJAX call has been made before
    // running the correct function
    // TODO: should check capability with $USER here to improve security. currently, this is only
    // checked when making course nodes.

        global $CFG, $USER;

        // TODO - not necessary to load all things for all types. submissions level doesn't need
        // the data for all the other types
        $this->get_variables();
        $this->initial_setup();

        // The type here refers to what was clicked, or sets up the tree in the case of 'main' and
        // 'config_main_tree'. Type is also returned, where it refers to the node(s) that will be created,
        // which then gets sent back to this function when that node is clicked.
        switch ($this->type) {
        
            // generate the list of courses when the tree is first prepared. Currently either makes
            // a config tree or a main tree
            case "main":
                
                $course_ids = NULL;

                // admins will have a problem as they will see all the courses on the entire site.
                // However, they may want this (CONTRIB-1017)
                // TODO - this has big issues around language. role names will not be the same in
                // diffferent translations.

                // begin JSON object
                $this->output = '[{"type":"main"}';

                // iterate through each course, checking permisions, counting relevant assignment
                // submissions and adding the course to the JSON output if any appear
                foreach ($this->courses as $course) {
                    
                    $courseid = '';
                    $students = '';
                    // set course assessments counter to 0
                    $count = 0;
                    
                    // show nothing if the course is hidden
                    if (!$course->visible == 1)  {
                        continue;
                    }

                    // we must make sure we only get work from enrolled students
                    $courseid = $course->id;
                    $this->get_course_students($courseid);
                    // If there are no students, there's no point counting
                    if (!isset($this->student_ids->$courseid)) {
                        continue;
                    }

                    // see which modules are currently enabled
                    $sql = "SELECT name 
                              FROM {$CFG->prefix}modules
                             WHERE visible = 1";

                    $enabledmods = get_records_sql($sql);
                    $enabledmods = array_keys($enabledmods);
          
                    // loop through each module, getting a count for this course id from each one.
                    foreach ($this->modulesettings as $modname => $module) {
                        // Do not use modules which have been disabled by the admin
                        if(in_array($modname, $enabledmods)) {
                            $count += $this->$modname->count_course_submissions($courseid);
                        }
                    }

                    // TO DO: need to check in future for who has been assigned to mark them (new
                    // groups stuff) in 1.9

                    if ($count > 0 || $this->config) {

                        // there are some assessments, or its a config tree, so we include the
                        // course always.

                        $this->output .= ','; 
                        $this->output .= '{';

                        $this->output .= '"id":"'.$courseid.'",';
                        $this->output .= '"type":"course",';
                        
                        $this->output .= '"label":"'.$this->add_icon('course');
                        $this->output .= ($this->config) ? '' : "(<span class='AMB_count'>".$count.'</span>) ';
                        $this->output .= $this->clean_name_text($course->shortname, 0).'",';
                        
                        // name is there to allow labels to be reconstructed with a new count after
                        // marked nodes are removed
                        $this->output .= '"name":"'.$this->clean_name_text($course->shortname, 0).'",';
                        $this->output .= '"title":"'.$this->clean_name_text($course->shortname, -2).'",';
                        $this->output .= '"summary":"'.$this->clean_name_text($course->shortname, -2).'",';
                        $this->output .= '"icon":"'.$this->add_icon('course').'",';
                        $this->output .= '"uniqueid":"course'.$courseid.'",';
                        $this->output .= '"count":"'.$count.'",';
                        $this->output .= '"dynamic":"true",';
                        $this->output .= '"cid":"c'.$courseid.'"';
                        $this->output .= '}';

                    } 
                } 
                //end JSON object
                $this->output .= "]";

                break;

            case "config_main_tree":

                // Makes the course list for the configuration tree. No need to count anything, just
                // make the nodes. Might be possible to collapse it into the main one with some IF
                // statements.

                $this->config = true;

                $this->output = '[{"type":"config_main_tree"}';

                if ($this->courses) { 

                    foreach ($this->courses as $course) {
                        // iterate through each course, checking permisions, counting assignments and
                        // adding the course to the JSON output if anything is there that can be graded
                        $count = 0;

                        if (!$course->visible) {
                            continue;
                        }

                        foreach ($this->modulesettings as $modname => $module) {
                            $count += $this->$modname->count_course_assessment_nodes($course->id);
                        }

                        if ($count > 0) {

                            $course_settings = $this->get_groups_settings('course', $course->id);

                            $this->output .= ','; 
                            $this->output .= '{';

                            $this->output .= '"id":"'       .$course->id.'",';
                            $this->output .= '"type":"config_course",';
                            $this->output .= '"title":"';
                            $this->output .= get_string('confCurrent', 'block_ajax_marking').': ';

                            // add the current settings to the tooltip
                            if (isset($course_settings->showhide)) {
                                switch ($course_settings->showhide) {

                                    case 1:
                                        $this->output .= get_string('confCourseShow', 'block_ajax_marking');
                                        break;

                                    case 2:
                                        $this->output .= get_string('confGroups', 'block_ajax_marking');
                                        break;

                                    case 3:
                                        $this->output .= get_string('confCourseHide', 'block_ajax_marking');

                                }
                            } else {
                                $this->output .= get_string('confCourseShow', 'block_ajax_marking');
                            }

                            $this->output .= '",';
                            $this->output .= '"name":"'  .$this->clean_name_text($course->fullname).'",';
                            // to be used for the title
                            $this->output .= '"icon":"'  .$this->add_icon('course').'",';
                            $this->output .= '"label":"' .$this->add_icon('course');
                            $this->output .= $this->clean_name_text($course->fullname).'",';
                            $this->output .= '"count":"' .$count.'"';

                            $this->output .= '}';

                        }
                    }
                }
                $this->output .= ']';

                break;

            case "course":

                $courseid = $this->id;
                // we must make sure we only get work from enrolled students
                $this->get_course_students($courseid);

                $this->output = '[{"type":"course"}';

                foreach ($this->modulesettings as $modname => $module) {
                    $this->$modname->course_assessment_nodes($courseid);
                }

                $this->output .= "]";
                break;

            case "config_course":
                $this->get_course_students($this->id);

                $this->config = true;
                $this->output = '[{"type":"config_course"}';

                foreach ($this->modulesettings as $modname => $module) {
                    $this->$modname->config_assessment_nodes($this->id, $modname);
                }
               
                $this->output .= "]";
                break;


            case "config_groups":

               // writes to the db that we are to use config groups, then returns all the groups.
               // Called only when you click the option 2 of the config, so the next step is for the
               // javascript functions to build the groups checkboxes.

                $this->output = '[{"type":"config_groups"}'; // begin JSON array

                // first set the config to 'display by group' as per the ajax request (this is the
                // option that was clicked)
                $this->make_config_data();
                if ($this->config_write()) {
                    // next, we will now return all of the groups in a course as an array,
                    $this->make_config_groups_radio_buttons($this->id, 
                                                            $this->assessmenttype,
                                                            $this->assessmentid);
                } else {
                    $this->output .= ',{"result":"false"}';
                }
                $this->output .= ']';

                break;

            case "config_set":

                /**
                 * this is to save configuration choices from the radio buttons for 1 and 3 once
                 * they have been clicked. Needed as a wrapper
                 * so that the config_write bit can be used for the function above too
                 */

                $this->output = '[{"type":"config_set"}';

                // if the settings have been put back to default, destroy the existing record
                if ($this->showhide == AMB_CONF_DEFAULT) {
                    $deleterecord = delete_records('block_ajax_marking',
                                                   'assessmenttype',
                                                   $this->assessmenttype,
                                                   'assessmentid',
                                                   $this->assessmentid,
                                                   'userid',
                                                   $USER->id);
                    if ($deleterecord) {
                        $this->output .= ',{"result":"true"}]';
                    } else {
                        $this->output .= ',{"result":"false"}]';
                    }
                } else {
                    $this->make_config_data();
                    if($this->config_write()) {
                        $this->output .= ',{"result":"true"}]';
                    } else {
                        $this->output .= ',{"result":"false"}]';
                    }
                }

                break;

            case "config_check":

               /**
                * this is to check what the current status of an assessment is so that
                * the radio buttons can be made with that option selected.
                * if its currently 'show by groups', we need to send the group data too.
                * 
                * This might be for an assessment node or a course node
                */

                // begin JSON array
                $this->output = '[{"type":"config_check"}'; 

                $assessment_settings = $this->get_groups_settings($this->assessmenttype, $this->assessmentid);
                $course_settings     = $this->get_groups_settings('course', $this->courseid);

                // Procedure if it's an assessment
                if ($this->assessmentid) {
                    if ($assessment_settings) {
                        $this->output .= ',{"value":"'.$assessment_settings->showhide.'"}';
                        if ($assessment_settings->showhide == 2) {
                            $this->make_config_groups_radio_buttons($this->courseid, 
                                                                    $this->assessmenttype,
                                                                    $this->assessmentid);
                       
                        }
                    }  else {
                        // no settings, so use course default.
                        $this->output .= ',{"value":"0"}';
                    }
                } else {
                    // Procedure for courses
                    if ($course_settings) {
                        $this->output .= ',{"value":"'.$course_settings->showhide.'"}';
                        if ($course_settings->showhide == 2) {
                            $this->make_config_groups_radio_buttons($this->courseid, 'course');
                        }
                    } else {
                        // If there are no settings, default to 'show'
                        $this->output .= ',{"value":"1"}';
                    }
                }

                $this->output .= ']';

                break;

            case "config_group_save":

                /**
                 * sets the display of a single group from the config screen when its checkbox is
                 * clicked. Then, it sends back a confirmation so that the checkbox can be un-greyed
                 * and marked as done
                 */

                $this->output = '[{"type":"config_group_save"},{'; // begin JSON array

                $this->make_config_data();
                if($this->groups) {
                    $this->data->groups = $this->groups;
                }
                if($this->config_write()) {
                    $this->output .= '"value":"true"}]';
                } else {
                    $this->output .= '"value":"false"}]';
                }

                break;

            default:

                // assume it's specific to one of the added modules. Run through each until
                // one of them has that function and it returns true.
                foreach ($this->modulesettings as $modname => $module) {
                    if($this->$modname->return_function($this->type))  {
                        break;
                    }
                }

                break;

        }
        // return the output to the client
        print_r($this->output);
    }
}


$AMB_AJAX_response = new ajax_marking_response;