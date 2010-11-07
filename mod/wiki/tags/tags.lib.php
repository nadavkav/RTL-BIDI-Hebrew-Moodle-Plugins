<?php

/**
 * Functions for tagging NWiki wiki pages.
 *
 * @author Gonzalo Serrano
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: tags.lib.php,v 1.5 2008/11/29 11:31:35 kenneth_riba Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot. '/tag/lib.php');

/**
 * Save the wikitags list 
 *
 * @param Object $WS WikiStorage
 * @param String $taglist Comma-separated tags
 */
function wiki_tags_save_tags($WS, $taglist)
{
    global $COURSE;

    add_to_log($COURSE->id, 'wiki', 'save tags',
               addslashes("view.php?id={$WS->cm->id}&amp;page=$WS->page"),
               $WS->dfwiki->id, $WS->cm->id);

    $page = wiki_page_last_version($WS->page, $WS);

    // delete the tags of the current wiki page version
    /*
     *$tagids = tag_get_tags_ids('wiki', $taglist);
     *foreach ($tagids as $tagid)
     *    tag_delete_instance('wiki', $page->created, $tagid);
     */

    // add the tags to the next wiki page
    tag_set('wiki', $page->created, explode(',', $taglist));
}

/**
 * Prints the input text for settings wikitags at wiki page edit page.
 *
 * @param Object $WS WikiStorage
 */
function wiki_tags_print_editbox($WS)
{
    global $CFG;

    if (empty($CFG->usetags)) return;
    if ($WS->pageaction != 'edit') return;

    $string  = get_string('tags','wiki').'&nbsp;'; // update translation string (nadavkav patch)
    $edsize  = $WS->editor_size->editorcols;
    $size    = $edsize - strlen($string) + strlen('&nbsp;');
    $taglist = wiki_tags_get_tag_names($WS);

    echo('<div class="felement ftext">'.$string.
         '<input id="id_wikitags" type="text" name="wikitags" size="'.
         $size.'" '.'value="'.$taglist.'"/>'.
         '</div>');

    //wiki_form_end();
}

/**
 * Prints the wiki tags at view wiki page.
 *
 * @param Object $WS WikiStorage
 */
function wiki_tags_print_viewbox($WS)
{
    global $CFG;

    if (empty($CFG->usetags)) return;
    if ($WS->pageaction != 'view') return;

    $tags = optional_param('wikitags', '', PARAM_TEXT);

    $string  = '<b>'.get_string('tags','wiki').'</b>:&nbsp;';// update translation string (nadavkav patch)
    $taglist = wiki_tags_get_tag_links($WS);

    if (!$taglist) return;

    print_box_start();
    echo('<div class="felement ftext">'.$string.$taglist.'</div>');
    print_box_end();
}

/**
 * Returns an array with all the wikitags.
 *
 * @param Object $WS WikiStorage
 */
function wiki_tags_get_all_wikitags()
{
    global $CFG;

    $query = "SELECT tag.id, tag.name, tag.rawname, count(*) AS count, tag.flag ".
        "FROM {$CFG->prefix}tag tag LEFT OUTER JOIN {$CFG->prefix}tag_instance ti ".
        "ON tag.id = ti.tagid ".
        "WHERE ti.itemtype = 'wiki' GROUP BY tag.id ORDER BY tag.name";
        //"WHERE ti.itemtype = 'wiki' GROUP BY ti.tagid";

    $tags = get_records_sql($query); //, $limitfrom, $limitnum);
    return $tags;
}

/**
 * Returns a string with comma-separated wikitags names
 *
 * @param Object $WS WikiStorage
 */
function wiki_tags_get_tag_names($WS)
{
    $page = wiki_page_last_version($WS->page, $WS);
    if (!$page) return null;

    $tags = tag_get_tags('wiki', $page->created);

    $tag_names = '';
    if (!empty($tags)){
        $tags = array_values($tags);
        $size = count($tags);
        for ($i = 0; $i < $size; $i++) {
            $tag = $tags[$i];
            $tag_names .= tag_display_name($tag);
            if ($i != $size - 1) $tag_names .= ', ';
        }
    }

    return $tag_names;
}

/**
 * Returns a XHTML string with comma-separated wikitags links
 *
 * @param Object $WS WikiStorage
 */
