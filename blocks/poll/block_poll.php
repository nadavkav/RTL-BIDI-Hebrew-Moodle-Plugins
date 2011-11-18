<?php

	// Paul Holden 24th July, 2007
	// this file contains the poll block class

  include("$CFG->dirroot/blocks/poll/lib.php");

  class block_poll extends block_base {

	var $poll, $options;

	function init() {
		$this->title = get_string('formaltitle', 'block_poll');
		$this->version = 2007072000;
	}

	function instance_allow_config() {
		return true;
	}

// 	function applicable_formats() {
// 	    // Default case: the block can be used in all course types
// 	    return array('all' => false,
// 			'course-*' => true, 'mod-oublog' => true);
// 	}

	function specialization() {
		if (!empty($this->config) && !empty($this->config->customtitle)) {
			$this->title = $this->config->customtitle;
		} else {
			$this->title = get_string('formaltitle', 'block_poll');
		}
	}

	function poll_can_edit() {
		// TODO: Proper roles & capabilities
		return isteacher($this->instance->pageid);
	}

	function poll_user_eligible() {
		// TODO: Proper roles & capabilities
		return ($this->poll->eligible == 'all') ||
			(($this->poll->eligible == 'students') && isstudent($this->instance->pageid)) ||
			(($this->poll->eligible == 'teachers') && isteacher($this->instance->pageid));
	}

	function poll_results_link() {
		global $USER;
		$page = page_create_object($this->instance->pagetype, $this->instance->pageid);
		$url = $page->url_get_full(array('instanceid' => $this->instance->id, 'sesskey' => $USER->sesskey, 'blockaction' => 'config', 'action' => 'responses', 'pid' => $this->poll->id));
		return "<hr />(<a href=\"$url\">" . get_string('responses', 'block_poll') . '</a>)';
	}

	function poll_print_options() {
		global $CFG;
		$this->content->text .= '<form method="get" action="' . $CFG->wwwroot . '/blocks/poll/poll_action.php">
					 <input type="hidden" name="action" value="respond" />
					 <input type="hidden" name="pid" value="' . $this->poll->id . '" />
					 <input type="hidden" name="id" value="' . $this->instance->pageid . '" />';
		foreach ($this->options as $option) {
			$this->content->text .= "<tr><td><input type=\"radio\" id=\"r_$option->id\" name=\"rid\" value=\"$option->id\" />
						 <label for=\"r_$option->id\">$option->optiontext</label></td></tr>";
		}
		$this->content->text .= '<tr><td><input type="submit" value="' . get_string('submit', 'block_poll') . '" /></td></tr></form>';
	}

	function poll_get_results(&$results, $sort = true) {
		foreach ($this->options as $option) {
			$responses = get_records('block_poll_response', 'optionid', $option->id);
			$results[$option->optiontext] = (!$responses ? '0' : count($responses));
		}
		if ($sort) { poll_sort_results($results); }
	}

	function poll_print_results() {
		$this->poll_get_results($results);
		foreach ($results as $option => $count) {
			$img = ($img == 0 ? 1 : 0);
			$highest = (!$highest ? $count : $highest);
			$imgwidth = round($this->config->maxwidth / $highest * $count);
			$imgwidth = ($imgwidth == 0 ? 1 : $imgwidth);
			$this->content->text .= "<tr><td>$option ($count)<br />" . poll_get_graphbar($img, $imgwidth) . '</td></tr>';
		}
	}

	function get_content() {
		global $USER;

		if ($this->content !== null) {
			return $this->content;
		}

		if ( !isset($this->config->pollid) ) return false;

		//echo_fb('block notice [$this->poll]:',$this->poll);
		$this->poll = get_record('block_poll', 'id', $this->config->pollid);
		//if ($this->poll == 'false') {
		//        return $this->poll;
		//}
		//echo_fb('block notice [$this->poll->id]:',$this->poll->id);
		$this->options = get_records('block_poll_option', 'pollid', $this->poll->id);

		//echo_fb('Backtrace to here',$this->poll, 'INFO');

		if ($this->poll->id == NULL) return false;

		//echo_fb('block notice:',array($this));

		$this->content = new stdClass;
		$this->content->text = '<table cellspacing="2" cellpadding="2">';
		$this->content->text .= '<tr><th>' . $this->poll->questiontext . '</th></tr>';

		$response = get_record('block_poll_response', 'pollid', $this->poll->id, 'userid', $USER->id);
		$func = 'poll_print_' . (!$response && $this->poll_user_eligible() ? 'options' : 'results');
		$this->$func();

		$this->content->text .= '</table>';

		$this->content->footer = ($this->poll_can_edit() ? $this->poll_results_link() : '');

		return $this->content;
	}

  }

?>
