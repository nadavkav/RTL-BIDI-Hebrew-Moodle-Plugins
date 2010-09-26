<?php
/**
 * SharingCart_CourseScript
 */

class SharingCart_CourseScript
{
	/**
	 * Add onload event
	 * 
	 * @param string  $script  JavaScript code
	 * @param boolean $return  false: echo / true: return
	 */
	public function addLoadEvent($script, $return = false)
	{
		$html = '
<script type="text/javascript">
//<![CDATA[';
		if (!$this->add_load_event_declared) {
			$html .= '
function addLoadEvent(func)
{
	if (window.addEventListener)
		window.addEventListener("load", func, false);
	else if (window.attachEvent)
		window.attachEvent("onload", func);
	else {
		if (typeof window.onload != "function")
			window.onload = func;
		else {
			var prev = window.onload;
			window.onlaod = function ()
			{
				prev();
				func();
			};
		}
	}
}';
			$this->add_load_event_declared = true;
		}
		$html .= '
addLoadEvent(function () { '.$script.' });
//]]>
</script>
';
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
	}
	protected $add_load_event_declared = false;
	
	/**
	 * Import external script file (prevent duplication)
	 * 
	 * @param string  $path    JavaScript (*.js) file url
	 * @param boolean $return  false: echo / true: return
	 */
	public function import($path, $return = false)
	{
		global $CFG;
		
		if (empty($this->imported_scripts[$path])) {
			$html = '<script type="text/javascript" src="'.$path.'"></script>';
			$this->imported_scripts[$path] = true;
		} else {
			$html = '';
		}
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
	}
	protected $imported_scripts = array();
}

?>