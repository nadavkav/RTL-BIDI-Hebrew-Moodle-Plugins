<?php
/**
 * Library of functions necessary for course format
 * 
 * @author Jeff Graham, Mark Nielsen
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

// status settings for display options 
define('DISP_PUBLISH', 1);  // publish page (show when editing turned off)
define('DISP_THEME', 2);    // theme (show page in theme, eg. top tabs)
define('DISP_MENU', 4);     // menu (show page in menus)

// display constants for previous & next buttons
define('BUTTON_NEXT', 1);
define('BUTTON_PREV', 2);
define('BUTTON_BOTH', 3);

/**
 * This function displays the controls to add modules and blocks to a page
 *
 * @param object $page A fully populated page object
 * @param object $course A fully populated course object
 * @uses $USER;
 * @uses $CFG;
 */
function page_print_add_mods_form($page, $course) {
    global $USER, $CFG, $PAGE;

    if (empty($PAGE)) {
        $PAGE = page_create_object(PAGE_COURSE_VIEW, $course->id);
    }

    print_box_start('centerpara addpageitems');

    // Add drop down to add blocks
    if ($blocks = get_records('block', 'visible', '1', 'name')) {

        $format  = $PAGE->get_format_name();
        $options = array();
        foreach($blocks as $b) {
            if (in_array($b->name, array('format_page', 'page_module'))) {
                continue;
            }
            if (!blocks_name_allowed_in_format($b->name, $format)) {
                continue;
            }
            $blockobject = block_instance($b->name);
            if ($blockobject !== false && $blockobject->user_can_addto($PAGE)) {
                $options[$b->id] = $blockobject->get_title();
            }
        }
        asort($options);
        print '<span class="addblock">';
        $common = $CFG->wwwroot.'/course/format/page/format.php?id='.$course->id.'&amp;page='.$page->id.'&amp;blockaction=add&amp;sesskey='.sesskey().'&amp;blockid=';
        popup_form($common, $options, 'addblock', '', get_string('addblock', 'format_page'));
        print '</span>&nbsp;';
    }

    // Add drop down to add existing module instances
    if ($modules = page_get_modules($course, 'name')) {
        // From our modules object we can build an existing module menu using separators
        $options = array();
        foreach ($modules as $modplural => $instances) {
            // Sets an optgroup which can't be selected/submitted
            $options[$modplural.'_group_start'] = "--$modplural";

            foreach($instances as $cmid => $name) {
                $options[$cmid] = shorten_text($name);
            }

            // Ends an optgroup
            $options[$modplural.'_group_end'] = '--';
        }

        print '<span class="addexistingmodule">';
        $common = $CFG->wwwroot.'/course/format/page/format.php?id='.$course->id.'&amp;page='.$page->id.'&amp;blockaction=addmod&amp;sesskey='.sesskey().'&amp;instance=';
        popup_form($common, $options, 'addinstance', '', get_string('addexistingmodule', 'format_page'));
        print '</span>';
    }
    print_box_end();
}

/**
 * Prints a menu for jumping from page to page
 *
 * @return void
 **/
function page_print_jump_menu() {
    global $PAGE;

    if ($pages = page_get_all_pages($PAGE->get_id(), 'flat')) {
        $current = $PAGE->get_formatpage();

        $options = array();
        foreach ($pages as $page) {
            $options[$page->id] = page_name_menu($page->nameone, $page->depth);
        }
        print_box_start('centerpara pagejump');
        popup_form($PAGE->url_build('page'), $options, 'editpage', $current->id, get_string('choosepagetoedit', 'format_page'));
        print_box_end();
    }
}

/** 
 * Function returns the next sortorder value for a particular page column combo
 *
 * @param int $pageid Page id
 * @param string $position The column get the next weight for
 * @return int next sortorder 
 * @uses $CFG
 */
