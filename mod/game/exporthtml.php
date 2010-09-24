<?php  // $Id: exporthtml.php,v 1.8 2009/08/28 23:43:25 bdaloukas Exp $
/**
 * This page export the game to html for games: cross, hangman
 * 
 * @author  bdaloukas
 * @version $Id: exporthtml.php,v 1.8 2009/08/28 23:43:25 bdaloukas Exp $
 * @package game
 **/
 
    require_once( "exportjavame.php");
        
    function game_OnExportHTML( $gameid, $html, $update){
        global $CFG;
        
        $game = get_record_select( 'game', "id=$gameid");          
        
        if( $game->gamekind == 'cross'){
            $destdir = "{$CFG->dataroot}/{$game->course}/export";
            if( !file_exists( $destdir)){
                mkdir( $destdir);
            }
            game_OnExportHTML_cross( $game, $html, $update, $destdir);
            return;
        }
        
        $destdir = game_export_createtempdir();
                
        switch( $game->gamekind){
        case 'hangman':
            game_OnExportHTML_hangman( $game, $html, $update);
            break;
        case 'millionaire':
            game_OnExportHTML_millionaire( $game, $html, $update, $destdir);
            break;
        }
        
        if( $destdir != ''){
            remove_dir( $destdir);
        }

    }
    
    function game_OnExportHTML_cross( $game, $html, $update, $destdir){
  
        global $CFG;
    
        if( $html->filename == ''){
            $html->filename = 'cross';
        }
        
        $filename = $html->filename . '.htm';
        
        require( "cross/play.php");
        $attempt = false;
        game_getattempt( $game, $crossrec);
        
        $ret = game_export_printheader( $html->title);
        
        echo "$ret<br>";
        
        ob_start();

        game_cross_play( $update, $game, $attempt, $crossrec, '', true, false, false, false, $html->checkbutton, true, $html->printbutton);

        $output_string = ob_get_contents();
        ob_end_clean();
                
        $course = get_record_select( 'course', "id={$game->course}");
        
        $filename = $html->filename . '.htm';
        
        file_put_contents( $destdir.'/'.$filename, $ret . "\r\n" . $output_string);
                        
        echo "$ret<a href=\"{$CFG->wwwroot}/file.php/{$game->course}/export/$filename\">{$filename}</a>";
    }
    
    function game_export_printheader( $title, $showbody=true)
    {
        $ret = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
        $ret .= '<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="el" xml:lang="el">'."\n";
        $ret .= "<head>\n";
        $ret .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
        $ret .= '<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">'."\n";
        $ret .= "<title>$title</title>\n";
        $ret .= "</head>\n";
        if( $showbody)
            $ret .= "<body>";              
        
        return $ret;
    }    
    
    function game_OnExportHTML_hangman( $game, $html, $update){
    
        global $CFG;
        
        if( $html->filename == ''){
            $html->filename = 'hangman';
        }
        
        $filename = $html->filename . '.htm';
        
        $ret = game_export_printheader( $html->title, false);
        $ret .= "\r<body onload=\"reset()\">\r";

        ob_start();
        
        //Here is the code of hangman
        require( "exporthtml_hangman.php");        
                        
        $output_string = ob_get_contents();
        ob_end_clean();
                        
        $courseid = $game->course;
        $course = get_record_select( 'course', "id=$courseid");
                
        $filename = $html->filename . '.htm';
        
        file_put_contents( $destdir.'/'.$filename, $ret . "\r\n" . $output_string);
        
        if( $html->type != 'hangmanp')
        {
            //Not copy the standard pictures when we use the "Hangman with pictures"
            $src = $CFG->dirroot.'/mod/game/hangman/1';                
	    	$handle = opendir( $src);
	    	while (false!==($item = readdir($handle))) {
	    		if($item != '.' && $item != '..') {
	    			if(!is_dir($src.'/'.$item)) {
	    			    $itemdest = $item;

	    			    if( strpos( $item, '.') === false)
	    			        continue;

	    				copy( $src.'/'.$item, $destdir.'/'.$itemdest);
	    			}
	    		}
	    	}
	    }
		
		$filezip = game_create_zip( $destdir, $courseid, $html->filename.'.zip');		
                        
        echo "$ret<a href=\"{$CFG->wwwroot}/file.php/$courseid/export/$filezip\">{$filezip}</a>";
    }


    function game_OnExportHTML_millionaire( $game, $html, $update, $destdir){
    
        global $CFG;
        
        if( $html->filename == ''){
            $html->filename = 'millionaire';
        }
        
        $filename = $html->filename . '.htm';
        
        $ret = game_export_printheader( $html->title, false);
        $ret .= "\r<body onload=\"Reset();\">\r";

        ob_start();
                        
        //Here is the code of millionaire
        require( "exporthtml_millionaire.php");

        //End of millionaire code        
        $output_string = ob_get_contents();
        ob_end_clean();
                        
        $courseid = $game->course;
        $course = get_record_select( 'course', "id=$courseid");
                
        $filename = $html->filename . '.htm';
        
        file_put_contents( $destdir.'/'.$filename, $ret . "\r\n" . $output_string);
        
        if( $html->type != 'hangmanp')
        {
            //Not copy the standard pictures when we use the "Hangman with pictures"
            $src = $CFG->dirroot.'/mod/game/millionaire/1';                
	    	$handle = opendir( $src);
	    	while (false!==($item = readdir($handle))) {
	    		if($item != '.' && $item != '..') {
	    			if(!is_dir($src.'/'.$item)) {
	    			    $itemdest = $item;

	    			    if( strpos( $item, '.') === false)
	    			        continue;

	    				copy( $src.'/'.$item, $destdir.'/'.$itemdest);
	    			}
	    		}
	    	}
	    }
		
		$filezip = game_create_zip( $destdir, $courseid, $html->filename.'.zip');
		
        if( $destdir != ''){
            remove_dir( $destdir);
        }		
                        
        echo "$ret<a href=\"{$CFG->wwwroot}/file.php/$courseid/export/$filezip\">{$filezip}</a>";
}

?>
