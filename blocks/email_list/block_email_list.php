<?php

require_once($CFG->dirroot .'/blocks/email_list/email/lib.php');

/**
 * This block shows information about user email's
 *
 * @author Sergio Sama
 * @author Toni Mas
 * @version 1.0
 * @package email
 **/
class block_email_list extends block_list {

	function init() {
		$this->title = get_string('email_list', 'block_email_list');
		$this->version = 2008081500;
		$this->cron = 1;
	}

	function get_content() {
		global $USER, $CFG, $COURSE;

		// Get course id
		if ( ! empty($COURSE) ) {
            $this->courseid = $COURSE->id;
        }

		// If block have content, skip.
		if ($this->content !== NULL) {
			return $this->content;
		}

		$this->content = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();


		// Get context
		$context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);

		$emailicon = '<img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/sobre.png" height="11" width="15" alt="'.get_string("course").'" />';
		$composeicon = '<img src="'.$CFG->pixpath.'/i/edit.gif" alt="" />';

		// Only show all course in principal course, others, show it
		if ( $this->instance->pageid == 1 ) {
			//Get the courses of the user
			$mycourses = get_my_courses($USER->id);
			$this->content->footer = '<br /><a href="'.$CFG->wwwroot.'/blocks/email_list/email/">'.get_string('view_all', 'block_email_list').' '.$emailicon.'</a>';
		} else {

			if (! empty($CFG->mymoodleredirect) and $COURSE->id == 1 ) {
				//Get the courses of the user
				$mycourses = get_my_courses($USER->id);
				$this->content->footer = '<br /><a href="'.$CFG->wwwroot.'/blocks/email_list/email/">'.get_string('view_all', 'block_email_list').' '.$emailicon.'</a>';
			} else {
				// Get this course
				$course = get_record('course','id',$this->instance->pageid);
				$mycourses[] = $course;
				$this->content->footer = '<br /><a href="'.$CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$course->id.'">'.get_string('view_inbox', 'block_email_list').' '.$emailicon.'</a>';
				$this->content->footer .= '<br /><a href="'.$CFG->wwwroot.'/blocks/email_list/email/sendmail.php?course='.$course->id.'&folderid=0&filterid=0&folderoldid=0&action=newmail">'.get_string('compose', 'block_email_list').' '.$composeicon.'</a>';
			}
		}

		// Count my courses
		$countmycourses = count($mycourses);

		//Configure item and icon for this account
		$icon = '<img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/openicon.gif" height="16" width="16" alt="'.get_string("course").'" />';

		$number = 0;
		foreach( $mycourses as $mycourse ) {

			++$number; // increment for first course

			if ( $number > $CFG->email_max_number_courses && !empty($CFG->email_max_number_courses) ) {
				continue;
			}
			//Get the number of unread mails
			$numberunreadmails = email_count_unreaded_mails($USER->id, $mycourse->id);

			// Only show if has unreaded mails
			if ( $numberunreadmails > 0 ) {

				$unreadmails = '<b>('.$numberunreadmails.')</b>';
				$this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$mycourse->id.'">'.$mycourse->fullname .' '. $unreadmails.'</a>';
				$this->content->icons[] = $icon;
			}
		}

		if ( count( $this->content->items ) == 0 ) {
			$this->content->items[] = '<div align="center">'.get_string('emptymailbox', 'block_email_list').'</div>';
		}

