<?php

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* Library of internal functions and constants for module tracker
*/

/**
* includes and requires
*/
require_once $CFG->dirroot.'/mod/tracker/filesystemlib.php';

// statusses
define('POSTED', 0);
define('OPEN', 1);
define('RESOLVING', 2);
define('WAITING', 3);
define('RESOLVED', 4);
define('ABANDONNED', 5);
define('TRANSFERED', 6);
define('TESTING', 7);

// states && eventmasks
define('EVENT_POSTED', 0);
define('EVENT_OPEN', 1);
define('EVENT_RESOLVING', 2);
define('EVENT_WAITING', 4);
define('EVENT_RESOLVED', 8);
define('EVENT_ABANDONNED', 16);
define('EVENT_TRANSFERED', 32);
define('ON_COMMENT', 64);
define('EVENT_TESTING', 128);

define('ALL_EVENTS', 255);

global $STATUSCODES;
global $STATUSKEYS;
$STATUSCODES = array(POSTED => 'posted', 
                    OPEN => 'open', 
                    RESOLVING => 'resolving', 
                    WAITING => 'waiting', 
                    RESOLVED => 'resolved', 
                    ABANDONNED => 'abandonned',
                    TRANSFERED => 'transfered',
                    TESTING => 'testing');

$STATUSKEYS = array(POSTED => get_string('posted', 'tracker'), 
                    OPEN => get_string('open', 'tracker'), 
                    RESOLVING => get_string('resolving', 'tracker'), 
                    WAITING => get_string('waiting', 'tracker'), 
                    RESOLVED => get_string('resolved', 'tracker'), 
                    ABANDONNED => get_string('abandonned', 'tracker'),
                    TRANSFERED => get_string('transfered', 'tracker'),
                    TESTING => get_string('testing', 'tracker'));

/**
* loads all elements in memory
* @uses $CFG
* @uses $COURSE
* @param reference $tracker the tracker object
* @param reference $elementsobj
*/
function tracker_loadelements(&$tracker, &$elementsobj){
    global $COURSE, $CFG;

    /// first get shared elements
    $elements = get_records('tracker_element', 'course' , 0);
    if (!$elements) $elements = array();

    /// get course scope elements
    $courseelements = get_records('tracker_element', 'course' , $COURSE->id);
    if ($courseelements){
        $elements = array_merge($elements, $courseelements);
    }

    /// make a set of element objet with records
    if (!empty($elements)){
        foreach ($elements as $element){
            if ($element->type == ''){
                $elementsobj[$element->id] = new trackerelement($tracker, $element->id);
                $elementsobj[$element->id]->setoptionsfromdb();
            }
            else{
                // this get the options by the constructor
                include_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/'.$element->type.'/'.$element->type.'.class.php');
                $constructorfunction = "{$element->type}element";
                $elementsobj[$element->id] = new $constructorfunction($tracker, $element->id);
            }
            $elementsobj[$element->id]->name = $element->name;
            $elementsobj[$element->id]->description = $element->description;
            $elementsobj[$element->id]->type = $element->type;
            $elementsobj[$element->id]->course = $element->course;
        }
    }
}

/**
* this implements an element factory
* makes a single element from a record if given an id, or a new element of a desired type
* @uses $CFG
* @param int $elementid
* @param string $type the type for creating a new element
* @return object
*/
function tracker_getelement(&$tracker, $elementid=null, $type=null){
    global $CFG;
    
    if ($elementid){
        $element = get_record('tracker_element', 'id' , $elementid);
        $elementtype = ($element) ? $element->type : $type ;

        if (!empty($element)){
            if ($element->type == ''){
                $elementobj = new trackerelement($tracker, $element->id);
                $elementobj->setoptionsfromdb();
            } else {
                include_once('classes/trackercategorytype/' . $element->type . '/'.$element->type.'.class.php');
                $constructorfunction = "{$elementtype}element";
                $elementobj = new $constructorfunction($tracker, $element->id);
            }
            $elementobj->name = $element->name;
            $elementobj->description = $element->description;
            $elementobj->type = $element->type;
            $elementobj->course = $element->course;
        }
    }
    else{
        if ($type == ''){
            $elementobj = new trackerelement($tracker);
            $elementobj->setoptionsfromdb();
        }
        else{
            include_once('classes/trackercategorytype/' . $type . '/'.$type.'.class.php');
            $constructorfunction = "{$type}element";
            $elementobj = new $constructorfunction($tracker);
        }
    }   
    return $elementobj;
}

/**
* get all available types which are plugins in classes/trackercategorytype
* @uses $CFG
* @return an array of known element types
*/
function tracker_getelementtypes(){
    global $CFG;
    
    $typedir = "{$CFG->dirroot}/mod/tracker/classes/trackercategorytype";
    $DIR = opendir($typedir);
    while($entry = readdir($DIR)){
        if (strpos($entry, '.') === 0) continue;
        if ($entry == 'CVS') continue;
        if (!is_dir("$typedir/$entry")) continue;
        $types[] = $entry;
    }
    return $types;
}

/**
* tells if at least one used element is a file element
* @param int $trackerid the current tracker
*/
function tracker_requiresfile($trackerid){
    global $CFG;

    $sql = "
        SELECT 
            COUNT(*)
        FROM 
            {$CFG->prefix}tracker_element e,
            {$CFG->prefix}tracker_elementused eu
        WHERE 
            eu.elementid = e.id AND 
            eu.trackerid = {$trackerid} AND
            e.type = 'file'
    ";
    $count = count_records_sql($sql);
    
    return $count;
}

/**
* loads elements in a reference
* @param int $trackerid the current tracker
* @param reference a reference to an array of used elements
*/
function tracker_loadelementsused(&$tracker, &$used){
    global $CFG;

    $sql = "
        SELECT 
            e.*,
            eu.id AS usedid,
            eu.sortorder, 
            eu.trackerid, 
            eu.canbemodifiedby, 
            eu.active
        FROM 
            {$CFG->prefix}tracker_element e,
            {$CFG->prefix}tracker_elementused eu
        WHERE 
            eu.elementid = e.id AND 
            eu.trackerid = {$tracker->id} 
        ORDER BY 
            eu.sortorder ASC
    ";
    $elements = get_records_sql($sql);
    
    $used = array();
    if (!empty($elements)){
        foreach ($elements as $element){
            if ($element->type == ''){
                $used[$element->id] = new trackerelement($tracker, $element->id);
                $used[$element->id]->setoptionsfromdb();
            } else {
                include_once($CFG->dirroot.'/mod/tracker/classes/trackercategorytype/' . $element->type . '/'.$element->type.'.class.php');
                $constructorfunction = "{$element->type}element";
                $used[$element->id] = new $constructorfunction($tracker, $element->id);
            }
            $used[$element->id]->usedid = $element->usedid;
            $used[$element->id]->name = $element->name;
            $used[$element->id]->description = $element->description;
            $used[$element->id]->type = $element->type;
            $used[$element->id]->course = $element->course;
            $used[$element->id]->sortorder = $element->sortorder;                       
        }
    }
}   

