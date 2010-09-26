                 Moodle drag and drop Quiz Question type


**************
Abstract
**************

This question type was initially written by Brian King from Mediagonal
It was ported to Moodle 1.6 by Gustav Delius and maintained by 
Jean-Michel Védrine.
In June 2008 it was rewritten by Harry Winkelmann with the primary goal 
to port it from the Walter Zorn Drag&drop javascript library to the yui 
library used in all other question types.
But this rewrite also added some other interesting functions and solved 
some problems of the initial version.
See changelog.txt for a detailed list of changes.
Latest version is available from Moodle Modules and plugins section.
A short tutorial is available here : 
http://docs.moodle.org/en/Drag_and_Drop_question_tutorial


******************
Installation
******************

Begining with this version it is no more necessary to edit Moodle core 
files to install this question type
So the folowing instructions should apply to dragdrop as to any new 
quiz question type you want to install on your Moodle server :

- Copy all the dragdrop folder inside your question/type/ subdirectory 
  on your Moodle serveer
- Log in as admin and visit Administration to create the dragdrop tables.

The only thing specific to dragdrop question type is that it needs some 
images to works so if you haven't already done it,
add some image files to your course that you want to use for drag and 
drop questions.
After that you should be able to add a drag and drop question to the new 
quiz. 


*************************************
Upgrading from previous versions
*************************************

As said above it is no more necessary to edit core Moodle files to 
install this question type so users having installed
a previous version should revert the files :
lib/questionlib.php
mod/quiz/attempt.php,
mod/quiz/review.php,
question/preview.php,
mod/quiz/reviewquestion.php,
to their original state.

After that, delete the content of the /questions/type/dragdrop 
subdirectory and proceed as it was a new installation.
All your existing dragdrop questions will be upgraded when you visit 
administration.
