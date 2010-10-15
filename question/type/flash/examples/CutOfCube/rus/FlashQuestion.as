/***************************************************
 * PLEASE, DON'T CHANGE THIS FILE!
 ***************************************************
 * Этот файл является частью интерфейса вопросов
 * типа "Flash" для LMS Moodle.
 *
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

import flash.external.*;
import JSON; //класс сериализации данных

//
// Свойства класса FlashQuestion:
//        Grade: Number        - (чтение/запись) оценка за текущую попытку (вещественное число от 0 до 1).
//        Description: String  - (чтение/запись) текстовое описание оценки. Это описание используется для
//                               группировки результатов попыток на странице "Анализ вопросов".
//                               Одно описание может использоваться для разных оценок (обычно близких).
//                               Например "Верно", "Частично верно", "Неверно".
//                               В качестве описания можно использовать и саму оценку.
//        FlashData: Object    - (чтение/запись) состояние флеш-ролика в данной попытке, сохраняемое в базе данных.
//                               Тесты в Moodle позволяют студенту сохранить указанные ответы на вопросы,
//                               чтобы доработать или исправить в будущем.
//                               С помощью данного свойства ролик должен востановить свое состояние,
//                               чтобы прозволить студенту продолжить работу с того места, где он остановился.
//                               Это свойство также должно использоваться для отображения учителю попытки студента.
//                               Данное свойство может быть сколь угодно сложной структуры.
//        OptionalFile: String - (чтение) ссылка на файл (URL), который был выбран в процессе создания вопроса в Moodle.
//        OptionalData: String - (чтение) любая текстовая информация указанная в процессе создания вопроса в Moodle.
//                         Последние два свойства позволяют создать универсальный плеер под какую-то задачу
//                         и передавать в него исходные параметры указаные в файле (например в формате xml)
//                         или просто в текстовом формате.
//        InQuiz: Boolean      - (чтение) позволяет проверить, где в данный момент используется 
//                               флеш-ролик (в тесте или в каком-то другом месте). 
//                               Например, вы можете отобразить кнопку "Проверить ответ" и показывать оценку
//                               за выполненное задание прямо в ролике, если он открыт не из теста Moodle.
//
// Методы класса FlashQuestion:
//        function Init(): Void - Инициализирует объект. Это вызывает возникновение необходимых событий, 
//                                указанных в файле moodleEvents.as
//                                Когда вы вызываете этот метод все переменные вашего флеш-ролика должны
//                                быть иницициализированы, т.к. возможно потребуется отображение
//                                текущей попытки студента.
//        function Send(): Void - отправляет все данные на html-страницу.
//                                Передача информации непосредственно в систему Moodle осуществляется средствами
//                                html-страницы, т.е. также и в тот же момент, когда это происходит 
//                                у стандартных типов вопросов.

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
	
	// Конструктор
	// Инициализирует объект данными из Moodle
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
	
	// Вызывает встроенные события, необходимые в данном случае.
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

	// Подготавливает и отправляет в Moodle оценку, описание оценки
        // и информацию о действиях пользователя.
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
