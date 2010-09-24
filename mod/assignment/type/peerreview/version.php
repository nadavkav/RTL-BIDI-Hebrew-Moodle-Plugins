<?php

$plugin->version = 2010050700; // YYYYMMDDnn for when the plugin was created

// Version required: Moodle 1.9.7 for Assignment type fix (see http://tracker.moodle.org/browse/MDL-16796)

$plugin->requires = 2007101520; //2007101570; // Look in <moodleroot>/mod/assignment/version.php for the minimum version allowed here

// For earlier versions...
//  - Simple fix: lower the version above and add the following line to /mod/lang/assignment.php
//
    $string['typepeerreview'] = 'Peer Review';
//
//  - Generic fix: follow bug fix above to generic solution for pre-1.9.7 versions


// These 2 lines are needed for Moodle 1.8 and below
// $submodule->version  = $plugin->version;
// $submodule->requires = $plugin->requires;
?>