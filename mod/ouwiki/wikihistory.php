<?php
/**
 * 'Wiki changes' page. Displays a list of recent changes to the wiki. You
 * can choose to view all changes or only new pages.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

$countasview = true;
require('basicpage.php');

if (class_exists('ouflags')) {
    require_once('../../local/mobile/ou_lib.php');
    global $OUMOBILESUPPORT;
    $OUMOBILESUPPORT = true;
    ou_set_is_mobile(ou_get_is_mobile_from_cookies());
}

define('OUWIKI_PAGESIZE',50);

$newpages=optional_param('type','',PARAM_ALPHA)=='pages';
$from=optional_param('from','',PARAM_INT);

// Get basic wiki parameters
$wikiparams=ouwiki_display_wiki_parameters(null,$subwiki,$cm);
$tabparams=$newpages ? $wikiparams.'&amp;type=pages' : $wikiparams;

// Get changes
if($newpages) {
    $changes=ouwiki_get_subwiki_recentpages($subwiki->id,$from,OUWIKI_PAGESIZE+1);
} else {
    $changes=ouwiki_get_subwiki_recentchanges($subwiki->id,$from,OUWIKI_PAGESIZE+1);
}

// Do header
$atomurl=$CFG->wwwroot.'/mod/ouwiki/feed-wikihistory.php?'.$wikiparams.
    ($newpages?'&amp;type=pages' : '').'&amp;magic='.$subwiki->magic;
$rssurl=$CFG->wwwroot.'/mod/ouwiki/feed-wikihistory.php?'.$wikiparams.
    ($newpages?'&amp;type=pages' : '').'&amp;magic='.$subwiki->magic.'&amp;format=rss';
$meta='<link rel="alternate" type="application/atom+xml" title="Atom feed" '. 
    'href="'.$atomurl.'" />';

// bug #3542
$wikiname=format_string(htmlspecialchars($ouwiki->name));
$title = $wikiname.' - '.get_string('wikirecentchanges','ouwiki');

if (class_exists('ouflags') && ou_get_is_mobile()){
    ou_mobile_configure_theme();
}

ouwiki_print_start($ouwiki,$cm,$course,$subwiki,
    $from>0 
        ? get_string('wikirecentchanges_from','ouwiki',(int)($from/OUWIKI_PAGESIZE)+1)
        : get_string('wikirecentchanges','ouwiki'),
    $context,null,false,false,$meta, $title);

// Print tabs for selecting all changes/new pages
$tabrow=array();
$tabrow[]=new tabobject('changes','wikihistory.php?'.$wikiparams,
    get_string('tab_index_changes','ouwiki'));
$tabrow[]=new tabobject('pages','wikihistory.php?'.$wikiparams.'&amp;type=pages',
    get_string('tab_index_pages','ouwiki'));   
$tabs=array();
$tabs[]=$tabrow;
print_tabs($tabs,$newpages ? 'pages' : 'changes');
print '<div id="ouwiki_belowtabs">';

// On first page, show information
if(!$from) {
    print get_string('advice_wikirecentchanges_'.($newpages?'pages':'changes'.(!empty($CFG->ouwikienablecurrentpagehighlight) ? '' : '_nohighlight')),'ouwiki').'</p>';
}

$strdate=get_string('date');
$strtime=get_string('time');
$strpage=get_string('page','ouwiki');
$strperson=get_string('changedby','ouwiki');
$strview=get_string('view','ouwiki');
print "
<table>
<tr><th scope='col'>$strdate</th><th scope='col'>$strtime</th><th scope='col'>$strpage</th>".
($newpages?'':'<th><span class="accesshide">'.$strview.'</span></th>')."
  <th scope='col'>$strperson</th></tr>
";

$strchanges=get_string('changes','ouwiki');
$strview=get_string('view');
$lastdate='';
$count=0;
foreach($changes as $change) {
    $count++;
    if($count>OUWIKI_PAGESIZE) {
        break;
    }
    
    $pageparams=ouwiki_display_wiki_parameters($change->title,$subwiki,$cm);
    
    $date=userdate($change->timecreated,get_string('strftimedate'));
    if($date==$lastdate) {
        $date='';
    } else {
        $lastdate=$date;
    }
    $time=ouwiki_recent_span($change->timecreated).userdate($change->timecreated,get_string('strftimetime')).'</span>';
    
    $page=$change->title ? htmlspecialchars($change->title) : get_string('startpage','ouwiki');
    if(!empty($change->previousversionid)) {
        $changelink=" <small>(<a href='diff.php?$pageparams&amp;v2={$change->versionid}&amp;v1={$change->previousversionid}'>$strchanges</a>)</small>";
    } else {
        $changelink=' <small>('.get_string('newpage','ouwiki').')</small>';
    }

    $current='';
    if($change->versionid==$change->currentversionid || $newpages) {
        $viewlink="view.php?$pageparams";
        if(!$newpages && !empty($CFG->ouwikienablecurrentpagehighlight)) {
            $current=' class="current"';
        }
    } else {
        $viewlink="viewold.php?$pageparams&amp;version={$change->versionid}";
    }

    $change->id=$change->userid;
    if($change->id) {
        $userlink=ouwiki_display_user($change,$course->id);
    } else {
        $userlink='';
    }
    
    if($newpages) {
        $actions='';
        $page="<a href='$viewlink'>$page</a>";
    } else {
        $actions="<td class='actions'><a href='$viewlink'>$strview</a>$changelink</td>";          
    }
    
    
    // see bug #3611
    if(!empty($current) && !empty($CFG->ouwikienablecurrentpagehighlight)) {
    	
    	// current page so add accessibility stuff
    	
    	$accessiblityhide = '<span class="accesshide">'.get_string('currentversionof','ouwiki').'</span>';		
		$dummy = $page;
		$page = $accessiblityhide.$dummy;    	
    }
    
    print "
<tr$current>
  <td class='ouw_leftcol'>$date</td><td>$time</td><td>$page</td>
  $actions
  <td class='ouw_rightcol'>$userlink</td>
</tr>";
}

print '</table>';

if($count > OUWIKI_PAGESIZE || $from>0) {
    print '<div class="ouw_paging"><div class="ouw_paging_prev">&nbsp;';
    if($from>0) {
        $jump=$from-OUWIKI_PAGESIZE;
        if($jump<0) {
            $jump=0;
        }
        print link_arrow_left(get_string('previous','ouwiki'),
            'wikihistory.php?'.$tabparams. ($jump>0? '&amp;from='.$jump : ''));
    }
    print '</div><div class="ouw_paging_next">';
    if($count>OUWIKI_PAGESIZE) {
        $jump=$from+OUWIKI_PAGESIZE;
        print link_arrow_right(get_string('next','ouwiki'),
            'wikihistory.php?'.$tabparams. ($jump>0? '&amp;from='.$jump : ''));
    }
    print '&nbsp;</div></div>';
}

$a->atom=$atomurl;
$a->rss=$rssurl;
print '<p class="ouw_subscribe"><a href="'.$atomurl.'" title="'.get_string('feedalt','ouwiki').
    '"><img src="'.$CFG->pixpath.'/i/rss.gif" alt=""/></a> <span>'.
    get_string('feedsubscribe','ouwiki',$a).'</span></p>';

// Footer
ouwiki_print_footer($course,$cm,$subwiki);
?>
