<?php // $Id: questions.php,v 1.2 2008/05/13 22:52:10 bdaloukas Exp $
/**
 * The script supports book
 *
 * @version $Id: questions.php,v 1.2 2008/05/13 22:52:10 bdaloukas Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package game
 **/

    require_once("../../../config.php");
	require_once( "../header.php");
    require_once("../locallib.php");

	$attempt = game_getattempt( $game, $detail);
    if( $game->bookid == 0){
        error( get_string( 'bookquiz_not_select_book', 'game'));
    }

    if ($form = data_submitted())
    {   /// Filename
		$ids = explode( ',', $form->ids);
		game_bookquiz_save( $game->id, $game->bookid, $ids, $form);
		
		redirect( "$CFG->wwwroot/mod/game/bookquiz/questions.php?id=$cm->id", '', 0);
    }

    /// Print upload form

    print_heading_with_help( get_string( "bookquiz_questions", "game"), "questions", "game");
	
    print_simple_box_start( "center");
	
	$select = "gameid={$game->id}";
	$categories = array();
	if( ($recs = get_records_select( 'game_bookquiz_questions', $select, '', 'chapterid,questioncategoryid')) != false){
		foreach( $recs as $rec){
			$categories[ $rec->chapterid] = $rec->questioncategoryid;
		}
	}
	
	$a = array();
	$a[ 0] = '';
	$select = '';
	$recs = get_records_select( 'question_categories', '', '', '*', 0, 1);
	foreach( $recs as $rec){
		if( array_key_exists( 'course', $rec)){
			$select = "course=$cm->course";
		}else{
			$context = get_context_instance(50, $cm->course);
	    		$select = " contextid in ($context->id)";
		}
		break;
	}

	$a = array();
        if( $recs = get_records_select( 'question_categories', $select, 'id,name')){
            foreach( $recs as $rec){
                $s = $rec->name;
                if( ($count = count_records_select( 'question', "category={$rec->id}")) != 0){
                    $s .= " ($count)";
                }
                $a[ $rec->id] = $s;
            }
        }
	
	$sql = "SELECT chapterid, COUNT(*) as c ".
				"FROM {$CFG->prefix}game_bookquiz_questions,{$CFG->prefix}question ".
				"WHERE {$CFG->prefix}game_bookquiz_questions.questioncategoryid={$CFG->prefix}question.category ".
				"AND gameid=$game->id ".
				"GROUP BY chapterid";
	$numbers = array();
	if( ($recs = get_records_sql( $sql)) != false){
		foreach( $recs as $rec){
			$numbers[ $rec->chapterid] = $rec->c;
		}
	}
	
	echo '<form name="form" method="post" action="questions.php">';
	echo '<table border=1>';
	echo '<tr>';
	echo '<td><center>'.get_string( 'bookquiz_chapters', 'game').'</td>';
	echo '<td><center>'.get_string( 'bookquiz_categories', 'game').'</td>';
	echo '<td><center>'.get_string( 'bookquiz_numquestions', 'game').'</td>';
	echo "</tr>\r\n";
	$ids = '';
	if( ($recs = get_records_select( 'book_chapters', 'bookid='.$game->bookid, 'pagenum', 'id,title')) != false)
	{
		foreach( $recs as $rec){
			echo '<tr>';
			echo '<td>'.$rec->title.'</td>';
			echo '<td>';
			if( array_key_exists( $rec->id, $categories))
				$categoryid = $categories[ $rec->id];
			else
				$categoryid = 0;
			echo game_showselectcontrol( 'categoryid_'.$rec->id, $a, $categoryid, ''); 
			echo '</td>';
			
			echo '<td>';
			if( array_key_exists( $rec->id, $numbers)){
				echo '<center>'.$numbers[ $rec->id].'</center>';
			}else
			{
				echo '&nbsp;';
			}
			echo '</td>';
			
			echo "</tr>\r\n";
			
			$ids .= ','.$rec->id;
		}
	}
?>
</table>
<br>
<!-- These hidden variables are always the same -->
<input type="hidden" name=id       value="<?php  p($id) ?>" />
<input type="hidden" name=ids       value="<?php  p( substr( $ids, 1)) ?>" />
<center>
<input type="submit" value="<?php  print_string("savechanges") ?>" />
</center>

</form>
<?php

	print_footer($course);

function game_bookquiz_save( $gameid, $bookid, $ids, $form)
{
	$select = "gameid=$gameid";
	$questions = array();
	$recids = array();
	if( ($recs = get_records_select( 'game_bookquiz_questions', $select, '', 'id,chapterid,questioncategoryid')) != false){
		foreach( $recs as $rec){
			$questions[ $rec->chapterid] = $rec->questioncategoryid;
			$recids[ $rec->chapterid]  = $rec->id;
		}
	}

	foreach( $ids as $chapterid){
		$name = 'categoryid_'.$chapterid;
		$categoryid = $form->$name;
		
		if( !array_key_exists( $chapterid, $questions)){
			
			if( $categoryid == 0){
				continue;
			}
			
			unset( $rec);
			$rec->gameid = $gameid;
			$rec->chapterid = $chapterid;
			$rec->questioncategoryid = $categoryid;
			
			if (($newid=insert_record('game_bookquiz_questions', $rec)) == false) {
				print_object( $rec);
				error( "Can't insert to game_bookquiz_questions");
			}
			continue;
		}
		
		$cat = $questions[ $chapterid];
		if( $cat == $categoryid){
			$recids[ $chapterid] = 0;
			continue;
		}
		
		if( $categoryid == 0){
			if( !delete_records( 'game_bookquiz_questions', 'id', $recids[ $chapterid])){
				error( "Can't delete game_bookquiz_questions");
			}
		}else
		{
			unset( $updrec);
			$updrec->id = $recids[ $chapterid];
			$updrec->questioncategoryid = $categoryid;
			if ((update_record( 'game_bookquiz_questions', $updrec)) == false) {
				print_object( $rec);
				error( "Can't update game_bookquiz_questions");
			}
		}
		
		$recids[ $chapterid] = 0;
	}

	foreach( $recids as $chapterid => $id){
		if( $id == 0){
			continue;
		}
	}
}
