<?php // $Id: index.php,v 1.5 2007/04/30 17:08:54 skodak Exp $

    require_once('../../../config.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/course/report/log/lib.php');
    require_once($CFG->libdir.'/adminlib.php');

    admin_externalpage_setup('reportlivelog');

    admin_externalpage_print_header();

    $course = get_site();

    echo '<br />';
	echo '<iframe src="'.$CFG->wwwroot.'/course/report/log/live.php?id='.$course->id.'" width="100%" height="800">';
	echo '  <p>Your browser does not support iframes.</p>';
	echo '</iframe>';

    admin_externalpage_print_footer();

?>