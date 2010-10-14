<?php

/**
 * Code fragment to define the version of wordtable
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author Eoin Campbell
 * @package wordtable
 **/

$module->version  = 2010060300;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101545.01;  // Requires Moodle 1.9 or later
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
