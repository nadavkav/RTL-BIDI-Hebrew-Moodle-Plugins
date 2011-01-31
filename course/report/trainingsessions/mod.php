<?php // $Id: mod.php,v 1.1.2.1 2010/08/20 10:55:20 diml Exp $

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }

    if (has_capability('coursereport/trainingsessions:view', $context)) {
        echo '<p>';
        $trainingsessionsreport = get_string('trainingsessionsreport', 'report_trainingsessions');
        echo "<a href=\"{$CFG->wwwroot}/course/report/trainingsessions/index.php?id={$course->id}\">";
        echo "$trainingsessionsreport</a>\n";
        echo '</p>';
    }
?>
