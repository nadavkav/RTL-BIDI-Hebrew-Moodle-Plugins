<?php

$regValues = array();
$registerdNow = false;
//We use this classes for registration purposes (so no data will be written
class kaltura_admin_setting_configtext extends admin_setting_configtext
{
    var $mandatory;
    
    function kaltura_admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $mandatory=true, $paramtype=PARAM_RAW, $size=null)
    {
      $this->mandatory=$mandatory;
      parent::admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype, $size);
    }
    
    function get_setting() 
    {
        global $regValues;
        
        return (isset($regValues[$this->name]) ? $regValues[$this->name] : '');
    }
     
    function write_setting($data) {
        global $regValues;
    
        if ($this->mandatory && !$this->validate($data))
        {
          return get_string('registrationmandatory','kaltura');
        }
        else
        {
          $regValues[$this->name] = $data;
          return '';
        }
    }

    /**
     * Validate data before storage
     * @param string data
     * @return mixed true if ok string if error found
     */
    function validate($data) {
      return ($data == '' ? false : true);
    }}

class kaltura_admin_setting_configselet extends admin_setting_configselect
{

    function get_setting() 
    {
        global $regValues;
        
        return (isset($regValues[$this->name]) ? $regValues[$this->name] : '');
    }

    function write_setting($data) {
        global $regValues;
        if (!$this->validate($data))
        {
          return get_string('registrationmandatory','kaltura');
        }
        else
        {
           $regValues[$this->name] = $data;
           return '';
        }
    }

    /**
     * Validate data before storage
     * @param string data
     * @return mixed true if ok string if error found
     */
    function validate($data) {
      return ($data == 'Please select...' ? false : true);
    }}

class kaltura_admin_setting_configmultiselect extends admin_setting_configmultiselect
{

    function get_setting() 
    {
        global $regValues;
        
        return (isset($regValues[$this->name]) ? $regValues[$this->name] : '');
    }

    function write_setting($data) {
        global $regValues;
        if (!$this->validate($data))
        {
          return get_string('registrationmandatory','kaltura');
        }
        else
        {
           $regValues[$this->name] = $data;
           return '';
        }
    }

    /**
     * Validate data before storage
     * @param string data
     * @return mixed true if ok string if error found
     */
    function validate($data) {
      return (isset($data[0]) ? true : false);
    }}

class kaltura_admin_setting_configtextarea extends admin_setting_configtextarea
{

    function get_setting() 
    {
        global $regValues;
        
        return (isset($regValues[$this->name]) ? $regValues[$this->name] : '');
    }

    function write_setting($data) {
         global $regValues;
       if (!$this->validate($data))
        {
          return get_string('registrationmandatory','kaltura');
        }
        else
        {
          $regValues[$this->name] = $data;
          return '';
        }
    }

    /**
     * Validate data before storage
     * @param string data
     * @return mixed true if ok string if error found
     */
    function validate($data) {
      return ($data == '' ? false : true);
    }}

class kaltura_admin_setting_configradio extends admin_setting {

    var $choices;
    /**
     * config text contructor
     * @param string $name of setting
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string $defaultsetting
     * @param mixed $paramtype int means PARAM_XXX type, string is a allowed format in regex
     * @param int $size default field size
     */
    function kaltura_admin_setting_configradio($name, $visiblename, $description, $defaultsetting, $choices) {
        $this->choices = $choices;
        parent::admin_setting($name, $visiblename, $description, $defaultsetting);
    }

    function get_setting() 
    {
        global $regValues;
        
        return (isset($regValues[$this->name]) ? $regValues[$this->name] : '');
    }

    function write_setting($data) {
        global $regValues;
        if (!$this->validate($data))
        {
          return get_string('registrationmandatory','kaltura');
        }
        else
        {
          $regValues[$this->name] = $data;
          return '';
        }
    }

    /**
     * Validate data before storage
     * @param string data
     * @return mixed true if ok string if error found
     */
    function validate($data) {
      return ($data == '' ? false : true);
    }

