<?php

/**
 * Accept settings.
 *
 * Authors:
 *		Victor Bautista (victor [at] sinkia [dt] com)
 *
 * @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 * @package   mod_livewebteaching
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
 * This file incorporates work covered by the following copyright and permission notice: 
 * 
 * Original Author:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @copyright 2010 Blindside Networks
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

$settings->add( new admin_setting_configtext( 'lwt_username', get_string( 'lwt_username', 'livewebteaching' ), get_string( 'lwt_username_info', 'livewebteaching'), 'Check your My Account at Livewebteaching.com for the Username.' ));
$settings->add( new admin_setting_configtext( 'lwt_apikey', get_string( 'lwt_apikey', 'livewebteaching' ), get_string( 'configsecuritysalt', 'livewebteaching' ), 'Check your My Account at Livewebteaching.com for the API Key.' ) );

?>
