<?php

/**
 * This file contains WikiManager class.
 *
 * WikiManager class is the "Wiki API"
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: wiki_manager.php,v 1.63 2008/04/22 15:40:00 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package API
 */

require_once ($CFG->dirroot.'/mod/wiki/lib/control_auth_wiki.php');

// How long locks stay around without being confirmed (seconds)
define("WIKI_LOCK_PERSISTENCE",120);

// How often to confirm that you still want a lock
define("WIKI_LOCK_RECONFIRM",60);

// Session variable used to store wiki locks
define('SESSION_WIKI_LOCKS','wikilocks');


/**
 * Returns a singleton instance of wiki_manager
 * 
 * @param String $type=false just 'moodle' or 'OKI' just required first time
 * @return wiki_manager
 */
function wiki_manager_get_instance ($type='moodle') {
	static $man = array();
	if (!isset($man['single']) && !$type) return false;
	if (!isset($man['single'])) $man['single'] = new wiki_manager($type);
	return $man['single'];
}

class wiki_manager{

	var $persistencemanager;
	var $controlauth;

	 /**
     *  Constructor.
     *
     *  @param String $type. It represents the environement. It must be MOODLE or OKI.
     */
    function wiki_manager($type) {
		global $CFG;
		require_once ($CFG->dirroot.'/mod/wiki/lib/'.$type.'/persistence_manager.php');
		$this->persistencemanager = new persistence_manager();
		require_once ($CFG->dirroot.'/mod/wiki/lib/control_auth_wiki.php');
		$this->controlauth= new control_auth_wiki();
    }

	/**
     *  Returns the wiki with the id given, or false if it does not exist.
     *
     *  @param  int $wikiid
     *  @return Wiki
     */

	function get_wiki_by_id($wikiid){
		return $this->persistencemanager->get_wiki_by_id($wikiid);
	}
	
	/**
	 * returns a wikipage with the pagename given or false if does not exists
	 * @param wiki $wiki: wiki object
	 * @param String $pagename
	 * @param int $version=false (last version if false)
	 * @param int $groupid=0
	 * @param int $owner=0
	 * @return wikipage or null
	 */
	function get_wiki_page_by_pagename ($wiki,$pagename,$version=false,$groupid=0,$owner=0) {
		return $this->persistencemanager->get_wiki_page_by_pagename ($wiki,$pagename,$version,$groupid,$owner);
	}

    /**
     * Returns the page or false if it doesn't exist.
     *
     * @param   wiki_pageid     $pageid
     *
     * @return  wiki_page|false
     */
    function get_wiki_page_by_pageid($pageid) {
        return $this->persistencemanager->get_wiki_page_by_pageid($pageid);
    }
    
    /**
     * Returns true if page exists, false otherwise.
     *
     * @param   wiki_pageid     $pageid
     *
     * @return  bool
     */
    function page_exists($pageid) {
        return $this->persistencemanager->page_exists($pageid);
    }

	/**
     *  Returns the wikipage with the id given, or false if it does not exist.
     *
     *  @param  int $wikipageid
     *  @return wiki_page
     */
	function get_wiki_page_by_id($wikipageid){
		return $this->persistencemanager->get_wiki_page_by_id($wikipageid);
	}
	
	
	
	/**
     *  Saves the wiki data in the database.
     *
     *  @param wiki $wiki
     *
     */
	function save_wiki($wiki){
		return $this->persistencemanager->save_wiki($wiki);
	}

    /**
     * Delete a wiki and all record that depend on it.
     *
     * @param int $wikiid
     *
     * @return bool
     */
    function delete_wiki($wikiid) {
        return $this->persistencemanager->delete_wiki($wikiid);
    }

    /**
     * Sets editables on all pages of the wiki.
     *
     * @param int $wikiid
     * @param int $editable
     */
    function pages_set_editable($wikiid, $editable) {
        $this->persistencemanager($wikiid, $editable);
    }


