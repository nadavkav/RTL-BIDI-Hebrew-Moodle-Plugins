<?php  // $Id: locallib.php,v 4 2010/04/22 00:00:00 gibson,oz Exp $

require_once($CFG->dirroot . "/version.php");

/**
 * Print the submission script for message editing
 **/
function nanogong_print_submit_form_script($nanogong) {
    global $USER;
?>
    <script language="Javascript" type="text/javascript">
    function submitMessage() {
<?php
    if (isteacheredit($nanogong->course)) {
?>
        var locked = document.getElementById("menulocked");
        if (locked.selectedIndex == 0) {
            if (confirm("<?php print_string("confirmlock","nanogong") ?>"))
                locked.selectedIndex = 1;
        }
<?php
    }
?>

        var recorder = document.getElementById("recorder");
        if (recorder == null) {
            alert("<?php print_string("recordernotready", "nanogong");?>");
            return false;
        }

        // check whether the voice is ready
        var duration = recorder.sendGongRequest("GetMediaDuration", "audio");
        if (duration == null || duration == "" ||
            isNaN(duration) || parseInt(duration) <= 0) {
            alert("<?php print_string("norecording", "nanogong");?>");
            return false;
        }

        // check whether the applet is modified
        var modified = recorder.getModified();
        if (modified == null || modified != "1") {
            if (document.getElementById("path").value == null ||
                document.getElementById("path").value == "") return false;
            return true;
        }

        // upload the voice file to the server
        var path = recorder.sendGongRequest("PostToForm", "uploadsound.php?id=<?php echo $nanogong->course ?>&sesskey=<?php echo p($USER->sesskey) ?>", "userfile", "", "temp");
        if (path == null || path == "") {
            alert("<?php print_string("uploadfailure", "nanogong");?>");
            return false;
        }
        document.getElementById("path").value = path;

        return true;
    }
    </script>
<?php
}

/**
 * Print the new message form using the given
 * NanoGong object.
 **/
function nanogong_print_new_message_form($nanogong) {
    global $CFG, $USER;

    $usehtmleditor = can_use_html_editor();
?>
    <form method="post">
    <center>
    <h3><?php print_string("submitnewmessage","nanogong") ?></h3>
    </center>
    <?php print_simple_box_start("center"); ?>
    <center>
    <table cellpadding="5">
    <tr valign="top">
        <td align="right"><strong><?php print_string("title","nanogong") ?>:</strong></td>
        <td align="left"><input id="title" name="title" type="text" maxlength="255" size="80" value="" /></td>
    </tr>
    <tr valign="top">
        <td align="right"><strong><?php print_string("audiomessage","nanogong") ?>:</strong></td>
        <td align="left">
            <applet archive="<?php p("$CFG->wwwroot/mod/nanogong/nanogong.jar") ?>"
                id="recorder" name="recorder" code="gong.NanoGong" width="180px" height="40px">
<?php
    if (!empty($nanogong->color)) {
?>
                <param name="Color" value="<?php p($nanogong->color) ?>" />
<?php
    }
?>
<?php
    if (!empty($nanogong->maxduration)) {
?>
                <param name="MaxDuration" value="<?php p($nanogong->maxduration) ?>" />
<?php
    }
?>
								<param name="ShowTime" value="true" />
            </applet>
        </td>
    </tr>
    <tr valign="top">
        <td align="right"><strong><?php print_string("textmessage","nanogong") ?>:</strong></td>
        <td align="left">
        <?php print_textarea($usehtmleditor, 20, 60, 680, 400, "message"); ?>
        </td>
    </tr>
    </table>
    <?php nanogong_print_submit_form_script($nanogong) ?>
    <input id="path" name="path" type="hidden" value="" />
    <input id="action" name="action" type="hidden" value="submit" />
    <input type="submit" value="<?php  print_string("submitmessage", "nanogong") ?>" onclick="return submitMessage()" />

<?php
    if ($nanogong_message = get_record("nanogong_message", "nanogongid", $nanogong->id, "userid", $USER->id)) {
?>
    <input type="button" value="<?php  print_string("cancel") ?>" onclick="location.replace('view.php?id=<?php p($nanogong->id) ?>')" />
<?php
    }
?>
    </center>
    <br />&nbsp;
    <?php print_simple_box_end() ?>
    </form>
<?php
    if ($usehtmleditor) use_html_editor("message");
}

