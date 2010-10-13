<?php // $Id: mod_form.php,v 1.7 2009-04-06 18:50:12 jfilip Exp $

/**
 * Standard activity module configuration form.
 *
 * @version $Id: mod_form.php,v 1.7 2009-04-06 18:50:12 jfilip Exp $
 * @author Justin Filip <jfilip@remote-learner.net>
 * @author Remote Learner - http://www.remote-learner.net/
 */


require_once $CFG->dirroot . '/course/moodleform_mod.php';
require_once dirname(__FILE__) . '/lib.php';
require_js($CFG->wwwroot . '/mod/elluminate/jquery-1.4.2.min.js');
require_js($CFG->wwwroot . '/mod/elluminate/mod_form.js');

class mod_elluminate_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $COURSE, $USER;

        $id = optional_param('update', '', PARAM_RAW);
        if(!empty($id)) {     
	        if (!$cm = get_coursemodule_from_id('elluminate', $id)) {
	            error("Course Module ID was incorrect");
	        }         
	        if (!$elluminate = get_record("elluminate", "id", $cm->instance)) {
	            error("Course module is incorrect");
	        }
        }        
		if(!empty($id)) {
			if($cm->groupmode != $elluminate->groupmode) {		
				elluminate_check_for_group_change($cm, $elluminate);
				if (!$elluminate = get_record("elluminate", "id", $cm->instance)) {
		            error("Course module is incorrect");
		        }
		        //We're doing a redirect onto itself so that the changes are reflected on the UI
		        //redirect($CFG->wwwroot . '/course/modedit.php?update=' . $cm->id. '&amp;return=0');		        		
			} else {
				elluminate_check_for_new_groups($elluminate);
			}
		}
		
        if(!empty($elluminate)) {
	        $participant = false;
	        if ($elluminate->private) {
		    	//Checks to see if the user is a participant in the private meeting
		        if(elluminate_is_participant_in_meeting($elluminate, $USER->id)) {
		        	//then checks to make sure that the user role has the privilege to join a meeting
		        	$participant = true;
		        }
		    } else {
		    	$participant = true;
		    } 
	               
			if($participant == false) {
				error('You need to be invited to this private session in order to edit it.');
			}
        }               
               
        $elluminate_boundary_times = array (
			0 => '0',
			15 => '15',
			30 => '30',
			45 => '45',		
			60 => '60'
		);
        
