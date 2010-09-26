<?php
/**
 * Format Version
 *
 * @author Jeff Graham
 * @version $Id: version.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
 * @package format_page
 **/

$plugin->version  = 2009060200; // Plugin version (update when tables change) if this line is changed ensure that the following line 
                                // in blocks/course_format_page/block_course_format_page.php is changed to reflect the proper version number
                                // set_config('format_page_version', '2008121000');        // trick the page course format into thinking its already installed.
$plugin->requires = 2007021501; // Required Moodle version

// This format can generate a huge number of block instances per course.
// Eventually, the weight field can grow past 127 and without this
// modification we run into a nasty bug with block API because
// all weights after 127 are 127
if (!$blocks_upgraded = get_config('format/page', 'blocks_updated')) {
    $result = table_column('block_instance', 'weight', 'weight', 'integer', '10', 'signed');
    if ($result) {
        set_config('blocks_updated', 1, 'format/page');
    }
}

?>