/**
 * Print the edit message form using the given
 * NanoGong message object.
 **/
function nanogong_print_edit_message_form($id, $nanogong, $nanogong_message) {
    global $CFG, $USER;

    if (isstudent($nanogong->course) && $nanogong_message->locked) {
        error(get_string("lockerror", "nanogong"), "view.php?id=$id");
        return;
    }

    $usehtmleditor = can_use_html_editor();
?>
    <form method="post">
    <center>
    <h3><?php print_string("editmessage","nanogong") ?></h3>
    </center>
    <?php print_simple_box_start("center"); ?>
    <center>
    <table cellpadding="5">
    <tr valign="top">
        <td align="right"><strong><?php print_string("submitdate","nanogong") ?>:</strong></td>
        <td align="left"><?php p(userdate($nanogong_message->timestamp)) ?></td>
    </tr>
    <tr valign="top">
        <td align="right"><strong><?php print_string("title","nanogong") ?>:</strong></td>
        <td align="left"><input id="title" name="title" type="text" maxlength="255" size="80" value="<?php p($nanogong_message->title) ?>" /></td>
    </tr>
    <tr valign="top">
        <td align="right"><strong><?php print_string("audiomessage","nanogong") ?>:</strong></td>
        <td align="left">
            <applet archive="<?php p("$CFG->wwwroot/mod/nanogong/nanogong.jar") ?>"
                id="recorder" name="recorder" code="gong.NanoGong" width="180px" height="40px">
<?php
    if ($CFG->slasharguments)
        $url = "{$CFG->wwwroot}/file.php{$nanogong_message->path}";
    else
        $url = "{$CFG->wwwroot}/file.php?file={$nanogong_message->path}";
?>
                <param name="SoundFileURL" value="<?php p($url) ?>" />
<?php
    if (!empty($nanogong->color)) {
?>
                <param name="Color" value="<?php p($nanogong->color) ?>" />
<?php
    }
?>
<?php
    if (!empty($nanogong->maxduration)) {
?>
                <param name="MaxDuration" value="<?php p($nanogong->maxduration) ?>" />
<?php
    }
?>
								<param name="ShowTime" value="true" />
            </applet></td>
    </tr>
    <tr valign="top">
        <td align="right"><strong><?php print_string("textmessage","nanogong") ?>:</strong></td>
        <td align="left" valign="top">
        <?php print_textarea($usehtmleditor, 20, 60, 680, 400, "message", $nanogong_message->message); ?>
        </td>
    </tr>
<?php
    if (isteacheredit($nanogong->course)) {
?>
    <tr valign="top">
        <td align="right"><strong><?php print_string("comments","nanogong") ?>:</strong></td>
        <td align="left">
        <?php print_textarea($usehtmleditor, 20, 60, 680, 400, "comments", $nanogong_message->comments); ?>
        </td>
    </tr>
    <tr valign="top">
        <td align="right"><strong><?php print_string("score","nanogong") ?>:</strong></td>
        <td align="left"><input type="text" id="score" name="score" size="7" maxsize="7" value="<?php p($nanogong_message->score) ?>" /></td>
    </tr>
    <tr valign="top">
        <td align="right"><strong><?php print_string("locked","nanogong") ?>:</strong></td>
        <td align="left">
<?php
        $options = array();
        $options[0] = get_string('no');
        $options[1] = get_string('yes');
        choose_from_menu($options, 'locked', $nanogong_message->locked, '');
?></td>
    </tr>
<?php
    }
?>
    </table>
    <?php nanogong_print_submit_form_script($nanogong) ?>
    <input id="path" name="path" type="hidden" value="<?php p($nanogong_message->path) ?>" />
    <input id="action" name="action" type="hidden" value="editsubmit" />
    <input id="messageid" name="messageid" type="hidden" value="<?php p($nanogong_message->id) ?>" />
    <input type="submit" value="<?php  print_string("updatemessage", "nanogong") ?>" onclick="return submitMessage()" />
    <input type="button" value="<?php  print_string("cancel") ?>" onclick="location.replace('view.php?id=<?php p($id) ?>')" />
    <br />&nbsp;
    </center>
    <?php print_simple_box_end() ?>
    </form>
<?php
    if ($usehtmleditor) {
        use_html_editor("message");
        if (isteacheredit($nanogong->course)) use_html_editor("comments");
    }
}

