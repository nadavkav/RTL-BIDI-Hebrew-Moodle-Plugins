<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a dropdown element
*/

include_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class dropdownelement extends trackerelement{
	var $options;
	
	function dropdownelement(&$tracker, $id=null){
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
		    if (!empty($this->options)){
		        foreach($this->options as $option){
		            $optionsmenu[$option->id] = format_string($option->description);
		        }
    	        choose_from_menu($optionsmenu, "element$this->name", $this->value, 'choose');
		    } else {
				echo "\t\t" . get_string('nooptions', 'tracker');
		    }
		} else {
			if (isset($this->options)){
			    $optionstrs = array();
				foreach ($this->options as $option){
					if ($this->value != null){
						if ($this->value == $option->id){
							$optionstrs[] = format_string($option->description);
						}
					}
				}
                echo implode(', ', $optionstrs);				
			}			
		}
	}
}
?>
