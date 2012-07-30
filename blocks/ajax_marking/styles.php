<?php
    // either use the theme icons, or if there are none, the standard ones.
    //global $USER;
    //echo "user obj: <br />";
    //print_r($USER);
    //echo "<br />";

    function icon_check($icon_name, $type) {

        global $CFG, $USER, $THEME;
        
        if ($type=='mod') {
            $icon_name_mod = '/theme/'.current_theme().'/pix/'.$icon_name;
        } else {
            $icon_name_mod = '/theme/'.current_theme().'/'.$icon_name;
        }


        if ($icon_name == 'pix/i/course.gif') {
            //echo $CFG->dirroot.$icon_name_mod;
           // echo ' '.$USER->id.' ';
            print_r($USER);
        }

        if (file_exists($CFG->dirroot.$icon_name_mod)) {
            echo $CFG->wwwroot.$icon_name_mod;
        } else {
            echo  '/'.$icon_name;
        }
    }
?>
.icon-course, .icon-assignment, .icon-workshop, .icon-forum, .icon-quiz, .icon-quiz_question,
.icon-journal, .icon-group {
  padding-left: 0px;
  padding-bottom: 0px;
  background-repeat: no-repeat;
  cursor:pointer;
  background-color: transparent;

  /* white-space: nowrap; */
  margin-left: 0px;
  display: block;
  float: left;
}
.amb-icon {
  width: 20px;
  padding-right: 3px;
  margin-bottom: -5px;
}
.icon-course {
  padding-left: 0px;

}
/*

No longer needed.
.icon-assignment {
  background-image: url(<?php icon_check('mod/assignment/icon.gif', 'mod') ?>);
}
.icon-workshop {
  background-image: url(<?php icon_check('mod/workshop/icon.gif', 'mod') ?>);
}
.icon-forum {
  background-image: url(<?php icon_check('mod/forum/icon.gif', 'mod') ?>);
}
.icon-quiz {
  background-image: url(<?php icon_check('mod/quiz/icon.gif', 'mod') ?>);
}
.icon-quiz_question {
  background-image: url(<?php icon_check('pix/i/questions.gif') ?>);
}
.icon-journal {
  background-image: url(<?php icon_check('mod/journal/icon.gif', 'mod') ?>);
}
.icon-group {
  background-image: url(<?php icon_check('pix/i/users.gif') ?>);
}
*/
/* the following 8 styles give different coloured borders to 
   submissions depending on when they were submitted. The 
   colours may not be the best for your theme so change them
   below if needs be. The timings are in javascript.js at around line
   340. If you have colour blind users, you may need to take contrast into account
   and maybe vary the line style - dotted, dashed, solid.
*/
   
.icon-user-one, .icon-user-two, .icon-user-three, .icon-user-four, .icon-user-five, .icon-user-six,
.icon-user-seven, .icon-user-eight {
  padding-left: 0px;
  padding-right: 2px;
  cursor:pointer;
 /*
  background-repeat: no-repeat;
  white-space: nowrap;

  background-color: transparent;
  */
  border-style: none;
  border-width: 2px;
  overflow: hidden;
  width: 150px;
  height: 40px;
  margin: 0;
}
.icon-user-one {
  background-color: #ccffcc; 
}
.icon-user-two  {
  background-color: #ccffcc;
}
.icon-user-three  {
  background-color: #EEE5AA;
}
.icon-user-four  {
  background-color: #EEE5AA;
}
.icon-user-five  {
  background-color: #EECAB3;
}
.icon-user-six  {
  background-color: #EECAB3;
}
.icon-user-seven  {
  background-color: #ffb0bb;
}
.icon-user-eight  {
  background-color: #ffb0bb;
}
#loader {
  position: relative;
  top: 3px;
  right: 0px;
  float: left;
  z-index: 100;
  margin: 0px;
  padding: 0px;
}
#hidden-icons {
  display: none;
}
<?php
 //include '../../lib/yui/treeview/assets/skins/sam/treeview.css';
//include '../../lib/yui/container/assets/container.css';
?>