/**
 * Print the JavaScript for loading the NanoGong player.
 **/
function nanogong_print_player_script() {
?>
    <script language="Javascript" type="text/javascript">
    var nanogong_icons = [];
    var images = document.getElementsByTagName("img");
    for(var i = 0; i < images.length; i++) {
        if (images[i].className == "nanogong_icon") nanogong_icons.push(images[i]);
    }
			
    var nanogong_container = document.getElementById("nanogong_container");
    var nanogong_player = document.getElementById("player");
			
    function loadURL(image) {
        for (var i = 0; i < nanogong_icons.length; i++)
            nanogong_icons[i].style.display = "inline";
				
        nanogong_player.sendGongRequest("LoadFromURL", image.getAttribute("soundurl"));
				
        if (image.x) {
            nanogong_container.style.top = parseInt(image.y - image.offsetTop) + "px";
            nanogong_container.style.left = parseInt(image.x - image.offsetLeft) + "px";
        }
        else {
            var obj = image.offsetParent, curTop = 0, curLeft = 0;
            do {
                curTop += obj.offsetTop;
                curLeft += obj.offsetLeft;
            } while (obj = obj.offsetParent);
            nanogong_container.style.top = curTop + "px";
            nanogong_container.style.left = curLeft + "px";
        }
        image.style.display = "none";
    }
    </script>
<?php
}

/**
 * Print the message list for student using the given NanoGong object.
 **/
