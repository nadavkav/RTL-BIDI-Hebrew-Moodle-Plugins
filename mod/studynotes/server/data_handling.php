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
 * Provides more specific data handling than session handler
 * Being refered to from MediabirdSessionHandler
 * @author fabian
 *
 */
class MediabirdDataHandler {
	private $userId;
	/**
	 * Constructor
	 * @param int $userId Database Id of current user
	 */
	function __construct($userId=null) {
		$this->userId=$userId;
	}

	/**
	 * Returns the access rights of the current user against the given topic
	 * @param int $topicId Id of topic to check rights against
	 * @return int Bit mask specifying topic access rights of current user
	 */
	function getTopicRights($topicId) {
		global $mediabirdDb;

		$query = "SELECT mask FROM ".MediabirdConfig::tableName('Right')." WHERE topic=$topicId AND group_id=ANY
		(SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$this->userId AND active=1)";
		$mask = 0;
		if ($result = $mediabirdDb->getRecordSet($query)) {
			while($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
				$mask |= intval($results['mask']);
			}
		}
		return $mask;
	}

	/**
	 * Converts absolute links into relative ones for notes sent by the client
	 * @param string $input HTML containing (absolute) links
	 * @return string HTML featuring relative links for site-internal links
	 */
	function correctAbsoluteLinks($input) {
		if(!isset(MediabirdConfig::$www_root)) {
			$baseUrl = dirname($_SERVER['PHP_SELF']);
			$baseUrl = str_replace(DIRECTORY_SEPARATOR,"/",$baseUrl);

			if(substr($baseUrl,strlen($baseUrl)-1)!="/") {
				$baseUrl.="/";
			}

			$http = ( isset ($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off")?"https://":"http://".str_replace(".", "\\.", $_SERVER["SERVER_NAME"]);
			$port = $_SERVER["SERVER_PORT"];
			$baseUrl = str_replace(".", "\\.", $baseUrl);
			$subex = "$http(:$port){0,1}($baseUrl){0,1}";
		}
		else {
			$subex = MediabirdConfig::$www_root;
		}

		//affected attributes
		$attrList = "href|cite|background|codebase|action|usemapcite|src|data|classid|src|href|profile|longdesc";


		//regex
		$search = "%(<[^>]+)($attrList)=([\"']{0,1})$subex%mi";

		//just omit the final part of the expression which represents the web server's absolute path like http://youdomain/path/to/php
		$replace = '\1\2=\3';

		return preg_replace($search, $replace, $input);
	}

	private $_purifier;
	/**
	 * Filters HTML source for invalid constructs and forbidden items such as XSS scripts
	 * @param string $html HTML to be filtered
	 * @return string Filtered HTML
	 */
	function purifyHTML($html) {
		//correct absolute links and make them relative
		if (!MediabirdConfig::$disable_absolute_link_correction) {
			$html = $this->correctAbsoluteLinks($html);
		}

		if(class_exists("HTMLPurifier",false)) {
			if(!isset($this->_purifier)) {
				$config = HTMLPurifier_Config::createDefault();
				$config->set('Attr', 'EnableID', true);
				$config->set('CSS', 'AllowedProperties', array(
				'font-weight','font-style','text-align','text-decoration', //support text formatting
				'float', //support image float
				'width','height', //support image size
				'padding','padding-top','padding-right','padding-bottom','padding-left', //support image padding
				'margin','margin-left', //support indendation
				'direction' //support RTL/LTR
				));
				if(isset(MediabirdConfig::$cache_folder)) {
					$cachePath=MediabirdConfig::$cache_folder."filter";
					if(!file_exists($cachePath)) {
						mkdir($cachePath);
					}
					$config->set('Cache', 'SerializerPath', $cachePath);
				}
				$config->set('HTML', 'Doctype', 'HTML 4.01 Strict');
				$this->_purifier = new HTMLPurifier($config);
			}
			return $this->_purifier->purify($html);
		}
		else {
			return $html;
		}
	}

	/**
	 * Updates and retrieves a card from the database
	 * @return object Object holding the data of the card
	 * @param int $topicId ID of the topic the card belongs to
	 * @param int $cardId ID of the card to update and retrieve
	 * @param object $data Data to update to the database
	 * @param int $mask Access mask of the current user
	 * @param object $initial[optional] Object to extend with the data, if left out a new object will be used
	 */
	function updateCard($topicId, $cardId, $data, $mask, $initial = null, $enforceRevisiupdate=false, &$isLocked=false) {
		global $mediabirdDb;
		// a card is retrieved from server and associated data is copied into an object $card
		$query = "SELECT title,index_num,level_num,user_id,content,revision,locked_by,locked_time FROM ".MediabirdConfig::tableName('Card')." WHERE id=$cardId AND topic=$topicId";
		if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
			$card = $initial != null?$initial:(object)null;
			$results = $mediabirdDb->fetchNextRecord($result);
			$card->id = $cardId;
			$card->topic = $topicId;
			$card->title = $results->title;
			$card->index = intval($results->index_num);
			$card->level = intval($results->level_num);
			$card->user = intval($results->user_id);
			$card->revision = intval($results->revision);
			$card->content = $results->content;

			$minuteAgo = time()-60;
			$isLocked = ($results->locked_by!=0 && $results->locked_by!=$this->userId) && $mediabirdDb->timestamp($results->locked_time) > $minuteAgo;

			//process changes - in the following in multiple if (isset (..)) statments it is checked for changes to be added to the database
			if ( isset ($data)) {
				$cardDB = (object)null;
				$cardChanged = false;
				if (!$isLocked && isset ($data->title) && $card->title != $data->title && strlen($data->title) > 0 && ($mask & MediabirdTopicAccessConstants::allowEditingContent)) {
					$card->title = $data->title;
					$cardDB->title = $data->title;
					$cardChanged = true;
				}
				if (!$isLocked && property_exists($data, "content")) {
					if ($card->content != $data->content && ($mask & MediabirdTopicAccessConstants::allowEditingContent)) {
						$enforceRevisiupdate = true;
						if ( isset ($data->content)) {
							$card->content = $data->content;
							$cardDB->content = $data->content;
						}
						else {
							$card->content = null;
							$cardDB->content = null;
						}
						$cardChanged = true;
					}
				}

				if(!$isLocked && $enforceRevisiupdate) {
					$card->revision++;
					$cardDB->revision = $card->revision;
					$cardChanged = true;
				}

				if (property_exists($data, "level") && is_int($data->level) && $card->level != $data->level && ($mask & MediabirdTopicAccessConstants::allowRearrangingCards)) {
					$card->level = $data->level;
					$cardDB->level_num = $data->level;
					$cardChanged = true;
				}
				if (property_exists($data, "index") && is_int($data->index) && $card->index != $data->index && ($mask & MediabirdTopicAccessConstants::allowRearrangingCards)) {
					$card->index = $data->index;
					$cardDB->index_num = $data->index;
					$cardChanged = true;
				}
				if($cardChanged) {
					$cardDB->modified = $mediabirdDb->datetime(time());
					$cardDB->id = $card->id;
					if(!$mediabirdDb->updateRecord(MediabirdConfig::tableName('Card', true), $cardDB)){
						error_log("could not update card ".print_r($cardDB, true));
						return null;
					}
				}
			}
			return $card;
		}
		else {
			error_log($query);
			return null;
		}
	}

	/**
	 * Updates a marker in the database
	 * @return Object Marker object
	 * @param $cardId int Card id
	 * @param $id int Marker id
	 * @param $data Marker properties
	 * @param $userId int ID of current user
	 * @param $mask int Topic access mask
	 */
	function updateMarker($cardId, $id, $data, $userId, $mask) {
		global $mediabirdDb;
		if($userId==0) {
			error_log("invalid user ID in updateMarker");
		}
		$query = "SELECT * FROM ".MediabirdConfig::tableName('Marker')." WHERE (user_id=$userId OR shared=1 OR user_id=0) AND card=$cardId AND id=$id";
		if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1 && ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result)))) {
			$marker = (object)null;
			$marker->id = $id;
			$marker->tool = $results['tool'];
			$marker->shared = (bool)$results['shared'];
			$marker->notify = (bool)$results['notify'];
			$marker->data = $results['data'];
			$marker->range = $results['range_store'];
			$marker->user = intval($results['user_id']);
			$marker->revision = intval($results['revision']);

			$canAlter = $marker->user == $userId || ($mask & MediabirdTopicAccessConstants::allowAlteringMarkers);
			$canUpdate = $canAlter | $marker->shared;

			//only allow updates if revision was specified and equals local revision (was not changed in between)
			if(!isset($data) || !property_exists($data,"revision") || $data->revision!=$marker->revision) {
				$canAlter = $canUpdate = false;
			}

			$markerDB = (object)null;
			$markerChanged = false;
			if($marker->user == 0) {
				$marker->user = $userId; //assign global markers a user
				$marker->shared = 1;
				$markerDB->user_id = $marker->user;
				if(!$canAlter || !property_exists($data,"shared")) {
					$markerDB->shared = 1;
				}
				$markerChanged = true;
			}


			$query = "SELECT * FROM ".MediabirdConfig::tableName('Relation')." WHERE marker_id = $id";
			if($relationsResult = $mediabirdDb->getRecordSet($query)) {
				$relations = (array)null;
				while ($relationsResults = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($relationsResult))) {
					$relation = (object)null;
					$relation->type = $relationsResults['relation_type'];
					$relation->id = intval($relationsResults['id']);
					$relId = intval($relationsResults['relation_id']);
					$query = "SELECT * FROM ".MediabirdConfig::tableName('Relation'.ucfirst($relation->type)). " WHERE id = $relId";
					if($result = $mediabirdDb->getRecordSet($query)) {
						if($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
							foreach($results as $key=>$value){
								if($key!="id" && $key!="created" && $key!="modified") {
									$relation->$key=$value;
								}
							}
						}
					}
					else {
						error_log($query);
						return null;
					}
					$relations[] = $relation;
				}
			}
			else {
				error_log($query);
				return null;
			}
			$marker->relations = $relations;

			if ($canUpdate && isset ($data->relations)) {
				$obsoleteRelIds = array();
				$filteredRels = array();
				foreach ($marker->relations as $localRel) {
					$found = false;
					foreach ($data->relations as $remoteRel) {
						if(property_exists($remoteRel,"id") && $remoteRel->id == $localRel->id) {
							$found = true;
							break;
						}
					}
					if (!$found){
						$obsoleteRelIds[] = $localRel->id;
					}
					else {
						$filteredRels[] = $localRel;
					}
				}
				$marker->relations = $filteredRels;

				if (count($obsoleteRelIds) > 0) {
					$query = "id IN (".join(",",$obsoleteRelIds).")";
					if (!$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Relation',true),$query)) {
						error_log($query);
						return null;
					}
				}

				$newRels = (array)null;
				foreach($data->relations as $remoteRel) {
					$found = false;
					if(property_exists($remoteRel,"id")) { //only consider leaving DB unchanged if id is given
						foreach($marker->relations as $localRel){
							$found=true;
							//check if same entry already exists
							foreach($remoteRel as $key=>$value) {
								if($localRel->$key!=$remoteRel->$key) {
									$found=false;
									break;
								}
							}
							if($found) {
								break;
							}
						}
					}
					if($found == false){
						$newRels[] = $remoteRel;
					}
				}
				if(count($newRels) > 0){
					foreach($newRels as $newRel) {
						$whereCond = array();
						$relationDB = (object)null;
						foreach($newRel as $key => $value) {
							if($key!="type" && $key!="id") {
								$relationDB->$key = $value;
								$whereCond[]= $key."='".$mediabirdDb->escape($value)."'";
							}
						}

						unset($relId);
						if(count($whereCond)>0) {
							$query = "SELECT id FROM ".MediabirdConfig::tableName('Relation'.ucfirst($newRel->type))." WHERE (".join(" AND ",$whereCond).")";
							if($result=$mediabirdDb->getRecordSet($query,null,1)) {
								if($results = $mediabirdDb->fetchNextRecord($result)) {
									$relId=intval($results->id);
								}
							}
						}

						if(!isset($relId)) {
							$relationDB->created = $mediabirdDb->datetime(time());
							$relationDB->modified = $mediabirdDb->datetime(time());
							if(!$relId = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Relation'.ucfirst($newRel->type),true), $relationDB)){
								error_log("could not insert relation data ".print_r($relationDB, true));
								return null;
							}
						}

						//store the relation link
						$relationDB = (object)null;
						$relationDB->marker_id=$id;
						$relationDB->relation_id=$relId;
						$relationDB->relation_type=$remoteRel->type;
						$relationDB->created = $mediabirdDb->datetime(time());
						$relationDB->modified = $mediabirdDb->datetime(time());
							
						if($relId = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Relation',true),$relationDB)) {
							$newRel->id=$relId;
							$marker->relations[]=$newRel;
						}
						else {
							error_log("couldn't insert relation ".print_r($relationDB,true));
							return null;
						}
					}
				}
			}

			if ($canUpdate && isset ($data->data) && $marker->data != $data->data) {
				if ($props = json_decode($data->data)) {
					if ( isset ($props->question)) {
						$props->question = $this->purifyHTML($props->question);
					}
					if ( isset ($props->answer)) {
						$props->answer = $this->purifyHTML($props->answer);
					}
					$data->data = json_encode($props);
					$marker->data = $data->data;
					$markerDB->data = $marker->data;
					$markerChanged = true;
				}
			}

			if ($canAlter) {
				if ( isset ($data->range) && $marker->range != $data->range) {
					$marker->range = $data->range;
					$markerDB->range_store = $marker->range;
					$markerChanged = true;
				}
				if ( isset ($data->shared) && $marker->shared != $data->shared) {
					$marker->shared = intval($data->shared);
					$markerDB->shared = $marker->shared;
					$markerChanged = true;
				}
				if ( isset ($data->notify) && $marker->notify != $data->notify && $marker->user==$userId) {
					$marker->notify = intval($data->notify);
					$markerDB->notify = $marker->notify;
					$markerChanged = true;
				}
			}
			if($markerChanged) {
				$markerDB->modified = $mediabirdDb->datetime(time());
				$marker->revision++;
				$markerDB->revision = $marker->revision;
				$markerDB->id = $marker->id;
				if(!$mediabirdDb->updateRecord(MediabirdConfig::tableName('Marker', true), $markerDB)){
					error_log("could not update marker ".print_r($markerDB, true));
					return null;
				}
			}
			return $marker;
		}
		else
		{
			error_log($query);
			return null;
		}
	}

	/**
	 * Retrieves and updates markers
	 * @return Array Array of marker objects
	 * @param $id int Card id
	 * @param $propertySets Array Array of marker properties
	 * @param $deletedMarkerIds int[] Array of ids of markers to delete
	 * @param $mask int Topic access mask
	 * @param $userId int ID of current user
	 */
	function updateMarkers($id, $propertySets, $deletedMarkerIds, $mask, $userId) {
		global $mediabirdDb;
		if($userId==0) {
			error_log("invalid user ID in updateMarkers");
			return;
		}
		$markers = (array)null;
		if (is_array($propertySets)) {
			$markerIds = (array)null;
			foreach ($propertySets as $properties) {
				if (!property_exists($properties, "id") || !is_int($properties->id)) {
					//create marker
					$markerDB = (object) null;
					$markerDB->card=$id;
					$markerDB->tool=$properties->tool;
					$markerDB->user_id=$userId;
					$markerDB->created=$mediabirdDb->datetime(time());
					$markerDB->modified=$mediabirdDb->datetime(time());
					if ($markerDB->id=$mediabirdDb->insertRecord(MediabirdConfig::tableName('Marker',true),$markerDB)) {
						if ($marker = $this->updateMarker($id, $markerDB->id, $properties, $userId, $mask)) {
							$markers[] = $marker;
							$markerIds[] = $marker->id;
						}
						else {
							error_log("error in updateMarkers");
							return null;
						}
					}
					else {
						error_log($query);
						return null;
					}
				}
				else { // here markers are handled that are not new, i.e. updated
					if ($marker = $this->updateMarker($id, $properties->id, $properties, $userId, $mask)) {
						$markers[] = $marker;
						$markerIds[] = $marker->id;
					}
					else {
						//most probably does not exist, skip it
					}
				}
			}
		}
		//deal with the remaining markers
		$query = "SELECT id,user_id FROM ".MediabirdConfig::tableName('Marker')." WHERE (user_id IN (0,$userId) OR (shared=1 AND user_id IN
		(SELECT user_id FROM ".MediabirdConfig::tableName('Membership')." WHERE active=1
					AND group_id IN	
					(SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$userId AND active=1)
		AND group_id IN
		(SELECT group_id FROM ".MediabirdConfig::tableName('Right')." WHERE mask>0 AND topic IN
						(SELECT topic FROM ".MediabirdConfig::tableName('Card')." WHERE id=$id)
		)
		)
		)) AND card=$id";
		if ( isset ($markerIds) && count($markerIds) > 0) {
			$query .= " AND id NOT IN (".join(",", $markerIds).")";
		}
		if ($result = $mediabirdDb->getRecordSet($query)) {
			while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
				$markerId = intval($results['id']);
				$markerUserId = intval($results['user_id']);
				if (is_array($propertySets) && ($markerUserId==$userId ||($mask & MediabirdTopicAccessConstants::allowAlteringMarkers))&& in_array($markerId, $deletedMarkerIds)) {
					$cleanupFlashCardsQuery = "marker = $markerId";
					$cleanupMarkersQuery = "id=$markerId";
					$deleteRelationsQuery = "marker_id=$markerId";
					if ($mediabirdDb->deleteRecords(MediabirdConfig::tableName('Flashcard',true),$cleanupFlashCardsQuery) === false
					|| $mediabirdDb->deleteRecords(MediabirdConfig::tableName('Marker',true),$cleanupMarkersQuery) === false
					|| $mediabirdDb->deleteRecords(MediabirdConfig::tableName('Relation',true),$deleteRelationsQuery) === false) {
						return null;
					}
				}
				else {
					if ($marker = $this->updateMarker($id, $markerId, null, $userId, $mask)) {
						$markers[] = $marker;
					}
					else {
						error_log("error in updateMarker");
						return null;
					}
				}
			}
		}
		else {
			error_log($query);
			return null;
		}
		return $markers;
	}

	/**
	 * Retrieves and updates a topic
	 * Handles content cards including content but excluding markers
	 * @return stdClass
	 * @param $id int
	 * @param $data stdClass
	 * @param $mask int
	 * @param $ignoreCardContent bool True to ignore retrieved content, false otherwise
	 */
	function updateTopic($id, $data, $mask, $ignoreCardContent = false) {
		global $mediabirdDb;
		$topic = (object)null;
		$topic->id = $id;

		//retrieve data
		$query = "SELECT * FROM ".MediabirdConfig::tableName('Topic')." WHERE id=$id";
		if (($result = $mediabirdDb->getRecordSet($query)) && $mediabirdDb->recordLength($result) == 1) {
			$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
			$topic->title = $results['title'];
			if($topic->title==null) {
				$topic->title="-";
			}
			$topic->category = $results['category'];
			$topic->author = intval($results['user_id']);
			$topic->license = intval($results['license']);
			$topic->revision = intval($results['revision']);

			//count cards on main level
			$mainLevelCount = 0;

			$cards = array();
			$lockedCards = array();
			//retrieve cards
			$query = "SELECT id FROM ".MediabirdConfig::tableName('Card')." WHERE topic=$id ORDER BY index_num ASC";
			if ($result = $mediabirdDb->getRecordSet($query)) {
				while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
					$cardId = intval($results['id']);
					$cardLocked = false;
					if ($card = $this->updateCard($id, $cardId, null, $mask, null, false, &$cardLocked)) {
						if (!$ignoreCardContent && is_array($markers = $this->updateMarkers($card->id, null, array(), $mask, $this->userId))) {
							$card->markers = $markers;
						}
						$cards[] = $card;
						if($cardLocked) {
							$lockedCards[]=$card;
						}
						if($card->level==MediabirdConstants::levelMain) {
							$mainLevelCount++;
						}
						if ($ignoreCardContent && ! isset ($data) && isset ($card->content)) {
							unset ($card->content); //save traffic
						}
					}
					else {
						error_log($query);
						return null;
					}
				}
			}
			else {
				error_log($query);
				return null;
			}


			//update data
			if ( isset ($data)) {
				//update cards
				if (property_exists($data, "cards")) {
					//check if there is exactly one main card in each step
					//which either belongs to the topic or can be created
					$count = array(); //registers main index cards for later sorting
					$levelCount = array(); //jagged array that counts cards on their individual level
					$cardsToRemove = array();
					$cardsToUpdate = array();
					$cardsToAdd = array();
					$forgetChanges = false;
					
					$chargedIds = array();

					foreach ($data->cards as $remote) {
						if(!isset($levelCount[$remote->index][$remote->level])) {
							$levelCount[$remote->index][$remote->level]=0;
						}
						$levelCount[$remote->index][$remote->level]++;

						if(property_exists($remote,"id")) {
							if(in_array($remote->id,$chargedIds)) {
								//do not allow several cards with same id
								$forgetChanges=true;
								break;
							}
							else {
								array_push($chargedIds,$remote->id);
							}
						}
						
						unset ($local);
						foreach ($cards as $card) {
							if (property_exists($remote, "id") && $card->id == $remote->id) {
								$local = $card;
								if ($ignoreCardContent && isset ($local->content)) {
									unset ($local->content); //save traffic
								}
							}
						}
						if ( isset ($local)) {
							//exists locally
							if ($remote->index != $local->index || $remote->level != $local->level) {
								if ($mask & MediabirdTopicAccessConstants::allowRearrangingCards) {
									if(in_array($local, $lockedCards)) {
										unset($remote->content);
										unset($remote->title);
										unset($remote->category);
										unset($remote->markers);
									}
									$cardsToUpdate[] = $remote;
									$remote->local = $local;
								}
								else {
									$forgetChanges = true;
									break; //restructuring not permitted
								}
							}
							else if ((property_exists($remote, "title") && $remote->title != $local->title)
							|| (property_exists($remote, "category") && $remote->category != $local->category)
							|| (property_exists($remote, "content"))) {
								if (($mask & MediabirdTopicAccessConstants::allowEditingContent) && !in_array($local, $lockedCards)) {
									$cardsToUpdate[] = $remote;
									$remote->local = $local;
								}
								else {
									$forgetChanges = true;
									break; //editing not permitted
								}
							}
							else if (property_exists($remote, "markers") && is_array($remote->markers)) {
								if (($mask & MediabirdTopicAccessConstants::allowAlteringMarkers) && !in_array($local, $lockedCards)) {
									$cardsToUpdate[] = $remote;
									$remote->local = $local;
								}
								else {
									$forgetChanges = true;
									break; //editing not permitted
								}
							}
						}
						else {
							unset ($remote->id);
							if ($mask & MediabirdTopicAccessConstants::allowAddingCards) {
								$cardsToAdd[] = $remote;
							}
							else {
								$forgetChanges = true;
								break; //adding not permitted
							}
						}
						if ($remote->level == MediabirdConstants::levelMain) {
							if (array_search($remote->index, $count) !== false) {
								$forgetChanges = true;
								break; //new main level not valid
							}
							$count[] = $remote->index;
						}
					}

					if($forgetChanges == false && count($count)==0) {
						//do not allow topics with no cards on the main level
						$forgetChanges = true;
					}
					if($forgetChanges == false && count($levelCount)>count($count)) {
						//do not allow topics with empty steps
						// (i.e. topics where there are more steps than those that feature a main card)
						$forgetChanges = true;
					}
						
					if ($forgetChanges == false) {
						sort($count);
						for ($i = 0; $i < count($count); $i++) {
							if ($count[$i] != $i) {
								$forgetChanges = true;
								break; //new main level not valid
							}
							if (
							(isset($levelCount[$i][MediabirdConstants::levelAdvanced]) && $levelCount[$i][MediabirdConstants::levelAdvanced] > MediabirdConstants::maxAdvancedCount)
							||
							(isset($levelCount[$i][MediabirdConstants::levelIllustrative]) && $levelCount[$i][MediabirdConstants::levelIllustrative] > MediabirdConstants::maxIllustrativeCount)
							) {
								$forgetChanges = true;
								break; //to many cards on particular level
							}
						}
					}
					if ($forgetChanges == false) {
						foreach ($cards as $local) {
							$remote = null;
							foreach ($data->cards as $card) {
								if (property_exists($card, "id") && $local->id == $card->id) {
									$remote = $card;
									break;
								}
							}
							if ($remote == null) {
								//only remove card if $local is *not* in $lockedCards array --> otherwise $forgetChanges = true;
								if (($mask & MediabirdTopicAccessConstants::allowRemovingCards) && !in_array($local, $lockedCards)) {
									$cardsToRemove[] = $local;
								}
								else {
									$forgetChanges = true;
									break; //removing not permitted
								}
							}
						}
					}
					if ($forgetChanges == false) {
						//increase and return revision number
						$topic->revision++;
						$topicDB = (object)null;
						$topicDB->revision = $topic->revision;
						$topicDB->modified = $mediabirdDb->datetime(time());
						$topicDB->id = $topic->id;
						if(!$mediabirdDb->updateRecord(MediabirdConfig::tableName('Topic',true), $topicDB)){
							error_log("could not update topic revision");
							return null;
						}

						//add new cards
						foreach ($cardsToAdd as $card) {
							$cardDB = (object)null;
							$cardDB->topic = $id;
							$cardDB->index_num = $card->index;
							$cardDB->level_num = $card->level;
							$cardDB->user_id = $this->userId;
							$cardDB->created = $mediabirdDb->datetime(time());
							$cardDB->locked_time = $mediabirdDb->datetime(time());
							$cardDB->modified = $mediabirdDb->datetime(time());
							if($cardDB->id = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Card', true), $cardDB)){
								$remote = $card;
								if(property_exists($card, "content")) {
									$card->content= $this->purifyHTML($card->content);
								}
								if ($card = $this->updateCard($id, $cardDB->id, $card, $mask)) {
									if (!$ignoreCardContent && property_exists($remote, "markers") && is_array($markers = $this->updateMarkers($card->id, $remote->markers, array(), $mask, $this->userId))) {
										$card->markers = $markers;
									}
									$cards[] = $card;
								}
								else {
									error_log("error while inserting cards");
									return null;
								}
							}
							else {
								error_log($query);
								return null;
							}
						}

						//remove old cards
						foreach ($cardsToRemove as $card) {
							$cleanupFlashCardsQuery = "marker = ANY
								(SELECT id FROM ".MediabirdConfig::tableName('Marker')." WHERE card = $card->id)";
							$cleanupRelationsQuery = "marker_id = ANY
								(SELECT id FROM ".MediabirdConfig::tableName('Marker')." WHERE card = $card->id)";
							$cleanupMarkersQuery = "card = $card->id"; // was: "AND user_id=0"; ==> only delete global markers => trash function for personal ones
							$cleanupCardsQuery = "id=$card->id";
							if ($mediabirdDb->deleteRecords(MediabirdConfig::tableName('Flashcard',true),$cleanupFlashCardsQuery) === false
							|| $mediabirdDb->deleteRecords(MediabirdConfig::tableName('Relation',true),$cleanupRelationsQuery) === false
							|| $mediabirdDb->deleteRecords(MediabirdConfig::tableName('Marker',true),$cleanupMarkersQuery) === false
							|| $mediabirdDb->deleteRecords(MediabirdConfig::tableName('Card',true),$cleanupCardsQuery) === false) {
								error_log($cleanupFlashCardsQuery);
								return null;
							}
							array_splice($cards, array_search($card, $cards, true), 1);
						}

						//alter cards
						foreach ($cardsToUpdate as $card) {
							if(property_exists($card, "content")) {
								$card->content= $this->purifyHTML($card->content);
							}
							if ($this->updateCard($id, $card->id, $card, $mask, $card->local) != null) {
								if (!$ignoreCardContent && property_exists($card, "markers") && is_array($markers = $this->updateMarkers($card->local->id, $card->markers, array(), $mask, $this->userId))) {
									$card->local->markers = $markers;
								}
								if ($ignoreCardContent && !property_exists($card, "content")) {
									unset ($card->local->content);
								}
							}
							else
							{
								return null;
							}
						}
					}
					else {
						$topic->reverted=true;
					}
				}

				//update topic and category
				$topicDB = (object)null;
				$topicChanged = false;
				if (($mask & MediabirdTopicAccessConstants::allowRename) &&
				( isset ($data->title) || isset ($data->category))) {
					if ( isset ($data->title) && $data->title != $topic->title && strlen($data->title) > 0) {
						$topic->title = $data->title;
						$topicDB->title = $topic->title;
						$topicChanged = true;
					}
					if ( isset ($data->category) && $data->category != $topic->category) {
						$topic->category = $data->category;
						$topicDB->category = $topic->category;
						$topicChanged = true;
					}
				}
				if (($mask & MediabirdTopicAccessConstants::owner) && property_exists($data, "license")) {
					if ($topic->license != $data->license) {
						$topic->license = $data->license;
						$topicDB->license = $topic->license;
						$topicChanged = true;
					}
				}
				if ($topicChanged) {
					$topicDB->id = $id;
					if(!$mediabirdDb->updateRecord(MediabirdConfig::tableName('Topic', true), $topicDB)) {
						error_log("could not update topic ".print_r($topicDB,true));
						return null;
					}
				}


				//update prerequisites
				if (($mask & MediabirdTopicAccessConstants::allowRename) && property_exists($data, "prerequisites")) {
					$query = "topic=$id";
					if ($mediabirdDb->deleteRecords(MediabirdConfig::tableName('Prerequisite',true),$query) !== false) {
						foreach ($data->prerequisites as $prerequisite) {
							$prerequisiteDB = (object)null;
							$prerequisiteDB->topic = $id;
							$prerequisiteDB->title = $prerequisite->title;
							$prerequisiteDB->requiredTopic = property_exists($prerequisite, "topic")?$prerequisite->topic:"NULL";
							if(!$mediabirdDb->insertRecord(MediabirdConfig::tableName('Prerequisite', true), $prerequisiteDB)){
								return null;
							}
						}
					}
					else
					{
						return null;
					}
				}
			}

			//retrieve prerequisites
			$query = "SELECT requiredTopic,title FROM ".MediabirdConfig::tableName('Prerequisite')." WHERE topic=$id";
			if ($result = $mediabirdDb->getRecordSet($query)) {
				$topic->prerequisites = (array)null;
				while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
					$prerequisite = (object)null;
					$prerequisite->topic = intval($results['requiredTopic']);
					$prerequisite->title = $results['title'];
					$topic->prerequisites[] = $prerequisite;
				}
			}
			else {
				error_log($query);
				return null;
			}


			//retrieve rights!
			$topic->rights = (array)null;
			$query = "SELECT id,mask,group_id FROM ".MediabirdConfig::tableName('Right')." WHERE topic=$topic->id AND group_id=ANY
			(SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$this->userId AND active=1)";

			if (($resultRight = $mediabirdDb->getRecordSet($query))) {
				while ($resultsRight = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($resultRight))) {
					$right = (object)null;
					$right->mask = intval($resultsRight['mask']);
					$right->group = intval($resultsRight['group_id']);
					$right->id = intval($resultsRight['id']);
					$topic->rights[] = $right;
				}
			}
			if ( isset ($cards)) {
				$topic->cards = $cards;
			}

			return $topic;
		}
		else
		{
			error_log($query);
			return null;
		}
		return $topic;
	}




	/**
	 * Retrieves the current user name from the database
	 * @return String User name
	 * @param $userId int
	 */
	function getUserInfo($userId) {
		global $mediabirdDb;
		$query = "SELECT name,email FROM ".MediabirdConfig::tableName('User')." WHERE id=$userId";
		$result = $mediabirdDb->getRecordSet($query);
		if ($result) {
			$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
			$userInfo = (object)null;
			$userInfo->name = $results['name'];
			$userInfo->email = $results['email'];
			return $userInfo;
		}
		else
		{
			error_log($query);
			return null;
		}
	}

	/**
	 * Deletes a group from the database
	 * @return Bool True on success, false otherwise
	 * @param $groupId Integer Id of the group
	 */
	function deleteGroup($groupId) {
		global $mediabirdDb;
		$cleanMembershipsQuery = "group_id=$groupId";
		$cleanRightsQuery = "group_id=$groupId";
		$deleteGroupQuery = "id=$groupId";
		$result =
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Membership',true),$cleanMembershipsQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Right',true),$cleanRightsQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Group',true),$deleteGroupQuery);
		return $result;
	}

	function deleteMembership($userId, $groupId) {
		global $mediabirdDb;
		$cleanSharesQuery = "group_id = $groupId AND topic = ANY
		(SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE user_id=$userId)";
		$deleteMembershipQuery = "user_id=$userId AND group_id=$groupId";
		$result =
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Right',true),$cleanSharesQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Membership',true),$deleteMembershipQuery);
		return $result;
	}

	/**
	 * Completely deletes a topic from the database including all steps, cards and markers associated with it
	 * @return Bool True if sucessful, false otherwise
	 * @param $topicId int ID of the topic to delete
	 */
	function deleteTopic($topicId) {
		global $mediabirdDb;
		$cleanFlashCardsQuery = "marker = ANY
			(SELECT id FROM ".MediabirdConfig::tableName('Marker')." WHERE card = ANY
				(SELECT id FROM ".MediabirdConfig::tableName('Card')." WHERE topic=$topicId)
		)";
		$cleanRelationsQuery = "marker_id = ANY
			(SELECT id FROM ".MediabirdConfig::tableName('Marker')." WHERE card = ANY
				(SELECT id FROM ".MediabirdConfig::tableName('Card')." WHERE topic=$topicId)
		)";
		$cleanMarkersQuery = "card = ANY
			(SELECT id FROM ".MediabirdConfig::tableName('Card')." WHERE topic=$topicId)";
		$cleanCardsQuery = "topic=$topicId";
		$cleanPrerequisitesQuery = "topic=$topicId";
		$cleanRightsQuery = "topic=$topicId";
		$deleteTopicQuery = "id='$topicId'";
		$result =
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Flashcard',true),$cleanFlashCardsQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Relation',true),$cleanRelationsQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Marker',true),$cleanMarkersQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Card',true),$cleanCardsQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Prerequisite',true),$cleanPrerequisitesQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Right',true),$cleanRightsQuery) &&
		$mediabirdDb->deleteRecords(MediabirdConfig::tableName('Topic',true),$deleteTopicQuery);
		return $result;
	}

	/**
	 * Search for a public group covering a specific topic
	 * @return Number The public group with maximum rights with respect to the topics or -1 if no group found
	 * @param object $id Id of group, -1 if not found
	 */
	function getPublicTopicGroup($id) {
		global $mediabirdDb;
		$query = "SELECT group_id,mask FROM ".MediabirdConfig::tableName('Right')." WHERE mask > 0 AND topic=$id AND group_id=ANY
		(SELECT id FROM ".MediabirdConfig::tableName('Group')." WHERE access_num>=1)";
		$candidate = -1;
		if ($result = $mediabirdDb->getRecordSet($query)) {
			$tempMask = 0;
			while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
				$group = intval($results['group_id']);
				$mask = intval($results['mask']);
				if (! isset ($candidate)) {
					$candidate = $group;
					$tempMask = $mask;
				}
				else
				{
					for ($i = 0; $i < 11; $i++) {
						if (($mask & pow(2, $i)) < ($tempMask & pow(2, $i))) {
							break;
						}
					}
					if ($i == 11) {
						$candidate = $group;
						$tempMask = $mask;
					}
				}
			}
		}
		return $candidate;
	}

	/**
	 * Stores user's settings into the database
	 * @param String $settings
	 * @return void
	 */
	function storeSettings($settings) {
		global $mediabirdDb;
		if ($settingsJson = json_decode($settings)) {
			$settings = json_encode($settingsJson);
			$userDB = (object)null;
			$userDB->id = $this->userId;
			$userDB->settings = $settings;
			if(!$mediabirdDb->updateRecord(MediabirdConfig::tableName('User', true), $userDB)){
				error_log("could not update users settings ".print_r($userDB, true));
				return false;
			}
			else {
				return true;
			}
		}
	}

	/**
	 * Searches the database for a specific query and return topics and groups who match it
	 * @return Array First element: groups found, second: topics found. Null on error
	 * @param $needle String
	 */
	function searchDatabase($needle,$type) {
		global $mediabirdDb;
		//remove non-alpha
		$needle = preg_replace("/[^ 0-9a-zA-Zßäöüéèáà\-_]/i", '', $needle);
		$needle = preg_replace("/[ ]+/i", ' ', $needle);


		$exps=split(" ",$needle);

		//prepare result arrays
		$topics = (array)null;
		$groups = (array)null;
		$cards = (array)null;

		//determine accessible groups
		$accessibleGroups = array();

		$minAccess=3; //only search groups the user is member of

		if(strlen($needle)>0) {
			if($type == MediabirdSearchType::group) {
				//user is looking for a specific group
				$minAccess = 1;
			}
			else {
				//user is looking for specific content, only show easily accessible one
				$minAccess = 2;
			}
		}

		$query="SELECT id FROM ".MediabirdConfig::tableName('Group')." WHERE id IN (SELECT id FROM ".MediabirdConfig::tableName('Group')." WHERE access_num>=$minAccess) OR id IN (SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$this->userId AND active IN (1,3))";
		if ($result = $mediabirdDb->getRecordSet($query)) {
			//collect ids
			while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
				$accessibleGroups[] = intval($results['id']);
			}
		}
		else {
			error_log($query);
			return null;
		}
		
		if(($type & MediabirdSearchType::topic) || ($type & MediabirdSearchType::card)) {
			//find all topics which are accessible
			$query = "SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE user_id=$this->userId";

			if(count($accessibleGroups)>0) {
				$query .= " OR id=ANY
			(SELECT topic FROM ".MediabirdConfig::tableName('Right')." WHERE mask > 1
								AND group_id IN (".join(",",$accessibleGroups).")
							)";
			}

			$topicIds = (array)null;
			if ($result = $mediabirdDb->getRecordSet($query)) {
				//collect ids
				while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
					$topicIds[] = intval($results['id']);
				}
			}
			else {
				error_log($query);
				return null;
			}

			if (($type & MediabirdSearchType::topic) && count($topicIds) > 0) {
				//select those whose title match the query
				$query = "SELECT id,title,category FROM ".MediabirdConfig::tableName('Topic')." WHERE id IN (".join(",", $topicIds).") AND (
				".$this->__likeOr("title",$exps)." OR
				".$this->__likeOr("category",$exps)."
				)
				ORDER BY modified DESC";

				if ($result = $mediabirdDb->getRecordSet($query, null, 10)) {
					//collect ids
					while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
						$topic = (object)null;
						$topic->id = intval($results['id']);
						$topic->group = $this->getPublicTopicGroup($topic->id);
						$topic->title = $results['title'];
						$topic->category = $results['category'];
						$topics[] = $topic;
						array_splice($topicIds, array_search($topic->id, $topicIds), 1);
					}
				}
				else
				{
					error_log($query);
					return null;
				}
			}

			if (count($topicIds) > 0) {
				//from the remainder, select those whose cards match the query
				$query = "SELECT id,topic,title FROM ".MediabirdConfig::tableName('Card')."
				WHERE topic IN (".join(",", $topicIds).") AND (
				".$this->__likeOr("title",$exps)." OR
				".$this->__likeOr("content",$exps)."
				) 
				ORDER BY modified DESC";
				if ($result = $mediabirdDb->getRecordSet($query, null, 7)) {
					//reset the topic ids
					$topicIds = (array)null;
					//set up array for card ids
					while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
						if($type & MediabirdSearchType::topic){
							$topicId=intval($results['topic']);
							if(array_search($topicId,$topicIds)===false) {
								$topicIds[] = $topicId; //collect all topic ids
							}
						}
						if($type & MediabirdSearchType::card) {
							$card = (object)null;
							$card->title=$results['title'];
							$card->id=intval($results['id']);
							$cards[] = $card; //collect all card
						}
					}
				}
				else {
					error_log($query);
					return null;
				}

				if (count($topicIds) > 0) {
					//determine data
					$query = "SELECT id,title,category FROM ".MediabirdConfig::tableName('Topic')." WHERE id IN (".join(",", $topicIds).")";

					if ($result = $mediabirdDb->getRecordSet($query)) {
						//collect ids
						while ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
							$topic = (object)null;
							$topic->id = intval($results['id']);
							$topic->group = $this->getPublicTopicGroup($topic->id);
							$topic->title = $results['title'];
							$topic->category = $results['category'];
							$topics[] = $topic;
						}
					}
					else
					{
						error_log($query);
						return null;
					}
				}
			}
		}

		//find groups
		if (count($accessibleGroups)>0 && ($type & MediabirdSearchType::group)) {

			//find groups that have a matching member
			$select="group_id IN (".join(",",$accessibleGroups).")
					AND
					user_id IN 
						(SELECT id FROM ".MediabirdConfig::tableName('User')." WHERE 
						".$this->__likeOr("name",$exps)." OR
						".$this->__likeOr("email",$exps)."
						)";

			$groupIds = array();
			
			if($records = $mediabirdDb->getRecords(MediabirdConfig::tableName('Membership',true),$select,'created DESC','group_id','',10)) {
				foreach($records as $record) {
					if(in_array($record->group_id,$groupIds)==false) {
						array_push($groupIds,$record->group_id);
					}
				}
			}

			//find groups that match description/category/name
			$select = "id IN (".join(",",$accessibleGroups).") ";
			if(count($groupIds)>0) {
				$select.="AND
				id NOT IN (".join(",",$groupIds).") ";
			}
			$select.="AND
					(
					".$this->__likeOr("name",$exps)." OR
					".$this->__likeOr("description",$exps)." OR
					".$this->__likeOr("category",$exps)."
					)";

			$records = $mediabirdDb->getRecords(MediabirdConfig::tableName('Group',true),$select,'created DESC','id','',7);

			if($records) {
				foreach($records as $record) {
					if(in_array($record->id,$groupIds)==false) {
						array_push($groupIds,$record->id);
					}
				}
			}

			if(count($groupIds)>0) {
				if ($records = $mediabirdDb->getRecords(MediabirdConfig::tableName('Group',true),"id IN (".join(",",$groupIds).")",'created DESC',"id,name,category,description",'',15)) {
					foreach ($records as $record) {
						$group = (object)null;
						$group->id = $record->id;
						$group->title = $record->name;
						$group->category = $record->category;
						$group->description = $record->description;
						$groups[] = $group;
					}
				}
				else {
					error_log("couldn't find groups");
					return null;
				}
			}
		}
		return array ($groups, $topics, $cards);
	}

	/**
	 * Generates a LIKE clause for the given expressions
	 * @param string $field Field to generate LIKE clauses for
	 * @param array $exps Words to include in the LIKE clauses
	 * @return string
	 */
	function __likeOr($field,$exps) {
		global $mediabirdDb;
		$expq = array();
		foreach($exps as $exp) {
			$exp="$field LIKE '%".$mediabirdDb->escape($exp)."%'";
			array_push($expq,$exp);
		}
		return join(" OR ",$expq);
	}
}
?>
