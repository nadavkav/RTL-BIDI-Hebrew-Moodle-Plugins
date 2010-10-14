Installation
============

1. Drop this module into the blocks directory
2. Go to the main admin notifications page, Moodle will install the block
3. Go into a class and add the “Course Menu” block

Upgrades
========

Those who are upgrading from the old course_menu block need to follow a few
steps. First, go to the blocks directory and remove course_menu (don’t do
the “Delete” from the Moodle admin page though). Then drop this new module
into the blocks directory.

After that, you need to the script to do migration. It’s called
`course_menu_migrate.php`  and it’s in the `blocks/yui_menu/db` directory.
If you have access to the command line, you can just run the the program
using `php course_menu_migrate.php`. This will convert any of the old
menus to the new ones and remove the old block from the database.

If you only have access via FTP or something you can run the script
from your web browser by temporarily commenting out a line. Instructions
are in the file. You should also drop your web host as soon as possible.
FTP is *really bad* for security, you’d probably be safer yelling out
your passwords in the middle of Times Square.

Customisation
=============

Plugins can be added to the plugin directory. See the other files in
there as examples.

License
=======

See COPYING.txt
