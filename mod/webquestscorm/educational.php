<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: educational.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
		$xpath = new DOMXpath($this->manifest);
		$interactivityType='';
		$learningResourceType='';
		$interactivityLevel='';
		$semanticDensity='';
		$intendedEndUserRoler='';
		$context='';
		$typicalAgeRange='';
		$difficulty='';
		$duration='';
		$description='';
		$language='';

		$strNode = "educational";
    $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    $q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	
 
    $nodelist = $xpath->query($q);
    if ($nodelist->length > 0){

		    $node = $nodelist->item(0); 
		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'semanticDensity');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $semanticDensity = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }	
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'context');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $context = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }	
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'typicalAgeRange');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $typicalAgeRange = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'difficulty');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $difficulty = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }	
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'duration');
		    if ($nodelistElement->length > 0){
		        $duration = $nodelistElement->item(0)->nodeValue;
		    }								
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'description');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $description = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }				    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'language');
		    if ($nodelistElement->length > 0){
		        $language = $nodelistElement->item(0)->nodeValue;
		    }								    								 
    }    
?>
<form name="form" method="post" action="editmetadata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>
<table cellpadding="5">
<tr>
    <td align="right"><b><?php print_string("interactivityType", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["active"] = get_string("active", "webquestscorm"); 
        choose_from_menu($options, "interactivityType", $interactivityType, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("learningResourceType", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["exercise"] = get_string("exercise", "webquestscorm"); 
        choose_from_menu($options, "learningResourceType", $learningResourceType, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("interactivityLevel", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["very low"] = get_string("very low", "webquestscorm"); 
        choose_from_menu($options, "interactivityLevel", $interactivityLevel, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("semanticDensity", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["very low"] = get_string("very low", "webquestscorm"); 
        $options["low"] = get_string("low", "webquestscorm");
        $options["medium"] = get_string("medium", "webquestscorm");
        $options["high"] = get_string("high", "webquestscorm");
        $options["very high"] = get_string("very high", "webquestscorm");
        choose_from_menu($options, "semanticDensity", $semanticDensity, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("intendedEndUserRole", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["learner"] = get_string("learner", "webquestscorm"); 
        choose_from_menu($options, "intendedEndUserRole", $intendedEndUserRoler, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("context", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["school"] = get_string("school", "webquestscorm"); 
        $options["higher education"] = get_string("higher education", "webquestscorm"); 
        $options["training"] = get_string("training", "webquestscorm"); 
        $options["other"] = get_string("other", "webquestscorm");; 
        choose_from_menu($options, "context", $context, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("typicalAgeRange", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="typicalAgeRange" size="50"  maxlength="50" value="<?php  p($typicalAgeRange) ?>" />
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("difficulty", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["very easy"] = get_string("very easy", "webquestscorm"); 
        $options["easy"] = get_string("easy", "webquestscorm"); 
        $options["medium"] = get_string("medium", "webquestscorm"); 
        $options["difficult"] = get_string("difficult", "webquestscorm"); 
        $options["very difficult"] = get_string("very difficult", "webquestscorm"); 
        choose_from_menu($options, "difficulty", $difficulty, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("typicalLearningTime", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="duration" size="50"  maxlength="50" value="<?php  p($duration) ?>" />
        <?php helpbutton("duration", get_string("duration", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("description", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=38 name='description' maxlength="500"><?php  p($description) ?></textarea>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("language", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["en"] = get_string("en", "webquestscorm"); 
        $options["es"] = get_string("es", "webquestscorm");
				choose_from_menu($options, "language", $language, "");
    ?>
    </td>
</tr>

<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'educational'; ?>" />
</form>
</table>