function page_get_next_weight($pageid, $position) {
    global $CFG;

    $weight = get_record_sql('SELECT 1, MAX(sortorder) + 1 '.sql_as()." nextfree
                                FROM {$CFG->prefix}format_page_items
                               WHERE pageid = $pageid
                                 AND position = '$position'");

    if (empty($weight->nextfree)) {
        $weight->nextfree = 0;
    }

    return $weight->nextfree;
}

/**
 * Function returns the next sortorder value for a group of pages with the same parent
 *
 * @param int $parentid ID of the parent grouping, can be 0
 * @return int
 */
function page_get_next_sortorder($parentid, $courseid) {
    global $CFG;

    $sortorder = get_record_sql('SELECT 1, MAX(sortorder) + 1 '.sql_as()." nextfree
                                   FROM {$CFG->prefix}format_page
                                  WHERE parent = $parentid
                                    AND courseid = $courseid");

    if (empty($sortorder->nextfree)) {
        $sortorder->nextfree = 0;
    }

    return $sortorder->nextfree;
}

/**
 * Removes a page from its current location my decrementing
 * the sortorder field of all pages that have the same
 * parent and course as the page.
 *
 * Must be called before the page is actually moved/deleted
 *
 * @param int $pageid ID of the page that is to be removed
 * @return boolean
 **/
function page_remove_from_ordering($pageid) {
    global $CFG;

    if ($pageinfo = get_record('format_page', 'id', $pageid, '', '', '', '', 'parent, courseid, sortorder')) {

        return execute_sql("UPDATE {$CFG->prefix}format_page
                               SET sortorder = sortorder - 1
                             WHERE sortorder > $pageinfo->sortorder
                               AND parent = $pageinfo->parent
                               AND courseid = $pageinfo->courseid", false);
    }
    return false;
}

/**
 * Prints blocks for a given position
 *
 * @param array $pageblocks An array of blocks organized by position
 * @param char $position Position that we are currently printing
 * @return void
 **/
function page_print_position($pageblocks, $position, $width) {
    global $PAGE, $THEME;

    $editing = $PAGE->user_is_editing();

    if ($editing || blocks_have_content($pageblocks, $position)) {
    /// Figure out an appropriate ID
        switch ($position) {
            case BLOCK_POS_LEFT:
                $id = 'left';
                break;
            case BLOCK_POS_RIGHT:
                $id = 'right';
                break;
            case BLOCK_POS_CENTER:
                $id = 'middle';
                break;
            default:
                $id = $position;
                break;
        }

    /// Figure out the width - more for routine than being functional.  May want to impose a minimum width though
        $width = bounded_number($width, blocks_preferred_width($pageblocks[$position]), $width);

    /// Print it
        if ( is_numeric($width) ) { // default to px  MR-263
            $tdwidth = $width.'px';
        } else {
            $tdwidth = $width;
        }
        echo "<td style=\"width: {$tdwidth}\" id=\"$id-column\">";
        if ( is_numeric($width) or strpos($width,'px') ) {
            print_spacer(1, $width, false);
        }
        print_container_start();
        if ($position == BLOCK_POS_CENTER) {
            echo skip_main_destination();
            page_frontpage_settings();
        }
        page_blocks_print_group($pageblocks, $position);
        print_container_end();
        echo '</td>';
    } else {
        // Empty column - no class, style or width

        /// Figure out an appropriate ID
            switch ($position) {
                case BLOCK_POS_LEFT:
                    $id = 'left';
                    break;
                case BLOCK_POS_RIGHT:
                    $id = 'right';
                    break;
                case BLOCK_POS_CENTER:
                    $id = 'middle';
                    break;
                default:
                    $id = $position;
                    break;
            }
        
        // we still want to preserve values unles 
        if ($width != '0'  ) {
            if ( is_numeric($width) ) { // default to px  MR-263
                $tdwidth = $width.'px';
            } else {
                $tdwidth = $width;
            }
            echo '<td style="width:'.$tdwidth.'" id="'.$id.'-column" > ';
            if ( $width != '0' and is_numeric($width) or strpos($width,'px') ) {
                print_spacer(1, $width, false);
            }
            echo "</td>";
        } else {
            echo '<td></td>'; // 0 means no column anyway
        }
    }
}

/**
 * COPIED from lib/blocklib.php blocks_print_group almost
 * verbatim.
 *
 * This function prints one group of blocks in a page
 * Parameters passed by reference for speed; they are not modified.
 *
 * @param object $page format_page class instance
 * @param array $pageblocks An array of blocks organized by position
 * @param char $position The position in the pageblocks array to print
 * @return void
 **/
function page_blocks_print_group(&$pageblocks, $position) {
    global $COURSE, $CFG, $USER, $PAGE;

    if (empty($pageblocks[$position])) {
        $groupblocks = array();
        $maxweight = 0;
    } else {
        $groupblocks = $pageblocks[$position];
        $maxweight = max(array_keys($groupblocks));
    }


    foreach ($groupblocks as $instance) {
        if (!empty($instance->pinned)) {
            $maxweight--;
        }
    }

    $isediting = $PAGE->user_is_editing();


    foreach($groupblocks as $instance) {


        // $instance may have ->rec and ->obj
        // cached from when we walked $pageblocks
        // in blocks_have_content()
        if (empty($instance->rec)) {
            if (empty($instance->blockid)) {
                continue;   // Can't do anything
            }
            $block = blocks_get_record($instance->blockid);
        } else {
            $block = $instance->rec;
        }

        if (empty($block)) {
            // Block doesn't exist! We should delete this instance!
            continue;
        }

        if (empty($block->visible)) {
            // Disabled by the admin
            continue;
        }

        if (empty($instance->obj)) {
            if (!$obj = block_instance($block->name, $instance)) {
                // Invalid block
                continue;
            }
        } else {
            $obj = $instance->obj;
        }

        $editalways = $PAGE->edit_always();

        if (($isediting  && empty($instance->pinned)) || !empty($editalways)) {
            // THIS IS WHY WE HAVE THIS FUNCTION
            $PAGE->set_pageitemid($instance->pageitemid);

            $options = 0;
            // The block can be moved up if it's NOT the first one in its position. If it is, we look at the OR clause:
            // the first block might still be able to move up if the page says so (i.e., it will change position)
            $options |= BLOCK_MOVE_UP    * ($instance->weight != 0          || ($PAGE->blocks_move_position($instance, BLOCK_MOVE_UP)   != $instance->position));
            // Same thing for downward movement
            $options |= BLOCK_MOVE_DOWN  * ($instance->weight != $maxweight || ($PAGE->blocks_move_position($instance, BLOCK_MOVE_DOWN) != $instance->position));
            // For left and right movements, it's up to the page to tell us whether they are allowed
            $options |= BLOCK_MOVE_RIGHT * ($PAGE->blocks_move_position($instance, BLOCK_MOVE_RIGHT) != $instance->position);
            $options |= BLOCK_MOVE_LEFT  * ($PAGE->blocks_move_position($instance, BLOCK_MOVE_LEFT ) != $instance->position);
            // Finally, the block can be configured if the block class either allows multiple instances, or if it specifically
            // allows instance configuration (multiple instances override that one). It doesn't have anything to do with what the
            // administrator has allowed for this block in the site admin options.
            $options |= BLOCK_CONFIGURE * ( $obj->instance_allow_multiple() || $obj->instance_allow_config() );
            $obj->_add_edit_controls($options);
        }

        // todo: update rounded corner styles to use core rounded corner divs then remove this junk
        if ($block->name == 'page_module') {
            $obj->get_content();

            if (!empty($obj->module->name)) {
                $name = $obj->module->name;
            } else {
                $name = $block->name;
            }
            $plugin = 'module';
        } else {
            $name   = $block->name;
            $plugin = 'block';
        }
        if (!($obj->is_empty() && empty($COURSE->javascriptportal) and empty($obj->edit_controls))) {
            echo '<div class="rounded rounded-'.$plugin.' rounded-'.$name.'" style="width:100%;"><div class="hd hd-'.$plugin.'"><div class="c"></div></div>';
        }

        if (!$instance->visible && empty($COURSE->javascriptportal)) {
            if ($isediting) {
                $obj->_print_shadow();
            }
        } else {
            global $COURSE;
            if(!empty($COURSE->javascriptportal)) {
                 $COURSE->javascriptportal->currentblocksection = $position;
            }
            $obj->_print_block();
        }
        if (!empty($COURSE->javascriptportal)
                    && (empty($instance->pinned) || !$instance->pinned)) {
            $COURSE->javascriptportal->block_add('inst'.$instance->id, !$instance->visible);
        }
        // todo: update rounded corner styles to use core rounded corner divs then remove this junk
        if (!($obj->is_empty() && empty($COURSE->javascriptportal) and empty($obj->edit_controls))) {
            echo '<div class="ft ft-'.$plugin.'"><div class="c"></div></div></div>';
        }
    } // End foreach

    // Zero this out so it is not used anymore
    $PAGE->set_pageitemid(0);
}

/**
 * Returns blocks organized by page and weight for
 * a given page.
 *
 * @return array
 * @todo Eventually, I think that when pageitems that are modules are added, then
 *       a corresponding block_instance record will be created.  Right now, this
 *       is just how it works and it does keep number of block_instance records down
 * @todo Support pinned blocks?
 **/
function page_blocks_setup() {
    global $CFG, $PAGE;

    $pageid = $PAGE->get_id();
    $type   = $PAGE->get_type();
    $as     = sql_as();

    // We must have a real page_module instance to work with
    // otherwise capability checks fail - here we look for one
    // in database or create one
    $instances = get_records_sql("SELECT i.*
                                    FROM {$CFG->prefix}block_instance i,
                                         {$CFG->prefix}block b
                                   WHERE b.id = i.blockid
                                     AND b.name = 'page_module'
                                     AND i.pagetype = '$type'
                                     AND i.pageid = $pageid");

    if (!$instances) {
        // Make a new one
        if (!$blockid = get_field('block', 'id', 'name', 'page_module')) {
            error('page_module block is not installed and is required');
        }

        $weight = get_record_sql('SELECT 1, MAX(weight) + 1 '.sql_as().' nextfree
                                    FROM '. $CFG->prefix .'block_instance
                                   WHERE pageid = '. $pageid .'
                                     AND pagetype = \''. $type .'\'
                                     AND position = \''. BLOCK_POS_LEFT .'\'');

        if (empty($weight->nextfree)) {
            $weight->nextfree = 0;
        }

        $dummy = new stdClass;
        $dummy->blockid    = $blockid;
        $dummy->pageid     = $pageid;
        $dummy->pagetype   = $type;
        $dummy->position   = BLOCK_POS_LEFT;
        $dummy->weight     = $weight->nextfree;
        $dummy->visible    = 1;
        $dummy->configdata = '';

        if (!$dummy->id = insert_record('block_instance', $dummy)) {
            error('Failed to create page_module block instance');
        }
    } else {
        // Any will do really...
        $dummy = array_shift($instances);
    }

    // OLD METHOD - keeping code since it might be handy later, not sure though :\
    //
    // // Get our block instances by combining fields from the block_instance 
    // // table and format_page_items table.  This is done because API only 
    // // supports left/right columns and pageid = $courseid (which then makes 
    // // the need for new weight values)
    // $blocks = get_records_sql("SELECT i.id $as pageitemid, b.id, b.blockid, b.pageid, b.pagetype,
    //                                   i.position, i.sortorder $as weight, i.visible, b.configdata
    //                              FROM {$CFG->prefix}format_page_items i,
    //                                   {$CFG->prefix}block_instance b
    //                             WHERE i.blockinstance = b.id
    //                               AND b.pageid = $pageid
    //                               AND b.pagetype = '$type'
    //                               AND i.pageid = {$PAGE->formatpage->id}
    //                          ORDER BY i.position, i.sortorder");
    // 
    // if (!$blocks) {
    //     $blocks = array();
    // }
    // 
    // // Get our page pageitems that are modules and process those
    // $modules = get_records_sql("SELECT i.id $as pageitemid, i.position, i.sortorder $as weight, i.visible, i.cmid $as configdata
    //                               FROM {$CFG->prefix}format_page_items i
    //                              WHERE i.blockinstance = 0
    //                                AND i.pageid = {$PAGE->formatpage->id}");
    // 
    // if ($modules) {
    //     // Since these do not have block_instance records in the database
    //     // we need to add a couple of fields from our dummy instance to make 
    //     // it look like a block_instance record
    //     foreach ($modules as $module) {
    //         $module->id       = $dummy->id;
    //         $module->blockid  = $dummy->blockid;
    //         $module->pageid   = $dummy->pageid;
    //         $module->pagetype = $dummy->pagetype;
    // 
    //         $blocks[$module->pageitemid] = $module;
    //     }
    //     // Need to resort blocks by position and weight
    //     $function = create_function('$a, $b', 'return ($a->position == $b->position) ? strnatcasecmp($a->weight, $b->weight) : strcmp($a->position, $b->position);');
    //     usort($blocks, $function);
    // }

    // Get our block instances by combining fields from the block_instance 
    // table and format_page_items table.  This is done because API only 
    // supports left/right columns and pageid = $courseid
    // We then combine those block instances with "fake" page_module block
    // instance records for every module page item in format_page_items
    $blocks = get_records_sql("(SELECT i.id $as pageitemid, b.id, b.blockid, b.pageid, b.pagetype, i.position, i.sortorder $as weight, i.visible, b.configdata
                                  FROM {$CFG->prefix}format_page_items i,
                                       {$CFG->prefix}block_instance b
                                 WHERE i.blockinstance = b.id
                                   AND b.pageid = $pageid
                                   AND b.pagetype = '$type'
                                   AND i.pageid = {$PAGE->formatpage->id})
                                 UNION
                               (SELECT i.id $as pageitemid, b.id, b.blockid, b.pageid, b.pagetype, i.position, i.sortorder $as weight, i.visible, i.cmid $as configdata
                                  FROM {$CFG->prefix}format_page_items i,
                                       {$CFG->prefix}block_instance b
                                 WHERE i.blockinstance = 0
                                   AND b.pageid = $pageid
                                   AND b.pagetype = '$type'
                                   AND i.pageid = {$PAGE->formatpage->id}
                                   AND b.id = $dummy->id)
                              ORDER BY position, weight");

    // Initialize $pageblocks array
    $positions  = $PAGE->blocks_get_positions();
    $arrays     = array_pad(array(), count($positions), array());
    $pageblocks = array_combine($positions, $arrays);

    // Load blocks into their apporpriate locations
    if (!empty($blocks)) {
        foreach($blocks as $block) {
            if ($block->id == $dummy->id) {
                // Since our page_module instances are dummies, need to fix their config value
                $config = new stdClass;
                $config->cmid = $block->configdata;
                $block->configdata = base64_encode(serialize($config));
            }
            $pageblocks[$block->position][$block->weight] = $block;
        }
    }

    $pinned = blocks_get_pinned($PAGE);
    foreach ($pinned as $pos => $blocks) {
        $pageblocks[$pos] = array_merge($blocks, $pageblocks[$pos]);
    }

    // Handle block actions
    page_blocks_execute_url_action();

    return $pageblocks;
}

/**
 * Same functionality as blocks_execute_url_action()
 * Handle all block actions ourselves.
 *
 * @param boolean $redirect (Optional) Redirect after action
 * @return void
 **/
function page_blocks_execute_url_action($redirect = true) {
    global $CFG, $COURSE, $PAGE;

    $pageitemid  = optional_param('pageitemid', 0, PARAM_INT);
    $blockaction = optional_param('blockaction', '', PARAM_ALPHA);

    // Reasons to stop right meow
    if (empty($blockaction) || !$PAGE->user_allowed_editing() || !confirm_sesskey()) {
        return;
    }
    // Make sure if we have a valid pageitem.
    if ($pageitemid and !$pageitem = get_record('format_page_items', 'id', $pageitemid)) {
        return;
    }

    switch ($blockaction) {
        case 'config':
            if (empty($pageitem->blockinstance) and !empty($pageitem->cmid)) {
                // Its a module - go to module update
                redirect("$CFG->wwwroot/course/mod.php?update=$pageitem->cmid&amp;sesskey=".sesskey());
            } else if (!empty($pageitem->blockinstance)) {
                // Its a block instance - allow core routine to handle
                redirect($PAGE->url_build('instanceid', $pageitem->blockinstance, 'blockaction', 'config', 'sesskey', sesskey()));
            } else {
                error('Invalid page item to configure');
            }
            break;
        case 'toggle':
            $update     = new stdClass;
            $update->id = $pageitem->id;

            if (empty($pageitem->visible)) {
                $update->visible = 1;
            } else {
                $update->visible = 0;
            }
            update_record('format_page_items', $update);
            break;
        case 'delete':
            page_block_delete($pageitem);
            break;
        case 'moveup':
            page_block_move($pageitem, $pageitem->position, $pageitem->sortorder - 1);
            break;
        case 'movedown':
            page_block_move($pageitem, $pageitem->position, $pageitem->sortorder + 1);
            break;
        case 'moveright':
            $destposition = $PAGE->blocks_move_position($pageitem, BLOCK_MOVE_RIGHT);
            $destweight   = page_get_next_weight($pageitem->pageid, $destposition);

            page_block_move($pageitem, $destposition, $destweight);
            break;
        case 'moveleft':
            $destposition = $PAGE->blocks_move_position($pageitem, BLOCK_MOVE_LEFT);
            $destweight   = page_get_next_weight($pageitem->pageid, $destposition);

            page_block_move($pageitem, $destposition, $destweight);
            break;
        case 'addmod':
            // Right now, modules are added differently
            $instance = required_param('instance', PARAM_INT);

            $record                = new stdClass;
            $record->pageid        = $PAGE->formatpage->id;
            $record->cmid          = $instance;
            $record->blockinstance = 0;
            $record->position      = $PAGE->blocks_default_position();
            $record->sortorder     = page_get_next_weight($record->pageid, $record->position);
            insert_record('format_page_items', $record);
            break;
        case 'add': 
            // Add a block instance and a pageitem
            $blockid = required_param('blockid', PARAM_INT);
            $block   = blocks_get_record($blockid);

            if (empty($block) or !$block->visible) {
                break;
            }
            if (!block_method_result($block->name, 'user_can_addto', $PAGE)) {
                break;
            }

            // Add a block instance if one does not already exist or if the block allows multiple block instances
            $exists = record_exists('block_instance', 'pageid', $PAGE->get_id(), 'pagetype', $PAGE->get_type(), 'blockid', $blockid);
            if ($block->multiple || !$exists) {
                // Get the next weight value NOTE: hard code left position
                $weight = get_record_sql('SELECT 1, MAX(weight) + 1 '.sql_as().' nextfree
                                            FROM '. $CFG->prefix .'block_instance
                                           WHERE pageid = '. $PAGE->get_id() .'
                                             AND pagetype = \''. $PAGE->get_type() .'\'
                                             AND position = \''. BLOCK_POS_LEFT .'\'');

                if (empty($weight->nextfree)) {
                    $weight->nextfree = 0;
                }

                $newinstance = new stdClass;
                $newinstance->blockid    = $blockid;
                $newinstance->pageid     = $PAGE->get_id();
                $newinstance->pagetype   = $PAGE->get_type();
                $newinstance->position   = BLOCK_POS_LEFT; // Make sure we keep them all in same column
                $newinstance->weight     = $weight->nextfree;
                $newinstance->visible    = 1;
                $newinstance->configdata = '';

                $instanceid = $newinstance->id = insert_record('block_instance', $newinstance);

                if ($newinstance and ($obj = block_instance($block->name, $newinstance))) {
                    // Return value ignored
                    $obj->instance_create();
                }
            } else if ($exists) {
                // Get the existing blockinstance as the block only allows one instance.
                $instanceid = get_field('block_instance', 'id', 'pageid', $PAGE->get_id(), 'pagetype', $PAGE->get_type(), 'blockid', $blockid);
            }

            if (!empty($instanceid)) {
                // Create a new page item that links to the instance
                $record                = new stdClass;
                $record->pageid        = $PAGE->formatpage->id;
                $record->cmid          = 0;
                $record->blockinstance = $instanceid;
                $record->position      = $PAGE->blocks_default_position();
                $record->sortorder     = page_get_next_weight($record->pageid, $record->position);
                insert_record('format_page_items', $record);
            }
            break;
    }

    if ($redirect) {
        // In order to prevent accidental duplicate actions, redirect to a page with a clean url
        redirect($PAGE->url_get_full());
    }
}

/**
 * This function removes blocks/modules from a page in the case of
 * blocks it also removes the entry from block_instance 
 *
 * @param object $pageitem a fully populated page_item object
 * @uses $CFG
 * @uses $COURSE
 */
function page_block_delete($pageitem) {
    global $CFG, $COURSE;

    require_once($CFG->libdir.'/blocklib.php');

    // we leave module cleanup to the manage modules tab... blocks need some help though.
    if (!empty($pageitem->blockinstance)) {
        if ($blockinstance = get_record('block_instance', 'id', $pageitem->blockinstance)) {
            // see if this is the last reference to the blockinstance
            $count = count_records('format_page_items', 'blockinstance', $pageitem->blockinstance);
            if ($count == 1) {
                if ($block = blocks_get_record($blockinstance->blockid)) {
                    if ($block->name != 'course_menu') {
                        // At this point, the format has done all of its own checking,
                        // hand it off to block API
                        blocks_delete_instance($blockinstance);
                    }
                }
            }
        }
    }

    delete_records('format_page_items', 'id', $pageitem->id);

    execute_sql("UPDATE {$CFG->prefix}format_page_items
                    SET sortorder = sortorder - 1
                  WHERE pageid = $pageitem->pageid
                    AND position = '$pageitem->position'
                    AND sortorder > $pageitem->sortorder", false);
}

/**
 * Performs a block move
 *
 * @param object $pageitem A fully populated page_item object
 * @param char $destposition Destination position
 * @param int $destweight Desitnation weight
 * @uses $CFG
 * @uses $COURSE
 * @return boolean
 **/
function page_block_move($pageitem, $destposition, $destweight) {
    global $CFG, $COURSE;

/// Fix sortorder for the position that the item is leaving
    $closegapsql = "UPDATE {$CFG->prefix}format_page_items
                       SET sortorder = sortorder - 1 
                     WHERE sortorder > $pageitem->sortorder
                       AND position = '$pageitem->position'
                       AND pageid = $pageitem->pageid";

    if (!execute_sql($closegapsql, false)) {
        // return false;
        // todo: perhaps a more manual reset of the sortorder field?
    }

/// Make room at the destination position
    $opengapsql = "UPDATE {$CFG->prefix}format_page_items
                       SET sortorder = sortorder + 1
                     WHERE sortorder >= $destweight
                       AND position = '$destposition'
                       AND pageid = $pageitem->pageid";

    if (!execute_sql($opengapsql, false)) {
        // return false;
        // todo: perhaps a more manual reset of the sortorder field?
    }

/// Update the pageitem
    $update            = new stdClass;
    $update->id        = $pageitem->id;
    $update->sortorder = $destweight;
    $update->position  = $destposition;

    return update_record('format_page_items', $update);
}

/**
 * Handles format actions, specifically, the
 * parameter action.
 *
 * Usually execution of the script stops here.
 *
 * @param string $action (Optional) The action that should be handled
 * @return void
 **/
function page_format_execute_url_action($action = NULL) {
    global $CFG, $PAGE, $USER;

    $PAGE->init_full();

    if ($action === NULL) {
        // Try to grab from request
        $action = optional_param('action', 'layout', PARAM_ALPHA);
    }

    // Load some vars that can be used by the actions
    $page    = clone($PAGE->get_formatpage());  // Do not allow the actions to modify the page object's page record (PHP 5)
    $course  = $PAGE->courserecord;
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if (!empty($action)) {
        if (!isloggedin() and $action != 'layout') {
            // If on site page, then require_login may not be called
            // At this point, we make sure the user is logged in
            require_login($course->id);
        }
        switch ($action) {
            case 'showhide':
                if (!confirm_sesskey()) {
                    error(get_string('confirmsesskeybad', 'error'));
                }
                require_capability('format/page:managepages', $context);

                $display  = required_param('display', PARAM_INT);
                $showhide = required_param('showhide', PARAM_INT);

                page_showhide($page->id, $display, $showhide);

                redirect($PAGE->url_get_full(array('action' => 'manage')));
                break;

            case 'confirmdelete':
                if (!confirm_sesskey()) {
                    error(get_string('confirmsesskeybad', 'error'));
                }
                require_capability('format/page:editpages', $context);

                $message = get_string('confirmdelete', 'format_page', format_string($page->nameone));
                $linkyes = $CFG->wwwroot.'/course/format/page/format.php?id='.$page->courseid.'&amp;page='.$page->id.'&amp;action=deletepage&amp;sesskey='.sesskey();
                $linkno  = $PAGE->url_build('action', 'manage');
                notice_yesno($message, $linkyes, $linkno);
                print_footer($course);
                die;
                break;

            case 'deletepage':  // actually delete a page
                if (!confirm_sesskey()) {
                    error(get_string('confirmsesskeybad', 'error'));
                }
                require_capability('format/page:editpages', $context);

                $pageid = required_param('page', PARAM_INT);

                if (!page_delete_page($pageid)) {
                    error(get_string('couldnotdeletepage', 'format_page'));
                }
                redirect($PAGE->url_build('action', 'manage'));

                break;

            case 'movepage':
                if (!confirm_sesskey()) {
                    error(get_string('confirmsesskeybad', 'error'));
                }
                require_capability('format/page:managepages', $context);

                $moving = required_param('moving', PARAM_INT);
                $moveto = required_param('moveto', PARAM_INT);
                $pos    = required_param('pos', PARAM_INT);

                // Ensure that the move is legal
                $children = page_get_children($moving);

                if (array_key_exists($moveto, $children) or $moveto == $moving) {
                    // Bad move request, would create cyclical inheritance
                    error(get_string('badmoverequest', 'format_page'));
                }
                // Fix sortorder for the position that the item is leaving
                page_remove_from_ordering($moving);

                // Make room at the destination position
                $opengapsql = "UPDATE {$CFG->prefix}format_page
                                   SET sortorder = sortorder + 1
                                 WHERE sortorder >= $pos
                                   AND parent = $moveto
                                   AND courseid = $course->id";

                if (!execute_sql($opengapsql, false)) {
                    // return false;
                    // todo: perhaps a more manual reset of the sortorder field?
                }

                // Update the page
                $update            = new stdClass;
                $update->id        = $moving;
                $update->sortorder = $pos;
                $update->parent    = $moveto;
                update_record('format_page', $update);

                redirect($PAGE->url_build('action', 'manage'));

            default:
                // Include a file from the actions directory
                $file = "$CFG->dirroot/course/format/page/plugin/action/$action.php";

                if (file_exists($file)) {
                    require_once($file);

                    $classname = "format_page_action_$action";

                    if (!class_exists($classname)) {
                        error('Action file does not have a valid class defined');
                    }

                    $class = new $classname($page, $context);
                    $class->display();

                    // Above script may perform an exit or a redirect - but usually we want to finish the page
                    print_footer($course);
                    die;
                } else {
                    error("Unknown action passed: $action");
                }
                break;
        }
    }
}

/**
 * Gets a page record
 *
 * @param int $pageid ID of the page to be fetched
 * @param int $courseid ID of the course that the page belongs to
 * @return object
 **/
function page_get($pageid, $courseid = NULL) {
    global $COURSE;

    if ($courseid === NULL) {
        $courseid = $COURSE->id;
    }

    // Attempt to find in cache, otherwise try the DB
    if ($pages = page_get_all_pages($courseid, 'flat')) {
        if (array_key_exists($pageid, $pages)) {
            return clone($pages[$pageid]);
        }
    }
    return get_record('format_page', 'id', $pageid);
}

/**
 * Grabs all of the pages and organizes
 * them into their parent/child hierarchy
 * or into a logical flat structure.
 *
 * The result is cached and the whole operation
 * is performed with one database query.
 *
 * All page objects get a new attribute of depth, which
 * is their current depth in the parent/child hierarchy
 *
 * @param int $courseid ID of the course
 * @param string $structure The structure in which to organize the pages.  EG: flat or nested
 * @param boolean $clearcache If true, then the cache is reset for the passed structure
 * @return mixed False if no pages are found otherwise an array of page objects with children set
 **/
function page_get_all_pages($courseid, $structure = 'nested', $clearcache = false) {
    static $cache = array();

    if (!in_array($structure, array('nested', 'flat'))) {
        error("Unknown structure type: $structure");
    }

    if ($clearcache) {
        $cache = array();
    }
    if (empty($cache[$courseid]) or empty($cache[$courseid][$structure])) {
        if ($allpages = get_records('format_page', 'courseid', $courseid, 'parent, sortorder')) {
            $masterpages = page_filter_child_pages(0, $allpages);
            $structures  = page_build_structures($masterpages, $allpages);
        } else {
            $structures = array('nested' => false, 'flat' => false);
        }
        $cache[$courseid] = $structures;
    }

    return $cache[$courseid][$structure];
}

/**
 * Recursively calls itself to build the various page structures.
 *
 * @param array $pages Array of page objects that are passed by reference
 * @param array $allpages All pages in the course
 * @param int $depth Current depth in the parent/child hierarchy
 * @return array
 **/
function page_build_structures($pages, &$allpages, $depth = 0) {
    $return = array('nested' => array(), 'flat' => array());

    foreach ($pages as $pageid => $page) {
        // Add the depth value, very handy
        $page->depth = $depth;

        // Each structure needs its own copy of the page object
        $return['nested'][$pageid] = clone($page);
        $return['flat'][$pageid]   = clone($page);

        // Get and process the children
        $children = page_filter_child_pages($pageid, $allpages);
        $children = page_build_structures($children, $allpages, $depth + 1);

        // Store the children based on the structure
        $return['nested'][$pageid]->children = $children['nested'];
        $return['flat'] += $children['flat'];
    }

    return $return;
}

/**
 * Filters all pages who have the same given parent
 *
 * @param int $parent Page ID of the parent
 * @param array $allpages All pages in the course
 * @return array
 **/
function page_filter_child_pages($parent, &$allpages) {
    $collected = false;
    $return    = array();
    foreach ($allpages as $id => $page) {
        if ($page->parent == $parent) {
            $return[$id] = $page;

            // Remove from all pages to improve seek times later
            unset($allpages[$id]);

            // This will hault seeking after we get all the children
            $collected = true;
        } else if ($collected) {
            // Since $allpages is organized by parent,
            // then once we find one, we get them all in a row
            break;
        }
    }
    return $return;
}

/**
 * Get the parents of the passed page
 *
 * @param int $pageid ID of the page to find parents
 * @param int $courseid ID of the course that the page belongs to
 * @return array
 **/
function page_get_parents($pageid, $courseid) {
    $parents = array();

    if ($allpages = page_get_all_pages($courseid, 'flat')) {
        while ($pageid != 0 and !empty($allpages[$pageid])) {
            $parents[$pageid] = $allpages[$pageid];
            $pageid = $allpages[$pageid]->parent;
        }
        // Flip array around so top lvl parent is first
        $parents = array_reverse($parents, true);
    }
    return $parents;
}

/**
 * Gets all possible page parents for the given page
 *
 * @param int $pageid ID of the page to find parents for (0 is fine)
 * @param int $courseid ID of the course that the page belongs to
 * @return mixed
 */
function page_get_possible_parents($pageid, $courseid) {
    if ($parents = page_get_all_pages($courseid, 'flat')) {
        if ($pageid != 0) {  // If zero, then it can have any page as a parent
            // Get the children
            $children = page_get_children($pageid);

            // Unset the current page...
            unset($parents[$pageid]);
            // ...and all of its children
            foreach ($children as $id => $child) {
                unset($parents[$id]);
            }
        }
    } else {
        $parents = false;
    }
    return $parents;
}

/**
 * This gets the full child tree of the passed page.
 *
 * @param object $pageid Page ID to return children for
 * @param string $structure Structure of the tree, EG: flat or nested
 * @param int $courseid (Optional) ID of the current course
 * @return array
 */
function page_get_children($pageid, $structure = 'flat', $courseid = NULL) {
    global $COURSE;

    $children = array();

    if ($courseid === NULL) {
        $courseid = $COURSE->id;
    }

    if ($allpages = page_get_all_pages($courseid, 'flat')) {

        switch ($structure) {
            case 'flat':
                // Loop through the pages until we find the passed
                // pageid and then collect its children
                $found = false;
                foreach ($allpages as $page) {
                    if ($page->id == $pageid) {
                        $found = true;
                        $depth = $page->depth;
                        // Don't include this one, skip it
                        continue;
                    }
                    if ($found) {
                        if ($page->depth <= $depth) {
                            // Not a child, break
                            break;
                        }
                        $children[$page->id] = $page;
                    }
                }
                break;
            case 'nested':
                // Find the parent page IDs
                $parentids = array();
                while ($pageid != 0 and !empty($allpages[$pageid])) {
                    array_unshift($parentids, $pageid);
                    $pageid = $allpages[$pageid]->parent;
                }
                // Dig down through the parents to get the children
                $children = page_get_all_pages($courseid, 'nested');
                foreach ($parentids as $pageid) {
                    $children = $children[$pageid]->children;
                }
                break;
        }
    }

    return $children;
}

/** 
 * This function returns a number of "master" pages that are first in the sortorder
 *
 * @param int $courseid the course id to get pages from
 * @param int $limit (optional) the maximumn number of 'master' pages to return (0 meaning no limit);
 * @param int $display (optional) bitmask representing what display status pages to return
 * @return array of pages
 */
function page_get_master_pages($courseid, $limit=0, $display=DISP_PUBLISH) {

    if (!$allpages = page_get_all_pages($courseid)) {
        return false;
    }
    $pages = array();
    foreach ($allpages as $page) {
        if (!empty($limit) and count($pages) == $limit) {
            break;
        }
        if (($page->display & $display) == $display) {
            $pages[] = $page;
        }
    }

    if (empty($pages)) {
        return false;
    }
    return $pages;
}

/**
 * Gets the default first page for a course
 *
 * @param int $courseid (Optional) The course to look in
 * @return mixed Page object or false
 * @todo Check to make sure that the page being returned has any page items?  Still might be blank depending on blocks though.
 **/
function page_get_default_page($courseid = 0) {
    global $COURSE;

    $return = false;

    if (empty($courseid)) {
        $courseid = $COURSE->id;
    }

    if (has_capability('format/page:managepages', get_context_instance(CONTEXT_COURSE, $courseid))) {
        $display = false;
    } else {
        $display = DISP_PUBLISH;
    }

    if ($pages = page_get_master_pages($courseid, 1, $display)) {
        // If page must have content, then use this
        // foreach($pages as $page) {
        //     if (record_exists('format_page_items', 'pageid', $page->id)) {
        //         // we found THE master page
        //         break;
        //     }
        // }
        $return = current($pages);
    }
    if (!$return) {
        // OK, first try failed, try grabbing almost anything now
        $select = "courseid = $courseid";
        if ($display) {
            $select .= " AND ((display & $display) = $display)";
        }
        if ($pages = get_records_select('format_page', $select, 'sortorder,nameone', '*', 0, 1)) {
            $return = current($pages);
        }
    }

    return $return;
}

/**
 * Makes sure that the current page ID
 * is an actual page ID and if the page
 * is published.  If not published,
 * then do a capability check to see
 * if the user can view unpubplished pages
 *
 * @param int $pageid ID to process
 * @param int $courseid ID of the current courese
 * @return mixed Page object or false
 **/
function page_validate_pageid($pageid, $courseid) {
    $return = false;
    $pageid = clean_param($pageid, PARAM_INT);

    if ($pageid > 0 and $page = page_get($pageid, $courseid)) {
        if ($page->courseid == $courseid and ($page->display & DISP_PUBLISH or has_capability('format/page:editpages', get_context_instance(CONTEXT_COURSE, $page->courseid)))) {
            // This page belongs to this course and is published or the current user can see unpublished pages
            $return = $page;
        }
    }
    return $return;
}

/**
 * This function returns a number of "theme" pages that are first in the sortorder
 * 
 * @param int $courseid the course id ot get pages from
 * @param int $limit (optional) the maximum number of pages to return (0 meaning no limit);
 * @return array of pages
 */
function page_get_theme_pages($courseid, $limit=0) {
    return page_get_master_pages($courseid, $limit, DISP_THEME | DISP_PUBLISH);
}

/**
 * This function returns a number of "menu" pages that are first in the sortorder
 * 
 * @param int $courseid the course id ot get pages from
 * @param int $limit (optional) the maximum number of pages to return (0 meaning no limit);
 * @return array of pages
 */
function page_get_menu_pages($courseid, $limit=0) {
    return page_get_master_pages($courseid, $limit, DISP_MENU | DISP_PUBLISH);
}

/**
 * This function will adjust the display value for a page
 * depending on the $mode and whether or not to to show or hide
 * the $mode based on the $showhide value.
 *
 * @param int $pageid the pageid to adjust
 * @param int $mode Display constant
 * @param boolean $showhide True = set to display; False = set to hide
 * @return true if success
 */
function page_showhide($pageid, $mode, $showhide = true) {
    $display = get_field('format_page', 'display', 'id', $pageid);

    if ($showhide) {
        return set_field('format_page', 'display', $display | $mode, 'id', $pageid);
    } else {
        return set_field('format_page', 'display', $display ^ $mode, 'id', $pageid);
    }
}

/**
 * Sets the current page for the user
 * in their session.
 *
 * @param int $courseid ID of the current course
 * @param int $pageid ID of the page to set
 * @return int
 **/
function page_set_current_page($courseid, $pageid) {
    global $USER;

    if (!isset($USER->formatpage_display)) {
        $USER->formatpage_display = array();
    }

    return $USER->formatpage_display[$courseid] = $pageid;
}

/**
 * Returns the current page set in the session or
 * returns the default first page.
 *
 * @param int $courseid (Optional) The course in which to check for a page.  Defaults to global $COURSE->id
 * @param boolean $disablehack (Optional) Disable any hacks this funtion may employ
 * @return mixed A page object if found or false
 **/
function page_get_current_page($courseid = 0, $disablehack = true) {
    global $CFG, $USER, $COURSE;

    if (empty($courseid)) {
        $courseid = $COURSE->id;
    }

    // HACK! This method can be called anywhere - so check to see if
    // we are navigating and we are now viewing a new page but have not
    // hit format.php yet (Example: call this method from theme header)
    if (!$disablehack and $pageid = optional_param('page', 0, PARAM_INT)) {
        $url = qualified_me();
        $url = strip_querystring($url);

        // URLs where the format could be displayed
        $locations = array($CFG->wwwroot,
                           $CFG->wwwroot.'/',
                           $CFG->wwwroot.'/index.php',
                           $CFG->wwwroot.'/course/view.php',
                           $CFG->wwwroot.'/course/format/page/format.php');

        // See if we are on a course format page already
        if (in_array($url, $locations)) {
            if ($page = page_validate_pageid($pageid, $courseid)) {
                return $page;
            }
        }
    }

    // Check session for current page ID
    if (isset($USER->formatpage_display[$courseid])) {
        if ($page = page_validate_pageid($USER->formatpage_display[$courseid], $courseid)) {
            return $page;
        }
    }

    // Last try, attempt to get the default page for the course
    if ($page = page_get_default_page($courseid)) {
        return $page;
    }

    return false;
}

/**
 * Finds the top-level parent page of a given page ID.  If a page has no parent(s)
 * then it will return the same page.
 *
 * @param int $pageid The page ID of the page you want to find the parent of
 * @param int $courseid (Optional) ID of the course that the page is in
 * @return object if toplevel parent exists else returns false for no parent
 **/
function page_get_toplevel_parent($pageid, $courseid = NULL) {
    if ($courseid === NULL) {
        if (!$courseid = get_field('format_page', 'courseid', 'id', $pageid)) {
            error('Invalid page ID');
        }
    }
    $page     = false;
    $allpages = page_get_all_pages($courseid, 'flat');

    while ($pageid != 0 and !empty($allpages[$pageid])) {
        $page   = $allpages[$pageid];
        $pageid = $allpages[$pageid]->parent;
    }

    return $page;
}

/**
 * Deletes a page deleting non-referenced block instances, all page items for that page,
 * and moving child pages up the page hierarchy to the parent page at the end of the parent;s
 * children
 *
 * @param int $pageid The page ID of the page to delte
 **/
function page_delete_page($pageid) {
    if (!$page = get_record('format_page', 'id', $pageid)) {
        error('Invalid Page ID');
    }

    // Delete all page items
    if ($pageitems = get_records('format_page_items', 'pageid', $pageid)) {
        foreach($pageitems as $pageitem) {
            page_block_delete($pageitem);
        }
    }

    // Remove from current location
    page_remove_from_ordering($page->id);

    // Need to get the page out of there so we can get
    // proper sortorder value for children
    $return = delete_records('format_page', 'id', $page->id);

    // Now remap the parent id and sortorder of all the child pages.
    if ($return and $children = get_records('format_page', 'parent', $pageid, 'sortorder', 'id')) {
        $sortorder = page_get_next_sortorder($page->parent, $page->courseid);

        foreach($children as $child) {
            $update            = new stdClass;
            $update->id        = $child->id;
            $update->parent    = $page->parent;
            $update->sortorder = $sortorder;
            update_record('format_page', $update);
            $sortorder++;
        }
    }

    return $return;
}

/**
 * This function returns the page objects of the next/previous pages
 * relative to the passed page ID
 *
 * @param int $pageid Base next/previous off of this page ID
 * @param int $courseid (Optional) ID of the course
 */
function page_get_next_previous_pages($pageid, $courseid = NULL) {
    global $COURSE;

    if ($courseid === NULL) {
        $courseid = $COURSE->id;
    }

    $return = new stdClass;
    $return->prev = false;
    $return->next = false;

    if ($pages = page_get_all_pages($courseid, 'flat')) {
        // Remove any unpublished pages
        foreach ($pages as $id => $page) {
            if (!($page->display & DISP_PUBLISH)) {
                unset($pages[$id]);
            }
        }
        if (!empty($pages)) {
            // Search for the pages
            $get    = false;
            $locked = array();
            foreach($pages as $id => $page) {
                if (in_array($page->parent, $locked) or (page_is_locked($page) and !page_is_visible_lock($page))) {
                    $locked[] = $page->id;
                }

                if ($get and !in_array($page->id, $locked)) {
                    // We have seen the id we're looking for
                    $return->next = $page;
                    break;  // quit this business
                }
                if ($id == $pageid) {
                    // We've found the id that we are looking for
                    $get = true;
                }
                if (!$get and !in_array($page->id, $locked)) {
                    // Only if we haven't found what we're looking for
                    $return->prev = $page;
                }
            }
        }
    }
    return $return;
}


/**
 * this function handles all of the necessary session hacks needed by the page course format
 *
 * @param int $courseid course id (used to ensure user has proper capabilities)
 * @param string the action the user is performing
 * @uses $SESSION
 * @uses $USER
 */
function page_handle_session_hacks($courseid, $action) {
    global $SESSION, $USER, $CFG;

    // load up the context for calling has_capability later
    $context = get_context_instance(CONTEXT_COURSE, $courseid);

    // handle any actions that need to push a little state data to the session
    switch($action) {
        case'deletemod':
            if (!confirm_sesskey()) {
                error(get_string('confirmsesskeybad', 'error'));
            }
            if (!isloggedin()) {
                // If on site page, then require_login may not be called
                // At this point, we make sure the user is logged in
                require_login($course->id);
            }
            if (has_capability('moodle/course:manageactivities', $context)) {
                // set some session stuff so we can find our way back to where we were
                $SESSION->cfp = new stdClass;
                $SESSION->cfp->action = 'finishdeletemod';
                $SESSION->cfp->deletemod = required_param('cmid', PARAM_INT);
                $SESSION->cfp->id = $courseid;
                // redirect to delete mod
                redirect($CFG->wwwroot.'/course/mod.php?delete='.$SESSION->cfp->deletemod.'&amp;sesskey='.sesskey());
            }
            break;
    }

    // handle any cleanup as a result of session being pushed from above block
    if (isset($SESSION->cfp)) {
        // the user did something we need to clean up after
        if (!empty($SESSION->cfp->action)) {
            switch ($SESSION->cfp->action) {
                case 'finishdeletemod':
                    if (!isloggedin()) {
                        // If on site page, then require_login may not be called
                        // At this point, we make sure the user is logged in
                        require_login($course->id);
                    }
                    if (has_capability('moodle/course:manageactivities', $context)) {
                        // Get what we need from session then unset it
                        $sessioncourseid = $SESSION->cfp->id;
                        $deletecmid      = $SESSION->cfp->deletemod;
                        unset($SESSION->cfp);

                        // See if the user deleted a module
                        if (!record_exists('course_modules', 'id', $deletecmid)) {
                            // Looks like the user deleted this so clear out corresponding entries in format_page_items
                            if ($pageitems = get_records('format_page_items', 'cmid', $deletecmid)) {
                                foreach ($pageitems as $pageitem) {
                                    page_block_delete($pageitem);
                                }
                            }
                        }
                        if ($courseid == $sessioncourseid and empty($action) and !optional_param('page', 0, PARAM_INT)) {
                            // We are in same course and not performing another action or
                            // looking at a specific page, so redirect back to manage modules 
                            // for a nice workflow
                            $action = 'activities';
                        }
                    }
                    break;
                default:
                    // Doesn't match one of our handled session action hacks
                    unset($SESSION->cfp);
                    break;
            }
        }
    }

    return $action;
}

/**
 * Function called during delete course routines to do any necessary cleanup
 *
 * @param int $courseid ID of the course being deleted
 **/
function page_course_format_delete_course($courseid) {
    $pageids = get_records_menu('format_page', 'courseid', $courseid, '', 'id, nameone');
    if (!empty($pageids)) {
        $pagekeys = array_keys($pageids);
        delete_records_select('format_page_items', 'pageid IN ('.implode(', ', $pagekeys).')');
        delete_records_select('format_page', 'id IN ('.implode(', ', $pagekeys).')');
    }
}

/**
 * Gets the name of a page
 *
 * @param object $page Full page format page
 * @return string
 **/
function page_get_name($page) {
    if (!empty($page->nametwo)) {
        $name = $page->nametwo;
    } else {
        $name = $page->nameone;
    }
    return format_string($name);
}

/**
 * Padds a string with spaces and a hyphen
 *
 * @param string $string The string to be padded
 * @param int $amount The amount of padding to add (if zero, then no padding)
 * @return string
 **/
function page_pad_string($string, $amount) {
    if ($amount == 0) {
        return $string;
    } else {
        return str_repeat('&nbsp;&nbsp;', $amount).'-&nbsp;'.$string;
    }
}

/**
 * Preps a page name for being added to a menu dropdown
 *
 * @param string $name Page name
 * @param int $amount Amount of padding (Page depth for example)
 * @param int $length Can shorten the name so the dropdown does not get too wide (Pass NULL avoid shortening)
 * @return string
 **/
function page_name_menu($name, $amount, $length = 28) {
    $name = format_string($name);
    if ($length !== NULL) {
        $name = shorten_text($name, $length);
    }
    return page_pad_string($name, $amount);
}

/**
 * This function is called when printing the format
 * in /index.php
 *
 * @return void
 **/
function page_frontpage() {
    // Get all of the standard globals - the format script is usually included
    // into a file that has called config.php
    global $CFG, $PAGE, $USER, $SESSION, $COURSE, $SITE;

    $course = get_site();

    if (has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, SITEID))) {
        echo '<div style="text-align:right">'.update_course_icon($course->id).'</div>';
    }
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once($CFG->dirroot.'/course/format/page/format.php');
    print_footer('home');
    die;
}

/**
 * Called from {@link page_print_position()} and it is
 * supposed to print the front page settings in the
 * center column for the site course and only for
 * the default page (EG: the landing page).
 *
 * @return boolean
 **/
function page_frontpage_settings() {
    global $CFG, $SESSION, $SITE, $PAGE, $COURSE;

    // Cheap check first - course ID
    if ($COURSE->id != SITEID) {
        return false;
    }

    // More expensive check - make sure we are viewing default page
    $default = page_get_default_page();
    $current = $PAGE->get_formatpage();

    if (empty($default->id) or empty($current->id) or $default->id != $current->id) {
        return false;
    }

    $editing = $PAGE->user_is_editing();

/// START COPY/PASTE FROM INDEX.PHP

    print_container_start();

/// Print Section
    if ($SITE->numsections > 0) {

        if (!$section = get_record('course_sections', 'course', $SITE->id, 'section', 1)) {
            delete_records('course_sections', 'course', $SITE->id, 'section', 1); // Just in case
            $section->course = $SITE->id;
            $section->section = 1;
            $section->summary = '';
            $section->sequence = '';
            $section->visible = 1;
            $section->id = insert_record('course_sections', $section);
        }

        if (!empty($section->sequence) or !empty($section->summary) or $editing) {
            print_box_start('generalbox sitetopic');

            /// If currently moving a file then show the current clipboard
            if (ismoving($SITE->id)) {
                $stractivityclipboard = strip_tags(get_string('activityclipboard', '', addslashes($USER->activitycopyname)));
                echo '<p><font size="2">';
                echo "$stractivityclipboard&nbsp;&nbsp;(<a href=\"course/mod.php?cancelcopy=true&amp;sesskey=$USER->sesskey\">". get_string('cancel') .'</a>)';
                echo '</font></p>';
            }

            $options = NULL;
            $options->noclean = true;
            echo format_text($section->summary, FORMAT_HTML, $options);

            if ($editing) {
                $streditsummary = get_string('editsummary');
                echo "<a title=\"$streditsummary\" ".
                     " href=\"course/editsection.php?id=$section->id\"><img src=\"$CFG->pixpath/t/edit.gif\" ".
                     " class=\"iconsmall\" alt=\"$streditsummary\" /></a><br /><br />";
            }

            get_all_mods($SITE->id, $mods, $modnames, $modnamesplural, $modnamesused);
            print_section($SITE, $section, $mods, $modnamesused, true);

            if ($editing) {
                print_section_add_menus($SITE, $section->section, $modnames);
            }
            print_box_end();
        }
    }

    if (isloggedin() and !isguest() and isset($CFG->frontpageloggedin)) {
        $frontpagelayout = $CFG->frontpageloggedin;
    } else {
        $frontpagelayout = $CFG->frontpage;
    }

    foreach (explode(',',$frontpagelayout) as $v) {
        switch ($v) {     /// Display the main part of the front page.
            case FRONTPAGENEWS:
                if ($SITE->newsitems) { // Print forums only when needed
                    require_once($CFG->dirroot .'/mod/forum/lib.php');

                    if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
                        error('Could not find or create a main news forum for the site');
                    }

                    if (!empty($USER->id)) {
                        $SESSION->fromdiscussion = $CFG->wwwroot;
                        if (forum_is_subscribed($USER->id, $newsforum)) {
                            $subtext = get_string('unsubscribe', 'forum');
                        } else {
                            $subtext = get_string('subscribe', 'forum');
                        }
                        print_heading_block($newsforum->name);
                        echo '<div class="subscribelink"><a href="mod/forum/subscribe.php?id='.$newsforum->id.'">'.$subtext.'</a></div>';
                    } else {
                        print_heading_block($newsforum->name);
                    }

                    forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'plain', 'p.modified DESC');
                }
            break;

            case FRONTPAGECOURSELIST:

                if (isloggedin() and !has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM)) and !isguest() and empty($CFG->disablemycourses)) {
                    print_heading_block(get_string('mycourses'));
                    print_my_moodle();
                } else if ((!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM)) and !isguest()) or (count_records('course') <= FRONTPAGECOURSELIMIT)) {
                    // admin should not see list of courses when there are too many of them
                    print_heading_block(get_string('availablecourses'));
                    print_courses(0);
                }
            break;

            case FRONTPAGECATEGORYNAMES:

                print_heading_block(get_string('categories'));
                print_box_start('generalbox categorybox');
                print_whole_category_list(NULL, NULL, NULL, -1, false);
                print_box_end();
                print_course_search('', false, 'short');
            break;

            case FRONTPAGECATEGORYCOMBO:

                print_heading_block(get_string('categories'));
                print_box_start('generalbox categorybox');
                print_whole_category_list(NULL, NULL, NULL, -1, true);
                print_box_end();
                print_course_search('', false, 'short');
            break;

            case FRONTPAGETOPICONLY:    // Do nothing!!  :-)
            break;

        }
        // echo '<br />';  REMOVED FOR THE FORMAT
    }

    print_container_end();

