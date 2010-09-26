<?php //$Id: filter.php,v 0.1 2009/11/23 17:12:30 stronk7 Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2001-3001 Martin Dougiamas        http://dougiamas.com  //
//           (C) 2001-3001 Eloy Lafuente (stronk7) http://contiento.com  //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

// This filter allows you to play an audio sound of a word in the text
// based on www.yourdictionary.com service (free dictionary)
//
// Syntax for filter is:
//     [[sayit:EnglishWord]]
// where:
//     sayit:        acronym of "sayit", must be always present.
//     EnglishWord:    a word in english
//

function sayit_filter($courseid, $text) {
    global $CFG, $THEME;

    if (!empty($THEME->filter_mediaplugin_colors)) {
        $c = $THEME->filter_mediaplugin_colors;   // You can set this up in your theme/xxx/config.php
    } else {
        $c = 'bgColour=000000&btnColour=ffffff&btnBorderColour=cccccc&iconColour=000000&'.
             'iconOverColour=00cc00&trackColour=cccccc&handleColour=ffffff&loaderColour=ffffff&'.
             'waitForPlay=yes';
    }
    $c = htmlentities($c);

    $u = empty($CFG->unicodedb) ? '' : 'u'; //Unicode modifier

    preg_match_all('/\[\[sayit:([a-zA-Z]+)\]\]\s/i'.$u, $text, $list_sayitwords);

	/// No question links found. Return original text
    if (empty($list_sayitwords[0])) {
        return $text;
    }

	static $count = 0;
    foreach ($list_sayitwords[0] as $key=>$item) {
        $replace = '';
		// Extract info from the question link
        $sayit = new stdClass;
        $sayit->word = $list_sayitwords[1][$key];
		// Calculate footer text (it's optional in the filter)
//         if ($sayit->word) {
//             $headertext = '<br /><span class="$iframe-title">'.format_string($sayit->word).'</span>';
//         } else {
//             $headertext = '';
//         }

    $count++;
    $id = 'filter_sayit_'.time().$count; //we need something unique because it might be stored in text cache

	$soundfileurl = 'http://www.yourdictionary.com/audio/'.substr($sayit->word,0,1).'/'.substr($sayit->word,0,2).'/'.$sayit->word.'.mp3';

// http://media.merriam-webster.com/soundc11/l/love0001.wav
	//$wavesoundfileurl = 'http://media.merriam-webster.com/soundc11/'.substr($sayit->word,0,1).'/'.str_pad(substr($sayit->word,0,6), 6 ,"0").'01.wav';

    $replace = '<span class="mediaplugin mediaplugin_mp3" id="'.$id.'">('.get_string('mp3audio', 'mediaplugin').')</span>
<script type="text/javascript">
//<![CDATA[
  var FO = { movie:"'.$CFG->wwwroot.'/filter/sayit/mp3player.swf?soundFile='.$soundfileurl.'",
    width:"200", height:"15", majorversion:"6", build:"40", flashvars:"'.$c.'", quality: "high" };
  UFO.create(FO, "'.$id.'");
//]]>
</script>';

	//$replace .= '<a target="_new" href="'.$wavesoundfileurl.'" > (wav) </a>';

// 		$replace = '<a href="http://www.yourdictionary.com/audio/'.
// 			substr($sayit->word,0,1).'/'.substr($sayit->word,0,2).'/'.$sayit->word.'.mp3">'.$sayit->word.'</a>&nbsp;';

		// If replace found, do it
        if ($replace) {
            $text = str_replace($list_sayitwords[0][$key], $replace, $text);
        }
    }

/// Finally, return the text
    return $text;
}
?>