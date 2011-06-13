<?php //$Id: mod_form.php,v 0.2 2009/02/21 matbury Exp $

/**
* Creates instance of Media Player activity module
* Adapted from mod_form.php template by Jamie Pratt
*
* By Matt Bury - http://matbury.com/ - matbury@gmail.com
* @version $Id: index.php,v 0.2 2009/02/21 matbury Exp $
* @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
*
* DB Table name (mdl_)mplayer
* 
*/

/**    Copyright (C) 2009  Matt Bury
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once ('moodleform_mod.php');

class mod_mplayer_mod_form extends moodleform_mod {

	function definition() {

		global $CFG;
		global $COURSE;
		global $USER;
		
		$mform =& $this->_form;
		$mplayer_url_array = array('size'=>'80');
		$mplayer_int_array = array('size'=>'6');
//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are shown
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('mplayername', 'mplayer'), $mplayer_url_array);
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');
    /// Adding the optional "intro" and "introformat" pair of fields
    	$mform->addElement('htmleditor', 'intro', get_string('mplayerintro', 'mplayer'));
		$mform->setType('intro', PARAM_RAW);
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('format', 'introformat', get_string('format'));

//--------------------------------------- MEDIA SOURCE ----------------------------------------
	$mform->addElement('header', 'mplayersource', get_string('mplayersource', 'mplayer'));
	$mform->setHelpButton('mplayersource', array('mplayer_source', get_string('mplayersource', 'mplayer'), 'mplayer'));
	// mplayerfile
	$mform->addElement('choosecoursefile', 'mplayerfile', get_string('mplayerfile', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->addRule('mplayerfile', get_string('required'), 'required', null, 'client');
	// type
	$mform->addElement('select', 'type', get_string('type', 'mplayer'), mplayer_list_type());
	$mform->setDefault('type', 'video');
	$mform->setAdvanced('type');
	// streamer
	$mform->addElement('select', 'streamer', get_string('streamer', 'mplayer'), mplayer_list_streamer());
	$mform->setDefault('streamer', '');
	$mform->setAdvanced('streamer');
	
////--------------------------------------- playlists ---------------------------------------
	$mform->addElement('header', 'playlists', get_string('playlists', 'mplayer'));
	$mform->setHelpButton('playlists', array('mplayer_playlist', get_string('playlists', 'mplayer'), 'mplayer'));
	// playlist
	$mform->addElement('select', 'playlist', get_string('playlist', 'mplayer'), mplayer_list_playlistposition());
	$mform->setDefault('playlist', 'none');
	$mform->setAdvanced('playlist');
	// playlistsize
	$mform->addElement('text', 'playlistsize', get_string('playlistsize', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('playlistsize', '180');
	$mform->setAdvanced('playlistsize');
	// item
	$mform->addElement('text', 'item', get_string('item', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('item', '');
	$mform->setAdvanced('item');
	// repeat
	$mform->addElement('select', 'mplayerrepeat', get_string('mplayerrepeat', 'mplayer'), mplayer_list_repeat());
	$mform->setDefault('mplayerrepeat', 'none');
	$mform->setAdvanced('mplayerrepeat');
	// shuffle
	$mform->addElement('select', 'shuffle', get_string('shuffle', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('shuffle', 'false');
	$mform->setAdvanced('shuffle');
	
////--------------------------------------- configxml ---------------------------------------
	$mform->addElement('header', 'config', get_string('config', 'mplayer'));
	$mform->setHelpButton('config', array('mplayer_configxml', get_string('appearance', 'mplayer'), 'mplayer'));
	// configxml
	$mform->addElement('choosecoursefile', 'configxml', get_string('configxml', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('configxml');

////--------------------------------------- APPEARANCE ---------------------------------------
	$mform->addElement('header', 'appearance', get_string('appearance', 'mplayer'));
	$mform->setHelpButton('appearance', array('mplayer_appearance', get_string('appearance', 'mplayer'), 'mplayer'));
	//notes
    $mform->addElement('htmleditor', 'notes', get_string('notes', 'mplayer'), array('canUseHtmlEditor'=>'detect','rows'=>10, 'cols'=>65, 'width'=>0,'height'=>0));
	$mform->setType('notes', PARAM_RAW);
	// width
	$mform->addElement('text', 'width', get_string('width', 'mplayer'), $mplayer_int_array);
	$mform->addRule('width', get_string('required'), 'required', null, 'client');
	if(!$CFG->mplayer_default_width) {
		$CFG->mplayer_default_width = '100%';
	}
	$mform->setDefault('width', $CFG->mplayer_default_width);
	// height
	$mform->addElement('text', 'height', get_string('height', 'mplayer'), $mplayer_int_array);
	$mform->addRule('height', get_string('required'), 'required', null, 'client');
	if(!$CFG->mplayer_default_height) {
		$CFG->mplayer_default_height = '570';
	}
	$mform->setDefault('height', $CFG->mplayer_default_height);
	// skin
	$mform->addElement('select', 'skin', get_string('skin', 'mplayer'), mplayer_list_skins());
	if(!$CFG->mplayer_default_skin) {
		$CFG->mplayer_default_skin = '';
	}
	$mform->setDefault('skin', $CFG->mplayer_default_skin);
	// image
	$mform->addElement('choosecoursefile', 'image', get_string('image', 'mplayer'), array('courseid'=>$COURSE->id));
	// icons
	$mform->addElement('select', 'icons', get_string('icons', 'mplayer'), mplayer_list_truefalse());
	if(!$CFG->mplayer_default_icons) {
		$CFG->mplayer_default_icons = 'true';
	}
	$mform->setDefault('icons', $CFG->mplayer_default_icons);
	$mform->setAdvanced('icons');
	// controlbar
	$mform->addElement('select', 'controlbar', get_string('controlbar', 'mplayer'), mplayer_list_controlbar());
	if(!$CFG->mplayer_default_controlbar) {
		$CFG->mplayer_default_controlbar = 'bottom';
	}
	$mform->setDefault('controlbar', $CFG->mplayer_default_controlbar);
	$mform->setAdvanced('controlbar');
	// backcolor
	$mform->addElement('text', 'backcolor', get_string('backcolor', 'mplayer'), $mplayer_int_array);
	if(!$CFG->mplayer_default_backcolor) {
		$CFG->mplayer_default_backcolor = '';
	}
	$mform->setDefault('backcolor', $CFG->mplayer_default_backcolor);
	$mform->setAdvanced('backcolor');
	// frontcolor
	$mform->addElement('text', 'frontcolor', get_string('frontcolor', 'mplayer'), $mplayer_int_array);
	if(!$CFG->mplayer_default_frontcolor) {
		$CFG->mplayer_default_frontcolor = '';
	}
	$mform->setDefault('frontcolor', $CFG->mplayer_default_frontcolor);
	$mform->setAdvanced('frontcolor');
	// lightcolor
	$mform->addElement('text', 'lightcolor', get_string('lightcolor', 'mplayer'), $mplayer_int_array);
	if(!$CFG->mplayer_default_lightcolor) {
		$CFG->mplayer_default_lightcolor = '';
	}
	$mform->setDefault('lightcolor', $CFG->mplayer_default_lightcolor);
	$mform->setAdvanced('lightcolor');
	// screencolor
	$mform->addElement('text', 'screencolor', get_string('screencolor', 'mplayer'), $mplayer_int_array);
	if(!$CFG->mplayer_default_screencolor) {
		$CFG->mplayer_default_screencolor = '';
	}
	$mform->setDefault('screencolor', $CFG->mplayer_default_screencolor);
	$mform->setAdvanced('screencolor');
	// smoothing
	$mform->addElement('select', 'smoothing', get_string('smoothing', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('smoothing', 'true');
	$mform->setAdvanced('smoothing');
	// quality
	$mform->addElement('select', 'quality', get_string('quality', 'mplayer'), mplayer_list_quality());
	$mform->setDefault('quality', 'best');
	$mform->setAdvanced('quality');

////--------------------------------------- BEHAVIOUR ---------------------------------------
	$mform->addElement('header', 'behaviour', get_string('behaviour', 'mplayer'));
	$mform->setHelpButton('behaviour', array('mplayer_behaviour', get_string('behaviour', 'mplayer'), 'mplayer'));
	// autostart 
	$mform->addElement('select', 'autostart', get_string('autostart', 'mplayer'), mplayer_list_truefalse());
	if(!$CFG->mplayer_default_autostart) {
		$CFG->mplayer_default_autostart = 'false';
	}
	$mform->setDefault('autostart', $CFG->mplayer_default_autostart);
	// fullscreen 
	$mform->addElement('select', 'fullscreen', get_string('fullscreen', 'mplayer'), mplayer_list_truefalse());
	if(!$CFG->mplayer_default_fullscreen) {
		$CFG->mplayer_default_fullscreen = 'true';
	}
	$mform->setDefault('fullscreen', $CFG->mplayer_default_fullscreen);
	// stretching 
	$mform->addElement('select', 'stretching', get_string('stretching', 'mplayer'), mplayer_list_stretching());
	if(!$CFG->mplayer_default_stretching) {
		$CFG->mplayer_default_stretching = 'uniform';
	}
	$mform->setDefault('stretching', $CFG->mplayer_default_stretching);
	$mform->setAdvanced('stretching');
	// volume 
	$mform->addElement('select', 'volume', get_string('volume', 'mplayer'), mplayer_list_volume());
	if(!$CFG->mplayer_default_volume) {
		$CFG->mplayer_default_volume = '90';
	}
	$mform->setDefault('volume', $CFG->mplayer_default_volume);
	$mform->setAdvanced('volume');
	// mute 
	$mform->addElement('select', 'mute', get_string('mute', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('mute', 'false');
	$mform->setAdvanced('mute');
	// mplayerstart 
	$mform->addElement('text', 'mplayerstart', get_string('mplayerstart', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('mplayerstart', '0');
	$mform->setAdvanced('mplayerstart');
	// bufferlength 
	$mform->addElement('select', 'bufferlength', get_string('bufferlength', 'mplayer'), mplayer_list_bufferlength());
	$mform->setDefault('bufferlength', '1');
	$mform->setAdvanced('bufferlength');
	// resizing - deprecated
	//$mform->addElement('select', 'resizing', get_string('resizing', 'mplayer'), mplayer_list_truefalse());
	//$mform->setAdvanced('resizing');
	// plugins 
	$mform->addElement('text', 'plugins', get_string('plugins', 'mplayer'), $mplayer_url_array);
	$mform->setDefault('plugins', '');
	$mform->setAdvanced('plugins');
	
////--------------------------------------- metadata ---------------------------------------
	$mform->addElement('header', 'metadata', get_string('metadata', 'mplayer'));
	$mform->setHelpButton('metadata', array('mplayer_metadata', get_string('metadata', 'mplayer'), 'mplayer'));
	// author
	$mform->addElement('text', 'author', get_string('author', 'mplayer'), $mplayer_url_array);
	$mform->setDefault('author', fullname($USER));
	$mform->setAdvanced('author');
	// mplayerdate
	$mform->addElement('text', 'mplayerdate', get_string('mplayerdate', 'mplayer'), $mplayer_url_array);
	$mform->setDefault('mplayerdate', date('l jS \of F Y'));
	$mform->setAdvanced('mplayerdate');
	// title
	$mform->addElement('text', 'title', get_string('title', 'mplayer'), $mplayer_url_array);
	$mform->setAdvanced('title');
	// description
	$mform->addElement('text', 'description', get_string('description', 'mplayer'), $mplayer_url_array);
	$mform->setAdvanced('description');
	// tags
	$mform->addElement('text', 'tags', get_string('tags', 'mplayer'), $mplayer_url_array);
	$mform->setAdvanced('tags');

////--------------------------------------- audiodescription ---------------------------------------
	$mform->addElement('header', 'audiodescription', get_string('audiodescription', 'mplayer'));
	$mform->setHelpButton('audiodescription', array('mplayer_audiodescription', get_string('audiodescription', 'mplayer'), 'mplayer'));
	// audiodescriptionfile
	$mform->addElement('choosecoursefile', 'audiodescriptionfile', get_string('audiodescriptionfile', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('audiodescriptionfile');
	// audiodescriptionstate
	$mform->addElement('select', 'audiodescriptionstate', get_string('audiodescriptionstate', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('audiodescriptionstate', 'true');
	$mform->setAdvanced('audiodescriptionstate');
	// audiodescriptionvolume
	$mform->addElement('select', 'audiodescriptionvolume', get_string('audiodescriptionvolume', 'mplayer'), mplayer_list_volume());
	$mform->setDefault('audiodescriptionvolume', '90');
	$mform->setAdvanced('audiodescriptionvolume');
	
////--------------------------------------- captions ---------------------------------------
	$mform->addElement('header', 'captions', get_string('captions', 'mplayer'));
	$mform->setHelpButton('captions', array('mplayer_captions', get_string('captions', 'mplayer'), 'mplayer'));
	// captionsback
	$mform->addElement('select', 'captionsback', get_string('captionsback', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('captionsback', 'true');
	$mform->setAdvanced('captionsback');
	// captionsfile
	$mform->addElement('choosecoursefile', 'captionsfile', get_string('captionsfile', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('captionsfile');
	// captionsfontsize
	$mform->addElement('text', 'captionsfontsize', get_string('captionsfontsize', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('captionsfontsize', '14');
	$mform->setAdvanced('captionsfontsize');
	// captionsstate
	$mform->addElement('select', 'captionsstate', get_string('captionsstate', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('captionsstate', 'true');
	$mform->setAdvanced('captionsstate');
	
////--------------------------------------- HD ---------------------------------------
	$mform->addElement('header', 'hd', get_string('hd', 'mplayer'));
	$mform->setHelpButton('hd', array('mplayer_hd', get_string('hd', 'mplayer'), 'mplayer'));
	// hdbitrate 
	$mform->addElement('text', 'hdbitrate', get_string('hdbitrate', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('hdbitrate', '1500');
	$mform->setAdvanced('hdbitrate');
	// hdfile 
	$mform->addElement('choosecoursefile', 'hdfile', get_string('hdfile', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('hdfile');
	// hdfullscreen 
	$mform->addElement('select', 'hdfullscreen', get_string('hdfullscreen', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('hdfullscreen', 'true');
	$mform->setAdvanced('hdfullscreen');
	// hdstate 
	$mform->addElement('select', 'hdstate', get_string('hdstate', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('hdstate', 'true');
	$mform->setAdvanced('hdstate');

////--------------------------------------- infobox ---------------------------------------
	$mform->addElement('header', 'infobox', get_string('infobox', 'mplayer'));
	$mform->setHelpButton('infobox', array('mplayer_infobox', get_string('infobox', 'mplayer'), 'mplayer'));
	// infoboxcolor 
	$mform->addElement('text', 'infoboxcolor', get_string('infoboxcolor', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('infoboxcolor', 'ffffff');
	$mform->setAdvanced('infoboxcolor');
	// infoboxposition
	$mform->addElement('select', 'infoboxposition', get_string('infoboxposition', 'mplayer'), mplayer_list_infoboxposition());
	$mform->setDefault('infoboxposition', 'none');
	$mform->setAdvanced('infoboxposition');
	// infoboxsize
	$mform->addElement('text', 'infoboxsize', get_string('infoboxsize', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('infoboxsize', '85');
	$mform->setAdvanced('infoboxsize');
	
////--------------------------------------- livestream ---------------------------------------
	$mform->addElement('header', 'livestream', get_string('livestream', 'mplayer'));
	$mform->setHelpButton('livestream', array('mplayer_livestream', get_string('livestream', 'mplayer'), 'mplayer'));
	// livestreamfile
	$mform->addElement('choosecoursefile', 'livestreamfile', get_string('livestreamfile', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('livestreamfile');
	// livestreamimage 
	$mform->addElement('choosecoursefile', 'livestreamimage', get_string('livestreamimage', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('livestreamimage');
	// livestreaminterval
	$mform->addElement('text', 'livestreaminterval', get_string('livestreaminterval', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('livestreaminterval', '15');
	$mform->setAdvanced('livestreaminterval');
	// livestreammessage
	$mform->addElement('text', 'livestreammessage', get_string('livestreammessage', 'mplayer'), $mplayer_url_array);
	$mform->setDefault('livestreammessage', 'Checking for livestream...');
	$mform->setAdvanced('livestreammessage');
	// livestreamstreamer
	$mform->addElement('select', 'livestreamstreamer', get_string('livestreamstreamer', 'mplayer'), mplayer_list_streamer());
	$mform->setDefault('livestreamstreamer', '');
	$mform->setAdvanced('livestreamstreamer');
	// livestreamtags
	$mform->addElement('text', 'livestreamtags', get_string('livestreamtags', 'mplayer'), $mplayer_url_array);
	$mform->setAdvanced('livestreamtags');

////--------------------------------------- logobox ---------------------------------------
	$mform->addElement('header', 'logobox', get_string('logobox', 'mplayer'));
	$mform->setHelpButton('logobox', array('mplayer_logobox', get_string('logobox', 'mplayer'), 'mplayer'));
	// logoboxalign
	$mform->addElement('select', 'logoboxalign', get_string('logoboxalign', 'mplayer'), mplayer_list_logoboxalign());
	$mform->setDefault('logoboxalign', 'left');
	$mform->setAdvanced('logoboxalign');
	// logoboxfile 
	$mform->addElement('choosecoursefile', 'logoboxfile', get_string('logoboxfile', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('logoboxfile');
	// logoboxlink
	$mform->addElement('text', 'logoboxlink', get_string('logoboxlink', 'mplayer'), $mplayer_url_array);
	$mform->setAdvanced('logoboxlink');
	// logoboxmargin
	$mform->addElement('text', 'logoboxmargin', get_string('logoboxmargin', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('logoboxmargin', '15');
	$mform->setAdvanced('logoboxmargin');
	//logoboxposition
	$mform->addElement('select', 'logoboxposition', get_string('logoboxposition', 'mplayer'), mplayer_list_infoboxposition());
	$mform->setDefault('logoboxposition', 'top');
	$mform->setAdvanced('logoboxposition');
	
////--------------------------------------- metaviewer ---------------------------------------
	$mform->addElement('header', 'metaviewer', get_string('metaviewer', 'mplayer'));
	$mform->setHelpButton('metaviewer', array('mplayer_metaviewer', get_string('metaviewer', 'mplayer'), 'mplayer'));
	// metaviewerposition
	$mform->addElement('select', 'metaviewerposition', get_string('metaviewerposition', 'mplayer'), mplayer_list_metaviewerposition());
	$mform->setDefault('metaviewerposition', 'none');
	$mform->setAdvanced('metaviewerposition');
	// metaviewersize
	$mform->addElement('text', 'metaviewersize', get_string('metaviewersize', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('metaviewersize', '100');
	$mform->setAdvanced('metaviewersize');
	
////--------------------------------------- searchbar ---------------------------------------
	$mform->addElement('header', 'searchbar', get_string('searchbar', 'mplayer'));
	$mform->setHelpButton('searchbar', array('mplayer_searchbar', get_string('searchbar', 'mplayer'), 'mplayer'));
	// searchbarcolor 
	$mform->addElement('text', 'searchbarcolor', get_string('searchbarcolor', 'mplayer'), $mplayer_int_array);
	$mform->setDefault('searchbarcolor', 'CC0000');
	$mform->setAdvanced('searchbarcolor');
	// searchbarlabel 
	$mform->addElement('text', 'searchbarlabel', get_string('searchbarlabel', 'mplayer'), $mplayer_url_array);
	$mform->setDefault('searchbarlabel', 'Search');
	$mform->setAdvanced('searchbarlabel');
	// searchbarposition 
	$mform->addElement('select', 'searchbarposition', get_string('searchbarposition', 'mplayer'), mplayer_list_searchbarposition());
	$mform->setDefault('searchbarposition', '');
	$mform->setAdvanced('searchbarposition');
	// searchbarscript 
	$mform->addElement('select', 'searchbarscript', get_string('searchbarscript', 'mplayer'), mplayer_list_searchbarscript());
	$mform->setDefault('searchbarscript', '');
	$mform->setAdvanced('searchbarscript');
	
////--------------------------------------- snapshot ---------------------------------------
	$mform->addElement('header', 'snapshot', get_string('snapshot', 'mplayer'));
	$mform->setHelpButton('snapshot', array('mplayer_snapshot', get_string('snapshot', 'mplayer'), 'mplayer'));
	// snapshotbitmap
	$mform->addElement('select', 'snapshotbitmap', get_string('snapshotbitmap', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('snapshotbitmap', 'true');
	$mform->setAdvanced('snapshotbitmap');
	// snapshotscript
	$mform->addElement('select', 'snapshotscript', get_string('snapshotscript', 'mplayer'), mplayer_list_snapshotscript());
	$mform->setDefault('snapshotscript', '');
	$mform->setAdvanced('snapshotscript');
	
////--------------------------------------- logo (licenced players only) ---------------------------------------
	$mform->addElement('header', 'logo', get_string('logo', 'mplayer'));
	$mform->setHelpButton('logo', array('mplayer_logo', get_string('logo', 'mplayer'), 'mplayer'));
	// logofile 
	$mform->addElement('choosecoursefile', 'logofile', get_string('logofile', 'mplayer'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('logofile');
	// logolink 
	$mform->addElement('text', 'logolink', get_string('logolink', 'mplayer'), $mplayer_url_array);
	$mform->setAdvanced('logolink');
	// logohide
	$mform->addElement('select', 'logohide', get_string('logohide', 'mplayer'), mplayer_list_truefalse());
	$mform->setDefault('logohide', 'true');
	$mform->setAdvanced('logohide');
	// logoposition
	$mform->addElement('select', 'logoposition', get_string('logoposition', 'mplayer'), mplayer_list_logoposition());
	$mform->setDefault('logoposition', 'bottom-left');
	$mform->setAdvanced('logoposition');
	
////--------------------------------------- ADVANCED ---------------------------------------
	$mform->addElement('header', 'advanced', get_string('advanced', 'mplayer'));
	$mform->setHelpButton('advanced', array('mplayer_advanced', get_string('advanced', 'mplayer'), 'mplayer'));
	// fpversion
	$mform->addElement('text', 'fpversion', get_string('fpversion', 'mplayer'), array('size'=>'9'));
	$mform->setDefault('fpversion', '9.0.115');
	$mform->addRule('fpversion', get_string('required'), 'required', null, 'client');
	$mform->setAdvanced('fpversion');
	// tracecall
	$mform->addElement('text', 'tracecall', get_string('tracecall', 'mplayer'), $mplayer_url_array);
	$mform->setAdvanced('tracecall');
	
//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
		$this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

	}
}

?>