	/**
     *  Updates the wiki data in the database.
     *
     *  @param wiki $wiki
     *
     */
	function update_wiki($wiki){
		return $this->persistencemanager->update_wiki($wiki);
	}

	/**
     *  Saves the wikipage in the database.
     *
     *  @param Wikipage $wikipage
     */
	function save_wiki_page($wikipage){
        $pageid = $wikipage->pageid();
		if ($this->page_is_editable($pageid)) {
			return $this->persistencemanager->save_wiki_page($wikipage);
		}
		return false;
	}

	/**
     *  It returns a list with the wikis contained in the course given, or false if it does not exist.
     *
     *  @param  int $courseid
     *  @return Array of Wiki
     *
     */
	function get_wiki_list_by_course($courseid){

		// Check permissions

		return $this->persistencemanager->get_wikis_by_course($courseid);

	}

	/**
     *  It returns a list with the wiki pages contained in the wiki given, or false if it does not exist.
     *
     *  @param  int $wiki
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of Wikipage
     *
     */
	function get_wiki_pages_by_wiki($wiki, $groupid = 0, $ownerid = 0){
		if(($wiki->student_mode() == '0') && ($wiki->group_mode() != '0')){
        //only by groups
			return $this->persistencemanager->get_wiki_pages_by_wiki($wiki, $groupid);
		} //by students and their groups
			return $this->persistencemanager->get_wiki_pages_by_wiki($wiki, $groupid, $ownerid);
	}

    /**
     * Get all pages in a wiki.
     *
     * @param   int     $wikiid
     * @return  array
     */
    function get_wiki_pages_by_wikiid($wikiid) {
        return $this->persistencemanager->get_wiki_pages_by_wikiid($wikiid);
    }

	/**
     *  It returns a list with the historic wikipages of a wikipage given, or false if it does not exist.
     *
     *  @param  Wikipage $wikipage
     *  @return Array of Wikipage
     *
     */
	function get_wiki_page_historic($wikipage){

		return $this->persistencemanager->get_wiki_page_historic($wikipage);
	}

    /**
     *  Whether the page can be edited by the user.
     *
     *  @param  wiki_pageid $pageid
     *
     *  @return bool
     */
    function page_is_editable($pageid=null) {
        global $USER, $WS;

        if (!isset($pageid)) {
            $pageid = new wiki_pageid();
        }

        $wiki = $this->get_wiki_by_id($pageid->wikiid);
        $page = $this->get_wiki_page_by_pageid($pageid);

        // Admin role
        if ($this->controlauth->check_permissions('mod/wiki:editanywiki')) {
            return true;
        }

        // Teacher role
        if ($this->controlauth->check_permissions('mod/wiki:editawiki') and $WS->cm->groupmode == 0) {
            return true;
        }

        // Student role
        if ($this->controlauth->check_permissions('mod/wiki:caneditawiki')) {

            // Can't edit wiki
            if (!$page and !$wiki->editable) {
                return false;
            }

            // Can't edit page
            if ($page and !$page->editable) {
                return false;
            }
            // Can't edit wiki of another group
            if ($WS->cm->groupmode != 0 and !$wiki->editanothergroup
                    and in_array($WS->groupmember->groupid,$USER->groupmember)) {
                return false;
            }

            // Can't edit wiki of another student
            if ($wiki->studentmode != 0 and !$wiki->editanotherstudent
                    and $USER->id != $WS->member->id) {
                return false;
            }

            return true;
        }
        
        return false;
	}

	/**
     *  It returns an array of the Name ande Lastname of users who are owners
     *  of a wikipage contained in the wiki given, ordred alphabetically.
     *
     *  @param  wiki $wiki
	 *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of String
     *
     */
	function get_wiki_page_owners_of_wiki($wiki, $groupid = 0, $ownerid = 0){
		if(($wiki->student_mode() == '0') && ($wiki->group_mode() != '0')){
        //only by groups
			return $this->persistencemanager->get_wiki_page_owners_of_wiki($wiki, $groupid);
		} //by students and their groups
			return $this->persistencemanager->get_wiki_page_owners_of_wiki($wiki, $groupid, $ownerid);
	}

