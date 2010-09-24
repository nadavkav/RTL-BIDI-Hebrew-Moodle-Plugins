<?php 
require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->dirroot.'/course/lib.php'); // needed for some blocks

define('PAGE_OUBLOG_VIEW',   'mod-oublog-view');
//define('PAGE_FORUM_DISCUSS',   'mod-forum-discuss');

page_map_class(PAGE_OUBLOG_VIEW,'page_oublog_view');
//page_map_class(PAGE_FORUM_DISCUSS,'page_forum_discuss');

//$DEFINEDPAGES = array(PAGE_FORUM_VIEW,PAGE_FORUM_DISCUSS);
$DEFINEDPAGES = array(PAGE_OUBLOG_VIEW);

class page_oublog_view extends page_generic_activity {

    var $navblockinstance = NULL;

    function get_type() {
        return PAGE_OUBLOG_VIEW;
    }
    
    function init_quick($data) {
        if(empty($data->pageid)) {
            error('Cannot quickly initialize page: empty course id');
        }
        $this->activityname = 'oublog';
        parent::init_quick($data);
    }

}

?>