function nanogong_print_message_list_student($course, $modid, $nanogong, $groupid) {
    global $CFG, $USER;

?>
    <script language="Javascript" type="text/javascript">
    function submitForm(form, messageid, action) {
        if (action == "delete") {
            if (!confirm("<?php print_string("confirmdelete", "nanogong") ?>")) return;
        }
        document.getElementById("messageid").value = messageid;
        document.getElementById("action").value = action;
        form.submit();
    }
    </script>
    <center>
    <h3><?php print_string("messagelist","nanogong") ?></h3>
<?php
    nanogong_print_groups($modid, $course, $course->groupmode, $groupid, "view.php?id=".$modid);
?>
    </center>
    <form>
    <center>
<?php
    $nanogong_messages = get_records_select("nanogong_message", "nanogongid={$nanogong->id} AND userid={$USER->id}", "timestamp DESC");
    if (!$nanogong_messages) $nanogong_messages = array();
    if ($nanogong->maxmessages <= 0 ||
        count($nanogong_messages) < $nanogong->maxmessages) {
?>
        <input type="button" value="<?php  print_string("newmessage", "nanogong") ?>" onclick="submitForm(this.form, '', 'new')" />
<?php
    }
?>
    </center>
<?php
    foreach ($nanogong_messages as $nanogong_message) {
        $options = new object();
        $options->para = true;
        $messagetext = format_text($nanogong_message->message, FORMAT_HTML, $options);
        if ($messagetext == '') $messagetext = "&nbsp;";
        $commentstext = format_text($nanogong_message->comments, FORMAT_HTML, $options);
        if ($commentstext == '') $commentstext = "&nbsp;";
?>
    <p>
    <span class="nanogong_title"><?php p($nanogong_message->title) ?></span>
    <span class="nanogong_submitdate">-
<?php
        p(userdate($nanogong_message->timestamp));
        if (!empty($nanogong_message->timeedited)) {
            echo " <i>(";
            p(get_string("timeedited","nanogong"));
            echo ": ";
            p(userdate($nanogong_message->timeedited));
            echo ")</i>";
        }
?>
    </span>
    </p>
    <center>
    <table cellspacing="0" class="forumheaderlist">
    <thead>
    <tr>
        <th class="header" colspan="4"><?php print_string("message","nanogong") ?></th>
    <tr>
    </thead>
    <tbody>
    <tr>
<?php
        if ($CFG->slasharguments)
            $url = "{$CFG->wwwroot}/file.php{$nanogong_message->path}";
        else
            $url = "{$CFG->wwwroot}/file.php?file={$nanogong_message->path}";
?>
        <td align="center" width="140px" height="40px" valign="center">
            <img src="<?php p("$CFG->wwwroot/mod/nanogong/pix/sound.gif") ?>" soundurl="<?php p($url) ?>"
                 onclick="loadURL(this)" class="nanogong_icon"></td>
        <td align="left" valign="top" colspan="3"><?php print $messagetext ?></td>
    </tr>
    <tr>
        <th class="header"><?php print_string("commentedby","nanogong") ?></th>
        <th class="header"><?php print_string("comments","nanogong") ?></th>
        <th class="header" width="10%"><?php print_string("score","nanogong") ?></th>
        <th class="header" width="10%"><?php print_string("locked","nanogong") ?></th>
    </tr>
    <tr>
<?php
        if ($nanogong_message->commentedby != NULL &&
            $commentedby = get_record('user', 'id', $nanogong_message->commentedby)) {
?>
        <td><?php p(fullname($commentedby)) ?></td>
<?php
        } else {
?>
        <td>-</td>
<?php
        }
?>
        <td align="left" valign="top"><?php print $commentstext ?></td>
        <td align="center"><?php p($nanogong_message->score) ?></td>
        <td align="center">
<?php
        if ($nanogong_message->locked)
            print "<img alt=\"" . get_string("altlocked","nanogong") . "\" src=\"$CFG->wwwroot/mod/nanogong/pix/lock.gif\" />";
        else
            print "<img alt=\"" . get_string("altopen","nanogong") . "\" src=\"$CFG->wwwroot/mod/nanogong/pix/unlock.gif\" />";
?></td>
    </tr>
    </tbody>
    </table>
<?php
        if (!$nanogong_message->locked) {
?>
    <br />
    <input type="button" value="<?php  print_string("editmessage", "nanogong") ?>" onclick="submitForm(this.form, <?php p($nanogong_message->id) ?>, 'edit')" />
    <input type="button" value="<?php  print_string("deletemessage", "nanogong") ?>" onclick="submitForm(this.form, <?php p($nanogong_message->id) ?>, 'delete')" />
<?php
        }
?>
    </center>
<?php
    }
?>
    <br />
    <input id="id" name="id" type="hidden" value="<?php p($modid) ?>" />
    <input id="messageid" name="messageid" type="hidden" value="" />
    <input id="action" name="action" type="hidden" value="" />
    <div id="nanogong_container" style="position:absolute; top: -40px; left: -130px;">
        <applet archive="<?php print "$CFG->wwwroot/mod/nanogong/nanogong.jar" ?>"
                id="player" name="player" code="gong.NanoGong" width="130px" height="40px">
            <param name="ShowAudioLevel" value="false" />
            <param name="ShowRecordButton" value="false" />
<?php
    if (!empty($nanogong->color)) {
?>
            <param name="Color" value="<?php p($nanogong->color) ?>" />
<?php
    }
?>
						<param name="ShowTime" value="true" />
        </applet>
    </div>
    </form>
<?php
    nanogong_print_player_script();
}

/**
 * Print a selection box for groups
 * * modified from forum module
 **/
function nanogong_print_groups($modid, $course, $groupmode, $groupid) {
    global $USER;

    $context = get_context_instance(CONTEXT_MODULE, $modid);

    /// Now we need a menu for separategroups as well!
    if ($groupmode == VISIBLEGROUPS || ($groupmode && has_capability('moodle/site:accessallgroups', $context))) {
        if ($groups = get_records_menu("groups", "courseid", $course->id, "name ASC", "id,name")) {
?>
    <form>
    <table cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td>Please select the group:</td>
        <td>
            <select id="groupid" name="groupid" onchange="this.form.submit()">
<?php
            if ($groupmode && has_capability('moodle/site:accessallgroups', $context)) {
                if ($groupid == 0)
                    print "<option value=\"0\" selected>".get_string('allparticipants')."</option>";
                else
                    print "<option value=\"0\">".get_string('allparticipants')."</option>";
            }
            foreach ($groups as $key => $value) {
                if ($key == $groupid)
                    print "<option value=\"".$key."\" selected>";
                else
                    print "<option value=\"".$key."\">";
                p($value);
                print "</option>";
            }
?>
            </select></td>
    </tr>
    </table>
    <input id="id" name="id" type="hidden" value="<?php p($modid) ?>" />
    </form>
<?php
        }
    }

    /// Only print menus the student is in any course
    else if ($groupmode == SEPARATEGROUPS){
        if (($groups = user_group($course->id,$USER->id))
            && count($groups) > 1) {
            /// Extract the name and id for the group
?>
    <form>
    <table cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td>Please select the group:</td>
        <td>
            <select id="groupid" name="groupid" onchange="this.form.submit()">
<?php
            foreach ($groups as $group){
                if ($group->id == $groupid)
                    print "<option value=\"".$group->id."\" selected>";
                else
                    print "<option value=\"".$group->id."\">";
                p($group->name);
                print "</option>";
            }
?>
            </select></td>
    </tr>
    </table>
    <input id="id" name="id" type="hidden" value="<?php p($modid) ?>" />
    </form>
<?php
        }
    }

}

