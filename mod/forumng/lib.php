<?php

function forumng_add_instance($forumng) {
    // Avoid including forum libraries in large areas of Moodle code that
    // require this lib.php; only include when functions are called
    require_once(dirname(__FILE__).'/forum.php');

    $useshared = !empty($forumng->usesharedgroup['useshared']);
    if ($useshared) {
        $idnumber = $forumng->usesharedgroup['originalcmidnumber'];
        if (!($originalcm = get_record('course_modules', 'idnumber', $idnumber,
            'module', get_field('modules', 'id', 'name', 'forumng')))) {
            return false;
        }
        if (!($originalforumng = get_record('forumng', 'id', $originalcm->instance))) {
            return false;
        }

        // Create appropriate data for forumng table
        $forumng = (object)array(
            'name' => addslashes($originalforumng->name),
            'course' => $forumng->course,
            'type' => 'clone',
            'originalcmid' => $originalcm->id);
    }

    // Pick a random magic number
    $part1 = mt_rand(0, 99999999);
    $part2 = mt_rand(0, 99999999);
    while(strlen($part2)<8) {
        $part2 = '0'.$part2;
    }
    $forumng->magicnumber = $part1.$part2;

    if(!($id=insert_record('forumng', $forumng))) {
        return false;
    }

    // Handle post-creation actions (but only if a new forum actually was
    // created, and not just a new reference to a shared one!)
    if (!$useshared) {
        $forum=forum::get_from_id($id, forum::CLONE_DIRECT, false);
        $forum->created($forumng->cmidnumber);
    }

    return $id;
}

function forumng_update_instance($forumng) {
    require_once(dirname(__FILE__).'/forum.php');

    $forumng->id = $forumng->instance;

    $previous = get_record('forumng','id',$forumng->id);

    if (class_exists('ouflags')
        && (has_capability('local/course:revisioneditor', get_context_instance(CONTEXT_COURSE, $forumng->course), null, false))) {
        global $CFG;
        //  handle insitu editing updates
        include_once($CFG->dirroot.'/local/insitu/lib.php');
        oci_mod_make_backup_and_save_instance($forumng);
    } else {
        if (!update_record('forumng', $forumng)) {
            return false;
        }
    }

    try {
        $forum=forum::get_from_id($forumng->id, forum::CLONE_DIRECT);
        $forum->updated($previous);
    } catch(Exception $e) {
        return false;
    }

    return true;
}

// extra parameter to control skipping for OU OCI upload
function forumng_delete_instance($id, $ociskip=true) {
    require_once(dirname(__FILE__).'/forum.php');

    try {
        $forum = forum::get_from_id($id, forum::CLONE_DIRECT);
        // avoid deleting OCI specific forum if running in upload block
        if ($ociskip) {
            global $restore;
            if (isset($restore) && $restore->restoreto==0 && strpos($_SERVER['HTTP_REFERER'],'blocks/versions/upload.php')!==false) {
                if ($forum->get_name()==get_string('newunitforumname', 'createcourse')) { //Unit forum
                    echo ' found forumng '.$forum->get_id().' '.$forum->get_name();
                    return true;
                }
            }
        }
        $forum->delete_all_data();
        if(forum::search_installed()) {
            $cm = $forum->get_course_module();
            ousearch_document::delete_module_instance_data($cm);
        }
    } catch(Exception $e) {
        return false;
    }

    return delete_records('forumng','id',$id);
}

function forumng_cron() {
    require_once(dirname(__FILE__).'/forum_cron.php');

    try {
        forum_cron::cron();
    } catch(forum_exception $e) {
        mtrace("A forum exception occurred and forum cron was aborted: " .
            $e->getMessage() . "\n\n" .
            $e->getTraceAsString()."\n\n");
    }
}


