<?php

/**
 * Lock editing page. Allows user to lock or unlock the editing of a wiki page
 *
 * @copyright &copy; 2009 The Open University
 * @author b.j.waddington@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require('basicpage.php');

$id = required_param('id',PARAM_INT);           // Course Module ID that defines wiki

// check we are using the annotation comment system
    $action = required_param('ouw_lock',PARAM_RAW);
    $pageid = required_param('ouw_pageid',PARAM_INT);

    // Get the current page version, creating page if needed
    $pageversion=ouwiki_get_current_page($subwiki,$pagename,OUWIKI_GETPAGE_ACCEPTNOVERSION);
    $wikiformfields=ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_FORM);
    $sectionfields='';

    // get the context and check user has the required capability
    require_capability('mod/ouwiki:lock',$context);

    // Get an editing lock
    list($lockok,$lock)=ouwiki_obtain_lock($ouwiki,$pageversion->pageid);

    // Handle case where page is locked by someone else
    if(!$lockok) {
    // Print header etc
    ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context);

    $details=new StdClass;
    $lockholder=get_record('user','id',$lock->userid);
    $details->name=fullname($lockholder);
    $details->lockedat=ouwiki_nice_date($lock->lockedat);
    $details->seenat=ouwiki_nice_date($lock->seenat);
    $pagelockedtitle=get_string('pagelockedtitle','ouwiki');
    $pagelockedtimeout='';
    if($lock->seenat > time()) {
        // When the 'seen at' value is greater than current time, that means
        // their lock has been automatically confirmed in advance because they
        // don't have JavaScript support.
        $details->nojs=ouwiki_nice_date($lock->seenat+OUWIKI_LOCK_PERSISTENCE);
        $pagelockeddetails=get_string('pagelockeddetailsnojs','ouwiki',$details);
    } else {
        $pagelockeddetails=get_string('pagelockeddetails','ouwiki',$details);
        if($lock->expiresat) {
            $pagelockedtimeout=get_string('pagelockedtimeout','ouwiki',userdate($lock->expiresat));
        }
    }
    $canoverride=has_capability('mod/ouwiki:overridelock',$context);
    $pagelockedoverride=$canoverride ? '<p>'.get_string('pagelockedoverride','ouwiki').'</p>' : '';
    $overridelock=get_string('overridelock','ouwiki');
    $overridebutton=$canoverride ? "
<form class='ouwiki_overridelock' action='override.php' method='post'>
  <input type='hidden' name='redirpage' value='view'>
  $wikiformfields
  <input type='submit' value='$overridelock' />
</form>
" : '';
    $cancel=get_string('cancel');
    $tryagain=get_string('tryagain','ouwiki');
    print "
<div id='ouwiki_lockinfo'>
  <h2>$pagelockedtitle</h2>
  <p>$pagelockeddetails $pagelockedtimeout</p>
  $pagelockedoverride
  <div class='ouwiki_lockinfobuttons'>
    <form action='edit.php' method='get'>
      $wikiformfields
      $sectionfields
      <input type='submit' value='$tryagain' />
    </form>
    <form action='view.php' method='get'>
      $wikiformfields
      <input type='submit' value='$cancel' />
    </form>
    $overridebutton
  </div>
</div>";
    print_footer($course);
    exit;
} 

    // The page is now locked to us!
    // To have got this far everything checks out so lock or unlock the page as requested
    if ($action == get_string('lockpage','ouwiki')) {
        ouwiki_lock_editing($pageid, true);
        $event = 'lock';
    } elseif ($action == get_string('unlockpage','ouwiki')) {  
        ouwiki_lock_editing($pageid, false);
        $event = 'unlock';
    }

// all done - release the editing lock...
ouwiki_release_lock($pageversion->pageid);

// add to moodle log...
$url = 'lock.php';
$url.=(strpos($url,'?')===false ? '?' : '&').'id='.$cm->id;
if($subwiki->groupid) {
    $url.='&group='.$subwiki->groupid;
}
if($subwiki->userid) {
    $url.='&user='.$subwiki->userid;
}
if($pagename) {
    $url.='&page='.urlencode($pagename);
    $info=$pagename;
} else {
    $info='';
}
add_to_log($course->id,'ouwiki',$event,$url,$info,$cm->id);

// redirect back to the view page.
redirect('view.php?id='.$id);

?>