/***************************************************
 * Это пример реализации флеш-ролика для вопроса
 * типа "Flash" предназначенного для LMS Moodle.
 *
 * @author Petrov Aleksandr, Russia, Novosibirsk, 2009 (флеш-ролик)
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009 (интеграция в Moodle)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

Назначение файлов в этом примере:
1. FlashQuestion.as - определение класса FlashQuestion используемого для комуникации с Moodle (не изменяйте этот файл!).
2. moodleEvents.as - содержит события на которые должен отреагировать ваш ролик.
3. JSON.as - библиотека для сериализации данных (используется классом FlashQuestion).
4. другие файлы - содержат скрипты принадлежащие этому ролику.


Совет:
С помощью поиска слова "MoodleIntegration" по файлам с исходным текстом этого примера вы можете найти
все места, где осуществляется интеграция ролика в Moodle.