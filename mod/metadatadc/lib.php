<?PHP  // $Id: lib.php,v 1.3 2004/06/09 22:35:27 gustav_delius Exp $

/// Library of functions and constants for module metadatadc
/// (replace metadatadc with the name of your module and delete this line)

//require_once($CFG->libdir.'/filelib.php');

$metadatadc_CONSTANT = 7;     /// for example


function metadatadc_add_instance($metadatadc) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.




    $metadatadc->timemodified = time();

    # May have to add extra stuff in here #
    
    return insert_record("metadatadc", $metadatadc);
}


function metadatadc_update_instance($metadatadc) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.

    $metadatadc->timemodified = time();
    $metadatadc->id = $metadatadc->instance;

    # May have to add extra stuff in here #

    return update_record("metadatadc", $metadatadc);
}


function metadatadc_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    if (! $metadatadc = get_record("metadatadc", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("metadatadc", "id", "$metadatadc->id")) {
        $result = false;
    }

    return $result;
}

function metadatadc_user_outline($course, $user, $mod, $metadatadc) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    return $return;
}

function metadatadc_user_complete($course, $user, $mod, $metadatadc) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.

    return true;
}

function metadatadc_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in metadatadc activities and print it out. 
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

function metadatadc_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

    global $CFG;

    return true;
}

function metadatadc_grades($metadatadcid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;

   return NULL;
}

