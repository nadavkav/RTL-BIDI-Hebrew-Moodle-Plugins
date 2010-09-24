<?php

    $mod_lightboxgallery_capabilities = array(

        'mod/lightboxgallery:addcomment' => array(
            'riskbitmask' => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'legacy' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'mod/lightboxgallery:addimage' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'legacy' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'mod/lightboxgallery:edit' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'legacy' => array(
                'editingteacher' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'mod/lightboxgallery:viewcomments' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'legacy' => array(
                'guest' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        )

    );

?>
