<?php 
/**
 * Library of functions and constants for module webquestscorm

 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: lib.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

DEFINE ('WEBQUESTSCORM_COUNT_WORDS', 1);

if (!isset($CFG->webquestscorm_maxbytes)) {
    set_config("webquestscorm_maxbytes", 1024000);  // Default maximum size for all tasks
}
if (!isset($CFG->webquestscorm_itemstocount)) {
    set_config("webquestscorm_itemstocount", WEBQUESTSCORM_COUNT_WORDS);  // Default item to count
}


/**
 * Update grades by firing grade_updated event
 *
 * @param object $assignment null means all assignments
 * @param int $userid specific user only, 0 mean all
 */
function webquestscorm_update_grades($webquestscorm=null, $userid=0, $nullifnone=true) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($webquestscorm != null) {
        if ($grades = webquestscorm_get_user_grades($webquestscorm, $userid)) {
            foreach($grades as $k=>$v) {
                if ($v->rawgrade == -1) {
                    $grades[$k]->rawgrade = null;
                }
            }
            webquestscorm_grade_item_update($webquestscorm, $grades);
        } else {
            webquestscorm_grade_item_update($webquestscorm);
        }

    } else {
        $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
                  FROM {$CFG->prefix}webquestscorm a, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='webquestscorm' AND m.id=cm.module AND cm.instance=a.id";
        if ($rs = get_recordset_sql($sql)) {
            while ($webquestscorm = rs_fetch_next_record($rs)) {
                if ($webquestscorm->grade != 0) {
                    webquestscorm_update_grades($webquestscorm);
                } else {
                    webquestscorm_grade_item_update($webquestscorm);
                }
            }
            rs_close($rs);
        }
    }
	
}



/**
 * Return grade for given user or all users.
 *
 * @param int $assignmentid id of assignment
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function webquestscorm_get_user_grades($webquestscorm, $userid=0) {
    global $CFG;

    $user = $userid ? "AND u.id = $userid" : "";

    $sql = "SELECT u.id, u.id AS userid, s.grade AS rawgrade, s.submissioncomment AS feedback, s.format AS feedbackformat,
                   s.teacher AS usermodified, s.timemarked AS dategraded, s.timemodified AS datesubmitted
              FROM {$CFG->prefix}user u, {$CFG->prefix}webquestscorm_submissions s
             WHERE u.id = s.userid AND s.webquestscorm = $webquestscorm->id
                   $user";

    return get_records_sql($sql);
}



 /**
  * Update grade item for this submission.
  */
   function update_grade_for_webquestscorm($webquestscorm) {
        webquestscorm_update_grades($webquestscorm, $webquestscorm->userid);
	
    }




/**
 * Create grade item for given webquestscorm
 *
 *
 * @param object $webquestscorm object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */

function webquestscorm_grade_item_update($webquestscorm, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

     if (!isset($webquestscorm->course)) {
        $webquestscorm->course = $webquestscorm->course;
    }

//    $params = array('itemname'=>$webquestscorm->name, 'idnumber'=>$webquestscorm->cm->id);
    $params = array('itemname'=>$webquestscorm->name, 'idnumber'=>$webquestscorm->id);
    if ($webquestscorm->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $webquestscorm->grade;
        $params['grademin']  = 0;

    } else if ($webquestscorm->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$webquestscorm->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // allow text comments only
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }


    return grade_update('mod/webquestscorm', $webquestscorm->course, 'mod', 'webquestscorm', $webquestscorm->id, 0, $grades, $params);

}





/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted webquestscorm record
 **/
