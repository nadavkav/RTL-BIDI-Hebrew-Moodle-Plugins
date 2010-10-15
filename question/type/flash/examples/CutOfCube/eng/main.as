/***************************************************
 * This file is a example of "Flash" question type
 * for LMS Moodle.
 *
 * @author Petrov Aleksandr, Russia, Novosibirsk, 2009 (flash movie)
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009 (Moodle integration)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

#include "locallib.as"

/************************************************************************
 *  Including the library for integrating with Moodle (MoodleIntegration)
 ************************************************************************/
#include "moodleEvents.as"
//***********************************************************************

/************************************************************************
 *  Creating FlashQuestion object initiate receiving
 *  data from Moodle (MoodleIntegration)
 ************************************************************************/
var Question:FlashQuestion = new FlashQuestion(this);
//***********************************************************************

stop();
init();


ctlBar._visible = false;
// individual
// points of right cut
var numPointsSech = 4;
for (i=1; i<=numPointsSech; i++) {
	_root.qp[i] = new Object();
}
_root.qp[1] = {x:0.0, y:70.0, z:100.0, xp:0, yp:0};
_root.qp[2] = {x:0.0, y:40.0, z:0.0, xp:0, yp:0};
_root.qp[3] = {x:100.0, y:10.0, z:0.0, xp:0, yp:0};
_root.qp[4] = {x:100.0, y:40.0, z:100.0, xp:0, yp:0};

// count of points in begining
var numPoints = 11;
var oldNumPoints = numPoints+1;
var p1 = {x:0.0, y:100.0, z:100.0, xp:0, yp:0, tt:""};
var p2 = {x:0.0, y:0.0, z:100.0, xp:0, yp:0, tt:""};
var p3 = {x:100.0, y:0.0, z:100.0, xp:0, yp:0, tt:""};
var p4 = {x:100.0, y:100.0, z:100.0, xp:0, yp:0, tt:""};
var p5 = {x:0.0, y:100.0, z:0.0, xp:0, yp:0, tt:""};
var p6 = {x:0.0, y:0.0, z:0.0, xp:0, yp:0, tt:""};
var p7 = {x:100.0, y:0.0, z:0.0, xp:0, yp:0, tt:""};
var p8 = {x:100.0, y:100.0, z:0.0, xp:0, yp:0, tt:""};
var p9 = {x:0.0, y:70.0, z:100.0, xp:0, yp:0, tt:"A"};
var p10 = {x:0.0, y:40.0, z:0.0, xp:0, yp:0, tt:"B"};
var p11 = {x:100.0, y:40.0, z:100.0, xp:0, yp:0, tt:"C"};
for (i=1; i<=numPoints; i++) {
	_root.attachMovie("point", "point"+i, 100+i);
	points[i] = _root["point"+i];
	points[i].x = _root["p"+i].x;
	points[i].y = _root["p"+i].y;
	points[i].z = _root["p"+i].z;
	points[i].t.text = _root["p"+i].tt;
	points[i] = calc(points[i]);
}
// count of lines
var numLines = 12;
var oldNumLines = numLines+1;
lines[1] = {beg:1, end:2};
lines[2] = {beg:2, end:3};
lines[3] = {beg:3, end:4};
lines[4] = {beg:4, end:1};
lines[5] = {beg:5, end:6, t:"n"};
lines[6] = {beg:6, end:7, t:"n"};
lines[7] = {beg:7, end:8};
lines[8] = {beg:8, end:5};
lines[9] = {beg:1, end:5};
lines[10] = {beg:2, end:6, t:"n"};
lines[11] = {beg:3, end:7};
lines[12] = {beg:4, end:8};
// end individual

/*****************************************************************************
 *  Initialization of question cause events (MoodleIntegration).
 *  Hereto moment all own variable of flash movie 
 *  should be initialized because it will possible need
 *  to display student's response.
 *****************************************************************************/
Question.Init();
//****************************************************************************

drawAll(numPoints, numLines);


function prepareStep(s) {
	uPoints = 0;
	uLines = 0;
	for (var j = 0; j<_root.userActs.length-s; j++) {
		switch (_root.userActs[j][0]) {
			case "point": 
				uPoints++;
				break;
			case "line" :
				uLines++;
				break;
		}
	}	
}

ctlBar.btRewind.onRelease = function() {
	step = _root.userActs.length;
	drawAll(oldNumPoints-1, oldNumLines-1);
	trace("rewind");
}

ctlBar.btBack.onRelease = function() {
	if (step < _root.userActs.length) {
		step++;
	}
	prepareStep(step);
	drawAll(oldNumPoints-1+uPoints, oldNumLines-1+uLines);
	trace("back");
}

ctlBar.btForward.onRelease = function() {
	if (step > 0) {
		step--;
	}
	prepareStep(step);
	drawAll(oldNumPoints-1+uPoints, oldNumLines-1+uLines);
	trace("forward");
}


