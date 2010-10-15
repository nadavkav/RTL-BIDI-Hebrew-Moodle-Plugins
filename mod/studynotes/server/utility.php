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

//disable unwanted slashing
set_magic_quotes_runtime(0);

/**
 * Some constants for Mediabird
 * @author fabian
 */
class MediabirdConstants {
	const levelAdvanced = 3;
	const levelMain = 2;
	const levelIllustrative = 1;
	const groupLevelAdmin = 0xFFFF;
	const maxStepCount = 25;
	const maxAdvancedCount = 4;
	const maxIllustrativeCount = 4;
	const maxCardSize = 8192;
}

/**
 * Stores constants for topic access 
 * @author fabian
 *
 */
class MediabirdTopicAccessConstants {
	const noAccess = 0; //not shared at all (for easier database)
	const allowViewingCards = 1;// [read-only]
	const allowSearchingCards = 2; // [read-only]
	const allowCopyingCards = 4; // [read-only]
	const allowEditingContent = 8;// (and title) [allow write]
	const allowAlteringMarkers = 16; // [allow write]
	const allowAddingCards = 32; // [allow write]
	const allowRearrangingCards = 64; // [allow structure]
	const allowRemovingCards = 128; // [allow structure]
	const allowRename = 256; // [allow structure]
	const presetReadOnly = 7;
	const presetWriteAccess = 63;
	const presetFullAccess = 511;
	const owner = 1023;
}

/**
 * Stores constants of search type
 * @author fabian
 *
 */
class MediabirdSearchType {
	const group = 1;
	const topic = 2;
	const card = 4;
	const marker = 8;
}

/**
 * Stores group access constants
 * @author fabian
 *
 */
class GroupAccessConstants {
	const noAccess = 0;
	const publicView = 1; // enumerate group join on request ; look at members
	const publicJoin = 2;
}

class MediabirdUtility {
	/**
	 * Get a value from a $_POST/$_GET array without slashes
	 * @return String
	 * @param $str String
	 */
	static function getArgNoSlashes($str) {
		global $ignoreQuotes;
		if (!get_magic_quotes_gpc() || isset($ignoreQuotes)) {
			return $str;
		}
		else {
			return stripslashes($str);
		}
	}

	/**
	 * Recursively deletes a folder and its contents
	 * @return Bool True on success, false otherwise
	 * @param $folder String Path to folder
	 */
	static function deleteFolder($folder) {
		// Sanity check
		if (!file_exists($folder)) {
			return false;
		}

		// Simple delete for a file
		if (is_file($folder) || is_link($folder)) {
			return unlink($folder);
		}

		// Loop through the folder
		$dir = dir($folder);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue ;
			}

