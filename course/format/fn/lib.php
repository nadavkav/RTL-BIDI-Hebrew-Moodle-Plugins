<?php
require_once ($CFG->dirroot.'/course/lib.php');

define('FN_EXTRASECTION', 9999);     // A non-existant section to hold hidden modules.

/// Format Specific Functions:
function FN_update_course($form, $oldformat = false) {
    global $CFG;

    /// Updates course specific variables.
    /// Variables are: 'showsection0', 'showannouncements'.

    $config_vars = array('showsection0', 'showannouncements', 'sec0title', 'showhelpdoc', 'showclassforum',
                         'showclasschat', 'logo', 'mycourseblockdisplay',
                         'showgallery', 'gallerydefault', 'usesitegroups', 'mainheading', 'topicheading',
                         'activitytracking', 'ttmarking', 'ttgradebook', 'ttdocuments', 'ttstaff',
                         'defreadconfirmmess', 'usemandatory', 'expforumsec');
    foreach ($config_vars as $config_var) {
        if ($varrec = get_record('course_config_FN', 'courseid', $form->id, 'variable', $config_var)) {
            $varrec->value = $form->$config_var;
            update_record('course_config_FN', $varrec);
        } else {
            $varrec->courseid = $form->id;
            $varrec->variable = $config_var;
            $varrec->value = $form->$config_var;
            insert_record('course_config_FN', $varrec);
        }
    }

    /// We need to have the sections created ahead of time for the weekly nav to work,
    /// so check and create here.
    if (!($sections = get_all_sections($form->id))) {
        $sections = array();
    }

    for ($i = 0; $i <= $form->numsections; $i++) {
        if (empty($sections[$i])) {
            $section = new Object();
            $section->course = $form->id;   // Create a new section structure
            $section->section = $i;
            $section->summary = "";
            $section->visible = 1;
            if (!$section->id = insert_record("course_sections", $section)) {
                notify("Error inserting new section!");
            }
        }
    }

    /// Check for a change to an FN format. If so, set some defaults as well...
    if ($oldformat != 'FN') {
        /// Set the news (announcements) forum to no force subscribe, and no posts or discussions.
        require_once($CFG->dirroot.'/mod/forum/lib.php');
        $news = forum_get_course_forum($form->id, 'news');
        $news->open = 0;
        $news->forcesubscribe = 0;
        update_record('forum', $news);
    }
    rebuild_course_cache($form->id);
}

function FN_get_course(&$course) {
    /// Add course specific variable to the passed in parameter.

    if ($config_vars = get_records('course_config_fn', 'courseid', $course->id)) {
        foreach ($config_vars as $config_var) {
            $course->{$config_var->variable} = $config_var->value;
        }
    }
}

/// Mandatory activity functions.
function get_mandatory_activity($courseid, $userid) {
    global $CFG, $FULLME;

    $sql = 'SELECT q.* FROM '.$CFG->prefix.'course_modules c, '.$CFG->prefix.'questionnaire q, '.
                $CFG->prefix.'modules m '.
           'WHERE c.course = '.$courseid.' AND m.name = \'questionnaire\' AND c.module = m.id '.
                'AND q.id = c.instance AND q.numignores != -1 ';
    if (($activities = get_records_sql($sql)) !== false) {
        require_once($CFG->dirroot.'/mod/questionnaire/lib.php');
        foreach ($activities as $activity) {
            if (questionnaire_user_can_take($activity, $userid)) {
                if (($numignores = questionnaire_num_ignores_left($activity, $userid)) !== false) {
                    $message = '<h3>Survey Reminder</h3>'.
                               'You are required to complete the questionnaire '.$activity->name.
                               ':<br />'.$activity->summary.'.<br /><br />'.
                               '<a href="'.$CFG->wwwroot.'/mod/questionnaire/view.php?a='.
                                         $activity->id.'">Complete questionnaire now</a><br /><br />';
                    if ($numignores > 0) {
                        $message .= '<a href="'.$FULLME.'&amp;remindlater='.$activity->id.
                                    '">Remind me tomorrow ('.$numignores.' reminders left)';
                    }
                    return $message;
                }
            }
        }
    }
    return false;
}

