<?php
/**
 * Created by Nadavk Kavalerchik.
 * Email: nadavkav@gmail.com
 * Date: 7/6/2011
 *
 * Description:
 * 	Record Video and save it (MP4 file format with h.264 Video track and AAC audio track) inside the course (or user's folder)
 *
 */


  require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __videorecorder (editor) {

	// Make sure that editor has focus
    editor.focusEditor();

	// Support for pasting the Content into IE7+
	var sel = editor._getSelection();
	var range = editor._createRange(sel);

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/videorecorder/dialog.php?id=$courseid" ?>",450,380, function (param) {

        if (!param) {   // user must have pressed Cancel
            return false;
        }
        var doc = editor._doc;
        // create the DIV element  to hold the OBJECT embed inside of it
        newDiv = doc.createElement("div");

        // assign the given arguments
        for (var field in param) {
            var value = param[field];
            if (!value) {
                continue;
            }
            switch (field) {
                case "videoplayer" : newDiv.innerHTML = value; break;
            }
        }

        if (HTMLArea.is_ie) {
            range.pasteHTML(newDiv.outerHTML);
        } else {
            // insert the table
            editor.insertNodeAtSelection(newDiv);
        }
        return true;

    });

}