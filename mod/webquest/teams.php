<?php  // $Id: teams.php,v 1.3 2007/09/09 09:00:20 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    define("MAX_USERS_PER_PAGE", 5000);
    $id     = required_param('id', PARAM_INT);    // Course Module ID, or
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $teamid = optional_param('teamid');
    $cancel = optional_param('cancel');
    $add            = optional_param('add', 0, PARAM_BOOL);
    $remove         = optional_param('remove', 0, PARAM_BOOL);
    $showall        = optional_param('showall', 0, PARAM_BOOL);
    $searchtext     = optional_param('searchtext', '', PARAM_RAW); // search string
    $previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);
    $previoussearch = ($searchtext != '') or ($previoussearch) ? 1:0;


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

    $strteams    = get_string("teams", "webquest");
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests=  get_string("modulenameplural", "webquest");

    print_header_simple(format_string($webquest->name), "",
                "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strteams",
                "", "", true);


    add_to_log($course->id, "webquest", "update teams", "view.php?id=$cm->id", "$webquest->id");

    $straction = ($action) ? '-> '.get_string($action, 'webquest') : '';

    if ($action == 'editteam') {
        if (!isteacher($cm->course)){
            error("Only teachers can look at this page");
        }
        $form = get_record("webquest_teams","id",$teamid,"webquestid",$webquest->id );
        if (empty($form->name)){
            $form->name = "";
        }
        if (empty($form->description)){
            $form->description = "";
        }
        $string = get_string('cancel');
        if (!$teamid){
            print_heading_with_help(get_string("insertteam", "webquest"), "insertteam", "webquest");
        }
        else{
            print_heading_with_help(get_string("editteam", "webquest"), "editteam", "webquest");
        }
      ?>
        <form name="form" method="post" action="teams.php">
        <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
        <input type="hidden" name="action" value="insertteam" />
        <input type="hidden" name="teamid" value="<?php echo $teamid ?>" />
        <center><table cellpadding="5" border="1">
        <?php
    ///get the selected team
            echo "<tr valign=\"top\">\n";
            echo "<td align=\"right\"><b>". get_string("name").": </b></td>\n";
            echo "<td align=\"left\"><input type=\"text\" name=\"name\" size=\"30\" value=$form->name></td>";
            echo "</tr>";
            echo "<tr valign=\"top\">\n";
            echo "  <td align=\"right\"><b>". get_string("description").": </b></td>\n";
            echo "<td><textarea name=\"description\" rows=\"3\" cols=\"75\">".$form->description."</textarea>\n";
            echo "  </td></tr>\n";
    ?>
        </table><br />
        <input type="submit" value="<?php  print_string("savechanges") ?>" />
        <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
        </center>
        </form>
        <?php
    }

    if ($action == 'insertteam'){
        if (!isteacher($cm->course)){
            error("Only teachers can look at this page");
        }
        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=teams");
        }
        if (record_exists("webquest_teams","id",$teamid)){
            $team->name = $form->name;
            $team->description = $form->description;
            $team->id = $teamid;
            if (!update_record("webquest_teams",$team)){
                error("Could not update webquest team!");
                redirect("view.php?id=$cm->id&amp;action=teams");
            }
            redirect("view.php?id=$cm->id&amp;action=teams", get_string("wellsaved","webquest"));
        }
        unset($team);
        $team->webquestid = $webquest->id;
        $team->name = $form->name;
        $team->description = $form->description;
        if (!$team->id = insert_record("webquest_teams",$team)){
            error("Could not insert webquest team!");
            redirect("view.php?id=$cm->id&amp;action=teams");
        }
        redirect("view.php?id=$cm->id&amp;action=teams", get_string("wellsaved","webquest"));
    }

    if ($action == 'deleteteam'){
        if (!isteacher($course->id)){
            error("Only teachers can look at this page");
        }
        notice_yesno(get_string("suretodelteam","webquest"),
             "teams.php?action=deleteyesteam&amp;id=$id&amp;teamid=$teamid", "view.php?id=$id&amp;action=teams");
    }

    if ($action == 'deleteyesteam'){
        if(!isteacher($cm->course)){
            error("Only teachers can look at this page");
        }
        if(delete_records("webquest_team_members", "teamid", "$teamid")){
            if(delete_records("webquest_teams", "id", "$teamid")){
                delete_records("webquest_submissions","webquestid",$webquest->id,"userid",$teamid);
                redirect("view.php?id=$cm->id&amp;action=teams", get_string("deleted","webquest"));
            }else{
                error("Could not delete this webquest team!");
                redirect("view.php?id=$cm->id&amp;action=teams");
            }
        }else{
            error("Could not delete this team's members!");
            redirect("view.php?id=$cm->id&amp;action=teams");
        }
    }

    if($action == 'members'){
        if (!isteacher($cm->course)){
            error("Only teachers can look at this page");
        }
        $strassignstudents = get_string("assignstudents");
        $strexistingstudents   = get_string("members","webquest");
        $strnoexistingstudents = get_string("noexistingstudents");
        $strpotentialstudents  = get_string("potentialmembers","webquest");
        $strnopotentialstudents  = get_string("nopotentialstudents");
        $straddstudent    = get_string("addstudent");
        $strremovestudent = get_string("removestudent");
        $strsearch        = get_string("search");
        $strsearchresults  = get_string("searchresults");
        $strstudents   = get_string("students");
        $strshowall = get_string("showall");

        if($frm = data_submitted()){
            if ($add and !empty($frm->addselect)) {
                foreach ($frm->addselect as $addstudent) {
                    $addstudent = clean_param($addstudent, PARAM_INT);
                    $member->userid = $addstudent;
                    $member->teamid = $teamid;
                    $member->webquestid = $webquest->id;
                    if (! insert_record("webquest_team_members",$member)) {
                        error("Could not add member with id $addstudent to this team!");
                    }
                }
            } else if ($remove and !empty($frm->removeselect)) {
                foreach ($frm->removeselect as $removestudent) {
                    $removestudent = clean_param($removestudent, PARAM_INT);
                    if (! delete_records("webquest_team_members","userid",$removestudent,"teamid",$teamid)) {
                        error("Could not remove member with id $removestudent from this team!");
                    }
                }
            }else if ($showall) {
                $searchtext = '';
                $previoussearch = 0;
            }
        }
// get any team member of this Webquest... if an student is in a team he can't be in another team
        $existinguserarray = array();
        if ($exceptionsraw = get_records("webquest_team_members","webquestid",$webquest->id)){
            foreach ($exceptionsraw as $exception){
                $existinguserarray[] = $exception->userid;
            }
        }
        $existinguserlist = implode(',',$existinguserarray);
// get all members of this team...
        $students = array();
        if ($members = get_records("webquest_team_members","teamid",$teamid)){
            foreach ($members as $member){
                $listarray[] = $member->userid;
                if($teammembersraw = get_records("user","id",$member->userid)){
                    foreach ($teammembersraw as $teammember){
                        $teammembers[] = $teammember;
                    }
                }
            }
            $students = $teammembers;
        }

///Get search results excluding any users already in this course
        if (($searchtext != '') and $previoussearch) {
            $searchusers = get_course_students($cm->course,'firstname ASC, lastname ASC','',0,99999,
                            '','',NULL,$searchtext,'', $existinguserlist);
            $usercount = count($searchusers);
        }

/// if empty search then return all portential  members
        if (empty($searchusers)) {
            $potential = get_course_students($cm->course,'firstname ASC, lastname ASC','',0,99999,
                            '','',NULL,'','', $existinguserlist);
            $usercount = count($potential);
            $users = array();
            if ($usercount <= MAX_USERS_PER_PAGE) {
                $users = $potential;
            }
            if (empty($users)){
                $usercount=0;
            }
        }
        if ($teamraw = get_records("webquest_teams","id",$teamid)){
            foreach ($teamraw as $teampak){
                $team = $teampak;
            }
        }
/// print team description
        print_simple_box_start("center",'70%');
        echo '<div align="center">';
        p($team->description);
        echo '</div>';
        print_simple_box_end();
// print main part
        print_simple_box_start("center");
        include('members.html');
        print_simple_box_end();
    }
 print_footer($course);
