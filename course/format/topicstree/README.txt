This course format is one "clone" of the standard "topics" format. Its
main (unique) difference is that, following the indentation in activities performed
in edit mode, it displays the course in a tree way.

Credits for the collapse code and css go, respectively, to:

Mark Wilton-Jones: http://www.howtocreate.co.uk/tutorials/jsexamples/listCollapseExample.html
Michal Wojciechowski: http://odyniec.net/articles/turning-lists-into-trees/

Configuration hacks:

- If you aren't using one of the standard themes of Moodle and you use
  some custom background color, edit the styles.php file and change this
  line to use your theme background color:

  $bg = '#FAFAFA'; /// Change this to match your theme background color (defaults to standard ones)

- By default the tree display is disabled for section 0. If you want to
  see the trees there, edit the format.php file and change this line from:

  $topicstree_tree_in_section0 = false;

  to:

  $topicstree_tree_in_section0 = true;

That's all, enjoy. Ciao, Eloy Lafuente (stronk7) 20080831

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2001-3001 Martin Dougiamas        http://dougiamas.com  //
//           (C) 2001-3001 Eloy Lafuente (stronk7) http://contiento.com  //
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

