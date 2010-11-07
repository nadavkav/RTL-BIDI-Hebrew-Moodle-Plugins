<?php
/// Original DFwiki created by David Castro, Ferran Recio and Marc Alier.
/// this file contains wikitab object

/**
 * that object represents a wiki tab
 */
class wikitab {
	
	/**
	 * creator
	 * @param String $name=null: tab name identifier
	 * @param String $url=null: tab url codified for wiki_format_url
	 * @param String $text=null: tab display text. If null, class will return a get_string($name,'wiki') instead
	 * @param mixed $visible=null: if tab is visible. If is an function name, class will execute the callback function. 
	 */
	function wikitab ($name=null,$url=null,$text=null,$visible=null) {
		if ($name) $this->tabname = $name;
		if ($text!==null) $this->tabtext = $text;
		if ($url) $this->taburl = $url;
		if ($visible!==null && is_bool($visible)) $this->tabvisible = $visible;
	}
	
	/**
	 * returns the tab name and identifier.  If tabname is defined, the method will return it.
	 * @return String
	 */
	function name () {
		if (isset($this->tabname)) return $this->tabname;
		return 'wrong_tab';
	}
	
	/**
	 * returns the text that will be diplayed. If tabtext is defined, the method will return it.
	 * @return String or false (if false program will show get_string(tabname,'wiki') result instead)
	 */
	function text () {
		if (isset($this->tabtext)) return $this->tabtext;
		return false;
	}
	
	/**
	 * returns the url to access this tab. If taburl is defined the method
	 * will return the wiki_format_url result
	 */
	function url () {
		global $CFG;
		if (isset($this->taburl)) return wiki_format_url ($this->taburl);
		//if (isset($this->taburl)) return $this->taburl;
		return $CFG->wwwroot;
	}
	
	/**
	 * determine if this tab is visible or not. If tabvisible is defined and is a boolean
	 * the method will return it, if it's a string the method will execute the callback function.
	 * Otherwise will return false.
	 * @return boolean
	 */
	function visible () {
		if (isset($this->tabvisible)) {
			if (is_string($this->tabvisible) && !is_numeric($this->tabvisible) && function_exists($this->tabvisible)) {
				$func = $this->tabvisible;
				return $this->tabvisible($this);
			}
			return $this->tabvisible;
		}
		return false;
	}
}

?>