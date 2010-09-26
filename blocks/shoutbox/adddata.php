				<?php
				require_once('../../config.php');
				global $CFG, $COURSE;
				?>
				<link href="<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/styles.css" rel="stylesheet" type="text/css" />
				<script src='<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/script/xajaxex.js'></script>
				<script src='<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/script/ajaxfunction.js'></script>
				 <?php
				 $data=$_REQUEST['data'];
				 $block_id=$_REQUEST['block_id'];
				
				if(strpos($data,' ')===false)
				{
			    $val='';
                 
				if($_REQUEST['noofcharbreak']<= strlen($data))
				{
				
				$result=str_split($data,$_REQUEST['noofcharbreak']);
				for($i=0; $i<count($result); $i++)
				{
				$val.=$result[$i]."<br>";
				}}
				else
				{
				$val=$data;
				}
				}
				else
				{
				$val=$data;
				}
				 $request=$_REQUEST['req'];
				 $course_id=$_REQUEST['course_id'];
				 $row=$_REQUEST['row'];
				 $user_id=$_REQUEST['u_id'];
				if($request=='add' && strlen(trim($data)) > 1 )
				{
					
					$date=date("d M Y h:i A");
				mysql_query("insert into {$CFG->prefix}block_shoutbox_shoutbox  set course_id=\"$course_id\", block_id=\"$block_id\",  data=\"$val\", user_id=\"$user_id\", date=\"$date\" ") or die(mysql_error());
				
				
				
				$sql="SELECT * FROM {$CFG->prefix}block_shoutbox_shoutbox where block_id={$block_id} order by id desc ";
				$q = get_records_sql($sql, 0, $row);
				$i=0;
				echo"<div class=list_container>";
				foreach($q as $expense)
				{
				 $user_sql="SELECT * FROM {$CFG->prefix}user where id={$expense->user_id} ";
							  if($i%2==0)
							  {
							  $class="divcont3";
							  }
							  else
							  {
							  $class="divcont4";
							  } 
				//$use= mysql_fetch_array(mysql_query($user_sql));
				$use=get_record_sql($user_sql);
				$fullname=fullname($use);
echo"<div class=$class ><span style='float:left;'>".print_user_picture($use->id,$COURSE->id,$use->picture,10,true, true)."</span>".'<span style=text-align:justify;>'.nl2br($expense->data)."</span><br/>";
				echo"<span class=divcont2><a href=".$CFG->wwwroot."/user/view.php?id=".$use->id."&course=".$COURSE->id.">".fullname($use)."</a></span>";
				$date_explode=$expense->date;
				$date_format=explode(" ",$date_explode);
				
				echo"<span class=divcont1>".$date_format[0].'&nbsp;'.$date_format[1].'&nbsp;'.$date_format[3].'&nbsp;'.$date_format[4]."</span><br></div>";
				
				echo"</div>";
				
				$i++;
				}
				echo "</div>";
				echo "%".$block_id;
				}
				
				
				
				?>