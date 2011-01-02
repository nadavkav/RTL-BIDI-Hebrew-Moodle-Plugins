<?php
/**
 * Does some performance tests 
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
set_time_limit(0);

global $documents;
$folder=$CFG->dataroot.'/ousearch.loadtest';
if ($handle = opendir($folder)) {
    while (false !== ($file = readdir($handle))) {
        $documents[$file]=file($folder.'/'.$file);
    }
    closedir($handle);
}

function test2_ousearch_get_document($document) {
    return test_ousearch_get_document($document);
}
function test_ousearch_get_document($document) {
    global $CFG;
    $result=new StdClass;
    $result->activityname="Test activity";
    $result->activityurl="http://lies.and.more.lies/";
    global $documents;
    $result->title=trim($documents[$document->stringref][$document->intref1]);
    $result->content='';
    for($i=1;$i<$document->intref2;$i++) {
        $result->content.=htmlspecialchars($documents[$document->stringref][$document->intref1+$i]);
    }
    $result->url=$CFG->wwwroot.'/blocks/ousearch/loadtest/view.php?stringref='.$document->stringref.
        '&intref1='.$document->intref1.'&intref2='.$document->intref2;
    
    return $result;
    
}

function test_query($search,&$score,$displayresults) {

    global $PERF;
    $selects=$PERF->dbselects;
    $before=microtime(true);
    $results=$search->query();
    $selects=$PERF->dbselects-$selects;
    $time=round(microtime(true)-$before,3);
    
    $score->time+=$time;
    $score->count++;
    
    if($displayresults) {
        print '<h3>'.htmlspecialchars($search->querytext).'</h3>';
        $average=round($score->time/$score->count,2);
        print "<div>$selects queries ({$results->dbstart} rows used, {$results->dbrows} read), average $average s</div><ul>";
        if($results->success) {
            foreach($results->results as $result) {
                if($result->title==='') {
                    $result->title='(Blank line title)';
                }
                print '<li><div style="background:#eee">'.str_replace('highlight>','strong>',$result->title).'</div><div>'.
                    str_replace('highlight>','strong>',$result->summary).' [<a href="'.htmlspecialchars($result->url).'">View</a>]</div></li>';
            }
        } else {
            print '<li>Query failed: <strong>'.$results->problemword.'</strong></li>';
        }
        print '</ul>';
    }
}

$sh=new ousearch_search('"the time"');
$sh->set_plugin('test/test2');

$tests=array(
    new ousearch_search('a and'),
    new ousearch_search('23dfbsdg3456 and'),
    new ousearch_search('and 23dfbsdg3456'),
    new ousearch_search('virulent attack'),
    new ousearch_search('regiment moved'),
    new ousearch_search('adjutant galloping'),
    new ousearch_search('adjutant galloping -Napoleon'),
    new ousearch_search('adjutant galloping -"where Napoleon was standing"'),
    new ousearch_search('about an hour'),
    new ousearch_search('"about an hour"'),
    new ousearch_search('about french hour were an'),
    new ousearch_search('and'),
    new ousearch_search('"a and"'),
    new ousearch_search('"and maidens"'),
    new ousearch_search('"the time"')
    ,$sh
);

//$tests=array(new ousearch_search('"a and"'));

$results=array();
foreach($tests as $test) {
    $blankresult=new StdClass;
    $blankresult->time=0.0;
    $blankresult->count=0;
    $results[]=$blankresult;
}

// Test loop
define('OUSEARCH_TESTLOOPS',3);
print '<h1>Running tests '.OUSEARCH_TESTLOOPS.' times</h1>';
flush();
for($i=0;$i<OUSEARCH_TESTLOOPS;$i++) { 
    $last=$i===OUSEARCH_TESTLOOPS-1 ? true : false;
    if(!$last) {
        print '<h2>Test loop '.$i;
        flush();
    }
    for($pos=0;$pos<count($tests);$pos++) {
        test_query($tests[$pos],$results[$pos],$last);
        if(!$last) {
            print '.';
            flush();
        }
    }
    if(!$last) {
        print '</h2>';
    }
}

print_footer();
?>