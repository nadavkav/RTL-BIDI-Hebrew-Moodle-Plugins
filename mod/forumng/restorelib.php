<?php
require_once('forum.php');

@include_once(dirname(__FILE__).'/../../local/xml_backup.php');
if (!class_exists('xml_backup')) {
    require_once(dirname(__FILE__).'/local/xml_backup.php');
}

//This function executes all the restore procedure about this mod
function forumng_restore_mods($mod, $restore) {

    global $CFG;

    $status = true;

    //Get record from backup_ids
    $forumid = 0;
    if($data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id)) {
        try {
            if (!defined('RESTORE_SILENTLY')) {
                $name=$data->info['MOD']['#']['NAME']['0']['#'];
                echo "<li>".get_string('modulename','forumng').' "'.htmlspecialchars($name).'"</li>';
            }

            // Boom. Now try restoring!
            $xml=$data->info['MOD']['#'];
            $userdata=restore_userdata_selected($restore, 'forumng', $mod->id);

            $forumng = new stdClass;
            $forumng->course = $restore->course_id;
            $forumng->name = addslashes($xml['NAME'][0]['#']);

            // ForumNG-specific data
            if(isset($xml['TYPE'][0]['#'])) {
                $forumng->type = backup_todb($xml['TYPE'][0]['#']);
            }
            if(isset($xml['INTRO'][0]['#'])) {
                $forumng->intro = backup_todb($xml['INTRO'][0]['#']);
            } else {
                $forumng->intro = null;
            }
            $forumng->ratingscale = $xml['RATINGSCALE'][0]['#'];
            $forumng->ratingfrom = $xml['RATINGFROM'][0]['#'];
            $forumng->ratinguntil = $xml['RATINGUNTIL'][0]['#'];
            $forumng->ratingthreshold = $xml['RATINGTHRESHOLD'][0]['#'];
            $forumng->grading = $xml['GRADING'][0]['#'];
            $forumng->attachmentmaxbytes = $xml['ATTACHMENTMAXBYTES'][0]['#'];
            if (isset($xml['REPORTINGEMAIL'][0]['#'])) {
                $forumng->reportingemail = backup_todb($xml['REPORTINGEMAIL'][0]['#']);
            }
            $forumng->subscription = $xml['SUBSCRIPTION'][0]['#'];
            $forumng->feedtype = $xml['FEEDTYPE'][0]['#'];
            $forumng->feeditems = $xml['FEEDITEMS'][0]['#'];
            $forumng->maxpostsperiod = $xml['MAXPOSTSPERIOD'][0]['#'];
            $forumng->maxpostsblock = $xml['MAXPOSTSBLOCK'][0]['#'];
            $forumng->postingfrom = $xml['POSTINGFROM'][0]['#'];
            $forumng->postinguntil = $xml['POSTINGUNTIL'][0]['#'];
            if(isset($xml['TYPEDATA'][0]['#'])) {
                $forumng->typedata = backup_todb($xml['TYPEDATA'][0]['#']);
            }
            $forumng->magicnumber = $xml['MAGICNUMBER'][0]['#'];
            $forumng->completiondiscussions = $xml['COMPLETIONDISCUSSIONS'][0]['#'];
            $forumng->completionreplies = $xml['COMPLETIONREPLIES'][0]['#'];
            $forumng->completionposts = $xml['COMPLETIONPOSTS'][0]['#'];
            if (isset($xml['REMOVEAFTER'][0]['#'])) {
                $forumng->removeafter = $xml['REMOVEAFTER'][0]['#'];
            }
            if (isset($xml['REMOVETO'][0]['#'])) {
                $forumng->removeto = backup_todb($xml['REMOVETO'][0]['#']);
            }
            if (isset($xml['SHARED'][0]['#'])) {
                $forumng->shared = $xml['SHARED'][0]['#'];
            }
            // To protect the forum intro field from molestation if some idiot
            // sets it to a weird value...
            if (preg_match('~%%CMIDNUMBER:[^%]+%%$~', $forumng->intro)) {
                $forumng->intro .= '%%REMOVETHIS%%';
            }
            if (isset($xml['ORIGINALCMIDNUMBER'][0]['#'])) {
                if ($forumng->intro === null) {
                    $forumng->intro = '';
                }
                // This is a bit of a hack, but we need to wait until everything
                // is restored, and it is a text value; so temporarily, add it
                // to the end of the intro field.
                $forumng->intro .= '%%CMIDNUMBER:' .
                    backup_todb($xml['ORIGINALCMIDNUMBER'][0]['#']) . '%%';
            }

            // Insert main record
            if (!($forumng->id = insert_record('forumng', $forumng))) {
                throw new forum_exception('Error creating forumng instance');
            }
            $forumid = $forumng->id;
            backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id,
                $forumng->id);

            if ($userdata) {
                if (isset($xml['DISCUSSIONS'][0]['#']['DISCUSSION'])) {
                    foreach ($xml['DISCUSSIONS'][0]['#']['DISCUSSION'] as $xml_sub) {
                        forumng_restore_discussion(
                            $restore, $xml_sub['#'], $forumng);
                    }
                }

                if (isset($xml['SUBSCRIPTIONS'][0]['#']['SUBSCRIPTION'])) {
                    foreach ($xml['SUBSCRIPTIONS'][0]['#']['SUBSCRIPTION'] as $xml_sub) {
                        forumng_restore_subscription(
                            $restore, $xml_sub['#'], $forumng);
                    }
                }

                // Attachments
                xml_backup::restore_module_files($restore->backup_unique_code,
                    $restore->course_id, 'forumng', $mod->id);
                $basepath = $CFG->dataroot . '/' . $restore->course_id .
                    '/moddata/forumng';
                rename($basepath . '/' . $mod->id, $basepath . '/' . $forumng->id);
            }
        } catch(Exception $e) {
            // Clean out any partially-created data
            try {
                forum_utils::execute_sql("
DELETE FROM {$CFG->prefix}forumng_ratings
WHERE postid IN (
SELECT fp.id
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_posts fp ON fp.discussionid = fd.id
WHERE
    fd.forumid = $forumid
)");
                $discussionquery = "
WHERE discussionid IN (
SELECT id FROM {$CFG->prefix}forumng_discussions WHERE forumid=$forumid)";
                forum_utils::execute_sql(
                    "DELETE FROM {$CFG->prefix}forumng_posts $discussionquery");
                forum_utils::execute_sql(
                    "DELETE FROM {$CFG->prefix}forumng_read $discussionquery");
                forum_utils::delete_records('forumng_subscriptions', 'forumid',
                    $forumid);
                forum_utils::delete_records('forumng_discussions', 'forumid',
                    $forumid);
                forum_utils::delete_records('forumng', 'id', $forumid);
            } catch(Exception $e) {
                debugging('Error occurred when trying to clean partial data');
            }

            forum_utils::handle_backup_exception($e, 'restore');
            $status=false;
        }
    }
    return $status;
}

