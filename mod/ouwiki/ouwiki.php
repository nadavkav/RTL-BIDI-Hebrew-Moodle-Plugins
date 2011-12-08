<?php
/**
 * Main wiki functionality
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/difflib.php');

// OU shared APIs which (for OU system) are present in local, elsewhere
// are incorporated in module
@include_once(dirname(__FILE__).'/../../local/transaction_wrapper.php');
if (!class_exists('transaction_wrapper')) {
    require_once(dirname(__FILE__).'/null_transaction_wrapper.php');
}
@include_once(dirname(__FILE__).'/../../local/xml_backup.php');
if (!class_exists('xml_backup')) {
    require_once(dirname(__FILE__).'/local/xml_backup.php');
}

define('OUWIKI_SUBWIKIS_SINGLE',0);
define('OUWIKI_SUBWIKIS_GROUPS',1);
define('OUWIKI_SUBWIKIS_INDIVIDUAL',2);
// Constants for the commenting system
define('OUWIKI_COMMENTS_NONE',0);
define('OUWIKI_COMMENTS_ANNOTATION',1);
define('OUWIKI_COMMENTS_PERSECTION',2);
define('OUWIKI_COMMENTS_BOTH',3);


// How long locks stay around without being confirmed (seconds)
define("OUWIKI_LOCK_PERSISTENCE",120);

// How often to confirm that you still want a lock
define("OUWIKI_LOCK_RECONFIRM",60);

// Amount of extra time granted over what is displayed for a timeout
// (to allow browser to save etc.)
define('OUWIKI_TIMEOUT_EXTRA',60);

// How long a lock you get when JS is turned off
define('OUWIKI_LOCK_NOJS',15*60);

// Session variable used to store wiki locks
define('SESSION_OUWIKI_LOCKS','ouwikilocks');

// Number of entries included in Atom/RSS feeds
define('OUWIKI_FEEDSIZE',50);


function ouwiki_dberror($source=null) {
    if(!$source) {
        $backtrace=debug_backtrace();
        $source=preg_replace('@^.*/(.*)(\.php)?$@','\1',$backtrace[0]['file']).'/'.$backtrace[0]['line'];
    }
    global $db;
    error('Database problem: '.$db->ErrorMsg().' (code OUWIKI-'.$source.')');
}

function ouwiki_error($text,$source=null) {
        if(!$source) {
        $backtrace=debug_backtrace();
        $source=preg_replace('^.*/(.*)(\.php)?$^','$1',$backtrace[0]['file']).'/'.$backtrace[0]['line'];
    }
    error("Wiki error: $text (code OUWIKI-$source)");
}

/**
 * Obtains the appropriate subwiki object for a request. If one cannot
 * be obtained, either creates one or calls error() and stops.
 * @param object $ouwiki Wiki object
 * @param object $cm Course-module object
 * @param object $context Context to use for checking permissions
 * @param int $groupid Group ID or 0 to use any appropriate group
 * @param int $userid User ID or 0 to use current user
 * @param bool $create If true, creates a wiki if it doesn't exist
 * @return mixed Object with the data from the subwiki table. Also has extra 'canedit' field
 *   set to true if that's allowed.
 */
function ouwiki_get_subwiki($course,$ouwiki,$cm,$context,$groupid,$userid,$create=false) {
    global $USER;
    switch($ouwiki->subwikis) {

    case OUWIKI_SUBWIKIS_SINGLE:
        $subwiki=get_record_select('ouwiki_subwikis',"wikiid={$ouwiki->id} AND groupid IS NULL AND userid IS NULL");
        if($subwiki) {
            ouwiki_set_extra_subwiki_fields($subwiki,$ouwiki,$context);
            return $subwiki;
        }
        if($create) {
            $subwiki=new StdClass;
            $subwiki->wikiid=$ouwiki->id;
            $subwiki->userid=null;
            $subwiki->groupid=null;
            $subwiki->magic=ouwiki_generate_magic_number();
            if(!($subwiki->id=insert_record('ouwiki_subwikis',$subwiki))) {
                ouwiki_dberror();
            }
            ouwiki_set_extra_subwiki_fields($subwiki,$ouwiki,$context);
            ouwiki_init_pages($course,$cm,$ouwiki,$subwiki,$ouwiki);
            return $subwiki;
        }
        ouwiki_error('Wiki does not exist. View wikis before attempting other actions.');
        break;

    case OUWIKI_SUBWIKIS_GROUPS:
        $groupid=groups_get_activity_group($cm,true);
        if(!$groupid) {
            $groups=groups_get_activity_allowed_groups($cm);
            if(!$groups) {
                if(!groups_get_all_groups($cm->course, 0, $cm->groupingid)) {
                    ouwiki_error('This wiki cannot be displayed because it is a group wiki, but no groups have been set up for the course (or grouping, if selected).');
                } else {
                    ouwiki_error('You do not have access to any of the groups in this wiki.');
                }
            }
            $groupid=reset($groups)->id;
        }
        $othergroup=!groups_is_member($groupid);
        $subwiki=get_record_select('ouwiki_subwikis',"wikiid={$ouwiki->id} AND groupid=$groupid AND userid IS NULL");
        if($subwiki) {
            ouwiki_set_extra_subwiki_fields($subwiki,$ouwiki,$context,$othergroup);
            return $subwiki;
        }
        if($create) {
            $subwiki=new StdClass;
            $subwiki->wikiid=$ouwiki->id;
            $subwiki->groupid=$groupid;
            $subwiki->userid=null;
            $subwiki->magic=ouwiki_generate_magic_number();
            if(!($subwiki->id=insert_record('ouwiki_subwikis',$subwiki))) {
                ouwiki_dberror();
            }
            ouwiki_set_extra_subwiki_fields($subwiki,$ouwiki,$context, $othergroup);
            ouwiki_init_pages($course,$cm,$ouwiki,$subwiki,$ouwiki);
            return $subwiki;
        }
        ouwiki_error('Wiki does not exist. View wikis before attempting other actions.');
        break;

    case OUWIKI_SUBWIKIS_INDIVIDUAL:
        if($userid==0) {
            $userid=$USER->id;
        }
        $otheruser=false;
        if($userid!=$USER->id) {
            $otheruser=true;
            // Is user allowed to view everybody?
            if(!has_capability('mod/ouwiki:viewallindividuals',$context)) {
                // Nope. Are they allowed to view people in same group?
                if(!has_capability('mod/ouwiki:viewgroupindividuals',$context)) {
                    ouwiki_error('You do not have access to view somebody else\'s wiki.');
                }
                // Check user is in same group. Note this isn't now restricted to the
                // module grouping
                $ourgroups=groups_get_all_groups($cm->course,$USER->id);
                $theirgroups=groups_get_all_groups($cm->course,$userid);
                $found=false;
                foreach($ourgroups as $ourgroup) {
                    foreach($theirgroups as $theirgroup) {
                        if($ourgroup->id==$theirgroup->id) {
                            $found=true;
                            break;
                        }
                    }
                    if($found) {
                        break;
                    }
                }
                if(!$found) {
                    ouwiki_error('You do not have access to view this user\'s wiki.');
                }
            }
        }
        // OK now find wiki
        $subwiki=get_record_select('ouwiki_subwikis',"wikiid={$ouwiki->id} AND groupid IS NULL AND userid = $userid");
        if($subwiki) {
            ouwiki_set_extra_subwiki_fields($subwiki,$ouwiki,$context,$otheruser,!$otheruser);
            return $subwiki;
        }
        // Create one
        if($create) {
            $subwiki=new StdClass;
            $subwiki->wikiid=$ouwiki->id;
            $subwiki->userid=$userid;
            $subwiki->groupid=null;
            $subwiki->magic=ouwiki_generate_magic_number();
            if(!($subwiki->id=insert_record('ouwiki_subwikis',$subwiki))) {
                ouwiki_dberror();
            }
            ouwiki_set_extra_subwiki_fields($subwiki,$ouwiki,$context,$otheruser,!$otheruser);
            ouwiki_init_pages($course,$cm,$ouwiki,$subwiki,$ouwiki);
            return $subwiki;
        }
        ouwiki_error('Wiki does not exist. View wikis before attempting other actions.');
        break;

    default:
        ouwiki_error("Unexpected subwikis value: {$ouwiki->subwikis}");
    }
}

/**
 * Initialises wiki pages. Does nothing unless there's a template.
 * @param object $cm Course-module object
 * @param object $subwiki Subwiki object
 * @param object $ouwiki OU wiki object
 */
function ouwiki_init_pages($course,$cm,$ouwiki,$subwiki,$ouwiki) {
    if(is_null($ouwiki->template)) {
        return;
    }
    global $CFG;
    $template=$CFG->dataroot.'/'.$ouwiki->course.'/moddata/ouwiki/'.
        $ouwiki->id.'/'.$ouwiki->template;
    if(!file_exists($template)) {
        ouwiki_error('Failed to load wiki template - file missing.');
    }
    $xml=DOMDocument::load($template);
    if(!$xml) {
        ouwiki_error('Failed to load wiki template - not valid XML. Check file in XML viewer and correct.');
    }
    if($xml->documentElement->tagName!='wiki') {
        ouwiki_error('Failed to load wiki template - must begin with &lt;wiki> tag.');
    }
    for($page=$xml->documentElement->firstChild;$page;$page=$page->nextSibling) {
        if($page->nodeType!=XML_ELEMENT_NODE) {
            continue;
        }
        if($page->tagName!='page') {
            ouwiki_error('Failed to load wiki template - expected &lt;page>.');
        }
        $title=null;
        $xhtml=null;
        for($child=$page->firstChild;$child;$child=$child->nextSibling) {
            if($child->nodeType!=XML_ELEMENT_NODE) {
                continue;
            }
            if(!$child->firstChild) {
                $text='';
            } else {
                if($child->firstChild->nodeType!=XML_TEXT_NODE &&
                   $child->firstChild->nodeType!=XML_CDATA_SECTION_NODE) {
                    ouwiki_error('Failed to load wiki template - expected text node.');
                }
                if($child->firstChild->nextSibling) {
                    ouwiki_error('Failed to load wiki template - expected single text node.');
                }
                $text=$child->firstChild->nodeValue;
            }
            switch($child->tagName) {
                case 'title' :
                    $title=$text;
                    break;
                case 'xhtml' :
                    $xhtml=$text;
                    break;
                default:
                    ouwiki_error('Failed to load wiki template - unexpected element &lt;'.$child->tagName.'>.');
            }
        }
        if($xhtml===null) {
            ouwiki_error('Failed to load wiki template - required &lt;xhtml>.');
        }
        ouwiki_save_new_version($course,$cm,$ouwiki,$subwiki,$title,$xhtml,-1,-1,-1,true);
    }
}

/**
 * Checks whether a user can edit a wiki, assuming that they can view it. This
 * adds $subwiki->canedit, set to either true or false.
 * @param object &$subwiki The subwiki object to which we are going to add a canedit variable
 * @param object $ouwiki Wiki object
 * @param object $context Context for permissions
 * @param bool $othergroup If true, user is attempting to access a group that's not theirs
 * @param bool $defaultwiki If true, user is accessing the wiki that they see by default
 */
function ouwiki_set_extra_subwiki_fields(&$subwiki,$ouwiki,$context,$othergroup=false,$defaultwiki=false) {
    // They must have the edit capability
    $subwiki->canedit = has_capability('mod/ouwiki:edit',$context);
    $subwiki->cancomment = has_capability('mod/ouwiki:comment',$context);
    $subwiki->canannotate = has_capability('mod/ouwiki:annotate',$context);
    $subwiki->commenting = $ouwiki->commenting;
    // If the wiki is not one of their groups, they need editallsubwikis
    if($othergroup) {
        $subwiki->canedit = $subwiki->canedit && has_capability('moodle/site:accessallgroups',$context);
        $subwiki->cancomment = $subwiki->cancomment && has_capability('moodle/site:accessallgroups',$context);
        $subwiki->canannotate = $subwiki->canannotate && has_capability('moodle/site:accessallgroups',$context);
    }
    // Editing might be turned off for the wiki at the moment
    $subwiki->canedit = $subwiki->canedit && (is_null($ouwiki->editbegin) || time() >= $ouwiki->editbegin);
    $subwiki->canedit = $subwiki->canedit && (is_null($ouwiki->editend) || time() < $ouwiki->editend);
    $subwiki->defaultwiki=$defaultwiki;
}

/**
 * Checks whether the wiki is locked due to specific dates being set. (This is only used for
 * informational display as the dates are already taken into account in the general checking
 * for edit permission.)
 * @param object $subwiki The subwiki object
 * @param object $ouwiki Wiki object
 * @param object $context Context for permissions
 * @return False if not locked or a string of information if locked
 */
function ouwiki_timelocked($subwiki,$ouwiki,$context) {
    // If they don't have edit permission anyhow then they won't be able to edit later
    // so don't show this
    if(!has_capability('mod/ouwiki:edit',$context)) {
        return false;
    }
    if(!is_null($ouwiki->editbegin) && time() < $ouwiki->editbegin) {
        return get_string('timelocked_before','ouwiki',userdate($ouwiki->editbegin,get_string('strftimedate')));
    }
    if(!is_null($ouwiki->editend) && time() >= $ouwiki->editend) {
        return get_string('timelocked_after','ouwiki');
    }
    return false;
}

/** Format parameters for inclusion in link in xhtml */
define('OUWIKI_PARAMS_LINK',0);
/** Format parameters as form input fields */
define('OUWIKI_PARAMS_FORM',1);
/** Format parameters as URL-encoded (no HTML escaping) */
define('OUWIKI_PARAMS_URL',2);

/**
 * Prints the parameters that identify a particular wiki and could be used in view.php etc.
 * @param string $page Name of page (null for startpage)
 * @param object $subwiki Current subwiki object
 * @param object $cm Course-module object
 * @param int $type OUWIKI_PARAMS_xx constant
 */
function ouwiki_display_wiki_parameters($page,$subwiki,$cm,$type=OUWIKI_PARAMS_LINK) {
    $output=ouwiki_get_parameter('id',$cm->id,$type);
    if(!$subwiki->defaultwiki) {
        if($subwiki->groupid) {
            $output.=ouwiki_get_parameter('group',$subwiki->groupid,$type);
        }
        if($subwiki->userid) {
            $output.=ouwiki_get_parameter('user',$subwiki->userid,$type);
        }
    }
    if(!is_null($page) && $page!=='') {
        $output.=ouwiki_get_parameter('page',$page,$type);
    }
    return $output;
}

