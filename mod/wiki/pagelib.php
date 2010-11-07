<?php //$Id: pagelib.php,v 1.8 2008/08/25 08:20:28 pigui Exp $

//html functions
require_once ($CFG->dirroot.'/mod/wiki/weblib.php');
require_once($CFG->libdir.'/pagelib.php');

define('PAGE_WIKI_VIEW',   'mod-wiki-view');

page_map_class(PAGE_WIKI_VIEW, 'page_wiki');

$DEFINEDPAGES = array(PAGE_WIKI_VIEW);



class page_wiki extends page_generic_activity {

	//it overrides blocks_move_position(), to move blocks in a wiki activity
    function blocks_move_position(&$instance, $move) {
        if($instance->position == BLOCK_POS_LEFT && $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_RIGHT;
        } else if ($instance->position == BLOCK_POS_RIGHT && $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_LEFT;
        }
        return $instance->position;
    }
	
	//it returns the type class (future elimination)
    function get_type() {
        return PAGE_WIKI_VIEW;
    }
	
	// Do any validation of the officially recognized bits of the data and forward to parent.
    // Do NOT load up "expensive" resouces (e.g. SQL data) here!
    function init_quick($data) {
        if(empty($data->pageid)) {
            error('Cannot quickly initialize page: empty course id');
        }
        $this->activityname = 'wiki';
		parent::init_quick($data);
    }
	
   function print_header($title, $morebreadcrumbs = NULL) {
        global $USER, $CFG,$WS;
        $this->init_full();

        if($this->user_allowed_editing()) {
        	$buttons = wiki_table_start('',true);
            $buttons .= update_module_button($this->modulerecord->id, $this->courserecord->id, get_string('modulename', 'wiki'));

            if(!empty($CFG->showblocksonmodpages)) {
            	$buttons .= wiki_change_column('',true);
					$prop = null;
               		$prop->method = "get";
               		$prop->action = "view.php";
					$prop->events = 'onsubmit="this.target=\'_top\'; return true"';
					$buttons .= wiki_form_start($prop, true);
						$buttons .=	wiki_div_start('',true);

							$prop = null;
							$prop->name = "id";
							$prop->value = $this->modulerecord->id;
							$buttons .= wiki_input_hidden($prop,true);

							$prop = null;
							$prop->name = "edit";
							$prop->value = ($this->user_is_editing()?'off':'on');
							$buttons .= wiki_input_hidden($prop,true);

							$prop = null;
							$prop->value = get_string($this->user_is_editing()?'blockseditoff':'blocksediton');
							$buttons .= wiki_input_submit($prop,true);
						$buttons .= wiki_div_end(true);
					$buttons .= wiki_form_end(true);
            }
            $buttons .= wiki_table_end('',true);
        }
        else {
            $buttons = '&nbsp;';
        }
		
		
//        print_header($title, $this->courserecord->fullname, $crumbtext, '', '', true, $buttons, navmenu($this->courserecord, $this->modulerecord));
		
		
		/// Print header.
	    $navlinks = array();
	    $navlinks[] = array('name' => get_string('modulenameplural','wiki'), 'link' => $CFG->wwwroot.'/mod/wiki/index.php?id='.$this->courserecord->id, 'type' => 'activity');
	    $navlinks[] = array('name' => format_string($this->activityrecord->name), 'link' => "view.php", 'type' => 'activityinstance');
	    
	    $navigation = build_navigation($navlinks);
	    
	    print_header_simple(format_string($this->activityrecord->name), "",
	                 $navigation, "", "", true, $buttons, navmenu($this->courserecord, $WS->cm));


    }
	
}
	


?>
