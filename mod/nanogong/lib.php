<?php  // $Id: lib.php,v 4 2010/04/22 00:00:00 gibson Exp $

require_once($CFG->dirroot . "/version.php");

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted nanogong record
 **/
function nanogong_add_instance($nanogong) {
		global $CFG;

    $nanogong->timecreated = time();
    $nanogong->timemodified = time();
    $nanogong->maxmessages = clean_param($nanogong->maxmessages, PARAM_INT);
    $nanogong->maxduration = clean_param($nanogong->maxduration, PARAM_INT);
    $nanogong->maxscore = clean_param($nanogong->maxscore, PARAM_INT);

    $nanogong->id = insert_record("nanogong", $nanogong);

		if(substr($CFG->release, 0, 3) == "1.9") {
    	nanogong_update_grades($nanogong);
		}

    return $nanogong->id;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function nanogong_update_instance($nanogong) {
		global $CFG;

    $nanogong->timemodified = time();
    $nanogong->id = $nanogong->instance;
    $nanogong->maxmessages = clean_param($nanogong->maxmessages, PARAM_INT);
    $nanogong->maxduration = clean_param($nanogong->maxduration, PARAM_INT);
    $nanogong->maxscore = clean_param($nanogong->maxscore, PARAM_INT);

		if(substr($CFG->release, 0, 3) == "1.9") {
			return update_record("nanogong", $nanogong) && nanogong_update_grades($nanogong);
		}
		
		return update_record("nanogong", $nanogong);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function nanogong_delete_instance($id) {
		global $CFG;

    if (! $nanogong = get_record("nanogong", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (!delete_records("nanogong", "id", "$nanogong->id")) {
        $result = false;
    }
    if ($nanogong_messages = get_records("nanogong_message", "nanogongid", "$nanogong->id")) {
        global $CFG;
        foreach ($nanogong_messages as $nanogong_message) {
            $soundfile = $CFG->dataroot.$nanogong_message->path;
            if (file_exists($soundfile)) unlink($soundfile);
        }
    }

		if(substr($CFG->release, 0, 3) == "1.9") {
			nanogong_grade_item_delete($nanagong);
		}

    if (!delete_records("nanogong_message", "nanogongid", "$nanogong->id")) {
        $result = false;
    }

    return $result;
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
 * @param int $modid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/

if(substr($CFG->release, 0, 3) == "1.9") {
}
else {
function nanogong_grades($modid) {
    if (!$nanogong = get_record('nanogong', 'id', $modid)) {
        return null;
    }

    $grades = array();
    $students = get_course_students($nanogong->course);
    $nanogong_messages = get_records("nanogong_message", "nanogongid", $nanogong->id);
    if ($students != null) {
        foreach ($students as $student) {
            $grade = "-";
            if ($nanogong_messages) {
                $count = 0;
                foreach ($nanogong_messages as $nanogong_message) {
                    if ($nanogong_message->userid != $student->id) continue;
                    if ($grade == "-")
                        $grade = $nanogong_message->score;
                    else
                        $grade += $nanogong_message->score;
                    $count++;
                }
                if ($count > 0) $grade = $grade / $count;
            }
            $grades[$student->id] = $grade;
        }
    }
    $return->grades = $grades;
    $return->maxgrade = $nanogong->maxscore;

    return $return;
}
}

/**
 * Return grade for given user or all users.
 *
 * @param int $nanogongid id of nanagong
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function nanogong_get_user_grades($nanogong, $userid=0) {
    global $CFG;

    $user = $userid ? "AND u.id = $userid" : "";

    $sql = "SELECT u.id, u.id AS userid, AVG(g.score) AS rawgrade, MAX(g.timestamp) AS datesubmitted, MAX(g.timeedited) AS dategraded
            FROM {$CFG->prefix}user u, {$CFG->prefix}nanogong_message g
            WHERE u.id = g.userid AND g.nanogongid = {$nanogong->id}
                  $user
            GROUP BY u.id";

		$result = get_records_sql($sql);

    return $result;
}

/**
 * Update grades in central gradebook
 *
 * @param object $nanogong null means all nanogongs
 * @param int $userid specific user only, 0 mean all
 */
function nanogong_update_grades($nanogong=null, $userid=0, $nullifnone=true) {

    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($nanogong != null) {
        if ($grades = nanogong_get_user_grades($nanogong, $userid)) {
            nanogong_grade_item_update($nanogong, $grades);

        } else if ($userid and $nullifnone) {
            $grade = new object();
            $grade->userid   = $userid;
            $grade->rawgrade = NULL;
            nanogong_grade_item_update($nanogong, $grade);

        } else {
            nanogong_grade_item_update($nanogong);
        }

    } else {
        $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
                  FROM {$CFG->prefix}nanogong a, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='nanogong' AND m.id=cm.module AND cm.instance=a.id";
        if ($rs = get_recordset_sql($sql)) {
            while ($nanogong = rs_fetch_next_record($rs)) {
                if ($nanogong->maxscore != 0) {
                    nanogong_update_grades($nanogong, 0, false);
                } else {
                    nanogong_grade_item_update($nanogong);
                }
            }
            rs_close($rs);
        }
    }
    
    return true;
}

/**
 * Create grade item for given nanogong
 *
 * @param object $nanogong object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function nanogong_grade_item_update($nanogong, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $nanogong)) { //it may not be always present
        $params = array('itemname'=>$nanogong->name, 'idnumber'=>$nanogong->cmidnumber);
    } else {
        $params = array('itemname'=>$nanogong->name);
    }

    if ($nanogong->maxscore > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $nanogong->maxscore;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }
    
    $gradebook_grades = grade_get_grades($nanogong->course, 'mod', 'nanogong', $nanogong->id);

    return grade_update('mod/nanogong', $nanogong->course, 'mod', 'nanogong', $nanogong->id, 0, $grades, $params);
}

function nanogong_grade_item_delete($nanogong) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/nanogong', $nanogong->course, 'mod', 'nanogong', $nanogong->id, 0, NULL, array('deleted'=>1));
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other nanogong functions go here.  Each of them must have a name that 

?>
