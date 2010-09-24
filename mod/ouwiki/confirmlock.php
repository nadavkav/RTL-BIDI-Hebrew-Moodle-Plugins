<?php
/**
 * This script is called through AJAX. It confirms that a user is still 
 * trying to edit a page that they have locked (they haven't closed
 * their browser window or something). 
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require_once("../../config.php");

header('Content-Type: text/plain');    

if(empty($_POST['lockid'])) {
    print 'noid';
    exit;
}

$lockid=(int)$_POST['lockid'];
if($lock=get_record('ouwiki_locks','id',$lockid)) {
    $lock->seenat=time();
    update_record('ouwiki_locks',$lock);
    print 'ok';   
} else {
    print 'cancel'; // Tells user their lock has been cancelled.
}

?>