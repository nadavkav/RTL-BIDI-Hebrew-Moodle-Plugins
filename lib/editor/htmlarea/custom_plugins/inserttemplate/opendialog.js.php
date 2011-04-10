<?php require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

//include $CFG->dirroot."/lib/editor/htmlarea/custom_plugins/cellwidth/resizable-tables.js";

?>

function __inserttemplate (editor) {

    // Make sure that editor has focus
    editor.focusEditor();
    var sel = editor._getSelection();
    var range = editor._createRange(sel);

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/inserttemplate/dialog.php?id=$courseid" ?>",600,500, function (param) {

      if(!param) {
          return false;
      }
      var doc = editor._doc;
      // create the DIV element  to hold the HTML TEMPLATE inside of it
      newDiv = doc.createElement("div");

      // assign the given arguments
      for (var field in param) {
          var value = param[field];
          if (!value) {
              continue;
          }
          switch (field) {
              case "template"   : newDiv.innerHTML = value; break;
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