#totalmessage, #count {
  float: left;
  padding-bottom: 2px;
  margin-left: 3px;
}
.loaderimage {
  background: url(<?php echo $CFG->wwwroot ?>/blocks/ajax_marking/images/ajax-loader.gif) 0 0 no-repeat;
  width: 15px;
  height: 15px;
  display: block;
}
#count {
  font-weight: bold;
}
#treediv {
  clear: both; 
  margin-bottom: 5px;
  padding-bottom: 0px;
  float: left;
  font:10pt tahoma;
  min-width: 150px;
}
#mainIcon {
  float: left;
  margin-left: 8px;
}




/* The SAM treeview skin, copied from the lib fine so that proper URLs can be added for the images */

/*
Copyright (c) 2008, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.net/yui/license.txt
version: 2.6.0
*/
/*
Copyright (c) 2008, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.net/yui/license.txt
version: 2.5.2
*/

/* the style of the div around each node */
.ygtvitem { }

.ygtvitem table {
    margin-bottom:0; border:none;
}

/*.ygtvitem td {*/
.ygtvrow td {
    border: none; padding: 0;
}
.ygtvrow td a {
    text-decoration:none;
}


/* first or middle sibling, no children */
.ygtvtn {
    width:18px; height:22px;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -5600px no-repeat;
}

/* first or middle sibling, collapsable */
.ygtvtm {
    width:18px; height:22px;
    cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -4000px no-repeat;
}

/* first or middle sibling, collapsable, hover */
.ygtvtmh,.ygtvtmhh {
    width:18px; height:22px;
    cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -4800px no-repeat;
}

/* first or middle sibling, expandable */
.ygtvtp {
    width:18px; height:22px;
    cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -6400px no-repeat;
}

/* first or middle sibling, expandable, hover */
.ygtvtph ,.ygtvtphh {
    width:18px; height:22px;
    cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -7200px no-repeat;
}

/* last sibling, no children */
.ygtvln {
    width:18px; height:22px;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -1600px no-repeat;
}

/* Last sibling, collapsable */
.ygtvlm {
    width:18px; height:22px;
    cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 0px no-repeat;
}

/* Last sibling, collapsable, hover */
.ygtvlmh,.ygtvlmhh {
    width:18px; height:22px;
    cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -800px no-repeat;
}

/* Last sibling, expandable */
.ygtvlp {
    width:18px; height:22px;
    cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -2400px no-repeat;
}

/* Last sibling, expandable, hover */
.ygtvlph,.ygtvlphh {
    width:18px; height:22px; cursor:pointer ;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -3200px no-repeat;
}

/* Loading icon */
.ygtvloading {
    width:18px; height:22px;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-loading.gif)
    0 0 no-repeat;
}

/* the style for the empty cells that are used for rendering the depth
 * of the node */
.ygtvdepthcell {
    width:18px; height:22px;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -8000px no-repeat;
}

.ygtvblankdepthcell { width:18px; height:22px; }


/* the style of the div around each node's collection of children */
.ygtvchildren {  }
* html .ygtvchildren { height:2%; }

/* the style of the text label in ygTextNode */
.ygtvlabel, .ygtvlabel:link, .ygtvlabel:visited, .ygtvlabel:hover {
    margin-left:2px;
    text-decoration: none;
    background-color: white; /* workaround for IE font smoothing bug */
    cursor:pointer;
}

.ygtvcontent {
    cursor:default;
}

.ygtvspacer { height: 22px; width: 12px; }

.ygtvfocus {
    background-color: #c0e0e0;
    border: none;
}
.ygtvfocus .ygtvlabel, .ygtvfocus .ygtvlabel:link, .ygtvfocus .ygtvlabel:visited,
.ygtvfocus .ygtvlabel:hover {
    background-color: #c0e0e0;
}

.ygtvfocus a , .ygtvrow  td a {
    outline-style:none;
}


.ygtvok {
    width:18px; height:22px;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -8800px no-repeat;
}

.ygtvok:hover {
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -8844px no-repeat;
}

.ygtvcancel {
    width:18px; height:22px;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -8822px no-repeat;
}

.ygtvcancel:hover  {
    background: url(<?php echo $CFG->wwwroot."/lib/yui/treeview/assets/skins/sam/" ?>treeview-sprite.gif)
    0 -8866px no-repeat;
}

.ygtv-label-editor {
    background-color:#f2f2f2;
    border: 1px solid silver;
    position:absolute;
    display:none;
    overflow:hidden;
    margin:auto;
    z-index:9000;
}

.ygtv-edit-TextNode  {
    width: 190px;
}

.ygtv-edit-TextNode .ygtvcancel, .ygtv-edit-TextNode .ygtvok  {
    border:none;
}

