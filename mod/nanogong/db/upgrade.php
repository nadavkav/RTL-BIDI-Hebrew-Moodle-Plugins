<?php // $Id: upgrade.php,v 3.3 2010/01/25 00:00:00 gibson Exp $

function xmldb_nanogong_upgrade($oldversion=0) {
    $result = true;
    
    if ($result && $oldversion < 2010012500) {
        // Add maxduration field
        $table = new XMLDBTable('nanogong');
        $field = new XMLDBField('maxduration');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '300', 'color');
        $result = $result && add_field($table, $field);
    }

    return $result;
}

?>