/**
* quite the same as above, but not loading objects, and
* mapping hash keys by "name"
* @param int $trackerid
*
*/
function tracker_getelementsused_by_name(&$tracker){
    global $CFG;

    $sql = "
        SELECT 
            e.name,
            e.description,
            e.type,
            eu.id AS usedid,
            eu.sortorder, 
            eu.trackerid, 
            eu.canbemodifiedby, 
            eu.active
        FROM 
            {$CFG->prefix}tracker_element e,
            {$CFG->prefix}tracker_elementused eu
        WHERE 
            eu.elementid = e.id AND 
            eu.trackerid = {$tracker->id}
        ORDER BY 
            eu.sortorder ASC
    ";
    if (!$usedelements = get_records_sql($sql)){
        return array();
    }
    return $usedelements;
}

/**
* checks if an element is used somewhere in the tracker. It must be in used list
* @param int $trackerid the current tracker
* @param int $elementid the element
* @return boolean
*/
function tracker_iselementused($trackerid, $elementid){
    $inusedelements = count_records_select('tracker_elementused', 'elementid = ' . $elementid . ' AND trackerid = ' . $trackerid);  
    return $inusedelements;
}

/**
* print additional user defined elements in several contexts
* @param int $trackerid the current tracker
* @param array $fields the array of fields to be printed
*/
function tracker_printelements(&$tracker, $fields=null, $dest=false){
    tracker_loadelementsused($tracker, $used);
    
    if (!empty($used)){
        if (!empty($fields)){
            foreach ($used as $element){
                if (isset($fields[$element->id])){
                    foreach($fields[$element->id] as $value){
                        $element->value = $value;
                    }
                }
            }
        }
    
        foreach ($used as $element){
            echo '<tr>';
            echo '<td align="right" valign="top">';
            echo '<b>' . format_string($element->description) . ':</b>';
            echo '</td>';
            
            echo '<td align="left" colspan="3">';
            if ($dest == 'search'){
                $element->viewsearch();
            }
            elseif ($dest == 'query'){
                $element->viewquery();
            }
            else{
                $element->view(true);
            }
            echo '</td>';
            echo '</tr>';
        }
    }
}


/// Search engine 

/**
* constructs an adequate search query, based on both standard and user defined 
* fields. 
* @param int $trackerid
* @param array $fields
* @return an object where both the query for counting and the query for getting results are
* embedded. 
*/
function tracker_constructsearchqueries($trackerid, $fields, $own = false){
    global $CFG, $USER;

    $keys = array_keys($fields);

    //Check to see if we are search using elements as a parameter.  
    //If so, we need to include the table tracker_issueattribute in the search query
    $elementssearch = false;
    foreach ($keys as $key){
        if (is_numeric($key)){
            $elementssearch = true;
        }
    }
    $elementsSearchClause = ($elementssearch) ? " {$CFG->prefix}tracker_issueattribute AS ia, " : '' ;

    $elementsSearchConstraint = '';
    foreach ($keys as $key){
        if ($key == 'id'){
            $elementsSearchConstraint .= ' AND  (';
            foreach ($fields[$key] as $idtoken){
                $elementsSearchConstraint .= (empty($idquery)) ? 'i.id =' . $idtoken : ' OR i.id = ' . $idtoken ;
            }
            $elementsSearchConstraint .= ')';
        }

        if ($key == 'datereported' && array_key_exists('checkdate', $fields) ){
            $datebegin = $fields[$key][0];
            $dateend = $datebegin + 86400;
            $elementsSearchConstraint .= " AND i.datereported > {$datebegin} AND i.datereported < {$dateend} ";
        }

        if ($key == 'description'){
            $tokens = explode(' ', $fields[$key][0], ' ');
            foreach ($tokens as $token){
                $elementsSearchConstraint .= " AND i.description LIKE '%{$descriptiontoken}%' ";
            }
        }

        if ($key == 'reportedby'){
            $elementsSearchConstraint .= ' AND i.reportedby = ' . $fields[$key][0];
        }

        if ($key == 'summary'){
            $summarytokens = explode(' ', $fields[$key][0]);
            foreach ($summarytokens as $summarytoken){
                $elementsSearchConstraint .= " AND i.summary LIKE '%{$summarytoken}%'";
            }
        }

        if (is_numeric($key)){
            foreach($fields[$key] as $value){
                $elementsSearchConstraint .= ' AND i.id IN (SELECT issue FROM ' . $CFG->prefix . 'tracker_issueattribute WHERE elementdefinition=' . $key . ' AND elementitemid=' . $value . ')';
            }
        }
    }
    
    if ($own == false){
    
        $sql->search = "
            SELECT DISTINCT 
                i.id, 
                i.trackerid, 
                i.summary, 
                i.datereported, 
                i.reportedby, 
                i.assignedto, 
                i.status,
                COUNT(cc.userid) AS watches,
                u.firstname, 
                u.lastname
            FROM 
                {$CFG->prefix}user AS u, 
                $elementsSearchClause
                {$CFG->prefix}tracker_issue i
            LEFT JOIN
                {$CFG->prefix}tracker_issuecc cc
            ON
                cc.issueid = i.id           
            WHERE 
                i.trackerid = {$trackerid} AND 
                i.reportedby = u.id $elementsSearchConstraint
            GROUP BY
                i.id, 
                i.trackerid, 
                i.summary, 
                i.datereported, 
                i.reportedby, 
                i.assignedto, 
                i.status, 
                u.firstname,
                u.lastname
        ";
    
        $sql->count = "
            SELECT COUNT(DISTINCT 
                (i.id)) as reccount
            FROM 
                {$CFG->prefix}tracker_issue i
                $elementsSearchClause
            WHERE 
                i.trackerid = {$trackerid} 
                $elementsSearchConstraint
        ";
    } else {
        $sql->search = "
            SELECT DISTINCT 
                i.id, i.trackerid, i.summary, i.datereported, i.reportedby, i.assignedto, i.status,
                COUNT(cc.userid) AS watches
            FROM 
                $elementsSearchClause
                {$CFG->prefix}tracker_issue i
            LEFT JOIN
                {$CFG->prefix}tracker_issuecc cc
            ON
                cc.issueid = i.id           
            WHERE 
                i.trackerid = {$trackerid} AND 
                i.reportedby = $USER->id 
                $elementsSearchConstraint
            GROUP BY
                i.id, i.trackerid, i.summary, i.datereported, i.reportedby, i.assignedto, i.status
        ";
    
        $sql->count = "
            SELECT COUNT(DISTINCT 
                (i.id)) as reccount
            FROM 
                {$CFG->prefix}tracker_issue i
                $elementsSearchClause
            WHERE 
                i.trackerid = {$trackerid} AND
                i.reportedby = $USER->id
                $elementsSearchConstraint
        ";
    }
    return $sql;    
}

