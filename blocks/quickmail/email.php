<?php //$Id: email.php,v 1.20 2009/03/18 01:09:12 whchuang Exp $
/**
 * email.php - Used by Quickmail for sending emails to users enrolled in a specific course.
 *      Calls email.hmtl at the end.
 *
 * @author Mark Nielsen (co-maintained by Wen Hao Chuang)
 * @special thanks for Neil Streeter to provide patches for GROUPS
 * @package quickmail
 **/
    
    require_once('../../config.php');
    require_once($CFG->libdir.'/blocklib.php');

    $id         = required_param('id', PARAM_INT);  // course ID
    $instanceid = optional_param('instanceid', 0, PARAM_INT);
    $action     = optional_param('action', '', PARAM_ALPHA);

    $instance = new stdClass;

    if (!$course = get_record('course', 'id', $id)) {
        error('Course ID was incorrect');
    }

    require_login($course->id);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if ($instanceid) {
        $instance = get_record('block_instance', 'id', $instanceid);
    } else {
        if ($quickmailblock = get_record('block', 'name', 'quickmail')) {
            $instance = get_record('block_instance', 'blockid', $quickmailblock->id, 'pageid', $course->id);
        }
    }

/// This block of code ensures that Quickmail will run 
///     whether it is in the course or not
    if (empty($instance)) {
        $groupmode = groupmode($course);
        if (has_capability('block/quickmail:cansend', get_context_instance(CONTEXT_BLOCK, $instanceid))) {
            $haspermission = true;
        } else {
            $haspermission = false;
        }
    } else {
        // create a quickmail block instance
        $quickmail = block_instance('quickmail', $instance);
        
        $groupmode     = $quickmail->groupmode();
        $haspermission = $quickmail->check_permission();
    }
    
    if (!$haspermission) {
        error('Sorry, you do not have the correct permissions to use Quickmail.');
    }

    if (!$courseusers = get_users_by_capability($context, 'moodle/course:view', 'u.*', 'u.lastname, u.firstname', '', '', '', '', false)) {
        error('No course users found to email');
    }

    if ($action == 'view') {
        // viewing an old email.  Hitting the db and puting it into the object $form
        $emailid = required_param('emailid', PARAM_INT);
        $form = get_record('block_quickmail_log', 'id', $emailid);
        $form->mailto = explode(',', $form->mailto); // convert mailto back to an array

    } else if ($form = data_submitted()) {   // data was submitted to be mailed
        confirm_sesskey();

        if (!empty($form->cancel)) {
            // cancel button was hit...
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");
        }
        
        // prepare variables for email
        $form->subject = stripslashes($form->subject);
        $form->subject = clean_param(strip_tags($form->subject, '<lang><span>'), PARAM_RAW); // Strip all tags except multilang
        $form->message = clean_param($form->message, PARAM_CLEANHTML);

        // make sure the user didn't miss anything
        if (!isset($form->mailto)) {
            $form->error = get_string('toerror', 'block_quickmail');
        } else if (!$form->subject) {
            $form->error = get_string('subjecterror', 'block_quickmail');
        } else if (!$form->message) {
            $form->error = get_string('messageerror', 'block_quickmail');
        }

        // process the attachment
        $attachment = $attachname = '';
        if (has_capability('moodle/course:managefiles', $context)) {
            $form->attachment = trim($form->attachment);
            if (isset($form->attachment) and !empty($form->attachment)) {
                $form->attachment = clean_param($form->attachment, PARAM_PATH);
            
                if (file_exists($CFG->dataroot.'/'.$course->id.'/'.$form->attachment)) {
                    $attachment = $course->id.'/'.$form->attachment;
            
                    $pathparts = pathinfo($form->attachment);
                    $attachname = $pathparts['basename'];
                } else {
                    $form->error = get_string('attachmenterror', 'block_quickmail', $form->attachment);
                }
            }
        } else {
            require_once($CFG->libdir.'/uploadlib.php');
        
            $um = new upload_manager('attachment', false, true, $course, false, 0, true);

            // process the student posted attachment if it exists
            if ($um->process_file_uploads('temp/block_quickmail')) {
                
                // original name gets saved in the database
                $form->attachment = $um->get_original_filename();

                // check if file is there
                if (file_exists($um->get_new_filepath())) {
                    // get path to the file without $CFG->dataroot
                    $attachment = 'temp/block_quickmail/'.$um->get_new_filename();
                
                    // get the new name (name may change due to filename collisions)
                    $attachname = $um->get_new_filename();
                } else {
                    $form->error = get_string("attachmenterror", "block_quickmail", $form->attachment);
                }
            } else {
                $form->attachment = ''; // no attachment
            }
        }
           
        // no errors, then email
        if(!isset($form->error)) {
            $mailedto = array(); // holds all the userid of successful emails
            
            // get the correct formating for the emails
            $form->plaintxt = format_text_email($form->message, $form->format); // plain text
            $form->html = format_text($form->message, $form->format);        // html

            // run through each user id and send a copy of the email to him/her
            // not sending 1 email with CC to all user ids because emails were required to be kept private
            foreach ($form->mailto as $userid) {  
                if (!$courseusers[$userid]->emailstop) {
                    $mailresult = email_to_user($courseusers[$userid], $USER, $form->subject, $form->plaintxt, $form->html, $attachment, $attachname);
                    // checking for errors, if there is an error, store the name
                    if (!$mailresult || (string) $mailresult == 'emailstop') {
                        $form->error = get_string('emailfailerror', 'block_quickmail');
                        $form->usersfail['emailfail'][] = $courseusers[$userid]->lastname.', '.$courseusers[$userid]->firstname;
                    } else {
                        // success
                        $mailedto[] = $userid;
                    }
                } else {
                    // blocked email
                    $form->error = get_string('emailfailerror', 'block_quickmail');
                    $form->usersfail['emailstop'][] = $courseusers[$userid]->lastname.', '.$courseusers[$userid]->firstname;
                }
            }
            
            // cleanup - delete the uploaded file
            if (isset($um) and file_exists($um->get_new_filepath())) {
                unlink($um->get_new_filepath());
            }

            // prepare an object for the insert_record function
            $log = new stdClass;
            $log->courseid   = $course->id;
            $log->userid     = $USER->id;
            $log->mailto     = implode(',', $mailedto);
            $log->subject    = addslashes($form->subject);
            $log->message    = addslashes($form->message);
            $log->attachment = $form->attachment;
            $log->format     = $form->format;
            $log->timesent   = time();
            if (!insert_record('block_quickmail_log', $log)) {
                error('Email not logged.');
            }

            if(!isset($form->error)) {  // if no emailing errors, we are done
                // inform of success and continue
                redirect("$CFG->wwwroot/course/view.php?id=$course->id", get_string('successfulemail', 'block_quickmail'));
            }
        }
        // so people can use quotes.  It will display correctly in the subject input text box
        $form->subject = s($form->subject);

    } else {
        // set them as blank
        $form->subject = $form->message = $form->format = $form->attachment = '';
    }

