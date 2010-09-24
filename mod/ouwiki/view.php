<?php
/**
 * View page. Displays wiki pages.
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
    if (ou_get_is_mobile()){
        ou_mobile_configure_theme();
    }
}

ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context);

global $CFG;

//cheeck consistency in setting Sub-wikis and group mode;
$urllink = $CFG->wwwroot . '/course/view.php?id=' . $cm->course;
if (($cm->groupmode == 0) && isset($subwiki->groupid)) {
    error("Sub-wikis is set to 'One wiki per group'. 
        Please change Group mode to 'Separate groups' or 'Visible groups'.", $urllink);
}
if (($cm->groupmode > 0) && !isset($subwiki->groupid)) {
    error("Sub-wikis is NOT set to 'One wiki per group'. 
        Please change Group mode to 'No groups'.", $urllink);
}

if(ajaxenabled() || class_exists('ouflags')) {
    // YUI and basic script
    require_js(array('yui_yahoo', 'yui_event', 'yui_connection', 'yui_dom', 'yui_animation'));

    // Print javascript
    print '<script type="text/javascript" src="ouwiki.js"></script><script type="text/javascript">
    strCloseComments="'.addslashes_js(get_string('closecomments','ouwiki')).'";
    strCloseCommentForm="'.addslashes_js(get_string('closecommentform','ouwiki')).'";
    </script>';
}

// Get the current page version
$pageversion=ouwiki_get_current_page($subwiki,$pagename);
$locked = ($pageversion)? $pageversion->locked:false;
ouwiki_print_tabs('view',$pagename,$subwiki,$cm,$context,$pageversion?true:false,$locked);

if(($pagename==='' || $pagename===null) && strlen(preg_replace('/\s|<br\s*\/?>|<p>|<\/p>/','',$ouwiki->summary))>0) {
    print '<div class="ouw_summary">'.format_text($ouwiki->summary).'</div>';
}

if($pageversion) {
    // Print warning if page is large (more than 100KB)
    if (strlen($pageversion->xhtml) > 100 * 1024) {
        print '<div class="ouwiki-sizewarning"><img src="' . $CFG->modpixpath .
                '/ouwiki/warning.png" alt="" />' . get_string('sizewarning', 'ouwiki') .
                '</div>';
    }
    // Print page content
    $data = ouwiki_display_page($subwiki,$cm,$pageversion,true,'view');
    print($data[0]);
    if ($pageversion->locked != '1') {
        print ouwiki_display_create_page_form($subwiki,$cm,$pageversion);
    }
    if (has_capability('mod/ouwiki:lock',$context)) {
        print ouwiki_display_lock_page_form($pageversion,$id);
    }
} else {
    // Page does not exist
    print '<p>'.get_string($pagename ? 'pagedoesnotexist' : 'startpagedoesnotexist','ouwiki').'</p>';
    if($subwiki->canedit) {
        print '<p>'.get_string('wouldyouliketocreate','ouwiki').'</p>';
        print "<form method='get' action='edit.php'>";
        print ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_FORM);
        print "<input type='submit' value='".get_string('createpage','ouwiki')."' /></form>";  
    }
}

if($timelocked=ouwiki_timelocked($subwiki,$ouwiki,$context)) {
    print '<div class="ouw_timelocked">'.$timelocked.'</div>';
}

// Show dashboard feature if enabled, on start page only
if (class_exists('ouflags') && ($pagename==='' || $pagename===null)) {
    require_once($CFG->dirroot . '/local/externaldashboard/external_dashboard.php');
    external_dashboard::print_favourites_button($cm);
}

// Footer
if(class_exists('ouflags')) {
    completion_set_module_viewed($course,$cm);    
}
ouwiki_print_footer($course,$cm,$subwiki,$pagename);

?>