function print_weekly_activities_bar(&$course, &$sections, &$mods, $week=0) {

    global $THEME, $FULLME, $CFG;

    $timenow = time();
    $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
    $weekdate += 7200;                 // Add two hours to avoid possible DST problems
    $weekofseconds = 604800;

    if (isset($course->topicheading) && !empty($course->topicheading)) {
        $strtopicheading = $course->topicheading;
    } else {
        $strtopicheading = get_string('namefn','format_fn');//'Week';
    }

    $url = preg_replace('/(^.*)(&selected_week\=\d+)(.*)/', '$1$3', $FULLME);

    $actbar = '';
    $actbar .= '<table cellpadding="0" cellspacing="0" width="100%" class="fnweeklynav"><tr>';
    $width = (int)(100 / ($course->numsections+2));
    $actbar .= '<td width="4" align="center" height="25"></td>';
    $actbar .= '<td height="25">'.$strtopicheading.':&nbsp;</td>';
    $isteacher = isteacher($course->id);
    for ($i = 1; $i <= $course->numsections; $i++) {
        if (!$sections[$i]->visible || ($timenow < $weekdate)) {
            if ($i == $week) {
                $css = 'fnweeklynavdisabledselected';
            } else {
                $css = 'fnweeklynavdisabled';
            }
            if ($isteacher) {
                $f = '<a href="'.$url.'&selected_week='.$i.'" ><span class="'.$css.'">&nbsp;'.$i.'&nbsp;</span></a>';
            } else {
                $f = ' '.$i.' ';
            }
            $actbar .= '<td class="'.$css.'" height="25" width="'.$width.'%">'.$f.'</td>';
        }
        else if ($i == $week) {
            if (!$isteacher && $course->activitytracking && is_section_finished($sections[$i], $mods)) {
                $f = '<img src="'.$CFG->wwwroot.'/course/format/'.$course->format.'/pix/sectcompleted.gif" '.
                     'height="18" width="16" alt="Section Completed" title="Section Completed" align="right" hspace="0" vspace="0">';
            } else {
                $f = '';
            }
            $actbar .= '<td class="fnweeklynavselected" width="'.$width.'%" height="25">'.$f.$i.'</td>';
        }
        else {
            if (!$isteacher && $course->activitytracking && is_section_finished($sections[$i], $mods)) {
                $f = '<img src="'.$CFG->wwwroot.'/course/format/'.$course->format.'/pix/sectcompleted.gif" '.
                     'height="18" width="16" alt="Section Completed" title="Section Completed" align="right" hspace="0" vspace="0">';
            } else {
                $f = '';
            }
            $actbar .= '<td class="fnweeklynavnorm" width="'.$width.'%" height="25">'.
                       $f.'<a href="'.$url.'&selected_week='.$i.'">&nbsp;'.$i.'&nbsp;</a>'.'</td>';
        }
        $weekdate += ($weekofseconds);
        $actbar .= '<td align="center" height="25" style="width: 2px;">'.
                   '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" height="1" width="1" alt="" /></td>';
    }
    if ($week == 0) {
        $actbar .= '<td class="fnweeklynavselected" width="'.$width.'%" height="25">All</td>';
    }
    else {
        $actbar .= '<td class="fnweeklynavnorm" width="'.$width.'%" height="25">' .
                   '<a href="'.$url.'&selected_week=0">All</a></td>';
    }
    $actbar .= '<td width="1" align="center" height="25"></td>';
    $actbar .= '</tr>';
    $actbar .= '<tr>';
    $actbar .= '<td height="3" colspan="2"></td>';
    for ($i = 1; $i <= $course->numsections; $i++) {
        if ($i == $week) {
            $actbar .= '<td height="3" class="fnweeklynavselected"></td>';
        } else {
            $actbar .= '<td height="3"></td>';
        }
        $actbar .= '<td height="3"></td>';
    }
    $actbar .= '<td height="3" colspan="2"></td>';
    $actbar .= '</tr>';
    $actbar .= '</table>';

    return $actbar;
}

