<?php
/**
 * The question type class for the imagedit question type.
 *
 * The imagedit question type allows a student to edit a copy of the teacher's image
 * as the answer to a question.  The file is uploaded to a specific directory inside
 * the course's tree. each attempt gets a new image. images are stored in a user's directory
 * specified by:
 *
 * {$CFG->dataroot}/{$_GET['courseid']}/users/{$USER->id}/question{$_GET['qid']}_qatt{$_GET['qatt']}_{$filename}
 *
 * Once a file has been uploaded, the student can not submit again to replace
 * the file, and he/she cannot delete the file.
 *
 * @author Nadav Kavalerchik
 * based on the work of : Adriane Boyd (adrianeboyd@gmail.com) with fileresponse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_imagedit
 *
 */

// include library routines for this question type
//require_once(dirname(__FILE__) . '/locallib.php');

class imagedit_qtype extends default_questiontype {

    function name() {
        return 'imagedit';
    }

    function is_manual_graded() {
        return true;
    }

    function is_usable_by_random() {
        return false;
    }

    /**
    * Loads the question type specific options for the question.
    *
    * @return boolean to indicate success or failure
    */
    function get_question_options(&$question) {
        // Get additional information from database
        // and attach it to the question object
        if (!$question->options = get_record('question_imagedit', 'question', $question->id)) {
            notify('Error: Missing question options!');
            return false;
        }

	// Get data from question_answers (for feedback)
	parent::get_question_options($question);

        return true;
    }

    /**
     * Save the units and the answers associated with this question.
     * @return boolean to indicate success or failure.
     */
    function save_question_options($question) {
        // Save question options in question_answers
        $result = true;
        $update = true;
        $answer = get_record("question_answers", "question", $question->id);
        if (!$answer) {
            $answer = new stdClass;
            $answer->question = $question->id;
            $update = false;
        }
        $answer->answer   = $question->feedback;
        $answer->imgurl   = $question->imgurl;
        $answer->feedback = $question->feedback;
        $answer->fraction = $question->fraction;
        if ($update) {
            if (!update_record("question_answers", $answer)) {
                $result = new stdClass;
                $result->error = "Could not update quiz answer!";
            }
        } else {
            if (!$answer->id = insert_record("question_answers", $answer)) {
                $result = new stdClass;
                $result->error = "Could not insert quiz answer!";
            }
        }

	    // If the previous step succeeded, save question options in
	    // question_imagedit table
        if ($result) {
            if ($options = get_record("question_imagedit", "question", $question->id)) {
                $options->maxbytes = $question->maxbytes;
                $options->imgurl   = $question->imgurl;
                $options->essay = $question->essay;
                if (!update_record("question_imagedit", $options)) {
                    $result = new stdClass;
                    $result->error = "Could not update quiz imagedit options! (id=$options->id)";
                }
            } else {
                unset($options);
                $options->question = $question->id;
                $options->maxbytes = $question->maxbytes;
                $options->imgurl   = $question->imgurl;
                $options->essay = $question->essay;
                if (!insert_record("question_imagedit", $options)) {
                    $result = new stdClass;
                    $result->error = "Could not insert quiz imagedit options!";
                }
            }
        }

        return $result;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success or failure.
     */
    function delete_question($questionid) {
        delete_records("question_imagedit", "question", $questionid);
        return true;
    }

   /**
    * Deletes files submitted in these states
    *
    * @param string $stateslist  Comma separated list of state ids to be deleted
    * @return boolean to indicate success or failure.
    */
    function delete_states($stateslist) {
        global $CFG;

        $states = explode(',', $stateslist);
        foreach ($states as $stateid) {
            $state = get_record("question_states", "id", "$stateid");
            $attemptid = $state->attempt;
            $questionid = $state->question;

            // delete any files submitted in this attempt
            $dir = quiz_file_area_name($attemptid, $questionid);
            if (file_exists($CFG->dataroot.'/'.$dir)) {
                fulldelete($CFG->dataroot.'/'.$dir);
                $dirparts = explode('/', $dir);

                // the directory is two levels deep, so delete the lower directory, too
                array_pop($dirparts);
                $dir = implode('/', $dirparts);
                fulldelete($CFG->dataroot.'/'.$dir);
            }
        }
        return true;
    }

    /**
     * Format question display
     */

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;
        require_once($CFG->libdir.'/formslib.php');

        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
        static $htmleditorused = false;

        // Print formulation
        $questiontext = $this->format_text($question->questiontext, $question->questiontextformat, $cmoptions);
        $image = get_question_image($question);
        $maxbytes = $question->options->maxbytes;
        //$imgurl = $question->options->imgurl;
        $showessay = $question->options->essay;

        $answers = &$question->options->answers;
        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';

        // Only use the rich text editor for the first essay question on a page.
        $usehtmleditor = can_use_html_editor() && !$htmleditorused;

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;

        $inputname = $question->name_prefix;

        /// set question text and media
        $questiontext = format_text($question->questiontext,
                                   $question->questiontextformat,
                                   $formatoptions, $cmoptions->course);

        // feedback handling
        $feedback = '';
        if ($options->feedback && !empty($answers)) {
            foreach ($answers as $answer) {
                $feedback = format_text($answer->feedback, '', $formatoptions, $cmoptions->course);
            }
        }

        // get essay response value
        if (isset($state->responses[''])) {
            $answers = explode(',',$state->responses['']);
            $value = stripslashes_safe($answers[0]);
            $imgurl = $answers[1]; // get image from the answers array (textarea)
        } else {
            $value = "";
            //$imgurl = null;
        }

        // essay answer
        $answer = '';
        if ($showessay) {
            if (empty($options->readonly)) {
                // the student needs to type in their answer so print out a text editor
                $answer = print_textarea($usehtmleditor, 18, 80, 630, 400, $inputname, $value, $cmoptions->course, true);
            } else {
                // it is read only, so just format the students answer and output it
                $safeformatoptions = new stdClass;
                $safeformatoptions->para = false;
                $answer = format_text($value, FORMAT_MOODLE,
                                  $safeformatoptions, $cmoptions->course);
            }
        }

        // set the file input form
        $struploadform = upload_print_form_fragment(1, array($question->name_prefix . "file"), null, false, null, 0, $maxbytes, true);

        // set file upload feedback and display of uploaded file
        $uploadfeedback = '';
        if (isset($state->uploadfeedback)) {
            $uploadfeedback = $state->uploadfeedback;
        }

        $struploadedfile = '';
        // no need to display files. file upload was disabled // (nadavkav)
        //$currentfile = get_student_answer($state->attempt, $question->id);

        if ($currentfile) {
            $struploadedfile = get_string('answer', 'quiz').': '.$currentfile;
        }

        // string prompts for form
        if ($currentfile) {
            $struploadfile = get_string('uploadnew', 'qtype_imagedit');
        } else {
            $struploadfile = get_string('uploadafile');
        }

        $strmaxsize = get_string('maximumupload').' '. display_size($maxbytes);

        include("$CFG->dirroot/question/type/imagedit/display.html");

        if ($usehtmleditor) {
            use_html_editor($inputname);
            $htmleditorused = true;
        }
    }