.ygtv-edit-TextNode .ygtv-button-container {
    float: right;
}

.ygtv-edit-TextNode .ygtv-input  input{
    width: 140px;
}

.ygtv-edit-DateNode .ygtvcancel {
    border:none;
}
.ygtv-edit-DateNode .ygtvok  {
    display:none;
}

.ygtv-edit-DateNode   .ygtv-button-container {
    text-align:right;
    margin:auto;
}

/* makes sure the bottom of the icons don't get hidden */
.ygtvlabel, .ygtvlabel:link, .ygtvlabel:visited, .ygtvlabel:hover {
  background-color: transparent;
}

      
/* Debug styles */

.bd {
  text-align: left;
}

/*
 styles for the config screen pop up
 */

#conf_left {
  float:left;
  width: 45%;
  margin-left: 3px;
} 
#conf_right {
  float:right;
  width: 45%;
  margin-right: 3px;
  text-align: right;
} 
#conf-wrapper {
  float: left;
  clear: both;
  background-color: transparent;
}
#close {
  float:right;
  margin: 0px;
  padding: 0px;
}
#confname {
  float: left;
  font-weight: bold;
  width: 50%;
  padding-left: 4px;
  line-height: 15px;
}
#dialog {
  display:none;
  z-index: 500;
  background-color: transparent;
  padding:0px;
  font:10pt tahoma;
  border:1px solid gray;
  width:420px;
  position:absolute;
}
.dialogheader {
  line-height: 0;
  height: 25px;
  border-width: 0;
  border-bottom-width: 1px;
  border-style: solid;
  border-color: #000;
  width: 100%;
  margin: 0px;
}
#configTree {
  float: left;
  width: 220px;
  height: 480px;
  max-width: 200px;
  padding-top: 4px;
  overflow-y: scroll;
  font:10pt tahoma;
  background-color: transparent;
}
#configSettings {
  float:left;
  width:190px;
  padding-left: 10px;
  font:10pt tahoma;
}
#configGroups {
  float:right;
  width:190px;
  background-color: transparent;
}
#configIcon {
  position: relative;
  line-height: 0pt;
  width: 35px;
}
.AMhidden {
  display: none;
}
div.block_ajax_marking div.footer {
  border-style: none;
  padding-bottom: 0px;
  height: 30px;
}
#configInstructions {
  font:10pt tahoma;
  float: left;
  width: 100%;
}

/*
stuff from the container.css file, cleaned up to make it validate
*/

.yui-tt {
    visibility: hidden;
    position: absolute;
    color: #333;
    background-color: #FDFFB4;
    font-family: arial,helvetica,verdana,sans-serif;
    padding: 2px;
    border: 1px solid #FCC90D;
    font:75% sans-serif;
    width: auto;
}

.yui-tt-shadow {
    display: none;
}



/* added bits for the panel */

<?php //include 'container.css'; ?>
<?php // include '../../lib/yui/menu/assets/skins/menu-core.css' ?>
<?php //include '../../lib/yui/menu/assets/skins/sam/menu.css' ?>

.yui-skin-sam .container-close {
  background:url(<?php echo $CFG->wwwroot; ?>/lib/yui/assets/skins/sam/sprite.png) no-repeat 0 -300px;
}
.yui-skin-sam .yui-panel .hd {
  background:url(<?php echo $CFG->wwwroot; ?>/lib/yui/assets/skins/sam/sprite.png) repeat-x 0 -200px;
}







