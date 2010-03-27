<?php

class block_spelling_bee extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_spelling_bee');
        $this->version = 2009110601;
    }

    function applicable_formats() {
        return array('all' => true, 'tag' => false);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? $this->config->title : get_string('blockname', 'block_spelling_bee');
    }

    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
		global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

		//$spellingbee = file_get_contents('./spelling-bee.html',FILE_USE_INCLUDE_PATH);
		$filename = "$CFG->dirroot/blocks/spelling_bee/spelling-bee.html";
		$handle = fopen($filename , "r");
		$spellingbee = fread($handle, filesize($filename));
		fclose($handle);

        $this->content->text = $spellingbee ;
        $this->content->footer = '';

        return $this->content;
    }
}
?>