function fn_print_mandatory_section(&$course, &$mods, &$modnamesused, &$sections) {
    global $CFG, $USER, $THEME;

    $labeltext = '';
    $activitytext = '';

    /// Determine order using all sections.
    $orderedmods = array();
    foreach ($sections as $section) {
        $modseq = explode(",", $section->sequence);
        if (!empty($modseq)) {
            foreach ($modseq as $modnum) {
                if (!empty($mods[$modnum]) && $mods[$modnum]->mandatory && $mods[$modnum]->visible) {
                    $orderedmods[] = $mods[$modnum];
                }
            }
        }
    }

    $modinfo = unserialize($course->modinfo);
    foreach ($orderedmods as $mod) {
        if ($mod->mandatory && $mod->visible) {
            $instancename = urldecode($modinfo[$mod->id]->name);
            if (!empty($CFG->filterall)) {
                $instancename = filter_text("<nolink>$instancename</nolink>", $course->id);
            }

            if (!empty($modinfo[$mod->id]->extra)) {
                $extra = urldecode($modinfo[$mod->id]->extra);
            } else {
                $extra = "";
            }

            if (!empty($modinfo[$mod->id]->icon)) {
                $icon = "$CFG->pixpath/".urldecode($modinfo[$mod->id]->icon);
            } else {
                $icon = "$CFG->modpixpath/$mod->modname/icon.gif";
            }

            if ($mod->indent) {
                print_spacer(12, 20 * $mod->indent, false);
            }

            if ($mod->modname == "label") {
                if (!$mod->visible) {
                    $labeltext .= "<span class=\"dimmed_text\">";
                }
                $labeltext .= format_text($extra, FORMAT_HTML);
                if (!$mod->visible) {
                    $labeltext .= "</span>";
                }
                $labeltext .= '<br />';
            } else if ($mod->modname == "resource") {
                $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                $alttext = isset($link_title[$mod->modname]) ? $link_title[$mod->modname] : $mod->modfullname;
                $labeltext .= "<img src=\"$icon\"".
                     " height=16 width=16 alt=\"$alttext\">".
                     " <font size=2><a title=\"$alttext\" $linkcss $extra".
                     " href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">$instancename</a></font><br />";
            } else { // Normal activity

                $act_compl = is_activity_complete($mod, $USER->id);
                if ($act_compl === false) {
                    $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                    $alttext = isset($link_title[$mod->modname]) ? $link_title[$mod->modname] : $mod->modfullname;
                    $activitytext .= "<img src=\"$icon\"".
                         " height=16 width=16 alt=\"$alttext\">".
                         " <font size=2><a title=\"$alttext\" $linkcss $extra".
                         " href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">$instancename</a></font><br />";
                }
            }
        }
    }

    print_simple_box('<div align="right">'.$labeltext.'</div>', 'center', '100%');
//    print_simple_box('<div align="left">'.$activitytext.'</div>', 'center', '100%');
}

function first_unfinished_section(&$sections, &$mods) {
    if (is_array($sections) && is_array($mods)) {
        foreach ($sections as $section) {
            if ($section->section > 0) {
                if (!is_section_finished($section, $mods)) {
                    return $section->section;
                }
            }
        }
    }
    return false;
}

function is_section_finished(&$section, &$mods) {
    global $USER;

    if ($modnums = explode(',', $section->sequence)) {
        foreach($modnums as $modnum) {
            if (isset($mods[$modnum])) {
                $act_compl = is_activity_complete($mods[$modnum], $USER->id);
                if (($act_compl === false) || (is_int($act_compl) && ($act_compl < 50)) ||
                    ($act_compl == 'submitted')) {
                    return false;
                }
            }
        }
    }
    return true;
}

function fn_set_mandatory_for_module($id, $mandatory) {
    return set_field("course_modules", "mandatory", $mandatory, "id", $id);
}

function fn_all_mandatory_completed($courseid, &$mods) {
    global $USER;

    if (isteacheredit($courseid)) {
        return true;
    }

    foreach ($mods as $mod) {
        if ($mod->mandatory && $mod->visible && (is_activity_complete($mod, $USER->id) === false)) {
            return false;
        }
    }
    return true;
}

function set_resource_complete($resid, $userid) {
    $dataobj = new object();
    $dataobj->resourceid = $resid;
    $dataobj->userid = $userid;
    $dataobj->timecompleted = time();
    return insert_record('resource_completed', $dataobj, false, 'resourceid');
}

function block_resource($mod) {

    $field = get_field('course_sections', 'section', 'id', $mod->section);
    return ($field !== false and $field == FN_EXTRASECTION);
}

function FN_is_marker($courseid, $userid=0) {
    global $USER;

    if ($userid == 0 && !empty($USER->id)) {
        $userid = $USER->id;
    }

    /// TO DO: Is this still needed?
    return has_capability('moodle/course:managegrades', get_context_instance(CONTEXT_COURSE, $courseid));
//    return ((isadmin($userid)) ||
//            (get_record('course_marker_FN', 'courseid', $courseid, 'userid', $userid)));
}

