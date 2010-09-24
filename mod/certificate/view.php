<?PHP // $Id: version.php,v 3.1.0
require_once("../../config.php");
require_once("lib.php");
include '../../lib/fpdf/fpdf.php';
include '../../lib/fpdf/fpdfprotection.php';

$strreviewcertificate = get_string('reviewcertificate', 'certificate');
$strgetcertificate = get_string('getcertificate', 'certificate');

 $id = required_param('id', PARAM_INT);    // Course Module ID
    $action = optional_param('action', '');
  if ($id) {
    if (! $cm = get_coursemodule_from_id('certificate', $id)) {
            error("Course Module ID was incorrect");
        }
   
    if (! $course = get_record("course", "id", $cm->course)) {
        error("course is misconfigured");
    }

    if (! $certificate = get_record("certificate", "id", $cm->instance)) {
        error("course module is incorrect");
    }
} else {
    if (! $certificate = get_record("certificate", "id", $a)) {
        error("course module is incorrect");
    }
    if (! $course = get_record("course", "id", $certificate->course)) {
        error("course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("certificate", $certificate->id, $course->id)) {
        error("course Module ID was incorrect");
    }
}

    require_login($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/certificate:view', $context);
// log update
    add_to_log($course->id, "certificate", "view", "view.php?id=$cm->id", $certificate->id, $cm->id);
// Get teacher name of group
    if (groupmode($course, null) == SEPARATEGROUPS) {   // Separate groups are being used
    if (!$group = user_group($course->id, $USER->id)) {             // Try to find a group
        $group->id = 0;                                             // Not in a group, never mind
    }
    $teachers = get_group_teachers($course->id, $group->id);        // Works even if not in group
}
else {
    $teachers = get_course_teachers($course->id);
}

//Creating pages
$generate = false;
$unenrolment = false;
$type = $certificate->certificatetype;
$certificateid = $certificate->id;
$certrecord = certificate_get_issue($course, $USER, $certificateid);
if($certificate->printgrade > 1) {
$modinfo = certificate_mod_grade($course, $certificate->printgrade);
}
//Review certificate
if($certrecord AND !isset($_GET['certificate'])) {
	view_header($course, $certificate, $cm);
    echo "<p align=\"center\">".get_string('viewed', 'certificate')."<br /> ".certificate_date_format('timecreated', $certrecord).", ".strftime('%X', $certrecord->timecreated)."</p>";
    echo '<center>';
    echo '<form action="" method="get" name="form1" target="_blank">';
    echo '<input type="hidden" name="id" value='.$cm->id.' />';
    echo '<input type="hidden" name="certificate" value='.$certificate->id.' />';
    echo '<input type="button" name="Submit" value="'.get_string('backbutton', 'certificate').'" onClick="JavaScript:history.back();" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="Submit" value="'.$strreviewcertificate.'" />';
    echo '</form>';
    echo '</center>';
	print_footer(NULL, $course);
} else if ($certrecord AND isset($_GET['certificate'])) {
	$generate = true;
} else {
	//Record Certificate
	if (certificate_grade_condition()) {
		if(!isset($_GET['certificate'])) {
			view_header($course, $certificate, $cm);
			echo "<table width=\"100%\" border=\"0\" cellspacing=\"40\" cellpadding=\"0\"><tr><td colspan=\"2\"><div align=\"justify\"><p>".get_string('alert1', 'certificate').'</p></div></td></tr>';
			if ($certificate->unenrol > 0) {
			echo "<tr><td><strong><font color=\"#FF0000\">".get_string('attention', 'certificate').":</font></strong></td><td><div align=\"justify\"><p>".get_string('alert2', 'certificate').'</p></div></td></tr>';
			}
			echo '<tr><td colspan="2"><center>';
			echo '<form action="" method="post" name="form2">';
    		echo '<input type="button" name="Submit" value="'.get_string('backbutton', 'certificate').'" onClick="JavaScript:history.back();" />';
    		echo '</form></center></td></tr>';
			echo '<tr><td colspan="2">';
		    if ($certificate->delivery == 0)    {
    			echo "<p align=\"center\">".get_string('openwindow', 'certificate')."<br /> </p>";
			} else if ($certificate->delivery == 1)    {
    			echo "<p align=\"center\">".get_string('opendownload', 'certificate')."<br /> </p>";
			} else if ($certificate->delivery == 2)    {
    			echo "<p align=\"center\">".get_string('openemail', 'certificate')."<br /> </p>";
			}
		    echo '<center>';
    		echo '<form action="" method="get" name="form1" target="_blank">';
    		echo '<input type="hidden" name="id" value='.$cm->id.' />';
    		echo '<input type="hidden" name="certificate" value='.$certificate->id.' />';
    		echo '<input type="submit" name="Submit" value="'.$strgetcertificate.'" />';
    		echo '</form>';
    		echo '</center>';
			echo '</td></tr></table>';
			print_footer(NULL, $course);
		} else {
			$temp = certificate_prepare_issue($course, $USER);
			$generate = true;
			if ($certificate->unenrol > 0){
				$unenrolment = true;
			}
		}
	// Danny certificate.
	} else {
		view_header($course, $certificate, $cm);
    	echo "<p align=\"center\">".get_string('notpossible', 'certificate').$certificate->gradecondition;
		if ($certificate->gradefmt == 1) {
			echo '%';
		}
		if ($certificate->gradefmt == 2) {
			echo " ".get_string('gradepoints', 'certificate');
		}
		echo " ";
		if ($modinfo->name) {
			echo get_string('on', 'certificate')." ".utf8_encode($modinfo->name);
		}
    	echo '.</p><center>';
		echo '<br />';
		echo '<form action="" method="post" name="form2">';
    	echo '<input type="button" name="Submit" value="'.get_string('backbutton', 'certificate').'" onClick="JavaScript:history.back();" />';
    	echo '</form>';
		echo '</center>';
    	echo '<iframe name="certframe" id="certframe" frameborder="NO" border="0" style="width:90%;height:500px;border:0px;">';
		echo '</iframe>';

		print_footer(NULL, $course);
	}
}


// Output to pdf
if ($generate) {
// Load custom type
	require ("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php");

	$userid = $USER->id;
	certificate_file_area($userid);
	$file = $CFG->dataroot.'/'.$course->id.'/moddata/certificate/'.$certificate->id.'/'.$USER->id.'/certificate.pdf';

	if($certificate->savecert == 1){
		$pdf->Output($file, 'F');//save as file
	}
	if($certificate->delivery == 0){
		$pdf->Output('certificate.pdf', 'I');// open in browser
	}
	if($certificate->delivery == 1){
		$pdf->Output('certificate.pdf', 'D'); // force download
	}
	if($certificate->delivery == 2){
		$pdf->Output('certificate.pdf', 'I');// open in browser
		$pdf->Output('', 'S');// send
		certificate_email_students($USER);
	}
	if ($unenrolment) {
		include $CFG->dirroot.'enrol/manual/enrol/enrol.php';
		$contextunenrol = get_context_instance(CONTEXT_COURSE, $course->id);
		role_unassign(0, $USER->id, 0, $contextunenrol->id);
	}
}
?>