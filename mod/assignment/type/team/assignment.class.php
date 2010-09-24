<?php // $Id: assignment.class.php,v 1.32.2.15 2008/10/09 11:22:14 poltawski Exp $
require_once($CFG->libdir.'/formslib.php');

define('ASSIGNMENT_STATUS_SUBMITTED', 'submitted'); // student thinks it is finished
define('ASSIGNMENT_STATUS_CLOSED', 'closed');       // teacher prevents more submissions

/**
 * Extend the base assignment class for assignments where you upload a single file
 *
 */
class assignment_team extends assignment_base {

    function assignment_team($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'typeteam';
    }

    function view() {
        session_start();
        global $USER;

        require_capability('mod/assignment:view', $this->context);


        add_to_log($this->course->id, 'assignment', 'view', "view.php?id={$this->cm->id}", $this->assignment->id, $this->cm->id);

        $this->view_header();

        if ($this->assignment->timeavailable > time()
        and !has_capability('mod/assignment:grade', $this->context)      // grading user can see it anytime
        and $this->assignment->var3) {                                   // force hiding before available date
            print_simple_box_start('center', '', '', 0, 'generalbox', 'intro');
            print_string('notavailableyet', 'assignment');
            print_simple_box_end();
        } else {
            error_log('go to view intro');
            $this->view_intro();
        }

        $this->view_dates();

        if (has_capability('mod/assignment:submit', $this->context)) {
             
            $this->view_feedback();
            //1.check if user can join team
            if (isset($_POST['act_jointeam'])
            && isset($_POST['groups'])
            && count($_POST['groups'])==1
            && (!isset($_SESSION['jointeamtime']) || $_SESSION['jointeamtime']!= $_POST['jointeamtime']) ) {
                error_log('start join team');
                error_log('jointeamtime in session: '.$_SESSION['jointeamtime']);
                error_log('jointeamtime in post: '.$_SESSION['jointeamtime']);
                $this->join_team($USER->id, $_POST['groups'][0]);
                $_SESSION['jointeamtime'] = $_POST['jointeamtime'];
            }

            //2.check if user can remove a member from a team
            if (isset($_POST['act_removemember'])) {
                $members = $_POST['members'];
                $teamid = $_POST['teamid'];
                error_log('start remove members');
                error_log('removetime in session: '.$_SESSION['removetime']);
                error_log('removetime in post: '.$_POST['removetime']);
                if (isset($members)
                && is_array($members)
                && count($members)>0
                && $this->is_member($teamid)
                && (!isset($_SESSION['removetime']) || $_SESSION['removetime']!= $_POST['removetime'])
                //use session control to avoid users processing this action by refresh browser.
                ) {
                    error_log('pass test for removing members ');
                    $_SESSION['removetime'] = $_POST['removetime'];
                    foreach ($members as $member) {
                        error_log('teamid: '.$teamid);
                        error_log('member id:'.$member);
                        $this ->remove_user_from_team($member, $teamid);
                    }
                }
            }

            //3.check if user delete a team
            if (isset($_POST['act_deleteteam'])
            && isset($_POST['teamid'])
            && (!isset($_SESSION['deleteteamtime']) || $_SESSION['deleteteamtime']!= $_POST['deleteteamtime'] )) {
                //use session control to avoid users processing this action by refresh browser.
                error_log('start delete team');
                error_log('deleteteamtime in session: '.$_SESSION['deleteteamtime']);
                error_log('deleteteamtime in post: '.$_POST['deleteteamtime']);
                $this->delete_team($_POST['teamid']);
                $_SESSION['deleteteamtime'] = $_POST['deleteteamtime'];
            }

            //4. check if user can open or close team
            if (isset($_POST['act_opencloseteam'])
            && isset($_POST['teamid'])
            && (!isset($_SESSION['openclosetime']) || $_SESSION['openclosetime']!= $_POST['openclosetime'])
            ) {
                error_log('start open or close team');
                error_log('openclosetime in session: '.$_SESSION['openclosetime']);
                error_log('openclosetime in post: '.$_POST['openclosetime']);
                //use session control to avoid users processing this action by refresh browser.
                $this->open_close_team($_POST['teamid']);
                $_SESSION['openclosetime'] = $_POST['openclosetime'];

            }

            //5. check if user can create a team
            if (isset($_POST['act_createteam'])
            && (!isset($_SESSION['createteamtime']) || $_SESSION['createteamtime']!= $_POST['createteamtime'])) {
                error_log('start create team');
                error_log('createteamtime in session: '.$_SESSION['createteamtime']);
                error_log('createteamtime in post: '.$_POST['createteamtime']);
                $this->create_team($_POST['teamname']);
                $_SESSION['createteamtime'] = $_POST['createteamtime'];
            }

            //6. check if user belongs to a team for this assignment.
            $team = $this->get_user_team($USER->id);
            if ($team) {
                error_log('print admin page');
                error_log('team id: '. $team->id);
                $filecount = $this->count_user_files($USER->id);
                error_log('filecount:'.$filecount);
                $submission = $this->get_submission($USER->id);
                error_log("User belongs to a team");
                $this->print_team_admin($team, $filecount, $submission);
                $this->view_final_submission($team->id);
            } else {
                // Allow the user to join an existing team or create and join a new team
                error_log("User is not yet in a team go to print team list UI");
                $this->print_team_list();
            }
        }
        $this->view_footer();
    }


    function view_feedback($submission=NULL) {
        global $USER, $CFG;
        require_once($CFG->libdir.'/gradelib.php');

        if (!$submission) { /// Get submission for this assignment
            $submission = $this->get_submission($USER->id);
        }

        if (empty($submission->timemarked)) {   /// Nothing to show, so print nothing
            if ($this->count_responsefiles($USER->id)) {
                print_heading(get_string('responsefiles', 'assignment', $this->course->teacher), '', 3);
                $responsefiles = $this->print_responsefiles($USER->id, true);
                print_simple_box($responsefiles, 'center');
            }
            return;
        }

        $grading_info = grade_get_grades($this->course->id, 'mod', 'assignment', $this->assignment->id, $USER->id);
        $item = $grading_info->items[0];
        $grade = $item->grades[$USER->id];

        if ($grade->hidden or $grade->grade === false) { // hidden or error
            return;
        }

        if ($grade->grade === null and empty($grade->str_feedback)) {   /// Nothing to show yet
            return;
        }

        $graded_date = $grade->dategraded;
        $graded_by   = $grade->usermodified;

        /// We need the teacher info
        if (!$teacher = get_record('user', 'id', $graded_by)) {
            error('Could not find the teacher');
        }

        /// Print the feedback
        print_heading(get_string('submissionfeedback', 'assignment'), '', 3);

        echo '<table cellspacing="0" class="feedback">';

        echo '<tr>';
        echo '<td class="left picture">';
        print_user_picture($teacher, $this->course->id, $teacher->picture);
        echo '</td>';
        echo '<td class="topic">';
        echo '<div class="from">';
        echo '<div class="fullname">'.fullname($teacher).'</div>';
        echo '<div class="time">'.userdate($graded_date).'</div>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="content">';
        if ($this->assignment->grade) {
            echo '<div class="grade">';
            echo get_string("grade").': '.$grade->str_long_grade;
            echo '</div>';
            echo '<div class="clearer"></div>';
        }

        echo '<div class="comment">';
        echo $grade->str_feedback;
        echo '</div>';
        echo '</tr>';

        echo '<tr>';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="content">';
        echo $this->print_responsefiles($USER->id, true);
        echo '</tr>';

        echo '</table>';
    }

