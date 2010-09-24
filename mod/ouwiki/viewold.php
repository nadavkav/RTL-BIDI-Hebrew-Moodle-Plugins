<?php
/**
 * 'View old' page. Displays old versions of wiki pages.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

$countasview = true;
require('basicpage.php');

$versionid=required_param('version');

// Get the current page version
$pageversion=ouwiki_get_page_version($subwiki,$pagename,$versionid);
if(!$pageversion) {
    error('Unknown page version');
}

// Check permission - Allow anyone with delete page capability to view a deleted page version
$candelete = has_capability('mod/ouwiki:deletepage', $context);
if (!empty($pageversion->deletedat) && !$candelete) {
    print_error('viewdeletedversionerrorcapability', 'ouwiki');
}

// Get previous and next versions
$prevnext=ouwiki_get_prevnext_version_details($pageversion);

// Get basic wiki parameters
$wikiparams=ouwiki_display_wiki_parameters($pagename,$subwiki,$cm);

ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context,
    array(
        array('name'=>get_string('tab_history','ouwiki'),'link'=>'history.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm),'type'=>'ouwiki'),
        array('name'=>get_string('oldversion','ouwiki'),'type'=>'ouwiki')),
        true,true);

// Information box
if($prevnext->prev) {
    $date=ouwiki_nice_date($prevnext->prev->timecreated);
    $prev=link_arrow_left(get_string('previousversion','ouwiki',$date), "viewold.php?$wikiparams&amp;version={$prevnext->prev->versionid}");
} else {
    $prev='';
}
if($prevnext->next) {
    if($prevnext->next->versionid==$pageversion->currentversionid) {
        $date=get_string('currentversion','ouwiki');
        $next=link_arrow_right(get_string('nextversion','ouwiki',$date), "view.php?$wikiparams");
    } else {
        $date=ouwiki_nice_date($prevnext->next->timecreated);
        $next=link_arrow_right(get_string('nextversion','ouwiki',$date), "viewold.php?$wikiparams&amp;version={$prevnext->next->versionid}");
    }
} else {
    $next='';
}
$date=userdate($pageversion->timecreated);
$pageversion->id=$pageversion->userid; // To make it look like a user object
$name=ouwiki_display_user($pageversion,$course->id);
$savedby=get_string('savedby','ouwiki',$name);

$stradvice = get_string('advice_viewold','ouwiki');
if (!empty($pageversion->deletedat)) {
    $stradvice = get_string('advice_viewdeleted','ouwiki');
}

print "
<div class='ouw_oldversion'>
  <h1>$date <span class='ouw_person'>($savedby)</span></h1>
  <p>".$stradvice."</p>
  <div class='ouw_prev'>$prev</div>
  <div class='ouw_next'>$next</div>
  <div class='clearer'></div>
</div>";

// Print page content
$data = ouwiki_display_page($subwiki,$cm,$pageversion);
print($data[0]);

// Footer
ouwiki_print_footer($course,$cm,$subwiki,$pagename);
?>
