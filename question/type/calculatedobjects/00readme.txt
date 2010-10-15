Calculated Objects question type.

This a Moodle question type which extends the 'calculated' question type.
Teachers can create questions like "How much is {apples} + {oranges}?"
- where the {wildcards} become M and N x images of apples and oranges respectively. It is aimed at pre/primary-school students (age 3-9).

Note, this question type uses the database tables of the 'calculated' question type.

Tested with Moodle 1.9.7.

(Author N.D.Freear, 14 August 2010.)

Currently supported wildcards:  apple, orange, pear, pineapple, walnut, coffee, cookie (each with or without an 's', eg. {cookies} and with an optional differentiator, eg. {apples_1} + {apples_2}).

Changes, 27 August-2 September 2010:
1. Renamed language string file, for auto-include (from CONTRIB-2308).
2. Renamed help file, for auto-include.
3. Added 2 missing language strings.
4. Verified that styles.css is being auto-included.
5. Simplified install instructions.
6. Fixed missing % modulo operator bug (CONTRIB-2308)
7. Added support for 'like' objects, eg. {apples_1} + {apples_2} http://moodle.org/mod/forum/discuss.php?d=156605)
8. Added support for arbitrary single-character wildcards, eg. {apples} / {n}.
9. Support for more textual questions.
10. Improved layout/styling.

To install:
1. Download and unzip the archive. Copy the directory 'calculatedobjects' into the directory {MOODLE}/question/type/ on your server.
2. Visit the administrator 'notifications' page, http://moodle.example.org/admin/ - there are no database changes for this question type.

(Note, English language strings, help file, and styles will be auto-included.)

Upgrade.
To upgrade from previous versions:
1. Delete the 'question/type/calculatedobjects' directory.
2. Delete '{MOODLE}/lang/en_utf8/help/quiz/calculatedobjects.html
3. Follow the install instructions above.


TODO/ limitations:
* Add support for multiple operators (currently only 1 supported).
* Test with browsers.
* Test with Moodle 2 beta.
* More testing of backup and restore.
* Evaluate ereg and preg* calls.
* Work on validation functions (qtype_calculatedobjects_find_formula_errors).
* Tidy up.
* If there's demand, add ability to use custom icons/images.
* If there's demand, translation.


Acknowledgements:
- images sourced from Wikimedia:
* http://commons.wikimedia.org/wiki/Category:Food_and_drink_icons
* http://commons.wikimedia.org/wiki/File:Source_preview_FRUITS.jpg
* http://commons.wikimedia.org/wiki/File:Tulliana_cookie.png
- (icon sourced from Iconarchive):
* http://www.iconarchive.com/show/food-icons-by-aha-soft/apple-icon.html - See license.
* http://www.iconarchive.com/icons/aha-soft/food/license.txt


[End.]
