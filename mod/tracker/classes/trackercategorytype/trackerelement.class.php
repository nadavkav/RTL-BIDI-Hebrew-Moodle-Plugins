<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A generic class for collecting all that is common to all elements
*/

class trackerelement{
	var $id;
	var $course;
	var $usedid;
	var $name;
	var $description;
	var $format;
	var $type;
	var $sortorder;
	var $maxorder;
	var $value;
	var $options;
	var $tracker;
	
	function trackerelement(&$tracker, $elementid=null){
	    $this->id = $elementid;
	    $this->options = null;
		$this->value = null;
		$this->tracker = $tracker;
	}
	
	function hasoptions(){
		return $this->options !== null;
	}

	function getoption($optionid){
		return $this->options[$optionid];
	}
	
	function setoptions($options){
		$this->options = $options;
	}

    /**
    *
    *
    */
	function setoptionsfromdb(){
		if (isset($this->id)){
			$this->options = get_records_select('tracker_elementitem', "elementid={$this->id} AND active = 1 ORDER BY sortorder");
			if ($this->options){
                foreach($this->options as $option){
                    $this->maxorder = max($option->sortorder, $this->maxorder);
                }			
            } else {
                $this->maxorder = 0;
            }
		} else {
			error ('Element ID is not set');
		}
	}
	
	/**
	*
	*
	*/
    function getvalue($issueid){
        global $CFG;
        
        if (!$issueid) return '';
        
        $sql = "
            SELECT 
                elementitemid
            FROM
                {$CFG->prefix}tracker_issueattribute
            WHERE
                elementid = {$this->id} AND
                issueid = {$issueid}
        ";
        $this->value = get_field_sql($sql);
        return($this->value);
    }
    
    /**
    *
    *
    */
	function addview(){
	}
	
	function optionlistview($cm){
	    global $CFG, $COURSE;	    

        $strname = get_string('name');
        $strdescription = get_string('description');
        $strsortorder = get_string('sortorder', 'tracker');
        $straction = get_string('action');
        $table->width = "800";
        $table->size = array(100,110,240,75,75);
        $table->head = array('', "<b>$strname</b>","<b>$strdescription</b>","<b>$straction</b>");
        
        if (!empty($this->options)){
        	foreach ($this->options as $option){
                $actions  = "<a href=\"view.php?id={$cm->id}&amp;what=editelementoption&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('edit')."\"><img src=\"{$CFG->pixpath}/t/edit.gif\" /></a>&nbsp;" ;
                $img = ($option->sortorder > 1) ? 'up' : 'up_shadow' ;
                $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=moveelementoptionup&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('up')."\"><img src=\"{$CFG->wwwroot}/mod/tracker/pix/{$img}.gif\"></a>&nbsp;";
                $img = ($option->sortorder < $this->maxorder) ? 'down' : 'down_shadow' ;
                $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=moveelementoptiondown&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('down')."\"><img src=\"{$CFG->wwwroot}/mod/tracker/pix/{$img}.gif\"></a>&nbsp;";

                $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=deleteelementoption&amp;optionid={$option->id}&amp;elementid={$option->elementid}\" title=\"".get_string('delete')."\"><img src=\"{$CFG->pixpath}/t/delete.gif\"></a>";
        	    $table->data[] = array('<b> '.get_string('option', 'tracker').' '.$option->sortorder.':</b>',$option->name, format_string($option->description, true, $COURSE->id), $actions);
        	}
        }
        print_table($table);
	}
	
	function editview(){
	    if ($this->type != ''){
    		include_once $CFG->dirroot."/mod/tracker/classes/trackercategorytype/" . $this->type . "/edit" . $this->type . ".html";
    	}
	}

	function viewsearch(){
	    $this->view(true);
	}	

	function viewquery(){
	    $this->view(true);
	}	
}
?>