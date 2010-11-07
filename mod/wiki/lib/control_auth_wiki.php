<?php
//$Id: control_auth_wiki.php,v 1.9 2008/01/16 12:15:30 pigui Exp $

/**
 * This file contains wiki control_auth_wiki class.
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: control_auth_wiki.php,v 1.9 2008/01/16 12:15:30 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */
 
require_once ($CFG->dirroot.'/mod/wiki/lib/authorization_adapter.php');
require_once ($CFG->dirroot.'/mod/wiki/lib/authentication_adapter.php');
		
class control_auth_wiki{
	
	var $authorization;
	var $authentication;
	
	function control_auth_wiki(){
		$this->authentication = new authentication_adapter();
		$this->authorization = new authorization_adapter();
	}

	/**
     *  Checks if the user is authenticated and authorized to do 
     *  the action ($capability) in the resource is trying to access.
     *  The parameter $capability has to be an already defined capability of the wiki.
     *  
     *  @param  integer $capability
     *  
     *  @return boolean
     *  @deprecated This function is not implemented correctly
     */
	
	function check_permissions($capability){
		
		if ($this->authentication->is_user_authenticated()){
			$context = $this->authentication->get_context();
			if(isset($context))
				return $this->authorization->check_user_authorized($capability, $context);
		}
		return false;
	}	
	
		/**
     *  Checks if the user($agentid) is authenticated and authorized to do 
     *  the action ($functionid) in the resource is trying to access($qualifierid).
     *  
     *  @param  integer agentid
     *  @param  integer qualifierid
     *  @param  integer functionid
     *  
     *  @return boolean
     *  
     *  @deprecated This function is not implemented correctly
     *  @todo: delete deprecated function, rename this one, change all function calls.
     */
	function new_check_permissions($agentid, $qualifierid, $functionid){

		return false;
	}

	
	
}

?>