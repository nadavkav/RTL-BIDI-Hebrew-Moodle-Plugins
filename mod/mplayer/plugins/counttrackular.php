<?php
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////
//// FOR EXAMPLE USE ONLY
////
////////////////////////////////////////////////////
////////////////////////////////////////////////////

//////////////////////////////////////////////////////////
// Your website's MySql Database login info
//////////////////////////////////////////////////////////

/*$dbhost = "localhost"; // localhost will usually work
$dbusername = "yourusernameforthedatabase";
$dbpassword = "yourpasswordforthedatabase";
$dbnameofdb = "thenameofthedatabase";

$link = mysql_connect($dbhost, $dbusername, $dbpassword);
if (!$link) { echo "Did not connect!";}

$db_selected = mysql_select_db($dbnameofdb, $link);
if (!$db_selected) {echo "Did not select!";}

//////////////////////////////////////////////////////////
//CountTrackula States: Began, PrevSecs, Middle, Finished
// -- Enter the one you want to use in $mystate below
//////////////////////////////////////////////////////////

$mystate = "Began";

///////////////////////////////////////////////////////////////
//Enter in the full folder location where your mp3s are stored
// -- Enter more than one (1,2,3,4) if you need to
//////////////////////////////////////////////////////////////

$host1 = "http://yoursite.com/path/to/mp3/folder/";
$host2 = "";
$host3 = "";
$host4 = "";


//Force ReCache
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

//CATCH THE $_POST VARIABLES FROM COUNTTRACKULA
$state = $_POST["state"];
$file = $_POST["file"];

//REMOVE DOMAIN (HOST) FROM $_POST[FILE] STRINGS - ISOLATE FILENAME
if ( substr_count($file, $host1) > "0" ){ $file = eregi_replace($host1, "", $file); }
if ( substr_count($file, $host2) > "0" ){ $file = eregi_replace($host2, "", $file); }
if ( substr_count($file, $host3) > "0" ){ $file = eregi_replace($host3, "", $file); }
if ( substr_count($file, $host4) > "0" ){ $file = eregi_replace($host4, "", $file); }

//EXPLODE FILENAME INTO 2 PIECES - Name, Extension (mp3,flv, etc...)
$mp3_x = explode(".", $file);
$x0 = $mp3_x[0];
$x1 = $mp3_x[1];

if ($state == $mystate && $x0 != "" && $x1 != "")
{
//ADD A PLAY TO THE SONG
$strQuery3 = "UPDATE `songs` SET plays = plays + 1 WHERE id = '$x0' OR title = '$x0' LIMIT 1 ";
$result = mysql_query($strQuery3);
}*/

echo "done=yes";

?>