	/**
     *  It returns an array of the id users who are owners of a wikipage
     * contained in the wiki given.
     *
     *  @param  int $wikiid
     *  @return Array of integer
     *
     */
	function get_wiki_page_owners_ids_of_wiki($wikiid){

		return $this->persistencemanager->get_wiki_page_owners_ids_of_wiki($wikiid);
	}

	/**
     *  It returns a list with the names of the wikipages contained
     *  in the wiki given, or false if it does not exist.
     *
     *  @param  wiki $wiki
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of String
     *
     */
	function get_wiki_page_names_of_wiki($wiki, $groupid = 0, $ownerid = 0){
		$cm = get_coursemodule_from_instance('wiki',$wiki->id);
		if(($wiki->studentmode == '0') && ($cm->groupmode != '0')){
        //only by groups
			return $this->persistencemanager->get_wiki_page_names_of_wiki($wiki, $groupid);
		} //by students and their groups
			return $this->persistencemanager->get_wiki_page_names_of_wiki($wiki, $groupid, $ownerid);
	}
	
	/**
     *  It returns a list with the names of the wikipages contained
     *  in the wiki given, or false if it does not exist.
     *
     *  @param  wiki $wiki
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of String
     *
     */
	function get_last_wiki_page_version($wikipage ,$groupid = 0, $ownerid = 0){
			if(($ownerid == '0') && ($groupid!= '0')){
	        //only by groups
				return $this->persistencemanager->get_last_wiki_page_version($wikipage, $groupid);
			} //by students and their groups
				return $this->persistencemanager->get_last_wiki_page_version($wikipage, $groupid, $ownerid);
		}
	
	/**
     *  It returns all wikipages ordered by numeber of hits.
     *
     *  @param int $num=-1: results number
     *  @param  wiki $wiki
     *  
     *  @return Array of stdClass
     *
     */		
	function get_wiki_most_viewed_pages($num=-1,$wiki=false){
		if (!$wiki) $wiki = wiki_param ('dfwiki');
		$pages = $this->persistencemanager->get_most_viewed_pages($wiki->id);
		//built array
    	$lastpage = '[[]]';
    	$res = array();
    	foreach ($pages as $page){
    		if ($lastpage != $page->pagename && $num!=0){
    			$res[] = $page->pagename;
    			$lastpage = $page->pagename;
    			$num--;
    		}
    	}
    	return $res;
	}
	
	/**
     *  It returns all wikipages ordered by date of creation.
     *  @param int $num=-1 number of results (-1 for all)
     *  @param  wiki $wiki
     *  
     *  @return Array of stdClass
     *
     */
	function get_wiki_newest_pages($num=-1,$wiki=false){
		if (!$wiki) $wiki = wiki_param ('dfwiki');
		$pages = $this->persistencemanager->get_wiki_newest_pages($wiki->id);
		//built array
    	$lastpage = '[[]]';
    	$res = array();
    	foreach ($pages as $page){
    		if ($lastpage != $page->pagename && $num!=0){
    			$res[] = $page->pagename;
    			$lastpage = $page->pagename;
    			$num--;
    		}
    	}
    	return $res;
	}
	
	/**
     *  It returns all wikipages ordered by number of version.
     *
     *  @param  wiki $wiki
     *  
     *  @return Array of stdClass
     *
     */
	function get_wiki_most_uptodate_pages($num=-1,$wiki=false){
		if (!$wiki) $wiki = wiki_param ('dfwiki');
		$pages = $this->persistencemanager->get_wiki_most_uptodate_pages($wiki->id);
		//built array
    	$res = array();
    	foreach ($pages as $page){
    		if ($num!=0 && !in_array($page->pagename,$res)){
    			$res[] = $page->pagename;
    			$num--;
    		}
    	}
    	return $res;
		
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
        return $this->persistencemanager->get_wiki_pages_user_outline($username, $wikiid);
    }
	
