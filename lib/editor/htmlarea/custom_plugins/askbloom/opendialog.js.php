<?php require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

 ?>

function __askbloom (editor) {

    nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/askbloom/dialog.php?id=$courseid" ?>",1024,768, function (param) {

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
                case "objective"   : newDiv.innerHTML = value; break;
            }
        }

        // add the newly created element and it's content into the DOM
        //my_div = document.getElementById("org_div1");
        //document.body.insertBefore(newDiv, my_div);

        if (HTMLArea.is_ie) {
            range.pasteHTML(newDiv.outerHTML);
        } else {
            // insert the table
            editor.insertNodeAtSelection(newDiv);
        }
        return true;

    });
}