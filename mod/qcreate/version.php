<?php // $Id: version.php,v 1.3 2007/11/21 09:19:34 jamiesensei Exp $

/**
 * Code fragment to define the version of qcreate
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author
 * @version $Id: version.php,v 1.3 2007/11/21 09:19:34 jamiesensei Exp $
 * @package qcreate
 **/

$module->version  = 2007092600;  // The current module version (Date: YYYYMMDDXX)
$module->cron     = 300;           // Period for cron to check this module (secs)

?>
