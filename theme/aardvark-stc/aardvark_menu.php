<div id="aardvark_menu_date">
<a href="<?php echo $CFG->wwwroot.'/calendar/view.php' ?>"><script language="Javascript" type="text/javascript">
//<![CDATA[
<!--

// Get today's current date.
var now = new Date();

// Array list of days.
//var days = new Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
var days = new Array('ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת');

// Array list of months.
//var months = new Array('January','February','March','April','May','June','July','August','September','October','November','December');
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

<ul>

       <li><div><a href="<?php echo $CFG->wwwroot.'/' ?>"><img width="18" height="17" src="<?php echo $CFG->httpswwwroot.'/theme/'.current_theme() ?>/images/menu/home_icon.png" alt=""/></a></div>
       </li>

        <li><div><a href="<?php echo $CFG->wwwroot.'/' ?>">תפריט אחד</a>

        <ul>
        <h4>Subtitle Text</h4>

        <?php

 $text ='<li><a href="">Item One</a></li>';
 $text .='<li><a href="">Item Two</a></li>';
 $text .='<li><a href="">Item Three</a></li>';
 $text .='<li><a href="">Item Four</a></li>';
 $text .='<li><a href="">Item Five</a></li>';
 $text .='<li><a href="">Item Six</a></li>';
 $text .='<li><a href="">Item Seven</a></li>';
 $text .='<li><a href="">Item Eight</a></li>';

 echo $text;
?>

           </ul></div>

        <li><div><a href="<?php echo $CFG->wwwroot.'/' ?>">תפריט שני</a>

        <ul>
        <h4>Subtitle Text</h4>

        <?php

 $text ='<li><a href="">Item One</a></li>';
 $text .='<li><a href="">Item Two</a></li>';
 $text .='<li><a href="">Item Three</a></li>';
 $text .='<li><a href="">Item Four</a></li>';
 $text .='<li><a href="">Item Five</a></li>';
 $text .='<li><a href="">Item Six</a></li>';
 $text .='<li><a href="">Item Seven</a></li>';
 $text .='<li><a href="">Item Eight</a></li>';

 echo $text;
?>

           </ul></div>

        <li><div><a href="<?php echo $CFG->wwwroot.'/' ?>">נושא שלישי</a>

        <ul>
        <h4>Subtitle Text</h4>

        <?php

 $text ='<li><a href="">Item One</a></li>';
 $text .='<li><a href="">Item Two</a></li>';
 $text .='<li><a href="">Item Three</a></li>';
 $text .='<li><a href="">Item Four</a></li>';
 $text .='<li><a href="">Item Five</a></li>';
 $text .='<li><a href="">Item Six</a></li>';
 $text .='<li><a href="">Item Seven</a></li>';
 $text .='<li><a href="">Item Eight</a></li>';

 echo $text;
?>


           </ul></div>
                <li><div><a href="<?php echo $CFG->wwwroot.'/' ?>">נושא רביעי</a>

        <ul>
        <h4>Subtitle Text</h4>

        <?php

 $text ='<li><a href="">Item One</a></li>';
 $text .='<li><a href="">Item Two</a></li>';
 $text .='<li><a href="">Item Three</a></li>';
 $text .='<li><a href="">Item Four</a></li>';
 $text .='<li><a href="">Item Five</a></li>';
 $text .='<li><a href="">Item Six</a></li>';
 $text .='<li><a href="">Item Seven</a></li>';
 $text .='<li><a href="">Item Eight</a></li>';

 echo $text;
?>
           </ul></div>

        <li><div><a href="<?php echo $CFG->wwwroot.'/' ?>">רשימה חמישית</a>

        <ul>
        <h4>Subtitle Text</h4>

        <?php

 $text ='<li><a href="">Item One</a></li>';
 $text .='<li><a href="">Item Two</a></li>';
 $text .='<li><a href="">Item Three</a></li>';
 $text .='<li><a href="">Item Four</a></li>';
 $text .='<li><a href="">Item Five</a></li>';
 $text .='<li><a href="">Item Six</a></li>';
 $text .='<li><a href="">Item Seven</a></li>';
 $text .='<li><a href="">Item Eight</a></li>';

 echo $text;
?>

           </ul></div>