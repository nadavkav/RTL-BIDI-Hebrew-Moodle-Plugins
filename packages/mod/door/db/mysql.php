<?php // $Id: mysql.php,v 1.21.2.1 2005/05/31 23:02:44 moodler Exp $
function door_upgrade($oldversion) {
	global $CFG;
    
    if ($oldversion < 2007102900) {
        // add authentication table
        execute_sql("
            CREATE TABLE IF NOT EXISTS `{$CFG->prefix}door_repository_authentications` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `name` varchar(255) NOT NULL,
                PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0
        ");
        // insert the 3 supported authentications
        execute_sql("INSERT INTO `{$CFG->prefix}door_repository_authentications` (`id` ,`name`) VALUES (NULL , 'all')");
        execute_sql("INSERT INTO `{$CFG->prefix}door_repository_authentications` (`id` ,`name`) VALUES (NULL , 'local')");
        execute_sql("INSERT INTO `{$CFG->prefix}door_repository_authentications` (`id` ,`name`) VALUES (NULL , 'shibboleth')");
        // change authentication name (normal to local)
        execute_sql("UPDATE `{$CFG->prefix}door_repository` SET `{$CFG->prefix}door_repository`.`type` = 'local' WHERE `{$CFG->prefix}door_repository`.`type` = 'normal'");
        // update field in the table
        execute_sql("UPDATE `{$CFG->prefix}door_repository` SET `{$CFG->prefix}door_repository`.`type` = (SELECT `id` FROM `{$CFG->prefix}door_repository_authentications` WHERE `{$CFG->prefix}door_repository_authentications`.`name` = `{$CFG->prefix}door_repository`.`type`)");
        execute_sql("ALTER TABLE `{$CFG->prefix}door_repository` CHANGE `type` `authentication` INT( 11 ) NOT NULL");   
    }
    
    if ($oldversion < 2008072500) {
        // add authentication table
        execute_sql("ALTER TABLE `mdl_door_export` CHANGE `sesskey` `token` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");  
    }

    return true;
}
?>