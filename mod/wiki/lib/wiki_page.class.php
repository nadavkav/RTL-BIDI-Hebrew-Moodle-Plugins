<?php
//$Id: wiki_page.class.php,v 1.22 2008/03/14 13:12:46 gonzaloserrano Exp $

/**
 * This file contains wiki_page class
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: wiki_page.class.php,v 1.22 2008/03/14 13:12:46 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */ 
 
require_once ($CFG->dirroot.'/lib/dmllib.php');
require_once ($CFG->dirroot.'/mod/wiki/locallib.php');
define('WIKIPAGEID', 1);
define('PAGERECORD', 2);

class wiki_page {
	
	var $id;
	var $pagename;
	var $version;
	var $content;
	var $author;
	var $userid;
	var $created;
	var $lastmodified;
	var $refs;
	var $hits;
	var $editable;
	var $highlight;
	var $dfwiki;
	var $editor;
	var $groupid;
	var $ownerid;

	/**
     *  Constructor of the class. 
     *
     *  @param integer $type. This param must be WIKIPAGEID or PAGERECORD. It is used to indicate 
     *                    the type of the parameter $param.
     *  @param integer / record $param 
     */	
	function wiki_page($type,$param){
		
		// If the parameter type is WIKIPAGEID, we need to obtain the record from the
		// database and inicialize all the attributes of the class with it's value.
		if ($type == WIKIPAGEID){
			// Get the record.
			$record = get_record("wiki_pages","id",$param);
			// Inicialize attributes with the value of the record.
			$this->id = $record->id;
			$this->pagename = $record->pagename;
			$this->version = $record->version;
			$this->content = $record->content;
			$this->author = $record->author;
			$this->userid = $record->userid;
			$this->created = $record->created;
			$this->lastmodified = $record->lastmodified;
			$this->refs = $record->refs;
			$this->hits = $record->hits;
			$this->editable = $record->editable;
			$this->highlight = $record->highlight;
			$this->dfwiki = $record->dfwiki;
			$this->editor = $record->editor;
			$this->groupid = $record->groupid;
			$this->ownerid = $record->ownerid;
		// If the parameter type is PAGERECORD, we already have the record, so we only need to 
		// inicialize all the attributes of the class with it's value.
		} else if ($type == PAGERECORD){
			// Inicialize the attributes with the value of the record.
			$this->id = isset($param->id) ? $param->id : null;
			$this->pagename = $param->pagename;
			$this->version = $param->version;
			$this->content = $param->content;
			$this->author = $param->author;
			$this->userid = $param->userid;
			$this->created = $param->created;
			$this->lastmodified = $param->lastmodified;
			$this->refs = $param->refs;
			$this->hits = $param->hits;
			$this->editable = $param->editable;
			$this->highlight = isset($param->highlight) ? $param->highlight : 0;
			$this->dfwiki = $param->dfwiki;
			$this->editor = $param->editor;
			$this->groupid = $param->groupid;
			$this->ownerid = $param->ownerid;
			} else{
			error("The parameter type must be WIKIPAGEID or PAGERECORD");
		}
		
	}
	
	
	/// Get methods
	
	/**
     *  Database record of the wiki page.
     *
     *  @return stdClass
     */
    function wiki_page_to_record() {
        
		$record = new stdClass();			
		
		$record->pagename = $this->pagename;
		$record->version = $this->version;
		$record->content = $this->content;
		$record->author = $this->author;
		$record->userid = $this->userid;
		$record->created = $this->created;
		$record->lastmodified = $this->lastmodified;
		$record->refs = $this->refs;
		$record->hits = $this->hits;
		$record->editable = $this->editable;
		$record->highlight = $this->highlight;
		$record->dfwiki = $this->dfwiki;
		$record->editor = $this->editor;
		$record->groupid = $this->groupid;
		$record->ownerid = $this->ownerid;
	
		return $record;        
    }
	
	/**  
	 * Id of the wiki page.
     *
     *  @return integer
     */
    function id() {
        return $this->id;
    }
	
	/**
	 * Wiki page name. It strips the slashes.
	 * 
	 * @return string
	 */
	function page_name(){
		return stripslashes($this->pagename);
	}
	
	/**
	 * Version number of the wiki page.
	 * 
	 * @return integer
	 */
	function version(){
		return $this->version;
	}
	
	/**
	 * Integral content of the wiki page. It strips the slashes.
	 * 
	 * @return string
	 */
	function content(){
		return stripslashes($this->content);
	}
	
	/**
	 * User name of the author of version of a wiki page.
	 * 
	 * @return string
	 */
	function author(){
		return $this->author;
	}
	
	/**
	 * User id of the author of version of a wiki page.
	 * 
	 * @return integer
	 */
	function user_id(){
		return $this->userid;
	}
	
	/**
	 * Absolute time in seconds since first version of the wiki page.
	 * 
	 * @return integer
	 */
	function created(){
		return $this->created;
	}
	
	/**
	 * Absolute time in seconds of the last time wiki page has been modified. That is time of last version.
	 * 
	 * @return integer
	 */
	function last_modified(){
		return $this->lastmodified;
	}
	
	/**
	 * List of links inside the wiki page.
	 * 
	 * @return string. This list is save with character '|' which separates the name of the destination page.
	 */
	function refs(){
		return $this->refs;
	}
	
