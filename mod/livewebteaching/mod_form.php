<?php

/**
 * Apply settings.
 *
 *  @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 *  @package   mod_livewebteaching
 *  @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 *  
 *  This file is free software: you may copy, redistribute and/or modify it  
 *  under the terms of the GNU General Public License as published by the  
 *  Free Software Foundation, either version 2 of the License, or any later version.  
 *  
 *  This file is distributed in the hope that it will be useful, but  
 *  WITHOUT ANY WARRANTY; without even the implied warranty of  
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU  
 *  General Public License for more details.  
 *  
 *  You should have received a copy of the GNU General Public License  
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.  
 *
 *  This file incorporates work covered by the following copyright and permission notice:
 **
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_livewebteaching_mod_form extends moodleform_mod {

    function definition() {

        global $course, $CFG;
        $mform =& $this->_form;

	$mform->addElement('text', 'name', get_string('livewebteachingname','livewebteaching') );
	$mform->addRule( 'name', null, 'required', null, 'client' );

//	$mform->addElement( 'checkbox', 'wait', get_string('bbbuserwait', 'livewebteaching') );
//	$mform->setDefault( 'wait', 1 );

	$mform->addElement('hidden', 'wait', 1);

	#$mform->setHelpButton('moderatorpw', array('moderatorpw', get_string('livewebteachingmodpw', 'livewebteaching' )),true);

#	echo '<pre>';
#   	var_dump( $CFG );
#	echo '</pre>';

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $features = new stdClass;
        $features->groups = true;
        $features->grouping = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules

        $this->add_action_buttons();
    }
}


?>