/// END COPY/PASTE FROM INDEX.PHP

    return true;
}

/**
 * Organizes modules array(Mod Name Plural => instances in course)
 * and sorts by the plural name and by the instance name
 *
 * @param object $course Course
 * @param string $field Specify a field from the instance object to return, otherwise whole instance is returned
 * @return array
 **/
function page_get_modules($course, $field = NULL) {
    $modinfo  = get_fast_modinfo($course);
    $function = create_function('$a, $b', 'return strnatcmp($a->name, $b->name);');
    $modules  = array();
    if (!empty($modinfo->instances)) {
        foreach ($modinfo->instances as $instances) {
            // Run names through filter for proper sorting
            foreach ($instances as $key => $instance) {
                $instances[$key]->name = format_string($instances[$key]->name);
            }

            uasort($instances, $function);

            foreach ($instances as $instance) {
                if (empty($modules[$instance->modplural])) {
                    $modules[$instance->modplural] = array();
                }
                if (is_null($field)) {
                    $modules[$instance->modplural][$instance->id] = $instance;
                } else {
                    $modules[$instance->modplural][$instance->id] = $instance->$field;
                }
            }
        }
    }

    // Sort by key (module name)
    ksort($modules);

    return $modules;
}

/**
 * This function fixes the block weights for a given course.
 *
 * @param int $courseid the course id to fix block weights for
 * @return void
 */
