<?php // $Id: menueditor.php
    require_once("../../config.php");

    $courseid = optional_param('courseid',0,PARAM_INT);
    $removeid= optional_param('removeid',0,PARAM_INT);

    if (!$site = get_site()) {
        error('Site isn\'t defined!');
    }

	// Check for user login and force
    if ($CFG->forcelogin) {
        require_login();
    }

	// A course ID is required
    if (empty($courseid)) {
        error("Must specify course id");
    }
 	require_login($courseid);

	// Here it checks for the user capability if user has enough privileges to update a Course in order to update a menu
    if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
          $adminediting = true; // !empty(true)=> !false  => true
    } else {
        $adminediting = false;
    }
 	// End check

	// Admin check, if not admin, throw an error and send user to course front page
    if(!$adminediting) {
	        error('', 'view.php?id='.$courseid.'');
    }

	// If data for a new menu was submitted, then add it
	if ($form = data_submitted() and confirm_sesskey() ) {

			if (!empty($form->addmenu)&&($form->r1==1)&&($form->r11==1)&&empty($form->subselect)) {
				$newmenu = new object();
				$newmenu->name = $form->addmenu;
				$newmenu->courseid = $form->courseid;
				$newmenu->topic_id = $form->mainselect;
				$newmenu->sortorder = 999;

		// Topic Menus - Adds the Menu values to the DB
			if (!($id=insert_record('menu_flyout', $newmenu))) {
				notify("Could not insert the new Menu '" . format_string(stripslashes($newmenu->name)) . "'");
			}

		// External URL's
		} elseif ($form->r11==2&&$form->r1==1&&empty($form->subselect)) {
			$newmenu = new object();
			$newmenu->name = $form->addmenu;
			$newmenu->courseid = $form->courseid;
			$newmenu2->topic_id = 999;
			$newmenu->sortorder = 999;
			$newmenu->url=$form->addurl;
			$newmenu->external=1;

			if (!($id1=insert_record('menu_flyout', $newmenu))) {
				notify("Could not insert the new Menu '" . format_string(stripslashes($newmenu->name)) . "'");
			}
		}
	}

 	// Function to check up to three level menu depth
    function parent_level($myid){

	$pRec=get_record('menu_flyout', 'id', $myid);
	   $l=0;
	    if ($pRec->parent==0 && $pRec->id!=0)
		  {
			  $l=1;
		  }
	    $pRec1 = get_record('menu_flyout', 'id', $pRec->parent);
		  if ($pRec1->parent==0 && $pRec1->id!=0)
		   {
			   $l=2;
		   }

	   $pRec2 = get_record('menu_flyout', 'id',$pRec1->parent);
		  if ($pRec2->parent==0  && $pRec2->id!=0)
		   {
			$l=3;
		   }

  	    $pRec3 = get_record('menu_flyout', 'id',$pRec2->parent);
		  if ($pRec3->parent==0  && $pRec3->id!=0)
		   {
			$l=4;
		   }

	    return $l;
    }

    // if data for a Sub Menu was submitted, then add it to the sub menu
    if ($form = data_submitted() and confirm_sesskey() ) {
        if (!empty($form->addmenu)&&!empty($form->subselect)&&!empty($form->mainselect)&&($form->r1==2)&&($form->r11==1)) {
			$newsubmenu = new object();
			$newsubmenu->depth=2;
			$newsubmenu->name = $form->addmenu;
			$newsubmenu->courseid = $form->courseid;
			$newsubmenu->parent = $form->subselect;
			$newsubmenu->topic_id = $form->mainselect;
			$newsubmenu->sortorder = 999;

			if($p = get_record('menu_flyout', 'id', $form->subselect)) {
				$newsubmenu->depth=$p->depth+1;
			}
	    // Check the menu Levels

	   $mylevel=0;
	   $mylevel=parent_level($form->subselect);

	   if ($mylevel==4)
	   		$levelflage=0;
	   else
			$levelflage=1;
	   // End Levels Check

	   //
       if ($levelflage==1){
       		if (!insert_record('menu_flyout', $newsubmenu)) {
            	notify("Could not insert the Sub Menu '" . format_string(stripslashes($newsubmenu->name)) . "'");
            }
	    } else {
			notify("Support only three Levels Deep!");
	    }
	} elseif (!empty($form->subselect)&&!empty($form->addmenu)&&($form->r1==2)&&($form->r11==2)) {
		 // if the url is external then add the following values to the DB
	    $newsubmenu = new object();
	    $newsubmenu->depth=2;
	    $newsubmenu->name = $form->addmenu;
	    $newsubmenu->courseid = $form->courseid;
	    $newsubmenu->parent = $form->subselect;
	    $newsubmenu->topic_id = 999;
	    $newsubmenu->sortorder = 999;
	    $newsubmenu->url=$form->addurl;
	    $newsubmenu->external=1;
	   if($p = get_record('menu_flyout', 'id', $form->subselect))
		    {
		    $newsubmenu->depth=$p->depth+1;
	      	}

	   // Check the menu depth Levels
	   $mylevel=0;
	   $mylevel=parent_level($form->subselect);

	   if ($mylevel==4)
			   $levelflage=0;
	  	 else
			   $levelflage=1;
	   // End Levels Check


        if ($levelflage==1){
        	if (!insert_record('menu_flyout', $newsubmenu)) {
            	notify("Could not insert the Sub Menu '" . format_string(stripslashes($newsubmenu->name)) . "'");
            }
	    } else {
			notify("Support only three Levels Deep!");
	    }
	   // end external url
	   }
    }