	/**
	 * Number of times that wiki page has been visited (hits).
	 * 
	 * @return integer
	 */
	function hits(){
		return $this->hits;
	}
	
	/**
	 * Whether the wiki page can be edit by students.
	 * 
	 * @return boolean
	 */
	function editable(){
		return $this->editable != 0;
	}
	
	/**
	 * Whether the wiki page is in highlight.
	 * 
	 * @return boolean
	 */
	function high_light(){
		return $this->highlight != 0;
	}
	
	/**
	 * dfwiki number of the table 'mdl_wiki' where is the wiki intance.
	 * 
	 * @return integer
	 */
	function dfwiki(){
		return $this->dfwiki;
	}
	
	/**
	 * Wiki page editor name. Only 3 diferent names: 'dfwiki','ewiki','htmleditor'.
	 * 
	 * @return string
	 */
	function editor(){
		return $this->editor;
	}
	
	/**
	 * Group id which wiki page belongs.
	 * 
	 * @return integer
	 */
	function group_id(){
		return $this->groupid;
	}
	
	/**
	 * Wiki page owner id.
	 * 
	 * @return integer
	 */
	function owner_id(){
		return $this->ownerid;
	}
	
	///Set Methods
	 
	 /**
	  * Sets the id of the wiki page
	  * 
	  * @param integer $id. New id to asign.
	  */
	 function set_id($id){
		 $this->id = $id;
	 }
	 
	 /**
	  * Sets the name of the wiki page. Adds slashes to the page name.
	  * 
	  * @param string $page_name. New name to asign to the wiki page.
	  */
	 function set_page_name($page_name){
		 $this->pagename = addslashes($page_name);
	 }
	 
	 /**
	  * Sets the version of the wiki page.
	  * 
	  * @param int $version. New number of version of the wiki page.
	  */
	 function set_version($version){
		 $this->version = $version;
	 }
	 
	 /**
	  * Adds 1 to the actual version of the wiki page.
	  * 
	  * @return integer. Returns new number of version.
	  */
	 function inc_version(){
		 $this->version = $this->version + 1;
		 return $this->version;
	 }
	 
	 /**
	  * Sets the integral content of the wiki page.
	  * Updates the attribute refs with the internal links of the wikipage.
	  * Adds slashes to the page name.
	  * 
	  * @param string $content. New content to asign to the wiki page.
	  */
	 function set_content($content){
		// clean internal links of the page
        $links_refs  = wiki_sintax_find_internal_links($content);
        $links_clean = wiki_clean_internal_links($links_refs);
        $content     = wiki_set_clean_internal_links($content, $links_refs, $links_clean);
        $this->refs  = wiki_internal_link_to_string($links_clean);

        $this->content = addslashes($content);
	 }
	 
	 /**
	  * Sets the user name of the author of version of a wiki page.
	  * 
	  * @param string $author. This param is the new name of the author of the wiki.
	  */
	 function set_author($author){
		 $this->author = $author;
	 }
	 
	 /**
	  * Sets the user id of the author of version of a wiki page.
	  * 
	  * @param integer $user_id. This param is the id to asign to the user id.
	  */
	 function set_user_id($user_id){
		 $this->userid = $user_id;
	 }
	 
	 /**
	  * Sets the absolute time in seconds since first version of the wiki page.
	  * 
	  * @param integer $created. This param is the new absolut time in seconds to asign to wiki page created time.
	  */
	 function set_created($created){
		 $this->created = $created;
	 }
	 
	 /**
	  * Sets the absolute time in seconds of the last time wiki page has been modified. That is time of last version.
	  * 
	  * @param integer $last_modified. This param is the new absolut time in seconds of the last modification of the 
	  * wiki page.
	  */
	 function set_last_modified($last_modified){
		 $this->lastmodified = $last_modified;
	 }
	 
	 
	 /**
	  * Sets the list of links inside the wiki page.
	  * 
	  * @param string $refs. This param contains the news references of the wiki page.
	  */
	 function set_refs($refs){
		 $this->refs = $refs;
	 }
	 
	 
	 /**
	  * This function adds a reference to the list of links inside wiki page.
	  * 
	  * @param string $ref. This param is the reference to add to the wiki page.
	  */
	 function add_refs($ref){
		 $this->refs= $this->refs + "|" + $ref;
	 }
	 
	 /**
	  * This function sets number of times that wiki page has been visited (hits).
	  * 
	  * @param integer $hits.
	  */
	 function set_hits($hits){
		 $this->hits = $hits;
	 }
	 
	 /**
	  * This function sets whether the wiki page can be edit by students
	  * 
	  * @param boolean $editable.
	  */
	 function set_editable($editable){
		 $this->editable = $editable;
	 }
	 
	 /**
	  * This function sets the group id which wiki page belongs
	  * 
	  * @param integer $group_id. This param is the new id of the wiki page group.
	  */
	 function set_group_id($group_id){
		 $this->groupid = $group_id;
	 }
	 
	 /**
	  * This function sets wiki page owner id.
	  * 
	  * @param integer $owner_id. This param is the new if of the wiki page owner.
	  */
	 function set_owner_id($owner_id){
		 $this->ownerid = $owner_id;
	 }
	 
     /**
      * Get pageid.
      *
      * @return wiki_pageid
      */
     function pageid() {
        return new wiki_pageid($this->dfwiki, $this->pagename, null. $this->groupid, $this->ownerid);
     }

}
?>
