<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: lifecycle.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

		$xpath = new DOMXpath($this->manifest);
		$version ='';
		$status ='';
		$role ='';
		$entity ='';
		$dateTime ='';
		$description='';

		$strNode = "lifeCycle";
    $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    $q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	 
 
    $nodelist = $xpath->query($q);
    if ($nodelist->length > 0){

		    $node = $nodelist->item(0); 
		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'version');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $version = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'status');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $status = $nodelistSubElement->item(0)->nodeValue;
		        }
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
    }

    
?>
<form name="form" method="post" action="editmetadata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>

<table cellpadding="5">
<tr>
    <td align="right"><b><?php print_string("lversion", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="version" maxlength="50" size="50" value="<?php  p($version) ?>" />
        <?php helpbutton("lversion", get_string("lversion", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("lstatus", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP 

        $options = array(); 
        $options["draft"] = get_string("draft", "webquestscorm"); 
				$options["final"] = get_string("final", "webquestscorm");
        $options["revised"] = get_string("revised", "webquestscorm"); 
				$options["unavailable"] = get_string("unavailable", "webquestscorm");				
        choose_from_menu($options, "status", $status, "");
    ?>
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("lcontribute", "webquestscorm"); ?></b></td>
    <td align="left">
        <?php helpbutton("lcontribute", get_string("lcontribute", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("lrole", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP 
        $options = array(); 
        $options["author"] = get_string("author", "webquestscorm"); 
        $options["content provider"] = get_string("contentProvider", "webquestscorm"); 
        $options["editor"] = get_string("editor", "webquestscorm"); 
        $options["educational validator"] = get_string("educationalValidator", "webquestscorm"); 
        $options["graphical designer"] = get_string("graphicalDesigner", "webquestscorm"); 
        $options["initiator"] = get_string("initiator", "webquestscorm"); 
        $options["instructional designer"] = get_string("instructionalDesigner", "webquestscorm"); 
        $options["publisher unknown"] = get_string("publisherUnknown", "webquestscorm"); 
        $options["script writer"] = get_string("scriptWriter", "webquestscorm"); 
        $options["technical implementer"] = get_string("technicalImplementer", "webquestscorm"); 
        $options["technical validator"] = get_string("technicalValidator", "webquestscorm"); 
        $options["terminator"] = get_string("terminator", "webquestscorm"); 
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

<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>

<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'lifecycle'; ?>" />
</form>
</table>