    /**
     * Upload the file and prepare for manual grading
     */
    function grade_responses(&$question, &$state, $cmoptions) {
        global $CFG;

        $state->raw_grade = 0;
        $state->penalty = 0;

        // no need for file uploads (it is an image editor question type) // (nadavkav)
        //$state->uploadfeedback = imagedit_upload_response($question->name_prefix.'file', $cmoptions->course, $state->attempt, $question->id, $question->options->maxbytes);

        $state->responses[''] .= ",".$_POST['imgurl']; // add image to the response array //(nadavkav)

        if ($question->options->essay) {
            clean_param($state->responses[''], PARAM_CLEANHTML);
        }

        return true;
    }

    /**
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf, $preferences, $question, $level=6) {
        $status = true;
        $fileresponses = get_records("question_imagedit", "question", $question, "id ASC");
        // If there is a question
        if ($fileresponses) {
            // Iterate over each
            foreach ($fileresponses as $fileresponse) {
                $status = $status && fwrite($bf, start_tag("FILERESPONSE", $level, true));
                // Print contents
                $status = $status && fwrite($bf, full_tag("MAXBYTES", $level+1, false,$fileresponse->maxbytes));
                $status = $status && fwrite($bf, full_tag("ESSAY", $level+1, false, $fileresponse->essay));
                $status = $status && fwrite($bf, end_tag("FILERESPONSE", $level, true));
            }

            // Now print question_answers
            $status = $status && question_backup_answers($bf, $preferences, $question, $level);
        }
        return $status;
    }

    /**
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($oldquestion, $newquestion, $info, $restore) {
        $status = true;

        // Get the fileresponses array
        $fileresponses = $info['#']['FILERESPONSE'];

        // Iterate over fileresponses
        for($i = 0; $i < sizeof($fileresponses); $i++) {
            $frinfo = $fileresponses[$i];

            // Now, build the question_fileresponse record structure
            $fileresponse = new stdClass;
            $fileresponse->question = $newquestion;
            $fileresponse->maxbytes = backup_todb($frinfo['#']['MAXBYTES']['0']['#']);
            $fileresponse->essay = backup_todb($frinfo['#']['ESSAY']['0']['#']);

            // The structure is equal to the db, so insert the question_fileresponse
            $newid = insert_record("question_imagedit", $fileresponse);

            // Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }

        return $status;
    }
}

// Register this question type with the system.
question_register_questiontype(new imagedit_qtype());
?>
