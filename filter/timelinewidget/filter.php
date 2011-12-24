<?php
/**
 * Timeline Widget filter.
 *   A Moodle filter to embed an MIT SIMILE Timeline Javascript widget.
 *
 * Uses: MIT SIMILE; also, parse_ini_string function (compat.php).
 *
 * @category  Moodle4-9
 * @author    Nick Freear <nfreear @ yahoo.co.uk>
 * @copyright (c) 2010 Nicholas Freear {@link http://freear.org.uk}.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 * @link      http://freear.org.uk/moodle
 *
 * @copyright (c) Massachusetts Institute of Technology and Contributors 2006-2009 ~ Some rights reserved.
 * @license   http://opensource.org/licenses/bsd-license.php
 * @link      http://simile.mit.edu/
 */
/**
 Usage.
 Type the following in Moodle's rich-editor:

[Timeline]
; A comment.
title  = Important inventions timeline
dataUrl= {COURSE_ID}/simile-invent.xml
; The date on which to centre the timeline initially. This can
; be just a year, or a full date, eg. 20 January 1870.
date   = 1870
; UPPER-CASE! minute,hour,day,week,month,year,decade,century,millenium.
intervalUnit  = CENTURY
; How wide should the unit defined above be? In pixels.
intervalPixels= 75
[/Timeline]


NOTE. Why the square bracket/INI-file syntax above?
  A good question! I am initially writing this filter for use by teachers who
may be fairly non-technical. So, after some deliberation, I chose something
that was as 'clean' and readable as possible, could not be confused with HTML
and can easily be entered in a WYSIWYG editor in Moodle. The downsides are that
line-breaks (which can be <br>, <br />) are required, and if you disable the
filter, you're left with 'weird' square brackets.
*/

//  This filter will replace any [Timeline] OPTIONS [/Timeline] with
//  a SIMILE timeline widget.
//
//  To activate this filter, add a line like this to your
//  list of filters in your Filter configuration:
//
//  filter/timelinewidget/filter.php
//
//////////////////////////////////////////////////////////////

/// This is the filtering function itself.  It accepts the
/// courseid and the text to be filtered (in HTML form).

function timelinewidget_filter($courseid, $text) {
    static $filter_count = 0;

    if (!is_string($text)) {
        // non string data can not be filtered anyway (and don't use $filter_count).
        return $text;
    }
    // Copy the input text. Fullclone is slow and not needed here
    $newtext = $text;

    $filter_count++;

    $search  = "#\[Timeline\](.*?)\[\/?Timeline\]#ims";
    $newtext = preg_replace_callback($search, '_timeline_filter_callback', $newtext);

    if (is_null($newtext) or $newtext === $text) {
        // error or not filtered
        return $text;
    }

    return $newtext;
}


function _timeline_filter_callback($matches_ini) {
    global $CFG;

    $intervals = 'minute,hour,day,week,month,year,decade,century,millenium';
    $intervals = strtoupper(str_replace(',', '|', $intervals));

    // Reasonable defaults? (Was: date=2000; CENTURY; intervalPixels=75)
    $defaults = array(
        //'id'=>'tlw0',
        'title'=>get_string('defaulttitle', 'filter_timelinewidget'),
        'date' =>1900,
        'intervalUnit'=>'DECADE',
        'intervalPixels'=>35);

    // Tidy up after WYSIWYG editors - line breaks matter.
    $config = trim(str_ireplace(array('<br>', '<br />', '<p>'), "\n", $matches_ini[1]));
    $config = str_ireplace('</p>', '', $config);

    // For PHP < 5.3, do late loading of this compatibility library.
    if (!function_exists('parse_ini_string')) {
        require_once $CFG->dirroot.'/filter/timelinewidget/compat.php';
    }

    $config = parse_ini_string($config);
    $config = (object) array_merge($defaults, $config);

    // We probably should check types here too.
    if (!isset($config->date)) {
        print_error('errormissingdate', 'filter_timelinewidget');
    }

    $widget_url = "$CFG->wwwroot/filter/timelinewidget/widget.php?".
         http_build_query(array('conf' => $config));

    $tl_root = "$CFG->wwwroot/filter/timelinewidget";

    $js_load = $alt_link = NULL;
    if (isset($config->dataUrl)) { //XML.
        // Handle relative URLs. They must start with a course/resource ID, eg. '2/timeline-invent.xml'

        $label = get_string('xmltimelinedata', 'filter_timelinewidget');

        $alt_link = <<<EOS
    <a class="tl-widget-alt xml" href=
    "$config->dataUrl" type="application/xml" title="$label">$config->title<abbr class="accesshide"> XML</abbr></a>
EOS;
    }
    elseif (isset($config->dataId)) { //JSON.
        $label = get_string('datasource', 'filter_timelinewidget');

        $alt_link = <<<EOS
    <a class="tl-widget-alt mod-data" href=
    "$CFG->wwwroot/mod/data/view.php?d=$config->dataId" title="$label">$config->title</a>
EOS;
    } else { //Error.
        print_error('errordataurloridrequired', 'filter_timelinewidget');
    }

    $newwin_label= 'Open timeline in new window';
    $skip_label = get_string('skiplink', 'filter_timelinewidget');
    $newtext = <<<EOF

<style>
.tl-widget-skip{display:inline-block; width:1px; height:1em; overflow:hidden;}
.tl-widget-skip:focus, .tl-widget-skip:active{width:auto; overflow:visible;}
.tl-widget-frame{
  width:99%; height:300px; border:1px solid #ccc; border-radius:4px;
}
.tl-widget-alt.xml{
  background:url($tl_root/small-orange-xml.gif)no-repeat; padding-left:38px;
}
.tl-widget-alt.mod-data{
  background:url($CFG->wwwroot/mod/data/icon.gif)no-repeat; padding-left:24px;
}
.tl-widget-new{
  background:url($tl_root/pix/icon_new_window.gif)no-repeat; padding-left:18px;
}
</style>
  <a href="#tlw-end" class="tl-widget-skip">$skip_label</a>
  <iframe id="tlw0" class="tl-widget-frame" frameborder="0" src=
  "$widget_url"
  ></iframe>
<p id="tlw-end">
  <a class="tl-widget-new" target="_blank" href="$widget_url">$newwin_label</a> |
$alt_link
</p>

EOF;
    return $newtext;
}

#End.
