<?php //  * @author : Patrick Thibaudeau @version $Id: version.php,v 1.0 2007/07/01 16:41:20 @package tab
require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require($CFG->libdir.'/filelib.php');
require($CFG->libdir.'/adminlib.php');

$webroot = $CFG->wwwroot;
global $webroot;
class mod_tab_mod_form extends moodleform_mod {

    function definition() {
	global $CFG;

        $mform    =& $this->_form;

$mform->addElement('static', 'hidingjscode', '', '<script>
function shownextchildclass(id) {
  if ( document.getElementById(id).parentNode.nextSibling.nextSibling.nextSibling.attributes[0].nodeValue == \'fcontainer clearfix\') {
    document.getElementById(id).parentNode.nextSibling.nextSibling.nextSibling.attributes[0].nodeValue = \'fcontainer clearfix hide\';
  } else {
    document.getElementById(id).parentNode.nextSibling.nextSibling.nextSibling.attributes[0].nodeValue = \'fcontainer clearfix\';
  }
  //alert(document.getElementById(\'id_toggel1\').parentNode.nextSibling.nextSibling.nextSibling.attributes[0].nodeValue);
}
function shownextheader(id) {
  if ( document.getElementById(id).parentNode.parentNode.attributes[0].nodeValue == \'clearfix\') {
    document.getElementById(id).parentNode.parentNode.attributes[0].nodeValue = \'clearfix hide\';
  } else {
    document.getElementById(id).parentNode.parentNode.attributes[0].nodeValue = \'clearfix\';
  }
  //alert(document.getElementById(\'id_toggel1\').parentNode.nextSibling.nextSibling.nextSibling.attributes[0].nodeValue);
}

function hideallemptytabs() {
  for (i=2; i<9; i++) {
    if (document.getElementById(\'id_tab\'+i.toString()).value == \'\' ) {
      document.getElementById(\'id_htab\'+i.toString()).parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\';
    }
  }

}

window.addEventListener(\'load\',hideallemptytabs,false);

</script>
<style>
.hide {
display:none;
}
</style>
');

	$mform->addElement('text', 'name', get_string('name', 'tab'), array('size'=>'45'));
	//Name to be used
	$mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        //*********************************************************************************
	//************First four tabs available********************************************
        $mform->addElement('header', 'maintab', get_string('tabs', 'tab'));

	$mform->addElement('text', 'tab1', get_string('tabname1', 'tab'), array('size'=>'45'));
	$mform->addRule('tab1', get_string('required'), 'required', null, 'client');
	$mform->setHelpButton('tab1', array('questions', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('htmleditor', 'tab1content', get_string('tabcontent1', 'tab'), array('rows'=>'30'));
	$mform->addRule('tab1content', get_string('required'), 'required', null, 'client');
        $mform->setType('tab1content', PARAM_RAW);
	$mform->addElement('static', '', get_string('btnShowNextHeader','tab').
	  '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/one-more-tab.png" id="id_nextheader2" onclick="shownextheader(\'id_htab2\')">');

	$mform->addElement('header', '', get_string('tab2','tab'). '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/arrow-down-double.png" id="id_htab2" onclick="shownextchildclass(\'id_htab2\')">');
	// initially, hide this section
	//$mform->addElement('static', 'hidesection-tab2', '', '<script>if (document.getElementById(\'id_tab2\').value == \'\' ) { document.getElementById(\'id_htab2\').parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\'; } </script>');
	//$mform->addElement('static', 'hidesection-tab2', '', '<script>document.getElementById(\'id_tab2\').parentNode.nextSibling.nextSibling.nextSibling.attributes[0].nodeValue = \'fcontainer clearfix hide\';</script>');

	  $mform->addElement('text', 'tab2', get_string('tabname2', 'tab'), array('size'=>'45'));
	  $mform->addElement('htmleditor', 'tab2content', get_string('tabcontent2', 'tab'), array('rows'=>'30'));
	  $mform->setType('tab2content', PARAM_RAW);
	  $mform->setHelpButton('tab2', array('questions', 'richtext'), false, 'editorhelpbutton');
	  $mform->addElement('static', '', get_string('btnShowNextHeader','tab').
	    '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/one-more-tab.png" id="id_nextheader3" onclick="shownextheader(\'id_htab3\')">');

	$mform->addElement('header', '', get_string('tab3','tab'). '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/arrow-down-double.png" id="id_htab3" onclick="shownextchildclass(\'id_htab3\')">');
	// initially, hide this section
	//$mform->addElement('static', 'hidesection-tab3', '', '<script>if (document.getElementById(\'id_tab3\').value == \'\' ) { document.getElementById(\'id_htab3\').parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\'; }</script>');

	  $mform->addElement('text', 'tab3', get_string('tabname3', 'tab'), array('size'=>'45'));
	  $mform->addElement('htmleditor', 'tab3content', get_string('tabcontent3', 'tab'), array('rows'=>'30'));
	  $mform->setType('tab3content', PARAM_RAW);
	  $mform->setHelpButton('tab3', array('questions', 'richtext'), false, 'editorhelpbutton');
	  $mform->addElement('static', '', get_string('btnShowNextHeader','tab').
	    '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/one-more-tab.png" id="id_nextheader4" onclick="shownextheader(\'id_htab4\')">');

	$mform->addElement('header', '', get_string('tab4','tab'). '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/arrow-down-double.png" id="id_htab4" onclick="shownextchildclass(\'id_htab4\')">');
	// initially, hide this section
	//$mform->addElement('static', 'hidesection-tab4', '', '<script>document.getElementById(\'id_tab4\').parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\';</script>');

	  $mform->addElement('text', 'tab4', get_string('tabname4', 'tab'), array('size'=>'45'));
	  $mform->addElement('htmleditor', 'tab4content', get_string('tabcontent4', 'tab'), array('rows'=>'30'));
	  $mform->setType('tab4content', PARAM_RAW);
	  $mform->addElement('static', '', get_string('btnShowNextHeader','tab').
	    '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/one-more-tab.png" id="id_nextheader5" onclick="shownextheader(\'id_htab5\')">');

//	$mform->addElement('header', '', get_string('moretabs', 'tab'));

	$mform->addElement('header', '', get_string('tab5','tab'). '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/arrow-down-double.png" id="id_htab5" onclick="shownextchildclass(\'id_htab5\')">');
	// initially, hide this section
	//$mform->addElement('static', 'hidesection-tab5', '', '<script>document.getElementById(\'id_tab5\').parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\';</script>');

	  $mform->addElement('text', 'tab5', get_string('tabname5', 'tab'), array('size'=>'45'),$CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab5');
	  $mform->addElement('htmleditor', 'tab5content', get_string('tabcontent5', 'tab'), array('rows'=>'30'), $CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab5content');
	  $mform->setType('tab5content', PARAM_RAW);
	  $mform->setHelpButton('tab5', array('questions', 'richtext'), false, 'editorhelpbutton');
	  $mform->addElement('static', '', get_string('btnShowNextHeader','tab').
	    '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/one-more-tab.png" id="id_nextheader6" onclick="shownextheader(\'id_htab6\')">');

	$mform->addElement('header', '', get_string('tab6','tab'). '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/arrow-down-double.png" id="id_htab6" onclick="shownextchildclass(\'id_htab6\')">');
	// initially, hide this section
	//$mform->addElement('static', 'hidesection-tab6', '', '<script>document.getElementById(\'id_tab6\').parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\';</script>');

	  $mform->addElement('text', 'tab6', get_string('tabname6', 'tab'), array('size'=>'45'),$CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab6');
	  $mform->addElement('htmleditor', 'tab6content', get_string('tabcontent6', 'tab'), array('rows'=>'30'), $CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab6content');
	  $mform->setType('tab6content', PARAM_RAW);
	  $mform->setHelpButton('tab6', array('questions', 'richtext'), false, 'editorhelpbutton');
	  $mform->addElement('static', '', get_string('btnShowNextHeader','tab').
	    '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/one-more-tab.png" id="id_nextheader7" onclick="shownextheader(\'id_htab7\')">');

	$mform->addElement('header', '', get_string('tab7','tab'). '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/arrow-down-double.png" id="id_htab7" onclick="shownextchildclass(\'id_htab7\')">');
	// initially, hide this section
	//$mform->addElement('static', 'hidesection-tab7', '', '<script>document.getElementById(\'id_tab7\').parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\';</script>');

	  $mform->addElement('text', 'tab7', get_string('tabname7', 'tab'), array('size'=>'45'),$CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab7');
	  $mform->addElement('htmleditor', 'tab7content', get_string('tabcontent7', 'tab'), array('rows'=>'30'), $CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab7content');
	  $mform->setType('tab7content', PARAM_RAW);
	  $mform->setHelpButton('tab7', array('questions', 'richtext'), false, 'editorhelpbutton');
	  $mform->addElement('static', '', get_string('btnShowNextHeader','tab').
	    '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/one-more-tab.png" id="id_nextheader8" onclick="shownextheader(\'id_htab8\')">');

	$mform->addElement('header', '', get_string('tab8','tab'). '  <img src="'.$CFG->wwwroot.'/mod/tab/pix/arrow-down-double.png" id="id_htab8" onclick="shownextchildclass(\'id_htab8\')">');
	// initially, hide this section
	//$mform->addElement('static', 'hidesection-tab8', '', '<script>document.getElementById(\'id_tab8\').parentNode.parentNode.attributes[0].nodeValue = \'fcontainer clearfix hide\';</script>');

	  $mform->addElement('text', 'tab8', get_string('tabname8', 'tab'), array('size'=>'45'),$CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab8');
	  $mform->addElement('htmleditor', 'tab8content', get_string('tabcontent8', 'tab'), array('rows'=>'30'), $CHOICE_DISPLAY);
	  //$mform->setAdvanced('tab8content');
	  $mform->setType('tab8content', PARAM_RAW);
	  $mform->setHelpButton('tab8', array('questions', 'richtext'), false, 'editorhelpbutton');

	//*********************************************************************************
	//*********StyleSheet also not available when show advanced not clicked************
	//*********************************************************************************
	$mform->addElement('header', 'stylesheet', get_string('changestyle', 'tab'));
	$mform->addElement('textarea', 'css', get_string('css', 'tab'),'wrap="virtual" rows="64" cols="60"');
	$mform->setAdvanced('css');
	$mform->setType('css', PARAM_TEXT);
	$mform->setDefault('css','/*
	Copyright (c) 2007, Yahoo! Inc. All rights reserved.
	Code licensed under the BSD License:
	http://developer.yahoo.net/yui/license.txt
	version: 2.3.0
	*/
	.yui-navset .yui-nav li,.yui-navset .yui-navset-top .yui-nav li,.yui-navset .yui-navset-bottom .yui-nav li
	{
		margin:0 0.5em 0 0;
	}
	.yui-navset-left .yui-nav li,.yui-navset-right .yui-nav li
	{
		margin:0 0 0.5em;
	}
	.yui-navset .yui-navset-left .yui-nav,.yui-navset .yui-navset-right .yui-nav,.yui-navset-left .yui-nav,.yui-navset-right .yui-nav
	{
		width:6em;
	}
	.yui-navset-top .yui-nav,.yui-navset-bottom .yui-nav
	{
		width:auto;
	}
	.yui-navset .yui-navset-left,.yui-navset-left
	{
		padding:0 0 0 6em;
	}
	.yui-navset-right
	{
		padding:0 6em 0 0;
	}
	.yui-navset-top,.yui-navset-bottom
	{
		padding:auto;
	}
	.yui-nav,.yui-nav li
	{
		margin:0;
		padding:0;
		list-style:none;
	}
	.yui-navset li em
	{
		font-style:normal;
	}
	.yui-navset
	{
		position:relative;
		zoom:1;
	}
	.yui-navset .yui-content
	{
		zoom:1;
	}
	.yui-navset .yui-nav li,.yui-navset .yui-navset-top .yui-nav li,.yui-navset .yui-navset-bottom .yui-nav li
	{
		display:inline-block;
		display:-moz-inline-stack;
		*display:inline;
		vertical-align:bottom;
		cursor:pointer;
		zoom:1;
	}
	.yui-navset-left .yui-nav li,.yui-navset-right .yui-nav li
	{
		display:block;
	}
	.yui-navset .yui-nav a
	{
		Xoutline:0;
	}
	.yui-navset .yui-nav a
	{
		Xposition:relative;
	}
	.yui-navset .yui-nav li a,.yui-navset-top .yui-nav li a,.yui-navset-bottom .yui-nav li a
	{
		display:block;
		display:inline-block;
		vertical-align:bottom;
		zoom:1;
	}
	.yui-navset-left .yui-nav li a,.yui-navset-right .yui-nav li a
	{
		display:block;
	}
	.yui-navset-bottom .yui-nav li a
	{
		vertical-align:text-top;
	}
	.yui-navset .yui-nav li a em,.yui-navset-top .yui-nav li a em,.yui-navset-bottom .yui-nav li a em
	{
		display:block;
	}
	.yui-navset .yui-navset-left .yui-nav,.yui-navset .yui-navset-right .yui-nav,.yui-navset-left .yui-nav,.yui-navset-right .yui-nav
	{
		position:absolute;
		z-index:1;
	}
	.yui-navset-top .yui-nav,.yui-navset-bottom .yui-nav
	{
		position:static;
	}
	.yui-navset .yui-navset-left .yui-nav,.yui-navset-left .yui-nav
	{
		left:0;
		right:auto;
	}
	.yui-navset .yui-navset-right .yui-nav,.yui-navset-right .yui-nav
	{
		right:0;
		left:auto;
	}
	.yui-skin-sam .yui-navset .yui-nav,.yui-skin-sam .yui-navset .yui-navset-top .yui-nav
	{
		border:solid #2647a0;
		border-width:0 0 5px;
		Xposition:relative;
		zoom:1;
	}
	.yui-skin-sam .yui-navset .yui-nav li,.yui-skin-sam .yui-navset .yui-navset-top .yui-nav li
	{
		margin:0 0.16em 0 0;
		padding:1px 0 0;
		zoom:1;
	}
	.yui-skin-sam .yui-navset .yui-nav .selected,.yui-skin-sam .yui-navset .yui-navset-top .yui-nav .selected
	{
		margin:0 0.16em -1px 0;
	}
	.yui-skin-sam .yui-navset .yui-nav a,.yui-skin-sam .yui-navset .yui-navset-top .yui-nav a
	{
		background:#d8d8d8 url(../../lib/yui/assets/skins/sam/sprite.png) repeat-x;
		border:solid #a3a3a3;
		border-width:0 1px;
		color:#000;position:relative;text-decoration:none;
	}
	.yui-skin-sam .yui-navset .yui-nav a em,.yui-skin-sam .yui-navset .yui-navset-top .yui-nav a em
	{
		border:solid #a3a3a3;
		border-width:1px 0 0;
		cursor:hand;
		padding:0.25em .75em;
		left:0;
		right:0;
		bottom:0;
		top:-1px;
		position:relative;
	}
	.yui-skin-sam .yui-navset .yui-nav .selected a,.yui-skin-sam .yui-navset .yui-nav .selected a:focus,.yui-skin-sam .yui-navset .yui-nav .selected a:hover
	{
		background:#2647a0 url(../../lib/yui/assets/skins/sam/sprite.png) repeat-x left -1400px;
		color:#fff;
	}
	.yui-skin-sam .yui-navset .yui-nav a:hover,.yui-skin-sam .yui-navset .yui-nav a:focus
	{
		background:#bfdaff url(../../lib/yui/assets/skins/sam/sprite.png) repeat-x left -1300px;
		outline:0;
	}
	.yui-skin-sam .yui-navset .yui-nav .selected a em
	{
		padding:0.35em 0.75em;
	}
	.yui-skin-sam .yui-navset .yui-nav .selected a,.yui-skin-sam .yui-navset .yui-nav .selected a em
	{
		border-color:#243356;
	}
	.yui-skin-sam .yui-navset .yui-content
	{
		background:#edf5ff;
	}
	.yui-skin-sam .yui-navset .yui-content,.yui-skin-sam .yui-navset .yui-navset-top .yui-content
	{
		border:1px solid #808080;
		border-top-color:#243356;
		padding:0.25em 0.5em;
	}
	.yui-skin-sam .yui-navset-left .yui-nav,.yui-skin-sam .yui-navset .yui-navset-left .yui-nav,.yui-skin-sam .yui-navset .yui-navset-right .yui-nav,.yui-skin-sam .yui-navset-right .yui-nav
	{
		border-width:0 5px 0 0;
		Xposition:absolute;
		top:0;bottom:0;
	}
	.yui-skin-sam .yui-navset .yui-navset-right .yui-nav,.yui-skin-sam .yui-navset-right .yui-nav
	{
		border-width:0 0 0 5px;
	}
	.yui-skin-sam .yui-navset-left .yui-nav li,.yui-skin-sam .yui-navset .yui-navset-left .yui-nav li,.yui-skin-sam .yui-navset-right .yui-nav li
	{
		margin:0 0 0.16em;
		padding:0 0 0 1px;
	}
	.yui-skin-sam .yui-navset-right .yui-nav li
	{
		padding:0 1px 0 0;
	}
	.yui-skin-sam .yui-navset-left .yui-nav .selected,.yui-skin-sam .yui-navset .yui-navset-left .yui-nav .selected
	{
		margin:0 -1px 0.16em 0;
	}
	.yui-skin-sam .yui-navset-right .yui-nav .selected
	{
		margin:0 0 0.16em -1px;
	}
	.yui-skin-sam .yui-navset-left .yui-nav a,.yui-skin-sam .yui-navset-right .yui-nav a
	{
		border-width:1px 0;
	}
	.yui-skin-sam .yui-navset-left .yui-nav a em,.yui-skin-sam .yui-navset .yui-navset-left .yui-nav a em,.yui-skin-sam .yui-navset-right .yui-nav a em
	{
		border-width:0 0 0 1px;
		padding:0.2em .75em;
		top:auto;left:-1px;
	}
	.yui-skin-sam .yui-navset-right .yui-nav a em
	{
		border-width:0 1px 0 0;
		left:auto;right:-1px;
	}
	.yui-skin-sam .yui-navset-left .yui-nav a,.yui-skin-sam .yui-navset-left .yui-nav .selected a,.yui-skin-sam .yui-navset-left .yui-nav a:hover,.yui-skin-sam .yui-navset-right .yui-nav a,.yui-skin-sam .yui-navset-right .yui-nav .selected a,.yui-skin-sam .yui-navset-right .yui-nav a:hover,.yui-skin-sam .yui-navset-bottom .yui-nav a,.yui-skin-sam .yui-navset-bottom .yui-nav .selected a,.yui-skin-sam .yui-navset-bottom .yui-nav a:hover
	{
		background-image:none;
	}
	.yui-skin-sam .yui-navset-left .yui-content
	{
		border:1px solid #808080;
		border-left-color:#243356;
	}
	.yui-skin-sam .yui-navset-bottom .yui-nav,.yui-skin-sam .yui-navset .yui-navset-bottom .yui-nav
	{
		border-width:5px 0 0;
	}
	.yui-skin-sam .yui-navset .yui-navset-bottom .yui-nav .selected,.yui-skin-sam .yui-navset-bottom .yui-nav .selected
	{
		margin:-1px 0.16em 0 0;
	}
	.yui-skin-sam .yui-navset .yui-navset-bottom .yui-nav li,.yui-skin-sam .yui-navset-bottom .yui-nav li
	{
		padding:0 0 1px 0;
		vertical-align:top;
	}
	.yui-skin-sam .yui-navset .yui-navset-bottom .yui-nav li a,.yui-skin-sam .yui-navset-bottom .yui-nav li a{}.yui-skin-sam .yui-navset .yui-navset-bottom .yui-nav a em,.yui-skin-sam .yui-navset-bottom .yui-nav a em
	{
		border-width:0 0 1px;
		top:auto;bottom:-1px;
	}
	.yui-skin-sam .yui-navset-bottom .yui-content,.yui-skin-sam .yui-navset .yui-navset-bottom .yui-content
	{
		border:1px solid #808080;
		border-bottom-color:#243356;
	}
	.yui-hidden {
		display:none;
	}
	');
	//*********************************************************************************
	//******************Style sheet for menu*******************************************
	//*********************************************************************************
	$mform->addElement('textarea', 'menucss', get_string('menucss', 'tab'),'wrap="virtual" rows="45" cols="60"');
	$mform->setAdvanced('menucss');
	$mform->setType('menucss', PARAM_RAW);
	$mform->setDefault('menucss','#tab-menu-wrapper {
		min-width: 600px;
		max-width: 2000px;
		position: relative;
		margin: 10px;
	}
	#left {
		position: absolute;
		top: 0;
		bottom: 0;
		left: 0;
		width: 200px;
		padding: inherit;

	}
	#tabcontent {
		margin-left: 211px; /*DO NOT REMOVE OR CHANGE THIS VALUE*/
		padding: 10px;
	}

	#wrapper {
		width: expression(
			documentElement.clientWidth >=2000?
				2000
			:
				(documentElement.clientWidth <= 600? 600 : "auto")
		);
	}
	#left {
		top: 10px;
		height: expression(
			documentElement.getElementById("wrapper").offsetHeight -12
		);
	}
	.menutable {
		border: 1px solid #808080;
	}
	.menutitle {
		background:#2647a0 url(../../lib/yui/assets/skins/sam/sprite.png) repeat-x left -1400px;
		color:#fff;
	}
	.row {
		background-color: #edf5ff;
	}
	');
	//*********************************************************************************
	//*********************Display menu checkbox and name******************************
	//*********************************************************************************
	$mform->addElement('header', 'menu', get_string('displaymenu', 'tab'));
	$mform->addElement('advcheckbox', 'displaymenu', get_string('displaymenuagree', 'tab'), array(0,1));
	$mform->setType('displaymenu', PARAM_INT);
	$mform->addElement('text', 'menuname', get_string('menuname', 'tab'), array('size'=>'45'));

    $mform->addElement('header', 'special', get_string('special', 'tab'));
    $mform->addElement('advcheckbox', 'displayfp', get_string('displayfp', 'tab'), array(0,1));
    $mform->setType('displayfp', PARAM_INT);

	//*********************************************************************************
	//*********************************************************************************

        $features = array('groups'=>false, 'groupings'=>false, 'groupmembersonly'=>true,
                          'outcomes'=>false, 'gradecat'=>false, 'idnumber'=>false);
        $this->standard_coursemodule_elements($features);

//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();

    }

}
?>