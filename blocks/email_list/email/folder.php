<?php
/**
 * This page recive an actions for folder's
 *
 * @uses $CFG, $COURSE
 * @author Toni Mas
 * @version 1.0
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 **/

	global $CFG, $COURSE;

	require_once( "../../../config.php" );
	require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');
	require_once($CFG->dirroot.'/blocks/email_list/email/folder_form.php');

	$id 		= optional_param('id', 0, PARAM_INT); 				// Folder Id.
	$courseid 	= optional_param('course', SITEID, PARAM_INT);     // Course ID
	$action 	= optional_param('action', '', PARAM_ALPHANUM);	// Action

	// If defined course to view
    if (! $course = get_record('course', 'id', $courseid)) {
    	print_error('invalidcourseid', 'block_email_list');
    }

    // Options for new mail and new folder
	$options = new stdClass();
	$options->id = $id;
	$options->course = $courseid;

    $preferencesbutton = email_get_preferences_button($courseid);

    $stremail  = get_string('name', 'block_email_list');

    if ( function_exists( 'build_navigation') ) {
    	// Prepare navlinks
    	$navlinks = array();
    	$navlinks[] = array('name' => get_string('nameplural', 'block_email_list'), 'link' => 'index.php?id='.$course->id, 'type' => 'misc');
    	$navlinks[] = array('name' => get_string('name', 'block_email_list'), 'link' => null, 'type' => 'misc');

		// Build navigation
		$navigation = build_navigation($navlinks);

		print_header("$course->shortname: $stremail", "$course->fullname",
    	             $navigation,
    	              "", '<link type="text/css" href="email.css" rel="stylesheet" /><link type="text/css" href="treemenu.css" rel="stylesheet" /><link type="text/css" href="tree.css" rel="stylesheet" /><script type="text/javascript" src="treemenu.js"></script><script type="text/javascript" src="email.js"></script>',
    	              true, $preferencesbutton);
    } else {
    	$navigation = '';
		if ( isset($course) ) {
	    	if ($course->category) {
	    	    $navigation = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> ->';
	    	}
		}

		$stremails = get_string('nameplural', 'block_email_list');

    	print_header("$course->shortname: $stremail", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$stremails</a> -> $stremail",
                  "", '<link type="text/css" href="email.css" rel="stylesheet" /><link type="text/css" href="treemenu.css" rel="stylesheet" /><link type="text/css" href="tree.css" rel="stylesheet" /><script type="text/javascript" src="treemenu.js"></script><script type="text/javascript" src="email.js"></script>',
                  true, $preferencesbutton);
    }


	// Print principal table. This have 2 columns . . .  and possibility to add right column.
	echo '<table id="layout-table">
  			<tr>';


	// Print "blocks" of this account
	echo '<td style="width: 180px;" id="left-column">';
	email_printblocks($USER->id, $courseid);

	// Close left column
	echo '</td>';

	// Print principal column
	echo '<td id="middle-column">';

	// Print block
    print_heading_block('');

    echo '<div>&#160;</div>';

    if ( isset($folderid) ) {
    	if (! $folder = get_record('email_folder', 'id', $folderid) ) {
			print_error( 'failgetfolder', 'block_email_list');
		}
    }

	require_login($course->id, false);

	if ($course->id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);   // SYSTEM context
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);   // Course context
    }

	switch ( $action ) {
		case md5('admin'):
			$hassubfolders = email_print_administration_folders($options);

			if ( ! $hassubfolders  ) {

				// Can create subfolders?
				if ( ! has_capability('block/email_list:createfolder', $context)) {
					print_error('forbiddencreatefolder', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$course->id);
				}

				// Print form to new folder
				notify( get_string ('nosubfolders', 'block_email_list') );
				$mform = new folder_form('folder.php', array('id' =>$id, 'course' => $courseid, 'action' => ''));
				$mform->display();
			}

			break;
		case 'cleantrash':
			$trash = email_get_root_folder($USER->id, EMAIL_TRASH);

			/// If necessary, delete mail and delete attachments
			$options->folderid = $trash->id;

			$success = true;

			$mails = email_get_mails($USER->id, $course->id, NULL, '', '', $options);

			// Delete reference mails
			if (! delete_records('email_foldermail', 'folderid', $trash->id)) {
			   	$success = false;
			}

			// Get all trash mails
			if ( $mails ) {
				foreach( $mails as $mail ) {

					// if mailid exist, continue ...
					if ( get_records('email_foldermail', 'mailid', $mail->id) ) {
						continue;
					} else {
						// Mail is not reference by never folder (not possibility readed)
						if ( email_delete_attachments($mail->id) and delete_records('email_mail', 'id', $mail->id) ) {
							$success = true;
						}
					}

				}
			}


			$url = email_build_url($options);

			// Notify
			if ( $success ) {
		    	notify( get_string('cleantrashok', 'block_email_list') );
			} else {
				notify( get_string('cleantrashfail', 'block_email_list') );
			}

			$options->folderid = $id;
			$options->folderoldid = 0;
			email_showmails($USER->id, '', 0, 10, $options);

			break;

		case md5('edit'):

			// Can create subfolders?
			if ( ! has_capability('block/email_list:createfolder', $context) ) {
				print_error('forbiddencreatefolder', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$course->id);
			}

			$mform = new folder_form('folder.php', array('id' =>$id, 'action'=>$action, 'course' => $courseid));

			$folder = email_get_folder($id);
			$folder->foldercourse = $folder->course;
			unset($folder->course);
			$mform->set_data($folder);

			if ( $data = $mform->get_data() ) {
				$updatefolder = new stdClass();

				// Clean name
				$updatefolder->name = strip_tags($data->name);

				// Add user and course
				$updatefolder->userid = $USER->id;

				$updatefolder->course = $data->foldercourse;

				// Add id
				$updatefolder->id = $data->id;


				/// Update folder

				// Get old folder params
				if (! $oldfolder = get_record('email_folder', 'id', $data->id) ) {
					print_error('failgetfolder', 'block_email_list');
				}

				if ( $subfolder = email_is_subfolder($oldfolder->id) ) {

					// If user changed parent folder
					if ( $subfolder->folderparentid != $data->parentfolder ) {
						if (! set_field('email_subfolder', 'folderparentid', $data->parentfolder, 'id', $subfolder->id) ) {
						    	print_error('failchangingparentfolder', 'block_email_list');
						}
					}
				}

				// Unset parentfolder
				unset($data->parentfolder);

				if ( $preference = get_record('email_preference', 'userid', $USER->id) ) {
					if ( $preference->marriedfolders2courses ) {
						// Change on all subfolders if this course has changed.
						if ( $oldfolder->course != $data->foldercourse ) {
							if ( $subfolders = email_get_all_subfolders($data->id) ) {
								foreach ($subfolders as $subfolder0) {
									set_field('email_folder', 'course', $data->foldercourse, 'id', $subfolder0->id);
								}
							}
						}
					}
				}

				// Update record
				if (! update_record('email_folder', $updatefolder) ) {
				    	return false;
				}

				add_to_log($courseid, 'email', "update subfolder", 'folder.php?id='.$id, "$data->name", 0, $USER->id);

				notify(get_string('modifyfolderok', 'block_email_list'));

				email_print_administration_folders($options);
			} else {

				$mform->display();
			}

			break;

		case md5('remove'):

			email_removefolder($id, $options);

			email_print_administration_folders($options);

		default:

		// Can create subfolders?
		if ( ! has_capability('block/email_list:createfolder', $context) ) {
			print_error('forbiddencreatefolder', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$course->id);
		}

		$mform = new folder_form('folder.php', array('id' =>$id, 'course' => $courseid, 'action' => ''));

		// If the form is cancelled
		if ($mform->is_cancelled()) {

		redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid, get_string('foldercancelled', 'block_email_list'));

		// Get form sended
		} else if ( $form = $mform->get_data() ) {

			$foldernew = new stdClass();

			// Clean name
			$foldernew->name = strip_tags($form->name);

			// Add user and course
			$foldernew->userid = $USER->id;

			// Add courseid
			$foldernew->course = $form->foldercourse;

			// Apply this information
			$stralert = get_string('createfolderok', 'block_email_list');

			// Use this field, for known if folder exist o none
			if (! $form->oldname ) {
				// Add new folder
				if ( ! email_newfolder($foldernew, $form->parentfolder) ) {
					print_error('failcreatingfolder', 'block_email_list');
				}

			} else {

				$updatefolder = new stdClass();

				$updatefolder->id = $form->folderid;
				$updatefolder->name = $form->name;
				$updatefolder->parentfolder = $form->parentfolder;
				$updatefolder->course = $form->course;

				// If exist folderid (sending in form), set field
				if ( ! email_update_folder($updatefolder) ) {
					print_error('failupdatefolder', 'block_email_list');
				}

				// Apply this information
				$stralert = get_string('modifyfolderok', 'block_email_list');
			}

			redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid, $stralert, '3');

	    } else {

			// Set data
			if ( isset($folder) ) {
				$folder->oldname = $folder->name;
				$parentfolder = email_get_parent_folder($folder);
				$folder->parentfolder = $parentfolder->id;
				$folder->folderid = $folder->id;

				// FIX BUG: When update an folder, on this id has been put $COURSE->id
				$folder->id = $COURSE->id;

				$mform->set_data($folder);
			}

			$mform->display();
	    }
	}

    // Close principal column
	echo '</td>';

	// Close table
	echo '</tr>
			</table>';

	print_footer($course);

?>