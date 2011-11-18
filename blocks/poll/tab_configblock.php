<?php
	// Paul Holden 24th July, 2007
	// poll block; block configuration tab

	$availpolls = get_records('block_poll', 'courseid', $COURSE->id);
	foreach ($availpolls as $poll) {
		$menu[$poll->id] = $poll->name;
	}

	$table = new Object();
	$table->head = array(get_string('config_param', 'block_poll'), get_string('config_value', 'block_poll'));
	$table->tablealign = 'left';
	$table->width = '*';

	$table->data[] = array(get_string('editpollname', 'block_poll'), choose_from_menu($menu, 'pollid', $this->config->pollid, 'choose', '', 0, true));
	$table->data[] = array(get_string('editblocktitle', 'block_poll'), '<input type="text" name="customtitle" value="' . $this->config->customtitle . '" />');
	$table->data[] = array(get_string('editmaxbarwidth', 'block_poll'), '<input type="text" name="maxwidth" value="' . $this->config->maxwidth . '" />');
	$table->data[] = array('&nbsp;', '<input type="submit" value="' . get_string('savechanges') . '" />');

	print_table($table);
?>