function page_fix_block_weights($courseid) {
    $result = true;

    if ($blocks = get_records_select('block_instance', "pageid = $courseid and pagetype = 'course-view'", 'weight', 'id, position')) {

        $organized = array();
        foreach ($blocks as $block) {
            $organized[$block->position][] = $block->id;
        }
        foreach ($organized as $position => $blocks) {
            $weight = 0;
            foreach ($blocks as $blockid) {
                $result = $result and set_field('block_instance', 'weight', $weight, 'id', $blockid);
                $weight++;
            }
        }
    }

    return $result;
}

/**
 * This function fixes any issues with the format_page sortorder field in a course
 *
 * @param int $courseid the course id to fix the sortorder for
 * @param boolean
 */
function page_fix_page_sortorder($courseid) {
    $result = true;

    if ($pages = get_records('format_page', 'courseid', $courseid, 'parent, sortorder', 'id, parent, sortorder')) {
        $sortorder = $parentid = 0;
        foreach ($pages as $page) {
            if ($page->parent != $parentid) {
                // moving to a new parent section - reset $sortorder
                $sortorder = 0;
                $parentid  = $page->parent;
            }
            if ($page->sortorder != $sortorder) {
                // Needs fixing
                $result = $result and set_field('format_page', 'sortorder', $sortorder, 'id', $page->id);
            }
            $sortorder++;
        }
    }

    return $result;
}

