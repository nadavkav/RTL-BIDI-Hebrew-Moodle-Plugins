<?php  // $Id: export.php,v 1.11 2009/08/29 05:24:22 bdaloukas Exp $
/**
 * This page edits the bottom text of a game
 * 
 * @author  bdaloukas
 * @version $Id: export.php,v 1.11 2009/08/29 05:24:22 bdaloukas Exp $
 * @package game
 **/
 
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");
    require_once("exportjavame.php");
    require_once("exporthtml.php");

	if( !isteacherinanycourse( $USER->id)){
		error( get_string( 'only_teachers', 'game'));
	}

    if( array_key_exists( 'action', $_POST)){
        $update = (int )$_POST[ 'update'];
	    $gameid = get_field_select("course_modules", "instance", "id=$update");
        game_OnExport( $gameid);
        die;
    }  

    $update = (int )$_GET[ 'update'];
	$gameid = get_field_select("course_modules", "instance", "id=$update");
    
	$_GET[ 'id'] = $update;
	require_once( "header.php");
		    
    echo '<form name="form" method="post" action="export.php">';
    
    $game = get_record_select( 'game', "id=$gameid", 'id,name,gamekind');

    switch( $_GET[ 'target'])
    {
    case 'html';
        game_export_html( $game, $update);
        break;
    case 'javame':
        game_export_javame( $game, $update);
        break;
    }

function game_export_javame( $game, $update)
{
    $javame = game_getjavame( $game->id); 
    
    if( $javame->description == ''){
        $javame->description = 'MoodleHangman';            
    }
    if( $javame->name == ''){
        $javame->name = 'moodlehangman';
    }
    if( $javame->version == ''){
        $javame->version = '1.0';
    }
    if( $javame->createdby == ''){
        $javame->createdby = 'module Game';
    }
    
?>    
<br/>

<table>
<tr><td colspan=2><center><b><?php echo get_string('export', 'game'); ?></td></tr>

<tr>
<td><?php echo get_string( 'javame_type', 'game'); ?></td>
<td>
<select name="type" >
   <option value="0" selected="selected">Hangman with phrases</option>
   <option value="hangmanp" >Hangman with pictures</option>
</select>
</td>
</tr>

<tr>
<td><?php echo get_string( 'javame_filename', 'game'); ?></td>
<td><input type="input" size="20" name="filename" value="<?php echo $javame->filename; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_icon', 'game'); ?></td>
<td><input type="input" size="20" name="icon" value="<?php echo $javame->icon; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_createdby', 'game'); ?></td>
<td><input type="input" size="20" name="createdby" value="<?php echo $javame->createdby; ?>"/></td>
</tr>


<tr>
<td><?php echo get_string( 'javame_vendor', 'game'); ?></td>
<td><input type="input" size="20" name="vendor" value="<?php echo $javame->vendor; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_name', 'game'); ?></td>
<td><input type="input" size="20" name="name" value="<?php echo $javame->name; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_description', 'game'); ?></td>
<td><input type="input" size="20" name="description" value="<?php echo $javame->description; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_version', 'game'); ?></td>
<td><input type="input" size="20" name="version" value="<?php echo $javame->version; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_maxpicturewidth', 'game'); ?></td>
<td><input type="input" size="20" name="maxpicturewidth" value="<?php echo $javame->maxpicturewidth; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_maxpictureheight', 'game'); ?></td>
<td><input type="input" size="20" name="maxpictureheight" value="<?php echo $javame->maxpictureheight; ?>"/></td>
</tr>


</table>


<!-- These hidden variables are always the same -->
<input type="hidden" name="update"        value="<?php  p($update) ?>" />
<input type="hidden" name="id"        value="<?php  p($javame->id) ?>" />
<input type="hidden" name="action"        value="update" /><br>
<input type="hidden" name="kind"        value="javame" /><br>
<input type="submit" value="<?php  print_string('export', 'game') ?>" />
</center>
</form>
<?php  
        
    print_footer();
}
    
    function game_OnExport( $gameid){
        $kind = $_POST[ 'kind'];
        if( $kind == 'javame'){
            $javame->id = $_POST[ 'id'];
            $javame->filename = $_POST[ 'filename'];
            $javame->icon = $_POST[ 'icon'];
            $javame->createdby = $_POST[ 'createdby'];
            $javame->vendor = $_POST[ 'vendor'];
            $javame->name = $_POST[ 'name'];
            $javame->description = $_POST[ 'description'];
            $javame->version = $_POST[ 'version'];
            $javame->type = $_POST[ 'type'];
            $javame->maxpicturewidth = $_POST[ 'maxpicturewidth'];
            $javame->maxpictureheight = $_POST[ 'maxpictureheight'];
        
	    	if (!(update_record( "game_export_javame", $javame))){
	    		error("game_export_javame: not updated id=$javame->id");
	        }
	        
            game_OnExportJavaME( $gameid, $javame);
        }else if( $kind == 'html'){
            $html->id = $_POST[ 'id'];
            if( array_key_exists( 'type', $_POST))
                $html->type = $_POST[ 'type'];
            else
                $html->type = '';
            $html->filename = $_POST[ 'filename'];
            $html->title = $_POST[ 'title'];
            if( array_key_exists( 'maxpicturewidth', $_POST))
                $html->maxpicturewidth = $_POST[ 'maxpicturewidth'];
            else
                $html->maxpicturewidth = 0;
            if( array_key_exists( 'maxpictureheight', $_POST))
                $html->maxpictureheight = $_POST[ 'maxpictureheight'];
            else
                $html->maxpictureheight = 0;
            if( array_key_exists( 'checkbutton', $_POST))
                $html->checkbutton = ($_POST[ 'checkbutton'] ? 1: 0);
            if( array_key_exists( 'printbutton', $_POST))
                $html->printbutton = ($_POST[ 'printbutton'] ? 1: 0);
        
	    	if (!(update_record( "game_export_html", $html))){
	    		error("game_export_html: not updated id=$html->id");
	        }
	        
	        $update = $_POST[ 'update'];
	            
            require_once("exporthtml.php");
                      
            game_OnExportHTML( $gameid, $html, $update);            
        }
    }
    
    function game_getjavame( $gameid){
        $rec = get_record_select( 'game_export_javame', "gameid=$gameid");
        if( $rec == false){           
            $rec->gameid = $gameid;     
            if (!insert_record( "game_export_javame", $rec)){
                error("Insert page: new page mdl_game_export_javame not inserted");
            }
            $rec = get_record_select( 'game_export_javame', "gameid=$gameid");
        }
        
        if( $rec->vendor == ''){
            $rec->vendor = 'module Game';
        }
        
        return $rec;
    }

