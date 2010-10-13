<?php // $Id: version.php$

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of Accordian
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2007101541;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101520; //2007101541;  // Requires this Moodle version (nadavkav)
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
