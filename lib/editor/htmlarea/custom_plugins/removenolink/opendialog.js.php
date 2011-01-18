<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 10:53 PM
 *
 * Description:
 *    remove <nolink>text</nolink> tags that surrounds a selected text
 *    (remove the parent <SPAN> element and leaves the content)
 */

require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

function __removenolink (editor) {

  nolink = editor.getParentElement();

  // clear the class "nolink" and leave behind the span and the text
  //nolink.setAttribute('class', '');

  nolink.parentNode.insertBefore(nolink.firstChild, nolink);
  nolink.parentNode.removeChild(nolink);

  editor.selectNodeContents(nolink);

  //editor._doc.execCommand("unnolink", false, null);
  //editor.focusEditor();

}