/**
* analyses the POST parameters to extract values of additional elements
* @return an array of field descriptions
*/
function tracker_extractsearchparametersfrompost(){
    $count = 0;
    $fields = array();
    $issuenumber = optional_param('issueid', '', PARAM_INT);
    if (!empty ($issuenumber)){
        $issuenumberarray = explode(',', $issuenumber);
        foreach ($issuenumberarray as $issueid){
            if (is_numeric($issueid)){
                $fields['id'][] = $issueid;
            }
            else{
                error ('Only numbers (or a list of numbers seperated by a comma (",") allowed in the issue number field', 'view.php?id=' . $this->tracker_getcoursemodule() . '&what=search');
            }
        }
    }
    else{
        $checkdate = optional_param('checkdate', 0, PARAM_INT);
        if ($checkdate){
            $month = optional_param('month', '', PARAM_INT);
            $day = optional_param('day', '', PARAM_INT);
            $year = optional_param('year', '', PARAM_INT);
        
            if (!empty($month) && !empty($day) && !empty($year)){
                $datereported = make_timestamp($year, $month, $day);
                $fields['datereported'][] = $datereported;
            }
        }
        
        $description = optional_param('description', '', PARAM_CLEANHTML);
        if (!empty($description)){  
            $fields['description'][] = stripslashes($description);
        }
        
        $reportedby = optional_param('reportedby', '', PARAM_INT);
        if (!empty($reportedby)){   
            $fields['reportedby'][] = $reportedby;
        }
        
        $summary = optional_param('summary', '', PARAM_TEXT);
        if (!empty($summary)){  
            $fields['summary'][] = $summary;
        }
                                
        $keys = array_keys($_POST);                         // get the key value of all the fields submitted
        $elementkeys = preg_grep('/element./' , $keys);     // filter out only the element keys
        
        foreach ($elementkeys as $elementkey){
            preg_match('/element(.*)$/', $elementkey, $elementid);
            if (!empty($_POST[$elementkey])){
                if (is_array($_POST[$elementkey])){
                    foreach ($_POST[$elementkey] as $elementvalue){
                        $fields[$elementid[1]][] = $elementvalue;
                    }
                }
                else{
                    $fields[$elementid[1]][] = $_POST[$elementkey];
                }
            }
        }
    }
    return $fields;
}

/**
* given a query object, and a description of additional fields, stores 
* all the query description to database.  
* @uses $USER
* @param object $query
* @param array $fields
* @return the inserted or updated queryid
*/
function tracker_savesearchparameterstodb($query, $fields){
    global $USER;
        
    $query->userid = $USER->id;
    $query->published = 0;
    $query->fieldnames = '';
    $query->fieldvalues = '';
    
    if (!empty($fields)){
        $keys = array_keys($fields);
        if (!empty($keys)){
            foreach ($keys as $key){
                foreach($fields[$key] as $value){
                    if (empty($query->fieldnames)){
                        $query->fieldnames = $key;
                        $query->fieldvalues = $value;
                    }
                    else{
                        $query->fieldnames = $query->fieldnames . ', ' . $key;
                        $query->fieldvalues = $query->fieldvalues . ', '  . $value;
                    }
                }
            }       
        }
    }
    
    if (!isset($query->id)) {           //if not given a $queryid, then insert record
        $queryid = insert_record('tracker_query', $query, true);
    }
    else {                      //otherwise, update record
        $queryid = update_record('tracker_query', $query, true);
    }   
    return $queryid;        
}

/**
* prints the human understandable search query form
* @param array $fields
*/
function tracker_printsearchfields($fields){
    foreach($fields as $key => $value){
        switch(trim($key)){
            case 'datereported' :
                if (!function_exists('trk_userdate')){
                    function trk_userdate(&$a){
                        $a = userdate($a);
                        $a = preg_replace("/, \\d\\d:\\d\\d/", '', $a);
                    }
                }
                array_walk($value, 'trk_userdate');
                $strs[] = get_string($key, 'tracker') . ' '.get_string('IN', 'tracker')." ('".implode("','", $value) . "')";
                break;
            case 'summary' :
                $strs[] =  "('".implode("','", $value) ."') ".get_string('IN', 'tracker').' '.get_string('summary', 'tracker');
                break;
            case 'description' :
                $strs[] =  "('".implode("','", $value) ."') ".get_string('IN', 'tracker').' '.get_string('description');
                break;
            case 'reportedby' :
                $users = get_records_list('user', 'id', implode(',',$value), 'lastname', 'id,firstname,lastname');
                $reporters = array();
                if($users){
                    foreach($users as $user){
                        $reporters[] = fullname($user);
                    }
                }
                $reporterlist = implode ("', '", $reporters);
                $strs[] = get_string('reportedby', 'tracker').' '.get_string('IN', 'tracker')." ('".$reporterlist."')";
                break;
            default : 
                $strs[] = get_string($key, 'tracker') . ' '.get_string('IN', 'tracker')." ('".implode("','", $value) . "')";
        }
    }
    return implode (' '.get_string('AND', 'tracker').' ', $strs);
}

/**
*
*
*/
function tracker_extractsearchparametersfromdb($queryid=null){
    if (!$queryid)
        $queryid = optional_param('queryid', '', PARAM_INT);
    $query_record = get_record('tracker_query', 'id', $queryid);
    $fields = null;
    
    if (!empty($query_record)){
        $fieldnames = explode(',', $query_record->fieldnames);
        $fieldvalues = explode(',', $query_record->fieldvalues);
        
        $count = 0;
        if (!empty($fieldnames)){
            foreach ($fieldnames as $fieldname){
                $fields[trim($fieldname)][] = trim($fieldvalues[$count]);
                $count++;
            }
        }
    }
    else{
        error ("Invalid query id: " . $queryid);
    }
    
    return $fields;
}

/**
* set a cookie with search information
* @return boolean
*/
function tracker_setsearchcookies($fields){
    $success = true;
    if (is_array($fields)){
        $keys = array_keys($fields);
        
        foreach ($keys as $key){
            $cookie = '';
            foreach ($fields[$key] as $value){
                if (empty($cookie)){
                    $cookie = $cookie . $value;
                }       
                else{
                    $cookie = $cookie . ', ' . $value;
                }
            }
            
            $result = setcookie("moodle_tracker_search_" . $key, $cookie);          
            $success = $success && $result;
        }
    }
    else{
        $success = false;
    }
    return $success;    
}

