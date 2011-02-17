<?php  // $Id: view.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * TAB
 *
 * @author : Patrick Thibaudeau
 * @version $Id: version.php,v 1.0 2007/07/01 16:41:20
 * @package tab
 **/

    require_once("../../config.php");
    require_once("lib.php");


    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // tab ID
	$printfriendly  = optional_param('printfriendly');  // tab ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $tab = get_record("tab", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $tab = get_record("tab", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $tab->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("tab", $tab->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "tab", "view", "view.php?id=$cm->id", "$tab->id");

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    $strtabs = get_string("modulenameplural", "tab");
    $strtab  = get_string("modulename", "tab");

	if ($printfriendly) { // this is good for iframes embedding inside the course (nadavkav)
		print_header();
	} else {
		print_header("$course->shortname: $tab->name", "$course->fullname",
					"$navigation <a href=index.php?id=$course->id>$strtabs</a> -> $tab->name",
					"", "", true, update_module_button($cm->id, $course->id, $strtab),
					navmenu($course, $cm));
	}

///SQL to gather all Tab modules within the course. Needed if display tab menu is selected
	$results = get_records_sql('SELECT '.$CFG->prefix.'course_modules.id as id, '.$CFG->prefix.'tab.name as name, '
	                           .$CFG->prefix.'tab.menuname as menuname FROM ('.$CFG->prefix.'modules INNER JOIN '
							   .$CFG->prefix.'course_modules ON '
							   .$CFG->prefix.'modules.id = '.$CFG->prefix.'course_modules.module) INNER JOIN '
							   .$CFG->prefix.'tab ON '.$CFG->prefix.'course_modules.instance = '
							   .$CFG->prefix.'tab.id WHERE ((('.$CFG->prefix.'modules.name)="Tab") AND (('
							   .$CFG->prefix.'course_modules.course)="'.$course->id.'")) ORDER BY '.$CFG->prefix.'tab.name;');

