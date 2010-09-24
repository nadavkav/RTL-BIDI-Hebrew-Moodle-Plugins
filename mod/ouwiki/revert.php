<?php
/**
 * Confirms reverting to previous version
 * when confirmed, reverts to previous version then redirects back to that page.
 * @copyright &copy; 2008 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require('basicpage.php');

$versionid = required_param('version');

// Get the page version to be reverted back to (must not be deleted page version)
$pageversion = ouwiki_get_page_version($subwiki, $pagename, $versionid);
if (!$pageversion || !empty($pageversion->deletedat)) {
    print_error('reverterrorversion', 'ouwiki');
}

// Check for cancel
$cancelled = optional_param('cancel', null, PARAM_ALPHA);
if (isset($cancelled)) {
    redirect('history.php?'.ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL));
    exit;
}

// Check permission - Allow anyone with edit capability to revert to a previous version
$canrevert = has_capability('mod/ouwiki:edit', $context);
if(!$canrevert) {
    print_error('reverterrorcapability', 'ouwiki');
}

// Check if reverting to previous version has been confirmed
$confirmed = optional_param('confirm', null, PARAM_ALPHA);
if($confirmed) {

    // Lock something - but maybe this should be the current version
    list($lockok, $lock) = ouwiki_obtain_lock($ouwiki, $pageversion->pageid);

    // Revert to previous version
    ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pagename, $pageversion->xhtml);

    // Unlock whatever we locked
    ouwiki_release_lock($pageversion->pageid);

    // Redirect to view what is now the current version
    redirect('view.php?'.ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL));
    exit;

} else {

    // Display confirm form
    $nav = get_string('revertversion', 'ouwiki');
    ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context, array(array('name'=>$nav,'type'=>'ouwiki')), true, true);
    
    print_box_start();

    $a = ouwiki_nice_date($pageversion->timecreated);
    print get_string('revertversionconfirm', 'ouwiki', $a);
    print '<form action="revert.php" method="post">';
    print ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_FORM);
    print 
        '<input type="hidden" name="version" value="'.$versionid.'" />'.
        '<input type="submit" name="confirm" value="'.get_string('revertversion','ouwiki').'"/> '.
        '<input type="submit" name="cancel" value="'.get_string('cancel').'"/>';
    print '</form>';
    
    print_box_end();
    
    ouwiki_print_footer($course, $cm, $subwiki, $pagename);
}
?>
