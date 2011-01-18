<?php

/**
 * Advanced search
 * @copyright &copy; May 2010 The Open University
 * @author Mahmoud Kassaei m.kassaei@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package forumNG
 */

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once('forum.php');

define('FORUMNG_SEARCH_RESULTSPERPAGE', 10); // Number of results to display per page

class advancedsearch_form extends moodleform {
    function definition() {
        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('header', 'heading', get_string('moresearchoptions', 'forumng'));

        $mform->addElement('hidden', 'course', $this->_customdata['course']);
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->addElement('hidden', 'clone', $this->_customdata['cloneid']);

        //words to be searched
        $mform->addElement('text', 'query', get_string('words', 'forumng'), 'size="40"');

        //author name or OUCU to be filtered
        $mform->addElement('text', 'author', get_string('authorname', 'forumng'), 'size="40"');

        // Date range_from to be filtered
        $mform->addElement('date_time_selector', 'daterangefrom', get_string('daterangefrom', 'forumng'), array('optional'=>true, 'step'=>1));

        // Date range_to to be filtered
        $mform->addElement('date_time_selector', 'daterangeto', get_string('daterangeto', 'forumng'), array('optional'=>true, 'step'=>1));

        // Add help buttons
        $mform->setHelpButton('query', array('words', get_string('words', 'forumng'), 'forumng'));
        $mform->setHelpButton('author', array('authorname', get_string('authorname', 'forumng'), 'forumng'));
        $mform->setHelpButton('daterangefrom', array('daterangefrom', get_string('daterangefrom', 'forumng'), 'forumng'));
        $mform->setHelpButton('daterangeto', array('daterangeto', get_string('daterangeto', 'forumng'), 'forumng'));

        //Set default hour and minute for "Date ranfe from" and "date range to"
        $mform->addElement('static', 'sethourandminute', '',
        '<script type="text/javascript">
//<![CDATA[
        //check whether "Date range from" and/or "Date range to" are disabled
        var datefrom = false;
        var dateto = false;
        var inputs = document.getElementsByTagName("input");
        for (var i = 0; i < inputs.length; i++) {
            if(inputs[i].type == "checkbox") {
                if (inputs[i].checked == true) {
                    if (inputs[i].name == "daterangefrom[off]") {
                        datefrom = true;
                    }
                    if (inputs[i].name == "daterangeto[off]") {
                        dateto = true;
                    }
                }
            }
        }
        //Set hour and minute of "Date range from" and "Date range to"
        var sel = document.getElementsByTagName("select");
        for(var i = 0; i < sel.length; i++) {
            if (datefrom == true) {
                if (sel[i].name == "daterangefrom[hour]") {
                    sel[i].options[0].selected = true;
                }
                if (sel[i].name == "daterangefrom[minute]") {
                    sel[i].options[0].selected = true;
                }
            }
            if (dateto == true) {
                if (sel[i].name == "daterangeto[hour]") {
                    sel[i].options[23].selected = true;
                }
                if (sel[i].name == "daterangeto[minute]") {
                    sel[i].options[59].selected = true;
                }
            }
        }
//]]>
        </script>');

        // Add "Search all forums"/"Search this forum" and "Cancel" buttons
        if ($this->_customdata['course']) {
            $this->add_action_buttons(true, get_string('searchallforums', 'forumng'));
        } else {
            $this->add_action_buttons(true, get_string('searchthisforum', 'forumng'));
        }
    }

    public function validation($data, $files){
        $errors = parent::validation($data, $files);
        if ($data['daterangefrom'] > time()) {
            $errors['daterangefrom'] = get_string('inappropriatedateortime', 'forumng');
        }
        if (($data['daterangefrom'] > $data['daterangeto']) && $data['daterangeto'] != 0) {
            $errors['daterangeto'] = get_string('daterangemismatch', 'forumng');
        }
        if (($data['query'] === '') && ($data['author'] === '') &&
            !$data['daterangefrom'] && !$data['daterangeto']) {
            $errors['sethourandminute'] = get_string('nosearchcriteria', 'forumng');
        }
        return $errors;
    }
}
///////////////////////////////////////////////////////////////////////////////

$courseid = optional_param('course', 0,  PARAM_INT);
$cmid = 0;
if (!$courseid) {
    $cmid = required_param('id', PARAM_INT);
}
$query = stripslashes(trim(optional_param('query', null, PARAM_RAW)));
$author = stripslashes(trim(optional_param('author', null, PARAM_RAW)));
$daterangefrom = optional_param('datefrom', 0, PARAM_INT);
$daterangeto = optional_param('dateto', 0, PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    // Search in a single forum
    if ($cmid) {
        $forum = forum::get_from_cmid($cmid, $cloneid);
        $cm = $forum->get_course_module();
        $course = $forum->get_course();
        $forum->require_view(forum::NO_GROUPS, 0, true);
        forum::search_installed();
        $allforums = false;
    }
    if ($courseid) {
        $course = forum_utils::get_record('course', 'id', $courseid);
        require_login($course);
        $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
        forum::search_installed();
        $allforums = true;
    }

    //display the form
    $editform = new advancedsearch_form('advancedsearch.php',
                array('course'=> $courseid,'id'=> $cmid, 'cloneid' => $cloneid), 'get');

    $inputdata = new stdClass;
    $inputdata->query = $query;
    $inputdata->author = $author;
    $inputdata->daterangefrom = $daterangefrom;
    $inputdata->daterangeto = $daterangeto;
    $editform->set_data($inputdata);

    $data = $editform->get_data();

    if ($editform->is_cancelled()){
        if (isset($forum) ) {
            $returnurl = $forum->get_url(forum::PARAM_PLAIN);
        } else {
            $returnurl = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        }
        redirect($returnurl, '', 0);
    }

    if ($data) {
        $query = trim($data->query);
        $author = trim($data->author);
        $daterangefrom = $data->daterangefrom;
        $daterangeto = $data->daterangeto;
    }
    $action = $query !== '' || $author !== '' || $daterangefrom || $daterangeto;

    // Display header
    $navigation = array();
    $navigation[] = array(
        'name'=>get_string('moresearchoptions', 'forumng', ''),'type'=>'forumng');
    if ($allforums) {
        print_header_simple('', '', build_navigation($navigation));
    } else {
        print_header_simple(format_string($forum->get_name()), '',
            build_navigation($navigation, $cm), '', '', true, navmenu($course, $cm));
    }

    // Set the search results title, URL and URL options
    $urlrequired = $allforums ? "course=$courseid" : $forum->get_link_params(forum::PARAM_PLAIN);
    $url = $CFG->wwwroot. "/mod/forumng/advancedsearch.php?" . $urlrequired;

    $searchtitle = forumng_get_search_results_title(stripslashes($query), 
        stripslashes($author), $daterangefrom, $daterangeto);

    $urloptions = ($query) ? '&query=' . rawurlencode($query) : '';
    $urloptions = ($author) ? '&author=' . rawurlencode($author) : '';
    $urloptions .= ($daterangefrom) ? '&datefrom=' . $daterangefrom : '';
    $urloptions .= ($daterangeto) ? '&dateto=' . $daterangeto : '';

    if (!$allforums) {
        // Display group selector if required
        groups_print_activity_menu($cm, $url . $urloptions);
        $groupid = forum::get_activity_group($cm, true);
        $forum->require_view($groupid, 0, true);
        print '<br/><br/>';
    }
    $editform->display();

    // Searching for free text with or without filtering author and date range
    if ($query) {
        $result = new ousearch_search(stripslashes($query));
        // Search all forums
        if ($allforums) {
            $result->set_plugin('mod/forumng');
            $result->set_course_id($courseid);
            $result->set_visible_modules_in_course($COURSE);

            // Restrict them to the groups they belong to
            if (!isset($USER->groupmember[$courseid])) {
                $result->set_group_ids(array());
            } else {
                $result->set_group_ids($USER->groupmember[$courseid]);
            }
            // Add exceptions where they can see other groups
            $result->set_group_exceptions(ousearch_get_group_exceptions($courseid));

            $result->set_user_id($USER->id);
        }else {// Search this forum
            $result->set_coursemodule($forum->get_course_module(true));
            if ($groupid && $groupid!=forum::NO_GROUPS) {
                $result->set_group_id($groupid);
            }
        }
        $result->set_filter('forumng_exclude_words_filter');
        ousearch_display_results($result, $url . $urloptions, $searchtitle);

    // Searching without free text uding author and/or date range
    } elseif ($action) {
        $page = optional_param('page', 0, PARAM_INT);
        $prevpage = $page-FORUMNG_SEARCH_RESULTSPERPAGE;
        $prevrange = ($page-FORUMNG_SEARCH_RESULTSPERPAGE+1) . ' - ' . $page;

        //Get result from db
        if ($allforums) {
            $results = forumng_get_results_for_all_forums($course, $author, $daterangefrom, $daterangeto, $page);
        } else {
            $results = forumng_get_results_for_this_forum($forum, $groupid, $author, $daterangefrom, $daterangeto, $page);
        }
        $nextpage = $page + FORUMNG_SEARCH_RESULTSPERPAGE;

        $linknext = null;
        $linkprev = null;

        if ($results->success) {
            if (($page-FORUMNG_SEARCH_RESULTSPERPAGE+1)>0) {
                $linkprev = $url."&action=1&page=$prevpage".$urloptions;
            }
            if ($results->numberofentries == FORUMNG_SEARCH_RESULTSPERPAGE) {
                $linknext = $url."&action=1&page=$nextpage".$urloptions;
            }
        }
        if ($results->done ===1) {
            if (($page-FORUMNG_SEARCH_RESULTSPERPAGE+1)>0) {
                $linkprev = $url."&action=1&page=$prevpage".$urloptions;
            }
        }
        print ousearch_format_results($results, $searchtitle, $page+1, $linkprev, 
                        $prevrange, $linknext, $results->searchtime);
    }
} catch (forum_exception $e) {
    forum_utils::handle_exception($e);
}

print_footer($course);

////////////////////////////////////////////////////////////////////////////////
/**
 * Filter search result.
 * @param object $result
 * @return boolean
 */
function forumng_exclude_words_filter($result) {
    $author     = trim(optional_param('author', null, PARAM_RAW));
    $drfa  = optional_param('daterangefrom', 0, PARAM_INT);
    $drta  = optional_param('daterangeto', 0, PARAM_INT);

    // Filter the output based on the input string for "Author name" field
    if (!forumng_find_this_user($result->intref1, $author)) {
        return false;
    }

    // Filter the output based on input date for "Date range from" field
    if (count($drfa) > 1 ) {
        $daterangefrom = make_timestamp($drfa['year'], $drfa['month'], $drfa['day'],
                                    $drfa['hour'], $drfa['minute'], 0);
        if ($daterangefrom && $daterangefrom > $result->timemodified) {
            return false;
        }
    }

    // Filter the output based on input date for "Date range to" field
    if (count($drta) > 1 ) {
        $daterangeto = make_timestamp($drta['year'], $drta['month'], $drta['day'],
                                    $drta['hour'], $drta['minute'], 0);
        if ($daterangeto && $daterangeto < $result->timemodified) {
            return false;
        }
    }
    return true;
}


/**
 * Get search results.
 * @param object $forum
 * @param int $groupid
 * @param string $author
 * @param int $daterangefrom
 * @param int $daterangeto
 * @param int $page
 * @param int $resultsperpage (FORUMNG_SEARCH_RESULTSPERPAGE used as constant)
 * @return object
 */
function forumng_get_results_for_this_forum($forum, $groupid, $author=null, $daterangefrom=0, $daterangeto=0,
        $page, $resultsperpage=FORUMNG_SEARCH_RESULTSPERPAGE) {

    $before=microtime(true);

    global $CFG, $USER;
    $forumid = $forum->get_id();
    $context = $forum->get_context();

    $where = "WHERE d.forumid=$forumid";

    //exclude deleted discussion/post
    $where .= " AND d.deleted=0 AND p.deleted=0 AND p.oldversion=0 ";

    if ($author) {
        $where .= forumng_get_author_sql($author);
    }
    if ($daterangefrom && !is_array($daterangefrom)) {
        $where .= " AND p.modified>=$daterangefrom";
    }
    if ($daterangeto && !is_array($daterangeto)) {
        $where .= " AND p.modified<=$daterangeto";
    }

    $sql = "SELECT p.modified, p.id, p.discussionid, p.userid, p.parentpostid, 
            p.subject AS title, p.message AS summary, u.username, u.firstname, 
            u.lastname, p2.subject 
            FROM {$CFG->prefix}forumng_posts p
            INNER JOIN {$CFG->prefix}forumng_discussions d ON d.id = p.discussionid
            INNER JOIN {$CFG->prefix}user u ON p.userid = u.id
            INNER JOIN {$CFG->prefix}forumng_posts p2 ON p2.id = d.postid
            $where
            ORDER BY p.modified DESC, p.id ASC";

    $results = new stdClass;
    $results->success = 1;
    $results->numberofentries = 0;
    $results->done = 0;
    if (!$posts = get_records_sql($sql, $page, $resultsperpage)) {
        $posts = array();
    }
    $groupposts = array();
    foreach ($posts as $post) {
        if (!$post->title) {
            //Add Re: if the post doesn't have a subject
            $post->title = get_string('re', 'forumng', $post->subject);
        }
        $post->title = s(strip_tags($post->title));
        $post->summary = s(strip_tags(shorten_text($post->summary, 250)));
        $post->url = $CFG->wwwroot ."/mod/forumng/discuss.php?d=$post->discussionid" .
                $forum->get_clone_param(forum::PARAM_PLAIN) . "#p$post->id";

        // Check group
        if ($groupid && $groupid!=forum::NO_GROUPS) {
            if (groups_is_member($groupid, $post->userid)) {
                $groupposts[] = $post;
            }
        }
    }
    $results->results = $groupposts ? $groupposts : $posts;
    $results->searchtime = microtime(true) - $before;
    $results->numberofentries = count($results->results);

    if (count($results->results) < $resultsperpage) {
        $results->done = 1;
    } elseif(!$extrapost = get_records_sql($sql, $page+$resultsperpage, 1)) {
        $results->done = 1;
    }
    return $results;
}


/**
 * Get search results.
 * @param object $course
 * @param string $author
 * @param int $daterangefrom
 * @param int $daterangeto
 * @param int $page
 * @param int $resultsperpage (FORUMNG_SEARCH_RESULTSPERPAGE used as constant)
 * @return object
 */
function forumng_get_results_for_all_forums($course, $author=null, $daterangefrom=0, $daterangeto=0,
        $page, $resultsperpage=FORUMNG_SEARCH_RESULTSPERPAGE) {

    $before=microtime(true);

    global $CFG, $USER;

    // Get all forums
    $modinfo = get_fast_modinfo($course);
    $visibleforums = array();
    $accessallgroups = array();
    foreach ($modinfo->cms as $cmid=>$cm) {
        if ($cm->modname === 'forumng' && $cm->uservisible) {
            $visibleforums[$cm->instance] = $cm->groupmode;

            // Check access all groups for this forum, if they have it, add to list
            //$forum = forum::get_from_cmid($cm->id, 0);
            $forum = forum::get_from_id($cm->instance, 0);
            if ($forum->get_group_mode() == SEPARATEGROUPS) {
                if (has_capability('moodle/site:accessallgroups',$forum->get_context())) {
                    $accessallgroups[] = $cm->instance;
                }
            }
        }
    }
    $forumids = array_keys($visibleforums);
    $separategroupsforumids = array_keys($visibleforums, SEPARATEGROUPS);

    $inforumids = forum_utils::in_or_equals($forumids);
    $inseparategroups = forum_utils::in_or_equals($separategroupsforumids);
    $inaccessallgroups = forum_utils::in_or_equals($accessallgroups);
    $where = "WHERE d.forumid $inforumids";
    $where .= " AND (NOT (d.forumid $inseparategroups)";
    $where .= " OR d.forumid $inaccessallgroups";
    $where .= " OR gm.id IS NOT NULL";
    $where .= " OR d.groupid IS NULL)";

    // Note: Even if you have capability to view the deleted or timed posts,
    // we don't show them for consistency with the full-text search.
    $currenttime = time();
    $where .= " AND ($currenttime >= d.timestart OR d.timestart = 0)";
    $where .= " AND ($currenttime < d.timeend OR d.timeend = 0)";

    //exclude older post versions
    $where .= " AND p.oldversion=0 ";
    $where .= " AND d.deleted=0 AND p.deleted=0 ";

    if ($author) {
        $where .= forumng_get_author_sql($author);
    }
    if ($daterangefrom && !is_array($daterangefrom)) {
        $where .= " AND p.modified>=$daterangefrom";
    }
    if ($daterangeto && !is_array($daterangeto)) {
        $where .= " AND p.modified<=$daterangeto";
    }

    $sql = "SELECT p.modified, p.id, p.discussionid, gm.id AS useringroup, p.userid, p.parentpostid, 
            p.subject AS title, p.message AS summary, u.username, u.firstname, 
            u.lastname, d.forumid, d.groupid, d.postid AS discussionpostid
            FROM {$CFG->prefix}forumng_posts p
            INNER JOIN {$CFG->prefix}forumng_discussions d ON d.id = p.discussionid
            INNER JOIN {$CFG->prefix}user u ON p.userid = u.id
            LEFT JOIN {$CFG->prefix}groups_members gm ON gm.groupid = d.groupid AND gm.userid = $USER->id
            $where
            ORDER BY p.modified DESC, p.id ASC";

    $results = new stdClass;
    $results->success = 1;
    $results->numberofentries = 0;
    $results->done = 0;
    if (!$posts = get_records_sql($sql, $page, $resultsperpage)) {
        $posts = array();
    }
    foreach ($posts as $post) {
        if (!$post->title) {
            // Ideally we would get the parent post that has a subject, but
            // this could involve a while loop that might make numeroous
            // queries, so instead, let's just use the discussion subject
            $post->title = get_string('re', 'forumng',
                    get_field('forumng_posts', 'subject', 'id', $post->discussionpostid));
        }
        $post->title = s(strip_tags($post->title));
        $post->summary = s(strip_tags(shorten_text($post->summary, 250)));
        $post->url = $CFG->wwwroot . "/mod/forumng/discuss.php?d=$post->discussionid#p$post->id";
    }
    $results->results = $posts;
    $results->searchtime = microtime(true) - $before;
    $results->numberofentries = count($results->results);
    if (count($results->results) < $resultsperpage) {
        $results->done = 1;
    } elseif(!$extrapost = get_records_sql($sql, $page+$resultsperpage, 1)) {
        $results->done = 1;
    }
    return $results;
}


/**
 * Find this usr.
 * @param int $groupid
 * @param string $author
 * @return boolean
 */
function forumng_find_this_user($postid, $author=null) {
    global $CFG;
    require_once($CFG->libdir . '/dmllib.php');
    if (!$author) {
        return true;
    }
    $where = "WHERE p.id=$postid ";
    $where .= forumng_get_author_sql($author);
    $sql = "SELECT p.id, u.username, u.firstname, u.lastname
            FROM {$CFG->prefix}forumng_posts p
            INNER JOIN {$CFG->prefix}user u ON p.userid = u.id
            $where";
    if ($posts = get_record_sql($sql)) {
        return true;
    }
    return false;
}


/**
 * Get author sql
 * @param string $author
 * @param string $t
 * @return string
 */
function forumng_get_author_sql($author, $t='u'){
    $where = " AND ";
    $author= trim($author);
    $pos = strpos($author, ' ');
    if ($pos) {
        $fname = trim(substr($author, 0, $pos));
        $lname = trim(substr($author, ($pos+1)));
        // Searching for complete first name and last name fully or partially ignoring case.
        // Finds "Mahmoud Kassaei" by typing "Mahmoud k", "Mahmoud kas", "Mahmoud Kassaei", etc.
        $where .= " (UPPER($t.firstname) LIKE UPPER('$fname') AND UPPER($t.lastname) LIKE UPPER('$lname%'))";
    } else {
        // Searching for user name fully ignoring case
        // Finds "mk4359",  "Mk4359""MK4359", etc.
        $where .= "((UPPER($t.username)=UPPER('$author')) ";

        //search for first name only
        // Finds "Mah",  "Mahmo", "mahmoud", etc.
        $where .= " OR (UPPER($t.firstname) LIKE UPPER('$author%')) ";

        //search for surname only
        // Finds "Kass",  "kassa", "Kassaei", etc.
        $where .= " OR (UPPER($t.lastname) LIKE UPPER('$author%'))) ";
    }
    return $where;
}


/**
 * Get search results title
 * @param string $query
 * @param string $author
 * @param int $daterangefrom
 * @param int $daterangeto
 * @return string
 */
function forumng_get_search_results_title($query='', $author='', $daterangefrom=0, $daterangeto=0) {
    // Set the search results title
    if ($query) {
        if (!($author || $daterangefrom || $daterangeto)) {
            return get_string('searchresultsfor','block_ousearch', $query);
        }
    }
    $searchoptions = $query ? $query . ' (' : ' (';
    $searchoptions .= $author ? get_string('author', 'forumng', $author): '';
    $searchoptions .= ($author && ($daterangefrom || $daterangeto)) ? ', ' : '';
    $searchoptions .= $daterangefrom ? get_string('from', 'forumng', userdate($daterangefrom)) : '';
    $searchoptions .= ($daterangefrom && $daterangeto) ? ', ' : '';
    $searchoptions .= $daterangeto ? get_string('to', 'forumng', userdate($daterangeto)) : '';
    $searchoptions .= ' )';
    if ($query) {
        return get_string('searchresultsfor','block_ousearch', $searchoptions);
    }
    return get_string('searchresults','forumng', $searchoptions);
}


?>
