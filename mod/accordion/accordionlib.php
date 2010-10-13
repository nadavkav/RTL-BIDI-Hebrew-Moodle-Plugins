<?php  // $Id: lib.php,v 1.12 2008/10/15 09:33:07  $
  	global $iteration;
if(!$iteration)
{

	$iteration=1;
}


 if (!$mod->visible) {
                        echo "<span class=\"dimmed_text\">";
		    }
		 
if ($iteration<=1)
		{
		echo '<script language="JavaScript" src="'.$CFG->wwwroot.'/mod/accordion/accordion.js" type="text/javascript"></script><link href="'.$CFG->wwwroot.'/mod/accordion/accordion.css" type="text/css" rel="stylesheet" />';
		}
		echo '<div class="Accordion" id="Accordion'.$iteration.'" tabindex="0">';
		echo '<div class="AccordionPanel AccordionPanelClosed">';
		$e='';
		$e=explode('/---/',$extra);
		echo '<div class="AccordionPanelTab">';
		echo format_text($e[0], FORMAT_HTML, $labelformatoptions);
		echo '</div>';
		echo '<div class="summary">';
		echo format_text($e[1], FORMAT_HTML, $labelformatoptions);
		echo '</div></div>';
		echo '</div><!-- Do not edit below this marker -->';
		echo '<script language="JavaScript" type="text/javascript">';
		echo 'var Accordion'.($iteration).' = new Spry.Widget.Accordion("Accordion'.($iteration).'");';
		echo '</script>';

		$iteration++;
		echo '<script src="'.$CFG->wwwroot.'/mod/accordion/accord1.js" type="text/javascript"></script>';

		
if (!$mod->visible) {
                        echo "</span>";
                    }

?>
