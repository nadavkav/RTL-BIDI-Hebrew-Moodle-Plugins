<script language="javascript" type="text/javascript">
 function show_poll_results(id) {
  window.location.href="<?php echo(str_replace('&amp;', '&', $url) . 'editpoll&pid='); ?>" + id;
 }
</script>

<?php
	// Paul Holden 24th July, 2007
	// poll block; poll creating/editing tab

	$id = optional_param('id', 0, PARAM_INTEGER);	
	$pid = optional_param('pid', 0, PARAM_INTEGER);

	$polls = get_records('block_poll', 'courseid', $COURSE->id);
	foreach ($polls as $poll) {
		$menu[$poll->id] = $poll->name;
	}

	print_simple_box_start();
	echo(get_string('editpollname', 'block_poll') . ': ');
	choose_from_menu($menu, 'pid', $pid, 'choose', 'show_poll_results(this.options[this.selectedIndex].value);');
	print_simple_box_end();

	$poll = get_record('block_poll', 'id', $pid);
	$poll_options = get_records('block_poll_option', 'pollid', $pid);
	$poll_option_count = (!$poll_options ? 0 : count($poll_options));

?>
</form>
<form method="get" action="<?php echo($CFG->wwwroot); ?>/blocks/poll/poll_action.php">
<input type="hidden" name="pid" value="<?php echo($pid); ?>" />
<input type="hidden" name="action" value="<?php echo($pid == 0 ? 'create' : 'edit'); ?>" />
<input type="hidden" name="instanceid" value="<?php echo($this->instance->id) ;?>" />
<input type="hidden" name="id" value="<?php echo($id) ;?>" />
<input type="hidden" name="sesskey" value="<?php echo($USER->sesskey) ;?>" />
<input type="hidden" name="blockaction" value="config" />
<input type="hidden" name="course" value="<?php echo($COURSE->id); ?>" />

<?php

	$eligible = array('all' => get_string('all'), 'students' => get_string('students'), 'teachers' => get_string('teachers'));
	for ($i = 1; $i <= 10; $options[$i++] = ($i - 1)) {}

	$table = new Object();
	$table->head = array(get_string('config_param', 'block_poll'), get_string('config_value', 'block_poll'));
	$table->tablealign = 'left';
	$table->width = '*';

	$table->data[] = array(get_string('editpollname', 'block_poll'), '<input type="text" name="name" value="' . (!$poll ? '' : $poll->name) . '" />');
	$table->data[] = array(get_string('editpollquestion', 'block_poll'), '<input type="text" name="questiontext" value="' . (!$poll ? '' : $poll->questiontext) . '" />');
	$table->data[] = array(get_string('editpolleligible', 'block_poll'), choose_from_menu($eligible, 'eligible', $poll->eligible, 'choose', '', 0, true));
	$table->data[] = array(get_string('editpolloptions', 'block_poll'), choose_from_menu($options, 'optioncount', $poll_option_count, 'choose', '', 0, true));

	$option_count = 0;
	foreach ($poll_options as $option) {
		$option_count++;
		$table->data[] = array(get_string('option', 'block_poll') . " $option_count", "<input type=\"text\" name=\"options[$option->id]\" value=\"$option->optiontext\" />");
	}
	for ($i = $option_count + 1; $i <= $poll_option_count; $i++) {
		$table->data[] = array(get_string('option', 'block_poll') . " $i", '<input type="text" name="newoptions[]" />');
	}

	$table->data[] = array('&nbsp;', '<input type="submit" value="' . get_string('savechanges') . '" />');

	print_table($table);
?>

