<?php
// $Id: view.php,v 1.21 2009/08/10 19:26:21 fabiangebert Exp $
/**
 * This page prints a particular instance of studynotes
 *
 * @author
 * @version $Id: view.php,v 1.21 2009/08/10 19:26:21 fabiangebert Exp $
 * @package studynotes
 **/

require_once ("../../config.php"); //this is not the Mediabird config
require_once ("lib.php");

require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR."moodle_auth.php");
require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR."config_default.php");
require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR."config.php");
require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR."utility.php");
require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR."db_moodle.php");
require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR."helper.php");

function studynotes_handle_session($action, $auth) {
	global $CFG, $cm;

	require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR."data_handling.php");
	require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR."filterlib".DIRECTORY_SEPARATOR."HTMLPurifier.standalone.php");
	require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR."equationsupport".DIRECTORY_SEPARATOR."LaTeXrender.php");
	require_once ($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR."session_handler.php");


	$data = ($action == "upload"?$_POST:$_POST['data']);

	$ignoreQuotes = true;
	foreach ($_POST['data'] as $key=>$value) {
		if (!get_magic_quotes_gpc()) {
			$data[$key] = stripslashes($value);
		}
	}


	if ($action == "upload") {
		$topic = $data['topic'];
			
		$hasAccess = MediabirdUtility::checkAccess($topic, $auth->userId);
		if ($hasAccess) {
			$userQuota = MediabirdUtility::getUserQuota($auth->userId);
			$quotaLeft = MediabirdUtility::quotaLeft($auth->userId, $userQuota);

			//determine folder path
			$folder = MediabirdConfig::$uploads_folder.$auth->userId.DIRECTORY_SEPARATOR;

			$prefix = MediabirdConfig::$uploads_folder;

			$key = "file";

			$name = $_FILES[$key]['name'];

			$_FILES[$key]['name'] = MediabirdUtility::getFreeFilename($folder);

			$info = MediabirdUtility::storeUpload($key, $folder, $quotaLeft, $prefix);

			if ( isset ($info['filename']) && strlen($info['filename']) > 0) {
				if($id = MediabirdUtility::recordFile($info['filename'],0,$auth->userId,$topic)) {
					$info['filename'] = 'view.php?action=download&id='.$cm->id.'&did='.$id;
				}
				else {
					$info['filename'] = null;
					$info['error'] = "database error";
				}
			}
			else {
				$info['filename'] = null;
			}
		}
		else {
			$info['filename'] = null;
			$info['error'] = "invalidtopic";
		}

		echo MediabirdUtility::generateUploadHtml($info['filename'], $info['error']);

		exit ();
	}

	if ($action == "download") {
		$id = $_GET['did'];

		if ( isset ($id)) {
			if ($upload_info = get_record("studynotes_uploads", "id", $id)) {
				$topicId = $upload_info->topic_id;

				$hasAccess = MediabirdUtility::checkAccess($topicId, $auth->userId);

				if ($hasAccess) {
					MediabirdUtility::readUpload($upload_info->filename, $upload_info->type);
				}
			}
		}
		exit ();
	}

	$handler = new MediabirdSessionHandler();

	$reply = $handler->process($action, $auth, $data);

	if ( isset ($reply->filename) && isset($reply->success) && isset($reply->topic)) {
		if($id = MediabirdUtility::recordFile($reply->filename,0,$auth->userId,$reply->topic)) {
			$reply->filename = 'view.php?action=download&id='.$cm->id.'&did='.$id;
		}
		else {
			$reply->success = false;
			$reply->error = "database error";
		}
	}


	header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate;");
	header("Pragma: no-cache;");
	header('Content-Type: application/json;');
	return json_encode($reply);
}

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a = optional_param('a', 0, PARAM_INT); // studynotes ID

if ($id) {
	if (!$cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect");
	}

	if (!$course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}

	if (!$studynotes = get_record("studynotes", "id", $cm->instance)) {
		error("Course module is incorrect");
	}

}
else {
	if (!$studynotes = get_record("studynotes", "id", $a)) {
		error("Course module is incorrect");
	}
	if (!$course = get_record("course", "id", $studynotes->course)) {
		error("Course is misconfigured");
	}
	if (!$cm = get_coursemodule_from_instance("studynotes", $studynotes->id, $course->id)) {
		error("Course Module ID was incorrect");
	}
}
require_login($course->id);

if (isguestuser() || !isloggedin()) {
	error(get_string('login_required', 'studynotes'));
	return;
}

if(empty($_COOKIE)) {
	error(get_string('cookies_required', 'studynotes'));
	return;
}

