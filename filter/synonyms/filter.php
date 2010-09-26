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

// This filter adds synonyms to the tooltip (title) of a SPAN HTML Element
// so it could be diplayed when a user hover over the word. (which is "under dashed")
// based on http://words.bighugelabs.com/api.php API services
// (you will need to register for an API !)
//
// Syntax for filter is:
//     [[synonyms:EnglishWord]]
// where:
//     synonyms:        acronym of "synonyms", must be always present.
//     EnglishWord:    a word in English
//

function synonyms_filter($courseid, $text) {
    global $CFG;

    $u = empty($CFG->unicodedb) ? '' : 'u'; //Unicode modifier

    preg_match_all('/\[\[synonyms:([a-zA-Z]+)\]\]\s/i'.$u, $text, $list_synonymswords);

	/// No question links found. Return original text
    if (empty($list_synonymswords[0])) {
        return $text;
    }

    foreach ($list_synonymswords[0] as $key=>$item) {
        $replace = '';
		// Extract info from the question link
        $synonym = new stdClass;
        $synonym->word = $list_synonymswords[1][$key];

		$apikey = '5d56e4947b58fc2302ed0669b143d863';
		$synonymscontent = file_get_contents('http://words.bighugelabs.com/api/2/'.$apikey.'/'.$synonym->word.'/');
		$synonyms = str_replace('|syn|',' - ',$synonymscontent);
		$synonyms = str_replace('noun',' (noun) ',$synonyms );
		$synonyms = str_replace('verb',' (verb) ',$synonyms );
		$replace = '<span style="border-bottom:1px dashed blue;border-color:blue;" title="'.$synonyms.'">'.$synonym->word.'</span>&nbsp;';

		// If replace found, do it
        if ($replace) {
            $text = str_replace($list_synonymswords[0][$key], $replace, $text);
        }
    }

/// Finally, return the text
    return $text;
}
?>