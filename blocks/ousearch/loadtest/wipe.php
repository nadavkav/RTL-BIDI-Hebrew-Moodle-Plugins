<?php
/**
 * Wipes all the load test data. 
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
print_header();
if(empty($_POST['goahead'])) {
    print '<p>Are you sure you want to wipe all the load test data?</p>';
    print '<form enctype="multipart/form-data" action="wipe.php" method="post">
<input type="hidden" name="goahead" value="1" />
<input type="submit" value="Do it!"/>
</form>';
    print_footer();
    exit;     
}

print '<ul><li>Deleting occurences</li>';
flush();

$before=get_field_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'block_ousearch_occurrences');

$count=0;
$rs=get_recordset('block_ousearch_documents','plugin','test/test','','id');
while ($rec = rs_fetch_next_record($rs)) {
    delete_records('block_ousearch_occurrences','documentid',$rec->id);
    delete_records('block_ousearch_documents','id',$rec->id);
    $count++;
}
rs_close($rs);
$after=get_field_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'block_ousearch_occurrences');
$occurrences=$before-$after;
print "<li>$count documents deleted ($occurrences occurrences)</li>"; 
flush();

$before=get_field_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'block_ousearch_words');
delete_records_select('block_ousearch_words',
'NOT(id IN (select wordid FROM '.$CFG->prefix.'block_ousearch_occurrences))'); 
$after=get_field_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'block_ousearch_words');
$words=$before-$after;

print "<li>$words words deleted</li></ul>"; 

print_footer();
?>