//function assignment_count_ungraded($assignment, $graded, $students, $show='unmarked', $extra=false) {
//    $studentlist = implode(',', array_keys($students));
//
//    if (!empty($extra) && ($extra['type'] == 'fnassignment')) {
//        $subtable = 'fnassignment_submissions';
//    } else {
//        $subtable = 'assignment_submissions';
//    }
//
//    if (($show == 'unmarked') || ($show == 'all')) {
//        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
//                  '(timemarked < timemodified) AND (timemodified > 0)';
//        return count_records_select($subtable, $select, 'COUNT(DISTINCT userid)');
//    } else if ($show == 'marked') {
//        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
//                  '(timemarked >= timemodified) AND (timemodified > 0)';
//        $marked = count_records_select($subtable, $select, 'COUNT(DISTINCT userid)');
//        return $marked;
//    } else if ($show == 'unsubmitted') {
//    	$select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
//                  '(timemodified > 0)';
//        $subbed = count_records_select($subtable, $select, 'COUNT(DISTINCT userid)');
//        $unsubbed = abs(count($students) - $subbed);
//        return ($unsubbed);
//    } else {
//        return 0;
//    }
//}
//
//function fnassignment_count_ungraded($assignment, $graded, $students, $show='unmarked', $extra=false) {
//    $studentlist = implode(',', array_keys($students));
//
//    $subtable = 'fnassignment_submissions';
//
//    if (($show == 'unmarked') || ($show == 'all')) {
//        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
//                  '(timemarked < timemodified) AND (timemodified > 0)';
//        return count_records_select($subtable, $select, 'COUNT(DISTINCT userid)');
//    } else if ($show == 'marked') {
//        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
//                  '(timemarked >= timemodified) AND (timemodified > 0)';
//        $marked = count_records_select($subtable, $select, 'COUNT(DISTINCT userid)');
//        return $marked;
//    } else if ($show == 'unsubmitted') {
//        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
//                  '(timemodified <= 0)';
//        $unsubbed = count_records_select($subtable, $select, 'COUNT(DISTINCT userid)');
//        $subbed = abs(count($students) - $unsubbed);
//        return ($unsubbed);
//    } else {
//        return 0;
//    }
//}
//
//function assignment_oldest_ungraded($assignment) {
//    global $CFG;
//
//    $sql = 'SELECT MIN(timemodified) FROM '.$CFG->prefix.'assignment_submissions '.
//           'WHERE (assignment = '.$assignment.') AND (timemarked < timemodified) AND (timemodified > 0)';
//    return get_field_sql($sql);
//}
//
//function exercise_count_ungraded($exerciseid, $graded, $students) {
//    global $CFG;
//    require_once ($CFG->dirroot.'/mod/exercise/locallib.php');
//    $exercise = get_record('exercise', 'id', $exerciseid);
//    return exercise_count_unassessed_student_submissions($exercise);
//}
//
//function forum_count_ungraded($forumid, $graded, $students, $show='unmarked') {
//    global $CFG;
//
//    //Get students from forum_posts
//    $fusers = get_records_sql("SELECT DISTINCT u.*
//                                 FROM {$CFG->prefix}user u,
//                                      {$CFG->prefix}forum_discussions d,
//                                      {$CFG->prefix}forum_posts p
//                                 WHERE d.forum = '$forumid' and
//                                       p.discussion = d.id and
//                                       u.id = p.userid");
//
//    if (is_array($fusers)) {
////        if (function_exists('array_intersect_assoc')) {
////            $fusers = array_intersect_assoc($students, $fusers);
////        } else {
//            foreach ($fusers as $key => $user) {
//                if (!array_key_exists($key, $students)) {
//                    unset($fusers[$key]);
//                }
//            }
////        }
//    }
//
//    if (($show == 'unmarked') || ($show == 'all')) {
//        if (empty($graded) && !empty($fusers)) {
//            return count($fusers);
//        } else if (empty($fusers)) {
//            return 0;
//        } else {
//            return (count($fusers) - count($graded));
//        }
//    } else if ($show == 'marked') {
//        return count($graded);
//    } else if ($show == 'unsubmitted') {
//        $numuns = count($students) - count($fusers);
//        return max(0, $numuns);
//    }
//}
//
//function journal_count_ungraded($journalid, $graded, $students, $show='unmarked') {
//    $studentlist = implode(',', $graded);
//
//    if (($show == 'unmarked') || ($show == 'all')) {
//        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
//                  'timemarked < modified AND modified > 0';
//        return count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
//    } else if ($show == 'marked') {
//        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
//                  'timemarked < modified AND modified > 0';
//        $unmarked = count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
//        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
//                  'modified = 0';
//        $unsubbed = count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
//        return count($graded) - ($unsubbed + $unmarked);
//    } else if ($show == 'unsubmitted') {
//        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
//                  'modified = 0';
//        return count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
//    } else {
//        return 0;
//    }
//}
//
//function journal_oldest_ungraded($journalid) {
//    global $CFG;
//
//    $sql = 'SELECT MIN(modified) FROM '.$CFG->prefix.'journal_entries '.
//           'WHERE (journal = '.$journalid.') AND (timemarked < modified) AND (modified > 0)';
//    return get_field_sql($sql);
//}

