<?php
/*
 * This php script contains all the stuff to backup/restore
 * For the structure of oublog, see restorelib.php
 *
 */
include(dirname(__FILE__).'/locallib.php');
//This function executes all the restore procedure about this mod
function oublog_restore_mods($mod,$restore) {

    global $CFG;

    $status = true;

    //Get record from backup_ids
    $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

    if (!$data) {
        return false;
    }

    //Now get completed xmlized object
    $info = $data->info;

    // Currently OUBlog has no "start time" or "deadline" fields
    // that make sense to offset at restore time. Edit and delete times
    // must remain stable even through restores with startdateoffsets.
    // if ($restore->course_startdateoffset) {
    //     restore_log_date_changes('OUBlog', $restore,
    //                              $info['MOD']['#'], array(??));
    // }

    //
    // Debugging help :-)
    //
    //traverse_xmlize($info);
    //print_object ($GLOBALS['traverse_array']);
    //$GLOBALS['traverse_array']="";

    //Now, build the oublog record structure
    $oublog = new StdClass;
    $oublog->course = $restore->course_id;
    $fields = array('name', 'accesstoken', 'summary', 'allowcomments', 'individual', 
                    'maxvisibility', 'global', 'views','completionposts','completioncomments');
    foreach ($fields as $f) {
        if(isset($info['MOD']['#'][ strtoupper($f) ]['0']['#'])) {
            $oublog->$f = backup_todb($info['MOD']['#'][ strtoupper($f) ]['0']['#']);
        }
    }

    // if it's the global blog and we already have one then assume we can't restore this module since it already exits
    if ($oublog->global && $newid = get_field('oublog', 'id', 'global', 1)) {
        return(true);
    } else {

        //if "individual" field doe not exist, add it and set it to zero
        if (!isset($oublog->individual)) {
            $oublog->individual = OUBLOG_NO_INDIVIDUAL_BLOGS;
        }

        //The structure is equal to the db, so insert the blog
        $newid = insert_record ("oublog",$oublog);
    }

    //Do some output
    if (!defined('RESTORE_SILENTLY')) {
        echo "<li>".get_string("modulename","oublog")." \"".format_string(stripslashes($oublog->name),true)."\"</li>";
    }
    backup_flush(300);

    if ($newid) {
        //We have the newid, update backup_ids
        backup_putid($restore->backup_unique_code,$mod->modtype,
                     $mod->id, $newid);
        oublog_links_restore_mods($mod->id, $newid, $info, $restore);
        //Now check if want to restore user data and do it.
        if (restore_userdata_selected($restore,'oublog',$mod->id)) {
            //Restore journal_entries
            $status = oublog_userdata_restore_mods ($mod->id, $newid,$info,$restore);
        }
    } else {
        $status = false;
    }

    return $status;
}

function oublog_links_restore_mods($old_oublog_id, $new_oublog_id, $info, $restore) {

    global $CFG;

    $status = true;

    if (!isset($info['MOD']['#']['LINKS']['0']['#']['LINK'])) {
        return $status;
    }

    ///
    /// oublog_links
    //  (here we restore only those that have oublogid FK
    ///  - which are module data -)
    $recs = $info['MOD']['#']['LINKS']['0']['#']['LINK'];
    $fields = array('title', 'url',
                    'sortorder');
    //Iterate over recors
    $c = count($recs);
    for($i = 0; $i < $c; $i++) {
        $rec_info = $recs[$i];

        // Skip record?
        if (!isset($rec_info['#']['OUBLOGID']['0']['#'])) {
            continue;
        }

        // oldid is only for tables that will be referenced
        // $oldid = backup_todb($rec_info['#']['ID']['0']['#']);
        $rec = new StdClass;
        foreach ($fields as $f) {
            $rec->$f = backup_todb($rec_info['#'][ strtoupper($f) ]['0']['#']);
        }
        // Set the oublogid to the new one...
        $rec->oublogid = $new_oublog_id;

        //The structure is equal to the db, so INSERT away...
        $newid = insert_record ("oublog_links",$rec);

        //Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if ($newid) {
            // NOT needed - no table references oublog_links
            //We have the newid, update backup_ids
            // backup_putid($restore->backup_unique_code,
            // "oublog_links",
            // $oldid, $newid);
        } else {
            $status = false;
        }
    }
    return $status;
}

