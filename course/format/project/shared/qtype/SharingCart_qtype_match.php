<?php
/**
 *  SharingCart_qtype_match
 */

require_once $GLOBALS['CFG']->dirroot.'/question/type/match/questiontype.php';

class SharingCart_qtype_match extends question_match_qtype
{
	/* override */ function restore_map($old_question_id, $new_question_id, $info, $restore)
	{
		$matchs = $info['#']['MATCHS']['0']['#']['MATCH'];
		foreach ($matchs as $match) {
			$match_sub_id = backup_todb($match['#']['ID']['0']['#']);
			if (!record_exists('question_match_sub', 'id', $match_sub_id))
				throw new SharingCart_XmlException('match sub question not found');
			
			backup_putid($restore->backup_unique_code, 'question_match_sub', $match_sub_id, $match_sub_id);
		}
		
		return backup_putid($restore->backup_unique_code, 'question', $old_question_id, $new_question_id);
	}
}

$GLOBALS['QTYPES']['match'] = new SharingCart_qtype_match();

?>