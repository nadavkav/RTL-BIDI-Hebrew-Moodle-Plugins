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
 * This is the file that is called by block_ajax_marking.php when the block is created during page-load.
 *
 * It first includes the main lib.php fie that contains the base class which has all of the functions
 * in it, then instantiates a new html_list object which will process the request and output the HTML
 * that the block needs.
 *
 * @package   block-ajax_marking
 * @copyright 2008 Matt Gibson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_login(0, false);
include($CFG->dirroot.'/blocks/ajax_marking/lib.php');

/**
 * This class alows the building of the <ul> list of clickable links for non-javascript enabled
 * browsers
 */

/**
 * Wrapper for the main functions library class which adds the parts that deal with the HTML list
 * generation.
 *
 * The block is used in two ways. Firstly when the PHP version is made, necessitating a HTML list of
 * courses & assessment names, and secondly when an AJAX request is made, which requires a JSON
 * response with just one set of nodes e.g. courses OR assessments OR student. The logic is that
 * shared functions go in the base class and this is extended by either the ajax_marking_response
 * class in the ajax.php file, or the HTML_list class here.
 *
 * @copyright 2008 Matt Gibson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class AMB_html_list extends ajax_marking_functions {


    /**
     * This is to build the initial non-ajax set of html nodes for accessibility and non-javascript
     * browsers. It will eventually (hopefully) be used in a progressive enhancement way so that the
     * block exhibits gracful degradation, but this may prove awkward to implement.
     *
     * The output is a ul indented list of courses and assessment items with counts, with each
     * assessment item as a link to the grading page.
     *
     * The ul list can be recycled to make an accessible config tree in time.
     */
    function make_html_list() {

        global $CFG;

        $this->initial_setup(true);

        // get each module to do the sorting out - perhaps do this once when the request goes out
        // first.
        $this->html_list = '';

     
        // Foreach course, ask each module for all of the nodes to be returned as an array, with
        // each item having all the node details.
        foreach ($this->courses as $course) {
            

            $course_output = '';
            $course_count = 0;
            $courseid = $course->id;

            if (!$course->visible) {
                continue;
            }
            
            $this->get_course_students($courseid);
            if ((!isset($this->student_ids->$courseid)) || empty($this->student_ids->$courseid)) {
                // no students in this course
                continue;
            }

            // see which modules are currently enabled
            $sql = "
                SELECT name 
                FROM {$CFG->prefix}modules
                WHERE visible = 1
            ";
            $enabledmods =  get_records_sql($sql);
            $enabledmods = array_keys($enabledmods);
           
            // loop through each module, getting a count for this course id from each one.
            foreach ($this->modulesettings as $modname => $module) {
                if(in_array($modname, $enabledmods)) {

                    $mod_output = $this->$modname->course_assessment_nodes($course->id, true);
                    if ($mod_output['count'] > 0) {
                        $course_count  += $mod_output['count'];
                        $course_output .= $mod_output['data'];
                    }
                }
                
            }
            
            if ($course_count > 0) {
                
                $this->html_list .= '<ul class="AMB_html">';
                $this->html_list .=     '<li class="AMB_html_course">';
                $this->html_list .=         $this->add_icon('course');
                $this->html_list .=         '<strong>('.$course_count.')</strong> ';
                $this->html_list .=         $course->shortname;
                $this->html_list .=     '</li>';
                $this->html_list .=     '<ul class="AMB_html_items">';
                $this->html_list .=         $course_output;
                $this->html_list .=     '</ul>';
                $this->html_list .= '</ul>';
            }
             
             
        }

        if ($this->html_list) {
            return $this->html_list;
        } else {
            return get_string('nothing', 'block_ajax_marking');
        }

    }

}