//This function restores the journal_entries
function oublog_userdata_restore_mods($old_oublog_id, $new_oublog_id,$info,$restore) {

    global $CFG;

    $status = true;

    //
    // Debugging help :-)
    //
    //traverse_xmlize($info);
    //print_object ($GLOBALS['traverse_array']);
    //$GLOBALS['traverse_array']="";

    ///
    /// oublog_instances
    ///
    if (isset($info['MOD']['#']['INSTANCES']['0']['#']['INSTANCE'])) {
        $recs = $info['MOD']['#']['INSTANCES']['0']['#']['INSTANCE'];
    } else {
        $recs = array();
    }

    //Iterate over records
    $c = count($recs);
    for($i = 0; $i < $c; $i++) {
        $rec_info = $recs[$i];

        $oldid = backup_todb($rec_info['#']['ID']['0']['#']);

        $rec = new StdClass;
        $rec->oublogid = $new_oublog_id;
        $fields = array('userid', 'name', 'summary', 'accesstoken', 'views');
        foreach ($fields as $f) {
            $rec->$f = backup_todb($rec_info['#'][ strtoupper($f) ]['0']['#']);
        }
        // Recode the userid field - which is optional
        if (!empty($rec->userid)) {
            $new_rec = backup_getid($restore->backup_unique_code,
                                      "user", $rec->userid);
            if (!empty($new_rec->new_id)) {
                $rec->userid = $new_rec->new_id;
            }

            unset($new_rec);
        }

        //The structure is equal to the db, so INSERT away...
        $newid = insert_record ("oublog_instances",$rec);

        //Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if ($newid) {
            //We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code,
                         "oublog_instances",
                         $oldid, $newid);
        } else {
            $status = false;
        }
    }

    ///
    /// oublog_links
    //  (here we restore only those that have oubloginstanceid FK
    ///  - which are userdata -)
    if (isset($info['MOD']['#']['LINKS']['0']['#']['LINK'])) {
        $recs = $info['MOD']['#']['LINKS']['0']['#']['LINK'];
    } else {
        $recs = array();
    }
    $fields = array('oubloginstancesid','title', 'url',
                    'sortorder');
    //Iterate over recors
    $c = count($recs);
    for($i = 0; $i < $c; $i++) {
        $rec_info = $recs[$i];

        // Skip record
        if (!isset($rec_info['#']['OUBLOGINSTANCESID']['0']['#'])) {
            continue;
        }

        // oldid is only for tables that will be referenced
        // $oldid = backup_todb($rec_info['#']['ID']['0']['#']);
        $rec = new StdClass;
        foreach ($fields as $f) {
            $rec->$f = backup_todb($rec_info['#'][ strtoupper($f) ]['0']['#']);
        }
        // Recode
        $rec->oubloginstancesid = backup_getid($restore->backup_unique_code,
                                               "oublog_instances",
                                               $rec->oubloginstancesid)->new_id;

        //The structure is equal to the db, so INSERT away...
        $newid = insert_record ("oublog_links",$rec);

        //Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if ($newid) {
            // NOT needed - no table references oublog_links
            //We have the newid, update backup_ids
            // backup_putid($restore->backup_unique_code,
            // "oublog_links",
            // $oldid, $newid);
        } else {
            $status = false;
        }
    }

    ///
    /// oublog_posts
    ///
    if (isset($info['MOD']['#']['POSTS']['0']['#']['POST'])) {
        $recs = $info['MOD']['#']['POSTS']['0']['#']['POST'];
    } else {
        $recs = array();
    }
    //Iterate over records
    $c = count($recs);
    for($i = 0; $i < $c; $i++) {
        $rec_info = $recs[$i];

        $oldid = backup_todb($rec_info['#']['ID']['0']['#']);

        $rec = new StdClass;
        $fields = array('oubloginstancesid', 'groupid', 'title', 'message',
                        'timeposted','allowcomments','timeupdated',
                        'lastediteby','deletedby','timedeleted', 'visibility');
        foreach ($fields as $f) {
            // Bug 7080  undefined index restoring oublog
            if(empty($rec_info['#'][ strtoupper($f) ]['0']['#'])){
            	continue;
            }
            $rec->$f = backup_todb($rec_info['#'][ strtoupper($f) ]['0']['#']);
        }
        // Recode fields
        $rec->oubloginstancesid = backup_getid($restore->backup_unique_code,"oublog_instances",
                                                $rec->oubloginstancesid)->new_id;
        // Recode optional fields
        if (!empty($rec->groupid)) {
            $rec->groupid = backup_getid($restore->backup_unique_code,"groups",
                                         $rec->groupid)->new_id;
        }
        if (!empty($rec->deletedby)) {
            $rec->deletedby = backup_getid($restore->backup_unique_code,"user",
                                           $rec->deletedby)->new_id;
        }

        //The structure is equal to the db, so INSERT away...
        $newid = insert_record ("oublog_posts",$rec);

        //Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if ($newid) {
            //We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code,
                         "oublog_posts",
                         $oldid, $newid);
        } else {
            $status = false;
        }
    }

    ///
    /// oublog_comments
    ///
    if (isset($info['MOD']['#']['COMMENTS']['0']['#']['COMMENT'])) {
        $recs = $info['MOD']['#']['COMMENTS']['0']['#']['COMMENT'];
    } else {
        $recs = array();
    }
    //Iterate over records
    $c = count($recs);
    for($i = 0; $i < $c; $i++) {
        $rec_info = $recs[$i];

        $oldid = backup_todb($rec_info['#']['ID']['0']['#']);

        $rec = new StdClass;
        $fields = array('postid', 'userid', 'title', 'message',
                'timeposted', 'deletedby', 'timedeleted',
                'authorname', 'authorip', 'timeapproved');
        foreach ($fields as $f) {
            $rec->$f = backup_todb($rec_info['#'][ strtoupper($f) ]['0']['#']);
        }
        // Recode fields
        $rec->postid = backup_getid($restore->backup_unique_code,"oublog_posts",
                                    $rec->postid)->new_id;
        if ($newuserid = backup_getid($restore->backup_unique_code,"user",
                                    $rec->userid)->new_id) {
            $rec->userid = $newuserid;
        }

        // Recode optional fields
        if (!empty($rec->deletedby)) {
            $rec->deletedby = backup_getid($restore->backup_unique_code,"user",
                                           $rec->deletedby)->new_id;
        }

        //The structure is equal to the db, so INSERT away...
        $newid = insert_record ("oublog_comments",$rec);

        //Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if ($newid) {
            //We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code,
                         "oublog_comments",
                         $oldid, $newid);
        } else {
            $status = false;
        }
    }

    ///
    /// oublog_edits
    ///
    if (isset($info['MOD']['#']['EDITS']['0']['#']['EDIT'])) {
        $recs = $info['MOD']['#']['EDITS']['0']['#']['EDIT'];
    } else {
        $recs = array();
    }
    //Iterate over records
    $c = count($recs);
    for($i = 0; $i < $c; $i++) {
        $rec_info = $recs[$i];

        $oldid = backup_todb($rec_info['#']['ID']['0']['#']);

        $rec = new StdClass;
        $fields = array('postid', 'userid', 'oldtitle', 'oldmessage',
                        'timeupdated');
        foreach ($fields as $f) {
            $rec->$f = backup_todb($rec_info['#'][ strtoupper($f) ]['0']['#']);
        }
        // Recode fields
        $rec->postid = backup_getid($restore->backup_unique_code,"oublog_posts",
                                    $rec->postid)->new_id;
        if ($newuserid = backup_getid($restore->backup_unique_code,"user",
                                    $rec->userid)->new_id) {
            $rec->userid = $newuserid;
        }

        //The structure is equal to the db, so INSERT away...
        $newid = insert_record ("oublog_edits",$rec);

        //Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if ($newid) {
            //We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code,
                         "oublog_comments",
                         $oldid, $newid);
        } else {
            $status = false;
        }
    }

