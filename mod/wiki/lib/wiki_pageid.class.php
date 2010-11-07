<?php

/**
 * This file contains wiki_pageid class
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */ 

class wiki_pageid {

    /**
     * Constructor of the class.
     *
     * @param   int     $wikiid
     * @param   string  $name
     * @param   int     $version
     * @param   int     $groupid
     * @param   int     $ownerid
     */
    function wiki_pageid($wikiid=null, $name=null, $version=null,
            $groupid=null, $ownerid=null) {
        global $WS;
        if (isset($wikiid)) {
            $this->wikiid = $wikiid;
            $this->name = $name;
            $this->version = $version;
            $this->groupid = $groupid;
            $this->ownerid = $ownerid;
        } else {
            $wiki = wiki_param('dfwiki');
            $this->wikiid = $wiki->id;
            $this->name = $WS->page;
            $this->version = null;
            $this->groupid = $WS->groupmember->groupid;
            $this->ownerid = 0;
            if ($WS->cm->groupmode == 0 or $wiki->studentmode != 0) {
                $this->ownerid = $WS->member->id;
            }
        }
    }
}

?>
