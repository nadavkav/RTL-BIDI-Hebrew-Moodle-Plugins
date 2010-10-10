<?php    
/**
 * Submission Class
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: submission.class.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
require_once("../../config.php");
 require_once("lib.php");
class submission {

    var $webquestscorm;

    var $userid;
    var $timecreated;
    var $timemodified;
    var $numfiles;
    var $data1;
    var $data2;
    var $grade;
    var $submissioncomment;
    var $format;
    var $teacher;
    var $timemarked;
    var $mailed;
    
		function submission($wqid, $userid) {
        global $USER;

        if (empty($userid)) {
            $this->userid = $USER->id;
        }	else {
            $this->userid = $userid;
        }
        $this->webquestscorm = $wqid; 
     
        return $this;
    } 

    /**
     * Load the submission object for a particular user
     *
     * @param $userid int The id of the user whose submission we want or 0 in which case USER->id is used
     * @param $createnew boolean optional Defaults to false. If set to true a new submission object will be created in the database
     * @return object The submission
     */
    function get_submission($createnew=false) {

        $submission = get_record('webquestscorm_submissions', 'webquestscorm', $this->webquestscorm, 'userid', $this->userid);


        if ($submission || !$createnew) {
            return $submission;
        }
        $newsubmission = $this->prepare_new_submission();
        if (!insert_record("webquestscorm_submissions", $newsubmission)) {
            error("Could not insert a new empty submission");
        }else{
	   if ($CFG->version > 2007101500){ 
		update_grade_for_webquestscorm($webquestscorm);
	   }else{
	   	error($CFG->version);
		
	   }

	}


        return get_record('webquestscorm_submissions', 'webquestscorm', $this->webquestscorm, 'userid', $this->userid);
    }
    /**
     * Instantiates a new submission object for a given user
     *
     * Sets the task, userid and times, everything else is set to default values.
     * @param $userid int The userid for which we want a submission object
     * @return object The submission
     */
    function prepare_new_submission() {
        
				$this->timecreated  = time();
        $this->timemodified = $this->timecreated;
        $this->numfiles     = 0;
        $this->data1        = '';
        $this->data2        = '';
        $this->grade        = -1;
        $this->submissioncomment      = '';
        $this->format       = 0;
        $this->teacher      = 0;
        $this->timemarked   = 0;
        $this->mailed       = 0;
				        
        return $this;
    } 
}    
?>    
