AJAX marking block

Full documentation: http://docs.moodle.org/en/Ajax_marking_block

This block displays all of your marking in one place and allows you to grade the work in single-student 
pop ups without leaving the page. It is most useful as a front page block, but works just as well
in a course, although the pieces of work on display will still be from the whole site, not just that couse.

The block displays grading in a tree structure in the form of Course -> Assessment item -> Student.
There are exceptions for some assessment types as their structure needs extra levels e.g. quizzes:
Course -> Quiz -> Question -> Student.

There is an option to enable 'display by group' for each individual assessment. This will add an extra 
level: Course -> Assessment -> GROUP -> Student, with the option to choose which groups to show or hide.
To enable this, click on the 'Configure' link at the bottom of the block to open a settings pop up. 
The tree in the pop up shows all of the assessment items you have permission to grade and you can 
set you personal display preferences by clicking on the name of the assesment. Changes are saved instantly 
when you select any option, and you just need to close the settings pop up when you are done.

Currently supported types:

Assignment
Forum
Quiz
Workshop
Journal