/**
 * Obtains a search document given the ousearch parameters.
 * @param object $document Object containing fields from the ousearch documents table
 * @return mixed False if object can't be found, otherwise object containing the following
 *   fields: ->content, ->title, ->url, ->activityname, ->activityurl,
 *   and optionally ->extrastrings array and ->data
 */
function forumng_ousearch_get_document($document) {
    require_once(dirname(__FILE__).'/forum.php');
    return forum_post::search_get_page($document);
}

/**
 * Update all documents for ousearch.
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function forumng_ousearch_update_all($feedback=false, $courseid=0) {
    require_once(dirname(__FILE__).'/forum.php');

    forum::search_update_all($feedback, $courseid);
}

/**
 * Returns all other caps used in module
 */
function forumng_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames', 'moodle/site:trustcontent');
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 */
function forumng_get_coursemodule_info($coursemodule) {
    if ($forumng = get_record('forumng', 'id', $coursemodule->instance, '', '', '', '', 'id, name, type')) {
        $info = new object();
//        $info->name = $forumng->name;
        $info->extra = 'class="forumng-type-' . $forumng->type . '"';
        return $info;
    }
}

/**
 * Create html fragment for display on myMoodle page, forums changed since
 * user last visited
 *
 * @param $courses list of courses to output information from
 * @param $htmlarray returned results appended html to display
 */
function forumng_print_overview($courses,&$htmlarray) {
    global $USER, $CFG;
    require_once($CFG->dirroot . '/mod/forumng/forum.php');

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    $strnumunread = get_string('discussionsunread','forumng');
    $strforum = get_string('modulename','forumng');

    foreach($courses as $course) {
        $str = "";
        $forums = forum::get_course_forums($course, $USER->id,forum::UNREAD_DISCUSSIONS);
        if (!empty($forums)) {
            foreach($forums as $forum) {
                // note like all mymoodle, there's no check current user can see each forum
                // ok for openlearn & vital but might need addressing if VLE ever use it
                if ($forum->has_unread_discussions()) { // only listing unread, not new & unread for performance
                    $str .= '<div class="overview forumng"><div class="name">' .
                        $strforum . ':' . ' <a title="' . $strforum . '" href="' .
                        $forum->get_url(forum::PARAM_HTML).'">' .
                        $forum->get_name() . '</a></div>';
                    $str .= '<div class="info">'.$forum->get_num_unread_discussions(). ' '.$strnumunread.'</div></div>';
                }

            }
        }

        if (!empty($str)) {
            if (!array_key_exists($course->id,$htmlarray)) {
                $htmlarray[$course->id] = array();
            }
            if (!array_key_exists('forumng',$htmlarray[$course->id])) {
                $htmlarray[$course->id]['forumng'] = ''; // initialize, avoid warnings
            }
            $htmlarray[$course->id]['forumng'] .= $str;
        }
    }
}

/**
 * Indicates API features that the forum supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function forumng_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_GRADE_HAS_GRADE: return true;
        default: return false;
    }
}

/**
 * Obtains the automatic completion state for this forum based on any conditions
 * in forum settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function forumng_get_completion_state($course, $cm, $userid, $type) {
    // Use forum object to handle this request
    $forum = forum::get_from_cmid($cm->id, forum::CLONE_DIRECT);
    return $forum->get_completion_state($userid, $type);
}

/**
 * Used by course/user.php to display this module's user activity outline.
 * @param object $course as this is a standard function this is required but not used here
 * @param object $user Moodle user ob
 * @param object $mod not used here
 * @param object $forum Moodle forumng object
 * @return object A standard object with 2 variables: info (number of posts for this user) and time (last modified)
 */
function forumng_user_outline($course, $user, $mod, $forum) {
    require_once(dirname(__FILE__).'/forum.php');
    if ($posts = forum::get_user_activityreport($forum->id, $user->id)) {
        $result = new object();
        $result->info = get_string("numposts", "forumng", $posts->postcount);
        $result->time = $posts->lastpost;
        return $result;
    } else {
        return NULL;
    }
}

?>
