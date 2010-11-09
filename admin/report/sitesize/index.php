<?php

/*
get_directory_size() is a Moodle function that wraps over the system binary 'du' if it exists, or it defaults to the portable 'filesize()' PHP function which is a fair bit slower.

*/

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/backup/lib.php');
$adminroot = admin_get_root();
admin_externalpage_setup('reportsitesize', $adminroot); // 'report' prepended to the name of the string you will put into admin.php lang file
admin_externalpage_print_header($adminroot);

require_login();
if (!isadmin()) {
        error("Admin only report");
}

$dir = $CFG->dataroot;
$handle = opendir($dir);
if ($handle) {
    chdir($dir);
    echo "<html>\n<body>\n";
    echo "<table border=\"1\">";
    echo "<tr>";
    echo "<th>Backupdata Size<//th>\n<th>Total site size</th>\n<th>Site<//th><th>Instructor</th<th>E-mail<//th><th>Category<//th>\n";
    echo '</tr>';
    $bdatatotal = 0;
    $sdatatotal = 0;
     while (false !== ($file = readdir($handle))) {
           $path = "$dir/$file";
           if (!(is_dir($path))) {
            continue;
           }
           if ($file == '.' || $file == '..') {
                continue;
           } // we know it's a directory now, but lets ignore non-numeric directories as course data is stored in moodledata in a directory named after its course id

           if (!is_numeric($file)) { // site files are stored in /moodledata/$courseid where $courseid is a number - we want to filter out nonsite related directories
                        continue;
                }

        if (is_dir("$path/backupdata")) {
                        $bdata = display_size(get_directory_size("$path/backupdata"));
                        $bdatatotal += (int) get_directory_size("$path/backupdata");
                }
                else {
                        $bdata = display_size((int) 0);
                        $bdatatotal += (int) 0;
                }

                if (is_dir($path)) {
                        $sdata = display_size(get_directory_size($path));
                        $sdatatotal += (int) get_directory_size($path);
                }
                else {
                        $sdata = display_size((int) 0);
                        $sdatatotal += (int) 0;
                }

        $instructors = get_records_sql("select userid from {$CFG->prefix}role_assignments where roleid=3 and contextid=(select id from {$CFG->prefix}context where contextlevel=50 and instanceid=$file)");
        if (!empty($instructors)) {
            $catid = get_record("course","id",$file); // $file is course id
            $category = get_record("course_categories","id",$catid->category);
            $parentcategory = null;
            if (isset($category->parent) && $category->parent !== "0") {
                $parentcategory = get_record("course_categories","id",$category->parent);
            }
            foreach ($instructors as $instructor) {
                $userdetail = get_record_sql("select firstname, lastname, email from {$CFG->prefix}user where id=$instructor->userid");
                if (!empty($userdetail)) {
                    echo "<tr>\n<td>$bdata</td>\n<td>$sdata</td>\n<td><a href=\"$CFG->wwwroot/course/view.php?id=$file\">$CFG->wwwroot/course/view.php?id=$file</a></td>\n";
                    echo "<td>$userdetail->lastname, $userdetail->firstname</td>\n<td><a href='mailto:$userdetail->email'>$userdetail->email</a></td>\n";
                    if (isset($category->parent) && $category->parent !== "0") {
                                                echo "<td><a href='{$CFG->wwwroot}/course/category.php?id=$parentcategory->id'>$parentcategory->name</a> - <a href='{$CFG->wwwroot}/course/category.php?id=$category->id'>$category->name</a></td></tr>\n";
                                        }
                                        else if ($file !== "1") { // $file is the string that should be a number representing the Moodle course ID
                                                echo "<td><a href='{$CFG->wwwroot}/course/category.php?id=$category->id'>$category->name</a></td></tr>\n";
                                        }
                                        else {
                                                echo "<td>&nbsp;</td></tr>\n";
                                        }
                }
            }
            //echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
        }
        else {
            echo "<tr>\n<td>$bdata</td>\n<td>$sdata</td>\n<td><a href=\"$CFG->wwwroot/course/view.php?id=$file\">$CFG->wwwroot/course/view.php?id=$file</a></td><td></td><td></td><td>&nbsp;</td></tr>\n";
        }

        //echo "</tr>";
    }
}
else {
    echo "Failed to open moodledata<br />";
    exit;
}

closedir($handle);
echo "</table>";
echo "Backupdata total: " . display_size($bdatatotal) . " <br/>";
echo "Total size of all sites: " . display_size($sdatatotal) . " <br/>";
echo "</body>\n</html>";
admin_externalpage_print_footer($adminroot);

?>