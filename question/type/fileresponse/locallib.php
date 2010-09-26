<?php
/**
 * Function library for the fileresponse question type
 *
 * @copyright &copy; 2007 Adriane Boyd
 * @author Adriane Boyd adrianeboyd@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_fileresponse
 *
 */

/**
 * Uploads the file submitted (adapted from mod/workshop/submissions.php)
 *
 * @param string $fileid string corresponding to the input file ('resp##_file')
 * @param int $attemptid attempt id
 * @param int $questionid question id
 * @param int $maxbytes maximum upload size in bytes
 * @return string feedback from upload, related to success or failure
 */
function upload_response($fileid, $course, $attemptid, $questionid, $maxbytes) {
    global $CFG;

    require_once($CFG->dirroot.'/lib/uploadlib.php');
    $um = new upload_manager($fileid,true,false,$course,true,$maxbytes,true);
    if ($um->preprocess_files()) {
        $dir = quiz_file_area_name($attemptid, $questionid);
        if(!quiz_file_area($dir)) {
            return get_string('uploadproblem');
        }

        if($um->save_files($dir)) {
            return get_string('uploadedfile');
        }
    }

    return get_string('uploaderror', 'qtype_fileresponse');
}

/**
 * Creates a directory file name, suitable for make_upload_directory()
 *
 * @param int $attemptid attempt id
 * @param int $questionid question id
 * @return string path to file area
 */
function quiz_file_area_name($attemptid, $questionid) {
    global $CFG, $USER;

    if ($attemptid == 0) {
        $attemptid = $attemptid.'_'.$USER->id;
    }

    return 'questionattempt/'.$attemptid.'/'.$questionid;
}

/**
 * Makes an upload directory
 *
 * @param string $dir path to file area
 * @return string path to file area
 */
function quiz_file_area($dir) {
    return make_upload_directory($dir);
}

/**
 * Returns the HTML to display the file submitted by the student
 * (adapted from mod/assignment/lib.php)
 *
 * @param int $attemptid attempt id
 * @param int $questionid question id
 * @return string string to print to display file list
 */
function get_student_answer($attemptid, $questionid){
    global $CFG;

    $filearea = quiz_file_area_name($attemptid, $questionid);

    $output = '';

    if ($basedir = quiz_file_area($filearea)) {

        if ($files = get_directory_list($basedir)) {
            if (count($files) == 0) {
                return false;
            }
            foreach ($files as $key => $file) {
                require_once($CFG->libdir.'/filelib.php');

                $icon = mimeinfo('icon', $file);

                // remove "questionattempt", the first dir in the path, from the URL
                $filearealist = explode('/', $filearea);
                $fileareaurl = implode('/', array_slice($filearealist, 1));

                if ($CFG->slasharguments) {
                    $ffurl = "$CFG->wwwroot/question/file.php/$fileareaurl/$file";
                } else {
                    $ffurl = "$CFG->wwwroot/question/file.php?file=/$fileareaurl/$file";
                }

                $output .= '<img align="middle" src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                        '<a href="'.$ffurl.'" >'.$file.'</a><br />';
            }
        }
    }

    // (Is this desired for theme reasons?)
    //$output = '<div class="files">'.$output.'</div>';

    return $output;
}
?>
