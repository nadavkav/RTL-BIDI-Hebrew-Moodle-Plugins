<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a checkbox element
*/

include_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class checkboxhorizelement extends trackerelement{
	var $options;
	
	function checkboxhorizelement(&$tracker, $id=null){
	    $this->tracker = $tracker;
		if (isset($id)){
			$this->id = $id;
			$this->setoptionsfromdb();
		} else {
		    $this->options = array();
	    }
	}
		
	function view($editable, $issueid = 0){
	    $this->getvalue($issueid); // loads $this->value with current value for this issue
		if ($editable){
			if (isset($this->options)){
				foreach ($this->options as $option){
					echo "\t" . "<input type=\"checkbox\" name=\"element" . $this->name . "[]\" value=\"" . $option->id. "\"";
					if (!empty($this->value)){
						if (is_array($this->value)){
							foreach ($this->value as $select){
								if ($select == $option->id){
									echo ' CHECKED ';
								}
							}
						} else {
							if ($this->value == $option->id){							
								echo ' CHECKED ';
							}
						}
					}
					echo '/>' . format_string($option->description) . " &nbsp;&nbsp;&nbsp; ";
				}
			} else {	
				echo "\t\t" . get_string('nooptions', 'tracker');
			}
		} else {
			if (!empty($this->value)){
			    if (is_array($this->value)){
    				foreach ($this->value as $selected){
    					echo format_string($this->options[$selected]->description) . "<br/>\n";
    				}					
    			} else {
					echo format_string($this->options[$this->value]->description) . "<br/>\n";
    			}
			}		
		}
	}	
}
?>
