Appointment Scheduler for Moodle

This is a completely redrawn scheduler module that allows teachers to publish
appointment slots and students to appoint to these slots.

The redraw features :

Software implementation
- enhanced data model, with better normalization
- complete Model-View-Conroller design pattern
- code splitting and reorganization
- library redraw

Features
- complete backup/restore handling
- appointment grading capabilities
- enhanced slot management API
- volatile control
- more instance parameters
- email notifications for release and hold of slots
- added capability control
- added cross treacher scheduling
- added limiting to more than 1 user
- added guard time control for volatile slots
- added "appoint submanagement when adding/updating slots"
- group scheduling based on Moodle groups
- cross grading allowance when multiple teachers on same scheduler (site wide parameter)

Full compatibility from 1.8 upwards.

Install as usual : deploy in <%%MOODLE%%>/mod and go to the administration 
notifications to finish install. 

<valery.fremaux@club-internet.fr>