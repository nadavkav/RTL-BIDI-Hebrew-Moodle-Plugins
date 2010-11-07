<?php

    /**
     *  Checks if the user has voted. Returns true if the user has voted
     *  or false if hasn't.
     *
     *  @param String $pagename
     *  @param integer $version
     *  @param integer $wikiid
     *  @return Boolean
     */
    function user_has_voted($pagename, $version, $wikiid) {
		global $CFG, $USER;
  
		$count = get_record_sql('SELECT count(*) AS has_votes
								FROM '.$CFG->prefix.'wiki_votes
								WHERE pagename=\''.addslashes($pagename).'\' AND version='.$version.
								 ' AND dfwiki='.$wikiid.' AND username=\''.$USER->username.'\'');
		
		return $count->has_votes >= 1;						 	
    }




?>