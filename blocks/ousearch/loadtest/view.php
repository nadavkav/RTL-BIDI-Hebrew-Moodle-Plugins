<?php
/**
 * Views sections of documents added for load-testing
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ousearch
 *//** */
require_once('../../../config.php');
require_once('../searchlib.php');
require_login();
global $CFG;
if(!isadmin()) {
    error('Must be admin to access this page');
}
$stringref=required_param('stringref',PARAM_FILE);
$intref1=required_param('intref1',PARAM_INT);
$intref2=required_param('intref2',PARAM_INT);

$folder=$CFG->dataroot.'/ousearch.loadtest';
$lines=file($folder.'/'.$stringref);

print_header();

print '<pre>';
for($i=0;$i<$intref2;$i++) {
    print htmlspecialchars($lines[$intref1+$i]);
}
print '</pre>';

print_footer();
?>