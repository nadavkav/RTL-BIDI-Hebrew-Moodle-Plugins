<?php
/**
 * Standard API to Moodle core.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require_once(dirname(__FILE__).'/ouwiki.php');

function ouwiki_add_instance($ouwiki) {
    // Set up null values
    $nullvalues=array('editbegin','editend','timeout');
    foreach($nullvalues as $nullvalue) {
        if(empty($ouwiki->{$nullvalue})) {
            unset($ouwiki->{$nullvalue});
        }
    }
    if(strlen(preg_replace('/(<.*?>)|(&.*?;)|\s/','',$ouwiki->summary))==0) {
        unset($ouwiki->summary);
    }

    ouwiki_check_groups($ouwiki);

    // Create record
    $id=insert_record("ouwiki", $ouwiki);
    $ok=$id?true:false;

    // Set up template if provided. Note that it is not possible to use the
    // proper form / upload manager system because that doesn't cope with
    // optional files (at time of writing). Also even getting access to the
    // form here was a hack.
    if(count($_FILES)==1) {
        global $CFG;
        $templatefolder=$CFG->dataroot.'/'.$ouwiki->course.'/moddata/ouwiki/'.$id;
        foreach($_FILES as $file) {
            if($file['tmp_name']) {
                $newname=preg_replace('/[^A-Za-z0-9-.]/','_',$file['name']);
                mkdir_recursive($templatefolder);
                rename($file['tmp_name'],$templatefolder.'/'.$newname);

                $addtemp=new StdClass;
                $addtemp->id=$id;
                $addtemp->template=$newname;
                $ok&=update_record("ouwiki",$addtemp);
            }
        }
    }

    return $id;
}

function ouwiki_check_groups(&$ouwiki) {
    // Ensure group mode matches subwiki option
    if((int)$ouwiki->groupmode===0 && (int)$ouwiki->subwikis===1) {
        error('When selecting a per-group wiki you must also set group mode to visible or separate.');
    }
    if((int)$ouwiki->groupmode!==0 && (int)$ouwiki->subwikis!==1) {
        error('When enabling group mode you must also set the sub-wikis option to select a per-group wiki.');
    }
}

function ouwiki_update_instance($ouwiki) {
    global $CFG;
    $ok=true;
    $ouwiki->id=$ouwiki->instance;

    // Set up null values
    $nullvalues=array('editbegin','editend','timeout');
    foreach($nullvalues as $nullvalue) {
        if(empty($ouwiki->{$nullvalue})) {
            unset($ouwiki->{$nullvalue});
            $ok &= execute_sql("UPDATE {$CFG->prefix}ouwiki SET $nullvalue=NULL WHERE id={$ouwiki->id}",false);
        }
    }
    if(strlen(preg_replace('/(<.*?>)|(&.*?;)|\s/','',$ouwiki->summary))==0) {
        unset($ouwiki->summary);
        $ok &= execute_sql("UPDATE {$CFG->prefix}ouwiki SET summary=NULL WHERE id={$ouwiki->id}",false);
    }

    ouwiki_check_groups($ouwiki);

    // insitu editing
    if (class_exists('ouflags') && has_capability('local/course:revisioneditor', get_context_instance(CONTEXT_COURSE, $ouwiki->course),null,false)) {
        include_once($CFG->dirroot.'/local/insitu/lib.php');
        return oci_mod_make_backup_and_save_instance($ouwiki);
    }

    // Update main record
    $ok &= update_record("ouwiki", $ouwiki);

    return $ok;
}

function ouwiki_delete_instance($id) {
    if(ouwiki_search_installed()) {
        $moduleid=get_field('modules','id','name','ouwiki');
        $cm=get_record('course_modules','module',$moduleid,'instance',$id);
        if(!$cm) {
            error('Can\'t find coursemodule');
        }
        ousearch_document::delete_module_instance_data($cm);
    }

    global $CFG;
    // Subqueries that find all versions and pages associated with this wiki
    $versionquery="
SELECT
    v.id
FROM
    {$CFG->prefix}ouwiki_subwikis s
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.subwikiid=s.id
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id
WHERE
    s.wikiid=$id";
    $sectionquery="
SELECT
    sc.id
FROM
    {$CFG->prefix}ouwiki_subwikis s
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.subwikiid=s.id
    INNER JOIN {$CFG->prefix}ouwiki_sections sc ON sc.pageid=p.id
WHERE
    s.wikiid=$id";
    $pagequery="
SELECT
    p.id
FROM
    {$CFG->prefix}ouwiki_subwikis s
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.subwikiid=s.id
WHERE
    s.wikiid=$id";
    $subwikiquery="
SELECT
    s.id
FROM
    {$CFG->prefix}ouwiki_subwikis s
WHERE
    s.wikiid=$id";

    // Delete everything, bottom-up
    $ok=true;
    $ok=delete_records_select('ouwiki_links',"fromversionid IN ($versionquery)") && $ok;
    $ok=delete_records_select('ouwiki_comments',"sectionid IN ($sectionquery)") && $ok;
    $ok=delete_records_select('ouwiki_versions',"pageid IN ($pagequery)") && $ok;
    $ok=delete_records_select('ouwiki_locks',"pageid IN ($pagequery)") && $ok;
    $ok=delete_records_select('ouwiki_sections',"pageid IN ($pagequery)") && $ok;
    $ok=delete_records_select('ouwiki_pages',"subwikiid IN ($subwikiquery)") && $ok;
    $ok=delete_records_select('ouwiki_subwikis',"wikiid=$id") && $ok;
    $ok=delete_records("ouwiki", "id", "$id") && $ok;
    return $ok;
}

/**
 * Update all wiki documents for ousearch.
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function ouwiki_ousearch_update_all($feedback=false,$courseid=0) {
    global $CFG;

    // Get list of all wikis. We need the coursemodule data plus
    // the type of subwikis
    $coursecriteria=$courseid===0?'':'cm.course='.$courseid.' AND';
    $coursemodules=get_records_sql("
SELECT
    cm.id,cm.course,cm.instance,w.subwikis
FROM
    {$CFG->prefix}modules m
    INNER JOIN {$CFG->prefix}course_modules cm ON cm.module=m.id
    INNER JOIN {$CFG->prefix}ouwiki w ON cm.instance=w.id
WHERE
    $coursecriteria
    m.name='ouwiki'");
    if(!$coursemodules) {
        return;
    }

    if($feedback) {
        print '<li><strong>'.count($coursemodules).'</strong> wikis to process.</li>';
        $dotcount=0;
    }

    $count=0;
    foreach($coursemodules as $coursemodule) {

        // This condition is needed because if somebody creates some stuff
        // then changes the wiki type, it actually keeps the old bits
        // in the database. Maybe it shouldn't, not sure.
        switch($coursemodule->subwikis) {
            case OUWIKI_SUBWIKIS_SINGLE:
                $where="sw.userid IS NULL AND sw.groupid IS NULL";
                break;
            case OUWIKI_SUBWIKIS_GROUPS:
                $where="sw.userid IS NULL AND sw.groupid IS NOT NULL";
                break;
            case OUWIKI_SUBWIKIS_INDIVIDUAL:
                $where="sw.userid IS NOT NULL AND sw.groupid IS NULL";
                break;
        }

        // Get all pages in that wiki
        $rs=get_recordset_sql("
SELECT
    p.id,p.title,v.xhtml,v.timecreated,sw.groupid,sw.userid
FROM
    {$CFG->prefix}ouwiki_subwikis sw
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.subwikiid=sw.id
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.id=p.currentversionid
WHERE
    sw.wikiid={$coursemodule->instance} AND $where");
        while ($page=rs_fetch_next_record($rs)) {

            // Update the page for search
            $doc=new ousearch_document();
            $doc->init_module_instance('ouwiki',$coursemodule);
            if($page->groupid) {
                $doc->set_group_id($page->groupid);
            }
            if($page->title) {
                $doc->set_string_ref($page->title);
            }
            if($page->userid) {
                $doc->set_user_id($page->userid);
            }
            $title=$page->title ? $page->title:'';
            if(!$doc->update($title,$page->xhtml,$page->timecreated)) {
                ouwiki_error('Failed to update database record for page ID '.$page->id);
            }
        }
        rs_close($rs);

        $count++;
        if($feedback) {
            if($dotcount==0) {
                print '<li>';
            }
            print '.';
            $dotcount++;
            if($dotcount==20 || $count==count($coursemodules)) {
                print 'done '.$count.'</li>';
                $dotcount=0;
            }
            flush();
        }
    }
}

/**
 * Obtains a search document given the ousearch parameters.
 * @param object $document Object containing fields from the ousearch documents table
 * @return mixed False if object can't be found, otherwise object containing the following
 *   fields: ->content, ->title, ->url, ->activityname, ->activityurl
 */
