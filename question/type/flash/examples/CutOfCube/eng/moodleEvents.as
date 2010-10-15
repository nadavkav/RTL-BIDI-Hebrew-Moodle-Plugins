/***************************************************
 * This file is a part of "Flash" question interface
 * for LMS Moodle. 
 * Use this file for react on events and create 
 * interface for your own flash movie.
 *
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/
import FlashQuestion;


// Additional variables which needs to store student's actions
var userActs = new Array();
var step = 0;
var uPoints = 0;
var uLines = 0;


// This function used for prepare data to send to Moodle.
// Information about response should be stored in Question object.
// Directly sending of the information is carried out
// by means of method Send() the given object.
function sendUserAnswer():Void {
	answer = true;
	pi1 = 0;
	pi2 = 0;
	pi3 = 0;
	pi4 = 0;
	pi5 = 0;
	pi6 = 0;
	// search three points
	for (i=oldNumPoints; i<=numPoints; i++) {
		if (eqv(_root.points[i].x, 100) and eqv(_root.points[i].y, 10) and eqv(_root.points[i].z, 0) and pi1 == 0) {
			pi1 = i;
		}
	}
	if (pi1 == 0) {
		answer = false;
	}
	// Calculate grade for this attempt (рациональное число от 0 до 1)
	if (answer) {
		Question.Grade = 1;
		// Describing the grade (need to group attempts in Analysis Table)
		Question.Description = "Right";
	} else {
		Question.Grade = 0;
		Question.Description = "Wrong";
	}

	// We need to save currrent state of flash-movie. It allow user to 
        // contunue this attempt or allow teacher to see student's answer.
	// The object may be arbitrarily complex structure.
	myData = new Object;
	myData.history = userActs;
	//myData.mydata = "Any additional data";
	
	// Place data in Question object
	Question.FlashData = myData;
	
	// Send the data to Moodle
	Question.Send();
}

// The given event notifies that it is necessary to restore 
// a condition of a flash-movie from kept before the data. 
// It is necessary for display an attempt of the student for 
// the teacher or the student.
function flashRestoreFromData(flashData:Object):Void {
	if (flashData.history != undefined) {
		_root.userActs = flashData.history;
	}
	// Restore all actions made by student
	for (var j = 0; j<_root.userActs.length; j++) {
		switch (_root.userActs[j][0]) {
			case "point": 
				numPoints++;
				var p = _root.userActs[j][1];
				attachMovie("point", "point"+numPoints, 100+numPoints, {_x:-100, _y:-100});
				points[numPoints] = _root["point"+numPoints];
				points[numPoints].x = p.x;
				points[numPoints].y = p.y;
				points[numPoints].z = p.z;
				points[numPoints] = calc(points[numPoints]);
				break;
			case "line" :
				numLines++;
				lines[numLines] = _root.userActs[j][1];
				break;
		}
	}
	step = 0;
	drawAll(numPoints, numLines);
}	


// The given event notifies that it is necessary to display a right answer. 
// Pay attention that the given event can occur after event 
// flashRestoreFromData, thus the answer of the pupil and a right answer 
// should be displayed together and so that it was clear where what of them.
function flashFillCorrectAnswers():Void {
	// Displaying right cut of cube using green color
	_root.createEmptyMovieClip("grad", 16000);
	drawSech();
}


// The given event notifies that it is necessary to prevent possibility 
// of change by the user of a condition of a flash-roller. It is necessary 
// to block whenever possible all elements of interface. 
// Pay attention that the given event can occur after events 
// flashRestoreFromData and flashFillCorrectAnswers, therefore blocking 
// of controls should not prevent to display the answer of the student 
// and a right answer.
function flashReadOnly():Void {
	// Disable the mouse
	_root.onMouseUp = function() {}
	// Hide the edit panel
	_root.studentPanel._visible = false;
	// Display panel of viewing the executeded attempt
	_root.ctlBar._visible = true;
}


// The given event notifies on that, the test works in an adaptive mode. 
// Usually the flash-roller should not react to it since for storing 
// of attempts, charge of penalties, etc. answers Moodle system.
function flashAdaptiveMode():Void {
	// There is no need to anything do here.
}
