SWF Activity Module

Activity Module plugin for Moodle by Matt Bury
matbury@gmail.com
http://matbury.com/

The SWF Activity Module provides a reliable, easy to use framework for deploying Flash and Flex Framework learning applications as learning interactions in Moodle. 

/**    Copyright (C) 2009  Matt Bury
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

To install:
* Upload the complete 'swf' (not 'FLV') directory to ***MOODLEROOT***/mod/
* Login to Moodle as administrator and in the 'Site Administration' panel, select 'Notifications'
* Moodle will install the SWF Activity Module and DB tables for you.

To use:
* Go to the course page and position you wish to deploy a Flash or Flex Framework learning interaction.
* Select 'SWF' from the 'Add an activity...' list.
* Choose or upload a Flash or Flex Framework learning application.
* Select a learning interaction DB data set (not yet implemented), choose a learning interaction data XML file or enter learning interaction data as string values which are passed in via FlashVars
* Enter any other desired/required parameters.
* All other parameters are on default settings. Change or leave them as you wish.
* Select 'Save changes' (Moodle 1.8) or 'Save changes and preview' (Moodle 1.9).
That's it!

code.google.com project home page: http://code.google.com/p/moodle-swf/
Moodle Docs page: http://docs.moodle.org/en/SWF