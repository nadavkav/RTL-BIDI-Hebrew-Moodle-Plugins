<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/jquery.js";?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot."/mod/sclipowebclass/js/jquery.passwordmeter.js";?>"></script>
<script type="text/javascript">
jQuery(document).ready(function() {
		$('#userPassSignUp').keyup(function(){$('#chkpass').html(passwordStrength($('#userPassSignUp').val(),$('#userEmailSignup').val()))});
});
function checkmail()
{
    if ($.trim($('#userEmailSignup').val()) != '')
    {
/*
	if ($.trim($('#userFistName').val()) == '' || $.trim($('#userFistName').val()) == 'First Name')
	{
		return false;
	}
*/	
    $.post('<?php echo $CFG->wwwroot.'/mod/sclipowebclass/sclipoapi.php';?>' , {email: $('#userEmailSignup').val()}, function(data){
        if (data == 'ok') 
        {
            $('#chkmail').html('<span style="color:#8BB13F;">Your email address appears to be valid. Thanks!</span>');
            return true;
        }
        if (data == 'used')
        {
            $('#chkmail').html('<span style="color:#B70202;">This email is already registered to an account.</span>');
            $('#userEmail').focus();
            return false;
        }
        if (data == 'invalid')
        {
            $('#chkmail').html('<span style="color:#B70202;">The email provided does not appear to be valid.</span>');
            $('#userEmail').focus();
            return false;
        }
    });
    }
}
</script>
<script type="text/javascript">
	function showLogin()
	{
		document.getElementById("login").style.display="inline";
		document.getElementById("signup").style.display="none";
		document.getElementById("login_switch").style.display="none";
		document.getElementById("signup_switch").style.display="inline";
		document.getElementById("register_switch").style.display="none";
		document.getElementById("signin_switch").style.display="inline";
		
	}
	function showSignUp()
	{
		document.getElementById("login").style.display="none";
		document.getElementById("signup").style.display="inline";
		document.getElementById("signup_switch").style.display="none";
		document.getElementById("login_switch").style.display="inline";
		document.getElementById("signin_switch").style.display="none";
		document.getElementById("register_switch").style.display="inline";
	}
	
	function validateForm(form)
	{
		if (form.userEmail.value.indexOf('@') == -1) {
			alert("Please enter a valid email address");
			return false;
		}
		if (form.userPass.value == "") {
			alert("Please enter a password");
			return false;
		}
		if (form.userFirstName.value == "" || form.userLastName.value == "") {
			alert("Please enter your name");
			return false;
		}
		if (!(form.gender[0].checked || form.gender[1].checked)) {
			alert("Please select your gender");
			return false;
		}
		if (form.accept_terms.checked == true)
			return true;		
		else {
			alert("You need to accept the terms");
			return false;
		}
		return true;
	}
	
</script>

<div style="width: 1000px; margin: auto;">
<div class="join" id="content">

        

	<div class="bt">
		<div>
		</div>
	</div>
<div class="i1">
		<div class="i2">
			<div class="i3">
				<div id="main" style="width: 500px;">
                    <h2 style="color: rgb(254, 103, 27);">Teach Live Web Classes</h2>
					<p class="subtitle">Conduct unlimited live classes in your private web classroom.</p>

					<ul style="margin-left: 30px;">
						<li style="padding:10px 10px 10px 30px; list-style-type:square;">
							Teach up to 100 students per web class or webinar</li>
						<li style="padding:10px 10px 10px 30px; list-style-type:square;">
							Enrich your teaching with webcam for teacher and students</li>
						<li style="padding:10px 10px 10px 30px; list-style-type:square;">
							Share & present documents during live classes</li>
					    <li style="padding:10px 10px 10px 30px; list-style-type:square;">
							Interact through whiteboard</li> 
						<li style="padding:10px 10px 10px 30px; list-style-type:square;">
							Record your web classes</li>
						<li style="padding:10px 10px 10px 30px; list-style-type:square;">
							Embed recorded web classes anywhere</li>
						<li style="padding:10px 10px 10px 30px; list-style-type:square;">
							No download or installations needed!</li>
					</ul>
					<div style="padding: 20px 10px; background-color: rgb(240, 240, 240);
font-size: 12px; color: rgb(64, 64, 64); width: 440px;">
<img width="35" align="left" src="http://sclipo.com/scimg/join-meetings.png"
style="padding-right: 15px;"/>
The Live Web Classroom was developed by Sclipo.<br/><a href="
http://sclipo.com/videos/view/demo-of-sclipo-a-web-application-to-teach-learn-and-share-with-students-and-fellow-teachers">Learn
more about Sclipo and its  web applications for teaching, learning and
sharing</a>.</div>
					
				</div>
				
				<div id="extra">
							<div id="content-luke" style="margin-top: 5px; margin-right:10px">
									<div id="singon">
										<div class="rtitle-gray">
											<div>
												<h3>Register or Sign In</h3>
											</div>
										</div>
										
										<div class="wrapper outviewed">
											<p style="border: 1px solid rgb(192, 192, 192); padding: 10px;
