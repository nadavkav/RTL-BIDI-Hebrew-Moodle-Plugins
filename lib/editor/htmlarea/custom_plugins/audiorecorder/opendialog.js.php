<?php
/**
 * Created by Nadavk Kavalerchik.
 * Email: nadavkav@gmail.com
 * Date: 1/10/11
 * Time: 5:14 PM
 * Record Audio and save it (WAV format) inside the course (or user's folder)
 */

  require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __audiorecorder (editor) {

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/audiorecorder/dialog.php?id=$courseid" ?>",550,500, function (param) {

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
                case "audiofile"   : newDiv.innerHTML = value; break;
                case "audioplayer" : newDiv.innerHTML = value; break;
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