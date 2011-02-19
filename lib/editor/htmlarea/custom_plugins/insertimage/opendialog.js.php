<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 9:32 PM
 *
 * Description:
 *  Enable users, especially, with ROLE Students, to Drag and Drop files (images)
 *  and upload them immidiatly inside the HTMLAREA editor as IMG elements
 *  (images are saved on a course level, in a special "users" folder with each user's ID)
 */

  require_once("../../../../../config.php");

  $courseid = optional_param('id', SITEID, PARAM_INT);

  global $USER;

?>

function __insertimage (editor) {

    nbDialog("<?php
    if(true or !empty($courseid) and has_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $courseid)) ) {
        echo $CFG->wwwroot."/lib/editor/htmlarea/custom_plugins/insertimage/dragdrop.php?courseid=$courseid&userid=$USER->id";
    } else {
        //echo "insert_swf.php?id=$id";
    }?>" ,800,600, function (param) {

        if (!param) {   // user must have pressed Cancel
            return false;
        }
        imagelist ='';
        //for (i=0; i<param.length;i++) {
        for (i in param) {
          imagelist = imagelist + param[i] + "<br/>";
        }
        var span = editor._doc.createElement("span");
        span.innerHTML = imagelist;
        editor.insertNodeAtSelection(span);
        return true;
        
        var img = image;
        if (!img) {
            var sel = editor._getSelection();
            var range = editor._createRange(sel);
                if (HTMLArea.is_ie) {
                editor._doc.execCommand("insertimage", false, param.f_url);
                }
            if (HTMLArea.is_ie) {
                img = range.parentElement();
                // wonder if this works...
                if (img.tagName.toLowerCase() != "img") {
                    img = img.previousSibling;
                }
            } else {
                // MOODLE HACK: startContainer.perviousSibling
                // Doesn't work so we'll use createElement and
                // insertNodeAtSelection
                //img = range.startContainer.previousSibling;
                var img = editor._doc.createElement("img");

                img.setAttribute("src",""+ param.f_url +"");
                img.setAttribute("alt",""+ param.f_alt +"");
                editor.insertNodeAtSelection(img);
            }
        } else {
            img.src = param.f_url;
        }
        for (field in param) {
            var value = param[field];
            switch (field) {
                case "f_alt"    : img.alt    = value; img.title = value; break;
                case "f_border" : img.border = parseInt(value || "0"); break;
                case "f_align"  : img.align  = value; break;
                case "f_vert"   : img.vspace = parseInt(value || "0"); break;
                case "f_horiz"  : img.hspace = parseInt(value || "0"); break;
                case "f_width"  :
                    if(value != 0) {
                        img.width = parseInt(value);
                    } else {
                        break;
                    }
                    break;
                case "f_height"  :
                    if(value != 0) {
                        img.height = parseInt(value);
                    } else {
                        break;
                    }
                    break;
            }
        }
    });
}