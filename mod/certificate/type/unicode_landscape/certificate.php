<?php
include 'tcpdf/tcpdfprotection.php';
require_once('tcpdf/config/lang/eng.php');
include 'tcpdf/tcpdf.php';

// Load certificate info
$certificateid = $certificate->id;
$certrecord = certificate_get_issue($course, $USER, $certificateid);
$strgrade = get_string('grade', 'certificate');
$strcoursegrade = get_string('coursegrade', 'certificate');

// Date formatting
if($certificate->printdate > 0) {
	$certificatedate = certificate_date_format('certdate', $certrecord);
} else {
	$certificatedate = "";
}
//Credit hour
$credithour = '';
if ($certificate->printcredithours > 0) {
$credit = get_string('credithours', 'certificate').": ".$certrecord->credits." ".get_string('hours', 'certificate');
}

//Grade formatting - can be customized if necessary
$grade = '';
if($certificate->printgradestd > 0) {
//	if ($modinfo->name) {
//		$grade = get_string('grade', 'certificate')." ".get_string('on', 'certificate')." ".$modinfo->name.':  '.$certrecord->grade;
//	} else {
		$grade = $strcoursegrade.':  '.$certrecord->grade;
//	}
	$grade = str_replace('%%P%%', get_string('gradepoints', 'certificate'), $grade);
}

//Print the teacher
$teachername = '';
if($certificate->printteacher) {
			if ($teachers = get_users_by_capability($context, 'mod/certificate:teacher')) {
            foreach ($teachers as $teacher) {
            $teachername = fullname($teacher);
            } 
	 }
}

// Print the code number
$code = '';
if($certificate->printnumber) {
if ($certrecord) {
$code = $certrecord->code;
}
}

//Print the student name
$studentname = '';
if ($certrecord) {
$studentname = $certrecord->studentname;
}
    
    $pdf = new TCPDF_Protection('L', 'pt', 'A4', true); 
    $pdf->SetProtection(array('print'));
    $pdf->print_header = false;
    $pdf->print_footer = false;
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setLanguageArray($l); //set language items
    $pdf->AddPage();
    $orientation = "L";
    $color = $certificate->bordercolor;
	print_border($certificate->borderstyle, $color, $orientation);
    print_watermark($certificate->printwmark, $orientation);
    print_seal($certificate->printseal, $orientation, 175, 420, 80, 80);
    print_signature($certificate->printsignature, $orientation, 550, 440, '', '');
	
// Add text
    $pdf->SetTextColor(0,0,120);
    cert_printtext(170, 125, 'C', 'FreeSerif', 'B', 30, get_string('titlelandscape', 'certificate'));
    $pdf->SetTextColor(0,0,0);
    cert_printtext(170, 180, 'C', 'Vera', '', 20, get_string('introlandscape', 'certificate'));
    cert_printtext(170, 230, 'C', 'FreeSerif', 'I', 30, $studentname);
	cert_printtext(170, 280, 'C', 'Vera', '', 20, get_string('statementlandscape', 'certificate'));
    cert_printtext(170, 320, 'C', 'FreeSerif', '', 20, $course->fullname);
    cert_printtext(170, 360, 'C', 'FreeSerif', '', 14, $certificatedate);
    cert_printtext(385, 450, 'C', 'FreeSerif', '', 10, $teachername);
    cert_printtext(150, 400, 'L', 'Vera', '', 10, $credit);
	cert_printtext(185, 400, 'R', 'Vera', '', 10, $grade);
    cert_printtext(170, 500, 'C', 'FreeSerif', '', 12, $code);
	cert_printtext(170, 450, '', '', '', '12', '');
?>