		return $this->content;
	}

	function applicable_formats() {
        return array('all' => true, 'mod' => false, 'tag' => false);
    }

	function has_config() {
        return true;
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
    function cron() {

		global $CFG;

		// If no isset trackbymail, return cron.
		if ( !isset($CFG->email_trackbymail) ) {
			return true;
		}

		// If NOT enabled
		if ( $CFG->email_trackbymail == 0 ) {
			return true;
		}

		// Get actualtime
		$now = time();

		// Get record for mail list
		if ( $block = get_record('block', 'name', 'email_list') ) {

			if ( $now > $block->lastcron ) {

				$unreadmails = new stdClass();

				// Get users who have unread mails
				$from = "{$CFG->prefix}user u,
						 {$CFG->prefix}email_send s,
						 {$CFG->prefix}email_mail m";


				$where = " WHERE u.id = s.userid
								AND s.mailid = m.id
								AND m.timecreated > $block->lastcron
								AND s.readed = 0
								AND s.sended = 1";

				// If exist any users
				if ( $users = get_records_sql('SELECT u.* FROM '.$from.$where) ) {

					// For each user ... get this unread mails, and send alert mail.
					foreach ( $users as $user ) {

						$mails = new stdClass();

						// Preferences! Can send mail?
						// Case:
						// 		1.- Site allow send trackbymail
						//			1.1.- User doesn't define this settings -> Send mail
						//			1.2.- User allow trackbymail -> Send mail
						//			1.3.- User denied trackbymail -> Don't send mail

						// User can definied this preferences?
						if ( $preferences = get_record('email_preference', 'userid', $user->id) ) {
							if ( $preferences->trackbymail == 0 ) {
								continue;
							}
						}


						// Get this unread mails
						if ( $mails = get_records_sql("SELECT * FROM {$CFG->prefix}email_send where readed=0 AND sended=1 AND userid=$user->id ORDER BY course") ) {

							$bodyhtml = '<head>';
							foreach ($CFG->stylesheets as $stylesheet) {
						        $bodyhtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
						    }

						    $bodyhtml .= '</head>';
	    					$bodyhtml .= "\n<body id=\"email\">\n\n";


							$bodyhtml .= '<div class="content">'.get_string('listmails', 'block_email_list').": </div>\n\n";
							$body = get_string('listmails', 'block_email_list')  .": \n\n";

							$bodyhtml .= '<table border="0" cellpadding="3" cellspacing="0">';
							$bodyhtml .= '<th class="header">'.get_string('course').'</th>';
							$bodyhtml .= '<th class="header">'.get_string('subject','block_email_list').'</th>';
							$bodyhtml .= '<th class="header">'.get_string('from','block_email_list').'</th>';
							$bodyhtml .= '<th class="header">'.get_string('date','block_email_list').'</th>';

							// Prepare messagetext
							foreach ( $mails as $mail ) {

								// Get folder
								$folder = email_get_root_folder($mail->userid, EMAIL_SENDBOX);
								if ( ! email_isfolder_type($folder, EMAIL_SENDBOX) ) {
									continue;
								}

								if ( isset($mail->mailid) ) {
									$message = get_record('email_mail', 'id', $mail->mailid);
									$mailcourse = get_record('course', 'id', $mail->course);

									$body .= "---------------------------------------------------------------------\n";
									$body .= get_string('course').": $mailcourse->fullname \n";
									$body .= get_string('subject','block_email_list').": $message->subject \n";
									$body .= get_string('from', 'block_email_list').": ".fullname(email_get_user($message->id));
									$body .= " - ".userdate($message->timecreated)."\n";
									$body .= "---------------------------------------------------------------------\n\n";


									$bodyhtml .= '<tr  class="r0">';
									$bodyhtml .= '<td class="cell c0">'.$mailcourse->fullname .'</td>';
									$bodyhtml .= '<td class="cell c0">'.$message->subject .'</td>';
									$bodyhtml .= '<td class="cell c0">'.fullname(email_get_user($message->id)).'</td>';
									$bodyhtml .= '<td class="cell c0">'.userdate($message->timecreated).'</td>';
									$bodyhtml .= '</tr>';
								}
							}

							$bodyhtml .= '</table>';
							$bodyhtml .= '</body>';

							$body .= "\n\n\n\n";

							email_to_user($user, get_string('emailalert', 'block_email_list'),
											get_string('emailalert', 'block_email_list').': '.get_string('newmails', 'block_email_list'),
											$body, $bodyhtml);
						}
					}
				}

			}

    		return true;
		} else {
			mtrace('FATAL ERROR: I couldn\'t read eMail list block');
			return false;
		}
    }

    function backuprestore_instancedata_used() {
        return true;
    }
    /**
     * Backup emails
     *
     * @return boolean
     **/
    function instance_backup($bf, $preferences) {

        global $CFG;

        $status = true;

        if ($preferences->backup_users == 0 or $preferences->backup_users == 1) {

            require_once("$CFG->dirroot/blocks/email_list/email/backuplib.php");

            //are there any emails to backup?
            $courseid = $this->instance->pageid;

            $status = email_backup_instance($bf, $preferences, $courseid);

        }
        return $status;
    }

    /**
     * Restore routine
     *
     * @return boolean
     **/
    function instance_restore($restore, $data) {

        $status = true;

        if ($restore->users != 0 and $restore->users != 1) {
            return $status;
        }

        global $CFG;

        require_once("$CFG->dirroot/blocks/email_list/email/restorelib.php");

        $status = email_restore_instance($data, $restore);

        return $status;
    }
}
?>
