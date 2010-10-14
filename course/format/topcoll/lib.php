<style type="text/css" media="screen">
/* <![CDATA[ */
	@import url(<?php echo $CFG->wwwroot ?>/course/format/topcoll/topics_collapsed.css);
/* ]]> */
</style>
<!--[if lte IE 7]>
	<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot ?>/course/format/topcoll/ie-7-hacks.css" media="screen" />
<![endif]-->
	
<script type="text/javascript">
<?php

echo "var cssNode = document.createElement('link');";
echo "cssNode.setAttribute('rel', 'stylesheet');";
echo "cssNode.setAttribute('type', 'text/css');";
echo "cssNode.setAttribute('href', '".$CFG->wwwroot."/course/format/topcoll/js-override-topcoll.css');\n";
echo "document.getElementsByTagName('head')[0].appendChild(cssNode);";


echo "function toggle_topic(toggler)
      {
		if(document.getElementById)
		{
			imageSwitch = toggler;
			targetElement = toggler.parentNode.parentNode.nextSibling; // Called from a <td> inside a <tr> so find the next <tr>.
	
			if(targetElement.className == undefined)
			{
				targetElement = toggler.parentNode.parentNode.nextSibling.nextSibling; // If not found, try the next.
			}

			if (navigator.userAgent.indexOf('IE')!= -1)
			{
				var displaySetting = \"block\";
			}
			else
			{
				var displaySetting = \"table-row\";
			}
	
		if (targetElement.style.display == displaySetting)
		{
			targetElement.style.display = \"none\";
			imageSwitch.style.backgroundImage = \"url(".$CFG->wwwroot."/course/format/topcoll/arrow_down.png)\";
		}
		else
		{
			targetElement.style.display = displaySetting;
			imageSwitch.style.backgroundImage = \"url(".$CFG->wwwroot."/course/format/topcoll/arrow_up.png)\";
		}
	}
}";
?>
</script>
