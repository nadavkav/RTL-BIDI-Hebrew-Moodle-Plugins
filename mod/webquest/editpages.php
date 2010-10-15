<?php  // $Id: editpages.php,v 1.4 2007/09/09 09:00:17 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");


    $id     = required_param('id', PARAM_INT);
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $cancel = optional_param('cancel');

    $timenow = time();
    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
        if (! $webquest = get_record("webquest", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    }else{
        if (! $webquest = get_record("webquest", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $webquest->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("webquest", $webquest->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id, false, $cm);

    if (($action == 'editdescription') or ($action == 'editdescriptiondo')){
        $strpage = get_string("intro", "webquest");
    }else if (($action == 'editprocess') or ($action == 'editprocessdo')){
        $strpage = get_string("process", "webquest");
    }else if (($action == 'editconclussion') or ($action == 'editconclussiondo')){
        $strpage = get_string("conclussion", "webquest");
    }else if (($action == 'edittask') or ($action == 'edittaskdo')){
        $strpage = get_string("task", "webquest");
    }else{
        $strpage = "missed action";
    }
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests = get_string("modulenameplural", "webquest");
    print_header_simple(format_string($webquest->name), "",
                "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strpage",
                "", "", true);


    add_to_log($course->id, "webquest", "update ".$strpage, "view.php?id=$cm->id", "$webquest->id");

    //*****************************************************************************************************************//
    if ($action == 'editdescriptiondo'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=introduction");
        }
        $webquest->description = $form->description;
        if (!set_field("webquest", "description", trim($webquest->description), "id", $webquest->id)){
            error("Could not update webquest introduction!");
            redirect("view.php?id=$cm->id&amp;action=introduction");
        }else{
            redirect("view.php?id=$cm->id&amp;action=introduction", get_string("wellsaved","webquest"));
        }
    }

    if ($action == 'editprocessdo'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=process");
        }
        $webquest->process = $form->process;
        if (!set_field("webquest", "process", $webquest->process, "id", $webquest->id)){
            error("Could not update webquest Process!");
            redirect("view.php?id=$cm->id&amp;action=process");
        }else{
            redirect("view.php?id=$cm->id&amp;action=process", get_string("wellsaved","webquest"));
        }
    }

    if ($action == 'editconclussiondo'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=conclussion");
        }
        $webquest->conclussion = $form->conclussion;
        if (!set_field("webquest", "conclussion", $webquest->conclussion, "id", $webquest->id)){
            error("Could not update webquest conclussion!");
            redirect("view.php?id=$cm->id&amp;action=conclussion");
        }else{
            redirect("view.php?id=$cm->id&amp;action=conclussion", get_string("wellsaved","webquest"));
        }
    }

    if ($action == 'edittaskdo'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=tasks");
        }
        $webquest->taskdescription = $form->taskdescription;
        if (!set_field("webquest", "taskdescription", $webquest->taskdescription, "id", $webquest->id)){
            error("Could not update webquest Task Description!");
            redirect("view.php?id=$cm->id&amp;action=tasks");
        }else{
            redirect("view.php?id=$cm->id&amp;action=tasks", get_string("wellsaved","webquest"));
        }
    }

