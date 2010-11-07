<?php
/// Original DFwiki created by David Castro, Ferran Recio and Marc Alier.
/// Library of functions and constants for module wiki

//------- EXTENDED FILES MANAGEMENT ------------
//this functions are extrated from Por-Dulor project created by Ferran Recio
//distributed under GPL licence

/**
 * return the number of files in a directory
 * @param String $path: path to dir
 * @param String $ext=false: just files with a specific extension
 * @return int
 */
function wiki_count_files ($path,$ext=false){
	if (!is_dir($path)) return false;
	//result var
	$res = 0;
	//read directory
	if (!$dir = opendir($path)) return false;
	while ($file = readdir($dir)){
		//just ignore special dirs
		if ($file==='.' || $file==='..') continue;
		if ($ext==false){
			$res++;
		} else {
			if (strpos($file,$ext)===strlen($file)-strlen($ext)){
				$res++;
			}
		}
	}
	return $res;
}

/**
 * return an array with all file names in a directory
 * 
 * @param String path: el directori a escanejar
 * @param String ext=false: just files with an specific extension
 * @param boolean dirs=false: if directories must appear in list
 * @return array of strings (if dir doesn't exists)
 */
function wiki_dir_files ($path,$ext=false,$dirs=false){
	if (!is_dir($path)) return false;
	$res = array();
	//read directory
	if (!$dir = opendir($path)) return false;
	while ($file = readdir($dir)){
		//just ignore special dirs
		if ($file==='.' || $file==='..') continue;
		//look for directories
		if (is_dir($path.'/'.$file) && $dirs===false) continue;
		if ($ext==false){
			$res[] = $file;
		} else {
			if (strpos($file,$ext)===strlen($file)-strlen($ext)){
				$res[] = $file;
			}
		}
	}
	return $res;
}

/**
 * return a list of subdirectories in a directory
 * @param String $path: dir path
 * @return array of Strings (false id $path doesn't exists)
 */
function wiki_dir_dirs ($path){
	if (!is_dir($path)) return false;
	$res = array();
	//read directory
	if (!$dir = opendir($path)) return false;
	while ($file = readdir($dir)){
		//just ignore special dirs
		if ($file==='.' || $file==='..') continue;
		//look for directories
		if (is_dir($path.'/'.$file)) $res[]=$file;
	}
	return $res;
}

//---------------- wiki url defining ------------

/**
 * format an url string with current wiki params. Special marks are:
 * $baseurl: base wiki url
 * $wwwroot: $CFG->wwwroot
 * $id: cm id
 * $a: instance id
 * $courseid: course id
 * $pagename
 * $gid: group id
 * $uid: user id
 * $wikibook: wikibook
 * $basic: concats id,gid,uid and wikibook in one get param
 * $pageselector: concats id, pagename, gid, uid and wikibook in one get param
 * @param String $url: url string with special marks
 * @param Object $dat=false: an object containing alternative values for any special mark
 * For Example: $dat->pagename='first' will use 'first' intead of current page name
 */
function wiki_format_url ($url,$dat=false) {
	global $CFG;
	//define all mark posibilities
	$baseurl = (isset($dat->baseurl))?$dat->baseurl:$CFG->wwwroot.'/mod/wiki';
	$wwwroot =  (isset($dat->wwwroot))?$dat->wwwroot:$CFG->wwwroot;
	$id = (isset($dat->id))?$dat->id:wiki_param('id');
	$a = (isset($dat->a))?$dat->a:wiki_param('a');
	
	$course = wiki_param('course');
	$courseid = (isset($dat->courseid))?$dat->courseid:$course->id;
	$pagename = (isset($dat->pagename))?$dat->pagename:wiki_param('page');
	$pagename = urlencode($pagename);
	
	$groupmember = wiki_param('groupmember');
	$member = wiki_param('member');
	$gid = (isset($dat->gid))?$dat->gid:$groupmember->groupid;
	$uid = (isset($dat->uid))?$dat->uid:$member->id;
	$wikibook = (isset($dat->basic))?$dat->basic:wiki_param('wikibook');
	if ($wikibook) $wikibook = urlencode($wikibook);
	if (isset($dat->basic)) {
		$basic = $dat->basic;
	} else {
		$basic = "id=$id&amp;gid=$gid&amp;uid=$uid";
		if ($wikibook) $basic.="&amp;wikibook=$wikibook";
	}
	$pageselector = (isset($dat->pageselector))?$dat->pageselector:$basic.'&amp;page='.$pagename;

	//parse url
	$res = '';
	eval ('$res = "'.$url.'";');
	//$res = urlencode ($res);
	//return urlencode ($res);
	return $res;
}

//--------------------- WIKI PARAMS AND CACHE FUNCTIONS -----------------------

/**
 * gets/sets a wiki_param variable. Also can execute some special actions
 * over a wiki_param (defined by $special param):
 *   'unset' => unsets a wiki_param
 *   'print' or 'print_object': do a print_object of a wiki_param
 *   'isset' => will returns only true or false if a wiki_param is set
 *  'set_info' => load main info into global structure
 *  'all_ws' => (deprecated) returns all variables in a single object
 * 
 * @param String $name: wiki_param name
 * @param mixed $value=null: assign wiki_param value
 * @param Stirng $special=false: some special actions
 * @return mixed: wiki_param value or null if it's not set
 */
function wiki_param ($name,$value=null,$special = false) {
	global $WS;
	
	if (!isset($WS)) {
		$WS = new storage();
		$WS->recover_variables();
	}
	if ($special == 'set_info') $WS->set_info();
	
	//this is a deprecated line and will be removed shortly
	if ($special == 'all_ws') return $WS;
	
	if (!$name) return null;
	if ($value!==null) {
		$WS->{$name} = $value;
	}
	//special actions
	switch ($special) {
		case 'isset':
			return isset($WS->{$name});
			break;
		case 'unset':
			if (isset($WS->{$name})) unset($WS->{$name});
			break;
		case 'print':
		case 'print_object':
			if (isset($WS->{$name})) {
				print_object();
			} else {
				echo '$WS->'.$name.' is not set!';
			}
			break;
	}
	if (!isset($WS->{$name})) return null;
	return $WS->{$name};
}

//-------------------- wiki callback functions -----------------------

/**
 * adds a callback funcition
 * @param String $breakname: identifier of the breakname. Some possibilities are:
 *     - dfsetup: when program executes dfsetups
 * @param String $functionname: function callback name
 */
function wiki_add_callback ($breakname,$functionname) {
	$callbacks = wiki_param ('wiki_callback');
	if (!is_array($callbacks)) $callbacks = array();
	if (!isset($callbacks[$breakname])) $callbacks[$breakname] = array();
	$moment = $callbacks[$breakname];
	$moment[] = $functionname;
	$callbacks[$breakname] = $moment;
	wiki_param ('wiki_callback',$callbacks);
}

/**
 * return a moment callbacks list
 * @param String $breakname: identifier of the breakname
 * @return Array of Strings
 */
function wiki_get_callbacks ($breakname) {
	$callbacks = wiki_param ('wiki_callback');
	$res = array();
	if (!is_array($callbacks)) return $res;
	if (!isset($callbacks[$breakname])) return $res;
	return $callbacks[$breakname];
}

/**
 * execute a callback moment
 */
function wiki_execute_callbacks ($breakname) {
	$callbacks = wiki_get_callbacks ($breakname);
	foreach ($callbacks as $callback) {
		if (function_exists($callback)) {
			$callback ();
		}
	}
}
?>