function wiki_tags_get_tag_links($WS)
{
    global $CFG, $COURSE;

    $page = wiki_page_last_version($WS->page, $WS);
    if (!$page) return null;

    $tags = tag_get_tags('wiki', $page->created);
    if (!empty($tags))
    {
        $tags = array_values($tags);

        $links = '';
        $size = count($tags);
        for ($i = 0; $i < $size; $i++) {
            $tag = $tags[$i];
            $links .= '<a href="'.$CFG->wwwroot.'/mod/wiki/tags/view.php?cid='.
                       $COURSE->id.'&amp;cmid='.$WS->cm->id.
                      '&amp;tagid='.$tag->id.'">'.tag_display_name($tag).'</a>';
            /*
             * // when moodle-core (2.0?) allow to add custom items to
             * // taglist page we can enable this.
             *$links .= '<a href="'.$CFG->wwwroot.'/tag/index.php?'.
             *          '&amp;id='.$tag->id.'">'.$tag->name.'</a>';
             */
            if ($i != $size - 1) $links .= ', ';
        }
        return $links;
    }

    return '';
}

/**
 * Returns an array with all the wiki pages that cointain a wikitag.
 *
 * @param Object $tag
 * @param Int    $cid Course id
 */
function wiki_tags_get_wikipages($tag, $cid=0)
{
    global $CFG;

    $query = "SELECT wp.* ".
        "FROM {$CFG->prefix}wiki_pages wp INNER JOIN {$CFG->prefix}tag_instance t ON wp.created = t.itemid ".
        "WHERE t.itemtype = 'wiki' AND t.tagid = '{$tag->id}' ".
        "GROUP BY wp.created ORDER BY wp.dfwiki, wp.pagename";

    $pages = get_records_sql($query); //, $limitfrom, $limitnum);
    if (!$pages) return null;

    $available_pages = array();
    foreach ($pages as $page)
    {
        $wikicm = get_coursemodule_from_instance('wiki', $page->dfwiki, $cid);
        if ($wikicm)
            $available_pages[] = $page;
        /* else: 
         *  - or the wiki has been deleted so we don't count those tags
         *  - or the wiki is from another course so we don't need those tags
         */
    }

    return $available_pages;
}

/**
 * Returns a XHTML string composed of an unordered list with wikipages 
 * (page name link, wiki name link, course name link) related to a wikitag.
 * 
 * @param Array $wikipages
 */
function wiki_tags_get_wikipages_list($wikipages)
{
    global $CFG;

    $output      = '';
    $lastid      = -1;
    $wikiname    = '';
    foreach ($wikipages as $page) {
        $currentwiki = $page->dfwiki;
        if ($lastid != $page->dfwiki) {
            $lastid = $page->dfwiki;
            $queryname = "SELECT wiki.name FROM {$CFG->prefix}wiki wiki ".
                         "WHERE wiki.id = '{$page->dfwiki}'";
            $wikiname  =  get_field_sql($queryname);
            $wikicm    =  get_coursemodule_from_instance('wiki', $page->dfwiki);

            $wikilink  = '<a href="'.$CFG->wwwroot.'/mod/wiki/view.php?id='.$wikicm->id.'">'.
                          $wikiname.'</a>';
            $output .= "</ul>\n";
            $output .= '<h3>'.$wikilink.'</h3>';
            $output .= "\n\t".'<ul style="padding-left: 2.5em">'."\n";
            $add = '';
        }

        $pagelink  = '<a href="'.$CFG->wwwroot.'/mod/wiki/view.php?id='.$wikicm->id.
                     '&amp;page='.urlencode($page->pagename).
                     '">'.$page->pagename.'</a>';

        $output .= "\t".'    <li>'.$pagelink.'</li>'.$add."\n";
    }
    $output .= '</ul>';

    return $output;
}

/**
 * Returns a XHTML string composed of a moodle-table filled with wikipages 
 * (page name link, wiki name link, course name link) related to a wikitag.
 * 
 * @param Array $wikipages
 */
function wiki_tags_get_wikipages_table($wikipages)
{
    global $CFG;
    $output = '<p>'.
            '<table border="1" cellspacing="1" cellpadding="5" width="100%" class="generaltable boxalignleft">'."\n".
            '<tr>'."\n".
            '   <th valign="top" class="nwikileftnow header c0">'.get_string('course').'</th>'."\n".
            '   <th valign="top" class="nwikileftnow header c1">'.get_string('modulename', 'wiki').'</th>'."\n".
            '   <th valign="top" class="nwikileftnow header c2">'.get_string('page').'</th>'."\n".
            '</tr>'."\n";

    $lastid = -1;
    $wikiname = '';
    foreach ($wikipages as $page) {
        if ($lastid != $page->dfwiki) {
            $lastid = $page->dfwiki;
            $queryname = "SELECT wiki.name FROM {$CFG->prefix}wiki wiki ".
                         "WHERE wiki.id = '{$page->dfwiki}'";
            $wikiname  =  get_field_sql($queryname);
            $wikicm    =  get_coursemodule_from_instance('wiki', $page->dfwiki);

            $wikilink  = '<a href="'.$CFG->wwwroot.'/mod/wiki/view.php?id='.$wikicm->id.'">'.
                          $wikiname.'</a>';

            $querycourse = "SELECT course.id, course.shortname FROM ".
                           "{$CFG->prefix}course course INNER JOIN {$CFG->prefix}wiki wiki ".
                           "WHERE wiki.id = '{$page->dfwiki}' AND course.id = wiki.course";
            $course      =  get_record_sql($querycourse);

            $courselink  = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.
                            $course->shortname.'</a>';
        }
        $pagelink  = '<a href="'.$CFG->wwwroot.'/mod/wiki/view.php?id='.$wikicm->id.
                     '&amp;page='.urlencode($page->pagename).
                     //'&amp;gid='.$page->groupid.'&amp;uid='.$page->userid.
                     '">'.$page->pagename.'</a>';

        $output .= "\t".'    <tr><td class="textcenter nwikibargroundblanco">'.$courselink.'</td><td class="textcenter nwikibargroundblanco">'.$wikilink.'</td"><td class="textcenter nwikibargroundblanco">'.$pagelink."</td>\n";
    }
    $output .= "\t</table>\n";

    return $output;
}

