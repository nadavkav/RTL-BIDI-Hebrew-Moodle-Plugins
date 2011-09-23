<?php
/**
 * lib.php
 *
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.2
 * Common function for the map module
 *
 */

/// Standard functions /////////////////////////////////////////////////////////

require("GTranslate.php"); // nadavkav

function map_user_outline($course, $user, $mod, $map) {
	if ($locations = get_records('map_locations', 'mapid', $map->id, 'userid', $user->id)) {
		$result->time = 0;
		foreach($locations as $loc){
			$result->info += $loc->title . ",";
			if($loc->timemodified > $result->time){
				$result->time = $loc->timemodified ;
			}
		}
		return $result;
	}
	return NULL;
}




function map_add_instance($map) {
	// Given an object containing all the necessary data,
	// (defined by the form in mod.html) this function
	// will create a new instance and return the id number
	// of the new instance.

	$map->timemodified = time();



	//insert answers
	if($map->id = insert_record("map", $map)){
		if($map->requireok == 0){

			//get all locations

			//map_create_all_locations($map->id);

		}
	}

	return $map->id;
}


function map_update_instance($map) {
	// Given an object containing all the necessary data,
	// (defined by the form in mod.html) this function
	// will update an existing instance with new data.
	$map->id = $map->instance;
	$map->timemodified = time();
	return update_record('map', $map);

}




function map_delete_instance($id) {
	// Given an ID of an instance of this module,
	// this function will permanently delete the instance
	// and any data that depends on it.

	if (! $map = get_record("map", "id", "$id")) {
		return false;
	}

	$result = true;


	if(get_record("map_locations","mapid","$map->id")){
		if (! delete_records("map_locations", "mapid", "$map->id")) {
			$result = false;
			return false;
		}
	}

	if (! delete_records("map", "id", "$map->id")) {
		$result = false;
	}

	return $result;
}





function map_get_map($mapid) {
	// Gets a full map record

	if ($map = get_record("map", "id", $mapid)) {

		return $map;
	}
	return false;
}
/**
 * Is there better way to do this in Moodle?
 */
function map_insert_script($src) {
	$script = '<script src="'.$src.'" type="text/javascript" ></script>';
	echo $script;
	//$this->html = str_replace('</head>', $script.'</head>',$this->html);
}
function map_insert_css($src) {

	$script = '<link rel="stylesheet" type="text/css" href="'.$src.'" />';
	echo $script;

}
/**
 * Get Locations for a given group/map
 *
 * @param integer $mapid
 * @param integer $currentgroup
 * @param string $sort
 * @return array Returns an array of locations
 */
function map_get_locations($mapid,$currentgroup,$sort = 'userid'){
	global $CFG, $COLUMN_HEIGHT, $USER;

	$map_locations = get_records("map_locations","mapid",$mapid,$sort);
	if($map_locations){
		foreach($map_locations as $map_location){
			$map_location->user = get_record("user","id",$map_location->userid);
		}

		if(!$currentgroup){
			return $map_locations;
		}else{

			$map_group_locations = array();
			foreach($map_locations as $map_location){
				if(groups_is_member($currentgroup,$map_location->user->id)){
					$map_group_locations[] = $map_location;
				}
			}
			return $map_group_locations;
		}
	}
	return array();

}
/**
 * Saves a map location.  Will add or update.
 * @param object $data Information about the location
 */
function map_save_location($data){
	global $USER;
	$data->timemodified = time();
	if (! $cm = get_coursemodule_from_id('map', $data->id)) {
		error("Course Module ID was incorrect");
	}
	if (!$map = map_get_map($cm->instance)) {
		error("Course module is incorrect2");
	}
	$data->mapid = $map->id;
	if(!isset($data->userid)){
		$data->userid = $USER->id;
	}
	if(isset($data->nolocation)){
		$data->latitude = 0;
		$data->longitude = 0;
		$data->city = "";
		$data->state = "";
		$data->country = "";
		$data->showcode = 0;
	}else if($data->action != "resetlocation"){
		//longitude and latitude was given don't need to call to get location
		if(empty($data->latitude) && empty($data->longitude)){
			if(!map_get_latlong($data,$map)){
				return "Error finding location";

			}
		}
		$data->showcode = 1;
	}

	switch($data->action){
		case "insertlocation":
			$locationSuccess = insert_record("map_locations",$data);
			break;
		case "updatelocation":
			$data->id = $data->locationid;
			$locationSuccess = update_record("map_locations",$data);
			break;
		case "resetlocation":
			$locationSuccess = delete_records("map_locations","id", $data->locationid);
	}
	if($locationSuccess!=false){
		return true;
	}else{
		return "Could not update location";
	}

}
/**
 * Old method will not work on some servers
 */
