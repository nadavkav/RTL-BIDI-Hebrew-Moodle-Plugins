<ul>
  <li class="home">
    <div><a href="<?php echo $CFG->wwwroot.'/' ?>"><img width="18" height="17" src="<?php echo $CFG->httpswwwroot.'/theme/'.current_theme() ?>/images/home_icon.png" alt=""/></a>
    </div>
  </li> 


<?php
// Include HTML based menu from each course, if exists
include($CFG->dataroot.'/'.$COURSE->id.'/menu.txt'); ?>

  <li><div><a href="<?php echo $CFG->wwwroot.'/' ?>">מרחביי הלימוד שלי</a>
        <ul>
<?php
if ($courses = get_my_courses($USER->id, 'visible DESC, fullname ASC')) {
                foreach ($courses as $course) {
                    if ($course->id == SITEID) {
                        continue;
                    }
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    echo "<li><a $linkcss title=\"" . format_string($course->shortname) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . format_string($course->fullname) . "</a></li>";
                }
}
?>
     
	</ul>
</div></li>
</ul>
<div id="top_menu_date">
<a href="<?php echo $CFG->wwwroot.'/calendar/view.php' ?>"><script language="Javascript" type="text/javascript">
//<![CDATA[
<!--

// Get today's current date.
var now = new Date();

// Array list of days.
var days = new Array('ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת');

// Array list of months.
var months = new Array('ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר');

// Calculate the number of the current day in the week.
var date = ((now.getDate()<10) ? "0" : "")+ now.getDate();

// Calculate four digit year.
function fourdigits(number)     {
        return (number < 1000) ? number + 1900 : number;
                                                                }

// Join it all together
today =  days[now.getDay()] + " " +
              date + " " +
                          months[now.getMonth()] + " " +               
                (fourdigits(now.getYear())) ;

// Print out the data.
document.write("" +today+ " ");
  
//-->
//]]>
</script></a>
</div>


 
 
        