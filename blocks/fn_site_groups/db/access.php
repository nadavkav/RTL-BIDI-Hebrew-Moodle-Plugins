<?php //$Id
//
// Capability definitions for the fngroups block.
//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//

$block_fn_site_groups_capabilities = array(

    'block/fn_site_groups:managegroups' => array(

        'riskbitmask' => RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_INHERIT,
            'student' => CAP_INHERIT,
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'admin' => CAP_ALLOW
        )
    ),

    'block/fn_site_groups:managegroupmembers' => array(

        'riskbitmask' => RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_INHERIT,
            'student' => CAP_INHERIT,
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'admin' => CAP_ALLOW
        )
    ),

    'block/fn_site_groups:managestudents' => array(

        'riskbitmask' => RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_INHERIT,
            'student' => CAP_INHERIT,
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'admin' => CAP_ALLOW
        )
    ),

    'block/fn_site_groups:markallgroups' => array(

        'riskbitmask' => RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        )
    ),

    'block/fn_site_groups:assignallusers' => array(

        'riskbitmask' => RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_INHERIT,
            'student' => CAP_INHERIT,
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'admin' => CAP_ALLOW
        )
    ),

    'block/fn_site_groups:assignowngroupusers' => array(

        'riskbitmask' => RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_INHERIT,
            'student' => CAP_INHERIT,
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'admin' => CAP_ALLOW
        )
    ),

    'block/fn_site_groups:createnewgroups' => array(

        'riskbitmask' => RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_INHERIT,
            'student' => CAP_INHERIT,
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'admin' => CAP_ALLOW
        )
    )

);
?>