/// Create the table object for holding course users in the To section of email.html
    
    // table object used for printing the course users
    $table              = new stdClass;
    $table->cellpadding = '10px';    
    $table->width       = '100%';

    $t    = 1;    // keeps track of the number of users printed (used for javascript)
    $cols = 4;    // number of columns in the table

    if ($groupmode == NOGROUPS) { // no groups, basic view
        $table->head  = array();
        $table->align = array('left', 'left', 'left', 'left');
        $cells        = array();

        foreach($courseusers as $user) { 
            if (isset($form->mailto) && in_array($user->id, $form->mailto)) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }       
        
            $cells[] = "<input type=\"checkbox\" $checked id=\"mailto$t\" value=\"$user->id\" name=\"mailto[]\" />".
                        "<label for=\"mailto$t\">".fullname($user, true).'</label>';
            $t++;
        }
        $table->data = array_chunk($cells, $cols);
    } else {
        $groups      = new stdClass;    // holds the groups to be displayed
        $buttoncount = 1;               // counter for the buttons (used by javascript)
        $ingroup     = array();         // keeps track of the users that belong to groups
        
        // determine the group mode
        if (has_capability('moodle/site:accessallgroups', $context)) {
            // teachers/admins default to the more liberal group mode
            $groupmode = VISIBLEGROUPS;
        }
        
        // set the groups variable
        switch ($groupmode) {
            case VISIBLEGROUPS:
                $groups = groups_get_all_groups($course->id);
                break;

            case SEPARATEGROUPS:
                $groups = groups_get_all_groups($course->id,$USER->id);
                break;
        }

        // Add a fake group for those who are not group members
        $groups[] = 0;

        $notingroup = array();
        if ($allgroups = groups_get_all_groups($course->id)) {
            foreach ($courseusers as $user) {
                $nomembership = true;
                foreach ($allgroups as $group) {
                    if (groups_is_member($group->id, $user->id)) {
                        $nomembership = false;
                        break;
                    }
                }
                if ($nomembership) {
                    $notingroup[] = $user->id;
                }
            }
        }

        // set up the table
        $table->head        = array(get_string('groups'), get_string('groupmembers'));
        $table->align       = array('center', 'left');
        $table->size        = array('100px', '*');
        
        foreach($groups as $group) {            
            $start = $t;
            $cells = array();  // table cells (each is a check box next to a user name)
            foreach($courseusers as $user) { 
                if (is_object( $group ) and groups_is_member($group->id, $user->id) or                    // is a member of the group or
                   (!is_object( $group ) and $group == 0 and in_array($user->id, $notingroup)) ) {     // this is our fake group and this user is not a member of another group
                                                    
                    if (isset($form->mailto) && in_array($user->id, $form->mailto)) {
                        $checked = 'checked="checked"';
                    } else {
                        $checked = '';
                    }
        
                    $cells[] = "<input type=\"checkbox\" $checked id=\"mailto$t\" value=\"$user->id\" name=\"mailto[$user->id]\" />".
                                "<label for=\"mailto$t\">".fullname($user, true).'</label>';
                    $t++;
                }
            }
            $end = $t;
            
            // cell1 has the group picture, name and check button
            $cell1 = '';
            if ( $group ) {
                $cell1   .= print_group_picture($group, $course->id, false, true).'<br />';
            }
            if ($group) {
                $cell1 .= groups_get_group_name($group->id);
            } else {
                $cell1 .= get_string('notingroup', 'block_quickmail');
            }
            if (count($groups) > 1 and !empty($cells)) {
                $selectlinks = '<a href="javascript:void(0);" onclick="block_quickmail_toggle(true, '.$start.', '.$end.');">'.get_string('selectall').'</a> / 
                                <a href="javascript:void(0);" onclick="block_quickmail_toggle(false, '.$start.', '.$end.');">'.get_string('deselectall').'</a>';
            } else {
                $selectlinks = '';
            }
            $buttoncount++;
            
            // cell2 has the checkboxes and the user names inside of a table
            if (empty($cells) and !$group) {
                // there is no one that is not in a group, so no need to print our 'nogroup' group
                continue;
            } else if (empty($cells)) {
                // cells is empty, so there are no group members for that group
                $cell2 = get_string('nogroupmembers', 'block_quickmail');
            } else {
                $cell2 = '<table cellpadding="5px">';
                $rows = array_chunk($cells, $cols);
                foreach ($rows as $row) {
                    $cell2 .= '<tr><td nowrap="nowrap">'.implode('</td><td nowrap="nowrap">', $row).'</td></tr>';
                }
                $cell2 .= '</table>';
            }
            // add the 2 cells to the table
            $table->data[] = array($cell1, $selectlinks.$cell2);
        }
    }

    // get the default format       
    if ($usehtmleditor = can_use_richtext_editor()) {
        $defaultformat = FORMAT_HTML;
    } else {
        $defaultformat = FORMAT_MOODLE;
    }
    
    // set up some strings
    $readonly       = '';
    $strchooseafile = get_string('chooseafile', 'resource');
    $strquickmail   = get_string('blockname', 'block_quickmail');

