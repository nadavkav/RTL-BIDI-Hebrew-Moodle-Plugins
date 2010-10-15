<?php
/**
 * Pagemenu's Local Library
 *
 * @author Mark Nielsen
 * @version $Id: locallib.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * Get the base link class, almost always used
 **/
require_once($CFG->dirroot.'/mod/pagemenu/link.class.php');

/**
 * Get link types
 *
 * @return array
 **/
function pagemenu_get_links() {
    return array('link', 'module', 'page', 'ticket');
}

/**
 * Get an array of link type classes
 *
 * @return array
 **/
function pagemenu_get_link_classes() {
    $return = array();
    foreach(pagemenu_get_links() as $type) {
        $return[$type] = mod_pagemenu_link::factory($type);
    }
    return $return;
}

/**
 * Get available renderers
 *
 * @return array
 **/
function pagemenu_get_renderers() {
    return array('list', 'select');
}

/**
 * Get available renderers for a menu
 *
 * @return array
 **/
function pagemenu_get_renderer_menu() {
    $renderers = pagemenu_get_renderers();

    $options = array();
    foreach ($renderers as $renderer) {
        $options[$renderer] = get_string("render$renderer", 'pagemenu');
    }
    return $options;
}

/**
 * Returns course module, course and module instance.
 *
 * @param int $cmid Course module ID
 * @param int $pagemenuid pagemenu module ID
 * @return array of objects
 **/