function map_get_latlong_old(&$data){
	global $CFG;
	// Your Google Maps API key
	// Desired address


	$address = "http://maps.google.com/maps/geo?q=". str_replace(" ","+","$data->city,+$data->state,+$data->country") . "&output=xml&key={$CFG->map_google_api_key}&hl=iw";
	// Retrieve the URL contents
	$page = file_get_contents($address);

	// Parse the returned XML file
	$xml = new SimpleXMLElement($page);
	if($xml->Response->Status->code == "200"){

		// Retrieve the desired XML node
		list($data->longitude, $data->latitude, $altitude) = explode(",",$xml->Response->Placemark->Point->coordinates);
		return true;
	}else{
		return false;
	}

}
/**
 * Calls google to get longitude and latitude for location
 * This could be changed to use other mapping servcies. The file "map.js" would also have to be changed.
 * @param object $data Information get added to object
 * @return bool Whether call was successful
 */
function map_get_latlong(&$data,$map){
	global $CFG;
	// Your Google Maps API key
	// Desired address
	$provider = map_get_map_provider($map);
	//$address = "$data->city,+$data->state,+$data->country";

    // Translate Hebrew City name to English. will not work 100% of the time (nadavkav)
    try {
        $gt = new Gtranslate;
        $heCityName = $gt->hebrew_to_english($data->city);
    } catch (GTranslateException $ge) {
        echo $ge->getMessage();
    }

    $address = urlencode($heCityName)."&country=$data->country"; // Encode Hebrew translated to "Englished" city name (nadavkav)
	if(isset($data->address)){
		$address = str_replace(" ","+",",$data->address+" . $address);

	}

	if($provider=="google"){
		$address = urlencode($address);
		return map_get_latlong_google($data,$address);
	}else{
        return map_get_latlong_geonames($data,$address);
	}

}
/**
 * This gets longitude and latitude from the Geonames project: http://www.geonames.org/
 * Docs for this query can be found here: http://www.geonames.org/export/geonames-search.html
 *
 * @param object $data
 * @param string $address
 * @return boolean
 */
function map_get_latlong_geonames(&$data,$address){
	$url = "http://ws.geonames.org/search?q=$address&maxRows=1&username=demo";
	$xml = cURL_XML($url);

	if($xml->totalResultsCount > 0){
        foreach ($xml->geoname as $geoloc) {
            if ($geoloc->countryCode !=  $data->country) unset($geoloc);
        }
		$data->longitude = $xml->geoname[0]->lng;
		$data->latitude = $xml->geoname[0]->lat;

		return true;
	}


	return false;
}
/**
 * Get longitude and latitude from Google: http://code.google.com/apis/maps/documentation/services.html
 *
 * @param unknown_type $data
 * @param unknown_type $address
 * @return unknown
 */
function map_get_latlong_google(&$data,$address){
	global $CFG;
	$url = "http://maps.google.com/maps/geo?q=$address&output=xml&key={$CFG->map_google_api_key}&hl=iw";




	$xml = cURL_XML($url);
	if($xml->Response->Status->code == "200"){

		// Retrieve the desired XML node
		list($data->longitude, $data->latitude, $altitude) = explode(",",$xml->Response->Placemark->Point->coordinates);
		return true;
	}else{
		return false;
	}

}
/**
 * Makes a cURL request from a URL and returns and XML object
 *
 * @param string $url
 * @return unknown
 */
function cURL_XML($url){

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);

	curl_setopt($ch, CURLOPT_HEADER,0);

	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$page= curl_exec($ch);

	curl_close($ch);


	// Parse the returned XML file

	$xml = new SimpleXMLElement($page);
	return $xml;
}
/**
 * Creates the Javascript array for current locations. It is array of objects.  Is there a better way to output JS in Moodle?
 * @param array Current Locations
 * @param integer $courseid
 * @return string The script including script tags
 */
function map_create_locations_js($locations,$courseid){
	global $CFG;
	$script = "";

	foreach($locations as $location){
		if($location->showcode == "1"){
			if($script!=""){
				$script .=",";
			}
			if(map_isStudentLocation($location)){
				$locType = "'student'";
				$picHTML = addslashes_js(print_user_picture($location->userid,$courseid,$location->user->picture,250,true));
				$locDescription = addslashes_js($location->user->description);

			}else{
				$locType = "'extra'";
				$picHTML = "";
				$locDescription = addslashes_js($location->text);
				$location->title = addslashes_js($location->title);
			}
			$script .= "{userid: '$location->userid',latitude: '$location->latitude',longitude: '$location->longitude',city: '" . addslashes_js($location->city) . "',state: '" . addslashes_js($location->state) . "',country: '$location->country',title: '" . addslashes_js($location->title) . "',text: '" . $locDescription . "',firstname: '" . addslashes_js($location->user->firstname) . "',lastname: '" . addslashes_js($location->user->lastname) . "',description: '" . $locDescription . "',picHTML: '" . $picHTML . "',type: $locType}";
			//$script .= "{userid: '$location->userid',latitude: '$location->latitude',longitude: '$location->longitude',city: '$location->city',state: '$location->state',country: '$location->country'}";
		}
	}
	if($script == ""){return "";}
	$script = "<script type='text/javascript'>var locations = [$script];var baseMapURL = '$CFG->wwwroot/mod/map/';</script>";
	return $script;
}
/**
 * Is this a Student location or other type of locations
 * @param object $location
 * @return bool
 */
