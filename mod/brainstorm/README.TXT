Moodle Brainstorm Activity Module 

This is a complete redraw and massive redevelopement of Martin Ellerman's orginal brainstorm
module.

The overal process of redrawing the module was to offer a complete generalization of the
brainstorming process, allowing the third party community to add and invent new organisation operators,
based on a proposed subAPI of this module.

This will be documented at further date.

Changes are numerous and should not be exhaustively listed here :

- Adding a "phase control" workflow architecture, allowing participants or manager
to process any of the phase. 
    - phase 0 : collecting
    - phase 1 : preparing (a generalization of the "making catelgories")
    - phase 2 : organizing
    - phase 3 : viewing results
    - planned : phase 4 : making reports of what happened (could actually be done with an external assignment)

- Adding a generalization of the initial "categorize" operator.
    - categorization : spread ideas into named categories (original)
    - filtering : reduce ideas amount by eliminating
    - hierarchize : make a multilevel hierarchy with ideas
    - locate : place ideas onto a 2D map, by weighting 2 independant characteristics
    - map : make a map of relationships between ideas, weighted or unweighted
    - merge : take some of the ideas and try to reduce them to a single by a merge
    - order : try to order ideas
    - scale : try to weight numerically ideas so they can be compared
    - schedule : make a graph of dependancies with the ideas (unimplemented)

Multiple procedures : 

Many of the operator provide multiple procedures to perform their organisation process.

User isolation : 

All the operators can show or hide additional information on the organisation state when organizing.
When showing this information, participants may have some information on the previously entered data.
This may change the activity profile.

    
ARCHIVE :
---------

This is a simple upgrade of the nice brainstoming module to work
with moodle 1.6+ and later.

Important Note:
By using Moodle since 1.6 the installation of older Brainstrom versions
fails. Do not try to overwrite such installation before cleaning up
the Database using the instructions in DBcleanup.txt ! 


ChangeLog:

db/mysql.sql
INSERT action similar to the new database scructure in prefix_log_display

mod.php
Some bugfixing related the call of the text edit window


This Software comes without any warranty!!!!


martin.ellermann@uni-bielefeld.de   