//-------------------------------------------------------------------------------

        $mform    =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('title', 'elluminate'), array('size' => '64', 'maxlength' => '64'));                
        $mform->setType('name', PARAM_RAW);
        $mform->addRule('name', null, 'required', null, 'client');
		
		$sessiontypes = array();
		if($COURSE->groupmodeforce == '0') {
			$sessiontypes[0] = get_string('course','elluminate');
			$sessiontypes[1] = get_string('private','elluminate');
			$has_groups = get_records('groups', 'courseid', $COURSE->id, 'name ASC');
			if($has_groups != false) {
				$sessiontypes[2] = get_string('group','elluminate');
			}		
			$enablegroupings = get_record("config", "name", "enablegroupings");
			if($enablegroupings->value == '1') {
				$has_groupings = get_records('groupings', 'courseid', $COURSE->id, 'name ASC');
				if($has_groupings != false) {
					$sessiontypes[3] = get_string('groupings','elluminate');
				}
			}
		} else {
			if($COURSE->groupmode == 0) {
				$sessiontypes[0] = get_string('course','elluminate');
				$sessiontypes[1] = get_string('private','elluminate');
				$enablegroupings = get_record("config", "name", "enablegroupings");
				if($enablegroupings->value == '1') {
					$has_groupings = get_records('groupings', 'courseid', $COURSE->id, 'name ASC');
					if($has_groupings != false) {
						$sessiontypes[3] = get_string('groupings','elluminate');
					}
				}	
			} else if ($COURSE->groupmode > 0) {
				$has_groups = get_records('groups', 'courseid', $COURSE->id, 'name ASC');
				if($has_groups != false) {
					$sessiontypes[2] = get_string('group','elluminate');
				}		
				$enablegroupings = get_record("config", "name", "enablegroupings");
				if($enablegroupings->value == '1') {
					$has_groupings = get_records('groupings', 'courseid', $COURSE->id, 'name ASC');
					if($has_groupings != false) {
						$sessiontypes[3] = get_string('groupings','elluminate');
					}
				}
			}			
		}
		$mform->addElement('select', 'sessiontype', 'Session Type', $sessiontypes);
		 
		$mform->addElement('select', 'customname',  get_string('appendgroupname', 'elluminate'), array(0 => 'None', 1 => 'Only Group Name', 2 => 'Append Group Name to Title'));		
		$mform->addElement('text', 'sessionname', get_string('customsessionname', 'elluminate'), array('size' => '64', 'maxlength' => '64'));
		$mform->disabledIf('sessionname', 'customname', 'eq', 1);
		$mform->disabledIf('sessionname', 'customname', 'eq', 2);		          	    
		
		$mform->addElement('text', 'sessionname_state', 'Session Name State', array('size' => '64', 'maxlength' => '64'));
									
		if(!empty($id)) {
			$mform->addElement('hidden', 'isedit', 'true'); //This is a placeholder to do disables on.
			$mform->addElement('hidden', 'edit_groupmode', $elluminate->groupmode);					
			if($elluminate->sessiontype == '0') {
				$mform->setDefault('sessiontype', 0);
			} else if($elluminate->sessiontype == '1') {
				$mform->setDefault('sessiontype', 1);
			} else if($elluminate->sessiontype == '2') {
				$mform->setDefault('sessiontype', 2);
			} else if($elluminate->sessiontype == '3') {
				$mform->setDefault('sessiontype', 3);
				$mform->setDefault('grouping', $elluminate->groupid);
			}
			
			$mform->disabledIf('sessiontype', 'isedit', 'eq', 'true');											
		}

        $mform->addElement('htmleditor', 'description', get_string('description'));
        $mform->setType('description', PARAM_RAW);      
        $mform->setHelpButton('description', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');			
				
        $mform->addElement('checkbox', 'customdescription', get_string('customdescription', 'elluminate'));
        $mform->disabledIf('customdescription', 'sessiontype', 'eq', 0);
		$mform->disabledIf('customdescription', 'sessiontype', 'eq', 1);	
        		
        $mform->addElement('date_time_selector', 'timestart', get_string('meetingbegins', 'elluminate'), array('optional'=>false, 'step'=>15));
        $mform->setDefault('timestart', time()+900);
        $mform->addElement('date_time_selector', 'timeend', get_string('meetingends', 'elluminate'), array('optional'=>false, 'step'=>15));        
        $mform->setDefault('timeend', time()+4500);        		

        $recording_options = array(
            ELLUMINATELIVE_RECORDING_NONE      => get_string('disabled', 'elluminate'),
            ELLUMINATELIVE_RECORDING_MANUAL    => get_string('manual', 'elluminate'),
            ELLUMINATELIVE_RECORDING_AUTOMATIC => get_string('automatic', 'elluminate')
        );

        $mform->addElement('select', 'recordingmode', get_string('recordmeeting', 'elluminate') , $recording_options);
        $mform->setDefault('recordingmode', ELLUMINATELIVE_RECORDING_MANUAL);
        $mform->setHelpButton('recordingmode', array('recording', get_string('helprecording', 'elluminate'), 'elluminate'));

    	/// Don't allow choosing a boundary time if there is a globally defined default time.    	
        if ($CFG->elluminate_boundary_default != '-1') {
        	$attributes = array('disabled' => 'true');
        } else {
            $attributes = '';
        }

        $boundaryselect = $mform->addElement('select', 'boundarytime', get_string('boundarytime', 'elluminate') ,
                                             $elluminate_boundary_times, $attributes);
		if($CFG->elluminate_boundary_default == '-1') {
        	$mform->setDefault('boundarytime', ELLUMINATELIVE_BOUNDARY_DEFAULT);
        } else {
        	$mform->setConstant('boundarytime', $CFG->elluminate_boundary_default);
        }                                             
		      
		$mform->setHelpButton('boundarytime', array('boundarytime', get_string('helpboundarytime', 'elluminate'), 'elluminate'));		
        $mform->addElement('checkbox', 'boundarytimedisplay', get_string('boundarytimedisplay', 'elluminate'));
        $mform->disabledIf('boundarytimedisplay', 'boundarytime', 'eq', 0); 

        $mform->addElement('modgrade', 'grade', get_string('gradeattendance', 'elluminate'));
        $mform->setDefault('grade', 0);	
        
        
//-------------------------------------------------------------------------------
        //$features = new stdClass;
        //$features->groups = false;
        //$features->groupings = false;
        //$features->groupmembersonly = false;
        //$features->gradecat = false;
        //$features->idnumber = false;
        //$this->standard_coursemodule_elements($features);
        
        $features = new stdClass;
        $features->groupings = true;
        $features->groups = true;        
        $features->groupmembersonly = true;
        $features->gradecat = true;
        $features->idnumber = true;
        $this->standard_coursemodule_elements($features);
//-------------------------------------------------------------------------------

/// Add rules for group name dependent options defined earlier.
        //$mform->disabledIf('customname', 'groupmode', 'eq', NOGROUPS);
        //$mform->disabledIf('customdescription', 'groupmode', 'eq', NOGROUPS);
		$mform->disabledIf('groupname', 'groupsession', 'eq', 0);
// buttons
        $this->add_action_buttons();

    }

}

?>