function map_isStudentLocation($location){
	return $location->title == "";
}
/**
 * Create script tags needed for the map
 */
function map_load_js_scripts($map){
	global $CFG;
	$provider = map_get_map_provider($map);
	map_insert_script($CFG->wwwroot . "/mod/map/js/prototype.js");
	if($provider == 'google'){
		map_insert_script("http://maps.google.com/maps?file=api&allow_bidi=true&v=2&hl=iw&key=" . $CFG->map_google_api_key);
		map_insert_script("http://www.google.com/jsapi?key=" . $CFG->map_google_api_key);
		map_insert_script($CFG->wwwroot . "/mod/map/js/map.js");
		map_insert_script($CFG->wwwroot . "/mod/map/js/map_google.js");
	}else{
		map_insert_script($CFG->wwwroot . "/mod/map/js/OpenLayers.js");
		map_insert_script("http://www.openstreetmap.org/openlayers/OpenStreetMap.js");
		map_insert_script($CFG->wwwroot . "/mod/map/js/map.js");
		map_insert_script($CFG->wwwroot . "/mod/map/js/map_ol.js");
	}
	map_insert_css($CFG->wwwroot . "/mod/map/js/map.css");

	//map_insert_script("ddo.js");
}
/**
 * Prints a table of "extra" locations for the current user for a give map. The table has links to update and delete the locations.
 * This doesn't include the student personal location(where they live)
 * @param array $locations Array of all location fo this map.  Only locations for this user will be output
 * @todo Add the option to print all locations for all users. For users with rights update others' locations
 */
function map_print_extra_locations($locations,$cmID){
	global $CFG, $COLUMN_HEIGHT, $USER;
	foreach($locations as $map_location){
		if($map_location->userid == $USER->id && $map_location->title != ""){
			$delButton = print_single_button("extraLocationForm.php?id=" . $cmID . "&action=delete&id=" . $cmID ."&locationid=" . $map_location->id,null,get_string("delete"),"post","_self",true);
			$editButton = print_single_button("extraLocationForm.php?id=" . $cmID . "&action=edit&id=" . $cmID ."&locationid=" . $map_location->id,null,get_string("edit"),"post","_self",true);
			$table->data[] = array($map_location->title,$delButton,$editButton);
		}
	}
	if(isset($table)){
		$table->head = array(get_string("name"),get_string("delete"),get_string("edit"));
		print_heading(get_string("mylocations","map"));
		print_table($table);
	}

}
/**
 * Creates an address string for a location
 */
function map_addressString($location){
	return ($location->address != ""?$location->address.", ":"") . $location->city . ", " . ($location->state != ""?$location->state.", ":"") . $location->country;
}
/**
 * Gets user's "state" field by looking at profile fields
 *
 * @param object $user
 * @return unknown
 */
function map_get_user_state($user){
	global $CFG;
	if(isset($CFG->map_state_profile_field) && !empty($CFG->map_state_profile_field)){
		if(isset($user->{"profile_field_".$CFG->map_state_profile_field})){
			return $user->{"profile_field_".$CFG->map_state_profile_field};
		}
	}else{
		//if not in settings check default english words
		if(isset($user->profile_field_State)){
			return $user->profile_field_State;
		}
		if(isset($user->profile_field_Province)){
			return $user->profile_field_Province;
		}

	}
	return "";
}
/**
 * Checks to make sure module settings are set up correctly
 *
 * @return boolean
 */
function map_config_ok(){
	global $CFG;
	if(isset($CFG->map_provider) && $CFG->map_provider == "google" && empty($CFG->map_google_api_key)){
		return false;
	}
	return true;
}
/**
 * Makes array of map providers
 *
 * @return array
 */
function map_get_provider_array(){
	$providers = array();
	$providers['google'] = "Google";
	$providers['openstreetmap'] = "OpenStreetMaps/OpenLayers";
	return $providers;
}

/**
 * Makes array of map providers that have settings ready to work
 *
 */
function map_get_working_provider_array(){
	global $CFG;
	$providers = map_get_provider_array();
	if(empty($CFG->map_google_api_key)){
		unset($providers['google']);
	}
	return $providers;
}
/**
 * Get map provider that the map should use.
 * Looks at module settings and map object data
 *
 * @param object $map
 * @return unknown
 */
function map_get_map_provider($map=null){
	global $CFG;
	//have to code as if settings page was never set

	if(!isset($CFG->map_provider) || $CFG->map_provider == "choose"){
		if(isset($CFG->map_google_api_key)){
			$setting_provider = "google";
		}else{
			$setting_provider =  "openstreetmap";
		}

	}else{
		$setting_provider=  $CFG->map_provider;
	}
	if(isset($CFG->map_force_provider) && $CFG->map_force_provider == 1
	&& isset($CFG->map_provider) && $CFG->map_provider != 'choose' ){
		$force = true;
	}else{
		$force = false;
	}

	if($map == null){
		//map should never be null but just incase
		return $setting_provider;

	}else{
		if($force || empty($map->provider)){
			return $setting_provider;
		}else {
			return $map->provider;
		}
	}
}
?>