function pagemenu_get_basics($cmid = 0, $pagemenuid = 0) {
    if ($cmid) {
        if (!$cm = get_coursemodule_from_id('pagemenu', $cmid)) {
            error('Course Module ID was incorrect');
        }
        if (!$course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (!$pagemenu = get_record('pagemenu', 'id', $cm->instance)) {
            error('Course module is incorrect');
        }

    } else if ($pagemenuid) {
        if (!$pagemenu = get_record('pagemenu', 'id', $pagemenuid)) {
            error('Course module is incorrect');
        }
        if (!$course = get_record('course', 'id', $pagemenu->course)) {
            error('Course is misconfigured');
        }
        if (!$cm = get_coursemodule_from_instance('pagemenu', $pagemenu->id, $course->id)) {
            error('Course Module ID was incorrect');
        }

    } else {
        error('No course module ID or pagemenu ID were passed');
    }

    return array($cm, $course, $pagemenu);
}

/**
 * Print the standard header for pagemenu module
 *
 * @uses $CFG
 * @uses $USER tabs.php requires it
 * @param object $cm Course module record object
 * @param object $course Couse record object
 * @param object $pagemenu pagemenu module record object
 * @param string $currenttab File location and tab to be selected
 * @param string $focus Focus
 * @param boolean $showtabs Display tabs yes/no
 * @return void
 **/
function pagemenu_print_header($cm, $course, $pagemenu, $currenttab = 'view', $focus = '', $showtabs = true) {
    global $CFG, $USER;

    $strpagemenus = get_string('modulenameplural', 'pagemenu');
    $strpagemenu  = get_string('modulename', 'pagemenu');
    $strname      = format_string($pagemenu->name);

/// Log it!
    add_to_log($course->id, 'pagemenu', $currenttab, "$currenttab.php?id=$cm->id", $strname, $cm->id);


/// Print header, heading, tabs and messages
    print_header_simple($strname, $strname, build_navigation('',$cm), $focus, '', true, update_module_button($cm->id, $course->id, $strpagemenu), navmenu($course, $cm));

    print_heading($strname);

    if ($showtabs) {
        pagemenu_print_tabs($cm, $currenttab);
    }

    pagemenu_print_messages();
}

/**
 * Prints the tabs for the module
 *
 * @return void
 **/
function pagemenu_print_tabs($cm, $currenttab) {
    global $CFG;

    if (has_capability('mod/pagemenu:manage', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        $tabs = $row = $inactive = array();

        $row[] = new tabobject('view', "$CFG->wwwroot/mod/pagemenu/view.php?id=$cm->id", get_string('view', 'pagemenu'));
        $row[] = new tabobject('edit', "$CFG->wwwroot/mod/pagemenu/edit.php?id=$cm->id", get_string('edit', 'pagemenu'));

        $tabs[] = $row;

        print_tabs($tabs, $currenttab, $inactive);
    }
}

/**
 * pagemenu Message Functions
 *
 **/

/**
 * Sets a message to be printed.  Messages are printed
 * by calling {@link pagemenu_print_messages()}.
 *
 * @uses $SESSION
 * @param string $message The message to be printed
 * @param string $class Class to be passed to {@link notify()}.  Usually notifyproblem or notifysuccess.
 * @param string $align Alignment of the message
 * @return boolean
 **/
function pagemenu_set_message($message, $class="notifyproblem", $align='center') {
    global $SESSION;

    if (empty($SESSION->messages) or !is_array($SESSION->messages)) {
        $SESSION->messages = array();
    }

    $SESSION->messages[] = array($message, $class, $align);

    return true;
}

/**
 * Print all set messages.
 *
 * See {@link pagemenu_set_message()} for setting messages.
 *
 * Uses {@link notify()} to print the messages.
 *
 * @uses $SESSION
 * @return boolean
 **/
function pagemenu_print_messages() {
    global $SESSION;

    if (empty($SESSION->messages)) {
        // No messages to print
        return true;
    }

    foreach($SESSION->messages as $message) {
        notify($message[0], $message[1], $message[2]);
    }

    // Reset
    unset($SESSION->messages);

    return true;
}

/**
 * Link Management Functions
 *
 **/

/**
 * Gets the first link ID
 *
 * @param int $pagemenuid ID of a pagemenu instance
 * @return mixed
 **/
function pagemenu_get_first_linkid($pagemenuid) {
    return get_field('pagemenu_links', 'id', 'pagemenuid', $pagemenuid, 'previd', 0);
}

/**
 * Gets the last link ID
 *
 * @param int $pagemenuid ID of a pagemenu instance
 * @return mixed
 **/
function pagemenu_get_last_linkid($pagemenuid) {
    return get_field('pagemenu_links', 'id', 'pagemenuid', $pagemenuid, 'nextid', 0);
}

/**
 * Append a link to the end of the list
 *
 * @param object $link A link ready for insert with previd/nextid set to 0
 * @param int $previd (Optional) If the last link ID is know, then pass it here  DO NOT PASS ANY OTHER ID!!!
 * @return object
 **/
function pagemenu_append_link($link, $previd = NULL) {
    if ($previd !== NULL) {
        $link->previd = $previd;
    } else if ($lastid = pagemenu_get_last_linkid($link->pagemenuid)) {
        // Add new one after
        $link->previd = $lastid;
    } else {
        $link->previd = 0; // Just make sure
    }

    if (!$link->id = insert_record('pagemenu_links', $link)) {
        error('Failed to insert link');
    }
    // Update the previous link to look to the new link
    if ($link->previd) {
        if (!set_field('pagemenu_links', 'nextid', $link->id, 'id', $link->previd)) {
            error('Failed to update link order');
        }
    }

    return $link;
}

/**
 * Deletes a link and all associated data
 * Also maintains ordering
 *
 * @param int $linkid ID of the link to delete
 * @return boolean
 **/
function pagemenu_delete_link($linkid) {
    pagemenu_remove_link_from_ordering($linkid);

    if (!delete_records('pagemenu_link_data', 'linkid', $linkid)) {
        error('Failed to delete link data');
    }
    if (!delete_records('pagemenu_links', 'id', $linkid)) {
        error('Failed to delete link data');
    }
    return true;
}

/**
 * Move a link to a new position in the ordering
 *
 * @param object $pagemenu Page menu instance
 * @param int $linkid ID of the link we are moving
 * @param int $after ID of the link we are moving our link after (can be 0)
 * @return boolean
 **/
function pagemenu_move_link($pagemenu, $linkid, $after) {
    $link = new stdClass;
    $link->id = $linkid;

    // Remove the link from where it was (Critical: this first!)
    pagemenu_remove_link_from_ordering($link->id);

    if ($after == 0) {
        // Adding to front - get the first link
        if (!$firstid = pagemenu_get_first_linkid($pagemenu->id)) {
            error('Could not find first link ID');
        }
        // Point the first link back to our new front link
        if (!set_field('pagemenu_links', 'previd', $link->id, 'id', $firstid)) {
            error('Failed to update link ordering');
        }
        // Set prev/next
        $link->nextid = $firstid;
        $link->previd = 0;
    } else {
        // Get the after link
        if (!$after = get_record('pagemenu_links', 'id', $after)) {
            error('Invalid Link ID');
        }
        // Point the after link to our new link
        if (!set_field('pagemenu_links', 'nextid', $link->id, 'id', $after->id)) {
            error('Failed to update link ordering');
        }
        // Set the next link in the ordering to look back correctly
        if ($after->nextid) {
            if (!set_field('pagemenu_links', 'previd', $link->id, 'id', $after->nextid)) {
                error('Failed to update link ordering');
            }
        }
        // Set next/prev
        $link->previd = $after->id;
        $link->nextid = $after->nextid;
    }

    if (!update_record('pagemenu_links', $link)) {
        error('Failed to update link');
    }

    return true;
}

/**
 * Removes a link from the link ordering
 *
 * @param int $linkid ID of the link to remove
 * @return boolean
 **/
function pagemenu_remove_link_from_ordering($linkid) {
    if (!$link = get_record('pagemenu_links', 'id', $linkid)) {
        error('Invalid Link ID');
    }
    // Point the previous link to the one after this link
    if ($link->previd) {
        if (!set_field('pagemenu_links', 'nextid', $link->nextid, 'id', $link->previd)) {
            error('Failed to update link ordering');
        }
    }
    // Point the next link to the one before this link
    if ($link->nextid) {
        if (!set_field('pagemenu_links', 'previd', $link->previd, 'id', $link->nextid)) {
            error('Failed to update link ordering');
        }
    }
    return true;
}

/**
 * Generates a menu
 *
 * @param int $pagemenuid ID of the instance to print
 * @param string $render The renderer to use
 * @param boolean $menuinfo True, returns menu information object.  False, return menu HTML
 * @param array $links All of the links used by this menu
 * @param array $data All of the data for the links used by this menu
 * @param array $firstlinkid This is the first link ID for a pagemenu
 * @return mixed
 **/
function pagemenu_build_menu($pagemenuid, $render = 'list', $returnmenu = false, $links = NULL, $data = NULL, $firstlinkid = false) {
    global $CFG;

    $render = clean_param($render, PARAM_SAFEDIR);

    $classname = "mod_pagemenu_render_$render";
    $classfile = "$CFG->dirroot/mod/pagemenu/render/$render.class.php";
    if (file_exists($classfile)) {
        require_once($classfile);
    }
    if (!class_exists($classname)) {
        error("Programmer error: invalid renderer: $render");
    }

    $menu = new $classname($pagemenuid, $links, $data, $firstlinkid);

    if ($returnmenu) {
        return $menu;
    }
    return $menu->to_html();
}

/**
 * Bulk menu builder
 *
 * @param array $pagemenus An array of pagemenu course module records with id, instance and visible set
 * @param string $render The renderer to use for all menus
 * @param boolean $menuinfo True, returns menu information object.  False, return menu HTML
 * @param int $courseid ID of the course that the menus belong
 * @return array
 **/
function pagemenu_build_menus($pagemenus, $render = 'list', $menuinfo = false, $courseid = NULL) {
    global $COURSE;

    if ($courseid === NULL) {
        $courseid = $COURSE->id;
    }

/// Filter out the menus that the user cannot see

    $canviewhidden = has_capability('moodle/course:viewhiddenactivities', get_context_instance(CONTEXT_COURSE, $courseid));

    // Load all the context instances at once
    $instances = get_context_instance(CONTEXT_MODULE, array_keys($pagemenus));

    $pagemenuids = array();
    foreach ($pagemenus as $pagemenu) {
        if (has_capability('mod/pagemenu:view', $instances[$pagemenu->id]) and ($pagemenu->visible or $canviewhidden)) {
            $pagemenuids[$pagemenu->id] = $pagemenu->instance;
        }
    }

    if (empty($pagemenuids)) {
        // Cannot see any of them
        return false;
    }

/// Start fetching links and link data for ALL of the menus
    if (!$links = get_records_list('pagemenu_links', 'pagemenuid', implode(',', $pagemenuids))) {
        // None of the menus have links...
        return false;
    }

    $data = pagemenu_get_link_data($links);

/// Find all the first link IDs - this avoids going to the db
/// for each menu or looping through all links for each module
    $firstlinkids = array();
    foreach ($links as $link) {
        if ($link->previd == 0) {
            $firstlinkids[$link->pagemenuid] = $link->id;
        }
    }

    $menus = array();
    foreach ($pagemenuids as $cmid => $pagemenuid) {
        if (array_key_exists($pagemenuid, $firstlinkids)) {
            $firstlinkid = $firstlinkids[$pagemenuid];
        } else {
            $firstlinkid = false;
        }
        $menus[$cmid] = pagemenu_build_menu($pagemenuid, $render, $menuinfo, $links, $data, $firstlinkid);
    }

    return $menus;
}

/**
 * Gets link data for all passed links and organizes the records
 * in an array keyed on the linkid.
 *
 * @param array $links An array of links with the keys = linkid
 * @return array
 **/
function pagemenu_get_link_data($links) {
    $organized = array();

    if ($data = get_records_list('pagemenu_link_data', 'linkid', implode(',', array_keys($links)))) {

        foreach ($data as $datum) {
            if (!array_key_exists($datum->linkid, $organized)) {
                $organized[$datum->linkid] = array();
            }

            $organized[$datum->linkid][] = $datum;
        }
    }

    return $organized;
}

/**
 * Helper function to handle edit actions
 *
 * @param object $pagemenu Page menu instance
 * @param string $action Action that is being performed
 * @return boolean If return true, then a redirect will occure (in edit.php at least)
 **/
function pagemenu_handle_edit_action($pagemenu, $action = NULL) {
    global $CFG;

    if (!confirm_sesskey()) {
        error(get_string('confirmsesskeybad', 'error'));
    }

    $linkid = required_param('linkid', PARAM_INT);

    if ($action === NULL) {
        $action = required_param('action', PARAM_ALPHA);
    }

    switch ($action) {
        case 'edit':
        case 'move':
            return false;
            break;
        case 'movehere':
            $after = required_param('after', PARAM_INT);
            pagemenu_move_link($pagemenu, $linkid, $after);
            pagemenu_set_message(get_string('linkmoved', 'pagemenu'), 'notifysuccess');
            break;
        case 'delete':
            pagemenu_delete_link($linkid);
            pagemenu_set_message(get_string('linkdeleted', 'pagemenu'), 'notifysuccess');
            break;
        default:
            error('Inavlid action: '.$action);
            break;
    }

    return true;
}

?>