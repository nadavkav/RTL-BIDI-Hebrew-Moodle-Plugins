<?php  // $Id: print.php,v 1.1 2008/11/06 23:16:45 bdaloukas Exp $
/**
 * This page export the game to html
 * 
 * @author  bdaloukas
 * @version $Id: print.php,v 1.1 2008/11/06 23:16:45 bdaloukas Exp $
 * @package game
 **/
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");
    
    $id = $_GET[ 'id'];
    $gameid = $_GET[ 'gameid'];

    $game = get_record_select( 'game', "id=$gameid");
    
    require_login( $game->course);
    
    game_print( $game, $id);
        
    function game_print( $game, $update){
        
        if( $game->gamekind == 'cross'){
            game_print_cross( $game, $update);
        }
    }
    
    function game_print_cross( $game, $update){
    
        global $CFG;
                        
        require( "cross/play.php");
        $attempt = false;
        game_getattempt( $game, &$crossrec); 
        
        game_cross_play( $update, $game, $attempt, $crossrec, '', true, false, false, true, false, false, false);

    }    

?>