///
    /// oublog_tags and oublog_taginstances
    ///
    if (isset($info['MOD']['#']['TAGS']['0']['#']['TAG'])) {
        $recs = $info['MOD']['#']['TAGS']['0']['#']['TAG'];
    } else {
        $recs = array();
    }
    // Collect tags by post first
    $tagsbypost = array();
    $c = count($recs);
    for($i = 0; $i < $c; $i++) {
        $rec_info = $recs[$i];

        $oldid = backup_todb($rec_info['#']['ID']['0']['#']);

        $rec = new StdClass;
        $fields = array('postid', 'tagname');
        foreach ($fields as $f) {
            $rec->$f = backup_todb($rec_info['#'][ strtoupper($f) ]['0']['#']);
        }
        // Recode fields
        $rec->postid = backup_getid($restore->backup_unique_code,"oublog_posts",
                                    $rec->postid)->new_id;

        if (isset($tagsbypost[$rec->postid])) {
            $tagsbypost[$rec->postid][] = $rec->tagname;
        } else {
            $tagsbypost[$rec->postid] = array($rec->tagname);
        }
    }
    // And now insert the tags by post...
    foreach ($tagsbypost as $postid => $tags) {
        $oubloginstancesid = get_field('oublog_posts', 'oubloginstancesid', 'id', $postid);
        oublog_update_item_tags($oubloginstancesid, $postid, $tags);
    }

    return $status;
}