/// Print the main part of the page
/// Gather required javascript files and CSS information
	echo "\n";
	echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js"></script>'."\n";
	echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/element/element-beta-min.js"></script>'."\n";
	echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/tabview/tabview-min.js"></script>'."\n";
	echo '<style>'."\n";
	echo $tab->css;
	if (!$tab->displaymenu == 1) {
	//This function is used to replace the margin-left from 211 to 5 when no menu is selected
		str_replace($tab->menucss,"margin-left: 211px;","margin-left: 5px;");
	} else {
	echo $tab->menucss;
	}
	echo '</style>'."\n";
	echo '<div id="tab-menu-wrapper">'."\n";
	if ($tab->displaymenu == 1) {
	echo '<div id="left">'."\n";
	echo '	<table class="menutable" width="100%" border="0" cellpadding="4">'."\n";
	echo '  	<tr>'."\n";
	echo '  	  <td class="menutitle">'.$tab->menuname.'</td>'."\n";
	echo '  	</tr>'."\n";
		$i = 0; ///needed to determine color change on cell
	foreach ($results as $result){ /// foreach
    echo '	<tr';
		if ($i % 2) {
			echo ' class="row">'."\n";
			} else {
			echo '>'."\n";
			}

    echo 	'<td><a href="view.php?id='.$result->id.'">'.$result->name.'</a></td>'."\n";
  	echo '	</tr>'."\n";
	$i++;
	}
	echo '	</table>'."\n";
	echo '</div>';
	}
    echo '<div id="tabcontent">'."\n";
	echo '<div class=" yui-skin-sam">'."\n";
	echo '<div id="Tabs" class="yui-navset">'."\n";
	echo '  <ul class="yui-nav">'."\n";
	echo '    <li class="selected"><a href="#tab1"><em>'.$tab->tab1.'</em></a></li>'."\n";
	if (!empty($tab->tab2)){
	echo '    <li><a href="#tab2"><em>'.$tab->tab2.'</em></a></li>'."\n";
	}
	if (!empty($tab->tab3)){
	echo '    <li><a href="#tab3"><em>'.$tab->tab3.'</em></a></li>'."\n";
	}
	if (!empty($tab->tab4)){
	echo '    <li><a href="#tab4"><em>'.$tab->tab4.'</em></a></li>'."\n";
	}
	if (!empty($tab->tab5)){
	echo '    <li><a href="#tab5"><em>'.$tab->tab5.'</em></a></li>'."\n";
	}
	if (!empty($tab->tab6)){
	echo '    <li><a href="#tab6"><em>'.$tab->tab6.'</em></a></li>'."\n";
	}
	if (!empty($tab->tab7)){
	echo '    <li><a href="#tab7"><em>'.$tab->tab7.'</em></a></li>'."\n";
	}
	if (!empty($tab->tab8)){
	echo '    <li><a href="#tab8"><em>'.$tab->tab8.'</em></a></li>'."\n";
	}
	echo '  </ul>'."\n";

   $options = NULL;
   $options->noclean = true;

	echo '  <div class="yui-content">'."\n";
	echo '     <div id="tab1"><p>'.format_text($tab->tab1content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab1"><p>'.$tab->tab1content.'</p>'."\n";
	echo '</div>'."\n";
	if (!empty($tab->tab2)){
	echo '  <div id="tab2"><p>'.format_text($tab->tab2content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab2"><p>'.$tab->tab2content.'</p>'."\n";
	echo '</div>'."\n";
	}
	if (!empty($tab->tab3)){
	echo '  <div id="tab3"><p>'.format_text($tab->tab3content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab3"><p>'.$tab->tab3content.'</p>'."\n";
	echo '</div>'."\n";
	}
	if (!empty($tab->tab4)){
	echo '  <div id="tab4"><p>'.format_text($tab->tab4content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab4"><p>'.$tab->tab4content.'</p>'."\n";
	echo '</div>'."\n";
	}
	if (!empty($tab->tab5)){
	echo '  <div id="tab5"><p>'.format_text($tab->tab5content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab5"><p>'.$tab->tab5content.'</p>'."\n";
	echo '</div>'."\n";
	}
	if (!empty($tab->tab6)){
	echo '  <div id="tab6"><p>'.format_text($tab->tab6content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab6"><p>'.$tab->tab6content.'</p>'."\n";
	echo '</div>'."\n";
	}
	if (!empty($tab->tab7)){
	echo '  <div id="tab7"><p>'.format_text($tab->tab7content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab7"><p>'.$tab->tab7content.'</p>'."\n";
	echo '</div>'."\n";
	}
	if (!empty($tab->tab8)){
	echo '  <div id="tab8"><p>'.format_text($tab->tab8content, FORMAT_HTML,$options).'</p>'."\n";
	//echo '     <div id="tab8"><p>'.$tab->tab8content.'</p>'."\n";
	echo '</div>'."\n";
	}
	echo '<script type="text/javascript">'."\n";
	echo "    var tabView = new YAHOO.widget.TabView('Tabs');"."\n";
  echo "    var url = location.href.split('#');"."\n";
  echo "     if (url[1]) {"."\n";
  echo "         //We have a hash"."\n";
  echo "         var tabHash = url[1];"."\n";
  echo "         var tabs = tabView.get('tabs');"."\n";
  echo "        for (var i = 0; i < tabs.length; i++) {"."\n";
  echo "              if (tabs[i].get('href') == '#' + tabHash) {"."\n";
  echo "                  tabView.set('activeIndex', i);"."\n";
  echo "                  break;"."\n";
  echo "              }"."\n";
  echo "          }"."\n";
  echo "      }"."\n";
	echo '</script>'."\n";
    echo '	</div>'."\n";

	echo '</div>' ."\n";
	echo '</div>';



/// Finish the page
	if ($printfriendly) {
		//print_footer();
	} else {
		print_footer($course);
	}

?>
