<?php 
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 8/6/11 
 *
 * Description:
 *	Clear redundant HTML TAGs and code, which distorts the HTML code and the visual display of the content
 *  (Usually, when coping content from MS Word(tm) documents)
 */

  require_once("../../../../../config.php");

  $courseid = optional_param('id', SITEID, PARAM_INT);

?>

var htmlarea_body = null;

function __purifyhtml (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);

	//	sel.removeAllRanges();
	//	range.selectNodeContents(editor._doc.body);
	//	sel.addRange(range);

	htmlarea_body = editor._doc.body;

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/purifyhtml/dialog.php?id=$courseid" ?>",700,400, function (param) {

        if (!param) {   // user must have pressed Cancel
            return false;
        }

        // assign the given arguments
        for (var field in param) {
            var value = param[field];
            if (!value) {
                continue;
            }
            switch (field) {
                case "purifyhtml" : editor._doc.body.innerHTML = value; break;
            }
        }
        return true;

    });

}
