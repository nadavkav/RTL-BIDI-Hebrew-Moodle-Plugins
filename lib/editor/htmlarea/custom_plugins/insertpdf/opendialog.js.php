<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 9:32 PM
 *
 * Description:
 *  Insert an EMBED of a PDF document into the HTML
 */

  require_once("../../../../../config.php");

  $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __insertpdf (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);
    nbDialog("<?php
    if(true or !empty($courseid) and has_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $courseid)) ) {
        echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/insertpdf/dialog.php?id=$courseid";
    } else {
        //echo "insert_swf.php?id=$id";
    }?>" ,750,560, function (param) {

        if (!param) {   // user must have pressed Cancel
            return false;
        }

        if (HTMLArea.is_ie){

            range.pasteHTML("<embed src='" + param.f_url + "' \
                                    alt='" + param.f_alt + "' \
                                    width='" + param.f_width +"' \
                                    height='" + param.f_height + "'></embed>");

        } else {
                // MOODLE HACK: startContainer.perviousSibling
                // Doesn't work so we'll use createElement and
                // insertNodeAtSelection
                //img = range.startContainer.previousSibling;
                var img = editor._doc.createElement("embed");

                img.setAttribute("src",""+ param.f_url +"");
                img.setAttribute("alt",""+ param.f_alt +"");
                img.setAttribute("width",""+ param.f_width +"");
                img.setAttribute("height",""+ param.f_height +"");
                editor.insertNodeAtSelection(img);
            }
    });
}