<?php // $Id: pagelib.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
/**
 * Page class for format_page
 *
 * @author Mark Nielsen
 * @version $Id: pagelib.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->dirroot.'/course/lib.php'); // Needed for some blocks

if (!defined('BLOCK_POS_CENTER')) {
    define('BLOCK_POS_CENTER', 'c');
}

/**
 * Remapping PAGE_COURSE_VIEW to format_page class
 *
 **/
page_map_class(PAGE_COURSE_VIEW, 'format_page');

/**
 * Add the page types defined in this file
 *
 **/
$DEFINEDPAGES = array(PAGE_COURSE_VIEW);

/**
 * Class that models the behavior of a format page
 *
 * @package format_page
 **/
class format_page extends page_course {
    /**
     * Full format_page db record
     *
     * @var object
     **/
    var $formatpage = NULL;

    /**
     * format_page_item record ID
     *
     * @var string
     **/
    var $pageitemid = 0;

    /**
     * Local method - set the member formatpage
     *
     * @return void
     **/
    function set_formatpage($formatpage) {
        $this->formatpage = $formatpage;
    }

    /**
     * Local method - set the member pageitemid
     * This is very important as it is used in URL
     * construction.
     *
     * @return void
     **/
    function set_pageitemid($pageitemid) {
        $this->pageitemid = $pageitemid;
    }

    /**
     * Local method - returns the current
     * format_page
     *
     * @return object
     **/
    function get_formatpage() {
        if ($this->formatpage == NULL) {
            global $CFG;

            require_once($CFG->dirroot.'/course/format/page/lib.php');

            if (!empty($this->courserecord)) {
                $courseid = $this->courserecord->id;
            } else {
                $courseid = 0;
            }

            if ($currentpage = page_get_current_page($courseid)) {
                $this->formatpage = $currentpage;
            } else {
                $this->formatpage     = new stdClass;
                $this->formatpage->id = 0;
            }
        }
        return $this->formatpage;
    }

    /**
     * Override - this is a three column format
     *
     * @return array
     **/
    function blocks_get_positions() {
        return array(BLOCK_POS_LEFT, BLOCK_POS_CENTER, BLOCK_POS_RIGHT);
    }

    /**
     * Override - we like center because... well we do!
     *
     * @return char
     **/
    function blocks_default_position() {
        return BLOCK_POS_CENTER;
    }

    /**
     * Override - since we have three columns
     * we need to take that into account here
     *
     * @param object $instance Block instance
     * @param int $move Move constant (BLOCK_MOVE_RIGHT or BLOCK_MOVE_LEFT). This is the direction that we are moving
     * @return char
     **/
    function blocks_move_position(&$instance, $move) {
        if ($instance->position == BLOCK_POS_LEFT and $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_CENTER;
        } else if ($instance->position == BLOCK_POS_RIGHT and $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_CENTER;
        } else if ($instance->position == BLOCK_POS_CENTER and $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_LEFT;
        } else if ($instance->position == BLOCK_POS_CENTER and $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_RIGHT;
        }
        return $instance->position;
    }

    /**
     * Override - If pageitemid is set, then
     * return path to format.php (this is to handle
     * blockactions.  If we are at the site, then
     * path to index.php and our default is
     * course/view.php
     *
     * @return string
     **/
    function url_get_path() {
        global $CFG;

        if ($this->pageitemid) {
            return $CFG->wwwroot.'/course/format/page/format.php';
        } else if ($this->id == SITEID) {
            return $CFG->wwwroot.'/index.php';
        } else {
            return $CFG->wwwroot.'/course/view.php';
        }
    }

    /**
     * Override - VERY IMPORTANT
     * Include page and pageitemid (if set) in the params
     * so the format knows what page we are on and
     * so it can uniquely identify the block_instance (EG: page item)
     * This is needed because multiple page items can relate to a
     * single block instance
     *
     * @return array
     **/
    function url_get_parameters() {
        $page = $this->get_formatpage();

        $params = array('id' => $this->id, 'page' => $page->id);

        if ($this->pageitemid) {
            $params['pageitemid'] = $this->pageitemid;
        }

        return $params;
    }

    /**
     * Local method - Builds an appropriate URL
     *
     * Pass as many paramater name and value pairs
     * as you like.  This function will contstruct
     * a URL with them
     *
     * If param name id is not passed and this is
     * not the site front page, then the id param is
     * automatically added.
     *
     * @return string
     **/
    function url_build() {
        $args = func_get_args();

        $key    = '';
        $params = array();
        for ($i = 0; $i < func_num_args(); $i++) {
            $arg = $args[$i];

            if ($i % 2 == 0) {
                $key = $arg;
                $params[$arg] = '';
            } else {
                $params[$key] = $arg;
            }
        }

        if ($this->id != SITEID and !array_key_exists('id', $params)) {
            $pairs = array("id=$this->id");
        } else {
            $pairs = array();
        }

        foreach ($params as $name => $value) {
            $pairs[] = "$name=$value";
        }
        return $this->url_get_path().'?'.implode('&amp;', $pairs);
    }

    /**
     * Override - can the user edit?
     *
     * @return boolean
     **/
    function user_allowed_editing() {
        if (has_capability('format/page:editpages', get_context_instance(CONTEXT_COURSE, $this->id))) {
            return true;
        }
        return parent::user_allowed_editing();
    }

    /**
     * Override - cache result
     *
     * @return boolean
     **/
    function user_is_editing() {
        static $cache = NULL;

        if ($cache === NULL) {
            $cache = parent::user_is_editing();
        }
        return $cache;
    }

    /**
     * Prints the tabs for the format page type
     *
     * @param string $currenttab Tab to highlight
     * @return void
     **/
    function print_tabs($currenttab = 'layout') {
        $tabs = $row = $inactive = $active = array();

        $row[] = new tabobject('view', $this->url_get_full(), get_string('editpage', 'format_page'));
        $row[] = new tabobject('addpage', $this->url_build('action', 'editpage'), get_string('addpage', 'format_page'));
        $row[] = new tabobject('manage', $this->url_build('action', 'manage'), get_string('manage', 'format_page'), '', true);
        $row[] = new tabobject('activities', $this->url_build('action', 'activities'), get_string('managemods', 'format_page'));
        $tabs[] = $row;

        if (in_array($currenttab, array('layout', 'settings', 'view'))) {
            $active[] = 'view';

            $row = array();
            $row[] = new tabobject('layout', $this->url_get_full(), get_string('layout', 'format_page'));
            $row[] = new tabobject('settings', $this->url_get_full(array('action' => 'editpage')), get_string('settings', 'format_page'));
            $tabs[] = $row;
        }

        print_tabs($tabs, $currenttab, $inactive, $active);
    }
}
?>