/*
Copyright (c) 2008, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.net/yui/license.txt
version: 2.6.0
*/
.yuimenu{top:-999em;left:-999em;}.yuimenubar{position:static;}.yuimenu .yuimenu,
.yuimenubar .yuimenu{position:absolute;}.yuimenubar li,.yuimenu li{list-style-type:none;}.yuimenubar ul,
.yuimenu ul,.yuimenubar li,.yuimenu li,.yuimenu h6,.yuimenubar h6{margin:0;padding:0;}.yuimenuitemlabel,
.yuimenubaritemlabel{text-align:left;white-space:nowrap;}.yuimenubar ul{*zoom:1;}.yuimenubar
.yuimenu ul{*zoom:normal;}.yuimenubar>.bd>ul:after{content:".";display:block;clear:both;
visibility:hidden;height:0;line-height:0;}.yuimenubaritem{float:left;}.yuimenubaritemlabel,
.yuimenuitemlabel{display:block;}.yuimenuitemlabel .helptext{font-style:normal;display:block;margin:-1em 0 0 10em;}
.yui-menu-shadow{position:absolute;visibility:hidden;z-index:-1;}.yui-menu-shadow-visible{top:2px;right:-3px;left:-3px;
bottom:-3px;visibility:visible;}.hide-scrollbars *{overflow:hidden;}.hide-scrollbars select{display:none;}
.yuimenu.show-scrollbars,.yuimenubar.show-scrollbars{overflow:visible;}.yuimenu.hide-scrollbars
.yui-menu-shadow,.yuimenubar.hide-scrollbars .yui-menu-shadow{overflow:hidden;}.yuimenu.show-scrollbars
.yui-menu-shadow,.yuimenubar.show-scrollbars .yui-menu-shadow{overflow:auto;}.yui-skin-sam .yuimenubar{font-size:93%;
line-height:2;*line-height:1.9;border:solid 1px #808080;
background:url(<?php echo $CFG->wwwroot."/lib/yui/menu" ?>/assets/skins/sam/sprite.png) repeat-x 0 0;}
.yui-skin-sam .yuimenubarnav .yuimenubaritem{border-right:solid 1px #ccc;}.yui-skin-sam
.yuimenubaritemlabel{padding:0 10px;color:#000;text-decoration:none;cursor:default;border-style:solid;
border-color:#808080;border-width:1px 0;*position:relative;margin:-1px 0;}.yui-skin-sam .yuimenubarnav
.yuimenubaritemlabel{padding-right:20px;*display:inline-block;}.yui-skin-sam .yuimenubarnav
.yuimenubaritemlabel-hassubmenu{background:url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menubaritem_submenuindicator.png)
right center no-repeat;}.yui-skin-sam .yuimenubaritem-selected{background:url(<?php echo $CFG->wwwroot."/lib/yui/menu" ?>/assets/skins/sam/sprite.png)
repeat-x 0 -1700px;}.yui-skin-sam .yuimenubaritemlabel-selected{border-color:#7D98B8;}.yui-skin-sam
.yuimenubarnav .yuimenubaritemlabel-selected{border-left-width:1px;margin-left:-1px;*left:-1px;}.yui-skin-sam
.yuimenubaritemlabel-disabled{cursor:default;color:#A6A6A6;}.yui-skin-sam .yuimenubarnav
.yuimenubaritemlabel-hassubmenu-disabled{background-image:url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menubaritem_submenuindicator_disabled.png);}
.yui-skin-sam .yuimenu{font-size:93%;line-height:1.5;*line-height:1.45;}.yui-skin-sam .yuimenubar
.yuimenu,.yui-skin-sam .yuimenu .yuimenu{font-size:100%;}.yui-skin-sam .yuimenu .bd{*zoom:1;_zoom:normal;
border:solid 1px #808080;background-color:#fff;}.yui-skin-sam .yuimenu .yuimenu .bd{*zoom:normal;}
.yui-skin-sam .yuimenu ul{padding:3px 0;border-width:1px 0 0 0;border-color:#ccc;border-style:solid;}
.yui-skin-sam .yuimenu ul.first-of-type{border-width:0;}.yui-skin-sam .yuimenu h6{font-weight:bold;
border-style:solid;border-color:#ccc;border-width:1px 0 0 0;color:#a4a4a4;padding:3px 10px 0 10px;}
.yui-skin-sam .yuimenu ul.hastitle,.yui-skin-sam .yuimenu h6.first-of-type{border-width:0;}.yui-skin-sam
.yuimenu .yui-menu-body-scrolled{border-color:#ccc #808080;overflow:hidden;}.yui-skin-sam .yuimenu
.topscrollbar,.yui-skin-sam .yuimenu .bottomscrollbar{height:16px;border:solid 1px #808080;
background:#fff url(<?php echo $CFG->wwwroot."/lib/yui/menu" ?>/assets/skins/sam/sprite.png)
no-repeat 0 0;}.yui-skin-sam .yuimenu .topscrollbar{border-bottom-width:0;background-position:center -950px;}
.yui-skin-sam .yuimenu .topscrollbar_disabled{background-position:center -975px;}.yui-skin-sam .yuimenu
.bottomscrollbar{border-top-width:0;background-position:center -850px;}.yui-skin-sam .yuimenu
.bottomscrollbar_disabled{background-position:center -875px;}.yui-skin-sam .yuimenuitem{_border-bottom:solid 1px #fff;}
.yui-skin-sam .yuimenuitemlabel{padding:0 20px;color:#000;text-decoration:none;cursor:default;}.yui-skin-sam
.yuimenuitemlabel .helptext{margin-top:-1.5em;*margin-top:-1.45em;}.yui-skin-sam
.yuimenuitem-hassubmenu{background-image:url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_submenuindicator.png);
background-position:right center;background-repeat:no-repeat;}.yui-skin-sam
.yuimenuitem-checked{background-image:url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_checkbox.png);
background-position:left center;background-repeat:no-repeat;}.yui-skin-sam
.yui-menu-shadow-visible{background-color:#000;opacity:.12;*filter:alpha(opacity=12);}.yui-skin-sam
.yuimenuitem-selected{background-color:#B3D4FF;}.yui-skin-sam .yuimenuitemlabel-disabled{cursor:default;
color:#A6A6A6;}.yui-skin-sam .yuimenuitem-hassubmenu-disabled{
background-image:url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_submenuindicator_disabled.png);}
.yui-skin-sam .yuimenuitem-checked-disabled{background-image:url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_checkbox_disabled.png);}




/* menu SAM skin menu-skin.css file included so image paths can be fixed. */

/*
Copyright (c) 2008, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.net/yui/license.txt
version: 2.6.0
*/
/* MenuBar style rules */

.yui-skin-sam .yuimenubar {

    font-size: 93%;  /* 12px */
    line-height: 2;  /* ~24px */
    *line-height: 1.9; /* For IE */
    border: solid 1px #808080;
    background: url(<?php echo $CFG->wwwroot."/lib/yui/menu" ?>/assets/skins/sam/sprite.png) repeat-x 0 0;

}


/* MenuBarItem style rules */

.yui-skin-sam .yuimenubarnav .yuimenubaritem {

    border-right: solid 1px #ccc;

}

.yui-skin-sam .yuimenubaritemlabel {

    padding: 0 10px;
    color: #000;
    text-decoration: none;
    cursor: default;
    border-style: solid;
    border-color: #808080;
    border-width: 1px 0;
    *position: relative; /*  Necessary to get negative margins in IE. */
    margin: -1px 0;

}

.yui-skin-sam .yuimenubarnav .yuimenubaritemlabel {

    padding-right: 20px;

    /*
        Prevents the label from shifting left in IE when the
        ".yui-skin-sam .yuimenubarnav .yuimenubaritemlabel-selected"
        rule us applied.
    */

    *display: inline-block;

}

.yui-skin-sam .yuimenubarnav .yuimenubaritemlabel-hassubmenu {

    background: url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menubaritem_submenuindicator.png)
    right center no-repeat;

}



/* MenuBarItem states */

/* Selected MenuBarItem */

.yui-skin-sam .yuimenubaritem-selected {

    background: url(<?php echo $CFG->wwwroot."/lib/yui/menu" ?>/assets/skins/sam/sprite.png) repeat-x 0 -1700px;

}

.yui-skin-sam .yuimenubaritemlabel-selected {

    border-color: #7D98B8;

}

.yui-skin-sam .yuimenubarnav .yuimenubaritemlabel-selected {

    border-left-width: 1px;
    margin-left: -1px;
    *left: -1px;    /* For IE */

}


/* Disabled  MenuBarItem */

.yui-skin-sam .yuimenubaritemlabel-disabled {

    cursor: default;
    color: #A6A6A6;

}

.yui-skin-sam .yuimenubarnav .yuimenubaritemlabel-hassubmenu-disabled {

    background-image: url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menubaritem_submenuindicator_disabled.png);

}



/* Menu style rules */

.yui-skin-sam .yuimenu {

    font-size: 93%;  /* 12px */
    line-height: 1.5;  /* 18px */
    *line-height: 1.45; /* For IE */

}

.yui-skin-sam .yuimenubar .yuimenu,
.yui-skin-sam .yuimenu .yuimenu {

    font-size: 100%;

}

.yui-skin-sam .yuimenu .bd {

    /*
        The following application of zoom:1 prevents first tier submenus of a MenuBar from hiding
        when the mouse is moving from an item in a MenuBar to a submenu in IE 7.
    */

    *zoom: 1;
    _zoom: normal;  /* Remove this rule for IE 6. */
    border: solid 1px #808080;
    background-color: #fff;

}

.yui-skin-sam .yuimenu .yuimenu .bd {

    *zoom: normal;

}

.yui-skin-sam .yuimenu ul {

    padding: 3px 0;
    border-width: 1px 0 0 0;
    border-color: #ccc;
    border-style: solid;

}

.yui-skin-sam .yuimenu ul.first-of-type {

    border-width: 0;

}


/* Group titles */

.yui-skin-sam .yuimenu h6 {

    font-weight: bold;
    border-style: solid;
    border-color: #ccc;
    border-width: 1px 0 0 0;
    color: #a4a4a4;
    padding: 3px 10px 0 10px;

}

.yui-skin-sam .yuimenu ul.hastitle,
.yui-skin-sam .yuimenu h6.first-of-type {

    border-width: 0;

}


/* Top and bottom scroll controls */

.yui-skin-sam .yuimenu .yui-menu-body-scrolled {

    border-color: #ccc #808080;
    overflow: hidden;

}

.yui-skin-sam .yuimenu .topscrollbar,
.yui-skin-sam .yuimenu .bottomscrollbar {

    height: 16px;
    border: solid 1px #808080;
    background: #fff url(<?php echo $CFG->wwwroot."/lib/yui/menu" ?>/assets/skins/sam/sprite.png)
    no-repeat 0 0;

}

.yui-skin-sam .yuimenu .topscrollbar {

    border-bottom-width: 0;
    background-position: center -950px;

}

.yui-skin-sam .yuimenu .topscrollbar_disabled {

    background-position: center -975px;

}

.yui-skin-sam .yuimenu .bottomscrollbar {

    border-top-width: 0;
    background-position: center -850px;

}

.yui-skin-sam .yuimenu .bottomscrollbar_disabled {

    background-position: center -875px;

}


/* MenuItem style rules */

.yui-skin-sam .yuimenuitem {

    /*
        For IE 7 Quirks and IE 6 Strict Mode and Quirks Mode:
        Used to collapse superfluous white space between <li> elements
        that is triggered by the "display" property of the <a> elements being
        set to "block."
    */

    _border-bottom: solid 1px #fff;

}

.yui-skin-sam .yuimenuitemlabel {

    padding: 0 20px;
    color: #000;
    text-decoration: none;
    cursor: default;

}

.yui-skin-sam .yuimenuitemlabel .helptext {

    margin-top: -1.5em;
    *margin-top: -1.45em;  /* For IE*/

}

.yui-skin-sam .yuimenuitem-hassubmenu {

    background-image: url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_submenuindicator.png);
    background-position: right center;
    background-repeat: no-repeat;

}

.yui-skin-sam .yuimenuitem-checked {

    background-image: url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_checkbox.png);
    background-position: left center;
    background-repeat: no-repeat;

}


/* Menu states */


/* Visible Menu */

.yui-skin-sam .yui-menu-shadow-visible {

    background-color: #000;

    /*
        Opacity can be expensive, so defer the use of opacity until the
        menu is visible.
    */

    opacity: .12;
    *filter: alpha(opacity=12);  /* For IE */

}



/* MenuItem states */


/* Selected MenuItem */

.yui-skin-sam .yuimenuitem-selected {

    background-color: #B3D4FF;

}


/* Disabled MenuItem */

.yui-skin-sam .yuimenuitemlabel-disabled {

    cursor: default;
    color: #A6A6A6;

}

.yui-skin-sam .yuimenuitem-hassubmenu-disabled {

    background-image: url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_submenuindicator_disabled.png);

}