//Return a content decoded to support interactivities linking. Every module
//should have its own. They are called automatically from
//forumng_decode_content_links_caller() function in each module
//in the restore process
function forumng_decode_content_links ($content,$restore) {
    global $CFG;

    $result = $content;

    //Link to the list of instances
    $searchstring = '/\$@(FORUMNGINDEX)\*([0-9]+)@\$/';
    //We look for it
    $foundset = array();
    preg_match_all($searchstring, $content, $foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach ($foundset[2] as $old_id) {
            //We get the needed variables here (course id)
            $rec = backup_getid($restore->backup_unique_code, "course", $old_id);
            //Personalize the searchstring
            $searchstring = '/\$@(FORUMNGINDEX)\*(' . $old_id . ')@\$/';
            //If it is a link to this course, update the link to its new location
            if ($rec->new_id) {
                //Now replace it
                $result = preg_replace($searchstring,
                    $CFG->wwwroot.'/mod/forumng/index.php?id=' . $rec->new_id,
                    $result);
            } else {
                //It's a foreign link so leave it as original
                $result = preg_replace($searchstring,
                    $restore->original_wwwroot . '/mod/forumng/index.php?id=' .
                    $old_id, $result);
            }
        }
    }

    //Link to view by moduleid
    // TODO Argh, needs fixing
    $searchstring='/\$@(FORUMNGVIEW)\*([0-9]+)@\$/';
    //We look for it
    preg_match_all($searchstring, $result, $foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach ($foundset[2] as $old_id) {
            //We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code,
                "course_modules", $old_id);
            //Personalize the searchstring
            $searchstring = '/\$@(FORUMNGVIEW)\*(' . $old_id . ')@\$/';
            //If it is a link to this course, update the link to its new location
            if ($rec->new_id) {
                //Now replace it
                $result = preg_replace($searchstring,
                    $CFG->wwwroot . '/mod/forumng/view.php?id=' . $rec->new_id,
                    $result);
            } else {
                //It's a foreign link so leave it as original
                $result = preg_replace($searchstring,
                    $restore->original_wwwroot . '/mod/forumng/view.php?id=' .
                    $old_id, $result);
            }
        }
    }

    //Link to view discussion
    // TODO This needs to be fixed to take into account the clone feature somehow
    $searchstring='/\$@(FORUMNGDISCUSS)\*([0-9]+)@\$/';
    //We look for it
    preg_match_all($searchstring, $result, $foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach ($foundset[2] as $old_id) {
            //We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code,
                "forumng_discussions", $old_id);
            //Personalize the searchstring
            $searchstring = '/\$@(FORUMNGDISCUSS)\*(' . $old_id . ')@\$/';
            //If it is a link to this course, update the link to its new location
            if ($rec->new_id) {
                //Now replace it
                $result = preg_replace($searchstring,
                    $CFG->wwwroot . '/mod/forumng/discuss.php?d=' . $rec->new_id,
                    $result);
            } else {
                //It's a foreign link so leave it as original
                $result = preg_replace($searchstring,
                    $restore->original_wwwroot . '/mod/forumng/discuss.php?d=' .
                    $old_id, $result);
            }
        }
    }

    return $result;
}

