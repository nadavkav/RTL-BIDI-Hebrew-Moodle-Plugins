<?php

//minimals requires
require_once("../../../../config.php");
require_once($CFG->dirroot."/mod/wiki/lib.php");
//part lib
require_once ('lib.php');

//old requires
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/mod/wiki/pagelib.php');
require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

//configure wiki
wiki_config ();

//form threadment
//(...)

//wiki header
wiki_clean_tabs();
//$selectedtab = wiki_param('selectedtab','prova');
wiki_header();  // Include the actual course format

//content
wiki_part_search_print();

//wiki footer
wiki_footer();

//that's all!
?>