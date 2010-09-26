<?php // $Id: format.php,v 1.2 2008/09/11 22:19:02 stronk7 Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2001-3001 Martin Dougiamas        http://dougiamas.com  //
//           (C) 2001-3001 Eloy Lafuente (stronk7) http://contiento.com  //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
// Collapse idea & code from:                                            //
//   http://www.howtocreate.co.uk/tutorials/jsexamples/listCollapseExample.html
//   http://odyniec.net/articles/turning-lists-into-trees/               //
///////////////////////////////////////////////////////////////////////////

// This course format is one "clone" of the standard "topics" format. Its
// main (unique) difference is that, following the indentation in activities performed
// in edit mode, it displays the course in a tree way.

    require_once($CFG->libdir.'/ajax/ajaxlib.php');

    echo '<script type="text/javascript" ';
    echo "src=\"{$CFG->wwwroot}/course/format/topicstree/list.js\"></script>\n";

    echo '<script type="text/javascript" ';
    echo "src=\"{$CFG->wwwroot}/course/format/topicstree/cookie.js\"></script>\n";

    echo '<script type="text/javascript" ';
    echo "src=\"{$CFG->wwwroot}/course/format/topicstree/loadunload.js\"></script>\n";

    // Define if we want tree in section 0
    $topicstree_tree_in_section0 = false;

    $topic = optional_param('topic', -1, PARAM_INT);

    // Bounds for block widths
    // more flexible for theme designers taken from theme config.php
    $lmin = (empty($THEME->block_l_min_width)) ? 100 : $THEME->block_l_min_width;
    $lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
    $rmin = (empty($THEME->block_r_min_width)) ? 100 : $THEME->block_r_min_width;
    $rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

    define('BLOCK_L_MIN_WIDTH', $lmin);
    define('BLOCK_L_MAX_WIDTH', $lmax);
    define('BLOCK_R_MIN_WIDTH', $rmin);
    define('BLOCK_R_MAX_WIDTH', $rmax);

    $preferred_width_left  = bounded_number(BLOCK_L_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]),  
                                            BLOCK_L_MAX_WIDTH);
    $preferred_width_right = bounded_number(BLOCK_R_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]), 
                                            BLOCK_R_MAX_WIDTH);

    if ($topic != -1) {
        $displaysection = course_set_display($course->id, $topic);
    } else {
        if (isset($USER->display[$course->id])) {       // for admins, mostly
            $displaysection = $USER->display[$course->id];
        } else {
            $displaysection = course_set_display($course->id, 0);
        }
    }

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
        $course->marker = $marker;
        if (! set_field("course", "marker", $marker, "id", $course->id)) {
            error("Could not mark that topic for this course");
        }
    }

    $streditsummary   = get_string('editsummary');
    $stradd           = get_string('add');
    $stractivities    = get_string('activities');
    $strshowalltopics = get_string('showalltopics');
    $strtopic         = get_string('topic');
    $strgroups        = get_string('groups');
    $strgroupmy       = get_string('groupmy');
    $editing          = $PAGE->user_is_editing();

    if ($editing) {
        $strstudents = moodle_strtolower($course->students);
        $strtopichide = get_string('topichide', '', $strstudents);
        $strtopicshow = get_string('topicshow', '', $strstudents);
        $strmarkthistopic = get_string('markthistopic');
        $strmarkedthistopic = get_string('markedthistopic');
        $strmoveup = get_string('moveup');
        $strmovedown = get_string('movedown');
    }


/// Layout the whole page as three big columns.
    echo '<table id="layout-table" cellspacing="0" summary="'.get_string('layouttable').'"><tr>';

/// The left column ...
    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':

    if (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
        echo '<td style="width:'.$preferred_width_left.'px" id="left-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print_container_end();
        echo '</td>';
    }

            break;
            case 'middle':
/// Start main column
    echo '<td id="middle-column">';
    print_container_start();
    echo skip_main_destination();

    print_heading_block(get_string('topicoutline'), 'outline');

    echo '<table class="topics" width="100%" summary="'.get_string('layouttable').'">';