/**
 * Fixes format_page_items sortorder field for a
 * course.
 *
 * @param int $courseid ID of the course to fix
 * @return boolean
 **/
function page_fix_pageitem_sortorder($courseid) {
    global $CFG;

    $result = true;

    if ($pageitems = get_records_sql("SELECT i.id, i.pageid, i.position, i.sortorder
                                        FROM {$CFG->prefix}format_page p,
                                             {$CFG->prefix}format_page_items i
                                       WHERE p.id = i.pageid
                                         AND p.courseid = $courseid
                                    ORDER BY i.pageid, i.position, i.sortorder")) {

        $sortorder = 0;
        $position  = '';
        $pageid    = 0;
        foreach ($pageitems as $pageitem) {
            if ($pageid != $pageitem->pageid or $pageitem->position != $position) {
                // We are changing pages or positions - either way reset sortorder
                $sortorder = 0;
                $pageid    = $pageitem->pageid;
                $position  = $pageitem->position;
            }
            if ($pageitem->sortorder != $sortorder) {
                $result = $result and set_field('format_page_items', 'sortorder', $sortorder, 'id', $pageitem->id);
            }
            $sortorder++;
        }
    }

    return $result;
}

/**
 * Public API to page locks: Determine
 * if the page is locked or not
 *
 * @param object $page The page to test
 * @return boolean
 **/
function page_is_locked($page) {
    global $CFG;

    static $cache   = array();
    static $include = false;

    if (!isset($cache[$page->id])) {
        if (!empty($page->locks) and !has_capability('format/page:editpages', get_context_instance(CONTEXT_COURSE, $page->courseid))) {
            if (!$include) {
                require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');
                $include = true;
            }

            $cache[$page->id] = format_page_lock::is_locked($page);
        } else {
            $cache[$page->id] = false;
        }
    }
    return $cache[$page->id];
}

/**
 * Public API to page locks: determine
 * if the page lock is visible - should
 * only be called on pages that do
 * have a lock
 *
 * @param object $page The page to test
 * @return boolean
 **/
function page_is_visible_lock($page) {
    global $CFG;

    static $cache   = array();
    static $include = false;

    if (!isset($cache[$page->id])) {
        if (!empty($page->locks)) {
            if (!$include) {
                require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');
                $include = true;
            }
            $cache[$page->id] = format_page_lock::is_visible_lock($page);
        } else {
            $cache[$page->id] = true;
        }
    }
    return $cache[$page->id];
}

/**
 * Public API to page locks: Print lock
 * prerequisites to the user
 *
 * @param object $page The page to print reqs for
 * @return void
 **/
function page_print_lock_prerequisites($page) {
    global $CFG;

    require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');

    format_page_lock::print_lock_prerequisites($page);
}

?>