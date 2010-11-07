<?php 

/**
 * This file contains wiki_discussion_page class.
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: wiki_discussion_page.class.php,v 1.6 2008/03/14 13:12:46 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */
 
	global $CFG;
	require_once ($CFG->dirroot.'/mod/wiki/lib/wiki_page.class.php');

class wiki_discussion_page extends wiki_page{

	function wiki_discussion_page($type,$param){
		 parent::wiki_page($type,$param);
	 } 
	
	 /**
	  * Adds more content to the actual content of the discussion page. It's added at the end of the actual content.
 	  * Updates the attribute refs with the internal links of the discussion.
	  * 
	  * @param string $content. New content to add to the wiki page content.
	  */
	 function add_content($content){
        /*
		 *$this->content = $this->content + $content;
		 *$content = $this->content;
         */
       
		// clean internal links of the page
        $links_refs  = wiki_sintax_find_internal_links($content);
        $links_clean = wiki_clean_internal_links($links_refs);
        $content     = wiki_set_clean_internal_links($content, $links_refs, $links_clean);
        $this->refs  = wiki_internal_link_to_string($links_clean);

        $this->content .= $content;
	 }
}
?>	 