/**
 * Print the message list using the given NanoGong object.
 **/
function nanogong_print_message_list($course, $modid, $nanogong, $groupid = NULL, $readonly = false, $isguest = false) {
    global $CFG, $USER;

    if (!$readonly && !$isguest) {
?>
    <script language="Javascript" type="text/javascript">
    function submitForm(form, messageid, action) {
        if (action == "delete") {
            if (!confirm("<?php print_string("confirmdelete", "nanogong") ?>")) return;
        }
        document.getElementById("messageid").value = messageid;
        document.getElementById("action").value = action;
        form.submit();
    }
    </script>
<?php
    }
?>
    <center>
    <h3><?php print_string("messagelist","nanogong") ?></h3>
<?php
    nanogong_print_groups($modid, $course, $course->groupmode, $groupid, "view.php?id=".$modid);
?>
    </center>
    <form>
<?php
    if (empty($groupid))
        $students = get_course_students($nanogong->course, "u.lastname, u.firstname");
    else
        $students = get_course_students($nanogong->course, "u.lastname, u.firstname", '', '', '', '', '', $groupid);
    if (!$students) $students = array();
    foreach ($students as $student) {
        $nanogong_messages = get_records_select("nanogong_message", "nanogongid={$nanogong->id} AND userid={$student->id}", "timestamp DESC");
        if (!$nanogong_messages) $nanogong_messages = array();
?>
    <h4><?php p(fullname($student)) ?> (<?php p(count($nanogong_messages)) ?>)</h4>
<?php
        if (count($nanogong_messages) > 0) {
?>
    <center>
    <table cellspacing="0" class="forumheaderlist nanogong_messagelist">
    <thead>
    <tr>
        <th class="header" width="15%"><?php print_string("submitdate","nanogong") ?></th>
        <th class="header" colspan="2"><?php print_string("message","nanogong") ?></th>
<?php
    if (!$isguest) {
?>
        <th class="header"><?php print_string("comments","nanogong") ?></th>
        <th class="header"><?php print_string("score","nanogong") ?></th>
<?php
    }
    if (!$readonly && !$isguest) {
?>
        <th class="header" width="10%"><?php print_string("locked","nanogong") ?></th>
        <th class="header" width="10%">&nbsp;</th>
<?php
    }
?>
    <tr>
    </thead>
    <tbody>
<?php
            foreach ($nanogong_messages as $nanogong_message) {
                $options = new object();
                $options->para = true;
                $messagetext = format_text($nanogong_message->message, FORMAT_HTML, $options);
                if ($messagetext == '') $messagetext = "&nbsp;";
                $commentstext = format_text($nanogong_message->comments, FORMAT_HTML, $options);
                if ($commentstext == '') $commentstext = "&nbsp;";
?>
    <tr>
<?php
        if (empty($nanogong_message->timeedited)) {
?>
        <td align="center" class="nanogong_submitdate"><?php p(userdate($nanogong_message->timestamp)) ?></td>
<?php
        } else {
?>
        <td align="center" class="nanogong_submitdate">
            <?php p(userdate($nanogong_message->timestamp)) ?><br />
            <i>(<?php p(get_string("timeedited","nanogong").": ".userdate($nanogong_message->timeedited)) ?>)</i></td>
<?php
        }

        if ($CFG->slasharguments)
            $url = "{$CFG->wwwroot}/file.php{$nanogong_message->path}";
        else
            $url = "{$CFG->wwwroot}/file.php?file={$nanogong_message->path}";
?>
        <td align="center" width="140px" height="40px" valign="center">
            <img src="<?php p("$CFG->wwwroot/mod/nanogong/pix/sound.gif") ?>" soundurl="<?php p($url) ?>"
                 onclick="loadURL(this)" class="nanogong_icon"></td>
        <td align="left" valign="top"><?php print $messagetext ?></td>
<?php
        if (!$isguest) {
?>
        <td align="left" valign="top"><?php print $commentstext ?></td>
        <td align="center"><?php p($nanogong_message->score) ?></td>
<?php
        }
        if (!$readonly && !$isguest) {
?>
        <td align="center">
<?php
            if ($nanogong_message->locked)
                print "<img alt=\"" . get_string("altlocked","nanogong") . "\" src=\"$CFG->wwwroot/mod/nanogong/pix/lock.gif\" />";
            else
                print "<img alt=\"" . get_string("altopen","nanogong") . "\" src=\"$CFG->wwwroot/mod/nanogong/pix/unlock.gif\" />";
?></td>
        <td align="center">
        <input type="button" value="<?php  print_string("editmessage", "nanogong") ?>" onclick="submitForm(this.form, <?php p($nanogong_message->id) ?>, 'edit')" style="width: 100%" /><br />
        <input type="button" value="<?php  print_string("deletemessage", "nanogong") ?>" onclick="submitForm(this.form, <?php p($nanogong_message->id) ?>, 'delete')" style="width: 100%" /></td>
<?php
        }
?>
    </tr>
<?php
            }
?>
    </tbody>
    </table>
    </center>
<?php
        }
    }
?>
    <center>
<?php
    if (!$readonly && !$isguest) {
?>
    <br />
    <input id="id" name="id" type="hidden" value="<?php p($modid) ?>" />
    <input id="messageid" name="messageid" type="hidden" value="" />
    <input id="action" name="action" type="hidden" value="" />
    <input type="button" value="<?php  print_string("lockallmessage", "nanogong") ?>" onclick="submitForm(this.form, '', 'lockall')" />
    <input type="button" value="<?php  print_string("unlockallmessage", "nanogong") ?>" onclick="submitForm(this.form, '', 'unlockall')" />
<?php
    }
?>
    </center>
    <div id="nanogong_container" style="position:absolute; top: -40px; left: -130px;">
        <applet archive="<?php print "$CFG->wwwroot/mod/nanogong/nanogong.jar" ?>"
                id="player" name="player" code="gong.NanoGong" width="130px" height="40px">
            <param name="ShowAudioLevel" value="false" />
            <param name="ShowRecordButton" value="false" />
<?php
    if (!empty($nanogong->color)) {
?>
            <param name="Color" value="<?php p($nanogong->color) ?>" />
<?php
    }
?>
						<param name="ShowTime" value="true" />
        </applet>
    </div>
    </form>
<?php
    nanogong_print_player_script();
}

