<?php
require_once('../../config.php');
require_once($CFG->libdir . '/uploadlib.php');
require_once('forum.php');

// Script to add attachments to a form
$cmid = required_param('id', PARAM_INT);
$postid = optional_param('p', 0, PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try
{
    // Security check
    if ($postid) {
        $post = forum_post::get_from_id($postid, $cloneid);
        $post->require_view();
        $forum = $post->get_forum();
    } else {
        $forum = forum::get_from_cmid($cmid, $cloneid);
        $forum->require_view(forum::NO_GROUPS);
    }

    print_header();
    $um = $forum->get_upload_manager('file');
    $um->config->allownull = false;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $playspaceid = required_param('attachmentplayspace', PARAM_SEQUENCE);
        $ok = $um->preprocess_files();
        if ($ok && $name = $um->get_new_filename()) {
            $um->save_files(
                forum::get_attachment_playspace_folder($playspaceid));
             ?><script type="text/javascript">
window.opener.currentform.addattachment("<?php print addslashes_js($name); ?>");
window.close();
</script><?php
            print_footer('empty');
            exit;
        } else {
            print $um->get_errors();
        }
    }

    $playspaceid = optional_param('attachmentplayspace', 0, PARAM_SEQUENCE);
    if (!$playspaceid) {
        $playspaceid = forum::create_attachment_playspace(
            $postid ? $post : null); ?><script type="text/javascript">
window.opener.currentform.attachmentplayspace.value='<?php print $playspaceid; ?>';
</script><?php
    }

    $max = get_string('maxsize', '', display_size($um->config->maxbytes));
?><form action="addattachment.php" method="post" accept-charset="utf-8"
    enctype="multipart/form-data"><div class="forumng-addattachment-file"><?php 
?><h1><?php print_string('choosefile', 'forumng'); ?></h1>
<?php
print $forum->get_link_params(forum::PARAM_FORM);
?>
<input type="hidden" name="attachmentplayspace" value="<?php print $playspaceid; ?>" />
<?php if ($postid) { ?>
<input type="hidden" name="p" value="<?php print $postid; ?>" />
<?php } ?>
<input type="file" name="file" />
</div><div class="forumng-addattachment-submit">
<h1><?php print_string('clicktoadd', 'forumng'); ?></h1>
<div class="forumng-addatttachment-buttons">
<input type="submit" value="&nbsp;&nbsp;<?php print_string('add'); ?>&nbsp;&nbsp;" />
<input type="button" value="<?php print_string('cancel'); ?>" onclick="window.close()" />
</div>
</div>
<p class="forumng-addattachment-max"><?php print $max; ?></p>
</form>
<?php

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
print_footer('empty');
?>