

<link href="<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/styles.css" rel="stylesheet" type="text/css" />
<script src='<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/script/xajaxex.js'></script>
<script src='<?php echo $CFG->wwwroot; ?>/blocks/shoutbox/script/ajaxfunction.js'></script>


<?php

   class block_shoutbox extends block_base {

    function init() {
        $this->title = get_string('inserting', 'block_shoutbox');
        $this->version = 20092403004;
    }

    function applicable_formats() {
        return array('all' => true);
    }

	function get_content() {

	     global  $CFG, $COURSE, $USER;


	if ($this->content !== NULL) {
      return $this->content;
    }

    $this->content         =  new stdClass;
    $this->content->text   = '';
    $this->content->footer = '';

  $context=get_context_instance(CONTEXT_BLOCK, $this->instance->id);

  $text='<div class="shoutbox_footer">';

  if(has_capability('block/shoutbox:typemessage', $context))
  {
	 $text.='<textarea name="insertion_data"  id=insertion_data'.$this->instance->id.'  onkeyup="limiter('.$this->instance->id.');" onFocus="empty(this);"     class="textarea_box"> '.get_string('typehere','block_shoutbox').'</textarea> ';
	$ff=$this->config->char_allow;
	if(!isset($ff))
	   {
		$text.="<span class=limits>".get_string('setconfiguration', 'block_shoutbox')."</span>";
		}
	$text.='<div class="lower_display">
	<div class="left_content">'.get_string('charactorleft', 'block_shoutbox').'<span  id=limit'.$this->instance->id.' class="limits">'.$this->config->char_allow.'</span></div>';
	}
		else
		{
			$text.='<div class="lower_display">
			<div class="left_content">&nbsp;</div>';
		}

   if(has_capability('block/shoutbox:showoldmessage', $context))
  	{
			$text.=' <div class="right_content">  <A HREF="javascript:popUp('.$this->instance->id.')">'.get_string('oldmessages', 'block_shoutbox').'</A> </div></div>';
			$text.="</div>";
    }
	else
		{
			$text.=' <div class="right_content">&nbsp; </div></div>';
			$text.="</div>";
		}

   if(has_capability('block/shoutbox:typemessage', $context))
    {
	$text.='<input name="submit" type="submit" value="'.get_string('send','block_shoutbox').'" id='.$this->instance->id.'  onclick="return add_value(this);">';
	}
	$text.='<input name="hidden_data" type="hidden" value='.$CFG->wwwroot.' id="hidden_data" >';
	$text.='<input name="insertion_block_c_id" type="hidden" value='.$COURSE->id.' id="insertion_block_c_id" >';
	$text.='<input name="insertion_block_u_id" type="hidden" value='.$USER->id.' id="insertion_block_u_id" >';
	$text.='<input name="insertion_block_row" type="hidden" value='.$this->config->row.' id=insertion_block_row'.$this->instance->id.'  >';
	$text.='<input name="insertion_block_char" type="hidden" value='.$this->config->char_allow.' id=insertion_block_char'.$this->instance->id.'  >';
	$text.='<input name=insertion_block_noofcharbreak type="hidden" value='.$this->config->noofcharbreak.' id=insertion_block_noofcharbreak'.$this->instance->id.'  >';
	$text.='<input name="insertion_block_instanceid" type="hidden" value='.$this->instance->id.' id="insertion_block_instanceid" >';
	$text.='<br>';

	$text.="<script type='text/javascript' >

	var mod_data_".$this->instance->id."refresh_oAjax=new ExAjaxClass();
	mod_data_".$this->instance->id."refresh_oAjax.AddCallBackHnadler(refresh_recieve_".$this->instance->id.",AjaxResponseType.responseTEXT);


	function auto_refresh".$this->instance->id."(dd)
	{

	var yy=dd;
	setTimeout ('setToBlack".$this->instance->id."('+yy+')',".$this->config->secondrefresh.");
	}


	  function setToBlack".$this->instance->id."(xx)
	{
		 var url=document.getElementById('hidden_data').value;

			var course_id=document.getElementById('insertion_block_c_id').value;
			var user_id=document.getElementById('insertion_block_u_id').value;
			var insertion_block_row='insertion_block_row'+xx;
			 var noofrow=document.getElementById(insertion_block_row).value;
			 uri=url+'/blocks/shoutbox/auto_refresh.php?course_id='+course_id+'&row='+noofrow+'&block_id='+xx+'&';
			 mod_data_".$this->instance->id."refresh_oAjax.sendToServer(AjaxMethodType.GET,uri,'true');
			   setTimeout ('auto_refresh".$this->instance->id."('+xx+')',".$this->config->secondrefresh.");
			   }
         function isNull(val){return(val==null);}
			function refresh_recieve_".$this->instance->id."(retStr,errCode,AStatus)
			{

			if(isNull((retStr)))
			{

			}
			else
			{

				var result=retStr.split('%');

				var list_container='list_container'+result[1];

				document.getElementById(list_container).innerHTML = result[0];
			}


			}
			 function isNull(val){return(val==null);}


			auto_refresh".$this->instance->id."(".$this->instance->id.");
            </script>";

	$sql="SELECT * FROM {$CFG->prefix}block_shoutbox_shoutbox where block_id={$this->instance->id} order by id desc ";

	$users = get_records_sql($sql, 0, $this->config->row);


           $this->content->text .= '<div id=list_container'.$this->instance->id.' >';
		    $this->content->text .= '<div class="list_container">';
              $i=0;

			foreach ($users as $user) {
              $user_sql="SELECT * FROM {$CFG->prefix}user where id={$user->user_id} ";
              if($i%2==0)
			  {
			  $class="divcont3";
			  }
			  else
			  {
			  $class="divcont4";
			  }
              // $use= mysql_fetch_array(mysql_query($user_sql));
             $use=get_record_sql($user_sql);

			 $this->content->text .= '<div class='.$class.'>';
              $this->content->text .='<span style="float:left">'.print_user_picture($use->id,$COURSE->id,$use->picture,24,true, true).'</span>';
             $this->content->text .= '<span style="text-align:justify;">'.nl2br($user->data);
			 $fullname = fullname($use);
              $this->content->text .= '</span><br><span class="divcont2"><a href='.$CFG->wwwroot.'/user/view.php?id='.$use->id.'&course='.$COURSE->id.'>'.$fullname.'  </a></span>';

			  $date=$user->date;
			  $date_format=explode(" ",$date);
			   $this->content->text .= '<span class="divcont1">'.$date_format[0].' '.$date_format[1].' '.$date_format[3].' '.$date_format[4].'</span>';
			  $this->content->text .= '<br>';
			  $this->content->text .= '</div>';
			  $i++;

            }
			 $this->content->text .= '</div>';
			 $this->content->text .= '</div>';
			$this->content->text .= '';
            $this->content->footer = $text;
			 $this->content->footer.='<style>

			.divcont3 {
			background-color:'.$this->config->bgcolor1.';
			}


			.divcont4 {
			background-color:'.$this->config->bgcolor2.';
			}</style>';

	     return $this->content;

       }



		function instance_allow_multiple() {
				return true;
			}
		   function instance_allow_config() {
		  return true;
		}

		function specialization() {
				$this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('inserting', 'block_shoutbox'));
				$this->row = isset($this->config->row) ? format_string($this->config->row) : format_string(get_string('inserting', 'block_shoutbox'));
			}
		function has_config() {
		  return TRUE;
		}
		function config_save($data) {
		  // Default behavior: save all variables as $CFG properties
		  foreach ($data as $name => $value) {
			set_config($name, $value);
		  }
		  return TRUE;
		}

		function preferred_width() {
		  // The preferred value is in pixels
		  return 100;
		}

		}

   // Here's the closing curly bracket for the class definition
?>

