<?php //$Id: block_menu_flyout.php

class block_menu_flyout extends block_base {
    function init() {
        $this->title = get_string('menu_flyout','block_menu_flyout');
        $this->version = 2007101545; // 2007101541;
    }

    function has_config() {return true;}

    function get_content() {
        global $USER, $CFG, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }
$menumembersoptions='';

	$SQL="select * from {$CFG->prefix}menu_flyout where courseid='$COURSE->id' and parent=0 order by id";
	$menumembers = get_recordset_sql($SQL);
        if ($menumembers != false) {
 // The following code is replacement of a recursive function, which can later be developed, it supports 3 levels from the root flyout menus

		while ($rs = rs_fetch_next_record($menumembers)) {
			$sql1="select * from {$CFG->prefix}menu_flyout where parent='$rs->id' order by id";
			$cnt=count_records_sql($sql1);
			$menu_bullet_img = '<img src="'.$CFG->wwwroot.'/blocks/menu_flyout/course.gif" />';
			$atag='<a ';
			if ($cnt != 0)
				$atag='<a class="MenuBarItemSubmenu" ';

			if ($rs->external==1)
				//This prints the a tag for an external URL
				$menumembersoptions .= '<li class="MenuBarItemIE">'.$atag.' href="'.$rs->url.'" target="_new">'. $menu_bullet_img .' '.$rs->name.'</a>';
			else
				//This prints the a tag for the relative 'topic' URL's
				$menumembersoptions .= '<li class="MenuBarItemIE">'.$atag.' href='.$CFG->wwwroot.'/course/view.php?id='.$rs->courseid.'&topic='.$rs->topic_id.'>'. $menu_bullet_img .' '.$rs->name.'</a>';
  			$menumembers1 = get_recordset_sql($sql1);
		 if ($menumembers1 != false) {
		 $menumembersoptions.='<ul>';
		 while ($rs1 = rs_fetch_next_record($menumembers1)) {
			$sql2="select * from {$CFG->prefix}menu_flyout where parent='$rs1->id' order by id";
			$cnt=count_records_sql($sql2);
			$atag='<a ';
			if ($cnt != 0)
				$atag='<a class="MenuBarItemSubmenu" ';
			 if ($rs1->external==1)
				//This prints the a tag for an external URL
			   $menumembersoptions .= '<li class="MenuBarItemIE">'.$atag.' href="'.$rs1->url.'" target="_new">'. $menu_bullet_img .' '.$rs1->name.'</a>';
			 else
				//This prints the a tag for the relative 'topic' URL's
			   $menumembersoptions.='<li class="MenuBarItemIE">'.$atag.' href='.$CFG->wwwroot.'/course/view.php?id='.$rs1->courseid.'&topic='.$rs1->topic_id.'>'. $menu_bullet_img .' '.$rs1->name.'</a>';
	  //Sub Level Two

			 $menumembers2 = get_recordset_sql($sql2);
			 if ($menumembers2 != false) {
				$menumembersoptions.='<ul>';
				while ($rs2 = rs_fetch_next_record($menumembers2)) {
				  	 $sql3="select * from {$CFG->prefix}menu_flyout where parent='$rs2->id' order by id";
					$cnt=count_records_sql($sql3);
					$atag='<a ';
					if ($cnt != 0)
						$atag='<a class="MenuBarItemSubmenu" ';
					if ($rs2->external==1)
						//This prints the a tag for an external URL
						$menumembersoptions .= '<li class="MenuBarItemIE">'.$atag.' href="'.$rs2->url.'" target="_new">'. $menu_bullet_img .' '.$rs2->name.'</a>';
					else
						//This prints the a tag for the relative 'topic' URL's
						$menumembersoptions.='<li class="MenuBarItemIE">'.$atag.' href='.$CFG->wwwroot.'/course/view.php?id='.$rs2->courseid.'&topic='.$rs2->topic_id.'>'. $menu_bullet_img .' '.$rs2->name.'</a>';
	  //Sub Level Three

				 $menumembers3 = get_recordset_sql($sql3);
				 if ($menumembers3 != false) {
					 $menumembersoptions.='<ul>';
					 while ($rs3 = rs_fetch_next_record($menumembers3)) {
					       if ($rs3->external==1)
						//This prints the a tag for an external URL
						$menumembersoptions .= '<li class="MenuBarItemIE"><a href="'.$rs3->url.'" target="_new">'. $menu_bullet_img .' '.$rs3->name.'</a>';
					       else
						//This prints the a tag for the relative 'topic' URL's
					  	$menumembersoptions.='<li class="MenuBarItemIE"><a href='.$CFG->wwwroot.'/course/view.php?id='.$rs3->courseid.'&topic='.$rs3->topic_id.'>'. $menu_bullet_img .' '.$rs3->name.'</a></li>';
					}
				 $menumembersoptions.='</ul>';
				}
	  //End Sub Level Three
				$menumembersoptions.='</li>';
				}
			$menumembersoptions.='</ul>';
			}
	  //End Sub Level Two
			$menumembersoptions.='</li>';
			}
	$menumembersoptions.='</ul>';
	}
	$menumembersoptions .= '</li>';
        }


    } else {
        $menumembersoptions .= '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'">Menu Maker</a>';
    }
	// Remove Blank Menus
	$menumembersoptions =str_replace('<ul></ul>','',$menumembersoptions);


