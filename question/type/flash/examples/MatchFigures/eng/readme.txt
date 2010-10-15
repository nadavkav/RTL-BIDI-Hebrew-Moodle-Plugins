/***************************************************
 * This file is a example of "Flash" question type
 * for LMS Moodle.
 *
 * @author Pupinin Dmitry, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

Description:
This flash example may works inside Moodle's quiz and as independent task included in resource, 
lesson or just offline!

Mission of files in this example:
1. FlashQuestion.as - definition of FlashQuestion class used for communicate with Moodle (don't modify this!)
2. moodleEvents.as - contains a set of events on which the your own movie should react
3. JSON.as - library for serializing data
4. other files - contain scripts of this example


Tip:
If you'll searching word "MoodleIntegration" through source code, you'll find all place where flash 
using FlashQuestion class or other integration manners.