<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 9:32 PM
 *
 * Description:
 *  Advanced editing using TinyMCE editor
 */

require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>
var parent_editor = null;

function __tinymce (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);

    parent_editor = editor._doc;

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/tinymce/dialog.php?id=$courseid"; ?>" ,880,660, function (param) {

        if (!param) {   // user must have pressed Cancel
            return false;
        }
        var doc = editor._doc;
        doc.body.innerHTML = param['tinymceeditor'];
        return true;

/*
        // create the DIV element  to hold the OBJECT embed inside of it
        newDiv = doc.createElement("div");

        // assign the given arguments
        for (var field in param) {
            var value = param[field];
            if (!value) {
                continue;
            }

            switch (field) {
                case "tinymceeditor"   : newDiv.innerHTML = value ;
                break;
            }
        }
        if (HTMLArea.is_ie) {
            range.pasteHTML(newDiv.outerHTML);
        } else {
            editor.insertNodeAtSelection(newDiv);
        }
        //editor.insertHtml(newDiv);
        //editor.forceRedraw();
        return true;
*/
    });
}