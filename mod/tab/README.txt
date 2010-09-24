TAB MODULE
-----------------------------

The tab module was built using the new yui (The Yahoo! User Interface Library) supplied with
Moodle version 1.9+. It came from the need to have a tab interface to display notes, lectures,
discussions and other material. In order to make the most out of this module, we activate the
following filters:
	Database Auto-linking,
	Glossary Auto-linking,
	Resource Names Auto-linking,
	Wiki Page Auto-linking,
	Activity Names Auto-linking,
	Multimedia Plugins

With these filters activated, we can hide all of our activities, resources etc, and link to them
with their name within the tab content. We find that it makes for a visually cleaner course.

INSTALLING THE TAB MODULE
-----------------------------

1. To install, simply extract the contents of the zip file into the mod folder. mod folder
   can be found in the moodle root installation. If you do not have access, or do not know where
   this folder is, ask your system administrator.
2. Login as administrator and go to notifications. Voila, installed.

SPECIAL "DISPLAY ON COURSE's FRONT PAGE" setting
------------------------------------------------

open moodle/course/lib.php

before the line:

    if ($mod->modname == "label") {

add:
    // Special Display of TAB module on fronpage of course (if enabled)
    if ($mod->modname=="tab") {
        include($CFG->dirroot.'/mod/tab/tablib.php');
    }

USING THE TAB MODULE
-----------------------------
1. Turn editing mode on.
2. Select "Tab display" in the add resource menu
3. Enter a name for the module. example Chapter 1 or Module 1
4. You must enter at least one tab name and tab content.
5. You can add, by default 4 tabs. Just enter the appropriate data in each field
6. If you need more tabs, click on the "Show advanced" button. You will then be able to add
   another 4 tabs. Note if you need more tabs, modify the code in mod_form.php,view.php, install.xml.
   THIS IS BY NO MEANS A GOOD IDEA UNLESS YOU KNOW WHAT YOU ARE DOING.
7. You will notice that you can modify the stylesheets for this module. The reason I did this is
   to grant more flexibility to all users so that the tabs can be visually configured each individuals
   needs. Let's face it, we do not all use the same theme. AGAIN MODIFY AT YOUR OWN RISK. I STRONGLY SUGGEST
   COPYING AND PASTING THE DEFAULT STYLES TO AN OUTSIDE TEXT DOCUMENT.
8. You will notice a Display menu option. If selected, this adds a menu to the left of the tabs, of all Tab
   activities within your course. Here is an example of how we use this menu.
   Each tab activity is its own chapter or module. So every tab activity within the course is named
   in the following manner:
		Chapter 1
		Chapter 2
		Chapter 3
		Chapter 4
		Chapter 5 and so on.
	So that the students do not have to return to the main course layout, where all activites and
	resources are viewed, they can simply click on the Chapter link in the menu to jump from one
    chapter to another.
9. Of course, you have the option to name the menu he way you want to name it.
10. Last, but not least, you have the options to save and return to course, save and display or cancel.

Enjoy!

Patrick Thibaudeau
IT Team lead
Campus Saint-Jean
University of Alberta
patrick.thibaudeau@ualberta.ca

NOTE: Works with Moodle version 1.9 only. If you need to make it work in a Moodle version earlier than 1.9,
I strongly suggest that you upgrade. If that is not an option, I suppose you can download the yui code
from http://developer.yahoo.com/yui/ and extract the build folder into your Moodle lib/yui folder. You will
have to create the yui folder first. Now, I'm only speculating (and by speculating, I have just caused the
oil prices to rise again ;-)). I HAVE NOT TRIED THIS. I CANNOT QUARANTEE THAT THIS WILL WORK. But at least,
it's worth a try.