	$this->content->text = "";
//$this->content->text .='<script src="'.$CFG->wwwroot.'/blocks/menu_editor/SpryAssets/SpryMenuBar.js" type="text/javascript"></script>';
//<link href="SpryAssets/SpryMenuBarVertical.css" rel="stylesheet" type="text/css" />

		if(!file_exists("$CFG->dataroot/".$COURSE->id."/do_not_delete/flyoutmenu.css"))
		{
			$this->content->text.='<link href="'.$CFG->wwwroot.'/blocks/menu_flyout/flyoutmenu.css" rel="stylesheet" type="text/css" />'."\n";
		}
		else
		{
			$this->content->text.='<link href="'.$CFG->wwwroot.'/file.php/'.$COURSE->id.'/do_not_delete/flyoutmenu.css" rel="stylesheet" type="text/css" />'."\n";
		}
	$this->content->text.='	<!--[if IE6]>'."\n";
	$this->content->text.='<style type="text/css" media="screen">'."\n";
	$this->content->text.='body {'."\n";
	$this->content->text.='	behavior: url('.$CFG->wwwroot.'/blocks/menu_flyout/csshover2.htc); /* call hover behaviour file, needed for IE */'."\n";
	$this->content->text.='	font-size: 100%; /* enable IE to resize em fonts */'."\n";
	$this->content->text.='}'."\n";
	$this->content->text.='#flyoutmenu ul li {'."\n";
	$this->content->text.='	float: left; /* cure IE5.x "whitespace in lists" problem */'."\n";
	$this->content->text.='	width: 100%;'."\n";
	$this->content->text.='	padding-bottom: 2px;'."\n";
	$this->content->text.='}'."\n";
	$this->content->text.='#flyoutmenu ul li a {'."\n";
	$this->content->text.='	height: 1%; /* make links honour display: block; properly */'."\n";
	$this->content->text.='} '."\n";
	$this->content->text.='#flyoutmenu h2 {'."\n";
	$this->content->text.='	font: 11px arial, helvetica, sans-serif; '."\n";
	$this->content->text.='	/* if required use ems for IE as it wont resize pixels */'."\n";
	$this->content->text.='}'."\n";
	$this->content->text.='.tab_holder {'."\n";
	$this->content->text.='	margin-right: 14px;'."\n";
	$this->content->text.='}'."\n";
	$this->content->text.='</style>'."\n";
	$this->content->text.='<![endif]-->'."\n";
	$this->content->text .='<script src="'.$CFG->wwwroot.'/blocks/menu_flyout/flyoutmenu.js" type="text/javascript"></script>';
	$this->content->text .= '<ul class="MenuBarVertical" id="MenuBar1">'.$menumembersoptions.'</ul>';
	$this->content->text .='<script src="'.$CFG->wwwroot.'/blocks/menu_flyout/flyout.js" type="text/javascript"></script>';

// Checking User Mode
	if ($USER->editing)
	{
		$this->content->text .= '<div><a href="'.$CFG->wwwroot.'/blocks/menu_flyout/menueditor.php?courseid='.$COURSE->id.'">';
		$this->content->text .= '<img src="'.$CFG->wwwroot.'/pix/i/edit.gif"/>'.get_string('menueditor','block_menu_flyout').'</a>';
		$this->content->text .= "</div>";
	}


        return $this->content;
    }
}

?>



