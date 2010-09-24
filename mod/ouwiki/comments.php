<?php
/**
 * Comments page. Shows full comments for a page section and allows the user
 * to post a new comment.
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

$countasview=true;
require('basicpage.php');
// Get the current page version
$pageversion=ouwiki_get_current_page($subwiki,$pagename);
if(!$pageversion) {
    error('Cannot view comments when page does not exist');
}

// Need list of known sections on current version
$knownsections=ouwiki_find_sections($pageversion->xhtml);

// Get section, make sure the name is valid
$section=optional_param('section','',PARAM_RAW);
if(!preg_match('/^[0-9]+_[0-9]+$/',$section)) {
    $section=null;
}
if($section) {
    if(!array_key_exists($section,$knownsections)) {
        error("Unknown section $section"); 
    }
}

// Check permissions. (Note that you can always delete your
// own posts, $candelete is for other people's.)
$canpost=has_capability('mod/ouwiki:comment',$context); 
$candelete=has_capability('mod/ouwiki:deletecomments',$context);

// Get list of posts
$comments=ouwiki_get_all_comments(
    $pageversion->pageid,$section,$candelete,$knownsections);
    
// We need the section title if it's not the main page
if($section) {
    $nav=get_string('commentsonsection','ouwiki',$knownsections[$section]);
} else {
    $nav=get_string('commentsonpage','ouwiki');
}



$title = get_string('commenton','ouwiki');
$wikiname=format_string(htmlspecialchars($ouwiki->name));
$name = '';

if(!$section) {
	if($pagename) {
    	$name = $pagename; 
	} else {		
    	$name=get_string('startpage','ouwiki');   	
	}
}else {
	$sectiontitle=$knownsections[$section];
	$name = htmlspecialchars($sectiontitle);
}
   	
$title = $wikiname.' - '.$title.' '.$name;

// OK, finally ready to print header 
ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context,array(array('name'=>$nav,'type'=>'ouwiki')),true,true, '', $title);

// Print message about deleted things being invisible to students so admins
// don't get confused
if($candelete) {
    $found=false;
    foreach($comments as $comment) {
        if($comment->deleted) {
            $found=true;
            break;
        }
    }
    if($found) {
        print '<p class="ouw_deletedcommentinfo">'.get_string('commentdeletedinfo','ouwiki').'</p>';
    }    
}

print '<div class="ouwiki_allcomments">';

// OK, display all comments
print ouwiki_display_comments($comments,$section,$pagename,$subwiki,$cm,true,$candelete);

// And 'add comment' form
if($canpost) {
    print '<h2>'.get_string('commentpostheader','ouwiki').'</h2>';
    print '<a id="post"></a>';
    print ouwiki_display_comment_form('comments',$section,$section?$knownsections[$section]:null,$pagename,$subwiki,$cm);
}
print '</div>';

// Link to return to page
global $CFG,$THEME;
print '<div class="ouw_returnlink">'.
  // TODO Replace theme stuff with nick's function
  '<span class="sep">'.$THEME->larrow.'</span> '.
  '<a href="view.php?'.ouwiki_display_wiki_parameters($pageversion->title,$subwiki,$cm).'">'.
  get_string('returntopage','ouwiki').'</a></div>';

// Footer
ouwiki_print_footer($course,$cm,$subwiki,$pagename);
?>

