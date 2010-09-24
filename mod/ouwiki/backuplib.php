<?php
/**
 * Backup handling for wiki. Unlike some other backup files, 
 * the module's data structures are not documented here. Please see
 * the proper documentation for a database diagram and
 * detailed description.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require_once('ouwiki.php');

function ouwiki_backup_mods($bf,$preferences) {
    
    global $CFG;

    $status = true;

    //Iterate over resourcepage table
    $mods = get_records ('ouwiki','course',$preferences->backup_course,"id");
    if ($mods) {
        foreach ($mods as $mod) {
            if (backup_mod_selected($preferences,'ouwiki',$mod->id)) {
                $status = ouwiki_backup_one_mod($bf,$preferences,$mod);
            }
        }
    }
    return $status;
}

function ouwiki_backup_one_mod($bf,$preferences,$ouwiki) {

    global $CFG;

    if (is_numeric($ouwiki)) {
        $ouwiki = get_record('ouwiki','id',$ouwiki);
    }
    
    try {
        $xb=new xml_backup($bf,$preferences,3);
        $xb->tag_start('MOD');
        
        // Required bits
        $xb->tag_full('ID',$ouwiki->id);
        $xb->tag_full('MODTYPE','ouwiki');
        $xb->tag_full('NAME',$ouwiki->name);
        
        // Backup versioning 
        require(dirname(__FILE__).'/version.php');
        $xb->tag_full('OUWIKI_VERSION',$module->version);
        
        // Wiki-specific
        $xb->tag_full('SUBWIKIS',$ouwiki->subwikis);
        $userdata=backup_userdata_selected($preferences,'ouwiki',$ouwiki->id);
        $xb->tag_full_notnull('TIMEOUT',$ouwiki->timeout);

        if($ouwiki->template) {
            // Template setting and actual file
            $xb->tag_full('TEMPLATE',$ouwiki->template);
            $xb->copy_module_file('ouwiki',$ouwiki->id.'/'.$ouwiki->template);            
        }

        $xb->tag_full_notnull('SUMMARY',$ouwiki->summary);
        $xb->tag_full_notnull('EDITBEGIN',$ouwiki->editbegin);
        $xb->tag_full_notnull('EDITEND',$ouwiki->editend);
        $xb->tag_full('COMPLETIONPAGES',$ouwiki->completionpages);
        $xb->tag_full('COMPLETIONEDITS',$ouwiki->completionedits);
        $xb->tag_full('COMMENTING',$ouwiki->commenting);
        
        // Now do the actual subwikis
        $xb->tag_start('SUBS'); // not SUBWIKIS because that name already used
        
        // We only back up content when 'user data' is turned on
        if($userdata) {
            $subwikis=ouwiki_get_record_array('subwikis','wikiid',$ouwiki->id);
            foreach($subwikis as $subwiki) {
                ouwiki_backup_subwiki($xb,$subwiki,$userdata);
            }
        }
        
        $xb->tag_end('SUBS');        
        $xb->tag_end('MOD');
        
        return true;
    } catch(Exception $e) {
        ouwiki_handle_backup_exception($e);
        return false;        
    }
}

////Return an array of info (name,value)
function ouwiki_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
    
    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += ouwiki_check_backup_mods_instances($instance,$backup_unique_code);
        }
        return $info;
    }
    //First the course data
    $info[0][0] = get_string('modulenameplural','ouwiki');
    $info[0][1] = count_records('ouwiki','course',$course);
    
    //User-specific data doesn't get listed here
    return $info;
}

////Return an array of info (name,value)
function ouwiki_check_backup_mods_instances($instance,$backup_unique_code) {
    global $CFG;
    // Name
    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    // Number of pages (just for info)
    $count=get_record_sql("
SELECT 
    COUNT(*) AS pages
FROM
    {$CFG->prefix}ouwiki_subwikis sw
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.subwikiid=sw.id
WHERE
    sw.wikiid={$instance->id}");
    $info[$instance->id.'0'][1] = $count->pages.' pages';
    
    // Again, no user-specific data
    
    return $info;
}


//Return a content encoded to support interactivities linking. Every module
//should have its own. They are called automatically from the backup procedure.
function ouwiki_encode_content_links ($content,$preferences) {

    global $CFG;

    $base = preg_quote($CFG->wwwroot,"/");

    //Link to the list of wikis
    $buscar="/(".$base."\/mod\/ouwiki\/index.php\?id\=)([0-9]+)/";
    $result= preg_replace($buscar,'$@OUWIKIINDEX*$2@$',$content);

    //Link to wiki view by moduleid
    $buscar="/(".$base."\/mod\/ouwiki\/view.php\?id\=)([0-9]+)/";
    $result= preg_replace($buscar,'$@OUWIKIVIEWBYID*$2@$',$result);

    return $result;
}

// Wiki-specific parts that aren't relied on by other bits of Moodle
////////////////////////////////////////////////////////////////////

function ouwiki_backup_userid($xb,$userid,$name='USERID') {
    // Don't write anything for null userid
    if($userid===null) {
        return;
    }
    // If userid is non-zero, get the username to include too
    $xb->tag_full($name,$userid.
        ($userid===0 ? '' : '/'.get_field('user','username','id',$userid)));
}
  

function ouwiki_backup_subwiki($xb,$subwiki,$userdata) {
    $xb->tag_start('SUBWIKI');
    
    // Identification details
    $xb->tag_full_notnull('GROUPID',$subwiki->groupid);
    ouwiki_backup_userid($xb,$subwiki->userid);
    
    // Do all the pages
    $xb->tag_start('PAGES');
    $pages=ouwiki_get_record_array('pages','subwikiid',$subwiki->id);
    foreach($pages as $page) {
        ouwiki_backup_page($xb,$page,$userdata);        
    }
    $xb->tag_end('PAGES');
    
    $xb->tag_end('SUBWIKI');
}

function ouwiki_backup_page($xb,$page,$userdata) {
    $xb->tag_start('PAGE');
    
    $xb->tag_full('ID',$page->id);
    // Allow backup/restore of 'old' pages with space(s) at the beginning or end of the title
    // Use ]] at start of page title as these are the only characters not allowed here 
    if (isset($page->title)) {
        $xb->tag_full('TITLE',']]'.$page->title.'[[');
    }
    if (isset($page->locked)) {
        $xb->tag_full('LOCKED', $page->locked);   
    }
    
    $xb->tag_start('VERSIONS');
    $versions=ouwiki_get_record_array('versions','pageid',$page->id,'id');
    foreach($versions as $version) {
        ouwiki_backup_version($xb,$version,$userdata,$page->currentversionid==$version->id);
    }
    $xb->tag_end('VERSIONS');
    
    $xb->tag_start('COMMENTS');
    global $CFG;
    $comments=get_records_sql("
SELECT
    c.id,c.title,c.xhtml,c.userid,c.timeposted,c.deleted,s.xhtmlid AS sectionxhtmlid,s.title AS sectiontitle
FROM
    {$CFG->prefix}ouwiki_sections s
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.sectionid=s.id
WHERE
    s.pageid={$page->id}");
    if($comments) {
        foreach($comments as $comment) {
            $xb->tag_start('COMMENT');
            $xb->tag_full_notnull('TITLE',$comment->title);
            $xb->tag_full('XHTML',$comment->xhtml);
            ouwiki_backup_userid($xb,$comment->userid);
            $xb->tag_full('TIMEPOSTED',$comment->timeposted);
            $xb->tag_full('DELETED',$comment->deleted);
            $xb->tag_full_notnull('SECTIONTITLE',$comment->sectiontitle);
            $xb->tag_full_notnull('SECTIONXHTMLID',$comment->sectionxhtmlid);
            $xb->tag_end('COMMENT');
        }
    }
    $xb->tag_end('COMMENTS');
    
    $xb->tag_start('ANNOTATIONS');
    $annotations = ouwiki_get_record_array('annotations','pageid',$page->id);
    if ($annotations) {
        foreach($annotations as $annotation) {
            ouwiki_backup_annotation($xb,$annotation,$userdata);        
        }
    }
    $xb->tag_end('ANNOTATIONS');
    
    $xb->tag_end('PAGE');
}

function ouwiki_backup_annotation($xb, $annotation, $userdata) {
    $xb->tag_start('ANNOTATION');
    $xb->tag_full('ID', $annotation->id);
    ouwiki_backup_userid($xb,$annotation->userid);
    $xb->tag_full('TIMEMODIFIED', $annotation->timemodified);
    $xb->tag_full('CONTENT', $annotation->content);
    $xb->tag_end('ANNOTATION');
}

function ouwiki_backup_version($xb,$version,$userdata,$current) {
    $xb->tag_start('VERSION');

    // Current marker. May not be strictly needed as current should
    // always be most recent version, but this ensures we can preserve
    // the database values if that behaviour changes or something.    
    if($current) {
        $xb->tag_full('CURRENT','1');
    }
    
    // Basic data from table
    $xb->tag_full('XHTML',$version->xhtml);
    $xb->tag_full('TIMECREATED',$version->timecreated);
    ouwiki_backup_userid($xb,$version->userid);
    $xb->tag_full_notnull('CHANGESTART',$version->changestart);
    $xb->tag_full_notnull('CHANGESIZE',$version->changesize);
    $xb->tag_full_notnull('CHANGEPREVSIZE',$version->changeprevsize);
    $xb->tag_full_notnull('DELETEDAT',$version->deletedat);
    
    // Links for this version
    $xb->tag_start('LINKS');
    $links=ouwiki_get_record_array('links','fromversionid',$version->id);
    foreach($links as $link) {
        $xb->tag_start('LINK');
        $xb->tag_full_notnull('TOPAGEID',$link->topageid);
        $xb->tag_full_notnull('TOMISSINGPAGE',$link->tomissingpage);
        $xb->tag_full_notnull('TOURL',$link->tourl);
        $xb->tag_end('LINK');
    }
    $xb->tag_end('LINKS');
    
    $xb->tag_end('VERSION');
}


/**
 * Convenience wrapper around get_records that turns 'false' result
 * into the zero-length array that it should always have been.
 * @param string $subtable Name of table e.g. 'frog' for prefix_ouwiki_frog
 * @param string $field Field being checked
 * @param string $orderby Optional ordering
 * @param int $value Required value of field
 * @return array Array of records, 0-length if none
 */
function ouwiki_get_record_array($subtable,$field,$value,$orderby='') {
    $records=get_records('ouwiki_'.$subtable,$field,$value,$orderby);
    if(!$records) {
        $records=array(); 
    }
    return $records;
}
