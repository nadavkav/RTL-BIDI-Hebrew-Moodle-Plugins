/*
 * This program is free software: you can redistribute it and/or modify
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
 * YUI menu styles
 *
 * This CSS file is used by all yui menu pages.
 * The prefix 'yui_menu' is used for all classes and ids
 *
 * @author Alan Trick
 * @copyright Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl-3.0.html
 */
<?php
if ($THEME->custompix) {
    $ico = '../'.$CFG->theme.'/pix';
} else {
    $ico = '../../pix';
}
$skin = "{$CFG->wwwroot}/blocks/yui_menu/skin"
?>
#yui_menu_config_list {
    border: 0;
    margin: auto;
    border-collapse: collapse;
    padding: .5em;
    margin-bottom: 1em;
}
#yui_menu_config_list td {
    padding: 1em .3em 0;
}
.yui_menu_js_tree {
    clear: both;
    padding: 0;
}
.yui_menu_tree > .icon {
    /* dots behind the outine menu */
    background: url(<?php echo "$skin/l.gif" ?>) left bottom no-repeat;
}
/* Note: this is a CSS 2 selector pattern, it's not supported in
IE 6 and earlier, but the browser will degrade nicely */
.ygtvtm + td, .ygtvtmh + td,
.ygtvlm + td, .ygtvlmh + td {
    /* dots behind the section menus */
    background: url(<?php echo "$skin/l.gif" ?>) 10% bottom no-repeat;
}

.yui_menu_tree .highlight {
    font-weight: bold;
}
.yui_menu_icon_section {
    padding-left: 20px;
    display: block;
    min-height: 16px;
    background: no-repeat;
}
/* based of YUI library version 2.6
 *
 * Warning: unneccesarily messy and ugly code ahead. This could
 * have been done in about half the size, but it looks like someone
 * at Yahoo hasn't heard of space separated classes (or :hover)
 */
/* Cells */
.ygtvtn, .ygtvtm, .ygtvtmh, .ygtvtp, .ygtvtph,
.ygtvln, .ygtvlm, .ygtvlmh, .ygtvlp, .ygtvlph,
.ygtvdepthcell, .ygtvblankdepthcell {
    width:16px; /* width of standard moodle icons*/
    height:22px; /* about the hight of the line */
}
/* Nodes */
.ygtvtm, .ygtvtmh, .ygtvtp, .ygtvtph,
.ygtvlm, .ygtvlmh, .ygtvlp, .ygtvlph {
    cursor: pointer;
}
/* Misc */
.ygtvrow { vertical-align: top; }
.ygtvlabel { margin-left:2px; }
.ygtvspacer {
    outline-style: none;
    display: block;
    text-decoration: none !important;
}
/* Cell backgrounds */
.ygtvdepthcell, .ygtvtn, .ygtvtm, .ygtvtmh, .ygtvtp, .ygtvtph {
    background: url(<?php echo "$skin/t.gif" ?>) center 0 repeat-y;
}
.ygtvln, .ygtvlm, .ygtvlmh, .ygtvlp, .ygtvlph {
    background: url(<?php echo "$skin/l.gif" ?>) center top no-repeat;
}
.ygtvtn .ygtvspacer, .ygtvln .ygtvspacer {
    background: url(<?php echo "$skin/n.gif" ?>) right center no-repeat;
}
.ygtvtm .ygtvspacer, .ygtvlm .ygtvspacer {
    background: url(<?php echo "$skin/m.gif" ?>) right center no-repeat;
}
.ygtvtmh .ygtvspacer, .ygtvlmh .ygtvspacer {
    background: url(<?php echo "$skin/mh.gif" ?>) right center no-repeat;
}
.ygtvtp .ygtvspacer, .ygtvlp .ygtvspacer {
    background: url(<?php echo "$skin/p.gif" ?>) right center no-repeat;
}
.ygtvtph .ygtvspacer, .ygtvlph .ygtvspacer {
    background: url(<?php echo "$skin/ph.gif" ?>) right center no-repeat;
}

/*  fix topics word wrap in rtl mode */
.sideblock div.column.c1 {
display:inline;
width:192px;
}

.icon img {
vertical-align:middle;
}

.icon a:hover {
background-color:GreenYellow;
text-decoration:none;
}
td#left-column,td#right-column  {
width:220px !important;
}

.dir-rtl .ygtvtn .ygtvspacer, .dir-rtl .ygtvln .ygtvspacer {
    background: url(<?php echo "$skin/n.gif" ?>) no-repeat scroll left center transparent;
}

.dir-rtl .ygtvtm .ygtvspacer, .dir-rtl .ygtvlm .ygtvspacer {
    background: url(<?php echo "$skin/m-rtl.gif" ?>) no-repeat scroll left center transparent;
}

.dir-rtl .ygtvtmh .ygtvspacer, .dir-rtl .ygtvlmh .ygtvspacer {
    background: url(<?php echo "$skin/mh-rtl.gif" ?>) left center no-repeat;
}

.dir-rtl .ygtvtp .ygtvspacer, .dir-rtl .ygtvlp .ygtvspacer {
    background: url(<?php echo "$skin/p-rtl.gif" ?>) left center no-repeat;
}

.dir-rtl .ygtvtph .ygtvspacer, .dir-rtl .ygtvlph .ygtvspacer {
    background: url(<?php echo "$skin/ph-rtl.gif" ?>) left center no-repeat;
}

.dir-rtl .ygtvtm + td, .ygtvtmh + td,
.dir-rtl .ygtvlm + td, .ygtvlmh + td {
    /* dots behind the section menus */
    background: url(<?php echo "$skin/l.gif" ?>) 90% bottom no-repeat;
}

.dir-rtl .block_yui_menu div.column.c0 {
    float: right;
}