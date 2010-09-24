<?php  

function xmldb_assignment_type_peerreview_upgrade($oldversion=0) {
    global $CFG, $THEME, $db;
    $result = true;

    // Change type of criteria values to integers
    if ($result && $oldversion < 2010050700) {
        $table = new XMLDBTable('assignment_criteria');
        $field = new XMLDBField('value');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'textshownatreview');
        $result = $result && change_field_type($table, $field);        
    }

    // Add fields for review metrics
    if ($result && $oldversion < 2010050500) {
        $table = new XMLDBTable('assignment_review');
        $field = new XMLDBField('timedownloaded');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'downloaded');
        $result = $result && add_field($table, $field);
        $field = new XMLDBField('timecompleted');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'complete');
        $result = $result && add_field($table, $field);
    }

    return $result;
}

?>
