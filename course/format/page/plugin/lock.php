<?php
/**
 * Lock base class
 *
 * @author Mark Nielsen
 * @version $Id: lock.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
 * @package format_page
 * @todo Define all methods in there (perhaps abstract the class)
 **/

abstract class format_page_lock {
    /**
     * Get all lock type instances
     *
     * @return array
     **/
    public static function get_locks() {
        global $CFG;

        static $locks = false;

        if ($locks === false) {
            $locks = array();
            $files = get_directory_list($CFG->dirroot.'/course/format/page/plugin/lock');
            foreach ($files as $file) {
                require_once("$CFG->dirroot/course/format/page/plugin/lock/$file");

                $name      = pathinfo($file, PATHINFO_FILENAME);
                $classname = 'format_page_lock_'.$name;

                if (!class_exists($classname)) {
                    error('Lock classname does not exist');
                }

                $locks[$name] = new $classname();
            }
        }
        return $locks;
    }

    /**
     * Determine if a page is locked or not
     *
     * @param object $page Format page object
     * @return boolean
     **/
    public static function is_locked($page) {
        $locks    = self::get_locks();
        $lockinfo = self::decode($page->locks);

        foreach ($lockinfo['locks'] as $lock) {
            if ($locks[$lock['type']]->locked($lock)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the page lock is visible
     * or not
     *
     * @param object $page Format page object
     * @return boolean
     **/
    public static function is_visible_lock($page) {
        $lockinfo = self::decode($page->locks);

        return ($lockinfo['visible'] == 1);
    }

    /**
     * Print lock pre-requisites message
     *
     * @param object $page Format page object
     * @return void
     **/
    public static function print_lock_prerequisites($page) {
        $lockinfo = self::decode($page->locks);

        if ($lockinfo['showprereqs']) {
            $locks    = self::get_locks();

            $messages = array();
            foreach ($lockinfo['locks'] as $lock) {
                if ($locks[$lock['type']]->locked($lock)) {
                    $messages[] = $locks[$lock['type']]->get_prerequisite($lock);
                }
            }
            $messages = '<ul><li>'.implode('</li><li>', $messages).'</li></ul>';
            $message  = get_string('prereqsmessage', 'format_page', $messages);
        } else {
            $message = get_string('pageislocked', 'format_page');
        }

        print_box($message, 'generalbox', 'pagelock');
    }

    /**
     * Encode lock data
     *
     * @param array $locks Lock data
     * @return string
     **/
    public static function encode($locks) {
        return base64_encode(serialize($locks));
    }

    /**
     * Decode lock data
     *
     * @param string $encodedlocks Encoded lock data
     * @return array
     **/
    public static function decode($encodedlocks) {
        if (empty($encodedlocks)) {
            return array();
        } else {
            return unserialize(base64_decode($encodedlocks));
        }
    }

    /**
     * Restore locks
     *
     * @return string
     **/
    public static function restore($restore, $encodedlocks) {
        $lockinfo = self::decode($encodedlocks);
        $locks    = self::get_locks();

        $newlocks = array();

        foreach ($lockinfo['locks'] as $lock) {
            if (array_key_exists($lock['type'], $locks)) {
                if ($newlock = $locks[$lock['type']]->restore_hook($restore, $lock)) {
                    $newlocks[] = $newlock;
                }
            }
        }
        if (empty($newlocks)) {
            return '';
        }
        $lockinfo['locks'] = $newlocks;

        return self::encode($lockinfo);
    }

    /**
     * Restore a lock
     *
     * @return mixed
     **/
    protected function restore_hook($restore, $lock) {
        return $lock;
    }

    /**
     * Add form elements for adding a new lock
     *
     * @param object $mform Moodle Form
     * @param array $locks Current locks
     * @return void
     **/
    abstract public function add_form(&$mform, $locks);

    /**
     * Add form elements for editing current locks
     *
     * @param object $mform Moodle Form
     * @param array $locks Current locks
     * @return void
     **/
    abstract public function edit_form(&$mform, $locks);

    /**
     * Process form submit for elements added
     *
     * @param object $data Form data
     * @return array
     **/
    abstract public function process_form($data);

    /**
     * Is the passed lock locked or not
     *
     * @param array $lock The lock to check against
     * @return boolean
     **/
    abstract public function locked($lock);

    /**
     * Generate a prerequisite message for the passed lock
     *
     * @param array $lock The lock to get the message for
     * @return string
     **/
    abstract public function get_prerequisite($lock);
}

?>