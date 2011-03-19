<?php

/**
 *  Test for when a live session is running.
 *
 *  @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 *  @package   mod_livewebteaching
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
 *  @copyright 2010 Blindside Networks
 *
 *  @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once( "../../config.php" );
require_once("lib.php");

$name = $_GET['name'];

$salt = trim($CFG->lwt_apikey);
$url = trim(trim($CFG->lwt_server),'/').'/';

echo BigBlueButton::getMeetingXML( $name, $url, $salt );
