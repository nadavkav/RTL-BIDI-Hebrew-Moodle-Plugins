<?php
/**
 * This file contains wiki persistor interface implementation
 * for Moodle environment.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: wiki_persistor.php,v 1.62 2008/11/29 13:22:19 kenneth_riba Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */

global $CFG;
require_once ($CFG->dirroot.'/lib/dmllib.php');
require_once ($CFG->dirroot.'/mod/wiki/db/wiki_persistor_interface.php');

class wiki_persistor extends wiki_persistor_interface{

	//used for visible pages
    var $pagesel = array();

    //used for index level page
    var $pagelev = false;

	/**
     *  Constructor. Creates an instance of the class.
     *
     */

	function wiki_persistor(){

	}

	/**
     *  Saves the wiki data in the database.
     *
     *  @param wiki $wiki
     *
     */
    function save_wiki($wiki){

		return insert_record("wiki", $wiki);
	}

	/**
     *  Updates the wiki data in the database.
     *
     *  @param wiki $wiki
     *
     */
    function update_wiki($wiki) {
        return update_record('wiki', $wiki);
    }

    /**
     * Delete a wiki and all record that depend on it.
     *
     * @param int $wikiid
     *
     * @return bool
     */
    function delete_wiki($wikiid) {
        $result = true;

        if (! delete_records('wiki_pages', 'dfwiki', $wikiid)) {
            $result = false;
        }

        if (! delete_records('wiki_synonymous', 'dfwiki', $wikiid)) {
            $result = false;
        }

        if (! delete_records('wiki', 'id', $wikiid)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Sets editables on all pages of the wiki.
     *
     * @param int $wikiid
     * @param int $editable
     */
    function pages_set_editable($wikiid, $editable) {
        $quer = 'UPDATE '. $CFG->prefix.'wiki_pages
                SET editable=\''.$editable.'\'
                WHERE dfwiki=\''.$wikiid.'\'';
        execute_sql($quer,false);
    }

	/**
     *  Saves the record of the wikipage in the database.
     *  Returns the wikipage id if correct or false if failed.
     *
     *  @param  stdClass $record
	 *  @return int
     */
	function save_wiki_page($record){

		return insert_record('wiki_pages', $record);
	}

	/**
     *  Returns the record of the database with the data of the wiki given.
     *
     *  @param integer $id
     *  @return stdClass
     */
    function get_wiki_by_id($wikiid) {

		return $record = get_record('wiki', 'id', $wikiid);
    }

   	/**
     *  Returns the record of the database with the data of the wiki page given.
     *
     *  @param integer $id
     *  @return stdClass
     */
    function get_wiki_page_by_id($wikipageid) {

		return get_record('wiki_pages', 'id', $wikipageid);
    }
    
    /**
	 * returns a wikipage with the pagename given or false if does not exists
	 * @param int $wikiid: wiki object
	 * @param String $pagename
	 * @param int $version=false (last version if false)
	 * @param int $groupid=0
	 * @param int $ownerid=0
	 * @return wikipage or null
	 */
	function get_wiki_page_by_pagename ($wikiid,$pagename,$version=false,$groupid=0,$ownerid=0) {
		//if version===false get last version

		if (!$version) {
			$version = $this->get_last_wiki_page_version($pagename,$wikiid,$groupid,$ownerid);
		}
		if (!$version) return false;
		$where = 'pagename=\''.addslashes($pagename).'\' AND dfwiki='.$wikiid.'
                 AND version='.$version;

		$where.= ' AND groupid='.$groupid;
       
		if (!empty($ownerid)){
			$where.= ' AND ownerid='.$ownerid;
        }
        return get_record_select ('wiki_pages',$where,'*');
	}

    /**
     * Returns true if page exists, false otherwise.
     *
     * @param   int     $wikid
     * @param   string  $pagename
     * @param   int     $version
     * @param   int     $groupid
     * @param   int     $ownerid
     *
     * @return  bool
     */
    function page_exists($wikiid, $pagename, $version=null, $groupid=null, $ownerid=null) {
        $select = "dfwiki=$wikiid AND pagename='" . addslashes($pagename) . "'";
        if (isset($version)) {
            $select .= " AND version=$version";
        }
        if (isset($groupid)) {
            $select .= " AND groupid=$groupid";
        }
        if (isset($ownerid)) {
            $select .= " AND ownerid=$ownerid";
        }
        return record_exists_select('wiki_pages', $select);
    }

	/**
     *  Get array of records with wiki's data by course id.
     *
     *  @param String $courseid
     *  @return array of stdClass
     */
    function get_wikis_by_course($courseid) {

		return get_records('wiki', 'course', $courseid);
    }

	/**
     *  It returns a list with the records of the wiki pages contained in the wiki given.
     *
     *	@uses $CFG
     *  @param  String $wikiid
     *  @return Array of stdClass
     *
     */
	function get_wiki_pages_by_wiki($wikiid, $groupid, $ownerid){
		global $CFG;
		if (empty($ownerid)){
		return get_records('wiki_pages', 'dfwiki', $wikiid, 'groupid', $groupid);
		}
		return get_records('wiki_pages', 'dfwiki', $wikiid, 'groupid', $groupid, 'ownerid', $ownerid);
	}

	/**
     *  It returns a list with the records of the historic wikipages of a wikipage given.
     *
     *  @param  String $pagename. 	The name of the wikipage.
     *  @param  int $wikiid			The id of the wiki.
     *  @param  int $groupid		The group of the wikipage.
     *  @param  int $ownerid		The owner of the wikipage.
     *  @return Array of Wikipage
     *
     */
	function get_wiki_page_historic($pagename,$dfwikiid,$groupid,$ownerid){
		global $CFG;
		return  get_records_sql('SELECT *
                FROM '. $CFG->prefix.'wiki_pages
                WHERE pagename=\''.addslashes($pagename).'\' AND dfwiki='.$dfwikiid.'
                AND groupid='.$groupid.' AND ownerid='.$ownerid.' ORDER BY version DESC');
	}

	/**
     *  It returns an array of recrods that contains the users who
     *  are owners of a wikipage contained in the wiki.
     *
     *  @param  int $wikiid
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of stdClass
     *
     */
	function get_wiki_page_owners_of_wiki($wikiid, $groupid, $ownerid){
		global $CFG;
		if (empty($ownerid)){
			return get_records_sql('SELECT u.firstname, u.lastname
           							FROM '. $CFG->prefix.'wiki_pages wp, '. $CFG->prefix.'user u
            						WHERE wp.dfwiki='.$wikiid.' AND u.username = wp.author
            							  AND wp.groupid = '.$groupid.'
									ORDER BY u.firstname, u.lastname');
        }
		return get_records_sql('SELECT u.firstname, u.lastname,
       							FROM '. $CFG->prefix.'wiki_pages wp, '. $CFG->prefix.'user u
        						WHERE wp.dfwiki='.$wikiid.' AND u.username = wp.author
        							  AND wp.groupid = '.$groupid.' AND wp.ownerid = '.$ownerid.'
								ORDER BY u.firstname, u.lastname');
	}

	/**
     *  It returns an array of recrods that contains the id users who
     *  are owners of a wikipage contained in the wiki.
     *
     *  @param  int $wikiid
     *  @return Array of integer
     *
     */
	function get_wiki_page_owners_ids_of_wiki($wikiid){
		global $CFG;
		if ($records = get_records_sql('SELECT u.id
                FROM '. $CFG->prefix.'wiki_pages wp, '. $CFG->prefix.'user u
                WHERE wp.dfwiki='.$wikiid.' AND u.username = wp.author
				ORDER BY u.firstname, u.lastname')){
				return $records;
        }
		return false;
	}
	/**
     *  It returns an array of records that contains the page names of the wiki pages
     *  in the wiki, ordered by name.
     *
     *  @param  int $wikiid
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of stdClass
     *
     */
	function get_wiki_page_names_of_wiki($wikiid, $groupid, $ownerid){
		global $CFG;
		if (empty($ownerid)){
			return get_records_sql('SELECT pagename
                				FROM '. $CFG->prefix.'wiki_pages
                				WHERE dfwiki='.$wikiid.' AND groupid = '.$groupid.'
                				ORDER BY pagename');
        }
		return get_records_sql('SELECT pagename
                				FROM '. $CFG->prefix.'wiki_pages
                				WHERE dfwiki='.$wikiid.' AND groupid = '.$groupid.' AND ownerid = '.$ownerid.'
                				ORDER BY pagename');
	}

    /**
     * Array of pages of the user, ordered by last modified.
     *
     * @param string $username
     * @param int $wikiid
     *
     * return array of pages
     */
    function get_wiki_pages_user_outline($username, $wikiid) {
        global $CFG;
        return get_records_sql('SELECT *
                                FROM '. $CFG->prefix.'wiki_pages
                                WHERE author=\''.$username.'\' AND dfwiki='.$wikiid.'
                                ORDER BY lastmodified DESC');
    }

	/**
     *  It returns highest number of version of the wikipage given.
     *
     *  @param  string $pagename
     *  @param  integer $wikiid
     *  @param  integer $groupid
     *  @param  integer $ownerid
     *  
     *  @return integer
     *
     */
	function get_last_wiki_page_version($pagename,$wikiid,$groupid,$ownerid){
		global $CFG;

        $where = 'pagename=\''.addslashes($pagename).'\' AND dfwiki='.$wikiid;
		$where.= ' AND groupid='.$groupid;

		if (!empty($ownerid)){
			$where.= ' AND ownerid='.$ownerid;
        }

        $record = get_record_sql('SELECT MAX(version) AS version
                FROM '. $CFG->prefix.'wiki_pages
                WHERE ' . $where);
		
		if (empty($record)){
			return false;
		}
		
		return $record->version;
	}
	
	/**
     *  It returns all wikipages ordered by numeber of hits.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass or false if there are no pages
     *
     */	
	function get_most_viewed_pages($wikiid=false){
		global $CFG;
		if (!$wikiid) {
			$wikiid = wiki_param ('dfwiki');
			$wikiid = $wikiid->id;
		}
		$quer = 'dfwiki='.$wikiid.'
                AND pagename NOT LIKE \'discussion:%\'';
        if (!$pages = get_records_select('wiki_pages',$quer,'hits desc')) {
			return false;
		}
		return $pages;
	}
	
	/**
     *  It returns all wikipages ordered by date of creation.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass or false if there are no pages
     *
     */
	function get_wiki_newest_pages($wikiid){
		global $CFG;
		$quer = 'dfwiki='.$wikiid.'
				AND pagename NOT LIKE \'discussion:%\'';
		if (!$pages = get_records_select('wiki_pages',$quer,'created desc')) {
			return false;
		}
		return $pages;
	}
	
	/**
     *  It returns all wikipages ordered by number of version.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass or false if there are no pages
     *
     */
	function get_wiki_most_uptodate_pages($wikiid=false){
		global $CFG;
		if (!$wikiid) {
			$wikiid = wiki_param ('dfwiki');
			$wikiid = $wikiid->id;
		}
		$quer = 'dfwiki='.$wikiid.'
				AND pagename NOT LIKE \'discussion:%\'';
		if (!$pages = get_records_select('wiki_pages', $quer, 'lastmodified desc')) {
			return false;
		}
		return $pages;		
	}
	
	function get_wiki_orphaned_pages($num=-1){
		global $WS,$CFG;
		//get the page list EXCLUDING DISCUSSION PAGES
        $quer = 'dfwiki='.$WS->dfwiki->id.'
                        AND pagename NOT LIKE \'discussion:%\'';
        if (!$pages = get_records_select('wiki_pages',$quer,'pagename')) {
			return array();
		}

    	//get the list of pages that are accessible from the main tree
    	$links = $this->get_visible_pages ();

    	//built array
    	$res = array();
    	foreach ($pages as $page){
    		if ($num!=0 && !in_array($page->pagename,$res) && !in_array($page->pagename,$links)){
    			$res[] = $page->pagename;
    			$num--;
    		}
    	}
    	return $res;
	}
	
	function get_wiki_synonyms($name = false){
		global $CFG;
    	
    	$dfwiki = wiki_param('dfwiki');
    	$groupmember = wiki_param('groupmember');
    	
    	if($name===false){
    		$ands = '';
    	}else{
    		$ands = 'original=\''.$name.'\' AND ';
    	}

    	$res = array();

    	//select synonymous EXLUDING DISCUSSION PAGES
    	$quer = $ands.'dfwiki='.$dfwiki->id.'
                AND groupid='.$groupmember->groupid.'
                AND original NOT LIKE \'discussion:%\'';

    	if ($syns = get_records_select('wiki_synonymous',$quer)){
    		foreach ($syns as $syn){
    			$res[] = $syn->syn;
    		}
    	}
    	return $res;
	}	
	
	/**
     *  returns an array with the wanted pages
     */	
	function get_wiki_wanted_pages(){
		
	}
	
	/**
	 * this function return the real name if it's a synonymous,
	 * or the same pagename otherwise.
	 * 
	 * @param   string  $name       name of the page.
	 * @param   int     $id         dfwiki instance id, current dfwiki default
     * @param   int     $onwerid
     * @param   int     $groupid
	 * @return  string
	 */
	function wiki_get_real_pagename ($name,$id=false, $groupid=null, $ownerid=null) {
	    //set default $id value
	    //$id = ($id)? $id : $WS->dfwiki->id;
	    $dfwiki = wiki_param('dfwiki');
	    $id = ($id)? $id : $dfwiki->id;

	    $select = "syn='" . addslashes($name) . "' AND dfwiki=$id";
        if (isset($groupid)) {
            $select .= " AND groupid=$groupid";
        }
        if (isset($ownerid)) {
            $select .= " AND ownerid=$ownerid";
        }

	    //watch in synonymous
	    if ($synonymous = get_record_select('wiki_synonymous', $select)) {
	        //if there's synonymous search for the original
	        return $synonymous->original;
	    }
		//if isn't a synonymous it will be an original or an uncreated page.
	    return $name;
	}
	
	/**
     *  Changes the wiki page name in all the historic of the wiki page.
     *
     *  @param  String  $pagename_new
     *  @param  String  $pagename_old
     *  @param  Integer $wikiid
     *  @param  Integer	$groupid
     *  @param  Integer	$ownerid
     *  
     *  @uses $CFG
     *  
     */	
	function change_wiki_page_name($pagename_new, $pagename_old, $wikiid, $groupid, $ownerid ){
		global $CFG;
		$quer = 'UPDATE '. $CFG->prefix.'wiki_pages
    						SET pagename=\''.addslashes($pagename_new).'\'
    						WHERE pagename=\''.addslashes($pagename_old).'\' AND dfwiki='.$wikiid.'
    						      AND groupid='.$groupid.' AND ownerid ='.$ownerid;
		return execute_sql($quer,false);
	}
	
	/**
     *  Updates all the records of the database that are historic of the wikipage
     *  setting false in all of its editable fields.
     *
     *  @param  String  $pagename
     *  @param  Integer $wikiid
     *  @param  Integer	$groupid
     *  @param  Integer	$ownerid
     *  
     */	
	function disable_wiki_page_edition($pagename, $wikiid, $groupid, $ownerid ){
		global $CFG;
		$quer = 'UPDATE '. $CFG->prefix.'wiki_pages
    						SET editable=
    						WHERE pagename=\''.addslashes($pagename).'\' AND dfwiki='.$wikiid.'
    						      AND groupid='.$groupid.' AND ownerid ='.$ownerid;
		return execute_sql($quer,false);
	}
	
	/**
     *  return an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
     *
     *  @param  String 	$user
     *  @param  Integer $wikiid
     *  
     */	
	function user_activity_in_wiki($user, $wikiid){
		global $CFG;
		
		$select = 'author="'.addslashes($user).'" 
				AND dfwiki='.$wikiid.' 
				AND pagename NOT LIKE \'discussion:%\'';
		return get_records_select('wiki_pages', $select,'', 'DISTINCT pagename,dfwiki');
	}
	
	/**
	 * Return an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
	 * 
	 * @param Integer $wikiid
	 * @param String $user
	 * 
	 * @return Array of String
	 */
	function user_activity ($wikiid, $user){
		global $CFG;
		
        $quer = 'SELECT DISTINCT pagename,dfwiki
                FROM '. $CFG->prefix.'wiki_pages
                WHERE author="'.addslashes($user).'" 
				AND dfwiki='.$wikiid.' 
				AND pagename NOT LIKE \'discussion:%\'';

		return get_records_sql($quer);

	}
	
		/**
     *  Creates a XML file with all the data of the wiki in the folder given.
     *
     *  @param  Wiki 	$wiki
     *  @param  String	$folder
     *  
     */	
	function export_wiki_to_XML($wiki, $folder = 'exportedfiles'){

	}
	
		/**
	 * 
     *  Obtains the discussion pages of the wikipage indicated with the parametres.
     *
     *  @param  String  $pagename
     *  @param  Integer $wikiid
     *  @param  Integer	$groupid
     *  @param  Integer	$ownerid
     *  
     */	
	function get_discussion_page_of_wiki_page($pagename, $wikiid, $groupid, $ownerid){
		global $CFG;
		
		$quer = 'SELECT *
	    FROM '. $CFG->prefix.'wiki_pages
		WHERE pagename=\'discussion:'.addslashes($pagename).'\' AND dfwiki='.$wikiid.'
    	AND groupid='.$groupid.' AND ownerid ='.$ownerid;

		return get_records_sql($quer);
	}	
	
	/**
     *  It returns the users ordered by the number of wikipages saved.
     *
     *  @param  Integer $wikiid
     *  @param  Integer $wikiid
     *  
     *  @return Array of String
     *
     */
	function get_activest_users($wikiid, $groupid){
		
	}
	
	/**
	 * Return an array with all active users
	 * 
	 * @param Integer	$wikiid
	 * @param Integer	$groupid
	 * 
	 * @return Integer 
	 */
    function active_users($wikiid, $groupid = 0){
    	global $CFG;

    	$quer = 'SELECT DISTINCT author,dfwiki
				FROM '. $CFG->prefix.'wiki_pages
				WHERE dfwiki='.$wikiid.'
                AND groupid='.$groupid;
				
    	return get_records_sql($quer);
    }
	
	
	/**
	 * Return the number of participations in the current dfwiki EXCLUDING THE DISCUSSIONS.
	 * 
	 * @param Integer $wikiid
	 * @param String $user
	 * 
	 * @return Integer 
	 * 
	 */
	function user_num_activity ($wikiid, $user){
		global $CFG;
				
        $quer = 'SELECT COUNT(*) AS num
                FROM '. $CFG->prefix.'wiki_pages
                WHERE author="'.$user.'" AND dfwiki='.$wikiid.' AND pagename NOT LIKE \'discussion:%\'';
		
		return get_record_sql($quer);
	}
	


    /**
     * Return the vote ranking of a wiki.
     *
     * @param Integer $wikiid
     *
     * @return Array of stdClass
     */
    function get_vote_ranking($wikiid) {
		global $CFG;
        $sql = 'SELECT pagename, COUNT(*) as votes'
            . ' FROM ' . $CFG->prefix . 'wiki_votes'
            . ' WHERE `dfwiki`='. $wikiid
            . ' group by `pagename` order by votes desc';
        return get_records_sql($sql);   
    }

    /**
     * Wheter the given vote exists.
     *
     * @param Integer $wikiid
     * @param String $pagename
     * @param Integer $pageversion
     * @param String $username
     */
    function vote_exists($wikiid, $pagename, $pageversion, $username) {
        $select = "dfwiki=$wikiid AND pagename='" . addslashes($pagename)
            . "' AND version=$pageversion AND username='$username'";
        return record_exists_select('wiki_votes', $select);
    }

    /**
     * Insert a new vote.
     *
     * @param Integer $wikiid
     * @param String $pagename
     * @param Integer $pageversion
     * @param String $username
     */
    function insert_vote($wikiid, $pagename, $pageversion, $username) {
        $record = new stdClass;
        $record->dfwiki = $wikiid;
        $record->pagename = addslashes($pagename);
        $record->version = $pageversion;
        $record->username = $username;
        return insert_record('wiki_votes', $record);
    }

    /**
     * Get synonyms of a page.
     *
     * @param   int         $wikiid
     * @param   string      $pagename
     * @param   int         $groupid
     * @param   int         $ownerid
     *
     * @return  array    Array of records.
     */
    function get_synonyms($wikiid, $pagename, $groupid, $ownerid) {
        $select = "dfwiki=$wikiid AND original='" . addslashes($pagename)
            . "' AND groupid=$groupid AND ownerid=$ownerid";
        return get_records_select('wiki_synonymous', $select);
    }

    /**
     * Get synonyms in a wiki.
     *
     * @param   int         $wikiid
     *
     * @return  array    Array of records.
     */
    function get_synonyms_by_wikiid($wikiid) {
        return get_records('wiki_synonymous', 'dfwiki' , $wikiid);
    }

    /**
     * Delete a synonym.
     *
     * @param   int         $wikiid
     * @param   string      $name
     * @param   int         $groupid
     * @param   int         $ownerid
     */
    function delete_synonym($wikiid, $name, $groupid, $ownerid) {
        $select = "dfwiki=$wikiid AND syn='" . addslashes($name)
            . "' AND groupid=$groupid AND ownerid=$ownerid";
        return delete_records_select('wiki_synonymous', $select);
    }


    /**
     * Insert a synonym.
     *
     * @param   int         $wikiid
     * @param   string      $name
     * @param   string      $original
     * @param   int         $groupid
     * @param   int         $ownerid
     */
    function insert_synonym($wikiid, $name, $original, $groupid, $ownerid) {
        $record = new stdClass;
        $record->dfwiki = $wikiid;
        $record->syn = addslashes($name);
        $record->original = addslashes($original);
        $record->groupid = $groupid;
        $record->ownerid = $ownerid;
        return insert_record('wiki_synonymous', $record);
    }
    
    /**
     * Get the group id of the user in a course.
     *
     * @param   int     $userid
     * @param   int     $courseid
     *
     * @return  int
     * 
     */
    function get_groupid_by_userid_and_courseid($userid, $courseid) {
		//@TODO: This function must be removed.  basicgrouplib.php must be used.
        global $CFG;
		
		
        $query = "SELECT gm.groupid
                  FROM {$CFG->prefix}groups g,
                       {$CFG->prefix}groups_members gm
                  WHERE gm.userid='$userid'
                        AND gm.groupid=g.id
                        AND g.courseid=$courseid";

        if ($records = get_records_sql($query)) {
            return $records;
        } else {
            return false;
        }
    }
    

    /** Get list of groups in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_groups($courseid) {
		//@TODO: This function must be removed.  basicgrouplib.php must be used.
        global $CFG;
        return get_records('groups', 'courseid', $courseid);
    }

    /** Get list of members in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_members($courseid) {
		//@TODO: This function must be removed.  Moodle functions must be used.
        global $CFG;
        return get_records_sql('SELECT gm.id as groupsmembersid, u.id, g.id as groupid,
                                       g.name as groupname, u.firstname, u.lastname
                                FROM '. $CFG->prefix.'groups g,
                                     '. $CFG->prefix.'groups_members gm,
                                     '. $CFG->prefix.'user u
                                WHERE g.courseid=\''.$courseid.'\'
                                      AND g.id = gm.groupid
                                      AND u.id = gm.userid
                                      ORDER BY g.name, u.lastname');
    }

    /**
     * Get list of teachers in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_teachers($courseid) {
        global $CFG;
        return get_records_sql('SELECT u.id, u.firstname, u.lastname
                                FROM  '.$CFG->prefix.'user u, '. $CFG->prefix.'user_teachers ut
                                WHERE ut.course=\''.$courseid.'\' AND u.id = ut.userid');
    }


    /**
     * Increment the hits of a page.
     *
     * @param   int     $wikiid
     * @param   string  $pagename
     * @param   int     $version
     * @param   int     $groupid
     * @param   int     $ownerid
     */
    function increment_page_hits($wikiid, $pagename, $version, $groupid, $ownerid) {
        $select = "dfwiki=$wikiid AND pagename='" . addslashes($pagename)
            . "' AND version=$version";
        if (isset($groupid)) {
            $select .= " AND groupid=$groupid";
        }
        if (isset($ownerid)) {
            $select .= " AND ownerid=$ownerid";
        }

        // return set_field_select('wiki_pages', 'hits', 'hits+1', $select);
        $hits = get_field_select('wiki_pages', 'hits', $select);
        return set_field_select('wiki_pages', 'hits', $hits+1, $select);
    }

    /**
     * Update the evaliuation of a page.
     *
     * @param   int     $pageid
     * @param   string  $evaluation
     */
    function update_page_evaluation($pageid, $evaluation) {
        return set_field('wiki_pages', 'evaluation', $evaluation, 'id', $pageid);
    }

    /**
     * Return array of page versions in the wiki.
     *
     * @param   int     $wikiid
     * @return  array of int
     */
    function get_wiki_page_versions($wikiid) {
        global $CFG;
        return get_records_sql('SELECT DISTINCT version
                FROM '. $CFG->prefix . 'wiki_pages
                WHERE dfwiki=\''.$wikiid.'\'
          	    ORDER BY version ASC');
    }

    /**
     * Get all pages in a wiki.
     *
     * @param   int     $wikiid
     * @return  array
     */
    function get_wiki_pages_by_wikiid($wikiid) {
        return get_records('wiki_pages', 'dfwiki', $wikiid);
    }

    /**
     * Delete an editing lock.
     *
     * @param   int     $lockid
     */
    function delete_lock($lockid) {
        return delete_records('wiki_locks','id', $lockid);;
    }

    /** Get an editing lock.
     *
     * @param   int     $wikiid
     * @param   string  $pagename
     *
     * @return  stdClass
     */
    function get_lock($wikiid, $pagename) {
        return get_record('wiki_locks', 'wikiid', $wikiid, 'pagename',
            addslashes($pagename));
    }

    /**
     * Insert an editing lock.
     *
     * @param   int     $wikiid
     * @param   string  $pagename
     * @param   int     $lockedby
     * @param   int     $lockedsince
     * @param   int     $lockedseen
     */
    function insert_lock($wikiid, $pagename, $lockedby, $lockedsince, $lockedseen) {
        $record = new stdClass;
        $record->wikiid = $wikiid;
        $record->pagename = addslashes($pagename);
        $record->lockedby = $lockedby;
        $record->lockedsince = $lockedsince;
        $record->lockedseen = $lockedseen;
        return insert_record('wiki_locks', $record);
    }
    
    /**
     * return an array of all visibles pages from the
     * first page in the current dfwiki
     * @return array of Strings
     */
    function get_visible_pages (){
    	global $WS;
    	$this->pagesel = array();
    	$this->get_visible_pages_from_page ($WS->dfwiki->pagename);
    	return $this->pagesel;
    }

    /**
     * return an array of all visibles pages from a pagename
     * @param String $page: startingpagename
     * @return array of Strings
     */
    function get_visible_pages_from_page ($page){
    	global $WS,$CFG;

    	//printed links array
    	$printed_links = array();

    	//search in vector
    	if (!in_array($page,$this->pagesel)){
    		//put in vector
    		$this->pagesel[] = $page;

    		//get last version
    		if ($pageinfo = wiki_page_last_version ($page)){
    			//get links
    			$links = wiki_internal_link_to_array($pageinfo->refs);

    			//foreach link do recursive
    			foreach ($links as $link){
    				//get real page name
    				$link = wiki_get_real_pagename ($link);
    				//search in printed_links
    				if (!in_array($link,$printed_links)){
    					//put in printed links
    					$printed_links[] = $link;
    					//mount
    					$this->get_visible_pages_from_page ($link);
    				}
    			}
    		}
    	}
    }
    
    /**
     * return an array with all pages that have a link to the given one.
     * @param String $pagename
     * @return array of wikipages record object
     */
    function get_wiki_page_camefrom ($pagename){
    	global $CFG,$WS;
		
		$pagename = wiki_get_real_pagename ($pagename);

    	//get all pages list in the current dfwiki  EXCLUDING DISCUSSION PAGES
        $quer = 'dfwiki='.$WS->dfwiki->id.'
                        AND groupid='.$WS->groupmember->groupid.'
                        AND pagename NOT LIKE \'discussion:%\'
                        AND refs LIKE \'%'.addslashes($pagename).'%\'';

    	$res = array();
    	if ($pages = get_records_select('wiki_pages',$quer,'pagename ASC','DISTINCT pagename,dfwiki')){
    		foreach ($pages as $page){
    			$res[] = $page;
    		}
    	}
    	return $res;
    }
    
    /**
     * Get user information.
     *
     * @param   string  $user
     *
     * @return  stdClass
     */
    function get_user_info($username) {
        return get_record('user', 'username', $username);
    }

    /**
     * Get user information.
     *
     * @param   int  $userid
     *
     * @return  stdClass
     */
    function get_user_info_by_id($userid) {
        return get_record('user', 'id', $userid);
    }

    /**
     * Get authors of a wiki page.
     *
     * @param   int     $wikiid
     * @param   string  $pagename
     * @param   int     $groupid
     * @param   int     $ownerid
     * @return  array of string
     */
    function get_wiki_page_authors($wikiid, $pagename, $groupid=null, $ownerid=null) {
        global $CFG;
        $query = "SELECT DISTINCT author
                  FROM {$CFG->prefix}wiki_pages
                  WHERE dfwiki=$wikiid 
                  AND pagename='". addslashes($pagename) . "'";
        if (!empty($groupid)) {
            $query .= " AND groupid=$groupid";
        }
        if (!empty($ownerid)) {
            $query .= " AND ownerid=$ownerid";
        }
        $query .= ' ORDER BY author ASC';
        return get_records_sql($query);
    }

}
?>
