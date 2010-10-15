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
 * Handles client session requests
 * @author fabian
 */
class MediabirdSessionHandler {
	/**
	 * Processes a session request from the client
	 * @param $action Command that is to be performed
	 * @param $auth Auth interface to identify the current user
	 * @param $args Arguments for the given command
	 * @return stdClass Object that is supposed to be sent back to the client
	 */
	function process($action, $auth, $args) {
		global $mediabirdDb;

		$dataHandler = new MediabirdDataHandler($auth->userId);

		$reply = (object)null;
		if ( isset ($args['settings'])) {
			$settings = MediabirdUtility::getArgNoSlashes($args['settings']);
			$dataHandler->storeSettings($settings);
		}

		switch($action) {
			case "keepAlive": //keep alive session, that's done above
				$reply->success = true;
				break;
			case "loadTopicList": //retrieve the topic list

				$query = "SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE user_id='$auth->userId'";

				$infos = (array)null;
				if ($result = $mediabirdDb->getRecordSet($query)) {
					while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
						$id = intval($results['id']);
						$infos[$id] = MediabirdTopicAccessConstants::owner;
					}
					$query = "SELECT topic,mask FROM ".MediabirdConfig::tableName('Right')." WHERE mask>0 AND group_id=ANY (SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$auth->userId AND active=1)";
					if ($result = $mediabirdDb->getRecordSet($query)) {
						$reply->topics = (array)null;
						while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
							$id = intval($results['topic']);
							$mask = intval($results['mask']);
							if (array_key_exists($id, $infos)) {
								$infos[$id] = $infos[$id] | $mask;
							}
							else {
								$infos[$id] = $mask;
							}
						}
						$reply->topics = (array)null;
						foreach ($infos as $id=>$mask) {
							if ($topic = $dataHandler->updateTopic($id, null, $mask, true)) {
								$topic->access = $mask;
								$reply->topics[] = $topic;
							}
						}
					}
					else {
						$reply->error = "database error";
						error_log($query);
					}
				}
				else {
					$reply->error = "database error";
					error_log($query);
				}
				$reply->success = true;

				break;
			case "checkTopicRevision":
				$remoteRevision = intval($args['revision']); // revision on client
				$topicId = intval($args['id']);

				if($topic = $mediabirdDb->getRecord(MediabirdConfig::tableName('Topic',true),"id=$topicId")) {
					//attempt to load topic as owner
					if($topic->user_id != $auth->userId) {
						if($dataHandler->getTopicRights($topicId)<MediabirdTopicAccessConstants::allowViewingCards) {
							$reply->error = "accessdenied";
							break;
						}
					}

					$revision = intval($topic->revision); // revision in db
					if ($revision <= $remoteRevision) {
						$reply->success = true; //revision is up-to-date
						break;
					}
					else {
						//fall through
						$data = null;
						$ignoreContent = true;
					}
				}
				else {
					$reply->error = "database error";
					break;
				}

				//fall through
			case "updateTopic": //update or create a topic
				if ($action == "updateTopic") {
					$data = json_decode(MediabirdUtility::getArgNoSlashes($args['topic']));

					if ( isset ($args['id']) && is_numeric($args['id'])) {
						$topicId = intval(MediabirdUtility::getArgNoSlashes($args['id']));
						$ignoreContent = true;
					}
					else {
						if (property_exists($data, "title") && property_exists($data, "category") && strlen($data->title) > 0 && strlen($data->category) > 0) {
							$topic = (object)null;
							$topic->user_id = $auth->userId;
							$topic->created = $mediabirdDb->datetime(time());
							$topic->modified = $mediabirdDb->datetime(time());
							$topic->title = '-';
							if($topicId = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Topic', true), $topic)){
								$ignoreContent = false; //update content for new topic
							}
							else {
								$reply->error = "database error";
							}
						}
						else {
							$reply->error = "invaliddata";
						}
					}
				}

