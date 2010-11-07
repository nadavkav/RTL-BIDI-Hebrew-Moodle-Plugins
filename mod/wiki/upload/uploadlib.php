<?php
//this document contains all functions for uploading
//files into a dfwiki instance
//
//Created by David Castro & Ferran Recio

//include upload class @@@@@@@@@@@
require_once($CFG->dirroot.'/lib/uploadlib.php');

//this function configures the file upload
function wiki_upload_config(&$WS){
    global $CFG,$COURSE;
    $dir = make_mod_upload_directory($COURSE->id);
    $WS->dfdir->name = $dir.'/wiki'.$WS->cm->id;
    //generate uploaded file list
    $WS->dfdir->content = get_directory_list($WS->dfdir->name);
    $WS->dfdir->www = $CFG->wwwroot.'/file.php/'.substr($WS->dfdir->name, strlen($CFG->dataroot)+1);
    $WS->dfdir->error = array();
}

//this function uploads a file from the form
//@param $file: name of the form file target
function wiki_upload_file($file,&$WS){
    global $_FILES;
    if (file_exists($WS->dfdir->name.'/'.$_FILES[$file]['name'])){

    	//file already existst in the server
    	$WS->dfdir->error[] = get_string('fileexisterror','wiki');
    } else {
    	//save file
    	$upd = new upload_manager('dfformfile');
    	$upd->preprocess_files();
    	if (!$upd->save_files($WS->dfdir->name)){
    		//error uploading
    		$WS->dfdir->error[] = get_string('fileuploaderror','wiki');
    	}
    }
    $WS->dfdir->content = get_directory_list($WS->dfdir->name);
}

//this funcion deletes a file in the dfwiki instance dir.
function wiki_upload_del($file,&$WS){

    $content = $WS->dfdir->content;

    if (in_array($file,$content)){
    	$num = array_search($file,$content);

    	$upd = new upload_manager();

    	unset($content[$num]);
    	if (unlink($WS->dfdir->name.'/'.$file)){
    		$WS->dfdir->content = $content;
    		return true;
    	}else{
    		$WS->dfdir->error[] = get_string('filedeleteerror','wiki');
    		return false;
    	}
    }else{
    	$WS->dfdir->error[] = get_string('filenotexisterror','wiki');
    	return false;
    }
}

//delete all uploaded files in a dfwiki instance
function wiki_upload_deldir(&$WS){

    //cheack if the folder exists
    if (file_exists($WS->dfdir->name)){
    	//delete all folder files
    	$upd = new upload_manager();
    	$upd->delete_other_files($WS->dfdir->name);
    	rmdir($WS->dfdir->name);
    	return true;
    }else{
    	return true;
    }
}

//get the URL to a file.
function wiki_upload_url($file,&$WS){
    global $CFG;
	if (!isset($WS->dfdir->www)){
		wiki_upload_config($WS);		
    }
	return $WS->dfdir->www.'/'.$file;
}
?>
