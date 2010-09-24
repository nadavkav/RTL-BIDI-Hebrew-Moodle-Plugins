<?php  // $Id: attempt.php,v 1.6 2008/11/06 23:16:45 bdaloukas Exp $
/**
 * This page prints a particular attempt of game
 * 
 * @author  bdaloukas
 * @version $Id: attempt.php,v 1.6 2008/11/06 23:16:45 bdaloukas Exp $
 * @package game
 **/
 
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");
	require_once( "header.php");

    require_once( "hangman/play.php");
    require_once( "cross/play.php");
    require_once( "cryptex/play.php");
    require_once( "millionaire/play.php");
    require_once( "sudoku/play.php");
    require_once( "bookquiz/play.php");
    require_once( "snakes/play.php");
    require_once( "hiddenpicture/play.php");
	
	$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Teacher has requested new preview

/// Print the main part of the page
	switch( $action)
	{
	case 'crosscheck':
		$attempt = game_getattempt( $game, $detail);
		$g = game_cross_unpackpuzzle( $_GET[ 'g']);
		$finishattempt = array_key_exists( 'finishattempt', $_GET);
		game_cross_continue( $id, $game, $attempt, $detail, $g, $finishattempt);
		break;
	case 'crossprint':
		$attempt = game_getattempt( $game, $detail);
		game_cross_play( $id, $game, $attempt, $detail, '', true, false, false, true);
		break;
    case 'sudokucheck':		//the student tries to answer a question
		$attempt = game_getattempt( $game, $detail);
		$finishattempt = array_key_exists( 'finishattempt', $_POST);
		game_sudoku_check_questions( $id, $game, $attempt, $detail, $finishattempt);
        break;
    case 'sudokucheckg':		//the student tries to guess a glossaryenry
		$attempt = game_getattempt( $game, $detail);
		$endofgame = array_key_exists( 'endofgame', $_GET);
		game_sudoku_check_glossaryentries( $id, $game, $attempt, $detail, $endofgame);
        break;
    case 'sudokucheckn':	//the user tries to guess a number
		$attempt = game_getattempt( $game, $detail);
		$pos = $_GET[ 'pos'];
		$num = $_GET[ 'num'];
		game_sudoku_check_number( $id, $game, $attempt, $detail, $pos, $num);
        break;
	case 'cryptexcheck':	//the user tries to guess a question
		$attempt = game_getattempt( $game, $detail);
		$q = $_GET[ 'q'];
		$answer = $_GET[ 'answer'];
		game_cryptex_check( $id, $game, $attempt, $detail, $q, $answer);
        break;
    case 'bookquizcheck':		//the student tries to answer a question
		$attempt = game_getattempt( $game, $detail);
		game_bookquiz_check_questions( $id, $game, $attempt, $detail);
        break;
    case 'snakescheck':		//the student tries to answer a question
		$attempt = game_getattempt( $game, $detail);
		game_snakes_check_questions( $id, $game, $attempt, $detail);
        break;
    case 'snakescheckg':		//the student tries to answer a question
		$attempt = game_getattempt( $game, $detail);
		game_snakes_check_glossary( $id, $game, $attempt, $detail);
        break;
        
    case 'hiddenpicturecheck':		//the student tries to answer a question
		$attempt = game_getattempt( $game, $detail);
		$finishattempt = array_key_exists( 'finishattempt', $_POST);
		game_hiddenpicture_check_questions( $id, $game, $attempt, $detail, $finishattempt);
        break;
    case 'hiddenpicturecheckg':		//the student tries to guess a glossaryenry
		$attempt = game_getattempt( $game, $detail);
		$endofgame = array_key_exists( 'endofgame', $_GET);
		game_hiddenpicture_check_mainquestion( $id, $game, $attempt, $detail, $endofgame);
        break;        
	case "":
		game_create( $game, $id, $forcenew, $course);
		break;
	default:
		error( 'Not found action='.$action);
	}
