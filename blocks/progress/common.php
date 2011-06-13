<?php
/*

Instructions for adding new modules so they can be monitored
================================================================================
Activies that can be monitored (all resources are treated together) are defined in the $modules array.
Modules can be added with:
 - defaultTime (deadline from module if applicable),
 - actions (array if action-query pairs) and
 - defaultAction (selected by default in config page and needed for backwards compatability)
The module name needs to be the same as the table name for module in the database
Queries need to produce at least one result for completeness to go green, ie there is a record in the DB that indicates the user's completion
Queries may include the following terms that are substituted before the query is run:
 - #EVENTID# (the id of the activity/resource in the DB table that relates to it, eg., an assignment id)
 - #CMID# (the course module id that uniquely identifies the instance of the module within the course),
 - #USERID# (the current user's id) and
 - $COURSEID# (the current course id)
When you add a new module, you need to add a translation for it in the lang files.
If you add new action names, you need to add a translation for these in the lang files.

If you have added a new module to this array and think other's may benefit from the query you have created, please share it by sending it to deraadt@usq.edu.au

*/

$modules = array(
    'assignment' => array(
        'defaultTime'=>'timedue',
        'actions'=>array(
            'submitted'    => 'SELECT id FROM '.$CFG->prefix.'assignment_submissions WHERE assignment=\'#EVENTID#\' AND userid=\'#USERID#\'',
            'marked'       => 'SELECT id FROM '.$CFG->prefix.'assignment_submissions WHERE assignment=\'#EVENTID#\' AND userid=\'#USERID#\' AND grade!=\'-1\''
        ),
        'defaultAction' => 'submitted'
    ),
	'book' => array(
        'actions'=>array(
            'viewed'       => 'SELECT id FROM '.$CFG->prefix.'log WHERE course=\'#COURSEID#\' AND module=\'book\' AND action=\'view\' AND cmid=\'#CMID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'viewed'
    ),
    'certificate' => array(
        'actions'=>array(
            'awarded'    => 'SELECT id FROM '.$CFG->prefix.'certificate_issues WHERE certificateid=\'#EVENTID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'awarded'
    ),
    'chat' => array(
        'actions'=>array(
            'posted_to'    => 'SELECT id FROM '.$CFG->prefix.'chat_messages WHERE chatid=\'#EVENTID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'posted_to'
    ),
    'choice' => array(
        'defaultTime'=>'timeclose',
        'actions'=>array(
            'answered'     => 'SELECT id FROM '.$CFG->prefix.'choice_answers WHERE choiceid=\'#EVENTID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'answered'
    ),
    'data' => array(
        'defaultTime'=>'timeviewto',
        'actions'=>array(
            'viewed'       => 'SELECT id FROM '.$CFG->prefix.'log WHERE course=\'#COURSEID#\' AND module=\'data\' AND action=\'view\' AND cmid=\'#CMID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'viewed'
    ),
    'feedback' => array(
        'defaultTime'=>'timeclose',
        'actions'=>array(
            'responded_to' => 'SELECT id FROM '.$CFG->prefix.'feedback_completed WHERE feedback=\'#EVENTID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'responded_to'
    ),
    'flashcardtrainer' => array(
        'actions'=>array(
            'viewed' => 'SELECT id FROM '.$CFG->prefix.'log WHERE course=\'#COURSEID#\' AND module=\'flashcardtrainer\' AND action=\'view\' AND cmid=\'#CMID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'viewed'
    ),       
    'forum' => array(
        'defaultTime'=>'assesstimefinish',
        'actions'=>array(
            'posted_to'    => 'SELECT id FROM '.$CFG->prefix.'forum_posts WHERE userid=\'#USERID#\' AND discussion IN (SELECT id FROM '.$CFG->prefix.'forum_discussions WHERE forum=\'#EVENTID#\')'
        ),
        'defaultAction' => 'posted_to'
    ),
    'glossary' => array(
        'actions'=>array(
            'viewed'       => 'SELECT id FROM '.$CFG->prefix.'log WHERE course=\'#COURSEID#\' AND module=\'glossary\' AND action=\'view\' AND cmid=\'#CMID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'viewed'
    ),
	'hotpot' => array(
		    'defaultTime'=>'timeclose',
			'actions'=>array(
            'attempted'    => 'SELECT id FROM '.$CFG->prefix.'hotpot_attempts WHERE hotpot=\'#EVENTID#\' AND userid=\'#USERID#\'',
            'finished'     => 'SELECT id FROM '.$CFG->prefix.'hotpot_attempts WHERE hotpot=\'#EVENTID#\' AND userid=\'#USERID#\' AND timefinish!=\'0\'',
        ),
        'defaultAction' => 'finished'
    ),
    'journal' => array(
        'actions'=>array(
            'posted_to'    => 'SELECT id FROM '.$CFG->prefix.'journal_entries WHERE journal=\'#EVENTID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'posted_to'
    ),
    'lesson' => array(
        'defaultTime'=>'deadline',
        'actions'=>array(
            'attempted'    => 'SELECT id FROM '.$CFG->prefix.'lesson_attempts WHERE lessonid=\'#EVENTID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'attempted'
    ),
    'quiz' => array(
        'defaultTime'=>'timeclose',
        'actions'=>array(
            'attempted'    => 'SELECT id FROM '.$CFG->prefix.'quiz_attempts WHERE quiz=\'#EVENTID#\' AND userid=\'#USERID#\'',
            'finished'     => 'SELECT id FROM '.$CFG->prefix.'quiz_attempts WHERE quiz=\'#EVENTID#\' AND userid=\'#USERID#\' AND timefinish!=\'0\'',
            'graded'       => 'SELECT id FROM '.$CFG->prefix.'quiz_grades WHERE quiz=\'#EVENTID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'finished'
    ),
    'resource' => array(
        'actions'=>array(
            'viewed'       => 'SELECT id FROM '.$CFG->prefix.'log WHERE course=\'#COURSEID#\' AND module=\'resource\' AND action=\'view\' AND cmid=\'#CMID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'viewed'
    ),
    'scorm' => array(
        'actions'=>array(
            'attempted'    => 'SELECT id FROM '.$CFG->prefix.'scorm_scoes_track WHERE scormid=\'#EVENTID#\' AND userid=\'#USERID#\'',
            'completed'    => 'SELECT id FROM '.$CFG->prefix.'scorm_scoes_track WHERE scormid=\'#EVENTID#\' AND userid=\'#USERID#\' AND (element=\'cmi.core.lesson_status\' OR  element=\'cmi.completion_status\')  AND value=\'completed\'',
            'passed'       => 'SELECT id FROM '.$CFG->prefix.'scorm_scoes_track WHERE scormid=\'#EVENTID#\' AND userid=\'#USERID#\' AND (element=\'cmi.core.lesson_status\' OR  element=\'cmi.completion_status\') AND value=\'passed\''
        ),
        'defaultAction' => 'attempted'
    ),
    'wiki' => array(
        'actions'=>array(
            'viewed'       => 'SELECT id FROM '.$CFG->prefix.'log WHERE course=\'#COURSEID#\' AND module=\'wiki\' AND action=\'view\' AND cmid=\'#CMID#\' AND userid=\'#USERID#\''
        ),
        'defaultAction' => 'viewed'
    )
);

// Types of resources that can be monitored
$resourcesMonitorable = array (
    'directory',
    'text',
    'html',
    'file' // or link
);

// Default colours that can be overridden at the site level
$defaultColours = array(
    'attempted'=>'#33CC00',
    'notAttempted'=>'#FF3300',
    'futureNotAttempted'=>'#3366FF'
);

?>