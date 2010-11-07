<?PHP

/**
 * This file contains wiki MySQL upgrade process
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: mysql.php,v 1.8 2007/10/15 08:12:31 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Setup
 */

function wiki_upgrade($oldversion) {
/// This function does anything necessary to upgrade
/// older versions to match current functionality

    global $CFG, $db;

    $result = true;

    // Checks if the current version installed in the system is old wiki (ewiki) or is new wiki (nwiki)
    // We can distinguish ewiki from nwiki checking wiki_synonymous table existence.
    // Initialy we asume we aren't upgrading from old wiki
    $fromoldwiki = false;
    $tables = $db->MetaTables('TABLES');

    if (!in_array($CFG->prefix.'wiki_synonymous', $tables)) {  //New wiki isn't installed yet
        $fromoldwiki = true;  //We are upgrading from old wiki.

		// Upgrading ewiki to last version. 2006092502. Moodle182.
		
		require_once ($CFG->dirroot.'/mod/wiki/wikimigrate/ewiki_mysql.php');
		$result = ewiki_upgrade($oldversion);
		
    }
    return $result;
}

?>
