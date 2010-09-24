<?php
/**
 * [Un]Deletes a version of a page then redirects back to the history page
 * @copyright &copy; 2008 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require('basicpage.php');

$versionid = required_param('version');

// Get the page version to be [un]deleted
$pageversion = ouwiki_get_page_version($subwiki, $pagename, $versionid);
if (!$pageversion) {
    print_error('deleteversionerrorversion', 'ouwiki');
}

// Check permission - Allow anyone with delete page capability to delete a page version
$candelete = has_capability('mod/ouwiki:deletepage', $context);
if(!$candelete) {
    print_error('deleteversionerrorversion', 'ouwiki');
}

// Note: No need to confirm deleting/undeleting page version
// Lock page
list($lockok, $lock) = ouwiki_obtain_lock($ouwiki, $pageversion->pageid);

// Set default action
$action = 'delete';

try {
    // [Un]Delete page version
    if (empty($pageversion->deletedat)) {

        // Flag page version as deleted
        if (!set_field('ouwiki_versions', 'deletedat', time(), 'id', $versionid)) {
            throw new Exception('Error deleting/undeleting ouwiki page version');
        }

        // Check if current version has been deleted
        if ($pageversion->versionid == $pageversion->currentversionid) {

            // Current version deleted
            // Update current version to first undeleted version (or null)
            $pageversions = ouwiki_get_page_history($pageversion->pageid, false, 0, 1);
            if (($currentpageversion = reset($pageversions))) {

                // Page version found, update page current version id
                if (!set_field('ouwiki_pages', 'currentversionid', $currentpageversion->versionid, 'id', $pageversion->pageid)) {
                    throw new Exception('Error deleting/undeleting ouwiki page version');
                }

            } else {

                // No page version found, reset page current version id
                if (!set_field('ouwiki_pages', 'currentversionid', null, 'id', $pageversion->pageid)) {
                    throw new Exception('Error deleting/undeleting ouwiki page version');
                }
            }
        }
        
        // Update completion status for user
        if(class_exists('ouflags')) {
            if(completion_is_enabled($course,$cm) && ($ouwiki->completionedits || $ouwiki->completionpages)) {
                completion_update_state($course, $cm, COMPLETION_INCOMPLETE, $pageversion->userid);
            }
        }        
    } else {

        // Flag page version as no longer deleted
        $action = 'undelete';
        if (!set_field('ouwiki_versions', 'deletedat', null, 'id', $versionid)) {
            throw new Exception('Error deleting/undeleting ouwiki page version');
        }

        // Get first undeleted (current) page version (there must be one)
        $pageversions = ouwiki_get_page_history($pageversion->pageid, false, 0, 1);
        $currentpageversion = reset($pageversions);
        if (!$currentpageversion) {
            throw new Exception('Error deleting/undeleting ouwiki page version');
        }

        // Check if version that has been undeleted should be the new current version
        if ($pageversion->currentversionid != $currentpageversion->versionid) {

            // Set new current version id
            if (!set_field('ouwiki_pages', 'currentversionid', $currentpageversion->versionid, 'id', $pageversion->pageid)) {
                throw new Exception('Error deleting/undeleting ouwiki page version');
            }
        }

        // Update completion status for user
        if(class_exists('ouflags')) {
            if(completion_is_enabled($course,$cm) && ($ouwiki->completionedits || $ouwiki->completionpages)) {
                completion_update_state($course, $cm, COMPLETION_COMPLETE, $pageversion->userid);
            }
        }        
    }

} catch(Exception $e) {

    // Unlock page
    ouwiki_release_lock($pageversion->pageid);

    print_error('deleteversionerror', 'ouwiki');
}

// Unlock page
ouwiki_release_lock($pageversion->pageid);

// Log delete or undelete action
$ouwikiparamsurl = ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL);
add_to_log($course->id, 'ouwiki', 'version'.$action, 'delete.php?'.$ouwikiparamsurl.'&amp;version='.$versionid, '', $cm->id);

// Redirect to view what is now the current version
redirect('history.php?'.$ouwikiparamsurl);
exit;
?>