/**
* get last search parameters from use cookie
* @uses $_COOKIE
* @return an array of field desriptions
*/
function tracker_extractsearchcookies(){
    $keys = array_keys($_COOKIE);                                           // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_tracker_search./' , $keys);            // filter all search cookies
    
    $fields = null;
    foreach ($cookiekeys as $cookiekey){
        preg_match('/moodle_tracker_search_(.*)$/', $cookiekey, $fieldname);
        $fields[$fieldname[1]] = explode(', ', $_COOKIE[$cookiekey]);
    }
    return $fields;
}


/**
* clear the current search
* @uses _COOKIE
* @return boolean true if succeeded
*/
function tracker_clearsearchcookies(){
    $success = true;
    $keys = array_keys($_COOKIE);                                           // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_tracker_search./' , $keys);            // filter all search cookies
    
    foreach ($cookiekeys as $cookiekey){
        $result = setcookie($cookiekey, '');
        $success = $success && $result;
    }   

    return $success;        
}

/**
* settles data for memoising current search context
* @uses $CFG
* @param int $trackerid
* @param int $cmid
*/
function tracker_searchforissues(&$tracker, $cmid){
    global $CFG;
    
    tracker_clearsearchcookies($tracker->id);
    $fields = tracker_extractsearchparametersfrompost($tracker->id);
    $success = tracker_setsearchcookies($fields);
    
    if ($success){
        if ($tracker->supportmode == 'bugtracker')
            redirect ("view.php?id={$cmid}&amp;view=view&amp;page=browse");
        else 
            redirect ("view.php?id={$cmid}&amp;view=view&amp;page=mytickets");
    }
    else{
        error ("Failed to set cookie: " . $cookie . "<br>");
    }
}

/**
* get how many issues in this tracker
* @uses $CFG
* @param int $trackerid
* @param int $status if status is positive or null, filters by status
*/
function tracker_getnumissuesreported($trackerid, $status='*', $reporterid = '*', $resolverid='*', $developerids='', $adminid='*'){ 
    global $CFG;
    
    $statusClause = ($status !== '*') ? " AND i.status = $status " : '' ;
    $reporterClause = ($reporterid != '*') ? " AND i.reportedby = $reporterid " : '' ;
    $resolverClause = ($resolverid != '*') ? " AND io.userid = $resolverid " : '' ;
    $developerClause = ($developerids != '') ? " AND io.userid IN ($developerids) " : '' ;
    $adminClause = ($adminid != '*') ? " AND io.bywhomid IN ($adminid) " : '' ;

    $sql = "
        SELECT
            COUNT(DISTINCT(i.id))
        FROM
            {$CFG->prefix}tracker_issue i
        LEFT JOIN
            {$CFG->prefix}tracker_issueownership io
        ON 
            i.id = io.issueid
        WHERE
            i.trackerid = {$trackerid}
            $statusClause
            $reporterClause
            $developerClause
            $resolverClause
            $adminClause
    ";
    return count_records_sql($sql); 
}

//// User related 

/**
* get available managers/tracker administrators
* @param object $context
*/
function tracker_getadministrators($context){
    return get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,picture,email', 'lastname');
}

/**
* get available resolvers
* @param object $context
*/
function tracker_getresolvers($context){
    return get_users_by_capability($context, 'mod/tracker:resolve', 'u.id,firstname,lastname,picture,email', 'lastname');
}

/**
* get actual reporters from records
* @uses $CFG
* @param int $trackerid
*/
function tracker_getreporters($trackerid){
    global $CFG;
    
    $sql = "
        SELECT
            DISTINCT(reportedby) AS id,
            u.firstname,
            u.lastname
        FROM
            {$CFG->prefix}tracker_issue i,
            {$CFG->prefix}user u
        WHERE
            i.reportedby = u.id AND
            i.trackerid = $trackerid
    ";
    return get_records_sql($sql);
}

/**
*
*
*/
function tracker_getdevelopers($context){
    return get_users_by_capability($context, 'mod/tracker:develop', 'u.id,firstname,lastname,picture,email', 'lastname');
}

/**
* get the assignees of a manager
*
*/
function tracker_getassignees($userid){
    global $CFG;
    
    $sql = "
        SELECT DISTINCT 
            u.id, 
            u.firstname, 
            u.lastname, 
            u.picture, 
            u.email, 
            u.emailstop, 
            u.maildisplay,
            COUNT(i.id) as issues
        FROM
            {$CFG->prefix}tracker_issue i,
            {$CFG->prefix}user u
        WHERE
            i.assignedto = u.id AND
            i.bywhomid = {$userid}
        GROUP BY
            u.id, 
            u.firstname, 
            u.lastname, 
            u.picture, 
            u.email, 
            u.emailstop, 
            u.maildisplay
    ";
    return get_records_sql($sql);
}

/**
* submits an issue in the current tracker
* @uses $CFG
* @param int $trackerid the current tracker
*/
function tracker_submitanissue(&$tracker){
    global $CFG;
    
    $issue->datereported = required_param('datereported', PARAM_INT);
    $issue->summary = required_param('summary', PARAM_TEXT);
    $issue->description = addslashes(required_param('description', PARAM_CLEANHTML));
    $issue->format = addslashes(required_param('format', PARAM_CLEANHTML));
    $issue->assignedto = 0;
    $issue->bywhomid = 0;
    $issue->trackerid = $tracker->id;
    $issue->status = POSTED;
    $issue->reportedby = required_param('reportedby', PARAM_INT);

    // fetch max actual priority
    $maxpriority = get_field_select('tracker_issue', 'MAX(resolutionpriority)', " trackerid = {$tracker->id} GROUP BY trackerid ");
    $issue->resolutionpriority = $maxpriority + 1;

    $issue->id = insert_record('tracker_issue', $issue, true);
    
    if ($issue->id){
        tracker_recordelements($issue);
        // if not CCed, the assignee should be
        tracker_register_cc($tracker, $issue, $issue->reportedby);
        return $issue;
    } else {
         error("Could not submit issue");
    }
}

/**
* fetches all issues a user is assigned to as resolver
* @uses $USER
* @param int $trackerid the current tracker
* @param int $userid an eventual userid
*/
function tracker_getownedissuesforresolve($trackerid, $userid = null){
    global $USER;
    
    if (empty($userid)){
        $userid = $USER->id;
    }
    return get_records_select('tracker_issue', "trackerid = {$trackerid} AND assignedto = {$userid} ");
}

