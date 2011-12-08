<?php
/**
 * Library of functions for the oublog module.
 *
 * This contains functions that are called also from outside the oublog module
 * Functions that are only called by the quiz module itself are in {@link locallib.php}
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package oublog
 */




/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $oublog the data from the mod form
 * @return int The id od the newly inserted module
 */
function oublog_add_instance($oublog) {

    // Generate an accesstoken
    $oublog->accesstoken = md5(uniqid(rand(), true));

    if (!$oublog->id = insert_record('oublog', $oublog)) {
        return(false);
    }

    return($oublog->id);
}



/**
 * Given an object containing all the necessary data,(defined by the
 * form in mod_form.php) this function will update an existing instance
 * with new data.
 *
 * @param object $oublog the data from the mod form
 * @return boolean true on success, false on failure.
 */
function oublog_update_instance($oublog) {

    $oublog->id = $oublog->instance;

    if (!$blog = get_record('oublog', 'id', $oublog->id)) {
        return(false);
    }

    if (!update_record('oublog', $oublog)) {
        return(false);
    }

    return(true);
}



/**
 * Given an ID of an instance of this module, this function will
 * permanently delete the instance and any data that depends on it.
 *
 * @param int $id The ID of the module instance
 * @return boolena true on success, false on failure.
 */
function oublog_delete_instance($oublogid) {

    if (!$oublog = get_record('oublog', 'id', $oublogid)) {
        return(false);
    }

    if ($oublog->global) {
        error('You can\'t delete the global blog');
    }

    if ($instances = get_records('oublog_instances', 'oublogid', $oublog->id)) {

        foreach ($instances as $oubloginstancesid => $bloginstance) {
            // tags
            delete_records('oublog_taginstances', 'oubloginstancesid', $oubloginstancesid);

            if ($posts = get_records('oublog_posts', 'oubloginstancesid', $oubloginstancesid)) {

                foreach($posts as $postid => $post) {
                    // comments
                    delete_records('oublog_comments', 'postid', $postid);

                    // edits
                    delete_records('oublog_edits', 'postid', $postid);
                }

                // posts
                delete_records('oublog_posts', 'oubloginstancesid', $oubloginstancesid);

            }
        }
    }

    // links
    delete_records('oublog_links', 'oublogid', $oublog->id);

    // instances
    delete_records('oublog_instances', 'oublogid', $oublog->id);

    // Fulltext search data
    require_once(dirname(__FILE__).'/locallib.php');
    if(oublog_search_installed()) {
        $moduleid=get_field('modules','id','name','oublog');
        $cm=get_record('course_modules','module',$moduleid,'instance',$oublog->id);
        if(!$cm) {
            error('Can\'t find coursemodule');
        }
        ousearch_document::delete_module_instance_data($cm);
    }

    // oublog
    return(delete_records('oublog', 'id', $oublog->id));

}



/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $oublog
 * @return object containing a time and info properties
 */
function oublog_user_outline($course, $user, $mod, $oublog) {
    global $CFG;

    $sql = "SELECT count(*) AS postcnt, MAX(timeposted) as lastpost
            FROM {$CFG->prefix}oublog_posts p
                INNER JOIN {$CFG->prefix}oublog_instances i ON p.oubloginstancesid = i.id
            WHERE p.deletedby IS NULL AND i.userid = {$user->id} AND oublogid = {$mod->instance}";

    if ($postinfo = get_record_sql($sql)) {
        $result = new stdClass();
        $result->info = get_string('numposts', 'oublog', $postinfo->postcnt);
        $result->time = $postinfo->lastpost;

        return($result);
    }

    return(null);
}



/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $oublog
 * @return object containing a time and info properties
 */
function oublog_user_complete($course, $user, $mod, $oublog) {
    global $CFG;
    include_once('locallib.php');

    $baseurl = $CFG->wwwroot.'/mod/oublog/view.php?id='.$mod->id;

    $sql = "SELECT p.*
            FROM {$CFG->prefix}oublog_posts p
                INNER JOIN {$CFG->prefix}oublog_instances i ON p.oubloginstancesid = i.id
            WHERE p.deletedby IS NULL AND i.userid = {$user->id} AND oublogid = {$mod->instance} ";

    if ($posts = get_records_sql($sql)) {
        foreach($posts as $post) {
            $postdata = oublog_get_post($post->id);
            oublog_print_post($mod, $oublog, $postdata, $baseurl, 'course');
        }
    } else {
        echo get_string('noblogposts', 'oublog');
    }

    return(null);
}



