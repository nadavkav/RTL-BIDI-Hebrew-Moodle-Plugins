<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: metametadata.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

		$xpath = new DOMXpath($this->manifest);
		$catalog ='';
		$entry ='';
		$role ='';
		$entity ='';
		$dateTime ='';
		$description='';
		$language='';

		$strNode = "metaMetadata";
    $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    $q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	
 
    $nodelist = $xpath->query($q);
    if ($nodelist->length > 0){

		    $node = $nodelist->item(0); 
		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'catalog');
		    if ($nodelistElement->length > 0){
		        $catalog = $nodelistElement->item(0)->nodeValue;
		    }		  
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'entry');
		    if ($nodelistElement->length > 0){
		        $entry = $nodelistElement->item(0)->nodeValue;
		    }			
		    	
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'contribute');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $role = $nodelistSubElement->item(0)->nodeValue;
		        }
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'entity');
		        if ($nodelistSubElement->length > 0){		    
		            $entity = $nodelistSubElement->item(0)->nodeValue;
		        }	
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'dateTime');
		        if ($nodelistSubElement->length > 0){		    
		            $dateTime = $nodelistSubElement->item(0)->nodeValue;
		        }	
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'description');
		        if ($nodelistElement->length > 0){
		            $nodeSubElement = $nodelistElement->item(0);
		            $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		            if ($nodelistSubElement->length > 0){		    
		                $description = $nodelistSubElement->item(0)->nodeValue;
		            }
		        }																        
		    }		
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'metadataSchema');
		    if ($nodelistElement->length > 0){
		        $metadataSchema1 = $nodelistElement->item(0)->nodeValue;
		        $metadataSchema2 = $nodelistElement->item(1)->nodeValue;
		    }	 
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'language');
		    if ($nodelistElement->length > 0){
		        $language = $nodelistElement->item(0)->nodeValue;
		    }							    	 
    }
    if (empty($metadataSchema1)) {
        $metadataSchema1 = 'LOMv1.0';
    }
    if (empty($metadataSchema2)) {
        $metadataSchema2 = 'ADLv1.0';
    }
    
?>
<form name="form" method="post" action="editmetadata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>

<table cellpadding="5">
<tr>
    <td align="right"><b><?php print_string("identifier", "webquestscorm"); ?></b></td>
    <td align="left">
        <?php helpbutton("midentifier", get_string("midentifier", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("catalog", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="catalog" size="50"  maxlength="50" value="<?php  p($catalog) ?>" />
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("entry", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="entry" size="50"  maxlength="50" value="<?php  p($entry) ?>" />
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("lcontribute", "webquestscorm"); ?></b></td>
    <td align="left">
        <?php helpbutton("contribute", get_string("lcontribute", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("lrole", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["creator"] = get_string("creator", "webquestscorm"); 
        $options["validator"] = get_string("validator", "webquestscorm"); 
        choose_from_menu($options, "role", $role, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("lentity", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="entity" maxlength="50" size="50" value="<?php  p($entity) ?>" />
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("ldate", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="dateTime" maxlength="50" size="50" value="<?php  p($dateTime) ?>" />
        <?php helpbutton("datetime", get_string("datetime", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("description", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="description" maxlength="50" size="50" value="<?php  p($description) ?>" />
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("metadatascheme", "webquestscorm"); ?></b></td>
    <td align="left">
        <?php helpbutton("mmetadatascheme", get_string("metadatascheme", "webquestscorm"), "webquestscorm"); ?>
</tr>
<tr>
    <td align="right"><b></b></td>
    <td align="left">
    <?php
        $options = array(); 
        $options["LOMv1.0"] = 'LOMv1.0'; 
				choose_from_menu($options, "metadataSchema1", $metadataSchema1, "");
    ?>        
</tr>
<tr>
    <td align="right"><b></b></td>
    <td align="left">
    <?php
        $options = array(); 
        $options["ADLv1.0"] = 'ADLv1.0'; 
				choose_from_menu($options, "metadataSchema2", $metadataSchema2, "");
    ?>        
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("language", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?php
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
<input type="hidden" name="submode" value="<?php  echo 'metametadata'; ?>" />
</form>
</table>
