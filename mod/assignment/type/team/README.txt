Steps to install team assignments type to a 1.9+ Moodle installation:

1) unzip team-assignment-type.zip at the moodle root or manually copy the team directory
   to <moodle root>/mod/assignment/type/

2) edit <moodle root>/lang/en_utf8/assignment.php and add the following lines:
$string['typeteam'] = 'Team Assignment';

3) log in to your moodle site as Admin user.

4) visit the Admin notifications page.

5) run admin notifications. Two tables (team and team_student) will be installed.