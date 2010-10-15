<?php
/*
 * 	Copyright (C) 2008-2009 Fabian Gebert <fabiangebert@mediabird.net>
 *
 *	This file is part of Mediabird Web2.0-Learning.
 *
 *	Mediabird Web2.0-Learning is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	Mediabird Web2.0-Learning is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with Mediabird Web2.0-Learning.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Implementation of the default auth manager
 * Uses cookie driven PHP session management to store the user id
 * @author Fabian
 * 
 */
class MediabirdAuthManager {
	private $cookieName;
	/**
	 * Starts a new session
	 */
	private function startSession() {
		session_name($this->cookieName);
		session_start();
	}

	/**
	 * Constructor
	 * @param $cookieName
	 */
	function __construct($cookieName="Mediabird") {
		$this->cookieName=$cookieName;
		$this->startSession();
	}

	public $userId;

	/**
	 * Called to check if the current request runs on a valid session
	 * Sets global variable $userId to the current user id
	 * @return Boolean
	 */
	function isAuthorized() {
		if ( isset ($_SESSION['mb_user'])) {
			$this->userId = intval($_SESSION['mb_user']);
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Called when log-in was successful
	 * Stores the database user ID in the session object
	 * @param $userId Database user ID
	 * @return
	 */
	function createSession($userId) {
		$_SESSION['mb_user'] = $userId;
	}

	/**
	 * Restarts the session
	 */
	function restartSession() {
		// Unset all of the session variables.
		$_SESSION = array ();

		//destroy the session
		session_destroy();

		// But we do want a session started for the next request
		$this->startSession();
		session_regenerate_id();
	}

	/**
	 * Stores the session security code (used for captcha)
	 * @param string $code
	 */
	function setSecurityCode($code) {
		$_SESSION['mb_security_code']=$code;
	}

	/**
	 * Returns the session security code
	 * @return string
	 */
	function getSecurityCode() {
		if(isset($_SESSION['mb_security_code'])) {
			return $_SESSION['mb_security_code'];
		}
		else {
			return '';
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
		if(!MediabirdConfig::$disable_mail) {
			$address=null;
			if($to==-1) {
				$address=MediabirdConfig::$webmaster_address;
			}
			else {
				$query="SELECT email FROM ".MediabirdConfig::tableName('User')." WHERE id=$to";
				if($result=$mediabirdDb->getRecordSet($query)) {
					$results=$mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
					$address=$results['email'];
				}
			}
			if(isset($address)) {
				$headers = "From: ".MediabirdConfig::$no_reply_address."\r\n".
					"Reply-To: ".MediabirdConfig::$no_reply_address."\r\n".
					"X-Mailer: PHP/".phpversion();
				return mail($address, $subject, $body, $headers);	
			}
		}
		return false;
	}
}
?>
