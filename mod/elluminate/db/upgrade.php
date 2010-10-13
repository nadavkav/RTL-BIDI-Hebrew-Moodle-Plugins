<?php // $Id: upgrade.php,v 1.6 2009-06-05 20:12:38 jfilip Exp $

/**
 * Database upgrade code.
 *
 * @version $Id: upgrade.php,v 1.6 2009-06-05 20:12:38 jfilip Exp $
 * @author Justin Filip <jfilip@remote-learner.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */

    function xmldb_elluminate_upgrade($oldversion = 0) {
        global $CFG, $THEME, $db;

        $result = true;		
        if ($oldversion < 2006062102) {
        /// This should not be necessary but it's included just in case.
            $result = install_from_xmldb_file($CFG->dirroot . '/mod/elluminate/db/install.xml');
        }

        if ($result && $oldversion < 2009090801) {            
            $meetings = get_records('elluminate');
            
            $table = new XMLDBTable('elluminate');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }
	        
	        $table = new XMLDBTable('elluminate_recordings');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }    	       
	        	        
	        $table = new XMLDBTable('elluminate_session');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }
	        
	        $table = new XMLDBTable('elluminate_users');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }   

			$table = new XMLDBTable('elluminate_preloads');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }
	        
			install_from_xmldb_file($CFG->dirroot . '/mod/elluminate/db/upgrade.xml');         
            
            /// Modify all of the existing meetings, if any.
            if ($result && !empty($meetings)) {
                $timenow = time();
				
                foreach ($meetings as $meeting) {
                /// Update the meeting by storing values from the ELM server in the local DB.
                    if (!$elmmeeting = elluminate_get_meeting_full_response($meeting->meetingid)) {
                        continue;
                    }
					
                    //$mparams = elluminate_get_meeting_parameters($meeting->meetingid);
                    $sparams = elluminate_get_server_parameters($meeting->meetingid);					
                    $umeeting = new stdClass;
                    //$umeeting->id          = $meeting->id;
                    $umeeting->meetingid   = $meeting->meetingid;
                    $umeeting->meetinginit   = 2;
                    $umeeting->course = $meeting->course;
                    $umeeting->creator =  $elmmeeting->creatorId;
                    $umeeting->groupmode = '0';
                    $umeeting->groupid = '0';
                    $umeeting->sessionname = addslashes($meeting->name);
                    $umeeting->timestart   = substr($elmmeeting->startTime, 0, -3);
                    $umeeting->timeend     = substr($elmmeeting->endTime, 0, -3);
                    $umeeting->nonchairlist  = $elmmeeting->nonChairList;
                    $umeeting->chairlist     = $elmmeeting->chairList;
					$umeeting->recordingmode = $elmmeeting->recordingModeType;					
					$umeeting->name = $meeting->name;
					$umeeting->description = addslashes($meeting->description);
					$umeeting->boundarytime = $elmmeeting->boundaryTime;
					$umeeting->boundarytimedisplay = 1;
					$umeeting->seats = $meeting->seats;
					$umeeting->private = $meeting->private;
					$umeeting->grade = $meeting->grade;
					$umeeting->timemodified = $meeting->timemodified;
								                  
                    insert_record('elluminate', $umeeting);                                        
                    $newmeeting = get_record('elluminate', 'meetingid', $meeting->meetingid);                    

                    $attendancerecords = get_records('elluminate_attendance', 'elluminateid', $meeting->id);
                    
                    if(!empty($attendancerecords)) {
	                    foreach ($attendancerecords as $attendee) {
	                    	$attendee->ellumianteid = $newmeeting->id;
	                    	update_record('elluminate_attendance', $attendee);                    
	                    }
                    }
                    
                    
                    $recordings = elluminate_list_recordings($meeting->meetingid);
                    
		            if ($result && !empty($recordings)) {
		                $timenow = time();
		
		                foreach ($recordings as $recording) {               
		                	$urecording = new stdClass;
		                    $urecording->meetingid   = $recording->meetingid;
		                    $urecording->recordingid = $recording->recordingid;
		                    $urecording->description = $recording->roomname;
		                    $urecording->visible 	   = '1';
		                    $urecording->groupvisible = '0';
		                    $urecording->created 		= $recording->created;
							insert_record('elluminate_recordings', $urecording);
		                }
		            }
                }
            }   
            
            
            $timenow = time();
            $sysctx  = get_context_instance(CONTEXT_SYSTEM);

            $adminrid          = get_field('role', 'id', 'shortname', 'admin');
            $coursecreatorrid  = get_field('role', 'id', 'shortname', 'coursecreator');
            $editingteacherrid = get_field('role', 'id', 'shortname', 'editingteacher');
            $teacherrid        = get_field('role', 'id', 'shortname', 'teacher');

        /// Fully setup the Elluminate Moderator role.
            if ($result && !$mrole = get_record('role', 'shortname', 'elluminatemoderator')) {
                if ($rid = create_role(get_string('elluminatemoderator', 'elluminatelive'), 'elluminatemoderator',
                                       get_string('elluminatemoderatordescription', 'elluminatelive'))) {

                    $mrole  = get_record('role', 'id', $rid);
                    $result = $result && assign_capability('mod/elluminatelive:moderatemeeting', CAP_ALLOW, $mrole->id, $sysctx->id);
                } else {
                    $result = false;
                }
            }

            if (!count_records('role_allow_assign', 'allowassign', $mrole->id)) {
                $result = $result && allow_assign($adminrid, $mrole->id);
                $result = $result && allow_assign($coursecreatorrid, $mrole->id);
                $result = $result && allow_assign($editingteacherrid, $mrole->id);
                $result = $result && allow_assign($teacherrid, $mrole->id);
            }


        /// Fully setup the Elluminate Participant role.
            if ($result && !$prole = get_record('role', 'shortname', 'elluminateparticipant')) {
                if ($rid = create_role(get_string('elluminateparticipant', 'elluminatelive'), 'elluminateparticipant',
                                       get_string('elluminateparticipantdescription', 'elluminatelive'))) {

                    $prole  = get_record('role', 'id', $rid);
                    $result = $result && assign_capability('mod/elluminatelive:joinmeeting', CAP_ALLOW, $prole->id, $sysctx->id);
                } else {
                    $result = false;
                }
            }

            if (!count_records('role_allow_assign', 'allowassign', $prole->id)) {
                $result = $result && allow_assign($adminrid, $prole->id);
                $result = $result && allow_assign($coursecreatorrid, $prole->id);
                $result = $result && allow_assign($editingteacherrid, $prole->id);
                $result = $result && allow_assign($teacherrid, $prole->id);
            }
            
            
            
                   
        }

		if ($result && $oldversion == 2010021600) {			
			$table = new XMLDBTable('elluminate');
			
			$field = new XMLDBField('sessiontype');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, false, false, '0', 'creator');
            $result = $result && add_field($table, $field);
            
            $field = new XMLDBField('groupingid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, false, false, '0', 'sessiontype');
            $result = $result && add_field($table, $field);
            
            $meetings = get_records('elluminate');
            
            foreach ($meetings as $meeting) {
            	$meeting->groupingid = 0;            	
            	if($meeting->private == true) {
            		$meeting->sessiontype = 1;
            	}            	
            	if($meeting->groupmode > 0) {
            		$meeting->sessiontype = 2;
            	}   
            	
            	update_record('elluminate', $meeting);      		
            }
            
            $field = new XMLDBField('private');
            drop_field($table, $field);         
            
            $recordings_table = new XMLDBTable('elluminate_recordings');
			$size_field = new XMLDBField('recordingsize');
            $size_field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'description');
            $result = $result && add_field($recordings_table, $size_field);               
            
			$recordings = get_records('elluminate_recordings');    		    
		    foreach($recordings as $recording) {
		    	$full_recordings = elluminate_list_recordings($recording->meetingid);
		    	foreach($full_recordings as $full_recording) {
		    		if($full_recording->recordingid == $recording->recordingid) {
		    			$recording->recordingsize = $full_recording->size;
		    			update_record('elluminate_recordings', $recording);	
		    		}
		    	}
		    }
            
		}
		
        return $result;
    }

?>
