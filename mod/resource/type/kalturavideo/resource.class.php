<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once($CFG->dirroot.'/mod/kaltura/lib.php');

class resource_kalturavideo extends resource_base
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

            $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
            $formatoptions = new object();
            $formatoptions->noclean = true;
            print_simple_box(format_text($resource->summary, FORMAT_MOODLE, $formatoptions, $this->course->id), "center");
           
          	if (has_capability('moodle/course:manageactivities',$context)) //check if admin of this widget
            {            
              echo embed_kaltura($resource->alltext,get_width($entry),get_height($entry),$entry->entry_type, $entry->design, true);
            }
            else
            {
              echo embed_kaltura($resource->alltext,get_width($entry),get_height($entry),KalturaEntryType::MEDIA_CLIP, $entry->design, true);
            }
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

          $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
          $formatoptions = new object();
          $formatoptions->noclean = true;
          print_simple_box(format_text($resource->summary, FORMAT_MOODLE, $formatoptions, $this->course->id), "center");
          
          if (has_capability('moodle/course:manageactivities',$context)) //check if admin of this widget
          {
            echo embed_kaltura($resource->alltext,get_width($entry),get_height($entry),$entry->entry_type, $entry->design, true);
          }
          else
          {
            echo embed_kaltura($resource->alltext,get_width($entry),get_height($entry),KalturaEntryType::MEDIA_CLIP, $entry->design, true);
          }
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
    $entry->entry_type = $_POST['entry_type'];
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
		  $item_id = $_GET['update'];
		  $result = get_record('course_modules','id',$item_id);
      $result = get_record('resource','id',$result->instance);
      $entry = get_record('kaltura_entries','context',"R_" . "$result->id");
      $default_entry = $entry;
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

    $text_video = new HTML_QuickForm_static('video_text',null, '<span id="spanExplain"><table style="width:100%;font-size:9px;"><tr><td width="25%">'.get_string('videotext', 'resource_kalturavideo').'</td><td style="width:40%;padding-left:25px;">'.get_string('videoremixtext', 'resource_kalturavideo').'</td><td width="35%">&nbsp;</td></tr></table></span>');
 //   $text_video_remix = new HTML_QuickForm_static('video_text_remix',null, 'bbb');
    
	  $button = new HTML_QuickForm_input;
	  $button->setName('addvid');
	  $button->setType('button');
	  $button->setValue('Add Video');

	  $button_editable = new HTML_QuickForm_input;
	  $button_editable->setName('addeditvid');
	  $button_editable->setType('button');
	  $button_editable->setValue('Add Editable Video');

	  $button_replace = new HTML_QuickForm_input;
	  $button_replace->setName('replacevid');
	  $button_replace->setType('button');
	  $button_replace->setValue('Replace Video');

	  $button_preview = new HTML_QuickForm_input;
	  $button_preview->setName('previewvid');
	  $button_preview->setType('button');
	  $button_preview->setValue('Preview Video');

	  $button_preview_edit = new HTML_QuickForm_input;
	  $button_preview_edit->setName('previeweditvid');
	  $button_preview_edit->setType('button');
	  $button_preview_edit->setValue('Preview & Edit Video');

	  $videolabel = get_string('addvideo', 'resource_kalturavideo');
	  $videoeditablelabel = get_string('editablevideo', 'resource_kalturavideo');
	  $replacelabel = get_string('replacevideo', 'resource_kalturavideo');
	  $previewlabel = get_string('previewvideo', 'resource_kalturavideo');
	  $previeweditlabel = get_string('previeweditvideo', 'resource_kalturavideo');
  		
	  $cw_url = $CFG->wwwroot.'/mod/kaltura/kcw.php?';
	  $cw_url_init = $cw_url;
    $edit_url = $CFG->wwwroot.'/mod/kaltura/keditor.php?'; 
    $edit_url_init = $edit_url;
    $preview_url = $CFG->wwwroot.'/mod/kaltura/kpreview.php?'; 
    $preview_url_init = $preview_url;
    if (!empty($entry))
    {
      $cw_url_init .= 'id=' . $entry->id;
      $preview_url_init .= 'entry_id=' . $entry->entry_id . '&design=' . $entry->design . '&width=' . get_width($entry) . '&dimensions=' . $entry->dimensions;
      $edit_url_init .= 'entry_id=' . $entry->entry_id;
    }
    
	  $button_attributes = array(
		  'type' => 'button',
		  'onclick' => 'set_entry_type('.KalturaEntryType::MEDIA_CLIP.');kalturaInitModalBox(\''. $cw_url_init . '&upload_type=video' .'\', {width:760, height:422});',
		  'id' => 'id_addvideo',
		  'value' => $videolabel,
      'style' => (empty($entry) ? 'display:inline' : 'display:none'),    
	  );
  

  	$button_attributes_editable = array(
		'type' => 'button',
		'onclick' => 'set_entry_type('.KalturaEntryType::MIX.');kalturaInitModalBox(\''. $cw_url_init . '&upload_type=mix' .'\', {width:760, height:422});',
		'id' => 'id_addeditablevideo',
		'value' => $videoeditablelabel,
    'style' => (empty($entry) ? 'display:inline;margin-left:90px;' : 'display:none'),    
	);

  	$button_attributes_replace = array(
		'type' => 'button',
		'onclick' => 'kalturaInitModalBox(\''. $cw_url_init . (empty($entry) ? '' : ($entry->entry_type == KalturaEntryType::MEDIA_CLIP ? '&upload_type=video': '&upload_type=mix')) . '\', {width:760, height:422});',
		'id' => 'id_replace',
		'value' => $replacelabel,
    'style' => (empty($entry) ? 'display:none' : 'display:inline'),    
	);

  	$button_attributes_preview = array(
		'type' => 'button',
		'onclick' => 'kalturaInitModalBox(\''. $preview_url_init .'\', ' . (empty($entry) ? '{width:400, height:382}' : ('{width:' . get_width($entry) . ', height:' . (get_height($entry)+50) . '}')) . ');',
		'id' => 'id_preview',
		'value' => $previewlabel,
    'style' => ((empty($entry) || $entry->entry_type != KalturaEntryType::MEDIA_CLIP) ? 'display:none' : 'display:inline'),    
	);

  	$button_attributes_preview_edit = array(
		'type' => 'button',
		'onclick' => 'kalturaInitModalBox(\''. $edit_url_init .'\', {width:890, height:546});',
		'id' => 'id_preview_edit',
		'value' => $previeweditlabel,
    'style' => ((empty($entry) || $entry->entry_type != KalturaEntryType::MIX) ? 'display:none' : 'display:inline'),    
	);
    
  $resource = $this->resource;        


    $thumbnail = "";    
	if (isset($_GET['update'])) 
  {
      if(!empty($entry))
      {
      	$thumbnail = '<img id="id_thumb" src="'. KalturaHelpers::getThumbnailUrl(null, $entry->entry_id, 140, 105) .'" />';
	//	    $mform->addElement('static', 'video_thumb', get_string('video', 'resource_kalturavideo'), $thumbnail);
      }
   }
   
	$button->setAttributes($button_attributes);
	$button_editable->setAttributes($button_attributes_editable);
	$button_replace->setAttributes($button_attributes_replace);
	$button_preview->setAttributes($button_attributes_preview);
	$button_preview_edit->setAttributes($button_attributes_preview_edit);

  $objs = array();
  $objs[] = &$button;
  $objs[] = &$button_editable;
  $objs[] = &$button_replace;
  $objs[] = &$button_preview;
  $objs[] = &$button_preview_edit;

  
  $text_objs = array();
  $text_objs[]=$text_video;
  
	$divWait = '<div style="border:1px solid #bcbab4;background-color:#f5f1e9;width:140px;height:105px;float:left;text-align:center;;font-size:85%;display:' . (empty($thumbnail) ? 'none' : 'inline') . '" id="divWait">' . $thumbnail .'</div>
  <script type="text/javascript">
   function set_entry_type(type)
   {
      document.getElementById("id_entry_type").value = type;
   }
  
   function get_height()
   {
      if (get_field("id_dimensions") == "' . KalturaAspectRatioType::ASPECT_4_3 .'")
      {
        switch(get_field("id_size"))
        {
          case "' . KalturaPlayerSize::LARGE . '":
            return 445;
            break;
          case "' . KalturaPlayerSize::SMALL . '":
            return 340;
            break;
          case "' . KalturaPlayerSize::CUSTOM . '":
            return parseInt(get_field("id_custom_width"))*3/4 + 65 + 80;
            break;
          default:
            return 445;
           break;
        }
      }
      else
      {
        switch(get_field("id_size"))
        {
          case "' . KalturaPlayerSize::LARGE . '":
            return 370;
           break;
          case "' . KalturaPlayerSize::SMALL . '":
            return 291;
            break;
          case "' . KalturaPlayerSize::CUSTOM . '":
            return parseInt(get_field("id_custom_width"))*9/16 + 65 + 80;
            break;
          default:
            return 370;
            break;
        }
      
      }
   
   }
  
   function get_width()
   {
    switch(get_field("id_size"))
    {
      case "' . KalturaPlayerSize::LARGE . '":
        return 450;
        break;
      case "' . KalturaPlayerSize::SMALL . '":
        return 310;
        break;
      case "' . KalturaPlayerSize::CUSTOM . '":
        return parseInt(get_field("id_custom_width")) + 50;
        break;
      default:
        return 450;
        break;
    }   
   }
      
   function do_on_wait()
   {
      var entryId = document.getElementById("id_alltext").value;
      document.getElementById("id_addvideo").style.display="none";
      document.getElementById("id_addeditablevideo").style.display="none";
      document.getElementById("id_replace").style.display="inline";
      if (document.getElementById("spanExplain") != null)
      {
        document.getElementById("spanExplain").style.display = "none";
      }
      
      if (document.getElementById("id_entry_type").value == ' . KalturaEntryType::MEDIA_CLIP . ')
      {
        var design = get_field("id_design");
        var width = get_width();
        var dimensions = get_field("id_dimensions");
        document.getElementById("id_preview").style.display="inline";
//        document.getElementById("id_preview").onclick=new Function("kalturaInitModalBox(\'' . $preview_url . 'entry_id=" + entryId + "\', {width:400, height:382})");
        document.getElementById("id_preview").onclick=new Function("kalturaInitModalBox(\'' . $preview_url . 'entry_id=" + entryId + "&design=" + design + "&width=" + width + "&dimensions=" + dimensions + "\', {width:get_width(), height:get_height()})"); //width:get_width()+10
     }
      else
      {
        document.getElementById("id_preview_edit").style.display="inline";
        document.getElementById("id_preview_edit").onclick=new Function("kalturaInitModalBox(\'' . $edit_url . 'entry_id=" + entryId + "\', {width:890, height:546})");
        document.getElementById("id_replace").onclick=new Function("kalturaInitModalBox(\'' . $cw_url . '&upload_type=mix\', {width:760, height:422})");
      }
   }
   </script>';
  
	$mform->addElement('static', 'divWait', '',get_wait_image("divWait","id_alltext"));
	$mform->addElement('static', 'please_wait', (empty($entry) ? '' : get_string('video', 'resource_kalturavideo')), $divWait);
	$mform->addElement('group', 'videogroup', (empty($entry) ? get_string('video', 'resource_kalturavideo') : ''), $objs);
  if (!isset($_GET['update']))
  {
    $mform->addElement('group','videotextgroup', '',$text_objs);
  }
  
  $mform->addElement('header', 'displaysettings', get_string('display', 'resource'));
     return;
 
  } 
}
?>