			// Recurse
			deleteFolder($folder.DIRECTORY_SEPARATOR.$entry);
		}
		// Clean up
		$dir->close();
		return rmdir($folder);
	}

	/**
	 * Determines if given user has access to the given topic or not
	 * @param $topicId
	 * @param $userId
	 * @return bool
	 */
	static function checkAccess($topicId, $userId) {
		global $mediabirdDb;
		if ($topic = $mediabirdDb->getRecord(MediabirdConfig::tableName("Topic",true), "id=$topicId")) {
			if ($topic->user_id == $userId) {
				return true;
			}
			else {
				$select = "topic=$topicId AND group_id IN
					(SELECT group_id FROM ".MediabirdConfig::tableName("Membership")." WHERE user_id=$userId AND active=1)";
		
				if ($masks = $mediabirdDb->getRecords(MediabirdConfig::tableName("Right",true), $select, '','mask')) {
					$bitmask = 0;
					foreach ($masks as $mask) {
						$bitmask |= intval($mask->mask);
					}
					return $bitmask >= MediabirdTopicAccessConstants::allowViewingCards;
				}
			}
		}
		return false;
	}

	/**
	 * Returns a new random file name
	 * @param $folder string Folder of which to detemine a free file name
	 * @return string File name that does not exist in the given folder
	 */
	static function getFreeFilename($folder) {
		if(!file_exists($folder)) {
			return null;
		}
		
		do {
			$name = substr(sha1(rand()), 0, 8);
		}
		while (file_exists($folder.$name));
		return $name;
	}

	/**
	 * Stores a reference to a file in the database
	 * @param $filepath string
	 * @param $type int
	 * @param $userId int
	 * @param $topicId int
	 * @return int Id of record or false on error
	 */
	static function recordFile($filepath,$type,$userId,$topicId) {
		global $mediabirdDb;

		$query = "SELECT id FROM ".MediabirdConfig::tableName('Upload')." WHERE 
		filename='".$mediabirdDb->escape($filepath)."' AND 
		type=$type AND 
		user_id=$userId AND
		topic_id=$topicId";

		if($result = $mediabirdDb->getRecordset($query,null,1)) {
			if($record = $mediabirdDb->fetchNextRecord($result)) {
				return $record->id;
			}
		}
		
		$filerecord = (object)null;
		$filerecord->user_id = $userId;
		$filerecord->topic_id = $topicId;
		$filerecord->type = $type;
		$filerecord->filename = $filepath;
		$filerecord->created = $mediabirdDb->datetime(time());
		$filerecord->modified = $mediabirdDb->datetime(time());

		if($filerecord->id = $mediabirdDb->insertRecord(MediabirdConfig::tableName('Upload',true),$filerecord)) {
			return $filerecord->id;
		}
		else {
			return false;
		}
	}

	/**
	 * Determines if there is enough space for a file of the given size taking the
	 * quota limit into account if there is any
	 * @return bool True if enough space and false otherwise
	 * @param $userId int User whose folder to use
	 * @param $quota int Quota of the user
	 * @param $fileSize int Size of file to be added to the user's folder
	 * @param $default bool Default answer if no quota set
	 */
	static function enoughQuota($userId, $quota, $fileSize, $default) {
		if ($quota == 0) {
			return $default;
		}
		//determine user folder size
		$folder = MediabirdConfig::$uploads_folder.$userId.DIRECTORY_SEPARATOR;
		if (file_exists($folder)) {
			$folderSize = MediabirdUtility::getFolderSize($folder);
		}
		else {
			$folderSize = 0;
		}
		return ($folderSize+$fileSize) <= ($quota);
	}

	/**
	 * Return quota left from current user
	 *
	 * @param unknown_type $userId
	 * @param unknown_type $quota
	 * @return unknown
	 */
	static function quotaLeft($userId, $quota) {
		global $mediabirdDb;

		if ($quota == 0) {
			return -1;
		}

		$folder = MediabirdConfig::$uploads_folder.$userId.DIRECTORY_SEPARATOR;
		if (file_exists($folder)) {
			$folderSize = MediabirdUtility::getFolderSize($folder);
		}
		else {
			$folderSize = 0;
		}

		return $quota-$folderSize;
	}

	/**
	 * Returns quota of current user
	 *
	 * @param int $id Id of user
	 * @param int $default Default value if not given by database
	 * @return int
	 */
	static function getUserQuota($id, $default = 0) {
		global $mediabirdDb;

		$query = "SELECT quota FROM ".MediabirdConfig::tableName('User')." WHERE id=$id";
		$result = $mediabirdDb->getRecordSet($query);
		if ($result && $mediabirdDb->recordLength($result) == 1) {
			$results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result));
			$quota = intval($results['quota']);
		}
		else {
			$quota = $default;
		}
		return $quota;
	}

	/**
	 * Recursively determines the size of a folder in bytes
	 * @return int Number of bytes in folder and subfolders
	 * @param string $folder Path to folder
	 */
	static function getFolderSize($folder) {
		// Sanity check
		if (!file_exists($folder)) {
			return 0;
		}

		// Simple delete for a file
		if (is_file($folder) || is_link($folder)) {
			return is_link($folder)?0:filesize($folder);
		}

		// Loop through the folder
		$size = 0;
		$dir = dir($folder);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue ;
			}

			// Recurse
			$size += MediabirdUtility::getFolderSize($folder.DIRECTORY_SEPARATOR.$entry);
		}
		// Clean up
		$dir->close();
		return $size;
	}

	/**
	 * Stores uploaded files locally
	 * @param array $key
	 * @param string $folder
	 * @param int $quotaLeft
	 * @param array $allowedMime
	 * @return array
	 */
	static function storeUpload($key, $folder, $quotaLeft = -1, $prefix = '', $allowedMime = array ('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-bmp')) {
		//init variables
		$destName = null;
		$error = null;

		if (! isset ($_FILES[$key])) {
			$error = 'nofileuploaded';
		}
		else {
			$uploadError = $_FILES["file"]["error"];
			if ($uploadError == UPLOAD_ERR_OK) {
				$size = $_FILES[$key]['size'];
				$filepath = $_FILES[$key]['tmp_name'];
				$name = $_FILES[$key]['name'];
				if ($size <= $quotaLeft || $quotaLeft == -1) {
					$mime = null;
					if (function_exists("mime_content_type")) {
						$mime = mime_content_type($filepath);
					}
					if ($mime == null || in_array($mime, $allowedMime)) {
						$extIndex = strrpos($name, ".");

						if ($extIndex === false) {
							$extIndex = strlen($name);
						}
						$num = 1;

						while (file_exists($file = $folder.substr($name, 0, $extIndex).($num > 1?" ($num)":"").substr($name, $extIndex))) {
							$num += 1;
						}

						if (move_uploaded_file($filepath, $file)) {
							chmod($file, 0644); //0 indicates octal notation

							if (substr($file, 0, strlen($prefix)) == $prefix) {
								$file = substr($file, strlen($prefix));
							}
							else {
								$file = $prefix.$file;
							}
							$destName = str_replace(DIRECTORY_SEPARATOR, '/', $file);
						}
						else {
							$error = "moveerror";
						}
					}
					else {
						$error = "illegaltype";
					}
				}
				else {
					$error = "notenoughquota";
				}
			}
			else if ($uploadError == UPLOAD_ERR_INI_SIZE || $uploadError == UPLOAD_ERR_FORM_SIZE) {
				$error = "toobig";
			}
			else if ($uploadError == UPLOAD_ERR_NO_FILE) {
				$error = "nofileuploaded";
			}
			else {
				$error = "other";
			}
		}
		return array ('error'=>$error, 'filename'=>$destName);
	}

	/**
	 * Generates HTML for upload response
	 * @param $destName string
	 * @param $error string
	 * @return string
	 */
	static function generateUploadHtml($destName, $error) {
		//begin document
		$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
		$html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
		$html .= '<head>'."\n";
		$html .= '<title>Mediabird eLearning - File Upload</title>'."\n";
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />'."\n";
		$html .= '</head>'."\n";
		$html .= '<body>'."\n";
		$html .= '<script type="text/javascript">'."\n";
		$html .= '//<![CDATA['."\n";

		//generate javascript
		$html .= 'if (parent.window.utility.globalCallback!==undefined) {';
		$html .= '	parent.window.utility.globalCallback(';
		if ( isset ($destName)) {
			$html .= '"'.str_replace(DIRECTORY_SEPARATOR, "/", $destName).'"';
		}
		else {
			$html .= 'null';
		}
		$html .= ',';
		if ( isset ($error)) {
			$html .= '"'.$error.'"';
		}
		else {
			$html .= 'null';
		}
		$html .= ');'."\n";
		$html .= '}'."\n";

		//end script tag
		$html .= ' //]]>'."\n";
		$html .= ' </script>'."\n";
		$html .= ' </body>'."\n";
		$html .= '</html>'."\n";

		return $html;
	}

	/**
	 * Handles upload requests
	 * @param array $key Key in the $_FILE array 
	 * @param string $folder Folder to store files in
	 * @param int $quotaLeft Quota left to store the file
	 * @param array $allowedMime Allowed mime times
	 * @return string
	 */
	static function handleUpload($key, $folder, $quotaLeft = -1, $prefix = '', $allowedMime = array ('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-bmp')) {

		$info = MediabirdUtility::storeUpload($key, $folder, $quotaLeft, $prefix, $allowedMime);
		$error = $info['error'];
		$destName = $info['filename'];

		return self::generateUploadHtml($destName, $error);
	}

	/**
	 * Reads a file in the response stream
	 * @param string $name 
	 * @param int $type 
	 */
	static function readUpload($name, $type) {
		$mime = null;

		$path = MediabirdConfig::$uploads_folder.$name;

		if (function_exists("mime_content_type")) {
			$mime = mime_content_type($path);
		}
		if (!$mime) {
			if ($type == 0) {
				$mime = "image";
			}
		}
		header("Content-type: $mime;");

		readfile($path);
	}

	/**
	 * Validates an email address
	 * @return bool True if valid, false otherwise
	 * @param $email string Email address to validate
	 */
	static function checkEmail($email) {
		if (!preg_match("/^[a-zA-Z0-9]+[a-zA-Z0-9\._-]*@[a-zA-Z0-9_-]+[a-zA-Z0-9\._-]+$/", $email)) {
			return false;
		}
		return true;
	}

	/**
	 * Determines the desired content language of the user
	 * @param array $allowed_languages 
	 * @param string $default_language 
	 * @param bool $strict_mode 
	 * @return string
	 */
	static function determineBrowserLanguage($allowed_languages, $default_language, $strict_mode = false) {
		$lang_variable = null;
		if (! isset ($_SERVER['HTTP_ACCEPT_LANGUAGE']) || empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return $default_language;
		}
		else
		{
			$lang_variable = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}

		$accepted_languages = preg_split('/,\s*/', $lang_variable);

		// set default
		$current_lang = $default_language;
		$current_q = 0;

		// work through all specified languages
		foreach ($accepted_languages as $accepted_language) {
			$res = preg_match('/^([a-z]{1,8}(?:-[a-z]{1,8})*)'.
			'(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i', $accepted_language, $matches);
			if (!$res) {
				continue ;
			}

			$lang_code = explode('-', $matches[1]);

			// was quality given?
			if ( isset ($matches[2])) {
				// consider quality
				$lang_quality = (float)$matches[2];
			}
			else
			{
				// not given, assume 1.0
				$lang_quality = 1.0;
			}

			// work through all languages
			while (count($lang_code)) {
				// check if language wanted
				if (in_array(strtolower(join('-', $lang_code)), $allowed_languages)) {
					// check if quality high enough
					if ($lang_quality > $current_q) {
						// use this language
						$current_lang = strtolower(join('-', $lang_code));
						$current_q = $lang_quality;
						// exit while loop
						break;
					}
				}
				if ($strict_mode) {
					// exit while loop
					break;
				}
				array_pop($lang_code);
			}
		}

		// return language
		return $current_lang;
	}

	static private $rootUrl;
	static private $baseUrl;
	/**
	 * Helper function for makeLinksAbsolute
	 */
	static private function replaceUrl($matches) {

		$path = $matches[3];

		if (preg_match("%(:.*/)%mi", $path) == 1) {
			return $matches[0];
		}

		$i = (substr($path, 0, 1) == "\"" || substr($path, 0, 1) == "'")?1:0;

		$path = substr($path, 0, $i).(substr($path, $i, 1) == "/"?self::$rootUrl:self::$baseUrl).substr($path, 1);

		return $matches[1].$matches[2]."=".$path;
	}

	/**
	 * Makes absolute links relative 
	 * @param string $html HTML containing absolute links
	 * @return string
	 */
	static function makeLinksAbsolute($html) {
		//make relative paths in relevant attributes absolute. list comes from w3c
		$attrList = "href|cite|background|codebase|action|usemapcite|src|data|classid|src|href|profile|longdesc";

		$search = "%(<[^>]+)($attrList)=(\"[^\"]+\"|'[^']+'|[^\"' >]+[ >])%mi";

		$html = preg_replace_callback($search, "MediabirdUtility::replaceUrl", $html);

		return $html;
	}

	/**
	 * Retrieves a remote url using CURL making absolute links relative
	 * @param string $urlToLoad
	 * @return string HTML
	 */
	static function loadUrl($urlToLoad) {

		// create a new cURL resource
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $urlToLoad);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		//set up proxy if specified
		if(isset(MediabirdConfig::$proxy_address) && strlen(MediabirdConfig::$proxy_address)>0) {
			curl_setopt($ch,CURLOPT_PROXY,MediabirdConfig::$proxy_address.":".MediabirdConfig::$proxy_port);
		}
		
		//execute
		$html = curl_exec($ch);

		//check for error
		if ($html === false) {
			return null;
		}

		// grab URL and pass it to the browser
		$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

		// grab content type and pass it to the browser
		$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		if ( isset ($type)) {
			header('Content-Type: '.$type);
		}

		//parse the url
		$path = parse_url($finalUrl);

		//remove last bit of path
		if ( isset ($path['path'])) {
			$li = strrpos($path['path'], "/");
			if ($li > -1) {
				$path['path'] = substr($path['path'], 0, $li+1);
			}
		}
		//forget about query and fragment
		unset ($path['query']);
		unset ($path['fragment']);

		//construct base url
		self::$baseUrl = self::__glueUrl($path);

		//construct the root url
		unset ($path['path']);
		self::$rootUrl = self::__glueUrl($path);


		//correct relative URIs
		$html = self::makeLinksAbsolute($html);

		// close cURL resource, and free up system resources
		curl_close($ch);

		//echo modified HTML
		return $html;
	}
	/**
	 * Glues together a URL parsed by parse_url
	 * @param array $parsed Array of element given by a call to parse_url
	 * @return string
	 */
	private static function __glueUrl($parsed) {
		if (!is_array($parsed)) return false;

		$uri = isset ($parsed['scheme'])?$parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto')?'':'//'):'';
		$uri .= isset ($parsed['user'])?$parsed['user'].($parsed['pass']?':'.$parsed['pass']:'').'@':'';
		$uri .= isset ($parsed['host'])?$parsed['host']:'';
		$uri .= isset ($parsed['port'])?':'.$parsed['port']:'';
		$uri .= isset ($parsed['path'])?$parsed['path']:'';
		$uri .= isset ($parsed['query'])?'?'.$parsed['query']:'';
		$uri .= isset ($parsed['fragment'])?'#'.$parsed['fragment']:'';
		return $uri;
	}

	/**
	 * Sends the JSON response header
	 */
	public static function jsonHeader() {
		header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate;");
		header("Pragma: no-cache;");
		header('Content-Type: application/json;');
	}


}

