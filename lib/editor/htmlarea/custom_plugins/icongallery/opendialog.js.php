<?php require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __icongallery (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/icongallery/dialog.php?id=$courseid" ?>",500,400, function (param) {

        if(!param) {
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