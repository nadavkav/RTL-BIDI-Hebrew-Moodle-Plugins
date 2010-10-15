<?php  // $Id: resources.php,v 1.4 2007/09/09 09:00:19 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");


    $id     = required_param('id', PARAM_INT);    // Course Module ID, or
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $resid  = optional_param('resid');
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

    } else {
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

    $strresources = get_string("resources", "webquest");
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests =  get_string("modulenameplural", "webquest");

    print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strresources",
                  "", "", true);

    add_to_log($course->id, "webquest", "update resource", "view.php?id=$cm->id", "$webquest->id");

    $straction = ($action) ? '-> '.get_string($action, 'webquest') : '';

    ///////////////Edit Resources /////////////////////////////////////////////


   if ($action == 'editres'){
        if (!isteacher($cm->course)){
            error("Only teachers can look at this page");
        }
        $form = get_record("webquest_resources","id",$resid,"webquestid",$webquest->id );
        if (empty($form->name)){
            $form->name = "";
        }
        if (empty($form->description)){
            $form->description = "";
        }
        if (empty($form->path)){
            $form->path = "http://";
        }
        $string = get_string('cancel');
        $strsearch = get_string("searchweb", "webquest");
        $strchooseafile = get_string("chooseafile", "webquest");
        if (!$resid){
            print_heading_with_help(get_string("insertresources", "webquest"), "insertresources", "webquest");
        }else {
            print_heading_with_help(get_string("editresource", "webquest"), "editresource", "webquest");
        }
      ?>
        <form name="form" method="post" action="resources.php">
        <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
        <input type="hidden" name="action" value="insertres" />
    <input type="hidden" name="resid" value="<?php echo $resid ?>" />
        <center><table cellpadding="5" border="1">
        <?php
            if (right_to_left()) { // rtl support for table cell alignment (nadavkav patch)
              $alignmentleft = 'right';
              $alignmentright = 'left';
            } else {
              $alignmentleft = 'left';
              $alignmentright = 'right';
            }

    ///get the selected resource
            echo "<tr valign=\"top\">\n";
            echo "<td align=\"$alignmentright\"><b>". get_string("name").": </b></td>\n";
            echo "<td align=\"$alignmentleft\"><input type=\"text\" name=\"name\" size=\"30\" value=$form->name></td>";
            echo "</tr>";
            echo "<tr valign=\"top\">\n";
            echo "  <td align=\"$alignmentright\"><b>". get_string("description").": </b></td>\n";
            echo "<td><textarea name=\"description\" rows=\"3\" cols=\"75\">".$form->description."</textarea>\n";
            echo "  </td></tr>\n";
            echo "<tr valign =\"top\">\n";
            echo "<td align=\"$alignmentright\"><b>". get_string("url","webquest")." :</b></td>\n";
            echo "<td align=\"$alignmentleft\"><input type=\"text\" name=\"path\" size=\"30\" value=\"$form->path\" alt=\"reference\" style=\"text-align:left;direction:ltr;\"/><br />";
            button_to_popup_window ("/files/index.php?id=$cm->course&amp;choose=form.path", "coursefiles", $strchooseafile, 500, 750, $strchooseafile);
            echo "<input type=\"button\" name=\"searchbutton\" value=\"$strsearch ...\" ".
                "onclick=\"return window.open('$CFG->resource_websearch', 'websearch', 'menubar=1,location=1,directories=1,toolbar=1,scrollbars,resizable,width=800,height=600');\" />\n</td>";
            echo "</tr>";
            echo "<tr valign=\"top\">\n";
            echo "  <td colspan=\"2\" >&nbsp;</td>\n";
            echo "</tr>";
            echo"<td>";



 echo   "</td>";
    ?>
        </table><br />
        <input type="submit" value="<?php  print_string("savechanges") ?>" />
    <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
        </center>
        </form>
        <?php
   }
   if ($action == 'insertres'){
        if (!isteacher($cm->course)){
            error("Only teachers can look at this page");
        }
        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=process");
        }
        if (record_exists("webquest_resources","id",$resid)){
            $res->name = $form->name;
            $res->description = $form->description;
            $res->path = $form->path;
            $res->id = $resid;
            if (!update_record("webquest_resources",$res)){
                error("Could not update webquest resource!");
                redirect("view.php?id=$cm->id&amp;action=process");
            }
            redirect("view.php?id=$cm->id&amp;action=process", get_string("wellsaved","webquest"));
        }else{
            unset($res);
            $res->webquestid = $webquest->id;
            $res->name = $form->name;
            $res->description = $form->description;
            $res->path = $form->path;
            $res->resno =1+ (count_records("webquest_resources","webquestid",$webquest->id));
            if (!$res->id = insert_record("webquest_resources", $res)) {
                error("Could not insert webquest resource!");
                redirect("view.php?id=$cm->id&amp;action=process");
            }
            redirect("view.php?id=$cm->id&amp;action=process", get_string("wellsaved","webquest"));
        }
    }

    ///////////Delete Resource////////////////////////////
    if($action == 'deleteres'){
        if (!isteacher($course->id)){
            error("Only teachers can look at this page");
        }
        notice_yesno(get_string("suretodelres","webquest"),
             "resources.php?action=deleteyesres&amp;id=$id&amp;resid=$resid", "view.php?id=$id&amp;action=process");

    }

    /////// Delete Resource NOW////////
    if ($action == 'deleteyesres'){
        if (!isteacher($cm->course)){
            error("Only teachers can look at this page");
        }
        if (! delete_records("webquest_resources", "id", "$resid")){
            redirect("view.php?id=$cm->id&amp;action=process", get_string("couldnotdelete","webquest"));
        }else {
            redirect("view.php?id=$cm->id&amp;action=process", get_string("deleted","webquest"));
        }
    }
    print_footer($course);