/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in newmodule activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param bool $isteacher
 * @param int $timestart
 * @return boolean true on success, false on failure.
 **/
function oublog_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    include_once('locallib.php');

    $sql = "SELECT i.oublogid, p.id AS postid, p.*, u.firstname, u.lastname, u.email, u.idnumber, i.userid
            FROM {$CFG->prefix}oublog_posts p
                INNER JOIN {$CFG->prefix}oublog_instances i ON p.oubloginstancesid = i.id
                INNER JOIN {$CFG->prefix}oublog b ON i.oublogid = b.id
                INNER JOIN {$CFG->prefix}user u ON i.userid = u.id
            WHERE b.course = {$course->id} AND p.deletedby IS NULL AND p.timeposted >= $timestart ";

    if (!$rs = get_recordset_sql($sql)) {
        return(true);
    }

    $modinfo =& get_fast_modinfo($course);

    $strftimerecent = get_string('strftimerecent');

    print_headline(get_string('newblogposts', 'oublog').':', 3);

    echo "\n<ul class='unlist'>\n";
    while($blog = rs_fetch_next_record($rs)) {
        if (!isset($modinfo->instances['oublog'][$blog->oublogid])) {
            // not visible
            continue;
        }
        $cm = $modinfo->instances['oublog'][$blog->oublogid];
        if (!$cm->uservisible) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_MODULE, $cm->id))) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_USER, $blog->userid))) {
            continue;
        }


        $groupmode = oublog_get_activity_groupmode($cm, $course);

        if ($groupmode) {
            if ($blog->groupid && $groupmode != VISIBLEGROUPS) {
                // separate mode
                if (isguestuser()) {
                    // shortcut
                    continue;
                }

                if (is_null($modinfo->groups)) {
                    $modinfo->groups = groups_get_user_groups($course->id); // load all my groups and cache it in modinfo
                }

                if (!array_key_exists($blog->groupid, $modinfo->groups[0])) {
                    continue;
                }
            }
        }


        echo '<li><div class="head">'.
               '<div class="date">'.oublog_date($blog->timeposted, $strftimerecent).'</div>'.
               '<div class="name">'.fullname($blog).'</div>'.
             '</div>';
        echo '<div class="info">';
        echo "<a href=\"{$CFG->wwwroot}/mod/oublog/viewpost.php?post={$blog->postid}\">";
        echo break_up_long_words(format_string(empty($blog->title) ? $blog->message : $blog->title));
        echo '</a>';
        echo '</div>';
    }
    echo "</ul>\n";
    rs_close($rs);
}



/**
 * Get recent activity for a course
 *
 * @param array $activities
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return bool
 */
