<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: annotation.php,v 2.0 2009/25/04 
 * @package webquestscorm
 **/

$xpath = new DOMXpath($this->manifest);		
$strNode = "annotation";
$entity='';
$dateTime='';
$dateDescription='';
$description='';
$q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
$q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
$q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
$q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	
 
$nodelist = $xpath->query($q);
if ($nodelist->length > 0){
	$node = $nodelist->item(0);
		    
	$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'entity');
	if ($nodelistElement->length > 0){
		$entity = $nodelistElement->item(0)->nodeValue;
	}		
	$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'dateTime');
	if ($nodelistElement->length > 0){
		$dateTime = $nodelistElement->item(0)->nodeValue;
	}				    
	$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'date');
	if ($nodelistElement->length > 0){
		$nodeSubElement = $nodelistElement->item(0);
		$nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		if ($nodelistSubElement->length > 0){		    
			$dateDescription = $nodelistSubElement->item(0)->nodeValue;
		}
	}	
	$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'description');
	if ($nodelistElement->length > 1){
		$nodeSubElement = $nodelistElement->item(1);
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
    <td align="right"><b><?php print_string("entity", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="entity" size="75"  maxlength="1000" value="<?php  p($entity) ?>" />
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("date", "webquestscorm"); ?></b></td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("dateTime", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="dateTime" size="50"  maxlength="100" value="<?php  p($dateTime) ?>" />
        <?php helpbutton("datetime", get_string("datetime", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("description", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="dateDescription" size="75"  maxlength="500" value="<?php  p($dateDescription) ?>" />
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("description", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=56 name='description' maxlength="500"><?php  p($description) ?></textarea>
    </td>
</tr>
<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'annotation'; ?>" />
</form>
</table>