/**
* stores in database the element values
* @uses $CFG
* @param object $issue
*/
function tracker_recordelements(&$issue){
    global $CFG, $COURSE;
    
    $keys = array_keys($_POST);                 // get the key value of all the fields submitted
    $keys = preg_grep('/element./' , $keys);    // filter out only the element keys

    $filekeys = array_keys($_FILES);                 // get the key value of all the fields submitted
    $filekeys = preg_grep('/element./' , $filekeys);    // filter out only the element keys    

    $keys = array_merge($keys, $filekeys);
    
    foreach ($keys as $key){
        preg_match('/element(.*)$/', $key, $elementid);
        
        $elementname = $elementid[1];
        
        $sql = "
            SELECT 
              e.id as elementid,
              e.type as type
            FROM
                {$CFG->prefix}tracker_elementused eu,
                {$CFG->prefix}tracker_element e
            WHERE
                eu.elementid = e.id AND
                e.name = '{$elementname}' AND
                eu.trackerid = {$issue->trackerid} 
        ";
        $attribute = get_record_sql($sql);
        $attribute->timemodified = $issue->datereported;
        $values = optional_param($key, '', PARAM_CLEANHTML);
        $attribute->issueid = $issue->id;
        $attribute->trackerid = $issue->trackerid;
        
        /// For those elements where more than one option can be selected
        if (is_array($values)){
            foreach ($values as $value){
                $attribute->elementitemid = $value;
                $attributeid = insert_record('tracker_issueattribute', $attribute);
    
                if (!$attributeid){
                    error("Could not submit issue(s) attribute(s): issue:{$issue->id} issueid:$elementid[1] elementitemid:$attribute->elementitemid");
                }
            }
        } else {  //For the rest of the elements that can only support one answer
            if ($attribute->type != 'file'){
                require_once($CFG->libdir.'/uploadlib.php');
                $attribute->elementitemid = $values;
                $attributeid = insert_record('tracker_issueattribute', $attribute);    
            } else {
                $uploader = new upload_manager($key, false, false, $COURSE->id, true, 0, true);
                $uploader->preprocess_files();
                $newfilename = $uploader->get_new_filename();
                $encodedfilename = '';
                if (!empty($newfilename)){
                    $encodedfilename = md5(time()).'_'.$newfilename;
                    $storebase = "{$COURSE->id}/moddata/tracker/{$issue->trackerid}/{$issue->id}";
                    if (!filesystem_is_dir($storebase)){
                        filesystem_create_dir($storebase, FS_RECURSIVE);
                    }
                    $uploader->save_files($storebase);
                    filesystem_move_file($storebase.'/'.$newfilename, $storebase.'/'.$encodedfilename);
                    $attribute->elementitemid = $encodedfilename;
                    $attributeid = insert_record('tracker_issueattribute', $attribute);    
                }                
            }

            if (empty($attributeid)){
                error("Could not submit issue attribute: issue:{$issue->id} elementid:$elementid[1] elementitemid:$attribute->elementitemid");
            }
        }   
    }           
}

/**
* clears element recordings for an issue
* @TODO check if it is really used
* @param int $issueid the issue
*/
function tracker_clearelements($issueid){
    global $CFG, $COURSE;

    if (!$issue = get_record('tracker_issue', 'id', "$issueid")){
        return;
    }

    // find all files elements to protect
    
   $sql = "
            SELECT
                e.id,
                e.type
            FROM
                {$CFG->prefix}tracker_element e,
                {$CFG->prefix}tracker_elementused eu
            WHERE
                e.id = eu.elementid AND
                e.type = 'file' AND
                eu.trackerid = {$issue->trackerid}
    ";

    $nofileclause = '';
    if($fileelements = get_records_sql($sql)){
        $fileelementlist = implode("','", array_keys($fileelements));
        $nofileclause = " AND elementid NOT IN ('$fileelementlist') ";
    }
    
    if (!delete_records_select('tracker_issueattribute', "issueid = $issueid $nofileclause")){
        error("Could not clear elements for issue $issueid");
    }

    $storebase = "{$COURSE->id}/moddata/tracker/{$issue->trackerid}/{$issue->id}";

    // remove all deleted file attachements
    $keys = array_keys($_POST);
    $deletefilekeys = preg_grep('/deleteelement./' , $keys);    // filter out only the deleteelement keys    

    if (!empty($deletefilekeys)){
        foreach($deletefilekeys as $deletedkey){
            if (preg_match("/deleteelement(.*)$/", $deletedkey, $matches)){
                $elementname = $matches[1];
                $element = get_record('tracker_element', 'name', $elementname);
                if ($elementitem = get_record('tracker_issueattribute', 'elementid', $element->id, 'issueid', $issueid)){
                    if (!empty($elementitem->elementitemid)){
                        filesystem_delete_file($storebase.'/'.$elementitem->elementitemid);
                    }
                    delete_records('tracker_issueattribute', 'id', $elementitem->id);
                }
            }
        }
    }

    // remove all reloaded files
    $keys = array_keys($_FILES);
    $reloadedfilekeys = preg_grep('/element./' , $keys);    // filter out only the reloaded element keys    
    
    if (!empty($reloadedfilekeys)){
        foreach($reloadedfilekeys as $reloadedkey){
            if (preg_match("/element(.*)$/", $reloadedkey, $matches)){
                $elementname = $matches[1];
                $element = get_record('tracker_element', 'name', $elementname);
                if ($elementitem = get_record('tracker_issueattribute', 'elementid', $element->id, 'issueid', $issueid)){
                    if (!empty($elementitem->elementitemid)){
                        // echo "removing ".$storebase.'/'.$elementitem->elementitemid;
                        filesystem_delete_file($storebase.'/'.$elementitem->elementitemid);
                    }
                    delete_records('tracker_issueattribute', 'id', $elementitem->id);
                }
            }
        }
    }
}

/**
* adds an error css marker in case of matching error
* @param array $errors the current error set
* @param string $errorkey 
*/
if (!function_exists('print_error_class')){
    function print_error_class($errors, $errorkeylist){
        if ($errors){
            foreach($errors as $anError){
                if ($anError->on == '') continue;
                if (preg_match("/\\b{$anError->on}\\b/" ,$errorkeylist)){
                    echo " class=\"formerror\" ";
                    return;
                }
            }        
        }
    }
}

/**
* registers a user as cced for an issue in a tracker
* @param reference $tracker the current tracker
* @param reference $issue the issue to watch
* @param int $userid the cced user's ID
*/
function tracker_register_cc(&$tracker, &$issue, $userid){

    if ($userid && !get_record('tracker_issuecc', 'trackerid', $tracker->id, 'issueid', $issue->id, 'userid', $userid)){
        // Add new the assignee as new CC !!
        // we do not discard the old one as he may be still concerned
        $eventmask = 127;
        if ($userprefs = get_record('tracker_preferences', 'trackerid', $tracker->id, 'userid', $userid, 'name', 'eventmask')){
            $eventmask = $userprefs->value;
        }
        
        $cc->trackerid = $tracker->id;
        $cc->issueid = $issue->id;
        $cc->userid = $userid;
        $cc->events = $eventmask;
        insert_record('tracker_issuecc', $cc);
    }    

}

