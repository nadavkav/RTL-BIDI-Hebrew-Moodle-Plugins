<?php
/**
 * Format action base class
 *
 * @author Mark Nielsen
 * @version $Id: action.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
 * @package format_page
 * @todo Only just made this class - further
 *       development is needed to take full advantage
 **/

class format_page_action {
    /**
     * Constructor
     *
     * @param object $page Current page
     * @param object $context Course context
     * @return void
     **/
    public function __construct($page, $context = NULL) {
        global $COURSE;

        $this->page = $page;

        if ($context === NULL) {
            $this->context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        } else {
            $this->context = $context;
        }
    }

    /**
     * The action's display method
     *
     * @return void
     **/
    public function display() {
    }
}

?>