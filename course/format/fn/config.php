<?php
//
// Optional course format configuration file
//
// This file contains any specific configuration settings for the
// format.
//
// The default blocks layout for this course format:
    $format['defaultblocks'] = 'fn_course_format_fncat,fn_my_menu,fn_my_links,fn_admin:' .
                               'fn_people,fn_announcements,fn_teacher_tools,updated_blogs';

// Blocks to always disallow:
//    $disallowblocks['admin'] = 1;
    $disallowblocks['participants'] = 1;
    $disallowblocks['news_items'] = 1;
    $disallowblocks['activity_modules'] = 1;
    $disallowblocks['course_list'] = 1;

//
?>