<?php
require_once ($CFG->dirroot.'/mod/wiki/part/search/lib.php');

//include part tabs
//$tabme = new wikitab ('prova','$baseurl/part/prova/index.php?$pageselector','prova_tab',true);
//wiki_add_tab($tabme);

$dfform = wiki_param ('dfform');
if (optional_param('dfsearch') || (isset($dfform['field']) && trim($dfform['field']) != '')) {
	wiki_add_callback ('dfsetup','wiki_part_search_load');
}
?>