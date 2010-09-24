<?php
//This php script contains all the stuff to backup/restore
// OU Blog.

//This is the "graphical" structure of the blog mod:
//
//                                oublog
//         ---------------------(CL,pk->id)
//         |                         |
//         |                         |
//         |                         |
//         |                oublog_instances
//         ---------------(UL,pk->id, fk->oublog)
//         |                         |
//         |                         |
//         |                         |
//    oublog_links                   |
//(UL/CL,pk->id,                     |
// fk->oubloginstances|fk->oublog)   |
//            fk->oublog             |
//                                   |
//                                   |
//                              oublog_posts
//                         (UL,pk->id, fk->oubloginstances)
//                                   |
//                                   |
//oublog_tags                        |     oublog_comments
// (pk->id)                          |--(UL,pk->id,fk->posts)
//    |                              |
//    |                              |     oublog_edits
//    |                              |--(UL,pk->id,fk->posts)
//    |                              |
//    |     oublog_taginstances      |
//    -(pk->id,fk->tagid,fk->postid)--
//
// Meaning: pk->primary key field of the table
//          fk->foreign key to link with parent
//          nt->nested field (recursive data)
//          CL->course level info
//          UL->user level info
//          files->table may have files)
//
//-----------------------------------------------------------

function oublog_backup_mods($bf,$preferences) {

    global $CFG;

    $status = true;

    //Iterate over oublog table
    $blogsrs = get_recordset ("oublog","course",$preferences->backup_course,"id");

    if (!$blogsrs) {
        return $status;
    }
    while ($blog = rs_fetch_next_record($blogsrs)) {
        if (backup_mod_selected($preferences,'oublog',$blog->id)) {
            $status = oublog_backup_one_mod($bf,$preferences,$blog);
        }
    }
    return $status;
}

function oublog_backup_one_mod($bf,$preferences,$blog) {

    global $CFG;

    if (is_numeric($blog)) {
        $blog = get_record('oublog','id',$blog);
    }

    $status = true;


    //Start mod
    fwrite ($bf,start_tag("MOD",3,true));
    //Print blog data
    fwrite ($bf,full_tag("MODTYPE",4,false,"oublog"));
    $fields = array('id', 'name', 'accesstoken', 'summary', 'allowcomments', 'individual', 
                    'maxvisibility', 'global', 'views','completionposts','completioncomments');
    $status = write_rectags($bf,$fields, $blog, 4);

    $links = get_recordset('oublog_links', 'oublogid',$blog->id);
    if ($links->RecordCount()) {
        fwrite ($bf,start_tag("LINKS",4,true));
        while ($link = rs_fetch_next_record($links)) {
            fwrite ($bf,start_tag("LINK",5,true));
            $fields = array('id', 'oublogid', 'title', 'url',
                            'sortorder');
            $status = write_rectags($bf,$fields, $link, 6);
            fwrite ($bf,end_tag("LINK",5,true));
        }
        $status =fwrite ($bf,end_tag("LINKS",4,true));
    }

    //if we've selected to backup users info, then execute backup_oublog_userdata
    if (backup_userdata_selected($preferences,'oublog',$blog->id)) {
        $status = backup_oublog_userdata($bf,$preferences,$blog->id);
    }
    //End mod
    $status =fwrite ($bf,end_tag("MOD",3,true));

    return $status;
}

