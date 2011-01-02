<?php
// For admins to use to force updates of search indexes on a course.

require_once('../../config.php');
require_once('searchlib.php');
require_capability('moodle/site:config',get_context_instance(CONTEXT_SYSTEM));

$courseid=required_param('course',PARAM_INT);
$module=required_param('module',PARAM_ALPHA);

require_once($CFG->dirroot.'/mod/'.$module.'/lib.php');
$function=$module.='_ousearch_update_all';
print_header();

print "<h1>Re-indexing documents for module $module on course $courseid</h1><ul>";

$function(true,$courseid);

print "</ul>";

print_footer();
?>
