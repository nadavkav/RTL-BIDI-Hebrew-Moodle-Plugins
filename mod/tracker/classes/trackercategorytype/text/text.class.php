<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 17/12/2007
*
* A class implementing a textfield element
*/

include_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class textelement extends trackerelement{
	function textelement(&$tracker, $id=null){
	    $this->trackerelement($tracker, $id);
	}

	function view($editable, $issueid=0){
        $this->getvalue($issueid);
	    if ($editable){
		    echo '<input type="text" name="element' . $this->name . "\" value=\"".htmlspecialchars(addslashes($this->value))."\" style=\"width:100%\" />";
		} else {
		    echo format_text(format_string($this->value), $this->format);
	    }   
	}
}
?>
