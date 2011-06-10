<?php require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

//include $CFG->dirroot."/lib/editor/htmlarea/custom_plugins/cellwidth/resizable-tables.js";

?>

var selectedlink = null;

function __activitylink (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);

    selectedlink = range;

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/activitylink/dialog.php?id=$courseid" ?>",300,220, function (param) {

      if(!param) {
          return false;
      }
      var doc = editor._doc;
      // create the DIV element  to hold the HTML TEMPLATE inside of it
      newDiv = doc.createElement("span");

      // assign the given arguments
      for (var field in param) {
          var value = param[field];
          if (!value) {
              continue;
          }
          switch (field) {
              case "link"   : newDiv.innerHTML = value; break;
          }
      }

      if (HTMLArea.is_ie) {
          range.pasteHTML(newDiv.outerHTML);
      } else {
          // insert the table
          editor.insertNodeAtSelection(newDiv);
      }
      editor.forceRedraw();
      return true;

    });


}


