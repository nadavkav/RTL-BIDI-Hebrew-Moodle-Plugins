<?php

define("SCLIPOHOST", "http://sclipo.com/");

function sclipo_getteachertimezone($ref)
{
	global $debug;    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.getteachertimezone&ref=".$ref;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	
	if (array_key_exists('value',$vals[0]))
		$zone = $vals[0]['value'];
	else
		$zone = "America/New_York";
	if ($zone == "")
		$zone = "America/New_York";
	if (!date_default_timezone_set($zone)) {
		echo "<strong>Couldn't set time zone</strong>";
		exit();
	}
	return $zone;
}

function sclipo_validateEmail($email)
{
	global $debug;
    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.validateemail&email=".$email;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	echo $vals[0]['value'];
}

function sclipo_removecontent($ref, $id)
{
	global $debug;
    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.removecontent&ref=".$ref."&id=".$id;
	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
}

function sclipo_getCurrentDateAndTime($id, $moodle)
{
	global $debug;
   
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"])) 
		$id = 0;
	else {
		list($user, $session) = split("/", $_COOKIE["user"]);
		$id = $session;
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.getcurrentdateandtime&id=".$id."&moodleuser=".$moodle;
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	
	
    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$p = xml_parser_create("ISO-8859-1");
	xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	
	echo $vals[0]['value'];
}

function sclipo_getWebClassInfo($id, $moodle, $ref)
{
	global $debug;
    
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.getwebclassinfo&ref=".$ref."&id=".$id."&moodleuser=".$moodle;
		
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	
	
    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$data = utf8_encode($data);
		
	$p = xml_parser_create("ISO-8859-1");
	xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 0);
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	
	$class = array();
	$class["title"] = stripslashes($vals[1]['value']);
	$class["description"] = stripslashes($vals[2]['value']);
	$class["tags"] = stripslashes($vals[3]['value']);
	$class["class_date"] = $vals[4]['value'];
	$class["time"] = $vals[5]['value'];
	$class["duration"] = $vals[6]['value'];
	$class["max_students"] = $vals[7]['value'];
	$class["public"] = $vals[8]['value'];
	$class["reference"] = $vals[9]['value'];
	return $class;
}

function sclipo_getWebClassContent($ref)
{
	global $debug;
    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.getlibrarycontent&ref=".$ref;
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$p = xml_parser_create();
	xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($p, $data, &$vals, $index);
	xml_parser_free($p);
	$docs = array();
	if (array_key_exists('doc', $index))
		$num = sizeof($index["doc"]) / 2;
	else $num = 0;
	for ($i = 0; $i < $num; $i++) {
		$docs[$i]["title"] = $vals[2+$i*6]['value'];
		$docs[$i]["content_id"] = $vals[3+$i*6]['value'];
		$docs[$i]["content_type"] = $vals[4+$i*6]['value'];
		$docs[$i]["pretty_url"] = $vals[5+$i*6]['value'];
	}
	return $docs;
}

