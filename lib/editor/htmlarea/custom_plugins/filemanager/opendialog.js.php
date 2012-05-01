<?php
/**
 * Created by Nadavk Kavalerchik.
 * Email: nadavkav@gmail.com
 * Date: 1/10/11
 * Time: 5:14 PM
 * Get links to file from my File Manager block
 */

require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __filemanager (editor) {

	// Make sure that editor has focus
    editor.focusEditor();

	// Support for pasting the Content into IE7+
	var sel = editor._getSelection();
	var range = editor._createRange(sel);

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/filemanager/dialog.php?id=$courseid" ?>",1024,768, function (param) {

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