<?php

/**
 * This file contains block_wiki_new_page functions.
 * @deprecated version - 23/08/2007
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: lib.php,v 1.6 2007/08/23 12:24:00 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */
 
function wiki_block_new_page(){

	$name = optional_param('dfformname',NULL,PARAM_FILE);
    if (isset($name)){
    	wiki_param('page',$name);
    }
    wiki_main_setup();
}
?>
