// Readme.txt for Myfiles beta release
// Michael Avelar
// 1.26.06

// This is just a quick informational file to temporarily replace the built-in help features 
// that I am currently working on

// Zipping info
	- To unzip a file, click on the 'edit' action button and click unzip.  You can also upload 
	  a previously zipped file and unzip through the myfiles api, which will create entries for 
	  you automatically! (Be sure to read the known_issues file before messing with zipped files!)

------------------------------------------------------

// Readme.txt for Myfiles beta release
// Michael Daudignon & Romain Lombardo & Valery Fremaux
// 1.30

// NEW !!
- Implementation respects the standard API of Moodle 1.9

- Found bugs were fixed

- Help files are now available in French and English

- Feedback messages were implemented for almost each action

- Groups implementation
The block file manager implements functionalities based on groups, so each user that is member of a group get a shared folder for the group. All members of this group have the write/read access to this folder.
Depending on the groupmode of the course, users can see either no folder of groups (no groups), or only folders of groups to which they belong (separate groups), or all folders of all groups of the course, but have read-only access to folders' groups to which they do not belong (visible groups).


// Configuration
After installation, please note that the default size of uploads is 0 MB. You should correctly configure this size depending on your own use of this block.