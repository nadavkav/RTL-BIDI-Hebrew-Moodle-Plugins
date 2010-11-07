<?php

/**
 * This library contains functions to generate XHTML strict output
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: weblib.php,v 1.26 2007/08/21 13:57:11 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Output_API
 */



/**
 * Defines the start of a table.
 *
 * @param integer num number of lines break.
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_br($num=1, $return=false){

	$output = "<br />\n";

	while ($num > 1){
		$output .= "<br />\n";
		$num--;
	}

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Defines the start of a table.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 * 		<li>$propert->border, specifies the border width. Set border="0" to display tables with no borders!
 *  	<li>$propert->width, specifies the width of the table.
 *   	<li>$propert->padding, specifies the space between the cell walls and contents.
 *   	<li>$propert->spacing, specifies the space between cells.
 *   	<li>$propert->class, the class of the element.
 *   	<li>$propert->id, the id of the element.
 *   	<li>$propert->style, an inline style definition.
 * 		<li>$propert->classtr, the class of the element.
 *   	<li>$propert->aligntd, specifies the horizontal alignment of cell content.
 *   	<li>$propert->valigntd, specifies the vertical alignment of cell content.
 *   	<li>$propert->colspantd, indicates the number of columns this cell should span.
 *   	<li>$propert->rowspantd, indicates the number of rows this cell should span.
 * 	 	<li>$propert->classtd, the class of the element.
 * 	 	<li>$propert->idtd, the class of the element.
 * 	 	<li>$propert->styletd, an inline style definition.
 * 		<li>$propert->header, specifies if the table has a header.
 * 		<li>$propert->alignth, specifies the horizontal alignment of cell content.
 *   	<li>$propert->valignth, specifies the vertical alignment of cell content.
 *   	<li>$propert->colspanth, indicates the number of columns this cell should span.
 *   	<li>$propert->rowspanth, indicates the number of rows this cell should span.
 * 	 	<li>$propert->idth, the class of the element.
 * 	 	<li>$propert->classth, the class of the element.
 * 	 	<li>$propert->styleth, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 *	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_table_start($propert=null, $return=false) {

	if (isset($propert->border)) { $propert->border = ' border="'.$propert->border.'"'; }
	else { $propert->border = ''; }

	if (isset($propert->width)) { $propert->width = ' width="'.$propert->width.'"'; }
	else { $propert->width = ''; }

	if (isset($propert->padding)) { $propert->padding = ' cellpadding="'.$propert->padding.'"'; }
	else { $propert->padding = ''; }

	if (isset($propert->spacing)) { $propert->spacing = ' cellspacing="'.$propert->spacing.'"'; }
	else { $propert->spacing = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->classtr)) { $propert->classtr = ' class="'.$propert->classtr.'"'; }
	else { $propert->classtr = ''; }

	if (isset($propert->header)){

		if (isset($propert->alignth)) { $propert->alignth = ' align="'.$propert->alignth.'"'; }
		else { $propert->alignth = ''; }

		if (isset($propert->valignth)) { $propert->valignth = ' valign="'.$propert->valignth.'"'; }
		else { $propert->valignth = ''; }

		if (isset($propert->colspanth)) { $propert->colspanth = ' colspan="'.$propert->colspanth.'"'; }
		else { $propert->colspanth = ''; }

		if (isset($propert->rowspanth)) { $propert->rowspanth = ' rowspan="'.$propert->rowspanth.'"'; }
		else { $propert->rowspanth = ''; }

	    if (isset($propert->idth)) { $propert->idth = ' id="'.$propert->idth.'"'; }
		else { $propert->idth = ''; }

	    if (isset($propert->classth)) { $propert->classth = ' class="'.$propert->classth.'"'; }
		else { $propert->classth = ''; }

		if (isset($propert->styleth)) { $propert->styleth = ' style="'.$propert->styleth.'"'; }
		else { $propert->styleth = ''; }

		if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
		else { $propert->events = ''; }

		$output = "<table".$propert->border.$propert->spacing.$propert->padding.$propert->width.$propert->id.$propert->id.$propert->class.$propert->style.">\n".
     	  "<tr".$propert->classtr."><th".$propert->alignth.$propert->valignth.$propert->colspanth.$propert->rowspanth.$propert->idth.$propert->classth.$propert->styleth.$propert->events.">\n";

	} else {

		if (isset($propert->aligntd)) { $propert->aligntd = ' align="'.$propert->aligntd.'"'; }
		else { $propert->aligntd = ''; }

		if (isset($propert->valigntd)) { $propert->valigntd = ' valign="'.$propert->valigntd.'"'; }
		else { $propert->valigntd = ''; }

		if (isset($propert->colspantd)) { $propert->colspantd = ' colspan="'.$propert->colspantd.'"'; }
		else { $propert->colspantd = ''; }

		if (isset($propert->rowspantd)) { $propert->rowspantd = ' rowspan="'.$propert->rowspantd.'"'; }
		else { $propert->rowspantd = ''; }

	    if (isset($propert->idtd)) { $propert->idtd = ' id="'.$propert->idtd.'"'; }
		else { $propert->idtd = ''; }

	    if (isset($propert->classtd)) { $propert->classtd = ' class="'.$propert->classtd.'"'; }
		else { $propert->classtd = ''; }

		if (isset($propert->styletd)) { $propert->styletd = ' style="'.$propert->styletd.'"'; }
		else { $propert->styletd = ''; }

		if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
		else { $propert->events = ''; }

		$output = "<table".$propert->border.$propert->spacing.$propert->padding.$propert->width.$propert->id.$propert->class.$propert->style.">\n".
     	  "<tr".$propert->classtr."><td".$propert->aligntd.$propert->valigntd.$propert->colspantd.$propert->rowspantd.$propert->idtd.$propert->classtd.$propert->styletd.$propert->events.">\n";
	}

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Print the end of a table.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 * 		<li>$propert->header, specifies if the table has a header.
 *	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_table_end($propert=null, $return=false) {

	if (isset($propert->header)){
    	$output = "</th></tr>\n</table>\n";
	} else {
		$output = "</td></tr>\n</table>\n";
	}

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Change column.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->header, specifies if the table has a header.
 * 		<li>$propert->align, specifies the horizontal alignment of cell content.
 *     	<li>$propert->valign, specifies the vertical alignment of cell content.
 *     	<li>$propert->colspan, indicates the number of columns this cell should span.
 *     	<li>$propert->rowspan, indicates the number of rows this cell should span.
 * 	   	<li>$propert->id, the id of the element.
 * 	   	<li>$propert->class, the class of the element.
 * 	   	<li>$propert->style, an inline style definition.
 * 	   	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_change_column($propert=null, $return=false){

	if (isset($propert->align)) { $propert->align = ' align="'.$propert->align.'"'; }
	else { $propert->align = ''; }

	if (isset($propert->valign)) { $propert->valign = ' valign="'.$propert->valign.'"'; }
	else { $propert->valign = ''; }

	if (isset($propert->colspan)) { $propert->colspan = ' colspan="'.$propert->colspan.'"'; }
	else { $propert->colspan = ''; }

	if (isset($propert->rowspan)) { $propert->rowspan = ' rowspan="'.$propert->rowspan.'"'; }
	else { $propert->rowspan = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

    if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }

	if (isset($propert->header)){
		$output = "</th><th".$propert->align.$propert->valign.$propert->colspan.$propert->rowspan.$propert->id.$propert->class.$propert->style.$propert->events.">\n";
	} else {
		$output = "</td><td".$propert->align.$propert->valign.$propert->colspan.$propert->rowspan.$propert->id.$propert->class.$propert->style.$propert->events.">\n";
	}

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Change row.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->header, specifies if the table has a header.
 *		<li>$propert->align, specifies the horizontal alignment of cell content.
 *     	<li>$propert->valign, specifies the vertical alignment of cell content.
 *     	<li>$propert->colspan, indicates the number of columns this cell should span.
 *     	<li>$propert->rowspan, indicates the number of rows this cell should span.
 * 	   	<li>$propert->id, the class of the element.
 * 	   	<li>$propert->class, the class of the element.
 *		<li>$propert->classtr, the class of the element.
 * 	   	<li>$propert->style, an inline style definition.
 * 	   	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_change_row($propert=null, $return=false){

	if (isset($propert->align)) { $propert->align = ' align="'.$propert->align.'"'; }
	else { $propert->align = ''; }

	if (isset($propert->valign)) { $propert->valign = ' valign="'.$propert->valign.'"'; }
	else { $propert->valign = ''; }

	if (isset($propert->colspan)) { $propert->colspan = ' colspan="'.$propert->colspan.'"'; }
	else { $propert->colspan = ''; }

	if (isset($propert->rowspan)) { $propert->rowspan = ' rowspan="'.$propert->rowspan.'"'; }
	else { $propert->rowspan = ''; }

  	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

    if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->classtr)) { $propert->classtr = ' class="'.$propert->classtr.'"'; }
	else { $propert->classtr = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }

	if (isset($propert->header)){
		$output = "</th></tr>\n<tr".$propert->classtr."><td".$propert->align.$propert->valign.$propert->colspan.$propert->rowspan.$propert->id.$propert->class.$propert->style.$propert->events.">\n";
	} else {
		$output = "</td></tr>\n<tr".$propert->classtr."><td".$propert->align.$propert->valign.$propert->colspan.$propert->rowspan.$propert->id.$propert->class.$propert->style.$propert->events.">\n";
	}

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Creates a form for user input. A form can contain textfields, checkboxes, radio-buttons and more. Forms are used to pass user-data to a specified URL.
 *
 * @param string $info defines input info.
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->action, defines where to send the data when the submit button is pushed.
 *     	<li>$propert->method, the HTTP method for sending data to the action URL.
 *     	<li>$propert->enctype, defines where to send the data when the submit button is pushed.
 * 	   	<li>$propert->id, the id of the element.
 * 	   	<li>$propert->class, the class of the element.
 * 	   	<li>$propert->style, an inline style definition.
 * 	   	<li>$propert->events, event attributes.
 *	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
 function wiki_form($info='', $propert=null, $return=false){

	if (isset($propert->action)) { $propert->action = ' action="'.$propert->action.'"'; }
	else { $propert->action = ''; }

	if (isset($propert->method)) { $propert->method = ' method="'.$propert->method.'"'; }
	else { $propert->method = ''; }

	if (isset($propert->enctype)) { $propert->enctype = ' enctype="'.$propert->enctype.'"'; }
	else { $propert->enctype = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<form".$propert->action.$propert->method.$propert->enctype.$propert->id.$propert->class.$propert->style.$propert->events.">\n".$info."</form>\n";

	if ($return) {
        	return $output;
    	} else {
        	echo $output;
    }
}


/**
 * Creates a form for user input. A form can contain textfields, checkboxes, radio-buttons and more. Forms are used to pass user-data to a specified URL.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->action, defines where to send the data when the submit button is pushed.
 *		<li>$propert->method, the HTTP method for sending data to the action URL.
 *     	<li>$propert->enctype, defines where to send the data when the submit button is pushed.
 * 	   	<li>$propert->id, the id of the element.
 * 	   	<li>$propert->class, the class of the element.
 * 	   	<li>$propert->style, an inline style definition.
 * 	   	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
 function wiki_form_start($propert=null, $return=false){

	if (isset($propert->action)) { $propert->action = ' action="'.$propert->action.'"'; }
	else { $propert->action = ''; }

	if (isset($propert->method)) { $propert->method = ' method="'.$propert->method.'"'; }
	else { $propert->method = ''; }

	if (isset($propert->enctype)) { $propert->enctype = ' enctype="'.$propert->enctype.'"'; }
	else { $propert->enctype = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<form".$propert->action.$propert->method.$propert->enctype.$propert->id.$propert->class.$propert->style.$propert->events.">\n";

	if ($return) {
        	return $output;
    	} else {
        	echo $output;
    }
}


/**
 * Print the end of a form.
 *
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_form_end($return=false){

	$output = "</form>\n";

	if ($return) {
        	return $output;
    	} else {
        	echo $output;
    }
}


/**
 * Defines the relationship between two linked documents.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->rel, defines the relationship between the current document and the targeted document.
 *		<li>$propert->type, specifies the MIME type of the target URL.
 *     	<li>$propert->href, the target URL of the resource.
 * 	   	<li>$propert->class, the class of the element.
 * 	   	<li>$propert->style, an inline style definition.
 * 	   	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_link($propert=null, $return=false){

	if (isset($propert->rel)) { $propert->rel = ' rel="'.$propert->rel.'"'; }
	else { $propert->rel = ''; }

	if (isset($propert->type)) { $propert->type = ' type="'.$propert->type.'"'; }
	else { $propert->type = ''; }

	if (isset($propert->href)) { $propert->href = ' href="'.$propert->href.'"'; }
	else { $propert->href = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<link".$propert->rel.$propert->type.$propert->href.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines an anchor. An anchor can be used in two ways:
 * <ol>
 *   <li>To create a link to another document by using the href attribute.</li>
 *   <li>To create a bookmark inside a document, by using the name or id attribute.</li>
 * </ol>
 *
 * @param string $info defines the text.
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->href, The target URL of the link.
 *   	<li>$propert->name, Names an anchor. Use this attribute to create a bookmark in a document.
 *   	<li>$propert->rel, Specifies the relationship between the current document and the target URL.
 * 	 	<li>$propert->type, Specifies the MIME (Multipurpose Internet Mail Extensions) type of the target URL.
 * 	 	<li>$propert->class, class of this anchor.
 * 	 	<li>$propert->style, style of this anchor.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_a($info='', $propert=null, $return=false){

	if (isset($propert->href)) { $propert->href = ' href="'.$propert->href.'"'; }
	else { $propert->href = ''; }

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->rel)) { $propert->rel = ' rel="'.$propert->rel.'"'; }
	else { $propert->rel = ''; }

	if (isset($propert->type)) { $propert->type = ' type="'.$propert->type.'"'; }
	else { $propert->type = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<a".$propert->href.$propert->name.$propert->rel.$propert->type.$propert->class.$propert->style.$propert->events.">".$info."</a>\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, defines the text on the button.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_button($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"button\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->checked, indicates that the input element should be checked when it first loads.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_checkbox($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->checked)) { $propert->checked = ' checked="checked"'; }
	else { $propert->checked = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"checkbox\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->checked.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}



/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->accept, a comma-separated list of MIME types that indicates the MIME type of the file transfer.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_file($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->accept)) { $propert->accept = ' accept="'.$propert->accept.'"'; }
	else { $propert->accept = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"file\"".$propert->name.$propert->disabled.$propert->size.$propert->accept.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_hidden($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"hidden\"".$propert->name.$propert->value.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->alt, defines an alternate text for the image.
 * 		<li>$propert->src, defines the URL of the image to display.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_image($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->alt)) { $propert->alt = ' alt="'.$propert->alt.'"'; }
	else { $propert->alt = ''; }

	if (isset($propert->src)) { $propert->src = ' src="'.$propert->src.'"'; }
	else { $propert->src = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"image\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->alt.$propert->src.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_password($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"password\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->checked, indicates that the input element should be checked when it first loads.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_radio($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->checked)) { $propert->checked = ' checked="checked"'; }
	else { $propert->checked = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"radio\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->checked.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_reset($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"reset\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_submit($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"submit\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an input field where the user can enter data.
 *
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->name, defines a unique name for the input element.
 *   	<li>$propert->value, value for the input element.
 *   	<li>$propert->disabled, disables the input element when it first loads so that the user can not write text in it, or select it.
 * 	 	<li>$propert->size, defines the size of the input element.
 * 		<li>$propert->maxlength, defines the maximum number of characters allowed in a text field.
 * 		<li>$propert->readonly, indicates that the value of this field cannot be modified.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_input_text($propert=null, $return=false){

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->maxlength)) { $propert->maxlength = ' maxlength="'.$propert->maxlength.'"'; }
	else { $propert->maxlength = ''; }

	if (isset($propert->readonly)) { $propert->readonly = ' readonly="readonly"'; }
	else { $propert->readonly = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<input type=\"text\"".$propert->name.$propert->value.$propert->disabled.$propert->size.$propert->maxlength.$propert->readonly.$propert->id.$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines a label to a control. If you click the text within the label element, it is supposed to toggle the control.
 *
 * @param string $info defines the text.
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->for, defines which form element the label is for, Set to an ID of a form element.
 * 		<li>$propert->id, a unique id for the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_label($info='', $propert=null, $return=false){

	if (isset($propert->for)) { $propert->for = ' for="'.$propert->for.'"'; }
	else { $propert->for = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<label".$propert->for.$propert->id.$propert->class.$propert->style.$propert->events.">".$info."</label>\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Creates a drop-down list.
 *
 * @param string $options defines an option in the drop-down list.
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->disabled, disables the drop-down list.
 *   	<li>$propert->name, defines a unique name for the drop-down list.
 *   	<li>$propert->multiple, specifies that multiple items can be selected at a time.
 * 	 	<li>$propert->size, defines the number of visible items in the drop-down list.
 * 	 	<li>$propert->id, the id of the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_select($options, $propert=null, $return=false){

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->multiple)) { $propert->multiple = ' multiple="multiple"'; }
	else { $propert->multiple = ''; }

	if (isset($propert->size)) { $propert->size = ' size="'.$propert->size.'"'; }
	else { $propert->size = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<select".$propert->disabled.$propert->name.$propert->multiple.$propert->size.$propert->id.$propert->class.$propert->style.$propert->events.">\n".$options."</select>\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * This element allows you to group choices. When you have a long list of options, groups of related choices are easier to handle.
 *
 * @param string $options defines an option in the drop-down list.
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->label, defines the label for the option group.
 * 		<li>$propert->disabled, disables the option-group when it first loads.
 * 	 	<li>$propert->id, the id of the element.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_optgroup($options, $propert=null, $return=false){

	if (isset($propert->label)) { $propert->label = ' label="'.$propert->label.'"'; }
	else { $propert->label = ''; }

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<optgroup".$propert->label.$propert->disabled.$propert->id.$propert->class.$propert->style.$propert->events.">\n".$options."</optgroup>\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}
/**
 * Defines an option in the drop-down list.
 *
 * @param string $info defines the text.
 * @param array $propert is an object with several properties.
 * 	<ul>
 * 		<li>$propert->disabled, specifies that the option should be disabled when it first loads.
 *   	<li>$propert->selected, specifies that the option should appear selected (will be displayed first in the list).
 *   	<li>$propert->label, defines a label to use when using <optgroup>.
 * 	 	<li>$propert->value, defines the value of the option to be sent to the server.
 * 	 	<li>$propert->class, the class of the element.
 * 	 	<li>$propert->style, an inline style definition.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_option($info='', $propert=null, $return=false){

	if (isset($propert->disabled)) { $propert->disabled = ' disabled="disabled"'; }
	else { $propert->disabled = ''; }

	if (isset($propert->selected)) { $propert->selected = ' selected="selected"'; }
	else { $propert->selected = ''; }

	if (isset($propert->label)) { $propert->label = ' label="'.$propert->label.'"'; }
	else { $propert->label = ''; }

	if (isset($propert->value)) { $propert->value = ' value="'.$propert->value.'"'; }
	else { $propert->value = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<option".$propert->disabled.$propert->selected.$propert->label.$propert->value.$propert->class.$propert->style.$propert->events.">".$info."</option>\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Inserts a horizontal rule.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->class, class of this style horizontal rule.
 * 	 	<li>$propert->style, style of this style horizontal rule.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_hr($propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<hr".$propert->class.$propert->style.$propert->events." />\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Defines a division/section in a document.
 *
 * @param string $info defines the text.
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->class, class of this division/section.
 * 	 	<li>$propert->style, style of this division/section.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_div($info='', $propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<div".$propert->class.$propert->style.$propert->events.">\n".$info."</div>";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Defines a division/section in a document.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->id, id of this division/section.
 *		<li>$propert->class, class of this division/section.
 * 	 	<li>$propert->style, style of this division/section.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_div_start($propert=null, $return=false){

	if (isset($propert->id)) { $propert->id = ' id="'.$propert->id.'"'; }
	else { $propert->id = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }

	$output = "<div".$propert->id.$propert->class.$propert->style.$propert->events.">\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Print the end of a division/section.
 *
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_div_end($return=false){

	$output = "</div>\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Used to group inline-elements in a document.
 *
 * @param string $info defines the text.
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->class, class of this division/section.
 * 	 	<li>$propert->style, style of this division/section.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_span($info='', $propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<span".$propert->class.$propert->style.$propert->events.">\n".$info."</span>\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * The h1 to h6 tags define headers. h1 defines the largest header. h6 defines the smallest header.
 *
 * @param string $info defines the text.
 * @param string $num defines the number of the header.
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->class, class of this division/section.
 * 	 	<li>$propert->style, style of this division/section.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_size_text($info='', $num=1, $propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<h".$num.$propert->class.$propert->style.$propert->events.">".$info."</h".$num.">\n";

	if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Defines a paragraph.
 *
 * @param string $info defines the text in a paragraph.
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->class, class of this division/section.
 * 	 	<li>$propert->style, style of this division/section.
 * 	 	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_paragraph($info='', $propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<p".$propert->class.$propert->style.$propert->events.">\n".$info."</p>\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Font style element. Renders as bold text.
 *
 * @param string $info defines the text.
 * @param array $propert is an object with several properties.
 *	<ul>
 * 		<li>$propert->class, class of this element.
 * 	 	<li>$propert->style, style of this element.
 * 	 	<li>$propert->events, event attributes.
 *	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_b($info='', $propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<b".$propert->class.$propert->style.$propert->events.">".$info."</b>\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines an image.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->src, the URL of the image to display.
 *     	<li>$propert->alt, defines a short description of the image.
 *     	<li>$propert->height, defines the height of an image.
 *     	<li>$propert->width, sets the width of  an image.
 * 	   	<li>$propert->class, the class of this image.
 * 	   	<li>$propert->style, style of this image.
 * 	   	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_img($propert=null, $return=false){

	if (isset($propert->src)) { $propert->src = ' src="'.$propert->src.'"'; }
	else { $propert->src = ''; }

	if (isset($propert->alt)) { $propert->alt = ' alt="'.$propert->alt.'"'; }
	else { $propert->alt = ' alt=""'; }

	if (isset($propert->height)) { $propert->height = ' height="'.$propert->height.'"'; }
	else { $propert->height = ''; }

	if (isset($propert->width)) { $propert->width = ' width="'.$propert->width.'"'; }
	else { $propert->width = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }

	$output = "<img".$propert->src.$propert->alt.$propert->height.$propert->width.$propert->class.$propert->style.$propert->events." />";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines a text-area (a multi-line text input control).
 *
 * @param number $info defines the text.
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->cols, specifies the number of columns visible in the text-area.
 *     	<li>$propert->rows, specifies the number of rows visible in the text-area.
 *     	<li>$propert->name, specifies a name for the text-area.
 * 	   	<li>$propert->class, the class of this image.
 * 	   	<li>$propert->style, style of this image.
 * 	   	<li>$propert->events, event attributes.
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_textarea($info='', $propert=null, $return=false){

	if (isset($propert->cols)) { $propert->cols = ' cols="'.$propert->cols.'"'; }
	else { $propert->cols = ''; }

	if (isset($propert->rows)) { $propert->rows = ' rows="'.$propert->rows.'"'; }
	else { $propert->rows = ''; }

	if (isset($propert->name)) { $propert->name = ' name="'.$propert->name.'"'; }
	else { $propert->name = ''; }

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }

	$output = "<textarea".$propert->cols.$propert->rows.$propert->name.$propert->class.$propert->style.$propert->events.">\n".$info."\n</textarea>";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines a script, such as a JavaScript.
 *
 * @param string $info defines the text.
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->type, indicates the MIME type of the script.
 *     	<li>$propert->src, defines a URL to a file that contains the script .
 * 	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_script($info='', $propert=null, $return=false){

	if (isset($propert->type)) { $propert->type = ' type="'.$propert->type.'"'; }
	else { $propert->type = ''; }

	if (isset($propert->src)) { $propert->src = ' src="'.$propert->src.'"'; }
	else { $propert->src = ''; }


	if($info!='') {
		$output = "<script".$propert->type.$propert->src.">\n".$info."\n</script>\n";
	}
	else {
		$output ="<script".$propert->type.$propert->src."></script>\n";
	}
	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an ordered list.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 * 		<li>$propert->class, class of this element.
 * 	 	<li>$propert->style, style of this element.
 * 		<li>$propert->classli, class of this element.
 * 	 	<li>$propert->styleli, style of this element.
 * 	 	<li>$propert->events, event attributes.
 *	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_start_ol($propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->classli)) { $propert->classli = ' class="'.$propert->classli.'"'; }
	else { $propert->classli = ''; }

	if (isset($propert->styleli)) { $propert->styleli = ' style="'.$propert->styleli.'"'; }
	else { $propert->styleli = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<ol".$propert->class.$propert->style.">\n<li".$propert->classli.$propert->styleli.$propert->events.">\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the end of an ordered list.
 *
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_end_ol($return=false){

	$output = "</li>\n</ol>\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of an unordered list.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 * 		<li>$propert->class, class of this element.
 * 	 	<li>$propert->style, style of this element.
 * 		<li>$propert->classli, class of this element.
 * 	 	<li>$propert->styleli, style of this element.
 * 	 	<li>$propert->events, event attributes.
 *	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_start_ul($propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->classli)) { $propert->classli = ' class="'.$propert->classli.'"'; }
	else { $propert->classli = ''; }

	if (isset($propert->styleli)) { $propert->styleli = ' style="'.$propert->styleli.'"'; }
	else { $propert->styleli = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "<ul".$propert->class.$propert->style.">\n<li".$propert->classli.$propert->styleli.$propert->events.">";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the end of an unordered list.
 *
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_end_ul($return=false){

	$output = "</li>\n</ul>\n";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}


/**
 * Defines the start of a list item.
 *
 * @param array $propert is an object with several properties.
 *	<ul>
 *		<li>$propert->class, class of this element.
 * 	 	<li>$propert->style, style of this element.
 * 	 	<li>$propert->events, event attributes.
 *	</ul>
 * @param bool $return whether to return an output string or echo now.
 */
function wiki_change_li($propert=null, $return=false){

	if (isset($propert->class)) { $propert->class = ' class="'.$propert->class.'"'; }
	else { $propert->class = ''; }

	if (isset($propert->style)) { $propert->style = ' style="'.$propert->style.'"'; }
	else { $propert->style = ''; }

	if (isset($propert->events)) { $propert->events = ' '.$propert->events; }
	else { $propert->events = ''; }


	$output = "</li>\n<li".$propert->class.$propert->style.$propert->events.">";

	if ($return) {
    	return $output;
	} else {
    	echo $output;
    }
}
?>