/**
* a local version of the print user command that fits  better to the tracker situation
* @uses $COURSE
* @uses $CFG
* @param object $user the user record
*/
function tracker_print_user($user){
    global $COURSE, $CFG;

    if ($user){
        print_user_picture ($user->id, $COURSE->id, !empty($user->picture));
        if ($CFG->messaging){
            echo "<a href=\"$CFG->wwwroot/user/view.php?id={$user->id}&amp;course={$COURSE->id}\">".fullname($user)."</a> <a href=\"\" onclick=\"this.target='message'; return openpopup('/message/discussion.php?id={$user->id}', 'message', 'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500', 0);\" ><img src=\"$CFG->pixpath/t/message.gif\"></a>";
        } elseif (!$user->emailstop && $user->maildisplay){
            echo "<a href=\"$CFG->wwwroot/user/view.php?id={$user->id}&amp;course={$COURSE->id}\">".fullname($user)."</a> <a href=\"mailto:{$user->email}\"><img src=\"$CFG->pixpath/t/mail.gif\"></a>";
        } else {
            echo fullname($user);
        }
    }
}

/**
* prints comments for the given issue
* @uses $CFG
* @param int $issueid
*/
function tracker_printcomments($issueid){
    global $CFG;
    
    $comments = get_records('tracker_issuecomment', 'issueid', $issueid, 'datecreated');
    if ($comments){
        foreach ($comments as $comment){
            $user = get_record('user', 'id', $comment->userid);
            echo '<tr>';
            echo '<td valign="top" class="commenter" width="30%">';
            tracker_print_user($user);
            echo '<br/>';
            echo '<span class="timelabel">'.userdate($comment->datecreated).'</span>';
            echo '</td>';
            echo '<td colspan="3" valign="top" align="left" class="comment">';
            echo $comment->comment;
            echo '</td>';
            echo '</tr>';
        }
    }
}

/**
* get list of possible parents. Note that none can be in the subdependancies.
* @uses $CFG
* @param int $trackerid
* @param int $issueid
*/
function tracker_getpotentialdependancies($trackerid, $issueid){
    global $CFG;
    
    $subtreelist = tracker_get_subtree_list($trackerid, $issueid);
    $subtreeClause = (!empty($subtreelist)) ? "AND i.id NOT IN ({$subtreelist}) " : '' ;

    $sql = "
       SELECT
          i.id,
          id.parentid,
          id.childid as isparent,
          summary
       FROM
          {$CFG->prefix}tracker_issue i
       LEFT JOIN
          {$CFG->prefix}tracker_issuedependancy id
       ON
          i.id = id.parentid
       WHERE
          i.trackerid = {$trackerid} AND
          ((id.childid IS NULL) OR (id.childid = $issueid)) AND
          ((id.parentid != $issueid) OR (id.parentid IS NULL)) AND
          i.id != $issueid 
          $subtreeClause
       GROUP BY 
          i.id, 
          id.parentid, 
          id.childid, 
          summary
    ";
    // echo $sql;
    return get_records_sql($sql);
}

/**
* get the full list of dependencies in a tree // revamped from techproject/treelib.php
* @param table the table-tree
* @param id the node from where to start of
* @return a comma separated list of nodes
*/
function tracker_get_subtree_list($trackerid, $id){
    $res = get_records_menu('tracker_issuedependancy', 'parentid', $id, '', 'id,childid');
    $ids = array();
    if (is_array($res)){
        foreach(array_values($res) as $aSub){
            $ids[] = $aSub;
            $subs = tracker_get_subtree_list($trackerid, $aSub);
            if (!empty($subs)) $ids[] = $subs;
        }
    }
    return(implode(',', $ids));
}

/**
* prints all childs of an issue treeshaped
* @uses $CFG
* @uses $STATUSCODES
* @uses $STATUS KEYS
* @param object $tracker 
* @param int $issueid 
* @param boolean $return if true, returns the HTML, prints it to output elsewhere
* @param int $indent the indent value
* @return the HTML
*/
function tracker_printchilds(&$tracker, $issueid, $return=false, $indent=''){
    global $CFG, $STATUSCODES, $STATUSKEYS;
    
    $str = '';
    $sql = "
       SELECT
          childid,
          summary,
          status
       FROM
          {$CFG->prefix}tracker_issuedependancy id,
          {$CFG->prefix}tracker_issue i
       WHERE
          i.id = id.childid AND
          id.parentid = {$issueid} AND
          i.trackerid = {$tracker->id}
    ";
    $res = get_records_sql($sql);
    if ($res){
        foreach($res as $aSub){
            $str .= "<span style=\"position : relative; left : {$indent}px\"><a href=\"view.php?a={$tracker->id}&amp;what=viewanissue&amp;issueid={$aSub->childid}\">".$tracker->ticketprefix.$aSub->childid.' - '.format_string($aSub->summary)."</a>";
            $str .= "&nbsp;<span class=\"status_".$STATUSCODES[$aSub->status]."\">".$STATUSKEYS[$aSub->status]."</span></span><br/>\n";
            $indent = $indent + 20;
            $str .= tracker_printchilds($tracker, $aSub->childid, true, $indent);
            $indent = $indent - 20;
        }
    }
    if ($return) return $str;
    echo $str;
}


/**
* prints all parents of an issue tree shaped
* @uses $CFG
* @uses $STATUSCODES
* @uses STATUSKEYS
* @param object $tracker 
* @param int $issueid 
* @return the HTML
*/
function tracker_printparents(&$tracker, $issueid, $return=false, $indent=''){
    global $CFG, $STATUSCODES, $STATUSKEYS;
    
    $str = '';
    $sql = "
       SELECT
          parentid,
          summary,
          status
       FROM
          {$CFG->prefix}tracker_issuedependancy id,
          {$CFG->prefix}tracker_issue i
       WHERE
          i.id = id.parentid AND
          id.childid = {$issueid} AND
          i.trackerid = {$tracker->id}
    ";
    $res = get_records_sql($sql);
    if ($res){
        foreach($res as $aSub){
            $indent = $indent - 20;
            $str .= tracker_printparents($tracker, $aSub->parentid, true, $indent);
            $indent = $indent + 20;
            $str .= "<span style=\"position : relative; left : {$indent}px\"><a href=\"view.php?a={$tracker->id}&amp;what=viewanissue&amp;issueid={$aSub->parentid}\">".$tracker->ticketprefix.$aSub->parentid.' - '.format_string($aSub->summary)."</a>";
            $str .= "&nbsp;<span class=\"status_".$STATUSCODES[$aSub->status]."\">".$STATUSKEYS[$aSub->status]."</span></span><br/>\n";
        }
    }
    if ($return) return $str;
    echo $str;
}

