<?php  //$Id: upgrade.php,v 1.15 2008/08/04 08:37:04 pigui Exp $

// This file keeps track of upgrades to 
// the wiki module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what do you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_wiki_upgrade($oldversion=0) {

    global $CFG, $db;

    $result = true;

    // Checks if the current version installed in the system is old wiki (ewiki) or is new wiki (nwiki)
    // We can distinguish ewiki from nwiki checking wiki_synonymous table existence.
    //Initialy we asume we aren't upgrading from old wiki
    $fromoldwiki = false;

	$table = new XMLDBTable('wiki_synonymous');
    if (!table_exists($table)) {  //New wiki isn't installed yet
        $fromoldwiki = true;  //We are upgrading from old wiki.

		// Upgrading ewiki to last version using XMLDB functions.
		require_once ($CFG->dirroot.'/mod/wiki/wikimigrate/ewiki_upgrade.php');
		$result = xmldb_ewiki_upgrade($oldversion);
		
		// We have an upgraded ewiki at this point of process.
		// Migration can start.
		
        require_once ($CFG->dirroot.'/mod/wiki/wikimigrate/ewiki_migrate.php');
		wiki_migrate_ewiki();

        $oldversion=0;

    }

		
	if ($result && $oldversion < 2006042900) {

        //Delete previous log_display records for wiki for safety
		delete_records('log_display', 'module', 'wiki');
        
        //Add new log_display_records
        $record->module = 'wiki';
		$record->action = 'add';
		$record->mtable = 'wiki';
		$record->field = 'name';
        $result = insert_record('log_display', $record);

		$record->action = 'update';
        $result = $result && insert_record('log_display', $record);
		
		$record->action = 'view';
        $result = $result && insert_record('log_display', $record);
		
		$record->action = 'view all';
        $result = $result && insert_record('log_display', $record);
		
		$record->mtable = 'wiki_pages';
		$record->field = 'pagename';
		$record->action = 'view page';
        $result = $result && insert_record('log_display', $record);
		
		$record->action = 'edit page';
        $result = $result && insert_record('log_display', $record);

		$record->action = 'save page';
        $result = $result && insert_record('log_display', $record);
				
		$record->action = 'info page';
        $result = $result && insert_record('log_display', $record);
		
// OLD SENTENCES		
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'add', 'wiki', 'name') ");
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'update', 'wiki', 'name') ");
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'view', 'wiki', 'name') ");
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'view all', 'wiki', 'name') ");
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'view page', 'wiki_pages', 'pagename') ");
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'edit page', 'wiki_pages', 'pagename') ");
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'save page', 'wiki_pages', 'pagename') ");
//        execute_sql(" INSERT INTO {$CFG->prefix}log_display (module, action, mtable, field) VALUES ('wiki', 'info page', 'wiki_pages', 'pagename') ");
    }

    if ($result && $oldversion < 2006050301) {

        $result = table_column ('wiki_synonymous', '', 'userid', 'int', '10', 'unsigned', '0', 'not null', 'groupid');

        if (empty($fromoldwiki)) {  //Only if we aren't migrating from old wiki, because it creates these columns and fills them!
        
	        $result = $result && table_column ('wiki', '', 'intro', 'text', '', '', '', 'not null', 'name');
			$result = $result && table_column ('wiki', '', 'introformat', 'tinyint', '2', 'unsigned', '0', 'not null', 'intro');

            //add colums
            $result = $result && table_column ('wiki_pages', '', 'userid', 'int', '10', 'unsigned', '0', 'not null', 'author');
            $result = $result && table_column ('wiki_synonymous', '', 'userid', 'int', '10', 'unsigned', '0', 'not null', 'groupid');

            //set userid  via author values
			$authors = get_records_select('wiki_pages', null, null, 'distinct author');            
// OLD SENTENCE			
//            $authors = get_records_sql('SELECT DISTINCT author, author FROM '.$CFG->prefix.'wiki_pages');

            foreach($authors as $author) {
                if ($user = get_record('user', 'username', $author->author)) {
                    $result = $result && set_field ('wiki_pages', 'userid', $user->id, 'author', $author->author);
                }
            }
        }
    }

    if ($result && $oldversion < 2006050500) {
        //droping and regenerating some indexes to normalize everything
        //(some of them could not exist, but for safety...)

		$table = new XMLDBTable('wiki');
		$index = new XMLDBIndex('course');
		$index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
		$result = drop_index($table, $index);
		
		$index = new XMLDBIndex('course');
		$index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
		$result = $result && add_index($table, $index);
		
		$table = new XMLDBTable('wiki_pages');
		$key = new XMLDBKey('dfwiki_pages_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE,  array ('pagename', 'version', 'dfwiki', 'groupid', 'userid'));
		$result = $result && drop_key($table, $key);
		
		$key = new XMLDBKey('wiki_pages_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE,  array ('pagename', 'version', 'dfwiki', 'groupid', 'userid'));
		$result = $result && drop_key($table, $key);

		$key = new XMLDBKey('wiki_pages_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array ('pagename', 'version', 'dfwiki', 'groupid', 'userid', 'ownerid'));
		$result = $result && add_key($table, $key);
		
		$table = new XMLDBTable('wiki_synonymous');
		$key = new XMLDBKey('dfwiki_synonymous_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array('syn','dfwiki','groupid', 'userid'));
		$result = drop_key($table, $key);
		
		$key = new XMLDBKey('wiki_synonymous_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array('syn','dfwiki','groupid', 'userid'));
		$result = drop_key($table, $key);
		
		$key = new XMLDBKey('wiki_synonymous_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array('syn','dfwiki','groupid', 'userid'));
		$result = $result && add_key($table, $key);
				
// OLD SENTENCES
//-        execute_sql(" ALTER TABLE {$CFG->prefix}wiki DROP KEY course;",false);
//-        execute_sql(" ALTER TABLE {$CFG->prefix}wiki ADD KEY course (course);",false);
//-        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_pages DROP KEY dfwiki_pages_uk;",false);
//-        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_pages DROP KEY wiki_pages_uk;",false);
//-        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_pages ADD UNIQUE KEY wiki_pages_uk (pagename, version, dfwiki, groupid, userid);",false);
//-        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous DROP KEY dfwiki_synonymous_uk;",false);
//        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous DROP KEY wiki_synonymous_uk;",false);
//        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous ADD UNIQUE KEY wiki_synonymous_uk (syn,dfwiki,groupid, userid);",false);
    }
    
    if ($result && $oldversion < 2006051800) {

		$table = new XMLDBTable('wiki_pages');
		$field = new XMLDBField('highlight');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'editable');
		$result = add_field($table, $field);
// OLD SENTECE		
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_pages` ADD `highlight` tinyint(1) NOT NULL default 0 AFTER `editable`');
    }

    if ($result && $oldversion < 2006053000) {

        if (empty($fromoldwiki)) {  //Only if we aren't migrating from old wiki, because it creates this column!
        
			$table = new XMLDBTable('wiki_pages');
			$field = new XMLDBField('votes');
			$field->setAttributes(XMLDB_TYPE_INTEGER, null, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'hits');
			$result = add_field($table, $field);
// OLD SENTENCE
//    execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_pages` ADD `votes` INTEGER UNSIGNED NOT NULL default 0 AFTER `hits`');
       }
    }

    if ($result && $oldversion < 2006060701) {

        if (empty($fromoldwiki)) {

			$table = new XMLDBTable('wiki');
			$field = new XMLDBField('studentmode');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', null);
			$result = add_field($table, $field);
			
			$field = new XMLDBField('teacherdiscussion');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', null);
			$result = $result && add_field($table, $field);
			
			$field = new XMLDBField('studentdiscussion');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', null);
			$result = $result && add_field($table, $field);	

			$field = new XMLDBField('editanothergroup');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', null);
			$result = $result && add_field($table, $field);								
			
			$field = new XMLDBField('editanotherstudent');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', null);
			$result = $result && add_field($table, $field);
			
			
			$table = new XMLDBTable('wiki_pages');
			$field = new XMLDBField('evaluation');
			$field->setAttributes(XMLDB_TYPE_TEXT,'medium' , null, XMLDB_NOTNULL, null, null, null, '', 'groupid');
			$result = add_field($table, $field);
			
// OLD SENTENCES
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `studentmode` tinyint(1) NOT NULL default 0');
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `teacherdiscussion` int(1) NOT NULL default 0');
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `studentdiscussion` int(1) NOT NULL default 0');
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `editanothergroup` tinyint(1) not null default 0');
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `editanotherstudent` tinyint(1) not null default 0');
//
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_pages` ADD `evaluation` MEDIUMTEXT default NULL AFTER `groupid`');
        }
        else{
			
			$table = new XMLDBTable('wiki_pages');
			$field = new XMLDBField('evaluation');
			$field->setAttributes(XMLDB_TYPE_TEXT,'medium' , null, null, null, null, null, '', 'ownerid');
			$result = add_field($table, $field);			
			
// OLD SENTENCE
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_pages` ADD `evaluation` MEDIUMTEXT default NULL AFTER `ownerid`');
        }
		
		$table = new XMLDBTable('wiki');
		$field = new XMLDBField('evaluation');
		$field->setAttributes(XMLDB_TYPE_CHAR, '40', null, null, null, null, null, 'noeval', 'studentdiscussion');
		$result = $result && add_field($table, $field);
	
		$field = new XMLDBField('notetype');
		$field->setAttributes(XMLDB_TYPE_CHAR, '40', null, null, null, null, null, 'quant', 'evaluation');
		$result = $result && add_field($table, $field);
		
// OLD SENTECES
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `evaluation` varchar(40) default "noeval" AFTER `studentdiscussion`');
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `notetype` varchar(40) default "quant" AFTER `evaluation`');
    }


    if ($result && $oldversion < 2006060702 && empty($fromoldwiki)) {

        //make sure we don't have ownerid in the DB (backward compatibility)
		$table = new XMLDBTable('wiki_pages');
		$field = $table->findFieldInArray('ownerid');
		if (empty ($field) ){
			
			$field = new XMLDBField('ownerid');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'groupid');
			
			$result = add_field($table, $field);
			
			$key = new XMLDBKey('wiki_pages_uk');
			$key->setAttributes(XMLDB_KEY_UNIQUE, array('pagename', 'version', 'dfwiki', 'groupid', 'userid'));
			$result = $result && drop_key($table, $key);

			$key = new XMLDBKey('wiki_pages_uk');			
			$key->setAttributes(XMLDB_KEY_UNIQUE, array('pagename', 'version', 'dfwiki', 'groupid', 'userid', 'ownerid'));
			$result = $result && add_key($table, $key);
			
			$table = new XMLDBTable('wiki_synonymous');
			$field = new XMLDBField('userid');
			
			$result = $result && drop_field($table, $field);
			
			$field = new XMLDBField('ownerid');			
			$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);
			
			$result = $result && add_field($table, $field);
			
			$key = new XMLDBKey('wiki_synonymous_uk');
			$result = $result && drop_key($table, $key);
			
			$key = new XMLDBKey('wiki_synonymous_uk');			
			$key->setAttributes(XMLDB_KEY_UNIQUE, array('syn', 'dfwiki', 'groupid', 'ownerid'));
			$result = $result && add_key($table, $key);
			
			

// OLD SENTENCES		
//        //make sure we don't have ownerid in the DB (backward compatibility)
//       if(!get_records_sql('SELECT ownerid FROM '.$CFG->prefix.'wiki_pages LIMIT 0 , 30')){
//            //adding new ownerid replacing userid
//-            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_pages` ADD `ownerid` int(10) unsigned not null default 0 AFTER `groupid`');
//-            execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous DROP userid;",false);
//-            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_synonymous` ADD `ownerid` int(10) unsigned not null default 0');
//
//-            execute_sql(" ALTER TABLE {$CFG->prefix}wiki_pages DROP KEY wiki_pages_uk;",false);
//-            execute_sql(" ALTER TABLE {$CFG->prefix}wiki_pages ADD UNIQUE KEY wiki_pages_uk (pagename, version, dfwiki, groupid, userid, ownerid);",false);
//
//-            execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous DROP KEY wiki_synonymous_uk;",false);
//-            execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous ADD UNIQUE KEY wiki_synonymous_uk (syn,dfwiki,groupid,ownerid);",false);
//

			

			//now ownerid is the old userid, and userid is the author's id
			$quer = 'UPDATE '.$CFG->prefix.'wiki_pages SET ownerid = userid';
            $result = $result && execute_sql($quer); // It can't be translated to XMLDB functions
			
			$authors = get_records_select('wiki_pages', null, null, 'distinct author');
            foreach($authors as $author) {
                if ($user = get_record('user', 'username', $author->author)) {
                        //set userid corrrectly
                        set_field ('wiki_pages', 'userid', $user->id, 'author', $author->author);
                }
			
			
// OLD SENTENCES		
//            //now ownerid is the old userid, and userid is the author's id
//            $quer = 'UPDATE '.$CFG->prefix.'wiki_pages
//            SET ownerid = userid';
//            execute_sql($quer);
//
//            //set userid  via author values
//            $authors = get_records_sql('SELECT DISTINCT author, author FROM '.$CFG->prefix.'wiki_pages');
//            foreach($authors as $author) {
//                if ($user = get_record('user', 'username', $author->author)) {
//                        //set userid corrrectly
//                        set_field ('wiki_pages', 'userid', $user->id, 'author', $author->author);
//                }
//            }
	        }
		}
    }


    if ($result && $oldversion < 2006060702 && $fromoldwiki) {

		$table = new XMLDBTable('wiki_pages');
		$field = new XMLDBField('destacar');
		$result = drop_field($table, $field);
		
		$key = new XMLDBKey('wiki_pages_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array ('pagename', 'version', 'dfwiki', 'groupid', 'userid'));
		$result = $result && drop_key($table, $key);
		
		$key = new XMLDBKey('wiki_pages_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array ('pagename', 'version', 'dfwiki', 'groupid', 'userid', 'ownerid'));
		$result = $result && add_key($table, $key);
		
		$table = new XMLDBTable('wiki_synonymous');
		$field = new XMLDBField('userid');
		$result = $result && drop_field($table, $field);

		$key = new XMLDBKey('wiki_synonymous_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array ('syn', 'dfwiki', 'groupid', 'userid', 'ownerid'));			
		$result = $result && drop_key($table, $key);

		$key = new XMLDBKey('wiki_synonymous_uk');
		$key->setAttributes(XMLDB_KEY_UNIQUE, array ('syn', 'dfwiki', 'groupid', 'ownerid'));
		$result = $result && add_key($table, $key);
		
// OLD SENTENCES		
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_pages` DROP `destacar`');
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_synonymous` DROP `userid`');
//
//        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_pages DROP KEY wiki_pages_uk;",false);
//        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_pages ADD UNIQUE KEY wiki_pages_uk (pagename, version, dfwiki, groupid, userid, ownerid);",false);
//
//        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous DROP KEY wiki_synonymous_uk;",false);
//        execute_sql(" ALTER TABLE {$CFG->prefix}wiki_synonymous ADD UNIQUE KEY wiki_synonymous_uk (syn,dfwiki,groupid,ownerid);",false);
		

		$authors = get_records_select('wiki_pages', null, null, 'author');
		if(!empty($authors)){
	        foreach($authors as $author) {
				if ($user = get_record('user', 'username', $author->author)) {
	            	//set userid corrrectly
	                set_field ('wiki_pages', 'userid', $user->id, 'author', $author->author);
	            }
	        }
		}
//OLD SENTENCES
//        //set userid  via author values
//        $authors = get_records_sql('SELECT DISTINCT author, author FROM '.$CFG->prefix.'wiki_pages');
//        foreach($authors as $author) {
//            if ($user = get_record('user', 'username', $author->author)) {
//                //set userid corrrectly
//                set_field ('wiki_pages', 'userid', $user->id, 'author', $author->author);
//            }
//        }
    }


    //Actualizamos la newwiki independientemenre de si hemos utilizado
    //una dfwiki instalada y actualizada o una dfwiki temporal
    if ($result && $oldversion < 2006070700) {

		$table = new XMLDBTable('wiki');
		$field = new XMLDBField('votemode');
		
		$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL , null, null, null, '0', null);

		$result = add_field($table, $field);
						
// OLD SENTENCE		
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `votemode` tinyint(1) NOT NULL default 0');

    }

    if ($result && $oldversion < 2006070703) {

		$table = new XMLDBTable('wiki_votes');
		$table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
		$table->addFieldInfo('pagename', XMLDB_TYPE_CHAR, '160', null, XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('version', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, '0', null);
		$table->addFieldInfo('dfwiki', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, '0', null);
		$table->addFieldInfo('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, null);
		
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
		
        $result = create_table($table);
														
// OLD SENTENCE
//        execute_sql('CREATE TABLE `'.$CFG->prefix.'wiki_votes` (`id` int(10) unsigned NOT NULL auto_increment,
//                          `pagename` varchar(160) NOT NULL,
//                          `version` int(10) unsigned NOT NULL default 0,
//                          `dfwiki` int(10) unsigned NOT NULL,
//                          `username` varchar(100) NOT NULL,
//                          PRIMARY KEY  (`id`))');


    }

    if ($result && $oldversion < 2006071402) {
	
		$table = new XMLDBTable('wiki');
	    $field = new XMLDBField('listofteachers');
		
		$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL , null, null, null, '0', null);
		$result = add_field($table, $field);
			
// OLD SENTENCE		
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `listofteachers` tinyint(1) NOT NULL default 0');

    }


    if ($result && $oldversion < 2006072401) {
	
		$table = new XMLDBTable('wiki_pages');
		$field = new XMLDBField('votes');
		$result = drop_field($table, $field);
// OLD VERSION	
//        execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki_pages` DROP `votes`');

    }

    // correct version error
    if ($result && $oldversion == 2006271001){

        $oldversion = 2006102701;
		$result = set_field('modules', 'version', '2006102701', 'name', 'wiki');
		
// OLD SENTENCE		
//        execute_sql('UPDATE `'.$CFG->prefix.'modules` SET `version`= 2006102701 WHERE `name` = wiki');
    }

    if ($result && $oldversion < 2006112101) {

		$table = new XMLDBTable('wiki');
		$field = $table->findFieldInArray('editorrows');
		if (empty ($field) ){
		    $field = new XMLDBField('editorrows');
			
			$field->setAttributes(XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL , null, null, null, '40', null);
			$result = add_field($table, $field);			
		}
		$field = $table->findFieldInArray('editorcols');
		if (empty ($field) ){
		    $field = new XMLDBField('editorcols');
			
			$field->setAttributes(XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL , null, null, null, '60', null);
			$result = $result && add_field($table, $field);			
		}		
// OLD SENTENCES
//        if (!get_records_sql('SHOW COLUMNS FROM '.$CFG->prefix.'wiki WHERE Field=\'editorrows\'')){
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `editorrows` integer NOT NULL DEFAULT 40');
//        }
//
//        if (!get_records_sql('SHOW COLUMNS FROM '.$CFG->prefix.'wiki WHERE Field=\'editorcols\'')){
//            execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `editorcols` integer NOT NULL DEFAULT 60');
//        }

    }
    
    

    if ($result && $oldversion < 2006112502) {

		$table = new XMLDBTable('wiki');
        $field = new XMLDBField('wikicourse');
		
		$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL , null, null, null, '0', null);
		$result = add_field($table, $field);

// OLD SENTENCE		
//      execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `wikicourse` int(10) unsigned NOT NULL DEFAULT 0');

        if ($wikicourses = get_records('course','format', 'wiki') ) {

            $wikimod = get_record('modules', 'name', 'wiki');
            foreach ($wikicourses as $wikicourse){

				$min = get_record_select('course_module', 'course = '.$wikicourse->id.' AND module = '.$wikimod->id, 'min(id) as minim');
// OLD SENTENCE			
//              $min = get_record_sql('SELECT MIN(`id`) AS minim FROM '.$CFG->prefix.'course_modules WHERE `course`=\''.$wikicourse->id.'\' AND `module`=\''.$wikimod->id.'\'');
                $cm = get_record('course_modules', 'id', $min->minim);
                $result = $result && set_field ('wiki', 'wikicourse', $wikicourse->id,'id', $cm->instance);
            }
        }

    }
    


	if ($result && $oldversion < 2007022707) {

		// Moving archives to the new directory (from dfwikiX to wikiX)
		require_once($CFG->dirroot.'/lib/uploadlib.php');
		require_once($CFG->dirroot.'/backup/lib.php');

        $wikimod = get_record("modules","name","wiki");
		$wikis = get_records("wiki");
		
		if (!empty($wikis)){
			foreach ($wikis as $wiki){
				$cm = get_record("course_modules","course",$wiki->course,"instance",$wiki->id,"module",$wikimod->id);
		
				if( check_dir_exists($CFG->dataroot."/".$wiki->course."/moddata/dfwiki".$cm->id)){
					check_dir_exists($CFG->dataroot."/".$wiki->course."/moddata/wiki".$cm->id,true,true);
					$flist = list_directories_and_files ($CFG->dataroot."/".$wiki->course."/moddata/dfwiki".$cm->id);
			
					foreach($flist as $file){
						$result = $result && copy($CFG->dataroot."/".$wiki->course."/moddata/dfwiki".$cm->id."/".$file,$CFG->dataroot."/".$wiki->course."/moddata/wiki".$cm->id."/".$file);
					}
			
					$result = $result && remove_dir($CFG->dataroot."/".$wiki->course."/moddata/dfwiki".$cm->id);
				}
	
			}
		}

	}

        
	if ($result && $oldversion < 2007030301) {

		// Adding filetemplate file to database
		$table = new XMLDBTable('wiki');
        $field = new XMLDBField('filetemplate');
		
		$field->setAttributes(XMLDB_TYPE_CHAR, '60', null, null , null, null, null, null, null);
		$result = add_field($table, $field);

// OLD SENTENCE		
//      execute_sql('ALTER TABLE `'.$CFG->prefix.'wiki` ADD `filetemplate` VARCHAR(60) NULL DEFAULT NULL');
    }

    if ($result && $oldversion < 2007060701) {

		$table = new XMLDBTable('wiki_locks');
		if(!table_exists($table)){
			// Adding wiki_locks table to admin wiki pages lock system
			$table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
			$table->addFieldInfo('wikiid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
			$table->addFieldInfo('pagename', XMLDB_TYPE_CHAR, '160', null, XMLDB_NOTNULL, null, null, '', null);
			$table->addFieldInfo('lockedby', XMLDB_TYPE_INTEGER, '10', null , XMLDB_NOTNULL, null, null, 0, null);
			$table->addFieldInfo('lockedsince', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, '0', null);
			$table->addFieldInfo('lockedseen', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, '0', null);
			
	        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
			$table->addKeyInfo('wiki_locks_uk', XMLDB_KEY_UNIQUE, array('wikiid','pagename'));
	
			$table->addIndexInfo('wiki_locks_ix',XMLDB_INDEX_NOTUNIQUE, array('lockedseen'));
			
	        $result = $result && create_table($table);
		}
// OLD SENTENCE		
//		modify_database("","
//			CREATE TABLE prefix_wiki_locks
//			(
//			  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
//			  wikiid INT(10) UNSIGNED NOT NULL,
//			  pagename VARCHAR(160) NOT NULL DEFAULT '',
//			  lockedby INT(10) NOT NULL DEFAULT 0,
//			  lockedsince INT(10) NOT NULL DEFAULT 0,
//			  lockedseen INT(10) NOT NULL DEFAULT 0,
//			  PRIMARY KEY(id),
//			  UNIQUE INDEX wiki_locks_uk(wikiid,pagename),
//			  INDEX wiki_locks_ix(lockedseen)  
//			);"); 
	}
	

    if ($result && $oldversion < 2007072301) {
		
		////////////////////////////
		// Fixing some old errors //
		////////////////////////////
		
		// Old mysql.php created this field as intergers: int(11)
		// They must be smallint(3)
		//
		// I'm going to change defaults too.
		
		$table = new XMLDBTable('wiki');
		$field = new XMLDBField('editorrows');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL , null, null, null, '30', null);
		$result = change_field_precision($table, $field);
		$result = $result && change_field_default($table, $field);
		
		$field = new XMLDBField('editorcols');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL , null, null, null, '120', null);
		$result = $result && change_field_precision($table, $field);
		$result = $result && change_field_default($table, $field);
		
		
		// Changing wikicourse to bigint
        $field = new XMLDBField('wikicourse');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL , null, null, null, '0', null);
		if(!field_exists($table,$field)){
			$result = $result && add_field($table, $field);
		} else {
			$result = $result && change_field_precision($table, $field);		
		}
		// Renaming key 'course'
		$key= new XMLDBIndex('course');
		$key->setAttributes(XMLDB_INDEX_UNIQUE, array('course'));
		$result = $result && drop_index($table, $key);
		$key= new XMLDBIndex('wiki_cou_ix');
		$key->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
		$result = $result && add_index($table, $key);
	/*	
		// Changing some field in wiki_pages
		$table = new XMLDBTable('wiki_pages');
		$field = new XMLDBField('evaluation');
		$field->setAttributes(XMLDB_TYPE_TEXT,'medium' , null, XMLDB_NOTNULL, null, null, null, "", 'ownerid');
		$result = $result && change_field_default($table, $field);
		$result = $result && change_field_notnull($table, $field);
	*/
	}

    // Add groupid and ownerid fields to the wiki_locks table
    if ($result && $oldversion < 2007121701) {
		$table = new XMLDBTable('wiki_locks');

		$field = new XMLDBField('groupid');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 0, null);
		$result = add_field($table, $field);

		$field = new XMLDBField('ownerid');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL , null, null, null, 0, null);
		$result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008013105) { // To create a new tables: wiki_evaluation and wiki_evaluation_edition
		
		/////////// WIKI_EVALUATION ////////////////////////////////
		$table = new XMLDBTable('wiki');
		$field = new XMLDBField('evaluation');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'studentdiscussion');
		$result = $result && add_field($table, $field);
	
		$field = new XMLDBField('notetype');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3',XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'evaluation');
		$result = $result && add_field($table, $field);
	    /// Define field id to be added to wiki_evaluation
        $table = new XMLDBTable('wiki_evaluation');

	if(!table_exists($table)){
	// Adding wiki_evaluation table
	

		
	        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);
	        $table->addFieldInfo('pagename', XMLDB_TYPE_CHAR, '160', null, XMLDB_NOTNULL, null, null, null, null, 'id');
	        $table->addFieldInfo('wikiid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'pagename');
	        $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'wikiid');
	        $table->addFieldInfo('ownerid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'groupid');
	        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'ownerid');
	        $table->addFieldInfo('wikigrade', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'userid');
	        $table->addFieldInfo('wikigrade_initial', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'wikigrade');
	        $table->addFieldInfo('comment', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'wikigrade_initial');
	
				
	        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
	
	        $table->addKeyInfo('key_uinique', XMLDB_KEY_UNIQUE, array('pagename', 'wikiid', 'groupid', 'ownerid', 'userid'));
	
	        $table->addKeyInfo('foreign_key_user', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
	
	        $result = $result && create_table($table);
	    }
	
	/////////// WIKI_EVALUATION_EDITION ////////////////////////////////
	
	    /// Define field id to be added to wiki_evaluation_edition
	        $table = new XMLDBTable('wiki_evaluation_edition');
	
	if (!table_exists($table))
	    {
	        // Adding wiki_evaluation table
	        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);
	        $table->addFieldInfo('wiki_pageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'id');
	        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'wiki_pageid');
	        $table->addFieldInfo('valoration', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, null, null, 'userid');
	        $table->addFieldInfo('feedback', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'valoration');
	
				
	        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
	        $table->addKeyInfo('key_unique_edition', XMLDB_KEY_UNIQUE, array('wiki_pageid', 'userid'));
	        $table->addKeyInfo('foreign_key_user', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
	        $table->addKeyInfo('foreign_key_wikipage', XMLDB_KEY_FOREIGN, array('wiki_pageid'), 'wiki_pages', array('id'));
	
	        /// Create the table wiki_evaluation_edition
	        $result = $result && create_table($table);
	    }
		
		
    }	

	if ($result && $oldversion < 2008080401) {
		
		$table = new XMLDBTable('wiki');
		$field = new XMLDBField('evaluation');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'studentdiscussion');
		$result = $result && change_field_type($table, $field);
		
		$field = new XMLDBField('notetype');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3',XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'evaluation');
		$result = $result && change_field_type($table, $field);
	}
	return $result;
	
}
?>