function ouwiki_ousearch_get_document($document) {
    global $CFG;

    $groupconditions='';
    if(is_null($document->groupid)) {
        $groupconditions.=' AND sw.groupid IS NULL';
    } else {
        $groupconditions.=' AND sw.groupid='.$document->groupid;
    }
    if(is_null($document->userid)) {
        $groupconditions.=' AND sw.userid IS NULL';
    } else {
        $groupconditions.=' AND sw.userid='.$document->userid;
    }

    if(is_null($document->stringref)) {
        $titlecondition=" AND p.title IS NULL";
    } else {
        $titlecondition=" AND p.title='".addslashes($document->stringref)."'";
    }

    $result=get_record_sql($sql="
SELECT
    w.name AS activityname,p.title AS title,v.xhtml AS content
FROM
    {$CFG->prefix}course_modules cm
    INNER JOIN {$CFG->prefix}ouwiki w ON cm.instance=w.id
    INNER JOIN {$CFG->prefix}ouwiki_subwikis sw ON sw.wikiid=w.id
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.subwikiid=sw.id
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.id=p.currentversionid
WHERE
    cm.id=$document->coursemoduleid
    $titlecondition
    $groupconditions
");
    if(!$result) {
        return false;
    }
    if(is_null($result->title)) {
        $result->title=get_string('startpage','ouwiki');
    }
    $result->activityurl=$CFG->wwwroot.'/mod/ouwiki/view.php?id='.$document->coursemoduleid;
    $result->url=$result->activityurl;
    if(!is_null($document->stringref)) {
        $result->url.='&page='.urlencode($document->stringref);
    }
    if($document->groupid) {
        $result->url.='&group='.$document->groupid;
    }
    if($document->userid) {
        $result->url.='&user='.$document->userid;
    }
    return $result;
}

/**
 * Indicates API features that the module supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function ouwiki_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        default: return null;
    }
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in module settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function ouwiki_get_completion_state($course,$cm,$userid,$type) {
    global $CFG;

    // Get forum details
    if(!($ouwiki=get_record('ouwiki','id',$cm->instance))) {
        throw new Exception("Can't find ouwiki {$cm->instance}");
    }

    $countsql="
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}ouwiki_versions v
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.id=v.pageid
    INNER JOIN {$CFG->prefix}ouwiki_subwikis s ON s.id=p.subwikiid
WHERE
    v.userid=$userid AND v.deletedat IS NULL AND s.wikiid={$ouwiki->id}
    ";

    $result=$type; // Default return value

    if($ouwiki->completionedits) {
        $value = $ouwiki->completionedits <= get_field_sql($countsql);
          if($type==COMPLETION_AND) {
            $result=$result && $value;
        } else {
            $result=$result || $value;
        }
    }
    if($ouwiki->completionpages) {
        $value = $ouwiki->completionpages <=
            get_field_sql( $countsql." AND (SELECT MIN(id) FROM {$CFG->prefix}ouwiki_versions WHERE pageid=p.id AND deletedat IS NULL)=v.id");
        if($type==COMPLETION_AND) {
            $result=$result && $value;
        } else {
            $result=$result || $value;
        }
    }

    return $result;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in newmodule activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param bool $isteacher
 * @param int $timestart
 * @return boolean true on success, false on failure.
 **/
function ouwiki_print_recent_activity($course, $isteacher, $timestart) {  // was missing XXX_print_recent_activity function (nadavkav patch)
/// Given a course and a time, this module should find recent activity
/// that has occurred in dfwiki activities and print it out.
/// Return true if there was output, or false is there was none.

    global $CFG;
    $htmlarray = array();

    $return = ouwiki_print_overview(array("$course->id" => $course ),$htmlarray);
    foreach ($htmlarray  as $htmlelement) {
        echo $htmlelement['wiki'];
    }

    return $return;  //  True if anything was printed, otherwise false
}

/**
 * This function prints the recent activity (since current user's last login)
 * for specified courses.
 * @param array $courses Array of courses to print activity for.
 * @param string by reference $htmlarray Array of html snippets for display some
 *        -where, which this function adds its new html to.
 */
function ouwiki_print_overview($courses,&$htmlarray) {
    global $USER, $CFG;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }
    if (!$wikis = get_all_instances_in_courses('ouwiki',$courses)) {
        return;
        //echo "no wikis in course";
    }
    // get all ouwiki logs in ONE query (much better!)
    $sql = "SELECT instance,cmid,l.course,COUNT(l.id) as count FROM {$CFG->prefix}log l "
        ." JOIN {$CFG->prefix}course_modules cm ON cm.id = cmid "
        ." WHERE (";
    foreach ($courses as $course) {
        //$sql .= '(l.course = '.$course->id.' AND l.time > '.(string)((int)$USER->lastaccess - 3600).') OR ';
        $sql .= '(l.course = '.$course->id.' AND l.time < '.time().') OR ';
    }
    $sql = substr($sql,0,-3); // take off the last OR

    //Ignore comment actions for now, only entries.
    $sql .= ") AND l.module = 'ouwiki' AND action LIKE '%edit%' "
        ." AND userid != ".$USER->id." GROUP BY cmid,l.course,instance";
    if (!$new = get_records_sql($sql)) {
        $new = array(); // avoid warnings
    } else {
      //echo "got records!<br/>";
    }

    $strwikis = get_string('modulename','ouwiki');
    $strnumrespsince1 = get_string('overviewnumentrysince1','ouwiki');
    $strnumrespsince = get_string('overviewnumentrysince','ouwiki');

    //Go through the list of all wikis build previously, and check whether
    //they have had any activity.
    foreach ($wikis as $wiki) {

        if (array_key_exists($wiki->id, $new) && !empty($new[$wiki->id])) {
            $count = $new[$wiki->id]->count;

            if( $count > 0 ){
                if( $count == 1 ){
                    $strresp = $strnumrespsince1;
                }else{
                    $strresp = $strnumrespsince;
                }

                $str = '<br/><div class="overview wiki"><div class="name">'.
                    $strwikis.': <a title="'.$strwikis.'" href="'.
                    $CFG->wwwroot.'/mod/ouwiki/view.php?id='.$wiki->coursemodule.'"><h3>'.
                    $wiki->name.'</a><h3></div>';
                $str .= '<div class="info">';
                $str .= '<a href="'.$CFG->wwwroot.'/mod/ouwiki/wikihistory.php?id='.$wiki->coursemodule.'">'.$count.' '.$strresp.'</a>'; // link to recent changes (nadavkav patch)
                $str .= '</div></div>';

                if (!array_key_exists($wiki->course,$htmlarray)) {
                    $htmlarray[$wiki->course] = array();
                }
                if (!array_key_exists('wiki',$htmlarray[$wiki->course])) {
                    $htmlarray[$wiki->course]['wiki'] = ''; // initialize, avoid warnings
                }
                $htmlarray[$wiki->course]['wiki'] .= $str;
            }
        }
    }
}

