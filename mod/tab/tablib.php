<?php
    global $tabinstancecount;

    if(!$tabinstancecount) {
        $tabinstancecount=1;
    }

    if (!$mod->visible) {
        echo "<span class=\"dimmed_text\">";
    }

    // Include JavaScript YUI, only once per course page
    if ($tabinstancecount<=1) {
        echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js"></script>'."\n";
        echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/element/element-beta-min.js"></script>'."\n";
        echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/yui/tabview/tabview-min.js"></script>'."\n";
    }

    // Get Tab instance data from Module ID
   if ($mod->id) {
        if (! $cm = get_record("course_modules", "id", $mod->id)) {
            error("Course Module ID was incorrect");
        }

        if (! $tab = get_record("tab", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    }

    // Should we display on Course's Frontpage?
    if ($tab->displayfp == 1 ) {

        // Include Special CSS, if defined by user
        echo '<style>'."\n";
        echo $tab->css;
        if (!$tab->displaymenu == 1) {
            //This function is used to replace the margin-left from 211 to 5 when no menu is selected
            str_replace($tab->menucss,"margin-left: 211px;","margin-left: 5px;");
        } else {
            echo $tab->menucss;
        }
        echo '</style>'."\n";

        // Display side-menu -- DISABLED
        echo '<div id="tab-menu-wrapper">'."\n";
        if ($tab->displaymenu == 1 or false) { // disable menu, always
            echo '<div id="left">'."\n";
            echo '  <table class="menutable" width="100%" border="0" cellpadding="4">'."\n";
            echo '      <tr>'."\n";
            echo '        <td class="menutitle">'.$tab->menuname.'</td>'."\n";
            echo '      </tr>'."\n";
            $i = 0; ///needed to determine color change on cell
            foreach ($results as $result){ /// foreach
                echo '  <tr';
                if ($i % 2) {
                    echo ' class="row">'."\n";
                } else {
                    echo '>'."\n";
                }
                echo    '<td><a href="view.php?id='.$result->id.'">'.$result->name.'</a></td>'."\n";
                echo '  </tr>'."\n";
                $i++;
            }
            echo '  </table>'."\n";
            echo '</div>';
        }

        // Display TABs
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
        echo '</script>'."\n";
        echo '  </div>'."\n";

        echo '</div>' ."\n";
        echo '</div>';

        // TAB instance counter. to supress more then one JS include of YUI code.
        $tabinstancecount++;

        add_to_log($course->id, "tab", "view", "view.php?id=$cm->id", "$tab->id");

        if (!$mod->visible) {
            echo "</span>";
        }
    }
?>