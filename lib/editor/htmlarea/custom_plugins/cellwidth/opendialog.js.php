<?php require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

//include $CFG->dirroot."/lib/editor/htmlarea/custom_plugins/cellwidth/resizable-tables.js";

?>

function __cellwidth (editor) {

    // Make sure that editor has focus
    //editor.focusEditor();
    //var sel = editor._getSelection();
    //var range = editor._createRange(sel);

/*  // might be nice to have this "Resizble" demo work
    // http://bz.var.ru/comp/web/resizable.html

    var tagName = "table";
    var ancestors = editor.getAllAncestors();
    var ret = null;
    tagName = ("" + tagName).toLowerCase();
    for (var i in ancestors) {
        var el = ancestors[i];
        if (el.tagName.toLowerCase() == tagName) {
            ret = el;
            break;
        }
    }
    ret.className = "resizable";
    ResizableColumns(editor);
    
*/
    // get current cell element
    var tagName = "td";
    var ancestors = editor.getAllAncestors();
    var ret = null;
    tagName = ("" + tagName).toLowerCase();
    for (var i in ancestors) {
        var el = ancestors[i];
        if (el.tagName.toLowerCase() == tagName) {
            ret = el;
            break;
        }
    }

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/cellwidth/dialog.php?id=$courseid&cellwidth=" ?>"+ret.width,400,250, function (param) {

        if(!param) {
            return false;
        }
        if (HTMLArea.is_ie) {
            range.pasteHTML(param);
        } else {
            //editor.insertHTML(param['cellwidth']);
            //ret.width = param['cellwidth'];
            // get tbody of cell
            var tablebody = ret.parentNode.parentNode;
            // set width of all cells in the column
            for (var i =0; i<tablebody.rows.length; i++){
              tablebody.rows[i].cells[ret.cellIndex].width = param['cellwidth'];
            }
        }
        return true;
    });


}


