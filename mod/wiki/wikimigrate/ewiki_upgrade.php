<?php

/**
 * This file contains necessary SQL sentences to upgrade old wiki
 * using XMLDB libraries.
 * All this process sets ewiki tables to last known version to
 * start migration to nwiki.
 * 
 * 
 * This file is a copy of /mod/wiki/db/upgrade.php distributed 
 * with Moodle 1.8.2. It makes nothing. 
 * 
 * 
 * If Moodle changes ewiki tables this file must be updated. 
 * Appending new sentences to the end of file would be enough. 
 * 
 * 
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: ewiki_upgrade.php,v 1.2 2007/10/15 08:12:31 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Setup
 */
 
 
// This file keeps track of upgrades to 
// the wiki module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_ewiki_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    return $result;
}

?>
