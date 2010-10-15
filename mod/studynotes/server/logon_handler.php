<?php
/*
 * 	Copyright (C) 2008 Fabian Gebert <fabiangebert@mediabird.net>
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
 * Handles client sign-in, sign-up and sign-out requests
 * @author fabian
 *
 */
class MediabirdLogonHandler {
	
	/**
	 * Processes a logon/logout request from the client
	 * @param $action Command that is to be performed
	 * @param $auth Auth interface to identify the current user
	 * @param $args Arguments for the given command
	 * @return stdClass Object that is supposed to be sent back to the client
	 */
	function process($action, $auth, $args) {
		global $mediabirdDb;
		
		$reply = (object)null;

		switch($action) {
			case "signup":
				$name = MediabirdUtility::getArgNoSlashes($args['name']);
				$password = MediabirdUtility::getArgNoSlashes($args['password']);
				$password=sha1(MediabirdConfig::$security_salt.$password);
				$email = MediabirdUtility::getArgNoSlashes($args['email']);
				$captcha = MediabirdUtility::getArgNoSlashes($args['captcha']);

				if (!MediabirdConfig::$disable_signup) {
					if (!MediabirdUtility::checkEmail($email)) {
						$reply->error = "wrongemail";
					}
					else if (!$captcha || $auth->getSecurityCode() != $captcha) {
						$auth->restartSession();
						$reply->error = "wrongcaptcha";
					}
					else {
						$checkIfUniqueQuery = "SELECT email,name FROM ".MediabirdConfig::tableName('User')." WHERE email='".$mediabirdDb->escape($email)."' OR name='".$mediabirdDb->escape($name)."'";
						if ($result = $mediabirdDb->getRecordSet($checkIfUniqueQuery)) {
							if ($mediabirdDb->recordLength($result) > 0) {
								//there is already a user with same email or user name

								$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
								if ($results['email'] == $email) {
									$reply->error = "emailnotunique";
								}
								else {
									$reply->error = "namenotunique";
								}
							}
							else {
								if (MediabirdConfig::$disable_mail) {
									$hash = 1;
								}
								else {
									$hash = rand(2, pow(2, 24));
								}
								
								$user=(object)null;
								$user->name=$name;
								$user->password=$password;
								$user->email=$email;
								$user->active=$hash;
								$user->created=$mediabirdDb->datetime(time());
								
								if ($newId = $mediabirdDb->insertRecord(MediabirdConfig::tableName('User',true),$user)) {
									if (!MediabirdConfig::$disable_mail) {
										$oldReporting = error_reporting(0);

										$link = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?confirmemail=".urlencode($hash);

										$host = $_SERVER['SERVER_NAME'];
										$body = "Please confirm that you have registered the account '$name' at $host by opening the following location in your browser: $link . Please ignore this email if you have not issued the registration of this account. \nThank you.\n";

										if (method_exists($auth,'sendMail') && $auth->sendMail($newId, "Email confirmation for account $name", $body)) {
											$reply->success = true;
											$reply->mailsent = true;
										}
										else {
											$reply->error = "errorsending";
										}

										error_reporting($oldReporting);
									}
									else {
										$reply->success = true;
										$reply->mailsent = false;
									}
								}
								else {
									$reply->error = "database";
								}
							}
						}
						else {
							$reply->error = "database";
						}
					}
				}
				else {
					//signup disabled
					$reply->error = "disabled";
				}

				break;
			case "confirmemail":
				$hash = intval($_GET['confirmemail']);
				
				if ($user = $mediabirdDb->getRecord(MediabirdConfig::tableName('User',true),"active=$hash")) {
					$user->active=1;
					if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('User',true),$user)) {
						//success
						header("Location: ../confirmed.php?q=enabled");
						return;
					}
				}
				header("Location: ../confirmed.php");
				break;
			case "retrievepassword":
				$email = MediabirdUtility::getArgNoSlashes($args['email']);
				$captcha = MediabirdUtility::getArgNoSlashes($args['captcha']);

				if (!MediabirdConfig::$disable_mail) {
					if (!MediabirdUtility::checkEmail($email)) {
						$reply->error = "wrongemail";
					}
					else if (!$captcha || $auth->getSecurityCode() != $captcha) {
						$auth->restartSession();
						$reply->error = "wrongcaptcha";
					}
					else {
						$retrievePasswordQuery = "SELECT * FROM ".MediabirdConfig::tableName('User')." WHERE email='".$mediabirdDb->escape($email)."'";
						if (($result = $mediabirdDb->getRecordSet($retrievePasswordQuery)) && ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
							$name = $results['name'];
							$id = intval($results['id']);
							$password = $results['password'];

							$body = "You have requested a password notification.\n\nYour account is '$name' and the new password is '$password', both without the quotation marks.";
							
							$oldReporting = error_reporting(0);
							if (method_exists($auth,'sendMail') && $auth->sendMail($id, "Password retrieval for Mediabird", $body)) {
								$reply->success = true;
							}
							else {
								$reply->error = "errorsending";
							}
							error_reporting($oldReporting);
						}
						else {
							$reply->error = "nosuchuser";
						}
					}
				}
				else {
					//mail disabled
					$reply->error = "disabled";
				}
				break;
			case "signin":
				//check user and password, retrieve

				$name = MediabirdUtility::getArgNoSlashes($args['name']);
				$password = MediabirdUtility::getArgNoSlashes($args['password']);
				$password=sha1(MediabirdConfig::$security_salt.$password);
				$logonQuery = "SELECT id,active,settings FROM ".MediabirdConfig::tableName('User')." WHERE name='".$mediabirdDb->escape($name)."' AND password='".$mediabirdDb->escape($password)."'";
				$result = $mediabirdDb->getRecordSet($logonQuery);

				if ($result && ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {

					if ($results['active'] == 1) {
						$auth->userId = intval($results['id']);

						//update last login
						$user = $mediabirdDb->getRecord(MediabirdConfig::tableName('User',true),"id=$auth->userId");
						$user->last_login = $mediabirdDb->datetime(time());
						$mediabirdDb->updateRecord(MediabirdConfig::tableName('User',true),$user);
							
						//save the session info for subsequent requests
						$auth->createSession($auth->userId);

						$reply->id = $auth->userId;
						$reply->name = $name;
						$reply->settings = $results['settings'];
						$reply->success = true;
					}
					else {
						$reply->error = "disabled";
					}
				}
				else {
					$reply->error = "passwrong";
				}
				break;
			case "signout":
				//delete card locks associated with this user
				if ($auth->isAuthorized()) {
					$query="SELECT id,locked_by FROM ".MediabirdConfig::tableName('Card')." WHERE locked_by=$auth->userId";
					if ($result = $mediabirdDb->getRecordSet($query)) {
						while($record = $mediabirdDb->fetchNextRecord($result)) {
							$record->locked_by = 0;
							$mediabirdDb->updateRecord(MediabirdConfig::tableName('Card',true),$record);
						}
					}

					if ( isset ($args['settings'])) {
						$settings = MediabirdUtility::getArgNoSlashes($args['settings']);

						if ($settingsJson = json_decode($settings)) {
							$settings = json_encode($settingsJson);
							
							$user = $mediabirdDb->getRecord(MediabirdConfig::tableName('User',true),"id=$auth->userId");
							$user->settings = $settings;
							$mediabirdDb->updateRecord(MediabirdConfig::tableName('User',true),$user);
						}
					}

					$auth->restartSession();

					//notify back
					$reply->success = true;
				}
				break;
		}
		return $reply;
	}
}
?>
