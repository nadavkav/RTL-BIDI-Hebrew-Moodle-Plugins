<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 9:32 PM
 *
 * Description:
 *    Enable users to draw vector images using svg editor and display them on any browser
 *    (user must have modern browser that support svg, like Firefox, Chrome, Opera, Safari, IE9...)
 *  based on svg-edit (2.5.1) library and svgweb (2010-8-10) library.
 */

require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __drawsvg (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);
    var prefixwebsvg = '<script data-path="<?php echo $CFG->wwwroot.'/lib/editor/htmlarea/custom_plugins/drawsvg/lib/svgweb/src/'; ?>" src="<?php echo $CFG->wwwroot.'/lib/editor/htmlarea/custom_plugins/drawsvg/lib/svgweb/src/svg.js'; ?>"></script>';

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/drawsvg/dialog.php?id=$courseid"; ?>" ,1024,768, function (param) {

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
                case "svgcode"   : newDiv.innerHTML = prefixwebsvg + '<script type="image/svg+xml">' + value + '</script>'; break;
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
    });
}