//Backup user data (executed from oublog_backup_mods)
function backup_oublog_userdata ($bf,$preferences,$blogid) {

    global $CFG;

    $status = true;

    // Instead of nesting, we will build a flat structure
    // this takes less DB queries, and is more resilient.
    //
    // oublog_instances
    // oublog_posts
    // oublog_edits
    // oublog_comments
    // oublog_tags (via taginstances)
    // oublog_links

    // Instances
    $recs = get_recordset("oublog_instances","oublogid",$blogid);
    if ($recs->RecordCount()) {
        $status =fwrite ($bf,start_tag("INSTANCES",4,true));
        while ($rec = rs_fetch_next_record($recs)) {
            $status =fwrite ($bf,start_tag("INSTANCE",5,true));
            $fields = array('id', 'userid', 'name', 'summary',
                            'accesstoken', 'views');
            $status = write_rectags($bf, $fields, $rec, 6);
            $status =fwrite ($bf,end_tag("INSTANCE",5,true));
        }
        $status =fwrite ($bf,end_tag("INSTANCES",4,true));
    }
    unset($recs);

    // Blog post links
    // NOTE: these are links related to blog instances,
    //       not to oublog records
    $sql = "SELECT l.*
            FROM {$CFG->prefix}oublog_links l
            JOIN {$CFG->prefix}oublog_instances i
              ON l.oubloginstancesid=i.id
            WHERE i.oublogid={$blogid}";
    $recs = get_recordset_sql($sql);
    if ($recs->RecordCount()) {
        $status =fwrite ($bf,start_tag("LINKS",4,true));
        while ($rec = rs_fetch_next_record($recs)) {
            $status =fwrite ($bf,start_tag("LINK",5,true));
            $fields = array('id', 'oubloginstancesid',
                            'title', 'url', 'sortorder');
            $status = write_rectags($bf, $fields, $rec, 6);
            $status =fwrite ($bf,end_tag("LINK",5,true));
        }
        $status =fwrite ($bf,end_tag("LINKS",4,true));
    }
    unset($recs);

    // Blog posts
    $sql = "SELECT p.*
            FROM {$CFG->prefix}oublog_posts p
            JOIN {$CFG->prefix}oublog_instances i
              ON p.oubloginstancesid=i.id
            WHERE i.oublogid={$blogid}";
    $recs = get_recordset_sql($sql);
    if ($recs->RecordCount()) {
        $status =fwrite ($bf,start_tag("POSTS",4,true));
        while ($rec = rs_fetch_next_record($recs)) {
            $status =fwrite ($bf,start_tag("POST",5,true));
            $fields = array('id', 'oubloginstancesid', 'groupid', 'title',
                            'message', 'timeposted', 'allowcomments',
                            'timeupdated', 'lasteditedby', 'deletedby',
                            'timedeleted', 'visibility');
            $status = write_rectags($bf, $fields, $rec, 6);
            $status =fwrite ($bf,end_tag("POST",5,true));
        }
        $status =fwrite ($bf,end_tag("POSTS",4,true));
    }
    unset($recs);

    // Blog post comments
    $sql = "SELECT c.*
            FROM {$CFG->prefix}oublog_comments c
            JOIN {$CFG->prefix}oublog_posts p
              ON c.postid=p.id
            JOIN {$CFG->prefix}oublog_instances i
              ON p.oubloginstancesid=i.id
            WHERE i.oublogid={$blogid}";
    $recs = get_recordset_sql($sql);
    if ($recs->RecordCount()) {
        $status =fwrite ($bf,start_tag("COMMENTS",4,true));
        while ($rec = rs_fetch_next_record($recs)) {
            $status =fwrite ($bf,start_tag("COMMENT",5,true));
            $fields = array('id', 'postid', 'userid', 'title', 'message',
                    'timeposted', 'deletedby', 'timedeleted',
                    'authorname', 'authorip', 'timeapproved');
            $status = write_rectags($bf, $fields, $rec, 6);
            $status =fwrite ($bf,end_tag("COMMENT",5,true));
        }
        $status =fwrite ($bf,end_tag("COMMENTS",4,true));
    }
    unset($recs);

    // Blog post edits
    $sql = "SELECT e.*
            FROM {$CFG->prefix}oublog_edits e
            JOIN {$CFG->prefix}oublog_posts p
              ON e.postid=p.id
            JOIN {$CFG->prefix}oublog_instances i
              ON p.oubloginstancesid=i.id
            WHERE i.oublogid={$blogid}";
    $recs = get_recordset_sql($sql);
    if ($recs->RecordCount()) {
        $status =fwrite ($bf,start_tag("EDITS",4,true));
        while ($rec = rs_fetch_next_record($recs)) {
            $status =fwrite ($bf,start_tag("EDIT",5,true));
            $fields = array('id', 'postid', 'userid', 'oldtitle',
                            'oldmessage', 'timeupdated');
            $status = write_rectags($bf, $fields, $rec, 6);
            $status =fwrite ($bf,end_tag("EDIT",5,true));
        }
        $status =fwrite ($bf,end_tag("EDITS",4,true));
    }
    unset($recs);

    // Blog post tags
    // NOTE: We store a rationalised view of
    //       tags to XML.
    $sql = "SELECT t.*, ti.postid
            FROM {$CFG->prefix}oublog_tags t
            JOIN {$CFG->prefix}oublog_taginstances ti
              ON t.id=ti.tagid
            JOIN {$CFG->prefix}oublog_posts p
              ON ti.postid=p.id
            JOIN {$CFG->prefix}oublog_instances i
              ON p.oubloginstancesid=i.id
            WHERE i.oublogid={$blogid}";
    $recs = get_recordset_sql($sql);
    if ($recs->RecordCount()) {
        $status =fwrite ($bf,start_tag("TAGS",4,true));
        while ($rec = rs_fetch_next_record($recs)) {
            $status =fwrite ($bf,start_tag("TAG",5,true));
            $fields = array('id', 'tagname', 'postid');
            $rec->tagname = $rec->tag; // use 'tagname' to disambiguate
            unset($rec->tag);          // from 'tag' in the XML
            $status = write_rectags($bf, $fields, $rec, 6);
            $status =fwrite ($bf,end_tag("TAG",5,true));
        }
        $status =fwrite ($bf,end_tag("TAGS",4,true));
    }
    unset($recs);

    return $status;
}

