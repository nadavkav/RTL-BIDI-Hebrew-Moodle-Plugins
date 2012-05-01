<?php
/**
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage topcoll
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2009-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Used by the Moodle Core for identifing the format and displaying in the list of formats for a course in its settings.
$string['nametopcoll']='יחידות הוראה מוסתרות/גלויות';
$string['formattopcoll']='יחידות הוראה מוסתרות/גלויות';
$string['pluginname'] = 'יחידות הוראה מוסתרות/גלויות';

// Used in format.php
$string['topcolltoggle']='הסתרה/הצגה';
$string['topcolltogglewidth']='width: 28px;';

// Toggle all - Moodle Tracker CONTRIB-3190
$string['topcollall']='פעולה עבור כל יחידות ההוראה:';
$string['topcollopened']='גלוי';
$string['topcollclosed']='מוסתר';

// Layout enhancement - Moodle Tracker CONTRIB-3378
$string['setlayout'] = 'הגדרות תצורה';
$string['setlayout_default'] = 'בררת־מחדל';
$string['setlayout_no_toggle_section_x'] = 'ללא הסתרה/הצגה של יחידה X';
$string['setlayout_no_section_no'] = 'ללא מספר יחידת הוראה';
$string['setlayout_no_toggle_section_x_section_no'] = 'ללא הסתרה/הצגה של יחידה X וללא מספר יחידה';
$string['setlayout_no_toggle_word'] = 'ללא ההנחיה: הסתרה/הצגה';
$string['setlayout_no_toggle_word_toggle_section_x'] = 'ללא הסתרה/הצגה של יחידה X וללא הנחיה';
$string['setlayout_no_toggle_word_toggle_section_x_section_no'] = 'ללא שום חיוי';
$string['setlayoutelements'] = 'הגדרת מרכיבים';
$string['setlayoutstructure'] = 'הגדרת תצורה';
$string['setlayoutstructuretopic']='יחידה';
$string['setlayoutstructureweek']='שבוע';
$string['setlayoutstructurelatweekfirst']='Latest Week First';
$string['setlayoutstructurecurrenttopicfirst']='Current Topic First';
// Help
$string['setlayoutelements_help']='How much information about the toggles / sections you wish to be displayed.';
$string['setlayoutstructure_help']="The layout structure of the course.  You can choose between:

'Topics' - where each section is presented as a topic in section number order.

'Weeks' - where each section is presented as a week in ascending week order.

'Latest Week First' - which is the same as weeks but the current week is shown at the top and preceding weeks in decending order are displayed below execpt in editing mode where the structure is the same as 'Weeks'.

'Current Topic First' - which is the same as 'Topics' except that the current topic is shown at the top if it has been set.";
?>