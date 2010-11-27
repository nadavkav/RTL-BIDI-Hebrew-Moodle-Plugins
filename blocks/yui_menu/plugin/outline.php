<?php
/* This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * Builds a nested outline from course contents
 */

class yui_menu_plugin_outline extends yui_menu_plugin {

    function add_items($list, $block) {
        global $CFG, $COURSE;
        if (in_array($COURSE->format, array('weeks','weekscss'))) {
            $viewall = 'week=0';
        } else {
            $viewall = 'topic=all';
        }
        $outline = new yui_menu_item_link($this,
            get_string('outline', 'block_yui_menu'),
            "{$CFG->wwwroot}/course/view.php?id={$COURSE->id}&$viewall",
            "{$CFG->wwwroot}/blocks/yui_menu/icons/viewall.gif");
        $outline->children = $this->sections($block->config);
        $list[$this->id] = $outline;
    }

    function sections($config) {
        global $COURSE, $CFG, $USER, $THEME;
        // probably inefficient, but it works
        get_all_mods($COURSE->id, $mods, $modnames, $modnamesplural, $modnamesused);
        // sections
        $sections = get_all_sections($COURSE->id);
        // name for sections
        $sectionname = get_string("name{$COURSE->format}","format_{$COURSE->format}");
        // TODO: this fallback should be unnecessary
        if ($sectionname == "[[name{$COURSE->format}]]") {
            $sectionname = get_string("name{$COURSE->format}");
        }
        $return = array();
        // check what the course format is like
        // highlight for current week or highlighted topic
        if (in_array($COURSE->format, array('weeks','weekscss'))) {
            $format = 'week';
            $highlight = ceil((time()-$COURSE->startdate)/604800);
        } else {
            $format = 'topic';
            $highlight = $COURSE->marker;
        }
        $modinfo = unserialize($COURSE->modinfo);

        // I think $display is the section currently being displayed
        // Why are we calling course_set_display?
        // For Moodle 2.0 we should just use $PAGE and check type
        // and also $PAGE->activityrecord
        $path = str_replace($CFG->httpswwwroot.'/', '', $CFG->pagepath);
        if (substr($path, 0, 7) == 'course/') {
            //TODO: this code is hackish, we shouldn't use course_set_display
            # get current section being displayed
            $week = optional_param('week', -1, PARAM_INT);
            if ($week != -1) {
                // the course format should already be doing this
                $display = course_set_display($COURSE->id, $week);
            } else {
                if (isset($USER->display[$COURSE->id])) {
                    $display = $USER->display[$COURSE->id];
                } else {
                    $display = course_set_display($COURSE->id, 0);
                }
            }
        } elseif (substr($path, 0, 4) == 'mod/') {
            // Moodle 2: use $PAGE->activityrecord->section;
            $id = optional_param('id', -1, PARAM_INT);
            if ($id == -1) $display = 0;
            else {
                $sql="select section from {$CFG->prefix}course_sections where id=(select section from {$CFG->prefix}course_modules where id=$id)";
                $row=get_record_sql($sql);
                $display=$row->section;
            }
        } else {
            $display = 0;
        }

        foreach ($sections as $section) {
            // don't show the flowing sections
            if (!($section->visible && // invisible ones
                  $section->section && // the one at the very top
                  // anything above the courses section limit
                  $section->section <= $COURSE->numsections
                  )) { continue;}

            $text = trim($section->summary);

            if (empty($text)) {
                $text = ucwords($sectionname)." ".$section->section;
            } else {
                $text = $this->truncate_html(filter_text($text,$COURSE->id),$config);
            }
            // expand section if it's the one currently displayed
            $expand = false;
            if ($section->section == $display) {
                $expand = true;
            }
            $sectionstyle = 'yui_menu_icon_section';
            // highlight marked section
            if ($section->section == $highlight) {
                $sectionstyle .= ' highlight';
            }
            $iconpath = $CFG->wwwroot;

            if ($THEME->custompix) {
              $iconpath .= "/theme/".current_theme()."/pix";
            } else {
              $iconpath .= '/pix';
              //$iconpath .= '/';
            }
            //$iconpath = $CFG->wwwroot."/theme/".current_theme()."/pix";

            // decide what URL we want to use
            // A lot of this should really be done by the course format
            //
            // = intoaction config values =
            // * 'introhide' link to the section page (this effectively
            //   hides the other sections
            // * 'introscroll' link to the fragment id of the section on
            //   on the current page

            // whether or not any of the sections are hidden
            $hidden=false;
            foreach(array('topic','week') as $param) {
            	if (isset($_GET[$param]) && $_GET[$param]!='all'){
            		$hidden = true;
            	}
            }
            $introaction = (isset($config->introaction) ?
                    $config->introaction : 'introhide');
            if ($introaction == 'introhide' || $hidden) {
                // link to the section, this will effectively hide all
                // the other sections
                $url =  "{$CFG->wwwroot}/course/view.php?id={$COURSE->id}"
                        . "&$format={$section->section}";
            } else {
                // this pretty much just a hack
                // use $PAGE in Moodle 2 for great justice
                if (strpos($_SERVER['REQUEST_URI'],'course/view.php')!=0) {
                    $url = "#section-{$section->section}";
                } else {
                    $url = false;
                }
            }
            if ($url === false) {
                $item = new yui_menu_item($this, $text, '' );//$iconpath . '/i/one.gif'); // redundant icons, lets save space (nadavkav)
            } else {
                $item = new yui_menu_item_link($this, $text, $url,'');// $iconpath . '/i/one.gif'); // redundant icons, lets save space (nadavkav)
            }
            $item->expand = $expand;
            if (isset($section->sequence)) {
                $sectionmods = explode(",", $section->sequence);
            } else {
                $sectionmods = array();
            }

            foreach ($sectionmods as $modnumber) {
                if (empty($mods[$modnumber])) continue;
                $mod = $mods[$modnumber];
                // don't do anything invisible or labels
                if (!$mod->visible || $mod->modname == 'label') continue;
                // figure out the text and url
                $text = urldecode($modinfo[$modnumber]->name);
                if (!empty($CFG->filterall)) {
                    $text = filter_text($text, $COURSE->id);
                }
                if (trim($text) == '')  $text = $mod->modfullname;
                $text = $this->truncate_html($text, $config);
                $url = "{$CFG->wwwroot}/mod/{$mod->modname}/view.php?id={$mod->id}";
                $name = "yui_menu_mod_{$mod->modname}_$modnumber";
                // figure out if it is the current page
                $pageurl = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $pageurl = '~https?://'.preg_quote($pageurl,'~').'~';
                if (preg_match($pageurl, $CFG->wwwroot . $url)) {
                    $style = "yui_menu_mod_{$mod->modname} highlight";
                } else {
                    $style = "yui_menu_mod_{$mod->modname}";
                }
                $icon = "$iconpath/mod/{$mod->modname}/icon.gif";
                if ($mod->modname == 'resource') {
                    $info = resource_get_coursemodule_info($mod);
                    if (isset($info) && isset($info->icon)) {
                        $icon = "$CFG->pixpath/$info->icon";
                    }
                }

                $child = new yui_menu_item_link($this, $text, $url, $icon);
                $child->style = $style;
                $item->children[$modnumber] = $child;
            }
            $return[] = $item;
        }
        return $return;
    }
    /**
     * Filters html and junk, truncates result
     *
     * @param $html: string to filter
     * @param $max_size: length of largest piece when done
     * @param $trunc: string to append to truncated pieces
     */
    function truncate_html($html, $config) {
        $max_size = (isset($config->maxsize) ? $config->maxsize : '19');
        $trunc = (isset($config->trunc) ? $config->trunc : '...');
        $text = preg_replace_callback('|</?([^\s>]*).*?>|smi',
            array($this, 'filter_tag'), $html);
        // decode html entities, they will be encoded later with
        // htmlspecialchars
        $text= html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // trim ends of whitespace
        $text = trim($text);
        // filter any extra whitespace
        $text = preg_replace('|\s\s+|m', ' ', $text);
        // trunkcate string size
        $tl = textlib_get_instance();
        if ($tl->strlen($text) > $max_size) {
            $text = $tl->substr($text, 0, ($max_size - $tl->strlen($trunc))).$trunc;
        }
        return $text;
    }

    /**
     * Strip html tags
     *
     * replaces it with a single space or and emtpy string depending
     * one wheth it looks like a block or inline level element.
     */
    function filter_tag($matches) {
        $inline = array('a','abbr','acronym','b','big','cite','code',
            'del','dfn','em','i','ins','kbd','q','strong','s','samp',
            'small','span','sup','sub','tt','u','var');
        $tag = strtolower($matches[1]);
        // inline elements are displayed inline
        if (in_array($tag, $inline)) return '';
        // block level elements are given a single space separation
        return ' ';
    }

}
