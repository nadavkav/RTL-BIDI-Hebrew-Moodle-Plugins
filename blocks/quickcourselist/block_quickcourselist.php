<?php

class block_quickcourselist extends block_base {

    function init() {
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->version = 2009010600;
        $this->title = get_string('quickcourselist','block_quickcourselist');
        $this->content->footer = '';
    }
    
    //stop it showing up on any add block lists
    function applicable_formats() {
        return (array('all' => false,'site'=>true));
    }

    function get_content() {
        global $CFG;
        
        $context_system = get_context_instance(CONTEXT_SYSTEM);

        if (has_capability('block/quickcourselist:use', $context_system)) {
            $this->content->text="<input type='text' onkeyup='quickcoursesearch()' id='quickcourselistsearch'><br><p id='quickcourselist'>";
            
            
            $query='SELECT id,shortname,fullname FROM '.$CFG->prefix.'course WHERE id <>'.SITEID;
            if(!has_capability('moodle/course:viewhiddencourses',$context_system)){$query.=' AND visible=1';}
            

            if(!$courses=get_records_sql($query)){
                $this->content->text=get_string('nocourses','block_quickcourselist');
            }else{
                foreach ($courses as $course) {
                    $this->content->text .= "<a href='$CFG->wwwroot/course/view.php?id=$course->id'>$course->shortname: $course->fullname</a>";
                }

               $this->content->text .="</p>";
            }
            require_js($CFG->wwwroot.'/blocks/quickcourselist/quickcourselist.js');
            $this->content->text.='<script type="text/javascript">quickcoursesearch();</script>';
        }
        $this->content->footer='';
        return $this->content;
        
    }
}
?>
