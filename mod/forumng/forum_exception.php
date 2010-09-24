<?php
/**
 * This class is probably temporary. In Moodle 2 it can be replaced with uses
 * of standard Moodle exceptions.
 */
class forum_exception extends exception {
    function __construct($message) {
        parent::__construct($message);
    }
}
?>