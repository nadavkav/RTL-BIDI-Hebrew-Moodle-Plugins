<?php
/**
 * Base Render Class
 *
 * @author Mark Nielsen
 * @version $Id: render.class.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
 * @package pagemenu
 **/

abstract class mod_pagemenu_render {
    /**
     * Page menu instance ID
     *
     * @var string
     **/
    protected $pagemenuid;

    /**
     * Link classes that belong to the menu
     *
     * @var array
     **/
    protected $links = array();

    /**
     * If the rendered menu is active or not
     *
     * @var boolean
     **/
    protected $active = false;

    /**
     * The menu as HTML
     *
     * @var string
     **/
    protected $html = '';

    /**
     * Menu items generated from the links
     *
     * @var array
     **/
    protected $menuitems = array();

    /**
     * Descend down the whole menu structure or not
     *
     * @var boolean
     **/
    protected $descend = false;

    /**
     * Render with editing turned on
     *
     * @var boolean
     **/
    protected $editing = false;

    /**
     * Constructor - basic setup
     *
     * @param int $pagemenuid Page menu instance ID
     * @param array $links Page menu link records that belong to this page menu
     * @param array $data Link data for the links
     * @param int $firstlinkid First link ID
     * @return void
     **/
    public function __construct($pagemenuid, $links = NULL, $data = NULL, $firstlinkid = false) {
        $this->pagemenuid = $pagemenuid;

        if ($links === NULL) {
            $links = get_records('pagemenu_links', 'pagemenuid', $this->pagemenuid);
        }
        if (!$firstlinkid) {
            $firstlinkid = pagemenu_get_first_linkid($this->pagemenuid);
        }
        if ($data === NULL) {
            if (!empty($links)) {
                $data = pagemenu_get_link_data($links);
            } else {
                $data = array();
            }
        }
        if (!empty($links) and !empty($firstlinkid)) {
            $linkid = $firstlinkid;

            while ($linkid) {
                if (array_key_exists($linkid, $data)) {
                    $datum = $data[$linkid];
                } else {
                    $datum = NULL;
                }

                $link   = $links[$linkid];
                $linkid = $link->nextid;

                $this->links[$link->id] = mod_pagemenu_link::factory($link->type, $link, $datum);
            }
        }
        $this->init();
    }

    /**
     * Constructor hook
     *
     * @return void
     **/
    protected function init() {
    }

    /**
     * Get the menu's HTML
     *
     * @return string
     **/
    public function to_html() {
        if (!empty($this->links) and empty($this->html)) {
            foreach ($this->links as $link) {
                $menuitem = $link->get_menuitem($this->editing, $this->descend);

                // Update info
                if ($link->active) {
                    $this->active = true;
                }
                if ($menuitem) {
                    $this->menuitems[$link->link->id] = $menuitem;
                }
            }
            $this->html = $this->menuitems_to_html($this->menuitems);
        }
        if (empty($this->html)) {
            $this->html = print_box(get_string('nolinksinmenu', 'pagemenu'), 'generalbox boxaligncenter boxwidthnarrow centerpara', 'pagemenu-empty', true);
        }
        return $this->html;
    }

    /**
     * Determine if one of the links
     * in the rendered menu is active
     * or not
     *
     * @return boolean
     **/
    public function is_active() {
        // Find out if we are active by generating the HTML
        if (empty($this->html)) {
            $this->to_html();
        }
        return $this->active;
    }

    /**
     * Gets the first URL in the menu item
     *
     * @return mixed
     **/
    public function get_first_url() {
        // Load up the menuitems by generating the HTML
        if (empty($this->html)) {
            $this->to_html();
        }
        if (!empty($this->menuitems)) {
            $first = reset($this->menuitems);
            return $first->url;
        }
        return false;
    }

    /**
     * Render the menu items as HTML
     *
     * @param array $menutiems Menu items
     * @return string
     **/
    abstract protected function menuitems_to_html($menutiems, $depth = 0);
}

?>