//This function makes all the necessary calls to xxxx_decode_content_links()
//function in each module, passing them the desired contents to be decoded
//from backup format to destination site/course in order to mantain inter-activities
//working in the backup/restore process. It's called from restore_decode_content_links()
//function in restore process
function forumng_decode_content_links_caller($restore) {
    // Get all the items that might have links in, from the relevant new course
    try {
        global $CFG, $db;

        // 1. Intros
        if ($intros = get_records_select('forumng', 'course=' .
                $restore->course_id . ' AND intro IS NOT NULL', '', 'id, intro, name')) {
            foreach ($intros as $intro) {
                $newintro = $intro->intro;
                // Special behaviour hidden in intro
                $matches = array();
                if (preg_match('~%%CMIDNUMBER:([^%]+)%%$~', $newintro, $matches)) {
                    $newintro = substr($newintro, 0, -(strlen($matches[0])));
                    $idnumber = $matches[1];
                    $cm = forum::get_shared_cm_from_idnumber($idnumber);
                    if ($cm) {
                        set_field('forumng', 'originalcmid', $cm->id, 'id',
                            $intro->id);
                    } else {
                        // The original forum cannot be found, so restore
                        // this as not shared
                        if (!defined('RESTORE_SILENTLY')) {
                            $a = (object)array(
                                'name' => s($intro->name),
                                'idnumber' => s($idnumber)
                            );
                            print '<br />' . get_string(
                                    'error_nosharedforum', 'forumng', $a) . 
                                    '<br />';
                        }
                    }
                }
                if (preg_match('~%%REMOVETHIS%%$~', $newintro)) {
                    $newintro = substr($newintro, 0, -14); 
                }

                $newintro = restore_decode_content_links_worker(
                    $newintro, $restore);
                if ($newintro != $intro->intro) {
                    if (!set_field('forumng', 'intro', addslashes($newintro),
                        'id', $intro->id)) {
                        throw new forum_exception(
                            "Failed to set intro for forum {$intro->id}: " .
                            $db->ErrorMsg());
                    }
                }
            }
        }

        // 2. Post content
        $rs = get_recordset_sql("
SELECT
    fp.id, fp.message, fp.format
FROM
    {$CFG->prefix}forumng f
    INNER JOIN {$CFG->prefix}forumng_discussions d ON d.forumid = f.id
    INNER JOIN {$CFG->prefix}forumng_posts fp ON fp.discussionid = d.id
WHERE
    f.course={$restore->course_id}
");
        if (!$rs) {
            throw new forum_exception("Failed to query for forum data: " .
                $db->ErrorMsg());
        }
        while ($rec = rs_fetch_next_record($rs)) {
            $newcontent = restore_decode_content_links_worker(
                $rec->message, $restore);
            if ($newcontent != $rec->message) {
                if (!set_field('forumng_posts', 'message',
                    addslashes($newcontent), 'id', $rec->id)) {
                    throw new forum_exception("Failed to update content {$ec->id}: " .
                        $db->ErrorMsg());
                }
            }
        }
        rs_close($rs);

        // 3. Update search data (note this is not actually do with content
        //    links, but it has to be done here because we need a course-module
        //    id.
        if (forum::search_installed()) {
            forumng_ousearch_update_all(false, $restore->course_id);
        }

        return true;
    } catch(Exception $e) {
        forum_utils::handle_backup_exception($e, 'restore');
        return false;
    }
}

//This function returns a log record with all the necessay transformations
//done. It's used by restore_log_module() to restore modules log.
function forumng_restore_logs($restore,$log) {
    //Depending of the action, we recode different things
    switch ($log->action) {
    case 'update':
    case 'add':
    case 'view':
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid(
                $restoresettings->backup_unique_code,$log->module,$log->info);
            if ($mod) {
                $log->url = "view.php?id=".$log->cmid;
                $log->info = $mod->new_id;
                return $log;
            }
        }
        return false;
    case 'view all':
        $log->url = "index.php?id=".$log->course;
        return $log;

    // Custom log actions are not restored (I couldn't be arsed to implement
    // it). If anyone has a reason to need it to work, let me know

    default:
        if (!defined('RESTORE_SILENTLY')) {
            echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />"; //Debug
        }
        return false;
    }
}


