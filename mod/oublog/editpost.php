<?php
/**
 * This page allows a user to add and edit blog posts
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

    require_once("../../config.php");
    require_once("locallib.php");
    require_once('post_form.php');

    if(class_exists('ouflags')) {
	    require_once('../../local/mobile/ou_lib.php');
	    
	    global $OUMOBILESUPPORT;
	    $OUMOBILESUPPORT = true;
	    ou_set_is_mobile(ou_get_is_mobile_from_cookies());
    }
        
    $blog = required_param('blog', PARAM_INT);        // Blog ID
    $postid = optional_param('post', 0, PARAM_INT);   // Post ID for editing
    
    if ($blog) {
        if (!$oublog = get_record("oublog", "id", $blog)) {
            error('Blog parameter is incorrect');
        }
        if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
            error('Course module ID was incorrect');
        }
        if (!$course = get_record("course", "id", $oublog->course)) {
            error("Course is misconfigured");
        }
    }
    if ($postid) {
        if (!$post = get_record('oublog_posts', 'id', $postid)) {
            error('Invalid parameter');
        }
        if (!$oubloginstance = get_record('oublog_instances', 'id', $post->oubloginstancesid)) {
            error('Blog instance not found');
        }
    }

/// Check security
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    oublog_check_view_permissions($oublog, $context, $cm);

    if ($oublog->global) {
        $blogtype = 'personal';
        
        // New posts point to current user
        if(!isset($oubloginstance)) {
            $oubloguser = $USER;
            if (!$oubloginstance = get_record('oublog_instances', 'oublogid', $oublog->id, 'userid', $USER->id)) {
                error('Blog instance not found');
            }
        } else {
            $oubloguser = get_record('user','id',$oubloginstance->userid);
        }
        $viewurl = 'view.php?user='.$oubloguser->id;
    } else {
        $blogtype = 'course';
        $viewurl = 'view.php?id='.$cm->id;
    }

    // If editing a post, must be your post or you have manageposts
    $canmanage=has_capability('mod/oublog:manageposts', $context);
    if (isset($post) && $USER->id != $oubloginstance->userid && !$canmanage) {
        print_error('accessdenied','oublog');
    }

    // Must be able to post in order to post OR edit a post. This is so that if 
    // somebody is blocked from posting, they can't just edit an existing post.
    // Exception is that admin is allowed to edit posts even though they aren't
    // allowed to post to the blog.
    if(!(
        oublog_can_post($oublog,isset($oubloginstance) ? $oubloginstance->userid : 0,$cm) ||
        (isset($post) && $canmanage))) {
        print_error('accessdenied','oublog');
    }

/// Get strings
    $stroublogs  = get_string('modulenameplural', 'oublog');
    $stroublog   = get_string('modulename', 'oublog');
    $straddpost  = get_string('newpost', 'oublog');
    $streditpost = get_string('editpost', 'oublog');


/// Set-up groups
    $currentgroup = oublog_get_activity_group($cm, true);
    $groupmode = oublog_get_activity_groupmode($cm, $course);
    if($groupmode==VISIBLEGROUPS && !groups_is_member($currentgroup) && !$oublog->individual) {
        require_capability('moodle/site:accessallgroups',$context);
    } 

    $mform = new mod_oublog_post_form('editpost.php', array(
        'individual' => $oublog->individual,
        'maxvisibility' => $oublog->maxvisibility,
        'allowcomments' => $oublog->allowcomments,
        'edit' => !empty($postid),
        'personal' => $oublog->global));

    if ($mform->is_cancelled()) {
        redirect($viewurl);
        exit;
    }

    if (!$frmpost = $mform->get_data()) {

        if ($postid) {
            $post->post  = $post->id;
            $post->general = $streditpost;
            $post->tags = oublog_get_tags_csv($post->id);
        } else {
            $post = new stdClass;
            $post->general = $straddpost;
        }

        $post->blog = $oublog->id;
        $mform->set_data($post);


    /// Print the header
        if (class_exists('ouflags') && ou_get_is_mobile()){
            ou_mobile_configure_theme();
        }
        
        if ($blogtype == 'personal') {

            $navlinks = array();
            $navlinks[] = array('name' => fullname($oubloguser), 'link' => "../../user/view.php?id=$oubloguser->id", 'type' => 'misc');
            $navlinks[] = array('name' => format_string($oubloginstance->name), 'link' => $viewurl, 'type' => 'activityinstance');
            $navlinks[] = array('name' => $post->general, 'link' => '', 'type' => 'misc');

            $navigation = build_navigation($navlinks);
            print_header_simple(format_string($oublog->name), "", $navigation, "", "", true);

        } else {
            $navlinks = array();
            $navlinks[] = array('name' => $post->general, 'link' => '', 'type' => 'misc');
            $navigation = build_navigation($navlinks, $cm);

            print_header_simple(format_string($oublog->name), "", $navigation, "", "", true,
                          update_module_button($cm->id, $course->id, $stroublog), navmenu($course, $cm));
        }

        $mform->display();

        print_footer();

    } else {

        $post = $frmpost;
    /// Handle form submission
        if (!empty($post->post)) {
            // update the post
            $post->id = $post->post;
            $post->oublogid = $oublog->id;
            $post->userid = $oubloginstance->userid;

            oublog_edit_post($post,$cm);
            add_to_log($course->id, "oublog", "edit post", $viewurl, $oublog->id, $cm->id);
            redirect($viewurl);

        } else {
            if(class_exists('ouflags')) {
                $DASHBOARD_COUNTER=DASHBOARD_BLOG_POST;
            }
            
            // insert the post
            unset($post->id);
            $post->oublogid = $oublog->id;
            $post->userid = $USER->id;

            //consider groups only when it is not an individual blog
            if ($oublog->individual) {
                $post->groupid = 0;
            } else {
                if(!$currentgroup && $groupmode) {
                    error("Can't add post with no group");
                }
                $post->groupid = $currentgroup;
            }

            if (!oublog_add_post($post,$cm,$oublog,$course)) {
                error('Could not add post');
            }
            add_to_log($course->id, "oublog", "add post", $viewurl, $oublog->id, $cm->id);
            redirect($viewurl);
        }

    }

?>