background-color: white; color: rgb(64, 64, 64); line-height: 14px;
font-size: 12px;">
<img width="60%" src="
http://sclipo.com/blog/wp-content/uploads/2008/12/sclipo-logo-store_small.png"
style="padding-bottom: 6px;"/><br/>
Sclipo powers live web classes on Moodle.<br/>
<span style="padding-top: 3px;">Please register below (takes just a few
seconds).</span></p>						<span id="register_switch" style="font-size: 15px; font-weight: bold; color: black; display: <?php if (!isset($wrong_login) || !$wrong_login) echo 'inline'; else echo 'none';?>">Register</span>
											<span id="signin_switch" style="font-size: 15px; font-weight: bold; color: black; display: <?php if (isset($wrong_login) && $wrong_login) echo 'inline'; else echo 'none';?>;">Sign In</span><br />
											<span id="login_switch" style="font-size: 12px; font-weight: bold; color: black; display: <?php if (!isset($wrong_login) || !$wrong_login) echo 'inline'; else echo 'none';?>">Already registered? <a href="#" onclick="showLogin();" style="color: red;">Sign in</a></span>
											<span id="signup_switch" style="font-size: 12px; font-weight: bold; color: black; display: <?php if (isset($wrong_login) && $wrong_login) echo 'inline'; else echo 'none';?>;">Not yet registered? <a href="#" onclick="showSignUp();" style="color: red;">Register here</a></span>
											<?php if (isset($wrong_login) && $wrong_login == 1) echo '<div id="wrong_login" style="font-size: 13px; font-weight: bold; color: red;  echo "display: none;";?>Username / Password do not match</div>'; ?>
											<?php if (isset($wrong_signup) && $wrong_signup == 1) echo '<div id="wrong_signup" style="font-size: 13px; font-weight: bold; color: red;  echo "display: none;";?>A User with that email address already exists</div>'; ?>
											
											<form class="mainDiv" action="<?php echo $CFG->wwwroot."/mod/sclipowebclass/login.php"; ?>" id="login" method="post" style="display: <?php if (isset($wrong_login) && $wrong_login == 1) echo "inline"; else echo "none"; ?>">

												<ul class="form">
													<li>
													<div class="label">
														<label for="userEmail">Email:</label>
													</div>
													<div class="element">
														<input type="text" name="userEmail" id="userEmail" maxlength="150" >
														<input type="hidden" name="redirectpage" value="<?php echo $redirectpage; ?>">
														<input type="hidden" name="delete" value="<?php echo $delete; ?>">
														<input type="hidden" name="add" value="<?php echo $form->modulename; ?>">
														<input type="hidden" name="id" value="<?php echo $form->course; ?>">
														<input type="hidden" name="section" value="<?php echo $form->section; ?>">
														<input type="hidden" name="sesskey" value="<?php echo $form->sesskey; ?>">
														<input type="hidden" name="wwwroot" value="<?php echo $CFG->wwwroot; ?>">
														<input type="hidden" name="showadd" value="<?php echo isset($_REQUEST["showadd"])?"1":"0"; ?>">
													</div>
													</li>
													<li>
													<div class="label">
														<label for="userPass">Password:</label>
													</div>
													<div class="element">
														<input type="password" name="userPass" id="userPass" maxlength="25">
													</div>
													</li>
													<li class="normalize top" style="padding-top:0"><input type="image" src="<?php echo $scimg."join-sign-in.png";?>" value="Login" id="login" style="margin-left: 29%"></li>
											
													</ul>
											</form>
											
											<form onsubmit="return validateForm(this);" name="signup" class="mainDiv" action="<?php echo $CFG->wwwroot."/mod/sclipowebclass/signup.php"; ?>" id="signup" method="post" style="display: <?php if ($wrong_login == 1) echo "none"; else echo "inline"; ?>;">
													<input type="hidden" name="redirectpage" value="<?php echo $redirectpage; ?>">
													<input type="hidden" name="add" value="<?php echo $form->modulename; ?>">
													<input type="hidden" name="delete" value="<?php echo $delete; ?>">
													<input type="hidden" name="id" value="<?php echo $form->course; ?>">
													<input type="hidden" name="section" value="<?php echo $form->section; ?>">
													<input type="hidden" name="sesskey" value="<?php echo $form->sesskey; ?>">
													<input type="hidden" name="wwwroot" value="<?php echo $CFG->wwwroot; ?>">
												<ul class="form">
													<li>
													<div class="label">
														<label for="userEmail">Email:</label>
													</div>
													<div class="element">
														<input type="text" name="userEmail" id="userEmailSignup" maxlength="150" onblur="return checkmail();"><br />
														<span id="chkmail"></span>
														<input type="hidden" name="reference" >
														<input type="hidden" name="webclass_reference" >
													</div>
													</li>
													<li>
													<div class="label">
														<label for="userPass">Password:</label>
													</div>
													<div class="element">
														<input type="password" name="userPass" id="userPassSignUp" maxlength="25"><br />
														<span id="chkpass"></span>
													</div>
													</li>
													<li>
													<div class="label">
														<label for="first_name">Full Name:</label>
													</div>
													<div class="element">
														<input type="text" onClick="if (this.value=='First Name') this.value='';" onblur="if (this.value=='') this.value='First Name';" value="First Name" default="First Name" class="autoclean" name="first_name" id="userFirstName" maxlength="255" style="width: 45%; float: left;" >
														<input type="text" onClick="if (this.value=='Last Name') this.value='';" onblur="if (this.value=='') this.value='Last Name';" value="Last Name" default="Last Name" class="autoclean" name="last_name" id="userLastName" maxlength="255" style="width: 45%; float: right;" >
													</div>
													</li>
													<li>
													<div class="label">
														<label for="gender">Gender:</label>
													</div>
													<div class="element">
														<input type="radio" name="gender" id="genderMale" value="M" style="width: auto;" selected="selected"><label style="display: inline;"> Male</label>
														<input type="radio" name="gender" id="genderFemale" value="F" style="width: auto; margin-left: 10px" ><label style="display: inline;"> Female</label>
													</div>
													</li>
													<li>
													<div class="label">
														<label>Date of Birth:</label>
													</div>

													<div class="elements">
														<select class="mSelect" name="birth_Month" id="Bmonth">
															<option value="1" >Jan</option>
															<option value="2" >Feb</option>
															<option value="3" >Mar</option>
															<option value="4" >Apr</option>
															<option value="5" >May</option>
															<option value="6" >Jun</option>
															<option value="7" >Jul</option>
															<option value="8" >Aug</option>
															<option value="9" >Sep</option>
															<option value="10" >Oct</option>
															<option value="11" >Nov</option>
															<option value="12" >Dec</option>
														</select>
														<select id="Bday" class="dSelect" name="birth_Day">
														<option value="1" label="01">01</option>
														<option value="2" label="02">02</option>
														<option value="3" label="03">03</option>
														<option value="4" label="04">04</option>
														<option value="5" label="05">05</option>
														<option value="6" label="06">06</option>
														<option value="7" label="07">07</option>
														<option value="8" label="08">08</option>
														<option value="9" label="09">09</option>
														<option value="10" label="10">10</option>
														<option value="11" label="11">11</option>
														<option value="12" label="12">12</option>
														<option value="13" label="13">13</option>
														<option value="14" label="14">14</option>
														<option value="15" label="15">15</option>
														<option value="16" label="16">16</option>
														<option value="17" label="17">17</option>
														<option selected="selected" value="18" label="18">18</option>
														<option value="19" label="19">19</option>
														<option value="20" label="20">20</option>
														<option value="21" label="21">21</option>
														<option value="22" label="22">22</option>
														<option value="23" label="23">23</option>
														<option value="24" label="24">24</option>
														<option value="25" label="25">25</option>
														<option value="26" label="26">26</option>
														<option value="27" label="27">27</option>
														<option value="28" label="28">28</option>
														<option value="29" label="29">29</option>
														<option value="30" label="30">30</option>
														<option value="31" label="31">31</option>
														</select><select id="Byear" class="ySelect" name="birth_Year">
														<option value="1996" label="1996">1996</option>
														<option value="1995" label="1995">1995</option>
														<option value="1994" label="1994">1994</option>
														<option value="1993" label="1993">1993</option>
														<option value="1992" label="1992">1992</option>
														<option value="1991" label="1991">1991</option>
														<option value="1990" label="1990">1990</option>
														<option value="1989" label="1989">1989</option>
														<option value="1988" label="1988">1988</option>
														<option value="1987" label="1987">1987</option>
														<option value="1986" label="1986">1986</option>
														<option value="1985" label="1985">1985</option>
														<option value="1984" label="1984">1984</option>
														<option value="1983" label="1983">1983</option>
														<option value="1982" label="1982">1982</option>
														<option value="1981" label="1981">1981</option>
														<option value="1980" label="1980">1980</option>
														<option value="1979" label="1979">1979</option>
														<option value="1978" label="1978">1978</option>
														<option value="1977" label="1977">1977</option>
														<option value="1976" label="1976">1976</option>
														<option value="1975" label="1975">1975</option>
														<option value="1974" label="1974">1974</option>
														<option value="1973" label="1973">1973</option>
														<option value="1972" label="1972">1972</option>
														<option value="1971" label="1971">1971</option>
														<option value="1970" label="1970">1970</option>
														<option value="1969" label="1969">1969</option>
														<option value="1968" label="1968">1968</option>
														<option value="1967" label="1967">1967</option>
														<option value="1966" label="1966">1966</option>
														<option value="1965" label="1965">1965</option>
														<option value="1964" label="1964">1964</option>
														<option value="1963" label="1963">1963</option>
														<option value="1962" label="1962">1962</option>
														<option value="1961" label="1961">1961</option>
														<option value="1960" label="1960">1960</option>
														<option value="1959" label="1959">1959</option>
														<option value="1958" label="1958">1958</option>
														<option value="1957" label="1957">1957</option>
														<option value="1956" label="1956">1956</option>
														<option value="1955" label="1955">1955</option>
														<option value="1954" label="1954">1954</option>
														<option value="1953" label="1953">1953</option>
														<option value="1952" label="1952">1952</option>
														<option value="1951" label="1951">1951</option>
														<option value="1950" label="1950">1950</option>
														<option value="1949" label="1949">1949</option>
														<option value="1948" label="1948">1948</option>
														<option value="1947" label="1947">1947</option>
														<option value="1946" label="1946">1946</option>
														<option value="1945" label="1945">1945</option>
														<option value="1944" label="1944">1944</option>
														<option value="1943" label="1943">1943</option>
														<option value="1942" label="1942">1942</option>
														<option value="1941" label="1941">1941</option>
														<option value="1940" label="1940">1940</option>
														<option value="1939" label="1939">1939</option>
														<option value="1938" label="1938">1938</option>
														<option value="1937" label="1937">1937</option>
														<option value="1936" label="1936">1936</option>
														<option value="1935" label="1935">1935</option>
														<option value="1934" label="1934">1934</option>
														<option value="1933" label="1933">1933</option>
														<option value="1932" label="1932">1932</option>
														<option value="1931" label="1931">1931</option>
														<option value="1930" label="1930">1930</option>
														<option value="1929" label="1929">1929</option>
														<option value="1928" label="1928">1928</option>
														<option value="1927" label="1927">1927</option>
														<option value="1926" label="1926">1926</option>
														<option value="1925" label="1925">1925</option>
														<option value="1924" label="1924">1924</option>
														<option value="1923" label="1923">1923</option>
														<option value="1922" label="1922">1922</option>
														<option value="1921" label="1921">1921</option>
														<option value="1920" label="1920">1920</option>
														<option value="1919" label="1919">1919</option>
														<option value="1918" label="1918">1918</option>
														<option value="1917" label="1917">1917</option>
														<option value="1916" label="1916">1916</option>
														<option value="1915" label="1915">1915</option>
														<option value="1914" label="1914">1914</option>
														<option value="1913" label="1913">1913</option>
														<option value="1912" label="1912">1912</option>
														<option value="1911" label="1911">1911</option>
														<option value="1910" label="1910">1910</option>
														<option value="1909" label="1909">1909</option>
														<option value="1908" label="1908">1908</option>
														<option value="1907" label="1907">1907</option>
														<option value="1906" label="1906">1906</option>
														<option value="1905" label="1905">1905</option>
														<option value="1904" label="1904">1904</option>
														<option value="1903" label="1903">1903</option>
														<option value="1902" label="1902">1902</option>
														<option value="1901" label="1901">1901</option>
														<option value="1900" label="1900">1900</option>
														<option value="1899" label="1899">1899</option>
														<option value="1898" label="1898">1898</option>
														<option value="1897" label="1897">1897</option>
														<option value="1896" label="1896">1896</option>
														</select>
														
													</div>
													</li>
													<li class="normalize" style="padding-top:0">
													<p class="inline-form">
														<div class="element" style="margin-left: 29%;">
															<input type="checkbox" class="checkbox" id="accept_terms" name="accept_terms" value="1"><label for="accept_terms"> I accept the <a href="http://sclipo.com/terms">Terms of Use.</a></label>
														</div>
													</p>
													</li>
													<li class="normalize top" style="padding-top:0"><input type="image" src="<?php echo $scimg."register-button.png";?>" value="Join!"  id="join" style="margin-left: 29%"></li>
												</ul>
											</form>
											
											
											
										</div>
									</div>
								</div>
				</div>
			
			</div>
		</div>
	</div>
</div>
</div>