<?php // $Id: rpclib.php,v 1.1.2.3 2009/10/11 14:39:25 ulcc Exp $
/**
 * Some dummy functions to test XML-RPC with
 */

/**
 * The xxxx_RPC_OK must exist and return TRUE for the remote call to be
 * permitted
 *
 * @return bool True if the related function can be executed remotely
 */
function mnet_ilp_targets_RPC_OK() {
    return true;
}

function ilptarget_mnet_publishes() {

	$servicelist = array();
    $servicelist['name']        = 'ilptargetremote'; // Name & Description go in lang file
    $servicelist['apiversion']  = 1;
    $servicelist['methods']     = array('mnet_ilp_targets');
	return array($servicelist);
}

function mnet_ilp_targets($usercrit,$courseid=0,$sortorder='ASC',$limit=0,$status=-1,$tutorsetonly=FALSE,$studentsetonly=FALSE)
{

	global $CFG;
	
	$module = 'project/ilp';
    $config = get_config($module);
	
	if (isset($config) && $config != false)
	{
	
		$user	=	get_record('user',$usercrit[0],$usercrit[1]);
		if (isset($user) && $user != false)
		{
			$select = "SELECT {$CFG->prefix}ilptarget_posts.*, up.username ";
			$from = "FROM {$CFG->prefix}ilptarget_posts, {$CFG->prefix}user up ";
			$where = "WHERE up.id = setbyuserid AND setforuserid = $user->id ";

			if($status != -1) {
				$where .= "AND status = $status ";
			}elseif($config->ilp_show_achieved_targets == 1){
				$where .= "AND status != 3 ";
			}else{
				$where .= "AND status = 0 ";
			}

			if($CFG->ilptarget_course_specific == 1 && $courseid != SITEID){
				$where .= "AND course = $courseid ";
			}

			if($tutorsetonly == TRUE && $studentsetonly == FALSE) {
				$where .= "AND setforuserid != setbyuserid ";
			}

			if($studentsetonly == TRUE && $tutorsetonly == FALSE) {
				$where .= "AND setforuserid = setbyuserid ";
			}

			$order = "ORDER BY deadline $sortorder ";

			$target_posts = get_records_sql($select.$from.$where.$order,0,$limit);
			
			foreach ($target_posts as $target_post) {
				$posttutor = get_record('user','id',$target_post->setbyuserid);
				$target_post->setbyuserid = fullname($posttutor);
				/*if($target_post['courserelated'] == 1){
					$targetcourse = get_record('course','id',$target_post->targetcourse);
					$target_post->targetcourse = $targetcourse->shortname;
				}*/
			}			
			return $target_posts;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}


}


?>
