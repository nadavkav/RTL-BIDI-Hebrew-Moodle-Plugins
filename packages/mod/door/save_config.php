<?php
	require_once('../../config.php');
	require_once('lib.php');
	
	require_login();

    if (!isadmin()) {
        error("Only an admin can use this page");
    }

    if (!$site = get_site()) {
        error("Site isn't defined!");
    }
	
	$existing_repositories = $_POST['entries'];
	$count = 1;
	
	//Update existing repositories
	for($count=1;$count<$existing_repositories;$count++){
		//Remove protocol from address
		$pos = strpos($_POST['address'][$count],"://");
		if($pos>0)
			$_POST['address'][$count]=substr($_POST['address'][$count],$pos+3);
		//If the address ends with "/" we have to remove the last character
		$last = $_POST['address'][$count]{strlen($_POST['address'][$count])-1};
		if($last=="/")
			$_POST['address'][$count] = substr($_POST['address'][$count],0,strlen($_POST['address'][$count])-1);
		//If name and address are empty -> delete the repository else update it
		if($_POST['address'][$count]=="" && $_POST['name'][$count]==""){
			if(!delete_repository($_POST['id'][$count]))
				error(get_string("configdeleterepositoryerror","door"));
		}else{
			if(!update_repository($_POST['id'][$count],$_POST['name'][$count],$_POST['address'][$count],$_POST['authentication'][$count]))
				error(get_string("configupdaterepositoryerror","door"));
		}
	}
	
	//Add new repository (if it is the case)
	if($_POST['name'][$existing_repositories]!="" && $_POST['address'][$existing_repositories]!=""){
		//Remove protocol from address
		$pos = strpos($_POST['address'][$existing_repositories],"://");
		if($pos>0)
			$_POST['address'][$existing_repositories]=substr($_POST['address'][$existing_repositories],$pos+3);
		//If the address ends with "/" we have to remove the last character
		$last = $_POST['address'][$count]{strlen($_POST['address'][$count])-1};
		if($last=="/")
			$_POST['address'][$count] = substr($_POST['address'][$count],0,strlen($_POST['address'][$count])-1);
		//Add the new repository
		if(!add_repository($_POST['name'][$existing_repositories],$_POST['address'][$existing_repositories],$_POST['authentication'][$existing_repositories]))
			error(get_string("configaddrepositoryerror","door"));
	}
	header("Location: ../../admin/module.php?module=door");
?>