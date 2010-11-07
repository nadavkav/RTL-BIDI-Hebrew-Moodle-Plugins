<?php

/**
 * This file contains wiki persistance interface.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: persistence_manager_interface.php,v 1.28 2008/01/16 12:15:30 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package API
 */

 //This class is an interface
class persistence_manager_interface{

	/**
     *  Saves the wiki in the database.
     *
     *  @param Wiki $wiki
     */
	function save_wiki($wiki){
	}

	/**
     *  Saves the wikipage in the database. Updates the author, user_id, last_modified and owner.
     *  It also sets the version as one more than the las version of the wikipage.
     *  Returns the wikipage id if correct or false if failed.
     *
     *  @param Wikipage $wikipage
	 *  @return int
     */
	function save_wiki_page($wikipage){
	}

	/**
     *  Returns the wiki with the id given, or false if it does not exist.
     *
     *  @return Wiki
     */

	function get_wiki_by_id($wikiid){
	}

	/**
     *  Returns the wikipage with the id given, or false if it does not exist.
     *
     *  @return Wikipage
     */

	function get_wiki_page_by_id($wikipageid){
	}
	
	/**
	 * returns a wikipage with the pagename given or false if does not exists
	 * @param wiki $wiki: wiki object
	 * @param String $pagename
	 * @param int $version=false (last version if false)
	 * @param int $groupid=0
	 * @param int $ownerid=0
	 * @return wikipage or null
	 */
	function get_wiki_page_by_pagename ($wiki,$pagename,$version=false,$groupid=0,$ownerid=0) {
	}


	/**
     *  Get array of wikis by course id or false if course does not exist.
     *
     *  @param String $courseid
     *  @return array
     */
	function get_wikis_by_course($courseid){
	}

	/**
     *  It returns a list with the wikipages contained in the wiki
     *  given, or false if it does not exist.
     *
     *  @param  String $wikiid
     *  @return Array of Wikipage
     *
     */
	function get_wiki_pages_by_wiki($wikiid, $groupid, $ownerid){
	}

	/**
     *  It returns a list with the historic wikipages of a wikipage
     *  given, or false if it does not exist.
     *
     *  @param  Wikipage $wikipage
     *  @return Array of Wikipage
     *
     */
	function get_wiki_page_historic($wikipage){
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
     *  It returns a list of the id users who are owners of a wikipage contained
     *  in the wiki given, or false if it does not exist.
     *
     *  @param  int $wikiid
     *  @return Array of integer
     *
     */
	function get_wiki_page_owners_ids_of_wiki($wikiid){
	}

	/**
     *  It returns a list with the names of the wikipages contained
     *  in the wiki given, or false if it does not exist.
     *
     *  @param  int $wikiid
     *  @param  int $groupid
     *  @param  int $ownerid
     *  @return Array of stdClass
     *
     */
	function get_wiki_page_names_of_wiki($wiki, $groupid, $ownerid){
	}
	
	/**
     *  It returns highest number of version of the wikipage given.
     *
     *  @param  wikipage $wikipage
     *  @param  integer $groupid
     *  @param  integer $memberid
     *  
     *  @return integer
     *
     */
	function get_last_wiki_page_version($wikipage,$groupid,$ownerid){
	}
	
	/**
     *  It returns all wikipages ordered by numeber of hits.
     *
     *  @param  integer $wikiid
     *  
     *  @return Array of stdClass
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
     * 
     *  @param  Array $pages: Pages of wiki
     */	
	function get_wiki_wanted_pages($pages){
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
     *  Changes the wiki page name by the $pagename given.
     *
     *  @param  String    $pagename
     *  @param  Wiki_page $wikipage
     *  
     */	
	function change_wiki_page_name($pagename, $wikipage){
	}
	
	/**
     *  Makes the wiki page not editable.
     *
     *  @param  Wiki_page $wikipage
     *  
     */	
	function disable_wiki_page_edition($wikipage){
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
     *  Obtains the discussion pages of a wikipage.
     *
     *  @param  Wikipage 	$wikipage
     *  @return Array of Wikipage
     *  
     */	
	function get_discussion_page_of_wiki_page($wikipage){
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
	}
	
	/**
	 * Return an array with page names where user participates EXCLUDING THE DISCUSSION PAGES
	 * 
	 * @param Integer $wikiid
	 * @param String $user
	 * 
	 * @return Array of String
	 */
	function user_activity ($wiki, $user){
	}
	
	/**
	 * returns the maximum version of a page associated with a wiki and a groupid
	 * @param String $pagename
	 * @param int $dfwikiid
	 * @param int $groupid
	 * @return int
	 */
	function get_maximum_value_one ($pagename,$dfwikiid,$groupid) {
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
     * Vote a page.
     *
     * @param Integer $wikiid
     * @param String $pagename
     * @param Integer $pageversion
     * @param String $username
     */
    function vote_page($wikiid, $pagename, $pageversion, $username) {}

}


?>
