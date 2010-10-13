<?php // $Id: events.php,v 1.4 2009/06/22 21:30:52 mchurch Exp $

// Contains definitions for fn site group events.

$handlers = array (
    'course_created' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_course_created_handler',
         'schedule'         => 'instant'
     ),
    'course_deleted' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_course_deleted_handler',
         'schedule'         => 'instant'
     ),
     'groups_member_added' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_groups_member_added_handler',
         'schedule'         => 'instant'
     ),
    'groups_member_removed' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_groups_member_removed_handler',
         'schedule'         => 'instant'
     ),
     'role_assigned' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_role_assigned_handler',
         'schedule'         => 'instant'
     ),
    'user_created' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_user_created_handler',
         'schedule'         => 'instant'
     ),
    'groups_group_created' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_group_created_handler',
         'schedule'         => 'instant'
     ),
    'groups_group_deleted' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_group_deleted_handler',
         'schedule'         => 'instant'
     ),
     'groups_group_updated' => array (
         'handlerfile'      => '/blocks/fn_site_groups/eventhandlers.php',
         'handlerfunction'  => 'fn_group_updated_handler',
         'schedule'         => 'instant'
     )
);
?>