// Wiki-specific functions not called by system
///////////////////////////////////////////////

/**
 * Attempts to obtain a user ID from a string either "0" or "id/username".
 * Username is used to verify matches if we are just guessing the ID is
 * the same.
 * @param string $string User ID string as above
 * @return mixed 0, a valid user ID, or false
 */
function forumng_restore_userid($string, &$restore) {
    if ((string)$string==='0') {
        return 0;
    }
    $matches=array();
    if (preg_match('|^([0-9]+)/(.*)$|', $string, $matches)) {
        // Try backup_getid first
        $newid = backup_getid($restore->backup_unique_code,"user",$matches[1]);
        if ($newid) {
            return $newid->new_id;
        }
        // OK not there, see if they're still in real user db
        $realun = get_field('user', 'username', 'id', $matches[1]);
        if ($realun === $matches[2]) {
            return $matches[1];
        }
    }
    return false;
}

function forumng_restore_discussion($restore, $xml, $forumng) {
    // Get discussion fields
    $discussion = new stdClass;
    $discussion->forumid = $forumng->id;

    $oldid = $xml['ID'][0]['#'];
    if (isset($xml['GROUPID'][0]['#'])) {
        $newid = backup_getid($restore->backup_unique_code, 'groups',
            $xml['GROUPID'][0]['#']);
        if ($newid && $newid->new_id) {
            $discussion->groupid=$newid->new_id;
        } else {
            // If group doesn't exist, discussion goes into default group
        }
    }
    $discussion->timestart = $xml['TIMESTART'][0]['#'];
    $discussion->timeend = $xml['TIMEEND'][0]['#'];
    $discussion->deleted = $xml['DELETED'][0]['#'];
    $discussion->locked = $xml['LOCKED'][0]['#'];
    $discussion->sticky = $xml['STICKY'][0]['#'];

    // Add new record and track ID
    if (!($discussion->id = insert_record('forumng_discussions', $discussion))) {
        throw new forum_exception('Error creating discussion object');
    }
    backup_putid($restore->backup_unique_code, 'forumng_discussions', $oldid,
        $discussion->id);

    // Restore posts and read data
    foreach (forumng_get_restore_array($xml, 'POSTS', 'POST') as $xml_sub) {
        forumng_restore_post($restore, $xml_sub['#'], $discussion);
    }
    foreach (forumng_get_restore_array($xml, 'READS', 'READ') as $xml_sub) {
        forumng_restore_read($restore, $xml_sub['#'], $discussion);
    }

    // Add in post IDs
    $update = new stdClass;
    $update->id = $discussion->id;
    $oldpostid = $xml['POSTID'][0]['#'];
    $newpostid = backup_getid($restore->backup_unique_code, 'forumng_posts',
        $oldpostid);
    if ($newpostid && $newpostid->new_id) {
        $update->postid = $newpostid->new_id;
    } else {
        throw new forum_exception("Couldn't find discussion $oldid main post $oldpostid");
    }
    $oldpostid = $xml['LASTPOSTID'][0]['#'];
    $newpostid = backup_getid($restore->backup_unique_code, 'forumng_posts',
        $oldpostid);
    if ($newpostid && $newpostid->new_id) {
        $update->lastpostid = $newpostid->new_id;
    } else {
        throw new forum_exception("Couldn't find discussion $oldid last post $oldpostid");
    }
    if (!update_record('forumng_discussions', $update)) {
        throw new forum_exception('Error updating discussion object');
    }
}

