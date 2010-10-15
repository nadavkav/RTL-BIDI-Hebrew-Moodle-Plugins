<?php  // $Id: lib.php,v 1.4.4.1 2007/09/24 08:46:22 janne Exp $

/// Library of functions and constants for module netpublish

    // Permissions class to handle permissions
    require_once('permissions.class.php');

    if (empty($nperm)) {
        $nperm = new Permissions;
    }

function netpublish_add_instance($netpublish) {
/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will create a new instance and return the id number
/// of the new instance.

    $netpublish->timemodified = time();
    $netpublish->timecreated  = time();

    # May have to add extra stuff in here #

    return insert_record("netpublish", $netpublish);
}


function netpublish_update_instance($netpublish) {
/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will update an existing instance with new data.

    $netpublish->timemodified = time();
    $netpublish->id = $netpublish->instance;

    # May have to add extra stuff in here #

    // If locktime is not set purge every value
    // in netpublish_lock table wich are related
    // on this instance
    if (empty($netpublish->locktime)) {
        $pages = get_records_select("netpublish_articles",
                                    "publishid = $netpublish->instance", "", "id, title");
        if (!empty($pages)) {
            $strids = '';
            $cnt    = count($pages);
            $i      = 1;
            foreach ($pages as $page) {
                $strids .= ($i < $cnt) ? $page->id .',' : $page->id;
                $i++;
            }

            $select = "pageid IN ($strids)";
            delete_records_select("netpublish_lock", $select);
        }
        $netpublish->locktime = '0';
    }


    return update_record("netpublish", $netpublish);
}


