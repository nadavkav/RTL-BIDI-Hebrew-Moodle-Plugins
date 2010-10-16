<?php
/**
 * Responds to AJAX requests from the user field type.
 *
 * @copyright &copy; 2007 The Open University
 * @author T.J.Hunt@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package userrolesreport
 *//** */

require_once(dirname(__FILE__) . "/../../../config.php");
if (!isset($_GET['sesskey']) && isset($_GET['prefix'])) {
    header("HTTP/1.0 403 Forbidden");
    die;
}
if (!confirm_sesskey() && has_capability('moodle/user:viewdetails', get_context_instance(CONTEXT_SYSTEM))) {
    header("HTTP/1.0 403 Forbidden");
    die;
}
$prefix = required_param('prefix', PARAM_RAW);
$prefix = addslashes(preg_replace('/[^-\'a-zA-Z1-9]/', '', $prefix));
$ilike = sql_ilike();
$users = get_records_select('user',
        "firstname $ilike '$prefix%' OR lastname $ilike '$prefix%' OR username $ilike '$prefix%' OR idnumber $ilike '$prefix%' and deleted=0",
        'lastname', 'username, firstname, lastname', 0, 50);
if (!$users) {
    $users = array();
}
header('Content-type: text/plain; charset=utf-8');
foreach ($users as $user) {
    echo $user->username, "\t", fullname($user), "\n";
}
?>
