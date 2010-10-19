<?PHP //$Id: block_search_metadata.php,v 0.1 2006/09/01 11:47:38 vg Exp $

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');

class block_search_metadata extends block_base {
    function init() {
        $this->title = get_string('blocktitle', 'block_search_metadata');
        $this->version = 2006100100;
    }

    function get_content() {
        global $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';

        if (empty($this->instance)) {
            $this->content->text   = '';
            return $this->content;
        }

        $advancedsearch = get_string('advancedsearch', 'block_search_metadata');

        //Accessibility: replaced <input value=">" type="submit"> with button-embedded image.
        $this->content->text  = '<div class="searchform">';
        //$this->content->text .= '<form name="search" action="'.$CFG->wwwroot.'/mod/metadatadc/search_metadata.php" style="display:inline">';
        $this->content->text .= '<form name="search" action="'.$CFG->wwwroot.'/blocks/'.$this->name().'/search_allmetadata.php" style="display:inline">';		
        $this->content->text .= '<input name="id" type="hidden" value="'.$this->instance->pageid.'" />';  // course
//        $this->content->text .= '<input name="id" type="hidden" value="0" />';  // course
        $this->content->text .= '<input name="search" type="text" size="16" value="" />';
        $this->content->text .= '<button type="submit" title="'.get_string('search').'"><img src="'.$CFG->pixpath.'/a/r_go.gif" alt="" class="resize" /><span class="accesshide">'.get_string('search').'</span></button><br />'; 
        $this->content->text .= '<a href="'.$CFG->wwwroot.'/blocks/'.$this->name().'/search_allmetadata.php?id='.$this->instance->pageid.'">'.$advancedsearch.'</a>';
        $this->content->text .= helpbutton('search_los', $advancedsearch, 'moodle', true, false, '', true);
        $this->content->text .= '</form></div>';

        return $this->content;
    }

    function applicable_formats() {
        return array('site' => true, 'course' => true);
    }
}

?>
