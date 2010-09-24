<?php
/**
 * Adds a comment then redirects back to source page.
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

// Validate request
require_once(dirname(__FILE__).'/../../config.php');
if (!empty($_GET) || !confirm_sesskey()) {
    print_error('invalidrequest');
}

require('basicpage.php');

// Get the current page version
$pageversion=ouwiki_get_current_page($subwiki,$pagename);
if(!$pageversion) {
    error('Cannot delete comment from nonexistent page');
}

// Get section and other parameters
$section=required_param('section',PARAM_RAW);
if(!preg_match('/^[0-9]+_[0-9]+$/',$section)) {
    $section=null;
}
$commentid=required_param('comment',PARAM_INT);
$delete=required_param('delete',PARAM_INT);

// Check for cancel
$url='comments.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_URL).
        ($section ? '&section='.$section : '');
if(array_key_exists('cancel',$_POST)) {
    redirect($url);
    exit;
}


// Check permission
$comment=get_record('ouwiki_comments','id',$commentid);
$candelete=has_capability('mod/ouwiki:deletecomments',$context);
global $USER;
$owncomment=$comment->userid==$USER->id;
if(!$owncomment && !$candelete) {
    error('You do not have permission to delete this comment');
}

$confirmed=optional_param('confirm',0,PARAM_INT);
        

// Admin users don't have to confirm delete since they can undelete
if($candelete || $confirmed) {
    // Delete comment
    ouwiki_delete_comment($pageversion->pageid,$commentid,$delete);
    
    // Redirect
    redirect($url);
} else {
    // Display confirm form
    $nav=get_string('commentdelete','ouwiki');
    ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context,array(array('name'=>$nav,'type'=>'ouwiki')),true,true);
    
    print_box_start();
    print '<p>'.get_string('commentdeleteconfirm','ouwiki').'</p>';
    print '<form action="deletecomment.php" method="post">';
    print ouwiki_display_wiki_parameters($pageversion->title,$subwiki,$cm,OUWIKI_PARAMS_FORM);
    print 
        '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'. 
        '<input type="hidden" name="comment" value="'.$commentid.'" />'. 
        '<input type="hidden" name="section" value="'.$section.'" />'. 
        '<input type="hidden" name="delete" value="'.$delete.'" />'.
        '<input type="hidden" name="confirm" value="1" />'.
        '<input type="submit" name="action" value="'.get_string('commentdelete','ouwiki').'"/> '.
        '<input type="submit" name="cancel" value="'.get_string('cancel').'"/>';
    print '</form>';
    
    print_box_end();
    
    ouwiki_print_footer($course,$cm,$subwiki,$pagename);
}
?>