function netpublish_delete_instance($id) {
/// Given an ID of an instance of this module,
/// this function will permanently delete the instance
/// and any data that depends on it.

    if (! $netpublish = get_record("netpublish", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #
    // Delete locks in this instance
    netpublish_unset_locks($netpublish->id);
    // Delete status records and articles in this instance
    $articleids = array();
    if ( $articles = get_records("netpublish_articles", "publishid", $netpublish->id) ) {
        foreach ( $articles as $article ) {
            array_push($articleids, $article->id);
        }
    }
    $strarticleids = implode(",", $articleids);
    if ( !empty($strarticleids) ) {
        if ( !delete_records_select("netpublish_status_records","id IN ($strarticleids)") ) {
            $result = false;
        }
    }

    if (! delete_records("netpublish_articles", "publishid", $netpublish->id)) {
        $result = false;
    }
    // Delete sections in this instance
    if (! delete_records("netpublish_sections", "publishid", $netpublish->id)) {
        $result = false;
    }

    // Delete first section name
    if ( !delete_records("netpublish_first_section_names", "publishid", $netpublish->id) ) {
        $result = false;
    }

    // Delete grades of this instance
    if ( !delete_records("netpublish_grades", "publishid", $netpublish->id) ) {
        $result = false;
    }

    // Delete instance.
    if (! delete_records("netpublish", "id", "$netpublish->id")) {
        $result = false;
    }

    return $result;
}

function netpublish_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such
/// as sending out mail, toggling flags etc ...

    global $CFG;

    // Delete expired locks
    $netpublishes = get_records_sql("SELECT id, locktime
                                     FROM {$CFG->prefix}netpublish");

    if (!empty($netpublishes)) {
        foreach ($netpublishes as $netpublish) {
            // Clear locks.
            if (!empty($netpublish->locktime)) {
                netpublish_unset_locks($netpublish->id, $netpublish->locktime);
            }
            // Delete images which doesn't belong to any course.
            if ( !record_exists("course", "id", $netpublish->course) ) {
                $images = get_records("netpublish_images", "course", $netpublish->course);
                if ( !empty($images) ) {
                    $unlinkfunk = ($CFG->debug < 7 ) ? 'unlink' : '@unlink';
                    foreach ( $images as $image ) {
                        $unlinkfunk($CFG->dataroot .'/'. $image->path);
                    }
                    if ( delete_records("netpublish_images", "course", $netpublish->course) ) {
                        mtrace("Delete images from unexist course!");
                    }
                }
            }
        }
    }

    // Optimize netpublish tables
    $timenow  = time();
    $midnight = usergetmidnight($timenow);
    $timeone  = $midnight - 600;
    $timetwo  = $midnight + 600;

    if ($timenow > $timeone and $timenow < $timetwo) {
        if ($CFG->dbtype == 'mysql') {
            execute_sql("OPTIMIZE {$CFG->prefix}netpublish", false);
            execute_sql("OPTIMIZE {$CFG->prefix}netpublish_articles", false);
            execute_sql("OPTIMIZE {$CFG->prefix}netpublish_images", false);
            execute_sql("OPTIMIZE {$CFG->prefix}netpublish_lock", false);
            execute_sql("OPTIMIZE {$CFG->prefix}netpublish_sections", false);
        } else if ($CFG->dbtype == 'postgres7') {
            execute_sql("VACUUM {$CFG->prefix}netpublish", false);
            execute_sql("VACUUM {$CFG->prefix}netpublish_articles", false);
            execute_sql("VACUUM {$CFG->prefix}netpublish_images", false);
            execute_sql("VACUUM {$CFG->prefix}netpublish_lock", false);
            execute_sql("VACUUM {$CFG->prefix}netpublish_sections", false);
        }
        mtrace("Optimizing database tables...\r\n");
    }

    return true;
}

function netpublish_grades($netpublishid) {
/// Must return an array of grades for a given instance of this module,
/// indexed by user.  It also returns a maximum allowed grade.

    $retval = new stdClass;

    $netpublishid = clean_param($netpublishid, PARAM_INT);

    if (! $netpublish = get_record("netpublish", "id", $netpublishid) ) {
        return NULL;
    }

    $grades = get_records_menu("netpublish_grades", "publishid",
                               $netpublish->id, "", "userid,grade");

    if ($netpublish->scale > 0) {

        $retval->grades   = $grades;
        $retval->maxgrade = $netpublish->scale;

    } else if ($netpublish->scale == 0) {

        return NULL;

    } else {

        if ($scale = get_record("scale", "id", - $netpublish->scale)) {
            $scalegrades = make_menu_from_list($scale->scale);
            if ($grades) {
                foreach ($grades as $key => $grade) {
                    $grades[$key] = $scalegrades[$grade];
                }
            }
        }
        $retval->grades = $grades;
        $retval->maxgrade = "";

    }

    return $retval;

}

function netpublish_get_participants($netpublishid) {
//Return an array of user records (all data) who are participants
//for a given instance of netpublish. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.
    global $CFG;

    $netpublishid = clean_param($netpublishid, PARAM_INT);

    $userids = get_records_sql("SELECT DISTINCT userid, teacherid, authors
                                FROM {$CFG->prefix}netpublish_articles
                                WHERE publishid = $netpublishid");

    $usertmp = array();

    if ( is_array($userids) ) {
        foreach ($userids as $user) {
            array_push($usertmp, $user->userid);
            array_push($usertmp, $user->teacherid);
            if (! empty($user->authors) ) {
                $strtoarr = explode(",", $user->authors);
                foreach ($strtoarr as $userid) {
                    array_push($usertmp, $userid);
                }
            }
        }
    }

    unset($userids);
    $usertmp = array_unique($usertmp);
    sort($usertmp);
    $userids = implode(",", $usertmp);
    unset($usertmp);
    $userids = addslashes($userids);

    $users = get_records_sql("SELECT DISTINCT u.id, u.id
                              FROM {$CFG->prefix}user AS u
                              WHERE u.id IN ($userids)");


    return $users;

}

function netpublish_scale_used ($netpublishid,$scaleid) {
//This function returns if a scale is being used by one netpublish
//it it has support for grading and scales.

    $return = false;

    $rec = get_record("netpublish","id","$netpublishid","scale","-$scaleid");

    if (!empty($rec)  && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other netpublish functions go here.  Each of them must have a name that
/// starts with netpublish_

function netpublish_create_first_section ($publishid) {
// Creates a firstpage section to a selected netpublish

    $publishid = clean_param($publishid, PARAM_INT);

    if (empty($publishid)) {
        return;
    }

    // check if the record already exists
    if ( $frontpage = get_record("netpublish_first_section_names",
                                 "publishid", $publishid)) {
        return (int) $frontpage->id;
        exit;
    }

    $newfrontpage->publishid = $publishid;
    $newfrontpage->name = get_string("frontpage","netpublish");

    if (! $insertid = insert_record("netpublish_first_section_names",
          $newfrontpage)) {
        return;
    }

    return (int) $insertid;

}

function netpublish_print_section_list ($instance, $name, $toplevel=true, $selected="") {
/// this function need to be recursive
/// D'oh
///

    $sections    = netpublish_get_sections($instance);

    // There is only main section so make it
    // visible to students also.
    if (empty($sections)) {
        $toplevel = true;
    }

    $frontpageid   = !empty ($sections) && !empty($sections[key($sections)]->frontpageid) ?
                      $sections[key($sections)]->frontpageid :
                      netpublish_create_first_section($instance);
    $frontpagename = !empty($sections) && !empty($sections[key($sections)]->frontpagename) ?
                      $sections[key($sections)]->frontpagename :
                     ((! $frontpage = get_record("netpublish_first_section_names", "publishid", $instance)) ?
                     get_string("frontpage","netpublish") : $frontpage->name);

    if (empty($name)) {
        $name = "parentid";
    }

    if (!empty($frontpageid)) {
        printf("<input type=\"hidden\" name=\"frontpageid\" value=\"%d\" />\n",
               $frontpageid);
    }

    printf("<select id=\"%s\" name=\"%s\">\n", $name, $name);
    if ($toplevel) {
        printf("\t<option value=\"0\">%s</option>\n", $frontpagename);
    }

    if (!empty($sections)) {
        netpublish_section_options (0, $sections, $selected);
    }
    print("</select>\n");
}

function netpublish_section_options ($pid, &$arr, $selected="") {

    static $count;

    if (empty($count)) {
        $count = 0;
    }

    foreach ( $arr as $obj ) {
        if ($obj->parentid == $pid) {
            $strselected = (!empty($selected) && $selected == $obj->id) ? ' selected="selected">' : '>';
            printf("\t<option value=\"%d\"%s%s%s</option>\n", $obj->id, $strselected,
                  str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $count), $obj->fullname);
            $count++;
            netpublish_section_options($obj->id, &$arr, $selected);
        }
    }

    $count--;

}

function netpublish_print_status_list ($name, $publish=false, $selected=5, $onchange="", $return=false, $exclude="") {

    static $statuses;

    if (empty($statuses)) {
        $statuses = get_records("netpublish_status");
    }

    $output = '';

    if (is_array($statuses)) {

        $output  = "<select name=\"$name\"";
        $output .= !empty($onchange) ? " $onchange":"";
        $output .=  ">\n";
        foreach ($statuses as $status) {
            $strstatus = get_string(strtolower($status->name), "netpublish");

            if (!$publish && $status->id == 4) {
                continue;
            }

            if (! empty($exclude) && in_array($status->id, $exclude) ) {
                continue;
            }

            $output .=  "\t<option value=\"$status->id\"";
            $output .= (!empty($selected) && $selected == $status->id) ? " selected=\"selected\">" : ">";
            $output .=  $strstatus ."</option>\n";
        }
        $output .=  "</select>\n";
    }

    if (! $return ) {
        echo $output;
    } else {
        return $output;
    }

    unset($statuses, $status);

}

function netpublish_print_teacher_list ($courseid, $selected="") {

    $teachers = netpublish_get_visible_teachers($courseid);

    echo "<select name=\"teacherid\">\n";
    if (is_array($teachers)) {
        foreach ($teachers as $teacher) {
            echo "<option value=\"$teacher->id\"";
            print(!empty($selected) && $selected == $teacher->id) ? " selected=\"true\"" : "";
            echo ">$teacher->firstname $teacher->lastname</option>\n";
        }
    }
    echo "</select>\n";
    unset($teachers, $teacher);
}

function netpublish_print_sections ($moduleid, $instance) {

    $instance = clean_param($instance, PARAM_INT);

    $sid = !empty($_GET['section']) ? $_GET['section'] :
          (!empty($HTTP_GET_VARS['section']) ? $HTTP_GET_VARS['section'] : 0);
    $sid = clean_param($sid, PARAM_INT);
    $currentsection = optional_param('section', 0, PARAM_INT);     // section id

    $sections     = netpublish_get_sections($instance);
    $strfrontpage = !empty($sections) && !empty($sections[key($sections)]->frontpagename) ?
                     $sections[key($sections)]->frontpagename :
                     ((! $frontpage = get_record("netpublish_first_section_names", "publishid", $instance)) ?
                     get_string("frontpage","netpublish") : $frontpage->name);

    //echo "<p>";

    if ($currentsection != 0) $tmplink = "%sחזרה לדף הראשי: <a href=\"view.php?id=%d&amp;section=0\">%s</a>%s";
    echo !empty($sid) ? sprintf($tmplink, "", $moduleid, $strfrontpage, "") :
                        sprintf($tmplink, "<strong>", $moduleid, $strfrontpage, "</strong>");
echo "<ul class=\"netpublish-sectionlist\">";
    if (!empty($sections)) {
        netpublish_print_section_listview(0, &$sections, $moduleid);
    }
echo "</ul>";
    //echo "</p>\n";

}

function netpublish_print_section_tree($pid, $arr, $moduleid) {
    global $CFG;

    $sid = !empty($_GET['section']) ? $_GET['section'] :
          (!empty($HTTP_GET_VARS['section']) ? $HTTP_GET_VARS['section'] : 0);
    $sid = clean_param($sid, PARAM_INT);

    static $count;

    if (empty($count)) {
        $count = 0;
    }

    foreach ( $arr as $obj ) {
        if ( $obj->parentid == $pid ) {
            $prefix   = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $count);
            $fullname = ($sid != $obj->id) ? $obj->fullname : '<strong>'. $obj->fullname .'</strong>';

            printf("<div class=\"sectiontitle\">%s<a href=\"view.php?id=%d&amp;section=%d\"><img style=\"vertical-align:middle; width:32px;\" src=\"$CFG->wwwroot/mod/netpublish/pix/story-editor.png\"> %s</a></div>\n",
                   $prefix, $moduleid, $obj->id, $fullname);
            $count++;
            netpublish_print_section_tree($obj->id, &$arr, $moduleid);
        }
    }

    $count--;
}

function netpublish_print_section_listview($pid, $arr, $moduleid) {
    global $CFG;

    $sid = !empty($_GET['section']) ? $_GET['section'] :
          (!empty($HTTP_GET_VARS['section']) ? $HTTP_GET_VARS['section'] : 0);
    $sid = clean_param($sid, PARAM_INT);

    static $count;

    if (empty($count)) {
        $count = 0;
    }

    foreach ( $arr as $obj ) {
        if ( $obj->parentid == $pid ) {
            $prefix   = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $count);
            $fullname = ($sid != $obj->id) ? $obj->fullname : '<strong>'. $obj->fullname .'</strong>';

            printf("<li class=\"sectiontitle\">%s<a href=\"view.php?id=%d&amp;section=%d\"><img style=\"vertical-align:middle; width:32px;\" src=\"$CFG->wwwroot/mod/netpublish/pix/story-editor.png\"> %s</a></li>\n",
                   $prefix, $moduleid, $obj->id, $fullname);
            $count++;
            netpublish_print_section_listview($obj->id, &$arr, $moduleid);
        }
    }

    $count--;
}

function netpublish_set_rights ($inrights) {

    global $nperm;

    parse_str($inrights, $inrights);

    $outrights = array();
    $read      = !empty ($inrights['read'])  ? $inrights['read']  : '';
    $write     = !empty ($inrights['write']) ? $inrights['write'] : '';

    // Process read rights;
    if (is_array($read) && !empty($read)) {

        foreach ($read as $rkey => $rvalue) {
            $outrights[$rkey] = $nperm->assign_right(PRM_READ);
        }
        unset($rkey, $rvalue);
    }

    // Process write rights
    // Write permission is stronger than read permission so
    // it's ok to replace read permission with it.
    if (is_array($write) && !empty($write)) {

        foreach ($write as $wkey => $wvalue) {
            $outrights[$wkey] = $nperm->assign_right(PRM_WRITE);
        }
        unset($wkey, $wvalue);
    }

    if (!empty($outrights)) {
        $outrights = serialize($outrights);
        return $outrights;
    }

    return '';

}

function netpublish_get_rights ($inrights) {

    if (!empty($inrights)) {
        return unserialize($inrights);
    }

    return array();

}

function netpublish_print_actionbuttons ($cm, $article, $userid, $courseid=0, $printmove=false, $return=false) {
    global $CFG, $USER, $nperm;

    $stredit      = get_string('edit');
    $strdelete    = get_string('delete');
    $strmove      = get_string('move');
    $icons        = $CFG->wwwroot .'/pix/t';
    $editbutton   = '<img src="'. $icons .'/edit.gif" alt="'. $stredit .'" title="'. $stredit .'" />';
    $deletebutton = '<img src="'. $icons .'/delete.gif" alt="'. $strdelete .'" title="'. $strdelete .'" />';
    $movebutton   = '<img src="'. $icons .'/move.gif" alt="'. $strmove .'" title="'. $strmove .'" />';

    $editlink   = '<a href="editarticle.php?id=%d&amp;article=%d&amp;sesskey=%s">'. $editbutton .'</a>';
    $deletelink = '<a href="delete.php?id=%d&amp;article=%d&amp;s=%s">'. $deletebutton .'</a>';
    $movelink   = '<a href="move.php?id=%d&amp;article=%d&amp;sesskey=%s">'. $movebutton .'</a>';

    $isteacher = has_capability('moodle/legacy:editingteacher', get_context_instance(CONTEXT_COURSE, $courseid));
    $userights = !empty($article->rights) ? unserialize($article->rights) : 0;
    $canwrite  = !empty($userights[$userid]) ? $nperm->can_write($userights[$userid]) : 0;

    $output = '';

    if (!$isteacher and intval($article->statusid) >= 4) {
        return;
    }

    if ($isteacher or $canwrite or ($article->userid == $userid)) {
        $output .= sprintf($editlink, $cm->id, $article->id, $USER->sesskey);
    }

    if ($isteacher or ($article->userid == $userid)) {
        $output .= "&nbsp;&nbsp;";
        $output .= sprintf($deletelink, $cm->id, $article->id, $USER->sesskey);
    }

    if ($isteacher and $printmove) {
        $output .= "&nbsp;&nbsp;";
        $output .= sprintf($movelink, $cm->id, $article->id, $USER->sesskey);
    }

    if (! $return ) {
        echo $output;
    } else {
        return $output;
    }

    unset($cm, $article, $userights, $canwrite, $output);

}

function netpublish_print_move_here($id, $article, $count, $section) {
    global $CFG;
    global $USER;

    $pic = sprintf("<img src=\"%s/pix/movehere.gif\" alt=\"%s\" title=\"%s\" />",
                   $CFG->wwwroot,
                   get_string('movehere'),
                   get_string('movehere'));
    $strreturn = sprintf("<a href=\"move.php?id=%d&amp;article=%d&amp;order=%d&amp;section=%d&amp;sesskey=%s\">%s</a>",
                         $id, $article, $count, $section, $USER->sesskey, $pic);
    $strreturn = str_repeat("&nbsp;", 4) . $strreturn;

    return $strreturn;
}

function netpublish_construct_userids ($struserids, $owner, $userid) {

    if ($owner == $userid) {
        return $struserids;
    }

    $arrids = explode(",", $struserids);

    if (in_array($userid, $arrids)) {
        return $struserids;
    } else {
        if (!empty($struserids)) {
            return $struserids .','. $userid;
        } else {
            return $userid;
        }
    }

    return $struserids;

}

function netpublish_print_authors ($authorids, $return=false) {

    $authors = netpublish_get_authors($authorids);

    $output = '';

    if (!empty($authors)) {
        foreach ($authors as $author) {
            $output .= ', ' . fullname($author);
        }
    }

    if (!$return) {
        print($output);
    } else {
        return (string) $output;
    }

}

function netpublish_clean_userinput ($form) {
/// I'll use this until clean_text has
/// some sence of cleaning what it does...

    global $ALLOWED_TAGS;

    $form->title = trim(strip_tags($form->title));
    // Process intro field
    if (!empty($form->intro)) {
        $form->intro = trim($form->intro);
        $form->intro = strip_tags($form->intro, $ALLOWED_TAGS);
        $form->intro = eregi_replace("([^a-z])language([[:space:]]*)=", "\\1Xlanguage=", $form->intro);
        $form->intro = eregi_replace("([^a-z])on([a-z]+)([[:space:]]*)=", "\\1Xon\\2=", $form->intro);
        $form->intro = addslashes($form->intro);
    }

    // Process content field

    if (!empty($form->content)) {
        $form->content = trim($form->content);
        $form->content = strip_tags($form->content, $ALLOWED_TAGS);
        $form->content = eregi_replace("([^a-z])language([[:space:]]*)=", "\\1Xlanguage=", $form->content);
        $form->content = eregi_replace("([^a-z])on([a-z]+)([[:space:]]*)=", "\\1Xon\\2=", $form->content);
        $form->content = addslashes($form->content);
    }

    return $form;
}

function netpublish_is_intval ($val) {

    // Only digits are allowed
    if (preg_match("/^(\d+)?$/", $val)) {
        return true;
    }

    // no floating point numbers
    if (preg_match("/^(\d+)?\.(\d+)$/", $val)) {
        return false;
    }
    // No letters
    if (preg_match("/^\D+/", $val)) {
        return false;
    }

    return false;

}

function netpublish_set_std_classes (&$cm, &$course, &$mod, $arrdata) {
/// Constructs stdClasses $cm, $course and $mod
/// for Moodle compatibility.

    if (!is_array($arrdata)) {
        return false;
    }

    foreach ($arrdata as $key => $value) {
        switch ($key) {
            case 'cm':
                $cm = (object) $value;
                break;
            case 'course':
                $course = (object) $value;
                break;
            case 'mod':
                $mod = (object) $value;
                break;
        }
    }
    unset($arrdata);
}

/////////////////// SQL FUNCTIONS //////////////////////

function netpublish_get_sections ($publishid) {
/// Get sections available for current instance.

    global $CFG;

    $publishid = clean_param($publishid, PARAM_INT);

    return get_records_sql("SELECT s.*, f.id AS frontpageid, f.name AS frontpagename FROM
                           {$CFG->prefix}netpublish_sections AS s LEFT OUTER JOIN
                           {$CFG->prefix}netpublish_first_section_names AS f
                           ON s.publishid = f.publishid
                           WHERE s.publishid = $publishid");
}

function netpublish_get_visible_teachers ($courseid) {
/// This function returns all visible teachers info
/// as an array of objects
    return get_course_teachers($courseid);
}

function netpublish_get_articles ($sectionid, $instance, $fullpage=false) {

    global $CFG;

    if ($fullpage) {
        $content = " a.content,";
    } else {
        $content = "";
    }

    $fields  = 'a.id, a.publishid, a.sectionid, a.title, a.intro, ';
    $fields .= $content .' a.timecreated, a.timemodified, ';
    $fields .= 'a.timepublished, a.userid, a.authors, a.rights, a.statusid, ';
    $fields .= 'u.firstname, u.lastname ';

    $articles = get_records_sql("SELECT $fields
                                 FROM {$CFG->prefix}netpublish_articles AS a
                                 INNER JOIN {$CFG->prefix}user AS u
                                 ON a.userid = u.id
                                 AND a.publishid = $instance
                                 AND a.sectionid = $sectionid
                                 AND statusid = 4 ORDER BY a.sortorder");

    if (empty($articles)) {
        return false;
    }

    return (array) $articles;
}

function netpublish_set_article_linked_list($articles) {
    $prevarticle = 0;

    foreach ($articles as $article) {
        $update = new stdClass;
        $update->id = $article->id;

        $update->prevarticle = $articles[$article->id]->prevarticle = $prevarticle;
        $prevarticle = $article->id;

        if($nextarticle = next($articles)) {
            $nextarticle = $nextarticle->id;
        } else {
            $nextarticle = 0;
        }

        $update->nextarticle = $articles[$article->id]->nextarticle = $nextarticle;


        update_record('netpublish_articles', $update);
    }

    return $articles;
}

function netpublish_get_article($id, $status=4) {

    global $CFG;

    if (empty($id)) {
        return;
    }

    $article = get_records_sql("SELECT a.*, u.firstname, u.lastname
                                 FROM {$CFG->prefix}netpublish_articles AS a
                                 INNER JOIN {$CFG->prefix}user AS u
                                 ON a.userid = u.id
                                 AND a.statusid = $status
                                 AND a.id = $id");

    if (empty($article)) {
        return;
    }

    return $article[key($article)];

}

function netpublish_get_first_article($sectionid, $netpublishid, $status=4) {

    global $CFG;

    $article = get_records_sql("SELECT a.*, u.firstname, u.lastname
                                 FROM {$CFG->prefix}netpublish_articles AS a
                                 INNER JOIN {$CFG->prefix}user AS u
                                 ON a.userid = u.id
                                 AND a.statusid = $status
                                 AND a.sectionid = $sectionid
                                 AND a.publishid = $netpublishid
                                 AND a.prevarticle = 0");

    if (empty($article)) {
        return;
    }

    return $article[key($article)];

}

function netpublish_get_last_article($sectionid, $netpublishid, $status=4) {

    global $CFG;

    $article = get_records_sql("SELECT a.*, u.firstname, u.lastname
                                 FROM {$CFG->prefix}netpublish_articles AS a
                                 INNER JOIN {$CFG->prefix}user AS u
                                 ON a.userid = u.id
                                 AND a.statusid = $status
                                 AND a.sectionid = $sectionid
                                 AND a.publishid = $netpublishid
                                 AND a.nextarticle = 0");

    if (empty($article)) {
        return;
    }

    return $article[key($article)];

}

function netpublish_get_pending_articles ($instance, $sort=false) {

    global $CFG;

    if ($sort) {
        $orderby = 'ORDER BY a.teacherid DESC';
    } else {
        $orderby = 'ORDER BY a.timecreated DESC';
    }

    $articles = get_records_sql("SELECT a.id, a.publishid, a.title, a.timecreated, a.statusid,
                                a.timemodified, a.teacherid, a.userid, a.authors, a.rights, u.firstname,
                                u.lastname, t.firstname AS tfirstname, t.lastname AS tlastname, s.name AS status
                                FROM {$CFG->prefix}netpublish_status AS s,
                                {$CFG->prefix}netpublish_articles AS a
                                INNER JOIN {$CFG->prefix}user AS u
                                ON a.userid = u.id
                                INNER JOIN {$CFG->prefix}user AS t
                                ON a.teacherid = t.id
                                WHERE s.id = a.statusid
                                AND a.publishid = $instance
                                AND statusid != 4
                                $orderby");

    if (empty($articles)) {
        return;
    }

    return $articles;

}

function netpublish_get_info ($id) {

    global $CFG;

    $info = get_records_sql ("SELECT p.id, p.course, p.name
                             FROM {$CFG->prefix}netpublish AS p,
                             {$CFG->prefix}netpublish_articles AS a
                             WHERE p.id = a.publishid
                             AND a.id = $id");

    // Return first object
    return $info[key($info)];

}

function netpublish_count_pending ($publishid) {

    global $CFG;

    return count_records_sql("SELECT count(id) AS pending
                             FROM {$CFG->prefix}netpublish_articles
                             WHERE statusid != 4
                             AND publishid = $publishid");

}

function netpublish_count_sections ($publishid) {

    global $CFG;

    $publishid = clean_param($publishid, PARAM_INT);

    $select = "publishid = " . $publishid;
    $countitem = "COUNT(id) AS sections";

    return count_records_select("netpublish_first_section_names",
                                $select,
                                $countitem);
    //return count_records_sql("SELECT count(id) AS sections
    //                         FROM {$CFG->prefix}netpublish_sections
    //                         WHERE publishid = $publishid");

}

function netpublish_get_record ($moduleid) {

    global $CFG;

    // if version is 1.5 unstable development
    // or newer get also theme value for course
    if (intval($CFG->version) >= 2005041900) {
        $fields = "cm.id, cm.module, cm.instance, cm.visible,
                   cm.groupmode, c.id AS courseid, c.fullname,
                   c.shortname, c.category, c.maxbytes, c.theme,
                   c.groupmodeforce, c.lang, c.guest, c.student, c.visible,
                   m.id AS moduleid, m.name, m.intro,
                   m.timecreated, m.timemodified, m.maxsize,
                   m.locktime, m.published, m.fullpage, m.statuscount, m.scale, m.titleimage, m.netpublishtheme, m.title, m.frontpagecolumns ";
    } else {
        $fields = "cm.id, cm.module, cm.instance, cm.visible,
                   cm.groupmode, c.id AS courseid, c.fullname,
                   c.shortname, c.category, c.maxbytes,
                   c.groupmodeforce, c.lang, c.guest, c.student, c.visible,
                   m.id AS moduleid, m.name, m.intro,
                   m.timecreated, m.timemodified, m.maxsize,
                   m.locktime, m.published, m.fullpage, m.statuscount, m.scale, m.titleimage, m.netpublishtheme, m.title, m.frontpagecolumns ";
    }

    $recordset = get_records_sql("SELECT $fields
                           FROM {$CFG->prefix}course_modules AS cm,
                           {$CFG->prefix}course AS c,
                           {$CFG->prefix}netpublish AS m
                           WHERE c.id = cm.course
                           AND m.id = cm.instance
                           AND cm.id=$moduleid");

    // return first and only object in array
    //return $rs[key($rs)];

    $arrout           = array();
    $arrout['cm']     = array();
    $arrout['course'] = array();
    $arrout['mod']    = array();
    foreach ($recordset as $rs) {
        $arrout['cm']['id'] = $rs->id;
        $arrout['cm']['module'] = $rs->module;
        $arrout['cm']['instance'] = $rs->instance;
        $arrout['cm']['visible'] = $rs->visible;
        $arrout['cm']['groupmode'] = $rs->groupmode;
        $arrout['course']['id'] = $rs->courseid;
        $arrout['course']['fullname'] = $rs->fullname;
        $arrout['course']['shortname'] = $rs->shortname;
        $arrout['course']['category'] = $rs->category;
        $arrout['course']['maxbytes'] = $rs->maxbytes;
        $arrout['course']['groupmodeforce'] = $rs->groupmodeforce;
        $arrout['course']['lang'] = $rs->lang;
        $arrout['course']['guest'] = $rs->guest;
        $arrout['course']['student'] = $rs->student;
        $arrout['course']['visible'] = $rs->visible;
        $arrout['mod']['id'] = $rs->moduleid;
        $arrout['mod']['name'] = $rs->name;
        $arrout['mod']['intro'] = $rs->intro;
        $arrout['mod']['timecreated'] = $rs->timecreated;
        $arrout['mod']['timemodified'] = $rs->timemodified;
        $arrout['mod']['maxsize'] = $rs->maxsize;
        $arrout['mod']['locktime'] = $rs->locktime;
        $arrout['mod']['published'] = $rs->published;
        $arrout['mod']['fullpage'] = $rs->fullpage;
        $arrout['mod']['statuscount'] = $rs->statuscount;
        $arrout['mod']['scale'] = $rs->scale;
	$arrout['mod']['titleimage'] = $rs->titleimage;
	$arrout['mod']['netpublishtheme'] = $rs->netpublishtheme;
	$arrout['mod']['title'] = $rs->title;
	$arrout['mod']['frontpagecolumns'] = $rs->frontpagecolumns;

        if (intval($CFG->version) >= 2005041900) {
            $arrout['course']['theme'] = $rs->theme;
        }
    }

    unset($recordset);

    return $arrout;

}

// thanks to : http://www.laughing-buddha.net/jon/php/dirlist/
function netpublish_get_themes () {
    global $CFG;

    // themes directory
    $directory = $CFG->dirroot."/mod/netpublish/themes";

    // create an array to hold directory list
    $results = array();

    // create a handler for the directory
    $handler = opendir($directory);

    // keep going until all files in directory have been read
    while ($file = readdir($handler)) {

        // if $file isn't this directory or its parent,
        // add it to the results array
        if ($file != '.' && $file != '..')
            if (strpos($file,'php') > 0) $results[] = $file;
    }

    // tidy up: close the handler
    closedir($handler);

    // done!
    return $results;

}

function netpublish_get_authors ($id) {

    global $CFG;

    if (empty($id)) {
        return;
    }

    $authors = get_records_sql("SELECT id, firstname, lastname
                               FROM {$CFG->prefix}user
                               WHERE id IN ($id)");

    return $authors;

}

function netpublish_get_images ($course=0) {

    if (!is_integer($course)) {
        return false;
    }

    if (!empty($course)) {
        $select = 'course = '. $course;
    }

    $fields = 'id, name, width, height, size, timemodified, owner';
    $sort   = 'timemodified DESC';

    return get_records_select("netpublish_images", $select, $sort, $fields);

}

function netpublish_get_image ($id) {

    $id = intval($id);

    if (empty($id)) {
        return false;
    }

    $select = 'id = '. $id;

    return get_record_select("netpublish_images", $select);

}

function netpublish_delete_image ($image) {

    global $CFG;

    if (!is_object($image)) {
        return false;
    }

    if (empty($image->id)   or
        empty($image->name) or
        empty($image->path)) {
        return false;
    }

    $select = 'id = '. $image->id;

    if (!delete_records_select("netpublish_images", $select)) {
        return false;
    }

    $realpath = $CFG->dataroot . '/'. $image->path;

    if (!@unlink($realpath)) {
        return false;
    }

    return true;

}

function netpublish_get_lock ($pageid, $userid) {
/// Get page lock if any
/// Returns false if page isn't locked and
/// returns lockstart time and full username
/// as an object if page is locked.

    global $CFG;

    $select  = 'pageid = $pageid';
    $rs = get_record_sql("SELECT L.lockstart, L.userid, U.firstname, U.lastname
                          FROM {$CFG->prefix}netpublish_lock AS L, {$CFG->prefix}user AS U
                          WHERE U.id = L.userid
                          AND pageid = $pageid");

    if (empty($rs)) {
        return false;
    } else {
        // Is locked by owner so allow to open
        if (intval($rs->userid) == intval($userid)) {
            return false;
        }

        $return = new stdClass;
        $return->username = fullname($rs);
        $return->lockstart = $rs->lockstart;
        unset($rs);

    }

    return $return;

}

function netpublish_set_lock ($pageid, $userid, $publishid) {
/// Sets the editing lock for page.

    $data = new stdClass;
    $data->userid    = intval($userid);
    $data->pageid    = intval($pageid);
    $data->publishid = intval($publishid);
    $data->lockstart = time();

    if ($id = get_field("netpublish_lock", "id", "pageid", $data->pageid)) {
        $data->id        = $id;
        if (update_record("netpublish_lock", $data)) {
            return true;
        }
    } else {
        if (insert_record("netpublish_lock", $data)) {
            return true;
        }
    }

    return false;

}

function netpublish_unset_lock ($pageid) {
/// Delete editing lock for one page

    return delete_records("netpublish_lock", "pageid", $pageid);

}

function netpublish_unset_locks ($instance, $locktime=0, $return=false) {
/// Delete locks where locktimes are due.

    $timenow = time();
    $select = '';
    if (!empty($locktime)) {
        $select  = "(lockstart + $locktime) <= $timenow AND ";
    }

    $select .= "publishid = $instance";
    if (! $return) {
        delete_records_select("netpublish_lock", $select);
    } else {
        if (! delete_records_select("netpublish_lock", $select)) {
            return false;
        }
        return true;
    }
}

// Functions for outside publish
function netpublish_get_all_published_instances () {

    $select = 'published = 1';
    return get_records_select("netpublish", $select, 'timemodified');

}

function netpublish_get_published_record ($netpublishid) {

    global $CFG;

    $rs = get_records_sql("SELECT cm.id, cm.module, cm.instance, cm.visible, cm.groupmode, c.id AS courseid,
                           c.fullname, c.shortname, c.category, c.maxbytes, c.groupmodeforce,
                           c.lang, c.guest, m.id AS moduleid,
                           m.name, m.intro, m.timecreated, m.timemodified, m.maxsize, m.locktime, m.fullpage
                           FROM {$CFG->prefix}course_modules AS cm,
                           {$CFG->prefix}course AS c,
                           {$CFG->prefix}modules AS md,
                           {$CFG->prefix}netpublish AS m
                           WHERE c.id = m.course
                           AND cm.course = m.course
                           AND md.name = 'netpublish'
                           AND md.id = cm.module
                           AND cm.instance = $netpublishid
                           AND m.id = $netpublishid");

    // return first and only object in array
    return $rs[key($rs)];

}

function netpublish_get_excluded_sections ($id, $publishid) {
/// Get excluded sections for deleting sections and
/// moving articles to a new section.

    if (empty($id) or empty($publishid)) {
        return;
    }

    $id        = clean_param($id,        PARAM_INT);
    $publishid = clean_param($publishid, PARAM_INT);

    $select  = "(id = $id OR parentid = $id) ";
    $select .= "AND publishid = $publishid";
    $rs = get_records_select("netpublish_sections", $select,
                             "", "id, fullname");

    if (is_array($rs)) {
        $returnarray = array();
        foreach ($rs as $objrs) {
            $returnarray[] = $objrs->id;
        }
    }

    return !empty($returnarray) ? $returnarray : '';
}
?>