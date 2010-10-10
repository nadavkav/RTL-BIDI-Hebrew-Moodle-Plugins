<?php 
/**
 * Submissions Class
 *
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: submissions.class.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
 
global $CFG; 
require_once("locallib.php");
require_once("lib.php");
require ("$CFG->dirroot/mod/webquestscorm/submission.class.php"); 

class submissions {
    var $cm;
    
    var $course;
    var $context;
    var $wqid;
    var $wqmaxbytes;
    var $emailteachers;
    var $wqname;
    var $wqgrade;
    var $wqtimedue;
    var $wqtimeavailable;
    var $wqpreventlate;
    var $wqresubmit;
   
    function submissions($cmid) {
    
        global $CFG;
        
        if (!$this->cm = get_coursemodule_from_id('webquestscorm', $cmid)) {
            error('Course Module ID was incorrect');
        } 
        if (!$this->course = get_record('course', 'id', $this->cm->course)) {
            error('Course is misconfigured');
        }
        if (!$webquestscorm = get_record('webquestscorm', 'id', $this->cm->instance)) {
            error('Course module is incorrect');
        } else {

            $this->wqid = $webquestscorm->id;
            $this->wqmaxbytes = $webquestscorm->maxbytes;
            $this->emailteachers = $webquestscorm->emailteachers;
            $this->wqname = $webquestscorm->name;
            $this->wqgrade = $webquestscorm->grade;
            $this->wqtimedue = $webquestscorm->timedue;
            $this->wqtimeavailable = $webquestscorm->timeavailable;
            $this->wqpreventlate = $webquestscorm->preventlate;
            $this->wqresubmit = $webquestscorm->resubmit;
	}       

	$this->context = get_context_instance(CONTEXT_MODULE,$this->cm->id);					   
    }

    
    function view_upload_form() {
        global $CFG;
        $struploadafile = get_string("uploadafile");
        $strmaxsize = get_string("maxsize", "", display_size($this->wqmaxbytes));

        echo '<center>';
        echo '<form enctype="multipart/form-data" method="post" '.
             "action=\"$CFG->wwwroot/mod/webquestscorm/upload.php\">";
        echo "<p>$struploadafile ($strmaxsize)</p>";
        echo '<input type="hidden" name="cmid" value="'.$this->cm->id.'" />';
        require_once($CFG->libdir.'/uploadlib.php');
        upload_print_form_fragment(1,array('newfile'),false,null,0,$this->wqmaxbytes,false);
        echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
        echo '</form>';
        echo '</center>';
    }     
    /**
     * Count the files uploaded by a given user
     *
     * @param $userid int The user id
     * @return int
     */
     
    function count_user_files($userid) {
        global $CFG;
        $filearea = $this->file_area_name($userid);
        if ( is_dir($CFG->dataroot.'/'.$filearea) && $basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                return count($files);
            }
        }
        return 0;
    }  		 
    /**
     *
     * @param $userid int The user id
     * @return string path to file area
     */
    function file_area_name($userid) {
        global $CFG;  
        return $this->course->id.'/'.$CFG->moddata.'/webquestscorm/'.$this->cm->id.'/'.$userid;
    }    
    /**
     *
     * @param $userid int The user id
     * @return string path to file area.
     */
    function file_area($userid) {
        $upload_directory = $this->file_area_name($userid);
        return make_upload_directory( $upload_directory);
    }       
    /**
     * Load the submission object for a particular user
     *
     * @param $userid int The id of the user whose submission we want or 0 in which case USER->id is used
     * @param $createnew boolean optional Defaults to false. If set to true a new submission object will be created in the database
     * @return object The submission
     */
    function get_submission($userid=0, $createnew=false) {
        $submissioninstance = new submission($this->wqid, $userid); 
				$submission = $submissioninstance->get_submission($createnew=false);
        return $submission;
    }
    /**
     * Instantiates a new submission object for a given user
     *
     * Sets the task, userid and times, everything else is set to default values.
     * @param $userid int The userid for which we want a submission object
     * @return object The submission
     */
    function prepare_new_submission($userid) {
        $submissioninstance = new submission($this->wqid, $userid);         
        return $submissioninstance->prepare_new_submission();
    }        
    /**
     * Alerts teachers by email of new or changed assignments that need grading
     *
     * First checks whether the option to email teachers is set for this assignment.
     * Sends an email to ALL teachers in the course (or in the group if using separate groups).
     * Uses the methods email_teachers_text() and email_teachers_html() to construct the content.
     * @param $submission object The submission that has changed
     */
    function email_teachers($submission) {
        global $CFG;

        if (empty($this->emailteachers)) {          // No need to do anything
            return;
        }

        $user = get_record('user', 'id', $submission->userid);

        if (groupmode($this->course, $this->cm) == SEPARATEGROUPS) {   // Separate groups are being used
            if ($groups = user_group($this->course->id, $user->id)) {  // Try to find groups
                $teachers = array();
                foreach ($groups as $group) {
                    $teachers = array_merge($teachers, get_group_teachers($this->course->id, $group->id));
                }
            } else {
                $teachers = get_group_teachers($this->course->id, 0);   // Works even if not in group
            }
        } else {
            $teachers = get_course_teachers($this->course->id);
        }

        if ($teachers) {

            $strwebquestscorms = get_string('modulenameplural', 'webquestscorm');
            $strwebquestscorm  = get_string('modulename', 'webquestscorm');
            $strsubmitted  = get_string('submitted', 'webquestscorm');

            foreach ($teachers as $teacher) {
                unset($info);
                $info->username = fullname($user);
                $info->webquestscorm = format_string($this->wqname,true);
                $info->url = $CFG->wwwroot.'/mod/webquestscorm/editsubmissions.php?cmid='.$this->cm->id.'&element=uploadedTasks&subelement=all';

                $postsubject = $strsubmitted.': '.$info->username.' -> '.$this->wqname;
                $posttext = $this->email_teachers_text($this->course->shortname, $this->wqname, $strwebquestscorms, $info);
                $posthtml = ($teacher->mailformat == 1) ? $this->email_teachers_html($this->course->id, $this->course->shortname, $this->wqname, $strwebquestscorms, $this->cm->id, $info) : '';

                @email_to_user($teacher, $user, $postsubject, $posttext, $posthtml);  // If it fails, oh well, too bad.
            }
        }
    }     
    /**
     * Creates the text content for emails to teachers
     *
     * @param $info object The info used by the 'emailteachermail' language string
     * @return string
     */
    function email_teachers_text($strwebquestscorms, $info) {
        $posttext  = $this->course->shortname.' -> '.$strwebquestscorms.' -> '.
                     format_string($this->wqname, true)."\n";
        $posttext .= '---------------------------------------------------------------------'."\n";
        $posttext .= get_string("emailteachermail", "webquestscorm", $info)."\n";
        $posttext .= "\n---------------------------------------------------------------------\n";
        return $posttext;
    }
		/**
     * Creates the html content for emails to teachers
     *
     * @param $info object The info used by the 'emailteachermailhtml' language string
     * @return string
     */
    function email_teachers_html($strwebquestscorms,$info) {
        global $CFG;
        $posthtml  = '<p><font face="sans-serif">'.
                     '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.'">'.$this->course->shortname.'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/webquestscorm/index.php?id='.$this->course->id.'">'.$strwebquestscorms.'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/webquestscorm/view.php?id='.$this->cm->id.'">'.format_string($this->wqname,true).'</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>'.get_string('emailteachermailhtml', 'webquestscorm', $info).'</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }    
    /**
     * Display the feedback to the student
     *
     * This default method prints the teacher picture and name, date when marked,
     * grade and teacher submissioncomment.
     *
     * @param $submission object The submission object or NULL in which case it will be loaded
     */
    function view_feedback($submission=NULL) {
        global $USER;

        if (!$submission) { /// Get submission for this assignment
            $submission = $this->get_submission($USER->id);
        }

        if (empty($submission->timemarked)) {   /// Nothing to show, so print nothing
            return;
        }

    /// We need the teacher info
        if (! $teacher = get_record('user', 'id', $submission->teacher)) {
            error('Could not find the teacher');
        }

    /// Print the feedback
        print_heading(get_string('feedbackfromteacher', 'webquestscorm', $this->course->teacher));

        echo '<table cellspacing="0" class="feedback">';

        echo '<tr>';
        echo '<td class="left picture">';
        print_user_picture($teacher->id, $this->course->id, $teacher->picture);
        echo '</td>';
        echo '<td class="topic">';
        echo '<div class="from">';
        echo '<div class="fullname">'.fullname($teacher).'</div>';
        echo '<div class="time">'.userdate($submission->timemarked).'</div>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="content">';
        if ($this->wqgrade) {
            echo '<div class="grade">';
            echo get_string("grade").': '.$this->display_grade($submission->grade);
            echo '</div>';
            echo '<div class="clearer"></div>';
        }

        echo '<div class="comment">';
        echo format_text($submission->submissioncomment, $submission->format);
        echo '</div>';
        echo '</tr>';

        echo '</table>';
    }    
    /**
     *  Return a grade in user-friendly form, whether it's a scale or not
     *  
     * @param $grade
     * @return string User-friendly representation of grade
     */
    function display_grade($grade) {

        static $scalegrades = array();   // Cache scales for each assignment - they might have different scales!!

        if ($this->wqgrade >= 0) {    // Normal number
            if ($grade == -1) {
                return '-';
            } else {
                return $grade.' / '.$this->wqgrade;
            }

        } else {                                // Scale
            if (empty($scalegrades[$this->wqid])) {
                if ($scale = get_record('scale', 'id', -($this->wqgrade))) {
                    $scalegrades[$this->wqid] = make_menu_from_list($scale->scale);
                } else {
                    return '-';
                }
            }
            if (isset($scalegrades[$this->wqid][$grade])) {
                return $scalegrades[$this->wqid][$grade];
            }
            return '-';
        }
    } 
    /** 
     * Returns a link with info about the state of the submissions
     *
     * This is used by view_header to put this link at the top right of the page.
     * For teachers it gives the number of submitted assignments with a link
     * For students it gives the time of their submission.
     * @return string
     */
    function submittedlink() {
        global $USER;

        $submitted = '';
        if (has_capability('mod/webquestscorm:grade', $this->context)) {

        // if this user can mark and is put in a group
        // then he can only see/mark submission in his own groups
            $currentgroup = get_current_group($this->course->id);
            if (!has_capability('moodle/course:managegroups', $this->context) and (groupmode($this->course, $this->cm) == SEPARATEGROUPS)) {
                $count = $this->count_real_submissions( $currentgroup);  // Only their groups
            } else {
                $count = $this->count_real_submissions();;         // Everyone
            }
            $submitted = '<a href="editsubmissions.php?cmid='.$this->cm->id.'&element=uploadedTasks&subelement=all">'.
                           get_string('viewsubmissions', 'webquestscorm', $count).'</a>';
        } else {
        
            if (!empty($USER->id)) {
                if ($submission = $this->get_submission( $USER->id)) {
                    if ($submission->timemodified) {
                        if ($submission->timemodified <= $this->wqtimedue || empty($this->wqtimedue)) {
                            $submitted = '<span class="early">'.userdate($submission->timemodified).'</span>';
                        } else {
                            $submitted = '<span class="late">'.userdate($submission->timemodified).'</span>';
                        }
                    }
                }
            }
        }

        return $submitted;
    }   
    /**
     * Counts all real submissions by ENROLLED students (not empty ones)
     *
     * @param $groupid int optional If nonzero then count is restricted to this group
     * @return int The number of submissions
     */
    function count_real_submissions( $groupid=0) {
        global $CFG;
        if ($groupid) {     /// How many in a particular group?
            return count_records_sql("SELECT COUNT(DISTINCT g.userid, g.groupid)
                                     FROM {$CFG->prefix}webquestscorm_submissions a,
                                          {$CFG->prefix}groups_members g
                                    WHERE a.webquestscorm = $this->wqid 
                                      AND a.timemodified > 0
                                      AND g.groupid = '$groupid' 
                                      AND a.userid = g.userid ");
        } else {
            // this is all the users with this capability set, in this context or higher
            if ($users = get_users_by_capability($this->context, 'mod/webquestscorm:submit')) {

                foreach ($users as $user) {
                    $array[] = $user->id;
                }

                $userlists = '('.implode(',',$array).')';

                return count_records_sql("SELECT COUNT(*)
                                      FROM {$CFG->prefix}webquestscorm_submissions
                                     WHERE webquestscorm = '$this->wqid' 
                                       AND timemodified > 0
                                       AND userid IN $userlists ");
            } else {       
                return 0; // no users enroled in $this->course
            }
        }
    }	
    /**
     *  Process teacher feedback submission
     *
     * This is called by submissions() when a grading even has taken place.
     * It gets its data from the submitted form.
     * @return object The updated submission object
     */
    function process_feedback() {

        global $USER, $CFG;

        if (!$feedback = data_submitted()) {      // No incoming data?
            return false;
        }

        ///For save and next, we need to know the userid to save, and the userid to go
        ///We use a new hidden field in the form, and set it to -1. If it's set, we use this
        ///as the userid to store
        if ((int)$feedback->saveuserid !== -1){
            $feedback->userid = $feedback->saveuserid;
        }

        if (!empty($feedback->cancel)) {          // User hit cancel button
            return false;
        }

        $submission = $this->get_submission( $feedback->userid, true);  // Get or make one


        $submission->grade      = $feedback->grade;
        $submission->submissioncomment    = $feedback->submissioncomment;
        $submission->format     = $feedback->format;
        $submission->teacher    = $USER->id;
        $submission->mailed     = 0;       // Make sure mail goes out (again, even)
        $submission->timemarked = time();

        unset($submission->data1);  // Don't need to update this.
        unset($submission->data2);  // Don't need to update this.

        if (empty($submission->timemodified)) {   // eg for offline assignments
            $submission->timemodified = time();
        }

        if (! update_record('webquestscorm_submissions', $submission)) {
            return false;
        }else{
		if ($CFG->version > 2007101500){ 
			update_grade_for_webquestscorm($webquestscorm);
		}
	}
        
        add_to_log($this->course->id, 'webquestscorm', 'update grades', 
                   'editsubmissions.php?cmid='.$this->cm->id.'&user='.$feedback->userid.'&element=uploadedTasks&subelement=all', $feedback->userid, $this->cm->id);

        return $submission;

    }   
    /**
    * Helper method updating the listing on the main script from popup using javascript
    *
    * @param $submission object The submission whose data is to be updated on the main page
    */
    function update_main_listing( $submission) {
        global $SESSION;
        
        $output = '';

        $perpage = get_user_preferences('perpage', 10);

        $quickgrade = get_user_preferences('quickgrade', 0);
        
        /// Run some Javascript to try and update the parent page
        $output .= '<script type="text/javascript">'."\n<!--\n";
        if (empty($SESSION->flextable['mod-webquestscorm-submissions']->collapse['submissioncomment'])) {
            if ($quickgrade){
                $output.= 'opener.document.getElementById("submissioncomment['.$submission->userid.']").value="'
                .trim($submission->submissioncomment).'";'."\n";
             } else {
                $output.= 'opener.document.getElementById("com'.$submission->userid.
                '").innerHTML="'.shorten_text(trim(strip_tags($submission->submissioncomment)), 15)."\";\n";
            }
        }

        if (empty($SESSION->flextable['mod-webquestscorm-submissions']->collapse['grade'])) {
            //echo optional_param('menuindex');
            if ($quickgrade){
                $output.= 'opener.document.getElementById("menumenu['.$submission->userid.
                ']").selectedIndex="'.optional_param('menuindex', 0, PARAM_INT).'";'."\n";
            } else {
                $output.= 'opener.document.getElementById("g'.$submission->userid.'").innerHTML="'.
                $this->display_grade( $submission->grade)."\";\n";
            }            
        }    
        //need to add student's assignments in there too.
        if (empty($SESSION->flextable['mod-webquestscorm-submissions']->collapse['timemodified']) &&
            $submission->timemodified) {
            $output.= 'opener.document.getElementById("ts'.$submission->userid.
                 '").innerHTML="'.addslashes($this->print_student_answer( $submission->userid)).userdate($submission->timemodified)."\";\n";
        }
        
        if (empty($SESSION->flextable['mod-webquestscorm-submissions']->collapse['timemarked']) &&
            $submission->timemarked) {
            $output.= 'opener.document.getElementById("tt'.$submission->userid.
                 '").innerHTML="'.userdate($submission->timemarked)."\";\n";
        }
        
        if (empty($SESSION->flextable['mod-webquestscorm-submissions']->collapse['status'])) {
            $output.= 'opener.document.getElementById("up'.$submission->userid.'").className="s1";';
            $buttontext = get_string('update');
            $button = link_to_popup_window ('/mod/webquestscorm/submissions.php?cmid='.$this->cm->id.'&amp;userid='.$submission->userid.'&element=uploadedTasks&amp;subelement=single'.'&amp;offset='.optional_param('offset', '', PARAM_INT),
						          'grade'.$submission->userid, $buttontext, 450, 700, $buttontext, 'none', true, 'button'.$submission->userid);
            $output.= 'opener.document.getElementById("up'.$submission->userid.'").innerHTML="'.addslashes($button).'";';
        }        
        $output .= "\n-->\n</script>";
        return $output;
    }  			 		    
  
    function print_student_answer( $userid, $return=false){
        global $CFG, $USER;

        $filearea = $this->file_area_name( $userid);

        $output = '';
    
        if ($basedir = $this->file_area( $userid)) {
            if ($files = get_directory_list($basedir)) {
                
                foreach ($files as $key => $file) {
                    require_once($CFG->libdir.'/filelib.php');
                    
                    $icon = mimeinfo('icon', $file);
                    
                    if ($CFG->slasharguments) {
                        $ffurl = "$CFG->wwwroot/file.php/$filearea/$file";
                    } else {
                        $ffurl = "$CFG->wwwroot/file.php?file=/$filearea/$file";
                    }
                    //died right here
                    //require_once($ffurl);                
                    $output = '<img align="middle" src="'.$CFG->pixpath.'/f/'.$icon.'" height="16" width="16" alt="'.$icon.'" />'.
                            '<a href="'.$ffurl.'" >'.$file.'</a><br />';
                }
            }
        }

        $output = '<div class="files">'.$output.'</div>';
        return $output;    
    }      
  /**
     *  Display a single submission, ready for grading on a popup window
     *
     * This default method prints the teacher info and submissioncomment box at the top and
     * the student info and submission at the bottom.
     * This method also fetches the necessary data in order to be able to
     * provide a "Next submission" button.
     * Calls preprocess_submission() to give assignment type plug-ins a chance
     * to process submissions before they are graded
     * This method gets its arguments from the page parameters userid and offset
     */		 
    function display_submission( $extra_javascript = '') {

        global $CFG;
        
        $userid = optional_param('userid', PARAM_INT);
        $offset = required_param('offset', PARAM_INT);//offset for where to start looking for student.
        //echo '<p>userid: '.$userid;
        if (!$user = get_record('user', 'id', $userid)) {
            error('No such user!');
        }
  
        if (!$submission = $this->get_submission( $user->id)) {
            $submission = $this->prepare_new_submission( $userid);
        }
 
        if ($submission->timemodified > $submission->timemarked) {
            $subtype = 'webquestscormnew';
        } else {
            $subtype = 'webquestscormold';
        }

    /// Get all teachers and students

        $currentgroup = get_current_group($this->course->id);
        if ($currentgroup) {
            $users = get_group_users($currentgroup);
        } else {
            $users = get_users_by_capability($this->context, 'mod/webquestscorm:submit');
            //$users = get_course_users($this->course->id);
        }

        $select = 'SELECT u.id, u.firstname, u.lastname, u.picture,
                          s.id AS submissionid, s.grade, s.submissioncomment, 
                          s.timemodified, s.timemarked ';
        $sql = 'FROM '.$CFG->prefix.'user u '.
               'LEFT JOIN '.$CFG->prefix.'webquestscorm_submissions s ON u.id = s.userid 
                                                                  AND s.webquestscorm = '.$this->wqid.' '.
               'WHERE u.id IN ('.implode(',', array_keys($users)).') ';
               
        require_once($CFG->libdir.'/tablelib.php');

        if ($sort = flexible_table::get_sql_sort('mod-webquestscorm-submissions')) {
            $sort = 'ORDER BY '.$sort.' ';
        }
        $nextid = 0;
        if (($auser = get_records_sql($select.$sql.$sort, $offset+1, 1)) !== false) {
            $nextuser = array_shift($auser);
        /// Calculate user status
            $nextuser->status = ($nextuser->timemarked > 0) && ($nextuser->timemarked >= $nextuser->timemodified);
            $nextid = $nextuser->id;
        }

        print_header(get_string('feedback', 'webquestscorm').':'.fullname($user, true).':'.format_string($this->wqname));

        /// Print any extra javascript needed for saveandnext
        echo $extra_javascript;

        ///SOme javascript to help with setting up >.>
        
        echo '<script type="text/javascript">'."\n";
        echo 'function setNext(){'."\n";
        echo 'document.submitform.mode.value=\'next\';'."\n";
        echo 'document.submitform.userid.value="'.$nextid.'";'."\n";
        echo '}'."\n";
        
        echo 'function saveNext(){'."\n";
        echo 'document.submitform.mode.value=\'saveandnext\';'."\n";
        echo 'document.submitform.userid.value="'.$nextid.'";'."\n";
        echo 'document.submitform.saveuserid.value="'.$userid.'";'."\n";
        echo 'document.submitform.menuindex.value = document.submitform.grade.selectedIndex;'."\n";
        echo '}'."\n";
            
        echo '</script>'."\n";
        echo '<table cellspacing="0" class="feedback '.$subtype.'" >';

        ///Start of teacher info row

        echo '<tr>';
        echo '<td width="35" valign="top" class="picture teacher">';
        if ($submission->teacher) {
            $teacher = get_record('user', 'id', $submission->teacher);
        } else {
            global $USER;
            $teacher = $USER;
        }   

        print_user_picture($teacher->id, $this->course->id, $teacher->picture);
        echo '</td>';
        echo '<td class="content">';
        echo '<form name="submitform" action="submissions.php?cmid='.$this->cm->id.'" method="post">';
        echo '<input type="hidden" name="offset" value="'.++$offset.'">';
        echo '<input type="hidden" name="userid" value="'.$userid.'" />';
        echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
        echo '<input type="hidden" name="mode" value="grade" />';
        //echo '<input type="hidden" name="tabs" value="required" />';
        echo '<input type="hidden" name="menuindex" value="0" />';//selected menu index

        //new hidden field, initialized to -1.
        echo '<input type="hidden" name="saveuserid" value="-1" />';
        if ($submission->timemarked) {
            echo '<div class="from">';
            echo '<div class="fullname">'.fullname($teacher, true).'</div>';
            echo '<div class="time">'.userdate($submission->timemarked).'</div>';
            echo '</div>';
        }
        echo '<div class="grade">'.get_string('grade').':';
        choose_from_menu(make_grades_menu($this->wqgrade), 'grade', $submission->grade, get_string('nograde'), '', -1);
        echo '</div>';
        echo '<div class="clearer"></div>';

        echo '<br />';
        if ($usehtmleditor = can_use_html_editor()) {
            $defaultformat = FORMAT_HTML;
            $editorfields = '';
        } else {
            $defaultformat = FORMAT_MOODLE;
        }     
        print_textarea($usehtmleditor, 14, 58, 0, 0, 'submissioncomment', $submission->submissioncomment, $this->course->id);

        if ($usehtmleditor) { 
            echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
        } else {
            echo '<div align="right" class="format">';
            choose_from_menu(format_text_menu(), "format", $submission->format, "");
            helpbutton("textformat", get_string("helpformatting"));
            echo '</div>';
        }

        ///Print Buttons in Single View
        echo '<div class="buttons" align="center">';
        echo '<input type="submit" name="submit" value="'.get_string('savechanges').'" onclick = "document.submitform.menuindex.value = document.submitform.grade.selectedIndex" />';
        echo '<input type="submit" name="cancel" value="'.get_string('cancel').'" />';
        //if there are more to be graded.
        if ($nextid) {
            echo '<input type="submit" name="saveandnext" value="'.get_string('saveandnext').'" onclick="saveNext()" />';
            echo '<input type="submit" name="next" value="'.get_string('next').'" onclick="setNext();" />';
        }
        echo '</div>';
        echo '</form>';
/*
        $customfeedback = $this->custom_feedbackform($submission, true);
        if (!empty($customfeedback)) {
            echo $customfeedback; 
        }
*/
        echo '</td></tr>';
        ///End of teacher info row, Start of student info row
        echo '<tr>';
        echo '<td width="35" valign="top" class="picture user">';
        print_user_picture($user->id, $this->course->id, $user->picture);
        echo '</td>';
        echo '<td class="topic">';
        echo '<div class="from">';
        echo '<div class="fullname">'.fullname($user, true).'</div>';
        if ($submission->timemodified) {
            echo '<div class="time">'.userdate($submission->timemodified).
                                     $this->display_lateness( $submission->timemodified, $this->wqtimedue).'</div>';
        }
        echo '</div>';
        $this->print_user_files( $user->id);
	 
        echo '</td>';
        echo '</tr>';
        
        ///End of student info row
        
        echo '</table>';

        if ($usehtmleditor) {
            use_html_editor();
        }
	 
           
    }
    /**
     * Produces a list of links to the files uploaded by a user
     *
     * @param $userid int optional id of the user. If 0 then $USER->id is used.
     * @param $return boolean optional defaults to false. If true the list is returned rather than printed
     * @return string optional
     */
    function print_user_files( $userid=0, $return=false) {

        global $CFG, $USER;
    
        if (!$userid) {
            if (!isloggedin()) {
                return '';
            }
            $userid = $USER->id;
        }
        $filearea = $this->file_area_name( $userid);

        $output = '';
    
        if ($basedir = $this->file_area( $userid)) {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {
                    
                    $icon = mimeinfo('icon', $file);
                    
                    if ($CFG->slasharguments) {
                        $ffurl = "$CFG->wwwroot/file.php/$filearea/$file";
                    } else {
                        $ffurl = "$CFG->wwwroot/file.php?file=/$filearea/$file";
                    }
                    $ffurl = "$CFG->wwwroot/file.php?file=/$filearea/$file";
               
                    $output .= '<img align="middle" src="'.$CFG->pixpath.'/f/'.$icon.'" height="16" width="16" alt="'.$icon.'" />'.
                            '<a href="'.$ffurl.'" >'.$file.'</a><br />';
                }
            }
        }

        $output = '<div class="files">'.$output.'</div>';

        if ($return) {
            return $output;
        }
        echo $output;
    } 
		    


function display_lateness( $timesubmitted, $timedue) {
    if (!$timedue) {
        return '';
    }
    $time = $timedue - $timesubmitted;
    if ($time < 0) {
        $timetext = get_string('late', 'webquestscorm', format_time($time));
        return ' (<span class="late">'.$timetext.'</span>)';
    } else {
        $timetext = get_string('early', 'webquestscorm', format_time($time));
        return ' (<span class="early">'.$timetext.'</span>)';
    }
}  

    /**
     *  Display all the submissions ready for grading
     */
    function display_submissions() {

        global $CFG, $db, $USER;

        /* first we check to see if the form has just been submitted
         * to request user_preference updates
         */

        if (isset($_POST['updatepref'])){
            $perpage = optional_param('perpage', 10, PARAM_INT);
            $perpage = ($perpage <= 0) ? 10 : $perpage ;
            set_user_preference('webquestscorm_perpage', $perpage);
            set_user_preference('webquestscorm_quickgrade', optional_param('quickgrade',0, PARAM_BOOL));
        }

        /* next we get perpage and quickgrade (allow quick grade) params 
         * from database
         */
        $perpage    = get_user_preferences('webquestscorm_perpage', 10);
        $quickgrade = get_user_preferences('webquestscorm_quickgrade', 0);
        
        $teacherattempts = true; /// Temporary measure
        $page    = optional_param('page', 0, PARAM_INT);
        $strsaveallfeedback = get_string('saveallfeedback', 'webquestscorm');

    /// Some shortcuts to make the code read better
        
        
        $tabindex = 1; //tabindex for quick grading tabbing; Not working for dropdowns yet

        add_to_log($this->course->id, 'webquestscorm', 'view submission', 'editsubmissions.php?cmid='.$this->cm->id.'&element=uploadedTasks&subelement=all', $this->wqid, $this->cm->id);
        $strwebquestscorms = get_string('modulenameplural', 'webquestscorm');
        $strwebquestscorm  = get_string('modulename', 'webquestscorm');

    ///Position swapped
        if ($groupmode = groupmode($this->course, $this->cm)) {   // Groups are being used
            $currentgroup = setup_and_print_groups($this->course, $groupmode, 'editsubmissions.php?cmid='.$this->cm->id.'&element=uploadedTasks&subelement=all');
        } else {
            $currentgroup = false;
        }

    /// Get all teachers and students

        if ($currentgroup) {
            $users = get_group_users($currentgroup);
        } else {

            //$users = get_users_by_capability($this->context, 'mod/webquestscorm:submit'); // everyone with this capability set to non-prohibit
	    $users = get_course_students($this->course->id);
        }

        $tablecolumns = array('picture', 'fullname', 'grade', 'submissioncomment', 'timemodified', 'timemarked', 'status');
        $tableheaders = array('', get_string('fullname'), get_string('grade'), get_string('comment', 'webquestscorm'), get_string('lastmodified').' ('.$this->course->student.')', get_string('lastmodified').' ('.$this->course->teacher.')', get_string('status'));
        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-webquestscorm-submissions');
          
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/webquestscorm/editsubmissions.php?cmid='.$this->cm->id.'&element=uploadedTasks&subelement=all&amp;currentgroup='.$currentgroup);
        $table->define_baseurl($CFG->wwwroot.'/mod/webquestscorm/editsubmissions.php?cmid='.$this->cm->id.'&amp;currentgroup='.$currentgroup.'&element=uploadedTasks&subelement=all');
           
        $table->sortable(true, 'lastname');//sorted by lastname by default
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');
        
        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('grade', 'grade');
        $table->column_class('submissioncomment', 'comment');
        $table->column_class('timemodified', 'timemodified');
        $table->column_class('timemarked', 'timemarked');
        $table->column_class('status', 'status');
        
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'submissions');
        $table->set_attribute('width', '90%');
        $table->set_attribute('align', 'center');

        // Start working -- this is necessary as soon as the niceties are over
        $table->setup();
          
    /// Check to see if groups are being used in this webquestscorm

        if (!$teacherattempts) {
            $teachers = get_course_teachers($this->course->id);
            if (!empty($teachers)) {
                $keys = array_keys($teachers);
            }
            foreach ($keys as $key) {
                unset($users[$key]);
            }
        }
        
        if (empty($users)) {
            print_heading(get_string('noattempts','webquestscorm'));
	    print_footer($this->course);
            return true;
        }
  
    /// Construct the SQL

        if ($where = $table->get_sql_where()) {
            $where .= ' AND ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
        }

        $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, 
                          s.id AS submissionid, s.grade, s.submissioncomment, 
                          s.timemodified, s.timemarked ';
        $sql = 'FROM '.$CFG->prefix.'user u '.
               'LEFT JOIN '.$CFG->prefix.'webquestscorm_submissions s ON u.id = s.userid 
                                                                  AND s.webquestscorm = '.$this->wqid.' '.
               'WHERE '.$where.'u.id IN ('.implode(',', array_keys($users)).') ';
    
        $table->pagesize($perpage, count($users));
        
        ///offset used to calculate index of student in that particular query, needed for the pop up to know who's next
        $offset = $page * $perpage;
     
        $strupdate = get_string('update');
        $strgrade  = get_string('grade');
        $grademenu = make_grades_menu($this->wqgrade);
	
        if (($ausers = get_records_sql($select.$sql.$sort, $table->get_page_start(), $table->get_page_size())) !== false) {

            foreach ($ausers as $auser) { 

            /// Calculate user status
                $auser->status = ($auser->timemarked > 0) && ($auser->timemarked >= $auser->timemodified);
                $picture = print_user_picture($auser->id, $this->course->id, $auser->picture, false, true);
                
                if (empty($auser->submissionid)) {
                    $auser->grade = -1; //no submission yet
                }
                    
                if (!empty($auser->submissionid)) {
                ///Prints student answer and student modified date
                ///attach file or print link to student answer, depending on the type of the webquestscorm.
                ///Refer to print_student_answer in inherited classes.     
                    if ($auser->timemodified > 0) {         
                        $studentmodified = '<div id="ts'.$auser->id.'">'.$this->print_student_answer($auser->id).userdate($auser->timemodified).'</div>';
                    } else { 
                        $studentmodified = '<div id="ts'.$auser->id.'">&nbsp;</div>';
                    }
                ///Print grade, dropdown or text
                    if ($auser->timemarked > 0) { 
                        $teachermodified = '<div id="tt'.$auser->id.'">'.userdate($auser->timemarked).'</div>';
                        
                        if ($quickgrade) {
                            $grade = '<div id="g'.$auser->id.'">'.choose_from_menu(make_grades_menu($this->wqgrade), 
                            'menu['.$auser->id.']', $auser->grade, get_string('nograde'),'',-1,true,false,$tabindex++).'</div>';
                        } else {
                            $grade = '<div id="g'.$auser->id.'">'.$this->display_grade($auser->grade).'</div>';
                        }

                    } else {
                        $teachermodified = '<div id="tt'.$auser->id.'">&nbsp;</div>';
                        if ($quickgrade){                    
                            $grade = '<div id="g'.$auser->id.'">'.choose_from_menu(make_grades_menu($this->wqgrade), 
                            'menu['.$auser->id.']', $auser->grade, get_string('nograde'),'',-1,true,false,$tabindex++).'</div>';
                        } else {
                            $grade = '<div id="g'.$auser->id.'">'.$this->display_grade($auser->grade).'</div>';
                        }
                    }
                ///Print Comment
                    if ($quickgrade){
                        $comment = '<div id="com'.$auser->id.'"><textarea tabindex="'.$tabindex++.'" name="submissioncomment['.$auser->id.']" id="submissioncomment['.$auser->id.']">'.($auser->submissioncomment).'</textarea></div>';
                    } else {
                        $comment = '<div id="com'.$auser->id.'">'.shorten_text(strip_tags($auser->submissioncomment),15).'</div>';
                    }
                } else { 
                    $studentmodified = '<div id="ts'.$auser->id.'">&nbsp;</div>';
                    $teachermodified = '<div id="tt'.$auser->id.'">&nbsp;</div>';
                    $status          = '<div id="st'.$auser->id.'">&nbsp;</div>';
                    if ($quickgrade){   // allow editing
                        $grade = '<div id="g'.$auser->id.'">'.choose_from_menu(make_grades_menu($this->wqgrade), 
                                 'menu['.$auser->id.']', $auser->grade, get_string('nograde'),'',-1,true,false,$tabindex++).'</div>';
                    } else {
                        $grade = '<div id="g'.$auser->id.'">-</div>';
                    }
                    if ($quickgrade){
                        $comment = '<div id="com'.$auser->id.'"><textarea tabindex="'.$tabindex++.'" name="submissioncomment['.$auser->id.']" id="submissioncomment['.$auser->id.']">'.($auser->submissioncomment).'</textarea></div>';
                    } else {
                        $comment = '<div id="com'.$auser->id.'">&nbsp;</div>';
                    }
                }

                if (empty($auser->status)) { /// Confirm we have exclusively 0 or 1
                    $auser->status = 0;
                } else {
                    $auser->status = 1;
                }

                $buttontext = ($auser->status == 1) ? $strupdate : $strgrade;
       
                ///No more buttons, we use popups ;-).

                $button = link_to_popup_window ('/mod/webquestscorm/submissions.php?cmid='.$this->cm->id.'&element=uploadedTasks&amp;userid='.$auser->id.'&amp;subelement=single'.'&amp;offset='.$offset++, 
                                                'grade'.$auser->id, $buttontext, 500, 780, $buttontext, 'none', true, 'button'.$auser->id);

                $status  = '<div id="up'.$auser->id.'" class="s'.$auser->status.'">'.$button.'</div>';
                 
                $row = array($picture, fullname($auser), $grade, $comment, $studentmodified, $teachermodified, $status);
                 
                $table->add_data($row); 
	
            }
	
        }
	

        /// Print quickgrade form around the table

        if ($quickgrade){ 
            echo '<form action="submissions.php?cmid='.$this->cm->id.'" name="fastg" method="post">';
            echo '<input type="hidden" name="id" value="'.$this->cm->id.'">';
            echo '<input type="hidden" name="mode" value="fastgrade">';
            echo '<input type="hidden" name="page" value="'.$page.'">';
            echo '<input type="hidden" name="tabs" value="required" />';
            echo '<p align="center"><input type="submit" name="fastg" value="'.get_string('saveallfeedback', 'webquestscorm').'" /></p>';
        }      

        $table->print_html();  /// Print the whole table

        if ($quickgrade){
            echo '<p align="center"><input type="submit" name="fastg" value="'.get_string('saveallfeedback', 'webquestscorm').'" /></p>';
            echo '</form>';
        }
        /// End of fast grading form
        
        /// Mini form for setting user preference
        echo '<br />';
        echo '<form name="options" action="submissions.php?cmid='.$this->cm->id.'" method="post">';
        echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
        echo '<input type="hidden" name="tabs" value="required" />';
        echo '<table id="optiontable" align="center">';
        echo '<tr align="right"><td>';
        echo '<label for="perpage">'.get_string('pagesize','webquestscorm').'</label>';
        echo ':</td>';
        echo '<td align="left">';
        echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
        helpbutton('pagesize', get_string('pagesize','webquestscorm'), 'webquestscorm');
        echo '</td></tr>';
        echo '<tr align="right">';
        echo '<td>';
        print_string('quickgrade','webquestscorm');
        echo ':</td>';
        echo '<td align="left">';
        if ($quickgrade){
            echo '<input type="checkbox" name="quickgrade" value="1" checked="checked" />';
        } else {
            echo '<input type="checkbox" name="quickgrade" value="1" />';
        }
        helpbutton('quickgrade', get_string('quickgrade', 'webquestscorm'), 'webquestscorm').'</p></div>';
        echo '</td></tr>';
        echo '<tr>';
        echo '<td colspan="2" align="right">';
        echo '<input type="submit" value="'.get_string('savepreferences').'" />';
        echo '</td></tr></table>';
        echo '</form>';
        ///End of mini form
        print_footer($this->course);
    }
 
    function view_dates() {
        if (!$this->wqtimeavailable && !$this->wqtimedue) {
            return;
        }

        print_simple_box_start('center', '', '', '', 'generalbox', 'dates');
        echo '<table>';
        if ($this->wqtimeavailable) {
            echo '<tr><td>'.get_string('availabledate','webquestscorm').':</td>';
            echo '    <td>'.userdate($this->wqtimeavailable).'</td></tr>';
        }
        if ($this->wqtimedue) {
            echo '<tr><td>'.get_string('duedate','webquestscorm').':</td>';
            echo '    <td>'.userdate($this->wqtimedue).'</td></tr>';
        }
        echo '</table>';
        print_simple_box_end();
    }     

    function isopen() {
        $time = time();
        if ($this->wqpreventlate && $this->wqtimedue) {
            return ($this->wqtimeavailable <= $time && $time <= $this->wqtimedue);
        } else {
            return ($this->wqtimeavailable <= $time);
        }
    }	  
}
?>
