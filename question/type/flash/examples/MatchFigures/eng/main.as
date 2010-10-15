/***************************************************
 * This file is a example of "Flash" question type
 * for LMS Moodle.
 *
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

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

// Visibility of "check" panel
if (Question.InQuiz) {
	pnlCheck._visible = false;
} else {
	lbMsg._visible = false;
}

var matching: Object = new Object();
matching["circleDND"] = "circleTarget";
matching["rectDND"]   = "rectTarget";
matching["starDND"]   = "starTarget";
matching["polyDND"]   = "polyTarget";

// Additional variable which needs to store student's actions
var userActs = new Object();

// Calculating scores
function calculateScore(acts) {
	var score = 0;
	for (index in acts) {
		if (_root.matching[index] == acts[index]) {
			score = score + 0.25;
		}
	}
	trace ("score: " + score);
	return score;
}

// Checking that object dropped into any of targets
function droppedInTarget(obj: Object) {
	for (index in _root.matching) {
		if (eval(obj._droptarget) == eval(_root.matching[index])) {
			return true;
		}
	}
	return false;
}

function onPressFunc() {
	trace("start drag");
	startDrag(this, false);
}

function onReleaseFunc() {
	stopDrag();
	if (droppedInTarget(this)) {
		var target = eval(this._droptarget);
		this._x = target._x;
		this._y = target._y;
		_root.userActs[this._name] = target._name;
		trace("in target");
	} else {
		_root.userActs[this._name] = undefined;
		trace("wrong " + eval(this._droptarget) + ":" + this._name);
	}
	sendUserAnswer(); //MoodleIntegration
}

circleDND.onPress = onPressFunc;
circleDND.onRelease = onReleaseFunc;
rectDND.onPress = onPressFunc;
rectDND.onRelease = onReleaseFunc;
starDND.onPress = onPressFunc;
starDND.onRelease = onReleaseFunc;
polyDND.onPress = onPressFunc;
polyDND.onRelease = onReleaseFunc;

function onCheckClick() {
	trace("clicked");
	pnlCheck.txtMessage.text = "Your grade is " + calculateScore(userActs)*100 + "%";
}

pnlCheck.btCheck.onRelease = onCheckClick;

/*****************************************************************************
 *  Initialization of question cause events (MoodleIntegration).
 *  Hereto moment all own variable of flash movie 
 *  should be initialized because it will possible need
 *  to display student's response.
 *****************************************************************************/
Question.Init();
//****************************************************************************

