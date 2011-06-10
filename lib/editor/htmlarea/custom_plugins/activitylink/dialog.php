<?php
  require_once("../../../../../config.php");

  $id = optional_param('id', SITEID, PARAM_INT);
  $langpath = $CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/activitylink/lang/';

  // Add ajax-related libs (nadavkav)
  //require_js(array('yui_yahoo', 'yui_event', 'yui_dom', 'yui_connection'));

/**
 * Given a course and a (current) coursemodule
 * This function returns a small popup menu with all the
 * course activity modules in it, as a navigation menu
 * The data is taken from the serialised array stored in
 * the course record
 *
 * @param course $course A {@link $COURSE} object.
 * @param course $cm A {@link $COURSE} object.
 * @param string $targetwindow ?
 * @return string
 * @todo Finish documenting this function
 */
function activitylist($course, $cm=NULL, $targetwindow='self') {

    global $CFG, $THEME, $USER;

    if (empty($THEME->navmenuwidth)) {
        $width = 50;
    } else {
        $width = $THEME->navmenuwidth;
    }

    if ($cm) {
        $cm = $cm->id;
    }

    if ($course->format == 'weeks') {
        $strsection = get_string('week');
    } else {
        $strsection = get_string('topic');
    }
    $strjumpto = get_string('jumpto');

    $modinfo = get_fast_modinfo($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    $section = -1;
    $selected = '';
    $url = '';
    $previousmod = NULL;
    $backmod = NULL;
    $nextmod = NULL;
    $selectmod = NULL;
    $logslink = NULL;
    $flag = false;
    $menu = array();
    $menustyle = array();

    $sections = get_records('course_sections','course',$course->id,'section','section,visible,summary');

    foreach ($modinfo->cms as $mod) {
        if ($mod->modname == 'label') {
            continue;
        }

        if ($mod->sectionnum > $course->numsections) {   /// Don't show excess hidden sections
            break;
        }

        if (!$mod->uservisible) { // do not icnlude empty sections at all
            continue;
        }

        if ($mod->sectionnum > 0 and $section != $mod->sectionnum) {
            $thissection = $sections[$mod->sectionnum];

            if ($thissection->visible or !$course->hiddensections or
                has_capability('moodle/course:viewhiddensections', $context)) {
                $thissection->summary = strip_tags(format_string($thissection->summary,true));
                if ($course->format == 'weeks' or empty($thissection->summary)) {
                    $menu[] = '--'.$strsection ." ". $mod->sectionnum;
                } else {
                    if (strlen($thissection->summary) < ($width-3)) {
                        $menu[] = '--'.$thissection->summary;
                    } else {
                        $menu[] = '--'.substr($thissection->summary, 0, $width).'...';
                    }
                }
                $section = $mod->sectionnum;
            } else {
                // no activities from this hidden section shown
                continue;
            }
        }

        $url = $mod->modname.'/view.php?id='. $mod->id;
        if ($flag) { // the current mod is the "next" mod
            $nextmod = $mod;
            $flag = false;
        }
        $localname = $mod->name;
        if ($cm == $mod->id) {
            $selected = $url;
            $selectmod = $mod;
            $backmod = $previousmod;
            $flag = true; // set flag so we know to use next mod for "next"
            $localname = $strjumpto;
            $strjumpto = '';
        } else {
            $localname = strip_tags(format_string($localname,true));
            $tl=textlib_get_instance();
            if ($tl->strlen($localname) > ($width+5)) {
                $localname = $tl->substr($localname, 0, $width).'...';
            }
            if (!$mod->visible) {
                $localname = '('.$localname.')';
            }
        }
        $menu[$url] = $localname;
        if (empty($THEME->navmenuiconshide)) {
            $menustyle[$url] = 'style="background-repeat: no-repeat; background-image: url('.$CFG->modpixpath.'/'.$mod->modname.'/icon.gif);"';  // Unfortunately necessary to do this here
        }
        $previousmod = $mod;
    }
    //Accessibility: added Alt text, replaced &gt; &lt; with 'silent' character and 'accesshide' text.
    //return $menu;
    echo "<select id=\"activitylist\">";
    foreach ($menu as $link => $activity) {
        if (substr($activity,0,2) == '--') {
            echo "<option disabled=\"disabled\" value='$link'>$activity</option>";
        } else {
            echo "<option $menustyle[$link] value='$link'>$activity</option>";
        }

    }
    echo "</select>";

}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title><?php echo get_string('title', 'activitylink','',$langpath); ?></title>
    <link rel="stylesheet" href="dialog.css" type="text/css" />


<script type="text/javascript">
//<![CDATA[

function Init() {
    document.getElementById("selectlinktitle").value = opener.selectedlink;
};

function onOK() {

  // pass data back to the calling window
  var param = new Object();
  var el = document.getElementById('activitylist');
  //var f_modurl = el.options[el.selectedIndex].text;
  var f_modurl = el.value;
  var f_href = document.getElementById('target').value;
  var f_title = document.getElementById('selectlinktitle').value;
  param['link'] = '<a target="'+f_href+'" href="<?php echo $CFG->wwwroot."/mod/"; ?>'+f_modurl +'" >'+f_title+'</a>';

  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

function cancel() {
  window.close();
  return false;
};
//]]>
</script>

<?php if (right_to_left() ) { echo '<style>body {direction:rtl;text-align:right;}</style>'; } ?>

</head>

<body onload="Init()">

<?php //print_header(); ?>

    <form action="dialog.php" method="get">

        <?php
        echo get_string('chooseactivity','activitylink','',$langpath);
        echo "<br/>";
        $course = get_record('course','id',$id);
        $output = activitylist($course );
        echo $output;

        ?><br/><br/>
        <?php echo get_string('settitle','activitylink','',$langpath); ?><br/>
        <input id="selectlinktitle" type="text" value="" maxlength="50" ><br/><br/>
        <?php echo get_string('choosetarget','activitylink','',$langpath); ?>
        <select id="target">
          <option value=""><?php print_string("linktargetnone","editor");?></option>
          <option value="_blank"><?php print_string("linktargetblank","editor");?></option>
          <option value="_self"><?php print_string("linktargetself","editor");?></option>
          <option value="_top"><?php print_string("linktargettop","editor");?></option>
        </select><hr/>

        <button type="button" onclick="return cancel();"><?php echo get_string("cancel","activitylink",'',$langpath);?></button>
        <button type="button" onclick="return onOK();"><?php echo get_string("set","activitylink",'',$langpath);?></button><br/>
    </form>

<?php //print_header(); ?>

</body>
</html>