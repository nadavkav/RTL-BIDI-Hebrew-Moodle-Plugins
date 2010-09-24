<?php
/**
 * map.class.php
 *
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.2
 * Other forms used in Map module
 *
 */
require_once($CFG->libdir.'/formslib.php');

/**
 * Form used for a students location
 * Doesn't have address field for privacy
 * A user can have only one of these locations at a time
 */
class mod_map_user_location_form extends moodleform {
	function definition() {
		$mform =& $this->_form;


		// visible elements
		$mform->addElement('header', 'general', get_string('mylocation', 'map'));
		$mform->addElement('submit','nolocation',get_string("hidelocation","map"));
		$mform->addElement('text', 'city', get_string('city'), array('size'=>'64'));
		$mform->setType('city', PARAM_TEXT);
		$mform->addRule('city', null, 'required', null, 'client');
		$mform->addElement('text', 'state', get_string('state', 'map'), array('size'=>'64'));
		$mform->setType('state', PARAM_TEXT);

		$choices = get_list_of_countries();
		$choices= array(''=>get_string('selectacountry').'...') + $choices;
		$mform->addElement('select', 'country', get_string('selectacountry'), $choices);
		$mform->addRule('country', get_string('required'), 'required', null, 'client');
		if (!empty($CFG->country)) {
			$mform->setDefault('country', $CFG->country);
		}
		// hidden params
		$mform->addElement('hidden', 'id', 0);
		$mform->setType('id', PARAM_INT);
		$mform->addElement('hidden', 'mapid', 0);
		$mform->setType('mapid', PARAM_INT);
		$mform->addElement('hidden','userid');
		$mform->addElement('hidden','locationid',0);
		$mform->addElement('hidden', 'action', 'updatelocation');
		// buttons
		$this->add_action_buttons(false);
	}
	function validationx($data){

		if(empty($data->state)){
			$errors["state"] =  "The state field is required";
		}
		if(isset($errors) && is_array($errors)){
			return $errors;
		}
		return true;
			
	}
}
/**
 * Form for user to consent to show their location on the Map
 * Used when the user has explicitly choosen to not show their location on a map
 */
class mod_map_reset_location_form extends moodleform {
	function definition() {
		$mform =& $this->_form;
		$mform->addElement('header', 'general', get_string('resetlocation', 'map'));
		$mform->addElement('submit','consentmap',get_string("consentshow","map"));
		$mform->addElement('hidden','locationid',0);
		$mform->addElement('hidden', 'action', 'resetlocation');
		$mform->addElement('hidden', 'id', 0);
		$mform->setType('id', PARAM_INT);
			
	}
}
/**
 * Form used for extra locations
 * Show address field dependent on Map field "showaddress4extra"
 * User can multiple extra locations
 * @todo Add the option to enter latitude and longitude directly. Could be used for locations that would not have an address such as wilderness locations.
 */
class mod_map_extralocation_form extends moodleform {
	function definition() {
		$mform =& $this->_form;


		// visible elements
		$mform->addElement('header', 'general', get_string('extralocation', 'map'));

		$mform->addElement('text', 'title', get_string('name'), array('size'=>'64'));
		$mform->addRule('title', null, 'required', null, 'server');

		$mform->addElement('text', 'address', get_string('address'), array('size'=>'64'));
		$mform->setType('address', PARAM_TEXT);
		$mform->addElement('text', 'city', get_string('city'), array('size'=>'64'));
		$mform->setType('city', PARAM_TEXT);
		//$mform->addRule('city', null, 'required', null, 'server');
		$mform->addElement('text', 'state', get_string('state', 'map'), array('size'=>'64'));
		$mform->setType('state', PARAM_TEXT);

		$choices = get_list_of_countries();
		$choices= array(''=>get_string('selectacountry').'...') + $choices;
		$mform->addElement('select', 'country', get_string('selectacountry'), $choices);
		//$mform->addRule('country', get_string('required'), 'required', null, 'server');
		if (!empty($CFG->country)) {
			$mform->setDefault('country', $CFG->country);
		}
		//$mform->addElement('checkbox','usePoint',get_string('usepoint','map'));
		$mform->addElement('static','info',get_string('coordinates','map').':',get_string('coordinatesinfo','map'));
		$mform->addElement('text', 'latitude', get_string('latitude','map'), array('size'=>'20'));
		$mform->addElement('text', 'longitude', get_string('longitude','map'), array('size'=>'20'));
		$mform->addElement('htmleditor', 'text', get_string('description'));
		$mform->addRule('text', null, 'required', null, 'server');
		// hidden params
		$mform->addElement('hidden', 'id', 0);
		$mform->setType('id', PARAM_INT);
		$mform->addElement('hidden','locationid',0);
		$mform->addElement('hidden', 'action', 'insertlocation');
		// buttons
		$this->add_action_buttons();
	}
	function set_data($data){
		parent::set_data($data);
		$mform =& $this->_form;

	}
	function validation($data){
		if(!empty($data['longitude'])||!empty($data['latitude'])){

			if(empty($data['longitude'])){
				$errors["longitude"] = get_string("pointerrorfill","map");
			}else if(!is_numeric($data['longitude'])){
				$errors["longitude"] = get_string("errorvalid","map").$data['longitude'];
			}
			if(empty($data["latitude"])){
				$errors["latitude"] = get_string("pointerrorfill","map");
			}else if(!is_numeric($data["latitude"])){
				$errors["latitude"] = get_string("errorvalid","map");
			}
		}else{

			if(empty($data['city'])){
				$errors["city"] =  get_string("errorrequired","map");
			}
			if(empty($data['country'])){
				$errors["country"] =  get_string("errorrequired","map");
			}

		}
		if(isset($errors) && is_array($errors)){
			return $errors;
		}
		return true;
			
	}
	/**
	 * Remove address field from the form.
	 */
	function removeAddress(){
		$mform =& $this->_form;
		$mform->removeElement("address");

	}
	function removePoint(){
		$mform =& $this->_form;

		$mform->removeElement("longitude");
		$mform->removeElement("latitude");
	}

}


?>