// remove individual menu items
	     if (!empty($removeid)){
  		  if ($deletecat = get_record('menu_flyout', 'id', $removeid) ) {
	                /// Send the children menues to live with their grandparent
                	if ($childcats = get_records('menu_flyout', 'parent', $deletecat->id,'depth')) {
	                    foreach ($childcats as $childcat) {
        	                if (! set_field('menu_flyout', 'parent', $deletecat->parent, 'id', $childcat->id)) {
                	            error('Could not update a child menu!', 'menueditor.php');
				}
					if (! set_field('menu_flyout', 'depth', ($childcat->depth-1),'id',$childcat->id)) {
						error('Could not update a child menu!', 'menueditor.php');
					}
		    	}
                   }

                // Finally delete the Menu itself
                if (delete_records('menu_flyout', 'id', $deletecat->id)) {
                }
    		}
   	 }

	// Delete all menu items script
	if ($form = data_submitted() and confirm_sesskey()){
		if (!empty($form->delall)){
			 delete_records('menu_flyout', 'courseid', $form->courseid);
         }
    }

///////////////////////////////////////////////////////////////////////////

 	// Build topic drop down list
 	$section = 1;
    $sectionmenu = array();
    $course->numsections=10;
    $sql="select distinct section,summary from  {$CFG->prefix}course_sections cs where cs.course='$courseid' and
	        cs.section NOT IN (SELECT m.topic_id FROM {$CFG->prefix}menu_flyout m	WHERE m.courseid = '$courseid') and cs.section <> 0";
    $numsections=count_records_sql($sql);

////////////////////////////////////////////////////////////////////////////

//For Header - Place Moodle site header
    $strcategories = 'Menu Flyout';
    $sqlc="select fullname,shortname from {$CFG->prefix}course where id='$courseid'";
    $strcours = get_record_sql($sqlc);
    $strcourses=$strcours->shortname;

            print_header("$site->shortname: $strcategories", $strcours->fullname,
                          $strcourses, '', '', true);
// End Header Section

	// Store the topic numbers and names as a variable
 $nonmenumembersoptions='';
    $nonmenumembers = get_recordset_sql($sql);
        if ($nonmenumembers != false) {
	      while ($user = rs_fetch_next_record($nonmenumembers)) {
		  	$nonmenumembersoptions .= "<option value=\"$user->section\">".$user->section.'-'.substr($user->summary,0,60)."</option>\n";
        }
    } else {
        $nonmenumembersoptions .= '<option>&nbsp;</option>';
    }
