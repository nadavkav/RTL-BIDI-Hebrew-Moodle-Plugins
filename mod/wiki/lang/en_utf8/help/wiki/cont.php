<html>
<body>
<?php
	$files = array(
				'attach',
				'contributions',
				'differences',
				'ead',
				'editors',
				'howtowiki_dfwiki',
				'howtowiki_ewiki',
				'howtowiki_htmleditor',
				'index',
				'index_current',
				'list_pages',
				'mods',
				'navigator',
				'new_page',
				'orphaned',
				'privileges',
				'search',
				'synonymous',
				'updated',
				'wanted'
			);

	foreach ($files as $file){
		echo '<a href="http://147.83.59.182/moodle15/lang/es/help/dfwiki/'.$file.'.html">'.$file.'</a><br />';
	}
?>
</body>
</html>