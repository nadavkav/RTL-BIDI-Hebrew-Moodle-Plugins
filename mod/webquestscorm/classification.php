<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: classification.php,v 2.0 2009/25/04 
 * @package webquestscorm
 **/


$xpath = new DOMXpath($this->manifest);
$strNode = "classification";
$purpose='';
$source='';
$id='';
$entry='';
$description='';
$keyword='';

$q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
$q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
$q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
$q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	
 
$nodelist = $xpath->query($q);
if ($nodelist->length > 0){

	$node = $nodelist->item(0); 		    
	$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'purpose');
	if ($nodelistElement->length > 0){
		$nodeSubElement = $nodelistElement->item(0);
		$nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		if ($nodelistSubElement->length > 0){		    
			$purpose = $nodelistSubElement->item(0)->nodeValue;
		}
	}	
	$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'taxonPath');
	if ($nodelistElement->length > 0){
		$nodeSubElement = $nodelistElement->item(0);
		$nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'source');
		if ($nodelistSubElement->length > 0){		    
			$nodeSubSubElement = $nodelistElement->item(0);
		       	$nodelistSubSubElement = $nodeSubSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubSubElement->length > 0){		    
		        	$source = $nodelistSubElement->item(0)->nodeValue;
		        }		            
		 }
		 $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'id');
		 if ($nodelistSubElement->length > 0){		    
		        $nodeSubSubElement = $nodelistElement->item(0);
               	 	$id = $nodelistSubElement->item(0)->nodeValue;	            
		 }
		 $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'entry');
		 if ($nodelistSubElement->length > 0){		    
			$nodeSubSubElement = $nodelistElement->item(0);
		 	$nodelistSubSubElement = $nodeSubSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
			if ($nodelistSubSubElement->length > 0){		    
		        	$entry = $nodelistSubElement->item(0)->nodeValue;
		        }		            
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
	$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'keyword');
	if ($nodelistElement->length > 0){
		$nodeSubElement = $nodelistElement->item(0);
		$nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		if ($nodelistSubElement->length > 0){		    
			$keyword = $nodelistSubElement->item(0)->nodeValue;
		}
	}					    
}
		   
?>

<form name="form" method="post" action="editmetadata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>
<table cellpadding="5">
<tr>
    <td align="right"><b><?php print_string("purpose", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["discipline"] = get_string("discipline", "webquestscorm"); 
        $options["idea"] = get_string("idea", "webquestscorm"); 
        $options["prerequisite"] = get_string("prerequisite", "webquestscorm"); 
        $options["educational objective"] = get_string("educational objective", "webquestscorm"); 
        $options["accessibility restrictions"] = get_string("accessibility restrictions", "webquestscorm"); 
        $options["educational level"] = get_string("educational level", "webquestscorm"); 
        $options["skill level"] = get_string("skill level", "webquestscorm"); 
        $options["security level"] = get_string("security level", "webquestscorm"); 
        $options["competency"] = get_string("competency", "webquestscorm");         
        choose_from_menu($options, "purpose", $purpose, "");
    ?>
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("taxon path", "webquestscorm"); ?></b></td>
    <td align="left">
        <?php helpbutton("taxonpath", get_string("taxon path", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("source", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="source" size="75"  maxlength="1000" value="<?php  p($source) ?>" />
    </td>
</tr>
<tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("taxon", "webquestscorm"); ?></b></td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("id", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="id" size="75"  maxlength="50" value="<?php  p($id) ?>" />
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("entry", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="entry" size="75"  maxlength="100" value="<?php  p($entry) ?>" />
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("description", "webquestscorm") ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=56 name='description' maxlength="500"><?php  p($description) ?></textarea>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("keyword", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="keyword" size="75"  maxlength="1000" value="<?php  p($keyword) ?>" />
    </td>
</tr>

<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'classification'; ?>" />
</form>
</table>