/// Header setup
    if ($course->category) {
        $navigation = "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    print_header($course->fullname.': '.$strquickmail, $course->fullname, "$navigation $strquickmail", '', '', true);

    // print the email form START
    print_heading($strquickmail);

    // error printing
    if (isset($form->error)) {
        notify($form->error);
        if (isset($form->usersfail)) {
            $errorstring = '';

            if (isset($form->usersfail['emailfail'])) {
                $errorstring .= get_string('emailfail', 'block_quickmail').'<br />';
                foreach($form->usersfail['emailfail'] as $user) {
                    $errorstring .= $user.'<br />';
                }               
            }

            if (isset($form->usersfail['emailstop'])) {
                $errorstring .= get_string('emailstop', 'block_quickmail').'<br />';
                foreach($form->usersfail['emailstop'] as $user) {
                    $errorstring .= $user.'<br />';
                }               
            }
            notice($errorstring, "$CFG->wwwroot/course/view.php?id=$course->id", $course);
        }
    }

    $currenttab = 'compose';
    include($CFG->dirroot.'/blocks/quickmail/tabs.php');

    print_simple_box_start('center');
    require($CFG->dirroot.'/blocks/quickmail/email.html');
    print_simple_box_end();
    
    if ($usehtmleditor) {
        use_html_editor('message');
    }

    print_footer($course);
?>
