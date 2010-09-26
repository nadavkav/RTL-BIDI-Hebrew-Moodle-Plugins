			<?php
			    require_once('../../config.php');
				$page   = optional_param('page', 0, PARAM_INT);
				$perpage= optional_param('perpage', 15, PARAM_INT);        // how many per page
				$orderby = optional_param('orderby','id');
				$order_type = optional_param('ordertype','ASC');
				 if($order_type=='ASC') $ordertype='DESC';
				if($order_type=='DESC') $ordertype='ASC';
				$block_id = optional_param('course_id', 0, PARAM_INT);
			?>
			
			<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/theme/standard/styles.php" />
			<link rel="stylesheet" type="text/css" href="<?php echo $CFG->themewww .'/'. current_theme() ?>/styles.php" />
			<link href="<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/styles.css" rel="stylesheet" type="text/css" />
			<script src='<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/script/xajaxex.js'></script>
			<script src='<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/script/ajaxfunction.js'></script>
			<?php
			global $CFG, $COURSE;
			if(isset($_POST['delete'])) {
			 $block_id = optional_param('blockid', 0, PARAM_INT);
			 $checkbox_select=$_POST['check_delete'];
			  $com_id=implode(",", $checkbox_select);
			  $sql="delete from {$CFG->prefix}block_shoutbox_shoutbox where id in($com_id)";
			 mysql_query($sql);
			}
			echo "<div id=conatiner_list>";
			echo"<form action=pop_window.php method=post>";
			echo"<div class=divcont4 align=center>".get_string('oldmessages', 'block_shoutbox')."</div>";
						$count_records = mysql_num_rows(mysql_query("select * from {$CFG->prefix}block_shoutbox_shoutbox where block_id=$block_id"));
						
				echo "<div class=paging style=display:none>".print_paging_bar($count_records, $page, $perpage, "pop_window.php?course_id=$block_id&submit=0&perpage=$perpage&orderby=$orderby&ordertype=$order_type&")."</div>";
						
			$context=get_context_instance(CONTEXT_BLOCK, $block_id);
			
			if(has_capability('block/shoutbox:deleteallmessage', $context))
			{ 			
				echo "<div style=height:50px;>";			
				echo"<span class=buttonlist1>&nbsp;<input name=checked type=button value=".get_string('checked', 'block_shoutbox')." onClick=checked_all() ><input name=unchecked type=button value=".get_string('unchecked', 'block_shoutbox')." onClick=unchecked_all()><input name=delete type=submit value=".get_string('delete', 'block_shoutbox')."></span>";						
				echo "<span class=buttonlist2><input name=delete type=button value=".get_string('downloadcsv', 'block_shoutbox')." onClick=download_data('$block_id','$page','$perpage','$orderby')></span>";
				echo "</div>";
			}
			
			$q=get_records_select("block_shoutbox_shoutbox","block_id=$block_id","$orderby $ordertype","*", ($page*$perpage), $perpage);

			global $CFG;

				$i=0;
				if(count($q)==0	)
				{
				echo "No Message";
				}
				else
				{
				foreach($q as $expense)
				{
				 $user_sql="SELECT * FROM {$CFG->prefix}user where id={$expense->user_id} ";
							  if($i%2==0)
							  {
							  $class="divcont5";
							  }
							  else
							  {
							  $class="divcont6";
							  } 
				//$use= mysql_fetch_array(mysql_query($user_sql));
				$use=get_record_sql($user_sql);
				$fullname = fullname($use);
				
				echo"<div class=$class>";
							if(has_capability('block/shoutbox:deleteallmessage', $context))
							{ 
								echo "<span class=spanleft><input name=check_delete[] value=".$expense->id." align=left type=checkbox id=".$expense->id."></span>";
							}
							else
							{
								echo "<span>&nbsp;</span>";
							}
							
							
							echo "<span class=spanimage>".print_user_picture($use->id,$COURSE->id,$use->picture,24,true, true)."</span>";
							echo "<span class=spantext>";
							$remove_br=$expense->data;
							$bodytag = str_replace("<br>", "",$remove_br);
							$bodytag = str_replace("<br>", "",$remove_br);
							$slashes_remove=stripslashes($bodytag);
							echo "<span id=change_message".$expense->id.">".$slashes_remove."</span></span>";
						
			
							$date_explode=$expense->date;
							$date_format=explode(" ",$date_explode);
							
							echo"<span class=spanright> <a href=".$CFG->wwwroot."/user/view.php?id=".$use->id."&course=".$course_id.">".$fullname."</a><br>".$date_format[0].' '.$date_format[1].' '.$date_format[3].' '.$date_format[4]."&nbsp;&nbsp;";
							
							if(has_capability('block/shoutbox:editownmessage', $context))
							{
								if(has_capability('block/shoutbox:editallmessage', $context))
								{  
										echo "<a onClick=edit_shoutbox_message($expense->id) id=editid".$expense->id." class=edit_class >".get_string('edit', 'block_shoutbox')."</a>";
								} 
								
								else {
									   if ($USER->id==$expense->user_id) {
										echo "<a onClick=edit_shoutbox_message($expense->id) id=editid".$expense->id." class=edit_class >".get_string('edit', 'block_shoutbox')."</a>";
									}
								
								}
							}
							
							echo "</span><input name=hidden_data type=hidden value=".$CFG->wwwroot." id=hidden_data ><input name=insertion_block_c_id type=hidden value=".$course_id." id=insertion_block_c_id >";
							
				echo "</div>";
				$i++;
				}}
				
				echo "<input name=blockid type=hidden value=".$block_id." id=blockid_course >";
				echo"</form></div>";
				echo "<div class=paging style=display:none>".print_paging_bar($count_records, $page, $perpage, "pop_window.php?course_id=$block_id&submit=0&perpage=$perpage&orderby=$orderby&ordertype=$order_type&")."</div>";
				?>