/// Finish the page
    print_footer($course);


	function game_create( $game, $id, $forcenew, $course)
	{
		global $USER, $CFG;
		
		$attempt = game_getattempt( $game, $detail);

		switch( $game->gamekind)
		{
		case 'cross':
			game_cross_continue( $id, $game, $attempt, $detail, '', $forcenew);
			break;
		case 'hangman':
			if( array_key_exists( 'newletter', $_GET))
				$newletter = $_GET[ 'newletter'];
			else
				$newletter = '';
			if( array_key_exists( 'action2', $_GET))
				$action2 = $_GET[ 'action2'];
			else
				$action2 = '';
			game_hangman_continue( $id, $game, $attempt, $detail, $newletter, $action2);
			break;
		case 'millionaire':
			game_millionaire_continue( $id, $game, $attempt, $detail);
			break;
		case 'bookquiz':
			if( array_key_exists( 'chapterid', $_GET))
				$chapterid = (int )$_GET[ 'chapterid'];
			else
				$chapterid = 0;		
			game_bookquiz_continue( $id, $game, $attempt, $detail, $chapterid);
			break;
		case 'sudoku':
			game_sudoku_continue( $id, $game, $attempt, $detail);
			break;
		case 'cryptex':
			game_cryptex_continue( $id, $game, $attempt, $detail, $forcenew);
			break;
		case 'snakes':
			game_snakes_continue( $id, $game, $attempt, $detail);
			break;
		case 'hiddenpicture':
			game_hiddenpicture_continue( $id, $game, $attempt, $detail);
			break;
		case '':
			echo get_string( 'useupdategame', 'game');
			print_continue($CFG->wwwroot . '/course/view.php?id=' . $course->id);
			break;
		default:
			error( "Game {$game->gamekind} not found");
			break;
		}
	}
	
	//inserts a record to game_attempts
	function game_addattempt( $game)
	{
		global $CFG, $USER;
		
		$newrec->gamekind = $game->gamekind;
		$newrec->gameid = $game->id;
		$newrec->userid = $USER->id;
		$newrec->timestart = time();
		$newrec->timefinish = 0;
		$newrec->timelastattempt = 0;
		$newrec->preview = 0;
		$newrec->attempt = get_field( 'game_attempts', 'max(attempt)', 'gameid', $game->id, 'userid', $USER->id) + 1;
		$newrec->score = 0;

		if (!($newid = insert_record( 'game_attempts', $newrec))){
			error("Insert game_attempts: new rec not inserted");
		}
		
		if( $USER->username == 'guest'){
			$key = 'mod/game:instanceid'.$game->id;
			$_SESSION[ $key] = $newid;
		}

		return get_record_select( 'game_attempts', 'id='.$newid);
	}
	
	
function game_cross_unpackpuzzle( $g)
{
	$ret = "";
	$textlib = textlib_get_instance();
	
	$len = $textlib->strlen( $g);
	while( $len)
	{
		for( $i=0; $i < $len; $i++)
		{
			$c = $textlib->substr( $g, $i, 1);
			if( $c >= '1' and $c <= '9'){
			    if( $i > 0){
			        //found escape character
			        if(  $textlib->substr( $g, $i-1, 1) == '/'){
			            $g = $textlib->substr( $g, 0, $i-1).$textlib->substr( $g, $i);
			            $i--;
			            $len--;
			            continue;
			        }
			    }
				break;
			}
		}

		if( $i < $len){
			//found the start of a number
			for( $j=$i+1; $j < $len; $j++)
			{
				$c = $textlib->substr( $g, $j, 1);
				if( $c < '0' or $c > '9'){
					break;
				}
			}
			$count = $textlib->substr( $g, $i, $j-$i);
			$ret .= $textlib->substr( $g, 0, $i) . str_repeat( '_', $count);
			
			$g = $textlib->substr( $g, $j);
			$len = $textlib->strlen( $g);
			
		}else
		{
			$ret .= $g;
			break;
		}
	}
	
	return $ret;
}

	
?>
