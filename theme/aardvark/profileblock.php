<table border="0">
  <tr>
    <td width="600" height="65" valign="bottom"><div class="header-profilename" id="header-profilename">
    <?PHP
	
if (!isloggedin() or isguestuser()) {
echo '<a href="'.$CFG->wwwroot.'/login/index.php">'.get_string('loggedinnot').'</a>';

} else {
echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'">'.$USER->firstname.' '.$USER->lastname.'</a>';
}		


?>
    </div></td>
    <td width="90" height="90" rowspan="2"><div class="header-profilepic" id="header-profilepic">
        <?PHP

echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f1.jpg" width="80px" height="80px" title="Profile Picture" alt="Profile Picture" /></a>'; 

?>
      </div></td>
  </tr>
  <tr>
    <td width="600" height="25"> <div class="header-profileoptions" id="header-profileoptions">
    
    
    

 <?PHP
				
if (!isloggedin() or isguestuser()) {
echo '<ul><form class="loginform" id="login" method="post" action="'.$wwwroot.'/login/index.php">';
echo '<li><label for="login_username">'.get_string('username').'</label><input class="loginform" type="text" name="username" id="login_username" value="" /></li>';
echo '<li><label for="login_password">'.get_string('password').'</label><input class="loginform" type="password" name="password" id="login_password" value="" /></li>';
echo '<li><input type="submit" value="&nbsp;&nbsp;'.get_string('login').'&nbsp;&nbsp;" /></li>';
echo '</form></ul>';

} else {
echo '<ul>';
echo '<li><a href="'.$CFG->wwwroot.'/user/edit.php?id='.$USER->id.'&amp;course='.$COURSE->id.'">'.get_string('updatemyprofile').'</a></li>';
echo '<li><a href="'.$CFG->wwwroot.'/my">'.get_string('mycourses').'</a></li>';				
echo '<li><a href="'.$CFG->wwwroot.'/login/logout.php?sesskey='.sesskey().'">'.get_string('logout').'</a></li>';
echo '</ul>';

}
?>


    
    </div>
    </td>
  </tr>
</table>
