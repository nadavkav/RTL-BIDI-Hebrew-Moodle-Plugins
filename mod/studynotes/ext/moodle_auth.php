<?php
class MediabirdMoodleAuth {
	public $userId;
	function __construct($userId) {
		$this->userId=$userId;
	}

	function inviteUser($email) {
		//search for user
		if ($user = get_record("user", "email", $email)){
			return $this->inviteKnownUser($user->id,$inviteUnknown);
		}
		else {
			return false;
		}
	}

	/**
	 * Send's an anonymous email to some address, preferably the Mediabird team or a user
	 * @param $to Id of user to which to deliver email
	 * @param $subject Subject of email
	 * @param $body Body of email
	 * @return bool Success
	 */
	function sendMail($to,$subject,$body) {
		if($to==-1) {
			return false;
		}
		if($account_link=get_record("studynotes_account_links", "system", "moodle", "internal_id", $to)) {
			if($destination = get_record("user","id",$account_link->external_id)) {
				$supportuser = generate_email_supportuser();
				return email_to_user($destination, $supportuser, $subject, $body);
			}
		}
		return false;
	}

	/**
	 * Retrieve all known users for the current Moodle user
	 * @return array Array of objects featuring name and Moodle id
	 */
	function getKnownUsers(){
		global $course;
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		$students = get_role_users(5 , $context);
		$tmp = (array)null;

		foreach($students as $student){
			$knownUser = (object) null;
			$knownUser->id = $student->id;

			if ($student->maildisplay){
				$knownUser->email = $student->email;
			}
			$fullname = $student->firstname.' '.$student->lastname;

			if (strlen(trim($fullname)) == 0) {
				$fullname = $student->username;
			}
			$knownUser->name = $fullname;
			if ($account_link = get_record("studynotes_account_links", "system", "moodle", "external_id", $student->id)){
				$knownUser->mb_id = $account_link->internal_id;
			}
			$tmp[] = $knownUser;
		}
		return $tmp;
	}

	/**
	 * Invite known user
	 * @param int $id Id of known Moodle user
	 * @param bool $inviteeUnknown True if user was invited, false otherwise
	 * @return Mediabird user ID
	 */
	function inviteKnownUser($moodleid, &$inviteeUnknown) {
		global $helper;
		$mbuser = false;
		 
		if($records = get_records_select('user', "id=$moodleid", '', 'id,username,firstname,lastname,email')){
			$record = $records[$moodleid];
			$email = $record->email;
			$fullname = $record->firstname.' '.$record->lastname;
			if (strlen(trim($fullname)) == 0) {
				$fullname = $record->username;
			}
			if ($account_link = get_record("studynotes_account_links", "system", "moodle", "external_id", $moodleid)) {
				$mbuser = $account_link->internal_id;
				$inviteeUnknown = false;
			}
			else {
				if ($mbuser = $helper->registerUser($fullname, 1, $email)) {
					$account_link = (object)null;
					$account_link->external_id = $moodleid;
					$account_link->internal_id = $mbuser;
					$account_link->system = "moodle";
					insert_record("studynotes_account_links", $account_link, false);
					$inviteeUnknown = true;
				}
			}
		}

		return $mbuser;
	}
}
?>
