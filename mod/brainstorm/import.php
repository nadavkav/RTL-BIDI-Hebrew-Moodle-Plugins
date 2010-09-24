<?php

/**
* @package brainstorm
* @author Valery Fremaux / 1.8
* @date 22/12/2007
*
* This page shows view for importing inputs. 
* Inputs can be imported uploading a text file with one idea per line
* empty lines are ignored, so are lines starting with !, / or #
*/

/// get viewable responses

if (!has_capability('mod/brainstorm:import', $context)){
    error("This user cannot import data");
    return;
}
print_simple_box_start('center');

/// display the import form
?>
<form name="importform" method="post" action="view.php" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="what" value="import" />
<table align="center" width="80%">
    <tr>
        <td align="right"><b><?php print_string('importfile', 'brainstorm') ?></b></td>
        <td align="left">
            <input type="file" name="inputs" />
        </td>
    </tr>
    <tr>
        <td align="right"><b><?php print_string('clearalldata', 'brainstorm') ?></b></td>
        <td align="left">
            <input type="checkbox" name="clearall" /> <?php print_string('yes') ?>
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <input type="submit" name="go_btn" value="<?php print_string('upload') ?>" />
        </td>
    </tr>
</table>
</form>
<?php
print_simple_box_end();
?>