<?php
require_once('../../config.php');
require_once('forum.php');

// Script used when deleting attachments from within a post. (Which may or may
// not yet exist! I.e. this can also be used when a reply hasn't been saved
// yet.)

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$file = required_param('file', PARAM_FILE);
$playspaceid = optional_param('attachmentplayspace', 0, PARAM_SEQUENCE);
$postid = optional_param('p', 0, PARAM_INT);

try {
    // Security check
    if ($postid) {
        $post = forum_post::get_from_id($postid, $cloneid);
        $post->require_view();
    } else {
        $forum = forum::get_from_cmid($cmid, $cloneid);
        $forum->require_view(forum::NO_GROUPS);
    }

    if (!$playspaceid) {
        $playspaceid = forum::create_attachment_playspace(
            $postid ? $post : null);
    }

    // Delete the file (if not present, ignore)
    $files = forum::get_attachment_playspace_files($playspaceid, false);
    foreach($files as $existing) {
        if(basename($existing) == $file) {
            forum_utils::unlink($existing);
        }
    }

    // Print out the playspace id in case they don't already have it
    header('Content-Type: text/plain');
    print $playspaceid;
} catch(forum_exception $e) {
    header('Content-Type: text/plain', true, 500);
    print $e->getMessage();
}
?>