function forumng_get_admin_id() {
    static $adminid = 0;
    if (!$adminid) {
        $adminid = get_field('user', 'id', 'username', 'admin');
        if (!$adminid) {
            throw new forum_exception('Failed to obtain admin user id');
        }
    }
    return $adminid;
}

function forumng_restore_post($restore, $xml, $discussion) {
    // Set up post object and fields
    $post = new stdClass;
    $post->discussionid = $discussion->id;

    $oldpostid = $xml['ID'][0]['#'];
    if (isset($xml['PARENTPOSTID'][0]['#'])) {
        $old_id = $xml['PARENTPOSTID'][0]['#'];
        $rec = backup_getid($restore->backup_unique_code, "forumng_posts", $old_id);
        $post->parentpostid = $rec ? $rec->new_id : null;
        if (!$post->parentpostid) {
            throw new forum_exception("Missing parent post $old_id for $oldpostid");
        }
    }
    $post->userid = forumng_restore_userid($xml['USERID'][0]['#'], $restore);
    if (!$post->userid) {
        if (!defined('RESTORE_SILENTLY')) {
            print "<div>Warning: Missing user for post (old id) $oldpostid, setting to admin.</div>";
        }
        $post->userid = forumng_get_admin_id();
    }
    $post->created = $xml['CREATED'][0]['#'];
    $post->modified = $xml['MODIFIED'][0]['#'];
    $post->deleted = $xml['DELETED'][0]['#'];
    if (isset($xml['DELETEUSERID'][0]['#'])) {
        $post->deleteuserid = forumng_restore_userid(
            $xml['DELETEUSERID'][0]['#'], $restore);
        if (!$post->deleteuserid) {
            if (!defined('RESTORE_SILENTLY')) {
                print "<div>Warning: Missing delete user for post (old id) $oldpostid, setting to admin.</div>";
            }
            $post->deleteuserid = forumng_get_admin_id();
        }
    }
    if (isset($xml['IMPORTANT'][0]['#'])) {
        $post->important = $xml['IMPORTANT'][0]['#'];
    }
    $post->mailstate = $xml['MAILSTATE'][0]['#'];
    $post->oldversion = $xml['OLDVERSION'][0]['#'];
    if (isset($xml['EDITUSERID'][0]['#'])) {
        $post->edituserid = forumng_restore_userid(
            $xml['EDITUSERID'][0]['#'], $restore);
        if (!$post->edituserid) {
            if (!defined('RESTORE_SILENTLY')) {
                print "<div>Warning: Missing edit user for post (old id) $oldpostid, setting to admin.</div>";
            }
            $post->edituserid = forumng_get_admin_id();
        }
    }
    if (isset($xml['SUBJECT'][0]['#'])) {
        $post->subject = backup_todb($xml['SUBJECT'][0]['#']);
    }
    $post->message = backup_todb($xml['MESSAGE'][0]['#']);
    $post->format = $xml['FORMAT'][0]['#'];
    $post->attachments = $xml['ATTACHMENTS'][0]['#'];

    // Create object and remember id
    if (!($post->id = insert_record('forumng_posts', $post))) {
        throw new forum_exception('Error creating post object');
    }
    backup_putid($restore->backup_unique_code, 'forumng_posts', $oldpostid,
        $post->id);

    // Restore ratings
    foreach (forumng_get_restore_array($xml, 'RATINGS', 'RATING') as $xml_sub) {
        forumng_restore_rating($restore, $xml_sub['#'], $post);
    }

    // Restore flags
    foreach (forumng_get_restore_array($xml, 'FLAGS', 'FLAG') as $xml_sub) {
        forumng_restore_flag($restore, $xml_sub['#'], $post);
    }
}

