File Response Question
----------------------

Author: Adriane Boyd (adrianeboyd@gmail.com)

Description:

This question type allows students to submit a file in response to a 
question.

The teacher can specify the upload limit for the file (up to the course 
maximum upload limit) and choose whether the student is also given an 
essay field as part of their response.

The student submits a file and optionally an accompanying essay.  For each 
question the student is only allow to submit one file.  If a second file 
is submitted, the first file is overwritten.  The student is always given 
a link to the most recently submitted file, so he/she can review the 
response before final submission.  The student cannot delete the currently 
submitted file, but can overwrite it by uploading another file.

The file is uploaded to the directory:
$CFG->dirroot/questionattempt/attempt#/question#

Grading:

The question is graded manually.  The teacher sees a link to the submitted 
file and the accompanying essay.

Note about question preview mode:

In question preview mode, the previously submitted file is not deleted 
when you click "Start again".  This is due to differences between question 
preview mode and quiz mode.  Uploading another file will replace the 
currently submitted file, but it's not possible to delete the currently 
submitted file except by manually deleting the file from the data 
directory.

In quiz preview and normal quiz mode, "Start again" works as you would 
expect.  It deletes the previously submtitted file and restores the 
question to its initial state.

Warning about backup/restore:

A backup/restore that backs up the submitted files has not been developed 
yet.  When 1.9 nears release, it will be written and the question type 
will be updated.
