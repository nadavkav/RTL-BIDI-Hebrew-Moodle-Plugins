<?php


/// Standard functions /////////////////////////////////////////////////////////

function skype_add_instance($skype) {
// Given an object containing all the necessary data,
// (defined by the form in mod.html) this function
// will create a new instance and return the id number
// of the new instance.

    $skype->timemodified = time();

    if (!empty($skype->timerestrict) and $skype->timerestrict) {
        $skype->timeopen = make_timestamp($skype->openyear, $skype->openmonth, $skype->openday,
                                     $skype->openhour, $skype->openminute, 0);
        $skype->timeclose = make_timestamp($skype->closeyear, $skype->closemonth, $skype->closeday,
                                      $skype->closehour, $skype->closeminute, 0);
    } else {
        $skype->timeopen = 0;
        $skype->timeclose = 0;
    }

    //insert answers
    if (!$skype->id = insert_record("skype", $skype)) {

      error("Could not add new instance");

    }
    return $skype->id;
}


function skype_update_instance($skype) {
// Given an object containing all the necessary data,
// (defined by the form in mod.html) this function
// will update an existing instance with new data.

    $skype->id = $skype->instance;
    $skype->timemodified = time();

    return update_record('skype', $skype);

}

function skype_show($skype, $user, $cm, $what="form") {

   global $USER;

   $which_group = user_group($cm->course, $user->id);

   if(!$which_group){
	 $g_id = 0;
   } else{
	 foreach($which_group as $www=>$xxx) $g_id = $xxx->id ;
   }

	if( $skype->participants =='0'){   //0 for students, 1 for tutors of course
	  if($g_id==0)  $call_users =get_course_users($cm->course); // this is admin, so he can access everything!
	  else
	  $call_users = get_group_users($g_id);
	}
	else {
	  $call_users = get_course_teachers($cm->course);
	}

	$return="";

	if($call_users) {
		foreach ($call_users as $call_user) {
			if($call_user->skype) {
				$skypeid = $call_user->skype;
				if ($what=="casts") {

					$return .='
					<script type="text/javascript" src="https://feedsskypecasts.skype.com/skypecasts/webservice/get.js?limit=100&amp;user='.$skypeid.'"></script>
					<script language="javascript" type="text/javascript">//<![CDATA[
					var cast;
					var cntx=0;
					for(i in Skypecasts) {
					  cntx=1;
					  cast = Skypecasts[i];
					  document.write("<a target=\"_blank\" href=\""+cast.url_info+"\"><img src=\"skypecast_icon.gif\" border=0 width=\"76\" height=\"76\" alt=\""+cast.title+"\" /></a>");
					  document.write("<p class=\"skypecast-title\"><a target=\"_blank\" href=\""+cast.url_info+"\">"+cast.title+"</a></p>");
					  document.write("<p class=\"skypecast-host\">'.get_string("moderator", "skype").': "+cast.host_name+"</p>");
					  document.write("<p class=\"skypecast-date\">"+cast.start_time_hint+"</p>");
					}
					 if(cntx == 0){
					   document.write("<p class=\"skypecast-title\">There are no Skypecasts for you!</p><br><br><br>");
					   document.write("<p class=\"skypecast-title\"><br><br><br><br><br><br><br><br>&nbsp;</p>");
					 }
					//]]></script>
					';

				} else {

					if($USER->id != $call_user->id) {

						$return .= "
						<option value='$skypeid'>".fullname($call_user, true)."</option>";

					}

				}
			}

		}

	} else {
		 $call_users = get_course_users($cm->course);
		 if($call_users) {

				foreach ($call_users as $call_user) {

					if($call_user->skype) {
					  	$skypeid = $call_user->skype;
						if ($what=="casts") {

							$return .='
							<script type="text/javascript" src="https://feedsskypecasts.skype.com/skypecasts/webservice/get.js?limit=100&amp;user='.$skypeid.'"></script>
							<script language="javascript" type="text/javascript">//<![CDATA[
							var cast;
							var cntx=0;
							for(i in Skypecasts) {
							  cntx=1;
							  cast = Skypecasts[i];
							  document.write("<a target=\"_blank\" href=\""+cast.url_info+"\"><img src=\"skypecast_icon.gif\" border=0 width=\"76\" height=\"76\" alt=\""+cast.title+"\" /></a>");
							  document.write("<p class=\"skypecast-title\"><a target=\"_blank\" href=\""+cast.url_info+"\">"+cast.title+"</a></p>");
							  document.write("<p class=\"skypecast-host\">'.get_string("moderator", "skype").': "+cast.host_name+"</p>");
							  document.write("<p class=\"skypecast-date\">"+cast.start_time_hint+"</p>");
							}
							 if(cntx == 0){
							   document.write("<p class=\"skypecast-title\">There are no Skypecasts for you!</p><br><br><br>");
							   document.write("<p class=\"skypecast-title\"><br><br><br><br><br><br><br><br>&nbsp;</p>");
							 }
							//]]></script>
							';

						} else {

							if($USER->id != $call_user->id) {

								$return .= "
								<option value='$skypeid'>".fullname($call_user, true)."</option>";

							}

						}

					}

				}
		 }

	}

	return $return;

}





function skype_delete_instance($id) {
// Given an ID of an instance of this module,
// this function will permanently delete the instance
// and any data that depends on it.

    if (! $skype = get_record("skype", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("skype", "id", "$skype->id")) {
        $result = false;
    }


    return $result;
}


function skype_get_participants($skypeid) {
//Returns the users with data in one skype
//(users with records in skype_responses, students)

    global $CFG;

    //Get students
    $students = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}skype_answers a
                                 WHERE a.skypeid = '$skypeid' and
                                       u.id = a.userid and
									   u.id<>$USER->id");

    //Return students array (it contains an array of unique users)
    return ($students);
}




function skype_get_skype($skypeid) {
// Gets a full skype record

    if ($skype = get_record("skype", "id", $skypeid)) {

        return $skype;
    }
    return false;
}



?>
