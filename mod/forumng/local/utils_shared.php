<?php
/**
 * General utility functions
 *
 * @copyright &copy; 2006 The Open University
 * @author D.A.Woolhead@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local
 *//** */

if(!function_exists('mkdir_recursive')) {
/**
 * This function exists because mkdir(folder,mode,TRUE) doesn't work on our server.
 * Safe to call even if folder already exists (checks)
 * @param string $folder Folder to create
 * @param int $mode Mode for creation (default 0755)
 * @return boolean True if folder (now) exists, false if there was a failure
 */
function mkdir_recursive($folder,$mode='') {
    if(is_dir($folder)) {
        return true; 
    }
    if ($mode == '') {
        global $CFG;
        $mode = $CFG->directorypermissions;
    }
    if(!mkdir_recursive(dirname($folder),$mode)) {
        return false; 
    }
    return mkdir($folder,$mode);
}
}

if(!function_exists('sql_int')) {
/**
 * Converts a number into a value suitable for SQL int field - null becomes null, 
 * true and false become 1/0, otherwise we cast to int.
 * @param mixed $number Input integer, boolean, or null
 * @return string Number for SQL
 */
function sql_int($number) {
    if(is_null($number)) {
        return 'NULL';
    } else if($number===true) {
        return 1;
    } else if($number===false) {
        return 0;
    } else {
        return (int)$number;
    }        
}
}

if(!function_exists('sql_char')) {
/**
 * Quotes a string using ADODB quoting, or returns 'NULL' for null
 * @param string $string String or null
 * @param object &$thisdb Optional ADODB database (otherwise uses global)
 * @return string String for putting into SQL statement
 */    
function sql_char($string,&$thisdb=null) {
    global $db;
    if(!$thisdb) {
        $thisdb =& $db;
    }
    if(is_null($string)) {
        return 'NULL';
    }
    else {
        return $thisdb->qstr($string);
    }        
}
}

if(!function_exists('db_do')) {
/**
 * Runs an SQL statement, automatically replacing fprefix_ with the newsfeed prefix,
 * and prefix_ with the base Moodle prefix. Throws an exception if there is any SQL error.
 * @param string $sql SQL statement
 * @param bool $trace If true, prints query to screen
 * @return object ADODB result set
 * @throws Exception if anything goes wrong
 */
function db_do($sql,$trace=false) {
    global $CFG,$db;
    $sql=preg_replace('/prefix\_/',$CFG->prefix,$sql);
    
    if($trace) {
        print '<pre>'.htmlspecialchars($sql).'</pre>';
    }
    
    if(!$rs=get_recordset_sql($sql,false)) {
        throw new Exception('SQL error: '.
            $db->ErrorMsg());  
    }
    return $rs;
} 
}

if(!function_exists('db_q')) {
/**
 * Quotes a string for SQL, escaping everything. Should be more reliable than addslashes since
 * it uses the SQL quotes from ADODB specifically for this database.
 * @param string $str String for quoting e.g.: frog's
 * @return string Quoted string e.g.: 'frog''s'
 */
function db_q($str) {
    global $db;
    return sql_char($str,$db);
}    
}

/**
 * Calls error() but also displays - currently as an HTML comment - the actual exception trace.
 * @param Exception $e Exception object
 * @param string $text Text (leave blank for exception message)
 * @param string $link Link to continue to (leave blank for default)
 */
function error_exception($e,$text='',$link='') {
    if($text=='') {
        $text=$e->getMessage();
    }
    if (!headers_sent()) {
        @header('HTTP/1.0 404 Not Found');
        print_header(get_string('error'));
    }
    print "<!--\n".$e->getTraceAsString()."\n-->";
    error($text,$link);
}

/**
 * adding a link to the computing guide
 * @param string $mod 
 * @return sting
 */
function get_link_to_computing_guide($mod){
    global $CFG;
    $modcomputingguide = $mod . '_computing_guide'; 
    $cfgmodcomputingguide = $CFG->$modcomputingguide; 

    // Computing guide links should not display if setting is empty 
    $cfgmodcomputingguide = trim($cfgmodcomputingguide);
    if (!$cfgmodcomputingguide) {
        return '';
    }
    $helpicon =  "<img class='iconhelp' src='" . $CFG->pixpath . "/help.gif' alt=''/>";
    $link = get_string('computingguide', $mod);
    return "<span class='computing-guide'><a href='$cfgmodcomputingguide'><span>$link</span> $helpicon</a></span>";
}

?>