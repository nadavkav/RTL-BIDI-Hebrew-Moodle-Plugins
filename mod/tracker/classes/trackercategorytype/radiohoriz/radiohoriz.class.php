<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a radio button (exclusive choice) element horizontally displayed
*/

include_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class radiohorizelement extends trackerelement{
	var $options;
	
	function radiohorizelement(&$tracker, $id=null){
	    $this->tracker = $tracker;
		if (isset($id)){
			$this->id = $id;
			$this->setoptionsfromdb();
		} else {
		    $this->options = array();
	    }
	}
	
	function view($editable, $issueid = 0){
	    $this->getvalue($issueid);
		if ($editable){
			if (!empty($this->options)){
				foreach ($this->options as $option){
					echo "\t<input type=\"radio\" name=\"element" . $this->name . "\" value=\"" . $option->id . "\"";
					if ($this->value != null){
						if ($this->value == $option->id){
							echo ' CHECKED ';
						}
					}
					echo '/>' . format_string($option->description) . " &nbsp;&nbsp;&nbsp; ";
				}
			} else {	
				echo "\t\t" . get_string('nooptions', 'tracker');
			}
		} else {
			if (isset($this->options)){
			    $optionsstrs = array();
				foreach ($this->options as $option){
					if ($this->value != null){
						if ($this->value == $option->id){
							$optionsstrs[] = format_string($option->description);
						}
					}
				}
				echo implode(', ', $optionsstrs);
			}
		}
	}
}
?>
