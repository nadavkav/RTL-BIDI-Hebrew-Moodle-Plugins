<?PHP

function game_snakes_continue( $id, $game, $attempt, $snakes)
{
	if( $attempt != false and $snakes != false){
		return game_snakes_play( $id, $game, $attempt, $snakes);
	}

	if( $attempt === false){
		$attempt = game_addattempt( $game);
	}

	$newrec->id = $attempt->id;
	$newrec->snakesdatabaseid = $game->param3;
	$newrec->position = 1;
	$newrec->queryid = 0;
    $newrec->dice = rand( 1, 6);
	if( !game_insert_record(  'game_snakes', $newrec)){
		error( 'game_snakes_continue: error inserting in game_snakes');
	}

	game_updateattempts( $game, $attempt, 0, 0);

	return game_snakes_play( $id, $game, $attempt, $newrec);
}

function game_snakes_play( $id, $game, $attempt, $snakes)
{
	global $CFG;

	$board = get_record_select( 'game_snakes_database', "id=$snakes->snakesdatabaseid");
  echo "<table style=\"direction:left;\">";
  echo "<tr>";
  echo "<td>";
	if( $snakes->position > $board->cols * $board->rows && $snakes->queryid <> 0){
		$finish = true;

		if (! $cm = get_record("course_modules", "id", $id)) {
			error("Course Module ID was incorrect id=$id");
		}

		echo '<B>'.get_string( 'snakes_win', 'game').'</B><BR>';
		echo '<br>';
		echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?id=$id\">".get_string( 'snakes_new', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp; ';
		echo "<a href=\"$CFG->wwwroot/course/view.php?id=$cm->course\">".get_string( 'finish', 'game').'</a> ';

		$gradeattempt = 1;
		$finish = 1;
		game_updateattempts( $game, $attempt, $gradeattempt, $finish);
	}else
	{
		$finish = false;
		if( $snakes->queryid == 0){
			game_snakes_computenextquestion( $game, $snakes, $query);
		}else
		{
			$query = get_record( 'game_queries', 'id', $snakes->queryid);
		}
		if( $game->toptext != ''){
		    echo $game->toptext.'<br>';
	    }
		game_snakes_showquestion( $id, $game, $snakes, $query);
	}
  echo "</td>";
  echo "<td>";

?>
    <script language="javascript" event="onload" for="window">
    <!--
    var retVal = new Array();
    var elements = document.getElementsByTagName("*");
    for(var i = 0;i < elements.length;i++){
        if( elements[ i].type == 'text'){
            elements[ i].focus();
            break;
        }
    }
    -->
    </script>
<style type="text/css">
div#content {direction: rtl !important}
</style>
	<table>
	<tr>
		<td>

<DIV ID="board" STYLE="position:relative; left:0px;top:0px; width:<?php p($board->width); ?>px; height:<?php p($board->height); ?>px;"><br>
<b><center><img src="snakes/boards/<?php p($board->fileboard);?>"></img> </center>
</DIV>

<?php
if( $finish  == false){
    game_snakes_showdice( $snakes, $board);
}
?>
		</td>
	</tr>
	</table>
<?php

	if( $game->bottomtext != ''){
		echo '<br><br>'.$game->bottomtext;
	}
}

function game_snakes_showdice( $snakes, $board)
{
	$pos = game_snakes_computeplayerposition( $snakes, $board);
?>
<DIV ID="player1" STYLE="float:left;position:relative; left:<?php p( $pos->x);?>px;top:<?php p( $pos->y - $board->height);?>px; width:0px; height:23px;"><br>
<img src="snakes/1/player1.gif"></img>
</DIV>
</td>
</tr>
</table>
	<DIV ID="dice" STYLE="position:absolute;
right:80px;
top:80px;
 width:auto;
 height:0px;"><br><?php echo get_string('lastmovewas','game'); ?><br><br/>
	<img src="snakes/1/dice<?php  p( $snakes->dice);?>.gif"></img>
</DIV>
<?php


}

function game_snakes_computeplayerposition( $snakes, $board)
{
    //ladders
    if ($snakes->position == 3) $snakes->position = 18;
    if ($snakes->position == 24) $snakes->position = 39;
    if ($snakes->position == 29) $snakes->position = 53;
    if ($snakes->position == 48) $snakes->position = 63;

    // snakes
    if ($snakes->position == 19) $snakes->position = 5;
    if ($snakes->position == 27) $snakes->position = 8;
    if ($snakes->position == 62) $snakes->position = 32;
    if ($snakes->position == 58) $snakes->position = 41;

	$x = ($snakes->position - 1) % $board->cols;
	$y = floor( ($snakes->position-1) / $board->cols);
//echo "<span style=\"float:right;\">pos={$snakes->position} , x=$x , y=$y</span>";
	$cellwidth = ($board->width - $board->headerx - $board->footerx) / $board->cols;
	$cellheight = ($board->height - $board->headery - $board->footery) / $board->rows;

	unset( $pos);

	switch( $board->direction){
	case 1:
		if( ($y % 2) == 1){
			$x = $board->cols  - $x - 1;
		}
		$pos->x = round( $board->headerx + $x * $cellwidth + ($cellwidth-22) / 2);
		$pos->y = round( $board->footery + ($board->rows - $y) * $cellheight - $cellheight/2 - 23);

		break;
	}

	return $pos;
}

function game_snakes_computenextquestion( $game, &$snakes, &$query)
{
	global $USER;

	if( ($recs = game_questions_selectrandom( $game, 1)) == false){
		return false;
	}

	foreach( $recs as $rec)
	{
		$query->attemptid = $snakes->id;
		$query->gameid = $game->id;
		$query->userid = $USER->id;
		$query->sourcemodule = $game->sourcemodule;
		$query->questionid = $rec->questionid;
		$query->glossaryentryid = $rec->glossaryentryid;
		$query->score = 0;
		$query->timelastattempt = time();
		if( !($query->id = insert_record( 'game_queries', $query))){
			error( "Can't insert to table game_queries");
		}

		$snakes->queryid = $query->id;

		$updrec->id = $snakes->id;
		$updrec->queryid = $query->id;
		$updrec->dice = rand( 1, 6);

		if( !update_record(  'game_snakes', $updrec)){
			error( 'game_questions_selectrandom: error updating in game_snakes');
		}

		return true;
	}

	return false;
}

function game_snakes_showquestion( $id, $game, $snakes, $query)
{
	if( $query->sourcemodule == 'glossary'){
		game_snakes_showquestion_glossary( $id, $snakes, $query);
	}else
	{
		game_snakes_showquestion_question( $game, $id, $snakes, $query);
	}
}

function game_snakes_showquestion_question( $game, $id, $snakes, $query)
{
	global $CFG;

	$questionlist = $query->questionid;
    $questions = game_sudoku_getquestions( $questionlist);

	/// Start the form
	echo '<br>';
    echo "<form id=\"responseform\" method=\"post\" action=\"{$CFG->wwwroot}/mod/game/attempt.php\" onclick=\"this.autocomplete='off'\">\n";
	echo "<center><input type=\"submit\" name=\"finishattempt\" value=\"".get_string('snakes_submit', 'game')."\"></center>\n";

    // Add a hidden field with the quiz id
    echo '<input type="hidden" name="id" value="' . s($id) . "\" />\n";
    echo '<input type="hidden" name="action" value="snakescheck" />';
    echo '<input type="hidden" name="queryid" value="' . $query->id . "\" />\n";

	/// Print all the questions

    // Add a hidden field with questionids
    echo '<input type="hidden" name="questionids" value="'.$questionlist."\" />\n";

    foreach ($questions as $question) {
		global $QTYPES;
		unset( $cmoptions);
		$cmoptions->course = $game->course;
        $cmoptions->shuffleanswers = $question->options->shuffleanswers = false;
        $cmoptions->optionflags->optionflags = 0;
		$cmoptions->id = 0;
		$attempt = 0;
		if (!$QTYPES[$question->qtype]->create_session_and_responses( $question, $state, $cmoptions, $attempt)) {
			error( 'game_sudoku_showquestions_quiz: problem');
		}

		$state->last_graded = new StdClass;
		$state->last_graded->event = QUESTION_EVENTOPEN;
		$state->event = QUESTION_EVENTOPEN;
		$options->scores->score = 0;
		$question->maxgrade = 100;
		$state->manualcomment = '';
		$cmoptions->optionflags = 0;
		$options->correct_responses = 0;
		$options->feedback = 0;
		$options->readonly = 0;

		print_question($question, $state, '', $cmoptions, $options);

		break;
    }

    echo "</form>\n";
}

function game_snakes_showquestion_glossary( $id, $snakes, $query)
{
	global $CFG;

	$entry = get_record( 'glossary_entries', 'id', $query->glossaryentryid);

	/// Start the form
	echo '<br>';
    echo "<form id=\"responseform\" method=\"post\" action=\"{$CFG->wwwroot}/mod/game/attempt.php\" onclick=\"this.autocomplete='off'\">\n";
	echo "<center><input type=\"submit\" name=\"finishattempt\" value=\"".get_string('snakes_submit', 'game')."\"></center>\n";

    // Add a hidden field with the queryid
    echo '<input type="hidden" name="id" value="' . s($id) . "\" />\n";
    echo '<input type="hidden" name="action" value="snakescheckg" />';
    echo '<input type="hidden" name="queryid" value="' . $query->id . "\" />\n";

	/// Print all the questions

    // Add a hidden field with glossaryentryid
    echo '<input type="hidden" name="glossaryentryid" value="'.$query->glossaryentryid."\" />\n";

    echo game_filtertext( $entry->definition, 0).'<br>'; //  definition>> concept (nadavkav)

    echo get_string( 'answer').': ';
	echo "<input type=\"text\" name=\"answer\" size=30 /><br>";

    echo "</form>\n";
}


function game_snakes_check_questions( $id, $game, $attempt, $snakes)
{
	global $QTYPES, $CFG;

    $responses = data_submitted();

	if( $responses->queryid != $snakes->queryid){
		game_snakes_play( $id, $game, $attempt, $snakes);
		return;
	}

	$questionlist = get_field( 'game_queries', 'questionid', 'id', $responses->queryid);

    $questions = game_sudoku_getquestions( $questionlist);

    $actions = question_extract_responses( $questions, $responses, QUESTION_EVENTSUBMIT);
	$correct = false;
	$query = '';
    foreach($questions as $question) {
        if( !array_key_exists( $question->id, $actions)){
            //no answered
            continue;
        }
        unset( $state);
        unset( $cmoptions);
        $question->maxgrade = 100;
        $state->responses = $actions[ $question->id]->responses;
		$state->event = QUESTION_EVENTGRADE;

		$state->responses[ ''] = game_upper( $state->responses[ '']);

		$cmoptions = array();
        $QTYPES[$question->qtype]->grade_responses( $question, $state, $cmoptions);

		unset( $query);
        $select = "attemptid=$attempt->id ";
        $select .= " AND questionid=$question->id";
        if( ($query->id = get_field_select( 'game_queries', 'id', $select)) == 0){
			die("problem game_sudoku_check_questions (select=$select)");
            continue;
        }

        $grade = $state->raw_grade;
        if( $grade < 50){
			//wrong answer
        			game_update_queries( $game, $attempt, $query, 0, '');
            continue;
        }
        //correct answer
		$correct = true;

        game_update_queries( $game, $attempt, $query, 1, '');
    }

	//set the grade of the whole game
    game_snakes_position( $id, $game, $attempt, $snakes, $correct, $query);
}

function game_snakes_check_glossary( $id, $game, $attempt, $snakes)
{
	global $QTYPES, $CFG;

    $responses = data_submitted();

	if( $responses->queryid != $snakes->queryid){
		game_snakes_play( $id, $game, $attempt, $snakes);
		return;
	}

	$query = get_record( 'game_queries', 'id', $responses->queryid);

    $glossaryentry = get_record( 'glossary_entries', 'id', $query->glossaryentryid);

    $name = 'resp'.$query->glossaryentryid;
    $useranswer = $responses->answer;
    //if( game_upper( $useranswer) != game_upper( $glossaryentry->definition)){ // concept >> definition (nadavkav)
	if( strcmp(trim($useranswer),trim($glossaryentry->concept)) == 0 ) { // concept >> definition (nadavkav)
		//correct answer
		$correct = true;
        game_update_queries( $game, $attempt, $query, 1, $useranswer);//last param is grade
    } else {
		//wrong answer
        $correct = false;
        //  hanna  add  from---
        ?>
        	<DIV ID="wronganswer" STYLE="position:fixed;
right:50px;
top:680px;
 width:auto;
 height:0px;"><br><?php echo get_string('reattemptgame','game'); ?><br/>
</DIV>
         <?php
         //   echo get_string('reattemptgame','game');   //   till here --- hanna 4/1/11
		game_update_queries( $game, $attempt, $query, 0, $useranswer);//last param is grade
    }

	//set the grade of the whole game
    game_snakes_position( $id, $game, $attempt, $snakes, $correct, $query);
}


function game_snakes_position( $id, $game, $attempt, $snakes, $correct, $query)
{
	$data = get_field( 'game_snakes_database', 'data', 'id', $snakes->snakesdatabaseid);

	if( $correct){
		if( ($next=game_snakes_foundlander( $snakes->position, $data))){
			$snakes->position  = $next;
		}else
		{
			$snakes->position  = $snakes->position + $snakes->dice;
		}
	}else
	{
		if( ($next=game_snakes_foundsnake( $snakes->position, $data))){
			$snakes->position  = $next;
		}
	}

	$updrec->id = $snakes->id;
	$updrec->position = $snakes->position;
	$updrec->queryid = 0;

	if( !update_record( 'game_snakes', $updrec)){
		error( "game_snakes_position: Can't update game_snakes");
	}

	$board = get_record_select( 'game_snakes_database', "id=$snakes->snakesdatabaseid");
	$gradeattempt = $snakes->position / ($board->cols  * $board->rows);
	$finished = ( $snakes->position > $board->cols  * $board->rows ? 1 : 0);

	game_updateattempts( $game, $attempt, $gradeattempt, $finished);

	game_snakes_computenextquestion( $game, $snakes, $query);

	game_snakes_play( $id, $game, $attempt, $snakes);
}

//in lander go forward
function game_snakes_foundlander( $position, $data)
{
	preg_match( "/L$position-([0-9]*)/", $data, $matches);

	if( count( $matches)){
		return $matches[ 1];
	}

	return 0;
}

//in snake go backward
function game_snakes_foundsnake( $position, $data)
{
	preg_match( "/S([0-9]*)-$position,/", $data.',', $matches);

	if( count( $matches)){
		return $matches[ 1];
	}

	return 0;
}