function forumng_restore_rating($restore, $xml, $post) {
    $rating = new stdClass;
    $rating->postid = $post->id;

    $rating->userid = forumng_restore_userid($xml['USERID'][0]['#'], $restore);
    if (!$rating->userid) {
        // Silently ignore rating - if we're not restoring user it would  be
        // expected that we don't include their rating
        return;
    }
    $rating->time = $xml['TIME'][0]['#'];
    $rating->rating = $xml['RATING'][0]['#'];

    if (!insert_record('forumng_ratings', $rating)) {
        throw new forum_exception('Failed to insert rating data');
    }
}

function forumng_restore_flag($restore, $xml, $post) {
    $flag = new stdClass;
    $flag->postid = $post->id;

    $flag->userid = forumng_restore_userid($xml['USERID'][0]['#'], $restore);
    if (!$flag->userid) {
        // Silently ignore flag - if we're not restoring user it would  be
        // expected that we don't include their flag
        return;
    }
    $flag->flagged = $xml['FLAGGED'][0]['#'];

    if (!insert_record('forumng_flags', $flag)) {
        throw new forum_exception('Failed to insert flag data');
    }
}

function forumng_restore_read($restore, $xml, $discussion) {
    $read = new stdClass;
    $read->discussionid = $discussion->id;

    $read->userid = forumng_restore_userid($xml['USERID'][0]['#'], $restore);
    if (!$read->userid) {
        // Silently ignore.
        return;
    }
    $read->time = $xml['TIME'][0]['#'];

    if (!insert_record('forumng_read', $read)) {
        throw new forum_exception('Failed to insert read data');
    }
}

function forumng_restore_subscription($restore, $xml, $forumng) {
    $subscription = new stdClass;
    $subscription->forumid = $forumng->id;

    $subscription->userid = forumng_restore_userid($xml['USERID'][0]['#'], $restore);
    if (!$subscription->userid) {
        // Silently ignore.
        return;
    }

    if (isset($xml['SUBSCRIBED'])) {
        $subscription->subscribed = $xml['SUBSCRIBED'][0]['#'];
    } else {
        $subscription->subscribed = 1;
    }

    if (isset($xml['GROUPID'])) {
        $newid = backup_getid($restore->backup_unique_code, 'groups',
            $xml['GROUPID'][0]['#']);
        if ($newid && $newid->new_id) {
            $subscription->groupid = $newid->new_id;
        } else {
            if (!defined('RESTORE_SILENTLY')) {
                echo "Groupid  doesn't exist<br />";
            }
            return; 
        }
    }

    if (isset($xml['DISCUSSIONID'])) {
        $newid = backup_getid($restore->backup_unique_code, 'forumng_discussions',
            $xml['DISCUSSIONID'][0]['#']);
        if ($newid && $newid->new_id) {
            //$subscription->discussionid = backup_todb($newid->new_id);
            $subscription->discussionid = $newid->new_id;
        } else {
            if (!defined('RESTORE_SILENTLY')) {
                echo "Discussionid doesn't exist<br />";
            }
            return; 
        }
    }

    if (!insert_record('forumng_subscriptions', $subscription)) {
        throw new forum_exception('Failed to insert subscription data');
    }
}

/**
 * Convenience function. Given an XML array clipping that starts from the current element,
 * look for child elements called $single (e.g. VERSION) within a container called $multiple (e.g.
 * VERSIONS) and returns an array of these elements which you can use to call foreach. Note that
 * after calling foreach you will need to go to ['#'] on each result. Always returns an array even
 * when there are 0 elements.
 * @param array $current Weird XML array starting from current element
 * @param string $multiple Name of container tag
 * @param string $single Name of contained child tag
 * @return array Array of data
 */
function forumng_get_restore_array(&$current,$multiple,$single) {
    if (isset($current[$multiple]) &&
        isset($current[$multiple][0]['#'][$single])) {
        return $current[$multiple][0]['#'][$single];
    } else {
        return array();
    }
}
?>