function metadatadc_get_participants($metadatadcid) {
//Must return an array of user records (all data) who are participants
//for a given instance of metadatadc. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function metadatadc_scale_used ($metadatadcid,$scaleid) {
//This function returns if a scale is being used by one metadatadc
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
   
    $return = false;

    //$rec = get_record("metadatadc","id","$metadatadcid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other metadatadc functions go here.  Each of them must have a name that 
/// starts with metadatadc_

function metadatadc_search_form($course, $search='') {
    global $CFG;

    $output  = '<div class="metadatadcsearchform">';
    $output .= '<form name="search" action="'.$CFG->wwwroot.'/mod/metadatadc/search_metadata.php" style="display:inline">';
    $output .= '<input name="search" type="text" size="18" value="'.$search.'" alt="search" />';
    $output .= '<input value="'.get_string('searchmetadatas', 'metadatadc').'" type="submit" />';
    $output .= '<input name="id" type="hidden" value="'.$course->id.'" />';
    $output .= '</form>';
    $output .= helpbutton('search', get_string('search'), 'moodle', true, false, '', true);
    $output .= '</div>';

    return $output;
}

function metadatadc_search_metadatadc($searchterms, $courseid, $page=0, $recordsperpage=50, &$totalcount, $sepgroups=0, $extrasql='') {
/// Returns a list of posts found using an array of search terms
/// eg   word  +word -word
///
    global $CFG, $USER;
    require_once($CFG->libdir.'/searchlib.php');

/*    if (!isteacher($courseid)) {
        $notteacherforum = "AND f.type <> 'teacher'";
        $forummodule = get_record("modules", "name", "forum");
        $onlyvisible = "AND d.forum = f.id AND f.id = cm.instance AND cm.visible = 1 AND cm.module = $forummodule->id";
        $onlyvisibletable = ", {$CFG->prefix}course_modules cm, {$CFG->prefix}forum f";
        if (!empty($sepgroups)) {
            $separategroups = SEPARATEGROUPS;
            $selectgroup = " AND ( NOT (cm.groupmode='$separategroups'".
                                      " OR (c.groupmode='$separategroups' AND c.groupmodeforce='1') )";//.
            $selectgroup .= " OR d.groupid = '-1'"; //search inside discussions for all groups too
            foreach ($sepgroups as $sepgroup){
                $selectgroup .= " OR d.groupid = '$sepgroup->id'";
            }
            $selectgroup .= ")";

                               //  " OR d.groupid = '$groupid')";
            $selectcourse = " AND d.course = '$courseid' AND c.id='$courseid'";
            $coursetable = ", {$CFG->prefix}course c";
        } else {
            $selectgroup = '';
            $selectcourse = " AND d.course = '$courseid'";
            $coursetable = '';
        }
    } else {
        $notteacherforum = "";
        $selectgroup = '';
        $onlyvisible = "";
        $onlyvisibletable = "";
        $coursetable = '';
        if ($courseid == SITEID && isadmin()) {
            $selectcourse = '';
        } else {
            $selectcourse = " AND d.course = '$courseid'";
        }
    }

    $timelimit = '';
    if (!empty($CFG->forum_enabletimedposts) && (!((isadmin() and !empty($CFG->admineditalways)) || isteacher($courseid)))) {
        $now = time();
        $timelimit = " AND (d.userid = $USER->id OR ((d.timestart = 0 OR d.timestart <= $now) AND (d.timeend = 0 OR d.timeend > $now)))";
    }
*/
    $limit = sql_paging_limit($page, $recordsperpage);

    /// Some differences in syntax for PostgreSQL
    if ($CFG->dbtype == "postgres7") {
        $LIKE = "ILIKE";   // case-insensitive
        $NOTLIKE = "NOT ILIKE";   // case-insensitive
        $REGEXP = "~*";
        $NOTREGEXP = "!~*";
    } else {
        $LIKE = "LIKE";
        $NOTLIKE = "NOT LIKE";
        $REGEXP = "REGEXP";
        $NOTREGEXP = "NOT REGEXP";
    }

    $messagesearch = "";
    $searchstring = "";
    // Need to concat these back together for parser to work.
    foreach($searchterms as $searchterm){
        if ($searchstring != "") {
            $searchstring .= " ";
        }
        $searchstring .= $searchterm;
    }

    // We need to allow quoted strings for the search. The quotes *should* be stripped
    // by the parser, but this should be examined carefully for security implications.
    $searchstring = str_replace("\\\"","\"",$searchstring);
    $parser = new search_parser();
    $lexer = new search_lexer($parser);

    if ($lexer->parse($searchstring)) {
        $parsearray = $parser->get_parsed_array();
        $messagesearch = search_generate_SQL($parsearray,'p.message','p.subject','p.userid','u.id','u.firstname','u.lastname','p.modified', 'd.metadatadc');
    }

    $selectsql = "{$CFG->prefix}metadatadc d,
                  {$CFG->prefix}user u $onlyvisibletable $coursetable
             WHERE ($messagesearch)
               AND p.userid = u.id
               AND p.discussion = d.id $selectcourse $notteacherforum $onlyvisible $selectgroup $timelimit $extrasql";

    $totalcount = count_records_sql("SELECT COUNT(*) FROM $selectsql");

    return get_records_sql("SELECT p.*,d.forum, u.firstname,u.lastname,u.email,u.picture FROM
                            $selectsql ORDER BY p.modified DESC $limit");
}

function metadatadc_get_course_metadatadc($courseid, $type) {
// How to set up special 1-per-course forums
    global $CFG;

    if ($forums = get_records_select("metadatadc", "course = '$courseid'", "id ASC")) {
        // There should always only be ONE, but with the right combination of
        // errors there might be more.  In this case, just return the oldest one (lowest ID).
        foreach ($metadatadcs as $metadatadc) {
            return $metadatadc;   // ie the first one
        }
    }

    // Doesn't exist, so create one now.
    $metadatadc->course = $courseid;
    switch ($forum->type) {
        case "news":
            $forum->name  = addslashes(get_string("namenews", "forum"));
            $forum->intro = addslashes(get_string("intronews", "forum"));
            $forum->forcesubscribe = FORUM_FORCESUBSCRIBE;
            $forum->open = 1;   // 0 - no, 1 - posts only, 2 - discuss and post
            $forum->assessed = 0;
            if ($courseid == SITEID) {
                $forum->name  = get_string("sitenews");
                $forum->forcesubscribe = 0;
            }
            break;
        case "social":
            $forum->name  = addslashes(get_string("namesocial", "forum"));
            $forum->intro = addslashes(get_string("introsocial", "forum"));
            $forum->open = 2;   // 0 - no, 1 - posts only, 2 - discuss and post
            $forum->assessed = 0;
            $forum->forcesubscribe = 0;
            break;
        case "teacher":
            $forum->name  = addslashes(get_string("nameteacher", "forum"));
            $forum->intro = addslashes(get_string("introteacher", "forum"));
            $forum->open = 2;   // 0 - no, 1 - posts only, 2 - discuss and post
            $forum->assessed = 0;
            $forum->forcesubscribe = 0;
            break;
        default:
            notify("That forum type doesn't exist!");
            return false;
            break;
    }

    $forum->timemodified = time();
    $forum->id = insert_record("forum", $forum);

    if ($forum->type != "teacher") {
        if (! $module = get_record("modules", "name", "forum")) {
            notify("Could not find forum module!!");
            return false;
        }
        $mod->course = $courseid;
        $mod->module = $module->id;
        $mod->instance = $forum->id;
        $mod->section = 0;
        if (! $mod->coursemodule = add_course_module($mod) ) {   // assumes course/lib.php is loaded
            notify("Could not add a new course module to the course '$course->fullname'");
            return false;
        }
        if (! $sectionid = add_mod_to_section($mod) ) {   // assumes course/lib.php is loaded
            notify("Could not add the new course module to that section");
            return false;
        }
        if (! set_field("course_modules", "section", $sectionid, "id", $mod->coursemodule)) {
            notify("Could not update the course module with the correct section");
            return false;
        }
        include_once("$CFG->dirroot/course/lib.php");
        rebuild_course_cache($courseid);
    }

    return get_record("forum", "id", "$forum->id");
}


/*Não está a funcionar pq não encontra a classe quando chamado a partir de mod.html (exemplo em edit.html)
function metadatadc_make_resources_list(&$list, &$parents, $resource=NULL, $path="") {
/// Given an empty array, this function recursively travels the
/// resources, building up a nice list for display.  It also makes
/// an array that list all the parents for each resource.

    // initialize the arrays if needed
    if (!is_array($list)) {
        $list = array(); 
    }
    if (!is_array($parents)) {
        $parents = array(); 
    }

    if ($resource) {
        if ($path) {
            $path = $path.' / '.$resource->name;
        } else {
            $path = $resource->name;
        }
        $list[$resource->id] = $path;
    } else {
        $resource->id = 0;
    }

    if ($resources = get_resources($resource->id)) {   // Print all the children recursively
        foreach ($resources as $resourc) {
            if (!empty($resource->id)) {
                if (isset($parents[$resource->id])) {
                    $parents[$cat->id]   = $parents[$resource->id];
                }
                $parents[$resourc->id][] = $resource->id;
            }
            metadatadc_make_resources_list($list, $parents, $resourc, $path);
        }
    }
}
*/
?>
