<?php
header('Content-type: text/css');

?>
/*
This file is part of the Kaltura Collaborative Media Suite which allows users 
to do with audio, video, and animation what Wiki platfroms allow them to do with 
text.

Copyright (C) 2006-2008  Kaltura Inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

html,body { height:100%; }
#modalbox{ position: fixed; left: 50%; top:50%; margin:-180px 0 0 -340px; background:transparent; /*border:3px solid #666;*/ width: 680px; z-index: 200; }
#overlay{ position: fixed; top: 0; left: 0; z-index: 199; width: 100%; height: 100%; background:url('<?php echo $CFG->wwwroot.'/mod/kaltura/'; ?>images/trans-bg.png') 0 0 repeat; cursor: wait; }
#modalbox.white_bg { background:#ffffff; }
/* Fixed posistioning emulation for IE6, currently no need because its being set via the JQM js to offset the wizard in the middle */
* html #overlay{ position: absolute; background:#000; filter: alpha(opacity=40); top: expression((document.documentElement.scrollTop || document.body.scrollTop) + Math.round(0 * (document.documentElement.offsetHeight || document.body.clientHeight) / 100) + 'px'); }
* html #modalbox{ position: absolute; top: expression((document.documentElement.scrollTop || document.body.scrollTop) + Math.round((document.documentElement.offsetHeight || document.body.clientHeight) / 2) + 'px'); }

#modalbox iframe{ overflow:hidden; }
#modalbox iframe.remove_overflow { overflow:auto; }

.poweredByKaltura { font-family:'Lucida Grande',Verdana,Arial,Sans-Serif; font-size:9px; height:12px; line-height:11px; overflow:hidden; text-align:right; }
kalturaCode { font-size: 20px; padding: 5px; }

body#kaltura-kcw,
body#kaltura-kse,
body#kaltura-kdp {margin:0px; padding:0px; }
body#kaltura-kdp #page #container,
body#kaltura-kdp #page #container #content,
body#kaltura-kcw #page #container,
body#kaltura-kcw #page #container #content,
body#kaltura-kse #page #container,
body#kaltura-kse #page #container #content,
body#kaltura-kcw #page #container { padding:0px; margin:0px; border:0px; }

#kaltura_close_modal { height: 12px; font-size:10px; }

#klibrary { overflow:hidden; float:left; }
a.arrow_left,
a.arrow_right { display:block; float:left; width:40px; margin-top:15px; height:150px; background:url('<?php echo $CFG->wwwroot.'/kaltura/'; ?>images/right_arrow.gif');}
a.arrow_left { background:url('<?php echo $CFG->wwwroot.'/kaltura/'; ?>images/left_arrow.gif'); }
#klibrary_items { } 
.kobj {width:150px; padding:10px; border:1px solid #666666; margin:5px; float:left; height:150px; }
div.kobj.active { background-color:#cccccc; }
.kobj div span { display:block; }
div.clear-block { clear:both; }

.kaltura_hand { cursor:pointer; }

.kaltura_link {text-decoration:underline }
#profile-wall-postphoto-btn, #profile-wall-postvideo-btn,
#course-wall-postvideo-btn,
#course-wall-postphoto-btn { display:none; }

#static_library_player_div {height:364px; width:410px; overflow:hidden; }
.media_type_video { background-color:red; color:white; }
.media_type_image { background-color:blue; color:white; }
.media_type_mix  { background-color:black; color:white; }
.media_type_audio { background-color:yellow; color:black; }

a.current { color:red; font-weight:bold;}

.poweredByKaltura {display:none;}

.collapsed_pptdoc { cursor:pointer; padding-bottom:25px; display:block; background:url('<?php echo $CFG->wwwroot.'/kaltura/'; ?>images/collapsed_section.gif') no-repeat right top; }
.collapsed_pptdoc.opened { background:url('<?php echo $CFG->wwwroot.'/kaltura/'; ?>images/expanded_section.gif') no-repeat right top; }

#mediaStatus_label.fix_mediaStatus_label_ie { margin:expression('0px'); }
#kaltura .selectedMedia #mediaInfo .content.fix_content_ie { clear:both; height:191px; padding:0px; padding-bottom:expression('15px'); }