<?php
	require_once('../../config.php');
	require_once('lib.php');
	
	if(!isset($_GET['repositoryid'])){
		error(get_string("","door"));
		exit;
	}else{
		if(!delete_repository($_GET['repositoryid'])){
			error(get_string("configdeleterepositoryerror","door"));
			exit;
		}else{
			header("Location: ".$CFG->wwwroot."/admin/module.php?module=door");
		}
	}
?>