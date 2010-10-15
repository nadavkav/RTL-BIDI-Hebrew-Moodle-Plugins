<?PHP // $Id: version.php,v 1.4.10.9 2009/06/24 23:04:21 diml Exp $

/**
* @package mod-scheduler
* @category mod
* @author Valery Fremaux > 1.8
*/

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of scheduler
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2008061700;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2004082300;  // Requires this Moodle version
$module->cron     = 60;           // Period for cron to check this module (secs)

?>