    function print_team_admin($team, $filecount, $submission) {
        global $CFG, $PHP_SELF, $USER;
        // display the team and the file submission box
        echo '<table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">'."\n";

        //submission file and managment button row
        echo '<tr>';
        //print team name and files
        echo '<td>';
        $teamheading = $team ->name." ".$this -> get_team_status_name($team->membershipopen);
        print_heading($teamheading, '', 3);
        if (!$this->drafts_tracked() or !$this->isopen() or $this->is_finalized($submission)) {
            print_heading(get_string('submission', 'assignment'), '', 9);
        } else {
            print_heading(get_string('submissiondraft', 'assignment'), '', 9);
        }

        if ($filecount and $submission) {
            error_log('start print team files');
            print_simple_box($this->print_user_files($USER->id, true, $team->id), 'center');
        } else {
            if (!$this->isopen() or $this->is_finalized($submission)) {
                print_simple_box(get_string('nofiles', 'assignment'), 'center');
            } else {
                print_simple_box(get_string('nofilesyet', 'assignment'), 'center');
            }
        }
        echo '</td>';
         
        echo '<td>';
        echo '<form id="controlform" action="'.$PHP_SELF.'" method="post">';
        echo '<div align ="right" >';
        echo '<input type="hidden" name="teamid" value="'.$team->id.'" />';
        echo '<input type="hidden" name="openclosetime" value="'.time().'" />';
        echo '<input type="hidden" name="deleteteamtime" value="'.time().'" />';
        //may use for next release
        //echo '<input type ="submit" name ="act_editteam" style = "height:25px; width:100px" value="'.get_string('editteam','assignment_team').'"/><br/>';
        echo '<input type ="submit" name ="act_deleteteam" style = "height:25px; width:100px" value="'.get_string('deleteteam','assignment_team').'"/><br/>';
        echo '<input type ="submit" name ="act_opencloseteam" style = "height:25px; width:100px" value="'.get_string('openclosemembership','assignment_team').'"/><br/>';
        echo '</div>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
        //submission file and managment button row end

        //team member row and submission file start
        echo '<tr>';
        //print team members
        echo '<td>';
        echo '<p><label for="teammember"><span id="teammemberlabel">'.
        get_string('teammember', 'assignment_team').' </span></label></p>'."\n";
        echo '<form id="removememberform" action="'.$PHP_SELF.'" method="post">';
        echo '<select name ="members[]" multiple="multiple" id="teammember" size="15">';
        $members = get_records_sql("SELECT id, student, team".
                                 " FROM {$CFG->prefix}team_student ".
                                 " WHERE team = ".$team->id);
        if (isset($members) && count($members)>0) {
            foreach ($members as $member) {
                $userid = $member->student;
                $user = get_record ('user', 'id', $userid);
                echo "<option value=\"{$user->id}\" >".$user->firstname." ".$user->lastname."</option>";
            }
        } else {
            //print empty list
            echo '<option>&nbsp;</option>';
        }
        echo '</select><br/>';
        echo '<input type="hidden" name="teamid" value="'.$team->id.'" />';
        echo '<input type="hidden" name="removetime" value="'.time().'" />';
        echo '<input type ="submit" name ="act_removemember" value ="'.get_string('removeteammember','assignment_team').'"  >';
        echo '</form>';
        echo '</td>';
        echo '<td>';
        $this->view_upload_form($team->id);
        echo '</td>';
        echo '</tr>';
        //team row end
        echo '</table>';



    }

    function view_upload_form($teamid) {
        global $CFG, $USER;

        $submission = $this->get_submission($USER->id);

        $struploadafile = get_string('teamsubmission', 'assignment_team');
        $maxbytes = $this->assignment->maxbytes == 0 ? $this->course->maxbytes : $this->assignment->maxbytes;
        $strmaxsize = get_string('maxsize', '', display_size($maxbytes));

        if ($this->is_finalized($submission)) {
            // no uploading
            return;
        }

        if ($this->can_upload_file($submission, $teamid)) {
            echo '<div style="text-align:center">';
            echo '<form enctype="multipart/form-data" method="post" action="upload.php">';
            echo '<fieldset class="invisiblefieldset">';
            echo "<p>$struploadafile ($strmaxsize)</p>";
            echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
            echo '<input type="hidden" name="action" value="uploadfile" />';
            echo '<input type="hidden" name="teamid" value="'.$teamid.'" />';
            require_once($CFG->libdir.'/uploadlib.php');
            upload_print_form_fragment(1,array('newfile'),null,false,null,0,$this->assignment->maxbytes,false);
            echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
            echo '</fieldset>';
            echo '</form>';
            echo '</div>';
            echo '<br />';
        }

    }

    function view_notes() {
        global $USER;

        if ($submission = $this->get_submission($USER->id)
        and !empty($submission->data1)) {
            print_simple_box(format_text($submission->data1, FORMAT_HTML), 'center', '630px');
        } else {
            print_simple_box(get_string('notesempty', 'assignment'), 'center');
        }
        if ($this->can_update_notes($submission)) {
            $options = array ('id'=>$this->cm->id, 'action'=>'editnotes');
            echo '<div style="text-align:center">';
            print_single_button('upload.php', $options, get_string('edit'), 'post', '_self', false);
            echo '</div>';
        }
    }

    function view_final_submission($teamid) {
        global $CFG, $USER;

        $submission = $this->get_submission($USER->id);

        if ($this->isopen() and $this->can_finalize($submission)) {
            //print final submit button
            print_heading(get_string('submitformarking','assignment'), '', 3);
            echo '<div style="text-align:center">';
            echo '<form method="post" action="upload.php">';
            echo '<fieldset class="invisiblefieldset">';
            echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
            echo '<input type="hidden" name="action" value="finalize" />';
            echo '<input type="hidden" name="teamid" value="'.$teamid.'" />';
            echo '<input type="submit" name="formarking" value="'.get_string('sendformarking', 'assignment').'" />';
            echo '</fieldset>';
            echo '</form>';
            echo '</div>';
        } else if (!$this->isopen()) {
            print_heading(get_string('nomoresubmissions','assignment'), '', 3);

        } else if ($this->drafts_tracked() and $state = $this->is_finalized($submission)) {
            if ($state == ASSIGNMENT_STATUS_SUBMITTED) {
                print_heading(get_string('submitedformarking','assignment'), '', 3);
            } else {
                print_heading(get_string('nomoresubmissions','assignment'), '', 3);
            }
        } else {
            //no submission yet
        }
    }


    /**
     * Return true if var3 == hide description till available day
     *
     *@return boolean
     */
    function description_is_hidden() {
        return ($this->assignment->var3 && (time() <= $this->assignment->timeavailable));
    }

