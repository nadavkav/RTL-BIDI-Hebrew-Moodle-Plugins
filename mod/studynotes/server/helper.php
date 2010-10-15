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
 * Provides functions to integrate Mediabird into a web site
 */
class MediabirdHtmlHelper {
	const langEnglish = 0;
	const langGerman = 1;
	const langSpanish = 2;
	
	public $defaultOptions;

	/**
	 * Constructor
	 */
	function __construct() {
		$this->defaultOptions = array (
			'language'=>MediabirdHtmlHelper::langEnglish,
			'markerPlugins'=> array (
				'client.markers.QuestionMarker',
				'client.markers.RepetitionMarker',
				'client.markers.ReferenceMarker'
				//, 'client.markers.TemplateMarker'
			),
			'displayPlugins'=> array (
				'client.pageplugins.displayplugins.Image',
				'client.pageplugins.displayplugins.Link',
				'client.pageplugins.displayplugins.Table',
				'client.pageplugins.displayplugins.HTML',
				'client.pageplugins.displayplugins.LaTeXmage'
				/*,'client.pageplugins.displayplugins.Collapsible' */
				/*, 'client.pageplugins.displayplugins.PluginTemplate' */
			),
			'loadLogon'=>true,
			'serverPath'=>'server'.DIRECTORY_SEPARATOR,
			'imagePath'=>'images'.DIRECTORY_SEPARATOR,
			'cssPath'=>'css'.DIRECTORY_SEPARATOR.'style.css',
			'logoPath'=>'css'.DIRECTORY_SEPARATOR.'logo.jpg',
			'javascriptPath'=>'js'.DIRECTORY_SEPARATOR.'client.js',
			'jQueryPath'=>'js'.DIRECTORY_SEPARATOR.'jquery.js',
			'version'=>'0.5.6',
			'title'=>'Mediabird Web2.0-Learning',
			'containerId'=>'mediabirdContainer',
			'dummyPath'=>'dummy.php',
			'loadPath'=>'load.php?url=',
			'uploadPath'=>'upload.php',
			'feedbackPath'=>'internal',
			'prefixData'=>false
		);
	}

