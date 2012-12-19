<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This filter provides automatic linking to
 * glossary entries, aliases and categories when
 * found inside every Moodle text
 *
 * @package    filter
 * @subpackage hiddentext
 * @copyright  Copyright (C) 2008  Dmitry Pupinin <dlnsk@ngs.ru>  {@link mailto://dlnsk@ngs.ru}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * HiddenText filtering
 *
 */

class filter_hiddentext extends moodle_text_filter {

    public function filter($text, array $options = array()) {
        global $CFG;


        if (!$courseid = $this->context->get_course_context) {
            $courseid = 0;
        }

        if (empty($text) or is_numeric($text)) {
            return $text;
        }

        //When <> tags has written in HTMLeditor, parameters can be sorted alphabetically. Is Typo3 do it?
        $search_span = '/<span((\s+filter="hiddentext"|\s+class=".*?"|\s+desc=".*?"){0,2})\s*>(.*?)<\/span>/is';
        $search_div  =  '/<div((\s+filter="hiddentext"|\s+class=".*?"|\s+desc=".*?"){0,2})\s*>(.*?)<\/div>/is';
        //$search_pp ='/\[תמ((\s+class=\&quot\;.*?\&quot\;|\s+desc=&quot;.*?&quot;){0,2})\s*\](.*?)\[תמס\]/is';
        $search_pp ='/\[ht((\s+class=\&quot\;.*?\&quot\;|\s+desc=&quot;.*?&quot;){0,2})\s*\](.*?)\[hte\]/is';
        $search_pph ='/\[תמ((\s+class=\&quot\;.*?\&quot\;|\s+desc=&quot;.*?&quot;){0,2})\s*\](.*?)\[תמס\]/is';
        //$search_heb  =  '/<ht\s*>(.*?)<hte>/is';

        $result = preg_replace_callback($search_span, 'hiddentext_filter_callback', $text);
        $result = preg_replace_callback($search_div,  'hiddentext_filter_callback', $result);
        $result = preg_replace_callback($search_pp,   'hiddentext_filter_callback_pp', $result);
        $result = preg_replace_callback($search_pph,   'hiddentext_filter_callback_pp', $result);
        //$result = preg_replace_callback($search_heb,  'hiddentext_filter_callback', $result);

        // For HTMLeditor
        list($search_span, $search_div  ) = str_replace(array('"', '<', '>','[ht]','[hte]'),
            array('&quot;', '\[', '\]','<span filter="hiddentext">','</span>'),
            array($search_span, $search_div ));

        $result = preg_replace_callback($search_span, 'hiddentext_filter_callback_html', $result);
        $result = preg_replace_callback($search_div,  'hiddentext_filter_callback_html', $result);
        $result = preg_replace_callback($search_pp,   'hiddentext_filter_callback_html_pp', $result);
        $result = preg_replace_callback($search_pph,   'hiddentext_filter_callback_html_pp', $result);
        //$result = preg_replace_callback($search_heb,  'hiddentext_filter_callback_html', $result);

        if (is_null($result)) {
            return "ERROR in filter HiddenText<br/>".$text; //error during regex processing (too many nested spans?)
        } else {
            return $result;
        }
    }

    private function hiddentext_filter_callback($matches) {
        return hiddentext_filter_impl($matches, '"');
    }

    private function hiddentext_filter_callback_pp($matches) {
        return hiddentext_filter_impl_pp($matches);
    }

    private function hiddentext_filter_callback_html($matches) {
        return hiddentext_filter_impl($matches, '&quot;');
    }

    private function hiddentext_filter_callback_html_pp($matches) {
        return hiddentext_filter_impl_pp($matches);
    }

    private function hiddentext_filter_impl($matches, $quot) {
        global $CFG;

        if (strpos($matches[1], "filter={$quot}hiddentext{$quot}") === false) {
            return $matches[0];
        }

        $output =  '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse.css" />';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/animation/animation-min.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse-fancy.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse-css.js"></script>'."\n";


        preg_match("/class={$quot}(.*?){$quot}/is", $matches[1], $m);
        $class = empty($m[1]) ? '' : ' class="'.$m[1].'"';

        preg_match("/desc={$quot}(.*?){$quot}/is", $matches[1], $m);
        $desc = empty($m[1]) ? '' : $m[1];

        if (substr($matches[0], 1, 4) == 'span' ) {
            $output .= "<span class=\"ht_trigger\"><img src=\"{$CFG->wwwroot}/filter/hiddentext/show_hidden.png\" alt=\"$desc\" />".
                (empty($desc) ? '' : "&nbsp;$desc") .'</span>'. "<span$class>&nbsp;". $matches[3] .'</span>';
        } else {
            if (empty($desc)) {
                $desc = get_string('seemore', 'hiddentext');
            }
            $output .=  "<div class=\"ht_trigger\"><img src=\"{$CFG->wwwroot}/filter/hiddentext/show_hidden.png\" alt=\"\" />&nbsp;$desc</div>".
                "<div$class>".$matches[3].'</div>';
        }
        return $output;
    }

    private function hiddentext_filter_impl_pp($matches) {
        global $CFG;

        $output =  '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse.css" />';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/animation/animation-min.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse-fancy.js"></script>';
        $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/filter/hiddentext/yuidomcollapse-css.js"></script>';


        preg_match('/class=&quot;(.*?)&quot;/is', $matches[1], $m);
        $class = $m[1];
        if (!empty($class)) {
            $class = ' class="'.$class.'"';
        }
        preg_match('/desc=&quot;(.*?)&quot;/is', $matches[1], $m);
        $desc = $m[1];

        if (substr($matches[0], 1, 3) != 'htd') {
            $output .= "<span class=\"ht_trigger\"><img src=\"{$CFG->wwwroot}/filter/hiddentext/show_hidden.png\" alt=\"$desc\" />".
                (empty($desc) ? '' : "&nbsp;$desc") .'</span>'. "<span$class>&nbsp;".$matches[3].'</span>';
        } else {
            if (empty($desc)) {
                $desc = get_string('seemore', 'hiddentext');
            }
            $output .=  "<div class=\"ht_trigger\"><img src=\"{$CFG->wwwroot}/filter/hiddentext/show_hidden.png\" alt=\"\" />&nbsp;$desc</div>".
                "<div$class>".$matches[3].'</div>';

        }
        return $output;
    }

}

?>