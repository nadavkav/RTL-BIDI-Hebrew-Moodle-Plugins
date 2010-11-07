<?php
/**
 * Page module block external library
 *
 * @author Mark Nielsen
 * @version $Id: lib.php,v 1.1 2009/12/21 00:52:57 michaelpenne Exp $
 * @package block_page_module
 **/

/**
 * Our global cache variable
 */
$BLOCK_PAGE_MODULE;

/**
 * External function for retrieving module data.
 *
 * Using external method so we can cache results
 * to improve performance for all page_module
 * instances.
 *
 * @param int $cmid Course Module ID
 * @return array
 **/
function block_page_module_init($cmid) {
    global $COURSE, $CFG, $PAGE, $BLOCK_PAGE_MODULE;

    static $page = false, $baseurl = '';

    if (!$page) {
        if (!empty($PAGE) and get_class($PAGE) == 'format_page') {
            $page = $PAGE->get_formatpage();
        } else {
            require_once($CFG->dirroot.'/course/format/page/lib.php');

            if (!$page = page_get_current_page()) {
                $page = new stdClass;
                $page->id = 0;
            }
        }
        if ($COURSE->id == SITEID) {
            $baseurl = "$CFG->wwwroot/index.php?id=$COURSE->id&amp;page=$page->id";
        } else {
            $baseurl = "$CFG->wwwroot/course/view.php?id=$COURSE->id&amp;page=$page->id";
        }

        if (!empty($page->id)) {
            // Since we know what page will be printed, lets
            // get all of our records in bulk and cache the results
            if ($cms = get_records_sql("SELECT c.*
                                           FROM {$CFG->prefix}course_modules c,
                                                {$CFG->prefix}format_page p,
                                                {$CFG->prefix}format_page_items i
                                          WHERE i.cmid = c.id
                                            AND p.id = i.pageid
                                            AND p.id = $page->id")) {
                // Save for later
                $BLOCK_PAGE_MODULE['cms'] = $cms;

                if ($modules = get_records('modules')) {
                    // Save for later
                    $BLOCK_PAGE_MODULE['modules'] = $modules;

                    $mods = array();
                    foreach ($cms as $cm) {
                        $mods[$modules[$cm->module]->name][] = $cm->instance;
                    }
                    $instances = array();
                    foreach ($mods as $modname => $instanceids) {
                        if ($records = get_records_list($modname, 'id', implode(',', $instanceids))) {
                            $instances[$modname] = $records;
                        }
                    }
                    // Save for later
                    $BLOCK_PAGE_MODULE['instances'] = $instances;
                }
            }
        } else {
            // OK, we cannot do anything cool, make sure we dont break rest of the script
            $BLOCK_PAGE_MODULE = array('cms' => array(), 'modules' => array(), 'instances' => array());
        }
    }
    if (!$cm = block_page_module_get_cm($cmid, $page->id)) {
        return false;
    }
    if (!$module = block_page_module_get_module($cm->module)) {
        return false;
    }
    if (!$moduleinstance = block_page_module_get_instance($module->name, $cm->instance)) {
        return false;
    }

    return array($cm, $module, $moduleinstance, $COURSE, $page, $baseurl);
}

/**
 * Get the Course Module Record
 *
 * @param int $cmid Course Module ID
 * @return mixed
 **/
function block_page_module_get_cm($cmid) {
    global $BLOCK_PAGE_MODULE;

    $cms = &$BLOCK_PAGE_MODULE['cms'];

    if (empty($cms[$cmid])) {
        if (!$cm = get_record('course_modules', 'id', $cmid)) {
            return false;
        }
        $cms[$cm->id] = $cm;
    }

    return $cms[$cmid];
}

/**
 * Get the Module Record
 *
 * @param int $moduleid Module ID
 * @return mixed
 **/
function block_page_module_get_module($moduleid) {
    global $BLOCK_PAGE_MODULE;

    $modules = &$BLOCK_PAGE_MODULE['modules'];

    if (empty($modules[$moduleid])) {
        if (!$module = get_record('modules', 'id', $moduleid)) {
            return false;
        }
        $modules[$module->id] = $module;
    }

    return $modules[$moduleid];
}

/**
 * Get the Module Instance Record
 *
 * @param string $name Module name
 * @param int $id instance ID
 * @return mixed
 **/
function block_page_module_get_instance($name, $id) {
    global $BLOCK_PAGE_MODULE;

    $instances = &$BLOCK_PAGE_MODULE['instances'];

    if (empty($instances[$name]) or empty($instances[$name][$id])) {
        if (!$moduleinstance = get_record($name, 'id', $id)) {
            return false;
        }
        $instances[$name][$id] = $moduleinstance;
    }

    return $instances[$name][$id];
}

/**
 * Call a page item hook.
 *
 * Locations where the hook can be located:
 *    mod/modname/pageitem.php
 *    course/format/page/plugins/pageitem.modname.php
 *
 * If above fail, will call default method in course/format/page/plugins/pageitem.php
 *
 * @param string $module Module name to call the hook for
 * @param string $method Function that will be called (A prefix will be added)
 * @param mixed $args This will be passed to the hook function
 * @return mixed
 **/
function block_page_module_hook($module, $method, $args = array()) {
    global $CFG;

    $result = false;

    if (!is_array($args)) {
        $args = array($args);
    }

    // Path and function mappings
    $paths = array("$CFG->dirroot/mod/$module/pageitem.php"
                        => "format_page_pageitem_{$module}",
                   "$CFG->dirroot/course/format/page/plugin/pageitem/$module.php"
                        => "format_page_pageitem_{$module}",
                   "$CFG->dirroot/course/format/page/plugin/pageitem.php"
                        => "format_page_pageitem");

    foreach ($paths as $path => $class) {
        if (file_exists($path)) {
            require_once($path);

            if (class_exists($class)) {
                $pageitem = new $class();
                $result   = call_user_func_array(array($pageitem, $method), $args);
                break;
            }
        }
    }

    return $result;
}
?>