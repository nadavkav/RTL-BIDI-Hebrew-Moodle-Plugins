<?php
require_once('../../config.php');
require_once('forum.php');


if (class_exists('ouflags')) {
	require_once('../../local/mobile/ou_lib.php');
	
	global $OUMOBILESUPPORT;
	$OUMOBILESUPPORT = true;
	ou_set_is_mobile(ou_get_is_mobile_from_cookies());
}

// Get AJAX parameter
$ajax = optional_param('ajax', 0, PARAM_INT);
if (class_exists('ouflags') && $ajax) {
    $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_AJAX;
}

function finish($postid, $cloneid, $url, $fromform, $uploadfolder, $ajaxdata='') {
    // Clear out used playspace and/or uploadfolder
    if (isset($fromform->attachmentplayspace)) {
        // Unless we're keeping it, wipe the playspace
        forum::delete_attachment_playspace($fromform->attachmentplayspace,
            optional_param('keepplayspace', 0, PARAM_INT));
    }

    // Get rid of temporary upload folder
    if ($uploadfolder) {
        remove_dir($uploadfolder);
    }

    global $ajax;
    if ($ajax) {
        if ($ajaxdata) {
            // Print AJAX data if specified
            header('Content-Type: text/plain');
            print $ajaxdata;
            exit;
        } else {
            // Default otherwise is to print post
            forum_post::print_for_ajax_and_exit($postid, $cloneid,
                array(forum_post::OPTION_DISCUSSION_SUBJECT => true));
        }
    }

    redirect($url);
}

function add_attachments_from_draft($draft, $deleteattachments, &$attachments) {
    if ($draft && $deleteattachments!==true) {
        foreach($draft->get_attachment_names() as $name) {
            if(!in_array($name, $deleteattachments)) {
                $attachments[] = $draft->get_attachment_folder() . '/'.
                    $name;
            }
        }
    }
}

