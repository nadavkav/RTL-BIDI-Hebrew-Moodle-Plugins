<?php
require_once('forum.php');

@include_once(dirname(__FILE__).'/../../local/xml_backup.php');
if (!class_exists('xml_backup')) {
    require_once(dirname(__FILE__).'/local/xml_backup.php');
}

function forumng_backup_mods($bf,$preferences) {

    global $CFG;

    $status = true;

    // Iterate over module main table
    $mods = get_records ('forumng', 'course', $preferences->backup_course, "id");
    if ($mods) {
        foreach ($mods as $mod) {
            if (backup_mod_selected($preferences, 'forumng', $mod->id)) {
                $status = forumng_backup_one_mod($bf, $preferences, $mod);
            }
        }
    }
    return $status;
}

function forumng_backup_one_mod($bf, $preferences, $forumng) {
    global $CFG;

    try {
        if (is_numeric($forumng)) {
            $forumng = forum_utils::get_record('forumng', 'id', $forumng);
        }

        $xb = new xml_backup($bf, $preferences, 3);
        $xb->tag_start('MOD');

        // Required bits
        $xb->tag_full('ID', $forumng->id);
        $xb->tag_full('MODTYPE', 'forumng');
        $xb->tag_full('NAME', $forumng->name);

        // Backup versioning
        require(dirname(__FILE__) . '/version.php');
        $xb->tag_full('FORUMNG_VERSION', $module->version);

        // Are we doing user data?
        $userdata = backup_userdata_selected(
            $preferences, 'forumng', $forumng->id);

        // ForumNG-specific
        $xb->tag_full_notnull('TYPE', $forumng->type);
        $xb->tag_full_notnull('INTRO', $forumng->intro);
        $xb->tag_full('RATINGSCALE', $forumng->ratingscale);
        $xb->tag_full('RATINGFROM', $forumng->ratingfrom);
        $xb->tag_full('RATINGUNTIL', $forumng->ratinguntil);
        $xb->tag_full('RATINGTHRESHOLD', $forumng->ratingthreshold);
        $xb->tag_full('GRADING', $forumng->grading);
        $xb->tag_full('ATTACHMENTMAXBYTES', $forumng->attachmentmaxbytes);
        $xb->tag_full('REPORTINGEMAIL', $forumng->reportingemail);
        $xb->tag_full('SUBSCRIPTION', $forumng->subscription);
        $xb->tag_full('FEEDTYPE', $forumng->feedtype);
        $xb->tag_full('FEEDITEMS', $forumng->feeditems);
        $xb->tag_full('MAXPOSTSPERIOD', $forumng->maxpostsperiod);
        $xb->tag_full('MAXPOSTSBLOCK', $forumng->maxpostsblock);
        $xb->tag_full('POSTINGFROM', $forumng->postingfrom);
        $xb->tag_full('POSTINGUNTIL', $forumng->postinguntil);
        $xb->tag_full_notnull('TYPEDATA', $forumng->typedata);
        $xb->tag_full('MAGICNUMBER', $forumng->magicnumber);
        $xb->tag_full('COMPLETIONDISCUSSIONS', $forumng->completiondiscussions);
        $xb->tag_full('COMPLETIONREPLIES', $forumng->completionreplies);
        $xb->tag_full('COMPLETIONPOSTS', $forumng->completionposts);
        $xb->tag_full('REMOVEAFTER', $forumng->removeafter);
        $xb->tag_full('REMOVETO', $forumng->removeto);
        $xb->tag_full('SHARED', $forumng->shared);
        // When this is a clone forum, we store the idnumber of the original
        // forum so that it can be found afterward; this makes sense rather
        // than using the normal cmid mapping because it might be on a different
        // course or something, also idnumber is shown in the interface.
        if ($forumng->originalcmid) {
            $idnumber = get_field('course_modules', 'idnumber', 'id',
                $forumng->originalcmid);
            if ($idnumber) {
                $xb->tag_full('ORIGINALCMIDNUMBER', $idnumber);
            }
        }

        // We only back up most content when 'user data' is turned on
        if($userdata) {
            $xb->tag_start('DISCUSSIONS');

            $rs = forum_utils::get_recordset(
                'forumng_discussions', 'forumid', $forumng->id);
            while($discussion = rs_fetch_next_record($rs)) {
                forumng_backup_discussion($xb, $discussion);
            }
            rs_close($rs);

            $xb->tag_end('DISCUSSIONS');

            $xb->tag_start('SUBSCRIPTIONS');

            $rs = forum_utils::get_recordset(
                'forumng_subscriptions', 'forumid', $forumng->id);
            while($subscription = rs_fetch_next_record($rs)) {
                forumng_backup_subscription($xb, $subscription);
            }
            rs_close($rs);

            $xb->tag_end('SUBSCRIPTIONS');

            forumng_backup_files($bf, $preferences, $forumng->id);
        }

        $xb->tag_end('MOD');

        return true;
    } catch(Exception $e) {
        forum_utils::handle_backup_exception($e);
        return false;
    }
}