/// This function is similar to other Moodle update functions. It should be called from the
/// FN format.php if the user is an administrator.
function check_for_fn_update() {
    global $CFG, $course;

    if (!isadmin()) {
        return true;
    }

    include($CFG->dirroot.'/course/format/fn/version.php');
    if (!isset($CFG->firstnations_version) || ($fn->version > $CFG->firstnations_version)) {
        require_once($CFG->dirroot.'/course/format/fn/db/mysql.php');
        fn_upgrade($CFG->firstnations_version);
    }

    /// Check for section 9999 creation.
    if (!($section = get_record('course_sections', 'course', $course->id, 'section', FN_EXTRASECTION))) {
        $section->course = $course->id;
        $section->section = FN_EXTRASECTION;
        $section->sequence = '';
        $section->summary = '';
        $section->visible = 1;
        $section->id = insert_record('course_sections', $section);
    }

    /// FN - Look for a 'Class Forum'.
    if (($cm = FN_get_sideblock_activity('forum', 'Class Forum', $course->id)) === false) {
        $forum->course = $course->id;
        $forum->type = 'general';
        $forum->name = 'Class Forum';
        $forum->intro = 'This is your class forum.';
        $forum->open = 2;
        $forum->scale = -1;
        $forum->maxbytes = 1;
        $forum->timemodified = time();
        $forum->id = insert_record('forum', $forum);

        $fmid = get_field('modules', 'id', 'name', 'forum');
        $cm->course = $course->id;
        $cm->module = $fmid;
        $cm->instance = $forum->id;
        $cm->section = $section->id;
        $cm->added = time();
        $cm->visible = 1;
        $cm->id = insert_record('course_modules', $cm);

        if (empty($section->sequence)) {
            $section->sequence = $cm->id;
        } else {
            $section->sequence .= ','.$cm->id;
        }
        update_record('course_sections', $section);
    }

    /// FN - Look for a 'Class Chat Room'.
    if (($cm = FN_get_sideblock_activity('chat', 'Class Chat Room', $course->id)) === false) {
        $chat->course = $course->id;
        $chat->name = 'Class Chat Room';
        $chat->intro = 'This is your class chat room.';
        $chat->timemodified = time();
        $chat->id = insert_record('chat', $chat);

        $fmid = get_field('modules', 'id', 'name', 'chat');
        $cm->course = $course->id;
        $cm->module = $fmid;
        $cm->instance = $chat->id;
        $cm->section = $section->id;
        $cm->added = time();
        $cm->visible = 1;
        $cm->id = insert_record('course_modules', $cm);

        if (empty($section->sequence)) {
            $section->sequence = $cm->id;
        } else {
            $section->sequence .= ','.$cm->id;
        }
        update_record('course_sections', $section);
    }

    /// FN - Look for a 'My Journal'.
    if (($cm = FN_get_sideblock_activity('journal', 'My Journal', $course->id)) === false) {
        $journal->course = $course->id;
        $journal->name = 'My Journal';
        $journal->intro = 'This is your journal.';
        $journal->days = 0;
        $journal->assessed = 0;
        $journal->timemodified = time();
        $journal->id = insert_record('journal', $journal);

        $fmid = get_field('modules', 'id', 'name', 'journal');
        $cm->course = $course->id;
        $cm->module = $fmid;
        $cm->instance = $journal->id;
        $cm->section = $section->id;
        $cm->added = time();
        $cm->visible = 1;
        $cm->id = insert_record('course_modules', $cm);

        if (empty($section->sequence)) {
            $section->sequence = $cm->id;
        } else {
            $section->sequence .= ','.$cm->id;
        }
        update_record('course_sections', $section);
    }

    /// FN - Look for a 'Class Glossary'.
    if (($cm = FN_get_sideblock_activity('glossary', 'Glossary', $course->id)) === false) {
        $glossary->course = $course->id;
        $glossary->name = 'Glossary';
        $glossary->intro = 'Class glossary.';
        $glossary->studentcanpost = 1;
        $glossary->allowduplicatedentries = 0;
        $glossary->displayformat = 'dictionary';
        $glossary->timecreated = time();
        $glossary->timemodified = time();
        $glossary->id = insert_record('glossary', $glossary);

        $fmid = get_field('modules', 'id', 'name', 'glossary');
        $cm->course = $course->id;
        $cm->module = $fmid;
        $cm->instance = $glossary->id;
        $cm->section = $section->id;
        $cm->added = time();
        $cm->visible = 1;
        $cm->id = insert_record('course_modules', $cm);

        if (empty($section->sequence)) {
            $section->sequence = $cm->id;
        } else {
            $section->sequence .= ','.$cm->id;
        }
        update_record('course_sections', $section);
    }

    /// FN - Look for a 'Course Info'.
    if (($cm = FN_get_sideblock_activity('resource', 'Course Info', $course->id)) === false) {
        $resource->course = $course->id;
        $resource->name = 'Course Info';
        $resource->type = 'html';
        $resource->summary = 'Course Info.';
        $resource->alltext = '<p>Course Info.</p>';
        $resource->popup = '';
        $resource->timemodified = time();
        $resource->id = insert_record('resource', $resource);

        $fmid = get_field('modules', 'id', 'name', 'resource');
        $cm->course = $course->id;
        $cm->module = $fmid;
        $cm->instance = $resource->id;
        $cm->section = $section->id;
        $cm->added = time();
        $cm->visible = 1;
        $cm->id = insert_record('course_modules', $cm);

        if (empty($section->sequence)) {
            $section->sequence = $cm->id;
        } else {
            $section->sequence .= ','.$cm->id;
        }
        update_record('course_sections', $section);
    }

    /// FN - Look for a 'Gallery'.
    if (($cm = FN_get_sideblock_activity('resource', 'Gallery', $course->id)) === false) {
        $resource->course = $course->id;
        $resource->name = 'Gallery';
        $resource->type = 'file';
        $resource->summary = '';
        $resource->alltext = 'gallerydefault=id';
        $resource->reference = 'http://moodlefn.firstnationschools.ca/galleryfn/index.php';
        $resource->options = 'frame';
        $resource->timemodified = time();
        $resource->id = insert_record('resource', $resource);

        $fmid = get_field('modules', 'id', 'name', 'resource');
        $cm->course = $course->id;
        $cm->module = $fmid;
        $cm->instance = $resource->id;
        $cm->section = $section->id;
        $cm->added = time();
        $cm->visible = 1;
        $cm->id = insert_record('course_modules', $cm);

        if (empty($section->sequence)) {
            $section->sequence = $cm->id;
        } else {
            $section->sequence .= ','.$cm->id;
        }
        update_record('course_sections', $section);
    }
}

