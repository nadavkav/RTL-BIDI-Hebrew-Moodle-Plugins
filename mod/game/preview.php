<?php  // $Id: preview.php,v 1.2 2008/11/06 23:16:45 bdaloukas Exp $
/**
 * This page prints a particular attempt of game
 * 
 * @author  bdaloukas
 * @version $Id: preview.php,v 1.2 2008/11/06 23:16:45 bdaloukas Exp $
 * @package game
 **/
 
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    require_once( "hangman/play.php");
    require_once( "cross/play.php");
    require_once( "cryptex/play.php");
    require_once( "millionaire/play.php");
    require_once( "sudoku/play.php");
    require_once( "bookquiz/play.php");
	
	$update = (int )$_GET[ 'update'];
	$_GET[ 'id'] = $update;
	require_once( "header.php");
	
	if( !isteacherinanycourse( $USER->id)){
		error( get_string( 'only_teachers', 'game'));
	}
	
	$gamekind = $_GET[ 'gamekind'];

	$id = $update;
	$attemptid = (int )$_GET[ 'attemptid'];
	$attempt = get_record_select( 'game_attempts', "id=$attemptid");
	$game = get_record_select( 'game', "id=$attempt->gameid");
	$detail = get_record_select( 'game_'.$gamekind, "id=$attemptid");
	if( array_key_exists( 'solution', $_GET)){
		$solution = $_GET[ 'solution'];
	}else
	{
		$solution = 0;
	}

	switch( $gamekind)
	{
	case 'cross':
		game_cross_play( $update, $game, $attempt, $detail, '', true, $solution, false, false, false, false, true);
		break;
	case 'sudoku':
		game_sudoku_play( $update, $game, $attempt, $detail, true, $solution);
		break;
	case 'hangman':
		game_hangman_play( $update, $game, $attempt, $detail, true, $solution);
		break;
	case 'cryptex':
		$crossm = get_record_select( 'game_cross', "id=$attemptid");
		game_cryptex_play( $update, $game, $attempt, $detail, $crossm, false, true, $solution);
		break;
	}
