<?php

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }

    echo '<p>';
    $timelinereport = 'Timeline';

    echo "<a href=\"{$CFG->wwwroot}/course/report/log_timeline/index.php?id={$course->id}\">";
    echo "$timelinereport</a>\n";

    echo '</p>';


?>