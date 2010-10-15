<?php


    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // newmodule ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $mindmap = get_record("mindmap", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $mindmap = get_record("mindmap", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $mindmap->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("mindmap", $mindmap->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "mindmap", "view", "view.php?id={$cm->id}", $mindmap->id,$cm->id);

/// Print the page header
    $strmindmaps = get_string("modulenameplural", "mindmap");
    $strmindmap  = get_string("modulename", "mindmap");

    $navlinks = array();
    $navlinks[] = array('name' => $strmindmaps, 'link' => "index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($mindmap->name), 'link' => '', 'type' => 'activityinstance');

    $navigation = build_navigation($navlinks);

    print_header_simple(format_string($mindmap->name), "", $navigation, "", "", true,
                  update_module_button($cm->id, $course->id, $strmindmap), navmenu($course, $cm));

	echo '<div class="box generalbox generalboxcontent boxaligncenter" id="intro">'.$mindmap->intro.'</div>'; // add intro section (nadavkav)
?>

<br /> <br />
		<div id="flashcontent" style="margin:0 auto; padding:0px; text-align:center; border:1px black solid; width:900px;">
		</div>
<script type="text/javascript" src="./swfobject.js"></script>
	<script type="text/javascript">
		// <![CDATA[

		function mm_save(str)
		{
			alert(decodeURI(str));
		}
		var so = new SWFObject("./viewer43.swf", "viewer", 900, 600, "9", "#FFFFFF");
		so.addVariable("load_url", "./xml.php?id=<?php echo $mindmap->id;?>");
		<?php if((!empty($USER->id) && $mindmap->userid == $USER->id) || $mindmap->editable == '1'):?>
			so.addVariable('save_url', "./save.php?id=<?php echo $mindmap->id;?>");
			so.addVariable('auto_node_url', "./auto_node.php");
			so.addVariable('editable', "true");
		<?php endif;?>
		so.addVariable("lang", "en");
		so.write("flashcontent");
		// ]]>
	</script>

<?php
    print_footer($course);
?>
