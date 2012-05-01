<?php
/**
   @author Jochen Lackner, Markus Pusswald
   @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
class block_choice_to_group extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_choice_to_group');
        $this->version = 2008042500;
    }

    function get_content() {
		global $CFG,$USER,$COURSE;

		if (!empty($COURSE)) {
            $this->courseid = $COURSE->id;
        }
    	if ($this->content !== NULL) {
       		return $this->content;    	}

    	$this->content = new stdClass;
    	$choices = get_records_sql("SELECT id, name FROM {$CFG->prefix}choice where course=$COURSE->id");
    	if ($choices){
        	$this->content->text = "<SELECT id=\"selection\">";    
        
        
	        foreach ($choices as $choice){
	          $this->content->text.= "<option value={$choice->id}>{$choice->name}</option>";
	        }

	        $this->content->text.= '</select>';
	        $this->content->text.='
		  		<script type="text/javascript">
	          		function submitme(){
	    	      		document.getElementById("newsite").href="';
	   		
	   					$page = page_create_object($this->instance->pagetype, $this->instance->pageid);
	   					$this->content->text.= $page->url_get_path();
	  		    
		        		$params = array('instanceid' => $this->instance->id,
	    		            'sesskey' => $USER->sesskey,
	                		'blockaction' => 'config',
			                'currentaction' => 'create',
	        		        'id' => $this->courseid,
			                'section' => 'choice_to_group');
	
		        		$first = true;
	
	    	    		foreach($params as $var => $value) {
	        	    		$this->content->text.= $first? '?' : '&';
	            			$this->content->text.= $var .'='. urlencode($value);
	            			$first = false;
			       		}
			       		$this->content->text.= "&selection=\"";
			       		$this->content->text.= '+document.getElementById("selection").value;
	    	      			return true;
	          			} 
	          			</script>';
			 $this->content->text.= "<A ID=\"newsite\" HREF=\"\" onClick=\"return submitme()\">".get_string("continue","moodle")."</A>";
    	}
    	else
    	{
    		$this->content->text.= get_string("no_choices","block_choice_to_group");	
    	}
    	 

	    $this->content->footer = '';
		return $this->content;
    }
    
    function instance_allow_config(){
      return true;
    }
     
    function has_config(){
    	return true;
    }
    
   
}

?>
