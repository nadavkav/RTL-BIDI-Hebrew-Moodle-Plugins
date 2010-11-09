<?php
$ADMIN->add('reports', new admin_externalpage('reportcoursesize', get_string('coursesize', 'report_coursesize'), "$CFG->wwwroot/$CFG->admin/report/coursesize/index.php",'report/coursesize:view'));
?>
