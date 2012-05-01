<?php
/**
 * Created by Nadav Kavalerchik.
 * eMail: nadavkav@gmail.com
 * Date: 2/5/12
 * Description:
 *    Change me...
 */

?>

.digibook #middle-column {
    background-image: url('<? echo $CFG->wwwroot.'/course/format/digibook/' ?>open-book.png');
    background-repeat: no-repeat;
}

.dir-rtl .digibook #middle-column {
    background-image: url('<? echo $CFG->wwwroot.'/course/format/digibook/' ?>open-book.png');
    background-repeat: no-repeat;
}

.digibook #tbookpages {
position: relative;
top: 44px;
left: 50px;
width:89%;
}

.dir-rtl .digibook #tbookpages {
position: relative;
top: 44px;
letf:0;
right: 65px;
width:87%;
}

.digibook #tbookpages td {
width:250px;
padding:15px;
vertical-align:top;
}

.dir-rtl .digibook #tbookpages td {
width:45%;
padding:15px;
vertical-align:top;
}

.dir-rtl .digibook #dynamictopic {
<!--	position:absolute;-->
<!--	left:0px;-->
<!--	width:1800px;-->
	-webkit-transition:all 1.0s ease-in-out;
	-moz-transition:all 1.0s ease-in-out;
	-o-transition:all 1.0s ease-in-out;
	-ms-transition:all 1.0s ease-in-out;
	transition:all 1.0s ease-in-out;
}