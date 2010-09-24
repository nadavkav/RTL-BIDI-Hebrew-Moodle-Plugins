<?php  // $Id: bottomtext.php,v 1.3 2009/08/31 18:31:13 bdaloukas Exp $
/**
 * This page edits the bottom text of a game
 * 
 * @author  bdaloukas
 * @version $Id: bottomtext.php,v 1.3 2009/08/31 18:31:13 bdaloukas Exp $
 * @package game
 **/
 
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    if( array_key_exists( 'top', $_GET) or array_key_exists( 'top', $_POST)){
        $top = 1;
        $field = 'toptext';
    }else
    {
        $top = 0;
        $field = 'bottomtext';
    }
    
    if( array_key_exists( 'action', $_POST)){
        game_bottomtext_onupdate();
        die;
    }

	$update = (int )$_GET[ 'update'];
	$_GET[ 'id'] = $update;
	require_once( "header.php");

  	$sesskey = $_GET[ 'sesskey'];
	
	if( !isteacherinanycourse( $USER->id)){
		error( get_string( 'only_teachers', 'game'));
	}
	
	$gameid = get_field_select("course_modules", "instance", "id=$update");
    $usehtmleditor = true;
    
    echo '<b>'.get_string("create$field", 'game').'</b><br>';
    echo '<form name="form" method="post" action="bottomtext.php">';
    
    $game = get_record_select( 'game', "id=$gameid", 'id,course,'.$field);
    print_textarea( $usehtmleditor, 20, 60, 630, 300, $field, $game->$field, $game->course);
    use_html_editor();

?>    
<br/>
<!-- These hidden variables are always the same -->
<input type="hidden" name="update"        value="<?php  p($update) ?>" />
<input type="hidden" name="sesskey"        value="<?php  p($sesskey) ?>" />
<input type="hidden" name="top"        value="<?php  p($top) ?>" />
<input type="hidden" name="action"        value="update" />
<input type="submit" value="<?php  print_string("savechanges") ?>" />
</center>
</form>
<?php  
        
    print_footer();
    
    
    
    
function game_bottomtext_onupdate(){
    global $CFG;
    
  	$update = $_POST[ 'update'];
  	$sesskey = $_POST[ 'sesskey'];
  	$top = $_POST[ 'top'];
  	
  	$field = ($top ? 'toptext' : 'bottomtext');

	$gameid = get_field_select("course_modules", "instance", "id=$update");
	
	$game->id = $gameid;
	$game->$field = $_POST[ $field];

    if( !update_record( 'game', $game)){
        error( "game_bottomtext_onupdate: Can't update game id=$game->id");
    }
    
    redirect( "{$CFG->wwwroot}/course/mod.php?update=$update&sesskey=$sesskey&sr=1");
}


?>
