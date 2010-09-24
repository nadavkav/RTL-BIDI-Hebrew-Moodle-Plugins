<?php
/**
 * Return the users who have edited a page in the subwiki and the number of pages they have edited
 *
 * @param unknown_type $subwikiid
 * @param unknown_type $groupid
 * @param unknown_type $roleid
 * @return user id and pagecount
 */
function ouwiki_get_activeusers($contexts, $subwikiid, $groupid, $roleid) {
    global $CFG;
    // if not all groups
    if ($groupid != 0) {
        $result=get_records_sql("
SELECT
	  v.userid AS user, COUNT(v.id) AS pagecount  
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON p.id=v.pageid  
    INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=v.userid  
    INNER JOIN {$CFG->prefix}role_assignments ra ON v.userid=ra.userid
WHERE
    p.subwikiid=$subwikiid AND ra.roleid=$roleid AND gm.groupid=$groupid
    AND ra.contextid $contexts 
    AND v.deletedat IS NULL
GROUP BY
    v.userid
");
    // if all groups
    } else {        
        $result=get_records_sql("
SELECT
    v.userid AS user, COUNT(v.id) AS pagecount  
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON p.id=v.pageid  
    INNER JOIN {$CFG->prefix}role_assignments ra ON v.userid=ra.userid
WHERE
    p.subwikiid=$subwikiid AND ra.roleid=$roleid AND ra.contextid $contexts 
    AND v.deletedat IS NULL
GROUP BY
  	v.userid
");
    }    
	  return $result ? $result : array();
}


/**
 * Return the users in the specified group and role
 *
 * @param unknown_type $groupid the user group
 * @param unknown_type $roleid  the user role
 * @return the user ids
 */
function ouwiki_get_users($contexts, $groupid, $roleid) {
    global $CFG;
    // not all groups
    if ($groupid != 0) {    
        $result=get_records_sql("
SELECT
	  DISTINCT gm.userid 
FROM 
    {$CFG->prefix}groups_members gm    
    INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid=gm.userid  
WHERE
    gm.groupid=$groupid AND ra.roleid=$roleid AND ra.contextid $contexts
");
    // if all groups
    } else {            
        $result=get_records_sql("
SELECT
	  DISTINCT ra.userid 
FROM 
    {$CFG->prefix}role_assignments ra
WHERE
    ra.roleid=$roleid AND ra.contextid $contexts 
");
    }    
	  return $result ? $result : array();
}


/**
 * Return the number of edits and contributors for each page
 *
 * @param unknown_type $subwikiid  	the subwiki
 * @param unknown_type $groupid		the user group
 * @param unknown_type $roleid		the user role
 * @return the page title, the edit count and the contriburor count
 */
function ouwiki_get_editedpages($contexts, $subwikiid, $groupid, $roleid) {
    global $CFG;
    // if not all groups
    if ($groupid !=0) {
        $result=get_records_sql("
SELECT
	  p.id, p.title AS pagetitle, COUNT(DISTINCT v.id) AS editcount, COUNT(DISTINCT v.userid) AS contributorcount
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id  
    INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid=v.userid
    INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=v.userid  
WHERE
    p.subwikiid=$subwikiid AND ra.roleid=$roleid AND gm.groupid=$groupid AND ra.contextid $contexts  
    AND v.deletedat IS NULL
GROUP BY
	  p.title, p.id
ORDER BY 
	  p.title	
");
    // if all groups
    } else {
        $result=get_records_sql("
SELECT
	  p.id, p.title AS pagetitle, COUNT(DISTINCT v.id) AS editcount, COUNT(DISTINCT v.userid) AS contributorcount
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id  
    INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid=v.userid
WHERE
    p.subwikiid=$subwikiid AND ra.roleid=$roleid AND ra.contextid $contexts
    AND v.deletedat IS NULL
GROUP BY
	  p.title, p.id
ORDER BY 
	  p.title	
");        
    }
	  return $result ? $result : array();
}


/**
 * Return the number of comments for each page and the number of users who commented  
 *
 * @param unknown_type $subwikiid  the subwiki
 * @param unknown_type $groupid    the user group
 * @param unknown_type $roleid     the user role
 * @return page title, comment count and contributor count
 */
function ouwiki_get_pagecomments($contexts, $subwikiid, $groupid, $roleid) {
    global $CFG;
    // if not all groups
    if ($groupid !=0) {    
        $result=get_records_sql("
SELECT 
    p.id, p.title AS pagetitle, COUNT(DISTINCT c.id) AS commentcount, COUNT(DISTINCT c.userid) AS contributorcount 
FROM
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_sections s ON s.pageid=p.id         
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.sectionid=s.id
    INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=c.userid
    INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid=c.userid
WHERE
    c.deleted=0 AND p.subwikiid=$subwikiid AND ra.roleid=$roleid AND gm.groupid=$groupid AND ra.contextid $contexts
GROUP BY
    p.title, p.id
ORDER BY 
	  p.title
");
    // if all groups
    } else {        
        $result=get_records_sql("
SELECT 
    p.id, p.title AS pagetitle, COUNT(DISTINCT c.id) AS commentcount, COUNT(DISTINCT c.userid) AS contributorcount 
FROM
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_sections s ON s.pageid=p.id         
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.sectionid=s.id
    INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid=c.userid
WHERE
    c.deleted=0 AND p.subwikiid=$subwikiid AND ra.roleid=$roleid AND ra.contextid $contexts
GROUP BY
    p.title, p.id
ORDER BY 
	  p.title
");                
    } 
	  return $result ? $result : array();
}    


/**
 * Return info about the latest versions of all the pages on the subwiki
 *
 * @param unknown_type $subwikiid  the id of the subwiki to search
 * @return page title, text, start day of editing and last day of editing
 */
function ouwiki_get_pages($subwikiid) {
    global $CFG;    
    $result=get_records_sql("
SELECT
	  p.id, p.title AS title, v.xhtml AS text, v.timecreated AS lastday, 
    (SELECT 
        MIN(timecreated) 
    FROM 
        {$CFG->prefix}ouwiki_versions v2 
    WHERE
        v2.pageid=p.id AND v2.deletedat IS NULL) AS startday	
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON p.id=v.pageid AND p.currentversionid=v.id    
WHERE
    p.subwikiid=$subwikiid  
ORDER BY
	  p.title
");
	  return $result ? $result : array();
}


/**
 * Return the link count to each page
 *
 * @param unknown_type $subwikiid the subwiki
 * @return page title and link count
 */
function ouwiki_get_pagelinks($subwikiid) {
    global $CFG;    
    $result=get_records_sql("
SELECT
	  p.id, p.title AS title, COUNT(DISTINCT l.id) AS linkcount 
FROM 
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_links l ON p.id=l.topageid
    INNER JOIN {$CFG->prefix}ouwiki_versions v2 ON l.fromversionid=v2.id
    INNER JOIN {$CFG->prefix}ouwiki_pages p2 ON v2.pageid=p2.id AND p2.currentversionid=v2.id 
WHERE
    p.subwikiid=$subwikiid 
    AND v2.deletedat IS NULL
GROUP BY
	  p.id, p.title
ORDER BY
	  p.title
");
	  return $result ? $result : array();
}

/**
 * Gets a list of possible users for the current wiki, optionally restricted
 * to a particular group or role.
 *
 * @param string $contexts SQL condition to apply to role assignments to see
 *   if they have a role in this context.
 * @param int $groupid Group ID or 0 for any
 * @return array array of IDs or empty array if no users
 */
function ouwiki_get_usersin($contexts,$groupid=0) {
    global $CFG;
    $roleids = empty($CFG->ouwiki_reportroles) ? 0 : $CFG->ouwiki_reportroles;
    // Find applicable users
    if ($groupid !=0) {
        if($roleids) {    
            $rs=get_recordset_sql("
SELECT
	  DISTINCT u.id AS userid
FROM 
    {$CFG->prefix}groups_members gm
    INNER JOIN {$CFG->prefix}user u ON gm.userid=u.id
    INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid=gm.userid 
WHERE
    gm.groupid=$groupid
    AND ra.contextid $contexts
    AND ra.roleid IN ($roleids)
");
        } else {
            $rs=get_recordset_sql("
SELECT
    gm.userid
FROM 
    {$CFG->prefix}groups_members gm
WHERE
    gm.groupid=$groupid
");
        }
    } else {
        $rolecondition=$roleids ? " AND ra.roleid IN ($roleids)" : '';
        $rs=get_recordset_sql("
SELECT
	  DISTINCT u.id AS userid
FROM 
    {$CFG->prefix}role_assignments ra         
    INNER JOIN {$CFG->prefix}user u ON ra.userid=u.id 
WHERE
    ra.contextid $contexts
    $rolecondition
");
    }
    
    // Build array of users
    $userlist = array();
    while($rec=rs_fetch_next_record($rs)) {
        $userlist[$rec->userid] = $rec->userid;
    }
    rs_close($rs);
    return $userlist;    
}


/**
 * Return the user info, the number of edits by the user and the number of pages edited by the user,
 *   as well as the start day of editing and last day
 *
 * @param string $userlist Comma separated list of user id's
 * @param unknown_type $subwikiid  the subwiki
 * @param unknown_type $groupid    the user group
 * @return user id and name, edit count, number of edited pages, first and last days of editing
 */
function ouwiki_get_usersedits($userlist, $subwikiid, $groupid=0) {
    global $CFG;

    // Now get details about them
    $result=get_records_sql("
SELECT
    u.id AS userid, u.lastname, u.firstname, u.username,
    COUNT(DISTINCT v.id) AS editcount, COUNT(DISTINCT v.pageid) AS editedpagecount,
    MIN(v.timecreated) AS startday,
    MAX(v.timecreated) AS lastday
FROM
    {$CFG->prefix}user u
    LEFT JOIN {$CFG->prefix}ouwiki_versions v ON u.id=v.userid     
WHERE
    u.id IN ($userlist)
    AND v.deletedat IS NULL
    AND (v.pageid IS NULL OR v.pageid IN (SELECT id FROM {$CFG->prefix}ouwiki_pages p WHERE p.subwikiid=$subwikiid)) 
GROUP BY
    u.id, u.lastname, u.firstname, u.username
ORDER BY 
	  u.lastname, u.firstname	
");
    if ($result === false) {
        // If result === flase, assume records found (not error) and return empty array
        $result = array();
    }
	  return $result;
}


/**
 * Return all edited pages by this user in this subwiki as well as the time the 
 * page versions were created, the page id and title, the current version id and 
 * the previous version id.
 *
 * @param unknown_type $subwikiid the subwiki to search
 * @param unknown_type $userid    the id of the user to search for
 * @return version id, timecreated, page id, page title, current verstion id and 
 *        previous version id for all edited pages by the user in the subwiki
 */
function ouwiki_get_usereditpages($subwikiid, $userid) {
    global $CFG;
    $result=get_records_sql("
SELECT
	  v.id AS versionid, v.timecreated, p.id AS pageid, p.title as pagetitle, p.currentversionid,
	  (SELECT 
        MAX(id) 
    FROM 
        {$CFG->prefix}ouwiki_versions v2 
    WHERE
        v2.pageid=p.id AND v2.id < v.id AND v2.deletedat IS NULL) AS previousversionid	
FROM 
    {$CFG->prefix}ouwiki_pages p          
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id     
WHERE
    p.subwikiid=$subwikiid AND v.userid=$userid 
    AND v.deletedat IS NULL
ORDER BY 
    v.id	
");    
	  return $result ? $result : array();
}


/**
 * Return the user info as well as the number of edits and number of pages edited,
 * as well as the first and last days the user edited any pages, 
 * for the specified user and subwiki
 *
 * @param unknown_type $subwikiid  the subwiki in question
 * @param unknown_type $userid     the id of the user 
 * @return user id, name, number of edits, number of edited pages, first and last 
 * 			days where the user performed any editing 
 */
function ouwiki_get_useredits($subwikiid, $userid) {
    global $CFG;
    $result=get_records_sql("
SELECT
	  u.id, u.lastname, u.firstname, COUNT(DISTINCT v.id) AS editcount, COUNT(DISTINCT p.id) AS editedpagecount,
    (SELECT 
        MIN(timecreated) 
    FROM 
        {$CFG->prefix}ouwiki_pages p2 
        INNER JOIN {$CFG->prefix}ouwiki_versions v2 ON p2.id=v2.pageid
    WHERE
        p2.subwikiid=$subwikiid AND v2.userid=u.id AND v2.deletedat IS NULL) AS startday,
    (SELECT 
        MAX(timecreated) 
    FROM 
        {$CFG->prefix}ouwiki_pages p2 
        INNER JOIN {$CFG->prefix}ouwiki_versions v2 ON p2.id=v2.pageid
    WHERE
        p2.subwikiid=$subwikiid AND v2.userid=u.id AND v2.deletedat IS NULL) AS lastday
FROM 
    {$CFG->prefix}user u 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON u.id=v.userid     
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON v.pageid=p.id      
WHERE
    p.subwikiid=$subwikiid AND u.id=$userid 
    AND v.deletedat IS NULL
GROUP BY
  	u.id, u.lastname, u.firstname
ORDER BY 
	  u.lastname, u.firstname	
");    
	  return $result ? $result : array();
}


/**
 * Return dates of edits in subwiki by user in chronological order
 *   also returns the number of edits at that time just incase there is more than one - unlikely
 *
 * @param unknown_type $subwikiid   the subwiki to search
 * @param unknown_type $userid		the id of the user
 * @return unknown					the epoch time of the edit and the number of edits at that time 
 */
function ouwiki_get_usereditsbydate($subwikiid, $userid) {
    global $CFG;
    $result=get_records_sql("
SELECT
  	v.timecreated AS date, COUNT(DISTINCT v.id) AS editcount
FROM 
    {$CFG->prefix}ouwiki_pages p     
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id         
    INNER JOIN {$CFG->prefix}user u ON u.id=v.userid    
WHERE
    p.subwikiid=$subwikiid AND u.id=$userid 
    AND v.deletedat IS NULL
GROUP BY
	  v.timecreated
ORDER BY 
	  v.timecreated	
");    
	  return $result ? $result : array();
}

function ouwiki_get_userscomments($userlist, $subwikiid, $groupid=0) {
    global $CFG;
    $result=get_records_sql("
SELECT
	  u.id AS userid, u.lastname, u.firstname, COUNT(DISTINCT c.id) AS commentcount        
FROM
    {$CFG->prefix}user u
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.userid=u.id 
    INNER JOIN {$CFG->prefix}ouwiki_sections s ON c.sectionid=s.id         
    INNER JOIN {$CFG->prefix}ouwiki_pages p ON s.pageid=p.id
WHERE
    u.id IN ($userlist)
    AND c.deleted=0
    AND p.subwikiid=$subwikiid
GROUP BY
    u.id, u.lastname, u.firstname
ORDER BY
	  u.lastname, u.firstname
");
	  return $result ? $result : array();
}


/**
 * Return all comments by this user on this subwiki as well as
 *  the page title and the time the comment was posted
 *
 * @param unknown_type $subwikiid  the id of the subwiki
 * @param unknown_type $userid     the id of the user
 * @return comment id, page title and comment time posted
 */
function ouwiki_get_usercomments($subwikiid, $userid) {
    global $CFG;    
    $result=get_records_sql("
SELECT
	  DISTINCT c.id AS commentid, p.title, c.timeposted        
FROM     
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_sections s ON s.pageid=p.id         
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.sectionid=s.id    
WHERE
    c.deleted=0 AND p.subwikiid=$subwikiid AND c.userid=$userid
");
	  return $result ? $result : array();
}


/**
 * Return all coomments in subwiki by user in order of time posted
 *    also return a count of all comments at each time just in case
 *    there is more than one - unlikely.
 *	
 * @param unknown_type $subwikiid   the subwiki to search
 * @param unknown_type $userid		the id of the user
 * @return 						    time posted for the comment and comment count
 */
function ouwiki_get_usercommentsbydate($subwikiid, $userid) {
    global $CFG;    
    $result=get_records_sql("
SELECT
	  c.timeposted AS date, COUNT(DISTINCT c.id) AS commentcount         
FROM     
    {$CFG->prefix}ouwiki_pages p
    INNER JOIN {$CFG->prefix}ouwiki_sections s ON s.pageid=p.id         
    INNER JOIN {$CFG->prefix}ouwiki_comments c ON c.sectionid=s.id      
WHERE
    c.deleted=0 AND p.subwikiid=$subwikiid AND c.userid=$userid
GROUP BY
	  c.timeposted
ORDER BY
	  c.timeposted
");
	  return $result ? $result : array();
}


/**
 * Return the page versions with page title and time created as well as the user who edited the page.
 *
 * @param $subwikiid the subwiki to search
 * @param $groupid the user group to find edits for 
 * @return the page versions with page title and time created as well as the user who edited the page.
 */
function ouwiki_get_editpagetimes($subwikiid, $groupid) {
    global $CFG;    
    // if group is not all groups
    if ($groupid != 0) {    
        $result=get_records_sql("
SELECT
	  v.id AS version, p.title AS pagetitle, v.timecreated AS date, v.userid 
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id  
    INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=v.userid  
WHERE
    p.subwikiid=$subwikiid AND gm.groupid=$groupid
    AND v.deletedat IS NULL
ORDER BY 
	  p.title, v.timecreated	
");
    // if group is all groups
    } else {
        $result=get_records_sql("
SELECT
	  v.id AS version, p.title AS pagetitle, v.timecreated AS date, v.userid
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id  
WHERE
    p.subwikiid=$subwikiid
    AND v.deletedat IS NULL
ORDER BY 
	  p.title, v.timecreated	
");                    
    }
	  return $result ? $result : array();
}


/**
 * Select the version page title, time created and user id for all pages 
 * in the specified subwiki where the user who edited the page is in the
 * specified group and the user also has the specified role.
 * 
 * This then lists all the edits for each page along a time line, specifying 
 * the author of each edit.  
 *
 * @param int $subwikiid Subwiki ID
 * @param int $groupid Group ID, 0 = all groups
 * @param int $roleid Role ID, 0 = all roles
 * @return the page version id, page title, time created, user who edited 
 */
function ouwiki_get_pageversionsandusers($contexts, $subwikiid, $groupid, $roleid) {
    global $CFG;

    $rolejoin=$roleid ? "INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid=v.userid" : "";
    $rolewhere=$roleid ? "AND ra.roleid=$roleid AND ra.contextid $contexts" : "";
    
    $groupjoin=$groupid ? "INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=v.userid" : "";
    $groupwhere=$groupid ? "AND gm.groupid=$groupid" : "";
    
    $result=get_records_sql("
SELECT
	  v.id AS version, p.title AS pagetitle, v.timecreated, v.userid, p.id AS pageid
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id  
    $groupjoin
    $rolejoin
WHERE
    p.subwikiid=$subwikiid 
    AND v.deletedat IS NULL
    $groupwhere 
    $rolewhere
ORDER BY 
	  p.title, v.timecreated	
");
	  return $result ? $result : array();
}


/**
 * Return the earliest version of all pages in the wiki
 *
 * @param unknown_type $subwikiid
 * @return the page titel, the first version id and the time it was created
 */
function ouwiki_get_earliestpageversions($subwikiid) {
    global $CFG;    
    $result=get_records_sql("
SELECT
  	p.title AS pagetitle, MIN(v.id) AS first, MIN(v.timecreated) AS time
FROM 
    {$CFG->prefix}ouwiki_pages p 
    INNER JOIN {$CFG->prefix}ouwiki_versions v ON v.pageid=p.id  
WHERE
    p.subwikiid=$subwikiid
    AND v.deletedat IS NULL
GROUP BY
	  p.title    
ORDER BY 
	  p.title	
");
	  return $result ? $result : array();
}


/**
 * Return the number of pages created by each user
 *
 * @param unknown_type $subwikiid  the id of the subwiki to search
 * @return 						   the number of pages created by each user
 */
function ouwiki_get_userspagecreate($subwikiid) {
    global $CFG;    
    $result=get_records_sql("
SELECT
  	v.userid, COUNT(*) AS createdcount
FROM	
	  {$CFG->prefix}ouwiki_versions v
WHERE 
    v.id IN 
	    (SELECT 
	    	  MIN(v2.id) 
	    FROM 	        
	        {$CFG->prefix}ouwiki_pages p 
 	      	INNER JOIN {$CFG->prefix}ouwiki_versions v2 ON v2.pageid=p.id
	   	WHERE 
	     		p.subwikiid=$subwikiid
                AND v2.deletedat IS NULL
	    GROUP BY 
	      	p.id)	  
GROUP BY 
	  v.userid	      	    
");
	  return $result ? $result : array();
}


?>