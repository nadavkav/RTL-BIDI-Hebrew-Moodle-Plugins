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

        $mform->addElement('hidden', 'id', $this->_customdata['id']);

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

        // Add "Search this forum" and "Cancel" buttons
        $this->add_action_buttons(true, get_string('searchthisforum', 'forumng'));
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

$cmid           = required_param('id', PARAM_INT);
$query          = stripslashes(trim(optional_param('query', null, PARAM_RAW)));
$author         = stripslashes(trim(optional_param('author', null, PARAM_RAW)));
$daterangefrom  = optional_param('datefrom', 0, PARAM_INT);
$daterangeto    = optional_param('dateto', 0, PARAM_INT);

$forum = forum::get_from_cmid($cmid);
$cm = $forum->get_course_module();
$course = $forum->get_course();
$forum->require_view(forum::NO_GROUPS, 0, true);
forum::search_installed();


//display the form
$editform = new advancedsearch_form('advancedsearch.php',
            array('id'=>isset($cmid) ? $cmid : null), 'get');

$inputdata = new stdClass;
$inputdata->query = $query;
$inputdata->author = stripslashes($author);
$inputdata->daterangefrom = $daterangefrom;
$inputdata->daterangeto = $daterangeto;
$editform->set_data($inputdata);

$data = $editform->get_data();

if ($editform->is_cancelled()){
    $returnurl = $CFG->wwwroot.'/mod/forumng/view.php?id='.$cmid;
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
print_header_simple(format_string($forum->get_name()), '',
    build_navigation($navigation, $cm), '', '', true, navmenu($course, $cm));

// Try to get the search results
try {
    // Set the search results title, URL and URL options
    $url=$CFG->wwwroot. "/mod/forumng/advancedsearch.php?id=$cmid";
    $searchtitle = forumng_get_search_results_title($query, $author, $daterangefrom, $daterangeto);
    $urloptions = ($query) ? '&amp;query=' . rawurlencode($query) : '';
    $urloptions = ($author) ? '&amp;author=' . rawurlencode($author) : '';
    $urloptions .= ($daterangefrom) ? '&amp;datefrom=' . $daterangefrom : '';
    $urloptions .= ($daterangeto) ? '&amp;dateto=' . $daterangeto : '';
    $searchurl = 'advancedsearch.php?id=' . $cmid . $urloptions;

    // Display group selector if required
    groups_print_activity_menu($cm, $url . $urloptions);
    $groupid = forum::get_activity_group($cm, true);
    $forum->require_view($groupid, 0, true);
    print '<br/><br/>';

    $editform->display();

    // Searching for free text with or without filtering author and date range
    if ($query) {
        $result = new ousearch_search($query);
        $result->set_coursemodule($cm);
        if ($groupid && $groupid!=forum::NO_GROUPS) {
            $result->set_group_id($groupid);
        }
        $result->set_filter('forumng_exclude_words_filter');
        ousearch_display_results($result, $url . $urloptions, $searchtitle);

    // Searching without free text uding author and/or date range
    } elseif ($action) {
        $page = optional_param('page', 0, PARAM_INT);
        $prevpage = $page-FORUMNG_SEARCH_RESULTSPERPAGE;
        $prevrange = ($page-FORUMNG_SEARCH_RESULTSPERPAGE+1) . ' - ' . $page;

        //Get result from db
        $results = forumng_get_search_results($forum, $groupid, $author, $daterangefrom, $daterangeto, ($page));
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
 * @return object
 */
function forumng_get_search_results($forum, $groupid, $author=null, $daterangefrom=0, $daterangeto=0,
        $page, $resultsperpage=FORUMNG_SEARCH_RESULTSPERPAGE) {
    if(debugging()) {
        $before=microtime(true);
    }
    global $CFG, $USER;
    require_once($CFG->libdir . '/dmllib.php');
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
    $userids = array();
    $sql = "SELECT p.modified, p.id, p.discussionid, p.userid, p.parentpostid, 
            p.subject AS title, p.message AS summary, u.username, u.firstname, 
            u.lastname
            FROM {$CFG->prefix}forumng_posts p
            INNER JOIN {$CFG->prefix}forumng_discussions d ON d.id = p.discussionid
            INNER JOIN {$CFG->prefix}user u ON p.userid = u.id
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
            $post->title = get_field('forumng_posts', 'subject', 'id', $post->parentpostid);
        }
        $post->title = strip_tags($post->title);
        $post->summary = strip_tags(shorten_text($post->summary, 250));
        $post->url = $CFG->wwwroot ."/mod/forumng/discuss.php?d=$post->discussionid#p$post->id";

        // Check group
        if ($groupid && $groupid!=forum::NO_GROUPS) {
            if (groups_is_member($groupid, $post->userid)) {
                $groupposts[] = $post;
            }
        }
    }
    if(debugging()) {
        $searchtime = microtime(true) - $before;
    } else {
        $searchtime = null;
    }
    $results->results = $groupposts ? $groupposts : $posts;
    $results->searchtime = $searchtime;
    $results->numberofentries = count($results->results);

    if (count($results->results) < $resultsperpage) {
        $results->done = 1;
    } elseif(!$extrapost = get_records_sql($sql, $page+$resultsperpage, 1)) {
        $results->done=1;
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
        // Searching for first name and last name fully or partially ignoring case
        // Finds "Mahmoud Kassaei" by typing "moud kass", "ahmoud kassai", "oud Kas", etc.
        $where .= " ((UPPER($t.firstname) || ' ' || UPPER($t.lastname)) LIKE UPPER('%$author%') ";

        // Searching for first name and last name fully or partially ignoring case.
        // Finds "Mahmoud Kassaei" by typing "Mahmo kass", "Ma kassai", "M Kassaei", etc.
        $where .= " OR (UPPER($t.firstname) LIKE UPPER('$fname%') AND UPPER($t.lastname) LIKE UPPER('$lname%')))";
    } else {
        // Searching for user anem fully ignoring case
        // Finds "mk4359",  "Mk4359""MK4359", etc.
        $where .= "((UPPER($t.username)=UPPER('$author')) ";

        //search for first name only
        // Finds "Mah",  "Mahmo""mahmoud", etc.
        $where .= " OR (UPPER($t.firstname) LIKE UPPER('$author%')) ";

        //search for surname only
        // Finds "Kass",  "kassa""Kassae", etc.
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
    $searchoptions .= $author ? get_string('author', 'forumng', stripslashes($author)): '';
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