?>

	<form id="menuform" method="post" action="<?php echo $CFG->wwwroot .'/blocks/menu_flyout/menueditor.php';?>">
    <div>
    <input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
    <input type="hidden" name="courseid" value="<?php p($courseid); ?>" />
    <table summary="" border="0" cellpadding="5" cellspacing="0">
    <tr bgcolor="#DDDDDD">
    <td width="127" valign="top"><strong><?php print_string('stepone','block_menu_flyout'); ?></strong></td>
    <td width="78" valign="top"><label for="mainselect"><?php print_string('linktype','block_menu_flyout'); ?></label></td>
          <td width="321" valign="top">
    <fieldset class="invisiblefieldset">
    <p><input type=radio name="r11" id="r11" value="1"
    onfocus ="getElementById('menuform').subselect.disabled=true; getElementById('menuform').main.disabled=false; getElementById('menuform').addurl.disabled=true; getElementById('menuform').mainselect.disabled=false;" checked="checked" />
     <select name="mainselect" id="mainselect" />
              <?php
              // Print out the HTML for the topic selection drop down list
              echo $nonmenumembersoptions;
              ?>
          </select>
    </p> <?php print_string('or','block_menu_flyout'); ?> <p>
    <input type=radio name="r11" id="r22" value="2" onfocus ="getElementById('menuform').addurl.disabled=false; getElementById('menuform').mainselect.disabled=true;"/><?php print_string('externalurl','block_menu_flyout'); ?>:
     <input type="text" name="addurl" id="addurl" size="50" disabled="disabled" value="http://"
		  onfocus ="getElementById('menuform').mainselect.disabled=true;"
                            onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
                             //  if (keyCode == 13) {
                               //    getElementById('menuform').submit();
                                 //  }
                                getElementById('menuform').r11[1].checked=true;
                                if (getElementById('menuform').r11[0].checked)
                                {
                                    getElementById('menuform').addurl.disabled=true;
                                }else{
                                    getElementById('menuform').addurl.disabled=false;
                            }"  />
                            </p></fieldset>

    </td>
    <td width="903" valign="top">
      <p>Select a topic from your course (Your topic needs to have been setup before you add it to the menu structure),</p>
      <p>&nbsp;</p>
      <p>or you can add a url for an external site/page. You could even use the URL field to link to internal activities, files or pages.</p></td>
    </tr>
    <tr bgcolor="#E6E6E6">
        <td valign="top"><strong><?php print_string('steptwo','block_menu_flyout'); ?></strong></td>
        <td valign="top"><?php print_string('linktitle','block_menu_flyout'); ?></td>
        <td valign="top">
                 <input type="text" name="addmenu" id="addmenu" size="30"
                  onfocus ="getElementById('menuform').main.disabled=false;"
                                    onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
                                       if (keyCode == 13) {
                                           getElementById('menuform').submit();
                           }
                        if (getElementById('menuform').r1[0].checked)
                        {
                            getElementById('menuform').subselect.disabled=true;
                        }else{
                            getElementById('menuform').subselect.disabled=false;
        }"  />

        </td><td valign="top"> Give your topic a title that will show up on the menu as a link to the topic.
        </td>
    </tr>
    <tr bgcolor="#DDDDDD">
        <td valign="top"><strong><?php print_string('stepthree','block_menu_flyout'); ?></strong></td>
        <td valign="top"></td>
        <td>
        <fieldset class="invisiblefieldset">
        <p>
            <input type=radio name="r1" id="r1" value="1" onfocus ="getElementById('menuform').subselect.disabled=true; getElementById('menuform').main.disabled=false;" checked="checked" />
            <?php print_string('addasmenu','block_menu_flyout'); ?></p> <?php print_string('or','block_menu_flyout'); ?> <p>
            <input type=radio name="r1" id="r2" value="2"
             onfocus ="getElementById('menuform').subselect.disabled=false;"
                            /><?php print_string('or','block_menu_flyout'); ?> <?php print_string('addassubmenu','block_menu_flyout'); ?></p></fieldset>
            <br>