if(!version_compare(PHP_VERSION,'5.2.0','>=')) {
	error(get_string('wrong_php_version', 'studynotes'));
	return;
}

$moodleid = $USER->id;
$email = $USER->email;
$fullname = $USER->firstname.' '.$USER->lastname;
if (strlen(trim($fullname)) == 0) {
	$fullname = $USER->username;
}

$mediabirdDb = new MediabirdDboMoodle();

$helper = new MediabirdHtmlHelper();


unset ($action);
if ( isset ($_POST["data"]) && isset ($_POST["data"]["action"])) {
	$action = $_POST["data"]["action"];
}
else if ( isset ($_GET["action"])) {
	$action = $_GET["action"];
}

if ($account_link = get_record("studynotes_account_links", "system", "moodle", "external_id", $moodleid)) {
	$mbuser = $account_link->internal_id;
	if (! isset ($action)) {
		$helper->updateUser($mbuser, $fullname, 1, $email);
	}
}
else {
	if ($mbuser = $helper->registerUser($fullname, 1, $email)) {
		$account_link = (object)null;
		$account_link->external_id = $moodleid;
		$account_link->internal_id = $mbuser;
		$account_link->system = "moodle";
		insert_record("studynotes_account_links", $account_link, false);
	}
	else {
		error(get_string('error_linking', 'studynotes'));
	}
}

$auth = new MediabirdMoodleAuth($mbuser);

//set up config
MediabirdConfig::$latex_path = $CFG->studynotes_latex_path;
MediabirdConfig::$convert_path = $CFG->studynotes_dvipng_path;

//set up proxy
if(isset($CFG->proxyhost) && strlen($CFG->proxyhost)>0 && (!isset($CFG->proxytype) || $CFG->proxytype == 'HTTP')) {
	MediabirdConfig::$proxy_address = $CFG->proxyhost;
	MediabirdConfig::$proxy_port = $CFG->proxyport;
}


MediabirdConfig::$uploads_folder = $CFG->dataroot.DIRECTORY_SEPARATOR."1".DIRECTORY_SEPARATOR."moddata".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR."uploads".DIRECTORY_SEPARATOR;
if (!file_exists(MediabirdConfig::$uploads_folder.$auth->userId)) {
	make_mod_upload_directory(1);
	make_upload_directory("1/moddata/studynotes/uploads/".$auth->userId); // we store our images in a subfolder in here
}

MediabirdConfig::$cache_folder = $CFG->dataroot.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."studynotes".DIRECTORY_SEPARATOR;
if (!file_exists(MediabirdConfig::$cache_folder)) {
	make_upload_directory("temp/studynotes");
}


if ( isset ($action)) {
	if ($action == "changePass" || $action == "deleteAccount") {
		exit ;
	}

	if ($action == "load") {
		$urlToLoad = MediabirdUtility::getArgNoSlashes($_GET['url']);

		$html = MediabirdUtility::loadUrl($urlToLoad);
		if ($html == null) {
			echo $COULD_NOT_RETRIEVE_LABEL.$urlToLoad;
		}
		else {
			echo $html;
		}
	}
	else {
		echo studynotes_handle_session($action, $auth);
	}
	exit ;
}

add_to_log($course->id, "studynotes", "view", "view.php?id=$cm->id", "$studynotes->id");

//set up plugins and markers from settings
if ( isset ($CFG->studynotes_markers_available) && strlen($CFG->studynotes_markers_available) > 0) {
	$markersRaw = explode(",", $CFG->studynotes_markers_available);
	$markers = array ();
	foreach ($markersRaw as $marker) {
		if($marker != "importance" && $marker != "translation") {
			$markers[] = 'client.markers.'.ucfirst($marker).'Marker';
		}
	}
	$helper->defaultOptions['markerPlugins'] = $markers;
}

if ( isset ($CFG->studynotes_plugins_available) && strlen($CFG->studynotes_plugins_available) > 0) {
	$pluginsRaw = explode(",", $CFG->studynotes_plugins_available);
	$plugins = array ();

	foreach ($pluginsRaw as $plugin) {
		$plugins[] = 'client.pageplugins.displayplugins.'.ucfirst($plugin);
	}
	$helper->defaultOptions['displayPlugins'] = $plugins;
}