/// If currently moving a file then show the current clipboard
    if (ismoving($course->id)) {
        $stractivityclipboard = strip_tags(get_string('activityclipboard', '', addslashes($USER->activitycopyname)));
        $strcancel= get_string('cancel');
        echo '<tr class="clipboard">';
        echo '<td colspan="3">';
        echo $stractivityclipboard.'&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey='.$USER->sesskey.'">'.$strcancel.'</a>)';
        echo '</td>';
        echo '</tr>';
    }

/// Print Section 0

    $section = 0;
    $thissection = $sections[$section];

    if ($thissection->summary or $thissection->sequence or isediting($course->id)) {
        echo '<tr id="section-0" class="section main">';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="content">';

        echo '<div class="summary">';
        $summaryformatoptions->noclean = true;
        echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);

        if (isediting($course->id) && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
            echo '<a title="'.$streditsummary.'" '.
                 ' href="editsection.php?id='.$thissection->id.'"><img src="'.$CFG->pixpath.'/t/edit.gif" '.
                 ' alt="'.$streditsummary.'" /></a><br /><br />';
        }
        echo '</div>';

        if (isediting($course->id) || !$topicstree_tree_in_section0) { /// Editing use the mainstream print_section
            print_section($course, $thissection, $mods, $modnamesused);
            if (isediting($course->id)) {
                print_section_add_menus($course, $section, $modnames);
            }
        } else { /// Non-editing use our own print_section
            print_topicstree_section($course, $thissection, $mods, $modnamesused);
        }

        echo '</td>';
        echo '<td class="right side">&nbsp;</td>';
        echo '</tr>';
        echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';
    }


/// Now all the normal modules by topic
/// Everything below uses "section" terminology - each "section" is a topic.

    $timenow = time();
    $section = 1;
    $sectionmenu = array();

    while ($section <= $course->numsections) {

        if (!empty($sections[$section])) {
            $thissection = $sections[$section];

        } else {
            unset($thissection);
            $thissection->course = $course->id;   // Create a new section structure
            $thissection->section = $section;
            $thissection->summary = '';
            $thissection->visible = 1;
            if (!$thissection->id = insert_record('course_sections', $thissection)) {
                notify('Error inserting new topic!');
            }
        }

        $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

        if (!empty($displaysection) and $displaysection != $section) {
            if ($showsection) {
                $strsummary = strip_tags(format_string($thissection->summary,true));
                if (strlen($strsummary) < 57) {
                    $strsummary = ' - '.$strsummary;
                } else {
                    $strsummary = ' - '.substr($strsummary, 0, 60).'...';
                }
                $sectionmenu['topic='.$section] = s($section.$strsummary);
            }
            $section++;
            continue;
        }

        if ($showsection) {

            $currenttopic = ($course->marker == $section);

            $currenttext = '';
            if (!$thissection->visible) {
                $sectionstyle = ' hidden';
            } else if ($currenttopic) {
                $sectionstyle = ' current';
                $currenttext = get_accesshide(get_string('currenttopic','access'));
            } else {
                $sectionstyle = '';
            }

            echo '<tr id="section-'.$section.'" class="section main'.$sectionstyle.'">';
            echo '<td class="left side">'.$currenttext.$section.'</td>';

            echo '<td class="content">';
            if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible) {   // Hidden for students
                echo get_string('notavailable');
            } else {
                echo '<div class="summary">';
                $summaryformatoptions->noclean = true;
                echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);

                if (isediting($course->id) && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    echo ' <a title="'.$streditsummary.'" href="editsection.php?id='.$thissection->id.'">'.
                         '<img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.$streditsummary.'" /></a><br /><br />';
                }
                echo '</div>';

                if (isediting($course->id)) { /// Editing use the mainstream print_section
                    print_section($course, $thissection, $mods, $modnamesused);
                    print_section_add_menus($course, $section, $modnames);
                } else { /// Non-editing use our own print_section
                    print_topicstree_section($course, $thissection, $mods, $modnamesused);
                }
            }
            echo '</td>';

            echo '<td class="right side">';
            if ($displaysection == $section) {      // Show the zoom boxes
                echo '<a href="view.php?id='.$course->id.'&amp;topic=0#section-'.$section.'" title="'.$strshowalltopics.'">'.
                     '<img src="'.$CFG->pixpath.'/i/all.gif" alt="'.$strshowalltopics.'" /></a><br />';
            } else {
                $strshowonlytopic = get_string('showonlytopic', '', $section);
                echo '<a href="view.php?id='.$course->id.'&amp;topic='.$section.'" title="'.$strshowonlytopic.'">'.
                     '<img src="'.$CFG->pixpath.'/i/one.gif" alt="'.$strshowonlytopic.'" /></a><br />';
            }

            if (isediting($course->id) && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
                if ($course->marker == $section) {  // Show the "light globe" on/off
                    echo '<a href="view.php?id='.$course->id.'&amp;marker=0&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strmarkedthistopic.'">'.
                         '<img src="'.$CFG->pixpath.'/i/marked.gif" alt="'.$strmarkedthistopic.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;marker='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strmarkthistopic.'">'.
                         '<img src="'.$CFG->pixpath.'/i/marker.gif" alt="'.$strmarkthistopic.'" /></a><br />';
                }

                if ($thissection->visible) {        // Show the hide/show eye
                    echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strtopichide.'">'.
                         '<img src="'.$CFG->pixpath.'/i/hide.gif" alt="'.$strtopichide.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strtopicshow.'">'.
                         '<img src="'.$CFG->pixpath.'/i/show.gif" alt="'.$strtopicshow.'" /></a><br />';
                }

                if ($section > 1) {                       // Add a arrow to move section up
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=-1&amp;sesskey='.$USER->sesskey.'#section-'.($section-1).'" title="'.$strmoveup.'">'.
                         '<img src="'.$CFG->pixpath.'/t/up.gif" alt="'.$strmoveup.'" /></a><br />';
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=1&amp;sesskey='.$USER->sesskey.'#section-'.($section+1).'" title="'.$strmovedown.'">'.
                         '<img src="'.$CFG->pixpath.'/t/down.gif" alt="'.$strmovedown.'" /></a><br />';
                }

            }

            echo '</td></tr>';
            echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';
        }

        $section++;
    }
    echo '</table>';

    if (!empty($sectionmenu)) {
        echo '<div align="center" class="jumpmenu">';
        echo popup_form($CFG->wwwroot.'/course/view.php?id='.$course->id.'&amp;', $sectionmenu,
                   'sectionmenu', '', get_string('jumpto'), '', '', true);
        echo '</div>';
    }

    print_container_end();
    echo '</td>';

            break;
            case 'right':
    // The right column
    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing) {
        echo '<td style="width:'.$preferred_width_right.'px" id="right-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print_container_end();
        echo '</td>';
    }

            break;
        }
    }
    echo '</tr></table>';

