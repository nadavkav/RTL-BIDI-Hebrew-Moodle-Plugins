<?php // $Id: version.php,v 1.1 2010/09/04 11:36:31 deraadt Exp $
      //calculatedobjects

///////////////////////////////////////////////////////////////////////////
///  Called by moodle_needs_upgrading() and /admin/index.php

$plugin->version  = 2010090200;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2007101000;  //?? Requires this Moodle version (based on 'calculated' q-type)
$plugin->cron     = 0;           // Period for cron to check this module (secs)

$release = "0.9alpha";             // User-friendly version number

