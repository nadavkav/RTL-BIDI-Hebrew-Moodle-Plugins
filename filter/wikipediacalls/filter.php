<?php // $id$
//////////////////////////////////////////////////////////////
// 
//  This filter allows making calls to wikipedia.
//
//////////////////////////////////////////////////////////////

/// This is the filtering function itself.  It accepts the 
/// courseid and the text to be filtered (in HTML form).

function wikipediacalls_filter($courseid, $text) {
	global $USER;
	global $CFG;
	global $wikiBlockCount;
	
	if ($CFG->release < '1.6'){
        $CFG->filter_wikipediacalls_showkeys = 1;
	}

	// Filters special tags inserts
	    // get word before [WP] marker
       // replace word and marker by wikipedia link (language aware) 
    $lang = substr(@$USER->lang, 0, 2);
    if ($lang == '') $lang = "fr";
	
	// this collects all wikipedia keys for reporting
	if (isediting($courseid, 0) && $CFG->filter_wikipediacalls_showkeys){
	      $CFG->currenttextiscacheable = true;
	      $WPKeys = array();
	 	  preg_match_all("/([^ \.\'`\"\(\)\[\]<>;:]+)\[WP\]/", $text, $matches, PREG_PATTERN_ORDER);
	 	  foreach($matches[1] as $aMatch)
	 	  	 $WPKeys[urlencode($aMatch)] = "http://{$lang}.wikipedia.org/wiki/" . urlencode($aMatch);
	 	  preg_match_all("/([^ \.\'`\"\(\)\[\]<>;:]+)\[WP\|([^|]*?)\|([^\]]*?)\]/", $text, $matches, PREG_PATTERN_ORDER);
	 	  for($i = 0 ; $i < count($matches[2]) ; $i++ ){
	 	  	 $WPKeys[urlencode($matches[2][$i])] = "http://{$matches[3][$i]}.wikipedia.org/wiki/" . urlencode($matches[2][$i]);
	 	  	 $i++;
	 	  }
	 	  preg_match_all("/([^ \.\'`\"\(\)\[\]<>;:]+)\[WP\|([^|]*?)\]/", $text, $matches, PREG_PATTERN_ORDER);
	 	  foreach($matches[2] as $aMatch){
	 	  	 $WPKeys[urlencode($aMatch)] = "http://{$lang}.wikipedia.org/wiki/" . str_replace("+", "_", urlencode($aMatch));
	 	  }
	 }

	// this inserts any wikipedia calls
	 $text = preg_replace("/([^ \.\'`\"\(\)<>;:\[\]]+)\[WP\]/", "<a href=\"http://{$lang}.wikipedia.org/wiki/\\1\" target=\"_blank\">\\1</a>", $text); 
	 $text = preg_replace("/([^ \.\'`\"\(\)<>;:\[\]]+)\[WP\|([^|\]]+)\|([^|\]]+)\]/", "<a href=\"http://\\3.wikipedia.org/wiki/\\2\" target=\"_blank\">\\1</a>", $text); 
	 $text = preg_replace("/([^ \.\'`\"\(\)<>;:\[\]]+)\[WP\|([^\]]+)\]/", "<a href=\"http://{$lang}.wikipedia.org/wiki/\\2\" target=\"_blank\">\\1</a>", $text); 
     $text = preg_replace("/\[WP\]/", '', $text);

	// this prepare wikipedia reports and testing invocator
	 if (isediting($courseid, 0) && $CFG->filter_wikipediacalls_showkeys){
	 	 if (count($WPKeys)){
	 	 	 $text = $text . "<br>" . get_string('wikipediakeys', 'wikipediacalls') . " : <br>" . implode('<br>', $WPKeys);
	 	 	 
	 	 	 // pass all keys and call data to session for checking
	 	 	 if (!isset($wikiBlockCount)) $wikiBlockCount = 0;
	 	 	 $_SESSION['wikipediaKeys'][$wikiBlockCount] = $WPKeys;

			 // if link code is not loaded, load link code
			 // REM : no include can be used here as effective code production is delayed
	 	 	 if (!isset($CFG->testCallsLink))
	 	 	 	$CFG->testCallsLink = mb_convert_encoding(implode('', file("{$CFG->dirroot}/filter/wikipediacalls/testWikipediaCallsLink.tpl")), "ASCII", "auto");
	 	 	 $testCallsLink = str_replace('<%%WWWROOT%%>', $CFG->wwwroot, $CFG->testCallsLink);
	 	 	 $testCallsLink = str_replace('<%%TESTLINKLABEL%%>', get_string('wikipediacallslinklabel', 'wikipediacalls'), $testCallsLink);
	 	 	 $testCallsLink = str_replace('<%%WIKIBLOCKID%%>', $wikiBlockCount, $testCallsLink);
	 	 	 $text = $text . $testCallsLink; 
	 	 	 $wikiBlockCount++;
	 	 }
	 }

    // special views for developper
    if (isadmin()){
	     // $CFG->currenttextiscacheable = false; // huge overhead in computing time
		 if (preg_match("/\[MOODLE_CFG\]/", $text)){
		 	print_r($CFG);
		 }
		 if (preg_match("/\[MOODLE_USER\]/", $text)){
		 	print_r($USER);
		 }
		 if (preg_match("/\[MOODLE_SESSION\]/", $text)){
		 	print_r($_SESSION);
		 }
    }

    return $text;  // Look for all these words in the text
}


?>