function oublog_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0)  {
    global $CFG, $COURSE;


    $sql = "SELECT i.oublogid, p.id AS postid, p.*, u.firstname, u.lastname, u.email, u.idnumber, u.picture, u.imagealt, i.userid
            FROM {$CFG->prefix}oublog_posts p
                INNER JOIN {$CFG->prefix}oublog_instances i ON p.oubloginstancesid = i.id
                INNER JOIN {$CFG->prefix}oublog b ON i.oublogid = b.id
                INNER JOIN {$CFG->prefix}user u ON i.userid = u.id
            WHERE b.course = $courseid AND p.deletedby IS NULL AND p.timeposted >= $timestart ";

    if (!$rs = get_recordset_sql($sql)) {
        return(true);
    }

    $modinfo =& get_fast_modinfo($COURSE);



    while($blog = rs_fetch_next_record($rs)) {
        if (!isset($modinfo->instances['oublog'][$blog->oublogid])) {
            // not visible
            continue;
        }
        $cm = $modinfo->instances['oublog'][$blog->oublogid];
        if (!$cm->uservisible) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_MODULE, $cm->id))) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_USER, $blog->userid))) {
            continue;
        }


        $groupmode = oublog_get_activity_groupmode($cm, $COURSE);

        if ($groupmode) {
            if ($blog->groupid && $groupmode != VISIBLEGROUPS) {
                // separate mode
                if (isguestuser()) {
                    // shortcut
                    continue;
                }

                if (is_null($modinfo->groups)) {
                    $modinfo->groups = groups_get_user_groups($course->id); // load all my groups and cache it in modinfo
                }

                if (!array_key_exists($blog->groupid, $modinfo->groups[0])) {
                    continue;
                }
            }
        }


        $tmpactivity = new object();

        $tmpactivity->type         = 'oublog';
        $tmpactivity->cmid         = $cm->id;
        $tmpactivity->name         = $blog->title;
        $tmpactivity->sectionnum   = $cm->sectionnum;
        $tmpactivity->timeposted    = $blog->timeposted;

        $tmpactivity->content = new object();
        $tmpactivity->content->postid   = $blog->postid;
        $tmpactivity->content->title    = format_string($blog->title);

        $tmpactivity->user = new object();
        $tmpactivity->user->id        = $blog->userid;
        $tmpactivity->user->firstname = $blog->firstname;
        $tmpactivity->user->lastname  = $blog->lastname;
        $tmpactivity->user->picture   = $blog->picture;
        $tmpactivity->user->imagealt  = $blog->imagealt;
        $tmpactivity->user->email     = $blog->email;

        $activities[$index++] = $tmpactivity;
    }

    rs_close($rs);
}


/**
 * Print recent oublog activity for a course
 *
 * @param object $activity
 * @param int $courseid
 * @param bool $detail
 * @param array $modnames
 * @param bool $viewfullnames
 */
function oublog_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $CFG;

    echo '<table border="0" cellpadding="3" cellspacing="0" class=oublog-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    print_user_picture($activity->user, $courseid);
    echo "</td><td>";

    echo '<div class="title">';
    if ($detail) {
        echo "<img src=\"$CFG->modpixpath/$activity->type/icon.gif\" class=\"icon\" alt=\"".s($activity->title)."\" />";
    }
    echo "<a href=\"$CFG->wwwroot/mod/oublog/viewpost.php?post={$activity->content->postid}\">{$activity->content->title}</a>";
    echo '</div>';

    echo '<div class="user">';
    $fullname = fullname($activity->user, $viewfullnames);
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
    ."{$fullname}</a> - ".oublog_date($activity->timeposted);
    echo '</div>';
    echo "</td></tr></table>";

    return;
}



/**
 * Function to be run periodically according to the moodle cron
 * This function runs every 4 hours.
 *
 * @uses $CFG
 * @return boolean true on success, false on failure.
 **/
function oublog_cron() {
    global $CFG;

    // Delete outdated (> 30 days) moderated comments
    $outofdate = time() - 30 * 24 * 3600;
    delete_records_select('oublog_comments_moderated', "timeposted < $outofdate");

    return true;
}



/**
 * Execute post-install custom actions for the module
 *
 * @return boolean true if success, false on error
 */
function oublog_post_install() {
    global $CFG;
    require_once('locallib.php');

    /// Setup the global blog
    $oublog = new stdClass;
    $oublog->course = SITEID;
    $oublog->name = 'Personal Blogs';
    $oublog->summary = '';
    $oublog->accesstoken = md5(uniqid(rand(), true));
    $oublog->maxvisibility = OUBLOG_VISIBILITY_PUBLIC;
    $oublog->global = 1;
    $oublog->allowcomments = OUBLOG_COMMENTS_ALLOWPUBLIC;

    if (!$oublog->id = insert_record('oublog', $oublog)) {
        return(false);
    }

    $mod = new stdClass;
    $mod->course   = SITEID;
    $mod->module   = get_field('modules', 'id', 'name', 'oublog');
    $mod->instance = $oublog->id;
    $mod->visible  = 1;
    $mod->visibleold  = 0;
    $mod->section = 1;


    if (!$cm = add_course_module($mod)) {
        return(true);
    }
    $mod->id = $cm;
    $mod->coursemodule = $cm;

    $mod->section = add_mod_to_section($mod);

    update_record('course_modules', $mod);

    set_config('oublogsetup', true);

    return(true);
}

