<?php

/**
 * This file contains wiki_part_search functions.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: lib.php,v 1.1 2007/09/07 11:04:06 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

/**
 * load search results
 */
function wiki_part_search_load () {
    
    $dfsearch = optional_param ('dfsearch');

	$dfform = wiki_param ('dfform');
	
    if ($dfsearch) {
        $dfform['field'] = $dfsearch;
        wiki_param ('dfform',$dfform);
    }

    if (isset($dfform['field']) && trim($dfform['field']) != '') {
        $dfform['result'] = wiki_part_search_result ($dfform['field']);
        wiki_param ('dfform',$dfform);
        /*if (isset($dfform['main'])) {
            wiki_param ('dfcontent',10);
        }*/
    }
}

/**
 * this function prints the search result into work-space
 */
function wiki_part_search_print() {
    global $WS, $CFG;
    echo '<h1>' . get_string('searchresults') . '</h1>';
    print_box_start();
	
    // search results
    if (isset($WS->dfform['result'])) {
        echo '<h2>' . get_string('resultinpagename', 'wiki') . '</h2>';
        if (count($WS->dfform['result']['pagename']) != 0) {
            // there's results to print

            // this is the result matrix
            $rows = array();

            foreach ($WS->dfform['result']['pagename'] as $result){

                if ($dats = wiki_page_last_version($result)) {
                    // mount url
                    $aux = $WS->dfform['field'];
                    $url = '<script>var num=document.forms.length;'
                        . "document.write('<form name=\"formsearch' + num +"
                        . " '\" action=\"$CFG->wwwroot/mod/wiki/view.php?id="
                        . "{$WS->cm->id}&amp;page=$result&amp;"
                        . "gid={$WS->groupmember->groupid}&amp;"
                        . "uid={$WS->member->id}&amp;"
                        . "dfsearch=$aux\" method=\"post\">"
                        . "<a href=\"javascript:document.formsearch' + num +"
                        . " '.submit()\">$result</a>"
                        . "<input type=\"hidden\" name=\"dfsetup\" "
                        . "value=5 /></form>');</script>";

                    $created = strftime('%A, %d %B %Y %H:%M', $dats->created);
                    $modified = strftime('%A, %d %B %Y %H:%M', $dats->lastmodified);
                    $row = array ($url, $dats->version, $created, $modified);
                    $rows[] = $row;
                }
            }

            // print table
            $table->head = array(
                get_string('page'),
                get_string('version'),
                get_string('created','wiki'),
                get_string('lastmodified'),
            );
            $table->wrap = array('nowrap', '', '', '');
            $table->data = $rows;
            $table->width = '100%';
            print_table($table);

        }else{
            echo '<b>' . get_string('noresults') . '</b>';
        }

        echo '<hr /><h2>' . get_string('resultincontent', 'wiki') . '</h2>';

        if (count($WS->dfform['result']['content']) != 0) {
            //there's results to print

            //this is the result matrix
            $rows = array();

            foreach ($WS->dfform['result']['content'] as $result){
                if ($dats = wiki_page_last_version($result)) {
                    //mount url
                    $aux = $WS->dfform['field'];

                    $url = '<script>var num=document.forms.length;'
                        . "document.write('<form name=\"formsearch' + num +"
                        . " '\" action=\"$CFG->wwwroot/mod/wiki/view.php?id="
                        . "{$WS->cm->id}&amp;page=$result&amp;"
                        . "gid={$WS->groupmember->groupid}&amp;"
                        . "uid={$WS->member->id}&amp;"
                        . "dfsearch=$aux\" method=\"post\">"
                        . "<a href=\"javascript:document.formsearch' + num +"
                        . " '.submit()\">$result</a>"
                        . "<input type=\"hidden\" name=\"dfsetup\" "
                        . "value=5 /></form>');</script>";

                    $created = strftime('%A, %d %B %Y %H:%M', $dats->created);
                    $modified = strftime('%A, %d %B %Y %H:%M', $dats->lastmodified);
                    $row = array($url, $dats->version, $created, $modified);
                    $rows[] = $row;
                }
            }

            //print table
            $table->head = array(
                get_string('page'),
                get_string('version'),
                get_string('created', 'wiki'),
                get_string('lastmodified')
            );
            $table->wrap = array ('nowrap', '', '', '');
            $table->data = $rows;
            $table->width = '100%';
            print_table($table);

        }else{
            echo '<b>' . get_string('noresults') . '</b>';
        }

    } else {
        print_string ('noresults');
    }

    print_box_end();
}

/**
 * this function return an array with the pagenames of search results
 */
function wiki_part_search_result($text) {
    global $CFG,$WS;
    $res = array(
        'pagename' => array(),
        'content' => array(),
    );

    // mount search string
    $field = '%';
    $fields = explode(' ', $text);
    foreach ($fields as $f) {
        $field .= $f . '%';
    }

    $ead = wiki_manager_get_instance();
    $wiki = wiki_param ('dfwiki');
	$groupmember = wiki_param('groupmember');
    $pages = $ead->get_wiki_page_names_of_wiki($wiki, $groupmember->groupid);

    foreach ($pages as $page) {
        $pageinfo = wiki_page_last_version($page);
        $contentfound = count($fields) != 0;
        $namefound = count($fields) != 0;
        foreach ($fields as $f) {
            $f = stripslashes_safe($f);
            $namefound = $namefound
                && (stripos($pageinfo->pagename, $f) !== false);
            $contentfound = $contentfound
                && (strpos($pageinfo->content, $f) !== false);
        }
        if ($contentfound) {
            $res['content'][] = $pageinfo->pagename;
        }
        if ($namefound) {
            $res['pagename'][] = $pageinfo->pagename;
        }
    }

    // search in synonyms
    $wikimanager = wiki_manager_get_instance();
    $synonyms = $wikimanager->get_synonyms_by_wikiid($WS->dfwiki->id);
    foreach ($synonyms as $synonym) {
        $found = count($fields) != 0;
        foreach ($fields as $f) {
            $f = stripslashes_safe($f);
            $found = $found && (stripos($synonym->name, $f) !== false);
        }
        if ($found && !in_array($synonym->pageid->name, $res['pagename'])) {
            $res['pagename'][] = $synonym->pageid->name;
        }
    }
    return $res;
}

?>