function sclipo_deleteWebClass($id, $moodle, $ref)
{
	global $debug;
    
	
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"])) 
		return ;
	else {
		list($user, $session) = split("/", $_COOKIE["user"]);
		$id = $session;
	}
	if (!isset($_COOKIE["user"])) 
		return;
	else {
		list($user, $session) = split("/", $_COOKIE["user"]);
		$id = $session;
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.deletewebclass&id=".$id."&ref=".$ref."&moodleuser=".$moodle;
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
}

function sclipo_updateWebClass($id, $moodle, $webclass)
{
	global $debug;
    
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"]))
		return -1;
	list($user,$session) = split("/", $_COOKIE["user"]);

	$post = array("method" => "moodle.updatewebclass",
			"id" => $session,
			"public_class" => $webclass->public_class,
			"title" => $webclass->title,
			"tags" => $webclass->tags,
			"class_date" => $webclass->class_date,
			"time" => $webclass->time,
			"duration" => $webclass->duration,
			"moodleuser" => $moodle,
			"max_students" => $webclass->max_students,
			"ref" => $webclass->ref,
			"description" => $webclass->description);

    $url = SCLIPOHOST."api_rest/request";
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
}


function sclipo_createWebClass($id, $moodle, $webclass)
{
	global $debug;
    
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"]))
		return -1;
	list($user,$session) = split("/", $_COOKIE["user"]);
	
	$post = array("method" => "moodle.createwebclass",
			"id" => $session,
			"public_class" => $webclass->public_class,
			"title" => $webclass->title,
			"tags" => $webclass->tags,
			"moodleuser" => $moodle,
			"class_date" => $webclass->class_date,
			"time" => $webclass->time,
			"duration" => $webclass->duration,
			"max_students" => $webclass->max_students,
			"description" => $webclass->description);

    $url = SCLIPOHOST."api_rest/request";
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	return $vals[0]['value'];
}

function sclipo_getUserIDFromSession($id, $moodle)
{
	global $debug;
    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"]))
		return -1;
	list($user,$session) = split("/", $_COOKIE["user"]);
    $url = SCLIPOHOST."api_rest/request?method=moodle.getuserid&id=".$session."&moodleuser=".$moodle;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	if (array_key_exists('value', $vals[0]))
		return $vals[0]['value'];
	else
		return -1;
}

function sclipo_confirmTimezone($id, $moodle)
{
	global $debug;
    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"])) 
		return 0;
	else {
		list($user, $session) = split("/", $_COOKIE["user"]);
		$id = $session;
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.confirmtimezone&id=".$id."&moodleuser=".$moodle;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
}

function sclipo_isTimezoneConfirmed($id, $moodle)
{
	global $debug;    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"])) 
		return 0;
	else {
		list($user, $session) = split("/", $_COOKIE["user"]);
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.istimezoneconfirmed&id=".$session."&moodleuser=".$moodle;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	if (array_key_exists('value', $vals[0]))
		$zone = $vals[0]['value'];
	else
		$zone = 0;
	return $zone;
}

function sclipo_settimezone($id, $moodle, $timezone)
{
	global $debug;
    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"])) 
		return;
	else {
		list($user, $session) = split("/", $_COOKIE["user"]);
		$id = $session;
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.settimezone&id=".$id."&timezone=".$timezone."&moodleuser=".$moodle;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	if (!date_default_timezone_set($timezone)) {
		echo "<strong>Couldn't set time zone ($timezone)</strong>";
		exit();
	}
	echo date("Y-m-d h:iA e");
}

function sclipo_gettimezone($id, $moodle)
{
	global $debug;    

    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	if (!isset($_COOKIE["user"])) 
		return "America/New_York";
	else {
		list($user, $session) = split("/", $_COOKIE["user"]);
		$id = $session;
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.gettimezone&id=".$id."&moodleuser=".$moodle;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	if (array_key_exists('value',$vals[0])) 
		$zone = $vals[0]['value'];
	else
		$zone = "America/New_York";
	if ($zone == "")
		$zone = "America/New_York";
	if (!date_default_timezone_set($zone)) {
		echo "<strong>Couldn't set time zone</strong>";
		exit();
	}
	return $zone;
}

function sclipo_checkLogin($id, $moodle)
{
	global $debug;    
	
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	// $id in parameter is not used anymore, need to get rid of it at some point
	if (!isset($_COOKIE["user"])) 
		return 0;
	else {
		list($user, $session) = split("/",$_COOKIE["user"]);
		if ($moodle != $user)
			return 0;
	}
    $url = SCLIPOHOST."api_rest/request?method=moodle.checkLogin&id=".$session."&moodleuser=".$moodle;	
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	
	return $vals[0]['value'];
}

function sclipo_login($email, $pass, $moodle)
{
	global $debug;
    global $USER;
	
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	
    $url = SCLIPOHOST.'api_rest/request?method=moodle.login&email='.$email.'&pass='.$pass.'&moodleuser='.$moodle;
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	
	$sess = $vals[0]['value'];
	setcookie("user", $USER->username."/".$sess, time()+3600*24*365, "/");
	return $sess;
}

function sclipo_signup($email, $pass, $moodle, $firstname, $lastname, $gender, $bDay, $bMonth, $bYear)
{
	global $debug;
    global $USER;
	
    $ch = curl_init();
	if ($ch == FALSE) { 
		echo "<p><strong>FATAL ERROR: Could not initialize XML/RPC connection</strong></p>";
	}
	
    $url = SCLIPOHOST.'api_rest/request';
	$post = array( "method" => "moodle.signup",
				   "email"	=> $email,
				   "moodleuser"	=> $moodle,
				   "pass"	=> $pass,
				   "firstname"	=> $firstname,
				   "lastname"	=> $lastname,
				   "gender"	=> $gender,
				   "bday"	=> $bDay,
				   "bmonth"	=>	$bMonth,
				   "byear"	=>	$bYear );
	
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	
    $data = curl_exec($ch);
	if ($data == FALSE) {
		echo "<p><strong>FATAL ERROR: Could not connect to Sclipo</strong></p>";
	}
	$p = xml_parser_create();
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);
	
	if (array_key_exists('value',$vals[0])) {
		$sess = $vals[0]['value'];
		setcookie("user", $USER->username."/".$sess, time()+3600*24*365, "/");
		return $sess;
	}
	else
		return -1;
}

function sclipo_remaining_time($class_date, $webclass_duration, $time_zone=null)
    {
    	$timespan = '';
    	$minute = 60;
    	$hour = 60*$minute;
    	$day = 24*$hour;
    	$week = 7*$day;
    	$now = time();
    	$add_hours = 0;
    	$add_minutes = 0;
    	
    	$class_date = strtotime($class_date);
		
    	/*
    	if ($time_zone != NULL)
    	{
    		$first = substr($time_zone,0,1);
    		
    		switch ($first)
    		{
    			case '+': $add_hours = $this->hour_difference + substr($time_zone,1,2);
    					  $add_minutes = substr($time_zone,4,2);
    					  break;
    			case '-': $add_hours = $this->hour_difference - substr($time_zone,1,2);
    					  $add_minutes = -substr($time_zone,4,2);
    					  break;
    			case '0': break;
    		}
    	}
    	else
    	{
    		// No time zone set; use EST as default
    		$ok = date_default_timezone_set('America/New_York');	
    	}    	
    	   */
    	// When webclass duration is not set, we assume a duration of 30 minutes   						    				
    	$duration = $webclass_duration != null? ($webclass_duration*$minute):30*$minute;    	
    	
    	$now += $add_hours*$hour + $add_minutes*$minute;
    	
    	if ($now < $class_date)
    	{
    		$interval = $class_date - $now;	
    		if ($interval > $week)
    		{
    			$timespan = ceil($interval/$week)._(" week(s)");
    		}
    		elseif ($interval  > $day)
    		{
    			$timespan = ceil($interval/$day)._(" day(s)");
    		}
    		elseif ($interval  > $hour || $interval  > $minute)
    		{
    			//echo "Now: ".date(DATE_RFC822,$now).' Class date:'.date(DATE_RFC822,$class_date)." Suma:".date(DATE_RFC822,$class_date + $duration).' \n';
    			$hours = floor($interval/$hour);
    			$minutes = floor(($interval-($hours*$hour))/$minute);
    			
    			$timespan = ($hours>0?$hours._(" Hour").($hours!=1?'s ':' '):'').$minutes._(" Minute").($minutes!=0?'s':'');

    		}
    	}
    	elseif ($now < ($class_date + $duration))
    	{    		
    		$timespan = 'S'; 
    	}
		else
		{			
			//echo "Now: ".date(DATE_RFC822,$now).' Class date:'.date(DATE_RFC822,$class_date)." Suma:".date(DATE_RFC822,$class_date + $duration).' \n';
			$timespan = 'F';
		}

    	return $timespan;
    }

if (isset($_GET["do"]) && $_GET["do"] == "getcurrentdateandtime")
	sclipo_getCurrentDateAndTime($_GET["id"], $_GET["moodleuser"]);
if (isset($_POST["email"])) 
	sclipo_validateEmail($_POST["email"]);
if (isset($_GET["do"]) && $_GET["do"] == "confirmtimezone")
	sclipo_confirmTimezone($_GET["id"], $_GET["moodleuser"]);
if (isset($_GET["do"]) && $_GET["do"] == "settimezone")
	sclipo_settimezone($_GET["id"], $_GET["moodleuser"], $_GET["zone"]);
	
if (isset($_GET["do"]) && $_GET["do"] == "removecontent")
	sclipo_removecontent($_GET["ref"], $_GET["cid"]);

?>