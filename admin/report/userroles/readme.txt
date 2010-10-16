== User's Roles report ==

Version for Moodle 1.9.x.

This Moodle admin report lists all the role assignments that a user has throughout
a site. It also lets you easily remove any of those role assignments.

This version was tested and works with Moodle 1.9.4, it should work with
releases 1.9.3 and later on the 1.9 stable branch, but your mileage may vary.
(The YUI JavaScript library in Moodle was updated between the 1.9.2 and 1.9.3
and there were changes that affect this report. If you need a version that
works with 1.9.0, .1 or .2, then you can probably extract one from CVS history.

=== Installation ===

In addition to the standard installation steps:
* Unzip the downloaded file.
* Put the unzipped userroles folder in the admin/report folder.

You also need to make one very small change to one core file:

In lib/weblib.php, in the function print_header, just after the line:

    $meta .= "\n".require_js();

add the lines

    $fnname = 'require_css';
    if (function_exists($fnname)) {
        $meta .= "\n".$fnname();
    } 

Written by Tim Hunt, T.J.Hunt@open.ac.uk, The Open University, with
contributions from other people. Check CVS history for full credits.
Copyright 2007-9 The contributors
Licence: http://www.gnu.org/copyleft/gpl.html GNU Public License
