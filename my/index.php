<?php  // $Id: index.php,v 1.16.2.2 2008/04/09 02:43:51 dongsheng Exp $

    // this is the 'my moodle' page

    require_once('../config.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once('pagelib.php');
    
    require_login();

    $mymoodlestr = get_string('mymoodle','my');

    if (isguest()) {
        $wwwroot = $CFG->wwwroot.'/login/index.php';
        if (!empty($CFG->loginhttps)) {
            $wwwroot = str_replace('http:','https:', $wwwroot);
        }

        print_header($mymoodlestr);
        notice_yesno(get_string('noguest', 'my').'<br /><br />'.get_string('liketologin'),
                     $wwwroot, $CFG->wwwroot);
        print_footer();
        die();
    }


    $edit        = optional_param('edit', -1, PARAM_BOOL);
    $blockaction = optional_param('blockaction', '', PARAM_ALPHA);
    $filtermodule  = optional_param('filtermodule', '', PARAM_ALPHA);  // filter print_overview() by Module name (nadavkav patch)
    $coursenamefiler = optional_param('coursenamefiler', '', PARAM_ALPHA);  // filter course list by course name (nadavkav patch)

    $PAGE = page_create_instance($USER->id);

    $pageblocks = blocks_setup($PAGE,BLOCKS_PINNED_BOTH);

    if (($edit != -1) and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }

    $PAGE->print_header($mymoodlestr);

// Special visual toolbar for user's actions (nadavkav patch)
echo '<div class="toolbar" style="margins:auto;text-align:center;">';
echo '<a href="'.$CFG->wwwroot.'/calendar/view.php?view=month"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/preferences-system-time.png"></a>';
echo '<a href="'.$CFG->wwwroot.'/blocks/email_list/email/index.php?id=1"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/internet-mail.png"></a>';
echo '<a href="'.$CFG->wwwroot.'/user/view.php?id=2&course=1"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/preferences-desktop-user.png"></a>';
echo '<a href="'.$CFG->wwwroot.'/message/index.php"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/irc_protocol.png"></a>';
echo '<a href="'.$CFG->wwwroot.'/blocks/file_manager/view.php?id=1"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/warehouse.png"></a>';
echo '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view.php?courseid=1"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/folder-image.png"></a>';
echo '<a href="'.$CFG->wwwroot.'/blog/edit.php?action=add"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/xchat.png"></a>';

echo '<a target="_new" href="http://games2all.co.il/GameCategory.asp?CatID=1"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/package_games_kids.png"></a>';
echo '<a href="'.$CFG->wwwroot.'/message/index.php"><img class="toolicon" src="'.$CFG->wwwroot.'/my/toolbar/system-help.png"></a>';
echo "</div>";

echo '<style>.toolicon { padding:10px; } </style>';

// Filter Modules by... 
// (nadavkav patch)
$modlist['all'] = get_string('showall').get_string('activities');
if ($modules = get_records('modules')) {
        foreach ($modules as $mod) {
          $modlist[$mod->name] = get_string('modulenameplural',$mod->name);
        }
    }
echo '<div class="modulefilter" style="margins:auto;text-align:center;">';
  echo '<form action="index.php" method="get">';
    echo "תצוגת כל העדכונים בכל מרחבי הלימוד השייכים לפעילות: ";
    choose_from_menu ($modlist, "filtermodule", "","סינון תצוגת על־פי...", "self.location='index.php?filtermodule='+document.getElementById('modname').options[document.getElementById('modname').selectedIndex].value;", "0", false,false,"0","modname");
  echo '</form>';

  echo '<form action="index.php" method="get">';
    echo 'שם מלאה או חלקי של מרחב(יי) הלימוד לתצוגה<input type=text name="coursenamefiler" size="15" >';
    echo '<input type=submit value="סינון"> או ';
    echo '<input type=submit value="כל המרחבים">';
  echo '</form>';

echo '</div>';

// special JavaScript support for the print_overview() function
// which is defined at course/lib.php line 770
// (nadavkav patch)
echo <<<SCRIPT
<script type="text/javascript">
<!--
    function toggle_visibility(id) {
       var e = document.getElementById(id);
       if(e.style.display == 'block')
          e.style.display = 'none';
       else
          e.style.display = 'block';
    }
//-->
</script>
SCRIPT;

echo <<<JQUERY
<script src="jquery-1.2.6.min.js" type="text/javascript"></script>
<script src="jquery-ui-1.5.1.packed.js" type="text/javascript"></script>
<script src="jquery.cookie.js" type="text/javascript"></script>
JQUERY;

    echo '<table id="layout-table">';
    echo '<tr valign="top">';

    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':

    $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);

    if(blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing()) {
        echo '<td style="vertical-align: top; width: '.$blocks_preferred_width.'px;" id="left-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print_container_end();
        echo '</td>';
    }
    
            break;
            case 'middle':
    
    echo '<td valign="top" id="middle-column">';
    print_container_start(TRUE);