/**
 * Submit a new message in the NanoGong database.
 **/
function nanogong_submit_message($nanogong, $groupid, $title, $path, $message, &$error) {
    global $CFG, $USER;

    $error = null;
    if (!isstudent($nanogong->course)) {
        $error = get_string("notincourse", "nanogong");
        return false;
    }
    $nanogong_messages = get_records_select("nanogong_message", "nanogongid={$nanogong->id} AND userid={$USER->id}");
    if (!$nanogong_messages) $nanogong_messages = array();
    if ($nanogong->maxmessages > 0 &&
        count($nanogong_messages) >= $nanogong->maxmessages) {
        $error = get_string("maxmessagereached", "nanogong");
        return false;
    }

    $nanogong_message = new object;
    $nanogong_message->nanogongid = $nanogong->id;
    $nanogong_message->userid = $USER->id;
    $nanogong_message->groupid = $groupid;
    $nanogong_message->title = addslashes($title);
    $nanogong_message->message = addslashes($message);
    $nanogong_message->path = addslashes($path);
    $nanogong_message->timestamp = time();

    return insert_record("nanogong_message", $nanogong_message);
}

/**
 * Edit a message in the NanoGong database.
 **/
function nanogong_edit_message($nanogong, $messageid, $groupid, $title, $path, $message, $comments = NULL, $score = NULL, $locked = NULL, &$error) {
    global $CFG, $USER;

    $error = null;
    if (!isstudent($nanogong->course) && !isteacheredit($nanogong->course)) {
        $error = get_string("notincourse", "nanogong");
        return false;
    }

    if ($nanogong_message = get_record("nanogong_message", "id", $messageid)) {
        if (isstudent($nanogong->course) && $nanogong_message->locked) {
            $error = get_string("lockerror", "nanogong");
            return false;
        }

        if ($nanogong_message->path != $path) {
            $soundfile = $CFG->dataroot.$nanogong_message->path;
            if (file_exists($soundfile)) @unlink($soundfile);
        }
        $nanogong_message->groupid = $groupid;
        $nanogong_message->title = addslashes($title);
        $nanogong_message->message = addslashes($message);
        $nanogong_message->path = addslashes($path);
        if (isstudent($nanogong->course))
            $nanogong_message->timeedited = time();
        else {
            if ($nanogong_message->commentedby == NULL)
                $nanogong_message->commentedby = $USER->id;
            $nanogong_message->comments = addslashes($comments);
            $nanogong_message->score = $score;
            $nanogong_message->locked = $locked;
        }
        
        if(substr($CFG->release, 0, 3) == "1.9") {
        	return update_record("nanogong_message", $nanogong_message) && nanogong_update_grades($nanogong);
        }
        
        return update_record("nanogong_message", $nanogong_message);
    }

    return false;
}

