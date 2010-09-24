<?php
/**
 * Diff. Displays the difference between two versions of a wiki page.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

$countasview=true;
require('basicpage.php');

$v1=required_param('v1',PARAM_INT);
$v2=required_param('v2',PARAM_INT);

// Check permission - Allow anyone with delete page capability to compare with a deleted page version
$candelete = has_capability('mod/ouwiki:deletepage', $context);

// Get the current page [and current version, which we ignore]
$pageversion1=ouwiki_get_page_version($subwiki,$pagename,$v1);
$pageversion2=ouwiki_get_page_version($subwiki,$pagename,$v2);
if(!$pageversion1 || !$pageversion2 ||
   ((!empty($pageversion1->deletedat) || !empty($pageversion2->deletedat)) && !$candelete)) {
    error('Specified version does not exist');
}
if($pageversion1>=$pageversion2) {
    error('Versions out of order');
}

// Print header
ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context,
    array(
        array('name'=>get_string('tab_history','ouwiki'),'link'=>'history.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm),'type'=>'ouwiki'),
        array('name'=>get_string('changesnav','ouwiki'),'type'=>'ouwiki')),
    true,true);

    

// Obtain difference between two versions
list($diff1,$diff2,$numchanges)=ouwiki_diff_html($pageversion1->xhtml,$pageversion2->xhtml);

// if there are no changes then check if there are any annotations in the new version
if ($numchanges == 0) {
    $annotations = ouwiki_get_annotations($pageversion2);
    if (count($annotations) === 0) {
        $advice = get_string('diff_nochanges', 'ouwiki');
    } else {
        $advice = get_string('diff_someannotations','ouwiki');
    }
} else {
    $advice = get_string('advice_diff','ouwiki');
}

print '<p class="ouw_advice">'.
     $advice.' '.
     get_string('returntohistory','ouwiki',
    'history.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm)).'</p>';

// Obtain difference between two versions
list($diff1,$diff2)=ouwiki_diff_html($pageversion1->xhtml,$pageversion2->xhtml);

// Disply the two versions
print '<div class="ouw_left">';
$date=userdate($pageversion1->timecreated);
$pageversion1->id=$pageversion1->userid; // To make it look like a user object
$name=ouwiki_display_user($pageversion1,$course->id);
$savedby=get_string('savedby','ouwiki',$name);
$olderversion=get_string('olderversion','ouwiki');
$newerversion=get_string('newerversion','ouwiki');
print "<div class='ouw_versionbox'><h1 class='accesshide'>$olderversion</h1><div class='ouw_date'>$date</div><div class='ouw_person'>($savedby)</div></div><div class='ouw_diff ouwiki_content'>";
print $diff1;
print '</div></div><div class="ouw_right">';
$date=userdate($pageversion2->timecreated);
$pageversion2->id=$pageversion2->userid; // To make it look like a user object
$name=ouwiki_display_user($pageversion2,$course->id);
$savedby=get_string('savedby','ouwiki',$name);
print "<div class='ouw_versionbox'><h1 class='accesshide'>$newerversion</h1><div class='ouw_date'>$date</div><div class='ouw_person'>($savedby)</div></div><div class='ouw_diff ouwiki_content'>";
print $diff2;
print '</div></div><div class="clearer"></div>';

// Footer
ouwiki_print_footer($course,$cm,$subwiki,$pagename);
?>
