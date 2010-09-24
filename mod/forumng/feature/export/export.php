<?php
// Scripts for exporting to Word. This uses the post selector infrastructure to 
// handle the situation when posts are being selected.
require_once('../post_selector.php');

class export_post_selector extends post_selector {
    function get_button_name() {
        return get_string('exportword', 'forumng');
    }

    function apply($discussion, $all, $selected, $formdata) {
        global $COURSE, $USER, $CFG;

        $a = new stdClass;
        $a->date = userdate(time());
        $a->subject = $discussion->get_subject();
        $title = get_string('exportedtitle', 'forumng', $a);
        $date = date('Ymd-His');
        $allhtml = "<head><title>$title</title>" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $allhtml .= "</head>\n<body>\n";
        $poststext = '';
        $postshtml = '';
        $discussion->build_selected_posts_email($selected, $poststext, $postshtml, false);
        $allhtml .= $postshtml . '</body>';

        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename=forumthread.'. $date .'.doc');

        print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
END;
        print $allhtml;
        print '</body></html>';
    }
}

post_selector::go(new export_post_selector());
?>