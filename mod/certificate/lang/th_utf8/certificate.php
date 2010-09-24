<?PHP // $Id: certificate.php,v 1.18.2.7 2006/08/07 14:48:51 piriya Exp $ 
      // certificate.php - created with Moodle 1.7.1 (2005053000)


$string['modulename'] = 'ประกาศนียบัตร';
$string['modulenameplural'] = 'ประกาศนียบัตร';

$string['certificatetype'] = 'ประเภท';
$string['emailteachers'] = 'ส่งอีเมลให้ผู้สอน';
$string['savecertificate'] = 'บันทึกประกาศนียบัตร';
$string['deliver'] = 'การส่งมอบ';
$string['download'] = 'Force download';
$string['openbrowser'] = 'แสดงในหน้าต่างใหม่';
$string['emailcertificate'] = 'ทาง Email (ต้องเลือกบันทึกประกาศนียบัตร)';
$string['emailstudenttext'] = 'เอกสารที่แนบมาด้วยเป็นประกาศนียบัตรหลักสูตร $a->course.';
$string['awarded'] = 'Awarded';
$string['emailteachermail'] = '
$a->student ได้รับประกาศนียบัตร: \'$a->certificate\'
ของ $a->course.

คุณสามารถดูได้จาก:

    $a->url';
$string['emailteachermailhtml'] = '
$a->student ได้รับประกาศนียบัตร: \'<i>$a->certificate</i>\'
ของ $a->course.

คุณสามารถดูได้จาก:

    <a href=\"$a->url\">ประกาศนียบัตร</a>.';
$string['border'] = 'ขอบ';
$string['borderstyle'] = 'รูปแบบขอบ';
$string['bordernone'] = 'ไม่';
$string['borderlines'] = 'Lines';
$string['bordercolor'] = 'สีขอบ';
$string['borderblack'] = 'ดำ';
$string['borderbrown'] = 'น้ำตาล';
$string['borderblue'] = 'น้ำเงิน';
$string['bordergreen'] = 'เขียว';
$string['printwmark'] = 'ภาพพื้นหลัง';

$string['datehelp'] = 'วันที่';
$string['dateformat'] = 'รูปแบบวันที่';
$string['receiveddate'] = "วันที่ออกประกาศนียบัตร";
$string['courseenddate'] = 'วันที่จบหลักสูตร (ต้องตั้งค่าวันสุดท้ายของหลักสูตร)';

$string['printcode'] = 'รหัสวิชา';

$string['printgrade'] = 'คะแนน';
$string['print_grades'] = 'คะแนน';
$string['grade'] = 'คะแนน';
$string['coursegrade'] = 'คะแนนทั้งวิชา';
$string['nogrades'] = 'ไม่มีคะแนน';
$string['gradeformat'] = 'รูปแบบคะแนน';
$string['gradepercent'] = 'เปอร์เซนต์';
$string['gradepoints'] = 'คะแนน';
$string['gradeletter'] = 'ตัวอักษร';

$string['printsignature'] = 'ลายเซนต์';
$string['sigline'] = 'เส้น';

$string['printteacher'] = 'ชื่อผู้สอน';
$string['printdate'] = 'วันที่';
$string['printseal'] = 'ตราประทับ';

$string['code'] = 'รหัส';
$string['issued'] = 'มอบแล้ว';
$string['notissued'] = 'ไม่ได้มอบ';
$string['notissuedyet'] = 'ยังไม่ได้มอบ';
$string['notreceived'] = 'คุณไม่ได้รับประกาศนียบัตรนี้';
$string['getcertificate'] = 'รับประกาศนียบัตร';
$string['report'] = 'รายงาน';
$string['viewed'] = 'คุณได้รับประกาศนียบัตรเมื่อ';
$string['viewcertificateviews'] = 'คุณมีประกาศนียบัตร $a ใบ';
$string['reviewcertificate'] = 'ดูประกาศนียบัตร';
$string['openwindow'] = 'กดปุ่มด้านล่างเพื่อรับประกาศนียบัตรในหน้าต่างใหม่';
$string['download'] = 'ดาวน์โหลด';
$string['opendownload'] = 'กดปุ่มด้านล่างเพื่อดาวน์โหลดประกาศนียบัตร';
$string['openemail'] = 'กดปุ่มด่านล่างเพื่อรับประกาศนียบัตรทางอีเมล';
$string['receivedcerts'] = 'รับประกาศนียบัตร';
$string['certificate:view'] = 'เข้าชมประกาศนียบัตร';
$string['certificate:manage'] = 'จัดการประกาศนียบัตร';
$string['certificate:teacher'] = 'ชื่อผู้สอน';
$string['certificate:student'] = 'ได้รับประกาศนียบัตร';
$string['gsettings'] = 'ตั้งค่า';
$string['gradesettings'] = 'ตั้งค่าคะแนน';
$string['format'] = 'รูปแบบประกาศนียบัตร';
$string['unenrol'] = 'ยกเลิกประกาศนียบัตรได้';

