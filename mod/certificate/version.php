<?PHP // $Id: version.php,v 4.2 latest update 2007/06/08 

///////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of certificate
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
///  Last version by Leonardo Terra
///////////////////////////////////////////////////////////////////////////////

$module->version  = 2007042503;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2006080900;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>