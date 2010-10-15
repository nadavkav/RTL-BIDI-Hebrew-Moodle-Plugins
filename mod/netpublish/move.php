<?php // $Id: move.php,v 1.2 2007/04/27 09:10:51 janne Exp $
// used to move articles with in a netpublish or between netpublish instances
//  may be expanded for moving sections and moving sections/articles to other netpublishes

    require('../../config.php');
    include('lib.php');

    $id        = required_param('id', PARAM_INT);
    $articleid = required_param('article', PARAM_INT);
    $skey      = required_param('sesskey');

    if (! $cm = get_record('course_modules', 'id', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $netpublish = get_record('netpublish', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

    confirm_sesskey($skey);

    require_login($course->id);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if ( !has_capability('mod/netpublish:movearticle', $context) ) {
        error(get_string('errorpermissionmove','netpublish'));
    }

    if (!$sections = netpublish_get_sections($netpublish->id)) {
        $sections = array();
    }

    $movingarticle = netpublish_get_article($articleid);

    $strfrontpage = !empty($sections) && !empty($sections[key($sections)]->frontpagename) ?
                     $sections[key($sections)]->frontpagename :
                     ((! $frontpage = get_record("netpublish_first_section_names", "publishid", $netpublish->id)) ?
                     get_string("frontpage","netpublish") : $frontpage->name);

    $sections[0] = new stdClass;
    $sections[0]->id = 0;
    $sections[0]->publishid = $netpublish->id;
    $sections[0]->fullname = $strfrontpage;
    $sections[0]->parentid = 0;
    ksort($sections);

    //print_header();
    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    // Get strings
    $strnetpublishes = get_string("modulenameplural", "netpublish");
    $strnetpublish   = get_string("modulename", "netpublish");
    $strcreated      = get_string("created","netpublish");
    $strmodified     = get_string("modified","netpublish");
    $strpublished    = get_string("published","netpublish");
    $strauthor       = get_string("by","netpublish");
    $strreadmore     = get_string("readmore","netpublish");

    print_header("$course->shortname: $netpublish->name", "$course->fullname",
             "$navigation <a href=\"index.php?id=$course->id\">$strnetpublishes</a> -> $netpublish->name",
              "", "", true, update_module_button($cm->id, $course->id, $strnetpublish));


    if (isset($_GET['order']) and isset($_GET['section'])) {  // if true, we are ready to move the article

        $count   = clean_param($_GET['order'], PARAM_INT);
        $section = clean_param($_GET['section'], PARAM_INT);

        // Get articles in this section
        $temp = array();
        if ($articles = netpublish_get_articles($section, $netpublish->id)) {
            foreach($articles as $article) {
                array_push($temp, $article->id);
            }
        }

        $exists = netpublish_array_search($articleid, $temp);

        if ( isset($exists) && $exists !== false) {
            // Moving page within section.
            $temp = netpublish_change_keys($exists, $count, $temp);

            foreach ($temp as $sortorder => $pageid) {
                set_field("netpublish_articles", "sortorder", $sortorder, "id", $pageid, "sectionid", $section );
            }
        } else {
            // Moving page to another secion or netpublish.
            $temp = netpublish_insert_new($articleid, $count, $temp);

            // First move article to its new home...
            $update = new stdClass;
            $update->id = $articleid;
            $update->publishid = addslashes($netpublish->id);
            $update->sectionid = $section;
            if (! update_record("netpublish_articles", $update) ) {
                error("Select article could not be moved!",
                      "$CFG->wwwroot/mod/netpublish/view.php?id=$cm->id");
                }

            // Update sort order.
            foreach ($temp as $sortorder => $pageid) {
                set_field("netpublish_articles", "sortorder", $sortorder, "id", $pageid);
            }

        }

        // got to here, we are all good to go
        redirect($CFG->wwwroot.'/mod/netpublish/view.php?id='.$cm->id, get_string('movesuccess', 'netpublish'));
    }

    // making tabs
    if (isadmin()) {
        $netpublisheinstances = get_records('netpublish');  // can move to any netpublish in any course
    } else {
        // get all the courses that this teacher teaches
        $teachingcourses = get_records('user_teachers', 'userid', $USER->id);
        // get all the courseids
        $courseids = array();
        foreach ($teachingcourses as $teachingcourse) {
            $courseids[] = $teachingcourse->course;
        }
        // get all netpublishes that are in the courses that this user teaches
        $netpublisheinstances = get_records_select('netpublish', 'course IN('.implode(",", $courseids).')');
    }

    $tabs = array();
    $tabrows = array();
    foreach ($netpublisheinstances as $netpublishinstance) {
        // seems like there should be a more efficient way to do this...
        $netpublishcm = get_coursemodule_from_instance('netpublish', $netpublishinstance->id, $netpublishinstance->course);
        $courseshortname = get_field('course', 'shortname', 'id', $netpublishinstance->course);  // get the short name to distinguish different netpublishes from different courses
        $tabrows[] = new tabobject($netpublishcm->id, 'move.php?id='. $netpublishcm->id .'&amp;article='. $articleid .'&amp;sesskey='. $USER->sesskey, $courseshortname.': '.$netpublishcm->name);
    }
    $tabs[] = $tabrows;

    $table = new stdClass;
    $table->head = array("");
    $table->align = array("left");
    $table->wrap = array("");
    $table->width = "20%";
    $table->size = array("*");
    $table->data = array();

    foreach ($sections as $section) {
        $cnt = 0;
        $table->data[] = array('<strong>'. $section->fullname .'</section>');
        $table->data[] = array(netpublish_print_move_here($cm->id, $articleid, $cnt, $section->id));

        if ($articles = netpublish_get_articles($section->id, $netpublish->id)) {

        foreach ($articles as $article) {
            if ($article->id == $articleid) {
                continue;
            }
                $cnt++;

                $table->data[] = array("&nbsp;&nbsp;&nbsp;&nbsp;" . stripslashes($article->title));
                $table->data[] = array(netpublish_print_move_here($cm->id, $articleid, $cnt, $section->id));
        }
    }

    }

    // all of the printing
    print_heading_with_help(get_string('movingarticle', 'netpublish').": $movingarticle->title", 'movingarticle', 'netpublish');
    print_tabs($tabs, $cm->id);
    echo '<p>';
    print_table($table);
    echo '</p>';
    print_footer($course);

//////////////////////////////// HELP FUNCTIONS ////////////////////////////////

function netpublish_array_search($needle, $haystack) {

    $needle = intval($needle);

    foreach ($haystack as $key => $value) {
        if ($needle === intval($value)) {
            return (int) $key;
            exit;
        }
    }

    return false;

}

function netpublish_change_keys ($current, $wanted, $array) {

    $temp   = array();
    $holder = (int) $array[$current];
    $rows   = count($array);

    // Unset current position.
    unset($array[$current]);

    for ($i = 0; $i < $rows; $i++) {
        if ($i == $wanted) {
            array_push($temp, $holder);
        } else {
            array_push($temp, current($array));
            next($array);
        }
    }
    unset($array);
    return $temp;

}

function netpublish_insert_new ($value, $wanted, $array) {

    $temp = array();
    $rows = count($array) + 1;

    for ($i = 0; $i < $rows; $i++) {
        if ($i == $wanted) {
            array_push($temp, $value);
        } else {
            array_push($temp, current($array));
            next($array);
        }
    }
    unset($array);
    return $temp;

}

?>