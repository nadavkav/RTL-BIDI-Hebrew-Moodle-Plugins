<?php
/**
 * Lang strings for admin/report/customsql
 *
 * @package report_customsql
 * @copyright &copy; 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['addreport'] = 'הוספת דוח חדש';
$string['anyonewhocanveiwthisreport'] = 'כל אחד, בעל הרשאה: (report/customsql:view) יכול לראות דוח זה';
$string['archivedversions'] = 'גירסת ארכיון של דוח זה';
$string['automaticallymonthly'] = 'מתוזמן, על פי היום הראשון של כל חודש';
$string['automaticallyweekly'] = 'מתוזמן, על פי היום הראשון של כל שבוע';
$string['availablereports'] = 'דוחות להפעלה מיידית';
$string['availableto'] = 'מצב זמינו דוח זה: $a.';
$string['backtoreportlist'] = 'חזרה, לרשימת הדוחות';
$string['displaychart'] = 'הצגת גרף עוגה';
$string['customsql'] = 'דוחות מוכנים מראש';
$string['customsql:definequeries'] = 'הגדרת דוחות מותאמים-אישית';
$string['customsql:view'] = 'תצוגת דוחות';
$string['deleteareyousure'] = 'האם אתם בטוחים שאתם מעוניינים למחוק דוח זה?';
$string['deletethisreport'] = 'מחיקת דוח זה';
$string['description'] = 'תאור אשר יופיע בכותרת הדוח';
$string['displayname'] = 'שם הדוח';
$string['displaynamex'] = 'שם הדוח: $a';
$string['displaynamerequired'] = 'יש להזין שם לדוח';
$string['downloadthisreportascsv'] = 'שמירת דוח כקובץ CSV';
$string['editingareport'] = 'עריכת מאפייני דוח';
$string['editthisreport'] = 'עריכת דוח זה';
$string['errordeletingreport'] = 'Error deleting a query.';
$string['errorinsertingreport'] = 'Error inserting a query.';
$string['errorupdatingreport'] = 'התגלתה שגיאה בעת עדכון הדוח.';
$string['invalidreportid'] = 'Invalid query id $a.';
$string['lastexecuted'] = 'דוח זה הופעל לאחרונה בתאריך $a->lastrun. זמן עיבוד: {$a->lastexecutiontime} שניות.';
$string['manually'] = 'ידנית';
$string['manualnote'] = 'דוחות אילו מופעלים ידנית, כאשר אתם מקליקים על כותרת הדוח.';
$string['morethanonerowreturned'] = 'More than one row was returned. This query should return one row.';
$string['nodatareturned'] = 'לא נמצאו נתונים אשר עונים על הגדרות הדוח.';
$string['noexplicitprefix'] = 'Please use prefix_ in the SQL, not $a.';
$string['noreportsavailable'] = 'לא קיימים דוחות, עדיין.';
$string['norowsreturned'] = 'No rows were returned. This query should return one row.';
$string['nosemicolon'] = 'You are not allowed a ; character in the SQL.';
$string['notallowedwords'] = 'You are not allowed to use the words $a in the SQL.';
$string['note'] = 'הערות';
$string['notrunyet'] = 'This query has not yet been run.';
$string['onerow'] = 'השאילתה תציג כל פעם שורה אחת, ותצבור את השורות אחת אחר השנייה';
$string['queryfailed'] = 'תקלה התרחשה בעת ביצוע הדוח: $a';
$string['querynote'] = '<ul>
<li>The token <tt>%%%%WWWROOT%%%%</tt> in the results will be replaced with <tt>$a</tt>.</li>
<li>Any field in the output that looks like a URL will automatically be made into a link.</li>
<li>The token <tt>%%%%USERID%%%%</tt> in the query will be replaced with the user id of the user viewing the report, before the report is executed.</li>
<li>For scheduled reports, the tokens <tt>%%%%STARTTIME%%%%</tt> and <tt>%%%%ENDTIME%%%%</tt> are replaced by the Unix timestamp at the start and end of the reporting week/month in the query before it is executed.</li>
</ul>';
$string['queryrundate'] = 'זמן הפעלת דוח';
$string['querysql'] = 'שאילתת SQL';
$string['querysqlrequried'] = 'יש להזין פקודות SQL.';
$string['recordlimitreached'] = 'דוח זה הגיע למספר הרשומות המירבי $a אשר ניתן להציג. קיימת אפשרות שמספר רשומות הושמתו מסוף הדוח.';
$string['reportfor'] = 'הדוח הופעל בתאריך $a';
$string['runable'] = 'הפעלה';
$string['runablex'] = 'הפעלת: $a';
$string['schedulednote'] = 'These queries are automatically run on the first day of each week or month, to report on the previous week or month. These links let you view the results that has already been accumulated.';
$string['scheduledqueries'] = 'דוחות מתוזמנים';
$string['typeofresult'] = 'אופן תצוגת תוצאות הדוח';
$string['unknowndownloadfile'] = 'Unknown download file.';
$string['userswhocanviewsitereports'] = 'משתמשים בעלי הרשאה: (moodle/site:viewreports) אשר יש להם גישה לדוחות מערכת ';
$string['userswhocanconfig'] = 'רק מנהלי מערכת בעלי הרשאות: (moodle/site:config) יכולים לראות דוח זה';
$string['whocanaccess'] = 'למי יש גישה לדוחות';
$string['recordcount'] = 'מספר הרשומות בדוח';

?>
