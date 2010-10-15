/***************************************************
 * This file is a part of "Flash" question interface
 * for LMS Moodle.
 *
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/
import FlashQuestion;


// Описание дополнительных переменных, предназначенных 
// для сохранения действий пользователя
var userActs = new Array();
var step = 0;
var uPoints = 0;
var uLines = 0;


// В данной функии необходимо подготовить информацию
// к отправке в систему Moodle. Информация об ответе учащегося должна быть 
// помещена в объект Question. Непосредственно отправка информации осуществляется
// с помощью метода Send() данного объекта.
function sendUserAnswer():Void {
	answer = true;
	pi1 = 0;
	pi2 = 0;
	pi3 = 0;
	pi4 = 0;
	pi5 = 0;
	pi6 = 0;
	// поиск трех точек
	for (i=oldNumPoints; i<=numPoints; i++) {
		if (eqv(_root.points[i].x, 100) and eqv(_root.points[i].y, 10) and eqv(_root.points[i].z, 0) and pi1 == 0) {
			pi1 = i;
		}
	}
	// проверка условия точек и линий
	if (pi1 == 0) {
		answer = false;
	}
	// Вычисляем оценку за попытку (вещественное число от 0 до 1)
	if (answer) {
		Question.Grade = 1;
		// Указываем описание оценки (необходимо для группировки оценок в отчетах)
		Question.Description = "Верно";
	} else {
		Question.Grade = 0;
		Question.Description = "Неверно";
	}

	// Необходимо сохранить состояние ролика, чтобы в дальнейшем
	// можно было его востановить и показать преподавателю/студенту
	// данную попытку. Объект может быть сколь угодно сложной структуры.
	myData = new Object;
	myData.history = userActs;
	//myData.mydata = "Any additional data";
	
	// Помещаем данные в объект Question
	Question.FlashData = myData;
	
	// Отправляем информацию в систему Moodle
	Question.Send();
}

// Данное событие оповещает, что нужно востановить состояние
// flash-ролика из сохраненных ранее данных.
// Необходимо для отображения попытки учащегося 
// преподавателю или самому учащемуся
function flashRestoreFromData(flashData:Object):Void {
	if (flashData.history != undefined) {
		_root.userActs = flashData.history;
	}
	// Востанавливаем все действия сделанные студентом
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

// Данное событие оповещает о том, что необходимо отобразить
// правильный ответ.
// Обратите внимание, что данное событие может произойти после
// события flashRestoreFromData, таким образом ответ учащегося
// и правильный ответ должны быть отображены вместе, причем так,
// чтобы было понятно где какой из них.
function flashFillCorrectAnswers():Void {
	// Отображаем правильное сечение куба
	_root.createEmptyMovieClip("grad", 16000);
	drawSech();
}

// Данное событие оповещает о том, что необходимо предотвратить
// возможность изменения пользователем состояния flash-ролика.
// Следует заблокировать по возможности все элементы интерфайса. 
// Обратите внимание, что данное событие может произойти после
// событий flashRestoreFromData и flashFillCorrectAnswers, поэтому 
// блокировка элементов управления не должна помешать отобразить
// ответ учащегося и правильный ответ.
function flashReadOnly():Void {
	// Отключаем реакцию ролика на мышь
	_root.onMouseUp = function() {}
	// Скрываем панель редактирования задачи
	_root.studentPanel._visible = false;
	// Отображаем панель просмотра выполненной попытки
	_root.ctlBar._visible = true;
}

// Данное событие оповещает о том, тест работает в 
// обучающем (адаптивном) режиме. Обычно flash-ролик не должен
// как-либо реагировать на это, т.к. за запоминание попыток, 
// начисление штрафов и т.п. отвечает система Moodle.
// Но вы можете использовать это для отображения в обучающем
// режиме каких-либо подсказок учащемуся.
function flashAdaptiveMode():Void {
	// Нет необходимости что-либо делать здесь.
}
