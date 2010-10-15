/***************************************************
 * This file is a part of "Flash" question type
 * for LMS Moodle.
 *
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

function getEl(id){
	if(document.layers){
		return document.layers[id];
	}
	if(document.all && document.all.item){
		return document.all[id];
	}
	if(document.getElementById){
		return document.getElementById(id);
	}
}

function flashToJSComm(item) {
	var obj = getEl("idresp"+item.id+"_grade");
	obj.value = item.grade;
	
	obj = getEl("idresp"+item.id+"_flashdata");
	obj.value = item.flashdata;
	
	obj = getEl("idresp"+item.id+"_");
	obj.value = item.description;
}

function CpToJSComm(qid, cmd, lng) {
	var res = cmd.match(/#Score:(\d+)\D+(\d+)?/);
	var item = new Object;
	if (res[1] != null) {
		if (res[2] != null) {
			item.grade = res[1]/res[2];
		} else {
			item.grade = res[1]/100;
		}
		item.id = qid;
		item.flashdata = "Authorware";
		switch (item.grade) {
			case 0: 
				item.description = "Incorrect";
				break;
			case 1:
				item.description = "Correct";
				break;
			default:
				item.description = "Partially correct";
				break;
		}
		flashToJSComm(item);
		//alert(item.grade);
	}
}