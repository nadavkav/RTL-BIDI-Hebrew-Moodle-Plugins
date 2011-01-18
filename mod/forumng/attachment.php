<?php
require_once('../../config.php');
require_once('forum.php');
require_once($CFG->libdir . '/filelib.php');

$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$file = required_param('file', PARAM_FILE);

try {
    // Get post and do security checks
    $post = forum_post::get_from_id($postid, $cloneid);
    $post->require_view();

    // Check file exists
    $folder = $post->get_attachment_folder();
    $path = "$folder/$file";
    if (!file_exists($path)) {
        print_error('filenotfound');
    }

    // Send file
    send_file($path, $file, 'default', 0, false, true);
} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>