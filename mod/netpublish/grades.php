<?php // $Id: grades.php,v 1.2 2007/04/27 09:10:51 janne Exp $

// This page is used to control general grades
// of particular netpublish.

    require('../../config.php');
    require('lib.php');

    $id      = required_param('id',      PARAM_INT);        // Course Module ID, or
    $skey    = required_param('sesskey', PARAM_ALPHANUM);   // Session key.


    if ($id) {
        // Get all that I need using only one query
        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect");
        }

        // Construct objects used in Moodle
        netpublish_set_std_classes ($cm, $course, $netpublish, $info);
        unset($info);

    }

    require_login($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if ( !has_capability('mod/netpublish:viewownrating', $context) ) {
        error("You're not allowed to view this page!",
              "$CFG->wwwroot/mod/netpublish/view.php?id=$cm->id");
    }

    if (! confirm_sesskey($skey) ) {
        error("Session key error!");
    }

    if ( $form = data_submitted() && has_capability('mod/netpublish:editrating', $context) ) {
        if (! empty($form->student) ) {
            foreach ($form->student as $studentid => $grade) {
                $studentid = clean_param($studentid, PARAM_INT);

                if (! $rsid = get_field("netpublish_grades", "id", "userid", $studentid,
                                        "publishid", $netpublish->id) ) {
                    // Insert new record
                    $datain = new stdClass;
                    $datain->publishid = clean_param($netpublish->id, PARAM_INT);
                    $datain->userid    = $studentid;
                    $datain->grade     = clean_param($grade, PARAM_RAW);

                    insert_record("netpublish_grades", $datain);
                    unset($datain);

                } else {
                    // Update existing record.
                    $update = new stdClass;
                    $update->id    = clean_param($rsid, PARAM_INT);
                    $update->grade = clean_param($grade, PARAM_RAW);

                    update_record("netpublish_grades", $update);
                }

            }
        }
    }

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    // Get strings
    $strnetpublishes = get_string("modulenameplural", "netpublish");
    $strnetpublish   = get_string("modulename", "netpublish");
    $strgrades       = get_string("grades");

    print_header("$course->shortname: $netpublish->name", "$course->fullname",
                 "$navigation <a href=\"index.php?id=$course->id\">$strnetpublishes</a> -> ".
                 "<a href=\"view.php?id=$cm->id\">$netpublish->name</a> -> $strgrades",
                  "", "", true, update_module_button($cm->id, $course->id, $strnetpublish));

    print_simple_box_start("center","100%");
    print_heading($strgrades, "center");

    $authors = netpublish_get_all_authors($netpublish->id, $course->id);

    echo '<form method="post" action="grades.php">' . "\n";

    $grades = make_grades_menu($netpublish->scale);

    $table = new stdClass;
    $table->head  = array("&nbsp;", get_string('students'), get_string('grades'));
    $table->align = array("center", "left","left");
    $table->width = '100%';
    $table->data = array();

    if (! empty($authors) ) {
        foreach ($authors as $author) {
            $currentgrade = get_record("netpublish_grades", "userid", $author->id,
                                                            "publishid", $netpublish->id);

            $row = array();
            array_push($row, print_user_picture($author->id, $course->id, $author->picture, '', true));
            array_push($row, fullname($author));

            $usergrade = !empty($currentgrade->grade) ? $currentgrade->grade : 0;
            $userfield = choose_from_menu($grades, "student[$author->id]", $usergrade, get_string("nograde")."...", "", "", true);

            array_push($row, $userfield);
            array_push($table->data, $row);
        }
    }

    print_table($table);

    echo '<input type="hidden" name="id" value="'. addslashes($cm->id) .'" />' . "\n";
    echo '<input type="hidden" name="sesskey" value="'. addslashes($USER->sesskey) .'" />' . "\n";
    echo '<p style="text-align: center;"><input type="submit" value="'. get_string("savechanges") .'" /></p>'. "\n";
    echo '</form>' . "\n";

    print_simple_box_end();
    print_footer($course);

//////////////////////////////// HELPER FUNCTIONS ////////////////////////////////

function netpublish_get_all_authors ($netpublishid, $courseid) {

    global $CFG;

    $netpublishid = clean_param($netpublishid, PARAM_INT);
    $courseid     = clean_param($courseid,     PARAM_INT);

    if ( empty($netpublishid) or empty($courseid) ) {
        return NULL;
    }

    $userids = get_records_sql("SELECT DISTINCT userid, teacherid, authors
                                FROM {$CFG->prefix}netpublish_articles
                                WHERE publishid = $netpublishid");

    $usertmp = array();

    if ( is_array($userids) ) {
        foreach ($userids as $user) {
            array_push($usertmp, $user->userid);
            array_push($usertmp, $user->teacherid);
            if (! empty($user->authors) ) {
                $strtoarr = explode(",", $user->authors);
                foreach ($strtoarr as $userid) {
                    array_push($usertmp, $userid);
                }
            }
        }
    }

    unset($userids);
    $usertmp = array_unique($usertmp);
    sort($usertmp);
    $userids = implode(",", $usertmp);
    unset($usertmp);
    $userids = addslashes($userids);

    if ( empty($userids) ) {
        return NULL;
    }

    // Exclude teachers from list
    $exclude = NULL;
    if ( $teachers = get_course_teachers($courseid) ) {
        $count = 1;
        foreach ( $teachers as $teacher ) {
            if ( $count > 1 ) {
                $exclude .= ',';
            }
            $exclude .= $teacher->id;
            $count++;
        }
    }

    $exclude = !is_null($exclude) ? " AND u.id NOT IN ($exclude)" : '';
    $authors = get_records_sql("SELECT u.id, u.firstname, u.lastname, u.picture
                                FROM {$CFG->prefix}user u
                                WHERE u.id IN ($userids)$exclude");

    return $authors;

}

?>