/// The main overview in the middle of the page
    
    // limits the number of courses showing up
    $courses = get_my_courses($USER->id, 'visible DESC,sortorder ASC', '*', false, 21);
    $site = get_site();
    $course = $site; //just in case we need the old global $course hack

    if (array_key_exists($site->id,$courses)) {
        unset($courses[$site->id]);
    }

//$coursenamefiler = 'מוודל';
//echo $coursenamefiler;
    // remove courses by course name filter
    if ( !empty($_GET['coursenamefiler']) ) {
      foreach ($courses as $course) {
        if ( strpos($course->fullname,$_GET['coursenamefiler']) === false ) { unset($courses[$course->id]); }
      }
    }

    foreach ($courses as $c) {
        if (isset($USER->lastcourseaccess[$c->id])) {
            $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
        } else {
            $courses[$c->id]->lastaccess = 0;
        }
    }
    
    if (empty($courses)) {
        print_simple_box(get_string('nocourses','my'),'center');
    } else {
        if (empty($filtermodule) || $filtermodule=='all') {
          my_print_overview($courses);
        } else {// filter print_overview() by Module name (nadavkav patch)
          $htmlarray = array();
          if (file_exists(dirname(dirname(__FILE__)).'/mod/'.$filtermodule.'/lib.php')) {
            include_once(dirname(dirname(__FILE__)).'/mod/'.$filtermodule.'/lib.php');
            $fname = $filtermodule.'_print_overview';
            if (function_exists($fname)) {
              $fname($courses,$htmlarray);
              foreach ($courses as $course) {
                $linkcss = '';
                if (empty($course->visible)) {
                $linkcss = 'class="dimmed"';
                }
                if (array_key_exists($course->id,$htmlarray)) {
                echo '<h3><a title="'. format_string($course->fullname).'" '.$linkcss.
                  ' href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'. format_string($course->fullname).'</a></h3><br/>';
                  foreach ($htmlarray[$course->id] as $modname => $html) {
                    echo $html;
                  }
                }
              }
            }
          }
        }
      }
    
    // if more than 20 courses
    if (count($courses) > 20) {
        echo '<br />...';  
    }
    
    print_container_end();
    echo '</td>';
    
            break;
            case 'right':
            
    $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]), 210);

    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $PAGE->user_is_editing()) {
        echo '<td style="vertical-align: top; width: '.$blocks_preferred_width.'px;" id="right-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print_container_end();
        echo '</td>';
    }
            break;
        }
    }

    /// Finish the page
    echo '</tr></table>';

echo '<script type="text/javascript">';
include('jquery_order_list.js');
echo '</script>';

echo <<<LIST

<style>
ul#courselist {
padding-right:1px !important;
}
#courselist li {
  border:1px solid #DADADA;
  background-color:#EFEFEF;
  padding:3px 5px;
  margin-bottom:3px;
  margin-top:3px;
//   width:100px;
   list-style-type:none;
  font-family:Arial, Helvetica, sans-serif;
  color:#666666;
  font-size:0.8em;
}

#courselist li:hover {
  background-color:#FFF;
  cursor:move;
}
</style>

LIST;
    print_footer();

function my_print_overview($courses) {

    global $CFG, $USER;

    $htmlarray = array();
    if ($modules = get_records('modules')) {
        foreach ($modules as $mod) {
            if (file_exists(dirname(dirname(__FILE__)).'/mod/'.$mod->name.'/lib.php')) {
                include_once(dirname(dirname(__FILE__)).'/mod/'.$mod->name.'/lib.php');
                $fname = $mod->name.'_print_overview';
                if (function_exists($fname)) {
                    $fname($courses,$htmlarray);
                }
            }
        }
    }
    echo "<ul id=\"courselist\">";
    foreach ($courses as $course) {
        echo "<li id=\"item-$course->id\">";
        print_simple_box_start('center', '100%', '', 5, "coursebox");
        $linkcss = '';
        if (empty($course->visible)) {
            $linkcss = 'class="dimmed"';
        }
        print_heading('<a title="'. format_string($course->fullname).'" '.$linkcss.' href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'. format_string($course->fullname).'</a>');

        if (array_key_exists($course->id,$htmlarray)) {
        echo "<a href=\"#modulesoverview$course->id\" onclick=\"toggle_visibility('modulesoverview".$course->id."');\">";
        echo "<img class=\"icon\" src=\"$CFG->wwwroot/pix/i/bookmark.png\">".get_string('waitingyourattention','my')."</a>";
        echo '<div id="modulesoverview'.$course->id.'" style="display:none;">';
        echo '<a name="modulesoverview'.$course->id.'" ></a><br/>';
            foreach ($htmlarray[$course->id] as $modname => $html) {
                echo "<img class=\"bigicon\" height=\"32px\" width=\"32px\" src=\"$CFG->wwwroot/mod/$modname/icon.gif\">";
                echo "<div style=\"padding-right:40px;padding-left:40px;margin-top:-20px;\">$html</div>";
            }
        echo '</div>';
        }

        print_simple_box_end();
        echo "</li>";
    }
    echo "</ul>";
}

?>
