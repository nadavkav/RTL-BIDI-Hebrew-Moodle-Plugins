<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: rights.php v 2.0 2009/25/04
 * @package webquestscorm
 **/

		$xpath = new DOMXpath($this->manifest);
		$cost='';
		$copyrightAndOtherRestrictions='';
		$description='';

		$strNode = "rights";
    $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    $q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	
 
    $nodelist = $xpath->query($q);
    if ($nodelist->length > 0){

		    $node = $nodelist->item(0); 
		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'cost');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $cost = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }	
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'copyrightAndOtherRestrictions');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $copyrightAndOtherRestrictions = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }			    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'description');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $description = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }
		}
?>
<form name="form" method="post" action="editmetadata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>
<table cellpadding="5">
<tr>
    <td align="right"><b><?php print_string("cost", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["no"] = get_string("no", "webquestscorm"); 
        $options["yes"] = get_string("yes", "webquestscorm"); 
        choose_from_menu($options, "cost", $cost, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("copyright", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["no"] = get_string("no", "webquestscorm"); 
        $options["yes"] = get_string("yes", "webquestscorm"); 
        choose_from_menu($options, "copyrightAndOtherRestrictions", $copyrightAndOtherRestrictions, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("description", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=38 name='description' maxlength="500"><?php  p($description) ?></textarea>
    </td>
</tr>

<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'rights'; ?>" />
</form>
</table>
