<?
require_once("../../config.php");
require_once("lib.php");

$id = required_param("id"); 




$id= str_replace(",",";",$id);

require_course_login($course, false, $cm);

//display the form 
 
echo "<table width=100% border=0><tr><td>" ;

echo "<script src=\"js/skypeCheck.js\"></script>
<a href=\"skype:$id?call\"><img src='pics/createconference.gif' border='0' onclick=\"return skypeCheck();\"></a>";

echo "</td><td><script src=\"js/skypeCheck.js\"></script>
<a href=\"skype:$id?chat\"><img src='pics/createchat.gif' border='0' onclick=\"return skypeCheck();\"></a></td>";


echo "<td><script src=\"js/skypeCheck.js\"></script>
<a href=\"skype:$id?voicemail\"><img src='pics/sendvoicemail.gif' border='0' onclick=\"return skypeCheck();\"></a></td>";

echo "</tr><tr><td><script src=\"js/skypeCheck.js\"></script>
<a href=\"skype:$id?add\"><img src='pics/addcontact.gif' border='0' onclick=\"return skypeCheck();\"></a></td>";


echo "<td><script src=\"js/skypeCheck.js\"></script>
<a href=\"skype:$id?sendfile\"><img src='pics/send.gif' border='0' onclick=\"return skypeCheck();\"></a></td>";

echo "<td><script src=\"js/skypeCheck.js\"></script>
<a href=\"skype:$id?add\"><img src='pics/addme.png' border='0' onclick=\"return skypeCheck();\"></a></td>";

echo "</tr></table>";
		
		
?>

	
        
 