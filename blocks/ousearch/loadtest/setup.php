<?php
/**
 * Creates many documents to test searching. 
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
    print '<p>Are you sure you want to create a whole bunch of test documents? This may take a while and make your database huge. Do not do it on a production server under any circumstances!</p>';
    print '<p>If you do want to, you will need a very large ASCII text file. I use War and Peace from Project Gutenberg. Upload the file here.</p>';
    print '<form enctype="multipart/form-data" action="setup.php" method="post">
<input type="hidden" name="goahead" value="1" />
<input type="file" name="textfile"/>
<input type="submit" value="Do it!"/>
</form>';
    print_footer();
    exit;     
}
$file=$_FILES['textfile']['tmp_name'];
$lines=file($file);
$folder=$CFG->dataroot.'/ousearch.loadtest';
@mkdir($folder);
$filename=preg_replace('/[^A-Za-z0-9_.]/','',$_FILES['textfile']['name']);
rename($file,$folder.'/'.$filename);

// Get list of group and user IDs to use
$dbgroups=get_records('groups','','','','id');
if(!$dbgroups) {
    $dbgroups=array();
}
$dbusers=get_records('user','','','','id');
if(!$dbusers) {
    $dbusers=array();
}
$groups=array();
foreach($dbgroups as $group) {
    $groups[]=$group->id;
}
$users=array();
foreach($dbusers as $user) {
    $users[]=$user->id;
}

set_time_limit(0);

print '<h1>Importing '.htmlspecialchars($_FILES['textfile']['name']).'</h1>';
$numlines=count($lines);
print '<ul><li>'.$numlines.' lines</li>';
flush();
$lastreport=0;
$pos=0;
$type_none=0; $type_user=0; $type_group=0; $totaltime=0;
$documents=0;
while($pos<$numlines) {
    // Pick how many lines to use for one 'document'
    $doclines=rand(5,200);
    if($pos+$doclines > $numlines) {
        $doclines=$numlines-$pos;
    }
        
    $start=$pos;
    
    // Set up document
    $document=new ousearch_document();
    $document->init_test('test2');
    
    // Refs define document and place in it
    $document->set_string_ref($filename);
    $document->set_int_refs($start,$doclines);

    // Type (group.user)
    $type=rand(0,99);
    if($type<50) {
        // 50% None 
        $type_none++;
    } else if($type<90) {
        // 40% Group
        $document->set_group_id($groups[rand(0,count($groups)-1)]);
        $type_group++;
    } else {
        // 10% User
        $document->set_user_id($users[rand(0,count($users)-1)]);
        $type_user++;
    }
    
    // Update (create) document
    $title=trim($lines[$pos++]);
    $content='';
    for($i=1;$i<$doclines;$i++) {
        $content.=htmlspecialchars($lines[$pos++]);
    }

    $before=microtime(true);    
    $document->update($title,$content);
    $totaltime+=microtime(true)-$before;   
    
    $documents++;
    
    if($pos - $lastreport > 1000) {
        print "<li>Processed $pos lines ($documents documents)</li>";
        $lastreport=$pos;
        flush();
    }
}
$averagetime=round($totaltime/$documents,2);
print "<li>Done. Created $alldocuments documents (average creation time $averagetime s). $type_none standard documents, $type_group group documents, $type_user user documents.</li>"; 
print '</ul>';

print_footer();
?>