	/**
	 *
	 * @var stdClass
	 */
	public $user = null;
	/**
	 * Checks if there is a session already and determines the logged on user
	 * @return object
	 * @param MediabirdAuthManager $auth
	 */
	function loadUser($auth) {
		global $mediabirdDb;

		$query = "SELECT name,settings FROM ".MediabirdConfig::tableName('User')." WHERE id=$auth->userId";

		if (($results = $mediabirdDb->getRecordset($query)) && ($record = $mediabirdDb->fetchNextRecord($results))) {
			//create user object
			$this->user = array (
				'name'=>$record->name,
				'settings'=>$record->settings,
				'id'=>$auth->userId
			);
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Returns the body layout for the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function bodyLayout($options = array ()) {
		$options = array_merge($this->defaultOptions, $options);

		$ret = '<div class="container">';
		$ret .= '	<div class="headerbar"';
		if ( isset ($options['headerId'])) {
			$ret .= ' id="'.$options['headerId'].'"';
		}
		$ret .= '>';
		$ret .= '	<a href="" class="vendorlogo"><img src="'.$options['logoPath'].'"/></a>';
		$ret .= '	</div>';
		$ret .= '	<div id="'.$options['containerId'].'">';
		$ret .= '	</div>';
		$ret .= '	<div class="subcontainer">';
		$ret .= '		<p>';
		$ret .= '			Version '.$options['version'].' − &copy; 2008-2009 <a href="http://www.mediabird.net/" target="_blank" title="Mediabird Homepage">Mediabird</a>';
		$ret .= '		</p>';
		$ret .= '	</div>';
		$ret .= '</div>';
		return $ret;
	}

	/**
	 * Creates the main/launch function for the Javascript core of Mediabird
	 */
	function _mainBodyScript($options) {
		//JS to set image path
		$script = "config.imagePath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["imagePath"])."\";\n";

		//JS to set dummy php path
		$script .= "config.dummyPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["dummyPath"])."\";\n";

		//JS to set URL loader php path
		$script .= "config.loadUrlPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["loadPath"])."\";\n";

		//JS to set uploader php path
		$script .= "config.uploadPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["uploadPath"])."\";\n";

		//JS to set feedback php path
		$script .= "config.feedbackPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["feedbackPath"])."\";\n";

		//JS to set reference
		$script .= "config.reference = {};\n";
		
		//JS to specify reference destination
		if ( isset ($options['linkTarget'])) {
			$script .= "config.reference.target = \"".$options["linkTarget"]."\";\n";
		}
		
		//JS to set link url path
		if ( isset ($options['linkUrl']) && isset ($options['linkTitle'])) {
			$script .= "config.reference.link = \"".$options["linkUrl"]."\";\n";
			$script .= "config.reference.title = \"".str_replace("\"", "\\\"", $options["linkTitle"])."\";\n";
		}
		else if (isset ($options['autoLink']) && $options['autoLink']) {
			$script .= "config.reference.auto = true;\n";
		}
		
		//JS to allow internal link detection
		if ( isset ($options['linkPrefix'])) {
			$script .= "config.linkPrefix = \"".$options["linkPrefix"]."\";\n";
		}

		//JS to set SHIFT_RESEARCH_LEFT
		if ( isset ($options['SHIFT_RESEARCH_LEFT'])) {
			$script .= "config.SHIFT_RESEARCH_LEFT = ".$options['SHIFT_RESEARCH_LEFT'].";\n";
		}

		//JS to set SHIFT_RESEARCH_RIGHT
		if ( isset ($options['SHIFT_RESEARCH_RIGHT'])) {
			$script .= "config.SHIFT_RESEARCH_RIGHT = ".$options['SHIFT_RESEARCH_RIGHT'].";\n";
		}

		//JS to set SHIFT_RESEARCH_RIGHT_WIDE
		if ( isset ($options['SHIFT_RESEARCH_RIGHT_WIDE'])) {
			$script .= "config.SHIFT_RESEARCH_RIGHT_WIDE = ".$options['SHIFT_RESEARCH_RIGHT_WIDE'].";\n";
		}

		//JS to set RESEARCH_PANEL_WIDTH
		if ( isset ($options['RESEARCH_PANEL_WIDTH'])) {
			$script .= "config.RESEARCH_PANEL_WIDTH = ".$options['RESEARCH_PANEL_WIDTH'].";\n";
		}

		//JS to enable POST prefixing
		if ($options["prefixData"]) {
			$script .= "config.prefixData = true;\n";
		}

		//JS to set full location to switch to full mode from overlay
		if ( isset ($options["reduceFeatureSet"]) && $options["reduceFeatureSet"]) {
			$script .= "config.reduceFeatureSet = true;\n";
		}

		//JS to set full location to switch to full mode from overlay
		if ( isset ($options["fullLocationFromOverlay"])) {
			$script .= "config.fullLocationFromOverlay = \"".$options["fullLocationFromOverlay"]."\";\n";
		}

		//JS for loading english resource strings
		$script .= "lang = {};\n\$.extend(lang, client.lang);\n";
		if ($options["language"] == MediabirdHtmlHelper::langGerman) {
			//JS for loading german resource strings
			$script .= "\$.extend(lang, client.lang.de);\n";
		}
		else if ($options["language"] == MediabirdHtmlHelper::langSpanish) {
			//JS for loading spanish resource strings
			$script .= "\$.extend(lang, client.lang.es);\n";
		}
		
		//JS for server interface
		if ( isset ($options["addArgs"])) {
			$script .= "config.customArgs = ".$options["addArgs"].";\n";
		}

		$script .= "var server = new client.ServerInterface( {\nserverPath: \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["serverPath"])."\"";
		if ( isset ($options["furtherArgs"])) {
			$script .= ",\n".$options["furtherArgs"];
		}
		$script .= "\n});\n";

		$markerPlugins = $options["markerPlugins"];
		foreach ($markerPlugins as $markerPlugin) {
			$script .= "server.addMarkerPlugin(new ".$markerPlugin."());\n";
		}

		$displayPlugins = $options["displayPlugins"];
		foreach ($displayPlugins as $displayPlugin) {
			$script .= "server.addDisplayPlugin(new ".$displayPlugin."());\n";
		}

		//JS to create the page object
		if (! isset ($options['containerObject'])) {
			$script .= 'var page = new client.Page($("#'.$options['containerId'].'"), server';
		}
		else {
			$script .= 'var page = new client.Page('.$options['containerObject'].', server';
		}

		if ( isset ($options['headerId'])) {
			$script .= ',$("#'.$options['headerId'].'")';
		}

		$script .= ');
			';

		if ( isset ($options["loadCard"])) {
			$script .= "config.customLoadCard = ".$options["loadCard"].";\n";
		}

		if ($options["loadLogon"]) {
			//JS to load the logon form component
			$script .= "var plugin;\nplugin=new client.pageplugins.LogonForm();\npage.loadPagePlugin(plugin);\n";
		}
		else {
			//JS to load the main component
			$script .= "var plugin;\nplugin=new client.pageplugins.MainView();\n";
		}

		if ( isset ($options["user"]) || isset ($this->user)) {
			if ( isset ($options["user"])) {
				$user = $options["user"];
			}
			else {
				$user = $this->user;
			}
			$script .= "var user = new client.data.User();\n";
			$script .= "user.name=\"".str_replace("\"", "\\\"", $user["name"])."\";\n";
			$script .= "user.id=".$user["id"].";\n";
			if ( isset ($user["settings"]) && ($settings = json_decode($user["settings"]))) {
				$script .= "user.settings=".json_encode($settings).";\n";
			}
			else {
				$script .= "user.settings={};\n";
			}
			$script .= "server.resumeSession(user);\n";
			if ($options["loadLogon"]) {
				$script .= "plugin.gotoMainView();\n";
			}
			else {
				$script .= "page.loadPagePlugin(plugin);\n";
			}
		}
		else {
			if ($options["loadLogon"]) {
				$script .= "plugin.loadLogon();\n";
			}
			else {
				$script .= "page.loadPagePlugin(plugin);\n";
			}
		}

		return $script;
	}

	/**
	 * Returns the body script for the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function bodyScript($options = array ()) {
		$options = array_merge($this->defaultOptions, $options);

		$script = "<script type=\"text/javascript\">\n//<![CDATA[\n";

		//JS main function
		$script .= "function _main(){\n";

		$mainscript = $this->_mainBodyScript($options);

		$script .= $mainscript;



		//JS end of main function
		$script .= "}\n";

		//JS to call main function
		$script .= "_main();\n";

		//JS end of script
		$script .= "//]]>\n</script>";

		return $script;
	}

	/**
	 * Returns the body script for an overlay displaying the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function bodyScriptOverlay($options = array ()) {
		$options = array_merge($this->defaultOptions, $options, array ('containerObject'=>'overlayContainer'));

		$script .= "<script type=\"text/javascript\">\n//<![CDATA[\n";

		//JS to create overlay link handler
		$script .= 'var link = $("#'.$options['linkId'].'");
		link.one("click",function() {
		var overlayContainer = $(document.createElement("div")).addClass("mediabird-overlay").appendTo(this.ownerDocument.body);
		'.$this->_mainBodyScript($options).'
		var overlay = new client.integration.Overlay();
		overlay.load(overlayContainer);
		});
		';

		//JS end of script
		$script .= "//]]>\n</script>";

		return $script;
	}


	/**
	 * Generates the complete style cache file
	 * Not applicable in debug mode
	 * @param array $cssFiles
	 * @param string $destination
	 * @param string $cssPrefix
	 */
	function generateCSSCache($cssFiles, $destination, $cssPrefix) {
		if ($file = fopen($destination, "w")) {
			foreach ($cssFiles as $source) {
				$content = file_get_contents($source)."\n";

				$content = preg_replace("%url\('[^']*/([^/]+)'\)%",
				"url('".$cssPrefix."\\1')", $content);

				fwrite($file, $content);
			}
			fclose($file);
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Activates the javascript minifier for the javascript release build
	 * Not applicable in debug mode
	 * @return
	 * @param object $options[optional]
	 */
	function enableMinifier($options = array ()) {
		$options = array_merge($this->defaultOptions, $options);
		include_once ($options['minifierPath']);
	}

	/**
	 * Generates the javascript release file
	 * @param array $javascriptFiles
	 * @param string $destination
	 */
	function generateJavascriptCache($javascriptFiles, $destination) {
		if ($file = fopen($destination, "w")) {
			foreach ($javascriptFiles as $source) {
				$content = file_get_contents($source)."\n";
				if (class_exists("JSMin", false) == true) {
					$content = JSMin::minify($content);
				}
				fwrite($file, $content);
			}
			fclose($file);
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Loads the default file list into the default options
	 */
	function loadDefaultFileArrays() {
		$this->defaultOptions['cssFiles'] = array (
		'client'.DIRECTORY_SEPARATOR.'style.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'NoteDisplay.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'CardTrainer.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'LogonForm.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'MainView.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Community.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Organization.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Home.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'MapView.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'ProgressBox.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'FilterSearch.css',
		//'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'DatePicker.css',
		//'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'WidgetTemplate.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'TreeView.css'
		);
		$this->defaultOptions['javascriptFiles'] = array (
		"utility".DIRECTORY_SEPARATOR."json.js",
		"utility".DIRECTORY_SEPARATOR."utility.js",
		"utility".DIRECTORY_SEPARATOR."utility.ui.js",
		"utility".DIRECTORY_SEPARATOR."utility.mozillaNode.js",
		"utility".DIRECTORY_SEPARATOR."jquery.selection.js",
		"utility".DIRECTORY_SEPARATOR."jquery.balloon.js",
		"utility".DIRECTORY_SEPARATOR."jquery.droppables.js",
		"client".DIRECTORY_SEPARATOR."Namespaces.js",
		"client".DIRECTORY_SEPARATOR."lang".DIRECTORY_SEPARATOR."lang.js",
		"client".DIRECTORY_SEPARATOR."lang".DIRECTORY_SEPARATOR."lang.de.js",
		"client".DIRECTORY_SEPARATOR."lang".DIRECTORY_SEPARATOR."lang.es.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Topic.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Step.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Card.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."User.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Group.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Right.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Relation.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."LinkRelation.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Member.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."FlashCard.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."FlashCardBox.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."FlashCardContents.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Notification.js",
		"client".DIRECTORY_SEPARATOR."ServerInterface.js",
		"client".DIRECTORY_SEPARATOR."Page.js",
		"client".DIRECTORY_SEPARATOR."Widget.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."Editor.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."MapView.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."TreeView.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."TreeViewItem.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."ProgressBox.js",
		//"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."WidgetTemplate.js",
		//"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."Organizer.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."FilterSearch.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."SearchInterface.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."MediabirdSearchProvider.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."WikipediaSearchProvider.js",
		"client".DIRECTORY_SEPARATOR."PagePlugin.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."LogonForm.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."MainView.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Community.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Home.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Organization.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."NoteDisplay.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Search.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."NoteDisplayInterface.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."NoteDisplayPlugin.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."Image.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."Link.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."Table.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."HTML.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."LaTeXmage.js",
		//"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."Collapsible.js",
		//"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."PluginTemplate.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."CardTrainer.js",
		"client".DIRECTORY_SEPARATOR."Marker.js",
		//"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Importance.js",
		//"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Template.js",
		"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Question.js",
		//"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Translation.js",
		"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Repetition.js",
		"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Reference.js"
		);
	}

	/**
	 * Returns the head tag for the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function headTag($options = array ()) {
		if (( isset ($this->defaultOptions['debug']) && $this->defaultOptions['debug']) || ( isset ($options['debug']) && $options['debug'])) {
			$this->loadDefaultFileArrays();
		}

		$options = array_merge($this->defaultOptions, $options);

		if (! isset ($options['noMeta'])) {
			$script = '<title>'.htmlentities($options['title']).'</title>'."\n";
			$script .= '<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8"/>'."\n";
			$script .= '<link type="text/css" rel="stylesheet" href="css/default.css"/>'."\n";

			if ($options['debug']) {
				$files = $options['cssFiles'];
				foreach ($files as $file) {
					$script .= '<link type="text/css" rel="stylesheet" href="'.str_replace(DIRECTORY_SEPARATOR, '/', $file).'"/>'."\n";
				}
			}
			else {
				$script .= '<link type="text/css" rel="stylesheet" href="'.str_replace(DIRECTORY_SEPARATOR, '/', $options['cssPath']).'"/>'."\n";
			}
		}

		if (! isset ($options['noScripts'])) {
			if ( isset ($options['jQueryPath'])) {
				$script .= '<script type="text/javascript" src="'.str_replace(DIRECTORY_SEPARATOR, '/', $options['jQueryPath']).'"></script>'."\n";
			}
			if ($options['debug']) {
				$files = $options['javascriptFiles'];
				foreach ($files as $file) {
					$script .= '<script type="text/javascript" src="'.str_replace(DIRECTORY_SEPARATOR, '/', $file).'"></script>'."\n";
				}
			}
			else {
				$script .= '<script type="text/javascript" src="'.str_replace(DIRECTORY_SEPARATOR, '/', $options['javascriptPath']).'"></script>';
			}
		}
		return $script;
	}

	/**
	 * Registers a user in the database
	 */
	function registerUser($name, $active = 1, $email = null) {
		global $mediabirdDb;

		$ret = null;

		$user = (object)null;
		$user->name = $name;
		$user->last_login = $mediabirdDb->datetime(time());
		$user->created = $mediabirdDb->datetime(time());
		
		$user->active = $active;
		if($email!=null) {
			$user->email = $email;
		}

		if ($id=$mediabirdDb->insertRecord(MediabirdConfig::tableName('User',true),$user)) {
			$ret = intval($id);
		}
		else {
			error_log("could not register user");
		}
		return $ret;
	}

	/**
	 * Updates a user in the database
	 */
	function updateUser($id, $name, $active = 1, $email = null) {
		global $mediabirdDb;

		$ret = null;

		$user = (object)null;
		$user->id = $id;
		$user->name=$name;
		$user->last_login=$mediabirdDb->datetime(time());
		$user->active=$active;
		if ($email != null) {
			$user->email=$email;
		}
		if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('User',true),$user)) {
			return $id;
		}
		else {
			error_log("update user failed");
		}
		return $ret;
	}

	/**
	 * Links a given external user with a Mediabird user if not already linked
	 * and returns the internal id of that user
	 * @param int $externalId Id of external user
	 * @param string $system Name of external system, such as "facebook"
	 * @param string $name Name of external user
	 * @param int $active State of new user, 1 for active, 0 for disabled
	 * @param string $email Email address of user
	 * @return int Id of internal user
	 */
	function linkUser($externalId, $system, $name, $active = 1, $email = null) {
		global $mediabirdDb;

		$ret = null;
		$query = "SELECT internal_id FROM ".MediabirdConfig::tableName("AccountLink")." WHERE external_id=$externalId AND system='".$mediabirdDb->escape($system)."'";

		if ($result = $mediabirdDb->getRecordSet($query)) {
			if ($mediabirdDb->recordLength($result) == 1) {
				if ($results = $mediabirdDb->recordToArray($mediabirdDb->fetchNextRecord($result))) {
					$internalId = intval($results['internal_id']);
					$this->updateUser($internalId, $name, $active, $email);
					$ret = $internalId;
				}
			}
			else {
				if ($internalId = $this->registerUser($name, $active, $email)) {
					$user = (object) null;
					$user->system = $system;
					$user->external_id = $externalId;
					$user->internal_id = $internalId;
					if (!$mediabirdDb->insertRecord(MediabirdConfig::tableName("AccountLink",true),$user)) {
						error_log("could not link user");
					}
				}
			}
		}
		else {
			error_log($query);
		}

		return $ret;
	}

	/**
	 * Determine note sheets that are related to a given URL
	 * Technically speaking, this functions finds all note sheets that feature a reference marker pointing at the given location
	 * Sorts results by modification date, descending
	 * @param $url Location
	 * @param $userId Id of the user whose notes are to be determined
	 * @return string[]
	 */
	function findRelatedNotes($url, $userId) {
		global $mediabirdDb;

		//determine accessible groups
		$accessibleGroups = array (0); //leave item 0 to avoid empty array

		//do not include public groups, just friends!
		$query = "SELECT id FROM ".MediabirdConfig::tableName('Group')." WHERE id IN (SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$userId AND active=1)";
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

		//find all topics which are accessible
		$query = "SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE user_id=$userId";
		$query .= " OR id=ANY
			(SELECT topic FROM ".MediabirdConfig::tableName('Right')." WHERE mask > 1
				AND group_id IN (".join(",", $accessibleGroups).")
			)";

		$topicIds = (array)null;
		if ($result = $mediabirdDb->getRecordSet($query)) {
			//collect ids
			while ($results = $mediabirdDb->fetchNextRecord($result)) {
				$topicIds[] = intval($results->id);
			}
		}
		else {
			error_log($query);
			return null;
		}

		if (count($topicIds) > 0) {
			$query = "SELECT id,user_id FROM ".MediabirdConfig::tableName("Card")." WHERE id IN
			(SELECT card FROM ".MediabirdConfig::tableName("Marker")." WHERE id IN
				(SELECT marker_id FROM ".MediabirdConfig::tableName("Relation")." WHERE 
					(shared=1 OR user_id IN (0,$userId)) AND relation_id IN 
					(SELECT id FROM ".MediabirdConfig::tableName("RelationLink")." WHERE link='".$mediabirdDb->escape($url)."')
				)
			) AND topic IN (".join(",", $topicIds).")
			ORDER BY modified DESC";

			$ownCardIds = array ();
			$friendCardIds = array ();
			if ($result = $mediabirdDb->getRecordSet($query)) {
				while ($results = $mediabirdDb->fetchNextRecord($result)) {
					$card = intval($results->id);
					$user = intval($results->user_id);
					if ($user == $userId) {
						$ownCardIds[] = $card;
					}
					else {
						$friendCardIds[] = $card;
					}
				}
				return array ($ownCardIds, $friendCardIds);
			}
			else {
				error_log($query);
				return null;
			}
		}
		else {
			return array ();
		}
	}
	
	/**
	 * Determines new problems that user with user Id can answer to
	 * Returns problem object with: question, answer, questioner, card name, status date, topic name and group name 
	 * Sorts results by modification date, descending
	 * @param $userId Id of the user whose notes are to be determined
	 * @return object
	 */
	function findNewProblems($userId,$fromDate) {
		global $mediabirdDb;
	
		$type = "question";
		$selectProblem = "notify>0 AND tool='".$mediabirdDb->escape($type)."' 
				AND 
					(modified>'".$mediabirdDb->datetime($fromDate)."' OR created>'".$mediabirdDb->datetime($fromDate)."')
				AND
					(shared = 1 OR user_id = $userId)
				AND card IN ( 
					SELECT id FROM ".MediabirdConfig::tableName('Card')." WHERE 
						topic IN (SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE user_id=$userId) 	
			     	OR topic IN (
			       			SELECT topic FROM ".MediabirdConfig::tableName('Right')." WHERE mask>0 AND group_id 
				      			IN ( 
									SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$userId AND active=1)
								)
				)";
		
		$problems = (array)null;
		$cards = (array)null;
		
		if($results = $mediabirdDb->getRecords(MediabirdConfig::tableName('Marker',true),$selectProblem,'created DESC','id, user_id, card, data, modified, created')) {
			foreach ($results as $result) {
				$problem = (object)null;
				$problem->id = intval($result->id);
				
				$result->created = $mediabirdDb->timestamp($result->created);
				$result->modified = $mediabirdDb->timestamp($result->modified);
				
				$problem->date = $result->created;
				$problem->created = $result->created;
				$problem->modified = $result->created;
				
				$data = json_decode($result->data);
				if(isset($data->question)){
					$problem->question = $data->question;
				}
				//check for a suggested answer
				if(isset($data->answer)){
					$problem->answer = $data->answer;
				}
				if($card = $mediabirdDb->getRecord(MediabirdConfig::tableName('Card', true), "id=$result->card")){
					$problem->cardTitle = $card->title;
					$problem->cardId = $card->id;
				}
				
				$selectTopic = "id = (SELECT topic FROM ".MediabirdConfig::tableName('Card')." WHERE id=$result->card)";
	
				//check for questioner
				if($resultQuestioner = $mediabirdDb->getRecord(MediabirdConfig::tableName('User',true),"id=$result->user_id")){
					$problem->questioner = $resultQuestioner->name;
					if($resultTopic = $mediabirdDb->getRecord(MediabirdConfig::tableName('Topic', true), $selectTopic)){
						$problem->topicTitle = $resultTopic->title;
						$problem->topicId = $resultTopic->id;
					}
				}
				
				$selectGroup = "id IN (SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$userId AND active=1) 
					AND 
						id IN (SELECT group_id FROM ".MediabirdConfig::tableName('Membership')." WHERE user_id=$result->user_id AND active=1)";
				
				if($groups = $mediabirdDb->getRecords(MediabirdConfig::tableName('Group',true),$selectGroup,'id,name')){
					if(count($groups) == 1){
						$maxGroup = $groups[0];
					}
					else {		
						unset($maximum);
						foreach($groups as $group) {
							if($rights = $mediabirdDb->getRecords(MediabirdConfig::tableName('Right', true),"topic = $problem->topicId AND group_id = $group->id")){
								foreach($rights as $right){
									if(!isset($maximum) || $right->mask >= $maximum->mask){
										$maximum = $right;
										$maxGroup = $group;
									}
								}
							}
						}
					}
					$problem->groupName = $maxGroup->name;
				}				 
				$problems[] = $problem;	
			}
					
		}	
		return $problems;
	}
}
?>
