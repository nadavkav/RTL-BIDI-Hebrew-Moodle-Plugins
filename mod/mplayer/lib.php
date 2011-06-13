<?php  // $Id: lib.php,v 0.2 2009/02/21 matbury Exp $
/**
* Library of functions and constants for module mplayer
* For more information on the parameters used by JW FLV Player see documentation: http://developer.longtailvideo.com/trac/wiki/FlashVars
* 
* @author Matt Bury - matbury@gmail.com - http://matbury.com/
* @version $Id: index.php,v 0.2 2009/02/21 matbury Exp $
* @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
* @package mplayer
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

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted mplayer record
 **/
function mplayer_add_instance($mplayer)
{
    
    $mplayer->timecreated = time();

    # May have to add extra stuff in here #
	
	return insert_record('mplayer', $mplayer);
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function mplayer_update_instance($mplayer)
{

    $mplayer->timemodified = time();
    $mplayer->id = $mplayer->instance;
	
	# May have to add extra stuff in here #
		
    return update_record("mplayer", $mplayer);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function mplayer_delete_instance($id)
{

    if (! $mplayer = get_record("mplayer", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("mplayer", "id", "$mplayer->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function mplayer_user_outline($course, $user, $mod, $mplayer)
{
    $return->time = time();
	$return->info = '';
	
	return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function mplayer_user_complete($course, $user, $mod, $mplayer)
{
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in mplayer activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function mplayer_print_recent_activity($course, $isteacher, $timestart)
{
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * 
 *
 * @uses $CFG
 * @return array
 **/
function mplayer_get_view_actions() {
    return array('view');
}

/**
 * 
 *
 * @uses $CFG
 * @return array
 **/
function mplayer_get_post_actions() {
    return array();
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function mplayer_cron()
{
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $mplayerid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function mplayer_grades($mplayerid)
{
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of mplayer. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $mplayerid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function mplayer_get_participants($mplayerid)
{
    return false;
}

/**
 * This function returns if a scale is being used by one mplayer
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $mplayerid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function mplayer_scale_used ($mplayerid,$scaleid)
{
    $return = false;

    //$rec = get_record("mplayer","id","$mplayerid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
 * Checks if scale is being used by any instance of mplayer.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any mplayer
 */
function mplayer_scale_used_anywhere($scaleid)
{
    if ($scaleid and record_exists('mplayer', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function mplayer_install()
{
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function mplayer_uninstall()
{
    return true;
}

/*
-------------------------------------------------------------------- view.php --------------------------------------------------------------------
*/

/**
* Set moodledata path in $mplayer object
*
* @param $mplayer
* @return $mplayer
*/
function mplayer_set_moodledata($mplayer)
{
	global $CFG;
	global $COURSE;
	
	$mplayer->moodledata = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/';
	
	return $mplayer;
}

/**
* Assign the correct path to the file parameter (media source) in $mplayer object
*
* @param obj $mplayer
* @return obj $mplayer
*/
function mplayer_set_type($mplayer)
{
	switch($mplayer->type) {
		
		// video, sound, image and xml (SMIL playlists) are all served from moodledata course directories
		case 'video':
		$mplayer->prefix = $mplayer->moodledata;
		//$mplayer->test_variable = 'case video';
		break;
		
		case 'sound':
		$mplayer->prefix = $mplayer->moodledata;
		//$mplayer->test_variable = 'case sound';
		break;
		
		case 'image':
		$mplayer->prefix = $mplayer->moodledata;
		//$mplayer->test_variable = 'case image';
		break;
		
		case 'xml':
		$mplayer->type = ''; // JW FLV Player doesn't recognise 'xml' as a valid parameter
		$mplayer->prefix = $mplayer->moodledata;
		//$mplayer->test_variable = 'case playlist';
		break;
		
		case 'youtube':
		$mplayer->prefix = '';
		//$mplayer->test_variable = 'case youtube';
		break;
		
		case 'url':
		$mplayer->type = ''; // JW FLV Player doesn't recognise 'url' as a valid parameter
		$mplayer->prefix = '';
		//$mplayer->test_variable = 'case url';
		break;
		
		case 'http':
		$mplayer->prefix = '';
		//$mplayer->test_variable = 'case http';
		break;
		
		case 'lighttpd':
		$mplayer->prefix = '';
		//$mplayer->test_variable = 'case lighttpd';
		break;
		
		case 'rtmp':
		$mplayer->prefix = '';
		//$mplayer->test_variable = 'case rtmp';
		break;
		
		default;
		$mplayer->type = ''; // Prevent failures due to errant parameters getting passed in
		$mplayer->prefix = '';
		//$mplayer->test_variable = 'default';
	}
	return $mplayer;
}

/**
* Assign the correct path to the file parameter (media source) in $mplayer object
*
* @param $mplayer
* @return $mplayer
*/
function mplayer_set_paths($mplayer)
{
	global $CFG;
	
	// Set wwwroot
	$mplayer->wwwroot = $CFG->wwwroot;
	
	// Only need to call time() function once
	$mplayer_time = time();
	
//// --------------------------------------------------------- MEDIA SOURCE ---------------------------------------------------------
	// Check for type
	if($mplayer->type != '')
	{
		$mplayer->type = '&provider='.$mplayer->type; // parameter name has changed to provider
	}
	// Check for streamer
	if($mplayer->streamer != '')
	{
		$mplayer->streamer = '&streamer='.$mplayer->streamer;
	}
	
//// --------------------------------------------------------- PLAYLIST ---------------------------------------------------------
	// Check for playlist
	if($mplayer->playlist == 'none')
	{
		$mplayer->playlist = '';
		$mplayer->playlistsize = '';
		$mplayer->item = '';
		$mplayer->mplayerrepeat = '';
		$mplayer->shuffle = '';
	} else {
		$mplayer->playlist = '&playlist='.$mplayer->playlist;
		// repeat
		if($mplayer->mplayerrepeat != 'none')
		{
			$mplayer->mplayerrepeat = '&repeat='.$mplayer->mplayerrepeat;
		} else {
			$mplayer->mplayerrepeat = '';
		}
		// shuffle
		if($mplayer->shuffle == 'true')
		{
			$mplayer->shuffle = '&shuffle='.$mplayer->shuffle;
		} else {
			$mplayer->shuffle = '';
		}
		// playlistsize
		if($mplayer->playlistsize != '180')
		{
			$mplayer->playlistsize = '&playlistsize='.$mplayer->playlistsize;
		} else {
			$mplayer->playlistsize = '';
		}
		// item
		if($mplayer->item != '0')
		{
			$mplayer->item = '&item='.$mplayer->item;
		} else {
			$mplayer->item = '';
		}
	}
	
//// --------------------------------------------------------- CONFIG XML ---------------------------------------------------------
	// Check for configuration XML file URL
	if($mplayer->configxml != '')
	{
		$mplayer->configxml = '$config='.$mplayer->moodledata.$mplayer->configxml.'?'.$mplayer_time;
	}
	
//// --------------------------------------------------------- APPEARANCE ---------------------------------------------------------
	// Check for skin
	if($mplayer->skin != '')
	{
		$mplayer->skin = '&skin='.$mplayer->wwwroot.'/mod/mplayer/skins/'.$mplayer->skin;
	}
	// Check for image 
	if($mplayer->image != '')
	{
		$mplayer->image = $mplayer->moodledata.$mplayer->image;
		$mplayer->imageprefix = '&image=';
	} else {
		$mplayer->imageprefix = '';
	}
	// Check for icons 
	if($mplayer->icons == 'false')
	{
		$mplayer->icons = '&icons='.$mplayer->icons;
	} else {
		$mplayer->icons = '';
	}
	// Check for controlbar 
	if($mplayer->controlbar != 'bottom')
	{
		$mplayer->controlbar = '&controlbar='.$mplayer->controlbar;
	} else {
		$mplayer->controlbar = '';
	}
	// Check for backcolor
	if($mplayer->backcolor != '')
	{
		$mplayer->backcolor = '&backcolor='.$mplayer->backcolor;
	}
	// Check for frontcolor
	if($mplayer->frontcolor != '')
	{
		$mplayer->frontcolor = '&frontcolor='.$mplayer->frontcolor;
	}
	// Check for lightcolor
	if($mplayer->lightcolor != '')
	{
		$mplayer->lightcolor = '&lightcolor='.$mplayer->lightcolor;
	}
	// Check for screencolor
	if($mplayer->screencolor != '')
	{
		$mplayer->screencolor = '&screencolor='.$mplayer->screencolor;
	}
	// Check for smoothing
	if($mplayer->smoothing == 'false')
	{
		$mplayer->smoothing = '&smoothing='.$mplayer->smoothing;
	} else {
		$mplayer->smoothing = '';
	}
	// Check for quality
	if($mplayer->quality != 'best')
	{
		$mplayer->quality = '&quality='.$mplayer->quality;
	} else {
		$mplayer->quality = '';
	}
	// Check for resizing
	if($mplayer->resizing != '')
	{
		$mplayer->resizing = '&resizing='.$mplayer->resizing;
	}
	// deprecated
	$mplayer->resizing = '';
	
//// --------------------------------------------------------- BEHAVIOUR ---------------------------------------------------------
	// Check for autostart
	if($mplayer->autostart == 'true')
	{
		$mplayer->autostart = '&autostart='.$mplayer->autostart;
	} else {
		$mplayer->autostart = '';
	}
	// Check for stretching
	if($mplayer->stretching != 'uniform')
	{
		$mplayer->stretching = '&stretching='.$mplayer->stretching;
	} else {
		$mplayer->stretching = '';
	}
	// Check for volume
	if($mplayer->volume != '90')
	{
		$mplayer->volume = '&volume='.$mplayer->volume;
	} else {
		$mplayer->volume = '';
	}
	// Check for mute
	if($mplayer->mute == 'true')
	{
		$mplayer->mute = '&mute='.$mplayer->mute;
	} else {
		$mplayer->mute = '';
	}
	// Check for mplayerstart
	if($mplayer->mplayerstart != '0')
	{
		$mplayer->mplayerstart = '&mplayerstart='.$mplayer->mplayerstart;
	} else {
		$mplayer->mplayerstart = '';
	}
	// Check for bufferlength
	if($mplayer->bufferlength != '1')
	{
		$mplayer->bufferlength = '&bufferlength='.$mplayer->bufferlength;
	} else {
		$mplayer->bufferlength = '';
	}
	// Check for plugins
	if($mplayer->plugins != '')
	{
		$mplayer->plugins = '&plugins='.$mplayer->plugins;
	} else {
		$mplayer->plugins = '';
	}
	
//// --------------------------------------------------------- METADATA ---------------------------------------------------------
	// Check for author - author is always present in FlashVars embed code and should start without the & symbol
	if($mplayer->author != '')
	{
		$mplayer->author = 'author='.$mplayer->author;
	}
	// Check for mplayerdate
	if($mplayer->mplayerdate != '')
	{
		$mplayer->mplayerdate = '&date='.$mplayer->mplayerdate;
	}
	// Check for title
	if($mplayer->title != '')
	{
		$mplayer->title = '&title='.$mplayer->title;
	}
	// Check for description
	if($mplayer->description != '')
	{
		$mplayer->description = '&description='.$mplayer->description;
	}
	// Check for tags
	if($mplayer->tags != '')
	{
		$mplayer->tags = '&tags='.$mplayer->tags;
	}

//// --------------------------------------------------------- AUDIO DESCRIPTION ---------------------------------------------------------
	// Check for audiodescriptionfile
	if($mplayer->audiodescriptionfile == '')
	{
		$mplayer->audiodescriptionfile = '';
		$mplayer->audiodescriptionstate = '';
		$mplayer->audiodescriptionvolume = '';
	} else {
		$mplayer->audiodescriptionfile = '&audiodescription.file='.$mplayer->moodledata.$mplayer->audiodescriptionfile;
		$mplayer->audiodescriptionstate = '&audiodescription.state='.$mplayer->audiodescriptionstate;
		$mplayer->audiodescriptionvolume = '&audiodescription.volume='.$mplayer->audiodescriptionvolume;
		// Add the audiodescription plugin
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',audiodescription';
		} else {
			$mplayer->plugins = '&plugins=audiodescription';
		}
	}
	
//// --------------------------------------------------------- CAPTIONS ---------------------------------------------------------
	// Check for captions
	if($mplayer->captionsfile != '')
	{
		// There's a bug in the captions.back parameter so we'll compensate for that
		if($mplayer->captionsback == 'true')
		{
			$mplayer->captionsback = '&captions.back='.$mplayer->captionsback;
		} else {
			$mplayer->captionsback = '';
		}
		$mplayer->captionsfile = '&captions.file='.$mplayer->moodledata.$mplayer->captionsfile;
		$mplayer->captionsfontsize = '&captions.fontsize='.$mplayer->captionsfontsize;
		$mplayer->captionsstate = '&captions.state='.$mplayer->captionsstate; // this doesn't work
		// add captions plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = '&plugins='.$mplayer->plugins.',captions';
		} else {
			$mplayer->plugins = '&plugins=captions';
		}
	} else {
		$mplayer->captionsback = '';
		$mplayer->captionsfile = '';
		$mplayer->captionsfontsize = '';
		$mplayer->captionsstate = '';
	}
	
//// --------------------------------------------------------- HD ---------------------------------------------------------
	// As of 21/01/2010, there's a bug in the HD plugin that prevents switching 
	// between HD and normal when either of the files has downloaded completely
	// Check for hdfile
	if($mplayer->hdfile != '')
	{
		$mplayer->hdbitrate = '&hd.bitrate='.$mplayer->hdbitrate;
		$mplayer->hdfile = '&hd.file='.$mplayer->prefix.$mplayer->hdfile.'?'.$mplayer_time;
		$mplayer->hdfullscreen = '&hd.fullscreen='.$mplayer->hdfullscreen;
		$mplayer->hdstate = '&hd.state='.$mplayer->hdstate;
		// add hd plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',hd';
		} else {
			$mplayer->plugins = '&plugins=hd';
		}
	} else {
		$mplayer->hdbitrate = '';
		$mplayer->hdfile = '';
		$mplayer->hdfullscreen = '';
		$mplayer->hdstate = '';
	}
	// Check for tracecall
	if($mplayer->tracecall != '')
	{
		$mplayer->tracecall = '&tracecall='.$mplayer->tracecall;
	}
	
//// --------------------------------------------------------- INFOBOX ---------------------------------------------------------
	// Check for infobox
	if($mplayer->infoboxposition != 'none')
	{
		$mplayer->infoboxcolor = '&infobox.color='.$mplayer->infoboxcolor;
		$mplayer->infoboxposition = '&infobox.position='.$mplayer->infoboxposition;
		$mplayer->infoboxsize = '&infobox.size='.$mplayer->infoboxsize;
		// add infobox plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',infobox';
		} else {
			$mplayer->plugins = '&plugins=infobox';
		}
	} else {
		$mplayer->infoboxcolor = '';
		$mplayer->infoboxposition = '';
		$mplayer->infoboxsize = '';
	}
	
//// --------------------------------------------------------- LIVESTREAM ---------------------------------------------------------
	// Check for livestream
	if($mplayer->livestreamfile != '')
	{
		$mplayer->livestreamfile = '&livestream.file='.$mplayer->livestreamfile;
		$mplayer->livestreamimage = '&livestream.image='.$mplayer->livestreamimage;
		$mplayer->livestreaminterval = '&livestream.interval='.$mplayer->livestreaminterval;
		$mplayer->livestreammessage = '&livestream.message='.$mplayer->livestreammessage;
		$mplayer->livestreamstreamer = '&livestream.streamer='.$mplayer->livestreamstreamer;
		$mplayer->livestreamtags = '&livestream.tags='.$mplayer->livestreamtags;
		// add livestream plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',livestream';
		} else {
			$mplayer->plugins = '&plugins=livestream';
		}
	} else {
		$mplayer->livestreamfile = '';
		$mplayer->livestreamimage = '';
		$mplayer->livestreaminterval = '';
		$mplayer->livestreammessage = '';
		$mplayer->livestreamstreamer = '';
		$mplayer->livestreamtags = '';
	}
	
//// --------------------------------------------------------- LOGOBOX ---------------------------------------------------------
	// Check for logobox
	if($mplayer->logoboxfile != '')
	{
		$mplayer->logoboxalign = '&logobox.align='.$mplayer->logoboxalign;
		$mplayer->logoboxfile = '&logobox.file='.$mplayer->moodledata.$mplayer->logoboxfile;
		$mplayer->logoboxlink = '&logobox.link='.$mplayer->logoboxlink;
		$mplayer->logoboxmargin = '&logobox.margin='.$mplayer->logoboxmargin;
		$mplayer->logoboxposition = '&logobox.position='.$mplayer->logoboxposition;
		// add logobox plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',logobox';
		} else {
			$mplayer->plugins = '&plugins=logobox';
		}
	} else {
		$mplayer->logoboxalign = '';
		$mplayer->logoboxfile = '';
		$mplayer->logoboxlink = '';
		$mplayer->logoboxmargin = '';
		$mplayer->logoboxposition = '';
	}
	
	// Check for logo
	if($mplayer->logofile != '')
	{
		$mplayer->logofile = '&logo.file='.$mplayer->moodledata.$mplayer->logofile;
		$mplayer->logolink = '&logo.link='.$mplayer->logolink;
		$mplayer->logohide = '&logo.hide='.$mplayer->logohide;
		$mplayer->logoposition = '&logo.position='.$mplayer->logoposition;
	} else {
		$mplayer->logofile = '';
		$mplayer->logolink = '';
		$mplayer->logohide = '';
		$mplayer->logoposition = '';
	}
	
//// --------------------------------------------------------- METAVIEWER ---------------------------------------------------------
	// Check for metaviewer
	if($mplayer->metaviewerposition != '')
	{
		$mplayer->metaviewerposition = '&metaviewer.position='.$mplayer->metaviewerposition;
		$mplayer->metaviewersize = '&metaviewer.size='.$mplayer->metaviewersize;
		// add metaviewer plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',metaviewer';
		} else {
			$mplayer->plugins = '&plugins=metaviewer';
		}
	} else {
		$mplayer->metaviewerposition = '';
		$mplayer->metaviewersize = '';
	}
	
//// --------------------------------------------------------- SEARCHBAR ---------------------------------------------------------
	// Check for searchbar
	if($mplayer->searchbarposition != 'none')
	{
		$mplayer->searchbarlabel = '&searchbar.label='.$mplayer->searchbarlabel;
		$mplayer->searchbarposition = '&searchbar.position='.$mplayer->searchbarposition;
		$mplayer->searchbarscript = '&searchbar.script='.$mplayer->searchbarscript;
		if($mplayer->searchbarcolor != '')
		{
			$mplayer->searchbarcolor = '&searchbar.color='.$mplayer->searchbarcolor;
		} else {
			$mplayer->searchbarcolor = '';
		}
		// if playlist isn't set up, set up a default
		if($mplayer->playlist == '')
		{
			$mplayer->playlist = '&playlist=right';
			$mplayer->playlistsize = '&playlistsize=300';
			$mplayer->item = '&item=0';
		}
		// add searchbar plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',searchbar';
		} else {
			$mplayer->plugins = '&plugins=searchbar';
		}
	} else {
		$mplayer->searchbarcolor = '';
		$mplayer->searchbarlabel = '';
		$mplayer->searchbarposition = '';
		$mplayer->searchbarscript = '';
	}
	
//// --------------------------------------------------------- SNAPSHOT ---------------------------------------------------------
	// Check for snapshotscript
	if($mplayer->snapshotscript != 'none')
	{
		$mplayer->snapshotbitmap = '&snapshot.bitmap='.$mplayer->snapshotbitmap;
		$mplayer->snapshotscript = '&snapshot.script='.$mplayer->snapshotscript.'?id='.$mplayer->instance.'';
		// add snapshot plugin parameter
		if($mplayer->plugins != '')
		{
			$mplayer->plugins = $mplayer->plugins.',snapshot';
		} else {
			$mplayer->plugins = '&plugins=snapshot';
		}
	} else {
		$mplayer->snapshotbitmap = '';
		$mplayer->snapshotscript = '';
	}
	
	return $mplayer;
}

/**
* Print alternative FlashVars embed parameters
*
* @param $mplayer
* @return string
*/
function mplayer_print_body_flashvars($mplayer)
{
	// Build URL to moodledata directory
	$mplayer = mplayer_set_moodledata($mplayer);
	
	// Assign the correct path to the file parameter (media source)
	$mplayer = mplayer_set_type($mplayer);
	
	// Build URLs for FlashVars embed parameters
	$mplayer = mplayer_set_paths($mplayer);
	
	$mplayer_flashvars = '<param name="flashvars" value="'.
				$mplayer->author.
				$mplayer->autostart.
				$mplayer->audiodescriptionfile.
				$mplayer->audiodescriptionstate.
				$mplayer->audiodescriptionvolume.
				$mplayer->backcolor.
				$mplayer->bufferlength.
				$mplayer->captionsback.
				$mplayer->captionsfile.
				$mplayer->captionsfontsize.
				$mplayer->captionsstate.
				$mplayer->configxml.
				$mplayer->controlbar.
				$mplayer->mplayerdate.
				$mplayer->description.
				'&file='.$mplayer->prefix.$mplayer->mplayerfile.
				$mplayer->frontcolor.
				$mplayer->hdbitrate.
				$mplayer->hdfile.
				$mplayer->hdfullscreen.
				$mplayer->hdstate.
				$mplayer->icons.
				$mplayer->imageprefix.$mplayer->image.
				$mplayer->item.
				$mplayer->lightcolor.
				$mplayer->infoboxcolor.
				$mplayer->infoboxposition.
				$mplayer->infoboxsize.
				$mplayer->livestreamfile.
				$mplayer->livestreamimage.
				$mplayer->livestreaminterval.
				$mplayer->livestreammessage.
				$mplayer->livestreamstreamer.
				$mplayer->livestreamtags.
				$mplayer->logoboxalign.
				$mplayer->logoboxfile.
				$mplayer->logoboxlink.
				$mplayer->logoboxmargin.
				$mplayer->logoboxposition.
				$mplayer->logofile.
				$mplayer->logolink.
				$mplayer->logohide.
				$mplayer->logoposition.
				$mplayer->metaviewerposition.
				$mplayer->metaviewersize.
				$mplayer->mute.
				$mplayer->playlist.
				$mplayer->playlistsize.
				$mplayer->plugins.
				$mplayer->mplayerrepeat.
				$mplayer->resizing.
				$mplayer->screencolor.
				$mplayer->searchbarcolor.
				$mplayer->searchbarlabel.
				$mplayer->searchbarposition.
				$mplayer->searchbarscript.
				$mplayer->shuffle.
				$mplayer->skin.
				$mplayer->snapshotbitmap.
				$mplayer->snapshotscript.
				$mplayer->mplayerstart.
				$mplayer->streamer.
				$mplayer->stretching.
				$mplayer->tags.
				$mplayer->title.
				$mplayer->tracecall.
				$mplayer->type.
				$mplayer->volume.'" />';
				
	return $mplayer_flashvars;
}

/**
* Construct Javascript mplayerObject embed code for <head> section of view.php
* Please note: some URLs append a '?'.time(); query to prevent browser caching
*
* @param $mplayer (mdl_mplayer DB record for current mplayer module instance)
* @return string
*/
function mplayer_print_header_js($mplayer)
{
	// Build Javascript code for view.php print_header() function
	$mplayer_header_js = '<script type="text/javascript" src="swfobject/swfobject.js"></script>
		<script type="text/javascript">
			swfobject.registerObject("jwPlayer", "'.$mplayer->fpversion.'");
		</script>';
	// Don't show default dotted outline around Flash Player window in Firefox 3
	$mplayer_header_js .= '<style type="text/css" media="screen">
    		object { outline:none; }
		</style>';
		
	return $mplayer_header_js;
}

/**
* Construct Javascript SWFObject embed code for <body> section of view.php
* Please note: some URLs append a '?'.time(); query to prevent browser caching
*
* @param $mplayer (mdl_mplayer DB record for current mplayer module instance)
* @return string
*/
function mplayer_print_body($mplayer)
{
	//
	$mplayer_body_flashvars = mplayer_print_body_flashvars($mplayer);
	
	$mplayer_body = '<div align="center">
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$mplayer->width.'" height="'.$mplayer->height.'" id="jwPlayer" align="middle">
				<param name="allowfullscreen" value="'.$mplayer->fullscreen.'" />
				<param name="allownetworking" value="all" />
				<param name="allowscriptaccess" value="always" />
				<param name="devicefont" value="true" />
				<param name="menu" value="true" />
				<param name="movie" value="jw/player.swf" />
				<param name="quality" value="'.$mplayer->quality.'" />
				<param name="salign" value="tl" />
				<param name="scale" value="noscale" />
				<param name="seamlesstabbing" value="true" />
				<param name="wmode" value="opaque" />
				'.$mplayer_body_flashvars.'
				<!--[if !IE]>-->
				<object type="application/x-shockwave-flash" data="jw/player.swf" width="'.$mplayer->width.'" height="'.$mplayer->height.'" align="middle">
					<param name="allowfullscreen" value="'.$mplayer->fullscreen.'" />
					<param name="allownetworking" value="all" />
					<param name="allowscriptaccess" value="always" />
					<param name="devicefont" value="true" />
					<param name="menu" value="true" />
					<param name="quality" value="'.$mplayer->quality.'" />
					<param name="salign" value="tl" />
					<param name="scale" value="noscale" />
					<param name="seamlesstabbing" value="true" />
					<param name="wmode" value="opaque" />
					'.$mplayer_body_flashvars.'
				<!--<![endif]-->
					<div align="center">
						<video controls="controls"  height="'.$mplayer->height.'" id="container" poster="'.$mplayer->image.'" width="'.$mplayer->width.'">
							<source src="'.$mplayer->prefix.$mplayer->mplayerfile.'" type="video/mp4" />
							<source src="'.$mplayer->prefix.$mplayer->mplayerfile.'.ogg" type="video/ogg" />
							'.get_string('nohtml5','mplayer').'
						</video>
  						'.get_string('embederror1','mplayer').$mplayer->fpversion.get_string('embederror2','mplayer').'
  						<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" border="0" /></a></p>
						<p><a href="http://matbury.com/" title="Media Player Module developed by Matt Bury">by Matt Bury | matbury.com</a></p>
					</div>
				<!--[if !IE]>-->
				</object>
				<!--<![endif]-->
			</object></div><div><p>'.$mplayer->notes.'</div><br/>';
	
	// For testing
	//$mplayer_body .= '$mplayer->test_variable = '.$mplayer->test_variable.'<br/>$mplayer->prefix = '.$mplayer->prefix.'<br/>$mplayer->mplayerfile = '.$mplayer->mplayerfile.print_object($mplayer);
	
	return $mplayer_body;
}

/*
---------------------------------------- mod_form.php ----------------------------------------
*/

/**
* true/false options
* @return array
*/
function mplayer_list_truefalse()
{
	return array('true' => 'true',
				'false' => 'false');
}

/**
* true/false options
* @return array
*/
function mplayer_list_quality()
{
	return array('best' => 'best',
				'high' => 'high',
				'medium' => 'medium',
				'autohigh' => 'autohigh',
				'autolow' => 'autolow',
				'low' => 'low');
}

/**
* Define target of link when user clicks on 'link' button
* @return array
*/
function mplayer_list_linktarget()
{
	return array('_blank' => 'new window',
				'_self' => 'same page',
				'none' => 'none');
}

/**
* Define type of media to serve
* @return array
*/
function mplayer_list_type()
{
	return array('video' => 'Video',
				'youtube' => 'YouTube',
				'url' => 'Full URL',
				'xml' => 'XML Playlist',
				'sound' => 'Sound',
				'image' => 'Image',
				'http' => 'HTTP (pseudo) Streaming',
				'lighttpd' => 'Lighttpd Streaming',
				'rtmp' => 'RTMP Streaming');
}

/**
* HTTP streaming (Xmoov-php) not yet working!
* 
* For Lighttpd streaming or RTMP (Flash Media Server or Red5),
* enter the path to the gateway in the corresponding empty quotes
* and uncomment the appropriate lines
* e.g. 'path/to/your/gateway.jsp' => 'RTMP');
*
* For RTMP streaming, uncomment and edit this line: //, 'rtmp://yourstreamingserver.com/yourmediadirectory' => 'RTMP'
* to reflect your streaming server's details. It's probably a good idea to change the 'RTMP' bit to the name of your streaming service,
* i.e. 'My Media Server' or 'Acme Media Server'.
* Remember not to include the ".mplayer" file extensions in video file names when using RTMP.
* @return array
*/
function mplayer_list_streamer()
{
	global $CFG;
	return array('' => 'none'
				 //, $CFG->wwwroot.'/mod/mplayer/xmoov/xmoov.php' => 'Xmoov-php (http)'
				 //, 'lighttpd' => 'Lighttpd'
				 //, 'rtmp://yourstreamingserver.com/yourmediadirectory' => 'RTMP'
				 );
}

/**
* List array of available search scripts
* None are provided as yet.
* @return array
*/
function mplayer_list_searchbarscript()
{
	global $CFG;
	return array('' => 'none'
				 , 'http://gdata.youtube.com/feeds/api/videos?vq=QUERY&format=5' => 'YouTube.com Search'
				 //, $CFG->wwwroot.'/mod/mplayer/scripts/search.php' => 'Search Script Label'
				 //, $CFG->wwwroot.'/file.php/'.$COURSE->id.'/scripts/search.php' => 'Search Script Label'
				 );
}

/**
* List array of available search scripts
* None are provided as yet.
* @return array
*/
function mplayer_list_snapshotscript()
{
	global $CFG;
	return array('none' => 'none'
				 , $CFG->wwwroot.'/mod/mplayer/scripts/snapshot.php' => 'Demo Snapshot Script'
				 //, $CFG->wwwroot.'/file.php/'.$COURSE->id.'/scripts/snapshot.php' => 'Snapshot Script Label'
				 );
}

/**
* Define position of player control bar
* @return array
*/
function mplayer_list_controlbar()
{
	return array('bottom' => 'bottom',
				'over' => 'over',
				'none' => 'none');
}

/**
* Define position of playlist
* @return array
*/
function mplayer_list_playlistposition()
{
	return array('bottom' => 'bottom',
				'right' => 'right',
				'over' => 'over',
				'none' => 'none');
}

/**
* Define position of infobox
* @return array
*/
function mplayer_list_infoboxposition()
{
	return array('none' => 'none',
				 'bottom' => 'bottom',
				'over' => 'over',
				'top' => 'top');
}

/**
* Define logobox align
* @return array
*/
function mplayer_list_logoboxalign()
{
	return array('left' => 'left',
				'right' => 'right');
}

/**
* Define position of metaviewer
* @return array
*/
function mplayer_list_metaviewerposition()
{
	return array('' => 'none',
				 'over' => 'over',
				'left' => 'left',
				'right' => 'right',
				'top' => 'top',
				'bottom' => 'bottom');
}

/**
* Define position of searchbar
* @return array
*/
function mplayer_list_searchbarposition()
{
	return array('none' => 'none',
				 'top' => 'top',
				'bottom' => 'bottom');
}

/**
* Define position of searchbar
* @return array
*/
function mplayer_list_logoposition()
{
	return array('bottom-left' => 'bottom-left',
				 'bottom-right' => 'bottom-right',
				 'top-left' => 'top-left',
				'top-right' => 'top-right');
}

/**
* Skins define the general appearance of the JW FLV Player
* Skins can be downloaded from: http://www.longtailvideo.com/addons/skins
* Skins (the .swf file only) are kept in /mod/mplayer/skins/
* New skins must be added to the array below manually for them to show up on the mod_form.php list.
* Copy and paste the following line into the array below then edit it to match the name and filename of your new skin:
				'filename.swf' => 'Name',
* I find alphabetical order works best ;)
* @return array
*/
function mplayer_list_skins()
{
	return array('' => '',
				'beelden/beelden.xml' => 'Beelden XML Skin',
				'3dpixelstyle.swf' => '3D Pixel Style',
				'atomicred.swf' => 'Atomic Red',
				'bekle.swf' => 'Bekle',
				'bluemetal.swf' => 'Blue Metal',
				'comet.swf' => 'Comet',
				'controlpanel.swf' => 'Control Panel',
				'dangdang.swf' => 'Dangdang',
				'fashion.swf' => 'Fashion',
				'festival.swf' => 'Festival',
				'grungetape.swf' => 'Grunge Tape',
				'icecreamsneaka.swf' => 'Ice Cream Sneaka',
				'kleur.swf' => 'Kleur',
				'magma.swf' => 'Magama',
				'metarby10.swf' => 'Metarby 10',
				'modieus.swf' => 'Modieus',
				'nacht.swf' => 'Nacht',
				'neon.swf' => 'Neon',
				'pearlized.swf' => 'Pearlized',
				'pixelize.swf' => 'Pixelize',
				'playcasso.swf' => 'Playcasso',
				'silverywhite.swf' => 'Silvery White',
				'simple.swf' => 'Simple',
				'snel.swf' => 'Snel',
				'stijl.swf' => 'Stijl',
				'stylish_slim.swf' => 'Stylish Slim',
				'traganja.swf' => 'Traganja');
}

/**
* Define number of seconds of video stream to buffer before playing
* Longer buffer lengths can be given if a lot of users have particularly slow Internet connections
* @return array
*/
function mplayer_list_bufferlength()
{
	return array('0' => '0',
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
				'6' => '6',
				'7' => '7',
				'8' => '8',
				'9' => '9',
				'10' => '10',
				'11' => '11',
				'12' => '12',
				'13' => '13',
				'14' => '14',
				'15' => '15',
				'16' => '16',
				'17' => '17',
				'18' => '18',
				'19' => '19',
				'20' => '20',
				'21' => '21',
				'22' => '22',
				'23' => '23',
				'24' => '24',
				'25' => '25',
				'26' => '26',
				'27' => '27',
				'28' => '28',
				'29' => '29',
				'30' => '30');
}

/**
* Define action when user clicks on video
* @return array
*/
function mplayer_list_displayclick()
{
	return array('play' => 'play',
				'link' => 'link',
				'fullscreen' => 'fullscreen',
				'none' => 'none',
				'mute' => 'mute',
				'next' => 'next');
}

/**
* Define playlist repeat behaviour
* @return array
*/
function mplayer_list_repeat()
{
	return array('none' => 'none',
				 'list' => 'list',
				'always' => 'always',
				'single' => 'single');
}

/**
* Define scaling properties of video stream
* i.e. the way the video adjusts its dimensions to fit the FLV player window
* @return array
*/
function mplayer_list_stretching()
{
	return array('none' => 'none',
				 'uniform' => 'uniform',
				'exactfit' => 'exactfit',
				'fill' => 'fill');
}

/**
* Define default playback volume
* @return array
*/
function mplayer_list_volume()
{
	return array('0' => '0',
				'5' => '5',
				'10' => '10',
				'15' => '15',
				'20' => '20',
				'25' => '25',
				'30' => '30',
				'35' => '35',
				'40' => '40',
				'45' => '45',
				'50' => '50',
				'55' => '55',
				'60' => '60',
				'65' => '65',
				'70' => '70',
				'75' => '75',
				'80' => '80',
				'85' => '85',
				'90' => '90',
				'95' => '95',
				'100' => '100');
}
/// End of mod/mplayer/lib.php

?>