/**
 * Returns summary information about what a user has done,
 * for user activity reports.
 * @param $course
 * @param $user
 * @param $mod
 * @param $wiki
 * @return object
 */
function ouwiki_user_outline($course, $user, $mod, $wiki) {

    $result = NULL;
    $logsview = get_records_select("log", "userid='$user->id' AND module='ouwiki'
        AND action='view' AND cmid='$mod->id'", "time ASC");
    $logsedit = get_records_select("log", "userid='$user->id' AND module='ouwiki'
        AND action='edit' AND cmid='$mod->id'", "time ASC");
    if ($logsview) {
        $numviews = count($logsview);
        $lastlog = array_pop($logsview);
        $result = new object();
        $result->info = get_string("numviews", "", $numviews);
        $result->time = $lastlog->time;
    }
    if ($logsedit) {
        if ($logsview) {
            $numviews = count($logsedit);
            $lastlog = array_pop($logsedit);
            $result->info .= ', and '.get_string('numedits', 'ouwiki', $numviews);
            $result->time = $lastlog->time > $result->time ? $lastlog->time : $result->time;
        } else {
            $numviews = count($logsedit);
            $lastlog = array_pop($logsedit);
            $result = new object();
            $result->info = get_string('numedits', 'ouwiki', $numviews);
            $result->time = $lastlog->time;
        }
    }
    return $result;
}

?>