	/**
	 * return orphaned pages of a wiki
	 * @param int $num=-1: number opf results
	 * @return array of stdClass
	 */
	function get_wiki_orphaned_pages($num = -1){
    	return $this->persistencemanager->get_wiki_orphaned_pages($num);
	}
	
	/**
	 * returns all synonyms of a page
	 * @param String $name: pagename 
	 */
	function get_wiki_synonyms($name=false){
		return $this->persistencemanager->get_wiki_synonyms($name);
	}	
	
	/**
     *  returns an array with the wanted pages
     * 
     *  @param  Array $pages: Pages of wiki
     */	
	function get_wiki_wanted_pages(){
		$wiki = wiki_param ('dfwiki');
		$groupmember = wiki_param('groupmember');
		$pages = $this->get_wiki_page_names_of_wiki($wiki, $groupmember->groupid);;
		$res = $this->persistencemanager->get_wiki_wanted_pages($pages);
		if (!$res) return array();
		return $res;
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
	function wiki_get_real_pagename($name, $id=false, $groupid=null, $ownerid=null){
		return $this->persistencemanager->wiki_get_real_pagename($name, $id, $groupid, $ownerid);
	}
	
	/**
     *  Changes the wiki page name by the $pagename given.
     *
     *  @param  String    $pagename
     *  @param  Wiki_page $wikipage
     *  
     */	
	function change_wiki_page_name($pagename, $wikipage){

		if ($this->controlauth->check_permissions('mod/wiki:editawiki')){
			return $this->persistencemanager->change_wiki_page_name($pagename, $wikipage);
		}
		return false;
	}

	/**
     *  Forbides the edition of the wikipage, updating all the historic to avoid edition too.
     *
     *  @param  Wiki_page $wikipage
     *  
     */	
	function disable_wiki_page_edition($wikipage){

		if ($this->controlauth->check_permissions('mod/wiki:editawiki')){
			return $this->persistencemanager->disable_wiki_page_edition($wikipage);
		}
		return false;
	}
	
	/**
     *  Restores the version of the wiki page.
     *
     *  @param  Wiki_page  	$wikipage
     *  
     */	
	function restore_version($wikipage){

		if ($this->controlauth->check_permissions('mod/wiki:editawiki')){
			return $this->persistencemanager->save_wiki_page($wikipage);
		}
		return false;
	}
	
	/**
     *  return an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
     *
     *  @param  String 	$user
     *  @param  Integer $wikiid
     *  
     */	
	function user_activity_in_wiki($user, $wikiid=false){
		if (!$wikiid) {
			$wikiid = wiki_param('dfwiki');
			$wikiid = $wikiid->id;
		}
		return $this->persistencemanager->user_activity_in_wiki($user, $wikiid);
	}
	
	/**
     *  Creates a XML file with all the data of the wiki in the folder given.
     *
     *  @param  Wiki 	$wiki
     *  @param  String	$folder
     *  
     */	
	function export_wiki_to_XML($wiki, $folder = 'exportedfiles'){
		
		if ($this->controlauth->check_permissions('mod/wiki:adminactions')){
			return $this->persistencemanager->export_wiki_to_XML($wiki, $folder = 'exportedfiles');
		}
		return false;
	}
	
	/**
     *  Obtains the discussion pages of a wikipage.
     *
     *  @param  Wikipage 	$wikipage
     *  @return Array of Wikipage
     *  
     */	
	function get_discussion_page_of_wiki_page($wikipage){
		
		return $this->persistencemanager->get_discussion_page_of_wiki_page($wikipage);
	}
	
	/**
     *  It returns the users ordered by the number of wikipages saved.
     *
     *  @param  wiki $wiki
     *  
     *  @return Array of String
     *
     */
	function get_activest_users($wiki){
		return $this->persistencemanager->get_activest_users($wiki);
	}
	
	/**
	 * returns the maximum version of a page associated with a wiki and a groupid
	 * @param String $pagename
	 * @param int $dfwikiid
	 * @param int $groupid
	 * @return int
	 */
	function get_maximum_value_one ($pagename,$dfwikiid,$groupid) {
		return $this->persistencemanager->get_maximum_value_one ($pagename,$dfwikiid,$groupid);
	}

	/**
	 * returns the maximum version of a page associated with a wiki and a groupid and memberid
	 * @param String $pagename
	 * @param int $dfwikiid
	 * @param int $groupid
	 * @param int $memberid
	 * @return int
	 */
	function get_maximum_value_two($pagename,$dfwikiid,$groupid,$memberid) {
		return $this->persistencemanager->get_maximum_value_two($pagename,$dfwikiid,$groupid,$memberid);
    }

    /**
     * Return the vote ranking of a wiki.
     *
     * @param Integer $wikiid
     *
     * @return Array of stdClass
     */
    function get_vote_ranking($wikiid) {
        return $this->persistencemanager->get_vote_ranking($wikiid);
    }

    /**
     * Vote a page.
     *
     * @param Integer $wikiid
     * @param String $pagename
     * @param Integer $pageversion
     * @param String $username
     */
    function vote_page($wikiid, $pagename, $pageversion, $username) {
        return $this->persistencemanager->vote_page($wikiid, $pagename, $pageversion, $username);
    }

    /**
     * Get array of synonyms of the given page.
     *
     * @param   wiki_pageid     $pageid
     *
     * @return  array   Array of wiki_synonyms.
     */
    function get_synonyms($pageid) {
        $syns = $this->persistencemanager->get_synonyms($pageid);
        foreach ($syns as $syn) {
            $syn->deletable = $this->page_is_editable($syn->pageid);
        }
        return $syns;
    }

    /**
     * Get array of synonyms in the given wiki.
     *
     * @param   int     $wkiid
     *
     * @return  array   Array of wiki_synonyms.
     */
    function get_synonyms_by_wikiid($wikiid) {
        return $this->persistencemanager->get_synonyms_by_wikiid($wikiid);
    }

    /**
     * Delete a synonym.
     *
     * @param   wiki_synonym    $synonym
     */
    function delete_synonym($synonym) {
        if ($this->page_is_editable($synonym->pageid)) {
            return $this->persistencemanager->delete_synonym($synonym);
        }
    }

    /**
     * Insert a synonym.
     *
     * @param   wiki_synonym    $synonym
     */
    function insert_synonym($synonim) {
        return $this->persistencemanager->insert_synonym($synonim);
    }
    
    
    /**
     * return an array of all visibles pages from the
     * first page in the current dfwiki
     * @return array of Strings
     */
    function get_visible_pages (){
    	return $this->persistencemanager->get_visible_pages();
    }

    /**
     * return an array of all visibles pages from a pagename
     * @param String $page: startingpagename
     * @return array of Strings
     */
    function get_visible_pages_from_page($page){
    	return $this->persistencemanager->get_visible_pages_from_page($page);
    }
    
    /**
     * return an array with all pages that have a link to the given one.
     * @param String $pagename
     * @return array of wikipages record object
     */
    function get_wiki_page_camefrom ($pagename){
    	return $this->persistencemanager->get_wiki_page_camefrom ($pagename);
    }
    
    /**
     * return an array with the link in the page.
     * @param String $pagename
     * @return array of Strings
     */
    function get_wiki_page_goesto ($pagename){
		global $WS;
		
    	$pagename = wiki_get_real_pagename ($pagename);

    	$res = array();
    	if ($pageinfo = wiki_page_last_version($pagename)){
    			$res = wiki_internal_link_to_array ($pageinfo->refs);
                $res = wiki_filter_section_links($res);
    	}
    	return $res;
    }
    
    //--------------- EAD IMPORTED PAGES -----------------
    
    //used for visible pages
    var $pagesel = array();

    //used for index level page
    var $pagelev = false;

    //return the index level of a page
    function level ($pagename, $origin = false){
    	global $WS;
    	if ($origin===false){
    		$origin = $WS->dfwiki->pagename;
    	}
    	$this->pagesel = array();
    	//900000 is a big initialization
    	$this->pagelev = 900000;

    	$this->built_level ($pagename, $origin,0);

    	return $this->pagelev;

    }

    function built_level ($tofind, $page, $level){
    	global $WS,$CFG;

    	//printed links array
    	$printed_links = array();

    	//search in vector
    	if (!in_array($page,$this->pagesel)){

    		//put in vector
    		$this->pagesel[] = $page;

    		if ($tofind==$page ){
    			if ($this->pagelev>$level){
    				$this->pagelev = $level;
    			}
    		} else {
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
    						$this->built_level ($tofind,$link,$level+1);
    					}
    				}
    			}
    		}
    	}
    }

    //----------------------- USER EAD FUNCTIONS

    //this function returns the ranking of most active users in the current wiki
    function activestusers($num = -1){

    		//get the page list of active users
    		$users = $this->active_users();

    		//built list
    		$active = array();
    		foreach ($users as $user){
    			if ($num!=0){
    				$active[$user] = $this->user_num_activity($user);
    				$num--;
    			}
    		}

    		//order list.
    		arsort ($active);
    		//built result
    		foreach ($active as $user=>$activity){
    			$res[] = $user;
    		}
    		return $res;
    }

    /**
	 * Return an array with all active users
	 * 
	 * @param Wiki	$wiki
	 * @param int $groupid=0
	 * 
	 * @return Integer 
	 */
    function active_users($wiki=false, $groupid = 0){
    	if (!$wiki) {
			$wiki = wiki_param('dfwiki');
		}
		if (!$groupid) {
			$groupid =  wiki_param('groupmember');
			$groupid = $groupid->groupid;
		}
    	return $this->persistencemanager->active_users($wiki, $groupid);
    }

	//return the number of participations in the current dfwiki EXCLUDING THE DISCUSSIONS.
	function user_num_activity ($userid,$wiki=false){
		global $WS,$CFG;
		if (!$wiki) {
			$wiki = wiki_param('dfwiki');
		}
		return $this->persistencemanager->user_num_activity($userid,$wiki);
		
        /*$quer = 'SELECT COUNT(*) AS num
                FROM '. $CFG->prefix.'wiki_pages
                WHERE author="'.$user.'" AND dfwiki='.$WS->dfwiki->id.' AND pagename NOT LIKE \'discussion:%\'';
		
		if ($act = get_record_sql($quer)){
			$res = $act->num;
		} else {
			$res = 0;
		}
		return $res;*/
	}

	/**
	 * return an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
	 * @param int $user
	 * @return array of Strings
	 */
	function user_activity ($user){
		$dfwiki = wiki_param('dfwiki');
		$res =  $this->user_activity_in_wiki($user, $dfwiki->id);
		if (!$res) return array();
		return $res;
	}


    /** Get list of groups in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_groups($courseid) {
        return $this->persistencemanager->get_course_groups($courseid);
    }


    /** Get list of members in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_members($courseid) {
        return $this->persistencemanager->get_course_members($courseid);
    }

    /**
     * Get list of teachers in a course.
     *
     * @param int $courseid
     *
     * @return array
     */
    function get_course_teachers($courseid) {
        $this->persistencemanager->get_course_teachers($courseid);
    }

    /**
     * Incrment the number of hits of the page.
     *
     * @param   wiki_pageid     $pageid
     * @return  bool
     */
    function increment_page_hits($pageid) {
        return $this->persistencemanager->increment_page_hits($pageid);
    }

    /**
     * Update the evaliuation of a page.
     *
     * @param   int     $pageid
     * @param   string  $evaluation
     */
    function update_page_evaluation($pageid, $evaluation) {
        return $this->persistencemanager->update_page_evaluation($pageid, $evaluation);
    }

    /**
     * Return array of page versions in the wiki.
     *
     * @param   int     $wikiid
     * @return  array of int
     */
    function get_wiki_page_versions($wikiid) {
        return $this->persistencemanager->get_wiki_page_versions($wikiid);
    }

    /**
     * Obtains an editing lock on a wiki page.
     *
     * @param   int     $wikiid     ID of wiki object.
     * @param   string  $pagename   Name of page.
     *
     * @return  array   Two-element array with a boolean true (if lock has been obtained)
     *   or false (if lock was held by somebody else). If lock was held by someone else,
     *   the values of the wiki_locks entry are held in the second element; if lock was
     *   held by current user then the the second element has a member ->id only.
     */
    function obtain_lock($wikiid, $pagename) {
        global $USER;

        // Check for lock
        $alreadyownlock = false;
        if ($lock = $this->persistencemanager->get_lock($wikiid, $pagename)) {
            // Consider the page locked if the lock has been confirmed within WIKI_LOCK_PERSISTENCE seconds
            if ($lock->lockedby == $USER->id) {
                // Cool, it's our lock, do nothing except remember it in session
                $lockid = $lock->id;
                $alreadyownlock = true;
            } else if (time() - $lock->lockedseen < WIKI_LOCK_PERSISTENCE) {
                return array(false, $lock);
            } else {
                // Not locked any more. Get rid of the old lock record.
                if (! $this->persistencemanager->delete_lock($lock->id)) {
                    error('Unable to delete lock record');
                }
            }
        }

        // Add lock
        if (! $alreadyownlock) {
            $time = time();
            if (! $lockid = $this->persistencemanager->insert_lock($wikiid, $pagename,
                    $USER->id, $time, $time)) {
                error('Unable to insert lock record');
            }
        }

        // Store lock information in session so we can clear it later
        if (!array_key_exists(SESSION_WIKI_LOCKS, $_SESSION)) {
            $_SESSION[SESSION_WIKI_LOCKS] = array();
        }
        $_SESSION[SESSION_WIKI_LOCKS][$wikiid.'_'.$pagename] = $lockid;
        $lockdata = new StdClass;
        $lockdata->id = $lockid;
        return array(true, $lockdata);
    }

    /**
     * If the user has an editing lock, releases it. Has no effect otherwise.
     * Note that it doesn't matter if this isn't called (as happens if their
     * browser crashes or something) since locks time out anyway. This is just
     * to avoid confusion of the 'what? it says I'm editing that page but I'm
     * not, I just saved it!' variety.
     * @param int $wikiid ID of wiki object.
     * @param string $pagename Name of page.
     */
    function release_lock($wikiid,$pagename) {
        if (!array_key_exists(SESSION_WIKI_LOCKS, $_SESSION)) {
            // No locks at all in session
            return;
        }

        $key = $wikiid.'_'.$pagename;

        if (array_key_exists($key,$_SESSION[SESSION_WIKI_LOCKS])) {
            $lockid = $_SESSION[SESSION_WIKI_LOCKS][$key];
            unset($_SESSION[SESSION_WIKI_LOCKS][$key]);
            if (! $this->persistencemanager->delete_lock($lockid)) {
                error("Unable to delete lock record.");
            }
        }
    }


    /**
     * Get user information.
     *
     * @param   string  $user
     *
     * @return  stdClass
     */
    function get_user_info($username) {
        return $this->persistencemanager->get_user_info($username);
    }

    /**
     * Get user information.
     *
     * @param   int  $userid
     *
     * @return  stdClass
     */
    function get_user_info_by_id($userid) {
        return $this->persistencemanager->get_user_info_by_id($userid);
    }


    /**
     * Get authors of a wiki page.
     *
     * @param   wiki_pageid     $pageid
     * @return  array of string
     */
    function get_wiki_page_authors($pageid=null) {
        if (empty($pageid)) {
            $pageid = new wiki_pageid();
        }
        return $this->persistencemanager->get_wiki_page_authors($pageid);
    }

}
?>