if (! isset ($_GET["frame"])) {
	/// Print the page header
	$strstudynotess = get_string("modulenameplural", "studynotes");
	$strstudynotes = get_string("modulename", "studynotes");

	$navigation = format_string($studynotes->name);
	print_header_simple(format_string($studynotes->name), "", $navigation, "", "", true, '', navmenu($course, $cm));
	
	$html  = '<iframe src="'.$CFG->wwwroot.'/mod/studynotes/view.php?id='.$id.'&frame=true&nored=true" style="width: 100%; height: 540px;" scrolling="no" frameborder="no"></iframe>';
	echo $html;
	print_footer($course);
}
else {
	
	//set options for overlay
	if(!isset($_GET["nored"])) {
		$helper->defaultOptions['reduceFeatureSet'] = true;
		$fullUrl = $CFG->wwwroot.'/mod/studynotes/view.php?id='.$cm->id;
		$helper->defaultOptions['fullLocationFromOverlay'] = $fullUrl;
	}

	
	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
	$html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
	$html .= '<head>'."\n";
	$html .= '<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8"/>'."\n";
	$html .= '<title>Mediabird Web2.0-Learning</title>'."\n";
	$html .= '<link type="text/css" rel="stylesheet" href="ext/design.css" />';
	$html .= '<link type="text/css" rel="stylesheet" href="css/style.css" />';
	$html .= '<script type="text/javascript" src="js/jquery.js"></script>';
	$html .= '<script type="text/javascript" src="js/client.js"></script>';
	
	$lang = current_language();
	
	if (strlen($lang) > 2) {
		$lang = substr($lang, 0, 2);
	}
	if ($lang == "de") {
		$helper->defaultOptions['language'] = MediabirdHtmlHelper::langGerman;
	}
	else if ($lang == "es") {
		$helper->defaultOptions['language'] = MediabirdHtmlHelper::langSpanish;
	}
	
	//fixme: provide help for spanish (let users translate help.en.js)
	$loadHelp = isset($_GET["nored"]) && ($lang=="de" || $lang=="es" || $lang=="en") && (!isset($CFG->studynotes_show_help) || $CFG->studynotes_show_help);
	if($loadHelp) {
		$html .= '<script type="text/javascript" src="js/help.'.$lang.'.js"></script>';
	}
	$html .= '</head>'."\n";
	if(isset($_GET["nored"])) {
		$html .= '<body class="margin">'."\n";
	}
	else {
		$html .= '<body>'."\n";
	}
	$html .= '<div id="mediabirdContainer"></div>'."\n";
	
	if(isset($_GET["nored"])) {
		$html .= '<div class="subcontainer">'."\n";
		$html .= '<p>&copy; 2008-2009 <a href="http://www.mediabird.net/" target="_blank" title="Mediabird Homepage">Mediabird</a>. All rights reserved. Version 0.5.6</p>'."\n";
		$html .= '</div>'."\n";
	}
	
	$helper->loadUser($auth);



	$options = array (
		'dummyPath'=>'dummy.php',
		'loadPath'=>'view.php?action=load&id='.$cm->id.'&url=',
		'uploadPath'=>'view.php?action=upload&id='.$cm->id.'',
		'prefixData'=>true,
		'loadLogon'=>false,
		'furtherArgs'=>'sessionPath: "view.php?id='.$cm->id.'"',
		'imagePath'=>'images/',
		'feedbackPath'=>'mailto:team@mediabird.net',
		'SHIFT_RESEARCH_RIGHT' => 260,
		'SHIFT_RESEARCH_LEFT' => 160
		);
	
	if ($loadHelp) {
		$options = array_merge(
			$options,
			array(
				'SHIFT_RESEARCH_RIGHT' => 124,
				'SHIFT_RESEARCH_RIGHT_WIDE' => 324,
				'SHIFT_RESEARCH_LEFT' => 132
			)
		);
	}

	$options['linkPrefix'] = $CFG->wwwroot;
	$options['linkTarget'] = '_parent';
	
	if ( isset ($_GET["frame"]) && isset ($_GET["mb_url"])) {
		$options['linkUrl'] = $_GET["mb_url"];
		
		$options['linkTitle'] = isset($_GET["mb_title"]) ? $_GET["mb_title"] : get_string('moodle_ref_title', 'studynotes'); 
	}
	if ( isset ($_GET["frame"]) && isset ($_GET["mb_card_id"])) {
		$options['loadCard'] = intval($_GET["mb_card_id"]);
	}

	$script = $helper->bodyScript($options);
	
	if($loadHelp) {
		$script .='<script type="text/javascript">'."\n";
		$script .='//<![CDATA['."\n";
		$script .='(new mediabird.help()).load($("#mediabirdContainer div.mb-cont").css("left",-40));'."\n";
		$script .='//]]>'."\n";
		$script .='</script>'."\n";
	}
	
	$html .= $script."\n";
	$html .= '</body>'."\n";
	echo $html;
}
?>
