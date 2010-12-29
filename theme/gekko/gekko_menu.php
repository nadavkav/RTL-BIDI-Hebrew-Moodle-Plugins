<div id="gekko_menu_date">
<a href="<?php echo $CFG->wwwroot; ?>/calendar/view.php"><script language="Javascript" type="text/javascript">
//<![CDATA[
<!--

// Get today's current date.
var now = new Date();

// Array list of days.
var days = new Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');

// Array list of months.
var months = new Array('January','February','March','April','May','June','July','August','September','October','November','December');

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

<div id="dropdown" class="yuimenubar yuimenubarnav">
      <div class="bd">
        <ul class="first-of-type">
        	<li class="yuimenubaritem first-of-type"><a class="yuimenubaritemlabel" href="<?php echo $CFG->wwwroot; ?>"><img width="18" height="17" src="<?php echo $CFG->httpswwwroot.'/theme/'.current_theme() ?>/images/menu/home_icon.png" alt=""/></a></li>
          <li class="yuimenubaritem"><a class="yuimenubaritemlabel" href="#">Courses</a>
          
            <div class="yuimenu">
              <div class="bd">
                <ul>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">First Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Second Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Third Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Fourth Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Fifth Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Sixth Menu Item</a>
                  
                    <div class="yuimenu">
                      <div class="bd">
                        <ul class="first-of-type">
                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">First Sub Item</a></li>
                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Second Sub Item</a></li>
                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Third Sub Item</a>
                            
                            <div class="yuimenu">
                              <div class="bd">
                                <ul class="first-of-type">
                                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">First Sub Sub Item</a></li>
                                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Second Sub Sub Item</a></li>
                                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Third Sub Sub Item</a>
                                    
                                    <div class="yuimenu">
                                      <div class="bd">
                                        <ul class="first-of-type">
                                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">First Sub Sub Sub Item</a></li>
                                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Second Sub Sub Sub Item</a></li>
                                         <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Third Sub Sub Sub Item</a></li>
                                        </ul>
                                      </div>
                                    </div>
                                    
                                  </li>
                                </ul>
                              </div>
                            </div>
                            
                          </li>
                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Fourth Sub Item</a></li>
                        </ul>            
                      </div>
                    </div>
                                        
                  </li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Seventh Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Eighth Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Ninth Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Tenth Menu Item</a></li>
                </ul>
              </div>
            </div>      
            
          </li>
          <li class="yuimenubaritem"><a class="yuimenubaritemlabel" href="http://www.google.com">Google</a>
          
            <div class="yuimenu">
              <div class="bd">                    
                <ul>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#"><img width="20" height="19" src="<?php echo $CFG->httpswwwroot.'/theme/'.current_theme() ?>/images/menu/gmail.png" alt=""/> Gmail</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#"><img width="20" height="19" src="<?php echo $CFG->httpswwwroot.'/theme/'.current_theme() ?>/images/menu/gdocs.png" alt=""/> Docs</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#"><img width="20" height="19" src="<?php echo $CFG->httpswwwroot.'/theme/'.current_theme() ?>/images/menu/video.png" alt=""/> Video</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#"><img width="20" height="19" src="<?php echo $CFG->httpswwwroot.'/theme/'.current_theme() ?>/images/menu/sites.png" alt=""/> Sites</a></li>
                  </ul>
              </div>
            </div>
                                
          </li>
          <li class="yuimenubaritem"><a class="yuimenubaritemlabel" href="#">My Moodle</a>
          
            <div class="yuimenu">
              <div class="bd">                    
                <ul>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">First Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Second Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Third Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Fourth Menu Item</a>

                    <div class="yuimenu">
                      <div class="bd">
                        <ul class="first-of-type">
                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">First Sub Item</a></li>
                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Second Sub Item</a></li>
                          <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Third Sub Item</a></li>
                        </ul>            
                      </div>
                    </div>                    

                  </li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Fifth Menu Item</a></li>
                </ul>                    
              </div>
            </div>                                        

          </li>
          <li class="yuimenubaritem"><a class="yuimenubaritemlabel" href="#">Staff Zone</a>

            <div class="yuimenu">
              <div class="bd">                                        
                <ul>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">First Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Second Menu Item</a></li>
                  <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#">Third Menu Item</a></li>
                </ul>
              </div>
            </div>                                        

          </li>
        </ul>     
               
      </div>
    </div>
