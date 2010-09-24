<?php
/**
 * This script is called via an IMG tag when JavaScript is disabled.
 * It updates the lock to allow 15 minutes without requiring confirmation. 
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require_once("../../config.php");
require_once("ouwiki.php");

$lockid=required_param('lockid',PARAM_INT);
if($lock=get_record('ouwiki_locks','id',$lockid)) {
    $lock->seenat=time()+OUWIKI_LOCK_NOJS;
    update_record('ouwiki_locks',$lock);
    
    header('Content-Type: image/png');
    readfile('dot.png');
    exit;
} else {
    error('No such lock');
}

?>