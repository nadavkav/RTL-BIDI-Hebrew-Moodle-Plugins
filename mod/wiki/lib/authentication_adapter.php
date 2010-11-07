<?php

/**
 * This file contains wiki authentication_adapter
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: authentication_adapter.php,v 1.7 2007/06/13 20:30:23 ester_galimany Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */

class authentication_adapter{




	/**
     *  Obtains the context instance from moodle.
     *
     *  @uses $WS
     *  @return context
     */

	function get_context(){
		global $WS;

		$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

		return $context;
	}

	/**
     *  Whether the user is loged in.
     *
     *  @return boolean
     */

	function is_user_authenticated(){

		return isloggedin();
	}

}