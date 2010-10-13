<?php  // $Id: view.php,v 4 2010/04/22 00:00:00 gibson Exp $

    global $CFG, $USER;

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $modid = optional_param('modid', 0, PARAM_INT);  // nanogong ID
    $groupid = optional_param('groupid', 0, PARAM_INT); // group ID
    $action = optional_param('action', 0, PARAM_ALPHA); // Action

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $nanogong = get_record("nanogong", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $nanogong = get_record("nanogong", "id", $modid)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $nanogong->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("nanogong", $nanogong->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    if (isguest() && !$nanogong->allowguestaccess) {
        error(get_string("guestaccessnotallow","nanogong"), "$CFG->wwwroot/course/view.php?id={$course->id}");
    }

    add_to_log($course->id, "nanogong", "view", "view.php?id={$cm->id}", "$nanogong->id");

    // Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id={$course->id}\">{$course->shortname}</a> ->";
    } else {
        $navigation = '';
    }

    $strnanogongs = get_string("modulenameplural", "nanogong");
    $strnanogong  = get_string("modulename", "nanogong");

    print_header("$course->shortname: $nanogong->name", "$course->fullname",
                 "$navigation <a href=index.php?id={$course->id}>$strnanogongs</a> -> {$nanogong->name}", 
                  "", "", true, update_module_button($cm->id, $course->id, $strnanogong), 
                  navmenu($course, $cm));
?>
<br />
<?php
    // Print the activity description
    $options = new object();
    $options->para = true;
    if ($nanogong->message != '')
        print_simple_box(format_text($nanogong->message, FORMAT_HTML, $options), 'center', '70%', '', 5, 'generalbox', 'message');

    // The redirection url
    $url = "view.php?id={$cm->id}";
    if (!empty($groupid)) $url .= "&groupid={$groupid}";

    if (isstudent($course->id)) {
        // New message is submitted
        if ($action === "submit") {
            $title = required_param('title', PARAM_TEXT); // Title
            $path = required_param('path', PARAM_PATH); // Path
            if ($path == "") error(get_string("pathmissing", "nanogong"), $url);
            $message = optional_param('message', '', PARAM_CLEANHTML); // Message
            $error = null;
            if (nanogong_submit_message($nanogong, $groupid, $title, $path, $message, $error))
                redirect($url, get_string("messagesubmitted", "nanogong"));
            else {
                if ($error == null) $error = get_string("submitfailed", "nanogong");
                error($error, $url);
            }
        }

        // Message is edited
        if ($action === "editsubmit") {
            $messageid = required_param('messageid', PARAM_INT); // Message ID
            $title = required_param('title', PARAM_TEXT); // Title
            $path = required_param('path', PARAM_PATH); // Path
            if ($path == "") error(get_string("pathmissing", "nanogong"), $url);
            $message = optional_param('message', '', PARAM_CLEANHTML); // Message
            $error = null;
            if (nanogong_edit_message($nanogong, $messageid, $groupid, $title, $path, $message, NULL, NULL, NULL, $error))
                redirect($url, get_string("messageedited", "nanogong"));
            else {
                if ($error == null) $error = get_string("editfailed", "nanogong");
                error($error, $url);
            }
        }

        // Message is deleted
        if ($action === "delete") {
            $messageid = required_param('messageid', PARAM_INT); // Message ID
            $error = null;
            if (nanogong_delete_message($nanogong, $messageid, $error))
                redirect($url, get_string("messagedeleted", "nanogong"));
            else {
                if ($error == null) $error = get_string("deletefailed", "nanogong");
                error($error, $url);
            }
        }

        // New message
        if (!($nanogong_message = get_record("nanogong_message", "nanogongid", $nanogong->id, "userid", $USER->id)) ||
            $action === "new") {
            nanogong_print_new_message_form($nanogong);
        }
        else
        // Edit message
        if ($action === "edit") {
            $messageid = required_param('messageid', PARAM_INT); // Message ID
            if (!$nanogong_message = get_record("nanogong_message", "nanogongid", $nanogong->id, "userid", $USER->id, "id", $messageid))
                error(get_string("messagenotfound", "nanogong"), $url);
            nanogong_print_edit_message_form($id, $nanogong, $nanogong_message);
        }
        else
            // Message list
            nanogong_print_message_list_student($course, $id, $nanogong, $groupid);
    }
    else if (isteacheredit($course->id)) {
        // Message is edited
        if ($action === "editsubmit") {
            $messageid = required_param('messageid', PARAM_INT); // Message Id
            $title = required_param('title', PARAM_TEXT); // Title
            $path = required_param('path', PARAM_PATH); // Path
            if ($path == "") error(get_string("pathmissing", "nanogong"), $url);
            $message = optional_param('message', '', PARAM_CLEANHTML); // Message
            $comments = optional_param('comments', '', PARAM_CLEANHTML); // Comments
            $score = optional_param('score', '', PARAM_INT); // Score
            $locked = optional_param('locked', 0, PARAM_INT); // Locked
            $error = null;
            if (nanogong_edit_message($nanogong, $messageid, $groupid, $title, $path, $message, $comments, $score, $locked, $error))
                redirect($url, get_string("messageedited", "nanogong"));
            else {
                if ($error == null) $error = get_string("editfailed", "nanogong");
                error($error, $url);
            }
        }

        // Lock all messages
        if ($action === "lockall") {
            if (nanogong_lock_all_messages($nanogong, $groupid))
                redirect($url, get_string("messageslocked", "nanogong"));
            else
                error(get_string("lockfailed", "nanogong"), $url);
        }

        // Unlock all messages
        if ($action === "unlockall") {
            if (nanogong_unlock_all_messages($nanogong, $groupid))
                redirect($url, get_string("messagesunlocked", "nanogong"));
            else
                error(get_string("unlockfailed", "nanogong"), $url);
        }

        // Delete a message
        if ($action === "delete") {
            $messageid = required_param('messageid', PARAM_INT); // Message Id
            $error = null;
            if (nanogong_delete_message($nanogong, $messageid, $error))
                redirect($url, get_string("messagedeleted", "nanogong"));
            else {
                if ($error == null) $error = get_string("deletefailed", "nanogong");
                error($error, $url);
            }
        }

        // Edit message
        if ($action === "edit") {
            $messageid = required_param('messageid', PARAM_INT); // Message Id
            if ($nanogong_message = get_record("nanogong_message", "id", $messageid))
                nanogong_print_edit_message_form($id, $nanogong, $nanogong_message);
            else
                error(get_string("messagenotfound", "nanogong"), $url);
        }
        else
            nanogong_print_message_list($course, $id, $nanogong, $groupid);
    }
    else
        nanogong_print_message_list($course, $id, $nanogong, $groupid, true, isguest());
?>
    </tbody>
</table>
</center>
<?php
    // Finish the page
    print_footer($course);
?>
