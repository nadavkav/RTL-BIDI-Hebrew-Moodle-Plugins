<?php
/*
 *  Capabilities
 *  
 *  @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 *  @package   mod_livewebteaching
 *  @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 *  
 *  This file is free software: you may copy, redistribute and/or modify it  
 *  under the terms of the GNU General Public License as published by the  
 *  Free Software Foundation, either version 2 of the License, or any later version.  
 *  
 *  This file is distributed in the hope that it will be useful, but  
 *  WITHOUT ANY WARRANTY; without even the implied warranty of  
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU  
 *  General Public License for more details.  
 *  
 *  You should have received a copy of the GNU General Public License  
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.  
 *
 *  This file incorporates work covered by the following copyright and permission notice:
 *  Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 *  @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *  @copyright 2010 Blindside Networks Inc.
 */
$mod_livewebteaching_capabilities = array(
	
	//
	// Ability to join a live session
    'mod/livewebteaching:join' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	//
	// Ability to moderate a live session
	'mod/livewebteaching:moderate' => array(
	    'captype' => 'write',
	    'contextlevel' => CONTEXT_MODULE,
	    'legacy' => array(
	        'teacher' => CAP_ALLOW,
	        'editingteacher' => CAP_ALLOW,
	        'admin' => CAP_ALLOW
	    )
	),
);

?>
