<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 10:53 PM
 *
 * Description:
 *    	wrapper for the DRAGMATH plugin
 *      http://docs.moodle.org/en/DragMath_equation_editor
 */

require_once("../../../../../config.php");

$courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __dragmath (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);
    var formula = new String(range);
    //formula.replace('`','');

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/dragmath/dialog.php?id=$courseid&exp="; ?>"+encodeURIComponent(formula) ,570,420, function (param) {

    if (!param) {   // user must have pressed Cancel
        return false;
    }

    if (HTMLArea.is_ie) {
        range.pasteHTML(param);
    } else {
        editor.insertHTML(param);
    }
    return true;

    });

}