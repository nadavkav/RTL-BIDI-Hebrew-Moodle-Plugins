<?php // $Id: open_mods_set.php, v 0.51 10/03/2008 11:16AM jstein, jkrutsch Exp $ 
      // /blocks/openshare/open_mods_set.php - created for Moodle 1.9

//Moodle standards scripts
require_once ("../../config.php");
require_once ("../../course/lib.php");

$cancel = optional_param( 'cancel' );
$submit = optional_param( 'submit' );
$page = optional_param( 'page' );
$modid = optional_param('cmid', 0, PARAM_INT);
$license = optional_param('license', 0, PARAM_INT);
$status = optional_param('status', 0, PARAM_INT);
$sect = optional_param('sect', 0, PARAM_INT);

$courseid = optional_param('id', 0, PARAM_INT);
$id = $courseid;

$course = get_record('course', 'id', $courseid);
$course = get_record('course', 'id', $courseid);

$context = get_context_instance(CONTEXT_COURSE, $courseid);
require_login($courseid);

print_header(get_string('openshare','block_openshare').' '.$course->fullname,'' ,
                 '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$id.'">'.$course->shortname.'</a> ->'.get_string('openmodsset','block_openshare'));

//We may need to evaluate and replace this
if(!has_capability('moodle/course:update', $context)){
	print_error("You don't have privileges to use this page.");
}

//Get and check course
if (!$course = get_record("course", "id", $id)) {
    print_error("Course ID was incorrect (can't find it)");
}

//Query for OpenShare block installed at server level
if (!$openshare = get_record("block", "name", "openshare")){
    print_error('openshareinvis','block_shareopen');
}

//Query for OpenShare enabled at course level
if (!$opencourse = get_record("block_openshare_courses", "courseid", $courseid)){
    print_error('opencourseinvis','block_shareopen');
}

//Check to ensure Groups and Groupings are set up

$opengroup = get_record("groups", "courseid", $courseid, "name", "Course Members");
if($opengroup->id<1){
	$grouperror .= get_string('grouperror','block_openshare');
}
	
$opengrouping = get_record("groupings", "courseid", $courseid, "name", "Closed");
if($opengrouping->id<1){
	$grouperror .= get_string('groupingerror','block_openshare');
}
		
$opengroupinggroup = get_record("groupings_groups", "groupingid", $opengrouping->id, "groupid", $opengroup->id);
if($opengroupinggroup->id<1){
	$grouperror .= get_string('groupinggrouperror','block_openshare');
}

if($grouperror){
	print_error($grouperror . get_string('reenablecourse','block_openshare'));
}


//Begin Individual Module Insert/Update
//This is useful ONLY if course/view.php (and course/lib.php) was modified for OpenShare
if ((!empty($license) || !empty($status)) && !empty($modid)) {//was page loaded from course page (individual module update)?
	//Check that Course Module exists
      if (!$cm = get_record("course_modules", "id", $modid)) {
            print_error($courseid."This course module doesn't exist");
        }

		//ready to update or insert open_licenses
		
		//get license ids
		$ccid = get_record("block_openshare_licenses","name","CC by-nc-sa");
		$cid = get_record("block_openshare_licenses","name",'copyright');
		
		//prep dataobject for license
		$openshare = new object();
		$openshare->timemodified = time();
        $openshare->courseid = $courseid;
        $openshare->moduleid = $modid;
		
		//prep dataobject for door
		$opengrouped = new object();
		$opengrouped->timemodified = time();
		
		//prep license OR status as part of dataobject to be updated/inserted
		if (!empty($license)){
//print "lic".$license; 
        	$openshare->license = $license;
			//we must close door through status
			$status=1;
		}
		
			//status of open is  1, but Grouping needs to be open = 0
			if($status>1) {
				$opengrouped->groupmembersonly = 0;
				$opengrouped->groupingid = 0;
			} else if(($status==1) || ($license==$cid->id)){
				$opengrouped->groupmembersonly = 1;
				$opengrouped->groupingid = $opengrouping->id;
			}
			
			//make sure this module exists, and verify id
			$checkmod = get_record("course_modules", "course", $courseid, "id", $modid);
			if(!empty($checkmod)) {
				//add to date object and update record
				$opengrouped->id = $checkmod->id;
				update_record("course_modules", $opengrouped);
				//we update this record with data object later
				$openshare->status = $status;
			}
			
		//check for this module in open_modules table
		$checkmod = get_record("block_openshare_modules", "courseid", $courseid, "moduleid", $modid);
		if(!empty($checkmod)) {
			$openshare->id = $checkmod->id;
			update_record("block_openshare_modules", $openshare);
			//print "up";
		} else if (empty($checkmod)){
			insert_record("block_openshare_modules", $openshare);
			//print "ins";
		}
	
		rebuild_course_cache($courseid);
		if (SITEID == $courseid) {
			redirect($CFG->wwwroot);
		} else {
			redirect($CFG->wwwroot."/course/view.php?id=$courseid#section-".$sect, '', 0);//pass $sectionreturn in future edit
		}
		exit;
	} else // End individual module update. Begin en masse update
	if ($submit == "Save Changes") { //was actual form submitted?

       //check for the group, grouping, and association and prep error msg
	//FIX I'd like to put these checks in a single function later
	$opengroup = get_record("groups", "courseid", $courseid, "name", "Course Members");
	if($opengroup->id<1){
		$grouperror.="The Course Members Group does not exist! Please re-enable Open Course in the openshare block.\n\n";
	}

	$opengrouping = get_record("groupings", "courseid", $courseid, "name", "Closed");
	if($opengrouping->id<1){
		$grouperror.="The Closed Grouping does not exist! Please re-enable Open Course in the openshare block.\n\n";
	}
	//print $opengrouping->id;

	$opengroupinggroup = get_record("groupings_groups", "groupingid", $opengrouping->id, "groupid", $opengroup->id);
	if($opengroupinggroup->id<1){
		$grouperror.="The Closed Grouping is not associated with the Course Members Group! Please re-enable Open Course in the openshare block.\n\n";
	}
	//if any of these 3Gs errors show the error
	if($grouperror){
		print_error($grouperror);
		//otherwise, update course_modules & open_modules
	}

	/*
	* the insert function
	* insert into the openshare_modules table licenses or statuses for modules.
	* licenses are important to track, but statuses could be relied upon with course_modules grouping status
	* however the redundancy provides future development of openness in different ways
	*/
	
	function insert_openshare_modules($ins1, $ins2, $type1, $type2, $courseid){
		//print "start array: ";
		//print_r ($targetins);
		$modinsert = new object();
		$modinsert->timemodified = time();
		$modinsert->courseid=$courseid;
		foreach ($ins1 as $key=>$value){
			$modinsert->$type1=$value;
			$modinsert->moduleid=$key;
			$modinsert->$type2=$ins2[$key];
			insert_record("block_openshare_modules",$modinsert);
			//print "Module".$key." inserted, ".$type." set to .".$value.". ";
			//print_r ($modinsert);
			
		}
		reset($targetins);
	}

				
	function update_openshare_modules($targetset, $resultset, $type){
		//print"start array: ";
		//print_r ($resultset);
		foreach ($targetset as $key=>$value){
			//print_r ($value);
			foreach ($value as $subk=>$subv){
				//print $subk.$subv."<br>";
				if(array_key_exists($subk,$resultset)){
					//print "output <br/>id=".$key.", moduleid=".$subk."license=".$resultup[$subk];
					$modupdate = new object();
					$modupdate->timemodified = time();
					$modupdate->$type=$resultset[$subk];
					$modupdate->id=$key;
					update_record("block_openshare_modules",$modupdate);
					//print "Mod ".$key." updated, ".$type." set to ".$resultset[$subk].". ";
				}
			}
		}
		reset($resultset);
	}
	
	
	/*
	* Update course_modules grouping setting
	* Actually set the grouping and grouping status for a module to open or closed
	*/
	function block_openshare_groupingset($recordset,$ogid){
	
		foreach ($recordset as $key => $value) {
			//print "key (id): ".$key." value(status): ".$value." <br/>";
			$opengrouped = new object();
			$opengrouped->timemodifed = time();
			if ($value > 1) {
				$opengrouped->groupmembersonly = 0;
				$opengrouped->groupingid = 0;
				//print "Module ".$key." removed from Closed Grouping. ";
			} else if (($value==1)) {
				$opengrouped->groupmembersonly = 1;
				$opengrouped->groupingid = $ogid;
				//print "Module ".$key." added to Closed Grouping. ";
			}
			//make sure this module 
			//add to date object and update record
			$opengrouped->id = $key;
			update_record("course_modules", $opengrouped);
		}
		reset($recordset);
	}
	
	//start our arrays for license and status
	array ($statuses);
	array ($licenses);
	
	foreach($_POST as $key=>$value){
		//print $key.$value."<br/>";
		$type = explode("_", $key);
		if ($type[0]=="license"){
			//print "a licence".$value;
			$licenses[$type[1]]=$value;
		}
		else if ($type[0]=="status"){
			//print "a status".$value;
			$statuses[$type[1]]=$value;
		}
	}
			
	//check db for existing OSh module entries
	$existmods = get_records("block_openshare_modules","courseid",$course->id);
			
	//if there are entries, we need to do a comparison
	if(!empty($existmods)){
		//set up modules with statuses
		array ($modulestats);
		//set up modules with licenses
		array ($modulelics);
		//set up target modules with statuses
		array ($targetstatupdate);
		//set up target modules with licenses
		array ($targetlicupdate);
		//loop and fill arrays
		foreach($existmods as $mods){
			$targetstatupdate[$mods->id]= array($mods->moduleid => $mods->status);
			$targetlicupdate[$mods->id]=array($mods->moduleid => $mods->license);
			$modulestats[$mods->moduleid]=$mods->status;
			$modulelics[$mods->moduleid]=$mods->license;
		};
		
		//what are differences between form submitted and existing table entries?
		$licdiff = array_diff_assoc($licenses, $modulelics);
		$statdiff = array_diff_assoc($statuses, $modulestats);
		//which need to inserted (are not present already in table)?
		$licins = array_diff_key($licenses, $modulelics);
		$statins = array_diff_key($statuses, $modulestats);
		//Which existing entries need to updated
		$licup = array_diff_key($licdiff, $licins);
		$statup = array_diff_key($statdiff, $statins);
/*
		 print_r ($targetstatupdate);
		print "<pre><h3>Submitted Entries:</h3>\n";
		print "lic: ";
		print_r ($licenses);
		print "stat: ";
		print_r ($statuses);
		print "<h3>Existing db Entries:</h3>\n";
		print "lic: ";
		print_r ($modulelics);
		print "stat: ";
		print_r ($modulestats);
		print "<h3>Differences:</h3>\n";
		print "lic: ";
		print_r($licdiff);
		print "stat: ";
		print_r($statdiff);
		
		print "<h3>To INSERT:</h3>\n";
		print "lic: ";
		print_r ($licins);
		print "stat: ";
		print_r($statins);
		
		print "<h3>To UPDATE:</h3>\n";
		print "lic: ";
		print_r ($licup);
		print "stat: ";
		print_r ($statup);
		print "<h3>Update Query</h3>\n";
*/
	} else {
	//nothing to match to in the table, so insert licenses or statuses
		if(!empty($licenses)){
			$licins=$licenses;
		}
		if(!empty($statuses)){
			$statins=$statuses;
		}
	}
			
	/*print "<h3>To INSERT:</h3>\n";
	print "lic: ";
	print_r ($licins);
	print "stat: ";
	print_r($statins);
	
	print "<h3>To UPDATE:</h3>\n";
	print "lic: ";
	print_r ($licup);
	print "stat: ";
	print_r ($statup);
	print "<h3>Update Query</h3>\n";*/
	
	if(!empty($licins)){
		if(!empty($statins)){
			insert_openshare_modules($licins,$statins,'license','status',$id);
		print count($licins)." course module licenses inserted.</br>";
		print count($statins)." course module statuses inserted.</br>";
		}
	}
	
	if(!empty($licup)){
		update_openshare_modules($targetlicupdate,$licup,"license");
		print count($licup)." course module licenses updated.<br />";
	}
	
	if(!empty($statup)){
		update_openshare_modules($targetstatupdate,$statup,"status");
		print count($licup)." course module statuses updated.<br />";
	}
	
	//update Closed Grouping for all modules with status update
	if(!empty($statdiff)){
		block_openshare_groupingset($statdiff, $opengrouping->id);
		print count($statdiff)." existing course module Groupings updated.<br />";
	}
	
	//update Closed Grouping for all modules with status update
	if(!empty($statins)){
		block_openshare_groupingset($statins, $opengrouping->id);
		print count($statins)." new course module Groupings updated.<br />";
	}
	
	//Our Work Here Is Done
	rebuild_course_cache($courseid);
	if (SITEID == $courseid) {
		redirect($CFG->wwwroot);
	} else {
		//redirect($CFG->wwwroot.'/course/view.php?id='.$courseid, get_string('openmodssetfinished', 'block_openshare'), 3);
	}
	//exit;
//if not submitted just run the form
} else {

//NOTE the following functions are ugly as sin
//but were useful for rapidly changing form
	
/*
* make license select menu passing in a prefix, element name, 
* javascript target, db check results, and db results of licenses
*/

function make_license_select($selectname,$selectid,$jstarget,$checkmod,$licenses){
	$licenseselect = "\n\n\t".'<select name="'.$selectname.'" id="'.$selectid.'"';
	$licenseselect .= 'onchange="changeRadio(this.value,this.name,'.$jstarget.')"';
	$licenseselect .= '>'."\n";
	$licenseselect .= "\t\t".'<option value="0">Choose License</option>'."\n.";
	foreach ($licenses as $license){
		$licenseselect .= "\t\t".'<option id="'.$selectname.'_'.$license->id.'" value="'.$license->id.'"';
			if($license->id==$checkmod->license){
				$licenseselect .=' selected';
			}
		$licenseselect .= '>'.$license->name.'</option>'."\n";
	}
	$licenseselect .= "\t".'</select>'."\n";
	$licenseselect .= helpbutton('licenses','OpenShare','block_openshare',true,false,'',true);
	return $licenseselect;
}

/*
* make status radio menu passing in a prefix, element name, 
* javascript target, db check results, and db results of statuses
*/
function make_status_inputs ($label,$radioname,$radioid,$val,$jstarget,$checkmod){
	global $CFG;
	$statusinput ="\t".'<div class="doordoor"><input type="radio" value="'.$val.'" id="'.$radioid.'" name="'.$radioname.'"';
	$statusinput .= ' onclick="changeSelection('.$jstarget.')"';
	if($checkmod->status==$val){
		$statusinput.=" checked";
	}
	$statusinput .= ' />'."\n\t".'<label for="'.$radioid.'">';	
	$statusinput .= "\n\t\t".'<img src="'.$CFG->wwwroot.'/blocks/openshare/images/'.$label.'.png" alt="'.$label.'"/><span class="icon">'.$label.'</span>';
	$statusinput .= "\n\t".'</label></div>';
	return $statusinput;
}

	//print form for changing

	//Get the list of modules and licenses
	$licenses = get_records("block_openshare_licenses");
	$modnames = get_records("modules");
	
	//start menu var to output table
	$menu = '<table id="openshare">'."\n";
	$menu .= "\t".'<tr class="allmods">';
	$menu .= "\t\t".'<th class="ffield">';
	
	$menu .= 'Licenses <img src="'.$CFG->wwwroot.'/blocks/openshare/images/cc.png" alt="CC"/><img src="'. $CFG->wwwroot.'/blocks/openshare/images/c.png" alt="C"/><br/>';
	
	$menu .= make_license_select('alllics','alllics','\'\',0',$checkmod,$licenses);
	
	$menu .= '</th>'."\n";
	$menu .= '<th class="ffield">Openness<br/>';
	
	//make radios for status
	
	$menu .= make_status_inputs ('closed','allstats','allstats_closed',1,'1,\'alllics\',0',$checkmod);
	$menu .= make_status_inputs ('open','allstats','allstats_open',2,'3,\'alllics\',0',$checkmod);

	$menu .= '</th><th>Activities &amp; Resources</th>'."\n".'</tr>';
	
	//loop through the list of Moodle modules
	//check for modules of each type in this course
	//output form elements for each module type
	for($i=1;$i<=count($modnames);$i++){
		//$mods = ;
		$menu .= '<tr class="modtype">';
		$modid = $modnames[$i]->id;
		if (record_exists("course_modules", "course", $id, "module", $modnames[$i]->id)){
			$mods = get_records_select('course_modules', "course=$id AND module=$modid");
			
			//heading for license for category
			$menu .= '<th class="ffield">'."\n";
			
			//make the license select menu
			$menu .= make_license_select('modlics_'.$modid,$modid.'~modlics_'.$modid,'this.name,'.$modid,$checkmod,$licenses);
			
			//heading for status for category
			$menu .= '</th>'."\n\t".'<th class="ffield">';
			
			//make the radio buttons
			$menu .= make_status_inputs ('closed','statusmods_'.$modid,$modid.'~closedmods_'.$modid,'1','this.value,\'modlics_'.$modid.'\','.$modid.'',$checkmod);
			$menu .= make_status_inputs ('open','statusmods_'.$modid,$modid.'~openmods_'.$modid,'2','this.value,\'modlics_'.$modid.'\','.$modid.'',$checkmod);

			$menu .= '</th>'."\n\t".'<th>'.$modnames[$i]->name.'(s)</th>'."\n".'</tr>'."\n";
			
			//loop through each module instance in this course for this module type
			//output form elements for each module instance
			foreach ($mods as $mod){
				$thismod = get_record($modnames[$i]->name,"id",$mod->instance);
				$checkmod = get_record("block_openshare_modules","moduleid",$mod->id);
				$menu.= '<tr class="modinstance">'."\n\t\t".'<td class="ffield">';

				//make the license select menu
				$menu .= make_license_select('license_'.$mod->id,$modid.'~license_'.$mod->id,$mod->id,$checkmod,$licenses);
				$menu.= '</td>'."\n\t\t".'<td class="ffield">';
				
				//make the radio buttons
				$menu .= make_status_inputs ('closed','status_'.$mod->id,$modid.'~closed_'.$mod->id,'1','1,\'license_'.$mod->id.'\'',$checkmod);
				$menu .= make_status_inputs  ('open','status_'.$mod->id,$modid.'~open_'.$mod->id,'2','3,\'license_'.$mod->id.'\'',$checkmod);
				
				$menu .= '</td>'."\n\t\t".'<td>'.$thismod->name.'</td>'."\n\t</tr>\n";
			}
		reset($checkmod);
		}
	}
	
		
//Print form
print_heading_with_help(get_string('openmodsset','block_openshare'),'openshare','block_openshare');
print_simple_box_start("center");
print "<h2>".$course->fullname." (".$course->shortname.")</h2>";
?>
	<script type="text/javascript" src="set_open_mods.js"></script>

	<style type="text/css">
		#openshare { border: 2px solid gray; }
		#openshare td, #openshare th { border: 1px solid gray; border-collapse: collapse; text-align: left; padding: 4px 6px; }
		#openshare th {text-transform: capitalize; font-weight: normal; }
		tr.allmods { border: 2px solid; }
		.allmods th { font-size: 130%; text-align: center; vertical-align: top; }
		.modtype { border: 2px solid gray; text-transform: capitalize; }
		.modtype th { font-size: 120%;}
		#openshare tr.modtype th.ffield { padding-left: 1em;  background: url("<?=$CFG->wwwroot?>/blocks/openshare/images/break.png") repeat-y .75em; }
		td.ffield,th.ffield {text-align: center;}
		.ffield select {margin-left: .4em; }
		#openshare tr.modinstance { font-size: 90%; }
		#openshare tr.modinstance td { padding-left: 2em; background: url("<?=$CFG->wwwroot?>/blocks/openshare/images/break.png") repeat-y 1.55em; }
		#openshare .doordoor { width: 50%; float: left; }
		label span { font-size: 10px; font-weight: normal; text-transform: lowercase; padding-left: .5em; display: block;}
	</style>
		<form action="<?=$_SERVER[PHP_SELF] ?>?id=<?=$id ?>" method="post" id="form1" name="form1">
		<?php
		echo $menu; 
		?>
		</table>
			<div id="ip_submit_buttons">
				<input type="submit" value="Save Changes" name="submit" />
				<input type="reset" value="Reset" />
				<input type="submit" value="Cancel" name="cancel"/>
			</div>
		</form>
	</div>
<?php
	}
    print_simple_box_end();

    //Print footer
    print_footer();
?>