////Return an array of info (name,value)
function oublog_check_backup_mods($course,$user_data=false,$backup_unique_code, $instances=null) {
   global $CFG;

   if (!empty($instances) && is_array($instances) && count($instances)) {
       $info = array();
       foreach ($instances as $id => $instance) {
           $info += oublog_check_backup_mods_instances($instance,$backup_unique_code);
       }
       return $info;
   }
    //First the course data
    $info[0][0] = get_string("modulenameplural","oublog");
    $info[0][1] = get_field_sql("SELECT COUNT(id)
                                 FROM {$CFG->prefix}oublog
                                 WHERE course=$course");

    //Now, if requested, the user_data
    if ($user_data) {
        $info[1][0] = get_string("instances","oublog");
        $info[1][1] = get_field_sql("SELECT COUNT(i.id)
                                     FROM {$CFG->prefix}oublog b
                                     JOIN {$CFG->prefix}oublog_instances i
                                       ON b.id=i.oublogid
                                     WHERE b.course=$course");
    }
    return $info;
}

////Return an array of info (name,value)
function oublog_check_backup_mods_instances($instance,$backup_unique_code) {

    // NOTE here that the "instance" terminology refers to a
    // module instance (record in oublog) and NOT oublog_instance.

    global $CFG;
    //First the course data
    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';

    //Now, if requested, the user_data
    if (!empty($instance->userdata)) {
        $info[$instance->id.'1'][0] = get_string("posts","oublog");
        $info[$instance->id.'1'][1] = get_field_sql("SELECT COUNT(p.id)
                                                     FROM {$CFG->prefix}oublog_posts p
                                                     JOIN {$CFG->prefix}oublog_instances i
                                                       ON p.oubloginstancesid=i.id
                                                     WHERE i.oublogid=$instance->id");
        $info[$instance->id.'2'][0] = get_string("comments","oublog");
        $info[$instance->id.'2'][1] = get_field_sql("SELECT COUNT(c.id)
                                                     FROM {$CFG->prefix}oublog_comments c
                                                     JOIN {$CFG->prefix}oublog_posts p
                                                       ON c.postid=p.id
                                                     JOIN {$CFG->prefix}oublog_instances i
                                                       ON p.oubloginstancesid=i.id
                                                     WHERE i.oublogid=$instance->id");
        $info[$instance->id.'3'][0] = get_string("edits","oublog");
        $info[$instance->id.'3'][1] = get_field_sql("SELECT COUNT(e.id)
                                                     FROM {$CFG->prefix}oublog_edits e
                                                     JOIN {$CFG->prefix}oublog_posts p
                                                       ON e.postid=p.id
                                                     JOIN {$CFG->prefix}oublog_instances i
                                                       ON p.oubloginstancesid=i.id
                                                     WHERE i.oublogid=$instance->id");
        $info[$instance->id.'3'][0] = get_string("tags","oublog");
        $info[$instance->id.'3'][1] = get_field_sql("SELECT COUNT(t.id)
                                                     FROM {$CFG->prefix}oublog_tags t
                                                     JOIN {$CFG->prefix}oublog_taginstances ti
                                                       ON t.id=ti.tagid
                                                     JOIN {$CFG->prefix}oublog_posts p
                                                       ON ti.postid=p.id
                                                     JOIN {$CFG->prefix}oublog_instances i
                                                       ON p.oubloginstancesid=i.id
                                                     WHERE i.oublogid=$instance->id");
    }
    return $info;
}


//Return a content encoded to support interactivities linking. Every module
//should have its own. They are called automatically from the backup procedure.
function oublog_encode_content_links($content, $preferences) {

    global $CFG;

    $result = '';
    $base = preg_quote($CFG->wwwroot,"/");

    $regex = "/(".$base."\/mod\/oublog\/index.php\?id\=)([0-9]+)/";
    $result = preg_replace($regex,'$@OUBLOGINDEX*$2@$',$content);

    $regex = "/(".$base."\/mod\/oublog\/view.php\?id\=)([0-9]+)/";
    $result = preg_replace($regex,'$@OUBLOGVIEW*$2@$',$result);

    $regex = "/(".$base."\/mod\/oublog\/view.php\?user\=)([0-9]+)/";
    $result = preg_replace($regex,'$@OUBLOGVIEWUSER*$2@$',$result);

    $regex = "/(".$base."\/mod\/oublog\/viewpost.php\?post\=)([0-9]+)/";
    $result = preg_replace($regex,'$@OUBLOGVIEWPOST*$2@$',$result);

    return($result);
}

// INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE


function write_rectags($fh, $fields, $rec, $depth) {
    foreach($fields as $f) {
        $res = fwrite($fh,full_tag(strtoupper($f),
                                   $depth, false, $rec->$f));
        if ($res===false) {
            return false;
        }
    }
    return true;
}
?>
