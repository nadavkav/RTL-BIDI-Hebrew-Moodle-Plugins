<?php
/**
 * Landing page for this module
 *
 * @author Mark Nielsen
 * @version $Id: view.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
 * @package pagemenu
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$a  = optional_param('a', 0, PARAM_INT); // Instance ID

list($cm, $course, $pagemenu) = pagemenu_get_basics($id, $a);

require_login($course->id);
require_capability('mod/pagemenu:view', get_context_instance(CONTEXT_MODULE, $cm->id));

pagemenu_print_header($cm, $course, $pagemenu);
print_box(pagemenu_build_menu($pagemenu->id, $pagemenu->render), 'boxwidthnormal boxaligncenter');
print_footer($course);

?>