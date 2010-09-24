<?PHP // $Id: admin.php,v 1.18.2.7 2006/08/07 14:48:51 skodak Exp $ 
      // admin.php - created with Moodle 1.6 development (2005053000)


$string['modulename'] = 'Certificate';
$string['modulenameplural'] = 'Certificates';

$string['certificatetype'] = 'Certificate Type';
$string['emailteachers'] = 'Email Teachers';
$string['savecertificate'] = 'Save Certificates';
$string['deliver'] = 'Delivery';
$string['download'] = 'Force download';
$string['openbrowser'] = 'Open in new window';
$string['emailcertificate'] = 'Email (Must also choose save!)';
$string['emailstudenttext'] = 'Attached is your certificate for $a->course.';
$string['awarded'] = 'Awarded';
$string['emailteachermail'] = '
$a->student has received their certificate: \'$a->certificate\'
for $a->course.

You can review it here:

    $a->url';
$string['emailteachermailhtml'] = '
$a->student has received their certificate: \'<i>$a->certificate</i>\'
for $a->course.

You can review it here:

    <a href=\"$a->url\">Certificate Report</a>.';
$string['border'] = 'Border';
$string['borderstyle'] = 'Border Style';
$string['bordernone'] = 'No border';
$string['borderlines'] = 'Lines';
$string['bordercolor'] = 'Border Color';
$string['borderblack'] = 'Black';
$string['borderbrown'] = 'Brown';
$string['borderblue'] = 'Blue';
$string['bordergreen'] = 'Green';
$string['printwmark'] = 'Print Watermark';

$string['datehelp'] = 'Date';
$string['dateformat'] = 'Date Format';
$string['receiveddate'] = "Date Received";
$string['courseenddate'] = 'Course End Date (Must be set!)';

$string['printcode'] = 'Print Code';

$string['printgrade'] = 'Print Grade';
$string['grade'] = 'Grade';
$string['coursegrade'] = 'Course Grade';
$string['nogrades'] = 'No grades available';
$string['gradeformat'] = 'Grade Format';
$string['gradepercent'] = 'Percentage Grade';
$string['gradepoints'] = 'Points Grade';
$string['gradeletter'] = 'Letter Grade';

$string['printsignature'] = 'Print Signature';
$string['sigline'] = 'line';

$string['printteacher'] = 'Print Teacher';
$string['printdate'] = 'Print Date';
$string['printseal'] = 'Print Seal';

$string['code'] = 'Code';
$string['issued'] = 'Issued';
$string['notissued'] = 'Not Issued';
$string['notissuedyet'] = 'Not issued yet';
$string['notreceived'] = 'You have not received this certificate';
$string['getcertificate'] = 'Get your certificate';
$string['report'] = 'Report';
$string['viewed'] = 'You received this certificate on:';
$string['viewcertificateviews'] = 'View $a issued certificates';
$string['reviewcertificate'] = 'Review your certificate';
$string['openwindow'] = 'Click the button below to open your certificate
in a new browser window.';
$string['download'] = 'Download';
$string['opendownload'] = 'Click the button below to save your certificate
to your computer.';
$string['openemail'] = 'Click the button below and your certificate
will be sent to you as an email attachment.';
$string['receivedcerts'] = 'Received certificates';
$string['certificate:view'] = 'View Certificate';
$string['certificate:manage'] = 'Manage Certificate';
$string['certificate:teacher'] = 'Print Teacher';
$string['certificate:student'] = 'Get Certificate';
$string['gsettings'] = 'SETTINGS';
$string['gradesettings'] = 'GRADE SETTINGS';
$string['format'] = 'FORMAT';
$string['unenrol'] = 'Cancel enrolment';

//names of type folders
$string['typeportrait'] = 'Portrait';
$string['typeletter_portrait'] = 'Portrait (letter)';
$string['typelandscape'] = 'Landscape';
$string['typeletter_landscape'] = 'Landscape (letter)';
$string['typeunicode_landscape'] = 'Unicode (landscape)';

//strings for verification 
$string['configcontent'] = 'Config content';
$string['validate'] = 'Verify';
$string['certificate'] = 'Verification for certificate code:';
$string['verifycertificate'] = 'Verify Certificate';
$string['dontallowall'] = 'Do not allow all';
$string['cert'] = '#';
$string['notfound'] = 'The certificate number could not be validated.';
$string['back'] = 'Back';
$string['to'] = 'Awarded to';
$string['course'] = 'For';
$string['date'] = 'On';
$string['alert1'] = 'This activity generates a certificate. After first emission certificates can’t be replaced anymore, data like grade, name and date stay same after emission of this certificate, if you like to change some alteration in your data or like to complete some activity complete this alterations before continue.';
$string['attention'] = 'ATTENTION';
$string['alert2'] = 'Emission of this certificate cancels your registration in this course, if you like to do some activity do it before generate this certificate.';
$string['backbutton'] = 'Back';

//strings for certificates
$string['titlelandscape'] = 'CERTIFICATE of ACHIEVEMENT';
$string['introlandscape'] = 'This is to certify that';
$string['statementlandscape'] = 'has completed the course';

$string['titleletterlandscape'] = 'CERTIFICATE of ACHIEVEMENT';
$string['introletterlandscape'] = 'This is to certify that';
$string['statementletterlandscape'] = 'has completed the course';

$string['titleportrait'] = 'CERTIFICATE of ACHIEVEMENT';
$string['introportrait'] = 'This is to certify that';
$string['statementportrait'] = 'has completed the course';
$string['ondayportrait'] = 'on this day';

$string['titleletterportrait'] = 'CERTIFICATE of ACHIEVEMENT';
$string['introletterportrait'] = 'This is to certify that';
$string['statementletterportrait'] = 'has completed the course';

//Conditional certificate
$string['conditional'] = "Conditional to grade";
$string['notpossible'] = 'Your grade in this course is not sufficient to obtain this certificate. </br> To get this certificate you need: ';
$string['condeactivationd'] = 'Disable';
$string['condeactivationa'] = 'Enable';
$string['referencegrade'] = 'Grade to be used';
$string['gradeformat'] = 'Grade Format';
$string['on'] = 'on';

//Credit hours
$string['printcredithours'] = 'Print credit hours';
$string['credithours'] = 'Credit hours';
$string['hours'] = 'hours';
?>