/**
 * Prints a header with the number of wikipages that have a certain wikitag
 * plus a list/table of the page names, wikis and courses of them.
 *
 * @param String $tag
 */
function wiki_tags_print_wikipages($tag)
{
    global $CFG, $COURSE;

    $wikipages = wiki_tags_get_wikipages($tag, $COURSE->id);
    if ($wikipages) 
    {
        $wikipages_list = wiki_tags_get_wikipages_list($wikipages);
        //$wikipages_list = wiki_tags_get_wikipages_table($wikipages);

        print_box_start('generalbox', 'tag-blogs');
        $heading = get_string('wikitagpages', 'wiki', tag_display_name($tag)).': '.count($wikipages);
        print_heading($heading, '', 3);

        $baseurl = $CFG->wwwroot.'/tag/index.php?id='.$tag->id;

        //print_paging_bar($usercount, $userpage, $perpage, $baseurl.'&amp;', 'userpage');
        echo($wikipages_list);
        print_box_end();
    }
}

/**
 * Creates a tag cloud formed of wikitags. 
 * 
 */
function wiki_tags_print_tag_cloud($tagcloud='', $cmid='', $options='view', 
                                   $shuffle=true, $max_size=180, $min_size=80, 
                                   $return=false)
{
    global $CFG, $COURSE, $WS;

    $tagcloud = wiki_tags_get_all_wikitags();

    if (empty($tagcloud)) return;

    if ($shuffle) {
        shuffle($tagcloud);
    } else {
        ksort($tagcloud);
    }

    $count = array();
    foreach ($tagcloud as $tag) {
        if (!empty($value->count))
            $count[] = log10($tag->count);
        else
            $count[] = 0;
    }

    $max = max($count);
    $min = min($count);

    $spread = $max - $min;
    if (0 == $spread) { // we don't want to divide by zero
        $spread = 1;
    }

    $step = ($max_size - $min_size)/($spread);

    $systemcontext   = get_context_instance(CONTEXT_SYSTEM);
    $can_manage_tags = has_capability('moodle/tag:manage', $systemcontext);

    //prints the tag cloud
    $output = '<ul id="tag-cloud-list">';
    $i = 1;
    foreach ($tagcloud as $tag) 
    {
        $tagname = tag_display_name($tag);

        $size = $min_size + ((log10($tag->count) - $min) * $step);
        $size = ceil($size);

        $style = 'style="font-size:'.$size.'%"';
        //$title = 'title="'.s(get_string('thingstaggedwith','tag', $tagname)).'"';
        $title = 'title="'.$tagname.'"';

        //highlight tags that have been flagged as inappropriate for those who can manage them
        //$href = 'href="'.$CFG->wwwroot.'/mod/wiki/tags/view.php?id='.$cmid.'&action=search&query=tag'.urlencode(':'.$tagname).'"';
        $href = 'href="'.$CFG->wwwroot.'/mod/wiki/tags/view.php?cid='.
                   $COURSE->id.'&amp;cmid='.$WS->cm->id.
                  '&amp;tagid='.$tag->id.'"';
        $onclick = '';
        if($options == 'edit'){
            $onclick = 'class="clickable-label" onclick="selectTag(this); return false;"';
            $href = 'href="#"';
        }
        if ($tag->flag > 0 && $can_manage_tags) {
            $tagname =  '<span class="flagged-tag">' . tag_display_name($tag) . '</span>';
        }

        $tag_link = '<li><a '.$onclick.' '.$href.' '.$title.' '. $style .'>'.$tagname.'</a></li> ';
        $output .= $tag_link;

        // FIXME: it seems that the block width ain't set so lets say that
        // we do a new line every 4 tags for now.
        if ($i % 4 == 0)
            $output .= '<br/>';

        $i++;
    }
    $output .= '</ul>';
    return $output;

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

?>
