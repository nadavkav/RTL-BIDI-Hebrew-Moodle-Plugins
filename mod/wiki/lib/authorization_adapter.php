<?php

/**
 * This file contains authorition_adapter.
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: authorization_adapter.php,v 1.6 2007/06/13 18:30:27 lauramontagut Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */
 
class authorization_adapter{
		
	function authorization_adapter(){

	}

	/**
     *  Checks if the user is authorized to do the action ($capability)
     *   in the resource he is trying to access.
     *  
     *  @param  String $capability
     *  @param  object  $context
     *  
     *  @return boolean
     */
	
	function check_user_authorized($capability, $context){

		return has_capability($capability, $context);
	}	
		
}