<?php

/**
   @author Jochen Lackner, Markus Pusswald
   @license http://opensource.org/licenses/gpl-license.php GNU Public License

   Use given choice to create groups (named as the options within choice are named) and
   assign users to this groups (depending on their choice).
*/

require_once('../../config.php');
require_once('../../version.php');
require_login();
global $USER, $release;


if (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = $_SERVER['HTTP_REFERER'];
} else {
    $referrer = $CFG->wwwroot.'/';
}


// Ensure that the logged in user is not using the guest account
if (isguest()) {
    error(get_string('noguestpost', 'forum'), $referrer);
}


$id        = optional_param('id', SITEID, PARAM_INT);
$action_id = $_POST["myRadio"];									//get posted value for action to be executed
$selection = optional_param('selection',NULL,PARAM_INT);					//selection to be used for processing

if (!defined('MAGPIE_OUTPUT_ENCODING')) {
    define('MAGPIE_OUTPUT_ENCODING', 'utf-8');  // see bug 3107
}


if (!empty($id)) {
    // we get the complete $course object here because print_header assumes this is
    // a complete object (needed for proper course theme settings)
    $course = get_record('course', 'id', $id);
}

$straddedit = get_string('mode1_action', 'block_choice_to_group');


if (!empty($course)) {
    $navigation = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$id.'">'.$course->shortname.'</a> -> '.$straddedit;
} else {
    $navigation = $straddedit;
}

print_header($straddedit, $straddedit);//, $navigation);

echo "<h1>".get_string("is_processing","block_choice_to_group")."</h1>";

print_box_start(); //Start frame for background

if ( $action_id == 1 ){
  $groupids = get_groups($id); // Get all groups in course
  if ($groupids){
    foreach ($groupids as $groupid) groups_delete_group($groupid->id); //delete the old groups and members
    echo "<h2>".get_string("deletion_successful","block_choice_to_group")."</h2>";
  }
}

if ( $action_id >= 1 ){

  $options = get_records_sql("SELECT id, text FROM {$CFG->prefix}choice_options where choiceid=$selection"); //get options from selected choice (=groupnames)

  $groups = array();

  foreach ($options as $option){
    $dataobject_group->name = $option->text;
    $dataobject_group->courseid = $id ;						//assign course-id to dataobject

    //version check
    if ($release < "1.9") {
      $groups[$option->text] = groups_create_group($id,$dataobject_group);
    }
    else {
      $groups[$option->text] = groups_create_group($dataobject_group);
    }

    echo $dataobject_group->name."<BR>";
  }
  echo "<h2>".get_string("group_creation_successful","block_choice_to_group")."</h2>";

  $choices = get_records_sql("SELECT userid, optionid FROM {$CFG->prefix}choice_answers where choiceid=$selection");
  if ($choices){
    foreach ($choices as $choice) {
      $dataobject_member_group->userid = $choice->userid;					//store id of user that made this choice

      $groupname = $options[$choice->{optionid}]->{text};					//get title of choice (=groupname) from choice_options-table
      $usernames = get_records_sql("SELECT id, firstname, lastname FROM {$CFG->prefix}user where id={$choice->userid}");

      echo get_string("add_user","block_choice_to_group")." <STRONG>".
            $usernames[$choice->{userid}]->{firstname}." ".
            $usernames[$choice->{userid}]->{lastname}." </STRONG>".
            get_string("to_group","block_choice_to_group")." <STRONG>".$groupname."</STRONG> ".
            get_string("hinzu","block_choice_to_group")."<BR>";

      $dataobject_member_group->groupid = $groups[ $groupname ];
      $check = insert_record("groups_members", $dataobject_member_group);
      if (!$check) break;

    }
    if ( $check ) {
      echo "<h2>".get_string("user_assignment_successful","block_choice_to_group")."</h2>";
    }
  }
  else {
    echo "<h2>".get_string("no_users","block_choice_to_group")."</h2>";
  }
}
print_box_end();
echo "<form action=\"".$CFG->wwwroot."/course/view.php?id=".$id."\" method=\"post\" id=\"block_rss\">";
echo "<input type=\"submit\" value=\"".get_string("continue","moodle")."\">";
echo "</form>";
print_footer();
?>
