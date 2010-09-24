<?PHP // $Id: brainstorm.php,v 1.2 2004/08/24 16:36:19 cmcclean Exp $
      // brainstorm.php - created with Moodle 1.8 development (2007122600)

$string['addmoreresponses'] = 'Add more ideas';
$string['allopdata'] = 'Oranisation records (data for operators) ';
$string['allresponses'] = 'Number of ideas ';
$string['allusersclear'] = 'Clear for all participants';
$string['brainstormname'] = 'Title';
$string['brainstormtext'] = 'Description';
$string['chooseoperatornotice'] = 'To activate or disable an operator, clic on the appropriate icon. You will be able to set parameters for each operator using the above submenu entries. When disabling an operator, you will keep the last value set for that operator.';
$string['chooseoperators'] = 'Choose operators';
$string['clearallprevious'] = 'Clear previous data';
$string['collect'] = 'Collecting';
$string['collectingideas'] = 'Collecting ideas';
$string['commands'] = 'Commmands';
$string['data'] = 'Brainstorm data';
$string['deleteselection'] = 'Delete selection';
$string['display'] = 'Display';
$string['dograde'] = 'Grade him !';
$string['feedback'] = 'Feedback/report';
$string['feedbackweight'] = 'Feedback grade weight';
$string['finalgrade'] = 'Total';
$string['float'] = 'Floating point ';
$string['flowcontrol'] = 'Action flow control ';
$string['flowmode'] = 'Action flow mode ';
$string['foradminsonly'] = 'Additional settings. Managers only.';
$string['editareport'] = 'Write a report';
$string['grade'] = 'Grade ';
$string['grades'] = 'Grades';
$string['graded'] = 'Graded ';
$string['gradeforfeedback'] = 'Grade for feedback work';
$string['gradefororganisation'] = 'Grade for organisation work';
$string['gradeforparticipation'] = 'Grade for ideas';
$string['gradeforpreparing'] = 'Grade for preparation work';
$string['grading'] = 'Grading ';
$string['gradingof'] = 'Grading ';
$string['havereport'] = 'Have posted a report';
$string['heightresponse'] = 'Input field height';
$string['helptext'] = 'Help upon formats';
$string['importfile'] = 'Import file';
$string['importideas'] = 'Import ideas ';
$string['integer'] = 'Integer (positive) ';
$string['manager'] = 'Manager ';
$string['modulename'] = 'Brainstorm';
$string['modulenameplural'] = 'Brainstorms';
$string['multiple'] = 'Composite quantification ';
$string['mustentercategory'] = 'You should enter at least one category before saving.';
$string['mustentersomething'] = 'You should enter at least one input before saving.';
$string['myresponses'] = 'My ideas';
$string['myreport'] = 'My report';
$string['notabletodisplayfor'] = 'Not able to display results for operator <b>$a</b>';
$string['notimplemented'] = 'Not implemented yet';
$string['notresponded'] = 'No input till now';
$string['noreports'] = 'No reports posted';
$string['numcolumns'] = 'Columns in display';
$string['numresponses'] = 'Inputs per participant';
$string['numresponsesinform'] = 'Input fields in collect form';
$string['opdatainallgroups'] = 'Organisation records (all groups)';
$string['opdatainyourgroup'] = 'Orrganisation records (current group)';
$string['operators'] = 'Operators';
$string['oprequirementtype'] = 'Operator requirement edition type ';
$string['organizations'] = 'Organizations';
$string['organize'] = 'Organize';
$string['organizeideas'] = 'Organizing ideas';
$string['organizeweight'] = 'Organizing grade weight';
$string['otherresponses'] = 'Other participant\'s inputs';
$string['parallel'] = 'Parallel flow';
$string['participant'] = 'Participant ';
$string['participation'] = 'Participation';
$string['participationweight'] = 'Participation grade weight';
$string['preparations'] = 'Preparation';
$string['prepare'] = 'Preparing';
$string['preparingweight'] = 'Preparing grade weight';
$string['privacy'] = 'User privacy';
$string['procedure'] = 'Process ';
$string['publishing'] = 'Publication';
$string['report'] = 'Report';
$string['reportless'] = 'Have not posted';
$string['requirement'] = 'Requirement';
$string['responded'] = 'ideas';
$string['responses'] = 'ideas';
$string['responsesinallgroups'] = 'Number of ideas (all groups)';
$string['responsesinyourgroup'] = 'Number of ideas (current group)';
$string['saveconfig'] = 'Save the configuration ';
$string['savemyresponse'] = 'Save my idea(s)';
$string['scale'] = 'Scale';
$string['select'] = 'Choose operators';
$string['sequential'] = 'Sequential flow (phased)';
$string['sequentialaccess'] = 'Phase assignation (sequential only) ';
$string['singlegrade'] = 'Single/dissociated grade';
$string['splittedgrade'] = 'Splitted grade';
$string['summary'] = 'Summary';
$string['teacherfeedback'] = 'Animator\'s feedback';
$string['textarea'] = 'Text area';
$string['textfield'] = 'Text field (sentence)';
$string['unlimited'] = 'Unlimited';
$string['utf8advice'] = 'You should first convert your text file to utf8 encoding, or unexpectable results can occur with non ASCII characters.';
$string['userdata'] = 'Participant scoped data';
$string['warnclear'] = 'Warning : if you update this valuee, existing data for this operator\\\\n may not be relevant any more and will be deleted.\\\\n If you need keeping old data, DON\'T SAVE this form and browse directly to any other screen.';
$string['whysiwhygtextarea'] = 'Whysiwhyg editor';
$string['widthresponse'] = 'Input field width';

///get all operators lang files
global $CFG, $USER, $SITE;
$DIR = opendir($CFG->dirroot.'/mod/brainstorm/operators');
$lang = current_language();
while($opname = readdir($DIR)){
    if (!is_dir($CFG->dirroot.'/mod/brainstorm/operators/'.$opname)) continue;
    if (ereg("^\\.", $opname)) continue;
    if (file_exists("{$CFG->dirroot}/mod/brainstorm/operators/{$opname}/lang/{$lang}/operator.php")){ 
        include "{$CFG->dirroot}/mod/brainstorm/operators/{$opname}/lang/{$lang}/operator.php";
    }
}

?>