function webquestscorm_add_instance($webquestscorm) {

   global $CFG;
   $webquestscorm->timemodified = time();

/*
   if (empty($webquestscorm->dueenable)) {
       $webquestscorm->timedue = 0;
       $webquestscorm->preventlate = 0;
   } else {
       $webquestscorm->timedue = make_timestamp($webquestscorm->dueyear, $webquestscorm->duemonth, 
                                                      $webquestscorm->dueday, $webquestscorm->duehour, 
                                                      $webquestscorm->dueminute);
   }
   if (empty($webquestscorm->availableenable)) {
       $webquestscorm->timeavailable = 0;
   } else {
       $webquestscorm->timeavailable = make_timestamp($webquestscorm->availableyear, $webquestscorm->availablemonth, 
                                                            $webquestscorm->availableday, $webquestscorm->availablehour, 
                                                            $webquestscorm->availableminute);
   }
*/
        
   $result = insert_record("webquestscorm", $webquestscorm);
   if ($result) {

		$webquestscorm->id=$result; 


            if ($webquestscorm->timedue) {
		$event = new object(); 
                $event->id          = $webquestscorm->id;
                $event->name        = $webquestscorm->name;
                $event->description = $webquestscorm->name;
                $event->courseid    = $webquestscorm->course;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'webquestscorm';
                $event->instance    = $result;
                $event->eventtype   = 'due';
                $event->timestart   = $webquestscorm->timedue;
                $event->timeduration = 0;
                add_event($event);
            }
	
	if ($CFG->version > 2007101500){ 
		$webquestscorm = stripslashes_recursive($webquestscorm);
       	webquestscorm_grade_item_update($webquestscorm);
	}

   }   
   return $result;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function webquestscorm_update_instance($webquestscorm) {

   $webquestscorm->id=$webquestscorm->instance;
   $webquestscorm->timemodified = time();

/*
   if (empty($webquestscorm->dueenable)) {
       $webquestscorm->timedue = 0;
       $webquestscorm->preventlate = 0;
   } else {
       $webquestscorm->timedue = make_timestamp($webquestscorm->dueyear, $webquestscorm->duemonth, 
                                                      $webquestscorm->dueday, $webquestscorm->duehour, 
                                                      $webquestscorm->dueminute);
   }
   if (empty($webquestscorm->availableenable)) {
       $webquestscorm->timeavailable = 0;
   } else {
       $webquestscorm->timeavailable = make_timestamp($webquestscorm->availableyear, $webquestscorm->availablemonth, 
                                                            $webquestscorm->availableday, $webquestscorm->availablehour, 
                                                            $webquestscorm->availableminute);
   }
*/
       
   $result = update_record("webquestscorm", $webquestscorm);

   if ($result) {

        if ($webquestscorm->timedue) {
            $event = NULL;

            if ($event->id = get_field('event', 'id', 'modulename', 'webquestscorm', 'instance', $webquestscorm->id)) {
                $event->name        = $webquestscorm->name;
                $event->description = $webquestscorm->description;
                $event->timestart   = $webquestscorm->timedue;
                update_event($event);
             } else {
                $event = NULL;
                $event->id 	    = $webquestscorm->id;                
                $event->name        = $webquestscorm->name;
                $event->description = $webquestscorm->name;
                $event->courseid    = $webquestscorm->course;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'webquestscorm';
		$webquestscorm->id = $webquestscorm->instance;
                $event->eventtype   = 'due';
                $event->timestart   = $webquestscorm->timedue;
                $event->timeduration = 0;
  
                add_event($event);
             }
         } else {
                delete_records('event', 'modulename', 'webquestscorm', 'instance', $webquestscorm->id);
         }
   }   

       	$webquestscorm = stripslashes_recursive($webquestscorm);
        webquestscorm_grade_item_update($webquestscorm);

   return $result;
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function webquestscorm_delete_instance($wqid) {

    global $CFG;

    if (!$webquestscorm = get_record('webquestscorm', 'id', $wqid)) {
        error('Course module is incorrect');
    }
    
    if (!$cm = get_coursemodule_from_instance('webquestscorm', $webquestscorm->id, $webquestscorm->course)) {
        return false;
    }
    
    $result = true;

    if (! delete_records('webquestscorm_submissions', 'webquestscorm', $webquestscorm->id)) {
        $result = false;
    }

    if (! delete_records('webquestscorm', 'id', $webquestscorm->id)) {
        $result = false;
    }

    if (! delete_records('event', 'modulename', 'webquestscorm', 'instance', $webquestscorm->id)) {
        $result = false;
    }
    
    if ($CFG->version < 2007101500){ 

	  if (! $cm = get_record('modules', 'name', 'webquestscorm')) {
        	$result = false;
    	  } else {
        	if (! delete_records('grade_item', 'modid', $cm->id, 'cminstance', $webquestscorm->id)) {
                $result = false;
        	}
    	  }   
    }else
    	  webquestscorm_grade_item_delete($webquestscorm);

    require_once($CFG->libdir.'/filelib.php');
    fulldelete($CFG->dataroot.'/'.$webquestscorm->course.'/'.$CFG->moddata.'/webquestscorm/'.$cm->id);

    return $result;
}


function webquestscorm_grade_item_delete($webquestscorm) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($webquestscorm->courseid)) {
        $webquestscorm->courseid = $webquestscorm->course;
    }

    return grade_update('mod/webquestscorm', $webquestscorm->courseid, 'mod', 'webquestscorm', $webquestscorm->id, 0, NULL, array('deleted'=>1));
}




