<?php require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

 ?>

function __bigger (editor) {

    if (editor.config.height == "500px") {
      editor.config.height = "200px";
      editor._iframe.style.height = "200px";
      editor._textArea.style.height = "200px";

    } else {
      editor.config.height = "500px";
      editor._iframe.style.height = "500px";
      editor._textArea.style.height = "500px";

    }
}