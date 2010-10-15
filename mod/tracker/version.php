<?PHP // $Id: version.php,v 1.1.10.8 2010/02/13 16:35:17 diml Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of tracker
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2009121300;  // The current module version (Date: YYYYMMDDXX)
$module->cron     = 0;           // Period for cron to check this module (secs)

?>