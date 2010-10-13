<?php //$Id: access.php,v 1.1.2.2 2009/03/18 16:45:48 mchurch Exp $
//
// Capability definitions for the elluminate module.
//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.


$mod_elluminate_capabilities = array(

    // Ability to see that the elluminatelive exists, and the basic information
    // about it.
    'mod/elluminate:view' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
            'user' => CAP_ALLOW,
            'guest' => CAP_ALLOW
        )
    ),

    // Ability to manage the settings.
    'mod/elluminate:manage' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),
    
    // Ability to moderate a meeting.
    'mod/elluminate:moderatemeeting' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),
    
    // Ability to join a meeting.
    'mod/elluminate:joinmeeting' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
            'user' => CAP_ALLOW
        )
    ),
    
    
    // Ability to enable / disable recordings.
    'mod/elluminate:enablerecordings' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),
    
    // Ability to view recordings.
    'mod/elluminate:viewrecordings' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
            'user' => CAP_ALLOW
         )
    ),
    
    // Ability to delete own recordings.
    'mod/elluminate:deleterecordings' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
         )
    ),
    
     // Ability to edit own recording descriptions
    'mod/elluminate:editownrecordings' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
         )
    ),
    
    // Ability to edit all recording descriptions
    'mod/elluminate:editallrecordings' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
         )
    ),
    
    // Ability to delete any recordings.
    'mod/elluminate:deleteanyrecordings' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'admin' => CAP_ALLOW
         )
    ),
    
    // Ability to add / remove moderators.
    'mod/elluminate:managemoderators' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),
    
    // Ability to add / remove participants.
    'mod/elluminate:manageparticipants' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    // Ability to view attendance records.
    'mod/elluminate:viewattendance' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
         )
    ),

    // Ability to manage attendance records (grades).
    'mod/elluminate:manageattendance' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
         )
    ),
        
    // Ability to manage recordings.
    'mod/elluminate:managerecordings' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
         )
    ),

    // Ability to manage any recordings.
    'mod/elluminate:manageanyrecordings' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'admin' => CAP_ALLOW
         )
    ),

    // Ability to manage any recordings.
    'mod/elluminate:managepreloads' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'admin' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
         )
    )
    
);

?>
