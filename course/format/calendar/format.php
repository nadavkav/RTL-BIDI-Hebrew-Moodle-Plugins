<?php // $Id: format.php,v 1.83.2.3 2008/12/10 06:05:27 dongsheng Exp $
      // Display the whole course as "topics" made of of modules
      // In fact, this is very similar to the "weeks" format, in that
      // each "topic" is actually a week.  The main difference is that
      // the dates aren't printed - it's just an aesthetic thing for
      // courses that aren't so rigidly defined by time.
      // Included from "view.php"
      

    require_once($CFG->libdir.'/ajax/ajaxlib.php');
  
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

/// Display course calendar

        global $USER, $CFG, $SESSION, $COURSE;
        $cal_m = optional_param( 'cal_m', 0, PARAM_INT );
        $cal_y = optional_param( 'cal_y', 0, PARAM_INT );

        date_default_timezone_set('Asia/Jerusalem');
        require_once($CFG->dirroot.'/calendar/lib.php');

        // Reset the session variables
        calendar_session_vars($COURSE);

        $calendar = '';

        // [pj] To me it looks like this if would never be needed, but Penny added it
        // when committing the /my/ stuff. Reminder to discuss and learn what it's about.
        // It definitely needs SOME comment here!
        $courseshown = $COURSE->id;

        if ($courseshown == SITEID) {
            // Being displayed at site level. This will cause the filter to fall back to auto-detecting
            // the list of courses it will be grabbing events from.
            $filtercourse    = NULL;
            $groupeventsfrom = NULL;
            $SESSION->cal_courses_shown = calendar_get_default_courses(true);
            calendar_set_referring_course(0);

        } else {
            //MDL-14693: fix calendar on resource page
            $courseshown =  optional_param( 'id', $COURSE->id, PARAM_INT );
            // Forcibly filter events to include only those from the particular course we are in.
            $filtercourse    = array($courseshown => $COURSE);
            $groupeventsfrom = array($courseshown => 1);
        }

        // We 'll need this later
        calendar_set_referring_course($courseshown);

        // MDL-9059, set to show this course when admins go into a course, then unset it.
        if ($COURSE->id != SITEID && !isset($SESSION->cal_courses_shown[$COURSE->id]) && has_capability('moodle/calendar:manageentries', get_context_instance(CONTEXT_SYSTEM))) {
            $courseset = true;
            $SESSION->cal_courses_shown[$COURSE->id] = $COURSE;
        }

        if (current_language() == 'he_utf8') { // nadavkav
          setlocale(LC_TIME, 'he_IL.utf8');
        }
        if (current_language() == 'en_utf8') {
          setlocale(LC_TIME, 'en_US');
        }

        // Be VERY careful with the format for default courses arguments!
        // Correct formatting is [courseid] => 1 to be concise with moodlelib.php functions.
        calendar_set_filters($courses, $group, $user, $filtercourse, $groupeventsfrom, false);
        if ($courseshown == SITEID) {
            // For the front page
            $calendar .= calendar_overlib_html();
            $calendar .= calendar_top_controls('frontpage', array('id' => $courseshown, 'm' => $cal_m, 'y' => $cal_y));
            $calendar .= calendar_get_mini($courses, $group, $user, $cal_m, $cal_y);
            // No filters for now

        } else {
            // For any other course
            //echo '<div id="topcalendar"><div id="calendarview">'.calendar_overlib_html();
            //echo calendar_top_controls('course', array('id' => $courseshown, 'm' => $cal_m, 'y' => $cal_y));
            // Layout the whole page as three big columns.
            echo '<table id="calendartable" style="height:100%;">';
            echo '<tr>';

            // START: Main column

            echo '<td class="maincalendar">';
            //$calendar .= calendar_get_mini($courses, $group, $user, $cal_m, $cal_y).'</div>';
            echo calendar_show_month_detailed($cal_m, $cal_y, $courses, $group, $user, $courseshown);
            //$calendar .= '<div id="calendarfilters"><h3 class="eventskey">'.get_string('eventskey', 'calendar').'</h3>';
            //$calendar .= '<div class="filters">'.calendar_filter_controls('course', '', $COURSE).'</div></div></div>';
            //$calendar .= "<div id='newevent'><a href='$CFG->wwwroot/calendar/event.php?action=new&course=2&cal_m=".date('n')."&cal_y=".date('Y')."'>".get_string('newevent','calendar')."</a></div>";
            echo '</td>';
            echo '</tr></table>';
        }

        // MDL-9059, unset this so that it doesn't stay in session
        if (!empty($courseset)) {
            unset($SESSION->cal_courses_shown[$COURSE->id]);
        }

        echo $calendar;

        // move styles to a separate file (nadavkav)
        echo '<style>
            #calendarview {float:right; width:70%;}
            #calendarfilters {float:left; width:25%;}
            #newevent {width:60px;}
            .calendarmonth {width: 100%;}
            .nottoday {width: 60px; height: 50px; border: 1px solid black;}
            #calendartable {width: 100%; height: 400px;}
            .dir-rtl li.event_course {margin-right:30px; margin-left: 0px;}
        </style>';

