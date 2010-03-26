<?php

// This Block enables the users, surfing a Moodle website, to DoubleClick any English word
// on the page and get a tooltip popup which links them directly to
// MacMillian's Dictionary of the English Language.
//
// Author: Kavalerchik Nadav (nadavkav@gmail.com)
// Date: 2009-Mar-16
//
// Using the public code from : http://www.macmillandictionary.com/tools/doubleclick.html
//

class block_dic_macmillian extends block_base {

    function init() {
           $this->title = get_string('blockname','block_dic_macmillian');
           $this->content_type = BLOCK_TYPE_TEXT;
           $this->version = 2010031601;
    }

    function get_content() {
        global $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
		$this->content->text = get_string('instructions','block_dic_macmillian');
		$this->content->text .= '<script language="javascript" type="text/javascript" src="'.$CFG->wwwroot.'/blocks/dic_macmillian/jquery-1.4.2.min.js"></script>';
		$this->content->text .= '<script language="javascript" type="text/javascript" src="'.$CFG->wwwroot.'/blocks/dic_macmillian/doubleclick.js"></script>';
		$this->content->text .= "<script language=\"javascript\" type=\"text/javascript\">
									setupDoubleClick('http://www.macmillandictionary.com/', 'british', null, false, null);
								</script>";

        $this->content->footer = '';

        return $this->content;
    }

    function applicable_formats() {
        return array('site' => true,'my' => true,'course' => true);
    }

}

?>
