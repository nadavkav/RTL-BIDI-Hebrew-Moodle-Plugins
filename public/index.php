<?php
/**
 * Created by Nadav Kavalerchik
 * User: nadavkav@gmail.com
 * Date: 6/12/11
 *
 * Display all Open Courses on this system
 */

    require_once('../config.php');

    print_header();

    echo "<div id=\"title\">".get_string('opencourses','public','',$CFG->dirroot.'/public/lang/')."</div><br/>";

    $sql = 'SELECT * FROM '.$CFG->prefix.'course WHERE visible = 1 AND guest = 1';
    $courses = get_records_sql($sql);
    foreach ($courses as $course) {
        echo "<div id=\"course\"><a href=\"$CFG->wwwroot/course/view.php?id=$course->id&username=guest\">$course->fullname</a></div>";

        echo get_string('teachers','public','',$CFG->dirroot.'/public/lang/');
        $sql_teachers = 'SELECT ra.userid,u.firstname,u.lastname,u.email
                          FROM '.$CFG->prefix.'role_assignments AS ra
                          JOIN '.$CFG->prefix.'context AS ctx ON ra.contextid = ctx.id
                          JOIN '.$CFG->prefix.'user as u ON u.id = ra.userid
                          WHERE ra.roleid = 3 AND ctx.instanceid = '.$course->id;
        $teachers = get_records_sql($sql_teachers);
        foreach ($teachers as $teacher) {
            echo " <a href='mailto:$teacher->email'>$teacher->firstname $teacher->lastname,</a> ";
        }
        echo "<br/>";

        echo "<div id=\"summary\">$course->summary</div>";
    }
    print_footer();


?>
<style>
    #title {text-align:center;font-size:1.8em;}
    #course {background-color:beige;font-size:1.6em;}
    #summary {padding:10px;padding-left:15px;}
</style>