<?php
/**
 * Link class definition
 *
 * @author Mark Nielsen
 * @version $Id: link.class.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * Base link class
 */
abstract class mod_pagemenu_link {

    /**
     * Link type
     *
     * @var string
     **/
    public $type;

    /**
     * Link record object
     *
     * @var object
     **/
    public $link;

    /**
     * Link config options
     *
     * @var object
     **/
    public $config;

    /**
     * Editing flag
     *
     * @var boolean
     **/
    protected $editing = false;

    /**
     * Descend through all menu
     * items the link may have
     *
     * @var boolean
     **/
    protected $descend = false;

    /**
     * Is the link active
     *
     * @var boolean
     **/
    public $active = false;

    /**
     * Constructor
     *
     * @param mixed $link Link record object or link record ID
     * @param array $data Link data records
     * @return void
     **/
    public function __construct($link = NULL, $data = NULL) {
        global $CFG;

        // Get the last word in the classname
        $this->type = get_class($this);
        $this->type = explode('_', $this->type);
        $this->type = end($this->type);

        if (is_int($link)) {
            if (!$this->link = get_record('pagemenu_links', 'id', $link)) {
                error('Failed to get link');
            }
        } else if (is_object($link)) {
            $this->link = $link;
        } else {
            $this->link             = new stdClass;
            $this->link->id         = 0;
            $this->link->pagemenuid = 0;
            $this->link->previd     = 0;
            $this->link->nextid     = 0;
            $this->link->type       = $this->type;
        }

        $this->config = $this->get_config($data);
    }

    /**
     * Link Factory
     *
     * Creates link objects and makes
     * sure all the necessary files are
     * included
     *
     * @param string $type Link type (AKA class name)
     * @param mixed $link (Optional) Include Link ID or Link Record Object - will be passed to constructor
     * @param object $config (Optional) The links data records
     * @param type $name description
     **/
    public static function factory($type, $link = NULL, $data = NULL) {
        global $CFG;

        $classname = "mod_pagemenu_link_$type";
        $classfile = "$CFG->dirroot/mod/pagemenu/links/$type.class.php";

        // Get the class file if needed
        if (!class_exists($type) and file_exists($classfile)) {
            require_once($classfile);
        }
        // Make sure the class name is defined
        if (class_exists($classname)) {
            // Woot!  Make it :)
            return new $classname($link, $data);
        }
        throw new Exception("pagemenu_link factory error for type: $type");
    }

    /**
     * Returns the display name of the link
     *
     * @return string
     **/
    public function get_name() {
        return get_string($this->type, 'pagemenu');
    }

    /**
     * Add an element to the
     * edit_form to add a link
     *
     * @param object $mform The Moodle Form Class
     * @return void
     **/
    abstract public function edit_form_add(&$mform);

    /**
     * Save form data from creating
     * a new link
     *
     * @param object $data Form data (cleaned)
     * @return mixed
     **/
    public function save($data) {
        $names = $this->get_data_names();

        $allset = true;
        foreach ($names as $name) {
            if (empty($data->$name)) {
                $allset = false;
                break;
            }
        }
        if ($allset) {
            if (!empty($data->linkid)) {
                $linkid = $data->linkid;
            } else {
                $linkid = $this->add_new_link($data->a);
            }
            foreach ($names as $name) {
                $this->save_data($linkid, $name, $data->$name);
            }
        }
    }

    /**
     * Create a new link
     *
     * @param int $pagemenuid Instance ID
     * @return int
     **/
    public function add_new_link($pagemenuid) {
        $link             = new stdClass;
        $link->type       = $this->type;
        $link->previd     = 0;
        $link->nextid     = 0;
        $link->pagemenuid = $pagemenuid;

        $link = pagemenu_append_link($link);

        return $link->id;
    }

    /**
     * Get the names of the link data items
     * This allows for the auto processing of 
     * simple data items.
     *
     * @return array
     **/
    public function get_data_names() {
        return array();
    }

    /**
     * Save a piece of link data
     *
     * @param int $linkid ID of the link that the data belongs to
     * @param string $name Name of the data
     * @param mixed $value Value of the data
     * @param boolean $unique Is the name/value combination unique?
     * @return int
     **/
    public function save_data($linkid, $name, $value, $unique = false) {
        $return = false;

        $data         = new stdClass;
        $data->linkid = $linkid;
        $data->name   = $name;
        $data->value  = $value;

        if ($unique) {
            $fieldname  = 'value';
            $fieldvalue = $data->value;
        } else {
            $fieldname = $fieldvalue = '';
        }

        if ($id = get_field('pagemenu_link_data', 'id', 'linkid', $linkid, 'name', $name, $fieldname, $fieldvalue)) {
            $data->id = $id;
            if (update_record('pagemenu_link_data', $data)) {
                $return = $id;
            }
        } else {
            $return = insert_record('pagemenu_link_data', $data);
        }

        return $return;
    }

    /**
     * Gets all of the of the data associated
     * with the link.  This method is designed
     * to work well with passing link data recorded
     * objects.
     *
     * @param array $data An array of pagemenu_link_data records belonging to this link
     * @return object
     **/
    protected function get_config($data) {
        $config = new stdClass;

        if (!empty($this->link->id)) {
            if ($data !== NULL or $data = get_records('pagemenu_link_data', 'linkid', $this->link->id)) {

                foreach ($data as $datum) {
                    $config->{$datum->name} = $datum->value;
                }
            }
        }

        return $config;
    }

    /**
     * Create a menu item that will be used to contruct the menu HTML
     *
     * @param boolean $editing Editing is turned on
     * @param boolean $descend Print all links
     * @return object
     **/
    abstract public function get_menuitem($editing = false, $descend = false);

    /**
     * Returns a blank menu item
     *
     * @return object
     **/
    protected function get_blank_menuitem() {
        $menuitem            = new stdClass;
        $menuitem->title     = '';
        $menuitem->url       = '';
        $menuitem->class     = 'link_'.get_class($this);
        $menuitem->pre       = '';
        $menuitem->post      = '';
        $menuitem->active    = false;
        $menuitem->childtree = false;

        return $menuitem;
    }

    /**
     * The link can create its own edit actions.
     * Handle them using this method.
     *
     * @return mixed
     **/
    public function handle_action() {
        // Nothing
    }

    /**
     * Mostly an internal method to see if the
     * current link is active
     *
     * @param string $url URL to test - see if it is the current page
     * @return boolean
     **/
    protected function is_active($url = NULL) {
        if ($url === NULL) {
            return false;
        } else if (strpos(qualified_me(), $url) === false) {
            return false;
        } else {
            $this->active = true;
            return true;
        }
    }

    /**
     * Whether or not this link type is enabled
     *
     * @return boolean
     **/
    public function is_enabled() {
        return true;
    }

    /**
     * Restore link data - return boolean!
     *
     * @param array $data An array of pagemenu_link_data record objects
     * @param object $restore Restore object
     * @return boolean
     **/
    public static function restore_data($link, $restore) {
        return true;
    }
}
?>