////Return an array of info (name,value)
function forumng_check_backup_mods($course, $user_data=false,
    $backup_unique_code, $instances=null) {

    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += forumng_check_backup_mods_instances(
                $instance,$backup_unique_code);
        }
        return $info;
    }

    // First the course data
    $info[0][0] = get_string('modulenameplural','forumng');
    $info[0][1] = count_records('forumng', 'course', $course);

    // User-specific data doesn't get listed here
    return $info;
}

////Return an array of info (name,value)
function forumng_check_backup_mods_instances($instance, $backup_unique_code) {
    global $CFG;
    // Name
    $info[$instance->id.'0'][0] = '<b>' . $instance->name . '</b>';
    $info[$instance->id.'0'][1] = '';

    // User data
    if (!empty($instance->userdata)) {
        $info[$instance->id.'1'][0] = get_string("subscriptions", "forumng");
        $info[$instance->id.'1'][1] =
            count_records('forumng_subscriptions', 'forumid', $instance->id);

        // Discussions
        $info[$instance->id.'2'][0] = get_string("discussions", "forumng");
        $info[$instance->id.'2'][1] =
            count_records('forumng_discussions', 'forumid', $instance->id);

        // Posts
        $info[$instance->id.'3'][0] = get_string("posts", "forumng");
        $info[$instance->id.'3'][1] = count_records_sql("
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_posts fp ON fp.discussionid = fd.id
WHERE
    fd.forumid = {$instance->id}");

        // Ratings
        $info[$instance->id.'4'][0] = get_string("ratings", "forumng");
        $info[$instance->id.'4'][1] = count_records_sql("
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_posts fp ON fp.discussionid = fd.id
    INNER JOIN {$CFG->prefix}forumng_ratings fr ON fr.postid = fp.id
WHERE
    fd.forumid = {$instance->id}");

        // Ratings
        $info[$instance->id.'5'][0] = get_string("readdata", "forumng");
        $info[$instance->id.'5'][1] = count_records_sql("
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_read fr ON fr.discussionid = fd.id
WHERE
    fd.forumid = {$instance->id}");
    }

    return $info;
}


//Return a content encoded to support interactivities linking. Every module
//should have its own. They are called automatically from the backup procedure.
function forumng_encode_content_links ($content,$preferences) {

    global $CFG;

    $base = preg_quote($CFG->wwwroot, "/");

    // Link to the list of forums
    $query = "/(".$base."\/mod\/forumng\/index.php\?id\=)([0-9]+)/";
    $result = preg_replace($query, '$@FORUMNGINDEX*$2@$', $content);

    // Link to forum view by moduleid
    $query = "/(".$base."\/mod\/forumng\/view.php\?id\=)([0-9]+)/";
    $result = preg_replace($query, '$@FORUMNGVIEW*$2@$', $result);

    // Link to forum discussion page
    $query = "/(".$base."\/mod\/forumng\/discuss.php\?d\=)([0-9]+)/";
    $result = preg_replace($query, '$@FORUMNGDISCUSS*$2@$', $result);

    return $result;
}

// Forum-specific parts that aren't relied on by other bits of Moodle
////////////////////////////////////////////////////////////////////

//Backup forum files because we've selected to backup user info
//and files are user info's level
function forumng_backup_files($bf, $preferences, $instanceid) {
    global $CFG;

    // Create forum root folder if needed
    $rootfolder = $CFG->dataroot . '/temp/backup/' .
        $preferences->backup_unique_code . '/moddata/forumng';
    $status = check_dir_exists($rootfolder, true, true);

    // Copy folder for this instance
    $instancefolder = $CFG->dataroot . '/' . $preferences->backup_course . '/' .
        $CFG->moddata . '/forumng/' . $instanceid;

    if (is_dir($instancefolder)) {
        $status = $status && backup_copy_file($instancefolder,
            $rootfolder . '/' . $instanceid);
    }

    if (!$status) {
        throw new forum_exception('Error backing up forum files');
    }
}

function forumng_backup_discussion($xb, $discussion) {
    $xb->tag_start('DISCUSSION');

    // Discussion data
    $xb->tag_full('ID', $discussion->id);
    $xb->tag_full_notnull('GROUPID', $discussion->groupid);
    $xb->tag_full('POSTID', $discussion->postid);
    $xb->tag_full('LASTPOSTID', $discussion->lastpostid);
    $xb->tag_full('TIMESTART', $discussion->timestart);
    $xb->tag_full('TIMEEND', $discussion->timeend);
    $xb->tag_full('DELETED', $discussion->deleted);
    $xb->tag_full('LOCKED', $discussion->locked);
    $xb->tag_full('STICKY', $discussion->sticky);

    // Posts
    $xb->tag_start('POSTS');
    $records = forum_utils::get_records(
        'forumng_posts', 'discussionid', $discussion->id, 'id');
    $done = array ();
    while(count($records)>0) {
        foreach($records as $post) {
            if ($post->parentpostid == null || array_key_exists($post->parentpostid, $done) ) {
                forumng_backup_post($xb, $post);
                $done[$post->id] = true;
                unset($records[$post->id]);
            }
        }
    }
    rs_close($records);
    $xb->tag_end('POSTS');

    // Read data
    $xb->tag_start('READS');
    $rs = forum_utils::get_recordset(
        'forumng_read', 'discussionid', $discussion->id);
    while($read = rs_fetch_next_record($rs)) {
        forumng_backup_read($xb, $read);
    }
    rs_close($rs);
    $xb->tag_end('READS');

    $xb->tag_end('DISCUSSION');
}

function forumng_backup_post($xb, $post) {
    $xb->tag_start('POST');

    // Post data
    $xb->tag_full('ID', $post->id);
    $xb->tag_full_notnull('PARENTPOSTID', $post->parentpostid);
    forumng_backup_userid($xb, $post->userid);
    $xb->tag_full('CREATED', $post->created);
    $xb->tag_full('MODIFIED', $post->modified);
    $xb->tag_full('DELETED', $post->deleted);
    forumng_backup_userid($xb, $post->deleteuserid, 'DELETEUSERID');
    $xb->tag_full('MAILSTATE', $post->mailstate);
    $xb->tag_full('IMPORTANT', $post->important);
    $xb->tag_full('OLDVERSION', $post->oldversion);
    forumng_backup_userid($xb, $post->edituserid, 'EDITUSERID');
    $xb->tag_full_notnull('SUBJECT', $post->subject);
    $xb->tag_full('MESSAGE', $post->message);
    $xb->tag_full('FORMAT', $post->format);
    $xb->tag_full('ATTACHMENTS', $post->attachments);

    // Ratings
    $xb->tag_start('RATINGS');
    $rs = forum_utils::get_recordset(
        'forumng_ratings', 'postid', $post->id);
    while($rating = rs_fetch_next_record($rs)) {
        forumng_backup_rating($xb, $rating);
    }
    rs_close($rs);
    $xb->tag_end('RATINGS');

    // Flags
    $xb->tag_start('FLAGS');
    $rs = forum_utils::get_recordset(
        'forumng_flags', 'postid', $post->id);
    while($flag = rs_fetch_next_record($rs)) {
        forumng_backup_flag($xb, $flag);
    }
    rs_close($rs);
    $xb->tag_end('FLAGS');

    $xb->tag_end('POST');
}

function forumng_backup_rating($xb, $rating) {
    $xb->tag_start('RATING');
    forumng_backup_userid($xb, $rating->userid);
    $xb->tag_full('TIME', $rating->time);
    $xb->tag_full('RATING', $rating->rating);
    $xb->tag_end('RATING');
}

function forumng_backup_flag($xb, $flag) {
    $xb->tag_start('FLAG');
    forumng_backup_userid($xb, $flag->userid);
    $xb->tag_full('FLAGGED', $flag->flagged);
    $xb->tag_end('FLAG');
}

function forumng_backup_read($xb, $read) {
    $xb->tag_start('READ');
    forumng_backup_userid($xb, $read->userid);
    $xb->tag_full('TIME', $read->time);
    $xb->tag_end('READ');
}

function forumng_backup_subscription($xb, $subscription) {
    $xb->tag_start('SUBSCRIPTION');
    forumng_backup_userid($xb, $subscription->userid);
    $xb->tag_full('SUBSCRIBED', $subscription->subscribed);
    $xb->tag_full_notnull('DISCUSSIONID', $subscription->discussionid);
    $xb->tag_full_notnull('GROUPID', $subscription->groupid);
    $xb->tag_end('SUBSCRIPTION');
}

function forumng_backup_userid($xb, $userid, $name='USERID') {
    // Don't write anything for null userid
    if($userid===null) {
        return;
    }
    // If userid is non-zero, get the username to include too
    $xb->tag_full($name,$userid.
        ($userid===0 ? '' : '/'.get_field('user','username','id',$userid)));
}

