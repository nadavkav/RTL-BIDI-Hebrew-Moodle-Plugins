<?php // $Id: sections.php,v 1.2 2007/04/27 09:10:51 janne Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id  = required_param('id',           PARAM_INT);     // module id
    $a   = optional_param('a',         0, PARAM_INT);     // module id

    if ($id) {
        // Get all that I need using only one query
        // id              => Orig $cm
        // module          => Orig $cm
        // instance        => Orig $cm
        // visible         => Orig $cm
        // groupmode       => Orig $cm
        // courseid        => Orig $course
        // fullname        => Orig $course
        // shortname       => Orig $course
        // category        => Orig $course
        // maxbytes        => Orig $course
        // groupmodeforce  => Orig $course
        // lang            => Orig $course
        // guest           => Orig $course
        // moduleid        => Orig $netpublish
        // name            => Orig $netpublish
        // intro           => Orig $netpublish
        // timecreated     => Orig $netpublish
        // timemodified    => Orig $netpublish

        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect");
        }

    } else {
        // Get all that I need using only one query
        if (! $cm = netpublish_get_record($a) ) {
            error("Course Module ID was incorrect");
        }
    }

    // Construct objects used in Moodle
    netpublish_set_std_classes ($cm, $course, $mod, $info);
    unset($info);

    require_login($course->id);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    //$isteacher = isteacher($course->id);

    if ( !has_capability('mod/netpublish:editsection', $context) ) {
        error(get_string('errorpermissionseditsection','netpublish'),
              $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
    }

    if ($data = data_submitted()) {

        $skey = required_param('sesskey');              // User session key
        $pid  = required_param('publishid', PARAM_INT); // Publish id from POST

        $redirectonerror  = $CFG->wwwroot;
        $redirectonerror .= '/mod/netpublish/sections.php?id='. $cm->id .'&course=' .$course->id;

        if (!confirm_sesskey($skey)) {
            error("Session error!", $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
        }

        if ($pid != $cm->instance) {
            error("You can't edit other instances sections!", $redirectonerror);
        }

        if (!empty($data->remove)) {
            // Remove section
            // TODO:
            // check for articles that are under this section and move
            // them into another nearby section (parent if there are one).
            unset($data->frontpageid); // Empty frontpage id just in case.

            $data->parentid = clean_param($data->parentid, PARAM_INT);
            if (empty($data->confirmed) && empty($data->cancelled) && $data->parentid != 0) {
                $redirecturl  = $CFG->wwwroot;
                $redirecturl .= '/mod/netpublish/confirm.php?';

                $querystring           = array();
                $querystring['id']     = $cm->id;
                $querystring['course'] = $course->id;
                $querystring['publishid'] = $pid;
                $querystring['action'] = "removesection";
                $querystring['pid']    = $data->parentid;
                $querystring = base64_encode(serialize($querystring));

                redirect($redirecturl.$querystring);
            }
            if (!empty($data->confirmed) && empty($data->cancelled)) {

                $newsection = required_param('newsection', PARAM_INT);
                $parentid   = required_param('parentid',   PARAM_INT);
                $publishid  = required_param('publishid',  PARAM_INT);

                $oldsections   = netpublish_get_excluded_sections($parentid, $publishid);
                $strsectionids = implode(",", $oldsections);

                if (!empty($data->movearticles)) {
                    // Move related articles
                    // and delete selected sections

                    $select  = "sectionid IN ($strsectionids) ";
                    $select .= "AND publishid = $publishid";
                    $movearticles = get_records_select("netpublish_articles", $select, "", "id, publishid");

                    if (is_array($movearticles)) {
                        foreach ($movearticles as $movearticle) {
                            set_field("netpublish_articles","sectionid", $newsection,
                                      "id", $movearticle->id);
                        }
                    }

                    if (! delete_records_select("netpublish_sections", "id IN ($strsectionids)")) {
                        error ("Couldn't remove section!", $redirectonerror);
                    }

                    $strnoticemessage = get_string('sectionmovesuccess','netpublish');

                } else {
                    // Delete ralated articles
                    // and delete selected sections
                    $select  = "sectionid IN ($strsectionids) ";
                    $select .= "AND publishid = $publishid";
                    if (! delete_records_select("netpublish_articles", $select)) {
                        error("Couldn't delete sections related articles!",
                               $redirectonerror);
                    }

                    if (! delete_records_select("netpublish_sections","id IN ($strsectionids)")) {
                        error ("Couldn't remove section!", $redirectonerror);
                    }

                    $strnoticemessage = get_string('sectiondeletesuccess','netpublish');
                }
            }

        } else if (!empty($data->edit)) {
            // Edit section
            // Check if is frontpage id set.
            if (empty($data->parentid) && !empty($data->frontpageid)) {
                $data->frontpageid = clean_param($data->frontpageid, PARAM_INT);
                $frontpage = get_record("netpublish_first_section_names", "id", $data->frontpageid);

                if (empty($frontpage)) {
                    error("Couldn't get requested recordset!
                           Unable to rename requested record!",
                           $redirectonerror);
                }

                $data->id   = $frontpage->id;
                $data->name = strip_tags($data->fullname);

                if (! update_record("netpublish_first_section_names", $data)) {
                    error("Update error! Couldn't rename $frontpage->name to $data->fullname !",
                           $redirectonerror);

                }

            } else {
                $data->parentid = clean_param($data->parentid, PARAM_INT);
                $parentid = get_record("netpublish_sections","id", $data->parentid);
                $data->id = $data->parentid;
                $data->parentid = $parentid->parentid;
                $data->fullname = strip_tags($data->fullname);
                unset($parentid);
                if (!update_record("netpublish_sections", $data)) {
                    error("Couldn't update section!", $redirectonerror);
                }
            }
        } else {
            // insert section data
            if (empty($data->fullname)) {
                error("Empty section name!", $redirectonerror);
            }

            $data->id = '';
            $data->sortorder = 0;
            $data->fullname  = strip_tags($data->fullname);

            if (!insert_record("netpublish_sections", $data)) {
                error("Couldn't create new netpublish section!");
            }
        }
    }

    $strpublish        = get_string("modulename","netpublish");
    $strpublishes      = get_string("modulenameplural","netpublish");
    $straddnewordelete = get_string("addnewordelete","netpublish");

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $navigation .= " <a href=\"index.php?id=$course->id\">$strpublishes</a> -> ";
    $navigation .= "<a href=\"view.php?id=$cm->id\">$mod->name</a> -> ";

    print_header("$course->shortname: $mod->name", "$course->fullname",
                 "$navigation $straddnewordelete",
                  "", "", true, update_module_button($cm->id, $course->id, $strpublish));
    print_simple_box_start("center");
    print_heading_with_help($straddnewordelete, "sections", "netpublish");
    if (!empty($strnoticemessage)) {
        notify($strnoticemessage, "black");
    }
    include_once('sections.html');
    print_simple_box_end();
    print_footer($course);

?>