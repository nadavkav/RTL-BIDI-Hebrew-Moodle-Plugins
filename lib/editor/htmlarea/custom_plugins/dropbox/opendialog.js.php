<?php
/**
 * Created by Nadavk Kavalerchik.
 * Email: nadavkav@gmail.com
 * Date: 1/29/11
 *
 * Enable sharing the User's Public files from his Dropbox account
 */

require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __dropbox (editor) {

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/dropbox/dialog.php?id=$courseid" ?>",800,500, function (param) {

        if (!param) {   // user must have pressed Cancel
            return false;
        }
        var doc = editor._doc;
        // create the DIV element  to hold the OBJECT embed inside of it
        newDiv = doc.createElement("div");

        // assign the given arguments
        var linklist = '';
        for (var field in param) {
            var value = param[field];
            if (!value) {
                continue;
            }
            //switch (field) {
            //    case "filelink"   : newDiv.innerHTML = value; break;
            //}
            linklist = linklist + value + "<br/>";
        }
        newDiv.innerHTML = linklist;

        if (HTMLArea.is_ie) {
            range.pasteHTML(newDiv.outerHTML);
        } else {
            // insert the table
            editor.insertNodeAtSelection(newDiv);
        }
        return true;

    });

}