<?php
/**
 * Exception codes for local utilities. (Note: these codes are used only
 * for the various utils in local/ itself and not in subfolders, though
 * I guess it could be reused if needed.)
 *
 * @copyright &copy; 2006 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local
 *//** */

if(!defined('EXN_LOCAL_BASE')) {
    define('EXN_LOCAL_BASE',10500);
    
    define('EXN_LOCAL_FORMLOAD',EXN_LOCAL_BASE+1);
    define('EXN_LOCAL_UNKNOwNUSERNAME',EXN_LOCAL_BASE+2);
    define('EXN_LOCAL_FRIDAYEDITING_INSERT_FAILED',EXN_LOCAL_BASE+3);
    define('EXN_LOCAL_FRIDAYEDITING_DELETE_FAILED',EXN_LOCAL_BASE+4);
    
    define('EXN_LOCAL_BACKUPWRITE',EXN_LOCAL_BASE+21);
    define('EXN_LOCAL_BACKUPEMPTYSTACK',EXN_LOCAL_BASE+22);
    define('EXN_LOCAL_BACKUPMISMATCH',EXN_LOCAL_BASE+23);
    define('EXN_LOCAL_TRANSACTIONENTER',EXN_LOCAL_BASE+24);
    define('EXN_LOCAL_TRANSACTIONLEAVE',EXN_LOCAL_BASE+25);
    define('EXN_LOCAL_BACKUPCOPY',EXN_LOCAL_BASE+26);
    define('EXN_LOCAL_BACKUPFOLDER',EXN_LOCAL_BASE+27);
    
    define('EXN_LOCAL_ASSESSMENTREPORTDB',EXN_LOCAL_BASE+30);
    define('EXN_LOCAL_ASSESSMENTCOURSEDB',EXN_LOCAL_BASE+31);
    define('EXN_LOCAL_ASSESSMENTEXAMDB',EXN_LOCAL_BASE+32);
    define('EXN_LOCAL_ASSESSMENTREPORTOKDB',EXN_LOCAL_BASE+33);
    define('EXN_LOCAL_ASSESSMENTWIPEDB',EXN_LOCAL_BASE+34);
    define('EXN_LOCAL_ASSESSMENTINSERTDB',EXN_LOCAL_BASE+35);
}
?>