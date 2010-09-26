<?PHP  //$Id: version.php,v 1.5 2009/03/30 01:17:43 danmarsden Exp $
// This file defines the current version of the
// backup/restore code that is being used.  This can be
// compared against the values stored in the 
// database (backup_version) to determine whether upgrades should
// be performed (see db/backup_*.php)

 $file_manager_version = 2008112402;
//$file_manager_version = 2008060201;   // The current version is a date (YYYYMMDDXX)

 $file_manager_release = "Version 3";  // User-friendly version number
?>