// Internal function used by the above
function ouwiki_get_parameter($name,$value,$type) {
    switch($type) {
        case OUWIKI_PARAMS_FORM:
            $value=htmlspecialchars($value,ENT_QUOTES,'UTF-8');
            $output="<input type='hidden' name='$name' value='$value' />";
            break;
        case OUWIKI_PARAMS_LINK:
            $value=htmlspecialchars(urlencode($value),ENT_QUOTES,'UTF-8');
            $output='';
            if($name!='id') {
                $output.='&amp;';
            }
            $output.="$name=$value";
            break;
        case OUWIKI_PARAMS_URL:
            $value=urlencode($value);
            $output='';
            if($name!='id') {
                $output.='&';
            }
            $output.="$name=$value";
            break;
    }
    return $output;
}

/**
 * Prints the subwiki selector if user has access to more than one subwiki.
 * Also displays the currently-viewing subwiki.
 * @param object $subwiki Current subwiki object
 * @param object $ouwiki Wiki object
 * @param object $cm Course-module object
 * @param object $context Context for permissions
 * @param object $course Course object
 */
function ouwiki_display_subwiki_selector($subwiki, $ouwiki, $cm, $context,
        $course) {
    global $USER,$CFG;
    if($ouwiki->subwikis==OUWIKI_SUBWIKIS_SINGLE) {
        return '';
    }

    $choicefield='';

    switch($ouwiki->subwikis) {

    case OUWIKI_SUBWIKIS_GROUPS:
        $groups=groups_get_activity_allowed_groups($cm);
        uasort($groups,create_function('$a,$b','return strcasecmp($a->name,$b->name);'));
        $wikifor=htmlspecialchars($groups[$subwiki->groupid]->name);

        // Do they have more than one?
        if(count($groups)>1) {
            $choicefield='group';
            $choices=$groups;
        }
        break;

    case OUWIKI_SUBWIKIS_INDIVIDUAL:
        $user=get_record('user','id',$subwiki->userid,'','','','','id,firstname,lastname,username');
        $wikifor=ouwiki_display_user($user,$cm->course);
        if(has_capability('mod/ouwiki:viewallindividuals',$context)) {
            // Get list of everybody...
            $choicefield='user';
            $choices=get_records_sql("
SELECT
    u.id,u.firstname,u.lastname
FROM
    {$CFG->prefix}ouwiki_subwikis sw
    INNER JOIN {$CFG->prefix}user u ON sw.userid=u.id
WHERE
    sw.wikiid={$ouwiki->id}
ORDER BY
    u.lastname,u.firstname");
            foreach($choices as $choice) {
                $choice->name=fullname($choice);
            }
        } else if(has_capability('mod/ouwiki:viewgroupindividuals',$context)) {
            $choicefield='user';
            $choices=array();
            // User allowed to view people in same group
            $theirgroups=groups_get_all_groups($cm->course, $USER->id,
                    $course->defaultgroupingid);
            if(!$theirgroups) {
                $theirgroups=array();
            }
            foreach($theirgroups as $group) {
                $members=groups_get_members($group->id,'u.id,u.firstname,u.lastname');
                foreach($members as $member) {
                    $member->name=fullname($member);
                    $choices[$member->id] = $member;
                }
            }
        } else {
            // Nope, only yours
        }
        break;

    default:
        ouwiki_error("Unexpected subwikis value: {$ouwiki->subwikis}");
    }

    $out='<div class="ouw_subwiki"><label for="wikiselect">'.get_string('wikifor','ouwiki').'</label>';
    if($choicefield && count($choices)>1) {
        $selectedid=$choicefield=='user' ? $subwiki->userid : $subwiki->groupid;
        $out.='<form method="get" action="view.php" class="ouwiki_otherwikis"><div>
<input type="hidden" name="id" value="'.$cm->id.'"/>
<select name="'.$choicefield.'" id="wikiselect">';
        foreach($choices as $choice) {
            $selected = $choice->id==$selectedid ? ' selected="selected"' : '';
            $out.='<option value="'.$choice->id.'"'.$selected.'>'.htmlspecialchars($choice->name).'</option>';
        }
        $out.='</select> <input type="submit" value="'.get_string('changebutton','ouwiki').'" /></div></form>';

    } else {
        $out.=$wikifor;
    }
    $out.='</div>';
    return $out;
}

// Indicates that a current version must be available
define('OUWIKI_GETPAGE_REQUIREVERSION',0);

// Indicates that a current version doesn't need to be available
define('OUWIKI_GETPAGE_ACCEPTNOVERSION',1);

// Indicates that page should be created if it does not exist
define('OUWIKI_GETPAGE_CREATE',2);

/**
 * Returns an object containing the details from 'pages' and 'versions'
 * tables for the current version of the specified (named) page, or false
 * if page does not exist. Note that if the page exists but there are no
 * versions, then the version fields will not be set.
 * @param object $subwiki Current subwiki object
 * @param string $pagename Name of desired page or null for start
 * @param int $option OUWIKI_GETPAGE_xx value. Can use _ACCEPTNOVERSION
 *   if it's OK when a version doesn't exist, or _CREATE which creates
 *   pages when they don't exist.
 * @return object Page-version object
 */
function ouwiki_get_current_page($subwiki,$pagename,$option=OUWIKI_GETPAGE_REQUIREVERSION) {
    global $CFG;

    $tl=textlib_get_instance();
    if($pagename) {
        $pagename_s="UPPER(p.title)='".addslashes($tl->strtoupper($pagename))."'";
    } else {
        $pagename_s="p.title IS NULL";
    }

    $jointype=$option==OUWIKI_GETPAGE_REQUIREVERSION ? 'INNER' : 'LEFT';
    $pageversion=get_record_sql($query="
SELECT
    p.id AS pageid,p.subwikiid,p.title,p.currentversionid,p.locked,
    v.id AS versionid,v.xhtml,v.timecreated,v.userid
FROM
    {$CFG->prefix}ouwiki_pages p
    $jointype JOIN {$CFG->prefix}ouwiki_versions v ON p.currentversionid=v.id
WHERE
    p.subwikiid={$subwiki->id} AND $pagename_s");
    if(!$pageversion) {
        if($option!=OUWIKI_GETPAGE_CREATE) {
            return false;
        }

        // Create page
        $pageversion=new StdClass;
        $pageversion->subwikiid=$subwiki->id;
        $pageversion->title=$pagename ? addslashes($pagename) : null;
        $pageversion->locked = 0;
        if(!($pageversion->pageid=insert_record('ouwiki_pages',$pageversion))) {
            ouwiki_dberror();
        }

        // Update any missing link records that might exist
        $uppertitle=addslashes($tl->strtoupper($pagename));
        if(!execute_sql("
UPDATE
    {$CFG->prefix}ouwiki_links
SET
    tomissingpage=NULL,topageid={$pageversion->pageid}
WHERE
    tomissingpage='$uppertitle'
    AND {$subwiki->id} =(
        SELECT
            p.subwikiid
        FROM
            {$CFG->prefix}ouwiki_versions v
            INNER JOIN {$CFG->prefix}ouwiki_pages p ON v.pageid=p.id
        WHERE v.id=fromversionid)",false)) {
            ouwiki_dberror();
        }

        $pageversion->title=stripslashes($pageversion->title);
          // Because it wouldn't have slashes if
          // we returned it from get_record. It's kind of
          // insane that insert_record requires them, but
          // anyway.

        $pageversion->currentversionid=null;
        $pageversion->versionid=null;
        $pageversion->xhtml=null;
        $pageversion->timecreated=null;
        $pageversion->userid=null;
        return $pageversion;
    }

    // Ensure valid value for comparing time created
    $timecreated = empty($pageversion->timecreated) ? 0 : $pageversion->timecreated ;

    $pageversion->recentversions=get_records_sql("
SELECT
    v.id,v.timecreated,v.userid,u.firstname,u.lastname
FROM
    {$CFG->prefix}ouwiki_versions v
    INNER JOIN {$CFG->prefix}user u ON v.userid=u.id
WHERE
    v.pageid={$pageversion->pageid}
    AND v.timecreated <= {$timecreated}
    AND v.deletedat IS NULL
ORDER BY
    v.id DESC",0,3);
    return $pageversion;
}

/**
 * Returns an object containing the details from 'pages' and 'versions'
 * tables for the specified version of the specified (named) page, or false
 * if page/version does not exist.
 * @param object $subwiki Current subwiki object
 * @param string $pagename Name of desired page or null for start
 * @return int $versionid Version ID
 */
function ouwiki_get_page_version($subwiki,$pagename,$versionid) {
    global $CFG;

    if($pagename) {
        $tl=textlib_get_instance();
        $pagename_s="='".addslashes($tl->strtoupper($pagename))."'";
    } else {
        $pagename_s="IS NULL";
    }

    $pageversion=get_record_sql("
SELECT
    p.id AS pageid,p.subwikiid,p.title,p.currentversionid,
    v.id AS versionid,v.xhtml,v.timecreated,v.userid, v.deletedat,
    u.firstname,u.lastname,u.username
FROM
    {$CFG->prefix}ouwiki_pages p,
    {$CFG->prefix}ouwiki_versions v
    LEFT JOIN {$CFG->prefix}user u ON v.userid=u.id
WHERE
    p.subwikiid={$subwiki->id} AND UPPER(p.title) $pagename_s AND v.id=$versionid");
    $pageversion->recentversions=false;
    return $pageversion;
}

/**
 * Obtains details (versionid,timecreated plus user id,username,firstname,lastname)
 * for the previous and next version after the specified one.
 * @param object $pageversion Page/version object
 * @return object Object with ->prev and ->next fields, either of which may be false
 *   to indicate (respectively) that this is the first or last version. If not false,
 *   these objects contain the fields mentioned above.
 */
function ouwiki_get_prevnext_version_details($pageversion) {
    global $CFG;
    $prevnext=new StdClass;

    $prev=get_records_sql("
SELECT
    v.id AS versionid,v.timecreated,
    u.id,u.username,u.firstname,u.lastname
FROM
    {$CFG->prefix}ouwiki_versions v
    LEFT JOIN {$CFG->prefix}user u ON u.id=v.userid
WHERE
    v.pageid={$pageversion->pageid}
    AND v.timecreated < {$pageversion->timecreated}
    AND v.deletedat IS NULL
ORDER BY
    v.id DESC",0,1);
    $prevnext->prev = $prev ? current($prev) : false;

    $next=get_records_sql("
SELECT
    v.id AS versionid,v.timecreated,
    u.id,u.username,u.firstname,u.lastname
FROM
    {$CFG->prefix}ouwiki_versions v
    LEFT JOIN {$CFG->prefix}user u ON u.id=v.userid
WHERE
    v.pageid={$pageversion->pageid}
    AND v.timecreated > {$pageversion->timecreated}
    AND v.deletedat IS NULL
ORDER BY
    v.id",0,1);
    $prevnext->next= $next ? current($next) : false;

    return $prevnext;
}

/**
 * Returns an HTML span with appropriate class to indicate how recent something
 * is by colour.
 */
function ouwiki_recent_span($time) {
    $now=time();
    if($now-$time < 5*60) {
        $category='ouw_recenter';
    } else if($now-$time < 4*60*60) {
        $category='ouw_recent';
    } else {
        $category='ouw_recentnot';
    }
    return '<span class="'.$category.'">';
}

/**
 * @param object $subwiki For details of user/group and ID so that
 *   we can make links
 * @param object $cm Course-module object (again for making links)
 * @param object $pageversion Data from page and version tables.
 * @return string HTML content for page
 */
function ouwiki_display_page($subwiki,$cm,$pageversion,$gewgaws=false,$page='history') {
    global $CFG;

    // Get comments - only if using per-section comment system. prevents unnecessary db access
    $comments = array();
    if(ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_PERSECTION || ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH) {
        if($gewgaws) {
            $comments=ouwiki_get_recent_comments($pageversion->pageid,ouwiki_find_sections($pageversion->xhtml));
        }
    }
    // And params
    $params=ouwiki_display_wiki_parameters($pageversion->title,$subwiki,$cm);

    // Get annotations - only if using annotation comment system. prevents unnecessary db access
    if(ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_ANNOTATION ||ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH) {
        $annotations = ouwiki_get_annotations($pageversion);
    } else {
        $annotations = '';
    }

    // Title
    $title=is_null($pageversion->title) ? get_string('startpage','ouwiki') : htmlspecialchars($pageversion->title);

    // setup annotations according to the page we are on
    if($page == 'view') {
        // create the annotations
        if((ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_ANNOTATION ||ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH) &&
            count($annotations)) {
            ouwiki_highlight_existing_annotations(&$pageversion->xhtml, $annotations, 'view');
        }
    } elseif ($page == 'annotate') {
        // call function for the annotate page
        ouwiki_setup_annotation_markers(&$pageversion->xhtml);
        ouwiki_highlight_existing_annotations(&$pageversion->xhtml, $annotations, 'annotate');
    }

    $result='';
    $result.='<div class="ouwiki_content"><div class="ouw_topheading">';
    $returncomments=true;
    $result.='<div class="ouw_heading"><h1 id="ouw_topheading">'.$title.'</h1>'.
        ($gewgaws
            ? ouwiki_internal_display_heading_bit(1,$pageversion->title,$subwiki,$cm,$comments,null,$annotations,$pageversion->locked,$returncomments)
            : '</div>');

    // List of recent changes
    if($gewgaws && $pageversion->recentversions) {
        $result.='<div class="ouw_recentchanges">'.
            get_string('recentchanges','ouwiki').': <span class="ouw_recentchanges_list">';
        $first=true;
        foreach($pageversion->recentversions as $recentversion) {
            if($first) {
                $first=false;
            } else {
                $result.='; ';
            }
            $result.=ouwiki_recent_span($recentversion->timecreated);
            $result.=ouwiki_nice_date($recentversion->timecreated).'</span> (';
            $recentversion->id=$recentversion->userid; // so it looks like a user object
            $result.=ouwiki_display_user($recentversion,$cm->course,false).')';
        }

        if (class_exists('ouflags') && ou_get_is_mobile()){
           $result.='; </span></div>';
        	
        }
        else {
            $result.='; <br/><a class="seedetails" href="history.php?'.$params.'">'.get_string('seedetails','ouwiki').'</a></span></div>';
        }
    }

    $result.='</div><div class="ouw_belowmainhead">';
    if($returncomments!==true) {
        $result.=$returncomments;
    }
    $result.='<div class="ouw_topspacer"></div>';

    // Content of page
    $result.=ouwiki_convert_content($pageversion->xhtml,$subwiki,$cm);

    if($gewgaws) {
        // Add in links/etc. around headings
        global $ouwiki_internal_re;
        $ouwiki_internal_re->comments=$comments;
        $ouwiki_internal_re->pagename=$pageversion->title;
        $ouwiki_internal_re->subwiki=$subwiki;
        $ouwiki_internal_re->cm=$cm;
        $ouwiki_internal_re->annotations = $annotations;
        $ouwiki_internal_re->locked = $pageversion->locked;
        $result=preg_replace_callback('|<h([1-9]) id="ouw_s([0-9]+_[0-9]+)">(.*?)(<br\s*/>)?</h[1-9]>|s','ouwiki_internal_re_heading_bits',$result);
    }
    $result.='<div class="clearer"></div></div></div>';
    if($gewgaws) {
        $links=ouwiki_get_links_to($pageversion->pageid);
        if(count($links)>0) {
            $result.='<div class="ouw_linkedfrom"><h3>'.get_string(count($links)==1 ? 'linkedfromsingle' : 'linkedfrom','ouwiki').'</h3><ul>';
            $first=true;
            foreach($links as $link) {
                $result.=' <li>';
                if($first) {
                    $first=false;
                } else {
                    $result.='&#8226; ';
                }
                $result.='<a href="view.php?'.ouwiki_display_wiki_parameters($link->title,$subwiki,$cm).
                    '">'.($link->title ? htmlspecialchars($link->title) : get_string('startpage','ouwiki')).'</a></li>';
            }
            $result.='</ul></div>';
        }

        if($subwiki->cancomment && (ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_PERSECTION || ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH)) {
            $result.='<div id="ouw_ac_formcontainer" style="display:none">'.ouwiki_display_comment_form('view',null,null,$pageversion->title,$subwiki,$cm).'</div>';
        }
    }

    // disply the orphaned annotations
    if((ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_ANNOTATION || ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH)
        && $annotations && $page != 'history') {
        $orphaned = '';
        foreach($annotations as $annotation) {
            if ($annotation->orphaned) {
                $orphaned .= ouwiki_setup_hidden_annotation($annotation);
            }
        }
        $result = ($orphaned !== '')?  $result.'<h3>'.get_string('orphanedannotations','ouwiki').'</h3>'.$orphaned : $result;
    }

    return array($result, $annotations);
}

function ouwiki_internal_re_heading_bits($matches) {
    global $ouwiki_internal_re;
    return '<div class="ouw_heading ouw_heading'.$matches[1].'"><h'.$matches[1].' id="ouw_s'.$matches[2].'">'.$matches[3].'</h'.$matches[1].'>'.
        ouwiki_internal_display_heading_bit($matches[1],
            $ouwiki_internal_re->pagename,$ouwiki_internal_re->subwiki,$ouwiki_internal_re->cm,$ouwiki_internal_re->comments,$matches[2],$ouwiki_internal_re->annotations,$ouwiki_internal_re->locked);
}

function ouwiki_internal_re_plain_heading_bits($matches) {
    return '<div class="ouw_heading"><h'.$matches[1].' id="ouw_s'.$matches[2].'">'.$matches[3].'</h'.$matches[1].'></div>';
}

function ouwiki_internal_display_heading_bit($headingnumber,$pagename,$subwiki,$cm,&$comments,$xhtmlid,$annotations,$locked,&$returncomments=false) {
    global $CFG;
    $params=ouwiki_display_wiki_parameters($pagename,$subwiki,$cm);
    $result=' <div class="ouw_byheading">';

// next link, seems to be, redundent. since it shows up in the Tabs links
// so it is not enabled on Page level with: "&& !empty($xhtmlid)"    (nadavkav 8-5-2011)
    if ($subwiki->canedit && !$locked && !empty($xhtmlid)) {
        $result.='<a class="ouw_editsection" href="edit.php?'.$params.($xhtmlid ? '&amp;section='.$xhtmlid : '').'">'.
            get_string($xhtmlid ? 'editsection' : 'editpage','ouwiki').'</a> ';
    }

    // output the 'comment on page' link if using per-section comment system
    $gotcomments = false;
    if(ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_PERSECTION || ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH) {
        $key=$xhtmlid?$xhtmlid : '';
        $url='comments.php?'.$params.($xhtmlid ? '&amp;section='.$xhtmlid : '');
        if(array_key_exists($key,$comments) && $comments[$key]->count>0) {
            $a=new StdClass;
            $a->count=$comments[$key]->count;
            $lastcomment=$comments[$key]->comments[count($comments[$key]->comments)-1];
            $a->date=ouwiki_nice_date($lastcomment->timeposted,true,true);
            $a->plural=$a->count==1 ? '' : 's';
            global $USER;
            $a->commentlink='<a class="ouw_revealcomment" href="'.$url.'">'.
                get_string('commentcount','ouwiki',$a).'</a>';
            $result.='<span class="ouw_commentsinfo">'.
                get_string('commentinfo','ouwiki',$a).
                ($USER->id==$lastcomment->userid ? ' ('.get_string('commentbyyou','ouwiki').')' : '').'</span> ';
            $gotcomments=true;
        } else {
            if($subwiki->cancomment) {
                $result.='<a class="ouw_makecomment" href="'.$url.'#post">'.
                    get_string($xhtmlid?'commentonsection':'commentonpage','ouwiki').'</a>';
            }
            $gotcomments=false;
        }
    }

    // output the annotate link if using annotation comment system
    if (ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_ANNOTATION || ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH){
//        if ($subwiki->canannotate) {
//            $result.='<a class="ouw_annotate" href="annotate.php?'.$params.'">'.
//                    get_string('annotate','ouwiki').'</a>';
//        }

        if ($annotations != false) {
            $orphancount = 0;
            foreach($annotations as $annotation) {
                if($annotation->orphaned == 1) {
                    $orphancount++;
                }
            }
            if (count($annotations) > $orphancount) {
                $result .= '<span id="showhideannotations">
                           <a href="javascript:ouwikiShowAllAnnotations(\'block\')" id="showallannotations">'.get_string('showallannotations','ouwiki').'</a>
                           <a href="javascript:ouwikiShowAllAnnotations(\'none\')" id="hideallannotations">'.get_string('hideallannotations','ouwiki').'</a>
                           </span>';
            }
        }
    }

    // Addition to offer Save To Portfolio ability for postings
    global $CFG;
    if (file_exists(dirname(__FILE__).'/../portfolio/index.php')) {
        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
        if (has_capability('mod/portfolio:doanything', $modcontext, NULL, true, 'portfolio:doanything:false', 'portfolio')) {
            global $CFG;
            $wikiposttoportfolio = get_string($xhtmlid ? 'wikisectionposttoportfolio':'wikiposttoportfolio',"portfolio");
            $result.='<div style="display: inline; float: right;"><form method="post" action="'.$CFG->wwwroot.'/mod/portfolio/moodle/ouwiki/saveToPortfolio.php?'.$params.($xhtmlid ? '&amp;section='.$xhtmlid : '').'"><div><input type="image" title="'.$wikiposttoportfolio.'" alt="'.$wikiposttoportfolio.'" src="'.$CFG->wwwroot.'/mod/portfolio/html/_design/icon_download.gif" value="save" name="save" style="width: 11px; height: 11px;" class="ouwiki_download_icon"/></div></form></div>';
        }
    }

    $result.='</div></div>';
    if($gotcomments) {
        $commentsresult='<div class="ouw_hiddencomments" id="ouw_comments_'.$xhtmlid.'">';
        $commentsresult.=ouwiki_display_comments($comments[$key]->comments,$xhtmlid,$pagename,$subwiki,$cm);
        $commentsresult.='<div class="ouw_hiddencommentoptions">';
        if($comments[$key]->count > count($comments[$key]->comments)) {
            $a=new StdClass;
            $a->count=count($comments[$key]->comments);
            $a->link='<a href="'.$url.'">'.get_string('commentsviewall','ouwiki').'</a>';
            $commentsresult.='<span>'.get_string('commentsolder','ouwiki',$a).'</span> ';
        } else {
            $commentsresult.='<span><a href="'.$url.'">'.get_string('commentsviewseparate','ouwiki').'</a></span> ';
        }
        if($subwiki->cancomment) {
            $commentsresult.='<a class="ouw_makecomment2" href="'.$url.'#post">'.
                get_string('commentpostheader','ouwiki').'</a>';
        }
        $commentsresult.='</div></div>';
        if($returncomments) {
            $returncomments=$commentsresult;
        } else {
            $result.=$commentsresult;
        }
    }

    return $result;
}

function ouwiki_display_preview($content,$page,$subwiki,$cm) {
    // Title
    $title=$page!==null && $page!=='' ? htmlspecialchars($page) : get_string('startpage','ouwiki');
    $result='<h1>'.$title.'</h1>';

    // Content of page
    $result.=ouwiki_convert_content($content,$subwiki,$cm);
    return $result;
}

define('OUWIKI_LINKS_SQUAREBRACKETS','/\[\[(.*?)\]\]/');

function ouwiki_internal_re_internallinks($matches) {
    // Used to replace links when displaying wiki all one one page
    global $ouwiki_internallinks;
    $details = ouwiki_get_wiki_link_details($matches[1]);

    // See if it matches a known page
    foreach ($ouwiki_internallinks as $indexpage) {
        if (
            ($details->page==='' && is_null($indexpage->title)) ||
            (strtoupper($indexpage->title)===strtoupper($details->page)) ) {
            // Page matches, return link
            return '<a class="ouw_wikilink" href="#' . $indexpage->pageid .
                '">' . $details->title . '</a>';
        }
    }
    // Page did not match, return title in brackets
    return '(' . $details->title . ')';
}

function ouwiki_internal_re_wikilinks($matches) {
    global $ouwiki_wikilinks;
    $details = ouwiki_get_wiki_link_details($matches[1]);
    return '<a class="ouw_wikilink" href="view.php?' .
        ouwiki_display_wiki_parameters(null, $ouwiki_wikilinks->subwiki,
            $ouwiki_wikilinks->cm) .
        ($details->page!==''
            ? '&amp;page=' . htmlspecialchars(urlencode($details->page)) : '') .
        '">' . $details->title . '</a>';
}

function ouwiki_convert_content($content,$subwiki,$cm,$internallinks=false) {
    // Detect links. Note that changes to this code ought to be reflected
    // in the code in ouwiki_save_new_version which analyses to search for
    // links.

    // Ordinary [[links]]
    if($internallinks) {
        // When displayed on one page
        global $ouwiki_internallinks;
        $ouwiki_internallinks=$internallinks;
        $function='ouwiki_internal_re_internallinks';
    } else {
        global $ouwiki_wikilinks;
        $ouwiki_wikilinks = (object)array('subwiki'=>$subwiki, 'cm'=>$cm);
        $function = 'ouwiki_internal_re_wikilinks';
    }
    $content=preg_replace_callback(OUWIKI_LINKS_SQUAREBRACKETS,$function,$content);

    // We do not use FORMAT_MOODLE (which adds linebreaks etc) because that was
    // already handled manually.
    return '<div class="ouwiki_content">'.format_text($content,FORMAT_HTML).'</div>';
}

/**
 * Displays a user's name and link to profile etc.
 * @param object $user User object (must have at least id, firstname and lastname)
 * @param int $courseid ID of course
 * @param bool $link If true, makes it a link
 */
function ouwiki_display_user($user,$courseid,$link=true) {
    // Wiki pages can be created by the system which obviously doesn't
    // need a profile link.
    if(!$user->id) {
        return get_string('system','ouwiki');
    }

    global $CFG;
    $fullname = fullname($user);
    $extra='';
    if(!$link) {
        $extra='class="ouwiki_noshow"';
    }

    $result='<a href="'.$CFG->wwwroot.'/user/view.php?id='.
        $user->id.'&amp;course='.$courseid.'" '.$extra.'>'.fullname($user).'</a>';
    return $result;
}

function ouwiki_print_tabs($selected,$pagename,$subwiki,$cm,$context,$pageexists=true,$pagelocked=false) {
    global $CFG;
    $tabrow=array();

    if (class_exists('ouflags')) {
        //  OpenLearn insitu editing
        //  Now we have to check if we are in an insitu edited course and display
        //  the extra tabs for versions and the main mod settings (tidyer this way)
        //  means you don't need to go back to the course homepage to edit the other
        //  mod settings

        if(has_capability('local/course:revisioneditor', get_context_instance(CONTEXT_COURSE, $cm->course), null, false)) {
            if(!empty($revisionedit)) {
                //  We don't need a URL as the current tab is always empty, and this tab
                //  will only display while you are viewing a revision
                $tabrow[] = new tabobject('viewrevision', '', 'View Revision');
                $current_tab = 'viewrevision';
            }

            $tabrow[] = new tabobject('edit_settings', $CFG->wwwroot.'/course/modedit.php?update='.$cm->id.'&amp;return=1', 'Edit Settings');
            $tabrow[] = new tabobject('revisions', $CFG->wwwroot.'/local/insitu/activity_revisions.php?id='.$cm->id, get_string('revisions', 'course'));
        }
    }

    $params=ouwiki_display_wiki_parameters($pagename,$subwiki,$cm);

    $tabrow[]=new tabobject('view',
        'view.php?'.$params,get_string('tab_view','ouwiki'));

    if($subwiki->canedit && !$pagelocked) {
        $tabrow[]=new tabobject('edit',
            'edit.php?'.$params,get_string('tab_edit','ouwiki'));
    }

    if(ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_ANNOTATION || ouwiki_get_commenting($subwiki->commenting) == OUWIKI_COMMENTS_BOTH) {
        if($subwiki->canannotate) {
            $tabrow[]=new tabobject('annotate',
                'annotate.php?'.$params,get_string('tab_annotate','ouwiki'));
        }
    }

    if((!class_exists('ouflags') && $pageexists) || (class_exists('ouflags') && !ou_get_is_mobile() && $pageexists)) {
        $tabrow[]=new tabobject('history',
            'history.php?'.$params,get_string('tab_history','ouwiki'));
    }

    $tabs=array();
    $tabs[]=$tabrow;
    print_tabs($tabs, $selected, $pageexists ? '' : array('edit', 'annotate'));

    print '<div id="ouwiki_belowtabs">';
}

/**
 * Prints the footer and also logs the page view.
 *
 * @param object $course Course object
 * @param object $subwiki Subwiki object; used to add parameters to $logurl or the default URL
 * @param object $pagename Page name or NULL if homepage/not relevant
 * @param string $logurl URL to log; if null, uses current page as default
 * @param string $logaction Action to log; if null, uses page before .php as default
 * @param string $loginfo Optional info string
 */
function ouwiki_print_footer($course,$cm,$subwiki,$pagename=null,$logurl=null,$logaction=null,$loginfo=null) {
    print '</div>';
    print_footer($course);

    // Log
    $url=$logurl ? $logurl : preg_replace('~^.*/ouwiki/~','',$_SERVER['PHP_SELF']);

    $url.=(strpos($url,'?')===false ? '?' : '&').'id='.$cm->id;
    if($subwiki->groupid) {
        $url.='&group='.$subwiki->groupid;
    }
    if($subwiki->userid) {
        $url.='&user='.$subwiki->userid;
    }
    if($pagename) {
        $url.='&page='.urlencode($pagename);
        $info=$pagename;
    } else {
        $info='';
    }
    if($loginfo) {
        if($info) {
            $info.=' ';
        }
        $info.=$loginfo;
    }
    $action=$logaction ? $logaction : preg_replace('~\..*$~','',$url);
    add_to_log($course->id,'ouwiki',$action,$url,$info,$cm->id);
}

function ouwiki_nice_date($time,$insentence=false,$showrecent=false) {
    $result=$showrecent ? ouwiki_recent_span($time) : '';
    if(function_exists('specially_shrunken_date')) {
        $result.=specially_shrunken_date($time,$insentence);
    } else {
        $result.=userdate($time);
    }
    $result.=$showrecent ? '</span>' : '';
    return $result;
}

function ouwiki_handle_backup_exception($e,$type='backup') {
    if(debugging()) {
        print '<pre>';
        print $e->getMessage().' ('.$e->getCode().')'."\n";
        print $e->getFile().':'.$e->getLine()."\n";
        print $e->getTraceAsString();
        print '</pre>';
    } else {
        print '<div><strong>Error</strong>: '.htmlspecialchars($e->getMessage()).' ('.$e->getCode().')</div>';
    }
    print "<div><strong>This $type has failed</strong> (even though it may say otherwise later). Resolve this problem before continuing.</div>";
}

/**
 * Obtains an editing lock on a wiki page.
 * @param object $ouwiki Wiki object (used just for timeout setting)
 * @param int $pageid ID of page to be locked
 * @return array Two-element array with a boolean true (if lock has been obtained)
 *   or false (if lock was held by somebody else). If lock was held by someone else,
 *   the values of the wiki_locks entry are held in the second element; if lock was
 *   held by current user then the the second element has a member ->id only.
 */
function ouwiki_obtain_lock($ouwiki,$pageid) {
    global $USER;

    // Check for lock
    $alreadyownlock=false;
    if($lock=get_record('ouwiki_locks','pageid',$pageid)) {
        $timeoutok=is_null($lock->expiresat) || time() < $lock->expiresat;
        // Consider the page locked if the lock has been confirmed within OUWIKI_LOCK_PERSISTENCE seconds
        if($lock->userid==$USER->id && $timeoutok) {
            // Cool, it's our lock, do nothing except remember it in session
            $lockid=$lock->id;
            $alreadyownlock=true;
        } else if(time()-$lock->seenat < OUWIKI_LOCK_PERSISTENCE && $timeoutok) {
            return array(false,$lock);
        } else {
            // Not locked any more. Get rid of the old lock record.
            if(!delete_records('ouwiki_locks','pageid',$pageid)) {
                error('Unable to delete lock record');
            }
        }
    }

    // Add lock
    if(!$alreadyownlock) {
        // Lock page
        $newlock=new StdClass;
        $newlock->pageid=$pageid;
        $newlock->userid=$USER->id;
        $newlock->lockedat=time();
        $newlock->seenat=$newlock->lockedat;
        if($ouwiki->timeout) {
            $newlock->expiresat=$newlock->lockedat+$ouwiki->timeout+OUWIKI_TIMEOUT_EXTRA;
        }
        if(!$lockid=insert_record('ouwiki_locks',$newlock)) {
            error('Unable to insert lock record');
        }
    }

    // Store lock information in session so we can clear it later
    if(!array_key_exists(SESSION_OUWIKI_LOCKS,$_SESSION)) {
            $_SESSION[SESSION_OUWIKI_LOCKS]=array();
    }
    $_SESSION[SESSION_OUWIKI_LOCKS][$pageid]=$lockid;
    $lockdata=new StdClass;
    $lockdata->id=$lockid;
    return array(true,$lockdata);
}

/**
 * If the user has an editing lock, releases it. Has no effect otherwise.
 * Note that it doesn't matter if this isn't called (as happens if their
 * browser crashes or something) since locks time out anyway. This is just
 * to avoid confusion of the 'what? it says I'm editing that page but I'm
 * not, I just saved it!' variety.
 * @param int $pageid ID of page that was locked
 */
function ouwiki_release_lock($pageid) {
    if(!array_key_exists(SESSION_OUWIKI_LOCKS,$_SESSION)) {
        // No locks at all in session
        return;
    }

    if(array_key_exists($pageid,$_SESSION[SESSION_OUWIKI_LOCKS])) {
        $lockid=$_SESSION[SESSION_OUWIKI_LOCKS][$pageid];
        unset($_SESSION[SESSION_OUWIKI_LOCKS][$pageid]);
        if(!delete_records('ouwiki_locks','id',$lockid)) {
            error("Unable to delete lock record.");
        }
    }
}

/**
 * Kills any locks on a given page.
 * @param int $pageid ID of page that was locked
 */
function ouwiki_override_lock($pageid) {
    if(!delete_records('ouwiki_locks','pageid',$pageid)) {
        error("Unable to delete lock record.");
    }
}


/**
 * Prints the header and (if applicable) group selector.
 * @param object $ouwiki Wiki object
 * @param object $cm Course-modules object
 * @param object $subwiki Subwiki objecty
 * @param string $pagename Name of page
 * @param object $context Context object
 * @param string $afterpage If included, extra content for navigation string after page link
 * @param bool $hideindex If true, doesn't show the index/recent pages links
 * @param bool $notabs If true, prints the after-tabs div here
 * @param string $head Things to include inside html head
 */
function ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$pagename,$context,$afterpage=null,$hideindex=false,$notabs=false,
    $head='', $title='') {
    $wikiname=format_string(htmlspecialchars($ouwiki->name));

    // Print header
    $strwiki=get_string("modulename", "ouwiki");
    $strwikis=get_string("modulenameplural", "ouwiki");
    $buttontext = update_module_button($cm->id,$course->id,$strwiki);

    $extranavigation=array();
    if($afterpage && $pagename) {
        $extranavigation[]=array('name'=>htmlspecialchars($pagename),'type'=>'ouwiki',
            'link'=>'view.php?'.ouwiki_display_wiki_parameters($pagename,$subwiki,$cm));
    } else if($pagename) {
        $extranavigation[]=array('name'=>htmlspecialchars($pagename),'type'=>'ouwiki');
    } else if($afterpage) {
        $extranavigation[]=array('name'=>get_string('startpage','ouwiki'),'type'=>'ouwiki');
    } else {
    }
    if($afterpage) {
        foreach($afterpage as $element) {
            $extranavigation[]=$element;
        }
    }

    if(empty($title)) {

        $title = $wikiname;
    }
    $navigation=build_navigation($extranavigation, $cm);
    print_header_simple($title, "",
         $navigation, "", $head, true, $buttontext, navmenu($course, $cm));

    // Print group selector
    $selector = ouwiki_display_subwiki_selector($subwiki, $ouwiki, $cm,
            $context, $course);
    print $selector;

    // Print index link
    if(!$hideindex) {
        print '<div id="ouwiki_indexlinks">';

        print '<ul>';
        $isindex=basename($_SERVER['PHP_SELF'])=='wikiindex.php';
        if($isindex) {
            print '<li id="ouwiki_nav_index"><span>'.get_string('index','ouwiki').'</span></li>';
        } else {
            print '<li id="ouwiki_nav_index"><a href="wikiindex.php?'.
                ouwiki_display_wiki_parameters(null,$subwiki,$cm).'">'.get_string('index','ouwiki').'</a></li>';
        }
        $ishistory=basename($_SERVER['PHP_SELF'])=='wikihistory.php';
        if($ishistory) {
            print '<li id="ouwiki_nav_history"><span>'.get_string('wikirecentchanges','ouwiki').'</span></li>';
        } else {
            print '<li id="ouwiki_nav_history"><a href="wikihistory.php?'.
                ouwiki_display_wiki_parameters(null,$subwiki,$cm).'">'.get_string('wikirecentchanges','ouwiki').'</a></li>';
        }
        $isreports=basename($_SERVER['PHP_SELF'])=='reportssummary.php';
        if($isreports) {
            print '<li id="ouwiki_nav_report"><span>'.get_string('reports','ouwiki').'</span></li>';
        } else if(has_capability('mod/ouwiki:viewcontributions',$context)) {
            print '<li id="ouwiki_nav_report"><a href="reportssummary.php?'.
                ouwiki_display_wiki_parameters(null,$subwiki,$cm).'">'.get_string('reports','ouwiki').'</a></li>';
        }

        print '</ul>';

        if(ouwiki_search_installed()) {
            print '<form action="search.php" method="get"><div>';
            print ouwiki_display_wiki_parameters(null,$subwiki,$cm,OUWIKI_PARAMS_FORM);
            print '<label for="ouw_searchbox" class="accesshide">'.get_string('search','ouwiki').'</label>';
            $query=stripslashes(optional_param('query','',PARAM_RAW));
            print '<input type="text" id="ouw_searchbox" name="query" '.
                ($query ? 'value="'.htmlspecialchars($query).'" ' : '').'/>';
            print '<input type="submit" value="'.get_string('search','ouwiki').'" /></div></form>';
        }

        print '</div>';
    } else {
        print '<div id="ouwiki_noindexlink"></div>';
    }

    //adding a link to the computing guide
    if (class_exists('ouflags')) {
        global $CFG;
        require_once($CFG->dirroot.'/local/utils_shared.php');
        $computingguidelink = get_link_to_computing_guide('ouwiki');
        print '<span class="computing-guide"> '.$computingguidelink.'</span>';
    }

    print '<div class="clearer"></div>';
    if($notabs) {
        $extraclass=$selector ? ' ouwiki_gotselector' : '';
        print '<div id="ouwiki_belowtabs" class="ouwiki_notabs'.$extraclass.'">';
    }
}


/**
 * Obtains information about all versions of a wiki page in time order (newest first).
 * @param int $pageid Page ID
 * @param mixed $limitfrom If set, used to return results starting from this index
 * @param mixed $limitnum If set, used to return only this many results
 * @return array An array of records (empty if none) containing id, timecreated, userid,
 *   username, firstname, and lastname fields.
 */
function ouwiki_get_page_history($pageid,$selectdeleted,$limitfrom='',$limitnum='') {
    global $CFG;

    // Set AND clause if not selecting deleted page versions
    $ANDclause = '';
    if (!$selectdeleted) {
        $ANDclause = ' AND v.deletedat IS NULL';
    }

    $result=get_records_sql("
SELECT
    v.id AS versionid,v.timecreated, v.deletedat,
    u.id,u.username,u.firstname,u.lastname
FROM
    {$CFG->prefix}ouwiki_versions v
    LEFT JOIN {$CFG->prefix}user u ON v.userid=u.id
WHERE
    v.pageid=$pageid$ANDclause
ORDER BY
    v.id DESC
",$limitfrom,$limitnum);
    // Fix confusing behaviour when no results
    if(!$result) {
        $result=array();
    }
    return $result;
}

/**
 * Obtains the index information of a subwiki.
 * @param int $subwikiid ID of subwiki
 * @param mixed $limitfrom If set, used to return results starting from this index
 * @param mixed $limitnum If set, used to return only this many results
 * @return array Array of objects, one per page, containing the following fields:
 *   pageid, title, versionid, timecreated, (user) id, username, firstname, lastname,
 *   and linksfrom which is an array of page IDs of pages that currently link to this
 *   one.
 */
function ouwiki_get_subwiki_index($subwikiid,$limitfrom='',$limitnum='') {
    global $CFG;

    // Get all the pages...
    $pages=get_records_sql("
SELECT
    p.id AS pageid,p.title,
    v.id AS versionid,v.timecreated,
    u.id,u.username,u.firstname,u.lastname
FROM
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON p.currentversionid=v.id
    LEFT JOIN {$CFG->prefix}user u ON v.userid=u.id
WHERE
    p.subwikiid={$subwikiid}
    AND v.deletedat IS NULL
ORDER BY
    CASE WHEN p.title IS NULL THEN '' ELSE UPPER(p.title) END
",$limitfrom,$limitnum);
    // Fix confusing behaviour when no results
    if(!$pages) {
        $pages=array();
    }
    foreach($pages as $page) {
        $page->linksfrom=array();
    }

    // ...and now get all the links for those pages
    if(count($pages)) {
        $pagelist=implode(',',array_keys($pages));
        $links=get_records_sql("
SELECT
    l.id,l.topageid,p.id AS frompage
FROM
    {$CFG->prefix}ouwiki_links l
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.currentversionid=l.fromversionid
WHERE
    l.topageid IN ($pagelist)
");
    } else {
        $links=false;
    }
    if(!$links) {
        $links=array();
    }

    // Add links into pages array
    foreach($links as $obj) {
        $pages[$obj->topageid]->linksfrom[]=$obj->frompage;
    }

    return $pages;
}

/**
 * Obtains list of recent changes across subwiki.
 * @param int $subwikiid ID of subwiki
 * @param int $limitfrom Database result start, if set
 * @param int $limitnum Database result count (default 51)
 */
function ouwiki_get_subwiki_recentchanges($subwikiid,$limitfrom='',$limitnum=51) {
    global $CFG;

    $result=get_records_sql($q="
SELECT
    v.id AS versionid,v.timecreated,v.userid,
    p.id AS pageid,p.subwikiid,p.title,p.currentversionid,
    u.firstname,u.lastname,u.username,
    (SELECT
        MAX(id)
    FROM
        {$CFG->prefix}ouwiki_versions v2
    WHERE
        v2.pageid=p.id AND v2.id < v.id) AS previousversionid
FROM
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id
    LEFT JOIN {$CFG->prefix}user u ON v.userid=u.id
WHERE
    p.subwikiid=$subwikiid
    AND v.deletedat IS NULL
ORDER BY
    v.id DESC
",$limitfrom,$limitnum);
    if(!$result) {
        $result=array();
    }
    return $result;
}

/**
 * Obtains list of contributions to wiki made by a particular user,
 * in similar format to the 'recent changes' list except ordered by page
 * then date.
 * @param int $subwikiid ID of subwiki
 * @param int $userid ID of subwiki
 * @return Array of all changes (zero-length if none)
 */
function ouwiki_get_contributions($subwikiid,$userid) {
    global $CFG;

    $result=get_records_sql($q="
SELECT
    v.id AS versionid,v.timecreated,v.userid,
    p.id AS pageid,p.subwikiid,p.title,p.currentversionid,
    (SELECT
        MAX(id)
    FROM
        {$CFG->prefix}ouwiki_versions v2
    WHERE
        v2.pageid=p.id AND v2.id < v.id) AS previousversionid
FROM
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id
WHERE
    p.subwikiid=$subwikiid AND v.userid=$userid
    AND v.deletedat IS NULL
ORDER BY
    CASE WHEN p.title IS NULL THEN '' ELSE UPPER(p.title) END,v.id
");
    if(!$result) {
        $result=array();
    }
    return $result;
}


/**
 * Obtains list of recently created pages across subwiki.
 * @param int $subwikiid ID of subwiki
 * @param int $limitfrom Database result start, if set
 * @param int $limitnum Database result count (default 51)
 * @return Array (may be 0-length) of page-version records, with the following
 *   fields: pageid,subwikiid,title,currentversionid,versionid,timecreated,userid,
 *   firstname,lastname,username. The version fields relate to the first version of
 *   the page.
 */
function ouwiki_get_subwiki_recentpages($subwikiid,$limitfrom='',$limitnum=51) {
    global $CFG;

    $result=get_records_sql("
SELECT
    p.id AS pageid,p.subwikiid,p.title,p.currentversionid,
    v.id AS versionid,v.timecreated,v.userid,
    u.firstname,u.lastname,u.username
FROM
    {$CFG->prefix}ouwiki_versions v
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON v.pageid=p.id
    LEFT JOIN {$CFG->prefix}user u ON v.userid=u.id
WHERE
    v.id IN (
        SELECT
            MIN(v2.id)
        FROM
            {$CFG->prefix}ouwiki_pages p2
            INNER JOIN {$CFG->prefix}ouwiki_versions v2 ON v2.pageid=p2.id
        WHERE
            p2.subwikiid=$subwikiid
            AND v2.deletedat IS NULL
        GROUP BY p2.id)
ORDER BY
    v.id DESC
",$limitfrom,$limitnum);
    if(!$result) {
        $result=array();
    }
    return $result;
}

/**
 * Obtains the list of pages in a subwiki that are linked to, but do not exist.
 *
 * @param int $subwikiid ID of subwiki
 * @param mixed $limitfrom If set, used to return results starting from this index
 * @param mixed $limitnum If set, used to return only this many results
 * @return array Array of missing title => array of source page titles. Sorted
 *   in alphabetical order of missing title.
 */
function ouwiki_get_subwiki_missingpages($subwikiid,$limitfrom='',$limitnum='') {
    global $CFG;

    // Get all the pages that either link to a nonexistent page, or link to
    // a page that has been created but has no versions.
    $result=get_records_sql("
SELECT
    l.id,l.tomissingpage,p2.title,p.title AS fromtitle
FROM
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON p.currentversionid=v.id
    INNER JOIN {$CFG->prefix}ouwiki_links l ON v.id=l.fromversionid
    LEFT JOIN {$CFG->prefix}ouwiki_pages p2 ON l.topageid=p2.id
WHERE
    p.subwikiid={$subwikiid}
    AND (l.tomissingpage IS NOT NULL OR (l.topageid IS NOT NULL AND p2.currentversionid IS NULL))
    AND v.deletedat IS NULL
",$limitfrom,$limitnum);
    // Fix confusing behaviour when no results
    if(!$result) {
        $result=array();
    }
    $missing=array();
    foreach($result as $obj) {
        if(is_null($obj->tomissingpage) || $obj->tomissingpage==='') {
            $title=$obj->title;
        } else {
            $title=$obj->tomissingpage;
        }
        if(!array_key_exists($title,$missing)) {
            $missing[$title]=array();
        }
        $missing[$title][]=$obj->fromtitle;
    }
    uksort($missing,'strnatcasecmp');
    return $missing;
}

/**
 * Given HTML content, finds all our marked section headings.
 * @param string $content XHTML content
 * @return array Associative array of section ID => current title
 */
function ouwiki_find_sections($content) {
    $results=array();
    $matchlist=array();
    preg_match_all('|<h([0-9]) id="ouw_s([0-9]+_[0-9]+)">(.*?)</h([0-9])>|s',$content,$matchlist,PREG_SET_ORDER);
    foreach($matchlist as $matches) {
        if($matches[1]!=$matches[4]) {
            // Some weird s*** with nested headings
            continue;
        }
        $section=$matches[2];
        $content=$matches[3];
        // Remove tags and decode entities
        $content=preg_replace('|<.*?>|','',$content);
        $content=html_entity_decode($content,ENT_QUOTES,'UTF-8');
        // Tidy up whitespace
        $content=preg_replace('|\s+|',' ',$content);
        $content=trim($content);
        if($content) {
           $results[$section]=$content;
        }
    }
    return $results;
}

/**
 * Obtains various details about a named section. (This function will call error()
 * if it can't find the section; it shouldn't fail if the section was checked with
 * ouwiki_find_sections.)
 * @param string $content XHTML content
 * @param string $sectionxhtmlid ID of desired section
 * @return Object containing ->startpos and ->content
 */
function ouwiki_get_section_details($content,$sectionxhtmlid) {
    // Check heading number
    $matches=array();
    if(!preg_match('|<h([0-9]) id="ouw_s'.$sectionxhtmlid.'">|s',$content,$matches)) {
        error('Unable to find expected section');
    }
    $h=$matches[1];

    // Find position of heading and of next heading with equal or lower number
    $startpos=strpos($content,$stupid='<h'.$h.' id="ouw_s'.$sectionxhtmlid.'">');
    if($startpos===false) {
        error('Unable to find expected section again');
    }
    $endpos=strlen($content);
    for($count=1;$count<=$h;$count++) {
        $nextheading=strpos($content,'<h'.$count,$startpos+1);
        if($nextheading!==false && $nextheading < $endpos) {
            $endpos=$nextheading;
        }
    }

    // Extract the relevant slice of content and return
    $result=new StdClass;
    $result->startpos=$startpos;
    $result->size=$endpos-$startpos;
    $result->content=substr($content,$startpos,$result->size);
    return $result;
}

function ouwiki_internal_re_headings($matches) {
    global $ouwiki_internal_re;
    return '<h'.$matches[1].' id="ouw_s'.$ouwiki_internal_re->version.'_'.($ouwiki_internal_re->count++).'">';
}

/**
 * Saves a change to the given page while recording section details.
 * @param object $cm Course-module object
 * @param object $subwiki Subwiki object
 * @param string $pagename Name of page (NO SLASHES)
 * @param string $contentbefore Previous XHTML Content (NO SLASHES)
 * @param string $newcontent Content of new section (NO SLASHES)
 * @param object $sectiondetails Information from ouwiki_get_section_details for section
 */
function ouwiki_save_new_version_section($course,$cm,$ouwiki,$subwiki,$pagename,$contentbefore,$newcontent,$sectiondetails) {
    // Put section into content
    $result=substr($contentbefore,0,$sectiondetails->startpos).$newcontent.
        substr($contentbefore,$sectiondetails->startpos+$sectiondetails->size);
    // Store details of change size in db
    ouwiki_save_new_version($course,$cm,$ouwiki,$subwiki,$pagename,$result,
        $sectiondetails->startpos,strlen($newcontent),$sectiondetails->size);
}

/**
 * Internal function. Sorts deletions into reverse order so the byte numbers
 * stay accurate.
 * @param object $a Deletion object
 * @param object $b Other one
 * @return int Negative to put $a before $b, etc
 */
function ouwiki_internal_sort_deletions($a,$b) {
    return $b->startbyte - $a->startbyte;
}

/**
 * Saves a new version of the given named page within a subwiki. Can create
 * a new page or just add a new version to an existing one. In case of
 * failure, ends up calling error() rather than returning something.
 * @param object $course Course object
 * @param object $cm Course-module object
 * @param object $ouwiki OU wiki object
 * @param object $subwiki Subwiki object
 * @param string $pagename Name of page (NO SLASHES)
 * @param string $content XHTML Content (NO SLASHES)
 * @param int $changestart For section changes. Start position of change. (-1 if not section change)
 * @param int $changesize Size of changed section.
 * @param int $changeprevsize Previous size of changed section
 * @param bool $nouser If true, creates as system
 */
function ouwiki_save_new_version($course,$cm,$ouwiki,$subwiki,$pagename,$content,$changestart=-1,$changesize=-1,$changeprevsize=-1,
    $nouser=false) {
    $tw=new transaction_wrapper();

    // Find page if it exists
    $pageversion=ouwiki_get_current_page($subwiki,$pagename,OUWIKI_GETPAGE_CREATE);

    // Analyse content for HTML headings that don't already have an ID.
    // These are all assigned unique, fairly short IDs.

    // Get number of version [guarantees in-page uniqueness of generated IDs]
    $versionnumber=count_records('ouwiki_versions','pageid',$pageversion->pageid);

    // Remove any spaces from annotation tags that were added for editing or by users
    // and remove any duplicate annotation tags
    $pattern = '~<span\b.id=\"annotation(.+?)\">.*?</span>~';
    $replace = '<span id="annotation$1"></span>';
    $content=preg_replace($pattern,$replace,$content);
    unset($pattern,$replace,$used);

    // Get rid of any heading tags that only contain whitespace
    $emptypatterns=array();
    for($i=1;$i<=6;$i++) {
        $emptypatterns[]='~<h'.$i.'[^>]*>\s*(<br[^>]*>\s*)*</h'.$i.'>~';
    }
    $content=preg_replace($emptypatterns,'',$content);

    // List all headings that already have IDs, to check for duplicates
    $matches=array();
    preg_match_all('|<h[1-9] id="ouw_s(.*?)">(.*?)</h[1-9]>|',
        $content,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

    // Organise list by ID
    $byid=array();
    foreach($matches as $index=>$data) {
        $id=$data[1][0];
        if(!array_key_exists($id,$byid)) {
            $byid[$id]=array();
        }
        $byid[$id][]=$index;
    }

    // Handle any duplicates
    $deletebits=array();
    foreach($byid as $id=>$duplicates) {
        if(count($duplicates)>1) {
            // We have a duplicate. By default, keep the first one
            $keep=$duplicates[0];

            // See if there is a title entry in the database for it
            $knowntitle=get_field('ouwiki_sections','title',
                'xhtmlid',addslashes($id),'pageid',$pageversion->pageid);
            if($knowntitle) {
                foreach($duplicates as $duplicate) {
                    $title=ouwiki_get_section_title(null,null,$matches[$duplicate][2][0]);
                    if($title===$knowntitle) {
                        $keep=$duplicate;
                        break;
                    }
                }
            }

            foreach($duplicates as $duplicate) {
                if($duplicate!==$keep) {
                    $deletebits[]=(object)array(
                        'startbyte'=>$matches[$duplicate][1][1]-10,
                        'bytes'=>strlen($matches[$duplicate][1][0])+11);
                }
            }
        }
    }

    // Were there any?
    if(count($deletebits)>0) {
        // Sort in reverse order of starting position
        usort($deletebits,'ouwiki_internal_sort_deletions');

        // Delete each bit
        foreach($deletebits as $deletebit)  {
            $content=substr($content,0,$deletebit->startbyte).
                substr($content,$deletebit->startbyte+$deletebit->bytes);
        }
    }

    // Replace existing empty headings with an ID including version count plus another index
    global $ouwiki_count; // Nasty but I can't think of a better way!
    $ouwiki_count=0;
    global $ouwiki_internal_re;
    $ouwiki_internal_re->version=$versionnumber;
    $ouwiki_internal_re->count=0;
    $sizebefore=strlen($content);
    $content=preg_replace_callback('/<h([1-9])>/','ouwiki_internal_re_headings',$content);
    $sizeafter=strlen($content);

    // Replace wiki links to [[Start page]] with the correct (non
    // language-specific) format [[]]
    $regex = str_replace('.*?', preg_quote(get_string('startpage', 'ouwiki')),
        OUWIKI_LINKS_SQUAREBRACKETS) . 'ui';
    $content = preg_replace($regex, '[[]]', $content);

    // Create version
    $version=new StdClass;
    $version->pageid=$pageversion->pageid;
    $version->xhtml=addslashes($content);
    $version->timecreated=time();
    if(!$nouser) {
        global $USER;
        $version->userid=$USER->id;
    }
    if($changestart!=-1) {
        $version->changestart=$changestart;
        // In tracking the new size, account for any added headings etc
        $version->changesize=$changesize+($sizeafter-$sizebefore);
        $version->changeprevsize=$changeprevsize;
    }
    if(!($versionid=insert_record('ouwiki_versions',$version))) {
        $tw->rollback();
        ouwiki_dberror();
    }

    // Update latest version
    if(!set_field('ouwiki_pages','currentversionid',$versionid,'id',$pageversion->pageid)) {
        $tw->rollback();
        ouwiki_dberror();
    }

    // Analyse for links
    $wikilinks=array();
    $externallinks=array();

    // Wiki links: ordinary [[links]]
    $matches=array();
    preg_match_all(OUWIKI_LINKS_SQUAREBRACKETS,$content,$matches,PREG_PATTERN_ORDER);
    foreach($matches[1] as $match) {
        // Convert to page name (this also removes HTML tags etc)
        $wikilinks[] = ouwiki_get_wiki_link_details($match)->page;
    }

    // Note that we used to support CamelCase links but have removed support because:
    // 1. Confusing: students type JavaScript or MySpace and don't expect it to become a link
    // 2. Not accessible: screenreaders cannot cope with run-together words, and
    //    dyslexic students can have difficulty reading them

    // External links
    preg_match_all('/<a [^>]*href=(?:(?:\'(.*?)\')|(?:"(.*?))")/',
        $content,$matches,PREG_PATTERN_ORDER);
    foreach($matches[1] as $match) {
        if($match) {
            $externallinks[]=html_entity_decode($match);
        }
    }
    foreach($matches[2] as $match) {
        if($match) {
            $externallinks[]=html_entity_decode($match);
        }
    }

    // Add link records
    $link=new StdClass;
    $link->fromversionid=$versionid;
    foreach($wikilinks as $targetpage) {
        if(!empty($targetpage)) {
            $pagerecord=get_record_select('ouwiki_pages',"subwikiid='{$subwiki->id}' AND UPPER(title)=UPPER('".addslashes($targetpage)."')");
            if($pagerecord) {
                $pageid=$pagerecord->id;
            } else {
                $pageid=false;
            }
        } else {
            $pageid=get_field_select('ouwiki_pages','id',"subwikiid={$subwiki->id} AND title IS NULL");
        }
        if($pageid) {
            $link->topageid=$pageid;
            $link->tomissingpage=null;
        } else {
            $link->topageid=null;
            $link->tomissingpage=addslashes(strtoupper($targetpage));
        }
        if(!($link->id=insert_record('ouwiki_links',$link))) {
            $tw->rollback();
            ouwiki_dberror();
        }
    }
    $link->topageid=null;
    $link->tomissingpage=null;
    $tl = textlib_get_instance();
    foreach ($externallinks as $url) {
        // Restrict length of URL
        if ($tl->strlen($url) > 255) {
            $url = $tl->substr($url, 0, 255);
        }
        $link->tourl = addslashes($url);
        if(!($link->id=insert_record('ouwiki_links',$link))) {
            $tw->rollback();
            ouwiki_dberror();
        }
    }

    // Inform search, if installed
    if(ouwiki_search_installed()) {
        $doc=new ousearch_document();
        $doc->init_module_instance('ouwiki',$cm);
        if($subwiki->groupid) {
            $doc->set_group_id($subwiki->groupid);
        }
        $doc->set_string_ref($pageversion->title==='' ? null : $pageversion->title);
        if($subwiki->userid) {
            $doc->set_user_id($subwiki->userid);
        }
        $title=is_null($pageversion->title) ? '' : $pageversion->title;
        if(!$doc->update($title,$content)) {
            $tw->rollback();
            ouwiki_dberror();
        }
    }

    // Inform completion system, if available
    if(class_exists('ouflags')) {
        if(completion_is_enabled($course,$cm) && ($ouwiki->completionedits || $ouwiki->completionpages)) {
            completion_update_state($course,$cm,COMPLETION_COMPLETE);
        }
    }

    $tw->commit();
}

/**
 * Given the text of a wiki link (between [[ and ]]), this function converts it
 * into a safe page name by removing white space at each end and restricting to
 * max 200 characters. Also splits out the title (if provided).
 * @param string $wikilink HTML code between [[ and ]]
 * @return object Object with parameters ->page (page name as PHP UTF-8
 *   string), ->title (link title as HTML; either an explicit title if specified
 *   or the start page string or the page name as html), ->rawpage (page name
 *   as HTML including possible entities, tags), and ->rawtitle (link title if
 *   specified as HTML including possible entities, tags; null if not specified)
 */
function ouwiki_get_wiki_link_details($wikilink) {
    // Split out title if present (note: because | is lower-ascii it is safe
    // to use byte functions rather than UTF-8 ones)
    $rawtitle = null;
    $bar = strpos($wikilink, '|');
    if ($bar !== false) {
        $rawtitle = trim(substr($wikilink, $bar+1));
        $wikilink = substr($wikilink, 0, $bar);
    }

    // Remove whitespace at either end
    $wikilink = trim($wikilink);
    $rawpage = $wikilink;

    // Remove html tags
    $wikilink = html_entity_decode(preg_replace(
        '/<.*?>/', '', $wikilink), ENT_QUOTES, 'UTF-8');

    // Trim to 200 characters or less (note: because we don't want to cut it off
    // in the middle of a character, we use proper UTF-8 functions)
    $tl = textlib_get_instance();
    if ($tl->strlen($wikilink) > 200) {
        $wikilink = $tl->substr($wikilink, 0, 200);
        $space = $tl->strrpos($wikilink, ' ');
        if ($space > 150) {
            $wikilink = $tl->substr($wikilink, 0, $space);
        }
    }

    // What will the title be of this link?
    if ($rawtitle) {
        $title = $rawtitle;
    } else if ($wikilink === '') {
        $title = get_string('startpage', 'ouwiki');
    } else {
        $title = $rawpage;
    }

    // Return object with both pieces of information
    return (object)array('page'=>$wikilink, 'title'=>$title,
        'rawtitle'=>$rawtitle, 'rawpage'=>$rawpage);
}

/** @return True if OU search extension is installed */
function ouwiki_search_installed() {
    return @include_once(dirname(__FILE__).'/../../blocks/ousearch/searchlib.php');
}

/**
 * Obtains all comments for a particular section of a particular wiki page.
 * @param int $pageid ID of wiki page
 * @param string $sectionxhtmlid XHTML id of the section we're interested in
 *   or null for main section
 * @param bool $includedeleted If false, excludes deleted comments
 * @param array $validsections Array of current valid XHTML ids=>titles - only required
 *   if $sectionxhtmlid is null
 * @return array Array of comment objects (each has fields from comments table
 *   plus ->sectiontitle and the user's ->firstname ->lastname ->username (null if
 *   system user) in date order, earliest first.
 */
function ouwiki_get_all_comments($pageid,$sectionxhtmlid,$includedeleted=false,$validsections=null) {
    if(!$sectionxhtmlid) {
        $validsectionlist='';

        foreach($validsections as $validsection=>$ignoretitle) {
            if($validsectionlist!=='') {
                $validsectionlist.=',';
            }
            $validsectionlist.="'".addslashes($validsection)."'";
        }
        if($validsectionlist==='') {
            $sectioncheck='';
        } else {
            $sectioncheck=" AND (s.xhtmlid IS NULL OR NOT s.xhtmlid IN ($validsectionlist))";
        }
    }
    return ouwiki_internal_get_comments("s.pageid=$pageid".($sectionxhtmlid
        ? " AND s.xhtmlid IS NOT NULL AND s.xhtmlid='".addslashes($sectionxhtmlid)."'" : $sectioncheck).
        ($includedeleted ? "" : " AND c.deleted=0"),
        "c.timeposted,c.id");
}

/**
 * Internal method that retrieves comments from the database along with standard
 * additional fields that are joined in.
 * @param string $where Where clause, not including 'WHERE'
 * @param string $orderby ORDER BY clause, not including 'ORDER BY'; optional
 * @return Array (always array, even if empty) of comment objects
 */
function ouwiki_internal_get_comments($where,$orderby=null) {
    global $CFG;
    $result=get_records_sql("
SELECT
    c.id,c.sectionid,c.title,c.xhtml,c.userid,c.timeposted,c.deleted,
    s.title AS sectiontitle,s.xhtmlid AS section,
    u.firstname,u.lastname,u.username
FROM
    {$CFG->prefix}ouwiki_sections s
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.sectionid=s.id
    LEFT JOIN {$CFG->prefix}user u ON c.userid=u.id
WHERE $where".($orderby ? " ORDER BY $orderby" : ''));
    if(!$result) {
        $result=array();
    }
    return $result;
}

/**
 * Obtains recent comments for each section of a particular wiki page.
 * @param int $pageid ID of wiki page
 * @param array $validsections Array of current valid XHTML ids=>titles
 * @param int $commentspersection Number of comments to include for each section
 * @return array Associative array from section xhtml id (no prefix) =>
 *   object. Each object contains ->count (total #comments, maybe >shown) and an
 *   array ->comments, ordered by date (0 = oldest). Each comment is
 *   an object from the ouwiki_comments table, plus ->sectiontitle and the
 *   user's ->firstname ->lastname ->username (null if system user).
 *   Comments on entire table are in $result[''].
 */
function ouwiki_get_recent_comments($pageid,$validsections,$commentspersection=3) {
    global $CFG;
    $results=array();
    $results['']->comments=array();
    $results['']->count=0;

    // This query runs in two stages: first get the list of IDs of all
    // comments on the page and from that extract the 'top 3' for each
    // section, then obtain full details of those.

    // The reason for this is that it's difficult to construct SQL
    // which that obtains the top 3 for each section on a page, including
    // the 'non-section' section, especially since this general section
    // can include things from past deleted sections that are not in
    // current valid sections. So obtaining all comments is the easiest way
    // to do it. But on a much-commented page, obtaining all comments could
    // result in a significant amount of data transfer. Instead we
    // retrieve the limited list and get the full results later.

    // 1a. Get list of all (non-deleted) comments on page
    $rs = get_recordset_sql("
SELECT
    s.xhtmlid,c.id
FROM
    {$CFG->prefix}ouwiki_sections s
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.sectionid=s.id
WHERE
    s.pageid=$pageid AND c.deleted=0
ORDER BY
    c.timeposted DESC,c.id DESC");

    // 1b. Categorise comments by section
    $numbers=array();
    $numbers['']=array();
    while($rec=rs_fetch_next_record($rs)) {

        // Where should record go?
        if($rec->xhtmlid && array_key_exists($rec->xhtmlid,$validsections)) {
            // In a named section
            if(!array_key_exists($rec->xhtmlid,$numbers)) {
                $numbers[$rec->xhtmlid]=array();
                $results[$rec->xhtmlid]=new StdClass;
                $results[$rec->xhtmlid]->count=0;
            }
            if(count($numbers[$rec->xhtmlid])<$commentspersection) {
                $numbers[$rec->xhtmlid][]=$rec->id;
            }
            $results[$rec->xhtmlid]->count++;
        } else {
            // In main section
            if(count($numbers[''])<$commentspersection) {
                $numbers[''][]=$rec->id;
            }
            $results['']->count++;
        }
    }
    rs_close($rs);

    // 2a. Get list of all required IDs
    $ids=array();
    foreach($numbers as $list) {
        foreach($list as $id) {
            $ids[]=$id;
        }
    }
    $inlist=implode(',',$ids);

    // 2b. Query full details for necessary IDs
    if(count($ids)>0) {
        $allcomments=ouwiki_internal_get_comments("s.pageid=$pageid AND c.id IN ($inlist)");
        if(count($allcomments)!=count($ids)) {
            debugging('Unable to obtain comments from database!');
        }
    } else {
        $allcomments=array();
    }

    // 2c. Construct final results array
    foreach($numbers as $section=>$list) {
        $results[$section]->comments=array();
        $reverse=array_reverse($list);
        foreach($reverse as $id) {
            $results[$section]->comments[]=$allcomments[$id];
        }
    }

    return $results;
}

/**
 * Obtains the title (contents of h1-6 tag as plain text) for a
 * named section.
 * @param string $sectionxhtmlid Section ID not including prefix
 * @param string $xhtml Full XHTML content of page
 * @param string $extracted If the title has already been pulled out of
 *   the XHTML, supply this variable (other two are ignored)
 * @return mixed Title or false if not found
 */
function ouwiki_get_section_title($sectionxhtmlid,$xhtml,$extracted=null) {
    // Get from HTML if not already extracted
    $matches=array();
    if($extracted===null && preg_match(
        '|<h[1-9] id="ouw_s'.$sectionxhtmlid.'">(.*?)</h[1-9]>|',$xhtml,$matches)) {
        $extracted=$matches[1];
    }
    if($extracted===null) {
        // Not found in HTML
        return false;
    }

    // Remove tags and decode entities
    $stripped=preg_replace('|<.*?>|','',$extracted);
    $stripped=html_entity_decode($stripped,ENT_QUOTES,'UTF-8');
    // Tidy up whitespace
    $stripped=preg_replace('|\s+|',' ',$stripped);
    return trim($stripped);
}

define('OUWIKI_SYSTEMUSER',-1);

/**
 * Adds a new comment to a wiki page.
 * @param int $pageid ID of wiki page
 * @param string $sectionxhtmlid XHTML id (without prefix), null/empty for main
 * @param string $sectiontitle Title of section. null/empty for main
 * @param string $title Title (plain text, no XHTML, so & on its own is OK for instance)
 * @param string $xhtml Content in XHTML
 * @param int $userid If not specified/0, uses current user. Otherwise can set user
 *   or use OUWIKI_SYSTEMUSER to leave null for comments generated by system.
 */
function ouwiki_add_comment($pageid,$sectionxhtmlid,$sectiontitle,$title,$xhtml,$userid=0) {
    $tw=new transaction_wrapper();

    // Find or create section for comment
    $idclause=$sectionxhtmlid ? "xhtmlid='".addslashes($sectionxhtmlid)."'" : "xhtmlid IS NULL";
    $section=get_record_select('ouwiki_sections',"pageid=$pageid AND $idclause");
    if(!$section) {
        $section=new StdClass;
        $section->pageid=$pageid;
        if($sectionxhtmlid) {
            $section->xhtmlid=addslashes($sectionxhtmlid);
            $section->title=addslashes($sectiontitle);
        }
        if(!($section->id=insert_record('ouwiki_sections',$section))) {
            $tw->rollback();
            error('Failed to add section into database for comment');
        }
    }

    // Add comment itself
    $comment=new StdClass;
    $comment->sectionid=$section->id;
    if($title) {
        $comment->title=addslashes($title);
    }
    $comment->xhtml=addslashes($xhtml);
    if($userid==0) {
        global $USER;
        $comment->userid=$USER->id;
    } else if($userid!=-1) {
        $comment->userid=$userid;
    }

    $comment->timeposted=time();
    if(!($comment->id=insert_record('ouwiki_comments',$comment))) {
        $tw->rollback();
        error('Failed to add comment to database');
    }

    $tw->commit();
}

/**
 * Displays an array of comments in the order of the array.
 * @param array $comments Array of comment objects
 * @param string $fromphp Page name i.e. 'view' or 'comments'
 * @param string $section Comment section (null for main page)
 * @param string $page Name of page (null for startpage)
 * @param object $subwiki Current subwiki object
 * @param object $cm Course-module object
 * @param bool $candelete If true, user gets delete option for everything not just
 *   their own comments, also gets undelete
 * @return string XHTML for output
 */
function ouwiki_display_comments($comments,$section,$page,$subwiki,$cm,$showdelete=false,$candelete=false) {
    global $USER;
    $out='<ul class="ouw_comments">';
    $first=true;
    $strcommenter=get_string('access_commenter','ouwiki').': ';
    $strdate=get_string('date').': ';
    foreach($comments as $comment) {
        $out.='<li class="ouw_comment'.($comment->deleted ? ' ouw_deletedcomment' : '').
            ($first ? ' ouw_firstcomment' : '').'">';
        $first=false;
        $user=new StdClass;
        $user->id=$comment->userid;
        $user->firstname=$comment->firstname;
        $user->lastname=$comment->lastname;
        $out.='<div class="ouw_commentposter"><span class="accesshide">'.$strcommenter.'</span>'.ouwiki_display_user($user,$cm->course).'</div>';
        $out.='<div class="ouw_commentdate"><span class="accesshide">'.$strdate.'</span>'.ouwiki_nice_date($comment->timeposted,false,true).'</div>';
        if($comment->section!=$section) {
            $out.='<div class="ouw_commentsection">'.get_string('commentoriginalsection','ouwiki',$comment->sectiontitle).'</div>';
        }

        if($comment->title!==null) {
            $out.='<h3 class="ouw_commenttitle">'.htmlspecialchars($comment->title).'</h3>';
        }
        $out.='<div class="ouw_commentxhtml">';
        $out.=format_text($comment->xhtml);
        $out.='</div>';
        if($showdelete && ($candelete||($USER->id==$comment->userid))) {
            $out.='<form class="ouw_commentsubmit" action="deletecomment.php" method="post">';
            $out.=ouwiki_display_wiki_parameters($page,$subwiki,$cm,OUWIKI_PARAMS_FORM);
            $out.='<input type="hidden" name="section" value="'.$section.'" />';
            $out.='<input type="hidden" name="comment" value="'.$comment->id.'" />';
            $out.='<input type="hidden" name="delete" value="'.($comment->deleted?0:1).'" />';
            $out.='<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            $out.='<input type="submit" value="'.get_string($comment->deleted?'commentundelete':'commentdelete','ouwiki').'" />';
            $out.='</form>';
        }
        $out.='</li>';
    }
    $out.='</ul>';
    return $out;
}

/**
 * Obtains HTML for the 'post comment' form.
 * @param string $fromphp Page name i.e. 'view' or 'comments'
 * @param string $section Comment section (null for main page)
 * @param string $page Name of page (null for startpage)
 * @param object $subwiki Current subwiki object
 * @param object $cm Course-module object
 */
function ouwiki_display_comment_form($fromphp,$section,$sectiontitle,$page,$subwiki,$cm) {
    $out='<form class="ouw_addcomment" action="addcomment.php" method="post"><div>';
    $out.=ouwiki_display_wiki_parameters($page,$subwiki,$cm,OUWIKI_PARAMS_FORM);
    $out.='<input type="hidden" name="fromphp" value="'.$fromphp.'" />';
    $out.='<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    if($section || $fromphp=='view') {
        $out.='<input type="hidden" name="section" id="ouw_ac_section" value="'.$section.'" />';
    }
    $idpart=$section?'_'.$section:'';
    $out.='<div class="ouw_ac_field"><label for="ouw_ac_title">'.get_string('commentsubject','ouwiki').
        '</label><input class="ouw_ac_input" name="title" id="ouw_ac_title" type="text" /></div>';
    $out.='<div class="ouw_ac_field"><label for="edit-xhtml">'.get_string('commenttext','ouwiki').
        '</label>';
    if(defined('OUWIKI_COMMENTS_HTMLEDITOR')) {
        $usehtmleditor=can_use_html_editor();
        if($usehtmleditor) {
            $out.='<table><tr><td>';
        }
    } else {
        $usehtmleditor=false;
    }
    ob_start();
    print_textarea($usehtmleditor, $fromphp=='view' ? 5 : 10, 50, 0, 0, 'xhtml','');
    $out.=ob_get_contents();
    ob_end_clean();
    if($usehtmleditor) {
        $out.='</td></tr></table>';
    }
    $out.='</div>';
    $out.='<input type="submit" class="ouw_ac_submit" value="'.get_string('commentpost','ouwiki').'" />';
    $out.='</div></form>';
    if($usehtmleditor) {
        ob_start();
        use_html_editor();
        $out.=ob_get_contents();
        ob_end_clean();
    }
    return $out;
}

/**
 * Deletes or undeletes a comment.
 * @param int $pageid ID of page that contains comment
 * @param int $commentid ID of comment
 * @param int $delete 1 to delete, 0 to undelete
 */
function ouwiki_delete_comment($pageid,$commentid,$delete) {
    $comment=get_record('ouwiki_comments','id',$commentid);
    if(!$comment) {
        error('Requested comment does not exist');
    }
    $section=get_record('ouwiki_sections','id',$comment->sectionid);
    if(!$section) {
        error('Page-section is missing from database');
    }
    if($section->pageid!=$pageid) {
        error('Comment is not on specified page');
    }
    if(!set_field('ouwiki_comments','deleted',$delete,'id',$commentid)) {
        error('Unable to set comment deleted status');
    }
}

/**
 * Obtains list of wiki links from other pages of the wiki to this one.
 * @param int $pageid
 * @return array Array (possibly zero-length) of page objects
 */
function ouwiki_get_links_to($pageid) {
    global $CFG;
    $links=get_records_sql("
SELECT
    DISTINCT p.id, p.title, UPPER(p.title) AS uppertitle
FROM
    {$CFG->prefix}ouwiki_links l
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.currentversionid=l.fromversionid
WHERE
    l.topageid=$pageid
ORDER BY
    UPPER(p.title)");
    return $links ? $links : array();
}

/** @return Array listing XHTML tags that we stick in a couple newlines after */
function ouwiki_internal_newline_tags() {
    return array('h1','h2','h3','h4','h5','h6','div','p','ul','li','table');
}

/**
 * Normalises/pretty-prints XHTML. This is intended to produce content that can
 * reasonably be edited using the plaintext editor and that has linebreaks only in
 * places we know about. Should be called before ouwiki_save_version.
 * @param string $content Content from html editor
 * @return string Content after pretty-printing
 */
function ouwiki_format_xhtml_a_bit($content) {
    // 0. Remove unnecessary linebreak at start of textarea
    if(substr($content,0,2)=="\r\n") {
        $content=substr($content,2);
    }

    // 1. Replace all (possibly multiple) whitespace with single spaces
    $content=preg_replace('/\s+/'," ",$content);

    // 2. Add two line breaks after tags marked as requiring newline
    $newlinetags=ouwiki_internal_newline_tags();
    $searches=array();
    foreach($newlinetags as $tag) {
        $searches[]='|(</'.$tag.'>) ?(?!\n\n)|i';
    }
    $content=preg_replace($searches,'$1'."\n\n",$content);

    // 3. Add single line break after <br/>
    $content=preg_replace('|(<br\s*/?>)\s*|','$1'."\n",$content);
    return $content;
}

function ouwiki_xhtml_to_plain($content) {
    // Just get rid of <br/>
    $content=preg_replace('|<br\s*/?>|','',$content);
    return $content;
}

function ouwiki_plain_to_xhtml($content) {
    // Convert CRLF to LF (makes easier!)
    $content=preg_replace('/\r?\n/',"\n",$content);

    // Remove line breaks that are added by format_xhtml_a_bit
    // i.e. that were already present
    $newlinetags=ouwiki_internal_newline_tags();
    $searches=array();
    foreach($newlinetags as $tag) {
        $searches[]='|(</'.$tag.'>)\n\n|i';
    }
    $content=preg_replace($searches,'$1',$content);

    // Now turn all the other line breaks into <br/>
    $content=str_replace("\n",'<br />',$content);
    return $content;
}

/**
 * @param string $content Arbitrary string
 * @return string Version of string suitable for inclusion in double-quoted
 *   Javascript variable within XHTML.
 */
function ouwiki_javascript_escape($content) {
    // Escape slashes
    $content=str_replace("\\","\\\\",$content);
// Escape newlines
    $content=str_replace("\n","\\n",$content);
    // Escape double quotes
    $content=str_replace('"','\\"',$content);
    // Remove ampersands and left-angle brackets (for XHTML)
    $content=str_replace('<','\\x3c',$content);
    $content=str_replace('&','\\x26',$content);
    return $content;
}

/**
 * Generates a 16-digit magic number at random.
 * @return string 16-digit long string
 */
function ouwiki_generate_magic_number() {
    $result=rand(1,9);
    for($i=0;$i<15;$i++) {
        $result.=rand(0,9);
    }
    return $result;
}


/**
 * @param object $subwiki For details of user/group and ID so that
 *   we can make links
 * @param object $cm Course-module object (again for making links)
 * @param object $pageversion Data from page and version tables.
 * @return string HTML content for page
 */

function ouwiki_display_create_page_form($subwiki,$cm,$pageversion) {
    $result='';

    $genericformdetails ='<form method="get" action="edit.php">
<div class="ouwiki_addnew_div">
<input type="hidden" name="originalpagename" value="'.$pageversion->title.'" />
'.ouwiki_display_wiki_parameters($pageversion->title,$subwiki,$cm,$type=OUWIKI_PARAMS_FORM);

$result.='<div id="ouwiki_addnew"><ul>
<li>
'.$genericformdetails.'
'.get_string('addnewsection','ouwiki').'
<input type="text" size="30" name="newsectionname" id="ouw_newsectionname" value="'.get_string('typeinsectionname','ouwiki').'" />
<input type="submit" id="ouw_add" name="ouw_subb" value="'.get_string('add','ouwiki').'" />
</div>
</form>
</li>
<li>
'.$genericformdetails.'
'.get_string('createnewpage','ouwiki').'
<input type="text" name="page" id="ouw_newpagename" size="30" value="'.get_string('typeinpagename','ouwiki').'" />
<input type="submit" id="ouw_create" name="ouw_subb" value="'.get_string('create','ouwiki').'" />
</div>
</form>
</li>
</ul>
</div>';

    return $result;
}


/**
 * @param string $cm ID of course module
 * @param string $subwiki details if it exists
 * @param string $pagename of the original wiki page for which the new page is a link of
 * @param string $newpagename page name of the new page being created
 * @param string $content of desired new page
 */

function ouwiki_create_new_page($course,$cm,$ouwiki,$subwiki,$pagename, $newpagename, $content) {

    // need to get old page and new page
    $sourcepage = ouwiki_get_current_page($subwiki, $pagename);
    $sourcecontent = $sourcepage->xhtml;
    $sourcecontent .= '<p>[['.htmlspecialchars($newpagename).']]</p>';

    // Create the new  page
    $pageversion = ouwiki_get_current_page($subwiki, $newpagename, OUWIKI_GETPAGE_CREATE);

    // need to save version - will call error if does not work
    ouwiki_save_new_version($course,$cm,$ouwiki,$subwiki,$newpagename,$content);

    // save the revised original page as a new version
    if(empty($pagename)) {
        $pagename = null;
    }
    add_to_log($course->id,'ouwiki',"add page",$pagename,$newpagename,$cm->id);
    ouwiki_save_new_version($course,$cm,$ouwiki,$subwiki,$pagename,$sourcecontent);
}


/**
 * Creates a new section on a page from scratch
 * @param string $cm ID of course module
 * @param string $subwiki details if it exists
 * @param string $pagename of the original wiki page for which the new page is a link of
 * @param string $newcontent of desired new section
 * @param string $sectionheader for the new section
*/
function ouwiki_create_new_section($course,$cm,$ouwiki,$subwiki,$pagename,$newcontent, $sectionheader){

    $sourcepage=ouwiki_get_current_page($subwiki, $pagename);
    $sectiondetails = ouwiki_get_new_section_details($sourcepage->xhtml, $sectionheader);
    ouwiki_save_new_version_section($course,$cm,$ouwiki, $subwiki,$pagename,$sourcepage->xhtml, $newcontent,$sectiondetails);

}


/**
 * Obtains various details about a named section. (This function will call error()
 * if it can't find the section; it shouldn't fail if the section was checked with
 * ouwiki_find_sections.)
 * @param string $content XHTML content
 * @param string $sectionheader for the new section
 * @return Object containing ->startpos and ->content
 */
function ouwiki_get_new_section_details($content, $sectionheader) {

    // Create new section details
    $result=new StdClass;
    $result->startpos=strlen($content);
    $result->size=0;
    $result->content=$sectionheader;
    return $result;
}

/**
 * Obtains information about all the annotations for the given page.
 * @param int $pageid ID of wiki page
 * @return array annotations indexed by annotation id. Returns an empty array if none found.
 */
function ouwiki_get_annotations($pageversion) {
    global $CFG;
    $annotations = array();

    $rs = get_records_sql("
SELECT
    a.id, a.pageid, a.userid, a.timemodified, a.content, u.firstname, u.lastname, u.picture, u.imagealt
FROM
    {$CFG->prefix}ouwiki_annotations a
    INNER JOIN {$CFG->prefix}user u ON a.userid=u.id
WHERE
    a.pageid = $pageversion->pageid
ORDER BY
    a.id");

    // look through the results and check for orphanes annotations. Also set the position and tag for later use.
    if($rs) {
        $annotations = $rs;
        foreach ($annotations As &$annotation) {
            $spanstr = '<span id="annotation'.$annotation->id.'">';
            $position = strpos($pageversion->xhtml, $spanstr);
            if ($position !== false) {
                $annotation->orphaned = 0;
                $annotation->position = $position;
                $annotation->annotationtag = $spanstr;
            } else {
                $annotation->orphaned = 1;
                $annotation->position = '';
                $annotation->annotationtag = '';
            }
            $annotation->content = stripslashes($annotation->content);
        }
    }

    return $annotations;
}

/**
 * Set up the html for the hidden annotation boxes for each none orphaned annotation
 * @param array $annotations all the annotations fot this page.
 * @return string $result text containing the html for the annotation boxes.
 */
function ouwiki_setup_hidden_annotation($annotation) {
    global $COURSE;
    $author->id = $annotation->userid;
    $author->firstname = $annotation->firstname;
    $author->lastname = $annotation->lastname;
    $author->picture = $annotation->picture;
    $author->imagealt = $annotation->imagealt;
    $classname = ($annotation->orphaned)? 'ouwiki-orphaned-annotation':'ouwiki-annotation';
    $picture = NULL;
    $size = 0;
    $return = true;
    $result = '<span class="'.$classname.'" id="annotationbox'.$annotation->id.'">'.
                print_user_picture($author, $COURSE->id, $picture, $size, $return).
                '<span class="ouwiki-annotation-content"><span class="ouwiki-annotation-content-title">'.
                fullname($author).'</span>'.
                $annotation->content.'</span></span>';
    return $result;
}

/**
 * Sets up the annotation markers
 * @param string $content The content (xhtml) to be displayed
 * @param int $pageid ID of wiki page
 * @return array annotations indexed by annotation id. Returns an empty array if none found.
 */
function ouwiki_setup_annotation_markers(&$content) {
    // get lists of all the tags
    $pattern = '~</?.+?>~';
    $taglist = array();
    $tagcount = preg_match_all($pattern,$content,$taglist,PREG_OFFSET_CAPTURE);

    $pattern = '~\[\[.+?]\]~';
    $taglist2 = array();
    $tagcount = preg_match_all($pattern,$content,$taglist2,PREG_OFFSET_CAPTURE);

    // merge the lists together
    $taglist = array_merge($taglist[0], $taglist2[0]);

    // create a new array of tags against char positions.
    $tagpositions = array();
    foreach($taglist as $tag) {
        $tagpositions[$tag[1]] = $tag[0];
    }

    // look at each postion, check it's not within a tag and create a list of space locations
    $spacepositions = array();
    $newcontent = '';
    $prevpos = 0;
    $space = false;
    $markeradded = false;
    $pos = 0;
    while ($pos < strlen($content)) {
        // we check if the $pos is the start of a tag and do something for particular tags
        if(array_key_exists($pos, $tagpositions)) {
            if ($tagpositions[$pos] == '<p>') {
                $newcontent .= $tagpositions[$pos];
                $pos += strlen($tagpositions[$pos]);
                $newcontent .= ouwiki_get_annotation_marker($pos);
                $markeradded = true;
                $space = false;
            } elseif ($tagpositions[$pos] == '</p>'){
                $newcontent .= ouwiki_get_annotation_marker($pos);
                $newcontent .= $tagpositions[$pos];
                $pos += strlen($tagpositions[$pos]);
                $markeradded = true;
                $space = false;
            } elseif (strpos($tagpositions[$pos], '<span id="annotation') !== false) {
                // we're at the opening annotation tag span so we need to skip past </span> which is the next tag
                // in $tagpositions[]
                $newcontent .= $tagpositions[$pos];
                $pos += strlen($tagpositions[$pos]);
                while(!array_key_exists($pos, $tagpositions)) {
                    $newcontent .= substr($content, $pos, 1);
                    $pos++;
                    print_object('while '.$pos);
                }

                $newcontent .= $tagpositions[$pos];
                $pos += strlen($tagpositions[$pos]);
                $markeradded = true;
            } elseif (strpos($tagpositions[$pos], '<a ') !== false) {
                // markers are not added in the middle of an anchor tag so need to skip
                // to after the closing </a> in $tagpositions[]
                $newcontent .= ouwiki_get_annotation_marker($pos);
                $markeradded = true;
                $space = true;
                $newcontent .= $tagpositions[$pos];
                $pos += strlen($tagpositions[$pos]);
                while(!array_key_exists($pos, $tagpositions)) {
                    $newcontent .= substr($content, $pos, 1);
                    $pos++;
                }

                $newcontent .= $tagpositions[$pos];
                $pos += strlen($tagpositions[$pos]);
            } else {
                $newcontent .= $tagpositions[$pos];
                $pos += strlen($tagpositions[$pos]);
            }
        }

        // if we have not already inserted a marker then check for a space
        // next time through we can check for a non space char indicating the start of a new word
        if (!$markeradded) {
            // this is the first char so if no marker has been added due to a <p> then
            // pretend the preceding char was a space to force adding a marker
            if ($pos == 0) {
                $space = true;
            }
            if (substr($content, $pos, 1) === ' ') {
                $space = true;
            } elseif ($space) {
                $newcontent .= ouwiki_get_annotation_marker($pos);
                $space = false;
            }

            // add the current charactor from the original content
            $newcontent .= substr($content, $pos, 1);
            $pos++;
        } else {
            $markeradded = false;
        }
    }

    $content = $newcontent;
}

/**
 * Returns a formatted annotation marker
 * @param integer $position The character position of the annotation
 * @return string the formatted annotation marker
 */
function ouwiki_get_annotation_marker($position) {
    global $CFG;
    $icon = '<img src="annotation-marker.gif" alt="'.get_string('annotationmarker', 'ouwiki').'" title="'.get_string('annotationmarker', 'ouwiki').'" />';
    return '<span class="ouwiki-annotation-marker" id="marker'.$position.'">'.$icon.'</span>';
}

/**
 * Highlights existing annotations in the xhtml for display.
 * @param string $content The content (xhtml) to be displayed
 * @param object $annotations List of annotions in a object
 * @param string $page The page being displayed
 * @return nothing
 */
function ouwiki_highlight_existing_annotations(&$content,$annotations,$page) {
    global $CFG;
    $icon = '<img src="annotation.gif" alt="'.get_string('annotation', 'ouwiki').'" title="'.get_string('annotation', 'ouwiki').'" />';

    usort($annotations, "ouwiki_internal_position_sort");
    // we only need the used annotations, not the orphaned ones.
    $usedannotations = array();
    foreach($annotations as $annotation) {
        if (!$annotation->orphaned) {
            $usedannotations[$annotation->id] = $annotation;
        }
    }

    $annotationnumber = count($usedannotations);
    if ($annotationnumber) {
        // cycle through the annotations and process ready for display
        foreach($usedannotations as $annotation) {
            switch ($page) {
                case 'view':
                    $replace = '<span class="ouwiki-annotation-tag" id="annotation'.$annotation->id.'">'.
                       $icon.ouwiki_setup_hidden_annotation($annotation);
                    break;
                case 'annotate':
                    $replace = '<span id="zzzz'.$annotationnumber.'"><strong>('.$annotationnumber.')</strong>';
                    break;
                case 'edit':
                    $replace = $annotation->annotationtag.'&nbsp;';
                    break;
                default:
            }
            $content = str_replace($annotation->annotationtag,$replace,$content);
            $annotationnumber--;
        }
    }
}

/**
 * Inserts new annotations into the xhtml at the marker location
 * @param string $marker The marker id added to the annotation edit page
 * @param string &$xhtml A reference to the subwiki xhtml
 * @param string $content The content of the annotation
 */
function ouwiki_insert_annotation($position, &$xhtml, $id) {
    $replace = '<span id="annotation'.$id.'"></span>';
    $xhtml = substr_replace($xhtml, $replace, $position, 0);
}

/**
 * Array sort callback function
 */
function ouwiki_internal_position_sort($a, $b) {
    return intval($b->position) - intval($a->position);
}

/**
 * Cleans up the annotation tags
 * @param $updated_annotations
 * @param string &$xhtml A reference to the subwiki xhtml
 * @return bool $result
 */
function ouwiki_cleanup_annotation_tags($updated_annotations, &$xhtml) {
    $result = false;
    $matches = array();
    $pattern = '~<span\b.id=\"annotation([0-9].+?)\"[^>]*>(.*?)</span>~';

    preg_match_all($pattern, $xhtml, $matches);
    foreach($matches[1] as $match) {
        if(!array_key_exists($match, $updated_annotations)) {
            $deletepattern = '~<span\b.id=\"annotation'.$match.'\">.*?</span>~';
            $xhtml = preg_replace($deletepattern, '', $xhtml);
            $result = true;
        }
    }

    return $result;
}

/**
 * Sets the page editing lock according to $lock
 * @param integer $pageid Wiki page id
 * @param bool $lock
 * @return nothing
 */
function ouwiki_lock_editing($pageid, $lock) {
        $locked = ouwiki_is_page_editing_locked($pageid);

        if ($lock != $locked) {
            $dataobject->id = $pageid;
            $dataobject->locked = ($lock)? 1:0;

            try {
                update_record('ouwiki_pages', $dataobject);
            } catch (Exception $e) {
                ouwiki_dberror('Could not change the lock status for this wiki page');
            }
        }
}

/**
 * Returns the lock status of a wiki page
 * @param integer $pageid Wiki page id
 * @return bool True if locked
 */
function ouwiki_is_page_editing_locked($pageid) {
    global $CFG;

    $rs = get_records_sql("
SELECT
    locked
FROM
    {$CFG->prefix}ouwiki_pages
WHERE
    id = $pageid");

    foreach ($rs as $record) {
        return ($record->locked == '1') ? true:false;
    }

    return false;
}

/**
 * Sets up the lock page button and form html
 * @param object $pageversion Page/version object
 * @param int $cmid Course module id
 * @return string $result Contains the html for the form
 */
function ouwiki_display_lock_page_form($pageversion,$cmid) {
    $result='';

    $genericformdetails ='<form method="get" action="lock.php">
<div class="ouwiki_lock_div">
<input type="hidden" name="ouw_pageid" value="'.$pageversion->pageid.'" />
<input type="hidden" name="id" value="'.$cmid.'" />';
    $buttonvalue = ($pageversion->locked == '1')?  get_string('unlockpage','ouwiki'):get_string('lockpage','ouwiki');

$result.='<div id="ouwiki_lock">
'.$genericformdetails.'
<input type="submit" id="ouw_lock" name="ouw_lock" value="'.$buttonvalue.'" />
</div>
</form>
</div>';

    return $result;
}

/**
 * Sets up the editing lock
 * @param object $lock
 * @param string $ouwiki
 */
function ouwiki_print_editlock($lock, $ouwiki) {
    // Prepare the warning about lock without JS...
    $a=new StdClass;
    $a->now=userdate(time(),get_string('strftimetime'));
    $a->minutes=(int)(OUWIKI_LOCK_NOJS/60);
    $a->deadline=userdate(time()+$a->minutes*60,get_string('strftimetime'));
    $nojswarning=get_string('nojswarning','ouwiki',$a);
    $nojsstart='<p class="ouw_nojswarning">';

    // Put in the AJAX for keeping the lock, if on a supported browser
    $ie=check_browser_version('MSIE', 6.0);
    $ff=check_browser_version('Gecko', 20051106);
    $op=check_browser_version('Opera', 9.0);
    $sa=check_browser_version('Safari', 412);
    $js=$ie||$ff||$op||$sa;
    if($js) {
        $nojsdisabled=get_string('nojsdisabled','ouwiki');
        $nojs=$nojsstart.$nojsdisabled.' '.$nojswarning.
            '<img src="nojslock.php?lockid='.$lock->id.'" alt=""/></p>';

        print require_js(array('yui_yahoo','yui_event','yui_connection'),true);
        $strlockcancelled=ouwiki_javascript_escape(get_string('lockcancelled','ouwiki'));
        $intervalms=OUWIKI_LOCK_RECONFIRM*1000;

        $timeoutscript='';
        if($ouwiki->timeout) {
            $countdownurgent=ouwiki_javascript_escape(get_string('countdownurgent','ouwiki'));
            $timeoutscript="
        var ouw_countdownto=(new Date()).getTime()+1000*{$ouwiki->timeout};
        var ouw_countdowninterval=setInterval(function() {
            var countdown=document.getElementById('ouw_countdown');
            var timeleft=ouw_countdownto-(new Date().getTime());
            if(timeleft<0) {
                clearInterval(ouw_countdowninterval);
                document.forms['ouw_edit'].elements['save'].click();
                return;
            }
            if(timeleft<2*60*1000) {
                var urgent=document.getElementById('ouw_countdownurgent');
                if(!urgent.firstChild) {
                    urgent.appendChild(document.createTextNode(\"".$countdownurgent."\"));
                    countdown.style.fontWeight='bold';
                    countdown.style.color='red';
                }
            }
            var minutes=Math.floor(timeleft/(60*1000));
            var seconds=Math.floor(timeleft/1000) - minutes*60;
            var text=minutes+':';
            if(seconds<10) text+='0';
            text+=seconds;
            while(countdown.firstChild) {
                countdown.removeChild(countdown.firstChild);
            }
            countdown.appendChild(document.createTextNode(text));
        },500);
        ";
        }

        print "
        <script type='text/javascript'>
        var intervalID;
        function handleResponse(o) {
            if(o.responseText=='cancel') {
                document.forms['ouw_edit'].elements['preview'].disabled=true;
                document.forms['ouw_edit'].elements['save'].disabled=true;
                clearInterval(intervalID);
                alert(\"$strlockcancelled\");
            }
        }
        function handleFailure(o) {
            // Ignore for now
        }
        intervalID=setInterval(function() {
            YAHOO.util.Connect.asyncRequest('POST','confirmlock.php',
                {success:handleResponse,failure:handleFailure},'lockid={$lock->id}');
            },$intervalms);
        $timeoutscript
        </script>
        <noscript>
        $nojs
        </noscript>
        ";
    } else {
        // If they have a non-supported browser, update the lock time right now without
        // going through the dodgy image method, to reserve their 15-minute slot.
        // (This means it will work for Lynx, for instance.)
        print $nojsstart.get_string('nojsbrowser','ouwiki').' '.$nojswarning.'.</p>';
        $lock->seenat=time()+OUWIKI_LOCK_NOJS;
        update_record('ouwiki_locks',$lock);
    }
}

/**
 * Gets an integer representing the commenting system to use on this wiki.
 * @param string $commenting The commenting system selected on this wiki.
 * @return int A value representing the commenting system to use on this wiki.
 */
function ouwiki_get_commenting($commenting) {
    global $CFG;
    switch ($commenting) {
        case 'none':
            return 0;
            break;
        case 'annotations':
            return 1;
            break;
        case 'persection':
            return 2;
            break;
        case 'both':
            return 3;
            break;
        default:
            return $CFG->ouwiki_comment_system;
    }
}

/**
 * Get last-modified time for wiki, as it appears to this user. This takes into
 * account the user's groups/individual settings if required. (Does not check
 * that user can view the wiki.)
 * @param object $cm Course-modules entry for wiki
 * @param object $Course Course object
 * @param int $userid User ID or 0 = current
 * @return int Last-modified time for this user as seconds since epoch
 */
function ouwiki_get_last_modified($cm, $course, $userid=0) {
    global $CFG, $USER;
    if (!$userid) {
        $userid = $USER->id;
    }

    if (!($ouwiki = get_record('ouwiki', 'id', $cm->instance))) {
        return false;
    }

    // Default applies no restriction
    $restrictjoin = '';
    $restrictwhere = '';
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    switch($ouwiki->subwikis) {

    case OUWIKI_SUBWIKIS_SINGLE:
        break;

    case OUWIKI_SUBWIKIS_GROUPS:
        if (!has_capability('moodle/site:accessallgroups', $context, $userid) &&
            groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS) {
            // Restrictions only in separate groups mode and if you don't have
            // access all groups
            $restrictjoin = "
INNER JOIN {$CFG->prefix}groups_members gm ON gm.groupid = sw.groupid";
            $restrictwhere = "
AND gm.userid = $userid";
        }
        break;

    case OUWIKI_SUBWIKIS_INDIVIDUAL:
        if (has_capability('mod/ouwiki:viewallindividuals',$context)) {
            // You can view everyone: no restrictions
        } else if (has_capability('mod/ouwiki:viewgroupindividuals',$context)) {
            // You can view everyone in your group - TODO this is complicated
            $restrictjoin = "

INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid = sw.userid
INNER JOIN {$CFG->prefix}groups g ON g.id = gm.groupid
INNER JOIN {$CFG->prefix}groups_members gm2 ON gm2.groupid = g.id
";
            $restrictwhere = "
AND g.courseid = $course->id
AND gm2.userid = $userid";

            if ($cm->groupingid) {
                $restrictjoin .= "
INNER JOIN {$CFG->prefix}groupings_groups gg ON gg.groupid = g.id";
                $restrictwhere .= "
AND gg.groupingid = $cm->groupingid";
            }
        } else {
            // You can only view you
            $restrictwhere = "
AND sw.userid = $userid";
        }
        break;
    }

    // Query for newest version that follows these restrictions
    return get_field_sql("
SELECT
    MAX(v.timecreated)
FROM
    {$CFG->prefix}ouwiki_versions v
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON p.id = v.pageid
    INNER JOIN {$CFG->prefix}ouwiki_subwikis sw ON sw.id = p.subwikiid
    $restrictjoin
WHERE
    sw.wikiid = {$cm->instance}
    AND v.deletedat IS NULL
    $restrictwhere");
}
?>