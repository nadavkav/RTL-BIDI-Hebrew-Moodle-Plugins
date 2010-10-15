<?php

	
if(!isguestuser() && isloggedin() && !empty($_COOKIE)) {
	if (isset($_SERVER['HTTPS'])) {
        $protocol = ($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
    } else if (isset($_SERVER['SERVER_PORT'])) { # Apache2 does not export $_SERVER['HTTPS']
        $protocol = ($_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://';
    } else {
        $protocol = 'http://';
    }

	if(stripos($CFG->wwwroot, $protocol) === 0) {
		$study_notes_module_name = "studynotes";

		global $mediabirdDb,$id;

		$mods = unserialize((string)$COURSE->modinfo);
		$siteMods = unserialize((string)get_site()->modinfo);
		if(!empty($siteMods)) {
			$mods = array_merge($siteMods, $mods);
		}
		
		foreach ($mods as $mod_candidate) {
			if ($mod_candidate->mod == $study_notes_module_name && !isset($mediabirdDb)) {
				$mod = $mod_candidate;
				break;
			}
		}
	}
}

if (isset($mod)) :

$pageUrl = $CFG->pagepath."?id=".$id;

$titleMb = $title;

if(strlen($titleMb)>80) {
	$titleMb = substr($titleMb,0,77)."...";
}

$frameUrl = $CFG->wwwroot.'/mod/'.$mod->mod.'/view.php?id='.$mod->cm.
			'&frame=true'.
			'&mb_url='.urlencode($pageUrl).
			'&mb_title='.urlencode($titleMb);

$lang=current_language();
if(strlen($lang)>2) {
	$lang=substr($lang,0,2);
}

include_once($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR.$study_notes_module_name.DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR.'config_default.php');
include_once($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR.$study_notes_module_name.DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR.'config.php');
include_once($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR.$study_notes_module_name.DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR.'utility.php');
include_once($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR.$study_notes_module_name.DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR.'db_moodle.php');
include_once($CFG->dirroot.DIRECTORY_SEPARATOR."mod".DIRECTORY_SEPARATOR.$study_notes_module_name.DIRECTORY_SEPARATOR."server".DIRECTORY_SEPARATOR.'helper.php');

$helper=new MediabirdHtmlHelper();

$moodleid=$USER->id;
if($account_link=get_record("studynotes_account_links", "system", "moodle", "external_id", $moodleid)) {
	$mbuser = $account_link->internal_id;
	
	$mediabirdDb = new MediabirdDboMoodle();
	list($myCards,$theirCards) = $helper->findRelatedNotes($pageUrl,$mbuser);
}
	
if(is_array($myCards) && count($myCards)>0) {
	$titleString = get_string('edit_my_notes','studynotes');
	$iconPath = '/mod/studynotes/ext/launcher-own.png';
	$frameUrl.="&mb_card_id=".$myCards[0];
}
else if(is_array($theirCards) && count($theirCards)>0) {
	$titleString = get_string('edit_their_notes','studynotes');
	$iconPath = '/mod/studynotes/ext/launcher-friends.png';
	$frameUrl.="&mb_card_id=".$theirCards[0];
}
else {
	$titleString = get_string('take_notes','studynotes');
	$iconPath = '/mod/studynotes/ext/launcher-new.png';
}

?>
<link
	rel="stylesheet"
	href="<?php echo $CFG->wwwroot.'/mod/studynotes/css/overlay.css' ; ?>" />
<script type="text/javascript"
	src="<?php echo $CFG->wwwroot.'/mod/studynotes/js/overlay.js'; ?>">
</script>
<a class="mediabird-overlay-link" href="javascript:void(0)"
	id="mediabirdLink"><img src="<?php echo $CFG->wwwroot.$iconPath; ?>"
	alt="Mediabird" title="<?php echo $titleString; ?>" /></a>
<div id="mediabirdOverlay" class="mediabird-overlay">
<div class="bar"><a href="javascript:void(0)" class="closer"
	title="<?php echo get_string('save_and_close','studynotes'); ?>"></a> <a
	href="javascript:void(0)" class="expander expanded"
	title="<?php echo get_string('change_size','studynotes'); ?>"></a></div>
<div class="resize-handle"></div>
<div class="resize-handle right"></div>
<iframe src="" frameborder="no" scrolling="no" id="mediabirdFrame"></iframe>
</div>
<script type="text/javascript">
    //<![CDATA[
    var url = "<?php echo $frameUrl; ?>";
    mbOverlay.MAX_HEIGHT = 464;
	mbOverlay.MAX_WIDTH = 660;
	mbOverlay.SIZE_SECURE = 4;
	mbOverlay.doIframe(url, document.getElementById("mediabirdLink"), document.getElementById("mediabirdOverlay"), {
		width: 660,
		height: 464
	}, document.getElementById("mediabirdFrame"));
    //]]>
</script>
<?php endif; ?>
