<?php  
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: submissions.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
	
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");


    global $CFG,$USER;
    
    $cmid   = optional_param('cmid', 0, PARAM_INT);          // Course module ID

    if (!isset($submissionsinstance)){
        require_once ("submissions.class.php"); 
        $submissionsinstance = new submissions($cmid);     
    }
    require_login($submissionsinstance->course->id, false, $submissionsinstance->cm);
    require_capability('mod/webquestscorm:grade', $submissionsinstance->context);
   

    $subelement = optional_param('subelement', 'all', PARAM_ALPHA);  // What mode are we in?
    $mode = optional_param('mode', $subelement, PARAM_ALPHA);  // What mode are we in?

         
    if ($form = data_submitted()){
        if ($form->tabs == 'required') {
            webquestscorm_print_header($submissionsinstance->wqname, 'uploadTasks', $submissionsinstance->course,  $submissionsinstance->cm);
        }
    }

		switch ($mode) {
        case 'grade':                         // We are in a popup window grading
            if ($submission = $submissionsinstance->process_feedback()) {
                //IE needs proper header with encoding
                print_header(get_string('feedback', 'webquestscorm').':'.format_string($submissionsinstance->wqname));
                print_heading(get_string('changessaved'));
                print $submissionsinstance->update_main_listing($submission);
            }
                
            close_window();
            break;

        case 'single':                        // We are in a popup window displaying submission
            $submissionsinstance->display_submission();
            break;

        case 'all':                          // Main window, display everything
            $submissionsinstance->display_submissions();
            break;

        case 'fastgrade':
            ///do the fast grading stuff
            $grading    = false;
            $commenting = false;
            $col        = false;
            if (isset($_POST['submissioncomment'])) {
                $col = 'submissioncomment';
                $commenting = true;
            }
            if (isset($_POST['menu'])) {
                $col = 'menu';
                $grading = true;
            }
            if (!$col) {
                //both submissioncomment and grade columns collapsed..
                $submissionsinstance->display_submissions();            
                break;
            }

            foreach ($_POST[$col] as $id => $unusedvalue){

                $id = (int)$id; //clean parameter name
                if (!$submission = $submissionsinstance->get_submission($id)) {
                    $submission = $submissionsinstance->prepare_new_submission($id);
                    $newsubmission = true;
                } else {
                    $newsubmission = false;
                }
                unset($submission->data1);  // Don't need to update this.
                unset($submission->data2);  // Don't need to update this.
                //for fast grade, we need to check if any changes take place
                $updatedb = false;

                if ($grading) {
                    $grade = $_POST['menu'][$id];
                    $updatedb = $updatedb || ($submission->grade != $grade);
                    $submission->grade = $grade;
                } else {
                    if (!$newsubmission) {
                        unset($submission->grade);  // Don't need to update this.
                    }
                }
                if ($commenting) {
                    $commentvalue = trim($_POST['submissioncomment'][$id]);
                    $updatedb = $updatedb || ($submission->submissioncomment != stripslashes($commentvalue));
                    $submission->submissioncomment = $commentvalue;
                } else {
                    unset($submission->submissioncomment);  // Don't need to update this.
                }

                $submission->teacher    = $USER->id;
                $submission->mailed     = $updatedb?0:$submission->mailed;//only change if it's an update
                $submission->timemarked = time();

                //if it is not an update, we don't change the last modified time etc.
                //this will also not write into database if no submissioncomment and grade is entered.
		
                if ($updatedb){
			
                    if ($newsubmission) {
                        if (!insert_record('webquestscorm_submissions', $submission)) {
                            return false;
                        }else{
			    if ($CFG->version > 2007101500){ 
				update_grade_for_webquestscorm($webquestscorm);
			     }else{
	   			error($CFG->version);	
	  		     }

			}
                    } else {
                        if (!update_record('webquestscorm_submissions', $submission)) {
                            return false;
                        }else{
			   if ($CFG->version > 2007101500){ 
				update_grade_for_webquestscorm($webquestscorm);
			   }else{
	   			error($CFG->version);
			
	   		   }

			}

                    }       
                    //add to log only if updating 
                    add_to_log($submissionsinstance->course->id, 'webquestscorm', 'update grades', 
                               'editsubmissions.php?cmid='.$submissionsinstance->cm->id.'&user='.$submission->userid.'&element=uploadedTasks&subelement=all',
                               $submission->userid, $submissionsinstance->cm->id);          
                              
                }
                      
            } 
            print_heading(get_string('changessaved'));
            $submissionsinstance->display_submissions();
            break;

        case 'next':
            /// We are currently in pop up, but we want to skip to next one without saving.
            ///    This turns out to be similar to a single case
            /// The URL used is for the next submission.
            $submissionsinstance->display_submission();
            break;
                
        case 'saveandnext':
            ///We are in pop up. save the current one and go to the next one.
            //first we save the current changes
            if ($submission = $submissionsinstance->process_feedback()) {
                //print_heading(get_string('changessaved'));
                $extra_javascript = $submissionsinstance->update_main_listing($submission);
            }
                
            //then we display the next submission
            $submissionsinstance->display_submission($extra_javascript);
            break;
            
        default:
            echo "something seriously is wrong!!";
            break;                    
    }
?>
