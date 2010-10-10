<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez 
 * @version $Id: introdata.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
 ?>
<form name="form" method="post" action="editdata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>
<table cellpadding="5">
<tr valign="top">
     <td align="right"><b><?//php print_string("introduction", "webquestscorm") ?></b>
     <br /><br />
     <?php 
        helpbutton("writing", get_string("helpwriting"), "moodle", true, true);
        echo "<br />";
        helpbutton("questions", get_string("helpquestions"), "moodle", true, true);
        echo "<br />";
        if ($this->usehtmleditor) {
           helpbutton("richtext", get_string("helprichtext"), "moodle", true, true);
        } else {
           emoticonhelpbutton("form", "description");
        } 
    ?>
    </td>
    <td>
    <?php  
        print_textarea($this->usehtmleditor, 20, 60, 680, 400, "data", $data);
        if ($this->usehtmleditor) {
            use_html_editor('');
            echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
        } else {
            echo '<div align="right">';
            helpbutton("textformat", get_string("formattexttype"));
            print_string("formattexttype");
            echo ':&nbsp;';
            choose_from_menu(format_text_menu(), "format", $this->defaultformat, ""); 
            echo '</div>';
        }
    ?>
    </td>
</tr>
<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>


</table>