				if (! isset ($reply->error)) {
					//check if user is owner
					$query = "SELECT user_id FROM ".MediabirdConfig::tableName('Topic')." WHERE id=$topicId";
					if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
						$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
						$owner = intval($results['user_id']);
						$mask = 0;
						if ($owner != $auth->userId) {
							//retrieve access rights
							$mask = $dataHandler->getTopicRights($topicId);
						}
						else {
							//owner has full rights
							$mask = MediabirdTopicAccessConstants::owner;
						}
						if ($mask == 0) {
							$reply->error = "accessdenied";
						}
					}
					else {
						$reply->error = "database error";
					}
				}
				if (! isset ($reply->error)) {
					if ($topic = $dataHandler->updateTopic($topicId, $data, $mask, $ignoreContent)) {
						$topic->access = $mask;
						$reply->success = true;
						if ( isset ($topic->reverted)) {
							$reply->reverted = $topic->reverted;
							unset ($topic->reverted);
						}
						$reply->topic = $topic;
					}
					else {
						$reply->error = "database error";
					}
				}
				break;
			case "updateTopicLicense": //update or create a topic

				$topicId = intval(MediabirdUtility::getArgNoSlashes($args['id']));
				$newLicense = intval(MediabirdUtility::getArgNoSlashes($args['license']));

				$query = "SELECT license,user_id FROM ".MediabirdConfig::tableName('Topic')." WHERE id=$topicId";
				if (($result = $mediabirdDb->getRecordSet($query)) && ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
					$license = intval($results['license']);
					$user = intval($results['user_id']);
					if ($user == $auth->userId) {
						if ($license != $newLicense) {
							$topicDb=(object)null;
							$topicDb->id=$topicId;
							$topicDb->license=$newLicense;

							if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('Topic',true),$topicDb)) {
								$reply->success = true;
								$reply->license = $newLicense;
							}
							else {
								$reply->error = "database error";
							}
						}
						else {
							$reply->success = true;
							$reply->license = $license;
						}
					}
					else {
						$reply->error = "accessdenied";
					}
				}
				else {
					$reply->error = "database error";
				}

				break;
			case "deleteTopics": //delete a topic

				$topicIds = split(",", $args['ids']);

				foreach ($topicIds as $topicId) {
					//get user of topic and check if current user is owner
					$query = "SELECT user_id FROM ".MediabirdConfig::tableName('Topic')." WHERE id=$topicId AND user_id=$auth->userId";
					if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
						if ($dataHandler->deleteTopic($topicId)) {
							$reply->success = true;
						}
						else {
							unset ($reply->success);
							$reply->error = "database error";
							break;
						}
					}
					else {
						unset ($reply->success);
						$reply->error = "accessdenied";
						break;
					}
				}
				break;
			case "updateCard": //updates the contents and markers of an already registered content card
				$isUpdateCard = true;
			case "updateMarkers": //updates the personal markers of an already registered content card
				$cardId = intval($args['id']);

				if (! isset ($isUpdateCard)) {
					$isUpdateCard = false;
				}

				//determine topic
				$query = "SELECT id,user_id FROM ".MediabirdConfig::tableName('Topic')." WHERE id=ANY (SELECT topic FROM ".MediabirdConfig::tableName('Card')." WHERE id=$cardId)";
				if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
					$record = $mediabirdDb->fetchNextRecord($result);
					$topicId = intval($record->id);
					$owner = intval($record->user_id);
					$mask = 0;
					if ($owner != $auth->userId) {
						$mask = $dataHandler->getTopicRights($topicId);
					}
					else {
						$mask = MediabirdTopicAccessConstants::owner;
					}
					if ($mask == 0) {
						$reply->error = "accessdenied";
					}
				}
				else {
					error_log($query);
					$reply->error = "accessdenied";
				}

				if ( isset ($args["markers"])) {
					$markers = json_decode(MediabirdUtility::getArgNoSlashes($args["markers"]));
				}
				$deletedMarkerIds = array (); //default to "none deleted"
				if ( isset ($args["deletedMarkerIds"])) {
					$deletedMarkerIds = json_decode(MediabirdUtility::getArgNoSlashes($args["deletedMarkerIds"]));
				}
				if ($isUpdateCard) {
					//check for card locks
					$minuteAgo = $mediabirdDb->datetime(time()-60);
					$query = "SELECT id FROM ".MediabirdConfig::tableName('Card')." WHERE id=$cardId AND (locked_by=$auth->userId OR locked_by=0 OR locked_time < '$minuteAgo')";

					if ($result = $mediabirdDb->getRecordSet($query)) {
						if ($mediabirdDb->recordLength($result) == 1) {
							$properties = (object)null;
							if ( isset ($args["content"])) {
								if ($args["content"] == "null") {
									$properties->content = null;
								}
								else {
									$content = $dataHandler->purifyHTML(MediabirdUtility::getArgNoSlashes($args["content"]));

									if (strlen($content) > MediabirdConstants::maxCardSize) {
										$reply->error = "toobig";
									}
									else {
										$properties->content = $content;
									}
								}
							}
							if ( isset ($args["title"])) {
								$properties->title = MediabirdUtility::getArgNoSlashes($args["title"]);
							}
							if ( isset ($markers)) {
								$properties->markers = $markers;
							}
						}
						else {
							$reply->error = "locked";
						}
					}
					else {
						error_log($query);
						$reply->error = "database error";
					}
				}
				else {
					$properties = $markers;
				}

				if (! isset ($reply->error)) {
					if ($isUpdateCard) {
						if ($card = $dataHandler->updateCard($topicId, $cardId, $properties, $mask, null, property_exists($properties, "markers"))) {
							if ((!property_exists($properties, "markers")) || is_array($card->markers = $dataHandler->updateMarkers($cardId, $properties->markers, $deletedMarkerIds, $mask, $auth->userId))) {
								$reply->success = true;
								$reply->content = $card->content;
								$reply->revision = $card->revision;
								$reply->title = $card->title;
								if (property_exists($card, "markers") && is_array($card->markers)) {
									$reply->markers = $card->markers;
								}
							}
							else {
								$reply->error = "database error";
							}
						}
						else {
							$reply->error = "database error";
						}
					}
					else {
						if (($markers = $dataHandler->updateMarkers($cardId, $properties, $deletedMarkerIds, $mask, $auth->userId)) !== null) {
							$reply->success = true;
							$reply->markers = $markers;
							$query = "SELECT revision FROM ".MediabirdConfig::tableName('Card')." WHERE id=$cardId";
							if ($result = $mediabirdDb->getRecordSet($query)) {
								$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
								$revision = intval($results['revision']);
								$revision++;

								$card = (object)null;
								$card->id = $cardId;
								$card->revision = $revision;

								if (!$mediabirdDb->updateRecord(MediabirdConfig::tableName('Card',true),$card)) {
									error_log("could not increase revision of card $cardId");
								}
							}
							else {
								error_log($query);
							}
						}
						else {
							$reply->error = "database error";
						}
					}
				}
				break;
			case "checkCardRevision":
				$id = intval($args['id']);
				$rev = intval($args['revision']);

				if($card = $mediabirdDb->getRecord(MediabirdConfig::tableName('Card',true),"id=$id")) {
					$topicId = $card->topic;
					if($topic = $mediabirdDb->getRecord(MediabirdConfig::tableName('Topic',true),"id=$topicId")) {
						//attempt to load topic as owner
						if($topic->user_id != $auth->userId) {
							if($dataHandler->getTopicRights($topicId)<MediabirdTopicAccessConstants::allowViewingCards) {
								$reply->error = "accessdenied";
								break;
							}
						}

						//access okay
						$revision = intval($card->revision);
						if ($revision <= $rev) {
							$reply->success = true;
							break;
						}
						else {
							$args['ids'] = "$id";
							//fall through to "loadCards"
						}
					}
					else {
						$reply->error = "database error";
						break;
					}
				}
				else {
					$reply->error = "database error";
					break;
				}
				//fall through
			case "loadCards": //retrieves the contents of content cards (given by their id)
				$cardIds = explode(",", MediabirdUtility::getArgNoSlashes($args['ids']));
				$cards = (array)null;
				foreach ($cardIds as $cardId) {
					//determine topic
					$query = "SELECT id,user_id FROM ".MediabirdConfig::tableName('Topic')." WHERE id=ANY (SELECT topic FROM ".MediabirdConfig::tableName('Card')." WHERE id=$cardId)";
					if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
						$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
						$topicId = intval($results['id']);
						$owner = intval($results['user_id']);
						$mask = 0;
						if ($owner != $auth->userId) {
							$mask = $dataHandler->getTopicRights($topicId);
						}
						else {
							$mask = MediabirdTopicAccessConstants::owner;
						}
						if ($mask == 0) {
							$reply->error = "accessdenied";
						}
					}
					else {
						$reply->error = "accessdenied";
					}

					//load content
					if ($card = $dataHandler->updateCard($topicId, $cardId, null, $mask)) {
						if (is_array($markers = $dataHandler->updateMarkers($cardId, null, array (), $mask, $auth->userId))) {
							$card->markers = $markers;

							foreach ($card->markers as $marker) {
								//load flash cards
								$query = "SELECT * FROM ".MediabirdConfig::tableName('Flashcard')." WHERE marker=$marker->id AND user_id=$auth->userId ORDER BY num ASC";
								$resultFlashCards = $mediabirdDb->getRecordSet($query);
								while ($resultsFlashCards = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($resultFlashCards))) {
									$flashCard = (object)null;
									$flashCard->marker = $marker->id;
									$flashCard->number = intval($resultsFlashCards['num']);
									$flashCard->level = intval($resultsFlashCards['level_num']);
									if(isset($resultsFlashCards['markedforrepetition'])) {
										$flashCard->markedForRepetition = intval($resultsFlashCards['markedforrepetition']);
									}
									else {
										$flashCard->markedForRepetition = 0;
									}
									if(isset($resultsFlashCards['lasttimeanswered'])) {
										$flashCard->lastTimeAnswered = intval($resultsFlashCards['lasttimeanswered']);
									}
									else {
										$flashCard->lastTimeAnswered = 0;
									}
									$trainingData = intval($resultsFlashCards['results']);
									for ($i = 0; $i < 5; $i++) {
										$flashCard->results[] = ($trainingData & 3*pow(4, $i))/pow(4, $i);
									}
									$marker->flashCards[] = $flashCard;
								}
							}

							$cards[] = $card;
						}
						else {
							$reply->error = "database error";
						}
					}
					else {
						$reply->error = "database error";
					}
				}
				if (! isset ($reply->error)) {
					$reply->success = true;
					$reply->cards = $cards;
				}

				break;
			case "updateTrainingSession": //stores the current training session, expects a marker=>flashCards array
				$flashCards = json_decode(MediabirdUtility::getArgNoSlashes($args['trainingSession']));
				$result = true;
				foreach ($flashCards as $flashCard) {
					$trainingResults = 0;
					for ($i = 0; $i < sizeof($flashCard->results); $i++) {
						$trainingResults |= pow(4, $i)*$flashCard->results[$i];
					}
					if ($flashCard->number == 0) {
						$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Flashcard', true), "marker=$flashCard->marker AND user_id=$auth->userId");
					}
					$flashcard = (object)null;
					$flashcard->marker = $flashCard->marker;
					$flashcard->user_id = $auth->userId;
					$flashcard->num = $flashCard->number;
					$flashcard->level_num = $flashCard->level;
					$flashcard->lastTimeAnswered = $flashCard->lastTimeAnswered;
					$flashcard->markedForRepetition = $flashCard->markedForRepetition;
					$flashcard->results = $trainingResults;
					$result = $result && $mediabirdDb->insertRecord(MediabirdConfig::tableName('Flashcard', true),  $flashcard);
				}
				if ($result) {
					$reply->success = true;
				}
				else {
					$reply->error = "database error";
				}

				break;
			case "reportAbuse": //file an abuse report

				$id = $args['id'];
				$type = $args['type'];

				$body = "User with id $auth->userId has reported a violation against the Terms of Use.\nConcerned type: $type\nConcerned content id: $id\n";
				if (!MediabirdConfig::$disable_mail) {
					$oldReporting = error_reporting(0);
					if (method_exists($auth, 'sendMail') && $auth->sendMail(-1, "Terms of Use violation report", $body)) {
						$reply->success = true;
					}
					else {
						$reply->error = "errorsending";
					}
					error_reporting($oldReporting);
				}
				else {
					error_log("Abuse reported by user $auth->userId for data type $type and data id $id.");
					$reply->success = true;
				}


				break;
			case "suggestFeature": //file a suggestion

				$description = MediabirdUtility::getArgNoSlashes($args['description']);

				$body = "User with id $auth->userId has suggested the following feature:\n".$description;
				$body = wordwrap($body, 70);

				if (!MediabirdConfig::$disable_mail) {
					$oldReporting = error_reporting(0);
					if (method_exists($auth, 'sendMail') && $auth->sendMail(-1, "Mediabird Feedback", $body)) {
						$reply->success = true;
					}
					else {
						$reply->error = "errorsending";
					}
					error_reporting($oldReporting);
				}
				else {
					error_log("Feature suggested by user $auth->userId: $description .");
					$reply->success = true;
				}

				break;
			case "changePass":
				$current = MediabirdUtility::getArgNoSlashes($args['current']);
				$newpass = MediabirdUtility::getArgNoSlashes($args['newpass']);

				if ($current == $newpass) {
					$reply->success = true;
				}
				else {
					if($user = $mediabirdDb->getRecord(MediabirdConfig::tableName("User",true),"id=$auth->userId")) {
						if($user->password==$current) {
							$user->password=$newpass;
							if($mediabirdDb->updateRecord(MediabirdConfig::tableName("User",true),$user)) {
								$reply->success = true;
							}
						}
						else {
							$reply->error = "wrongpass";
						}
					}
				}

				break;
			case "deleteAccount": //delete the current account

				$current = MediabirdUtility::getArgNoSlashes($args['current']);

				$query = "SELECT email,name FROM ".MediabirdConfig::tableName('User')." WHERE id=$auth->userId AND password='".$mediabirdDb->escape($current)."'";
				$result = $mediabirdDb->getRecordSet($query);

				if ($result && $mediabirdDb->recordLength($result) == 1) {
					//fetch user info
					$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
					$name = $results['name'];
					$email = $results['email'];

					//delete user
					$result = $mediabirdDb->deleteRecords(MediabirdConfig::tableName('User',true), "id=$auth->userId AND password='".$mediabirdDb->escape($current)."'");

					//also delete topics
					$query = "SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE user_id=$auth->userId";
					$result = $mediabirdDb->getRecordSet($query);
					if ($result) {
						while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
							$topicId = $results['id'];
							$dataHandler->deleteTopic($topicId);
						}
					}


					$userfolder = ".MediabirdConfig::$uploads_folder.".$auth->userId.DIRECTORY_SEPARATOR;
					if (file_exists($userfolder)) {
						$oldReporting = error_reporting(0);
						MediabirdUtility::deleteFolder($userfolder);
						error_reporting($oldReporting);
					}


					//notify user
					$body = "Your account '$name' has been deleted and all associated personal data has been erased.\nWe hope you enjoyed using Mediabird. You are welcome back anytime.\nYour Mediabird team.";
					if (!MediabirdConfig::$disable_mail) {
						$oldReporting = error_reporting(0);
						if (method_exists($auth, 'sendMail') && $auth->sendMail($email, "Account cancelled", $body)) {
							$reply->success = true;
						}
						else {
							$reply->error = "errorsending";
						}
						error_reporting($oldReporting);
					}

					//restart session!
					$auth->restartSession();
					$reply->success = true;
				}
				else {
					$reply->error = "wrongpass";
				}

				break;
			case "checkOutCard": //checks out a card for editing
				$cardid = intval($args['id']);
				$minuteAgo = $mediabirdDb->datetime(time()-60);
				$select = "id=$cardid AND (locked_by IN (0,$auth->userId) OR locked_time < '$minuteAgo')";
				if($card = $mediabirdDb->getRecord(MediabirdConfig::tableName('Card',true),$select)) {
					$topicId = $card->topic;
					if($topic = $mediabirdDb->getRecord(MediabirdConfig::tableName('Topic',true),"id=$topicId")) {
						//attempt to load topic as owner
						if($topic->user_id != $auth->userId) {
							if($dataHandler->getTopicRights($topicId)<MediabirdTopicAccessConstants::allowViewingCards) {
								$reply->error = "accessdenied";
								break;
							}
						}

						//access okay
						$reply->revision = intval($card->revision);
						$card->locked_by=$auth->userId;
						$card->locked_time=$mediabirdDb->datetime(time());
						if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('Card',true),$card)) {
							$reply->success = true;
						}
						else {
							error_log("could not update card ".print_r($card,true));
							$reply->error = "database error";
						}
					}
					else {
						$reply->error = "database error";
					}
				}
				else {
					$reply->error = "locked";
				}
				break;
			case "checkInCard": //releases a content card lock
				$cardid = intval($args['id']);
				if($card=$mediabirdDb->getRecord(MediabirdConfig::tableName('Card',true),"id=$cardid AND locked_by=$auth->userId")) {
					$card->locked_by = 0;
					if($mediabirdDb->updateRecord(MediabirdConfig::tableName('Card',true),$card)) {
						$reply->success = true;
					}
					else {
						$reply->error = "database error";
					}
				}
				else {
					$reply->error = "database error";
				}
				break;
			case "loadNotifications":

				//feed.title, feed.message_type
				$query = "SELECT id, object_id, object_type, user_id, feed_id FROM ".MediabirdConfig::tableName('FeedMessage')."
				feed_id = ANY (SELECT feed_id FROM ".MediabirdConfig::tableName('FeedSubscription')." WHERE user_id=$auth->userId) AND user_id = ANY
					(SELECT user_id FROM ".MediabirdConfig::tableName('Membership')." WHERE active=1 AND user_id <> $auth->userId AND group_id = ANY
					 (SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE active=1 AND user_id <> $auth->userId)
					 ) AND message.id NOT IN 
					 ( SELECT message_id FROM ".MediabirdConfig::tableName('FeedMessagesStatus')." WHERE user_id=$auth->userId AND status=1 )
					  GROUP BY id";
				if ($result = $mediabirdDb->getRecordSet($query)) {
					$feedMessages = (array)null;
					while (($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
						$feedMessage = (object)null;
						$feedMessage->id = intval($results['id']);
						$feedMessage->feedId = intval($results['feed_id']);
						$feedMessage->objectId = intval($results['object_id']);
						$feedMessage->objectType = intval($results['object_type']);
						$feedMessage->userId = intval($results['user_id']);
						$feedMessages[] = $feedMessage;
					}

					//collect feed ids
					$feedIds = array();
					foreach($feedMessages as $feedMessage) {
						if(!in_array($feedMessage->feedId,$feedIds)) {
							array_push($feedIds,$feedMessage->feedId);
						}
					}

					if($records=$mediabirdDb->getRecords(MediabirdConfig::tableName('Feed',true),"id IN (".join(",",$feedIds).")",'',"id,title,message_type")) {
						foreach($feedMessages as $fi => $feedMessage) {
							foreach($records as $record) {
								if($record->id==$feedMessage->feedId) {
									$feedMessage->messageType = $record->message_type;
									$feedMessage->feedTitle = $record->title;
									$feedMessages[$fi]=$feedMessage;
									break;
								}
							}
						}
					}
					$reply->notifications = $feedMessages;
					$reply->success = true;
				}
				else {
					error_log($query);
					$reply->error = "database error";
				}
				break;
			case "markNotificationAsRead":
				$id = intval($args['id']);

				//check if $id valid
				$query = "SELECT id,status FROM ".MediabirdConfig::tableName('FeedMessagesStatus')." WHERE user_id=$auth->userId AND message_id=$id ";
				if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
					if ($results = $mediabirdDb->fetchNextRecord($result)) {
						$statusId = intval($results->id);
						$statusStatus = intval($results->status);
						if ($statusStatus != 1) {
							$messagesStatus = (object)null;
							$messagesStatus->id=$statusId;
							$messagesStatus->status=1;
							if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('FeedMessagesStatus',true),$messagesStatus)) {
								$reply->success = true;
							}
							else {
								error_log($query);
								$reply->error = "database error";
							}
						}
						else {
							$reply->success = true;
						}
					}
					else {
						error_log($query);
						$reply->error = "database error";
					}
				}
				else {
					$feedDB = (object)null;
					$feedDB->message_id = $id;
					$feedDB->status = 1;
					$feedDB->user_id = $auth->userId;
					if ($mediabirdDb->insertRecord(MediabirdConfig::tableName('FeedMessagesStatus', true), $feedDB)) {
						$reply->success = true;
					}
					else {
						error_log($query);
						$reply->error = "database error";
					}
				}

				break;
			case "getCardsWithMarker":
				$type = $args['tool'];

				$select =
				"id IN (
					SELECT card FROM ".MediabirdConfig::tableName('Marker')." WHERE  notify>0 AND tool='".$mediabirdDb->escape($type)."'  
						AND (shared = 1 OR user_id = $auth->userId)
					)
					AND (
						topic IN (
							SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE user_id=$auth->userId
						)
						OR
						topic IN (
							SELECT topic FROM ".MediabirdConfig::tableName('Right')." WHERE group_id IN (
								SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$auth->userId AND active=1
							)
						)
					)";
				
				$cards = (array)null;
				if ($results = $mediabirdDb->getRecords(MediabirdConfig::tableName('Card',true),$select,'created DESC','id')) {
					foreach ($results as $result) {
						$card = (object)null;
						$card->id = intval($result->id);
						$cards[] = $card;
					}
				}
				$reply->cards = $cards;
				$reply->success = true;
				break;
			case "loadGroups":
				$referredUsers = (array)null;
				//retrieve all groups where current user is member and public groups
				$query = "SELECT * FROM ".MediabirdConfig::tableName('Group')." WHERE id=ANY (SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$auth->userId) OR access_num>0";
				$reply->groups = (array)null;
				if ($result = $mediabirdDb->getRecordSet($query)) {
					while (($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
						$group = (object)null;
						$group->id = intval($results['id']);
						$group->name = $results['name'];
						$group->category = $results['category'];
						$group->description = $results['description'];
						$group->access = intval($results['access_num']);

						//check for own membership
						$query = "SELECT level_num,active FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$group->id AND user_id=$auth->userId";
						if (($resultMember = $mediabirdDb->getRecordSet($query)) && $results = $mediabirdDb->fetchNextRecord($resultMember)) {
							$memberMe = (object)null;
							$memberMe->user = $auth->userId;
							$memberMe->enabled = intval($results->active);
							$memberMe->level = intval($results->level_num);
							$group->members[] = $memberMe;
						}

						//retrieve all members!
						$query = "SELECT user_id,level_num,active FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$group->id AND user_id<>$auth->userId";
						if (!isset ($memberMe) || ($memberMe->enabled != 1 && $memberMe->enabled != 3)) {
							//if not member of group or (requested or invited by a member) -> only show active members, hide invitees and requesters
							$query .= " AND active=1";
						}
						if ($resultMembers = $mediabirdDb->getRecordSet($query)) {
							while ($resultsMember = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($resultMembers))) {
								$member = (object)null;
								$member->user = intval($resultsMember['user_id']);
								if (array_search($member->user, $referredUsers) === false) {
									$referredUsers[] = $member->user;
								}
								$member->enabled = intval($resultsMember['active']);
								$member->level = intval($resultsMember['level_num']);
								$group->members[] = $member;
							}
						}
						$reply->groups[] = $group;
					}
				}
				else {
					$reply->error = "database error";
					error_log($query);
				}

				$users = (array)null;
				if (count($referredUsers) > 0) {
					$query = "SELECT id,name,email FROM ".MediabirdConfig::tableName('User')." WHERE id IN (".join(",", $referredUsers).")";
					if ($result = $mediabirdDb->getRecordSet($query)) {
						while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
							$userInfo = (object)null;
							$userInfo->id = intval($results['id']);
							$userInfo->name = $results['name'];
							$userInfo->email = $results['email'];
							$users[] = $userInfo;
						}
						$reply->success = true;
					}
					else {
						$reply->error = "database error";
						error_log($query);
					}
				}
				else {
					$reply->success = true;
				}
				$reply->userNames = $users;

				if (isset($args['includeKnown']) && method_exists($auth, 'getKnownUsers')) {
					$externalUsersTemp = $auth->getKnownUsers();

					$externalUsers = array ();

					foreach ($externalUsersTemp as $externalTemp) {
						$found = false;
						if (property_exists($externalTemp, 'mb_id')) {
							foreach ($users as $user) {
								if ($user->id == $externalTemp->mb_id) {
									$found = true;
								}
							}
						}
						if (!$found) {
							array_push($externalUsers, $externalTemp);
							
						}
					}

					$reply->externalUsers = $externalUsers;
				}
				break;
			case "updateGroup":
				$groupId = intval(MediabirdUtility::getArgNoSlashes($args['id']));
				$properties = json_decode(MediabirdUtility::getArgNoSlashes($args['group']));

				//check for own membership
				$query = "SELECT level_num,active FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$groupId AND user_id=$auth->userId";
				$memberMe = (object)null;
				if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1 && $results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
					if (intval($results['level_num']) >= MediabirdConstants::groupLevelAdmin) {
						//we are admin and allowed to change it
						$groupDB = (object)null;
						$groupDB->access_num = $properties->access;
						$groupDB->name = $properties->name;
						$groupDB->category = $properties->category;
						$groupDB->description = $properties->description;
						$groupDB->modified = $mediabirdDb->datetime(time());
						$groupDB->id = $groupId;
						if($mediabirdDb->updateRecord(MediabirdConfig::tableName('Group', true), $groupDB)){
							$reply->access = intval($properties->access);
							$reply->name = $properties->name;
							$reply->category = $properties->category;
							$reply->description = $properties->description;
							$reply->success = true;
						}
					}
					else {
						$reply->error = "norights";
					}
				}
				else {
					$reply->error = "database error";
				}

				break;
			case "createGroup":
				$group_raw = json_decode(MediabirdUtility::getArgNoSlashes($args['group']));
				$group = (object)null;
				$group->name = $group_raw->name;
				$group->description = $group_raw->description;
				$group->category = $group_raw->category;
				$group->type = 0;
				$group->access_num = $group_raw->access;
				$group->created = $mediabirdDb->datetime(time());
				$group->modified = $mediabirdDb->datetime(time());

				if ($id = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Group', true), $group)) {
					$group->id = intval($id);
					$group->access = $group->access_num;
					unset ($group->access_num);
					unset ($group->created);
					unset ($group->modified);
					//create membership with admin level
					$membershipDB = (object)null;
					$membershipDB->group_id = $group->id;
					$membershipDB->user_id = $auth->userId;
					$membershipDB->level_num = MediabirdConstants::groupLevelAdmin;
					$membershipDB->active = 1;
					$membershipDB->created = $mediabirdDb->datetime(time());
					$membershipDB->modified = $mediabirdDb->datetime(time());

					if ($id = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Membership', true), $membershipDB)) {
						$membership = (object)null;
						$membership->id = intval($id);
						$membership->enabled = $membershipDB->active;
						$membership->level = $membershipDB->level_num;
						$membership->user = $membershipDB->user_id;
						$group->members[] = $membership;
						$reply->success = true;
						$reply->group = $group;

					}
					else {
						error_log("membership");
						$reply->error = "database error";
					}
				}
				else {
					error_log("group");
					$reply->error = "database error";
				}

				break;
			case "inviteToGroup":
				$groupId = intval(MediabirdUtility::getArgNoSlashes($args['group']));
				//users identifyable by id, i.e. mediabird members
				if ( isset ($args['ids']) && strlen($args['ids']) > 0) {
					$ids = MediabirdUtility::getArgNoSlashes($args['ids']);
					$ids = split(",", $ids);
				}
				else {
					$ids = (array)null;
				}
				
				if ( isset ($args['names']) && strlen($args['names']) > 0) {
					$emails = MediabirdUtility::getArgNoSlashes($args['names']);
					$emails = split(",", $emails);
				}
				else {
					$emails = (array)null;
				}

				//array containing users that have just been invited
				$unknownInvitees = (array)null; 
	
				if ( isset ($args['externalIds']) && strlen($args['externalIds']) > 0 && method_exists($auth, 'inviteKnownUser')) {
					$externalIds = MediabirdUtility::getArgNoSlashes($args['externalIds']); //users known from a mediabird embedding plattform
					$externalIds = split(",", $externalIds);
					
					$inviteeUnknown = false; // variable to receive a value whether the user is already using Mediabird or not  
					
					foreach ($externalIds as $eId) {
						if ($internalId = $auth->inviteKnownUser($eId, $inviteeUnknown)) {
							if($inviteeUnknown){
								array_push($unknownInvitees, $internalId);			   
							} 
							array_push($ids, $internalId);
						}
					}
				}

				if (count($ids) > 0 || count($emails) > 0) {
					//check for own membership
					$query = "SELECT level_num,active FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$groupId AND user_id=$auth->userId";
					$memberMe = (object)null;
					if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1 && ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
						$memberMe->user = $auth->userId;
						$memberMe->enabled = intval($results['active']);
						$memberMe->level = intval($results['level_num']);
					}


					//check for invite rights
					$query = "SELECT access_num FROM ".MediabirdConfig::tableName('Group')." WHERE id=$groupId";
					if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1 && ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
						if (( isset ($memberMe) && ($memberMe->level >= MediabirdConstants::groupLevelAdmin)) || (intval($results['access_num']) > 0)) {
							//current user is admin or group is public

							if (count($ids) != 0) {
								foreach ($ids as $i=>$id) {
									$ids[$i] = intval($id);
								}
							}

							if (count($emails) > 0) {
								foreach ($emails as $i=>$email) {
									if (MediabirdUtility::checkEmail($email)) { //checks if email has a valid format
										$query = "SELECT id FROM ".MediabirdConfig::tableName('User')." WHERE email='".$mediabirdDb->escape($email)."'";
										if ($result = $mediabirdDb->getRecordSet($query)) {
											if ($results = $mediabirdDb->fetchNextRecord($result)) {
												$emailUserId = intval($results->id);
												if (array_search($emailUserId, $ids) === false) {
													$ids[] = $emailUserId;
												}
											}
											else if (!method_exists($auth, "inviteUser")) {
												if (!property_exists($reply, "notfound")) {
													$reply->notfound = (array)null;
												}
												$reply->notfound[] = $email;
											}
											else { 
												// users unknown to the system are invited per mail here
												// using the auth interface to allow for external email invitation
												$mailSuccess = $auth->inviteUser($email);
												if ($mailSuccess) {
													$ids[] = $mailSuccess;
													$unknownInvitees[] = $mailSuccess;
												}
												else {
													$reply->notfound[] = $email;
												}
											}
										}
									}
								}
							}

							//find valid ids
							$query = "SELECT id FROM ".MediabirdConfig::tableName('User')." WHERE id<>$auth->userId AND ";
							if (count($ids) > 0) {
								$query .= "id IN (".join(",", $ids).")";
							}
							else {
								$query .= "0=1";
							}
							if ($result = $mediabirdDb->getRecordSet($query)) {
								while ($results = $mediabirdDb->fetchNextRecord($result)) {
									$inviteId = intval($results->id);
									//check if user is already member of group
									$select = "user_id=$inviteId AND group_id=$groupId";
									if (!$mediabirdDb->getRecord(MediabirdConfig::tableName('Membership',true),$select)) {
										if ($memberMe->level >= MediabirdConstants::groupLevelAdmin) {
											if(in_array($inviteId, $unknownInvitees)){
												// if user has just been invited to Mediabird, a full membership is created
												// new user will be able to find shared topic when having logged in
												$enabled = 1; 
											}
											else {
												$enabled = 3;													
											}
										}
										else {
											$enabled = 2;
										}
										$membershipDB = (object)null;
										$membershipDB->user_id = $inviteId;
										$membershipDB->group_id = $groupId;
										$membershipDB->active = $enabled;
										$membershipDB->created = $mediabirdDb->datetime(time());
										$membershipDB->modified = $mediabirdDb->datetime(time());
										if($mediabirdDb->insertRecord(MediabirdConfig::tableName('Membership', true), $membershipDB)){
											if (!property_exists($reply, "invited")) {
												$reply->invited = (array)null;
											}
											$reply->invited []= $inviteId;
										}
										else {
											$reply->error = "database error";
										}
									}
								}
							}
							else {
								error_log($query);
								$reply->error = "database error";
							}
							if (! isset ($reply->error)) {
								$reply->success = true;
							}
						}
						else {
							$reply->error = "norights";
						}
					}
					else {
						error_log($query);
						$reply->error = "database error";
					}
				}
				else {
					$reply->state = "emptylist";
				}

				break;
			case "joinGroup":
				$groupId = intval(MediabirdUtility::getArgNoSlashes($args['id']));

				//test if already joined
				$query = "SELECT id,active FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$auth->userId AND group_id=$groupId";
				if ($result = $mediabirdDb->getRecordSet($query)) {
					$resultssCount = $mediabirdDb->recordLength($result);
					$enabled = 0;
					if ($resultssCount > 0 && ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
						$memberId = intval($results['id']);
						$enabled = intval($results['active']);
						$reply->state = $enabled;
					}

					if ($resultssCount == 0 || $enabled >= 2) { //not a member or was invited
						//check if user is allowed to join or request, or confirm invitation
						$query = "SELECT access_num FROM ".MediabirdConfig::tableName('Group')." WHERE id=$groupId";
						if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
							$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
							$access = intval($results['access_num']);

							//enabled = 3 means was invited by admin
							if ($enabled != 3 && $access == 0) {
								//access denied!
								$reply->error = "access denied";
							}
							else {
								if ($resultssCount == 0) { //request
									if ($access == 1) {
										$enabled = 0;
									}
									else {
										$enabled = 1;
									}
									$membershipDB = (object)null;
									$membershipDB->user_id = $auth->userId;
									$membershipDB->group_id = $groupId;
									$membershipDB->active = $enabled;
									$membershipDB->created = $mediabirdDb->datetime(time());
									$membershipDB->modified = $mediabirdDb->datetime(time());
									if($mediabirdDb->insertRecord(MediabirdConfig::tableName('Membership', true), $membershipDB)){
										$reply->state = $enabled;
									}
									$reply->created = true;
								}
								else { //confirm invitation
									if ($enabled == 3 || $access > 1) {
										$enabled = 1;
									}
									else {
										$enabled = 0;
									}
									$membership = (object)null;
									$membership->id = $memberId;
									$membership->active = $enabled;
									$membership->modified = $mediabirdDb->datetime(time());
									if($mediabirdDb->updateRecord(MediabirdConfig::tableName('Membership',true), $membership)){
										$reply->state = $enabled;
									}
								}
							}
						}
					}
				}
				if (property_exists($reply, "state")) {
					$reply->success = true;
				}
				else {
					if (! isset ($reply->error)) {
						$reply->error = "database error";
					}
				}

				break;
			case "updateMember": //promote, accept or remove member
				$groupId = intval(MediabirdUtility::getArgNoSlashes($args['group']));
				$memberUserId = intval(MediabirdUtility::getArgNoSlashes($args['user']));
				$level = intval(MediabirdUtility::getArgNoSlashes($args['level']));
				$enabled = intval(MediabirdUtility::getArgNoSlashes($args['enabled']));

				//check if user has admin rights
				$query = "SELECT id FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$groupId AND user_id=$auth->userId AND (level_num >= ".MediabirdConstants::groupLevelAdmin.")";
				if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
					if ($memberUserId != $auth->userId) {
						if ($level != -1) {
							//check if there is a user that can be promoted
							$membershipDB = $mediabirdDb->getRecord(MediabirdConfig::tableName('Membership', true), "group_id=$groupId AND user_id=$memberUserId AND active=0");
							$membershipDB->level_num = $level;
							$membershipDB->active = $enabled;
							$membershipDB->modified = $mediabirdDb->datetime(time());
							if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('Membership', true), $membershipDB)) {
								$reply->success = true;
								$reply->level = $level;
								$reply->enabled = $enabled;
							}
							else {
								$reply->error = "nomember";
							}
						}
						else {
							if($dataHandler->deleteMembership($memberUserId, $groupId)) {
								$reply->success = true;
								$reply->level = -1;
							}
							else {
								$reply->error = "database error";
							}
						}

					}
					else {
						if ($level == -1) {
							$reply->error = "cannotremoveownmembership";
						}
						else {
							//only allow demoting oneself if there is at least one other admin!
							$query = "SELECT FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$groupId AND user_id<>$auth->userId AND (level_num >= ".MediabirdConstants::groupLevelAdmin.")";
							if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) > 0) {
								//demoting allowed
								$membershipDB = $mediabirdDb->getRecord(MediabirdConfig::tableName('Membership', true), "group_id=$groupId AND user_id=$memberUserId");
								$membershipDB->level_num = $level;
								$membershipDB->active = $enabled;
								$membershipDB->modified = $mediabirdDb->datetime(time());
								if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('Membership', true), $membershipDB)) {
									$reply->success = true;
									$reply->level = $level;
									$reply->enabled = $enabled;
								}
								else {
									$reply->error = "database error";
								}
							}
							else {
								$reply->error = "notenoughadmins";
							}
						}
					}
				}
				else {
					$reply->error = "norights";
				}

				break;
			case "leaveGroup":
				$groupId = intval(MediabirdUtility::getArgNoSlashes($args['id']));

				//check for further admins
				$query = "SELECT user_id FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$groupId AND (level_num >= ".MediabirdConstants::groupLevelAdmin.")";
				if ($result = $mediabirdDb->getRecordSet($query)) {
					$removeMembership = true;
					if ($mediabirdDb->recordLength($result) == 1) {
						$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
						if (intval($results['user_id']) == $auth->userId) {
							//user is only admin of group, promote to next member
							$query = "SELECT id FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$groupId AND user_id <> $auth->userId";
							if ($result = $mediabirdDb->getRecordSet($query)) {
								if ($mediabirdDb->recordLength($result) > 0) {
									if($membershipDB = $mediabirdDb->getRecord(MediabirdConfig::tableName('Membership', true), "group_id=$groupId AND user_id <> $auth->userId AND active=1")) {
										$membershipDB->level_num = MediabirdConstants::groupLevelAdmin;
										$membershipDB->modified = $mediabirdDb->datetime(time());
										if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('Membership', true), $membershipDB)) {
											$reply->state = "foundnewadmin";
										}
										else {
											$removeMembership = false;
										}
									}
									else {
										$removeMembership = false;
									}
								}
								else {
									//no user left, delete group!
									if ($dataHandler->deleteGroup($groupId)) {
										$reply->state = "groupremoved";
									}
									else {
										$removeMembership = false;
									}
								}
							}
							else {
								$removeMembership = false;
							}
						}
					}
					if ($removeMembership) {
						if ($dataHandler->deleteMembership($auth->userId, $groupId)) {
							$reply->success = true;
						}
						else {
							$reply->error = "database error";
						}
					}
					else {
						$reply->error = "nonewadmin";
					}
				}
				else {
					$reply->error = "database error";
				}

				break;
			case "shareTopic":
				$topicId = intval(MediabirdUtility::getArgNoSlashes($args['topic']));
				$groupId = intval(MediabirdUtility::getArgNoSlashes($args['group']));
				$mask = intval(MediabirdUtility::getArgNoSlashes($args['mask']));

				//check if user is owner of topic
				$query = "SELECT user_id FROM ".MediabirdConfig::tableName('Topic')." WHERE id=$topicId";
				if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
					$result = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
					$owner = intval($result['user_id']);
					if ($owner == $auth->userId) {
						//check if user is member of group
						$query = "SELECT id FROM ".MediabirdConfig::tableName('Membership')." WHERE group_id=$groupId AND user_id=$auth->userId AND active=1";
						if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
							//user is member of group, check if already shared
							$query = "SELECT id,mask FROM ".MediabirdConfig::tableName('Right')." WHERE topic=$topicId AND group_id=$groupId";
							if ($result = $mediabirdDb->getRecordSet($query)) {
								if ($mediabirdDb->recordLength($result) == 0) {
									//share
									$right = (object)null;
									$right->topic = $topicId;
									$right->group_id = $groupId;
									$right->mask = $mask;
									if ($rightId = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Right', true), $right)) {
										$reply->mask = $mask;
										$reply->id = $rightId;
										$reply->success = true;
									}
									else {
										$reply->error = "database error";
									}
								}
								else {
									if ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
										$reply->id = intval($results['id']);
										$reply->mask = $mask;
										$currentMask = intval($results['mask']);
										if ($currentMask != $mask) {
											$rightDB = (object)null;
											$rightDB->id = $reply->id;
											$rightDB->mask = $mask;
											if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('Right', true), $rightDB)) {
												$reply->success = true;
											}
											else {
												$reply->error = "database error";
											}
										}
										else {
											$reply->success = true;
										}
									}
									else {
										$reply->error = "database error";
									}
								}
							}
							else {
								$reply->error = "database error";
							}
						}
						else {
							$reply->error = "notmember";
						}
					}
					else {
						$reply->error = "notowner";
					}
				}


				break;
			case "searchDatabase":
				$query = MediabirdUtility::getArgNoSlashes($args['query']);
				$type = intval($args['type']);

				if ($results = $dataHandler->searchDatabase($query, $type)) {
					$reply->groups = $results[0];
					$reply->topics = $results[1];
					$reply->cards = $results[2];
					$reply->success = true;
				}
				else {
					$reply->error = "database error";
				}
				break;
			case "checkEquationSupport":
				$reply->exists = class_exists("LatexRender", false) && file_exists(MediabirdConfig::$latex_path) && file_exists(MediabirdConfig::$convert_path);
				break;
			case "renderEquation":
				if (class_exists("LatexRender") && isset($args["topic"]) && isset($args["equation"])) {
					$topic = intval($args["topic"]);
					if(MediabirdUtility::checkAccess($topic,$auth->userId)) {
						$userFolder = MediabirdConfig::$uploads_folder;
						
						if(property_exists($auth,"userSubfolder")) {
							$userFolder .= $auth->userSubfolder.DIRECTORY_SEPARATOR;
						}
						else {
							$userFolder .= $auth->userId.DIRECTORY_SEPARATOR;
						}
						
						if (!file_exists($userFolder)) {
							mkdir($userFolder, 0777, true);
						}

						$userQuota = MediabirdUtility::getUserQuota($auth->userId);
						$quotaLeft = MediabirdUtility::quotaLeft($auth->userId, $userQuota);
						
						$equation = MediabirdUtility::getArgNoSlashes($args["equation"]);
							
						$renderer = new LatexRender($userFolder, "", MediabirdConfig::$cache_folder);

						$renderer->_latex_path = MediabirdConfig::$latex_path;
						$renderer->_convert_path = MediabirdConfig::$convert_path;

						$resultFile = $renderer->checkFormulaCache($equation);

						if (!$resultFile) {
							$resultFile = $renderer->renderLatex($equation);
							if ($resultFile && file_exists($resultFile)) {
								$fileSize = filesize($resultFile);

								if ($fileSize<$quotaLeft||$quotaLeft==-1) {
									$status_code = copy($resultFile, $renderer->destinationFile);
									if (!$status_code) {
										$resultFile = null;
										$renderer->_errorcode = 6;
									}
									else {
										$resultFile = $renderer->destinationFile;
									}
								}
								else {
									$resultFile = null;
									$renderer->_errorcode = 7; //not enough quota
								}
								$renderer->cleanTemporaryDirectory();
							}
							else {
								$renderer->_errorcode = 3; //could not render file
							}
						}
						else {
							$resultFile = $userFolder.$resultFile;
						}

						$reply = (object)null;
						if ($resultFile && file_exists($resultFile)) {
							$resultFile = str_ireplace(MediabirdConfig::$uploads_folder,'',$resultFile);
							$resultFile = str_replace(DIRECTORY_SEPARATOR, '/', $resultFile);
							$reply->success = true;
							$reply->topic = $topic;
							$reply->filename = $resultFile;
						}
						else {
							$reply->errorcode = $renderer->_errorcode;
							$reply->error = "latex";
						}
					}
					else {
						$reply->error="invalidtopic";
					}
				}
				break;
		}
		return $reply;
	}
}
?>
