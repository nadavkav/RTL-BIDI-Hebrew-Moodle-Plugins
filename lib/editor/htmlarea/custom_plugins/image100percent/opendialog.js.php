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

function __image100percent (editor) {

    var sel = editor._getSelection();
    var rng = editor._createRange(sel);
    //rng.startContainer.childNodes[0].style.widht = "100%";
    var img = rng.startContainer.childNodes[0]; // Let's hope it is the image element ;-)
    img.setAttribute("width","100%");
	img.style.width = "100%"; // maybe this is needed too?
}