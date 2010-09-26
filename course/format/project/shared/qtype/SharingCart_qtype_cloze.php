<?php
/**
 *  SharingCart_qtype_cloze
 */

require_once $GLOBALS['CFG']->dirroot.'/question/type/multianswer/questiontype.php';

class SharingCart_qtype_cloze extends embedded_cloze_qtype
{
	/* override */ function restore_map($old_question_id, $new_question_id, $info, $restore)
	{
		$multianswers = $info['#']['MULTIANSWERS']['0']['#']['MULTIANSWER'];
		foreach ($multianswers as $multianswer) {
			$sequence = $multianswer['#']['SEQUENCE']['0']['#'];
			
			$child_question_ids = array_filter(explode(',', $sequence));
			foreach ($child_question_ids as $child_question_id) {
				if (!record_exists('question', 'id', $child_question_id))
					throw new SharingCart_XmlException('cloze child question not found');
				
				backup_putid($restore->backup_unique_code, 'question', $child_question_id, $child_question_id);
			}
		}
		
		return backup_putid($restore->backup_unique_code, 'question', $old_question_id, $new_question_id);
	}
}

$GLOBALS['QTYPES']['multianswer'] = new SharingCart_qtype_cloze();

?>