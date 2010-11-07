<?php
/**
 * This file contains wiki persistor interface.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: wiki_persistor_interface.php,v 1.28 2007/07/13 12:12:18 davcastro Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package API
 */

// This class is an interface
class wiki_persistor_interface{

	/**
     *  Saves the wikipage in the database.
     *
     *  @param Wikipage $wikipage
     *
     */
	function save_wiki($wiki){
	}

	/**
     *  Saves the record of the wikipage in the database.
     *  Returns the wikipage id if correct or false if failed.
     *
     *  @param  stdClass $record
	 *  @return int
     */
	function save_wiki_page($wikipage){
	}

	/**
     *  Returns the record of the wiki with the id given.
     *
     *  @return Wiki
     */
	function get_wiki_by_id($wikiid){
	}

	/**
     *  Returns the record of the wikipage with the id given.
     *
     *  @return Wikipage
     */
	function get_wiki_page_by_id($wikipageid){
	}

	/**
     *  Get array of record of the wikis by course id.
     *
     *  @param String $courseid
     *  @return array
     */
	function get_wikis_by_course($courseid){
	}

	/**
     *  It returns an array with the records of the wiki pages contained in the wiki given.
     *
     *  @param  String $wikiid
     *  @return Array of Wikipage
     *
     */
	function get_wiki_pages_by_wiki($wikiid, $groupid, $ownerid){
	}

	/**
     *  It returns an array with the records of the historic wikipages of a wikipage given.
     *
     *  @param  String $pagename. 	The name of the wikipage.
     *  @param  int $wikiid			The id of the wiki.
     *  @param  int $groupid		The group of the wikipage.
     *  @param  int $ownerid		The owner of the wikipage.
     *  @return Array of Wikipage
     *
     */
	function get_wiki_page_historic($pagename,$dfwikiid,$groupid,$ownerid){
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
	}

	/**
     *  It returns a list of the id users who are owners of a wikipage contained in the wiki.
     *
     *  @param  int $wikiid
     *  @return Array of integer
     *
     */
	function get_wiki_page_owners_ids_of_wiki($wikiid){
	}

	/**
     *  It returns an array of recrods that contains the page names of the wiki pages
     *  in the wiki, ordered by name.
     *
     *  @param  int $wikiid
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of stdClass
     *
     */
	function get_wiki_page_names_of_wiki($wikiid, $groupid, $ownerid){
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
	}
	
	/**
     *  It returns all wikipages ordered by numeber of hits.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass or false if there are no pages
     *
     */	
	function get_most_viewed_pages($wikiid){
	}
	
	function get_wiki_newest_pages(){
		
	}
	
	function get_wiki_most_uptodate_pages(){
		
	}
	
	function get_wiki_orphaned_pages(){
		
	}
	
	function get_wiki_synonyms(){
		
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
	 * @param name: name of the page.
	 * @param id: dfwiki instance id, current dfwiki default
	 * @return String
	 */
	function wiki_get_real_pagename ($name,$id=false){
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
     */	
	function change_wiki_page_name($pagename_new, $pagename_old, $wikiid, $groupid, $ownerid ){
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
	}	

	/**
     *  Returns an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
     *
     *  @param  String 	$user
     *  @param  Integer $wikiid
     *  
     */	
	function user_activity_in_wiki($user, $wikiid){
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
    function active_users($wikiid, $groupid){
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
	}

    /**
     * Return the vote ranking of a wiki.
     *
     * @param Integer $wikiid
     *
     * @return Array of stdClass
     */
    function get_vote_ranking($wikiid) {}


    /**
     * Wheter the given vote exists.
     *
     * @param Integer $wikiid
     * @param String $pagename
     * @param Integer $pageversion
     * @param String $username
     */
    function vote_exists($wikiid, $pagename, $pageversion, $username) {}

    /**
     * Insert a new vote.
     *
     * @param Integer $wikiid
     * @param String $pagename
     * @param Integer $pageversion
     * @param String $username
     */
    function insert_vote($wikiid, $pagename, $pageversion, $username) {}
}
?>