/**
 * Own function to print one section in a tree way
 */
function print_topicstree_section($course, $section, $mods, $modnamesused, $absolute=false, $width="100%") {
/// Prints a section full of activity modules
    global $CFG, $USER;

    static $initialised;

    static $groupbuttons;
    static $groupbuttonslink;
    static $strmovehere;
    static $strmovefull;
    static $strunreadpostsone;
    static $usetracking;
    static $groupings;

    $spacer = '&nbsp;&nbsp;&nbsp;';

    if (!isset($initialised)) {
        $groupbuttons     = ($course->groupmode or (!$course->groupmodeforce));
        $groupbuttonslink = (!$course->groupmodeforce);
        include_once($CFG->dirroot.'/mod/forum/lib.php');
        if ($usetracking = forum_tp_can_track_forums()) {
            $strunreadpostsone = get_string('unreadpostsone', 'forum');
        }
        $initialised = true;
    }

    $labelformatoptions = new object();
    $labelformatoptions->noclean = true;

/// Casting $course->modinfo to string prevents one notice when the field is null
    $modinfo = get_fast_modinfo($course);

    //Acccessibility: replace table with list <ul>, but don't output empty list.
    if (!empty($section->sequence)) {

        $collapsablename = "collapsable-{$course->id}-{$section->id}";
        echo '<script type="text/javascript">';
        echo "//<![CDATA[\n";
        /**
         * Can use both:
         * call a function: addLoadEvent(nameOfSomeFunctionToRunOnPageLoad);
         * execute code: addLoadEvent(function() {
         *               // more code to run on page load 
         *           });
         */
        echo "addLoadEvent(function() {";
        echo "    compactMenu('{$collapsablename}',false,'{$spacer}&plusmn; ');";
        echo "    stateToFromStr('{$collapsablename}', retrieveCookie('{$collapsablename}'));";
        echo '});';
        echo "addUnloadEvent(function() {";
        echo "    setCookie('{$collapsablename}',stateToFromStr('{$collapsablename}'));";
        echo '});';
        echo "//]]>\n";
        echo '</script>';

        // Fix bug #5027, don't want style=\"width:$width\".
        echo "<ul id=\"{$collapsablename}\" class=\"section img-text treesection\">\n";
        $sectionmods = explode(",", $section->sequence);


    /// Preprocess all the mods, adding the necessary stuff to be able to
    /// output nested lists later
        preprocessmods4topicstree($sectionmods, $mods, $modinfo);

        foreach ($sectionmods as $modnumber) {
            if (empty($mods[$modnumber])) {
                continue;
            }

            $mod = $mods[$modnumber];

            if (isset($modinfo->cms[$modnumber])) {
                if (!$modinfo->cms[$modnumber]->uservisible) {
                    // visibility shortcut
                    continue;
                }
            } else {
                if (!file_exists("$CFG->dirroot/mod/$mod->modname/lib.php")) {
                    // module not installed
                    continue;
                }
                if (!coursemodule_visible_for_user($mod)) {
                    // full visibility check
                    continue;
                }
            }

            $lastcss = '';
        /// Close levels if necessary
            if (!empty($mod->closelevel)) {
                for ($n = 0; $n < $mod->closelevel; $n++) {
                    echo '</ul></li>' . "\n";
                }
            }

            if (!empty($mod->islast)) {
                $lastcss = 'last ';
            }

            echo '<li class="activity treeactivity '.$lastcss.$mod->modname.'" id="module-'.$modnumber.'">';  // Unique ID

        /// Add spacer for activities without collapse/expand button
            if (empty($mod->openlevel)) {
                echo $spacer;
            }

            $extra = '';
            if (!empty($modinfo->cms[$modnumber]->extra)) {
                $extra = $modinfo->cms[$modnumber]->extra;
            }

            if ($mod->modname == "label") {
                if (!$mod->visible) {
                    echo "<span class=\"dimmed_text\">";
                }
                echo format_text($extra, FORMAT_HTML, $labelformatoptions);
                if (!$mod->visible) {
                    echo "</span>";
                }
                if (!empty($CFG->enablegroupings) && !empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    if (!isset($groupings)) {
                        $groupings = groups_get_all_groupings($course->id);
                    }
                    echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                }

            } else { // Normal activity
                $instancename = format_string($modinfo->cms[$modnumber]->name, true,  $course->id);

                if (!empty($modinfo->cms[$modnumber]->icon)) {
                    $icon = "$CFG->pixpath/".$modinfo->cms[$modnumber]->icon;
                } else {
                    $icon = "$CFG->modpixpath/$mod->modname/icon.gif";
                }

                //Accessibility: for files get description via icon.
                $altname = '';
                if ('resource'==$mod->modname) {
                    if (!empty($modinfo->cms[$modnumber]->icon)) {
                        $possaltname = $modinfo->cms[$modnumber]->icon;

                        $mimetype = mimeinfo_from_icon('type', $possaltname);
                        $altname = get_mimetype_description($mimetype);
                    } else {
                        $altname = $mod->modfullname;
                    }
                } else {
                    $altname = $mod->modfullname;
                }
                // Avoid unnecessary duplication.
                if (false!==stripos($instancename, $altname)) {
                    $altname = '';
                }
                // File type after name, for alphabetic lists (screen reader).
                if ($altname) {
                    $altname = get_accesshide(' '.$altname);
                }

                $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                echo '<a '.$linkcss.' '.$extra.        // Title unnecessary!
                     ' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.
                     '<img src="'.$icon.'" class="activityicon" alt="" /> <span>'.
                     $instancename.$altname.'</span></a>';

                if (!empty($CFG->enablegroupings) && !empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    if (!isset($groupings)) {
                        $groupings = groups_get_all_groupings($course->id);
                    }
                    echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                }
            }
            if ($usetracking && $mod->modname == 'forum') {
                if ($unread = forum_tp_count_forum_unread_posts($mod, $course)) {
                    echo '<span class="unread"> <a href="'.$CFG->wwwroot.'/mod/forum/view.php?id='.$mod->id.'">';
                    if ($unread == 1) {
                        echo $strunreadpostsone;
                    } else {
                        print_string('unreadpostsnumber', 'forum', $unread);
                    }
                    echo '</a></span>';
                }
            }

        /// Open new level if necessary, else print end of li
            if (isset($mod->openlevel)) {
                echo '<ul class="treelevel-' . ($mod->openlevel + 1) . '">' . "\n";
            } else {
                echo "</li>\n";
            }
        } /// End modules iterator

    /// Close remaining levels if necessary
        if (!empty($mod->indent)) {
            for ($n = 0; $n < $mod->indent; $n++) {
                echo '</ul></li>' . "\n";
            }
        }

    }


    if (!empty($section->sequence)) {
        echo "</ul><!--class='section'-->\n\n";
    }
}

