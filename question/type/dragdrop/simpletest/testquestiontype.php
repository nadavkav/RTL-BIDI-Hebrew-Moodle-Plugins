<?php
/**
 * Unit tests for this question type.
 *
 * @copyright &copy; 2008 Harald Winkelmann
 * @author harry ät winkelmaenner.de
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *//** */

require_once(dirname(__FILE__) . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/simpletestlib.php');
require_once($CFG->dirroot . '/question/type/dragdrop/questiontype.php');

class dragdrop_qtype_test extends UnitTestCase {
    var $qtype;

    function setUp() {
        $this->qtype = new dragdrop_qtype();
    }

    function tearDown() {
        $this->qtype = null;
    }

    function test_name() {
        $this->assertEqual($this->qtype->name(), 'dragdrop');
    }

    // TODO write unit tests for the other methods of the question type class.
}

?>
