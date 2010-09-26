<?php

include("../../config.php");

$page   = optional_param('page', 0, PARAM_INT);
				$perpage= optional_param('perpage', 10, PARAM_INT);        // how many per page
				$orderby = optional_param('orderby','id');
				$order_type = optional_param('ordertype','ASC');
				 if($order_type=='ASC') $ordertype='DESC';
				if($order_type=='DESC') $ordertype='ASC';
				$block_id = optional_param('course_id', 106, PARAM_INT);
                

$records=get_records_select("block_shoutbox_shoutbox","block_id=$block_id","$orderby $ordertype","*", ($page*$perpage), $perpage);

$header="Date \t Username \t Message";

foreach($records as $row)
{
	$line = '';
	$value='';
	$date_explode=$row->date;
	$date_format=explode(" ",$date_explode);
	 $user_sql="SELECT * FROM {$CFG->prefix}user where id={$row->user_id} ";
	$use=get_record_sql($user_sql);
	$fullname = fullname($use);
	$value .= '"'.$date_format[0].' '.$date_format[1].' '.$date_format[3].'"' . "\t";
	$value .= '"'.$fullname.'"' . "\t";
	$value .= '"'.$row->data. '"' . "\t";
	
	
	
	$line .= $value;
	
	$data .= trim($line)."\n";
}

$data = str_replace("\r", "", $data);

if ($data == "") {
$data = "\nno matching records found\n";
}

$header=$header."\n";

$fname='report_'.$id.'.xls';
//$fp = fopen($fname,'w');
//fwrite ($fp,$header);
//fwrite ($fp,$data);





header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");;
header("Content-Disposition: attachment;filename=".$fname); 
header("Content-Transfer-Encoding: binary ");
//readfile($fname);
echo $header;
echo $data;
exit;
?>