//This function returns a log record with all the necessay transformations
//done. It's used by restore_log_module() to restore modules log.
function oublog_restore_logs($restore,$log) {

    $status = false;

    //Depending of the action, we recode different things
    switch ($log->action) {
    case "add":
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
            if ($mod) {
                $log->url = "view.php?id=".$log->cmid;
                $log->info = $mod->new_id;
                $status = true;
            }
        }
        break;
    case "update":
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
            if ($mod) {
                $log->url = "view.php?id=".$log->cmid;
                $log->info = $mod->new_id;
                $status = true;
            }
        }
        break;
    case "view":
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
            if ($mod) {
                $log->url = "view.php?id=".$log->cmid;
                $log->info = $mod->new_id;
                $status = true;
            }
        }
        break;
    case "add entry":
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
            if ($mod) {
                $log->url = "view.php?id=".$log->cmid;
                $log->info = $mod->new_id;
                $status = true;
            }
        }
        break;
    case "update entry":
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
            if ($mod) {
                $log->url = "view.php?id=".$log->cmid;
                $log->info = $mod->new_id;
                $status = true;
            }
        }
        break;
    case "view responses":
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
            if ($mod) {
                $log->url = "report.php?id=".$log->cmid;
                $log->info = $mod->new_id;
                $status = true;
            }
        }
        break;
    case "update feedback":
        if ($log->cmid) {
            $log->url = "report.php?id=".$log->cmid;
            $status = true;
        }
        break;
    case "view all":
        $log->url = "index.php?id=".$log->course;
        $status = true;
        break;
    default:
        if (!defined('RESTORE_SILENTLY')) {
            echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
        }
        break;
    }

    if ($status) {
        $status = $log;
    }
    return $status;
}

