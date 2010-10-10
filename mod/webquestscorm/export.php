<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez 
 * @version $Id: export.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
?>
<form name="form" method="post" action="editexport.php?cmid=<?php echo $this->cm->id; ?>"> 
<center>
<table cellpadding="5">
<tr valign="top">
<?php 
global $CFG;
if (file_exists($this->path.'/pif'.$this->cm->id.'.zip')){
   echo '<p><a href='.$CFG->wwwroot.'/file.php?file=/'.$this->cm->course.'/moddata/webquestscorm/'.$this->cm->id.'/pif'.$this->cm->id.'.zip>pif'.$this->cm->id.'.zip</a>'; 
}
?>
</tr>
<tr valign="top">
    <input type="hidden" name="mode" value="<?php  echo 'export'; ?>" />
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("generatePIF","webquestscorm") ?>" /></td>
</tr>
</form>
</table>