/**
* return watch list for a user
* @uses $CFG
* @param int trackerid the current tracker
* @param int userid the user
*/
function tracker_getwatches($trackerid, $userid){
    global $CFG;
    
    $sql = "
        SELECT
            w.*,
            i.summary
        FROM
            {$CFG->prefix}tracker_issuecc w,
            {$CFG->prefix}tracker_issue i
        WHERE
            w.issueid = i.id AND
            i.trackerid = {$trackerid} AND
            w.userid = {$userid}            
    ";
    
    $watches = get_records_sql($sql);
    if ($watches){
        foreach($watches as $awatch){
            $people = count_records('tracker_issuecc', 'issueid', $awatch->issueid);
            $watches[$awatch->id]->people = $people;
        }
    }
    
    return $watches;
}

/**
* sends required notifications when requiring raising priority
* @uses $COURSE
* @param object $issue
* @param object $cm
* @param object $tracker
*/
function tracker_notify_raiserequest($issue, &$cm, $reason, $urgent, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER;

    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = get_record('tracker', 'id', $issue->trackerid);
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    $by = get_record('user', 'id', $issue->reportedby);
    $urgentrequest = '';
    if ($urgent){
        $urgentrequest = get_string('urgentsignal', 'tracker');
    }

    $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                  'COURSENAME' => format_string($COURSE->fullname), 
                  'TRACKERNAME' => format_string($tracker->name), 
                  'ISSUE' => $tracker->ticketprefix.$issue->id, 
                  'SUMMARY' => format_string($issue->summary), 
                  'REASON' => stripslashes($reason), 
                  'URGENT' => $urgentrequest, 
                  'BY' => fullname($by),
                  'REQUESTEDBY' => fullname($USER),
                  'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                  );

    include_once($CFG->dirroot."/mod/tracker/mailtemplatelib.php");

    if (!empty($managers)){
        foreach($managers as $manager){
            $notification = compile_mail_template('raiserequest', $vars, 'tracker', $manager->lang);
            $notification_html = compile_mail_template('raiserequest_html', $vars, 'tracker', $manager->lang);
            if ($CFG->debugsmtp) echo "Sending Raise Request Mail Notification to " . fullname($manager) . '<br/>'.$notification_html;
            email_to_user($manager, $USER, get_string('raiserequestcaption', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }

    $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    $admins = get_users_by_capability($systemcontext, 'moodle/site:doanything', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    if (!empty($admins)){
        foreach($admins as $admin){
            $notification = compile_mail_template('raiserequest', $vars, 'tracker', $admin->lang);
            $notification_html = compile_mail_template('raiserequest_html', $vars, 'tracker', $admin->lang);
            if ($CFG->debugsmtp) echo "Sending Raise Request Mail Notification to " . fullname($admin) . '<br/>'.$notification_html;
            email_to_user($admin, $USER, get_string('urgentraiserequestcaption', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }

}

/**
* sends required notifications by the watchers when first submit
* @uses $COURSE
* @param object $issue
* @param object $cm
* @param object $tracker
*/
function tracker_notify_submission($issue, &$cm, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER;

    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = get_record('tracker', 'id', $issue->trackerid);
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $managers = get_users_by_capability($context, 'mod/tracker:manage', 'u.id,firstname,lastname,lang,email,emailstop,mailformat,mnethostid', 'lastname');

    $by = get_record('user', 'id', $issue->reportedby);
    if (!empty($managers)){
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issue->id, 
                      'SUMMARY' => format_string($issue->summary), 
                      'DESCRIPTION' => format_string(stripslashes($issue->description)), 
                      'BY' => fullname($by),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                      'CCURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;issueid={$issue->id}&amp;what=register"
                      );
        include_once($CFG->dirroot."/mod/tracker/mailtemplatelib.php");
        foreach($managers as $manager){
            $notification = compile_mail_template('submission', $vars, 'tracker', $manager->lang);
            $notification_html = compile_mail_template('submission_html', $vars, 'tracker', $manager->lang);
            if ($CFG->debugsmtp) echo "Sending Submission Mail Notification to " . fullname($manager) . '<br/>'.$notification_html;
            email_to_user($manager, $USER, get_string('submission', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }
}

/**
* sends required notifications by the watchers when first submit
* @uses $COURSE
* @param int $issueid
* @param object $tracker
*/
function tracker_notifyccs_changeownership($issueid, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER;

    $issue = get_record('tracker_issue', 'id', $issueid);
    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = get_record('tracker', 'id', $issue->trackerid);
    }

    $issueccs = get_records('tracker_issuecc', 'issueid', $issue->id);
    $assignee = get_record('user', 'id', $issue->assignedto);
    if (!empty($issueccs)){
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issue->id, 
                      'SUMMARY' => format_string($issue->summary), 
                      'ASSIGNEDTO' => fullname($assignee), 
                      'BY' => fullname($USER),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                      );
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach($issueccs as $cc){
            $ccuser = get_record('user', 'id', $cc->userid);
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
            $notification = compile_mail_template('ownershipchanged', $vars, 'tracker', $ccuser->lang);
            $notification_html = compile_mail_template('ownershipchanged_html', $vars, 'tracker', $ccuser->lang);
            if ($CFG->debugsmtp) echo "Sending Ownership Change Mail Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
            email_to_user($ccuser, $USER, get_string('submission', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
        }
    }
}

/**
* sends required notifications by the watchers when state changes
* @uses $COURSE
* @param int $issueid
* @param object $tracker
*/
function tracker_notifyccs_changestate($issueid, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER;

    $issue = get_record('tracker_issue', 'id', $issueid);
    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = get_record('tracker', 'id', $issue->trackerid);
    }
    $issueccs = get_records('tracker_issuecc', 'issueid', $issueid);

    if (!empty($issueccs)){    
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issueid, 
                      'SUMMARY' => format_string($issue->summary), 
                      'BY' => fullname($USER),
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issueid}");
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach($issueccs as $cc){
            unset($notification);
            unset($notification_html);
            $ccuser = get_record('user', 'id', $cc->userid);
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
            switch($issue->status){
                case OPEN : 
                    if($cc->events & EVENT_OPEN){
                        $vars['EVENT'] = get_string('open', 'tracker');
                        $notification = compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case RESOLVING : 
                    if($cc->events & EVENT_RESOLVING){
                        $vars['EVENT'] = get_string('resolving', 'tracker');
                        $notification = compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case WAITING : 
                    if($cc->events & EVENT_WAITING){
                        $vars['EVENT'] = get_string('waiting', 'tracker');
                        $notification = compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case RESOLVED : 
                    if($cc->events & EVENT_RESOLVED){
                        $vars['EVENT'] = get_string('resolved', 'tracker');
                        $notification = compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case ABANDONNED : 
                    if($cc->events & EVENT_ABANDONNED){
                        $vars['EVENT'] = get_string('abandonned', 'tracker');
                        $notification = compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case TRANSFERED : 
                    if($cc->events & EVENT_TRANSFERED){
                        $vars['EVENT'] = get_string('transfered', 'tracker');
                        $notification = compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                case TESTING : 
                    if($cc->events & EVENT_TESTING){
                        $vars['EVENT'] = get_string('testing', 'tracker');
                        $notification = compile_mail_template('statechanged', $vars, 'tracker', $ccuser->lang);
                        $notification_html = compile_mail_template('statechanged_html', $vars, 'tracker', $ccuser->lang);
                    }
                break;
                default:
            }
            if (!empty($notification)){
                if($CFG->debugsmtp) echo "Sending State Change Mail Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
                email_to_user($ccuser, $USER, get_string('trackereventchanged', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
            }
        }
    }
}

/**
* sends required notifications by the watchers when first submit
* @uses $COURSE
* @param int $issueid
* @param object $tracker
*/
function tracker_notifyccs_comment($issueid, $comment, $tracker = null){
    global $COURSE, $SITE, $CFG, $USER;

    $issue = get_record('tracker_issue', 'id', $issueid);
    if (empty($tracker)){ // database access optimization in case we have a tracker from somewhere else
        $tracker = get_record('tracker', 'id', $issue->trackerid);
    }

    $issueccs = get_records('tracker_issuecc', 'issueid', $issue->id);
    if (!empty($issueccs)){
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'TRACKERNAME' => format_string($tracker->name), 
                      'ISSUE' => $tracker->ticketprefix.$issue->id, 
                      'SUMMARY' => $issue->summary, 
                      'COMMENT' => format_string(stripslashes($comment)), 
                      'ISSUEURL' => $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}",
                      );
        include_once($CFG->dirroot.'/mod/tracker/mailtemplatelib.php');
        foreach($issueccs as $cc){
            $ccuser = get_record('user', 'id', $cc->userid);
            if ($cc->events & ON_COMMENT){
                $vars['CONTRIBUTOR'] = fullname($USER);
                $vars['UNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
                $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/tracker/view.php?a={$tracker->id}&amp;view=profile&amp;page=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
                $notification = compile_mail_template('addcomment', $vars, 'tracker', $ccuser->lang);
                $notification_html = compile_mail_template('addcomment_html', $vars, 'tracker', $ccuser->lang);
                if ($CFG->debugsmtp) echo "Sending Comment Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
                email_to_user($ccuser, $USER, get_string('submission', 'tracker', $SITE->shortname.':'.format_string($tracker->name)), $notification, $notification_html);
            }
        }
    }
}

/**
* loads the tracker users preferences in the $USER global.
* @uses $USER
* @param int $trackerid the current tracker
* @param int $userid the user the preferences belong to
*/
function tracker_loadpreferences($trackerid, $userid = 0){
    global $USER;
    
    if ($userid == 0) $userid = $USER->id;
    
    $preferences = get_records_select('tracker_preferences', "trackerid = $trackerid AND userid = $userid");
    if ($preferences){
        foreach($preferences as $preference){
            $USER->trackerprefs->{$preference->name} = $preference->value;
        }
    }
}

/**
* prints a transfer link follow up to an available parent record
* @uses $CFG
*
*/
function tracker_print_transfer_link(&$tracker, &$issue){
    global $CFG;
    
    if (empty($tracker->parent)) return '';
    if (is_numeric($tracker->parent)){
        if (!empty($issue->followid)){
            $href = "<a href=\"/mod/tracker/view.php?id={$tracker->parent}&view=view&page=viewanissue&issueid={$issue->followid}\">".get_string('follow', 'tracker').'</a>';
        } else {
            $href = '';
        }
    } else {
        list($parentid, $hostroot) = explode('@', $tracker->parent);
        $mnet_host = get_record('mnet_host', 'wwwroot', $hostroot);
        $remoteurl = urlencode("/mod/tracker/view.php?view=view&amp;page=viewanissue&amp;a={$parentid}&amp;issueid={$issue->id}");
        $href = "<a href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$mnet_host->id}&amp;wantsurl={$remoteurl}\">".get_string('follow', 'tracker')."</a>";
    }
    return $href;
}

/**
* displays a match status of element definition between two trackers
* @param int $trackerid the id of the local tracker
* @param object $remote a remote tracker
* @return false if no exact matching in name and type
*/
function tracker_display_elementmatch($local, $remote){

    $match = true;

    echo "<ul>";
    foreach($remote->elements as $name => $element){
        if (in_array($name, array_keys($local->elements))){
            if ($local->elements[$name]->type == $remote->elements[$name]->type){
                echo "<li>{$element->name} : {$element->description} ({$element->type})</li>";
            } else {
                echo "<li>{$element->name} : {$element->description} <span class=\"red\">({$element->type})</span></li>";
                $match = false;
            }
        } else {
            echo "<li><span class=\"red\">+{$element->name} : {$element->description} ({$element->type})</span></li>";
            $match = false;
        }
    }

    // Note that array_diff is buggy in PHP5
    foreach (array_keys($local->elements) as $localelement){
        if (!in_array($localelement, array_keys($remote->elements))){
            echo "<li><span style=\"color: blue\" class=\"blue\">-{$local->elements[$localelement]->name} : {$local->elements[$localelement]->description} ({$local->elements[$localelement]->type})</span></li>";
            $match = false;
        }
    }
    
    echo "</ul>";
    return $match;
}

/**
* prints a backlink to the issue when cascading
* @uses $SITE
* @uses $CFG
* @param object $cm the tracker course module
* @param object $issue the original ticket
*/
function tracker_add_cascade_backlink(&$cm, &$issue){
    global $SITE, $CFG;

    $vieworiginalstr = get_string('vieworiginal', 'tracker');
    $str = get_string('cascadedticket', 'tracker', $SITE->shortname);
    $str .= '<br/>';
    $str .= "<a href=\"{$CFG->wwwroot}/mod/tracker/view.php?id={$cm->id}&amp;view=view&amp;page=viewanissue&amp;issueid={$issue->id}\">{$vieworiginalstr}</a><br/>";

    return $str;    
}

/**
* reorder correctly the priority sequence and discard from the stack
* all resolved and abandonned entries
* @uses $CFG
* @param $reference $tracker
*/
function tracker_update_priority_stack(&$tracker){
    global $CFG;
    
    /// discards resolved, transferred or abandoned
    $sql = "
       UPDATE 
           {$CFG->prefix}tracker_issue
       SET
           resolutionpriority = 0
       WHERE
           trackerid = $tracker->id AND
           status IN (".RESOLVED.','.ABANDONNED.','.TRANSFERED.')';
    execute_sql($sql);

    /// fetch prioritarized by order
    $issues = get_records_select('tracker_issue', "trackerid = {$tracker->id} AND resolutionpriority != 0 ", 'resolutionpriority', 'id, resolutionpriority');
    $i = 1;
    if (!empty($issues)){
        foreach ($issues as $issue){
            $issue->resolutionpriority = $i;
            update_record('tracker_issue', $issue);
            $i++;
        }
    }
}
?>