function FN_get_sideblock_activity($activity_name, $instance_name, $courseid) {
    global $CFG;

    $sql = 'SELECT cm.* '.
           'FROM '.$CFG->prefix.'course_modules cm, '.$CFG->prefix.'modules m, '.
           $CFG->prefix.'course_sections cs, '.$CFG->prefix.$activity_name.' a '.
           'WHERE cs.course='.$courseid.' AND cs.section='.FN_EXTRASECTION.' AND cm.section=cs.id '.
           'AND m.name = \''.$activity_name.'\' AND cm.module=m.id '.
           'AND a.name=\''.$instance_name.'\' AND a.course='.$courseid.' AND a.id=cm.instance';
    return get_record_sql($sql);
}

function section_available(&$course, $sectnum) {
// Returns true if the weekly section number is within the course
// timeframe.

    $timenow = time();
    $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
    $weekdate += 7200;                 // Add two hours to avoid possible DST problems
    $weekofseconds = 604800;

    $currentweek = ($timenow > $course->startdate) ?
                    (int)((($timenow - $course->startdate) / $weekofseconds)+1) : 0;
    $currentweek = min($currentweek, $course->numsections);

    return ($sectnum <= $currentweek);
}

function FN_lock_user_profile($userid) {
    return (set_field('user', 'lockprofile', '1', 'id', $userid));
}

function FN_unlock_user_profile($userid) {
    return (set_field('user', 'lockprofile', '0', 'id', $userid));
}

function FN_block_user_chat($userid) {
    return (set_field('user', 'chatblocked', '1', 'id', $userid));
}

function FN_unblock_user_chat($userid) {
    return (set_field('user', 'chatblocked', '0', 'id', $userid));
}

