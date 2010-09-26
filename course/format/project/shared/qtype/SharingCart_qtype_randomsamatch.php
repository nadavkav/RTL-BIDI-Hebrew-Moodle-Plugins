<?php
/**
 *  SharingCart_qtype_randomsamatch
 */

require_once $GLOBALS['CFG']->dirroot.'/question/type/randomsamatch/questiontype.php';

class SharingCart_qtype_randomsamatch extends question_randomsamatch_qtype
{
	/* override */ function restore_map($old_question_id, $new_question_id, $info, $restore)
	{
		return backup_putid($restore->backup_unique_code, 'question', $old_question_id, $new_question_id);
	}
}

$GLOBALS['QTYPES']['randomsamatch'] = new SharingCart_qtype_randomsamatch();

?>