<?php
    $menumembersoptions='';
            $sql1="select * from {$CFG->prefix}menu_flyout where courseid='$courseid' and parent=0 order by id";
             $menumembers = get_recordset_sql($sql1);
                    if ($menumembers != false) {

                    while ($rs = rs_fetch_next_record($menumembers)) {
                            // Change URL's topic of 999 from the DB to state URL
                            if ($rs->topic_id == 999 || $rs->topic_id == 0) {
                                $rs->topic_id = "URL";
                            }
                        $menumembersoptions .= "<option value=\"$rs->id\">&#9500;".$rs->topic_id.'-'.$rs->name."</option>\n";
                         $sql2="select * from {$CFG->prefix}menu_flyout where parent='$rs->id' order by id";
                         $menumembers1 = get_recordset_sql($sql2);
                                if ($menumembers1 != false) {
                                while ($rs1 = rs_fetch_next_record($menumembers1)) {
                                        // Change URL's topic of 999 from the DB to state URL
                                        if ($rs1->topic_id == 999 || $rs1->topic_id == 0) {
                                            $rs1->topic_id = "URL";
                                        }
                                    $menumembersoptions.="<option value=\"$rs1->id\">&#9500;&#9472;".$rs1->topic_id.'-'.$rs1->name."</option>\n";

                                    // Level Three
                                    $sql3="select * from {$CFG->prefix}menu_flyout where parent='$rs1->id' order by id";
                                     $menumembers2 = get_recordset_sql($sql3);
                                        if ($menumembers2 != false) {
                                        while ($rs2 = rs_fetch_next_record($menumembers2)) {
                                            // Change URL's topic of 999 from the DB to state URL
                                            if ($rs2->topic_id == 999 || $rs2->topic_id == 0) {
                                                $rs2->topic_id = "URL";
                                            }
                                        $menumembersoptions.="<option value=\"$rs2->id\">&#9500;&#9472;&#9472;&#9472;".$rs2->topic_id.'-'.$rs2->name."</option>\n";
                                            // Level Three
                                             $sql4="select * from {$CFG->prefix}menu_flyout where parent='$rs2->id' order by id";
                                             $menumembers3 = get_recordset_sql($sql4);
                                                if ($menumembers3 != false) {
                                                // Level Four
                                                while ($rs3 = rs_fetch_next_record($menumembers3)) {
                                                    // Change URL's topic of 999 from the DB to state URL
                                                    if ($rs3->topic_id == 999 || $rs3->topic_id == 0) {
                                                        $rs3->topic_id = "URL";
                                                    }
                                                   $menumembersoptions.="<option value=\"$rs3->id\">&#9500;&#9472;&#9472;&#9472;&#9472;&#9472;".$rs3->topic_id.'-'.$rs3->name."</option>\n";
                                                }
                                                // End Level Four
                                            }
                                            // End Level Three
                                        }
                                    }
                                    // End Level two
                                }
                            }
                    }

                // Print out the HTML
                } else {
                    $menumembersoptions .= '<option>&nbsp;</option>';
                }
                $menumembersoptions=str_replace('<ul></ul>','',$menumembersoptions);
        ?>

        <fieldset class="invisiblefieldset">
              <select name="subselect" disabled="disabled" id="subselect" />
                 <?php echo $menumembersoptions ?>
              </select>
            <?php
                echo '<input type="hidden" name=courseid value="'.$courseid.'" />';
                echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            ?>
            </fieldset>

        </td><td>
        <p>Now choose whether the link will be a main link (always visible to the
        user) or a sub-menu link (sub-menu links will only be visible in a
        flyout/popout once the user hovers over the main menu link that it is
        attached to).</p>
        <p>Please note that the menu can only support up to 3 levels deep. This is
        to help the interface be more friendly to the user as more than 3 levels
        is deemed confusing rather than helpful to the user and the navigation.
        Please design your structure around this as it will really help your
        users navigate and remember their way around your course.</p>
        </td>
    </tr>
    <tr bgcolor="#E6E6E6">
        <td valign="top"></td>
        <td valign="top"></td>
        <td valign="top">
              <?php
                echo '<input type="hidden" name=courseid value="'.$courseid.'" />';
                echo '<input name="main" id="main" type="submit" value="'.get_string('additemtomenu','block_menu_flyout').'" />';
                echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                ?>
         </td>
         <td>
            <?php echo '<div class="loginfo"><input type="button" value="'.get_string('addmenutocourse','block_menu_flyout').'" onclick="window.location.href=\''.$CFG->wwwroot.'/course/view.php?id='.$courseid.'\'"></div>'; ?>
         </td>
     </tr>
   </table>
   </div>
   </form>
   <p></p>

