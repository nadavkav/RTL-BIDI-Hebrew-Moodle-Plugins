<?php
/**
 * JavaScript element for MoodleQuickForm
 */

global $CFG;

require_once $CFG->libdir.'/formslib.php';
require_once $CFG->libdir.'/form/static.php';

class MoodleQuickForm_script extends MoodleQuickForm_static
{
	/**
	 * Constructor
	 */
	public function MoodleQuickForm_script($script = '')
	{
		$this->script = $script;
	}
	
	/**
	 * Generate HTML
	 */
	public function toHtml()
	{
		return <<<EOT

<script type="text/javascript">
//<![CDATA[
$this->script
//]]>
</script>

EOT;
	}
	
	protected $script = '';
}
MoodleQuickForm::registerElementType('script', __FILE__, 'MoodleQuickForm_script');

?>