/**
 * Obtains a search document given the ousearch parameters.
 * @param object $document Object containing fields from the ousearch documents table
 * @return mixed False if object can't be found, otherwise object containing the following
 *   fields: ->content, ->title, ->url, ->activityname, ->activityurl
 */
function oublog_ousearch_get_document($document) {
    global $CFG;
    require_once('locallib.php');

    // Get data
    if(!($cm=get_record('course_modules','id',$document->coursemoduleid))) {
        return false;
    }
    if(!($oublog=get_record('oublog','id',$cm->instance))) {
        return false;
    }
    if(!($post=get_record_sql("
SELECT
    p.*,bi.userid
FROM
{$CFG->prefix}oublog_posts p
    INNER JOIN {$CFG->prefix}oublog_instances bi ON p.oubloginstancesid=bi.id
WHERE
    p.id=$document->intref1"))) {
return false;
    }

    $result=new StdClass;

    // Set up activity name and URL
    $result->activityname=$oublog->name;
    if($oublog->global) {
        $result->activityurl=$CFG->wwwroot.'/mod/oublog/view.php?user='.
        $document->userid;
    } else {
        $result->activityurl=$CFG->wwwroot.'/mod/oublog/view.php?id='.
        $document->coursemoduleid;
    }

    // Now do the post details
    $result->title=$post->title;
    $result->content=$post->message;
    $result->url=$CFG->wwwroot.'/mod/oublog/viewpost.php?post='.$document->intref1;

    // Sort out tags for use as extrastrings
    $taglist=oublog_get_post_tags($post,true);
    if(count($taglist)!=0) {
        $result->extrastrings=$taglist;
    }

    // Post object is used in filter
    $result->data=$post;

    return $result;
}

/**
 * Update all documents for ousearch.
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function oublog_ousearch_update_all($feedback=false,$courseid=0) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    // Get all existing blogs as $cm objects (which we are going to need to
    // do the updates). get_records is ok here because we're only taking a
    // few fields and there's unlikely to be more than a few thousand blog
    // instances [user blogs all use a single course-module]
    $coursemodules=get_records_sql("
SELECT
    cm.id,cm.course,cm.instance
FROM
{$CFG->prefix}modules m
    INNER JOIN {$CFG->prefix}course_modules cm ON m.id=cm.module
WHERE
    m.name='oublog'".($courseid ? " AND cm.course=$courseid" : ""));
    if (!$coursemodules) {
        $coursemodules = array();
    }

// Display info and loop around each coursemodule
if($feedback) {
    print '<li><strong>'.count($coursemodules).'</strong> instances to process.</li>';
    $dotcount=0;
}
$posts=0; $instances=0;
foreach($coursemodules as $coursemodule) {

    // Get all the posts that aren't deleted
    $rs=get_recordset_sql("
SELECT
    p.id,p.title,p.message,p.groupid,i.userid
FROM
{$CFG->prefix}oublog_instances i
    INNER JOIN {$CFG->prefix}oublog_posts p ON p.oubloginstancesid=i.id
WHERE
    p.deletedby IS NULL AND i.oublogid={$coursemodule->instance}");
while($post=rs_fetch_next_record($rs)) {
    oublog_search_update($post,$coursemodule);

    // Add to count and do user feedback every 100 posts
    $posts++;
    if($feedback && ($posts%100)==0) {
        if($dotcount==0) {
            print '<li>';
        }
        print '.';
        $dotcount++;
        if($dotcount==20 || $count==count($coursemodules)) {
            print "done $posts posts ($instances instances)</li>";
            $dotcount=0;
        }
        flush();
    }
}
rs_close($rs);
$instances++;
}
if($feedback && ($dotcount!=0 || $posts<100)) {
    print ($dotcount==0?'<li>':'')."done $posts posts ($instances instances)</li>";
}
}

/**
 * Indicates API features that the module supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function oublog_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        default: return null;
    }
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in module settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function oublog_get_completion_state($course,$cm,$userid,$type) {
    global $CFG;

    // Get oublog details
    if(!($oublog=get_record('oublog','id',$cm->instance))) {
        throw new Exception("Can't find oublog {$cm->instance}");
    }

    $result=$type; // Default return value

    if($oublog->completionposts) {
        // Count of posts by user
        $value = $oublog->completionposts <= get_field_sql("
SELECT
    COUNT(1)
FROM
{$CFG->prefix}oublog_instances i
    INNER JOIN {$CFG->prefix}oublog_posts p ON i.id=p.oubloginstancesid 
WHERE
    i.userid=$userid AND i.oublogid={$oublog->id} AND p.deletedby IS NULL");
if($type==COMPLETION_AND) {
    $result=$result && $value;
} else {
    $result=$result || $value;
}
    }
    if($oublog->completioncomments) {
        // Count of comments by user (on posts by any user)
        $value = $oublog->completioncomments <= get_field_sql("
SELECT
    COUNT(1)
FROM
{$CFG->prefix}oublog_comments c
    INNER JOIN {$CFG->prefix}oublog_posts p ON p.id=c.postid
    INNER JOIN {$CFG->prefix}oublog_instances i ON i.id=p.oubloginstancesid 
WHERE
    c.userid=$userid AND i.oublogid={$oublog->id} AND p.deletedby IS NULL AND c.deletedby IS NULL");
if($type==COMPLETION_AND) {
    $result=$result && $value;
} else {
    $result=$result || $value;
}
    }

    return $result;
}


/**
 * This function returns a summary of all the postings since the current user
 * last logged in.
 */
function oublog_print_overview($courses,&$htmlarray){
    global $USER, $CFG;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$blogs = get_all_instances_in_courses('oublog',$courses)) {
        return;
    }

    // get all  logs in ONE query
    $sql = "SELECT instance,cmid,l.course,COUNT(l.id) as count FROM {$CFG->prefix}log l "
    ." JOIN {$CFG->prefix}course_modules cm ON cm.id = cmid "
    ." WHERE (";
    foreach ($courses as $course) {
        $sql .= '(l.course = '.$course->id.' AND l.time > '.$course->lastaccess.')  OR ';
    }
    $sql = substr($sql,0,-3); // take off the last OR

    //Ignore comment actions for now, only entries.
    $sql .= ") AND l.module = 'oublog' AND action in('add post','edit post')  
      AND userid != ".$USER->id." GROUP BY cmid,l.course,instance"; 
    if (!$new = get_records_sql($sql)) {
        $new = array(); // avoid warnings
    }

    $strblogs = get_string('modulenameplural','oublog');

    $site = get_site();
    if( count( $courses ) == 1 && isset( $courses[$site->id] ) ){
        $strnumrespsince1 = get_string('overviewnumentrylog1','oublog');
        $strnumrespsince = get_string('overviewnumentrylog','oublog');
    }else{
        $strnumrespsince1 = get_string('overviewnumentryvw1','oublog');
        $strnumrespsince = get_string('overviewnumentryvw','oublog');
    }

    //Go through the list of all oublog instances build previously, and check whether
    //they have had any activity.
    foreach ($blogs as $blog) {
        if (array_key_exists($blog->id, $new) && !empty($new[$blog->id])) {
            $count = $new[$blog->id]->count;
            if( $count > 0 ){
                if( $count == 1 ){
                    $strresp = $strnumrespsince1;
                }else{
                    $strresp = $strnumrespsince;
                }

                $str = '<div class="overview oublog"><div class="name">'.
                $strblogs.': <a title="'.$strblogs.'" href="';
                if ($blog->global=='1'){
                    $str .= $CFG->wwwroot.'/mod/oublog/allposts.php">'.$blog->name.'</a></div>';
                } else {
                    $str .= $CFG->wwwroot.'/mod/oublog/view.php?id='.$new[$blog->id]->cmid.'">'.$blog->name.'</a></div>';
                }
                $str .= '<div class="info">';
                $str .= $count.' '.$strresp;
                $str .= '</div></div>';

                if (!array_key_exists($blog->course,$htmlarray)) {
                    $htmlarray[$blog->course] = array();
                }
                if (!array_key_exists('oublog',$htmlarray[$blog->course])) {
                    $htmlarray[$blog->course]['oublog'] = ''; // initialize, avoid warnings
                }
                $htmlarray[$blog->course]['oublog'] .= $str;

            }

        }

    }

}

?>