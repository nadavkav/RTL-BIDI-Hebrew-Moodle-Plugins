<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 9:32 PM
 *
 * Description:
 *  Enable users, especially, with ROLE Students, to Drag and Drop files (images)
 *  and upload them immidiatly inside the HTMLAREA editor as IMG elements
 *  (images are saved on a course level, in a special "users" folder with each user's ID)
 *
 *  based on: standupweb mootools homebrews (http://mootools.standupweb.net/dragndrop.php)
 */

  require_once("../../../../../config.php");

  $courseid = optional_param('id', SITEID, PARAM_INT);

  global $USER;

?>

function __dragdropimage (editor) {

    nbDialog("<?php
    if(true or !empty($courseid) and has_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $courseid)) ) {
        echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/dragdropimage/dialog.php?courseid=$courseid&userid=$USER->id";
    } else {
        //echo "insert_swf.php?id=$id";
    }?>" ,600,300, function (param) {

        if (!param) {   // user must have pressed Cancel
            return false;
        }
        imagelist ='';
        //for (i=0; i<param.length;i++) {
        for (i in param) {
          imagelist = imagelist + param[i] + "<br/>";
        }
        var span = editor._doc.createElement("span");
        span.innerHTML = imagelist;
        editor.insertNodeAtSelection(span);
        return true;
        
     });
}