<?php
	// Paul Holden 24th July, 2007
	// contains the code that controls polls and responses

	require_once('../../config.php');

	$action = required_param('action', PARAM_ALPHA);
	$pid = optional_param('pid', 0, PARAM_INTEGER);		
	$cid = required_param('id', PARAM_INTEGER);
	if ($cid == 0) $cid = 1;
	$instanceid = optional_param('instanceid', 0, PARAM_INTEGER);
	$sesskey = $USER->sesskey;

	function test_allowed_to_update() {
		// TODO: Proper roles & capabilities
		global $cid;
		if (!isteacher($cid)) {
			error(get_string('pollwarning', 'block_poll'));
		}
	}

	$url = "$CFG->wwwroot/course/view.php?id=$cid";

	switch ($action) {
	  case 'create':
		test_allowed_to_update();
	   	$poll = new Object();
		$poll->id = $pid;
		$poll->name = required_param('name', PARAM_TEXT);
		$poll->courseid = $cid;
		$poll->questiontext = required_param('questiontext', PARAM_TEXT);
		$poll->eligible = required_param('eligible', PARAM_ALPHA);
		$poll->created = time();
		$newid = insert_record('block_poll', $poll, true);
		$optioncount = optional_param('optioncount', 0, PARAM_INTEGER);
		for ($i = 0; $i < $optioncount; $i++) {
			$pollopt = new Object();
			$pollopt->id = 0;
			$pollopt->pollid = $newid;
			$pollopt->optiontext = '';
			insert_record('block_poll_option', $pollopt);
		}
		$url .= "&amp;instanceid=$instanceid&amp;sesskey=$sesskey&amp;blockaction=config&amp;action=editpoll&amp;pid=$newid";	
		break;
	  case 'edit':
		test_allowed_to_update();
		$poll = get_record('block_poll', 'id', $pid);
		$poll->name = required_param('name', PARAM_TEXT);
		$poll->questiontext = required_param('questiontext', PARAM_TEXT);
		$poll->eligible = required_param('eligible', PARAM_ALPHA);
		update_record('block_poll', $poll);
		$options = optional_param('options', array(), PARAM_RAW);
		foreach (array_keys($options) as $option) {
			$pollopt = get_record('block_poll_option', 'id', $option);
			$pollopt->optiontext = $options[$option];
			update_record('block_poll_option', $pollopt);
		}
		$optioncount = optional_param('optioncount', 0, PARAM_INTEGER);
		if (count($options) > $optioncount) {
			$temp = 1;
			foreach ($options as $optid => $optname) {
				if ($temp++ > $optioncount) break;
				$safe[] = $optid;
			}
			delete_records_select('block_poll_option', "pollid = $pid AND id NOT IN (" . implode($safe, ',') . ")");
		}
		for ($i = count($options); $i < $optioncount; $i++) {
			$pollopt = new Object();
			$pollopt->id = 0;
			$pollopt->pollid = $pid;
			$pollopt->optiontext = '';
			insert_record('block_poll_option', $pollopt);
		}
		$url .= "&amp;instanceid=$instanceid&amp;sesskey=$sesskey&amp;blockaction=config&amp;action=editpoll&amp;pid=$pid";
		break;
	  case 'delete':
		test_allowed_to_update();
		$step = optional_param('step', 'first', PARAM_TEXT);
		$urlno = $url . "&amp;instanceid=$instanceid&amp;sesskey=$sesskey&amp;blockaction=config&amp;action=managepolls";
		if ($step == 'confirm') {
			delete_records('block_poll', 'id', $pid);
			delete_records('block_poll_option', 'pollid', $pid);
			delete_records('block_poll_response', 'pollid', $pid);
			$url = $urlno;
		} else {
			$poll = get_record('block_poll', 'id', $pid);
			$urlyes = "$CFG->wwwroot/blocks/poll/poll_action.php?id=$cid&amp;instanceid=$instanceid&amp;action=delete&amp;step=confirm&amp;pid=$pid";
			notice_yesno(get_string('pollconfirmdelete', 'block_poll', $poll->name), $urlyes, $urlno);
			die();
		}
		break;
	  case 'respond':
		if (!get_record('block_poll_response', 'pollid', $pid, 'userid', $USER->id)) {
			$response = new Object();
			$response->id = 0;
			$response->pollid = $pid;
			$response->optionid = required_param('rid', PARAM_INTEGER);
			$response->userid = $USER->id;
			$response->submitted = time();
			insert_record('block_poll_response', $response);
		}
		break;
	}

	redirect($url);
?>
