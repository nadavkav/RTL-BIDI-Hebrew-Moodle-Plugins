<?php
require_once('../../config.php');
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

// This script is to help with tracking down a bug that occurs on our system
// where attachment folders somehow 'go missing'. It should be deleted once
// we resolve the problem.

$fix = optional_param('fix', 0, PARAM_INT);

$rs = get_recordset_sql("SELECT f.course, fp.discussionid, fp.id AS postid, f.id AS forumid, fp.modified, fp.oldversion, fp.deleted
FROM {$CFG->prefix}forumng_posts fp 
INNER JOIN {$CFG->prefix}forumng_discussions fd ON fp.discussionid = fd.id
INNER JOIN {$CFG->prefix}forumng f ON fd.forumid = f.id
WHERE fp.attachments = 1");

$ok=0; $failed=0;
while($rec = rs_fetch_next_record($rs)) {
    $folder = $CFG->dataroot . '/' . $rec->course . '/moddata/forumng/' . 
        $rec->forumid . '/' . $rec->discussionid . '/'. $rec->postid;

    if(is_dir($folder)) {
        $ok ++;
    } else {
        $info = "";
        if($rec->oldversion) {
            $info .= ' oldversion';
        }
        if($rec->deleted) {
            $info .= ' deleted';
        }
        // Note this is only test code - it does not support shared forums
        print "<div><a href='discuss.php?d={$rec->discussionid}#p{$rec->postid}'><tt>$folder</tt></a> (" . userdate($rec->modified) . "$info)</div>";
        $failed++;

        if($fix) {
            if (!check_dir_exists($folder, true, true)) {
                print "<div>Failed to create folder $folder!</div>";
            }
        }
    }
}

print "<h1>OK $ok Failed $failed</h1>";

print "<div><a href='./missingfolders.php?fix=1'>Create missing folders</a></div>";

rs_close($rs);