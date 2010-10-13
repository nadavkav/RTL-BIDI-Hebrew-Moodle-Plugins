<?php // $Id: locallib.php, v 0.1 9/23/2008 jstein Exp $ 
      // /blocks/openshare/locallib.php - created for Moodle 1.9

//Update groups_members Course Members group with students, teachers, editing teachers
function block_openshare_updategroup($courseid,$groupid){
	$sql = 'SELECT u.id FROM mdl_user u 
	JOIN mdl_role_assignments ra ON ra.userid = u.id 
	JOIN mdl_role r ON ra.roleid = r.id 
	JOIN mdl_context con ON ra.contextid = con.id 
	JOIN mdl_course c ON c.id = con.instanceid AND con.contextlevel = 50 WHERE (r.shortname = \'student\' OR r.shortname = \'teacher\' OR r.shortname = \'editingteacher\' OR r.shortname = \'coursecreator\') AND c.id = '.$courseid;
	$rs = get_recordset_sql($sql);
	
	if(!empty($rs)){
		while ($rec = rs_fetch_next_record($rs)) {
			//prep dataobject for door
			$groupenroll = new object();
			$groupenroll->timeadded = time();
			$groupenroll->groupid = $groupid;
			$groupenroll->userid = $rec->id;
			
			$ingroup = get_record("groups_members", "groupid", $groupid, "userid", $rec->id);
			if (empty($ingroup)){
				insert_record("groups_members", $groupenroll);
				print 'updated'.$groupenroll->groupid.$groupenroll->userid.'<br/>';
			}
		}
	} else {
			print_error("No users in this course!");
	}

	// Close the recordset to save memory
	rs_close($rs);
}


	 
//test a lot of Open Course settings	
function block_check_opensettings($courseid){
	//do this once on page load to prevent overload on loops

	//see if Open Meta, Open Course, Groups, Groupings are enabled
	$openshare = get_record("block", "name", "openshare");
	//print "Open Meta block status:".$openshare->visible;
	
	$opencourse = get_record("block_openshare_courses", "courseid", $courseid);
	//print "<br/>Open Course Status:".$opencourse->status;
	
	//we need to know what "resource" module's id# is so we can signify glass door on resources only
	$resmod = get_record("modules", "name", "resource");
	
	//we need to know how the open_licenses table has as ids for copyright and cc default
	$ccid = get_record("block_openshare_licenses","name","CC by-nc-sa");
	$cid = get_record("block_openshare_licenses","name","copyright");
	
	/* Check grouping on submit
	$opengroup = get_record("groups", "courseid", $courseid, "name", "Course Members");
	//print "<br/>Course Members Group status:".$opengroup->id.$opengroup->name;
	
	$opengrouping = get_record("groupings", "courseid", $courseid, "name", "Closed");
	//print "<br/>Closed Grouping status:".$opengrouping->id.$opengrouping->name;
	
	$opengroupinggroup = get_record("groupings_groups", "groupingid", $opengrouping->id, "groupid", $opengroup->id);
	//print "<br/>Grouping Group status:".$opengroupinggroup->id;
	*/
	
	return array(
	"ccid"=>$ccid->id,
	"cid"=>$cid->id,
	"resid"=>$resmod->id,
	"ometa"=>$openshare->visible,
	"ocourse"=>$opencourse->status
	/*
	"oggg"=>$opengroupinggroup->id,
	"ogg"=>$opengrouping->id,
	"og"=>$opengroup->id,
	*/
	);
}

?>