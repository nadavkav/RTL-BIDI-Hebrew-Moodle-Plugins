<?php

require_once($CFG->dirroot."/mod/wiki/locallib.php");

/**
 * Returns an array with the URLs of a page (plus optinal section if it's a
 * a partial editing) to view.

 * @param  String $pagename
 * @param  String $anchor Section name
 * @param  String $anchortype (0, 1, 2)<br>
 *                   0: no anchor<br>
 *                   1: [[page#section]]<br>
 *                   2: [[page##section]]
 * @return Array  URLs Contains a list (if there are more than one section
 *                     with the same name in the same page) of URLs that
 *                     match the page and section
 */
function wiki_view_page_url($pagename, $anchor='', $anchortype=0, $wikibook='') {
    global $CFG, $COURSE, $WS;

	if ($wikibook) {
		$wikibook = '&amp;wikibook='.urlencode($wikibook);
	}

    // support page synonyms
    $pagename = wiki_get_real_pagename($pagename);
    $urls = array();

    $page = wiki_page_last_version($pagename);
    if ($page) 
		$sectionnums = wiki_get_section_positions($page->content, $anchor);

    if ($page && ($anchortype > 0) && (count($sectionnums) > 0)) {
        if ($anchortype == 1) {        // [[page#section]]
            foreach ($sectionnums as $sectionnum) {
                $newurl = 'view.php?id=$id&amp;page=view/'.urlencode($pagename).'&amp;gid=$gid&amp;uid=$uid'.$wikibook.'#'.$sectionnum;
                $newurl = wiki_format_url($newurl);
                $urls[] = $newurl;
            }
        } else if ($anchortype == 2) { // [[page##section]]
            foreach ($sectionnums as $sectionnum) {
                $newurl = 'view.php?id=$id&amp;page=view/'.urlencode($pagename).'&amp;gid=$gid&amp;uid=$uid'.$wikibook.'&amp;section='.urlencode($anchor).'&amp;sectionnum='.$sectionnum;
                $newurl = wiki_format_url($newurl);
                $urls[] = $newurl;
            }
        }
    } else {                       // no anchor
        $newurl = 'view.php?id=$id&amp;page='.urlencode($pagename).'&amp;gid=$gid&amp;uid=$uid'.$wikibook;
        $newurl = wiki_format_url($newurl);
        $urls[] = $newurl;
    }

    return $urls;
}

?>
