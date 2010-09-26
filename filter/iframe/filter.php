<?php //$Id: filter.php,v 1.1 2008/11/23 17:12:30 stronk7 Exp $

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

// This filter allows you to display remote url (sites) in an iframe window
//
// Syntax for videos is:
//     [[iframe:url:height:width|title]]
// where:
//     iframe:        acronym of "iframe", must be always present.
//     url:    remote iframe url (src)
//     height : height of the frame that is embeded inside the resource's page.
//     width : width of the frame that is embeded inside the resource's page.
//     title:     free text to be displayed before the iframe's window (optional)
//

function iframe_filter($courseid, $text) {

    $u = empty($CFG->unicodedb) ? '' : 'u'; //Unicode modifier

    preg_match_all('/\[\[iframe:(.+):(.*?):(.*?)(\|(.*?))\]\]/s'.$u, $text, $list_iframes);

/// No question links found. Return original text
    if (empty($list_iframes[0])) {
        return $text;
    }

    foreach ($list_iframes[0] as $key=>$item) {
        $replace = '';
    /// Extract info from the question link
        $iframe = new stdClass;
        $iframe->url = $list_iframes[1][$key];
	$iframe->height = $list_iframes[2][$key];
	$iframe->width = $list_iframes[3][$key];
        $iframe->title = $list_iframes[5][$key];
    /// Calculate footer text (it's optional in the filter)
        if ($iframe->title) {
            $footertext = '<br /><span class="$iframe-title">'.format_string($iframe->title).'</span>';
        } else {
            $footertext = '';
        }

    $replace = '<div>'.$footertext.'</div><iframe src="'.$iframe->url.'" width="'.$iframe->width.'" height="'.$iframe->height.'">no iframe support</iframe>';

    /// If replace found, do it
        if ($replace) {
            $text = str_replace($list_iframes[0][$key], $replace, $text);
        }
    }

/// Finally, return the text
    return $text;
}
?>