/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function webquestscorm_user_outline($course, $user, $mod, $webquestscorm) {

   if ($submission = webquestscorm_get_submission($webquestscorm, $user->id) ) {
       $result->info = get_string('grade').': '.webquestscorm_display_grade($webquestscorm, $submission->grade);
       $result->time = $submission->timemodified;
       return $result;
   }
	return '';
}


/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function webquestscorm_user_complete($course, $user, $mod, $webquestscorm) {
        if ($submission = webquestscorm_get_submission($webquestscorm, $user->id)) {
            if ($basedir = webquestscorm_file_area($webquestscorm, $user->id)) {
                if ($files = get_directory_list($basedir)) {
                    $countfiles = count($files)." ".get_string("uploadedfiles", "webquestscorm");
                    foreach ($files as $file) {
                        $countfiles .= "; $file";
                    }
                }
            }
    
            print_simple_box_start();
            echo get_string("lastmodified").": ";
            echo userdate($submission->timemodified);
            echo webquestscorm_display_lateness($webquestscorm, $submission->timemodified);
             
            webquestscorm_print_user_files($webquestscorm, $user->id); 
    
            echo '<br />';
    
            if (empty($submission->timemarked)) {
                print_string("notgradedyet", "webquestscorm");
            } else {
                webquestscorm_view_feedback($webquestscorm, $submission);
            }
    
            print_simple_box_end();
    
        } else {
            print_string("notsubmittedyet", "webquestscorm");
        }
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in webquestscorm activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function webquestscorm_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    $content = false;
    $webquestscorms = array();

    if (!$logs = get_records_select('log', 'time > \''.$timestart.'\' AND '.
                                           'course = \''.$course->id.'\' AND '.
                                           'module = \'webquestscorm\' AND '.
                                           'action = \'upload\' ', 'time ASC')) {
        return false;
    }

    foreach ($logs as $log) {
        //Create a temp valid module structure (course,id)
        $tempmod = new object();
        $tempmod->course = $log->course;
        $tempmod->id = $log->info;
        //Obtain the visible property from the instance
        $modvisible = instance_is_visible($log->module,$tempmod);

        //Only if the mod is visible
        if ($modvisible) {
            if ($info = webquestscorm_log_info($log)) {
                $webquestscorms[$log->info] = $info;
                $webquestscorms[$log->info]->time = $log->time;
                $webquestscorms[$log->info]->url  = str_replace('&', '&amp;', $log->url);
            }
        }
    }

    if (!empty($webquestscorms)) {
        print_headline(get_string('newsubmissions', 'webquestscorm').':');
        foreach ($webquestscorms as $webquestscorm) {
            print_recent_activity_note($webquestscorm->time, $webquestscorm, $webquestscorm->name,
                                       $CFG->wwwroot.'/mod/webquestscorm/'.$webquestscorm->url);
        }
        $content = true;
    }

    return $content;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/


function webquestscorm_cron () {

    global $CFG, $USER;

    /// Notices older than 1 day will not be mailed.  This is to avoid the problem where
    /// cron has not been running for a long time, and then suddenly people are flooded
    /// with mail from the past few weeks or months

    $timenow   = time();
    $endtime   = $timenow - $CFG->maxeditingtime;
    $starttime = $endtime - 24 * 3600;   /// One day earlier

    if ($submissions = webquestscorm_get_unmailed_submissions($starttime, $endtime)) {

        foreach ($submissions as $key => $submission) {
            if (! set_field("webquestscorm_submissions", "mailed", "1", "id", "$submission->id")) {
                echo "Could not update the mailed field for id $submission->id.  Not mailed.\n";
                unset($submissions[$key]);
            }
        }

        $timenow = time();

        foreach ($submissions as $submission) {

            echo "Processing webquestscorm submission $submission->id\n";

            if (! $user = get_record("user", "id", "$submission->userid")) {
                echo "Could not find user $post->userid\n";
                continue;
            }

            $USER->lang = $user->lang;

            if (! $course = get_record("course", "id", "$submission->course")) {
                echo "Could not find course $submission->course\n";
                continue;
            }
            
            if (!has_capability('moodle/course:view', get_context_instance(CONTEXT_COURSE, $submission->course), $user->id)) {
                echo fullname($user)." not an active participant in $course->shortname\n";
                continue;
            }

            if (! $teacher = get_record("user", "id", "$submission->teacher")) {
                echo "Could not find teacher $submission->teacher\n";
                continue;
            }

            if (! $mod = get_coursemodule_from_instance("webquestscorm", $submission->webquestscorm, $course->id)) {
                echo "Could not find course module for webquestscorm id $submission->webquestscorm\n";
                continue;
            }

            if (! $mod->visible) {    
                continue;
            }

            $strassignments = get_string("modulenameplural", "webquestscorm");
            $strassignment  = get_string("modulename", "webquestscorm");

            unset($webquestscorminfo);
            $webquestscorminfo->teacher = fullname($teacher);
            $webquestscormtinfo->assignment = format_string($submission->name,true);
            $webquestscorminfo->url = "$CFG->wwwroot/mod/webquestscorm/view.php?id=$mod->id";

            $postsubject = "$course->shortname: $strwebquestscorms: ".format_string($submission->name,true);
            $posttext  = "$course->shortname -> $strwebquestscorms -> ".format_string($submission->name,true)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            $posttext .= get_string("webquestscormmail", "webquestscorm", $webquestscorminfo)."\n";
            $posttext .= "---------------------------------------------------------------------\n";

            if ($user->mailformat == 1) {  // HTML
                $posthtml = "<p><font face=\"sans-serif\">".
                "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->".
                "<a href=\"$CFG->wwwroot/mod/webquestscorm/index.php?id=$course->id\">$strwebquestscorms</a> ->".
                "<a href=\"$CFG->wwwroot/mod/webquestscorm/view.php?id=$mod->id\">".format_string($submission->name,true)."</a></font></p>";
                $posthtml .= "<hr /><font face=\"sans-serif\">";
                $posthtml .= "<p>".get_string("webquestscormmailhtml", "webquestscorm", $webquestscorminfo)."</p>";
                $posthtml .= "</font><hr />";
            } else {
                $posthtml = "";
            }

            if (! email_to_user($user, $teacher, $postsubject, $posttext, $posthtml)) {
                echo "Error: webquestscorm cron: Could not send out mail for id $submission->id to user $user->id ($user->email)\n";
            }
        }
    }

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $webquestscormid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function webquestscorm_grades($wqid) {

    if (!$webquestscorm = get_record('webquestscorm', 'id', $wqid)) {
        return NULL;
    }
    if ($webquestscorm->grade == 0) { 
        return NULL;
    }

    $grades = get_records_menu('webquestscorm_submissions', 'webquestscorm',
                               $webquestscorm->id, '', 'userid,grade');

    if ($webquestscorm->grade > 0) {
        if ($grades) {
            foreach ($grades as $userid => $grade) {
                if ($grade == -1) {
                    $grades[$userid] = '-';
                }
            }
        }
        $return->grades = $grades;
        $return->maxgrade = $webquestscorm->grade;

    } else {
        if ($grades) {
            $scaleid = - ($webquestscorm->grade);
            $maxgrade = "";
            if ($scale = get_record('scale', 'id', $scaleid)) {
                $scalegrades = make_menu_from_list($scale->scale);
                foreach ($grades as $userid => $grade) {
                    if (empty($scalegrades[$grade])) {
                        $grades[$userid] = '-';
                    } else {
                        $grades[$userid] = $scalegrades[$grade];
                    }
                }
                $maxgrade = $scale->name;
            }
        }
        $return->grades = $grades;
        $return->maxgrade = $maxgrade;
    }

    return $return;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of webquestscorm. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $webquestscormid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function webquestscorm_get_participants($wqid) {
    global $CFG;

    //Get students
    $students = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}webquestscorm_submissions a
                                 WHERE a.webquestscorm = '$wqid' and
                                       u.id = a.userid");
    //Get teachers
    $teachers = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}webquestscorm_submissions a
                                 WHERE a.webquestscorm = '$wqid' and
                                       u.id = a.teacher");

    //Add teachers to students
    if ($teachers) {
        foreach ($teachers as $teacher) {
            $students[$teacher->id] = $teacher;
        }
    }
    //Return students array (it contains an array of unique users)
    return ($students);
}

/**
 * This function returns if a scale is being used by one webquestscorm
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $webquestscormid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/





function webquestscorm_scale_used ($wqid,$scaleid) {
    $return = false;

    $rec = get_record('webquestscorm','id',$wqid,'grade',-$scaleid);

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Return list of marked submissions that have not been mailed out for currently enrolled students
 *
 * @return array
 */
function webquestscorm_get_unmailed_submissions($starttime, $endtime) {

    global $CFG;
    
    return get_records_sql("SELECT s.*, a.course, a.name
                              FROM {$CFG->prefix}webquestscorm_submissions s, 
                                   {$CFG->prefix}webquestscorm a
                             WHERE s.mailed = 0 
                               AND s.timemarked <= $endtime 
                               AND s.timemarked >= $starttime
                               AND s.webquestscorm = a.id");

}

/**
 * Make sure up-to-date events are created for all webquestscorm instances
 *
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every webquestscorm event in the site is checked, else
 * only webquestscorm events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param $courseid int optional If zero then all webquestscorms for all courses are covered
 * @return boolean Always returns true
 */
function webquestscorm_refresh_events($courseid = 0) {

    if ($courseid == 0) {
        if (! $webquestscorms = get_records("webquestscorm")) {
            return true;
        }
    } else {
        if (! $webquestscorms = get_records("webquestscorm", "course", $courseid)) {
            return true;
        }
    }
    $moduleid = get_field('modules', 'id', 'name', 'webquestscorm');

    foreach ($webquestscorms as $webquestscorm) {
        $event = NULL;
        $event->name        = addslashes($webquestscorm->name);
        $event->description = addslashes($webquestscorm->name);
        $event->timestart   = $webquestscorm->timedue;

        if ($event->id = get_field('event', 'id', 'modulename', 'webquestscorm', 'instance', $webquestscorm->id)) {
            update_event($event);

        } else {
            $event->courseid    = $webquestscorm->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'webquestscorm';
            $event->instance    = $webquestscorm->id;
            $event->eventtype   = 'due';
            $event->timeduration = 0;
            $event->visible     = get_field('course_modules', 'visible', 'module', $moduleid, 'instance', $webquestscorm->id);
            add_event($event);
        }

    }
    return true;
}
/**
 * Returns all webquestscorms since a given time.
 *
 * If webquestscorm is specified then this restricts the results
 */
function webquestscorm_get_recent_mod_activity(&$activities, &$index, $sincetime, $courseid, $webquestscorm="0", $user="", $groupid="")  {

    global $CFG;

    if ($webquestscorm) {
        $webquestscormselect = " AND cm.id = '$webquestscorm'";
    } else {
        $webquestscormselect = "";
    }
    if ($user) {
        $userselect = " AND u.id = '$user'";
    } else {
        $userselect = "";
    }

    $webquestscorms = get_records_sql("SELECT asub.*, u.firstname, u.lastname, u.picture, u.id as userid,
                                           a.grade as maxgrade, name, cm.instance, cm.section
                                  FROM {$CFG->prefix}webquestscorm_submissions asub,
                                       {$CFG->prefix}user u,
                                       {$CFG->prefix}webquestscorm a,
                                       {$CFG->prefix}course_modules cm
                                 WHERE asub.timemodified > '$sincetime'
                                   AND asub.userid = u.id $userselect
                                   AND a.id = asub.webquestscorm $webquestscormselect
                                   AND cm.course = '$courseid'
                                   AND cm.instance = a.id
                                 ORDER BY asub.timemodified ASC");

    if (empty($webquestscorms))
      return;

    foreach ($webquestscorms as $webquestscorm) {
        if (empty($groupid) || ismember($groupid, $webquestscorm->userid)) {

          $tmpactivity = new Object;

          $tmpactivity->type = "webquestscorm";
          $tmpactivity->defaultindex = $index;
          $tmpactivity->instance = $webquestscorm->instance;
          $tmpactivity->name = $webquestscorm->name;
          $tmpactivity->section = $webquestscorm->section;

          $tmpactivity->content->grade = $webquestscorm->grade;
          $tmpactivity->content->maxgrade = $webquestscorm->maxgrade;

          $tmpactivity->user->userid = $webquestscorm->userid;
          $tmpactivity->user->fullname = fullname($webquestscorm);
          $tmpactivity->user->picture = $webquestscorm->picture;

          $tmpactivity->timestamp = $webquestscorm->timemodified;

          $activities[] = $tmpactivity;

          $index++;
        }
    }

    return;
}
/**
 * Fetch info from logs
 *
 * @param $log object with properties ->info (the webquestscorm id) and ->userid
 * @return array with webquestscorm name and user firstname and lastname
 */
function webquestscorm_log_info($log) {
    global $CFG;
    return get_record_sql("SELECT a.name, u.firstname, u.lastname
                             FROM {$CFG->prefix}webquestscorm a, 
                                  {$CFG->prefix}user u
                            WHERE a.id = '$log->info' 
                              AND u.id = '$log->userid'");
}

/**
 * Return all webquestscorm submissions by ENROLLED students (even empty)
 *
 * There are also webquestscorm type methods get_submissions() wich in the default
 * implementation simply call this function.
 * @param $sort string optional field names for the ORDER BY in the sql query
 * @param $dir string optional specifying the sort direction, defaults to DESC
 * @return array The submission objects indexed by id
 */
function webquestscorm_get_all_submissions($webquestscorm, $sort="", $dir="DESC") {
    global $CFG;

    if ($sort == "lastname" or $sort == "firstname") {
        $sort = "u.$sort $dir";
    } else if (empty($sort)) {
        $sort = "a.timemodified DESC";
    } else {
        $sort = "a.$sort $dir";
    }
    
    return get_records_sql("SELECT a.* 
                              FROM {$CFG->prefix}webquestscorm_submissions a, 
                                   {$CFG->prefix}user u
                             WHERE u.id = a.userid
                               AND a.webquestscorm = '$webquestscorm->id' 
                          ORDER BY $sort");
}

/**
 * Return all webquestscorm submissions by User id
 *
 * There are also webquestscorm type methods get_submissions() wich in the default
 * implementation simply call this function.
 * @param $uid integer - user id
 * @param $sort string optional field names for the ORDER BY in the sql query
 * @param $dir string optional specifying the sort direction, defaults to DESC
 * @return array The submission objects indexed by id
 */
function webquestscorm_get_submission($webquestscorm, $uid ,$sort="", $dir="DESC") {
    global $CFG;

    if ($sort == "lastname" or $sort == "firstname") {
        $sort = "u.$sort $dir";
    } else if (empty($sort)) {
        $sort = "a.timemodified DESC";
    } else {
        $sort = "a.$sort $dir";
    }

    return get_records_sql("SELECT a.*
                              FROM {$CFG->prefix}webquestscorm_submissions a
                              JOIN {$CFG->prefix}user u ON u.id = a.userid
                             WHERE a.userid = $uid
                               AND a.webquestscorm = '$webquestscorm->id'
                          ORDER BY $sort");
}
?>
