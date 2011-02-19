<?php  // $Id: view.php,v 1.3 2007/04/27 09:10:51 janne Exp $

/// This page prints a particular instance of netpublish
/// (Replace netpublish with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    // Paranoia check B-}
    if (!empty($_GET['section'])) {
        if (!netpublish_is_intval($_GET['section'])) {
            error("Passed variable isn't integer!");
        }
    }

    if (!empty($_GET['article'])) {
        if (!netpublish_is_intval($_GET['article'])) {
            error("Passed variable isn't integer!");
        }
    }

    $id      = required_param('id',         PARAM_INT);     // Course Module ID, or
    $a       = optional_param('a',       0, PARAM_INT);     // netpublish ID
    $section = optional_param('section', 0, PARAM_INT);     // section id
    $article = optional_param('article', 0, PARAM_INT);     // article id
	$printfriendly = optional_param('printfriendly', 0, PARAM_INT);     // Print Friendly Page view

    if ($id) {
        // Get all that I need using only one query
        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect");
        }

        // Construct objects used in Moodle
        netpublish_set_std_classes ($cm, $course, $mod, $info);
        unset($info);

    } else {

        if (! $mod = get_record("netpublish", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $mod->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("netpublish", $mod->id, $course->id)) {
            error("Course Module ID was incorrect");
        }

    }

    require_login($course->id);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $isteacher = has_capability('moodle/legacy:teacher', get_context_instance(CONTEXT_COURSE, $course->id));
    $isguest   = has_capability('moodle/legacy:guest',
                                 get_context_instance(CONTEXT_SYSTEM, SITEID),
                                 $USER->id, false);

    $npsections = netpublish_count_sections($mod->id);

    if (empty($npsections)) {
        if ($isteacher) {
            netpublish_create_first_section($mod->id);
            redirect("sections.php?id=$cm->id");
        } else {
            netpublish_create_first_section($mod->id);
        }
    }

    if (!empty($article)) {
        $strtolog = get_string("articleview","netpublish");
        add_to_log($course->id, "netpublish", $strtolog, "view.php?id=$cm->id&section=$section&article=$article", "$mod->name");
    } else {
        add_to_log($course->id, "netpublish", "view", "view.php?id=$cm->id", "$mod->name");
    }

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    // Get strings
    $strnetpublishes = get_string("modulenameplural", "netpublish");
    $strnetpublish   = get_string("modulename", "netpublish");
    $strcreated         = get_string("created","netpublish");
    $strmodified        = get_string("modified","netpublish");
    $strpublished       = get_string("published","netpublish");
    $strauthor          = get_string("by","netpublish");
    $strreadmore        = get_string("readmore","netpublish");

	if ($printfriendly) {
		print_header();//"$course->shortname: $mod->name", "$course->fullname");
	} else {
		print_header("$course->shortname: $mod->name", "$course->fullname",
					"$navigation <a href=\"index.php?id=$course->id\">$strnetpublishes</a> -> $mod->name",
					"", "", true, update_module_button($cm->id, $course->id, $strnetpublish));
	}

/// Print the main part of the page

    // Get number of pending articles
    $pending        = netpublish_count_pending($mod->id);
    $canbepublished = false;

    if (@file_exists($CFG->dirroot .'/netpublish/index.php')) {
        $canbepublished = true;
    }

    if ( has_capability('mod/netpublish:addarticle', $context) and !$printfriendly ) {
        //include_once('editnavi.php');
        include_once('tabs_edit.php');
    }

    // The code here

    include_once('themes/'.$mod->netpublishtheme);

	if (!empty($mod->titleimage)) {
		$magtheme['headerimage'] = $mod->titleimage;
	}

    // Main Magazine FrontPage HEADER image
    if (!empty($magtheme['headerimage'])) {
      echo "<div class=\"magazineheader\" ><img src=\"".$magtheme['headerimage']."\" ><div class=\"netpublish-title\">$mod->title</div></div>";
    }
    //if ($magtheme['frontpagecolums'] == 1 )
    if ($mod->frontpagecolumns == 1)
      echo "<style>.netpublish-article {padding:5px 40px 5px 5px; width:660px;} </style>";
    //if ($magtheme['frontpagecolums'] == 2 )
    if ($mod->frontpagecolumns == 2)
      echo "<style>.netpublish-article {padding: 5px; width:350px;}  </style>";

    // get articles of section 0;
    $articles = netpublish_get_articles($section, $cm->instance, $mod->fullpage);

    $strnoarticles = '';
    if (empty($articles)) {
        $strnoarticles = '<p style="text-align: center; font-weight: bold;">'. get_string("noarticles","netpublish") .'</p>';
    }
    print_simple_box_start('','100%');
    echo "<style>.box {width:100%; background-image: url(".$magtheme['backgroundimage'].");} </style>";
    include_once('view.html.php');
    print_simple_box_end();

/// Finish the page
    print_footer($course);

?>