/**
 * Serves as prototype class for database interaction
 * @author fabian
 *
 */
abstract class MediabirdDbo {
	/**
	 * Connects database
	 */
	abstract function connect();
	/**
	 * Disconnects database
	 */
	abstract function disconnect();
	/**
	 * Retrieves a recordset that can be used in fetchNextRecord
	 * @param string $sql
	 * @param string $limit_from
	 * @param string $limit 
	 * @return stdClass
	 */
	abstract function getRecordset($sql,$limit_from='',$limit='');
	/**
	 * Determines the record count of a record set
	 * @param stdClass $result Record set given by getRecordSet 
	 * @return int
	 */
	abstract function recordLength($result);
	/**
	 * Fetches the next unread record from a recordset
	 * @param stdClass $result Recordset retrieved using getRecordSet
	 * @return stdClass record featuring the corresponding row's columns as properties
	 */
	abstract function fetchNextRecord($result);
	/**
	 * Retrieves as single record from the database
	 * @param string $table Table name without prefix
	 * @param string $select Where clause of query
	 * @return stdClass
	 */
	abstract function getRecord($table,$select);
	/**
	 * Retrieves 1 or many records from the database
	 * @param $table Table name without prefix
	 * @param $select Where clause of query
	 * @param $sort Sort clause
	 * @param $fields Fields to select
	 * @param $limitfrom Page offset
	 * @param $limitnum Record limit
	 * @return stdClass[] Array of records or null if none found
	 */
	abstract function getRecords($table, $select='', $sort='', $fields='*', $limitfrom='', $limitnum='');
	/**
	 * Deletes records on the base of a where clause
	 * @param string $table Table name where to delete records
	 * @param string $select Where clause
	 * @return bool True on success, false otherwise
	 */
	abstract function deleteRecords($table,$select);
	/**
	 * Converts a record into an associative array
	 * @param stdClass $obj Record
	 * @return array
	 */
	abstract function recordToArray($obj);
	/**
	 * Escapes a string such that it can be safely stored in the database
	 * @param string $str Raw value
	 * @return string Escaped value
	 */
	abstract function escape($str);
	/**
	 * Updates a record specified by the id property of $record in the given table $table
	 * @param string $table Name of table without prefix
	 * @param stdClass $record Object featuring properties of the record to be updated
	 * @return bool True on success, false otherwise
	 */
	abstract function updateRecord($table,$record);
	/**
	 * Inserts a new record into the database
	 * @param string $table Table name without prefix
	 * @param stdClass $dataobject Record to be inserted
	 * @param bool $returnid True to return id of inserted record
	 * @param string $primarykey Not used
	 * @return int Id of inserted record if $returnId set to true
	 */
	abstract function insertRecord($table, $dataobject, $returnid = true, $primarykey = 'id');
	/**
	 * Converts a date value from the database into a time stamp
	 * @param string $date
	 * @return int
	 */
	abstract function timestamp($date);
	/**
	 * Converts a time stamp into database date format
	 * @param int $time
	 * @return string
	 */
	abstract function datetime($time);
}

?>
