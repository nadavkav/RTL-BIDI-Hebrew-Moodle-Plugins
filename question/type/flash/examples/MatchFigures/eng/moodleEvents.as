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
import flash.geom.ColorTransform;
import FlashQuestion;


// This function used for prepare data to send to Moodle.
// Information about response should be stored in Question object.
// Directly sending of the information is carried out
// by means of method Send() the given object.
function sendUserAnswer():Void {
	// Calculate grade for this attempt (рациональное число от 0 до 1)
	Question.Grade = calculateScore(userActs);
	if (Question.Grade == 1) {
		// Describing the grade (need to group attempts in Analysis Table)
		Question.Description = "Right";
	} else if (Question.Grade == 0) {
		Question.Description = "Wrong";
	} else {
		Question.Description = "Partly Right";
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
	for (index in _root.userActs) {
		var dnd = eval(index);
		var target = eval(_root.userActs[index]);
		dnd._x = target._x;
		dnd._y = target._y;
	}
}	


// The given event notifies that it is necessary to display a right answer. 
// Pay attention that the given event can occur after event 
// flashRestoreFromData, thus the answer of the pupil and a right answer 
// should be displayed together and so that it was clear where what of them.
function flashFillCorrectAnswers():Void {
	// Displaying right cut of cube using green color
	for (index in _root.matching) {
		var obj = eval(_root.matching[index]);
		var newobj = obj.duplicateMovieClip("r_"+index, getNextHighestDepth(), {_x: obj._x, _y:-100});
		newobj._xscale = 70;
		newobj._yscale = 70;
		newobj.transform.colorTransform = new ColorTransform(0, 0, 0, 0, 0, 200, 0, 255);
		newobj._y = obj._y - 65;
	}

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
	for (index in matching) {
		_root[index].onPress = null;
		_root[index].onRelease = null;
	}
}


// The given event notifies on that, the test works in an adaptive mode. 
// Usually the flash-roller should not react to it since for storing 
// of attempts, charge of penalties, etc. answers Moodle system.
function flashAdaptiveMode():Void {
	// There is no need to anything do here.
}
