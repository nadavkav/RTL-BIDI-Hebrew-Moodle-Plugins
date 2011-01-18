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
    $filtermodule  = optional_param('filtermodule', '', PARAM_RAW);  // filter print_overview() by Module name (nadavkav patch)
    $coursenamefiler = optional_param('coursenamefiler', '', PARAM_RAW);  // filter course list by course name (nadavkav patch)
    $categorynamefiler = optional_param('categorynamefiler', '', PARAM_RAW);  // filter course list by course name (nadavkav patch)

    $PAGE = page_create_instance($USER->id);

    $pageblocks = blocks_setup($PAGE,BLOCKS_PINNED_BOTH);

    if (($edit != -1) and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }

    $PAGE->print_header($mymoodlestr);

// Special visual toolbar for user's actions (nadavkav patch)
echo '<table align=center><tr><td>';

echo '<div class="toolbar" style="margins:1px auto;text-align:center;widht:1024px;">';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/calendar/view.php?view=month"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/preferences-system-time.png">'.get_string('calendar','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/blocks/email_list/email/index.php?id=1"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/internet-mail.png">'.get_string('internalemail','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&course=1"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/preferences-desktop-user.png">'.get_string('myprofile','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/message/index.php"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/irc_protocol.png">'.get_string('instantmessages','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/blocks/file_manager/view.php?id=1"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/warehouse.png">'.get_string('myfiles','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view.php?courseid=1"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/folder-image.png">'.get_string('eportfolio','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/blog/edit.php?action=add"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/xchat.png">'.get_string('blogpost','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';

