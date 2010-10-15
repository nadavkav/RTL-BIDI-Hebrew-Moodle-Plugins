<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once($CFG->dirroot.'/mod/kaltura/lib.php');

class resource_kalturaswfdoc extends resource_base
{     
	function resource_kalturavideo($cmid=0)    
	{        
		parent::resource_base($cmid);    
	}     

	function display()    
	{        
    global $CFG;

    $formatoptions = new object();
    $formatoptions->noclean = true;

    /// Are we displaying the course blocks?
    if ($this->resource->options == 'showblocks') {

        parent::display_course_blocks_start();

       $entry = get_record('kaltura_entries','context',"R_" . "$this->resource->id");
       if (trim(strip_tags($this->resource->alltext))) 
        {
          echo $entry->title;
	        $player_url = $CFG->wwwroot.'/mod/kaltura/kswfdoc.php?context='.$this->course->id.'&entry_id='.$resource->alltext;
          $formatoptions = new object();
          $formatoptions->noclean = true;
          print_simple_box(format_text($resource->summary, FORMAT_MOODLE, $formatoptions, $this->course->id), "center");
          
	        if ($resource->alltext)
	        {
	            echo '<input style="margin-top:20px;" type="button" value="View video presentation" onclick="kalturaInitModalBox(\''. $player_url .'\', {width:780, height:400});">';
	        }
            
//            echo embed_kaltura($resource->alltext,get_width($entry),get_height($entry),$entry->entry_type);
        }

        parent::display_course_blocks_end();

    } else {

        /// Set up generic stuff first, including checking for access
        parent::display();

        /// Set up some shorthand variables
        $cm = $this->cm;
        $course = $this->course;
        $resource = $this->resource;

        $entry = get_record('kaltura_entries','context',"R_" . "$resource->id");

        $pagetitle = strip_tags($course->shortname.': '.format_string($resource->name));
        $inpopup = optional_param('inpopup', '', PARAM_BOOL);


        add_to_log($course->id, "resource", "view", "view.php?id={$cm->id}", $resource->id, $cm->id);
        $navigation = build_navigation($this->navlinks, $cm);

        print_header($pagetitle, $course->fullname, $navigation,
                "", "", true, update_module_button($cm->id, $course->id, $this->strresource),
                navmenu($course, $cm));

        if (trim(strip_tags($this->resource->alltext))) 
        {
          echo $entry->title;
        }
        
        $formatoptions = new object();
        $formatoptions->noclean = true;
        print_simple_box(format_text($resource->summary, FORMAT_MOODLE, $formatoptions, $this->course->id), "center");

        if (trim(strip_tags($this->resource->alltext))) 
        {
	        $player_url = $CFG->wwwroot.'/mod/kaltura/kswfdoc.php?context='.$this->course->id.'&entry_id='.$resource->alltext;
	        if ($resource->alltext)
	        {
	            echo '<input type="button" style="margin-top:20px;"  value="View video presentation" onclick="kalturaInitModalBox(\''. $player_url .'\', {width:780, height:400});">';
	        }
          
//          echo embed_kaltura($resource->alltext,get_width($entry),get_height($entry),$entry->entry_type);
        }
        
 /*       print_simple_box(format_text($resource->alltext, $resource->reference, $formatoptions, $course->id),
                "center", "", "", "20");
*/
        $strlastmodified = get_string("lastmodified");
        echo "<div class=\"modified\">$strlastmodified: ".userdate($resource->timemodified)."</div>";

        print_footer($course);

    }
	}     
	
	function add_instance($resource)    
	{      
		$result = parent::add_instance($resource);    
    $entry = new kaltura_entry;
    $entry->entry_id = $resource->alltext;
    $entry->dimensions = $_POST['dimensions'];
    $entry->size = $_POST['size'];
    $entry->custom_width = $_POST['custom_width'];
    $entry->design = $_POST['design'];
    $entry->title = $_POST['title'];
    $entry->context = "R_" . $result;
    $entry->entry_type = KalturaEntryType::DOCUMENT;
    $entry->media_type = KalturaMediaType::VIDEO;
   
    $entry->id = insert_record('kaltura_entries', $entry);
    
    //for backup we need a to tell moolde that kaltura is connected to this course
    $mod = new object();
    $mod->course = $this->course->id;
    $mod->module = get_field('modules', 'id', 'name', 'kaltura');
    $mod->instance = $entry->id;
    $mod->section = 0;
    add_course_module($mod);   
    return $result;
	}     
	