/// Calendar (end)



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

        print_section($course, $thissection, $mods, $modnamesused);

        if (isediting($course->id)) {
            print_section_add_menus($course, $section, $modnames);
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

        $section++;
    }
    echo '</table>';

    if (!empty($sectionmenu)) {
        echo '<div class="jumpmenu">';
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

require_once($CFG->dirroot.'/calendar/lib.php');

function calendar_show_month_detailed($m, $y, $courses, $groups, $users, $courseid) {
    global $CFG, $SESSION, $USER, $CALENDARDAYS;
    global $day, $mon, $yr;

    $getvars = 'from=month&amp;cal_d='.$day.'&amp;cal_m='.$mon.'&amp;cal_y='.$yr; // For filtering

    $display = &New stdClass;
    $display->minwday = get_user_preferences('calendar_startwday', CALENDAR_STARTING_WEEKDAY);
    $display->maxwday = $display->minwday + 6;

    if(!empty($m) && !empty($y)) {
        $thisdate = usergetdate(time()); // Time and day at the user's location
        if($m == $thisdate['mon'] && $y == $thisdate['year']) {
            // Navigated to this month
            $date = $thisdate;
            $display->thismonth = true;
        }
        else {
            // Navigated to other month, let's do a nice trick and save us a lot of work...
            if(!checkdate($m, 1, $y)) {
                $date = array('mday' => 1, 'mon' => $thisdate['mon'], 'year' => $thisdate['year']);
                $display->thismonth = true;
            }
            else {
                $date = array('mday' => 1, 'mon' => $m, 'year' => $y);
                $display->thismonth = false;
            }
        }
    }
    else {
        $date = usergetdate(time());
        $display->thismonth = true;
    }

    // Fill in the variables we 're going to use, nice and tidy
    list($d, $m, $y) = array($date['mday'], $date['mon'], $date['year']); // This is what we want to display
    $display->maxdays = calendar_days_in_month($m, $y);

    $startwday = 0;
    if (get_user_timezone_offset() < 99) {
        // We 'll keep these values as GMT here, and offset them when the time comes to query the db
        $display->tstart = gmmktime(0, 0, 0, $m, 1, $y); // This is GMT
        $display->tend = gmmktime(23, 59, 59, $m, $display->maxdays, $y); // GMT
        $startwday = gmdate('w', $display->tstart); // $display->tstart is already GMT, so don't use date(): messes with server's TZ
    } else {
        // no timezone info specified
        $display->tstart = mktime(0, 0, 0, $m, 1, $y);
        $display->tend = mktime(23, 59, 59, $m, $display->maxdays, $y);
        $startwday = date('w', $display->tstart); // $display->tstart not necessarily GMT, so use date()
    }

    // Align the starting weekday to fall in our display range
    if($startwday < $display->minwday) {
        $startwday += 7;
    }

    // Get events from database
    $events = calendar_get_events(usertime($display->tstart), usertime($display->tend), $users, $groups, $courses);
    if (!empty($events)) {
        foreach($events as $eventid => $event) {
            if (!empty($event->modulename)) {
                $cm = get_coursemodule_from_instance($event->modulename, $event->instance);
                if (!groups_course_module_visible($cm)) {
                    unset($events[$eventid]);
                }
            }
        }
    }

    // Extract information: events vs. time
    calendar_events_by_day($events, $m, $y, $eventsbyday, $durationbyday, $typesbyday, $courses);

    $text = '';
    if(!isguest() && !empty($USER->id) && calendar_user_can_add_event()) {
        $text.= '<div class="buttons"><form action="'.CALENDAR_URL.'event.php" method="get">';
        $text.= '<div>';
        $text.= '<input type="hidden" name="action" value="new" />';
        $text.= '<input type="hidden" name="course" value="'.$courseid.'" />';
        $text.= '<input type="hidden" name="cal_m" value="'.$m.'" />';
        $text.= '<input type="hidden" name="cal_y" value="'.$y.'" />';
        $text.= '<input type="submit" value="'.get_string('newevent', 'calendar').'" />';
        $text.= '</div></form></div>';
    }

//    $text .= '<label for="cal_course_flt_jump">'.
//               get_string('detailedmonthview', 'calendar').
//             ':</label>'.
//             calendar_course_filter_selector($getvars);

    echo '<div class="header">'.$text.'</div>';

    echo '<div class="controls">';
    //echo calendar_top_controls('course', array('id' => $courseshown, 'm' => $cal_m, 'y' => $cal_y));
    echo calendar_top_controls('course', array('id' => $courseid, 'm' => $m, 'y' => $y));
    echo '</div>';

    // Start calendar display
    echo '<table class="calendarmonth"><tr class="weekdays">'; // Begin table. First row: day names

    // Print out the names of the weekdays
    for($i = $display->minwday; $i <= $display->maxwday; ++$i) {
        // This uses the % operator to get the correct weekday no matter what shift we have
        // applied to the $display->minwday : $display->maxwday range from the default 0 : 6
        echo '<th scope="col">'.get_string($CALENDARDAYS[$i % 7], 'calendar').'</th>';
    }

    echo '</tr><tr>'; // End of day names; prepare for day numbers

    // For the table display. $week is the row; $dayweek is the column.
    $week = 1;
    $dayweek = $startwday;

    // Paddding (the first week may have blank days in the beginning)
    for($i = $display->minwday; $i < $startwday; ++$i) {
        echo '<td class="nottoday">&nbsp;</td>'."\n";
    }

    // Now display all the calendar
    for($day = 1; $day <= $display->maxdays; ++$day, ++$dayweek) {
        if($dayweek > $display->maxwday) {
            // We need to change week (table row)
            echo "</tr>\n<tr>";
            $dayweek = $display->minwday;
            ++$week;
        }

        // Reset vars
        $cell = '';
        $dayhref = calendar_get_link_href(CALENDAR_URL.'view.php?view=day&amp;id='.$courseid.'&amp;', $day, $m, $y);

        if(CALENDAR_WEEKEND & (1 << ($dayweek % 7))) {
            // Weekend. This is true no matter what the exact range is.
            $class = 'weekend';
        }
        else {
            // Normal working day.
            $class = '';
        }

        // Special visual fx if an event is defined
        if(isset($eventsbyday[$day])) {
            if(count($eventsbyday[$day]) == 1) {
                $title = get_string('oneevent', 'calendar');
            }
            else {
                $title = get_string('manyevents', 'calendar', count($eventsbyday[$day]));
            }
            $cell = '<div class="day"><a href="'.$dayhref.'" title="'.$title.'">'.$day.'</a></div>';
        }
        else {
            $cell = '<div class="day">'.$day.'</div>';
        }

        // Special visual fx if an event spans many days
        if(isset($typesbyday[$day]['durationglobal'])) {
            $class .= ' duration_global';
        }
        else if(isset($typesbyday[$day]['durationcourse'])) {
            $class .= ' duration_course';
        }
        else if(isset($typesbyday[$day]['durationgroup'])) {
            $class .= ' duration_group';
        }
        else if(isset($typesbyday[$day]['durationuser'])) {
            $class .= ' duration_user';
        }

        // Special visual fx for today
        if($display->thismonth && $day == $d) {
            $class .= ' today';
        } else {
            $class .= ' nottoday';
        }

        // Just display it
        if(!empty($class)) {
            $class = ' class="'.trim($class).'"';
        }
        echo '<td'.$class.'>'.$cell;

        if(isset($eventsbyday[$day])) {
            echo '<ul class="events-new">';
            foreach($eventsbyday[$day] as $eventindex) {

                // If event has a class set then add it to the event <li> tag
                $eventclass = '';
                if (!empty($events[$eventindex]->class)) {
                    $eventclass = ' class="'.$events[$eventindex]->class.'"';
                }

                echo '<li'.$eventclass.'><a href="'.$dayhref.'#event_'.$events[$eventindex]->id.'">'.format_string($events[$eventindex]->name, true).'</a></li>';
            }
            echo '</ul>';
        }
        if(isset($durationbyday[$day])) {
            echo '<ul class="events-underway">';
            foreach($durationbyday[$day] as $eventindex) {
                echo '<li>['.format_string($events[$eventindex]->name,true).']</li>';
            }
            echo '</ul>';
        }
        echo "</td>\n";
    }

    // Paddding (the last week may have blank days at the end)
    for($i = $dayweek; $i <= $display->maxwday; ++$i) {
        echo '<td class="nottoday">&nbsp;</td>';
    }
    echo "</tr>\n"; // Last row ends

    echo "</table>\n"; // Tabular display of days ends

    // OK, now for the filtering display
    echo '<div class="filters"><table><tr>';

    // Global events
    if($SESSION->cal_show_global) {
        echo '<td class="event_global" style="width: 8px;"></td><td><strong>'.get_string('globalevents', 'calendar').':</strong> ';
        echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showglobal&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
    } else {
        echo '<td style="width: 8px;"></td><td><strong>'.get_string('globalevents', 'calendar').':</strong> ';
        echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showglobal&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
    }

    // Course events
    if(!empty($SESSION->cal_show_course)) {
        echo '<td class="event_course" style="width: 8px;"></td><td><strong>'.get_string('courseevents', 'calendar').':</strong> ';
        echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showcourses&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
    } else {
        echo '<td style="width: 8px;"></td><td><strong>'.get_string('courseevents', 'calendar').':</strong> ';
        echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showcourses&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
    }

    echo "</tr>\n";

    if(!empty($USER->id) && !isguest()) {
        echo '<tr>';
        // Group events
        if($SESSION->cal_show_groups) {
            echo '<td class="event_group" style="width: 8px;"></td><td><strong>'.get_string('groupevents', 'calendar').':</strong> ';
            echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showgroups&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
        } else {
            echo '<td style="width: 8px;"></td><td><strong>'.get_string('groupevents', 'calendar').':</strong> ';
            echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showgroups&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
        }
        // User events
        if($SESSION->cal_show_user) {
            echo '<td class="event_user" style="width: 8px;"></td><td><strong>'.get_string('userevents', 'calendar').':</strong> ';
            echo get_string('shown', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showuser&amp;'.$getvars.'">'.get_string('clickhide', 'calendar').'</a>)</td>'."\n";
        } else {
            echo '<td style="width: 8px;"></td><td><strong>'.get_string('userevents', 'calendar').':</strong> ';
            echo get_string('hidden', 'calendar').' (<a href="'.CALENDAR_URL.'set.php?var=showuser&amp;'.$getvars.'">'.get_string('clickshow', 'calendar').'</a>)</td>'."\n";
        }
        echo "</tr>\n";
    }

    echo '</table></div>';
}

function calendar_course_filter_selector($getvars = '') {
    global $USER, $SESSION;

    if (empty($USER->id) or isguest()) {
        return '';
    }

    if (has_capability('moodle/calendar:manageentries', get_context_instance(CONTEXT_SYSTEM)) && !empty($CFG->calendar_adminseesall)) {
        $courses = get_courses('all', 'c.shortname','c.id,c.shortname');
    } else {
        $courses = get_my_courses($USER->id, 'shortname');
    }

    unset($courses[SITEID]);

    $courseoptions[SITEID] = get_string('fulllistofcourses');
    foreach ($courses as $course) {
        $courseoptions[$course->id] = format_string($course->shortname);
    }

    if (is_numeric($SESSION->cal_courses_shown)) {
        $selected = $SESSION->cal_courses_shown;
    } else {
        $selected = '';
    }

    return popup_form(CALENDAR_URL.'set.php?var=setcourse&amp;'.$getvars.'&amp;id=',
                       $courseoptions, 'cal_course_flt', $selected, '', '', '', true);
}

?>
