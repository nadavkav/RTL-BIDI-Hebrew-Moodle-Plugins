<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a textarea element
*/

include_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class textareaelement extends trackerelement{
	function textareaelement(&$tracker, $id=null){
	    $this->trackerelement($tracker, $id);
	}

	function view($editable, $issueid = 0){
        $this->getvalue($issueid);
	    if ($editable){
		    print_textarea(true, 10, 60, 680, 400, "element$this->name", $this->value);
		} else {
		    echo format_text(format_string($this->value), $this->format);
	    }   
	}

	function viewsearch(){
	    echo "<input type=\"text\" name=\"element{$this->name}\" style=\"width:100%\" />";
	}

	function viewquery(){
	    echo "<input type=\"text\" name=\"element{$this->name}\" style=\"width:100%\" />";
	}
}
?>