    function custom_feedbackform($submission, $return=false) {
        global $CFG;

        $mode         = optional_param('mode', '', PARAM_ALPHA);
        $offset       = optional_param('offset', 0, PARAM_INT);
        $forcerefresh = optional_param('forcerefresh', 0, PARAM_BOOL);

        $output = get_string('responsefiles', 'assignment').': ';

        $output .= '<form enctype="multipart/form-data" method="post" '.
             "action=\"$CFG->wwwroot/mod/assignment/upload.php\">";
        $output .= '<div>';
        $output .= '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
        $output .= '<input type="hidden" name="action" value="uploadresponse" />';
        $output .= '<input type="hidden" name="mode" value="'.$mode.'" />';
        $output .= '<input type="hidden" name="offset" value="'.$offset.'" />';
        $output .= '<input type="hidden" name="userid" value="'.$submission->userid.'" />';
        require_once($CFG->libdir.'/uploadlib.php');
        $output .= upload_print_form_fragment(1,array('newfile'),null,false,null,0,0,true);
        $output .= '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
        $output .= '</div>';
        $output .= '</form>';

        if ($forcerefresh) {
            $output .= $this->update_main_listing($submission);
        }

        $responsefiles = $this->print_responsefiles($submission->userid, true);
        if (!empty($responsefiles)) {
            $output .= $responsefiles;
        }

        if ($return) {
            return $output;
        }
        echo $output;
        return;
    }