/**
 * This function will preprocess all the mods in section, adding the required stuff to be able to
 * output them later in a nested lists behaviour
 */
function preprocessmods4topicstree($sectionmods, &$mods, &$modinfo) {

    global $CFG;

    $prev = null;
    $next = null;

    $tree    = array(); /// To create the tree structure while
    $parents = array(); /// iterating. Needed to detect last items

    array_push($parents, 0); /// Initial parent

    foreach ($sectionmods as $key => $modnumber) {

        $treeized = false; /// To know if we have inserted one module
                           /// in the $tree/$parents structures

        if (empty($mods[$modnumber])) {
            continue;
        }

    /// Calculate current
        $mod = $mods[$modnumber];

    /// Calculate next
        if (isset($sectionmods[$key+1])) {
            $next = $mods[$sectionmods[$key+1]];
        } else {
            $next = null;
        }

    /// Code goes here

    /// First item cannot have indent, reset it
        if (empty($prev) && ($mod->indent > 0)) {
            $mod->indent = 0;
        }

    /// Any difference > 1 isn't accepted, reduce it to 1
        if (!empty($next) && ($next->indent - $mod->indent > 1)) {
            $next->indent = $mod->indent + 1;
        }

    /// If prev is more indented than current, annotate it
        if (!empty($prev) && ($prev->indent > $mod->indent)) {
            $mod->closelevel = $prev->indent - $mod->indent;
        /// Delete from list of parent
            for ($n=0 ; $n < $mod->closelevel; $n++) {
                $last = array_pop($parents);
            }
        /// Assign current mod to current parent
            if (!$treeized) {
                $tree[end($parents)][$modnumber] = $modnumber;
                $treeized = true;
            }
        }

    /// If next is more indented than current, annotate it
        if (!empty($next) && ($next->indent > $mod->indent)) {
            $mod->openlevel = $next->indent;
        /// Assign current mod to current parent
            if (!$treeized) {
                $tree[end($parents)][$modnumber] = $modnumber;
                $treeized = true;
            }
        /// Now add mod to list of parents and start new list in tree for it
            array_push($parents, $modnumber);
            $tree[end($parents)] = array();
        }

    /// Assign current mod to current parent if not done before (in opening/closing)
        if (!$treeized) {
            $tree[end($parents)][$modnumber] = $modnumber;
            $treeized = true;
        }

    /// Just to be sure changes apply
        if ($mod) {
            $mods[$modnumber] = $mod;
        }
        if ($next) {
            $mods[$sectionmods[$key+1]] = $next;
        }

    /// Calculate new previous
        $prev = $mods[$modnumber];
    }

/// Now iterate over all the arrays in tree, getting last elements and
/// marking them as last in $mods
    foreach($tree as $list) {
        $mods[end($list)]->islast = true;
    }

/// Finally, iterate over all the arrays in tree, looking for hidden parents
/// and replacing that module by a custom label to respect the tree format
    foreach($tree as $key => $list) {
    /// Skip 0 (virtual) tree
        if ($key == 0) {
            continue;
        }
    /// Check user visibility, and replace module by hand-made label if it isn't visible
        if (!coursemodule_visible_for_user($mods[$key])) {
            $mods[$key]->modname = 'label';
            $mods[$key]->visible = 1;
            $modinfo->cms[$key]->uservisible = 1;
            $modinfo->cms[$key]->modname = 'label';
            $modinfo->cms[$key]->name = '';
            $modinfo->cms[$key]->extra = '<img src="' . $CFG->pixpath . '/f/folder.gif" alt="" />';
        }
    }
}
?>