//Return a content decoded to support interactivities linking. Every module
//should have its own. They are called automatically from
//forum_decode_content_links_caller() function in each module
//in the restore process
function oublog_decode_content_links($content, $restore) {

    global $CFG;

    $result = $content;

    $searchstring='/\$@(OUBLOGVIEW)\*([0-9]+)@\$/';

    preg_match_all($searchstring, $result, $foundset);

    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
            //Personalize the searchstring
            $searchstring='/\$@(OUBLOGVIEW)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if(!empty($rec->new_id)) {
                //Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/oublog/view.php?id='.$rec->new_id,$result);
            } else {
                //It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/oublog/view.php?id='.$old_id,$result);
            }
        }
    }

    $searchstring='/\$@(OUBLOGVIEWUSER)\*([0-9]+)@\$/';

    preg_match_all($searchstring, $result, $foundset);

    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code,"users",$old_id);
            //Personalize the searchstring
            $searchstring='/\$@(OUBLOGVIEWUSER)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if(!empty($rec->new_id)) {
                //Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/oublog/view.php?user='.$rec->new_id,$result);
            } else {
                //It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/oublog/view.php?user='.$old_id,$result);
            }
        }
    }


    $searchstring='/\$@(OUBLOGVIEWPOST)\*([0-9]+)@\$/';

    preg_match_all($searchstring, $result, $foundset);

    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code,"oublog_posts",$old_id);
            //Personalize the searchstring
            $searchstring='/\$@(OUBLOGVIEWPOST)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if(!empty($rec->new_id)) {
                //Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/oublog/viewpost.php?id='.$rec->new_id,$result);
            } else {
                //It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/oublog/viewpost.php?id='.$old_id,$result);
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
function oublog_decode_content_links_caller($restore) {
    global $CFG;
    $status = true;

    //Process every blog
    $sql = "SELECT b.id, b.summary
            FROM {$CFG->prefix}oublog b
            WHERE b.course = $restore->course_id";

    if ($blogs = get_records_sql($sql)) {
        //Iterate over each blog->summary
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($blogs as $blog) {
            //Increment counter
            $i++;
            $content = $blog->summary;
            $result = restore_decode_content_links_worker($content,$restore);
            if ($result != $content) {
                //Update record
                $blog->summary = addslashes($result);
                $status = update_record('oublog',$blog);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
            //Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }

    //Process every blog instance
    $sql = "SELECT i.id, i.summary
            FROM {$CFG->prefix}oublog_instances i
            INNER JOIN {$CFG->prefix}oublog b ON i.oublogid = b.id
            WHERE b.course = $restore->course_id";

    if ($instances = get_records_sql($sql)) {
        //Iterate over each blog->summary
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($instances as $instance) {
            //Increment counter
            $i++;
            $content = $instance->summary;
            $result = restore_decode_content_links_worker($content,$restore);
            if ($result != $content) {
                //Update record
                $instance->summary = addslashes($result);
                $status = update_record('oublog_instances',$instance);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
            //Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }


    //Process every blog post in the course
    $sql = "SELECT p.id, p.message
            FROM {$CFG->prefix}oublog_posts p
            INNER JOIN {$CFG->prefix}oublog_instances i ON p.oubloginstancesid = i.id
            INNER JOIN {$CFG->prefix}oublog b ON i.oublogid = b.id
            WHERE b.course = $restore->course_id";

    if ($posts = get_records_sql($sql)) {
        //Iterate over each post->message
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($posts as $post) {
            //Increment counter
            $i++;
            $content = $post->message;
            $result = restore_decode_content_links_worker($content,$restore);
            if ($result != $content) {
                //Update record
                $post->message = addslashes($result);
                $status = update_record('oublog_posts',$post);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
            //Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }

    //Process every blog comment in the course
    $sql = "SELECT c.id, c.message
            FROM {$CFG->prefix}oublog_comments c
            INNER JOIN {$CFG->prefix}oublog_posts p ON c.postid = p.id
            INNER JOIN {$CFG->prefix}oublog_instances i ON p.oubloginstancesid = i.id
            INNER JOIN {$CFG->prefix}oublog b ON i.oublogid = b.id
            WHERE b.course = $restore->course_id";

    if ($comments = get_records_sql($sql)) {
        //Iterate over each comment->message
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($comments as $comment) {
            //Increment counter
            $i++;
            $content = $comment->message;
            $result = restore_decode_content_links_worker($content,$restore);
            if ($result != $content) {
                //Update record
                $comment->message = addslashes($result);
                $status = update_record('oublog_comments',$comment);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
            //Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }


    //Process every related link in this course
    $sql = "SELECT l.id, l.url
            FROM {$CFG->prefix}oublog_links l
            INNER JOIN {$CFG->prefix}oublog b ON l.oublogid = b.id
            WHERE b.course = $restore->course_id";

    if ($links = get_records_sql($sql)) {
        //Iterate over each comment->message
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($links as $link) {
            //Increment counter
            $i++;
            $content = $link->url;
            $result = restore_decode_content_links_worker($content,$restore);
            if ($result != $content) {
                //Update record
                $link->url = addslashes($result);
                $status = update_record('oublog_links',$link);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
            //Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }

    // Now fix up search for the course
    if(oublog_search_installed()) {
        oublog_ousearch_update_all(false,$restore->course_id);
    }

    return $status;
}
?>
