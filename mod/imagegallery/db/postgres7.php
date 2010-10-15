<?php // $Id: postgres7.php,v 1.5 2006/10/17 10:24:47 janne Exp $

function imagegallery_upgrade($oldversion) {
/// This function does anything necessary to upgrade
/// older versions to match current functionality

    global $CFG;

    if ( $oldversion < 2006060701 ) {

        $sql  = "ALTER TABLE {$CFG->prefix}imagegallery ADD COLUMN requirelogin CHAR(1)";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN requirelogin SET DEFAULT '0'";
        execute_sql($sql);

        $sql = "UPDATE TABLE {$CFG->prefix}imagegallery SET requirelogin = '0' WHERE requirelogin IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN requirelogin SET NOT NULL";
        execute_sql($sql);

    }

    if ( $oldversion < 2006082801 ) {

        $images = get_records_sql("SELECT id, path FROM {$CFG->prefix}imagegallery_images");

        if ( !empty($images) ) {
            foreach ( $images as $image ) {
                $image->path = str_replace($CFG->dataroot, "", $image->path);
                $image->path = str_replace(addslashes($CFG->dataroot), "", $image->path);

                set_field("imagegallery_images", "path", $image->path, "id", $image->id);
            }
        }

        $sql  = "ALTER TABLE {$CFG->prefix}imagegallery ADD COLUMN resize CHAR(1)";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN resize SET DEFAULT '0'";
        execute_sql($sql);

        $sql = "UPDATE TABLE {$CFG->prefix}imagegallery SET resize = '0' WHERE resize IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN resize SET NOT NULL";
        execute_sql($sql);
    }

    if ( $oldversion < 2006082802 ) {
        $sql  = "ALTER TABLE {$CFG->prefix}imagegallery ADD COLUMN defaultcategory SMALLINT";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN defaultcategory SET DEFAULT '0'";
        execute_sql($sql);

        $sql = "UPDATE TABLE {$CFG->prefix}imagegallery SET defaultcategory = '0' WHERE defaultcategory IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN defaultcategory SET NOT NULL";
        execute_sql($sql);
    }

    if ( $oldversion < 2006101001 ) {
        // Once again I fu.. it up! And again I'm forced to clean
        // up my on soiled self :-(

        $imagagallerycols = array('requirelogin', 'resize', 'defaultcategory');

        $metacols = $db->MetaColumnNames($CFG->prefix.'imagegallery');
        $missing = array();

        foreach ( $imagegallerycols as $column ) {
            if ( !in_array($column, $metacols) ) {
                array_push($missing, $column);
            }
        }

        if ( !empty($missing) ) {
            foreach ( $missing as $absent ) {
                switch ( $absent ) {
                    case 'requirelogin':
                        $sql  = "ALTER TABLE {$CFG->prefix}imagegallery ADD COLUMN requirelogin CHAR(1)";
                        execute_sql($sql);

                        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN requirelogin SET DEFAULT '0'";
                        execute_sql($sql);

                        $sql = "UPDATE TABLE {$CFG->prefix}imagegallery SET requirelogin = '0' WHERE requirelogin IS NULL";
                        execute_sql($sql);

                        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN requirelogin SET NOT NULL";
                        execute_sql($sql);
                    break;
                    case 'resize':
                        $sql  = "ALTER TABLE {$CFG->prefix}imagegallery ADD COLUMN resize CHAR(1)";
                        execute_sql($sql);

                        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN resize SET DEFAULT '0'";
                        execute_sql($sql);

                        $sql = "UPDATE TABLE {$CFG->prefix}imagegallery SET resize = '0' WHERE resize IS NULL";
                        execute_sql($sql);

                        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN resize SET NOT NULL";
                        execute_sql($sql);
                    break;
                    case 'defaultcategory':
                        $sql  = "ALTER TABLE {$CFG->prefix}imagegallery ADD COLUMN defaultcategory SMALLINT";
                        execute_sql($sql);

                        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN defaultcategory SET DEFAULT '0'";
                        execute_sql($sql);

                        $sql = "UPDATE TABLE {$CFG->prefix}imagegallery SET defaultcategory = '0' WHERE defaultcategory IS NULL";
                        execute_sql($sql);

                        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN defaultcategory SET NOT NULL";
                        execute_sql($sql);
                    break;
                }
            }
        }

        $images = get_records_sql("SELECT ig.id, ig.categoryid, ig.path, igc.realname
                                   FROM
                                        {$CFG->prefix}imagegallery_images ig
                                   LEFT JOIN
                                        {$CFG->prefix}imagegallery_categories igc
                                   ON igc.id = ig.categoryid");

        if ( !empty($images) ) {
            foreach ( $images as $image ) {
                $newpath = '';
                $newpath = str_replace($CFG->dataroot, "", $image->path);
                $newpath = str_replace(addslashes($CFG->dataroot), "", $newpath);
                $newpath = str_replace($image->realname, $image->categoryid, $newpath);

                if ( !preg_match("/^\//", $newpath) ) {
                    $newpath = '/'. $newpath;
                }

                if ( $newpath !== $image->path ) {
                    set_field("imagegallery_images", "path", $newpath, "id", $image->id);
                    $oldpath = dirname($CFG->dataroot . $image->path);
                    $newpath = dirname($CFG->dataroot . $newpath);
                    if ( !@file_exists($newpath) ) {
                        if ( !@rename($oldpath, $newpath) ) {
                            echo '<div style="text-align: center; color: red;">';
                            echo '<strong>Could not rename '. $oldpath .' to '. $newpath;
                            echo '</strong></div>';
                        }
                    }
                }
            }
        }

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery_categories DROP COLUMN realname";
        execute_sql($sql);

    }

    if ( $oldversion < 2006101002 ) {
        $sql  = "ALTER TABLE {$CFG->prefix}imagegallery ADD COLUMN shadow CHAR(1)";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN shadow SET DEFAULT '0'";
        execute_sql($sql);

        $sql = "UPDATE TABLE {$CFG->prefix}imagegallery SET shadow = '0' WHERE shadow IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}imagegallery ALTER COLUMN shadow SET NOT NULL";
        execute_sql($sql);
    }

    return true;
}

?>