function game_export_html( $game, $update)
{
    $html = game_gethtml_rec( $game->id);
    
    switch( $game->gamekind)
    {
    case 'cross':
        if( $html->title == ''){
            $html->title = get_string( 'game_cross', 'game');
        }
        if( $html->filename == ''){
            $html->filename = 'crossword';
        }
        break;
    case 'hangman':
        if( $html->title == ''){
            $html->title = get_string( 'game_hangman', 'game');
        }
        if( $html->filename == ''){
            $html->filename = 'hangman';
        }
        break;
    case 'millionaire':
        if( $html->title == ''){
            $html->title = get_string( 'game_millionaire', 'game');
        }
        if( $html->filename == ''){
            $html->filename = 'millionaire';
        }
        break;    
    }
    
?>    
<br/>

<table>
<tr><td colspan=2><center><b><?php echo get_string('export', 'game'); ?></td></tr>

<?php
if( $game->gamekind == 'hangman')
{
?>
<tr>
<td><?php echo get_string( 'javame_type', 'game'); ?></td>
<td>
<select name="type" >
   <option value="0" selected="selected">Hangman with phrases</option>
   <option value="hangmanp" >Hangman with pictures</option>
</select>
</td>
</tr>
<?php
}
?>

<tr>
<td><?php echo get_string( 'javame_filename', 'game'); ?></td>
<td><input type="input" size="30" name="filename" value="<?php echo $html->filename; ?>"/></td>
</tr>


<tr>
<td><?php echo get_string( 'html_title', 'game'); ?></td>
<td><input type="input" size="100" name="title" value="<?php echo $html->title; ?>"/></td>
</tr>

<?php
if( $game->gamekind == 'cross')
{
//fields for cross
?>

<tr>
<td><?php echo get_string( 'html_hascheckbutton', 'game'); ?></td>
<td>
<select id="checkbutton" name="checkbutton" >
   <option value="1" selected="selected"><?php echo get_string( 'yes'); ?></option>
   <option value="0"><?php echo get_string( 'no'); ?></option>
</select>
</td>
</tr>

<tr>
<td><?php echo get_string( 'html_hasprintbutton', 'game'); ?></td>
<td>
<select id="printbutton" name="printbutton" >
   <option value="1" selected="selected"><?php echo get_string( 'yes'); ?></option>
   <option value="0"><?php echo get_string( 'no'); ?></option>
</select>
</td>
</tr>
<?php
}

//fields for hangman
if( $game->gamekind == 'hangman')
{
?>
<tr>
<td><?php echo get_string( 'javame_maxpicturewidth', 'game'); ?></td>
<td><input type="input" size="20" name="maxpicturewidth" value="<?php echo $html->maxpicturewidth; ?>"/></td>
</tr>

<tr>
<td><?php echo get_string( 'javame_maxpictureheight', 'game'); ?></td>
<td><input type="input" size="20" name="maxpictureheight" value="<?php echo $html->maxpictureheight; ?>"/></td>
</tr>

<?php
}
?>

</table>


<!-- These hidden variables are always the same -->
<input type="hidden" name="update"        value="<?php  p($update) ?>" />
<input type="hidden" name="id"        value="<?php  p($html->id) ?>" />
<input type="hidden" name="action"        value="update" /><br>
<input type="hidden" name="kind"        value="html" /><br>
<input type="submit" value="<?php  print_string('export', 'game') ?>" />
</center>
</form>
<?php  
        
    print_footer();
}

    function game_gethtml_rec( $gameid){
        $rec = get_record_select( 'game_export_html', "gameid=$gameid");
        if( $rec == false){           
            $rec->gameid = $gameid; 
            if (!insert_record( "game_export_html", $rec)){
                error("Insert page: new page mdl_game_export_html not inserted");
            }
            $rec = get_record_select( 'game_export_html', "gameid=$gameid");
        }
                
        return $rec;
    }



?>
