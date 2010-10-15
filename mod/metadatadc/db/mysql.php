<?PHP // $Id: mysql.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

function metadatadc_upgrade($oldversion) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    if ($oldversion < 2006042900) {

       # Do something ...

    }

    return true;
}

?>
