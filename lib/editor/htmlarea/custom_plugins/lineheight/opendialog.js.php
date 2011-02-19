<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 2/15/11 Time: 9:32 PM
 *
 * Description:
 *  Set Line Height of current/parent element
 */

require_once("../../../../../config.php");

 $courseid = optional_param('id', SITEID, PARAM_INT);

?>

var passparam = new Array();

function __lineheight (editor) {

  //var param = new Array();
  var el = editor.getParentElement();
  // get line height from parent element.
  passparam['lineheight'] = el.style.lineHeight;

  nbDialog("<?php echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/lineheight/dialog.php?id=$courseid"; ?>" ,300,300, function (param) {

    if (!param) {   // user must have pressed Cancel
        return false;
    }

    // set line height to parent element.
    el.style.lineHeight=param['lineheight'];

  });



}