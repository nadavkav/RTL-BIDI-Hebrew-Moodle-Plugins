					<?php
					require_once('../../config.php');
					global $CFG, $COURSE;
					?>
					<link href="<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/styles.css" rel="stylesheet" type="text/css" />
					<script src='<?php echo $CFG->wwwroot; ?>/lib/xajaxex.js'></script>
					<script src='<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/script/ajaxfunction.js'></script>
					 <?php
					
					  $block_id = optional_param('block_id', 0, PARAM_INT);
					  $course_id = optional_param('course_id', 0, PARAM_INT);
					  $row = optional_param('row', 0, PARAM_INT);
					 
					echo"<div class=list_container>";
					$sql="SELECT * FROM {$CFG->prefix}block_shoutbox_shoutbox where block_id={$block_id} order by id desc ";
					$q = get_records_sql($sql, 0, $row);
					$i=0;
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
echo"<div class=$class><span style='float:left;'>".print_user_picture($use->id,$COURSE->id,$use->picture,10,true, true)."</span>".'<span style=text-align:justify;>'.nl2br($expense->data)."</span><br>";
					
					echo"<span class=divcont2><a href=".$CFG->wwwroot."/user/view.php?id=".$use->id."&course=".$COURSE->id.">".fullname($use)."</a></span>";
					$date_explode=$expense->date;
					$date_format=explode(" ",$date_explode);
					
					echo"<span class=divcont1>".$date_format[0].'&nbsp;'.$date_format[1].'&nbsp;'.$date_format[3].'&nbsp;'.$date_format[4]."</span>";
					echo "<br /></div>";
					
					 
					
					$i++;
					}
					echo"</div>";
					echo "%".$block_id;
					?>