try {
    // Get type of action/request and check security
    $isdiscussion = false;
    $isroot = false;
    $ispost = false;
    $edit = false;
    $islock = false;
    $cloneid = optional_param('clone', 0, PARAM_INT);

    // See if this is a draft post
    $draft = null;
    $replytoid = 0;
    $cmid = 0;
    $groupid = 0;
    $forum = null;
    if ($draftid = optional_param('draft', 0, PARAM_INT)) {
        $draft = forum_draft::get_from_id($draftid);

        // Draft post must be for current user!
        if ($draft->get_user_id() != $USER->id) {
            print_error('draft_mismatch', 'forumng');
        }
        if ($draft->is_reply()) {
            $replytoid = $draft->get_parent_post_id();
        } else {
            $forum = forum::get_from_id($draft->get_forum_id(),
                optional_param('clone', 0, PARAM_INT));
            $groupid = $draft->get_group_id();
        }
    }

    if($forum || 
        ($cmid = optional_param('id', 0, PARAM_INT))) {
            // For new discussions, id (forum cmid) and groupid are required (groupid
        // may be forum::ALL_GROUPS if required)
        if ($forum) {
            // Came from draft post
            $cmid = $forum->get_course_module_id();
        } else {
            $forum = forum::get_from_cmid($cmid, $cloneid);
        }
        if ($forum->get_group_mode()) {
            if (!$draft) {
                $groupid = required_param('group', PARAM_INT);
            }
            if ($groupid == 0) {
                $groupid = forum::ALL_GROUPS;
            }
        } else {
            $groupid = forum::NO_GROUPS;
        }

        $post = null;

        // Handles all access security checks
        $forum->require_start_discussion($groupid);

        $isdiscussion = true;
        $isroot = true;
        $ispost = true;
        if ($draftid) {
            $params = array('draft'=>$draftid, 'group'=>$groupid);
        } else {
            $params = array('id'=>$cmid, 'group'=>$groupid);
        }
        $pagename = get_string('addanewdiscussion', 'forumng');

    } else if($replytoid || 
        ($replytoid = optional_param('replyto', 0, PARAM_INT))) {
        // For replies, replyto= (post id of one we're replying to) is required
        $replyto = forum_post::get_from_id($replytoid, $cloneid);
        $discussion = $replyto->get_discussion();
        $forum = $replyto->get_forum();

        // Handles all access security checks
        $replyto->require_reply();

        $ispost = true;
        if ($draftid) {
            $params = array('draft'=>$draftid);
        } else {
            $params = array('replyto'=>$replytoid);
        }
        $pagename = get_string('replytopost', 'forumng',
            $replyto->get_effective_subject(true));
    } else if($lock = optional_param('lock', 0, PARAM_INT)) {
        // For locks, d= discussion id of discussion we're locking
        $discussionid = required_param('d', PARAM_INT);
        $discussion = forum_discussion::get_from_id($discussionid, $cloneid);
        $replyto = $discussion->get_root_post();
        $forum = $discussion->get_forum();
        $discussion->require_edit();
        if ($discussion->is_locked()) {
            print_error('edit_locked', 'forumng');
        }

        $ispost = true;
        $islock = true;
        $params = array('d'=>$discussionid, 'lock'=>1);
        $pagename = get_string('lockdiscussion', 'forumng',
            $replyto->get_effective_subject(false));
    } else if($discussionid = optional_param('d', 0, PARAM_INT)) {
        // To edit discussion settings only (not the standard post settings
        // such as subject, which everyone can edit), use d (discussion id)
        $discussion = forum_discussion::get_from_id($discussionid, $cloneid);
        $post = $discussion->get_root_post();
        $forum = $discussion->get_forum();
        $discussion->require_edit();

        $isdiscussion = true;
        $edit = true;
        $params = array('d'=>$discussionid);
        $pagename = get_string('editdiscussionoptions', 'forumng',
            $post->get_effective_subject(false));
    } else  {
        // To edit existing posts, p (forum post id) is required
        $postid = required_param('p', PARAM_INT);
        $post = forum_post::get_from_id($postid, $cloneid);
        $discussion = $post->get_discussion();
        $forum = $post->get_forum();

        // Handles all access security checks
        $post->require_edit();

        $isroot = $post->is_root_post();
        $ispost = true;
        $edit = true;
        $params = array('p'=>$postid);
        $pagename = get_string('editpost', 'forumng',
            $post->get_effective_subject(true));
    }
    
    // Get other useful variables (convenience)
    $course = $forum->get_course();
    $cm = $forum->get_course_module();

    // See if this is a save action or a form view
    require_once('editpost_form.php');
    if ($cloneid) {
        // Clone parameter is required for all actions
        $params['clone'] = $cloneid;
    }
    $mform = new mod_forumng_editpost_form('editpost.php',
        array('params'=>$params, 'isdiscussion'=>$isdiscussion,
            'forum'=>$forum, 'edit'=>$edit, 'ispost'=>$ispost, 'islock'=>$islock,
            'post'=>isset($post) ? $post : null, 'isroot'=>$isroot,
            'ajaxversion'=>$ajax ? true : false,
            'timelimit' => $ispost && $edit && !$post->can_ignore_edit_time_limit() 
                ? $post->get_edit_time_limit() : 0,
            'draft'=>$draft));

    if ($mform->is_cancelled()) {
        if ($edit) {
            redirect('discuss.php?' . $post->get_discussion()->get_link_params(forum::PARAM_PLAIN));
        } else if ($islock) {
            redirect('discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
        } else {
            redirect('view.php?' . $forum->get_link_params(forum::PARAM_PLAIN));
        }
    } else if ($fromform = $mform->get_data()) {
        if (class_exists('ouflags') && $ispost) {
            // Any edit of a post counts as 'post' (including AJAX ones and
            // draft saves)
            $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_POST;
        }

        // Default is that no upload folder has been used
        $uploadfolder = false;

        // Set up values which might not be defined
        if ($ispost) {
            // Blank subject counts as null
            if (trim($fromform->subject)==='') {
                $fromform->subject = null;
            }

            if (!isset($fromform->mailnow)) {
                $fromform->mailnow = false;
            }

            if (!isset($fromform->setimportant)) {
                $fromform->setimportant = false;
            }
            
            if (!isset($fromform->format)) {
                $fromform->format = 0;
            }
            
            $attachments = array();

            if (!empty($fromform->attachmentplayspace)) {
                $deleteattachments = true;
                $attachments = forum::get_attachment_playspace_files(
                    $fromform->attachmentplayspace, 
                    optional_param('keepplayspace', 0, PARAM_INT));
            } else {
                // Attachments are saved initially into a temp folder, then
                // moved into place
                $uploadfolder = $CFG->dataroot . '/moddata/forumng/uploads/' .
                    $USER->id . ',' .  mt_rand();
                $mform->save_files($uploadfolder);
                if(is_dir($uploadfolder)) {
                    $handle = opendir($uploadfolder);
                    while (false!==($item = readdir($handle))) {
                        if($item != '.' && $item != '..') {
                            $attachments[] = $uploadfolder . '/' . $item;
                        }
                    }
                    closedir($handle);
                }
                
                // Get list of attachments to delete
                $deleteattachments = array_key_exists('deletefile', $_POST) ?
                    $_POST['deletefile'] : array();
            }
        }
        if ($isdiscussion) {
            if (!isset($fromform->timestart)) {
                $fromform->timestart = 0;
            }
            if (!isset($fromform->timeend)) {
                $fromform->timeend = 0;
            }
            if (!isset($fromform->sticky)) {
                $fromform->sticky = false;
            }
            // The form time is midnight, but because we want it to be
            // inclusive, set it to 23:59:59 on that day.
            if ($fromform->timeend) {
                $fromform->timeend = strtotime('23:59:59', $fromform->timeend);
            }
        }

        $savedraft = isset($fromform->savedraft);
        if ($savedraft) {
            $options = new stdClass;
            if(isset($fromform->timestart)) {
                $options->timestart = $fromform->timestart;
            }
            if(isset($fromform->timeend)) {
                $options->timeend = $fromform->timeend;
            }
            if(isset($fromform->sticky)) {
                $options->sticky = $fromform->sticky;
            }
            if(isset($fromform->mailnow)) {
                $options->mailnow = $fromform->mailnow;
            }
            if (isset($fromform->setimportant)) {
                $options->setimportant = $fromform->setimportant;
            }
            $date = get_string('draftexists', 'forumng', 
                forum_utils::display_date(time()));
            if ($draft) {
                // Update existing draft
                $draft->update(
                    stripslashes($fromform->subject), 
                    stripslashes($fromform->message), $fromform->format,
                    $deleteattachments, $attachments, 
                    $isdiscussion && $fromform->group ? $fromform->group : null, $options);

                // Redirect to edit it again
                finish(0, $cloneid, 'editpost.php?draft=' . $draft->get_id(), $fromform,
                    $uploadfolder, $draft->get_id() . ':' . $date);
            } else {
                // Save new draft
                $newdraftid = forum_draft::save_new(
                    $forum,
                    $isdiscussion ? $groupid : null,
                    $replytoid ? $replytoid : null,
                    stripslashes($fromform->subject), 
                    stripslashes($fromform->message), 
                    $fromform->format, $attachments, $options);

                // Redirect to edit it again
                finish(0, $cloneid, 'editpost.php?draft=' . $newdraftid, $fromform,
                    $uploadfolder, $newdraftid . ':' . $date);
            }
        } else if (!$edit) {

            // Check the random number is unique in session
            $random = optional_param('random', 0, PARAM_INT);
            if ($random) {
                if (!isset($SESSION->forumng_createdrandoms)) {
                    $SESSION->forumng_createdrandoms = array();
                }
                $now = time();
                foreach ($SESSION->forumng_createdrandoms as $r=>$then) {
                    // Since this is meant to stop you clicking twice quickly,
                    // expire anything older than 1 minute
                    if ($then < $now - 60) {
                        unset($SESSION->forumng_createdrandoms[$r]);
                    }
                }
                if (isset($SESSION->forumng_createdrandoms[$random])) {
                    print_error('error_duplicate', 'forumng',
                            $forum->get_url(forum::PARAM_PLAIN));
                }
                $SESSION->forumng_createdrandoms[$random] = $now;
            }

            // Creating new
            if ($isdiscussion) {
                add_attachments_from_draft($draft, $deleteattachments, 
                    $attachments);

                // Create new discussion
                list($discussionid, $postid) =
                    $forum->create_discussion($groupid,
                        stripslashes($fromform->subject),
                        stripslashes($fromform->message), $fromform->format, $attachments,
                        !empty($fromform->mailnow), $fromform->timestart,
                        $fromform->timeend, false, $fromform->sticky);

                // If there's a draft, get rid of it
                if ($draft) {
                    $draft->delete();
                }

                // Redirect to view discussion
                finish($postid, $cloneid, 'discuss.php?d=' . $discussionid .
                        $forum->get_clone_param(forum::PARAM_PLAIN), $fromform,
                        $uploadfolder);
            } else if ($islock) {
                $postid = $discussion->lock(stripslashes($fromform->subject),
                    stripslashes($fromform->message),
                    $fromform->format, $attachments, !empty($fromform->mailnow));

                // Redirect to view discussion
                finish($postid, $cloneid,
                    'discuss.php?' . $replyto->get_discussion()->get_link_params(forum::PARAM_PLAIN),
                    $fromform, $uploadfolder);
            } else {
                add_attachments_from_draft($draft, $deleteattachments, 
                    $attachments);

                // Create a new reply
                $postid = $replyto->reply(stripslashes($fromform->subject),
                    stripslashes($fromform->message),
                    $fromform->format, $attachments, !empty($fromform->setimportant), !empty($fromform->mailnow));

                // If there's a draft, get rid of it
                if ($draft) {
                    $draft->delete();
                }

                // Redirect to view discussion
                finish($postid, $cloneid, 'discuss.php?' .
                    $replyto->get_discussion()->get_link_params(forum::PARAM_PLAIN) .
                    '#p' . $postid, $fromform, $uploadfolder);
            }
        } else {
            // Editing

            // Group changes together
            forum_utils::start_transaction();

            // 1. Edit post if applicable
            if ($ispost) {
                $post->edit(stripslashes($fromform->subject),
                    stripslashes($fromform->message),
                    $fromform->format, $deleteattachments, $attachments, !empty($fromform->setimportant),
                    !empty($fromform->mailnow));
            }

            // 2. Edit discussion settings if applicable
            if ($isdiscussion) {
                $discussion = $post->get_discussion();
                $groupid = isset($fromform->group) ? $fromform->group
                    : $discussion->get_group_id();
                $discussion->edit_settings($groupid, $fromform->timestart,
                    $fromform->timeend, $discussion->is_locked(),
                    !empty($fromform->sticky));
            }

            forum_utils::finish_transaction();

            // Redirect to view discussion
            finish($post->get_id(), $cloneid, 'discuss.php?' .
                $post->get_discussion()->get_link_params(forum::PARAM_PLAIN) .
                '#p' . $post->get_id(),
                $fromform, $uploadfolder);
        }

    } else {
        if ($ajax) {
            // If this is an AJAX request we can't go printing the form, this
            // must be an error
            header('Content-Type: text/plain', true, 500);
            print 'Form redisplay attempt';
            exit;
        }
        $navigation = array();

        // Include link to discussion except when creating new discussion
        if(!$isdiscussion || $edit) {
            $navigation[] = array(
                'name'=>shorten_text(htmlspecialchars(
                    $discussion->get_subject())),
                'link'=>$discussion->get_url(forum::PARAM_HTML), 'type'=>'forumng');
        }
        $navigation[] = array(
            'name'=>$pagename, 'type'=>'forumng');

        $buttontext = '';
        
        if(class_exists('ouflags') && ou_get_is_mobile()){
            ou_mobile_configure_theme();
        }

        $PAGEWILLCALLSKIPMAINDESTINATION = true;
        print_header_simple(format_string($forum->get_name()) . ': ' . $pagename,
            "", build_navigation($navigation, $cm), "", "", true,
            $buttontext, navmenu($course, $cm));
        $forum->print_js();

        print skip_main_destination();

        // If replying, print original post here
        if(!$isdiscussion && !$edit && !$islock) {
            print '<div class="forumng-replyto">' .
                $replyto->display(true,
                    array(forum_post::OPTION_NO_COMMANDS=>true,
                        // Hack, otherwise it requires whole-discussion info
                        // Should really have a OPTION_SINGLE_POST which would
                        // have the same effect and be more logical/reusable 
                        forum_post::OPTION_FIRST_UNREAD=>false)) .
                '</div>';
        }
        
        // If draft has been saved, print that here
        if ($draft) {
            print '<div class="forumng-draftexists">'.
                get_string('draftexists', 'forumng', 
                    forum_utils::display_date($draft->get_saved())) . '</div>';
        }

        // Set up initial data
        $initialvalues = new stdClass;
        if ($edit) {
            // Work out initial values for all form fields
            if ($isdiscussion) {
                $initialvalues->timestart = $discussion->get_time_start();
                $initialvalues->timeend = $discussion->get_time_end();
                $initialvalues->sticky = $discussion->is_sticky() ? 1 : 0;
                $initialvalues->groupid = $discussion->get_group_id();
            }
            $initialvalues->subject = $post->get_subject();
            $initialvalues->message = $post->get_message();
            $initialvalues->format = $post->get_format();
            $initialvalues->setimportant = $post->is_important();
        }
        if ($draft) {
            $initialvalues->subject = $draft->get_subject();
            $initialvalues->message = $draft->get_message();
            $initialvalues->format = $draft->get_format();
            if ($isdiscussion) {
                $initialvalues->groupid = $draft->get_group_id();
            }
            if ($options = $draft->get_options()) {
                if (isset($options->timestart)) {
                    $initialvalues->timestart = $options->timestart;
                }
                if (isset($options->timeend)) {
                    $initialvalues->timeend = $options->timeend;
                }
                if (isset($options->sticky)) {
                    $initialvalues->sticky = $options->sticky;
                }
                if (isset($options->mailnow)) {
                    $initialvalues->mailnow = $options->mailnow;
                }
                if (isset($options->setimportant)) {
                    $initialvalues->setimportant = $options->setimportant;
                }
            }
        }
        if ($edit || $draft) {
            $mform->set_data($initialvalues);
        }

        // Print form
        $mform->display();

        // Display footer
        print_footer($course);
    }

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>