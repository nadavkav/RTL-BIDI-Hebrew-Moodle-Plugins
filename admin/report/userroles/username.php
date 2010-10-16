<?php
/**
 * A moodle form field type for selecting a user, with ajaxy help if JavaScript is turned on.
 *
 * @copyright &copy; 2007 The Open University
 * @author T.J.Hunt@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package userrolesreport
 *//** */

global $CFG;
require_once($CFG->libdir . '/pear/HTML/QuickForm/text.php');

require_js(array('yui_dom-event', 'yui_datasource', 'yui_connection', 'yui_autocomplete'));
require_js($CFG->wwwroot . '/admin/report/userroles/username.js');
require_css($CFG->wwwroot . '/lib/yui/autocomplete/assets/skins/sam/autocomplete.css');

/**
 * HTML class for a drop down element to select a question category.
 * @access public
 */
class MoodleQuickForm_username extends HTML_QuickForm_text {
    function MoodleQuickForm_username($elementName=null, $elementLabel=null, $attributes=null)
    {
        HTML_QuickForm_text::HTML_QuickForm_text($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->setType('user');
    }

    /**
     * Returns the input field in HTML
     */
    function toHtml()
    {
        global $CFG;
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            $name = $this->getName();
            $url = $CFG->wwwroot . '/admin/report/userroles/username_ajaxhelper.php';
            return $this->_getTabs() . '<div style="position: relative; height: 1.9em;" class="yui-skin-sam"><input ' .
                    $this->_getAttrString($this->_attributes) . ' /><div id="' . $name . 'container"></div></div>' .
                    '<script type="text/javascript">add_username_autocomplete("' . $name . '", "' .
                    sesskey() . '", "' . $url . '")</script>';
        }
    }

    function exportValue(&$values, $assoc = false) {

        if (empty($values)) {
            return null;
        }
        $value = null;
        $elementName = $this->getName();
        if (isset($values[$elementName])) {
            $value = $values[$elementName];
        } elseif (strpos($elementName, '[')) {
            $myVar = "['" . str_replace(array(']', '['), array('', "']['"), $elementName) . "']";
            $value =  eval("return (isset(\$values$myVar)) ? \$values$myVar : null;");
        }
        if (!is_null($value) && !record_exists('user', 'username', addslashes($value))) {
            $value = null;
        }
        return $this->_prepareValue($value, $assoc);
    }
}
?>
