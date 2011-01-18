<?php
/**
 * Created by Nadavk Kavalerchik.
 * Email: nadavkav@gmail.com
 * Date: 1/10/11
 * Time: 5:14 PM
 * Enable/Disable TableOperation icons
 */
 
require_once("../../../../../config.php");

//echo "<script type=\"text/javascript\" src=\"{$CFG->httpswwwroot}/lib/editor/htmlarea/plugins/TableOperations/table-operations.js\" charset=\"utf-8\"></script>";
//echo "<script type=\"text/javascript\" src=\"{$CFG->httpswwwroot}/lib/editor/htmlarea/plugins/TableOperations/lang/en.js\" charset=\"utf-8\"></script>";
include $CFG->dirroot."/lib/editor/htmlarea/plugins/TableOperations/table-operations.js";
include $CFG->dirroot."/lib/editor/htmlarea/plugins/TableOperations/lang/en.js";

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __tablesupport (editor) {

        // NOT WORKING :-(


    //var config = new HTMLArea.Config();
    editor.registerPlugin(TableOperations);
    html = editor.getHTML();

    editor._htmlArea.parentNode.removeChild(editor._htmlArea);

    editor.generate();
    editor.setHTML(html);



}