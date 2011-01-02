<?php

function xmldb_block_ousearch_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    // Set search-type of the existing block instances to muliactivity as default
    if ($result && $oldversion < 2010062500) { 
        $result = set_ousearch_instance_configdata();
    }

    // Change index/primary key on 'words' table
    if ($result && $oldversion < 2008020800) { 
        $result &= execute_sql(
            "ALTER TABLE {$CFG->prefix}block_ousearch_words DROP CONSTRAINT {$CFG->prefix}blocouseword_wor_pk"); 
        $result &= execute_sql(
            "ALTER TABLE {$CFG->prefix}block_ousearch_words ADD CONSTRAINT {$CFG->prefix}blocouseword_id_pk PRIMARY KEY(id)"); 
        $result &= execute_sql(
            "CREATE UNIQUE INDEX {$CFG->prefix}blocouseword_wor_uix ON {$CFG->prefix}block_ousearch_words (word)"); 
    }
    return $result;
}


/**
 * This function sets search-type of the existing block instances to muliactivity as default
 * @param void
 * @return boolean
 */
function set_ousearch_instance_configdata() {
    if (!$blockid = get_field('block', 'id', 'name', 'ousearch')) {
        return false;
    }
    $multiactivity = 'Tzo2OiJvYmplY3QiOjQ6e3M6MTM6Ik1BWF9GSUxFX1NJWkUiO3M6OToiMTE1MzQzMzYwIjtzOjE4OiJfcWZfX291c2VhcmNoX2Zvcm0iO3M6MToiMSI7czoxMDoic2VhcmNodHlwZSI7czoxMzoibXVsdGlhY3Rpdml0eSI7czoxMjoic3VibWl0YnV0dG9uIjtzOjEyOiJTYXZlIGNoYW5nZXMiO30=';
    if (!set_field('block_instance', 'configdata', $multiactivity, 'blockid', $blockid)) {
        error("Could not set the filed 'configdata' in 'block_instance' table");
        return false;
    }
    return true;
}

?>
