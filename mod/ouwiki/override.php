<?php
/**
 * Handles what happens when a user with appropriate permission attempts to 
 * override a wiki page editing lock.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require('basicpage.php');
if(!data_submitted()) {
    error("Only POST requests accepted");
}

if(!has_capability('mod/wiki:overridelock', $context)) {
    error("You do not have the capability to override editing locks");
}

$pageversion=ouwiki_get_current_page($subwiki,$pagename,OUWIKI_GETPAGE_ACCEPTNOVERSION);
ouwiki_override_lock($pageversion->pageid);

$redirpage = optional_param('redirpage','',PARAM_ALPHA);

if ($redirpage != '') {
    redirect($redirpage.'.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_URL),'',0);
} else {
    redirect('edit.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm,OUWIKI_PARAMS_URL),'',0);
}

?>