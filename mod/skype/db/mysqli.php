<?php 

function skype_upgrade($oldversion) {

/// This function does anything necessary to upgrade 
/// older versions to match current functionality 
///no upgrades yet :)
modify_database('','ALTER TABLE prefix_skype ADD `description` TEXT NOT NULL');
return true;

}

?>
