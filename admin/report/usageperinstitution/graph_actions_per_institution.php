<?php

	require_once('../../../config.php');
	global $CFG;
	include ($CFG->dirroot."/lib/graphlib.php");

	$institution = $_GET['institution'];

// 	$fromdate = $_GET['fromdate'];
// 	$todate = $_GET['todate'];
// 	$fromtime = $_GET['fromtime'];
// 	$totime = $_GET['totime'];
//
// 	$sql = 'SELECT 	DATE_FORMAT( FROM_UNIXTIME( time ) , \'%Y.%m.%d-%k:%i\' ) AS grptime,
// 				COUNT( time ) AS permin
// 				FROM mdl_log m
// 				WHERE 	DATE_FORMAT( FROM_UNIXTIME( time ) , \'%Y-%m-%d %H:%i\' ) >= \''.$fromdate.' '.$fromtime.'\' AND
// 						DATE_FORMAT( FROM_UNIXTIME( time ) , \'%Y-%m-%d %H:%i\' ) <= \''.$todate.' '.$totime.'\'
// 				GROUP BY grptime
// 				ORDER BY grptime DESC';

	$sql = 'SELECT count(m.`institution`) usercount , m.`institution` , mlog.`action`
				FROM mdl_user m LEFT OUTER JOIN mdl_log mlog ON mlog.userid = m.id
				WHERE m.`institution` LIKE \''.$institution.'\'
					GROUP BY m.`institution` , mlog.`action`
					HAVING count( m.`institution` ) >10
					ORDER BY usercount DESC';

	$userspermin = get_recordset_sql($sql);
	//print_r($userspermin);die;
	$permincount = array();
	$maxpermin = 1;
	$xcount = 1;
	while ($row = rs_fetch_next_record($userspermin)) {
		$permincount[] = $row->usercount;
		//$xdata[$xcount] = $xcount++;
		$xdata[] = $row->action;
		if ($maxpermin < $row->usercount ) $maxpermin = $row->usercount;
	}
	//print_r($permincount);
	rs_close($userspermin);

	$bar = new graph(800,600);
	$bar->parameter['title']   = '';
	$bar->parameter['y_label_left'] = 'users per minute';
	$bar->parameter['x_label'] = 'count';
	$bar->parameter['y_label_angle'] = 90;
	$bar->parameter['x_label_angle'] = 0;
	$bar->parameter['x_axis_angle'] = 60;

	//following two lines seem to silence notice warnings from graphlib.php
	$bar->y_tick_labels = null;
	$bar->offset_relation = null;

	//$bar->parameter['bar_size']    = 1; // will make size > 1 to get overlap effect when showing groups
	//$bar->parameter['bar_spacing'] = 1; // don't forget to increase spacing so that graph doesn't become one big block of colour

	$bar->x_data = $xdata;
	$bar->y_data['count'] = $permincount;
	$bar->y_format['count'] = array('colour' => 'blue', 'bar' => 'fill');
	$bar->y_order = array('count');


	$bar->parameter['y_min_left'] = 0;  // start at 0
	$bar->parameter['y_max_left'] = $maxpermin;
	$bar->parameter['y_decimal_left'] = 0; // 2 decimal places for y axis.

	//$bar->parameter['x_min_left'] = 0;  // start at 0
	//$bar->parameter['x_max_left'] = count($userspermin);

	//$bar->parameter['y_axis_gridlines'] = $maxpermin;
	$bar->draw();

?>