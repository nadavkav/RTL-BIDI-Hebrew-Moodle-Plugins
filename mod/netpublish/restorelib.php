<?php // $Id: restorelib.php,v 1.3 2006/03/28 08:26:38 janne Exp $

    function netpublish_restore_mods($mod,$restore) {

        global $CFG,$db;
        static $count;

        if ( empty($count) ) {
            $count = 0;
        }
        $count++;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the NETPUBLISH record structure
            $netpublish = new stdClass;
            $netpublish->course = $restore->course_id;
            $netpublish->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $netpublish->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
            $netpublish->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
            $netpublish->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);
            $netpublish->maxsize = backup_todb($info['MOD']['#']['MAXSIZE']['0']['#']);
            $netpublish->loctime = backup_todb($info['MOD']['#']['LOCKTIME']['0']['#']);
            $netpublish->published = backup_todb($info['MOD']['#']['PUBLISHED']['0']['#']);
            $netpublish->fullpage = backup_todb($info['MOD']['#']['FULLPAGE']['0']['#']);
            $netpublish->statuscount = backup_todb($info['MOD']['#']['STATUSCOUNT']['0']['#']);
            $netpublish->scale = backup_todb($info['MOD']['#']['SCALE']['0']['#']);
            $firstsectionname = backup_todb($info['MOD']['#']['FIRSTSECTIONNAME']['0']['#']);

            $newid = insert_record("netpublish", $netpublish);

            //Do some output
            echo "<li>".get_string("modulename","netpublish")." \"".format_string(stripslashes($netpublish->name),true)."\"";
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if ($restore->mods['netpublish']->userinfo) {
                    //Restore netpublish files
                    if ( $count < 2 ) { // Restore files only once.
                        $status = netpublish_files_restore($restore->course_id,$info,$restore);
                    }
                    $status = netpublish_sections_restore($newid,$info,$restore);
                    $status = netpublish_articles_restore($newid,$info,$restore);
                    $status = netpublish_grades_restore($newid,$info,$restore);
                    $status = netpublish_article_status_restore($info,$restore);
                }

                // Add new first sectionname
                $fsection = new stdClass;
                $fsection->publishid = $newid;
                $fsection->name = $firstsectionname;
                insert_record("netpublish_first_section_names", $fsection);

            } else {
                $status = false;
            }
        } else {
            $status = false;
        }
        return $status;
    }

    function netpublish_files_restore($course,$info,$restore) {

        global $CFG;
        $status = true;

        //Get the discussions array
        $files = $info['MOD']['#']['NETPUBLISHFILES']['0']['#']['FILE'];
        //Iterate over files
        for($i = 0; $i < sizeof($files); $i++) {
            $file_info = $files[$i];
            //traverse_xmlize($file_info);                                  //Debug
            //print_object ($GLOBALS['traverse_array']);                    //Debug
            //$GLOBALS['traverse_array']="";                                //Debug

            //We'll need this later!!
            $oldid = backup_todb($file_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($file_info['#']['OWNER']['0']['#']);

            $file = new stdClass;
            $file->course = $course;
            $file->name = backup_todb($file_info['#']['NAME']['0']['#']);
            $file->path = backup_todb($file_info['#']['PATH']['0']['#']);
            $file->mimetype = backup_todb($file_info['#']['MIMETYPE']['0']['#']);
            $file->size = backup_todb($file_info['#']['SIZE']['0']['#']);
            $file->width = backup_todb($file_info['#']['WIDTH']['0']['#']);
            $file->height = backup_todb($file_info['#']['HEIGHT']['0']['#']);
            $file->timemodified = backup_todb($file_info['#']['TIMEMODIFIED']['0']['#']);
            $file->owner = backup_todb($file_info['#']['OWNER']['0']['#']);
            $file->dir = backup_todb($file_info['#']['DIR']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$file->owner);
            if ($user) {
                $file->owner = $user->new_id;
            }

            // Create new file name to prevent overwriting.
            $oldfilename = $file->path;
            $timestring = intval((time()/2) - $i);
            $newfilename = 'nbimg_'. $timestring .'.image';
            $file->path = 'netpublish_images/'. $newfilename;

            $newid = insert_record("netpublish_images",$file);

            if ( $newid ) {
                // Copy oldfile to newfile.
                $todo = false;
                $temp_file = '';
                $dest_dir = $CFG->dataroot .'/netpublish_images';
                $status = check_dir_exists($dest_dir,true);

                //Now locate the temp dir we are restoring from
                if ($status) {
                    $temp_file = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                                 "/moddata/". $oldfilename;
                    //Check it exists
                    if (is_file($temp_file)) {
                        $todo = true;
                    }
                }

                if ( $status && $todo ) {
                    $newfile = $dest_dir .'/'. $newfilename;
                    $status = backup_copy_file($temp_file, $newfile);
                }

            }

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"netpublish_images",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }
        return $status;
    }

    function netpublish_sections_restore($publishid,$info,$restore) {

        global $CFG;
        $status = true;

        //Get the sections array
        $sections = $info['MOD']['#']['SECTIONS']['0']['#']['SECTION'];

        //Iterate over posts
        for($i = 0; $i < sizeof($sections); $i++) {
            $sec_info = $sections[$i];
            //traverse_xmlize($sec_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sec_info['#']['ID']['0']['#']);

            $section = new stdClass;
            $section->id = backup_todb($sec_info['#']['ID']['0']['#']);
            $section->publishid = $publishid;
            $section->parentid  = backup_todb($sec_info['#']['PARENTID']['0']['#']);
            $section->fullname  = backup_todb($sec_info['#']['FULLNAME']['0']['#']);
            $section->sortorder = backup_todb($sec_info['#']['SORTORDER']['0']['#']);

            //The structure is equal to the db, so insert record
            $newid = insert_record ("netpublish_sections", $section);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"netpublish_sections",$oldid,
                             $newid);
            }

        }

        //Now we get every section in this netpublish and recalculate its parent post
        $sections = get_records ("netpublish_sections","publishid",$publishid);
        if ($sections) {
            //Iterate over each section
            foreach ($sections as $section) {
                //Get its parent
                $old_parent = $section->parentid;
                //Get its new id from backup_ids table
                $rec = backup_getid($restore->backup_unique_code,"netpublish_sections",$old_parent);
                if ($rec) {
                    //Put its new parent
                    $section->parentid = $rec->new_id;
                } else {
                     $section->parentid = '0';
                }
                //Create temp section record
                $temp_section->id = $section->id;
                $temp_section->parentid = $section->parentid;
                //echo "Updated parent ".$old_parent." to ".$temp_section->parent."<br />";                //Debug
                //Update section (only parent will be changed)
                $status = update_record("netpublish_sections",$temp_section);
            }
        }

        return $status;
    }

    function netpublish_articles_restore($publishid,$info,$restore) {

        global $CFG;
        $status = true;

        //Get the sections array
        $articles = $info['MOD']['#']['ARTICLES']['0']['#']['ARTICLE'];

        //Iterate over posts
        for($i = 0; $i < sizeof($articles); $i++) {
            $a_info = $articles[$i];
            //traverse_xmlize($a_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($a_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($a_info['#']['USERID']['0']['#']);
            $oldauthors = backup_todb($a_info['#']['AUTHORS']['0']['#']);
            $oldsectionid = backup_todb($a_info['#']['SECTIONID']['0']['#']);
            $oldteacherid = backup_todb($a_info['#']['TEACHERID']['0']['#']);

            $article = new stdClass;
            $article->publishid = $publishid;
            $article->sectionid = backup_todb($a_info['#']['SECTIONID']['0']['#']);
            $article->userid = backup_todb($a_info['#']['USERID']['0']['#']);
            $article->teacherid = backup_todb($a_info['#']['TEACHERID']['0']['#']);
            $article->prevarticle = backup_todb($a_info['#']['PREVARTICLE']['0']['#']);
            $article->nextarticle = backup_todb($a_info['#']['NEXTARTICLE']['0']['#']);
            $article->authors = backup_todb($a_info['#']['AUTHORS']['0']['#']);
            $article->title = backup_todb($a_info['#']['TITLE']['0']['#']);
            $article->intro = backup_todb($a_info['#']['INTRO']['0']['#']);
            $article->content = backup_todb($a_info['#']['CONTENT']['0']['#']);
            $article->timepublished = backup_todb($a_info['#']['TIMEPUBLISHED']['0']['#']);
            $article->timecreated = backup_todb($a_info['#']['TIMECREATED']['0']['#']);
            $article->timemodified = backup_todb($a_info['#']['TIMEMODIFIED']['0']['#']);
            $article->statusid = backup_todb($a_info['#']['STATUSID']['0']['#']);
            $article->rights = backup_todb($a_info['#']['RIGHTS']['0']['#']);
            $article->sortorder = backup_todb($a_info['#']['SORTORDER']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
            if ($user) {
                $article->userid = $user->new_id;
            }

            //We have to recode the teacherid field
            $teacher = backup_getid($restore->backup_unique_code,"user",$oldteacherid);
            if ($teacher) {
                $article->teacherid = $user->new_id;
            }

            //We have to recode the sectionid field
            $section = backup_getid($restore->backup_unique_code,"netpublish_sections",$oldsectionid);
            if ($section) {
                $article->sectionid = $section->new_id;
            }

            //We have to recode the authors field
            $oldauthors = explode(",", $oldauthors);
            $newauthors = array();
            if ( !empty($oldauthors) ) {
                foreach ( $oldauthors as $author ) {
                    $user = backup_getid($restore->backup_unique_code,"user",$author);
                    if ($user) {
                        array_push($newauthors, $user->new_id);
                    }
                }
                $article->authors = implode(",",$newauthors);
            }

            // We have to recode right field
            $rights = unserialize($article->rights);
            $newrights = array();
            if ( !empty($rights) ) {
                foreach ( $rights as $key => $value ) {
                    $user = backup_getid($restore->backup_unique_code,"user",$key);
                    if ( $user ) {
                        $newrights[$user->new_id] = $value;
                    }
                }
                $article->rights = serialize($newrights);
            }

            //The structure is equal to the db, so insert the netpublish_articles
            $newid = insert_record ("netpublish_articles",$article);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"netpublish_articles",$oldid,
                             $newid);
            }
        }
        return $status;
    }

    function netpublish_grades_restore($publishid,$info,$restore) {

        global $CFG;
        $status = true;

        //Get the sections array
        $grades = $info['MOD']['#']['NETPUBLISHGRADES']['0']['#']['NETPUBLISHGRADE'];

        //Iterate over posts
        for($i = 0; $i < sizeof($grades); $i++) {
            $g_info = $grades[$i];
            //traverse_xmlize($g_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($g_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($g_info['#']['USERID']['0']['#']);

            $grade = new stdClass;
            $grade->publishid = $publishid;
            $grade->userid = backup_todb($g_info['#']['USERID']['0']['#']);
            $grade->grade = backup_todb($g_info['#']['GRADE']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
            if ($user) {
                $grade->userid = $user->new_id;
            }

            $newid = insert_record("netpublish_grades", $grade);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"netpublish_grades",$oldid,
                             $newid);
            }
        }
        return $status;
    }

    function netpublish_article_status_restore($info,$restore) {

        global $CFG;

        $status = true;

        //Get the statuses array
        $statuses = $info['MOD']['#']['ARTICLES']['0']['#']['ARTICLESTATUS'];

        //Iterate over posts
        for($i = 0; $i < sizeof($statuses); $i++) {
            $s_info = $statuses[$i];
            //traverse_xmlize($s_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                //Debug
            //$GLOBALS['traverse_array']="";                                                            //Debug

            //We'll need this later!!
            $oldid = backup_todb($s_info['#']['ID']['0']['#']);
            $oldarticleid = backup_todb($s_info['#']['ARTICLEID']['0']['#']);
            $s = new stdClass;
            $s->articleid = backup_todb($s_info['#']['ARTICLEID']['0']['#']);
            $s->statusid  = backup_todb($s_info['#']['STATUSID']['0']['#']);
            $s->counter   = backup_todb($s_info['#']['COUNTER']['0']['#']);

            // we have to recode articleid.
            $article = backup_getid($restore->backup_unique_code,"netpublish_articles",$oldarticleid);
            if ($article) {
                $s->articleid = $article->new_id;
            }

            $newid = insert_record("netpublish_status_records", $s);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"netpublish_status_records",$oldid,
                             $newid);
            }
        }
        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //netpublish_decode_content_links_caller() function in each module
    //in the restore process
    function netpublish_decode_content_links ($content,$restore) {

        global $CFG;

        $result = $content;

        //Link to the list of netpublishes

        $searchstring='/\$@(NETPUBLISHINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(NETPUBLISHINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/netpublish/index.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/netpublish/index.php?id='.$old_id,$result);
                }
            }
        }

        // Images
        $searchstring='/\$@(NETPUBLISHIMAGE)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"netpublish_images",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(NETPUBLISHIMAGE)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/netpublish/image.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/netpublish/image.php?id='.$old_id,$result);
                }
            }
        }

        //Link to netpublish view by moduleid

        $searchstring='/\$@(NETPUBLISHVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(NETPUBLISHVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/netpublish/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/netpublish/view.php?id='.$old_id,$result);
                }
            }
        }

        $searchstring='/\$@(NETPUBLISHSECTION)\*([0-9]+)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2] and foundset[3]. They are the old_ids
            foreach($foundset[2] as $key => $old_id) {
                $old_id2 = $foundset[3][$key];
                //We get the needed variables here (discussion id and post id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                $rec2 = backup_getid($restore->backup_unique_code,"netpublish_sections",$old_id2);
                //Personalize the searchstring
                $searchstring='/\$@(NETPUBLISHSECTION)\*('.$old_id.')\*('.$old_id2.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id && $rec2->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/netpublish/view.php?id='.$rec->new_id.'&section='.$rec2->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/netpublish/view.php?id='.$old_id.'&section='.$old_id2,$result);
                }
            }
        }

        //section + article.

        $searchstring='/\$@(NETPUBLISHSECTIONARTICLE)\*([0-9]+)\*([0-9]+)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2] and foundset[3]. They are the old_ids
            foreach($foundset[2] as $key => $old_id) {
                $old_id2 = $foundset[3][$key];
                $old_id3 = $foundset[4][$key];
                //We get the needed variables here (discussion id and post id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                $rec2 = backup_getid($restore->backup_unique_code,"netpublish_sections",$old_id2);
                $rec3 = backup_getid($restore->backup_unique_code,"netpublish_articles",$old_id3);
                //Personalize the searchstring
                $searchstring='/\$@(NETPUBLISHSECTIONARTICLE)\*('.$old_id.')\*('.$old_id2.')\*('.$old_id3.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id && $rec2->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.
                             '/mod/netpublish/view.php?id='.$rec->new_id.'&section='.$rec2->new_id .'&article='.$rec3->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.
                             '/mod/netpublish/view.php?id='.$old_id.'&section'.$old_id2 .'&article='.$old_id3,$result);
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
    function netpublish_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        //Process every netpublish article intro and content in the course
        if ($articles = get_records_sql ("SELECT a.id, a.intro, a.content
                                          FROM {$CFG->prefix}netpublish_articles a,
                                               {$CFG->prefix}netpublish n
                                          WHERE n.course = $restore->course_id
                                          AND a.publishid = n.id")) {
            //Iterate
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($articles as $article) {
                //Increment counter
                $i++;
                $intro   = !empty($article->intro)   ? $article->intro : '';
                $result = restore_decode_content_links_worker($intro,$restore);
                if ($result != $intro) {
                    //Update record
                    $article->intro = addslashes($result);
                    $status = update_record("netpublish_articles",$article);
                    if ($CFG->debug>7) {
                        echo '<br /><hr />'.htmlentities($intro).'<br />changed to<br />'.htmlentities($result).'<hr /><br />';
                    }
                }
                $content = !empty($article->content) ? $article->content : '';
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $article->content = addslashes($result);
                    $status = update_record("netpublish_articles",$article);
                    if ($CFG->debug>7) {
                        echo '<br /><hr />'.htmlentities($content).'<br />changed to<br />'.htmlentities($result).'<hr /><br />';
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                    backup_flush(300);
                }
            }
        }

        //Process every NETPUBLISH (intro) in the course
        if ($netpublishes = get_records_sql ("SELECT n.id, n.intro
                                   FROM {$CFG->prefix}netpublish n
                                   WHERE n.course = $restore->course_id")) {
            //Iterate over each netpublish->intro
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($netpublishes as $netpublish) {
                //Increment counter
                $i++;
                $content = $netpublish->intro;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $netpublish->intro = addslashes($result);
                    $status = update_record("netpublish",$netpublish);
                    if ($CFG->debug>7) {
                        echo '<br /><hr />'.htmlentities($content).'<br />changed to<br />'.htmlentities($result).'<hr /><br />';
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                backup_flush(300);
                }
            }
        }

        return $status;
    }
?>