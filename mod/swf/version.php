<?php // $Id: version.php,v 1.0 2009/01/28 matbury Exp $

/*
*    Copyright (C) 2009  Matt Bury - matbury@gmail.com - http://matbury.com/
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Code fragment to define the version of swf
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author Matt Bury - matbury@gmail.com - http://matbury.com/
 * @version $Id: version.php,v 1.0 2009/01/28 matbury Exp $
 * @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
 * @package swf
 **/

$module->version  = 2009121000;  // The current module version (Date: YYYYMMDDXX)
$module->cron     = 0;           // Period for cron to check this module (secs)

?>