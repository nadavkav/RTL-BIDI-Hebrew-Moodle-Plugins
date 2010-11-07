<?php

/**
 * This file contains wiki_synonym class
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */ 

class wiki_synonym {

    /**
     * Constructor of the class.
     *
     * @param   string      $name   Synonim name.
     * @param   wiki_pageid $pageid Original page id.
     */

    function wiki_synonym($name, $pageid) {
        $this->name = $name;
        $this->pageid = $pageid;
        $this->deletable = false;
    }

}

?>