    function print_student_answer($userid, $return=false){
        global $CFG;

        $filearea = $this->file_area_name($userid);
        $submission = $this->get_submission($userid);

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($this->drafts_tracked() and $this->isopen() and !$this->is_finalized($submission)) {
                $output .= '<strong>'.get_string('draft', 'assignment').':</strong> ';
            }

            if ($this->notes_allowed() and !empty($submission->data1)) {
                $output .= link_to_popup_window ('/mod/assignment/type/upload/notes.php?id='.$this->cm->id.'&amp;userid='.$userid,
                                                'notes'.$userid, get_string('notes', 'assignment'), 500, 780, get_string('notes', 'assignment'), 'none', true, 'notesbutton'.$userid);
                $output .= '&nbsp;';
            }

            if ($files = get_directory_list($basedir, 'responses')) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {
                    $icon = mimeinfo('icon', $file);
                    $ffurl = get_file_url("$filearea/$file");
                    $output .= '<a href="'.$ffurl.'" ><img class="icon" src="'.$CFG->pixpath.'/f/'.$icon.'" alt="'.$icon.'" />'.$file.'</a>&nbsp;';
                }
            }
        }
        $output = '<div class="files">'.$output.'</div>';
        $output .= '<br />';

        return $output;
    }


    /**
     * Produces a list of links to the files uploaded by a user
     *
     * @param $userid int optional id of the user. If 0 then $USER->id is used.
     * @param $return boolean optional defaults to false. If true the list is returned rather than printed
     * @return string optional
     */
    function print_user_files($userid=0, $return=false, $teamid) {
        global $CFG, $USER;
        error_log('test print upload file');
        $mode    = optional_param('mode', '', PARAM_ALPHA);
        $offset  = optional_param('offset', 0, PARAM_INT);

        if (!$userid) {
            if (!isloggedin()) {
                return '';
            }
            $userid = $USER->id;
        }

        error_log('team id'.$teamid);
        $filearea = $this->file_area_name($userid);
        error_log('file dir:'.$filearea);
        $output = '';

        $submission = $this->get_submission($userid);

        $candelete = $this->can_delete_files($submission, $teamid);
        error_log('can delete: '.$candelete);
        $strdelete   = get_string('delete');

        if ($this->drafts_tracked() and $this->isopen() and !$this->is_finalized($submission) and !empty($mode)) {                 // only during grading
            $output .= '<strong>'.get_string('draft', 'assignment').':</strong><br />';
        }

        if ($this->notes_allowed() and !empty($submission->data1) and !empty($mode)) { // only during grading

            $npurl = $CFG->wwwroot."/mod/assignment/type/upload/notes.php?id={$this->cm->id}&amp;userid=$userid&amp;offset=$offset&amp;mode=single";
            $output .= '<a href="'.$npurl.'">'.get_string('notes', 'assignment').'</a><br />';

        }

        if ($basedir = $this->file_area($userid)) {
            error_log('base dir: '.$basedir);
            if ($files = get_directory_list($basedir, 'responses')) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {
                    error_log('file: '. $file);
                    $icon = mimeinfo('icon', $file);
                     
                    $ffurl = get_file_url("$filearea/$file");
                    error_log('file url:'.$ffurl);

                    $output .= '<a href="'.$ffurl.'" ><img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.$file.'</a>';

                    if ($candelete) {
                        $delurl  = "$CFG->wwwroot/mod/assignment/delete.php?id={$this->cm->id}&amp;file=$file&amp;userid={$submission->userid}&amp;mode=$mode&amp;offset=$offset&amp;teamid=$teamid";

                        $output .= '<a href="'.$delurl.'">&nbsp;'
                        .'<img title="'.$strdelete.'" src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="" /></a> ';
                    }

                    $output .= '<br />';
                }
            }
        }

        if ($this->drafts_tracked() and $this->isopen() and has_capability('mod/assignment:grade', $this->context) and $mode != '') { // we do not want it on view.php page
            if ($this->can_unfinalize($submission)) {
                $options = array ('id'=>$this->cm->id, 'userid'=>$userid, 'action'=>'unfinalize', 'mode'=>$mode, 'offset'=>$offset);
                $output .= print_single_button('upload.php', $options, get_string('unfinalize', 'assignment'), 'post', '_self', true);
            } else if ($this->can_finalize($submission)) {
                $options = array ('id'=>$this->cm->id, 'userid'=>$userid, 'action'=>'finalizeclose', 'mode'=>$mode, 'offset'=>$offset, 'teamid' =>$teamid);
                $output .= print_single_button('upload.php', $options, get_string('finalize', 'assignment'), 'post', '_self', true);
            }
        }

        $output = '<div class="files">'.$output.'</div>';

        if ($return) {
            return $output;
        }
        echo $output;
    }

    function print_responsefiles($userid, $return=false) {
        global $CFG, $USER;

        $mode    = optional_param('mode', '', PARAM_ALPHA);
        $offset  = optional_param('offset', 0, PARAM_INT);

        $filearea = $this->file_area_name($userid).'/responses';

        $output = '';

        $candelete = $this->can_manage_responsefiles();
        $strdelete   = get_string('delete');

        if ($basedir = $this->file_area($userid)) {
            $basedir .= '/responses';

            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {

                    $icon = mimeinfo('icon', $file);

                    $ffurl = get_file_url("$filearea/$file");

                    $output .= '<a href="'.$ffurl.'" ><img src="'.$CFG->pixpath.'/f/'.$icon.'" alt="'.$icon.'" />'.$file.'</a>';

                    if ($candelete) {
                        $delurl  = "$CFG->wwwroot/mod/assignment/delete.php?id={$this->cm->id}&amp;file=$file&amp;userid=$userid&amp;mode=$mode&amp;offset=$offset&amp;action=response";

                        $output .= '<a href="'.$delurl.'">&nbsp;'
                        .'<img title="'.$strdelete.'" src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt=""/></a> ';
                    }

                    $output .= '&nbsp;';
                }
            }


            $output = '<div class="responsefiles">'.$output.'</div>';

        }

        if ($return) {
            return $output;
        }
        echo $output;
    }


    function upload() {
        $action = required_param('action', PARAM_ALPHA);

        switch ($action) {
            case 'finalize':
                $this->finalize();
                break;
            case 'finalizeclose':
                $this->finalizeclose();
                break;
            case 'unfinalize':
                $this->unfinalize();
                break;
            case 'uploadresponse':
                $this->upload_responsefile();
                break;
            case 'uploadfile':
                $this->upload_file();
            case 'savenotes':
            case 'editnotes':
                $this->upload_notes();
            default:
                error('Error: Unknow upload action ('.$action.').');
        }
    }

    function upload_notes() {
        global $CFG, $USER;

        $action = required_param('action', PARAM_ALPHA);

        $returnurl = 'view.php?id='.$this->cm->id;

        $mform = new mod_assignment_team_notes_form();

        $defaults = new object();
        $defaults->id = $this->cm->id;

        if ($submission = $this->get_submission($USER->id)) {
            $defaults->text = $submission->data1;
        } else {
            $defaults->text = '';
        }

        $mform->set_data($defaults);

        if ($mform->is_cancelled()) {
            redirect('view.php?id='.$this->cm->id);
        }

        if (!$this->can_update_notes($submission)) {
            $this->view_header(get_string('upload'));
            notify(get_string('uploaderror', 'assignment'));
            print_continue($returnurl);
            $this->view_footer();
            die;
        }

        if ($data = $mform->get_data() and $action == 'savenotes') {
            $submission = $this->get_submission($USER->id, true); // get or create submission
            $updated = new object();
            $updated->id           = $submission->id;
            $updated->timemodified = time();
            $updated->data1        = $data->text;

            if (update_record('assignment_submissions', $updated)) {
                add_to_log($this->course->id, 'assignment', 'upload', 'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                redirect($returnurl);
                $submission = $this->get_submission($USER->id);
                $this->update_grade($submission);

            } else {
                $this->view_header(get_string('notes', 'assignment'));
                notify(get_string('notesupdateerror', 'assignment'));
                print_continue($returnurl);
                $this->view_footer();
                die;
            }
        }

        /// show notes edit form
        $this->view_header(get_string('notes', 'assignment'));

        print_heading(get_string('notes', 'assignment'), '');

        $mform->display();

        $this->view_footer();
        die;
    }

    function upload_responsefile() {
        global $CFG;
        error_log('start upload response file');
        $userid = required_param('userid', PARAM_INT);
        $mode   = required_param('mode', PARAM_ALPHA);
        $offset = required_param('offset', PARAM_INT);

        $returnurl = "submissions.php?id={$this->cm->id}&amp;userid=$userid&amp;mode=$mode&amp;offset=$offset";

        if (data_submitted('nomatch') and $this->can_manage_responsefiles()) {
            $dir = $this->file_area_name($userid).'/responses';
            check_dir_exists($CFG->dataroot.'/'.$dir, true, true);

            require_once($CFG->dirroot.'/lib/uploadlib.php');
            $um = new upload_manager('newfile',false,true,$this->course,false,0,true);

            if (!$um->process_file_uploads($dir)) {
                print_header(get_string('upload'));
                notify(get_string('uploaderror', 'assignment'));
                echo $um->get_errors();
                print_continue($returnurl);
                print_footer('none');
                die;
            }
        }
        redirect($returnurl);
    }

    /**
     * Team members should have same files in their  folder.
     */
    function upload_file() {
        global $CFG, $USER;
        error_log('upload_file method');
        $mode   = optional_param('mode', '', PARAM_ALPHA);
        $offset = optional_param('offset', 0, PARAM_INT);
        $teamid = optional_param('teamid','',PARAM_INT);

        $returnurl = 'view.php?id='.$this->cm->id;

        $filecount = $this->count_user_files($USER->id);
        $submission = $this->get_submission($USER->id);
        if (!$this->can_upload_file($submission, $teamid)) {
            $this->view_header(get_string('upload'));
            notify(get_string('uploaderror', 'assignment'));
            print_continue($returnurl);
            $this->view_footer();
            die;
        }

        //team can not be empty
        $members = $this->get_members_from_team($teamid);
        if ($members && is_array($members)) {
            require_once($CFG->dirroot.'/lib/uploadlib.php');
            $currenttime = time();
            $um = new upload_manager('newfile',false,true,$this->course,false,$this->assignment->maxbytes,true);
            $dir = $this->file_area_name($USER->id);
            check_dir_exists($CFG->dataroot.'/'.$dir, true, true);
            error_log('source dir :'.$dir);
            if ($um -> process_file_uploads($dir)) {
                //copy this new file  to other members dir
                //update members' assignment_submission records.
                $file = $um->get_new_filename();
                foreach ($members as $member) {
                    //save this file in other team members' file dir.
                    if ($member->student != $USER->id) { //not process the file folder for itself
                        $memberdir = $this -> file_area_name($member->student);
                        check_dir_exists($CFG->dataroot.'/'.$memberdir, true, true);
                        error_log('member dir:'.$memberdir);
                        $this ->copy_file($USER->id, $file, $CFG->dataroot.'/'.$memberdir);
                    }
                    //update all team members's assignment_submission records.
                    error_log('update member assignment submission');
                    $submission = $this->get_submission($member->student, true); //create new submission if needed
                    $updated = new object();
                    $updated->id           = $submission->id;
                    $updated->timemodified = $currenttime;

                    if (update_record('assignment_submissions', $updated)) {
                        add_to_log($this->course->id, 'assignment', 'upload',
                            'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                        $submission = $this->get_submission($member->student);
                        $this->update_grade($submission);
                        if (!$this->drafts_tracked()) {
                            $this->email_teachers($submission);
                        }
                    } else {
                        $new_filename = $um->get_new_filename();
                        $this->view_header(get_string('upload'));
                        notify(get_string('uploadnotregistered', 'assignment', $new_filename));
                        print_continue($returnurl);
                        $this->view_footer();
                        die;
                    }
                }
            } else {
                $this->view_header(get_string('upload'));
                notify('upload process fail');
                print_continue($returnurl);
                $this->view_footer();
            }
            redirect('view.php?id='.$this->cm->id);
        }

        $this->view_header(get_string('upload'));
        notify(get_string('uploaderror', 'assignment'));
        echo $um->get_errors();
        print_continue($returnurl);
        $this->view_footer();
        die;
    }

    function finalize() {
        global $USER;
        $submission = $this->get_submission($USER->id);
        $confirm    = optional_param('confirm', 0, PARAM_BOOL);
        $returnurl  = 'view.php?id='.$this->cm->id;
        $teamid     = optional_param('teamid', '', PARAM_INT );
        error_log('team id: '.$teamid);

        if (!$this->can_finalize($submission) || !$this->is_member($teamid)) {
            error_log('can not finalize');
            redirect($returnurl); // probably already graded, redirect to assignment page, the reason should be obvious
        }

        if (!data_submitted() or !$confirm) {
            $optionsno = array('id'=>$this->cm->id);
            $optionsyes = array ('id'=>$this->cm->id, 'confirm'=>1, 'action'=>'finalize', 'teamid'=>$teamid);
            $this->view_header(get_string('submitformarking', 'assignment'));
            print_heading(get_string('submitformarking', 'assignment'));
            notice_yesno(get_string('onceassignmentsent', 'assignment'), 'upload.php', 'view.php', $optionsyes, $optionsno, 'post', 'get');
            $this->view_footer();
            die;

        }
        error_log('final redircet teamid: '.$teamid);
        $members = $this->get_members_from_team($teamid);
        $currenttime = time();
        if ($members) {
            foreach ($members as $member) {
                $submission = $this->get_submission($member->student);
                $updated = new object();
                $updated->id           = $submission->id;
                $updated->data2        = ASSIGNMENT_STATUS_SUBMITTED;
                $updated->timemodified = $currenttime;

                if (update_record('assignment_submissions', $updated)) {
                    add_to_log($this->course->id, 'assignment', 'upload', //TODO: add finalize action to log
                    'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                    $submission = $this->get_submission($USER->id);
                    $this->update_grade($submission);
                    $this->email_teachers($submission);
                } else {
                    $this->view_header(get_string('submitformarking', 'assignment'));
                    notify(get_string('finalizeerror', 'assignment'));
                    print_continue($returnurl);
                    $this->view_footer();
                    die;
                }
            }
            //close team membership
            error_log('start close team');
            $team = get_record('team', 'id', $teamid);
            if ($team && $this ->is_member($teamid)) {
                error_log('start update team');
                $team -> membershipopen = 0;
                $team ->timemodified = time();
                update_record('team', $team);
            }
        }
        redirect($returnurl);
    }

    function finalizeclose() {
        $userid    = optional_param('userid', 0, PARAM_INT);
        $mode      = required_param('mode', PARAM_ALPHA);
        $offset    = required_param('offset', PARAM_INT);
        $teamid    = optional_param('teamid','', PARAM_INT );
        $returnurl = "submissions.php?id={$this->cm->id}&amp;userid=$userid&amp;mode=$mode&amp;offset=$offset&amp;forcerefresh=1";

        // create but do not add student submission date
        $submission = $this->get_submission($userid, true, true);

        if (!data_submitted() or !$this->can_finalize($submission)) {
            redirect($returnurl); // probably closed already
        }

        $updated = new object();
        $updated->id    = $submission->id;
        $updated->data2 = ASSIGNMENT_STATUS_CLOSED;

        if (update_record('assignment_submissions', $updated)) {
            add_to_log($this->course->id, 'assignment', 'upload', //TODO: add finalize action to log
                    'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
            $submission = $this->get_submission($userid, false, true);
            $this->update_grade($submission);
        }
        redirect($returnurl);
    }

    function unfinalize() {

        $userid = required_param('userid', PARAM_INT);
        $mode   = required_param('mode', PARAM_ALPHA);
        $offset = required_param('offset', PARAM_INT);

        $returnurl = "submissions.php?id={$this->cm->id}&amp;userid=$userid&amp;mode=$mode&amp;offset=$offset&amp;forcerefresh=1";

        if (data_submitted('nomatch')
        and $submission = $this->get_submission($userid)
        and $this->can_unfinalize($submission)) {

            $updated = new object();
            $updated->id = $submission->id;
            $updated->data2 = '';
            if (update_record('assignment_submissions', $updated)) {
                //TODO: add unfinalize action to log
                add_to_log($this->course->id, 'assignment', 'view submission', 'submissions.php?id='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                $submission = $this->get_submission($userid);
                $this->update_grade($submission);
            } else {
                $this->view_header(get_string('submitformarking', 'assignment'));
                notify(get_string('unfinalizeerror', 'assignment'));
                print_continue($returnurl);
                $this->view_footer();
                die;
            }
        }
        redirect($returnurl);
    }


    function delete() {
        $action   = optional_param('action', '', PARAM_ALPHA);

        switch ($action) {
            case 'response':
                $this->delete_responsefile();
                break;
            default:
                $this->delete_file();
        }
        die;
    }


    function delete_responsefile() {
        global $CFG;

        $file     = required_param('file', PARAM_FILE);
        $userid   = required_param('userid', PARAM_INT);
        $mode     = required_param('mode', PARAM_ALPHA);
        $offset   = required_param('offset', PARAM_INT);
        $confirm  = optional_param('confirm', 0, PARAM_BOOL);

        $returnurl = "submissions.php?id={$this->cm->id}&amp;userid=$userid&amp;mode=$mode&amp;offset=$offset";

        if (!$this->can_manage_responsefiles()) {
            redirect($returnurl);
        }

        $urlreturn = 'submissions.php';
        $optionsreturn = array('id'=>$this->cm->id, 'offset'=>$offset, 'mode'=>$mode, 'userid'=>$userid);

        if (!data_submitted('nomatch') or !$confirm) {
            $optionsyes = array ('id'=>$this->cm->id, 'file'=>$file, 'userid'=>$userid, 'confirm'=>1, 'action'=>'response', 'mode'=>$mode, 'offset'=>$offset);
            print_header(get_string('delete'));
            print_heading(get_string('delete'));
            notice_yesno(get_string('confirmdeletefile', 'assignment', $file), 'delete.php', $urlreturn, $optionsyes, $optionsreturn, 'post', 'get');
            print_footer('none');
            die;
        }

        $dir = $this->file_area_name($userid).'/responses';
        $filepath = $CFG->dataroot.'/'.$dir.'/'.$file;
        if (file_exists($filepath)) {
            if (@unlink($filepath)) {
                redirect($returnurl);
            }
        }

        // print delete error
        print_header(get_string('delete'));
        notify(get_string('deletefilefailed', 'assignment'));
        print_continue($returnurl);
        print_footer('none');
        die;

    }


    function delete_file() {
        global $CFG;

        $file     = required_param('file', PARAM_FILE);
        $userid   = required_param('userid', PARAM_INT);
        $confirm  = optional_param('confirm', 0, PARAM_BOOL);
        $mode     = optional_param('mode', '', PARAM_ALPHA);
        $offset   = optional_param('offset', 0, PARAM_INT);
        $teamid   = optional_param('teamid', PARAM_INT);

        require_login($this->course->id, false, $this->cm);

        if (empty($mode)) {
            $urlreturn = 'view.php';
            $optionsreturn = array('id'=>$this->cm->id);
            $returnurl = 'view.php?id='.$this->cm->id;
        } else {
            $urlreturn = 'submissions.php';
            $optionsreturn = array('id'=>$this->cm->id, 'offset'=>$offset, 'mode'=>$mode, 'userid'=>$userid);
            $returnurl = "submissions.php?id={$this->cm->id}&amp;offset=$offset&amp;mode=$mode&amp;userid=$userid";
        }
        error_log('teamid->'.$teamid);
        if (!$submission = $this->get_submission($userid) // incorrect submission
        or !$this->can_delete_files($submission, $teamid)) {     // can not delete
            error_log('cannot delete file');
            $this->view_header(get_string('delete'));
            notify(get_string('cannotdeletefiles', 'assignment'));
            print_continue($returnurl);
            $this->view_footer();
            die;
        }
        $dir = $this->file_area_name($userid);

        if (!data_submitted('nomatch') or !$confirm) {
            $optionsyes = array ('id'=>$this->cm->id, 'file'=>$file, 'userid'=>$userid, 'confirm'=>1, 'sesskey'=>sesskey(), 'mode'=>$mode, 'offset'=>$offset, 'teamid'=>$teamid);
            if (empty($mode)) {
                $this->view_header(get_string('delete'));
            } else {
                print_header(get_string('delete'));
            }
            print_heading(get_string('delete'));
            notice_yesno(get_string('confirmdeletefile', 'assignment', $file), 'delete.php', $urlreturn, $optionsyes, $optionsreturn, 'post', 'get');
            if (empty($mode)) {
                $this->view_footer();
            } else {
                print_footer('none');
            }
            die;
        }

        $filepath = $CFG->dataroot.'/'.$dir.'/'.$file;
        error_log('filepath: '.$filepath);
        if (file_exists($filepath)) {
            error_log('file path exist');
            if (@unlink($filepath)) {
                $currenttime = time();
                $members = $this->get_members_from_team($teamid);
                if ($members && is_array($members)) {
                    foreach ($members as $member) {
                        if ($member -> student != $USER ->id) {
                            $this -> delete_user_file($member->student,$file);
                        }
                        $submission = $this->get_submission($member->student, true); //create new submission if needed
                        $updated = new object();
                        $updated->id           = $submission->id;
                        $updated->timemodified = $currenttime;
                        if (update_record('assignment_submissions', $updated)) {
                            add_to_log($this->course->id, 'assignment', 'upload', //TODO: add delete action to log
                                'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                            $this->update_grade($submission);
                        } else {
                            print_header(get_string('delete'));
                            notify("delete files fail");
                            print_continue($returnurl);
                            print_footer('none');
                            die;
                        }
                    }
                }
                 
                redirect($returnurl);
            }
        }

        // print delete error
        if (empty($mode)) {
            $this->view_header(get_string('delete'));
        } else {
            print_header(get_string('delete'));
        }
        notify(get_string('deletefilefailed', 'assignment'));
        print_continue($returnurl);
        if (empty($mode)) {
            $this->view_footer();
        } else {
            print_footer('none');
        }
        die;
    }


    function can_upload_file($submission, $teamid) {
        global $USER;

        if (has_capability('mod/assignment:submit', $this->context)           // can submit
        and $this->isopen()                                                 // assignment not closed yet
        and (empty($submission) or $submission->userid == $USER->id)        // his/her own submission
        and $this->count_user_files($USER->id) < $this->assignment->var1    // file limit not reached
        and !$this->is_finalized($submission)                              // no uploading after final submission
        and $this->is_member($teamid)) {
            return true;
        } else {
            return false;
        }
    }

    function can_manage_responsefiles() {
        if (has_capability('mod/assignment:grade', $this->context)) {
            return true;
        } else {
            return false;
        }
    }

    function can_delete_files($submission, $teamid) {
        global $USER;

        if (has_capability('mod/assignment:grade', $this->context)) {
            return true;
        }
        error_log('can_delete_files function team id: '.$teamid);
        if (has_capability('mod/assignment:submit', $this->context)
        and $this->isopen()                                      // assignment not closed yet
        and $this->assignment->resubmit                          // deleting allowed
        and $USER->id == $submission->userid                     // his/her own submission
        and !$this->is_finalized($submission)                    // no deleting after final submission
        and $this->is_member($teamid)) {
            return true;
        } else {
            error_log('can_delete_file return false');
            return false;
        }
    }

    function drafts_tracked() {
        return !empty($this->assignment->var4);
    }

    /**
     * Returns submission status
     * @param object $submission - may be empty
     * @return string submission state - empty, ASSIGNMENT_STATUS_SUBMITTED or ASSIGNMENT_STATUS_CLOSED
     */
    function is_finalized($submission) {
        if (!$this->drafts_tracked()) {
            return '';

        } else if (empty($submission)) {
            return '';

        } else if ($submission->data2 == ASSIGNMENT_STATUS_SUBMITTED or $submission->data2 == ASSIGNMENT_STATUS_CLOSED) {
            return $submission->data2;

        } else {
            return '';
        }
    }

    function can_unfinalize($submission) {
        if (!$this->drafts_tracked()) {
            return false;
        }
        if (has_capability('mod/assignment:grade', $this->context)
        and $this->isopen()
        and $this->is_finalized($submission)) {
            return true;
        } else {
            return false;
        }
    }

    function can_finalize($submission) {
        global $USER;
        $team = $this->get_user_team($USER->id);
        if (!$this->drafts_tracked()) {
            return false;
        }

        if ($this->is_finalized($submission)) {
            return false;
        }

        if (has_capability('mod/assignment:grade', $this->context)) {
            return true;

        } else if (has_capability('mod/assignment:submit', $this->context)    // can submit
        and $this->isopen()                                                 // assignment not closed yet
        and !empty($submission)                                             // submission must exist
        and $submission->userid == $USER->id                                // his/her own submission
        and ($this->count_user_files($USER->id)
        or ($this->notes_allowed() and !empty($submission->data1)))) {    // something must be submitted

            return true;
        } else {
            return false;
        }
    }

    function can_update_notes($submission) {
        global $USER;

        if (has_capability('mod/assignment:submit', $this->context)
        and $this->notes_allowed()                                          // notesd must be allowed
        and $this->isopen()                                                 // assignment not closed yet
        and (empty($submission) or $USER->id == $submission->userid)        // his/her own submission
        and !$this->is_finalized($submission)) {                            // no updateingafter final submission
            return true;
        } else {
            return false;
        }
    }

    function notes_allowed() {
        return (boolean)$this->assignment->var2;
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
        error_log('file area count_user_files function : '.$filearea);
        if ( is_dir($CFG->dataroot.'/'.$filearea) && $basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir, 'responses')) {
                return count($files);
            }
        }
        return 0;
    }

    function count_responsefiles($userid) {
        global $CFG;

        $filearea = $this->file_area_name($userid).'/responses';

        if ( is_dir($CFG->dataroot.'/'.$filearea) && $basedir = $this->file_area($userid)) {
            $basedir .= '/responses';
            if ($files = get_directory_list($basedir)) {
                return count($files);
            }
        }
        return 0;
    }

    function setup_elements(&$mform) {
        global $CFG, $COURSE;

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'assignment'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);

        $mform->addElement('select', 'resubmit', get_string("allowdeleting", "assignment"), $ynoptions);
        $mform->setHelpButton('resubmit', array('allowdeleting', get_string('allowdeleting', 'assignment'), 'assignment'));
        $mform->setDefault('resubmit', 1);

        $options = array();
        for($i = 1; $i <= 20; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'var1', get_string("allowmaxfiles", "assignment"), $options);
        $mform->setHelpButton('var1', array('allowmaxfiles', get_string('allowmaxfiles', 'assignment'), 'assignment'));
        $mform->setDefault('var1', 3);

        $mform->addElement('select', 'var2', get_string("allownotes", "assignment"), $ynoptions);
        $mform->setHelpButton('var2', array('allownotes', get_string('allownotes', 'assignment'), 'assignment'));
        $mform->setDefault('var2', 0);

        $mform->addElement('select', 'var3', get_string("hideintro", "assignment"), $ynoptions);
        $mform->setHelpButton('var3', array('hideintro', get_string('hideintro', 'assignment'), 'assignment'));
        $mform->setDefault('var3', 0);

        $mform->addElement('select', 'emailteachers', get_string("emailteachers", "assignment"), $ynoptions);
        $mform->setHelpButton('emailteachers', array('emailteachers', get_string('emailteachers', 'assignment'), 'assignment'));
        $mform->setDefault('emailteachers', 0);

        $mform->addElement('select', 'var4', get_string("trackdrafts", "assignment"), $ynoptions);
        $mform->setHelpButton('var4', array('trackdrafts', get_string('trackdrafts', 'assignment'), 'assignment'));
        $mform->setDefault('var4', 1);

    }

    /**
     * Returns the team that the user belongs to. A student can only belong to one
     * team per assignment
     * @param $userid
     * @return team object
     */
    function get_user_team($userid ){
        global $CFG;
        $teams = get_records_sql("SELECT id, assignment, name, membershipopen".
                                 " FROM {$CFG->prefix}team ".
                                 " WHERE assignment = ".$this->assignment->id);
        foreach($teams as $team) {
            $teamid = $team->id;
            if (get_record('team_student','student',$userid, 'team', $teamid)) {
                return $team;
            }
        }
        return null;
    }

    /**
     *return the first member whose id is not same as login user id
     */
    function get_another_user_copy($userid, $teamid) {
        global $CFG;
        $members = $this -> get_members_from_team($teamid);
        if (is_array($members)) {
            foreach($members as $member) {
                if($member->student != $userid) {
                    error_log('another menber userid: '.$member->student);
                    return $member;
                }
            }
        }
        return null;
    }
    /**
     * returns all the members from this team
     * @param $teamid
     * @return array of users
     */
    function get_members_from_team ($teamid) {
        global $CFG;
        error_log('called get members');
        return get_records_sql("SELECT id, student, timemodified".
                                 " FROM {$CFG->prefix}team_student ".
                                 " WHERE team = ".$teamid);
    }

    /**
     *
     * @param $userid
     * @param $teamid
     * @return removed object
     */
    function remove_user_from_team($userid , $teamid) {
        global $USER;

        $submission = $this->get_submission($userid, false);
        //capability check only if team member can remove a user from a team
        if ($this->is_member($teamid)) {
            $select = ' student = '.$userid.' and '.' team = '.$teamid;
            if (!delete_records_select('team_student', $select)){
                error_log('delete member fail in team_student');
                $this->print_error();

            }
            //if team members in this team  are empty, delete this team
            $members = $this->get_members_from_team($teamid);
            if (!$members) {
                $select = ' id = '.$teamid;
                if(!delete_records_select('team', $select)) {
                    error_log('delete team fail');
                    $this->print_error();
                }
            }
            if ($submission) {
                error_log('user submission is not null');
                $select = ' id ='.$submission->id;
                if (!delete_records_select('assignment_submissions', $select)) {
                    error_log('delete user submission fail');
                    $this->print_error();
                }
            }
            //remove this student's assignment files
            $this -> delete_all_files($userid);
            
            //update team record if this team exist
            $team = get_record('team' , 'id', $teamid);
            if ($team) {
                $team ->timemodified = time();
                update_record('team', $team);
            }
        } else {
            error_log('remove a user fail');
            $this->print_error();
        }
    }

    function is_member($teamid) {
        global $USER;
        $team = $this->get_user_team($USER->id);
        return isset($team) && ($team->id == $teamid);
         
    }

    /**
     *
     * @param $name
     *
     */
    function create_team ($name) {
        global $USER;
        if(!isset($name) || trim($name)=='' ) {
            error_log('team name is empty');
            notify(get_string('teamnameerror', 'assignment_team'));
        } else {
            error_log('insert new record into a team table');
            if (get_record('team', 'assignment',$this->assignment->id, 'name',$name)) {
                notify(get_string('teamnameexist','assignment_team'));
            } else {
                $userteam = $this->get_user_team($USER ->id);
                if (!isset($userteam)) {
                    $team = new Object();
                    $team ->assignment = $this->assignment->id;
                    $team ->name = $name;
                    $team ->membershipopen = 1; //1 for team membership open, 0 is for team membership close
                    $team ->timemodified = time();
                    //start create a team and join this team
                    $createTeam = insert_record('team', $team, true) ;
                    //Insert a record into a table and return the "id" field or boolean value
                    if (!$createTeam) {
                        notify(get_string('createteamerror', 'assignment_team'));
                    } else {
                        $this ->join_team($USER->id, $createTeam);
                    }
                }
            }
        }
    }

    /**
     *
     * @param $teamid
     *
     */
    function delete_team ($teamid) {
        error_log('get members start');
        global $USER;
        $members = $this->get_members_from_team($teamid);
        error_log('user id:'.$USER->id);
        //only can remove this team if only if there is only this log in student in this team
        if (isset($members)&& is_array($members)&& count($members)== 1) {
            error_log('remove members start');
            foreach ($members as $member) {
                if ($member->student == $USER->id) {
                    $this -> remove_user_from_team($USER->id, $teamid);
                }
            }
        }
    }

    /**
     * Print a select box with the list of teams
     * @return unknown_type
     */
    function print_team_list(){
        global $CFG, $PHP_SELF;
        error_log('start show teams');
        error_log($CFG->prefix."team");
        error_log("assignment id:".$this->assignment->id);
        $teams = get_records_sql("SELECT id, assignment, name, membershipopen".
                                 " FROM {$CFG->prefix}team ".
                                 " WHERE assignment = ".$this->assignment->id);
        $strteams = get_string('teams');
        $onchange = '';
        echo '<form id="jointeamform" action="'.$PHP_SELF.'" method="post">'."\n";
        echo '<div>'."\n";
        echo '<table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">'."\n";
        echo '<tr>'."\n";
        echo "<td>\n";
        echo '<p><label for="groups"><span id="groupslabel">'.get_string('existingteams', 'assignment_team').'</span></label></p>'."\n";
        echo '<select name="groups[]"  id="groups" size="15" class="select" onchange="'.$onchange.'"'."\n";
        echo ' onclick="window.status=this.selectedIndex==-1 ? \'\' : this.options[this.selectedIndex].title;" onmouseout="window.status=\'\';">'."\n";

        if ($teams) {
            // Print out the HTML
            error_log('teams not null');
            foreach ($teams as $team) {
                $select = '';
                //after any post action from act_viewmember button still can selected previous select
                if ($_POST['act_viewmember'] && isset($_POST['groups'])
                && count($_POST['groups'])==1 && $_POST['groups'][0] == $team->id ) {
                    error_log("team id".$_POST['groups'][0]);
                    error_log("team expected id".$team->id);
                    $select = ' selected = "true" ';
                }
                $usercount = (int)count_records('team_student', 'team', $team->id);
                $teamname = format_string($team->name).' ('.$usercount.')'.' '.$this ->get_team_status_name($team->membershipopen);
                echo "<option value=\"{$team->id}\"$select\" title=\"$teamname\">$teamname</option>\n";
            }
        } else {
            // Print an empty option to avoid the XHTML error of having an empty select element
            echo '<option>&nbsp;</option>';
        }
        echo '</select>'."\n";
        echo '<input type="hidden" name="jointeamtime" value="'.time().'" />';
        echo '<p><input type="submit" name="act_jointeam" id="jointeam" value="'
        . get_string('jointeam', 'assignment_team') . '" />
        <input type ="submit" name="act_viewmember"  id ="viewteam" value = "'
        . get_string('viewmember', 'assignment_team') . '"  />
        </p>'."\n";
        echo '</td>'."\n";
        echo '<td>'."\n";
        echo '<p><label for="teammember"><span id="teammemberlabel">'.
        get_string('teammember', 'assignment_team').' </span></label></p>'."\n";
        echo '<select multiple="multiple" id="teammember" size="15">';
        if (isset($_POST['act_viewmember']) && isset($_POST['groups'])
        && count($_POST['groups'])==1) {
            $teamid = $_POST['groups'][0];
            $members = get_records_sql("SELECT id, student, team".
                                 " FROM {$CFG->prefix}team_student ".
                                 " WHERE team = ".$teamid);
            if (isset($members) && count($members)>0) {
                foreach ($members as $member) {
                    $userid = $member->student;
                    $user = get_record ('user', 'id', $userid);
                    echo "<option >".$user->firstname." ".$user->lastname."</option>";
                }
            }
             
        } else {
            //print empty list
            echo '<option>&nbsp;</option>';
        }

        echo '</select>';
        echo '</td>'."\n";
        echo '</tr>'."\n";
        echo '<tr>';
        echo '<td colspan="2" >';
        echo '<p>'.get_string('createteamlabel','assignment_team').'</p>';
        echo '<p>'.get_string('teamname','assignment_team').' '.'<input type ="text" name ="teamname" id="teamname" />
              <input type="hidden" name="createteamtime" value="'.time().'" />
              <input type ="submit" name="act_createteam" id ="createteam" 
               value = "'.get_string('createteam','assignment_team').'" /> </p>';
        echo '</td>';
        echo '</tr>';
        echo '</table>'."\n";
        echo '</div>'."\n";
        echo '</form>'."\n";
    }


    function join_team ($studentid, $teamid) {
        global $CFG;
        //if user already in team update otherwise insert
        error_log('user join a team');
        $team = $this->get_user_team($studentid);
        //update team_student table
        if (isset($team)) {
            error_log('update into team start');
            $member = get_record('team_student','student',$studentid, 'team', $team->id);
            $member->timemodified = time();
            update_record('team_student',$member);
        } else {
            error_log('insert into team start');
            $insertteam = get_record('team', 'id', $teamid);
            if ($insertteam && $insertteam->membershipopen) {
                $teamstudent = new Object();
                $teamstudent ->student = $studentid;
                $teamstudent ->team = $teamid;
                $teamstudent ->timemodified = time();
                insert_record('team_student',$teamstudent, false);
                
                //update team timemodified
                $team = get_record('team', 'id', $teamid);
                $team -> timemodified = time();
                update_record('team', $team);

                //if there is a existing record in assignment_submissions for this team,
                //create a new assignment_submission record and copy all files from another team members file folder.
                $existmember = $this->get_another_user_copy($studentid, $teamid);
                if (isset($existmember)) {
                    $copy = get_record('assignment_submissions', 'assignment', $this->assignment->id, 'userid', $existmember->student);
                    if ($copy) {
                        $submission = $this -> prepare_update_submission($studentid, $copy);
                        if (update_record('assignment_submissions', $submission)) {
                            $dir = $this -> file_area_name($studentid);
                            check_dir_exists($CFG->dataroot.'/'.$dir, true, true);
                            //copy the assignment files from existing member to this new member.
                            $this->copy_all_files($existmember->student, $CFG->dataroot.'/'.$dir);
                        }
                    }
                }
            } else {
                notify(get_string('teamclosedwarning', 'assignment_team'));
            }
        }

    }

    function prepare_update_submission($studentid, $copy) {
        $submission = $this->get_submission($studentid, true);
        $submission->assignment   = $copy->assignment;
        $submission->userid       = $studentid;
        $submission->timecreated  = $copy->timecreated;
        $submission->timemodified = $copy->timemodified;
        error_log('copy time modified: '.$copy->timemodified);
        $submission->numfiles     = $copy->numfiles;
        $submission->data1        = $copy->data1;
        $submission->data2        = $copy->data2;
        $submission->grade        = $copy->grade;
        $submission->submissioncomment      = $copy->submissioncomment;
        $submission->format       = $copy->format;
        $submission->teacher      = $copy->teacher;
        $submission->timemarked   = $copy->timemarked;
        $submission->mailed       = $copy->mailed;
        return $submission;
    }

    /**
     * delete this user all files and dir.
     */
    function delete_all_files($userid) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');
        error_log('start delete all files');
        $dir = $this->file_area_name($userid);
        $filepath = $CFG->dataroot.'/'.$dir;
        fulldelete($filepath);
    }

    /**
     * copy all this user files to destination.
     */
    function copy_all_files($userid, $destination) {
        global $CFG;
        error_log('start copy all files');
        if ($basedir = $this->file_area($userid)) {
            if($files = get_directory_list($basedir, 'responses', false)){
                foreach ($files as $key => $file) {
                    error_log('copy file: '.$file);
                    $this ->copy_file($userid,$file, $destination);
                }
            }
        }

    }

    /**
     * copy a given user file to a destination
     */
    function copy_file($userid , $file, $destination) {
        global $CFG;
        $dir = $this->file_area_name($userid);
        $filepath = $CFG->dataroot.'/'.$dir.'/'.$file;
        error_log('copy file: '.$filepath);
        error_log('destination :'.$destination);
        if (file_exists($filepath)){
            copy($filepath, $destination.'/'.$file);
        }
    }

    function delete_user_file ($userid, $file) {
        global $CFG;
        $dir = $this->file_area_name($userid);
        $filepath = $CFG->dataroot.'/'.$dir.'/'.$file;
        if (file_exists($filepath)){
            unlink($filepath);
        }
    }

    function print_error() {
        $this->view_header(get_string('unkownerror', 'assignment_team'));
        notify(get_string('unkownerror', 'assignment_team'));
        $returnurl = 'view.php?id='.$this->cm->id;
        print_continue($returnurl);
        $this->view_footer();
        die;
    }

    /**
     *
     * @param $teamid
     */
    function open_close_team($teamid) {
        $team = get_record('team', 'id', $teamid);
        if ($team && $this ->is_member($teamid)) {
            $status = $team -> membershipopen;
            if ($status) {
                $team -> membershipopen = 0;
            } else {
                $team -> membershipopen = 1;
            }
            $team ->timemodified = time();
            update_record('team', $team);
        }
    }

    private function get_team_status_name($status) {
        if ($status) {
            return  get_string('teamopen', 'assignment_team');
        } else {
            return  get_string('teamclosed', 'assignment_team');
        }
    }

}

class mod_assignment_team_notes_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        // visible elements
        $mform->addElement('htmleditor', 'text', get_string('notes', 'assignment'), array('cols'=>85, 'rows'=>30));
        $mform->setType('text', PARAM_RAW); // to be cleaned before display
        $mform->setHelpButton('text', array('reading', 'writing'), false, 'editorhelpbutton');

        // hidden params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'savenotes');
        $mform->setType('id', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons();
    }
}

?>