.yui-skin-sam .yuimenuitem-checked-disabled {

    background-image: url(<?php echo $CFG->wwwroot."/lib/yui/menu/assets/skins/sam/" ?>menuitem_checkbox_disabled.png);

}

/* Stuff to make the lists display right with AJAX turned off */
ul.AMB_html {
  padding-left: 5px;
  list-style-type: none;
  margin: 0;
  margin-left: 10px;

}
li.AMB_html, li.AMB_html_course {
  text-indent: -24px;
  padding-top: 3px;
  padding-bottom: 3px;
}
li.AMB_html_course {
  background-color: #ddd;
}
ul.AMB_html_items {
  padding-left: 15px;
  margin-top: 5px;

  list-style-type: none;
}
span.AMB_count {
  font-weight: bold;
}

<?php //include($CFG->dirroot.'/lib/yui/fonts/fonts-min.css'); ?>
/* The following rule didn't work when it was just appended after an otherwise normal include of the button.css file. No idea why.
   Probably best to strip out the unecessary bits later */
.yui-skin-sam .yui-button{border-width:1px 0;border-style:solid;border-color:#808080;background:url(<?php echo $CFG->wwwroot.'/lib/yui/assets/skins/sam/'; ?>sprite.png) repeat-x 0 0;margin:auto .25em;}

.yui-button{display:-moz-inline-box;display:inline-block;vertical-align:text-bottom;}.yui-button
.first-child{display:block;*display:inline-block;}.yui-button button,.yui-button a{display:block;
*display:inline-block;border:none;margin:0;}.yui-button button{background-color:transparent;
*overflow:visible;cursor:pointer;}.yui-button a{text-decoration:none;}.yui-skin-sam .yui-button
.first-child{border-width:0 1px;border-style:solid;border-color:#808080;margin:0 -1px;*position:relative;
*left:-1px;_margin:0;_position:static;}.yui-skin-sam .yui-button button,.yui-skin-sam
.yui-button a{padding:0 10px;font-size:93%;line-height:2;*line-height:1.7;min-height:2em;
*min-height:auto;color:#000;}.yui-skin-sam .yui-button a{*line-height:1.875;*padding-bottom:1px;}
.yui-skin-sam .yui-split-button button,.yui-skin-sam .yui-menu-button button{padding-right:20px;
background-position:right center;background-repeat:no-repeat;}.yui-skin-sam
.yui-menu-button button{background-image:url(menu-button-arrow.png);}.yui-skin-sam
.yui-split-button button{background-image:url(split-button-arrow.png);}.yui-skin-sam
.yui-button-focus{border-color:#7D98B8;background-position:0 -1300px;}.yui-skin-sam
.yui-button-focus .first-child{border-color:#7D98B8;}.yui-skin-sam .yui-button-focus button,
.yui-skin-sam .yui-button-focus a{color:#000;}.yui-skin-sam
.yui-split-button-focus button{background-image:url(split-button-arrow-focus.png);}
.yui-skin-sam .yui-button-hover{border-color:#7D98B8;background-position:0 -1300px;}
.yui-skin-sam .yui-button-hover .first-child{border-color:#7D98B8;}.yui-skin-sam
.yui-button-hover button,.yui-skin-sam .yui-button-hover a{color:#000;}.yui-skin-sam
.yui-split-button-hover button{background-image:url(split-button-arrow-hover.png);}
.yui-skin-sam .yui-button-active{border-color:#7D98B8;background-position:0 -1700px;}
.yui-skin-sam .yui-button-active .first-child{border-color:#7D98B8;}.yui-skin-sam
.yui-button-active button,.yui-skin-sam .yui-button-active a{color:#000;}.yui-skin-sam
.yui-split-button-activeoption{border-color:#808080;background-position:0 0;}.yui-skin-sam
.yui-split-button-activeoption .first-child{border-color:#808080;}.yui-skin-sam
.yui-split-button-activeoption button{background-image:url(split-button-arrow-active.png);}
.yui-skin-sam .yui-radio-button-checked,.yui-skin-sam
.yui-checkbox-button-checked{border-color:#304369;background-position:0 -1400px;}
.yui-skin-sam .yui-radio-button-checked .first-child,.yui-skin-sam .yui-checkbox-button-checked
.first-child{border-color:#304369;}.yui-skin-sam .yui-radio-button-checked button,.yui-skin-sam
.yui-checkbox-button-checked button{color:#fff;}.yui-skin-sam
.yui-button-disabled{border-color:#ccc;background-position:0 -1500px;}.yui-skin-sam
.yui-button-disabled .first-child{border-color:#ccc;}.yui-skin-sam .yui-button-disabled button,
.yui-skin-sam .yui-button-disabled a{color:#A6A6A6;cursor:default;}.yui-skin-sam
.yui-menu-button-disabled button{background-image:url(menu-button-arrow-disabled.png);}
.yui-skin-sam .yui-split-button-disabled button{background-image:url(split-button-arrow-disabled.png);}


