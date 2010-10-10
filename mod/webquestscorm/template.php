<?php 
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: template.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
    if (empty($this->webquestscorm->template)) {
        $this->webquestscorm->template ='topblue';
    }
?>
<form name="form" method="post" action="edittemplate.php?cmid=<?php echo $this->cm->id; ?>"> 
<center>
<table cellpadding="5">
<tr><b><?php print_string("selectTemplate", "webquestscorm"); ?></b><p>
</tr>
<tr>
    <td align="right"><b><input type='radio' <?php if ($this->webquestscorm->template == 'topblue.css') echo ' checked ';?> name='template' value='topblue.css'></b></td>
    <td align="left"><?php print_string("topblue", "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><input type='radio' <?php if ($this->webquestscorm->template == 'topgreen.css') echo ' checked ';?> name='template' value='topgreen.css'></b></td>
    <td align="left"><?php print_string("topgreen", "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><input type='radio' <?php if ($this->webquestscorm->template == 'toporange.css') echo ' checked ';?> name='template' value='toporange.css'></b></td>
    <td align="left"><?php print_string("toporange", "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><br/></td>
    <td align="left"><br/>
    </td>
</tr>
<tr>
    <td align="right"><b><input type='radio' <?php if ($this->webquestscorm->template == 'leftblue.css') echo ' checked ';?> name='template' value='leftblue.css'></b></td>
    <td align="left"><?php print_string("leftblue", "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><input type='radio' <?php if ($this->webquestscorm->template == 'leftgreen.css') echo ' checked ';?> name='template' value='leftgreen.css'></b></td>
    <td align="left"><?php print_string("leftgreen", "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><input type='radio' <?php if ($this->webquestscorm->template == 'leftorange.css') echo ' checked ';?> name='template' value='leftorange.css'></b></td>
    <td align="left"><?php print_string("leftorange", "webquestscorm"); ?>
    </td>
</tr>


<input type="hidden" name="mode" value="<?php  echo 'template'; ?>" />
<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
</form>
</table>

