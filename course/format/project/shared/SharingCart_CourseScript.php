<?php
/**
 * SharingCart_CourseScript
 */

global $CFG;

require_once $CFG->libdir.'/ajax/ajaxlib.php';

class SharingCart_CourseScript
{
	/**
	 * Append the script to course footer
	 * 
	 * - It generates the HTML to print if course AJAX is turned off,
	 *   otherwise, puts the script to buffer and generates no string.
	 * 
	 * @param string  $script  JavaScript code
	 * @param boolean $return  false: echo / true: return
	 */
	public function appendFooter($script, $return = false)
	{
		global $COURSE;
		
		if (empty($COURSE->javascriptportal)) {
			// AJAX OFF
			$html = '
<script type="text/javascript">//<![CDATA[
'.$script.'
//]]></script>
';
		} else {
			// AJAX ON
			if (!method_exists($COURSE->javascriptportal, 'add_script')) {
				// replace the 'jsportal' with overridden class
				$COURSE->javascriptportal = new SharingCart_CourseScript_jsportal();
			}
			$COURSE->javascriptportal->add_script($script);
			
			$html = '';
		}
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
	}
	
	/**
	 * Import external script file
	 * 
	 * @param string  $path    JavaScript file path
	 * @param boolean $return  false: echo / true: return
	 */
	public function import($path, $return = false)
	{
		global $CFG;
		
		if (empty($this->included_scripts[$path])) {
			$html = '<script type="text/javascript" src="'.$path.'"></script>';
			$this->included_scripts[$path] = true;
		} else {
			$html = '';
		}
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
	}
	protected $included_scripts = array();
}

/**
 * Override the 'jsportal' to insert the script just after AJAX course construction
 */
class SharingCart_CourseScript_jsportal extends jsportal
{
	public function print_javascript($course_id, $return = false)
	{
		$output = parent::print_javascript($course_id, true) . '
<script type="text/javascript">//<![CDATA[
'.implode('
', $this->scripts).'
//]]></script>
';
		if ($return) {
			return $output;
		} else {
			echo $output;
		}
	}
	public function add_script($script)
	{
		$this->scripts[] = $script;
	}
	protected $scripts = array();
}

?>