<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class block_exabis_eportfolio extends block_list {

	function init() {
        $this->title = get_string('blocktitle', 'block_exabis_eportfolio');
        $this->version = 2009010103;
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_allow_config() {
        return false;
    }

	function has_config() {
	    return true;
	}

    function get_content() {
    	global $CFG, $COURSE, $USER;

    	$context = get_context_instance(CONTEXT_SYSTEM);
        if (!has_capability('block/exabis_eportfolio:use', $context)) {
	        $this->content = '';
        	return $this->content;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

		$this->content->items[]='<a title="' . get_string('mybookmarkstitle', 'block_exabis_eportfolio') . '" href="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/view.php?courseid=' . $COURSE->id . '">' . get_string('mybookmarks', 'block_exabis_eportfolio') . '</a>';
		$this->content->icons[]='<img src="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/pix/categories.png" height="16" width="16" alt="'.get_string("mybookmarks", "block_exabis_eportfolio").'" />';

		$this->content->items[]='<a title="' . get_string('sharedbookmarks', 'block_exabis_eportfolio') . '" href="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/shared_people.php?courseid=' . $COURSE->id . '">' . get_string('sharedbookmarks', 'block_exabis_eportfolio') . '</a>';
	    $this->content->icons[]='<img src="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/pix/publishedportfolios.png" height="16" width="16" alt="'.get_string("sharedbookmarks", "block_exabis_eportfolio").'" />';

		//$this->content->items[]='<a title="' . get_string('export', 'block_exabis_eportfolio') . '" href="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/export_scorm.php?courseid=' . $COURSE->id . '">' . get_string('export', 'block_exabis_eportfolio') . '</a>';
		//$this->content->icons[]='<img src="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/pix/export.png" height="16" width="16" alt="'.get_string("export", "block_exabis_eportfolio").'" />';

if (has_capability('block/exabis_eportfolio:importfrommoodle', $context)) {
  //echo "<p ><img src=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/pix/import.png\" height=\"16\" width=\"16\" alt='".get_string("moodleimport", "block_exabis_eportfolio")."' /> <a title=\"" . get_string("moodleimport","block_exabis_eportfolio") . "\" href=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/import_moodle.php?courseid=".$courseid."\">".get_string("moodleimport","block_exabis_eportfolio")."</a></p>";

    $this->content->items[]='<a title="' . get_string('export', 'block_exabis_eportfolio') . '" href="' . $CFG->wwwroot . '/import_moodle.php?courseid=' . $COURSE->id . '">' . get_string('export', 'block_exabis_eportfolio') . '</a>';
    $this->content->icons[]='<img src="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/pix/export.png" height="16" width="16" alt="'.get_string("export", "block_exabis_eportfolio").'" />';

}
        return $this->content;
    }
}
