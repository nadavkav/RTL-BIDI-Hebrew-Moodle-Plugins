<?php // $Id: backuplib.php,v 1.3 2006/12/12 07:08:01 janne Exp $

    // This is backup library for netpublish module.
    //This function executes all the backup procedure about this mod
    function netpublish_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        ////Iterate over imagegallery table
        if ($netpublishes = get_records ("netpublish","course", $preferences->backup_course,"id")) {
            foreach ($netpublishes as $netpublish) {
                if (backup_mod_selected($preferences,'netpublish',$netpublish->id)) {
                    $status = netpublish_backup_one_mod($bf,$preferences,$netpublish);
                }
            }
        }
        return $status;
    }

    function netpublish_backup_one_mod($bf,$preferences,$netpublish) {

        global $CFG;

        $status = true;
        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print forum data
        fwrite ($bf,full_tag("ID",4,false,$netpublish->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"netpublish"));
        fwrite ($bf,full_tag("NAME",4,false,$netpublish->name));
        fwrite ($bf,full_tag("INTRO",4,false,$netpublish->intro));
        fwrite ($bf,full_tag("TIMECREATED",4,false,$netpublish->timecreated));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$netpublish->timemodified));
        fwrite ($bf,full_tag("MAXSIZE",4,false,$netpublish->maxsize));
        fwrite ($bf,full_tag("LOCKTIME",4,false,$netpublish->locktime));
        fwrite ($bf,full_tag("PUBLISHED",4,false,$netpublish->published));
        fwrite ($bf,full_tag("FULLPAGE",4,false,$netpublish->fullpage));
        fwrite ($bf,full_tag("STATUSCOUNT",4,false,$netpublish->statuscount));
        fwrite ($bf,full_tag("SCALE",4,false,$netpublish->scale));


        if (backup_userdata_selected($preferences,'netpublish',$netpublish->id)) {
            // Backup grades.
            $status = backup_netpublish_grades($bf,$preferences,$netpublish->id);
            // Backup first section names.
            $status = backup_netpublish_first_section_name($bf,$preferences,$netpublish->id);
            if ( $status ) {
                // Backup sections.
                $status = backup_netpublish_sections($bf,$preferences,$netpublish->id);
                // Get articles in this netpublish and section
                $status = backup_netpublish_articles($bf,$preferences,$netpublish->id);
            }
        }
        // if we've selected to backup users info, then backup files too only once
        // since netpublishes files are shared through course.
        if ($status) {
            if ($preferences->mods["netpublish"]->userinfo && $count < 2) {
                $status = backup_netpublish_files($bf,$preferences);
            }
        }
        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));
        return $status;
    }

    function backup_netpublish_grades($bf,$preferences,$publishid) {

        global $CFG;
        $status = true;

        $grades = get_records("netpublish_grades", "publishid", $publishid, "id");

        if ( $grades ) {
            $status = fwrite($bf,start_tag("NETPUBLISHGRADES",4,true));
            foreach ( $grades as $grade ) {
                $status = fwrite ($bf,start_tag("NETPUBLISHGRADE",5,true));
                fwrite ($bf,full_tag("ID",6,false,$grade->id));
                fwrite ($bf,full_tag("PUBLISHID",6,false,$publishid));
                fwrite ($bf,full_tag("USERID",6,false,$grade->userid));
                fwrite ($bf,full_tag("GRADE",6,false,$grade->grade));
                $status = fwrite ($bf,end_tag("NETPUBLISHGRADE",5,true));
            }
            $status = fwrite($bf,end_tag("NETPUBLISHGRADES",4,true));
        }
        return $status;
    }

    function backup_netpublish_first_section_name($bf,$preferences,$publishid) {
        global $CFG;
        $status = true;

        $sectionname = get_record("netpublish_first_section_names","publishid",$publishid);

        if ( $sectionname ) {
            fwrite ($bf,full_tag("FIRSTSECTIONNAME",4,false,$sectionname->name));
        }
        return $status;
    }

    function backup_netpublish_sections($bf,$preferences,$publishid) {
        global $CFG;

        $status = true;

        $sections = get_records("netpublish_sections","publishid",$publishid,"id");

        if ($sections) {
            $status = fwrite ($bf,start_tag("SECTIONS",4,true));
            foreach ( $sections as $section ) {
                $status = fwrite ($bf,start_tag("SECTION",5,true));
                fwrite ($bf,full_tag("ID",6,false,$section->id));
                fwrite ($bf,full_tag("PUBLISHID",6,false,$section->publishid));
                fwrite ($bf,full_tag("PARENTID",6,false,$section->parentid));
                fwrite ($bf,full_tag("FULLNAME",6,false,$section->fullname));
                fwrite ($bf,full_tag("SORTORDER",6,false,$section->sortorder));
                $status =fwrite ($bf,end_tag("SECTION",5,true));
            }
            $status =fwrite ($bf,end_tag("SECTIONS",4,true));
        }
        return $status;
    }

    function backup_netpublish_articles($bf,$preferences,$publishid) {

        global $CFG;
        $status = true;

        $articles = get_records("netpublish_articles", "publishid", $publishid, "id");

        if ( $articles ) {
            $status = fwrite ($bf,start_tag("ARTICLES",5,true));
            foreach ( $articles as $a ) {
                $status = fwrite($bf,start_tag("ARTICLE",6,true));
                fwrite ($bf,full_tag("ID",7,false,$a->id));
                fwrite ($bf,full_tag("PUBLISHID",7,false,$publishid));
                fwrite ($bf,full_tag("SECTIONID",7,false,$a->sectionid));
                fwrite ($bf,full_tag("USERID",7,false,$a->userid));
                fwrite ($bf,full_tag("TEACHERID",7,false,$a->teacherid));
                fwrite ($bf,full_tag("PREVARTICLE",7,false,$a->prevarticle));
                fwrite ($bf,full_tag("NEXTARTICLE",7,false,$a->nextarticle));
                fwrite ($bf,full_tag("AUTHORS",7,false,$a->authors));
                fwrite ($bf,full_tag("TITLE",7,false,$a->title));
                fwrite ($bf,full_tag("INTRO",7,false,$a->intro));
                fwrite ($bf,full_tag("CONTENT",7,false,$a->content));
                fwrite ($bf,full_tag("TIMEPUBLISHED",7,false,$a->timepublished));
                fwrite ($bf,full_tag("TIMECREATED",7,false,$a->timecreated));
                fwrite ($bf,full_tag("TIMEMODIFIED",7,false,$a->timemodified));
                fwrite ($bf,full_tag("STATUSID",7,false,$a->statusid));
                fwrite ($bf,full_tag("RIGHTS",7,false,$a->rights));
                fwrite ($bf,full_tag("SORTORDER",7,false,$a->sortorder));
                $status = fwrite($bf,end_tag("ARTICLE",6,true));
                // Backup article statuses
                $status = backup_article_status($bf,$preferences,$a->id);
            }
            $status = fwrite ($bf,end_tag("ARTICLES",5,true));
        }
        return $status;
    }

    function backup_article_status($bf,$prefrences,$articleid) {
        global $CFG;
        $status = true;
        $articlestatus = get_record("netpublish_status_records","articleid",$articleid);

        if ( $articlestatus ) {
            $status = fwrite ($bf,start_tag("ARTICLESTATUS",4,true));
            fwrite ($bf,full_tag("ID",5,false,$articlestatus->id));
            fwrite ($bf,full_tag("ARTICLEID",5,false,$articleid));
            fwrite ($bf,full_tag("STATUSID",5,false,$articlestatus->statusid));
            fwrite ($bf,full_tag("COUNTER",5,false,$articlestatus->counter));
            $status =fwrite ($bf,end_tag("ARTICLESTATUS",4,true));
        }
        return $status;
    }

    //Backup netpublish files because we've selected to backup user info
    //and files are user info's level
    function backup_netpublish_files($bf,$preferences) {

        global $CFG;

        $status = true;

        $files = get_records("netpublish_images", "course", $preferences->backup_course, "id");
        if ( $files ) {
            $status = fwrite ($bf,start_tag("NETPUBLISHFILES",4,true));
            foreach ( $files as $file ) {
                $status = fwrite ($bf,start_tag("FILE",5,true));
                fwrite ($bf,full_tag("ID",6,false,$file->id));
                fwrite ($bf,full_tag("COURSE",6,false,$file->course));
                fwrite ($bf,full_tag("NAME",6,false,$file->name));
                fwrite ($bf,full_tag("PATH",6,false,$file->path));
                fwrite ($bf,full_tag("MIMETYPE",6,false,$file->mimetype));
                fwrite ($bf,full_tag("SIZE",6,false,$file->size));
                fwrite ($bf,full_tag("WIDTH",6,false,$file->width));
                fwrite ($bf,full_tag("HEIGHT",6,false,$file->height));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$file->timemodified));
                fwrite ($bf,full_tag("OWNER",6,false,$file->owner));
                fwrite ($bf,full_tag("DIR",6,false,$file->dir));
                $status = fwrite ($bf,end_tag("FILE",5,true));
            }
            $status = fwrite($bf,end_tag("NETPUBLISHFILES",4,true));
        }
        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        //Now copy the forum dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/netpublish_images")) {
                $status = backup_copy_file($CFG->dataroot."/netpublish_images",
                                           $CFG->dataroot."/temp/backup/".
                                           $preferences->backup_unique_code.
                                           "/moddata/netpublish_images");
            }
        }

        return $status;

    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function netpublish_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of netpublishes
        $buscar="/(".$base."\/mod\/netpublish\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@NETPUBLISHINDEX*$2@$',$content);

        //Link to netpublish view by moduleid
        $buscar="/(".$base."\/mod\/netpublish\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@NETPUBLISHVIEWBYID*$2@$',$result);

        //Link to netpublish section
        $buscar="/(".$base."\/mod\/netpublish\/view.php\?id\=)([0-9]+)\&section\=([0-9]+)/";
        $result= preg_replace($buscar,'$@NETPUBLISHSECTION*$2*$3@$',$result);

        //Link to netpublish section and article
        $buscar="/(".$base."\/mod\/netpublish\/view.php\?id\=)([0-9]+)\&section\=([0-9]+)\&article\=([0-9]+)/";
        $result= preg_replace($buscar,'$@NETPUBLISHSECTIONARTICLE*$2*$3*$4@$',$result);

        //Link to netpublish image.
        $buscar="/(".$base."\/mod\/netpublish\/image.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@NETPUBLISHIMAGE*$2@$',$result);

        return $result;
    }


    ////Return an array of info (name,value)
   function netpublish_check_backup_mods($course,$user_data=false,$backup_unique_code) {
        //First the course data
        $info[0][0] = get_string("modulenameplural","netpublish");
        if ($ids = netpublish_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            //Subscriptions
            $info[1][0] = get_string("articles","netpublish");
            if ($ids = netpublish_article_ids_by_course ($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

    function netpublish_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}netpublish a
                                 WHERE a.course = '$course'");
    }

    function netpublish_article_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.publishid
                                 FROM {$CFG->prefix}netpublish_articles s,
                                      {$CFG->prefix}netpublish a
                                 WHERE a.course = '$course' AND
                                       s.publishid = a.id");
    }

?>