<?php // $Id: access.php,v 1.7 2007/02/16 08:47:00 vyshane Exp $
/**
 * Capability definitions for the bookmarks module.
 *
 * For naming conventions, see lib/db/access.php.
 */
$mod_bookmarks_capabilities = array(

    'mod/bookmarks:additem' => array(

        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
            'student' => CAP_ALLOW
        )
    ),

    'mod/bookmarks:deleteitem' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
            'student' => CAP_ALLOW
        )
    ),
	
	'mod/bookmarks:manage' => array(
	
        'riskbitmask' => RISK_SPAM,
		
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
        )
    )

);