    function output_html($data, $query='') {
//       $default = $this->get_defaultsetting();
       $default = NULL;
       $preVal = $this->get_setting();

        $inner_text = '<input type="hidden" id="'.$this->get_id() .'" name="'.$this->get_full_name().'" value="'. $preVal .'" />';
        foreach($this->choices as $key=>$val)
        {
          $inner_text .= '<input type="radio" id="'.$this->get_id() . $key.'" name="'.$this->get_full_name() .'_radio" value="' . $key . '" ' .
                           ($preVal == $key ? ' checked="checked" ': ''). 'onclick="document.getElementById(\''.$this->get_id().'\').value=\''.$key.'\';" />' . $val . '<br />';
        }
        return format_admin_setting($this, $this->visiblename,
                '<div class="form-text defaultsnext">' . $inner_text .'</div>',
                $this->description, true, '', $default, $query);
    }
}

function get_option_key($key)
{
  $all_keys = array();
  $all_keys['registerdescself-opt1-key'] = 'Please select...';
  $all_keys['registerdescself-opt2-key'] = 'Integrator/Web developer';
  $all_keys['registerdescself-opt3-key'] = 'Ad Agency';
  $all_keys['registerdescself-opt4-key'] = 'Kaltura Plugin/Extension/Module Distributor';
  $all_keys['registerdescself-opt5-key'] = 'Social Network';
  $all_keys['registerdescself-opt6-key'] = 'Personal Site';
  $all_keys['registerdescself-opt7-key'] = 'Corporate Site';
  $all_keys['registerdescself-opt8-key'] = 'E-Commerce';
  $all_keys['registerdescself-opt9-key'] = 'E-Learning';
  $all_keys['registerdescself-opt10-key'] = 'Media Company/ Producer';
  $all_keys['registerdescself-opt11-key'] = 'Other';  
  $all_keys['registerebcontent-opt1-key'] = 'Arts & Literature';
  $all_keys['registerebcontent-opt2-key'] = 'Automotive';
  $all_keys['registerebcontent-opt3-key'] = 'Business';
  $all_keys['registerebcontent-opt4-key'] = 'Comedy';
  $all_keys['registerebcontent-opt5-key'] = 'Education';
  $all_keys['registerebcontent-opt6-key'] = 'Entertainment';
  $all_keys['registerebcontent-opt7-key'] = 'Film & Animation';
  $all_keys['registerebcontent-opt8-key'] = 'Gaming';
  $all_keys['registerebcontent-opt9-key'] = 'Howto & Style';
  $all_keys['registerebcontent-opt10-key'] = 'Lifestyle';
  $all_keys['registerebcontent-opt11-key'] = 'Men';
  $all_keys['registerebcontent-opt12-key'] = 'Music';
  $all_keys['registerebcontent-opt13-key'] = 'News & Politics';
  $all_keys['registerebcontent-opt14-key'] = 'Nonprofits & Activism';
  $all_keys['registerebcontent-opt15-key'] = 'People & Blogs';
  $all_keys['registerebcontent-opt16-key'] = 'Pets & Animals';
  $all_keys['registerebcontent-opt17-key'] = 'Science & Technology';
  $all_keys['registerebcontent-opt18-key'] = 'Sports';
  $all_keys['registerebcontent-opt19-key'] = 'Travel & Events';
  $all_keys['registerebcontent-opt20-key'] = 'Women';
  $all_keys['registerebcontent-opt21-key'] = 'N/A';
  $all_keys['registerebcontent-opt21-key'] = 'N/A';
  $all_keys['registeradult-opt1-key'] = 'Yes';
  $all_keys['registeradult-opt2-key'] = 'No';
  
  return $all_keys[$key];
}

function print_textfield_labeled ($name, $value, $label, $alt = '',$size=50,$maxlength=0, $return=false) {

    static $idcounter = 0;

    if (empty($name)) {
        $name = 'unnamed';
    }

    if (empty($alt)) {
        $alt = 'textfield';
    }

    if (!empty($maxlength)) {
        $maxlength = ' maxlength="'.$maxlength.'" ';
    }

    $htmlid = 'auto-tf'.sprintf('%04d', ++$idcounter);
    $output  = '<span class="textfield '.$name."\">";
    $output .= ' <label for="'.$htmlid.'">'.$label.'</label>';
    $output .= '<input name="'.$name.'" id="'.$htmlid.'" type="text" value="'.$value.'" size="'.$size.'" '.$maxlength.' alt="'.$alt.'" />';

    $output .= '</span>'."\n";

    if (empty($return)) {
        echo $output;
    } else {
        return $output;
    }

}
?>