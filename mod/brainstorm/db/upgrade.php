<?PHP

function xmldb_brainstorm_upgrade($oldversion=0) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    $result = true;

    if ($oldversion < 2008011200) {
       modify_database('', "ALTER TABLE `{$CFG->prefix}brainstorm` CHANGE `text` `description` TEXT NULL ; ");
       table_column('brainstorm', '', 'flowmode', 'varchar', '16', '', 'parallel', '', 'description');
       table_column('brainstorm', '', 'seqaccesscollect', 'int', '4', 'unsigned', 0, '', 'flowmode');
       table_column('brainstorm', '', 'seqaccessprepare', 'int', '4', 'unsigned', 0, '', 'seqaccesscollect');
       table_column('brainstorm', '', 'seqaccessorganize', 'int', '4', 'unsigned', 0, '', 'seqaccessprepare');
       table_column('brainstorm', '', 'seqaccessdisplay', 'int', '4', 'unsigned', 0, '', 'seqaccessorganize');
       table_column('brainstorm', '', 'phase', 'int', '4', 'unsigned', 0, '', 'seqaccessdisplay');
       table_column('brainstorm', '', 'privacy', 'int', '4', 'unsigned', 0, '', 'phase');
       table_column('brainstorm', '', 'numresponsesinform', 'int', '4', 'unsigned', 0, '', 'numresponses');
       table_column('brainstorm', '', 'oprequirementtype', 'int', '4', 'unsigned', 0, '', 'numcolumns');
       table_column('brainstorm', '', 'scale', 'int', '4', 'unsigned', 0, '', 'oprequirementtype');

       modify_database('', "ALTER TABLE `{$CFG->prefix}brainstorm_responses` CHANGE `brainstorm` `brainstormid` INT(10) UNSIGNED ; ");
       modify_database('', "ALTER TABLE `{$CFG->prefix}brainstorm_responses` DROP `categorytitle` ; ");
       table_column('brainstorm_responses', '', 'groupid', 'int', '10', 'unsigned', 0, '', 'userid');

       modify_database('', "ALTER TABLE `{$CFG->prefix}brainstorm_categories` CHANGE `brainstorm` `brainstormid` INT(10) UNSIGNED ; ");
       modify_database('', "ALTER TABLE `{$CFG->prefix}brainstorm_categories` DROP `categorynumber` ; ");
       table_column('brainstorm_categories', '', 'userid', 'int', '10', 'unsigned', 0, '', 'brainstormid');
       table_column('brainstorm_categories', '', 'groupid', 'int', '10', 'unsigned', 0, '', 'userid');

    /// Define table brainstorm_operatordata to be created
        $table = new XMLDBTable('brainstorm_operatordata');

    /// Adding fields to table brainstorm_operatordata
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('brainstormid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('operatorid', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('itemsource', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('itemdest', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('intvalue', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('floatvalue', XMLDB_TYPE_FLOAT, null, null, null, null, null, null, null);
        $table->addFieldInfo('blobvalue', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table brainstorm_operatordata
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('unique_assignation', XMLDB_KEY_UNIQUE, array('brainstormid', 'userid', 'operatorid', 'itemsource', 'itemdest', 'intvalue'));

    /// Launch create table for brainstorm_operatordata
        $result = $result && create_table($table);

    /// Define table brainstorm_operators to be created
        $table = new XMLDBTable('brainstorm_operators');

    /// Adding fields to table brainstorm_operators
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('brainstormid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('operatorid', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('configdata', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('active', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1');

    /// Adding keys to table brainstorm_operators
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('unique_operator', XMLDB_KEY_UNIQUE, array('brainstormid', 'operatorid'));

    /// Launch create table for brainstorm_operators
        $result = $result && create_table($table);
    }

    if ($oldversion < 2008011801) {
       table_column('brainstorm', '', 'seqaccessfeedback', 'int', '4', 'unsigned', 0, '', 'seqaccessdisplay');
    }
    
    if ($oldversion < 2008012100) {

    /// Define table brainstorm_grades to be created
        $table = new XMLDBTable('brainstorm_grades');

    /// Adding fields to table brainstorm_grades
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('brainstormid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('grade', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('gradeitem', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timeupdated', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table brainstorm_grades
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for brainstorm_grades
        $result = $result && create_table($table);

    /// Define table brainstorm_userdata to be created
        $table = new XMLDBTable('brainstorm_userdata');

    /// Adding fields to table brainstorm_userdata
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('brainstormid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('report', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('reportformat', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('feedback', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('feedbackformat', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timeupdated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table brainstorm_userdata
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));


    /// Launch create table for brainstorm_userdata
        $result = $result && create_table($table);

    /// Define field singlegrade to be added to brainstorm
        $table = new XMLDBTable('brainstorm');
        $field = new XMLDBField('singlegrade');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, null, null, 'scale');

    /// Launch add field singlegrade
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('participationweight');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '4, 2', null, XMLDB_NOTNULL, null, null, null, null, 'singlegrade');

    /// Launch add field participationweight
        $result = $result && add_field($table, $field);

    /// Define field preparingweight to be added to brainstorm
        $field = new XMLDBField('preparingweight');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '4, 2', null, XMLDB_NOTNULL, null, null, null, null, 'participationweight');

    /// Launch add field preparingweight
        $result = $result && add_field($table, $field);

    /// Define field organizeweight to be added to brainstorm
        $field = new XMLDBField('organizeweight');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '4, 2', null, XMLDB_NOTNULL, null, null, null, null, 'preparingweight');

    /// Launch add field organizeweight
        $result = $result && add_field($table, $field);

    /// Define field feedbackweight to be added to brainstorm
        $field = new XMLDBField('feedbackweight');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '4, 2', null, XMLDB_NOTNULL, null, null, null, null, 'organizeweight');

    /// Launch add field feedbackweight
        $result = $result && add_field($table, $field);
    }

    return true;
}

?>
