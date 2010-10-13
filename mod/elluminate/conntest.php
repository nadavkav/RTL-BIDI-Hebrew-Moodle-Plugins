<?php // $Id: conntest.php,v 1.1.2.2 2009/03/18 16:45:53 mchurch Exp $

/**
 * A simple Web Services connection test script for the configured Elluminate Live! server.
 * 
 * @version $Id: conntest.php,v 1.1.2.2 2009/03/18 16:45:53 mchurch Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */


    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';
    
    require_login(SITEID, false);

    if (!isadmin()) {
        redirect($CFG->wwwroot);
    }

    if (!$site = get_site()) {
        redirect($CFG->wwwroot);
    }

    $serverurl = required_param('serverURL', PARAM_NOTAGS);
    $serveradapter = required_param('serverAdapter', PARAM_NOTAGS);
    $username  = required_param('authUsername', PARAM_NOTAGS);
    $password  = required_param('authPassword', PARAM_NOTAGS);
    $boundary  = required_param('boundaryDefault', PARAM_NOTAGS);
    $prepopulate  = required_param('prepopulate', PARAM_NOTAGS);
    $wsDebug  = required_param('wsDebug', PARAM_NOTAGS);    

    $strtitle = get_string('elluminateconnectiontest', 'elluminate');

	print_header_simple(format_string($strtitle));
    print_simple_box_start('center', '100%');
	
    if (!elluminate_test_connection($serverurl, $serveradapter, $username, $password, $boundary, $prepopulate, $wsDebug)) {
        notify(get_string('connectiontestfailure', 'elluminate'));
    } else {
        notify(get_string('connectiontestsuccessful', 'elluminate'), 'notifysuccess');
    }

	$server = elluminate_send_command('getSchedulingManager', null, $serverurl, $serveradapter, $username, $password);
	
	if($server == 'ELM') {
		$server_type = new Stdclass;
		$server_type->name = 'elluminate_scheduling_server';
		$server_type->value = 'ELM';				
	} else {
		$server_type = new Stdclass;
		$server_type->name = 'elluminate_scheduling_server';
		$server_type->value = 'SAS';		
	}
	
	if($exists = get_record('config', 'name', 'elluminate_scheduling_server')) {
		$server_type->id = $exists->id;
		update_record('config', $server_type);
	} else {
		insert_record('config', $server_type);
	}	
	

    echo '<center><input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" /></center>';

    print_simple_box_end();
    print_footer('none');

?>
