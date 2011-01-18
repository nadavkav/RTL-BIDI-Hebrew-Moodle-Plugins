<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 10:53 PM
 *
 * Description:
 *    insert an already selected text: "write a remark" that has background style of yellow color
 *
 */

require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __marker (editor) {

    //var sel = editor._getSelection();
    //var rng = editor._createRange(sel);
    editor.insertHTML('<span style="background-color:yellow;"><?php echo get_string("yourcomment","marker",'',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/marker/lang/');?></span>');

}