	function update_instance($resource)    
	{        
    $entry = get_record('kaltura_entries','context',"R_" . "$resource->instance");
    $entry->entry_id = $resource->alltext;
    $entry->dimensions = $_POST['dimensions'];
    $entry->size = $_POST['size'];
    $entry->custom_width = $_POST['custom_width'];
    $entry->design = $_POST['design'];
    $entry->title = $_POST['title'];
    
    update_record('kaltura_entries', $entry);
  
		$result = parent::update_instance($resource);    
    return $result;
	}     
	
	function delete_instance($resource)    
	{  
    $entry = get_record('kaltura_entries','context',"R_" . "$resource->id");
    $mod = get_field('modules', 'id', 'name', 'kaltura');
    
    delete_records('kaltura_entries','context',"R_" . "$resource->id");
    delete_records('course_modules', 'module',$mod,'instance',$entry->id);
    
		return parent::delete_instance($resource);    
	}     
	
	function setup_elements(&$mform)    
	{    
    global $CFG, $RESOURCE_WINDOW_OPTIONS;
    $isNew = true;

    if (KalturaHelpers::getPlatformKey("partner_id", "none") == "none")
    {
//        $basic = get_string('needreg', 'kaltura');
//        $str = str_replace("##SERVER##", $CFG->wwwroot . "/admin/settings.php?section=modsettingkaltura", $basic);
//        $mform->addElement('static', 'pleasereg',$str, '');
        redirect($CFG->wwwroot . "/admin/module.php?module=kaltura");
        die();
//        return;
    }

	  if(isset($_GET['update']))
    {	
      $isNew = false;
		  $item_id = $_GET['update'];
		  $result = get_record('course_modules','id',$item_id);
      $result = get_record('resource','id',$result->instance);
      $entry = get_record('kaltura_entries','context',"R_" . "$result->id");
      $default_entry = $entry;
     
	    $url = $CFG->wwwroot .'/mod/kaltura/kswfdoc.php?entry_id='.$entry->entry_id . '&context=' . $this->course->id;
	    $editSyncButton = '<button onclick="kalturaInitModalBox(\''.$url.'\', {width:780, height:400});return false;">' . get_string('editsyncpoints','kaltura') . '</button>';
	    
	    $mform->addElement('static', 'edit_sync', get_string('editsyncpoints','kaltura'), $editSyncButton);    
	  }
    else
    {
      $last_entry_id = get_field('kaltura_entries','max(id)', 'id', 'id');
      if (!empty($last_entry_id))
      {
        $default_entry = get_record('kaltura_entries','id',"$last_entry_id");
        $default_entry->title = "";
      }
      else
      {
        $default_entry = new kaltura_entry;
      }
    }

	  $hidden_alltext = new HTML_QuickForm_hidden('alltext', $default_entry->dimensions, array('id' => 'id_alltext'));
	  $mform->addElement($hidden_alltext);

    $hidden_popup = new HTML_QuickForm_hidden('popup', '', array('id' => 'id_popup'));
	  $mform->addElement($hidden_popup);


	  $hidden_dimensions = new HTML_QuickForm_hidden('dimensions', $default_entry->dimensions, array('id' => 'id_dimensions'));
	  $mform->addElement($hidden_dimensions);

	  $hidden_size = new HTML_QuickForm_hidden('size', $default_entry->size, array('id' => 'id_size'));
	  $mform->addElement($hidden_size);

	  $hidden_custom_width = new HTML_QuickForm_hidden('custom_width', $default_entry->custom_width, array('id' => 'id_custom_width'));
	  $mform->addElement($hidden_custom_width);

	  $hidden_design = new HTML_QuickForm_hidden('design', $default_entry->design, array('id' => 'id_design'));
	  $mform->addElement($hidden_design);

	  $hidden_title = new HTML_QuickForm_hidden('title', $default_entry->title, array('id' => 'id_title'));
	  $mform->addElement($hidden_title);

	  $hidden_entry_type = new HTML_QuickForm_hidden('entry_type', $default_entry->entry_type, array('id' => 'id_entry_type'));
	  $mform->addElement($hidden_entry_type);

		$hidden_ppt = new HTML_QuickForm_hidden('ppt_input', $ppt_id, array('id' => 'id_ppt_input'));
		$mform->addElement($hidden_ppt);
		
		$hidden_video = new HTML_QuickForm_hidden('video_input', $video_id, array('id' => 'id_video_input'));
		$mform->addElement($hidden_video);

		$hidden_ppt_dnld = new HTML_QuickForm_hidden('ppt_dnld_url', $dnld_url, array('id' => 'id_ppt_dnld_url'));
		$mform->addElement($hidden_ppt_dnld);

	  $cw_url = $CFG->wwwroot.'/mod/kaltura/kcw.php?mod=ppt_resource';

    $resource = $this->resource;        
	  $kaltura_client = KalturaHelpers::getKalturaClient();

    $thumbnail = "";
    $ppt_id = $video_id = $dnld_url = '';

		$vid_thumb = '';
		$has_ppt = $has_video = '0';

	  if($isNew)
    {
      $uploader = '<div id="swfdoc_section">
      <div style="border:1px solid #bcbab4;background-color:#f5f1e9;width:140px;height:105px;float:left;margin-right:80px;text-align:center;font-size:85%" id="thumb_video_holder">
      '.$vid_thumb.'&nbsp;</div>
      <div style="border:1px solid #bcbab4;background-color:#f5f1e9;width:140px;height:105px;float:left;text-align:center;font-size:85%" id="thumb_doc_holder">'.$ppt_thumb.'&nbsp;</div><br/>
      <div style="width:140px;float:left;margin-right:80px;text-align:center;margin-top:10px;">
	      <input type="button" id="btn_selectvideo" value="' . get_string("selectvideo","resource_kalturaswfdoc") . '" onclick="kalturaInitModalBox(\''. $cw_url .'\', {width:760, height:422});return false;">
      </div>

      <div id="flashContainer" style="width:140px;float:left;text-align:center;margin-top:10px;">
      <script>	
	      pptIdHolder = document.getElementById("id_ppt_input");
	      pptThumbHolder = document.getElementById("thumb_doc_holder");
	      videoIdHolder = document.getElementById("id_video_input");
	      videoThumbHolder = document.getElementById("thumb_video_holder");
	      pptDnldUrlHolder = document.getElementById("id_ppt_dnld_url");
        
	      var has_ppt = '.$has_ppt.';
	      var has_video = '.$has_video.';
      	
	      $("document").ready(function(){ if(has_ppt && has_video) document.getElementById("sync_btn").disabled = false; });
      	
	      txt_document = "<br/>The Document is now being converted.<br/><br/><a href=\"javascript:check_ready(\'ppt\')\">Click here</a> to check if conversion is done";
	      function check_ready(theType){
		      if (theType == "ppt") {
			      theId = pptIdHolder.value;
			      theThumb = pptThumbHolder;
            theUrl = pptDnldUrlHolder.value;
		      }
		      var ksoa = new SWFObject("'.$CFG->wwwroot.'/mod/kaltura/images/Pleasewait.swf", "kwait", "140", "105", "9", "#ffffff");
		      ksoa.addParam("allowScriptAccess", "always");
		      ksoa.addParam("allowFullScreen", "TRUE");
		      ksoa.addParam("allowNetworking", "all");
		      ksoa.addParam("wmode","transparent");
		      if(ksoa.installedVer.major >= 9) {
			      ksoa.write("thumb_doc_holder");
		      }
      		
		      $.ajax({ 
		        type: "POST", 
		        url: "'.$CFG->wwwroot.'/mod/kaltura/kcheck_status.php", 
		        data: "type=ppt&downloadUrl="+theUrl, 
		        success: function(msg){ 
			      if (msg == "200") {
				      if (theType != "ppt") { 
					      theThumb.innerHTML = "<img src=\"'.$kaltura_cdn_url.'/p/'.$CFG->kaltura_partner_id.'/sp/'.$CFG->kaltura_subp_id.'/thumbnail/entry_id/"+theId+"/width/140/height/105/type/3/bgcolor/ffffff\">";
					      has_video = 1;
					      if (has_ppt) { document.getElementById("sync_btn").disabled = false; }
				      } else {
					      theThumb.innerHTML = "<img src=\"'.$CFG->wwwroot.'/mod/kaltura/images/V_ico.png\" style=\"margin:12px;\">";
					      has_ppt = 1;
					      if (has_video) { document.getElementById("sync_btn").disabled = false; }
				      }
      					
			      } else {
				      document.getElementById("thumb_doc_holder").innerHTML = txt_document;
			      } 
		        } 
		      });

	      }
	      var has_swfdoc = false;
              function set_has_swfdoc(val)
              {
                  has_swfdoc = val;
              }	
	      function create_swfdoc(){
                      if (has_swfdoc)
                      {
                          entry_id = document.getElementById("id_alltext").value;
                          url = "'. $CFG->wwwroot .'/mod/kaltura/kswfdoc.php?entry_id=" + entry_id + "&context=' . $this->course->id .'";
                          kalturaInitModalBox(url, {width:780, height:400});		
                      } else
                      {  	
		          $.ajax({ 
			          type: "POST", 
			          url: "'.$CFG->wwwroot.'/mod/kaltura/kcreate.php", 
			          data: "action=swfdoc&ppt=" + document.getElementById("id_ppt_input").value + "&video=" + document.getElementById("id_video_input").value + "&name=" + document.getElementById("id_name").value + "&downloadUrl="+pptDnldUrlHolder.value,
			          success: function(entry_id){ 
				          if (entry_id){
					          set_has_swfdoc(true);
					          document.getElementById("id_alltext").value = entry_id;
					          url = "'. $CFG->wwwroot .'/mod/kaltura/kswfdoc.php?entry_id=" + entry_id + "&context=' . $this->course->id . '";
					          kalturaInitModalBox(url, {width:780, height:400});
				          }
			          }
		          });
		      }
	      }
      		
	      function user_selected()
	      {
		      document.getElementById("uploader").upload();
	      }
      	
	      function uploaded()
	      {
        
	        document.getElementById("uploader").addEntries();
	      }
      	
	      function uploading(){
		      has_ppt = 0;

            var ksoa = new SWFObject("'.$CFG->wwwroot.'/mod/kaltura/images/Pleasewait.swf", "kwait", "140", "105", "9", "#ffffff");
            ksoa.addParam("allowScriptAccess", "always");
            ksoa.addParam("allowFullScreen", "TRUE");
            ksoa.addParam("allowNetworking", "all");
            ksoa.addParam("wmode","transparent");
            if(ksoa.installedVer.major >= 9) {
              ksoa.write("thumb_doc_holder");
            }

	      }
      	
	      function entries_added(obj)
	      {
		      document.getElementById("thumb_doc_holder").innerHTML = txt_document;
		      myobj = obj[0];
		      document.getElementById("id_ppt_input").value = myobj.entryId;

	        $.ajax({ 
			      type: "POST", 
			      url: "'.$CFG->wwwroot.'/mod/kaltura/kcreate.php", 
			      data: "action=ppt&ppt=" + document.getElementById("id_ppt_input").value,
            success: function(url){
                if( url.substring(0,2) == "y:")
                {
                  pptDnldUrlHolder.value = url.substring(2);
                }
            }
		      });
	          document.getElementById("uploader").removeFiles(0,0);
      	  
	      }
      	
	      delegate = { selectHandler: user_selected, progressHandler: uploading, allUploadsCompleteHandler: uploaded, entriesAddedHandler: entries_added };
      	
      	
      	
      </script>
          <span><input type="button" id="btn_uploaddoc" value="'. get_string("uploaddocument","resource_kalturaswfdoc") . '.">
          <span style="border: 0px solid black; position: relative; top: -20px; width: 110px;" id="divKalturaKupload">
          
          <script type="text/javascript">
            var kso = new SWFObject("'. $kaltura_client->getConfig()->serviceUrl .'/kupload/ui_conf_id/1002613", "uploader", "110", "25", "9", "#ffffff");
            kso.addParam("flashVars", "ks='.$kaltura_client->getKs().'&uid='.$USER->id.'&partnerId='.$CFG->kaltura_partner_id.'&subPId='.$CFG->kaltura_subp_id.'&entryId=-2&conversionProfile=5&maxUploads=10&maxFileSize=128&maxTotalSize=200&uiConfId=1002613&jsDelegate=delegate");
            kso.addParam("allowScriptAccess", "always");
            kso.addParam("allowFullScreen", "TRUE");
            kso.addParam("allowNetworking", "all");
            kso.addParam("wmode","transparent");
            if(kso.installedVer.major >= 9) {
              kso.write("divKalturaKupload");
            } else {
              document.getElementById("divKalturaKupload").innerHTML = "Flash player version 9 and above is required. <a href=\"http://get.adobe.com/flashplayer/\">Upgrade your flash version</a>";
            }
            
          function do_on_wait()
          {
            has_video = 1;
            if(has_ppt && has_video) document.getElementById("sync_btn").disabled = false;
          }

          </script>
	      </span></span>
      </div>
      <script>
      function save_sync() {
      
          create_swfdoc();
          document.getElementById("btn_uploaddoc").disabled = true;
          document.getElementById("btn_selectvideo").disabled = true;
          document.getElementById("divKalturaKupload").innerHTML = "";
      }
      </script>
      <div style="clear:both;text-align:center;width:370px;padding-top:20px;margin:0 auto;margin-left:-10px">' .get_string('syncdescription','resource_kalturaswfdoc') . '</div>
      <div style="clear:both;text-align:center;width:370px;padding-top:20px"><input type="button" id="sync_btn" onclick="save_sync()" value="'.get_string('syncpoints','kaltura').'" DISABLED></div>

      </div>';
	      $mform->addElement('static', 'divWait', '',get_wait_image("thumb_video_holder","id_video_input"));

	      $mform->addElement('static', 'ppt_thing', '', $uploader);
    }
    $mform->addElement('header', 'displaysettings', get_string('display', 'resource'));
  
  } 
}
?>