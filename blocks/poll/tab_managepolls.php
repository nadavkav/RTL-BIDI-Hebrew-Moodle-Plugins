<?php
	// Paul Holden 24th July, 2007
	// poll block; poll management tab

	function print_action($action, $url) {
		global $CFG;
		return "<a href=\"$url\"><img src=\"$CFG->pixpath/t/$action.gif\" alt=\"\" /></a> ";
	}

	$edit = get_string('edit');
	$delete = get_string('delete');
	$view = get_string('view');

	$polls = get_records('block_poll', 'courseid', $COURSE->id);

	$table = new Object();
	$table->head = array(get_string('editpollname', 'block_poll'),
			     get_string('editpolloptions', 'block_poll'),
			     get_string('responses', 'block_poll'),
			     get_string('action'));
	$table->align = array('left', 'right', 'right', 'left');
	$table->tablealign = 'left';
	$table->width = '*';

	foreach ($polls as $poll) {
		$options = get_records('block_poll_option', 'pollid', $poll->id);
		$responses = get_records('block_poll_response', 'pollid', $poll->id);
		$action = print_action('preview', "{$url}responses&amp;pid=$poll->id") .
			  print_action('edit', "{$url}editpoll&amp;pid=$poll->id") .
			  print_action('delete', "$CFG->wwwroot/blocks/poll/poll_action.php?id=$COURSE->id&amp;instanceid=" . $this->instance->id . "&amp;action=delete&amp;pid=$poll->id");
		$table->data[] = array($poll->name, (!$options ? '0' : count($options)), (!$responses ? '0' : count($responses)), $action);
	}

	print_table($table);
?>
