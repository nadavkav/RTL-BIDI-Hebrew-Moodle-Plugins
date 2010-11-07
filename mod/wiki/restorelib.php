<?PHP //Created by Antonio Casta�o & Juan Casta�o
	//Updated and fixed by Bernardino Todoli
    //This php script contains all the stuff to backup/restore
    //dfwiki mods
require_once ($CFG->libdir.'/ddllib.php');

    function wiki_restore_mods($mod,$restore) {

        global $CFG, $COURSE;
        $status = true;    
		$ewiki=false;   
        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);
        $info = $data->info; 
        //Now check if the mod is a ewiki or a newwiki mod, an create the
        //array $data with the info with the same format in both cases.
        if (isset($info["MOD"]["#"]["ENTRIES"])){           
            $data=wiki_read_xml_ewiki($info["MOD"]["#"],$restore);
            $ewiki=true;//Use this later.                   
        }else{
            $data=wiki_read_xml_wiki($info["MOD"]["#"],$restore->course_id);

        }
        if(!isset($data)){
        	$status = false;
        }//$data is an array with the info
        else{
            $schema=wiki_create_schema();
            $wiki=wiki_validate($data['wiki'],$schema['wiki']);
            $wiki->course = $restore->course_id;
            $id = get_record_sql('SELECT id
					FROM '. $CFG->prefix.'wiki WHERE wikicourse='.addslashes($wiki->course));
			if(empty($id->id) && $wiki->wikicourse!='0'){
            	//The course is a wiki format course, then set the correct wikicourse id
                $wiki->wikicourse=$wiki->course;
            }
			else
				$wiki->wikicourse = 0;
            //Now insert the wiki record
            $newid = insert_record("wiki",$wiki);
            //Do some output
            echo "<li>".get_string("modulename",'wiki')." \"".format_string(stripslashes($wiki->name),true)."\"</li>";
            backup_flush(300);

            if ($newid) {

                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if ($restore->mods['wiki']->userinfo){
                    $oldid = backup_todb($info['MOD']['#']['ID']['0']['#']);
                    $order = backup_todb($info['MOD']['#']['ORDER']['0']['#']);
                    //Now, build the wiki record structure
                    $schemapages=$schema['wiki_pages'];
                    $datapages=$data['wiki_pages'];
                    $numpages=count($datapages);
                    for($i=0; $i<$numpages; $i++){
                        //$page=wiki_validate_page($datapages[$i],$schemapages);
                        $page=wiki_validate($datapages[$i],$schemapages);
                        $page->dfwiki=$newid;
                        //We have to recode the userid field
                        $user = backup_getid($restore->backup_unique_code,"user",$page->userid);
                        if ($user) {
                            $page->userid = $user->new_id;
                        }
                        //We have to recode the ownerid field
                        if($ewiki){
                        	if ($wiki->studentmode!='0'){
                                $page->ownerid=$page->userid;
                            }
                        }
                        $owner = backup_getid($restore->backup_unique_code,"owner",$page->ownerid);
                        if ($owner) {
                            $page->ownerid = $owner->new_id;
                        }
                        //We have to recode the groupid field
                        $group = backup_getid($restore->backup_unique_code,"group",$page->groupid);
                        if ($group) {
                            $page->groupid = $group->new_id;
                        }
                        //The structure is equal to the db
                        $oldpageid=$page->id;
                        $newpageid = insert_record ("wiki_pages",$page);
						$page->id = $oldpageid;
                        //Do some output
                        if (($i+1) % 50 == 0) {
                            echo ".";
                            if (($i+1) % 1000 == 0) {
                                echo "<br />";
                            }
                            backup_flush(300);
                        }
            
                        if ($newpageid) {
                        //We have the newid, update backup_ids
                        //$page->id is now the page old id
                            backup_putid($restore->backup_unique_code,"wiki_pages",$page->id,$newpageid);

                        } else {
                            $status = false;
                        }
                    } 
                    
                    //Restore synonymous for the newwiki mod 
                    if(!$ewiki && isset($data['wiki_synonymous'])){
                        $schemasyn=$schema['wiki_synonymous'];
                        $datasyns=$data['wiki_synonymous'];
                        $numsyns=count($datasyns);
                        for($i=0; $i<$numsyns; $i++){
                    	   $syn=wiki_validate($datasyns[$i],$schemasyn);
                            $syn->dfwiki=$newid;
                            //We have to recode the userid field
                            $owner = backup_getid($restore->backup_unique_code,"owner",$syn->ownerid);
                            if ($owner) {
                                $syn->ownerid = $owner->new_id;
                            }
                            $group = backup_getid($restore->backup_unique_code,"group",$syn->groupid);
                            if ($group) {
                                $syn->groupid = $group->new_id;
                            }
                            //The structure is equal to the db
                            $newsynid = insert_record ("wiki_synonymous",$syn);
                        
                            //Do some output
                            if (($i+1) % 50 == 0) {
                                echo ".";
                                if (($i+1) % 1000 == 0) {
                                    echo "<br />";
                                }
                                backup_flush(300);
                            }
                        
                            if ($newsynid){
                        	//We have the newid, update backup_ids
                            backup_putid($restore->backup_unique_code,"wiki_synonymous",$syn->id,$newsynid);

                            } else {
                                $status = false;
                            }
                        
                        }
                    }
                    //Now copy moddata associated files
                    $e_wiki = $data['wiki_pages'][0];
                    wiki_restore_files ($oldid, $restore, $order, $e_wiki);
                }
            }
            else {
                $status = false;
            }           
        }
        return $status;
    }
            
    //This function restores the wiki_pages
    function wiki_pages_restore_mods($old_dfwiki_id,$new_dfwiki_id,$info,$restore) {

        global $CFG;

        $status = true;
        
        //Get the pages array
        $pages = $info['MOD']['#']['PAGES']['0']['#']['PAGE'];

        //Iterate over pages
        for($i = 0; $i < sizeof($pages); $i++) {

            $pag_info = $pages[$i];
            //We'll need this later!!
            $oldid = backup_todb($pag_info['#']['ID']['0']['#']);
            //Now, build the wiki_PAGES record structure
            $page->pagename = backup_todb($pag_info['#']['PAGENAME']['0']['#']);
            $page->version = backup_todb($pag_info['#']['VERSION']['0']['#']);
            $page->content = preg_replace("/\n/", "\r\n", backup_todb($pag_info['#']['CONTENT']['0']['#']));
            $page->author = backup_todb($pag_info['#']['AUTHOR']['0']['#']);

            $page->userid = backup_todb($pag_info['#']['USERID']['0']['#']);
            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$page->userid);
            if ($user) {
                $page->userid = $user->new_id;
            }
            
            $page->ownerid = backup_todb($pag_info['#']['OWNERID']['0']['#']);
            //We have to recode the userid field
            $owner = backup_getid($restore->backup_unique_code,"owner",$page->ownerid);
            if ($owner) {
                $page->ownerid = $owner->new_id;
            }

            $page->created = backup_todb($pag_info['#']['CREATED']['0']['#']);
            $page->lastmodified = backup_todb($pag_info['#']['LASTMODIFIED']['0']['#']);
            $page->refs = backup_todb($pag_info['#']['REFS']['0']['#']);
            $page->hits = backup_todb($pag_info['#']['HITS']['0']['#']);
            $page->editable = backup_todb($pag_info['#']['EDITABLE']['0']['#']);
            $page->dfwiki = $new_dfwiki_id;
            $page->editor = backup_todb($pag_info['#']['EDITOR']['0']['#']);
            $groupname = backup_todb($pag_info['#']['GROUPNAME']['0']['#']);
            if (!empty($groupname)) $page->groupid = '0';
            else $page->groupid = wiki_groupid($groupname, $restore->course_id);

            //We have to recode the groupid field
            $group = backup_getid($restore->backup_unique_code,"group",$page->groupid);
            if ($group) {
                $page->groupid = $group->new_id;
            }
            
            $page->highlight = backup_todb($pag_info['#']['HIGHLIGHT']['0']['#']);
	
            //The structure is equal to the db
            $newid = insert_record ("wiki_pages",$page);
			
            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }
			
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"wiki_pages",$oldid,$newid);
                
            } else {
                $status = false;
            }
        }
        return $status;
    }
    
    //This function restores the wiki_synonymous
    function wiki_synonymous_restore_mods($old_dfwiki_id,$new_dfwiki_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the synonymous array
        $synonymous = $info['MOD']['#']['SYNONYMOUS']['0']['#']['SYNONYM'];

        //Iterate over synonymous
        for($i = 0; $i < sizeof($synonymous); $i++) {
            $syn_info = $synonymous[$i];

            //We'll need this later!!
            $oldid = backup_todb($syn_info['#']['ID']['0']['#']);

            //Now, build the dfwiki_PAGES record structure
            $syn->syn = backup_todb($syn_info['#']['SYN']['0']['#']);
            $syn->original = backup_todb($syn_info['#']['ORIGINAL']['0']['#']);
            $syn->dfwiki = $new_dfwiki_id;
            
            $groupname = backup_todb($syn_info['#']['GROUPNAME']['0']['#']);
            if (!empty($groupname)) $syn->groupid = '0';
            else $syn->groupid = wiki_groupid($groupname, $restore->course_id);

            //We have to recode the groupid field
            $group = backup_getid($restore->backup_unique_code,"group",$syn->groupid);
            if ($group) {
                $syn->groupid = $group->new_id;
            }
            
            $syn->ownerid = backup_todb($syn_info['#']['OWNERID']['0']['#']);
            //We have to recode the userid field
            $owner = backup_getid($restore->backup_unique_code,"owner",$syn->ownerid);
            if ($owner) {
                $syn->ownerid = $owner->new_id;
            }
            
            $newid = insert_record ("wiki_synonymous",$syn);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"dfwiki_synonymous",$oldid,$newid);

            } else {
                $status = false;
            }
        }
        return $status;
    }

    
    //function which restores attached files to a wiki when restoring a course
    function wiki_restore_files ($olddfwikiid, $restore, $order, $e_wiki) {
        
        global $CFG;

        $status = true;
        $moddata_path = "";
        $temp_path = "";

        //get wiki $cm->id we're restoring
        //get the latest wiki $cm->id to restore
        
        $modul = get_record("modules", "name", 'wiki');
        
        $newdfwikiid = get_record_sql('SELECT MAX(id) AS maxim
					FROM '. $CFG->prefix.'course_modules
					WHERE module='.$modul->id.' AND course='.$restore->course_id);

        //necessary data is available to proceed
        $num = wiki_count_position($newdfwikiid->maxim, $restore->course_id, $order);
        
        if (isset($e_wiki['oldid'])){
            $oldcourseid = $e_wiki['oldid'];
        }else{
            $oldcourseid = '';
        }
        if (isset($e_wiki['oldentryid'])){
            $oldentryid = $e_wiki['oldentryid'];
        }else{
            $oldentryid = '';
        }
        
        check_dir_exists("$CFG->dataroot/$restore->course_id",true);
        check_dir_exists("$CFG->dataroot/$restore->course_id/$CFG->moddata",true);
        check_dir_exists("$CFG->dataroot/$restore->course_id/$CFG->moddata/wiki$num",true);
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata."/wiki".$num;
        if ($oldcourseid == '' && $oldentryid == ''){
            check_dir_exists("$CFG->dataroot/temp",true);
            check_dir_exists("$CFG->dataroot/temp/backup",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata/wiki",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata/wiki/wiki$olddfwikiid",true);
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/moddata/wiki/wiki".$olddfwikiid;    
        }else{
            check_dir_exists("$CFG->dataroot/temp",true);
            check_dir_exists("$CFG->dataroot/temp/backup",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata/wiki",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata/wiki/$oldcourseid",true);
            check_dir_exists("$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata/wiki/$oldcourseid/$oldentryid",true);
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/moddata/wiki/$oldcourseid/$oldentryid";
        }
        

        //get the attached files
        $list = null;
        $list = list_directories_and_files ($temp_path);
        if($list != null){
            foreach ($list as $file) {
                //if isn't a directory
                if (!is_dir("$temp_path/$file")) {
                    $to_file = "$moddata_path/$file";
                    $from_file = "$temp_path/$file";
                    copy($from_file,$to_file);
                }
                //if it's a directory
                else{
                    //one by one
                    $temp_path = "$temp_path/$file";
                    $files2 = list_directories_and_files($temp_path);
                    if($files2 != null){
                        foreach ($files2 as $file2) {
                            $to_file = "$moddata_path/$file2";
                            $from_file = "$temp_path/$file2";
                            copy($from_file,$to_file);
                        }
                    }
                }
            }
        }
    }
    
    
    //Get wiki $cm->id we're restoring
    function wiki_count_position ($maxim, $course, $order) {

        global $CFG;
    
        $status = true;
        $modul = get_record("modules", "name", 'wiki');

        $i = 0;
        while ($status == true){

            $cm = get_record("course_modules", "id", $maxim);
            if (($cm->module == $modul->id) && ($i == $order)) $status = false;
            else if (($cm->module == $modul->id) && ($i != $order)){
                $i++;
                $maxim--;
            }else $maxim--;
        }
    
        return $maxim;
    
    }
    
    //This function returns a group name
    function wiki_groupid($groupname, $courseid) {

        global $CFG;
        $group = get_record_sql('SELECT *
					FROM '. $CFG->prefix.'groups
					WHERE name=\''.addslashes($groupname).'\' AND courseid='.$courseid);
		if (!isset($group)){			
            $group->id='0';
        }
        return $group->id;
    }
    
 
	//this function return the wiki schema bd in an array.
	function wiki_create_schema(){
	    global $CFG;
	
		$wiki_tables = array();
	    $file = $CFG->dirroot.'/mod/wiki/db/install.xml';
	    $xmldb_file = new XMLDBFile($file);
	    if (!$xmldb_file->fileExists()) {
	        continue;
	    }
	    $loaded    = $xmldb_file->loadXMLStructure();
	    $structure =& $xmldb_file->getStructure();
	    if ($loaded and $tables = $structure->getTables()) {
	        foreach($tables as $table) {
	            $wiki_tables[$table->name] = $table;
	        }
	    }
	    $wiki_tables['block_instance'] = null;
	    return $wiki_tables;
	}

    function wiki_read_xml_wiki($info,$courseid){
        foreach ($info as $name => $value){
            if($name!="PAGES" and $name!="SYNONYMOUS"){
            //Tratamos la wiki
                $data['wiki'][strtolower($name)]=$value['0']['#'];
            }elseif ($name=="PAGES"){
            //Tratamos las paginas
                $pages=$value['0']['#']['PAGE'];
                $numpages=count($pages);
                for ($i=0; $i<$numpages; $i++){
                    foreach($pages[$i]['#'] as $name => $page){
                    //search the groupid using the groupname
                        if($name=="GROUPNAME"){
                            $groupname=$page['0']['#'];
                            if(!empty($groupname)){
                                $data['wiki_pages'][$i]['groupid']=wiki_groupid($groupname,$courseid);
                            }
                            else $data['wiki_pages'][$i]['groupid']=0;
                        }
                        else{
                            $data['wiki_pages'][$i][strtolower($name)]=$page['0']['#'];
                        }
                    }
                }
            }elseif ($name=="SYNONYMOUS"){
            	//tratamos ls sinonimos
                $syns=$value['0']['#']['SYNONYM'];
                $numsyns=count($syns);
                for ($i=0; $i<$numsyns; $i++){
                    foreach($syns[$i]['#'] as $name => $syn){
                        //search the groupid using the groupname
                        if($name=="GROUPNAME"){
                        	$groupname=$syn['0']['#'];
                            if(!empty($groupname)){
                                $data['wiki_synonymous'][$i]['groupid']=wiki_groupid($groupname,$courseid);
                            }
                            else $data['wiki_synonymous'][$i]['groupid']=0;
                        }
                        else{
                            $data['wiki_synonymous'][$i][strtolower($name)]=$syn['0']['#'];
                        }
                    }
                }
            }
        }
        return $data;
    }
    
    function wiki_read_xml_ewiki($info,$restore){
        global $CFG, $COURSE;
        //Search the groupmode in coursemodule
       $wikioldid = $info["ID"]["0"]["#"]; 
       $newcourseid = $restore->course_id;        
       $coursemoduleid = $restore->mods['wiki']->instances[$wikioldid]->restored_as_course_module;
       $coursemodule = get_records_sql('SELECT *
                    FROM '. $CFG->prefix.'course_modules
                    WHERE id='.$coursemoduleid); 
        $groupmode = $coursemodule[$coursemoduleid]->groupmode;      
        foreach ($info as $name => $value){
            if($name!="ENTRIES"){
            //Tratamos la wiki
                $data['wiki']['groupmode']=$groupmode; 
                if($name=="NAME"){
                	$data['wiki']['name']=$value['0']['#'];
                    $data['wiki']['pagename']=$value['0']['#'];
                }elseif($name=="PAGENAME"){
                	//nothing
                }elseif($name=="SUMMARY"){
                	$data['wiki']['intro']=$value['0']['#'];
                }elseif($name=="HTMLMODE"){
                	switch ($value['0']['#']){
                    case '0':
                        $data['wiki']['editor'] = 'ewiki';
                    break;
                    case '1':
                        $data['wiki']['editor'] = 'ewiki';
                    break;
                    case '2':
                        $data['wiki']['editor'] = 'htmleditor';
                    break;
                    default:
                    break;
                    }
                }elseif($name=="TIMEMODIFIED"){
                	$data['wiki'][strtolower($name)]= TIME();                
                }elseif($name=="WTYPE"){
                	$wtype=$value['0']['#'];
                    if($wtype=='teacher'){
                    	$data['wiki']['editable']='0';
                        $data['wiki']['studentmode']='0';                      
                    }elseif ($wtype=='student'){
                    	$data['wiki']['editable']='1';
                        if($groupmode=='0'){
                            $data['wiki']['studentmode']='1';
                        }else{
                            $data['wiki']['studentmode']='2';   
                        }
                    }else{//$wtype = 'group'
                    	$data['wiki']['editable']='1';
                        $data['wiki']['studentmode']='0';
                    }
                }else{
                    $data['wiki'][strtolower($name)]=$value['0']['#'];
                }
                
            }else{
                $dataentries=$value['0']['#']['ENTRY'];
                $i=0;
                foreach($dataentries as $dataentry){
                    $entrygroupid=$dataentry['#']['GROUPID']['0']['#'];
                    foreach ($dataentry as $datapages){
                        $datapage=$datapages['PAGES']['0']['#']['PAGE'];
                        foreach ($datapage as $ewikipage){
                            if($groups = get_record_sql('SELECT *
                                    FROM '. $CFG->prefix.'groups g
                                    WHERE g.id='.$entrygroupid)){
                               
                               $groupname = $groups->name;
                            }
                            if ($groups = get_record_sql('SELECT *
                                        FROM '. $CFG->prefix.'groups g
                                        WHERE g.name=\''.$groupname.'\'
                                        AND g.courseid = '.$newcourseid)){
                                $groupid = $groups->id;
                            }
                            $entryuserid = $ewikipage['#']['USERID']['0']['#'];              
                            if ($wtype == 'student' &&  $entrygroupid == '0' && $groupmode != '0'){
                                if($user_groups = get_records_sql('SELECT gm.id as groupsmembersid, u.id, g.id as groupid
                                                                        FROM '. $CFG->prefix.'groups g,
                                                                        '. $CFG->prefix.'groups_members gm,
                                                                        '. $CFG->prefix.'user u
                                                                        WHERE g.courseid=\''.$newcourseid.'\'
                                                                        AND u.id = \''.$entryuserid.'\'
                                                                        AND g.id = gm.groupid
                                                                        AND u.id = gm.userid')){
                                    foreach ($user_groups as $user_group){
                                        $data['wiki_pages'][$i]['id']=$ewikipage['#']['ID']['0']['#'];
                                        $data['wiki_pages'][$i]['pagename']=$ewikipage['#']['PAGENAME']['0']['#'];
                                        $data['wiki_pages'][$i]['version']=$ewikipage['#']['VERSION']['0']['#'];
                                        $data['wiki_pages'][$i]['content']=restore_decode_absolute_links(addslashes(wiki_treat_content($ewikipage['#']['CONTENT']['0']['#'])));
                                        $data['wiki_pages'][$i]['version']=$ewikipage['#']['VERSION']['0']['#'];
                                        $pagerefs =restore_decode_absolute_links(addslashes($ewikipage['#']['REFS']['0']['#']));
                                        $pagerefs=str_replace("$@LINEFEED@$","|",$pagerefs);
                                        $pagerefs=str_replace("||","", $pagerefs);
                                        $wikipages['importfrombackup'][$i]['refs']=wiki_treat_internal_ref($pagerefs);
                                        $data['wiki_pages'][$i]['lastmodified']=$ewikipage['#']['LASTMODIFIED']['0']['#'];
                                        $data['wiki_pages'][$i]['oldentryid'] = $info["ENTRIES"]["0"]["#"]["ENTRY"]["0"]["#"]["ID"]["0"]["#"];
                                        $data['wiki_pages'][$i]['oldid'] = $info["ID"]["0"]["#"];
                                        $data['wiki_pages'][$i]['editor']=$data['wiki']['editor'];
                                        $data['wiki_pages'][$i]['userid']=$ewikipage['#']['USERID']['0']['#'];
                                        $data['wiki_pages'][$i]['groupid']=$user_group->groupid;
                                        if ($wtype == 'teacher'){
                                            $data['wiki_pages'][$i]['editable']='0';
                                            $data['wiki_pages'][$i]['ownerid']='0';
                                        }elseif ($wtype == 'group'){
                                            $data['wiki_pages'][$i]['editable']='1';
                                            $data['wiki_pages'][$i]['ownerid']='0';
                                        }else{
                                            $data['wiki_pages'][$i]['editable']='1';
                                            $data['wiki_pages'][$i]['ownerid']=$entryuserid;
                                        }
                                        $i++;              
                                    }
                                }      
                            }else{
                                        $data['wiki_pages'][$i]['id']=$ewikipage['#']['ID']['0']['#'];
                                        $data['wiki_pages'][$i]['pagename']=$ewikipage['#']['PAGENAME']['0']['#'];
                                        $data['wiki_pages'][$i]['version']=$ewikipage['#']['VERSION']['0']['#'];
                                        $data['wiki_pages'][$i]['content']=restore_decode_absolute_links(addslashes(wiki_treat_content($ewikipage['#']['CONTENT']['0']['#'])));
                                        $data['wiki_pages'][$i]['version']=$ewikipage['#']['VERSION']['0']['#'];
                                        $pagerefs =restore_decode_absolute_links(addslashes($ewikipage['#']['REFS']['0']['#']));
                                        $pagerefs=str_replace("$@LINEFEED@$","|",$pagerefs);
                                        $pagerefs=str_replace("||","", $pagerefs);
                                        $wikipages['importfrombackup'][$i]['refs']=wiki_treat_internal_ref($pagerefs);                                        
                                        $data['wiki_pages'][$i]['lastmodified']=$ewikipage['#']['LASTMODIFIED']['0']['#'];
                                        $data['wiki_pages'][$i]['oldentryid'] = $info["ENTRIES"]["0"]["#"]["ENTRY"]["0"]["#"]["ID"]["0"]["#"];
                                        $data['wiki_pages'][$i]['oldid'] = $info["ID"]["0"]["#"];
                                        $data['wiki_pages'][$i]['editor']=$data['wiki']['editor'];
                                        $data['wiki_pages'][$i]['userid']=$ewikipage['#']['USERID']['0']['#'];
                                        if($groupmode == '0'){
                                            $data['wiki_pages'][$i]['groupid']='0';
                                        }else{
                                            $data['wiki_pages'][$i]['groupid']=$groupid;
                                        }
                                        if ($wtype == 'teacher'){
                                            $data['wiki_pages'][$i]['editable']='0';
                                            $data['wiki_pages'][$i]['ownerid']='0';
                                        }elseif ($wtype == 'group'){
                                            $data['wiki_pages'][$i]['editable']='1';
                                            $data['wiki_pages'][$i]['ownerid']='0';
                                        }else{
                                            $data['wiki_pages'][$i]['editable']='1';
                                            $data['wiki_pages'][$i]['ownerid']=$entryuserid;
                                        }
                                        $i++;
                            }
                    }
                }
            }
        }
    }
    return $data;
    }
    //Validate the content of the backup with the schema of the wiki table.
    function wiki_validate($data,$schema){
        foreach ($schema->fields as $field){
			
    		if(array_key_exists($field->name,$data)){
    			$data_bd->{$field->name}=backup_todb($data[$field->name]);
    		}
            else{
                if (isset($field->default)){
                    $data_bd->{$field->name}=backup_todb($field->default);
                }
                else{
                    if ($field->notnull=="0"){
                        $data_bd->{$field->name}=null;
                    }
                    else notify("The field:'$field->Field' not exist in the restore from backup xml file, and is necesary in the wiki table");
                }
           }
    	}
        return($data_bd);
    }
  
    //treats wiki contents to convert it into dfwiki
    function wiki_treat_content($content){

        global $CFG;

        $links = null;
        $content = preg_replace("/\n/", "\r\n", $content);
        $content = preg_replace("/<br \/>/", "<br \/><br \/>", $content);
        //$content = preg_replace("/internal:/", "attach:", $content);

        //get all links and save them into an array
        $end = strpos($content, ']', 0);
        $start = strpos($content, '[', 0);
        while ($start !== false){
            $ofmoment = substr($content, $start, $end - $start + 1);
            $smilestart = substr($content, $start - 2, $start);
            $smileend = substr($content, $end - 2, $end);
                if ((!wiki_contain_vector($links, $ofmoment)) && ($smileend != '}-') && ($smilestart != '8-')){
                    $link = substr($content, $start, $end - $start + 1);
                    $pieces = explode('"', $link);
                    if (count($pieces) !== 3){
                    $links[] = $link;   
                    }  
                }
                if(($smileend == '}-') && ($smilestart != '8-')) $end = strpos($content, ']', $start+1);
                else if(($smileend != '}-') && ($smilestart == '8-')) $start = strpos($content, '[', $start+1);
                else{
                    $start = strpos($content, '[', $start+1);
                    $end = strpos($content, ']', $start+1);
                }
        }

        //treat every link
        if($links != null){
            foreach ($links as $link) {
                $type = strpos($link, 'http://');
                if ($type === false){
                    //it's an internal one
                    $link2 = substr($link, 1, strlen($link)-2);
                    $exist = strpos($link2, '|');
                    if ($exist === false){
                        //wikipedia special case
                        $existwikipedia = strpos($link2, 'wikipedia:');
                        $existWikipedia = strpos($link2, 'Wikipedia:');
                        if (($existwikipedia === false) && ($existWikipedia === false)){
                            $link4 = trim($link2);
                            $link3 = "[[$link4]]";
                            $content = str_replace($link, $link3, $content);
                        }else{
                            $link4 = trim($link2);
                            $extensionwikipedia = explode(":",$link4);
                            $numwikipedia = count($extensionwikipedia)-1;
                            if(count($extensionwikipedia) == 1) $namewikipedia = '';
                            else $namewikipedia = trim($extensionwikipedia[$numwikipedia]);
                            $link3 = "[http://www.wikipedia.com/wiki.cgi?$namewikipedia]";
                            $content = str_replace($link, $link3, $content);
                        }
                    }else{
                        $existwikipedia = strpos($link2, 'wikipedia:');
                        $existWikipedia = strpos($link2, 'Wikipedia:');
                        if (($existwikipedia === false) && ($existWikipedia === false)){
                            $extension = explode("|",$link2);
                            $num1 = count($extension)-1;
                            $num2 = count($extension)-2;
                            $name1 = trim($extension[$num1]);
                            $name2 = trim($extension[$num2]);
                            $link3 = "[[$name1|$name2]]";
                            $content = str_replace($link, $link3, $content);
                        }else{
                            $extension = explode("|",$link2);
                            $num1 = count($extension)-1;
                            $num2 = count($extension)-2;
                            $name1 = trim($extension[$num1]);
                            $name2 = trim($extension[$num2]);
                            $extensionwikipedia = explode(":",$name2);
                            $numwikipedia = count($extensionwikipedia)-1;
                            if(count($extensionwikipedia) == 1) $namewikipedia = '';
                            else $namewikipedia = trim($extensionwikipedia[$numwikipedia]);
                            $link3 = "[http://www.wikipedia.com/wiki.cgi?$namewikipedia $name1]";
                            $content = str_replace($link, $link3, $content);
                        }
                    }
                }    
            }
        }

        return $content;

    }
    
    //funcion que mira si un elemento est� en un vector
    function wiki_contain_vector ($vector, $element){

        if ($vector != null){
            foreach ($vector as $vec) {
                if (trim($vec) == trim($element)) return true;
            }
        }

        return false;

    }
    
    function wiki_treat_internal_ref ($refs){

        $ref = "";
        if ($refs != ""){

            $extension = explode('|',$refs);
            $num = count($extension);

            for ($i = 0; $i < $num; $i++) {
                if(stripos ($extension[$i],'http:')===false){
                    $ref.= $extension[$i];
                    $ref.= '|';
                }
            }
            if ($ref != "") $ref.= '|';
        }

        $ref = str_replace("||","", $ref);

        return $ref;

    }    
    /*
    function wiki_validate_page_syn($data,$schema){
        foreach ($schema as $field){
            if(array_key_exists($field->Field,$data)){
                $data_bd->{$field->Field}=backup_todb($data[$field->Field]);
            }
            else{
                if (isset($field->Default)){
                    $data_bd->{$field->Field}=backup_todb($field->Default);
                }
                else{
                    if ($field->Null=="YES"){
                        $data_bd->{$field->Field}=null;
                    }
                    else error ("The field:'$field->Field' not exist in the restore from backup xml file, and is necesary in the wiki table");
                }
           }
        }
        return($data_bd);
    }
    
    function wiki_validate_syn($data,$schema){
    	foreach ($schema as $field){
            if(array_key_exists($field->Field,$data)){
                $data_bd->{$field->Field}=backup_todb($data[$field->Field]);
            }
            else{
                if (isset($field->Default)){
                    $data_bd->{$field->Field}=backup_todb($field->Default);
                }
                else{
                    if ($field->Null=="YES"){
                        $data_bd->{$field->Field}=null;
                    }
                    else notify("The field:'$field->Field' not exist in the restore from backup xml file, and is necesary in the wiki table");
                }
           }
        }
        return($data_bd);
    }
	*/
?>
