/***************************************************
 * PLEASE, DON'T CHANGE THIS FILE!
 ***************************************************
 * This file is a part of "Flash" question interface
 * for LMS Moodle.
 *
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

import flash.external.*;
import JSON; //the serializer class

//
// Properties of Question object:
//        Grade: Number        - (read/write) Grade of current attempt (rational number from 0 to 1)
//        Description: String  - (read/write) Textual description of grade. This description is going to use
//                               to group user's attempt in Item Analysis Table.
//                               One description may used for different grades. 
//                               For example, "Right", "Partially right", "Wrong".
//        FlashData: Object    - (read/write) State of flash-movie in this attempt, saved in a database.
//                               Tests in system Moodle allow the student to keep the current 
//                               form of the answer and to specify it in the future.
//                               By means of the given property possibility to restore a condition 
//                               of a movie at the moment of preservation by its student should be realised.
//                               It also is used to show to the teacher the response of the student.
//                               The object may be arbitrarily complex structure.
//        OptionalFile: String - (read) The URL to file which you choose during question creation.
//        OptionalData: String - (read) Any textual data which you add during question creation.
//        InQuiz: Boolean      - (read) Allow you to check where this movie are using now (in Quiz or not). 
//                               For example, you may display the button "Check answer" and show grade 
//                               right in movie if it opened not from Moodle's quiz.
//
// Methods of Question object:
//        function Init(): Void - Initialize the object. It will cause events.
//                                When you'll call this method all own variable of flash movie 
//                                should be initialized because it will possible need
//                                to display student's response.
//                                in Moodle is realized the facilities of html-page.
//        function Send(): Void - Send all object's data to html page. The issue of information right 
//                                in Moodle is realized the facilities of html-page.

class FlashQuestion
{
	private var serializer: JSON;
	private var movie:Object;
	private var ID:String = "";
	var Grade:Number = 0;
	var Description:String = "";
	var FlashData:Object = "";
	var OptionalFile: String = "";
	var OptionalData: String = "";
	var AdaptiveMode:Boolean = false;
	var FillCorrect:Boolean = false;
	var ReadOnly:Boolean = false;
	var InQuiz:Boolean = false;
	
	// Constuctor
	// Initialize object with data from Moodle
	function FlashQuestion(instance:Object)
	{
		serializer = new JSON();
		movie = instance;
		
		ID = _root.qID;
		Grade = _root.qGr;
		Description = _root.qDesc.split("#apstr;").join("'");
		OptionalFile = _root.optFile;
		OptionalData = _root.optData.split("#apstr;").join("'");
		FlashData = new Object;
		if (_root.flData != undefined) {
			FlashData = serializer.parse(_root.flData.split("#apstr;").join("'"));
		}
		AdaptiveMode = (_root.qAM == "1");
		FillCorrect = (_root.qFC == "1");
		ReadOnly = (_root.qRO == "1");
		InQuiz = (ID != undefined);
		
		Send();
	}
	
	// Cause embended events
	function Init():Void
	{
		if (FlashData != undefined && FlashData != "") {
			_root.flashRestoreFromData(FlashData);
		}
		if (FillCorrect) {
			_root.flashFillCorrectAnswers();
		}
		if (AdaptiveMode) {
			_root.flashAdaptiveMode();
		}
		if (ReadOnly) {
			_root.flashReadOnly();
		}
	}

	// Prepare and send to Moodle grade, description of grade
	// and information about user actions
	function Send():Void
	{
		var qData = new Object;
		qData.id = this.ID;
		qData.grade = this.Grade;
		qData.description = this.Description;
		qData.flashdata = serializer.stringify(this.FlashData);
		ExternalInterface.call("flashToJSComm", qData);		
	}
		
}