//names of type folders
$string['typeportrait'] = 'แนวตั้ง';
$string['typeletter_portrait'] = 'แนวตั้ง (จดหมาย)';
$string['typelandscape'] = 'แนวนอน';
$string['typeletter_landscape'] = 'แนวนอน (จดหมาย)';
$string['typeunicode_landscape'] = 'Unicode (แนวนอน)';

//strings for verification 
$string['configcontent'] = 'ตั้งค่าเนื้อหา';
$string['validate'] = 'ตรวจสอบ';
$string['certificate'] = 'ตรวจสอบประกาศนียบัตรหมายเลข:';
$string['verifycertificate'] = 'ตรวจสอบประกาศนียบัตร';
$string['dontallowall'] = 'ไม่อนุญาต';
$string['cert'] = '#';
$string['notfound'] = 'หมายเลขประกาศนียบัตรไม่ถูกต้อง';
$string['back'] = 'Back';
$string['to'] = 'มอบให้';
$string['course'] = 'หลักสูตร';
$string['date'] = 'เมื่อ';
$string['alert1'] = 'คุณกำลังเข้าสู่การออกประกาศนียบัตร เมื่อออกประกาศนียบัตรให้แล้วจะไม่สามารถเปลี่ยนแปลงข้อมูลได้ เช่น เกรด, ชื่อ, วันที่ออกประกาศนียบัตร คุณควรเปลี่ยนแปลงข้อมูลให้เรียบร้อยก่อนดำเนินการต่อ';
$string['attention'] = 'ประกาศ';
$string['alert2'] = 'การกระทำนี้จะยกเลิกประกาศนียบัตรในหลักสูตรนี้ ถ้าต้องการทำกิจกรรม กรุณาทำก่อนขอรับประกาศนียบัตร';
$string['backbutton'] = 'ย้อนกลับ';

//strings for certificates
$string['titlelandscape'] = 'สำนักงานอัยการสูงสุด';
$string['introlandscape'] = 'มอบประกาศนียบัตรฉบับนี้เพื่อแสดงว่า';
$string['statementlandscape'] = 'ได้ผ่านหลักสูตร';

$string['titleletterlandscape'] = 'สำนักงานอัยการสูงสุด';
$string['introletterlandscape'] = 'มอบประกาศนียบัตรฉบับนี้เพื่อแสดงว่า';
$string['statementletterlandscape'] = 'ได้ผ่านหลักสูตร';

$string['titleportrait'] = 'สำนักงานอัยการสูงสุด';
$string['introportrait'] = 'มอบประกาศนียบัตรฉบับนี้เพื่อแสดงว่า';
$string['statementportrait'] = 'ได้ผ่านหลักสูตร';
$string['ondayportrait'] = 'ให้ไว้ ณ วันที่';

$string['titleletterportrait'] = 'สำนักงานอัยการสูงสุด';
$string['introletterportrait'] = 'มอบประกาศนียบัตรฉบับนี้เพื่อแสดงว่า';
$string['statementletterportrait'] = 'ได้ผ่านหลักสูตร';

//Conditional certificate
$string['conditional'] = "เงื่อนไขคะแนน";
$string['notpossible'] = 'คะแนนของคุณไม่เพียงพอที่จะรับประกาศนียบัตร</br> คุณต้องทำดังนี้: ';
$string['condeactivationd'] = 'ไม่อนุญาต';
$string['condeactivationa'] = 'อนุญาต';
$string['referencegrade'] = 'คะแนนที่ใช้';
$string['gradeformat'] = 'รูปแบบคะแนน';
$string['on'] = 'on';

//Credit hours
$string['printcredithours'] = 'พิมพ์ชั่วโมงของหลักสูตร';
$string['credithours'] = 'จำนวนชั่วโมง';
$string['hours'] = 'ชั่วโมง';
?>
