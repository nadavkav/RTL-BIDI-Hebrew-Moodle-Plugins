<?php // $Id: format.php,v 1.83.2.2 2007/11/23 16:41:19 skodak Exp $
      // Display the whole course as "topics" made of of modules
      // In fact, this is very similar to the "weeks" format, in that
      // each "topic" is actually a week.  The main difference is that
      // the dates aren't printed - it's just an aesthetic thing for
      // courses that aren't so rigidly defined by time.
      // Included from "view.php"


    require_once($CFG->libdir.'/ajax/ajaxlib.php');

    $topic = optional_param('topic', 1, PARAM_INT); // Always start from Page 1 (Section 1) if nothing is set
    $page = optional_param('page', 1, PARAM_INT);
    $chapter = optional_param('chapter', 1, PARAM_INT);

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


    echo "<style>";
    // Hide breafcram natigation
    echo ".navbar .breadcrumb {display: none;}";
    // Hide side bars of topics/sections
    echo "#course-view .weekscss .section, #course-view .section td.side {display: none;}";
    echo "</style>";
//echo "topic=".$topic;
    // Get last Topic from cookies
    if ($topic < 0 or $topic == null) $topic = $_COOKIE['ebooktopic'];
    if (empty($topic)) $topic = 1;
//echo "topic=".$topic;
    // Add a new ebook entry if on new section
    if (!get_record('course_format_ebook','section',$topic,'courseid',$course->id)){
        $sql = "SELECT DISTINCT courseid,chapter,page,section FROM ".$CFG->prefix."course_format_ebook WHERE courseid = $course->id ORDER BY section DESC";
        $lastebookpage = get_record_sql($sql);
        $lastebookpage->courseid = $course->id;
        $lastebookpage->section = $topic;
        $lastebookpage->chapter = ($lastebookpage->chapter == 0) ? 1 : $lastebookpage->chapter;
        $lastebookpage->page = $lastebookpage->page + 1;
        $lastebookpage->title = 'New Title  pg.'.$lastebookpage->page;
        //print_r($lastebookpage);
        $result = insert_record('course_format_ebook',$lastebookpage);
    }

    // Update ebook record with submitted data
    if ($editing and !empty($_GET['action']) and $_GET['action'] == 'updateebook') {
        $ok  = set_field('course_format_ebook','chapter',$_GET['ebookchapter'],'section',$topic,'courseid',$course->id);
        $ok &= set_field('course_format_ebook','page',$_GET['ebookpage'],'section',$topic,'courseid',$course->id);
        $ok &= set_field('course_format_ebook','title',$_GET['ebooktitle'],'section',$topic,'courseid',$course->id);
    }

    $ebook = get_record("course_format_ebook","section",$topic,'courseid',$course->id);

    // Print Section 0 - Overview as default Header for the course
    print_section($course, $sections[0], $mods, $modnamesused);
    if (isediting($course->id)) {
        print_section_add_menus($course, 0, $modnames);
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

                    // Special (fixed) Page Index for the current Chapter

                    $sql = 'SELECT DISTINCT * FROM '.$CFG->prefix.'course_format_ebook WHERE courseid = '.$course->id.' GROUP BY chapter ORDER BY page';
                    $chapters = get_records_sql($sql);

                    foreach ($chapters as $item) {
                        $chapterslist[$item->section] = 'Chapter '.$item->chapter;
                    }
                    $onchangejs = "document.getElementById('newchapter').topic.value = document.getElementById('newchapter').chapters[document.getElementById('newchapter').chapters.selectedIndex].value;document.getElementById('newchapter').submit();";
                    $choosechapter = choose_from_menu($chapterslist, "chapters", '', 'Show Chapter...',$onchangejs,'',true);

                    $displaychapter = empty($ebook->chapter) ? 1 : $ebook->chapter;
                    $sql = 'SELECT * FROM '.$CFG->prefix.'course_format_ebook WHERE courseid = '.$course->id.' AND chapter = '.$displaychapter.' ORDER BY page';
                    $pages_in_chapter = get_records_sql($sql);

                    // Display Previous Chapter link
                    reset($pages_in_chapter);
                    $previouschapter = current($pages_in_chapter);
                    $pageindex = '<form action="view.php" id="newchapter">';
                    $pageindex .= '<input type="hidden" name="topic" id="topic">';
                    $pageindex .= '<input type="hidden" name="id" value="'.$course->id.'">';
                    $pageindex .= $choosechapter;
                    $pageindex .= '</form>';


                    if ($previouschapter->section > 1) {
                        $pageindex .= '<div><a href="'.$CFG->wwwroot.'/course/view.php?id='.$id.'&topic='.($previouschapter->section-1).'"> Previous Chapter...</a><br/></div><hr/>';
                    } else {
                        $pageindex .= '';
                    }

                    foreach ($pages_in_chapter as $singlepage){
                        if ($singlepage->section == $topic) {
                            $selectedpage = ' class="selectedpage" ';
                        }   else {
                            $selectedpage = ' ';
                        }
                        $pageindex .= '<div '.$selectedpage.'><a href="'.$CFG->wwwroot.'/course/view.php?id='.$id.'&topic='.$singlepage->section.'"><span id="pagetitle">Page '.$singlepage->page.'</span>: <span id="pagetitle">'.$singlepage->title.'</span></a><br/></div>';
                        $lastpageinchapter = $singlepage->section;
                    }
                    // Display Next Chapter link
                    $pageindex .= '<hr/><div><a href="'.$CFG->wwwroot.'/course/view.php?id='.$id.'&topic='.($lastpageinchapter + 1).'"> Next Chapter...</a><br/></div>';

                    print_side_block('Pages for Chapter '.$ebook->chapter,$pageindex);

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


                // use topics or pages?
                //$section = ($topic>0 and $page==0) ? $topic : $page;
                //if ($section == 0) $section = $_COOKIE['ebookpage'];
                $imgleft = '<a href="view.php?id='.$_GET['id'].'&topic='.(string)(int)($topic-1).'"><img height="24" src="'.$CFG->wwwroot.'/course/format/ebook/arrow-left.png"></a>';
                $imgright = '<a href="view.php?id='.$_GET['id'].'&topic='.(string)(int)($topic+1).'"><img height="24" src="'.$CFG->wwwroot.'/course/format/ebook/arrow-right.png"></a>';
                if (right_to_left()) {
                  print_heading_block($imgright.' (Previous page) '.get_string('chapter','format_ebook')." $ebook->chapter ".get_string('page','format_ebook')." $ebook->page (Next page) $imgleft", 'outline');
                } else {
                  print_heading_block("$imgleft   ".get_string('page','format_ebook')." $ebook->page    $imgright", 'outline');
                }

                if ($editing) {
                    echo "<form id='ebook' action='view.php'>";
                        echo "Chapter <input id='ebookchapter' name='ebookchapter' value='$ebook->chapter' maxlength='5'>";
                        echo "Page <input id='ebookpage' name='ebookpage' value='$ebook->page' maxlength='5'>";
                        echo "Title <input id='ebooktitle' name='ebooktitle' value='$ebook->title' maxlength='25'>";
                        echo "<input name='topic' value='$topic' type='hidden'>";
                        echo "<input name='id' value='$id' type='hidden'>";
                        echo "<input name='action' type='submit' value='updateebook'>";
                    echo "</form>";
                }
                //echo "<style>input#ebookpage , input#ebookchapter { width:30px;}</style>";
//echo "$section";
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

                /// Now all the normal modules by topic
                /// Everything below uses "section" terminology - each "section" is a topic.

                $timenow = time();
                $sectionmenu = array();
                $section = $topic;
//                 while ($section <= $course->numsections) {
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
//echo "showsection = $showsection";
//echo "displaysection = $displaysection";
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

                            print_section($course, $thissection, $mods, $modnamesused);

                            if (isediting($course->id)) {
                                print_section_add_menus($course, $section, $modnames);
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

//                     $section++;
//                 }
                echo '</table>';

                if (!empty($sectionmenu)) {
                    echo '<div align="center" class="jumpmenu">';
                    echo popup_form($CFG->wwwroot.'/course/view.php?id='.$course->id.'&amp;', $sectionmenu,
                              'sectionmenu', '', get_string('jumpto'), '', '', true);
                    echo '</div>';
                }

                print_container_end();

                if (right_to_left()) {
                  print_heading_block($imgright.' (Previous page) '.get_string('chapter','format_ebook')." $ebook->chapter ".get_string('page','format_ebook')." $ebook->page (Next page) $imgleft", 'outline');
                } else {
                  print_heading_block("$imgleft   ".get_string('page','format_ebook')." $ebook->page    $imgright", 'outline');
                }

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

// move styles to a separate file (nadavkav)
echo '<style>
#course-view .section .activity {font-size: 2em;}
.section .activity img.activityicon {height: 32px;width: 32px;}
</style>';

// save current page using java script. since headers were already sent by php
//$ebookpage = ($topic>0 and $page==0) ? $topic : $page;
//setcookie('ebookpage', $ebookpage , time() + (86400 * 7)); // 86400 = 1 day
//setcookie('ebookchapter',$chapter,time() + (86400 * 7)); // 86400 = 1 day

if ($ebook->section > 0) {
  echo '<script>
  var date = new Date();
  var days = 1;
  date.setTime(date.getTime()+(days*24*60*60*1000));
  var expires = "; expires="+date.toGMTString();
  document.cookie = "ebookpage=" + "'.$ebook->page.'"; + expires + "; path=/";
  document.cookie = "ebookchapter=" + "'.$ebook->chapter.'" + expires + "; path=/";
  document.cookie = "ebooktopic=" + "'.$ebook->section.'" + expires + "; path=/";
  </script>';
}

?>