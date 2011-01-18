<?php
// Email forwarding script. This uses the post selector infrastructure to 
// handle the situation when posts are being selected.
require_once('../post_selector.php');
require_once('forward_form.php');

class forward_post_selector extends post_selector {
    function get_button_name() {
        return get_string('forward', 'forumng');
    }

    function require_capability($context, $discussion) {
        require_capability('mod/forumng:forwardposts', $context);
    }

    function get_form($discussion, $all, $selected = array()) {
        $customdata = (object)array(
            'subject' => $discussion->get_subject(),
            'discussionid' => $discussion->get_id(),
            'cloneid' => $discussion->get_forum()->get_course_module_id(),
            'postids' => $selected,
            'onlyselected' => !$all);
        return new mod_forumng_forward_form('forward.php', $customdata);
    }

    function apply($discussion, $all, $selected, $formdata) {
        global $COURSE, $USER, $CFG;

        // Begin with standard text
        $a = (object)array('name' => fullname($USER, true));

        $allhtml = "<head>";
        foreach ($CFG->stylesheets as $stylesheet) {
            $allhtml .= '<link rel="stylesheet" type="text/css" href="' .
                $stylesheet . '" />' . "\n";
        }
        $allhtml .= "</head>\n<body id='forumng-email'>\n";

        $preface = get_string('forward_preface', 'forumng', $a);
        $allhtml .= $preface;
        $alltext = format_text_email($preface, FORMAT_HTML);

        // Include intro if specified
        if (!preg_match('~^(<br[^>]*>|<p>|</p>|\s)*$~', $formdata->message)) {
            $alltext .= "\n" . forum_cron::EMAIL_DIVIDER . "\n";
            $allhtml .= '<hr size="1" noshade="noshade" />';

            // Add intro
            $message = trusttext_strip(stripslashes($formdata->message));
            $allhtml .= format_text($message, $formdata->format);
            $alltext .= format_text_email($message, $formdata->format);
        }

        // Get list of all post ids in discussion order
        $alltext .= "\n" . forum_cron::EMAIL_DIVIDER . "\n";
        $allhtml .= '<hr size="1" noshade="noshade" />';
        $poststext = '';
        $postshtml = '';
        $discussion->build_selected_posts_email(
            $selected, $poststext, $postshtml);
        $alltext .= $poststext;
        $allhtml .= $postshtml . '</body>';

        $emails = preg_split('~[; ]+~', $formdata->email);
        $subject = stripslashes($formdata->subject);
        foreach ($emails as $email) {
            $fakeuser = (object)array(
                'email' => $email,
                'mailformat' => 1,
                'id' => 0
            );

            $from = $USER;
            $from->maildisplay = 999; // Nasty hack required for OU moodle

            if (!email_to_user($fakeuser, $from, $subject, $alltext, $allhtml)) {
                print_error('error_forwardemail', 'forumng', $formdata->email);
            }
        }

        // Log that it was sent
        $discussion->log('forward discussion', $formdata->email);
        if(!empty($formdata->ccme)) {
            if (!email_to_user($USER, $from, $subject, $alltext, $allhtml)) {
                print_error('error_forwardemail', 'forumng', $USER->email);
            }
        }

        $discussion->print_subpage_header($this->get_page_name());

        print_box(get_string('forward_done', 'forumng'));
        print_continue('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
        print_footer($COURSE);
    }

    function get_content_after_form($discussion, $all, $selected, $formdata) {
        // Print selected messages if they have any (rather than whole
        // discussion)
        if (!$all) {
            // Display selected messages below form
            $allhtml = '';
            $alltext = '';
            $discussion->build_selected_posts_email(
                $selected, $alltext, $allhtml);
            print '<div class="forumng-showemail">' . $allhtml . '</div>';
        } 
    }
}

post_selector::go(new forward_post_selector());
?>