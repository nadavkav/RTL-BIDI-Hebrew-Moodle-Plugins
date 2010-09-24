<?php
/**
 * History page. Shows list of all previous versions of a page.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

if (!array_key_exists('compare',$_GET)) {
    $countasview=true;
}

require('basicpage.php');

// Check if this is a compare request
if(array_key_exists('compare',$_GET)) {
    // OK, figure out the version numbers and redirect to diff.php (this
    // is done here just so diff.php doesn't have to worry about the manky
    // format)
    $versions=array();
    foreach($_GET as $name=>$value) {
        if(preg_match('/^v[0-9]+$/',$name)) {
            $versions[]=substr($name,1);
        }
    }
    if(count($versions)!=2) {
        error(get_string('mustspecify2','ouwiki'));
    }
    sort($versions,SORT_NUMERIC);
    $wikiurlparams=html_entity_decode(ouwiki_display_wiki_parameters($pagename,$subwiki,$cm),ENT_QUOTES);
    redirect("diff.php?$wikiurlparams&v1={$versions[0]}&v2={$versions[1]}");
    exit;
}

// Get information about page
$pageversion=ouwiki_get_current_page($subwiki,$pagename,OUWIKI_GETPAGE_CREATE);
$wikiparams=ouwiki_display_wiki_parameters($pagename,$subwiki,$cm);
$wikiinputs=ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_FORM);

// Do header
$atomurl=$CFG->wwwroot.'/mod/ouwiki/feed-history.php?'.$wikiparams.
    '&amp;magic='.$subwiki->magic;
$rssurl=$CFG->wwwroot.'/mod/ouwiki/feed-history.php?'.$wikiparams.
    '&amp;magic='.$subwiki->magic.'&amp;format=rss';
$meta='<link rel="alternate" type="application/atom+xml" title="Atom feed" '. 
    'href="'.$atomurl.'" />';

$title = get_string('historyfor','ouwiki');
$wikiname=format_string(htmlspecialchars($ouwiki->name));
$name = '';
if($pagename) {
    $name = $pagename; 
} else {		
    $name=get_string('startpage','ouwiki');   	
}
    	
$title = $wikiname.' - '.$title.' : '.$name;

ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context,null,false,false,$meta, $title);

// get delete capability for wiki page versions and associated delete strings
$candelete = has_capability('mod/ouwiki:deletepage', $context);
$strdelete = get_string('delete');
$strdeleted = get_string('deleted');
$strundelete = get_string('undelete', 'ouwiki');

// Get history
$changes=ouwiki_get_page_history($pageversion->pageid, $candelete);
ouwiki_print_tabs('history',$pagename,$subwiki,$cm,$context,true,$pageversion->locked);


print_string('advice_history','ouwiki',"view.php?$wikiparams");

// Print message about deleted things being invisible to students so admins
// don't get confused
if ($candelete) {
    $found = false;
    foreach($changes as $change) {
        if (!empty($change->deletedat)) {
            $found = true;
            break;
        }
    }
    if ($found) {
        print '<p class="ouw_deletedpageinfo">'.get_string('pagedeletedinfo','ouwiki').'</p>';
    }    
}

$strdate=get_string('date');
$strtime=get_string('time');
$strperson=get_string('changedby','ouwiki');
$strcompare=get_string('compare','ouwiki');
$strview=get_string('view','ouwiki');
$stractionheading=get_string('actionheading','ouwiki');
print "
<form name='ouw_history' class='ouw_history' method='get' action='history.php'>
<input type='hidden' name='compare' value='1'/>
$wikiinputs
<table>
<tr><th scope='col'>$strdate</th><th scope='col'>$strtime</th><th><span class='accesshide'>$stractionheading</span>
</th>
  <th scope='col'>$strperson</th><th scope='col'><span class='accesshide'>$strcompare</span></th></tr>
";


$strchanges=get_string('changes','ouwiki');
$strview=get_string('view');
$strrevert = get_string('revert');
$lastdate='';
$changeindex=0;
$changeids=array_keys($changes);
foreach($changes as $change) {
    
    $date=userdate($change->timecreated,get_string('strftimedate'));
    if($date==$lastdate) {
        $date='';
    } else {
        $lastdate=$date;
    }
    $time=ouwiki_recent_span($change->timecreated).userdate($change->timecreated,get_string('strftimetime')).'</span>';
     
    $createdtime = userdate($change->timecreated,get_string('strftimetime'));
    $nextchange=false;
    if($changeindex+1<count($changes)) {
        $nextchange=$changes[$changeids[$changeindex+1]];
    }

    if($nextchange) {
        $changelink=" <small>(<a href='diff.php?$wikiparams&amp;v2={$change->versionid}&amp;v1={$nextchange->versionid}'>$strchanges<span class=\"accesshide\"> $lastdate $createdtime</span></a>)</small>";
    } else {
        $changelink='';
    }
    $revertlink = '';
    if($change->versionid==$pageversion->versionid) {
        $viewlink="view.php?$wikiparams";
    } else {
        $viewlink="viewold.php?$wikiparams&amp;version={$change->versionid}";
        $revertlink = " <a href=revert.php?$wikiparams&amp;version={$change->versionid}>$strrevert</a>";
    }

    // set delete link as appropriate
    $deletedclass = '';
    $deletedstr = '';
    $deletelink = '';
    if ($candelete) {
        $deletestr = $strdelete;
        if (!empty($change->deletedat)) {
            $revertlink = '';
            $deletedclass = " class='ouw_deletedrow'";
            $deletestr = $strundelete;
            $deletedstr = "<span class='ouw_deleted'>$strdeleted</span>";
        }
        $deletelink = " <a href=delete.php?$wikiparams&amp;version={$change->versionid}>$deletestr</a>";
    }

    if($change->id) {
        $userlink=ouwiki_display_user($change,$course->id);
    } else {
        $userlink='';
    }
    
    $a=new StdClass;
    $a->lastdate=$lastdate;
    $a->createdtime=$createdtime;
    
    $selectaccessibility = get_string('historycompareaccessibility', 'ouwiki', $a);
    
    print "
<tr$deletedclass>
  <td class='ouw_leftcol'>$date</td><td>$time $deletedstr</td>  
  <td class='actions'><a href='$viewlink'>$strview</a>$deletelink$revertlink$changelink</td>  
  <td>$userlink</td>
  <td class='check ouw_rightcol'><label for='v{$change->versionid}' class=\"accesshide\"> $selectaccessibility </label>
  <input type='checkbox' name='v{$change->versionid}' id='v{$change->versionid}' onclick='ouw_check()' /></td>
</tr>";
    $changeindex++;
}

$strcompareselected=get_string('compareselected','ouwiki');
print "
<tr><td colspan='5' class='comparebutton'><input id='ouw_comparebutton' type='submit' value='$strcompareselected' /></td></tr>
</table></form>";

// The page works without JS. If you do have it, though, this script ensures
// you can't click compare without having two versions selected.
print '
<script type="text/javascript">
var comparebutton=document.getElementById("ouw_comparebutton");
comparebutton.disabled=true;

function ouw_check() {
    var elements=document.forms["ouw_history"].elements;
    var checked=0;
    for(var i=0;i<elements.length;i++) {
        if(/^v[0-9]+/.test(elements[i].name) && elements[i].checked) {
            checked++;
        }
    }
    comparebutton.disabled=checked!=2;
}

</script>
';   

$a->atom=$atomurl;
$a->rss=$rssurl;
print '<p class="ouw_subscribe"><a href="'.$atomurl.'" title="'.get_string('feedalt','ouwiki').
    '"><img src="'.$CFG->pixpath.'/i/rss.gif" alt=""/></a> <span>'.
    get_string('feedsubscribe','ouwiki',$a).'</span></p>';

// Footer
ouwiki_print_footer($course,$cm,$subwiki,$pagename);
?>