//****************************************Forms******************************************************//
    if ($usehtmleditor = can_use_html_editor()) {
        $defaultformat = FORMAT_HTML;
        $editorfields = '';
    }else{
        $defaultformat = FORMAT_MOODLE;
    }

    if ($action == 'editdescription'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        if (empty($webquest->description)) {
            $form->description = "";
        } else{
            $form->description = $webquest->description;
        }
        print_simple_box_start('center', '', '', 5, 'generalbox');
     ?>
     <form name="form" method="post" action="editpages.php">
     <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
     <input type="hidden" name="action" value="editdescriptiondo" />
     <center>
     <table cellpadding="5">
     <tr valign="top">
     <td align="right"><b>
     <?php  print_string("intro","webquest") ?>
     :</b><br />
     <font size="1">
    <?php
        helpbutton("writing", get_string("helpwriting"), "moodle", true, true);
        echo "<br />";
        if ($usehtmleditor) {
            helpbutton("richtext", get_string("helprichtext"), "moodle", true, true);
        } else {
            helpbutton("text", get_string("helptext"), "moodle", true, true);
            echo "<br />";
            emoticonhelpbutton("form", "description", "moodle", true, true);
            echo "<br />";
        }
      ?>
    </font></td>
    <td><?php
        print_textarea($usehtmleditor, 20, 60, 595, 400, "description", $form->description);
        if ($usehtmleditor) {
            echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
        }else {
            echo '<p align="right">';
            helpbutton("textformat", get_string("formattexttype"));
            print_string("formattexttype");
            echo ':&nbsp;';
            if (!$form->format) {
                $form->format = $defaultformat;
            }
            choose_from_menu(format_text_menu(), "format", $form->format, "");
            echo '</p>';
        }
    ?></td>
    </tr>
    </table>
    <input type="submit" value="<?php  print_string("savechanges") ?>" />
    <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
    </center>
    <?php
        print_simple_box_end();
    }

    if ($action == 'editprocess'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        if (empty($webquest->process)) {
            $form->process = "";
        }else{
            $form->process = $webquest->process;
        }
        print_simple_box_start('center', '', '', 5, 'generalbox');
    ?>
     <form name="form" method="post" action="editpages.php">
     <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
     <input type="hidden" name="action" value="editprocessdo" />
     <center>
     <table cellpadding="5">
     <tr valign="top">
     <td align="right"><b>
    <?php  print_string("process","webquest") ?>
     :</b><br />
     <font size="1">
    <?php
        helpbutton("writing", get_string("helpwriting"), "moodle", true, true);
        echo "<br />";
        if ($usehtmleditor) {
            helpbutton("richtext", get_string("helprichtext"), "moodle", true, true);
        }else{
            helpbutton("text", get_string("helptext"), "moodle", true, true);
            echo "<br />";
            emoticonhelpbutton("form", "description", "moodle", true, true);
            echo "<br />";
        }
    ?>
    </font></td>
    <td><?php
        print_textarea($usehtmleditor, 20, 60, 595, 400, "process", $form->process);
        if ($usehtmleditor) {
            echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
        }else{
            echo '<p align="right">';
            helpbutton("textformat", get_string("formattexttype"));
            print_string("formattexttype");
            echo ':&nbsp;';
            if (!$form->format) {
                $form->format = $defaultformat;
            }
            choose_from_menu(format_text_menu(), "format", $form->format, "");
            echo '</p>';
        }
    ?></td>
    </tr>
    </table>
    <input type="submit" value="<?php  print_string("savechanges") ?>" />
    <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
    </center>
    <?php
        print_simple_box_end();
    }


    if ($action == 'editconclussion'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        if (empty($webquest->conclussion)) {
            $form->conclussion = "";
        }else{
            $form->conclussion = $webquest->conclussion;
        }
        print_simple_box_start('center', '', '', 5, 'generalbox');
     ?>
     <form name="form" method="post" action="editpages.php">
     <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
     <input type="hidden" name="action" value="editconclussiondo" />
     <center>
     <table cellpadding="5">
     <tr valign="top">
     <td align="right"><b>
     <?php print_string("conclussion","webquest") ?>
     :</b><br />
     <font size="1">
    <?php
        helpbutton("writing", get_string("helpwriting"), "moodle", true, true);
        echo "<br />";
        if ($usehtmleditor) {
            helpbutton("richtext", get_string("helprichtext"), "moodle", true, true);
        }else{
            helpbutton("text", get_string("helptext"), "moodle", true, true);
            echo "<br />";
            emoticonhelpbutton("form", "description", "moodle", true, true);
            echo "<br />";
        }
    ?>
    </font></td>
    <td><?php
        print_textarea($usehtmleditor, 20, 60, 595, 400, "conclussion", $form->conclussion);
        if ($usehtmleditor) {
            echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
        }else{
            echo '<p align="right">';
            helpbutton("textformat", get_string("formattexttype"));
            print_string("formattexttype");
            echo ':&nbsp;';
            if (!$form->format) {
                $form->format = $defaultformat;
            }
            choose_from_menu(format_text_menu(), "format", $form->format, "");
            echo '</p>';
        }
    ?></td>
    </tr>
    </table>
    <input type="submit" value="<?php  print_string("savechanges") ?>" />
    <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
    </center>
    <?php
        print_simple_box_end();
    }

    if ($action == 'edittask'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page");
        }
        if (empty($webquest->taskdescription)) {
            $form->taskdescription = "";
        }else{
            $form->taskdescription = $webquest->taskdescription;
        }
        print_simple_box_start('center', '', '', 5, 'generalbox');
     ?>
     <form name="form" method="post" action="editpages.php">
     <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
     <input type="hidden" name="action" value="edittaskdo" />
     <center>
     <table cellpadding="5">
     <tr valign="top">
     <td align="right"><b>
     <?php  print_string("task","webquest") ?>
     :</b><br />
     <font size="1">
    <?php
        helpbutton("writing", get_string("helpwriting"), "moodle", true, true);
        echo "<br />";
        if ($usehtmleditor) {
            helpbutton("richtext", get_string("helprichtext"), "moodle", true, true);
        }else{
            helpbutton("text", get_string("helptext"), "moodle", true, true);
            echo "<br />";
            emoticonhelpbutton("form", "description", "moodle", true, true);
            echo "<br />";
        }
      ?>
    </font></td>
    <td><?php
       print_textarea($usehtmleditor, 20, 60, 595, 400, "taskdescription", $form->taskdescription);
       if ($usehtmleditor) {
            echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
        }else{
            echo '<p align="right">';
            helpbutton("textformat", get_string("formattexttype"));
            print_string("formattexttype");
            echo ':&nbsp;';
            if (!$form->format) {
                $form->format = $defaultformat;
            }
            choose_from_menu(format_text_menu(), "format", $form->format, "");
            echo '</p>';
        }
    ?></td>
    </tr>
    </table>
    <input type="submit" value="<?php  print_string("savechanges") ?>" />
    <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
    </center>
    <?php
        print_simple_box_end();
    }
    if ($usehtmleditor and empty($nohtmleditorneeded)) {
        use_html_editor($editorfields);
    }

    print_footer($course);
    ?>