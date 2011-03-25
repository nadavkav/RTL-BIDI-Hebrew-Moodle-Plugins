<?php
    require_once('../../../config.php');
    global $CFG,$USER,$COURSE;

//Check in there is a image in the stream
if(empty($_FILES['image'])) {
    echo "No image stream was found";
    exit;
}

//Check if there was an upload error
if($_FILES['image']['error'] != UPLOAD_ERR_OK) {
    echo "Error writing file to disc". $_FILES['image']['error'];
}

//Parse the form paramters
//$file = $_FILES['image']['tmp_name'];
$type = $_POST['type'];
$state = $_POST['state'];
$filename = $_POST['title'].".".$_POST['type'];

if ($_POST['title'] != $USER->sesskey ) error('Wrong sesskey :-(');

//Optional: set a unique filename if the file is saved to a public service and inserted into a database
//$filename = uniqid();

//Set the local file path where the image will be saved to
//$save_path = $CFG->dirroot."/upload/". $filename;

//Copy the temp_image to the path as set before.
//
//$result = @move_uploaded_file($_FILES['image']['tmp_name'], $save_path);
$created = mkdir("{$CFG->dataroot}/{$_GET['courseid']}/users/{$USER->id}", 0777, true);
// delete previous file, just in case we are re-editing
//unlink("{$CFG->dataroot}/{$_GET['courseid']}/users/{$USER->id}/question{$_GET['qid']}_qatt{$_GET['qatt']}_{$filename}");
if (!@move_uploaded_file($_FILES['image']['tmp_name'], "{$CFG->dataroot}/{$_GET['courseid']}/users/{$USER->id}/question{$_GET['qid']}_qatt{$_GET['qatt']}_{$filename}")) {
  echo "Error moving the uploaded file";
  exit;
}
//echo "saved as file: {$CFG->dataroot}/{$_GET['courseid']}/users/{$USER->id}/question{$_GET['qid']}_{$filename}<br/>";
//echo "Done :-)<br/>";

//Optional:Insert image information into database and or redirect to a page of some sort

/*
header("Location:thepagewhisthecoolstuff.php");
*/

?>
<!-- update IMG elements in the page and hide the Image editor -->
<script type="text/javascript">
    if(parent){
        parent.document.getElementById('editme<?php echo $_GET['qid']; ?>').src = '<?php echo "{$CFG->wwwroot}/file.php/{$_GET['courseid']}/users/{$USER->id}/question{$_GET['qid']}_qatt{$_GET['qatt']}_{$filename}"; ?>';
        parent.document.getElementById('imgurl<?php echo $_GET['qid']; ?>').value = '<?php echo "{$CFG->wwwroot}/file.php/{$_GET['courseid']}/users/{$USER->id}/question{$_GET['qid']}_qatt{$_GET['qatt']}_{$filename}"; ?>';
        parent.pixlr<?php echo $_GET['qid']; ?>.overlay.hide();
        //parent.document.getElementById('responseform').submit(); // save the image
    }
</script>