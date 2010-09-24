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
if(class_exists('ouflags')) {
    $DASHBOARD_COUNTER=DASHBOARD_WIKI_COMMENT;
}

if (!empty($_GET) || !confirm_sesskey()) {
    print_error('invalidrequest');
}

require('basicpage.php');
// Get the current page version
$pageversion=ouwiki_get_current_page($subwiki,$pagename);
if(!$pageversion) {
    error('Cannot add comment to nonexistent page');
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
    $sectiontitle=$knownsections[$section];
}

// Get other parameters
$fromphp=required_param('fromphp',PARAM_RAW);
$title=stripslashes(required_param('title',PARAM_RAW));
$xhtml=stripslashes(required_param('xhtml',PARAM_CLEAN));

// Work out redirect url
if($fromphp=='view') {
    $url='view.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_URL).
        '&showcomments'.($section ? '#ouw_s'.$section : '');
} else {
    $url='comments.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_URL).
        ($section ? '&section='.$section : '');
}

if(''==preg_replace('/(<.*?>)|(&nbsp;)|\s/','',$xhtml)) {
    error(get_string('commentblank','ouwiki'),$url);
}

// Check post permission
require_capability('mod/ouwiki:comment',$context);

// Add post
ouwiki_add_comment($pageversion->pageid,$section,$section?$sectiontitle:null,$title,$xhtml);

// Redirect
redirect($url);
?>