function FN_get_user_average($userid) {
    global $CFG, $course, $sections, $mods;

/// Search through all the modules, pulling out grade data
    for ($i=0; $i<=$course->numsections; $i++) {
        if (isset($sections[$i])) {   // should always be true
            $section = $sections[$i];
            $avail = !empty($section->sequence) && $section->visible && section_available($course, $i);
            if ($avail) {
                $sectionmods = explode(",", $section->sequence);
                foreach ($sectionmods as $sectionmod) {
                    $mod = $mods[$sectionmod];
                    if ($mod->visible) {
                        $instance = get_record("$mod->modname", "id", "$mod->instance");
                        $libfile = "$CFG->dirroot/mod/$mod->modname/lib.php";
                        if (file_exists($libfile)) {
                            require_once($libfile);
                            $gradefunction = $mod->modname."_grades";
                            if (function_exists($gradefunction)) {   // Skip modules without grade function
                                if ($modgrades = $gradefunction($mod->instance)) {
                                    if (!empty($modgrades->grades[$userid])) {
                                        if (!empty($modgrades->maxgrade) && ($modgrades->maxgrade > 0) &&
                                            ($modgrades->grades[$userid] > -1)) {
                                            $totalmaxgrade += $modgrades->maxgrade;
                                            $totalgrade += (float)$modgrades->grades[$userid];
                                        }
                                    } else if (!empty($modgrades->maxgrade)) {
                                        $totalmaxgrade += $modgrades->maxgrade;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if ($totalmaxgrade > 0) {
        return (sprintf('%.1f', ($totalgrade/$totalmaxgrade*100.0)).'%');
    } else {
        return '';
    }
}

function FN_translate_group_string($string) {
    global $CFG;
    static $strgroups = null;
    static $strgroup = null;

    if (is_null($strgroups)) {
        $strgroups = get_string('groups');
        $strgroup = get_string('group');
    }

    $string = str_replace($strgroups, $CFG->fnsitegroupnameplural, $string);
    $string = str_replace(strtolower($strgroups), strtolower($CFG->fnsitegroupnameplural), $string);
    $string = str_replace($strgroup, $CFG->fnsitegroupname, $string);
    $string = str_replace(strtolower($strgroup), strtolower($CFG->fnsitegroupname), $string);

    return($string);
}

function FN_translate_course_string($string) {
    global $CFG;
    static $strcourses = null;
    static $strcourse = null;

    if (is_null($strcourses)) {
        $strcourses = get_string('courses');
        $strcourse = get_string('course');
    }

    if (!empty($CFG->fncoursesname)) {
        $string = str_replace($strcourses, $CFG->fncoursesname, $string);
        $string = str_replace(strtolower($strcourses), strtolower($CFG->fncoursesname), $string);
        $string = str_replace($strcourse, $CFG->fncoursename, $string);
        $string = str_replace(strtolower($strcourse), strtolower($CFG->fncoursename), $string);
    }

    return($string);
}

/**
 * Function to return title of 'My Links' block, if it exists.
 */
function FN_get_my_links_title($courseid) {
    global $CFG;

    $bname = 'FN_my_links';
    $deftitle = 'My Links';

    if (empty($courseid)) {
    	return $deftitle;
    }

    $sql = 'SELECT bi.id as id, bi.configdata as configdata '.
           'FROM '.$CFG->prefix.'block b, '.$CFG->prefix.'block_instance bi '.
           'WHERE b.name = \''.$bname.'\' AND bi.blockid = b.id AND bi.pageid = '.$courseid;

    if (!($record = get_record_sql($sql))) {
    	return $deftitle;
    } else {
    	$configdata = unserialize(base64_decode($record->configdata));
        if (!empty($configdata->title)) {
        	return $configdata->title;
        } else {
            return $deftitle;
        }
    }
}

/**
 * Function to return title of 'Teacher Tools' block, if it exists.
 */
function FN_get_teacher_tools_title($courseid) {
    global $CFG;

    $bname = 'FN_teacher_tools';
    $deftitle = 'Teacher Tools';

    if (empty($courseid)) {
        return $deftitle;
    }

    $sql = 'SELECT bi.id as id, bi.configdata as configdata '.
           'FROM '.$CFG->prefix.'block b, '.$CFG->prefix.'block_instance bi '.
           'WHERE b.name = \''.$bname.'\' AND bi.blockid = b.id AND bi.pageid = '.$courseid;

    if (!($record = get_record_sql($sql))) {
        return $deftitle;
    } else {
        $configdata = unserialize(base64_decode($record->configdata));
        if (!empty($configdata->title)) {
            return $configdata->title;
        } else {
            return $deftitle;
        }
    }
}

/**
 * Function used by the site index page to display category specific information.
 */
function fn_display_category_content($course, $catid) {
    global $USER, $CFG;

    $totcount = 99;
    $isteacher = isteacher($course->id);
    $isediting = isediting($course->id);
    $ismoving  = ismoving($course->id);

    if (!($category = get_record('course_categories', 'id', $catid))) {
        error('Invalid category requested.');
    }
    $courses = get_courses_page($catid, 'c.sortorder ASC',
                                'c.id,c.sortorder,c.shortname,c.fullname,c.summary,c.visible,c.teacher,c.guest,c.password',
                                $totcount);

    /// Store a course section per category id. Code it by using the 'catid' plus 10 as the section number.
    $sectnum = $catid + 10;
    if (!($section = get_record('course_sections', 'course', $course->id, 'section', $sectnum))) {
        $section = new stdClass;
        $section->course   = $course->id;
        $section->section  = $sectnum;
        $section->summary  = $category->name;
        $section->sequence = '';
        $section->visible  = 1;
        if (!($section->id = insert_record('course_sections', $section))) {
            error('Could not create section for category '.$category->name);
        }
    }

    if (!empty($section) || $isediting) {
        get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
    }

    $groupbuttons = $course->groupmode;
    $groupbuttonslink = (!$course->groupmodeforce);

    if ($ismoving) {
        $strmovehere = get_string('movehere');
        $strmovefull = strip_tags(get_string('movefull', '', "'$USER->activitycopyname'"));
        $strcancel= get_string('cancel');
        $stractivityclipboard = $USER->activitycopyname;
    }

    $modinfo = unserialize($course->modinfo);
    $editbuttons = '';

    print_simple_box_start("center", "100%", '', 5, "coursebox");

    echo '<table class="topics" width="100%">';
    echo '<tr id="section-'.$section.'" class="section main">';
    echo '<td class="content">';
    print_heading_block('<div align="center">'.$category->name.'</div>');

    echo '<table class="section" width="100%">';
    if (!empty($section) && !empty($section->sequence)) {
        $sectionmods = explode(',', $section->sequence);
        foreach ($sectionmods as $modnumber) {
            if (empty($mods[$modnumber])) {
                continue;
            }
            $mod = $mods[$modnumber];
            if ($isediting && !$ismoving) {
                if ($groupbuttons) {
                    if (! $mod->groupmodelink = $groupbuttonslink) {
                        $mod->groupmode = $course->groupmode;
                    }

                } else {
                    $mod->groupmode = false;
                }
                $editbuttons = '<br />'.make_editing_buttons($mod, true, true);
            } else {
                $editbuttons = '';
            }
            if ($mod->visible || $isteacher) {
                echo '<tr><td class="activity '.$mod->modname.'">';
                if ($ismoving) {
                    if ($mod->id == $USER->activitycopy) {
                        continue;
                    }
                    echo '<a title="'.$strmovefull.'" href="'.$CFG->wwwroot.'/course/mod.php?moveto='.$mod->id.'&amp;sesskey='.$USER->sesskey.'">'.
                         '<img height="16" width="80" src="'.$CFG->pixpath.'/movehere.gif" alt="'.$strmovehere.'" border="0" /></a>';
                }
                $instancename = urldecode($modinfo[$modnumber]->name);
                $instancename = format_string($instancename, true, $course->id);
                $linkcss = $mod->visible ? '' : ' class="dimmed" ';
                if (!empty($modinfo[$modnumber]->extra)) {
                    $extra = urldecode($modinfo[$modnumber]->extra);
                } else {
                    $extra = '';
                }
                if (!empty($modinfo[$modnumber]->icon)) {
                    $icon = $CFG->pixpath.'/'.urldecode($modinfo[$modnumber]->icon);
                } else {
                    $icon = $CFG->modpixpath.'/'.$mod->modname.'/icon.gif';
                }

                if ($mod->modname == 'label') {
                    echo format_text($extra, FORMAT_HTML).$editbuttons;
                } else {
                    echo '<img src="'.$icon.'" height="16" width="16" alt="'.$mod->modfullname.'" /> '.
                         '<a title="'.$mod->modfullname.'" '.$linkcss.' '.$extra.
                         ' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$instancename.'</a>'.$editbuttons;
                }
                echo "</td>";
                echo "</tr>";
            }
        }
    } else {
        echo "<tr><td></td></tr>"; // needed for XHTML compatibility
    }

    if ($ismoving) {
        echo '<tr><td><a title="'.$strmovefull.'" href="'.$CFG->wwwroot.'/course/mod.php?movetosection='.$section->id.'&amp;sesskey='.$USER->sesskey.'">'.
             '<img height="16" width="80" src="'.$CFG->pixpath.'/movehere.gif" alt="'.$strmovehere.'" border="0" /></a></td></tr>';
    }

    if ($isediting && $modnames) {
        echo '<tr><td>';
        print_section_add_menus($course, $section->section, $modnames, true);
        echo '</td></tr>';
    }
    echo "</table>\n\n";
    echo '</td></tr></table>';
    print_simple_box_end();

    if (empty($courses)) {
        print_heading(FN_translate_course_string(get_string("nocoursesyet")));
    } else {
        foreach ($courses as $course) {
            print_course($course);
        }
    }
}
?>