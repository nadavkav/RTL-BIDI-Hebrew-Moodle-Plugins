<form id="theform" method="post" action="edittitle.php">
<table summary="Summary of week" cellpadding="5" class="boxaligncenter">
<tr valign="top">
    <td align="right">
      <p><b><?php print_string("directoryname","format_project") ?>
      <?php echo(helpbutton('format/project/directoryname', get_string("directoryname","format_project"), 'moodle', true, false, '', true)); ?>
      :</b></p>
    </td>
    <td>
      <?php print_textfield ('directoryname', $form->directoryname, get_string("directoryname","format_project") , 30, 30); ?>
    </td>
</tr>
<tr>
    <td colspan="2" align="center">
        <input type="hidden" name="id" value="<?php echo $form->id ?>" />
        <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
        <input type="hidden" name="olddirectoryname" value="<?php echo($form->directoryname) ?>" />
        <input type="submit" value="<?php print_string("savechanges") ?>" />
    </td>
</tr>
</table>
</form>