/**
 * Delete a message from the NanoGong database.
 **/
function nanogong_delete_message($nanogong, $messageid, &$error) {
    global $CFG, $USER;

    if (!isstudent($nanogong->course) && !isteacheredit($nanogong->course)) {
        $error = get_string("notincourse", "nanogong");
        return false;
    }

    if ($nanogong_message = get_record("nanogong_message", "id", $messageid)) {
        if (isstudent($nanogong->course) && $nanogong_message->locked) {
            $error = get_string("lockerror", "nanogong");
            return false;
        }

        $soundfile = $CFG->dataroot.$nanogong_message->path;
        if (file_exists($soundfile)) @unlink($soundfile);
        
        
        if(substr($CFG->release, 0, 3) == "1.9") {
        	return delete_records("nanogong_message", "id", $nanogong_message->id) && nanogong_update_grades($nanogong, $nanogong_message->userid);
        }
        
        return delete_records("nanogong_message", "id", $nanogong_message->id);
    }

    return false;
}

/**
 * Lock messages in the NanoGong database.
 **/
function nanogong_lock_all_messages($nanogong, $groupid) {
    global $CFG, $USER;

    if (!isteacheredit($nanogong->course)) return false;

    if (empty($groupid))
        $students = get_course_students($nanogong->course, "u.lastname, u.firstname");
    else
        $students = get_course_students($nanogong->course, "u.lastname, u.firstname", '', '', '', '', '', $groupid);
    if (!$students) $students = array();
    foreach ($students as $student) {
        $nanogong_messages = get_records_select("nanogong_message", "nanogongid={$nanogong->id} AND userid={$student->id}");
        if (!$nanogong_messages) $nanogong_messages = array();

        foreach ($nanogong_messages as $nanogong_message) {
            $nanogong_message->locked = 1;
            update_record("nanogong_message", $nanogong_message);
        }
    }

    return true;
}

/**
 * Unlock messages in the NanoGong database.
 **/
function nanogong_unlock_all_messages($nanogong, $groupid) {
    global $CFG, $USER;

    if (!isteacheredit($nanogong->course)) return false;

    if (empty($groupid))
        $students = get_course_students($nanogong->course, "u.lastname, u.firstname");
    else
        $students = get_course_students($nanogong->course, "u.lastname, u.firstname", '', '', '', '', '', $groupid);
    if (!$students) $students = array();
    foreach ($students as $student) {
        $nanogong_messages = get_records_select("nanogong_message", "nanogongid={$nanogong->id} AND userid={$student->id}");
        if (!$nanogong_messages) $nanogong_messages = array();

        foreach ($nanogong_messages as $nanogong_message) {
            $nanogong_message->locked = 0;
            update_record("nanogong_message", $nanogong_message);
        }
    }

    return true;
}

?>
