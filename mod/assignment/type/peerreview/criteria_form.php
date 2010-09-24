<?php 

// 

require_once($CFG->libdir.'/formslib.php');

class assignment_peerreview_criteria_form extends moodleform {

    //--------------------------------------------------------------------------
    // Form definition
    function definition() {
        global $USER, $CFG;
		
		// Create form object
		$mform =& $this->_form;

		// Pass on module ID and assignment ID
        $mform->addElement('hidden', 'id', $this->_customdata['moduleID']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'a',$this->_customdata['assignmentID']);
        $mform->setType('id', PARAM_INT);

		// Define criteria and repeat
        $repeatarray=array();
        $repeatarray[] = &MoodleQuickForm::createElement('header', '', get_string('criterion', 'assignment_peerreview').' {no}');
        $repeatarray[] = &MoodleQuickForm::createElement('text', 'criterionDescription', get_string('citerionwithdescription', 'assignment_peerreview'), array('size'=>'80'));
        $repeatarray[] = &MoodleQuickForm::createElement('text', 'criterionReview', get_string('citerionatreview', 'assignment_peerreview'), array('size'=>'80'));
        $repeatarray[] = &MoodleQuickForm::createElement('text', 'value', get_string('valueofcriterion', 'assignment_peerreview'), array('size'=>'3','onkeyup'=>'updateTotal();','onchange'=>'updateTotal();'));
		$repeatno=count_records('assignment_criteria', 'assignment', $this->_customdata['assignmentID']);
		$repeatno=$repeatno==0?3:$repeatno+2;
        $repeateloptions = array();
        //$repeateloptions['value']['rule'] = 'numeric';
        $this->repeat_elements($repeatarray, $repeatno,$repeateloptions, 'option_repeats', 'option_add_fields', 2, get_string('addTwoMoreCriteria', 'assignment_peerreview'));

		// Show mark summary
        $mform->addElement('header', 'marksummary', get_string('marksummary', 'assignment_peerreview'));
		$mform->setHelpButton('marksummary', array('marksummary', get_string('marksummary', 'assignment_peerreview'), 'assignment/type/peerreview/'));
		$mform->addElement('static', '', get_string('valueofcriteria', 'assignment_peerreview'),'<span id="totalOfValues" class="markSummaryValue">&nbsp;</span>');
		$mform->addElement('static', '', get_string('rewardforreviews', 'assignment_peerreview',$this->_customdata['reward']),'<div id="rewardCell"><span class="markSummaryValue">'.(2*$this->_customdata['reward']).'</span></div>');
		$mform->addElement('hidden', 'reward', $this->_customdata['reward']);
		$mform->addElement('static', '', get_string('totalmarksabove', 'assignment_peerreview'),'<span id="totalOfMarksAbove" class="markSummaryValue">&nbsp;</span>');
		$mform->addElement('static', '', get_string('totalmarksforgrade', 'assignment_peerreview'),'<span class="markSummaryValue">'.$this->_customdata['grade'].'</span>');
		$mform->addElement('hidden', 'grade', $this->_customdata['grade']);
		$mform->addElement('static', 'difference', get_string('difference', 'assignment_peerreview'),'<span id="peerReviewDifference" class="markSummaryValue">&nbsp;</span>');
		$mform->addElement('html', "
		
<style>
.mform div.felement, .mform fieldset.felement markSummaryValue { text-align:right; width:50px; }
#rewardCell {border-bottom:1px solid #000000;padding:2px}
</style>
		
<script type=\"text/javascript\">
//<![CDATA[

var numValues = 0;
while(document.getElementById('id_value_'+numValues) != null) {
	if(document.getElementById('id_value_'+numValues).value=='') {
		document.getElementById('id_value_'+numValues).value = '0';
	}
    numValues++;
}

function updateTotal() {
	var sum = 0.0;
	var totalMarks = ".$this->_customdata['grade'].";
	var rewardMarks = ".(2*$this->_customdata['reward']).";
	for(i=0; i<numValues; i++) {
		sum += parseFloat(document.getElementById('id_value_'+i).value);
	}
	if(isNaN(sum)) {
		document.getElementById('totalOfValues').innerHTML = '<span style=\"color:red\">".get_string('nonNumericCriterionValue', 'assignment_peerreview')."</span>';
		document.getElementById('totalOfMarksAbove').innerHTML = '<span style=\"color:red\">".get_string('nonNumericCriterionValue', 'assignment_peerreview')."</span>';
	}
	else {
		document.getElementById('totalOfValues').innerHTML = sum;
		document.getElementById('totalOfMarksAbove').innerHTML = sum+rewardMarks;
		document.getElementById('peerReviewDifference').innerHTML = '<span style=\"'+(totalMarks-rewardMarks-sum==0?'color:green;background:#e0ffe0;':'color:red;background:#ffe0e0;')+'\">'+(totalMarks-rewardMarks-sum)+'</span>';
	}
}

updateTotal();

//]]>
</script>

		");
		// Buttons for submit, reset and cancel
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('saveanddisplay','assignment_peerreview'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
	}
/*
    function definition_after_data() {
        global $CFG;

        $mform =& $this->_form;

        // add availabe groupings
        if ($courseid = $mform->getElementValue('id') and $mform->elementExists('defaultgroupingid')) {
            $options = array();
            if ($groupings = get_records('groupings', 'courseid', $courseid)) {
                foreach ($groupings as $grouping) {
                    $options[$grouping->id] = format_string($grouping->name);
                }
            }
            $gr_el =& $mform->getElement('defaultgroupingid');
            $gr_el->load($options);
        }
    }      
*/

    //--------------------------------------------------------------------------
    // Form validation after submission
    function validation($data, $files) {
		//echo '<pre>'.print_r($data,true).'</pre>';
        $errors = array();
		$sum = 0;
		foreach($data['value'] as $value) {
			$sum += (int)$value;
		}
		$sum += 2*(int)($data['reward']);
		if($sum != $data['grade']) {
			$errors['difference'] = get_string('marksdontaddup','assignment_peerreview');
		}
		
        return $errors;
    }
}
?>
