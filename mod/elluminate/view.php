<?php // $Id: view.php,v 1.16 2009-06-05 20:12:38 jfilip Exp $

/**
 * This page prints a particular instance of elluminate.
 *
 * @version $Id: view.php,v 1.16 2009-06-05 20:12:38 jfilip Exp $
 * @author Justin Filip <jfilip@remote-learner.net>
 * @author Remote Learner - http://www.remote-learner.net/
 */


    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';


    $id                 = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a                  = optional_param('a', 0, PARAM_INT);  // elluminate ID
    $editrecordingdesc  = optional_param('editrecordingdesc', 0, PARAM_INT);
    $delrecording       = optional_param('delrecording', 0, PARAM_INT);
    $hiderecording      = optional_param('hiderecording', 0, PARAM_INT);
    $showrecording      = optional_param('showrecording', 0, PARAM_INT);
    $hidegrouprecording = optional_param('hidegrouprecording', 0, PARAM_INT);
    $showgrouprecording = optional_param('showgrouprecording', 0, PARAM_INT);
	$groupid			= optional_param('group', 0, PARAM_INT);

    if ($id) {    	
        if (!$cm = get_coursemodule_from_id('elluminate', $id)) {
            error("Course Module ID was incorrect");
        }         
        if (!$course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }        
        if (!$elluminate = get_record("elluminate", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
        if($elluminate->sessiontype == 0 && $elluminate->groupparentid > 0) {
        	$elluminate = get_record("elluminate", "id", $elluminate->groupparentid);
        }             
    } else {    	
        if (!$elluminate = get_record("elluminate", "id", $a)) {
            error("Course module is incorrect");
        }
        if (!$course = get_record("course", "id", $elluminate->course)) {
            error("Course is misconfigured");
        }
        if($elluminate->groupmode == 0) {
	        if (!$cm = get_coursemodule_from_instance("elluminate", $elluminate->id, $course->id)) {
	            error("Course Module ID was incorrect");
	        }
        } else {
        	if($elluminate->groupid == 0) {
	        	if (!$cm = get_coursemodule_from_instance("elluminate", $elluminate->id, $course->id)) {
		            error("Course Module ID was incorrect");
		        }
        	} else {
        		if (!$cm = get_coursemodule_from_instance("elluminate", $elluminate->groupparentid, $course->id)) {
		            error("Course Module ID was incorrect");
		        }
        	}
        	
        }	     
    }    
		
	if($cm->groupmode != $elluminate->groupmode) {		
		elluminate_check_for_group_change($cm, $elluminate);
		$elluminate->groupmode = $cm->groupmode;
	}
	elluminate_check_for_new_groups($elluminate);
	
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	$accessallgroups    = has_capability('moodle/site:accessallgroups', $context);
	
	/// Check to see if groups are being used here
	$groupmode    = $elluminate->groupmode;	
	
	if($elluminate->sessiontype == 2 || $elluminate->sessiontype == 3) {
		if(empty($groupid)) {		
			if(!$accessallgroups) {	
				if($elluminate->sessiontype == 2) {
					$availablegroups = groups_get_all_groups($cm->course, $USER->id);
				} else if ($elluminate->sessiontype == 3) {
					$availablegroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);		
				} else {
					$availablegroups = null;
					$currentgroupid = 0;
				}
			} else {			
				if($elluminate->sessiontype == 2) {
					$availablegroups = groups_get_all_groups($cm->course, 0);
				} else if ($elluminate->sessiontype == 3) {
					$availablegroups = groups_get_all_groups($cm->course, 0, $cm->groupingid);		
				}
			}		

			if($availablegroups != false) {
				$i = 0;
				foreach ($availablegroups as $potentialgroup) { 
					$i++;
					$currentgroupid = $potentialgroup->id;
					if ( $i == 1 ) 
					break; 
				}
			} else {
				error('You are not a member of any groups therefore cannot view this session.');
			}	
		} else {
			$currentgroupid = $groupid;
		}
	}
 
	if($elluminate->sessiontype == 2 || $elluminate->sessiontype == 3) {
		if($elluminate->groupparentid > 0) {
			$sql = "SELECT e.*
	                FROM {$CFG->prefix}elluminate e
	                WHERE e.groupparentid = '$elluminate->groupparentid'
	                AND e.groupid = '$currentgroupid'";
			$elluminate = get_record_sql($sql);			
		} else {
			if($elluminate->groupmode != 0) {
				$sql = "SELECT e.*
		                FROM {$CFG->prefix}elluminate e
		                WHERE e.groupparentid = '$elluminate->id'
		                AND e.groupid = '$currentgroupid'";
				$elluminate = get_record_sql($sql);
			}
		}		
		if(!$elluminate) {
			error('Error retrieving group sessions.');
		}		
	} 
	
	
    $timenow = time();
/// Some capability checks.
    require_course_login($course, true, $cm);
    require_capability('mod/elluminate:view', $context);

    if (!$cm->visible){
        require_capability('moodle/course:viewhiddenactivities', $context);
    }

    require_js($CFG->wwwroot . '/mod/elluminate/checkseats.js');
	
    $candeleterecordings    = has_capability('mod/elluminate:deleterecordings', $context);
    $candeleteanyrecordings = has_capability('mod/elluminate:deleteanyrecordings', $context);
    $caneditownrecordings	= has_capability('mod/elluminate:editownrecordings', $context);
    $caneditallrecordings	= has_capability('mod/elluminate:editallrecordings', $context);
    $canmanageanyrecordings = has_capability('mod/elluminate:manageanyrecordings', $context);
    $canmanageseats         = has_capability('mod/elluminate:manageseats', $context);
    $canmanagemoderators    = has_capability('mod/elluminate:managemoderators', $context);
    $canmanageparticipants  = has_capability('mod/elluminate:manageparticipants', $context);
    $canviewrecordings      = has_capability('mod/elluminate:viewrecordings', $context);
    $canviewattendance      = has_capability('mod/elluminate:viewattendance', $context);
    $canmanageattendance    = has_capability('mod/elluminate:manageattendance', $context);
    $canmanagepreloads      = has_capability('mod/elluminate:managepreloads', $context);
    $canmoderatemeeting     = has_capability('mod/elluminate:moderatemeeting', $context, $USER->id);
    $canjoinmeeting         = has_capability('mod/elluminate:joinmeeting', $context, $USER->id);
	
	/// Determine if the current user can participate in this meeting.
    $participant = false;
        
    if ($elluminate->sessiontype == 1) {
        $mctx = get_context_instance(CONTEXT_MODULE, $cm->id);
           	
    	//Checks to see if the user is a participant in the private meeting
        if(elluminate_is_participant_in_meeting($elluminate, $USER->id)) {
        	//then checks to make sure that the user role has the privilege to join a meeting
        	$participant = $canjoinmeeting;
        }        
    } else {
    	if($elluminate->sessiontype == 3) {
    		if($accessallgroups) {
    			$participant = $canjoinmeeting;
    		} else {    		
				$isvaliduser = false;			
				$groupingusers = groups_get_grouping_members($elluminate->groupingid);			
				foreach($groupingusers as $groupinguser) {
					if(!$isvaliduser) {
						if($groupinguser->id == $USER->id) {
							$isvaliduser = true;
						}
						if($elluminate->creator == $USER->id) {
							$isvaliduser = true;
						}													
					}
				}	
				if($isvaliduser) {
					$participant = $canjoinmeeting;
				}
    		}
		} else {
    		$participant = $canjoinmeeting;
    	}
    	    	
    	// If the user is not a member of group or groupings, make sure they're not invited as a moderator
    	if(!$participant) {    	
	        if(elluminate_is_participant_in_meeting($elluminate, $USER->id)) {
	        	$participant = $canjoinmeeting;
	        }
    	}    	
    }
	

    if ($elluminate->creator == $USER->id ||
        ($groupmode && groups_is_member($currentgroupid) &&
         has_capability('mod/elluminate:managerecordings', $context))) {

        $canmanagerecordings = true;
    } else {
        $canmanagerecordings = false;
    }

/// Calculate the actual number of seconds for the boundary time.
    $boundaryminutes = $elluminate->boundarytime * MINSECS;

/// Determine if the meeting has started yet and also if the meeting has finished yet.

    $hasstarted  = (($elluminate->timestart - $boundaryminutes) <= $timenow);
    $hasfinished = ($elluminate->timeend < $timenow);


/// Print the page header
    $strelluminates   = get_string("modulenameplural", "elluminate");
    $strelluminate    = get_string('modulename", "elluminate');
    $elluminate->name = stripslashes($elluminate->name);
    $strelllive       = get_string('modulename', 'elluminate');
    $straddpreload    = get_string('addpreload', 'elluminate');

	$buttontext = "";
	if($participant) {
    	$buttontext = elluminate_update_module_button($cm->id, $course->id, $strelllive);
	}
    
    $navigation = build_navigation('', $cm);

    print_header_simple(format_string($elluminate->name), "",
                                      $navigation, "", "", true, $buttontext, navmenu($course, $cm));

	//if($elluminate->sessiontype == 2 || $elluminate->sessiontype == 3) {
		elive_groups_print_activity_menu($cm, 'view.php?id=' . $cm->id, false, $groupid);
	//}	
	
    $sesskey = !empty($USER->sesskey) ? $USER->sesskey : '';

/// Print the main part of the page
    print_simple_box_start('center', '50%');

/// Check for data submission.
    if (($data = data_submitted($CFG->wwwroot . '/mod/elluminate/view.php')) && confirm_sesskey()) {

    /// Handle the editing of a recording description field.
        if (isset($data->descsave) && !empty($data->recordingid) &&
            ($canmanageanyrecordings || $canmanagerecordings)) {

            if ($recording = get_record('elluminate_recordings', 'id', $data->recordingid)) {
                $recording->description = $data->recordingdesc;
                $recording = addslashes_object($recording);

                if (!update_record('elluminate_recordings', $recording)) {
                    debugging('Unable to edit recording description!');
                }
            }
        }
    }

/// Handle a request to delete a recording.
    if (!empty($delrecording) &&
        ($candeleteanyrecordings || ($candeleterecordings && ($elluminate->creator == $USER->id)))) {

        if (!$recording = get_record('elluminate_recordings', 'id', $delrecording)) {
            error('Could not find meeting recording record');
        }

        if (optional_param('confirm', '', PARAM_ALPHANUM) == $sesskey) {
            if (elluminate_delete_recording($recording->recordingid)) {
                delete_records('elluminate_recordings', 'id', $recording->id);
                redirect($CFG->wwwroot . '/mod/elluminate/view.php?id=' . $cm->id . '&amp;group=' . $elluminate->groupid,
                         get_string('deleterecordingsuccess', 'elluminate'), 4);
            } else {
                redirect($CFG->wwwroot . '/mod/elluminate/view.php?id=' . $cm->id . '&amp;group=' . $elluminate->groupid,
                         get_string('deleterecordingfailure', 'elluminate'), 4);
            }

        } else {
            notice_yesno(get_string('deleterecordingconfirm', 'elluminate', userdate($recording->created)),
                         $CFG->wwwroot . '/mod/elluminate/view.php?id=' . $cm->id .
                         '&amp;delrecording=' . $recording->id . '&amp;confirm=' . $sesskey . '&amp;group=' . $elluminate->groupid,
                         $CFG->wwwroot . '/mod/elluminate/view.php?id=' . $cm->id . '&amp;group=' . $elluminate->groupid);
        }

        print_simple_box_end();
        print_footer($course);
        exit;
    }

/// Hide a recording.
    if (!empty($hiderecording) && ($canmanageanyrecordings || $canmanagerecordings)) {
        if ($recording = get_record('elluminate_recordings', 'id', $hiderecording)) {
            $recording->visible = 0;
            $recording = addslashes_object($recording);

            if (!update_record('elluminate_recordings', $recording)) {
                debugging('Unable to hide recording!');
            }
        }
    }

/// Unhide a recording.
    if (!empty($showrecording) && ($canmanageanyrecordings || $canmanagerecordings)) {
        if ($recording = get_record('elluminate_recordings', 'id', $showrecording)) {
            $recording->visible = 1;
            $recording = addslashes_object($recording);

            if (!update_record('elluminate_recordings', $recording)) {
                debugging('Unable to change recording group visibility!');
            }
        }
    }

/// Make a recording visible to only group members.
    if (!empty($hidegrouprecording) && ($groupmode == VISIBLEGROUPS) &&
        ($canmanageanyrecordings || $canmanagerecordings)) {

        if ($recording = get_record('elluminate_recordings', 'id', $hidegrouprecording)) {
            $recording->groupvisible = 0;
            $recording = addslashes_object($recording);

            if (!update_record('elluminate_recordings', $recording)) {
                debugging('Unable to change recording group visibility!');
            }
        }
    }

/// Make a recording visible to only group members.
    if (!empty($showgrouprecording) && ($groupmode == VISIBLEGROUPS) &&
        ($canmanageanyrecordings || $canmanagerecordings)) {

        if ($recording = get_record('elluminate_recordings', 'id', $showgrouprecording)) {
            $recording->groupvisible = 1;
            $recording = addslashes_object($recording);

            if (!update_record('elluminate_recordings', $recording)) {
                debugging('Unable to hide recording!');
            }
        }
    }

    add_to_log($course->id, "elluminate", "view", "view.php?id=$cm->id", "$elluminate->id");

	
    
   
	if($groupmode) {
	    if (!empty($currentgroup)) {
	        if (!empty($elluminate->customname)) {
	            $elluminate->name = $elluminate->name . ' - ' . $course->shortname . ' - ' . $groupname;
	        }
	        if (!empty($elluminate->customdescription)) {
	            $elluminate->description = $groupname . ' - ' . $elluminate->description;
	        }
	    }
	}	

    $formelements = array(
        get_string('name')                            => $elluminate->name,
        get_string('description')                     => $elluminate->description,
        get_string('meetingbegins', 'elluminate') => userdate($elluminate->timestart),       
        get_string('meetingends', 'elluminate')   => userdate($elluminate->timeend)
    );

    echo '<table align="center" cellpadding="5">' . "\n";

    foreach ($formelements as $key => $val) {
        echo '<tr valign="top">' . "\n";
        echo '<td align="right"><b>' . $key . ':</b></td><td align="left">' . $val . '</td>' . "\n";
        echo '</tr>' . "\n";
    }
    
    if($elluminate->sessiontype == '3') {
    	$grouping = groups_get_grouping($elluminate->groupingid);
    	echo '<tr valign="top">' . "\n";
        echo '<td align="right"><b>Grouping:</b></td><td align="left">' . $grouping->name . '</td>' . "\n";
        echo '</tr>' . "\n";
    }
	
    echo '</tr><tr>';
    echo '</table>';

    if ($participant && $canmanagemoderators && !$hasfinished) {
        $link = '<a href="' . $CFG->wwwroot . '/mod/elluminate/moderators.php?id=' . $elluminate->id .
                '">' . get_string('editmoderatorsforthissession', 'elluminate') . '</a>';

        echo '<p class="elluminateeditmoderators">' . $link . '</p>';
    }
	
	if($elluminate->sessiontype == 1) {
	    if ($participant && $canmanageparticipants && !$hasfinished) {
		        $link = '<a href="' . $CFG->wwwroot . '/mod/elluminate/participants.php?id=' . $elluminate->id .
		                '">' . get_string('editparticipantsforthissession', 'elluminate') . '</a>';
		
		        echo '<p class="elluminateeditparticipants">' . $link . '</p>';
	    }
	}

    $link = '';

	/// Deal with meeting preload files if the current user has the capability to do so.
    if ($participant && $canmanagepreloads && !$hasfinished) {
        $haspreload = false;
        if (!empty($elluminate->meetingid)) {
            if ($preload = elluminate_list_meeting_preloads($elluminate->meetingid)) {                
                if (!empty($haspreload)) {
                    continue;
                }

                if (!empty($preload->presentationid)) {
                    $haspreload = true;
                }
            }
        }

        if ($haspreload) {
            $tooltip = get_string('deletepreloadfile', 'elluminate');

            $link = get_string('preloadfile', 'elluminate') . ': ' . $preload->description;
        }

        /// If the meeting hasn't even started yet, allow the user to delete this file.
        if ($haspreload) {
                $link .= ' <a href="' . $CFG->wwwroot . '/mod/elluminate/preload.php?id=' . $elluminate->id .
                         '&amp;delete=' . $preload->presentationid . '" title="' . $tooltip .'"><img src="' .
                         $CFG->pixpath . '/t/delete.gif" width="11" height="11" alt="' . $tooltip . '" /></a>';

	    /// Display a link to upload a preload file if the meeting hasn't started yet.
        } else if (!$haspreload) {
	            $link = '<a href="' . $CFG->wwwroot . '/mod/elluminate/preload.php?id=' . $elluminate->id .
	                    '">' . get_string('addpreload', 'elluminate') . '</a>';
        }

        if (!empty($link)) {
            echo '<p class="elluminatepreload">' . $link . '</p>';
        }
    }

	/// Only display a link to join the meeting if the current user is a participant
	/// or moderator for this meeting and it is currently happening right now.
	if ($participant) {
		
        if (!empty($elluminate->boundarytimedisplay)) {
        	if(time() < $elluminate->timeend) {
            	echo '<p class="elluminateboundarytime">' . get_string('boundarytimemessage', 'elluminate', $elluminate->boundarytime) . '</p>';//, 'center', '5', 'main elluminateboundarytime');
        	}
        }

        if (!empty($elluminate->recordingmode) && ($elluminate->timeend > $timenow)) {
            $recordingstring = '';

            switch ($elluminate->recordingmode) {
                case ELLUMINATELIVE_RECORDING_MANUAL:
                    $recordingstring = get_string('recordingmanual', 'elluminate');
                    break;
                case ELLUMINATELIVE_RECORDING_AUTOMATIC:
                    $recordingstring = get_string('recordingautomatic', 'elluminate');
                    break;
                case ELLUMINATELIVE_RECORDING_NONE:
                    $recordingstring = get_string('recordingnone', 'elluminate');
                    break;
            }

            if (!empty($recordingstring)) {
                echo '<p class="elluminaterecordingmode">' . $recordingstring . '</p>';
            }
        }
	}

    if ($participant && $hasstarted && !$hasfinished) {

        $link = '<a href="' . $CFG->wwwroot .
        '/mod/elluminate/loadmeeting.php?id=' . $elluminate->id .
        '" target="_blank">' . get_string('joinsession', 'elluminate') . '</a>';

        echo '<p class="elluminatejoinmeeting">' . $link . '</p>';

        echo '<p class="elluminateverifysetup">' . get_string('supportlinktext', 'elluminate', elluminate_support_link()) . '</a>';
    }

/// Display a link to play the recording if one exists.
    if ($participant && $canviewrecordings &&
        ($recordings = get_records('elluminate_recordings', 'meetingid', $elluminate->meetingid, 'created ASC'))) {

        $displayrecordings = array();
		$hasedit = false;
		
        foreach ($recordings as $recording) {
        /// Is this recording visible for non-managing users?
            if (!$canmanageanyrecordings && !$canmanagerecordings && !$recording->visible) {
                continue;
            }

        /// If the activity is using separate groups and this user isn't a member of the specific
        /// group the recording is for, has this recording been made available to them?
            if ($groupmode == VISIBLEGROUPS && (
                    $currentgroup == 0 ||
                    ($currentgroup != 0 && !groups_is_member($currentgroup, $USER->id))
                ) && empty($recording->groupvisible)) {
                continue;
            }

            $link = '<a href="' . $CFG->wwwroot . '/mod/elluminate/loadrecording.php?id=' .
                    $recording->id . '" target="new">' . get_string('playrecording', 'elluminate') .
                    '</a> - ' . userdate($recording->created) . ' - ' . $recording->recordingsize . ' KB';

        /// Include the recording description, if not empty.
            if (!empty($recording->description)) {
                $link .= ' - <span class="description">' . $recording->description . '</span>';
            }

            if ($caneditallrecordings || ($caneditownrecordings && ($elluminate->creator == $USER->id))) {
            /// Display an icon to allow editing the extra description field for this recording.
                $tooltip = get_string('editrecordingdescription', 'elluminate');
				
				if($elluminate->groupmode > 0) {
	                $link .= ' <a href="' . $CFG->wwwroot . '/mod/elluminate/view.php?id= ' . $cm->id .
	                         '&amp;editrecordingdesc=' . $recording->id . '&amp;group=' . $elluminate->groupid  . '" title="' . $tooltip .
	                         '"><img src="' . $CFG->pixpath . '/i/edit.gif" width="11" height="11" alt="' .
	                         $tooltip .'" /></a>';
				} else {
					$link .= ' <a href="' . $CFG->wwwroot . '/mod/elluminate/view.php?id= ' . $cm->id .
	                         '&amp;editrecordingdesc=' . $recording->id . '" title="' . $tooltip .
	                         '"><img src="' . $CFG->pixpath . '/i/edit.gif" width="11" height="11" alt="' .
	                         $tooltip .'" /></a>';
				}
            }

            if ($candeleteanyrecordings || ($candeleterecordings && ($elluminate->creator == $USER->id))) {
                $tooltip = get_string('deletethisrecording', 'elluminate');

                $link .= ' <a href="' . $CFG->wwwroot . '/mod/elluminate/view.php?id=' . $cm->id .
                         '&amp;delrecording=' . $recording->id . '&amp;group=' . $elluminate->groupid  . '" title="' . $tooltip . '"><img src="' .
                         $CFG->pixpath . '/t/delete.gif" width="11" height="11" alt="' . $tooltip .
                         '"></a>';
            }

            if ($canmanageanyrecordings || $canmanagerecordings) {
            /// Display an icon to change the overall recording visibility.
                if ($recording->visible) {
                    $tooltip = get_string('hidethisrecording', 'elluminate');

                    $link .= ' <a href="' . $CFG->wwwroot . '/mod/elluminate/view.php?id=' . $cm->id .
                             '&amp;hiderecording=' . $recording->id . '&amp;group=' . $elluminate->groupid  . '" title="' . $tooltip . '"><img src="' .
                             $CFG->pixpath . '/t/hide.gif" width="11" ' . 'height="11" alt="' . $tooltip .
                             '"></a>';
                } else {
                    $tooltip = get_string('showthisrecording', 'elluminate');

                    $link .= ' <a href="' . $CFG->wwwroot . '/mod/elluminate/view.php?id=' . $cm->id .
                             '&amp;showrecording=' . $recording->id . '&amp;group=' . $elluminate->groupid  . '" title="' . $tooltip . '"><img src="' .
                             $CFG->pixpath . '/t/show.gif" width="11" height="11" alt="' . $tooltip .
                             '"></a>';
                }
        	}
        	
        	/// Display the form to edit a recording description, if selected.
        	$recordingdisplay = '';
        	$recordingdisplay = '<hr /><p class="elluminaterecording">' . $link . '</p>';
        	
        	if(($recording->id == $editrecordingdesc) && !empty($editrecordingdesc) &&
	            ($canmanageanyrecordings || $canmanagerecordings)) {	       
			    	$description = !empty($recording->description) ? $recording->description : '';
			
			        $descform  = '<div class="elluminaterecordingdescriptionedit">';
			        $descform .= '<form action="view.php?group=' . $elluminate->groupid . '" method="post">';
			        $descform .= '<input type="hidden" name="id" value="' . $cm->id . '" />';
			        $descform .= '<input type="hidden" name="recordingid" value="' . $recording->id . '" />';
			        $descform .= '<input type="hidden" name="groupid" value="' . $elluminate->groupid . '" />';
			        $descform .= '<input type="hidden" name="sesskey" value="' . $sesskey . '" />';
			        $descform .= get_string('description') . ': <input type="text" name="recordingdesc" size="50" maxlength="255" value="' .
			                     $description  .'" />';
			        $descform .= ' <input type="submit" name="descsave" value="' . get_string('savechanges') . '" />';
			        $descform .= ' <input type="submit" name="cancel" value="' . get_string('cancel') . '" />';
			        $descform .= '</form>';
			        $descform .= '</div>';
			        
			        $recordingdisplay .= $descform;
			    }	       
            $displayrecordings[$recording->id] = $recordingdisplay;
			//$displayrecordings = array_merge($displayrecordings, array($recording->id => $recordingdisplay));
        }
    	echo implode('', $displayrecordings);
    }
    
    
    
    

/// Display an attendance page if attendance was recorded for this meeting.
    if ($participant && ($canviewattendance || $canmanageattendance) && $elluminate->grade && $hasfinished) {
        $link =  '<a href="' . $CFG->wwwroot . '/mod/elluminate/attendance.php?id=' .
                 $elluminate->id . '">' . get_string('meetingattendance', 'elluminate') .
                 '</a>';

        echo '<p class="elluminateattendance">' . $link . '</p>';
    }

    print_simple_box_end();

/// Finish the page
    print_footer($course);

?>
