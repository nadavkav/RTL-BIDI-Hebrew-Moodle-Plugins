README.txt $Id: README.txt,v 1.8 2008/10/05 19:58:33 dariem Exp $



WebQuest Module for Moodle  

Development start at Dec 1 2008, for moodle 2.0



///////////////////////////////////////////////////////////////////////////

//                                                                       //

// NOTICE OF COPYRIGHT                                                   //

// Webquest module for Moodle                                            //

// Moodle - Modular Object-Oriented Dynamic Learning Environment         //

//          http://moodle.org                                            //

//                                                                       //

// Copyright (C) 2006-2008 Dariem Garcés Urquiza   catdemian@gmail.com   //

//                                                                       //

// This program is free software; you can redistribute it and/or modify  //

// it under the terms of the GNU General Public License as published by  //

// the Free Software Foundation; either version 2 of the License, or     //

// (at your option) any later version.                                   //

//                                                                       //

// This program is distributed in the hope that it will be useful,       //

// but WITHOUT ANY WARRANTY; without even the implied warranty of        //

// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //

// GNU General Public License for more details:                          //

//                                                                       //

//          http://www.gnu.org/copyleft/gpl.html                         //

//                                                                       //

///////////////////////////////////////////////////////////////////////////







Topics:

=======

1.- Introduction to this module.

2.- Contact about this module.

3.- Developers of this module.

4.- Structure of this module directory.

5.- Installing this module.





1.-INTRODUCTION

===============

    This directory contains the first try to create a "Webquest"

    module for Moodle.



    Some day, if this is fully functional, it could be integrated

    into the main Moodle distribution (ask uncle Martin).   :-)



    Code here may not be tested - so there are no guarantees as

    to the quality of the code in here, even if part of a stable

    Moodle release.



2.-CONTACT

==========

    Please, for any comment about this option, do it in Moodle

    Developer Forum at:



       http://moodle.org/mod/forum/view.php?id=765

       (Webquests Moodle's Forum)

 

    and any bug about this topic in Moodle Bugs at:



      http://moodle.org/bugs



    All comments will be welcome.





3.-DEVELOPERS

=============

    The main developer of this module are:



       - Dariem Garcés Urquiza.



    You can contact him in "Using Moodle" or "Moodle en Espanol" 

    courses at:



      http://moodle.org





4.-STRUCTURE

============

    This module source repository is organized as follows:



       - README.txt: This file. :-) 



       - doc: Where all the documentation about the analysis and

              development of the module exists. See README.txt

              under it for more details.



       - db: Where the db creation and upgrade scripts exist.



       - lang: Where every string needed in the module exists. It

              have one subdirectory for every supported language. Every

              subdirectory will have:

                 + webquest.php: Strings translation.

                 + help: Help files for Moodle's online help system. 



       - other files: Module's script files (source code). These include:

              php scripts, html files, images... The module's core !!





5.-INSTALL

==========

   To install this module in your Moodle's TEST server, follow this steps:

        - Copy the entire "webquest" directory to your "moodle_dir/mod/" dir.

        - Copy the CONTENTS of every "lang/xx/" directory in this module to

            to your "moodle_dir/lang/xx/" dir (where xx in the language). Be

            careful with this step and avoid deleting the entire lang dir. Copy

            only contents !!



    in unix check permission in:

        yourmoodle/mod/webquest/

        yourmoodle/lang/es_utf8/

        yourmoodle/lang/en_utf8/

        yourmoodle/lang/en/

        yourmoodle/lang/es/



    After this you must visit http://yourmoodle/admin/index.php