echo '<div class="tbaction"><a target="_new" href="http://games2all.co.il/GameCategory.asp?CatID=1"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/package_games_kids.png">'.get_string('games','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo '<div class="tbaction"><a href="'.$CFG->wwwroot.'/message/index.php"><img class="toolicon" src="'.$CFG->wwwroot.'/myview/toolbar/system-help.png">'.get_string('help','myview','',$CFG->dirroot.'/myview/lang/').'</a></div>';
echo "</div>";

echo '<style>.toolicon { padding:10px; } .tbaction {float:right; width:95px;} </style>';

// Filter Modules by...
// (nadavkav patch)
$modlist['all'] = get_string('showall').get_string('activities');
if ($modules = get_records('modules')) {
        foreach ($modules as $mod) {
          $modlist[$mod->name] = get_string('modulenameplural',$mod->name);
        }
    }
echo '<div class="modulefilter" style="margins:1px auto;border-top: 2px solid; width: 880px; padding: 10px;text-align:center;float:right;">';
echo '<table>';
  echo '<tr><td colspan=3><form id="filterbymodule" action="index.php" method="post">';
    echo get_string('filterbyactivity','myview','',$CFG->dirroot.'/myview/lang/');
    choose_from_menu ($modlist, "filtermodule", "",get_string('filterby','myview','',$CFG->dirroot.'/myview/lang/')."...", "self.location='index.php?filtermodule='+document.getElementById('modname').options[document.getElementById('modname').selectedIndex].value;", "0", false,false,"0","modname");
  echo '</form></td></tr>';
echo '<tr><td width="345px">'.get_string('coursename','myview','',$CFG->dirroot.'/myview/lang/').'</td>';
  echo '<td width="345px"><form id="filterbycatname" action="index.php" method="post">';
    echo get_string('course').'<input type=text name="coursenamefiler" id="coursenamefiler" size="15" value="'.$coursenamefiler.'">';
    echo '<input type=submit value="'.get_string('filter','myview','',$CFG->dirroot.'/myview/lang/').'"> '.get_string('or','myview','',$CFG->dirroot.'/myview/lang/').' ';
    //echo '<input type=submit value="כל המרחבים">';
  //echo '</form></td>';
  echo '</td>';

  echo '<td width="580px"><form action="index.php" method="post">';
    echo get_string('category').'<input type=text name="categorynamefiler" id="categorynamefiler" size="15" value="'.$categorynamefiler.'" >';
    echo '<input type=submit value="'.get_string('filter','myview','',$CFG->dirroot.'/myview/lang/').'"> '.get_string('or','myview','',$CFG->dirroot.'/myview/lang/').' ';
    echo '<input type=submit onclick="document.getElementById(\'categorynamefiler\').value=\'\';document.getElementById(\'coursenamefiler\').value=\'\';" value="'.get_string('allcourses','myview','',$CFG->dirroot.'/myview/lang/').'">';
  echo '</form></td>';
echo '<td>'.get_string('toview','myview','',$CFG->dirroot.'/myview/lang/').'</td></tr>';
echo '</table>';
echo '</div>';

echo '</td></tr></table>';

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

// Drag and Drop sortable UL with Cookie save, thanks to : http://tool-man.org/examples/sorting.html
echo <<<TOOLMAN
<script language="JavaScript" type="text/javascript" src="tool-man/core.js"></script>
<script language="JavaScript" type="text/javascript" src="tool-man/events.js"></script>
<script language="JavaScript" type="text/javascript" src="tool-man/css.js"></script>
<script language="JavaScript" type="text/javascript" src="tool-man/coordinates.js"></script>
<script language="JavaScript" type="text/javascript" src="tool-man/drag.js"></script>
<script language="JavaScript" type="text/javascript" src="tool-man/dragsort.js"></script>
<script language="JavaScript" type="text/javascript" src="tool-man/cookies.js"></script>
TOOLMAN;

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

    // remove courses by course name filter
    if ( !empty($coursenamefiler) ) {
      foreach ($courses as $course) {
        if ( strpos($course->fullname,$coursenamefiler) === false ) { unset($courses[$course->id]); }
      }
    }

    // filter courses by categoryname
    if ( !empty($categorynamefiler) ) {
      foreach ($courses as $course) {
        $course->categoryfullpath = str_replace('"','',myview_get_category_fullpath($course));
        if ( strpos($course->categoryfullpath,$categorynamefiler) === false ) { unset($courses[$course->id]); }
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
                echo '<br/><h3><a title="'. format_string($course->fullname).'" '.$linkcss.
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

    print_footer();

    echo "<style>.coursebox .info {float: left;} .coursebox {overflow: auto;}</style>";

echo <<<TOOLMANJS
<script language="JavaScript" type="text/javascript"><!--
  var dragsort = ToolMan.dragsort()
  var junkdrawer = ToolMan.junkdrawer()

  window.onload = function() {
    junkdrawer.restoreListOrder("courselist")

    dragsort.makeListSortable(document.getElementById("courselist"),
        verticalOnly, saveOrder)
  }

  function verticalOnly(item) {
    item.toolManDragGroup.verticalOnly()
  }

  function speak(id, what) {
    var element = document.getElementById(id);
    element.innerHTML = 'Clicked ' + what;
  }

  function saveOrder(item) {
    var group = item.toolManDragGroup
    var list = group.element.parentNode
    var id = list.getAttribute("id")
    if (id == null) return
    group.register('dragend', function() {
      ToolMan.cookies().set("list-" + id,
          junkdrawer.serializeList(list), 365)
    })
  }

  //-->
</script>
TOOLMANJS;

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
        echo "<li itemID=\"item-$course->id\">";
        print_simple_box_start('center', '100%', '', 5, "coursebox");
        $linkcss = '';
        if (empty($course->visible)) {
            $linkcss = 'class="dimmed"';
        }
        // Display Category name
        //$category = get_record('course_categories','id',$course->category);
        //echo ' >> <a title="'. format_string($category->name).'" '.$linkcss.' href="'.$CFG->wwwroot.'/course/category.php?id='.$category->id.'">'. format_string($category->name).'</a><br/>';
        $coursecategories = myview_get_category_fullpath($course);
        echo $coursecategories . "<br/>";

        print_heading('<a title="'. format_string($course->fullname).'" '.$linkcss.' href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'. format_string($course->fullname).'</a>');
        if (array_key_exists($course->id,$htmlarray)) {
        echo "<a href=\"#modulesoverview$course->id\" onclick=\"toggle_visibility('modulesoverview".$course->id."');\">";
        echo "<img class=\"icon\" src=\"$CFG->wwwroot/myview/images/bookmark.png\">".get_string('waitingyourattention','myview','',$CFG->dirroot.'/myview/lang/')."</a>";
        echo '<div id="modulesoverview'.$course->id.'" style="display:none;">';
        echo '<a name="modulesoverview'.$course->id.'" ></a><br/>';
            foreach ($htmlarray[$course->id] as $modname => $html) {
                echo "<img class=\"bigicon\" height=\"32px\" width=\"32px\" src=\"$CFG->wwwroot/mod/$modname/icon.gif\">";
                echo "<div style=\"padding-right:40px;padding-left:40px;margin-top:-20px;margin-bottom:20px; background-color: beige;\">$html</div>";
            }
        echo '</div>';
        }

        print_simple_box_end();
        echo "</li>";
    }
    echo "</ul>";
}

function myview_get_category_fullpath(&$course) {
  global $CFG;

  // Course Category name, if appropriate. //(nadavkav patch)
  if (!$category = get_record("course_categories", "id", $course->category)) {
    //error("Category not known!");
  }

  $categoryfullpath ='';
  if ( !empty($category->path) ) {
    $categorypath = explode('/',$category->path); // display all parent category paths (nadavkav)

    foreach ($categorypath as $eachcategory) {
      if (!$singlecategory = get_record("course_categories", "id", $eachcategory)) {
        //error("Category not known!");
      }

      if (!empty($singlecategory)) {
        $categoryfullpath .= "<a href=\"$CFG->wwwroot/course/category.php?id=$singlecategory->id\">$singlecategory->name</a> >> ";
      }
    }
  }
  return $categoryfullpath;
}
?>