<?php
	// Reset menu container variable to blank
	$menumembersoptions='';

	// Fetch the root of the menu from DB
	$SQL_1="select * from {$CFG->prefix}menu_flyout where courseid='$courseid' and parent=0 order by id";
	$menu1 = get_recordset_sql($SQL_1);
			if ($menu1 != false) {
			// Build Menu
			$menumembersoptions .= '<ul class="MenuBarVertical" id="MenuBar1">';
			while ($rs_1 = rs_fetch_next_record($menu1)) {
			// Check whether a menu arrow is needed
			$sql_2="select * from {$CFG->prefix}menu_flyout where parent='$rs_1->id' order by id";
			$cnt=count_records_sql($sql_2);
			$menu_bullet_img = '<img src="'.$CFG->wwwroot.'/blocks/menu_flyout/course.gif" />';
			$atag='<a ';
			if ($cnt != 0)
				$atag='<a class="MenuBarItemSubmenu" ';

			// Build level 1 of the menu
			$menumembersoptions .= '<li class="MenuBarItemIE">'.$atag.' href="menueditor.php?removeid='.$rs_1->id.'&amp;courseid='.$courseid.'&amp;sesskey='.$USER->sesskey.'">'. $menu_bullet_img .' '.$rs_1->name.'</a>';

			// Menu Level Two
			$menu2 = get_recordset_sql($sql_2);
			  if ($menu2 != false) {
				  $menumembersoptions .= '<ul>';
				  while ($rs_2 = rs_fetch_next_record($menu2)) {
					$sql_3="select * from {$CFG->prefix}menu_flyout where parent='$rs_2->id' order by id";
					$cnt=count_records_sql($sql_3);
					$atag='<a ';

					// Check whether a menu arrow is needed
					if ($cnt != 0)
						$atag='<a class="MenuBarItemSubmenu" ';

				  	  // Build level 2 of the menu
					  $menumembersoptions .='<li class="MenuBarItemIE">'.$atag.' href="menueditor.php?removeid='.$rs_2->id.'&amp;courseid='.$courseid.'&amp;sesskey='.$USER->sesskey.'">'. $menu_bullet_img .' '.$rs_2->name.'</a>';

					  // Menu Level Three
					 $menu3 = get_recordset_sql($sql_3);
					  if ($menu3 != false) {
						 $menumembersoptions .= '<ul>';
						 while ($rs_3 = rs_fetch_next_record($menu3)) {
							  $sql_4="select * from {$CFG->prefix}menu_flyout where parent='$rs_3->id' order by id";
							  $cnt=count_records_sql($sql_4);
							  $atag='<a ';

							// Check whether a menu arrow is needed
							if ($cnt != 0)
								$atag='<a class="MenuBarItemSubmenu" ';

				  	  	  // Build level 3 of the menu
						  $menumembersoptions .='<li class="MenuBarItemIE">'.$atag.' href="menueditor.php?removeid='.$rs_3->id.'&amp;courseid='.$courseid.'&amp;sesskey='.$USER->sesskey.'">'. $menu_bullet_img .' '.$rs_3->name.'</a>';

						  // Menu Level Four
						 $menu4 = get_recordset_sql($sql_4);
						  if ($menu4 != false) {
							  $menumembersoptions .= '<ul>';
							  while ($rs_4 = rs_fetch_next_record($menu4)) {

				  	  	  	  // Build level 4 of the menu
							  $menumembersoptions .='<li class="MenuBarItemIE"><a href="menueditor.php?removeid='.$rs_4->id.'&amp;courseid='.$courseid.'&amp;sesskey='.$USER->sesskey.'">'. $menu_bullet_img .' '.$rs_4->name.'</a></li>';
							}
							  $menumembersoptions .= '</ul>';
							}

						  // End Level Four
						  $menumembersoptions.='</li>';
							}
						 $menumembersoptions .= '</ul>';
						}

					  // End Level Three
					  $menumembersoptions.='</li>';
					}
				$menumembersoptions .= '</ul>';
				}
			$menumembersoptions.='</li>';
			}
			$menumembersoptions .= '</ul>';
		} else {
			$menumembersoptions .= '';
		}
	 $menumembersoptions=str_replace('<ul></ul>','',$menumembersoptions);

	// Styles for the menu preview
	if(!file_exists("$CFG->dataroot/".$COURSE->id."/do_not_delete/flyoutmenu.css"))
		{
		echo '<link href="'.$CFG->wwwroot.'/blocks/menu_flyout/flyoutmenu.css" rel="stylesheet" type="text/css" />'."\n";
		}
		else
		{
		echo '<link href="'.$CFG->wwwroot.'/file.php/'.$COURSE->id.'/do_not_delete/flyoutmenu.css" rel="stylesheet" type="text/css" />'."\n";
		}

	// IE 6 fixes for menu preview
	echo '	<!--[if IE6]>'."\n";
	echo '<style type="text/css" media="screen">'."\n";
	echo 'body {'."\n";
	echo '	behavior: url('.$CFG->wwwroot.'/blocks/menu_flyout/csshover2.htc); /* call hover behaviour file, needed for IE */'."\n";
	echo '	font-size: 100%; /* enable IE to resize em fonts */'."\n";
	echo '}'."\n";
	echo '#flyoutmenu ul li {'."\n";
	echo '	float: left; /* cure IE5.x "whitespace in lists" problem */'."\n";
	echo '	width: 100%;'."\n";
	echo '	padding-bottom: 2px;'."\n";
	echo '}'."\n";
	echo '#flyoutmenu ul li a {'."\n";
	echo '	height: 1%; /* make links honour display: block; properly */'."\n";
	echo '} '."\n";
	echo '#flyoutmenu h2 {'."\n";
	echo '	font: 11px arial, helvetica, sans-serif; '."\n";
	echo '	/* if required use ems for IE as it wont resize pixels */'."\n";
	echo '}'."\n";
	echo '.tab_holder {'."\n";
	echo '	margin-right: 14px;'."\n";
	echo '}'."\n";
	echo '.editbuttons {'."\n";
	echo '	float: right; '."\n";
	echo '}'."\n";
	echo '</style>'."\n";
	echo '<![endif]-->'."\n";

	//position:absolute; left: 600px;
	echo '<script src="'.$CFG->wwwroot.'/blocks/menu_flyout/flyoutmenu.js" type="text/javascript"></script>';
	echo '<div style="float:left; margin-left:30px;"><fieldset class="visiblefieldset">Menu Preview:</br>'."\n";
	echo 'PLEASE NOTE:</br>Clicking on a link will remove that link from the menu.</br></br><fieldset class="visiblefieldset" style="width:160px;"><div id="flyoutmenu">';
	echo $menumembersoptions;
	echo '</div></fieldset></fieldset></div></div>';
	echo '</div>';

	//Delete All Form
	echo '<div style="clear:both"><br /></div>';
	echo '<div style="float:right;"><form name=frmdelmenu action="menueditor.php" method="post">';
	echo '<input type="hidden" name=courseid value="'.$courseid.'" />';
	echo '<input name="delall" id="delall" type="submit" value="'.get_string('deleteall','block_menu_flyout').'" />';
	echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
	echo '</form></div>';

	// End Delete All Form
	echo '<div class="loginfo"><input type="button" value="'.get_string('addmenutocourse','block_menu_flyout').'" onclick="window.location.href=\''.$CFG->wwwroot.'/course/view.php?id='.$courseid.'\'"></div>';
	echo '<script src="'.$CFG->wwwroot.'/blocks/menu_flyout/flyout.js" type="text/javascript"></script>';

	print_footer();
?>
