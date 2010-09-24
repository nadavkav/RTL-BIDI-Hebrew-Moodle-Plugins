<?php
/**
 * Restore handling. 
 *
 * @copyright &copy; 2006 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require_once('ouwiki.php');

//This function executes all the restore procedure about this mod
function ouwiki_restore_mods($mod,$restore) {

    global $CFG;

    $status = true;

    //Get record from backup_ids
    if($data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id)) {
        try {
            if (!defined('RESTORE_SILENTLY')) {
                $name=$data->info['MOD']['#']['NAME']['0']['#'];
                echo "<li>".get_string('modulename','ouwiki').' "'.htmlspecialchars($name).'"</li>';
            }
            
            // Boom. Now try restoring!
            $xml=$data->info['MOD']['#'];
            $userdata=restore_userdata_selected($restore,'ouwiki',$mod->id);
            
            $ouwiki=new stdClass;
            $ouwiki->course=$restore->course_id;                   
            $ouwiki->name=addslashes($xml['NAME'][0]['#']);

            $ouwiki->subwikis=$xml['SUBWIKIS'][0]['#'];
            if(isset($xml['TIMEOUT'][0]['#'])) {
                $ouwiki->timeout=$xml['TIMEOUT'][0]['#'];
            }
            if(isset($xml['TEMPLATE'][0]['#'])) {
                $ouwiki->template=addslashes($xml['TEMPLATE'][0]['#']);
            }
            if(isset($xml['SUMMARY'][0]['#'])) {
                $ouwiki->summary=addslashes($xml['SUMMARY'][0]['#']);
            }
            if(isset($xml['EDITBEGIN'][0]['#'])) {
                $ouwiki->editbegin=addslashes($xml['EDITBEGIN'][0]['#']);
            }
            if(isset($xml['EDITEND'][0]['#'])) {
                $ouwiki->editend=addslashes($xml['EDITEND'][0]['#']);
            }
            if(isset($xml['COMPLETIONPAGES'][0]['#'])) {
                $ouwiki->completionpages=addslashes($xml['COMPLETIONPAGES'][0]['#']);
            }
            if(isset($xml['COMPLETIONEDITS'][0]['#'])) {
                $ouwiki->completionedits=addslashes($xml['COMPLETIONEDITS'][0]['#']);
            }
            if(isset($xml['COMMENTING'][0]['#'])) {
                $ouwiki->commenting=addslashes($xml['COMMENTING'][0]['#']);
            }
            if(!($ouwiki->id=insert_record('ouwiki',$ouwiki))) {
                throw new Exception('Error creating ouwiki instance');
            }                                   
            backup_putid($restore->backup_unique_code,$mod->modtype,$mod->id, $ouwiki->id);
            
            if(isset($xml['SUBS'][0]['#']['SUBWIKI'])) {
                foreach($xml['SUBS'][0]['#']['SUBWIKI'] as $xml_sub) {
                    ouwiki_restore_subwiki($restore,$xml_sub['#'],$ouwiki,$userdata);
                }
            }
            
            xml_backup::restore_module_files($restore->backup_unique_code,
                $restore->course_id,'ouwiki',$mod->id);
            $basepath=$CFG->dataroot.'/'.$restore->course_id.'/moddata/ouwiki';            
            rename($basepath.'/'.$mod->id,$basepath.'/'.$ouwiki->id);                                    
        } catch(Exception $e) {
            ouwiki_handle_backup_exception($e,'restore');
            $status=false;
        }
    }
    return $status;
}

//Return a content decoded to support interactivities linking. Every module
//should have its own. They are called automatically from
//ouwiki_decode_content_links_caller() function in each module
//in the restore process
function ouwiki_decode_content_links ($content,$restore) {
    global $CFG;
        
    $result = $content;
            
    //Link to the list of resourcepages
    $searchstring='/\$@(OUWIKIINDEX)\*([0-9]+)@\$/';
    //We look for it
    $foundset=array();
    preg_match_all($searchstring,$content,$foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (course id)
            $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
            //Personalize the searchstring
            $searchstring='/\$@(OUWIKIINDEX)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if($rec->new_id) {
                //Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/ouwiki/index.php?id='.$rec->new_id,$result);
            } else { 
                //It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/ouwiki/index.php?id='.$old_id,$result);
            }
        }
    }

    //Link to resourcepage view by moduleid
    $searchstring='/\$@(OUWIKIVIEWBYID)\*([0-9]+)@\$/';
    //We look for it
    preg_match_all($searchstring,$result,$foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
            //Personalize the searchstring
            $searchstring='/\$@(OUWIKIVIEWBYID)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if($rec->new_id) {
                //Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/ouwiki/view.php?id='.$rec->new_id,$result);
            } else {
                //It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/ouwiki/view.php?id='.$old_id,$result);
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
function ouwiki_decode_content_links_caller($restore) {
    // Get all the items that might have links in, from the relevant new course
    try {
        global $CFG, $db;
        
        // 1. Summaries 
        if($summaries=get_records_select('ouwiki','course='.$restore->course_id.' AND summary IS NOT NULL',
            '','id,summary')) {
            foreach($summaries as $summary) {
                $newsummary=restore_decode_content_links_worker($summary->summary,$restore);
                if($newsummary!=$summary->summary) {
                    if(!set_field('ouwiki','summary',addslashes($newsummary),'id',$summary->id)) {
                        throw new Exception("Failed to set summary for wiki {$summary->id}: ".$db->ErrorMsg());
                    }
                }
            }
        }
        
        // 2. Actual content
        $rs=get_recordset_sql("
SELECT
    v.id,v.xhtml     
FROM
    {$CFG->prefix}ouwiki w
    INNER JOIN {$CFG->prefix}ouwiki_subwikis s ON w.id=s.wikiid
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON s.id=p.subwikiid
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON p.id=v.pageid     
WHERE
    w.course={$restore->course_id} 
");
        if(!$rs) {
            throw new Exception("Failed to query for wiki data: ".$db->ErrorMsg());
        }
        while(!$rs->EOF) {
            $newcontent=restore_decode_content_links_worker($rs->fields['xhtml'],$restore);
            if($newcontent!=$rs->fields['xhtml']) {
                if(!set_field('ouwiki_versions','xhtml',addslashes($newcontent),'id',$rs->fields['id'])) {
                    throw new Exception("Failed to update content {$rs->fields['id']}: ".$db->ErrorMsg());
                }
            }
            $rs->MoveNext();
        }
        
        // 3. This is a bit crappy, as it isn't directly to do with content links, but
        //    we can't do it until we have a course-module so it can't happen earlier.
        if(ouwiki_search_installed()) {
            ouwiki_ousearch_update_all(false,$restore->course_id);
        }
        
        return true;
    } catch(Exception $e) {
        ouwiki_handle_backup_exception($e,'restore');
        return false;
    }    
}

//This function returns a log record with all the necessay transformations
//done. It's used by restore_log_module() to restore modules log.
function ouwiki_restore_logs($restore,$log) {
    //Depending of the action, we recode different things
    switch ($log->action) {
    case 'update':
    case 'add':
    case 'view':
        if ($log->cmid) {
            //Get the new_id of the module (to recode the info field)
            $mod = backup_getid($restoresettings->backup_unique_code,$log->module,$log->info);
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
        
    // Custom log actions
    // TODO sam
    
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
function ouwiki_restore_userid($string,&$restore) {
    if((string)$string==='0') {
        return 0;
    }
    $matches=array();
    if(preg_match('|^([0-9]+)/(.*)$|',$string,$matches)) {
        // Try backup_getid first
        $newid=backup_getid($restore->backup_unique_code,"user",$matches[1]);
        if($newid) {
            return $newid->new_id;
        } 
        // OK not there, see if they're still in real user db
        $realun=get_field('user','username','id',$matches[1]);
        if($realun===$matches[2]) {
            return $matches[1];
        }
    }
    return false;
}

function ouwiki_restore_subwiki($restore,$xml,$ouwiki,$userdata) {
    // Make new subwiki object
    $subwiki=new StdClass;
    if(isset($xml['GROUPID'][0]['#'])) {
        $newid = backup_getid($restore->backup_unique_code, 'groups', $xml['GROUPID'][0]['#']);
        if($newid && $newid->new_id) {
            $subwiki->groupid=$newid->new_id;
        } else {
            // Don't restore wikis for groups that no longer exist
            return;
        }
    }
    if(isset($xml['USERID'][0]['#'])) {
        $subwiki->userid=ouwiki_restore_userid($xml['USERID'][0]['#'],$restore);
        if(!$subwiki->userid) {
            // Don't restore wikis for users that no longer exist
            return;
        }
    }
    $subwiki->wikiid=$ouwiki->id;
    // There is no need to backup/restore the magic number as for security
    // it's better if it is different anyway.
    $subwiki->magic=ouwiki_generate_magic_number(); 
    if(!($subwiki->id=insert_record('ouwiki_subwikis',$subwiki))) {
        throw new Exception('Error creating subwiki object');
    }
    
    // Do pages (first pass that stores page IDs which are needed to convert links)
    $pageids=array();
    $versionids=array();
    foreach(ouwiki_get_restore_array($xml,'PAGES','PAGE') as $xml_sub) {
        ouwiki_restore_page($restore,$xml_sub['#'],$subwiki,$userdata,$pageids,$versionids);
    }
    
    // Add link entries (second pass)
    foreach(ouwiki_get_restore_array($xml,'PAGES','PAGE') as $xml_sub) {
        ouwiki_restore_page_links($restore,$xml_sub['#'],$subwiki,$userdata,$pageids,$versionids);
    }
}

function ouwiki_restore_page($restore,$xml,$subwiki,$userdata,&$pageids,&$versionids) {
    // Make new page object
    $page=new StdClass;
    $page->subwikiid=$subwiki->id;
    if(isset($xml['TITLE'][0]['#'])) {
        $page->title=addslashes($xml['TITLE'][0]['#']);
        // Allow backup/restore of 'old' pages with space(s) at the beginning or end of the title
        // Use ]] at start of page title as these are the only characters not allowed here 
        if (substr($page->title, 0, 2) == ']]' && substr($page->title, -2) == '[[') {
            $page->title = substr($page->title, 2, -2);
        }
    }

    if (isset($xml['LOCKED'][0]['#'])) {
        $page->locked = $xml['LOCKED'][0]['#'];
    }

    if(!($page->id=insert_record('ouwiki_pages',$page))) {
        throw new Exception('Error creating page object');
    }

    // Remember page ID    
    $pageids[$xml['ID'][0]['#']]=$page->id;

    // Do annotations
    $annotationids = array();
    foreach (ouwiki_get_restore_array($xml,'ANNOTATIONS','ANNOTATION') as $xml_sub) {
        $annotationids[] = ouwiki_restore_annotation($restore, $xml_sub['#'], $page, $userdata);
    }

    // Do versions
    $lastversion=-1;
    $pageversions = array();
    foreach (ouwiki_get_restore_array($xml,'VERSIONS','VERSION') as $xml_sub) {
        $lastversion=ouwiki_restore_version($restore, $xml_sub['#'], $page,$userdata, $annotationids);
        $pageversions[] = $lastversion;
    }
    $versionids[$xml['ID'][0]['#']] = $pageversions;

    if($lastversion>-1) {
        // This works because versions are printed out in order [of ID which amounts
        // to a time ordering] by backuplib.
        set_field('ouwiki_pages','currentversionid',$lastversion,'id',$page->id); 
    }
    
    // Do comments
    if($userdata) {
        $madesections=array();
        foreach(ouwiki_get_restore_array($xml,'COMMENTS','COMMENT') as $xml_sub) {
            ouwiki_restore_comment($restore,$xml_sub['#'],$page,$madesections);
        }
    }
}

function ouwiki_restore_comment($restore,$xml,$page,&$madesections) {
    // Section object
    $section=new StdClass;
    if(!empty($xml['SECTIONXHTMLID'][0]['#'])) {
        $section->xhtmlid=addslashes($xml['SECTIONXHTMLID'][0]['#']);
    } 
    // Have we already made it?
    $done=false;
    foreach($madesections as $madesection) {
        if(isset($section->xhtmlid)) {
            if(isset($madesection->xhtmlid) && $section->xhtmlid===$madesection->xhtmlid) {
                $section=$madesection;
                $done=true;
                break;
            }
        } else {
            if(!isset($madesection->xhtmlid)) {
                $section=$madesection;
                $done=true;
                break;
            }
        } 
    }
    
    // No, make it
    if(!$done) {
        // Fill in other bits
        $section->pageid=$page->id;
        if(!empty($xml['SECTIONTITLE'][0]['#'])) {
            $section->title=addslashes($xml['SECTIONTITLE'][0]['#']);
        }
        
        // Insert to DB
        if(!($section->id=insert_record('ouwiki_sections',$section))) {
            throw new Exception('Error creating section object');
        }
        
        // Remember we made it
        $madesections[]=$section;
    }
    
    // Set up comment object and insert to DB
    $comment=new StdClass;
    $comment->sectionid=$section->id;
    if(!empty($xml['TITLE'][0]['#'])) {
        $comment->title=addslashes($xml['TITLE'][0]['#']);
    } 
    $comment->xhtml=backup_todb($xml['XHTML'][0]['#']);
    if(!empty($xml['USERID'][0]['#'])) {
        $comment->userid=ouwiki_restore_userid($xml['USERID'][0]['#'],$restore);
        if(!$comment->userid) {
            // System comments
            unset($comment->userid);
        }
    } 
    $comment->timeposted=$xml['TIMEPOSTED'][0]['#'];
    $comment->deleted=$xml['DELETED'][0]['#'];
    if(!($comment->id=insert_record('ouwiki_comments',$comment))) {
        throw new Exception('Error creating comment object');
    }
}

function ouwiki_restore_annotation($restore, $xml, $page, $userdata) {
    // set up new annotation and insert into the db
    $annotation = new stdClass;
    $annotation->pageid = $page->id;
    $annotation->userid = $xml['USERID'][0]['#'];
    if (isset($xml['USERID'][0]['#'])) {
        $annotation->userid = ouwiki_restore_userid($xml['USERID'][0]['#'],$restore);
        if (!$annotation->userid) {
            notify('Not restoring annotation for user '.$xml['USERID'][0]['#'].
               ' because they do not exist on this server','ouwiki-notifyproblem');
            return;
        }
    }

    $annotation->timemodified = $xml['TIMEMODIFIED'][0]['#'];
    $annotation->content = backup_todb($xml['CONTENT'][0]['#']);
    if (!($annotation->id=insert_record('ouwiki_annotations',$annotation))) {
        throw new Exception('Error writing annotation record');
    }

    // return the old and new annotation ids for changing the spans in each page version
    return array($xml['ID'][0]['#'],$annotation->id);
}

function ouwiki_restore_version($restore,$xml,$page,$userdata, $annotationids) {
    // Make new version object
    $version=new StdClass;
    $version->pageid=$page->id;
    $version->xhtml=backup_todb($xml['XHTML'][0]['#']);
    $version->timecreated=$xml['TIMECREATED'][0]['#'];
    if(isset($xml['USERID'][0]['#']) && $userdata) {
        $version->userid=ouwiki_restore_userid($xml['USERID'][0]['#'],$restore);
        if(!$version->userid) {
            $version->userid=0;
        }        
    }
    if(isset($xml['CHANGESTART'][0]['#'])) {
        $version->changestart=$xml['CHANGESTART'][0]['#'];
    }
    if(isset($xml['CHANGESIZE'][0]['#'])) {
        $version->changesize=$xml['CHANGESIZE'][0]['#'];
    }
    if(isset($xml['CHANGEPREVSIZE'][0]['#'])) {
        $version->changeprevsize=$xml['CHANGEPREVSIZE'][0]['#'];
    }
    if(isset($xml['DELETEDAT'][0]['#'])) {
        $version->deletedat=$xml['DELETEDAT'][0]['#'];
    }

    // update the annotation spans with the ids of the newly restored annotation records
    foreach ($annotationids as $annotationid) {
        $version->xhtml = str_replace('annotation'.$annotationid[0], 'annotation'.$annotationid[1], $version->xhtml);
    }

    if(!($version->id=insert_record('ouwiki_versions',$version))) {
        throw new Exception('Error creating version object');
    }
    
    // Current version?
    if(isset($xml['CURRENT'][0]['#'])) {
        if(!set_field('ouwiki_pages','currentversionid',$version->id,'id',$page->id)) {
            throw new Exception('Error updating current version field');
        }
    }
    
    // Remember ID for second pass
    return $version->id;
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
function ouwiki_get_restore_array(&$current,$multiple,$single) {
    if(isset($current[$multiple][0]['#'][$single])) {
        return $current[$multiple][0]['#'][$single];
    } else {
        return array();
    } 
}

function ouwiki_restore_page_links($restore,$xml,$subwiki,$userdata,&$pageids,&$versionids) {
    
    // Loop through all versions
    $pageversions = $versionids[$xml['ID'][0]['#']];
    reset($pageversions);
    foreach(ouwiki_get_restore_array($xml,'VERSIONS','VERSION') as $xml_sub) {
        $versionid = current($pageversions);
        $versionxml=$xml_sub['#'];
        // And all links
        foreach(ouwiki_get_restore_array($versionxml,'LINKS','LINK') as $xml_subsub) {
            $linkxml=$xml_subsub['#'];
            
            // Build up link for database
            $link=new StdClass;
            $link->fromversionid=$versionid;
            if(isset($linkxml['TOPAGEID'][0]['#'])) {
                $link->topageid=$pageids[$linkxml['TOPAGEID'][0]['#']];
            }
            if(isset($linkxml['TOMISSINGPAGE'][0]['#'])) {
                $link->tomissingpage=addslashes($linkxml['TOMISSINGPAGE'][0]['#']);
            }
            if(isset($linkxml['TOURL'][0]['#'])) {
                $link->tourl=addslashes($linkxml['TOURL'][0]['#']);
            }
            if(!($link->id=insert_record('ouwiki_links',$link))) {
                throw new Exception('Error creating link object');
            }
        }
        next($pageversions);
    }    
}


    
?>
