<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: relation.php v 2.0 2009/25/04
 * @package webquestscorm
 **/

		$xpath = new DOMXpath($this->manifest);
		$kind='';
		$catalog='';
		$entry='';
		$description='';

		$strNode = "relation";
    $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    $q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	
 
    $nodelist = $xpath->query($q);
    if ($nodelist->length > 0){

		    $node = $nodelist->item(0); 
		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'kind');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $kind = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }	
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'catalog');		    
		    if ($nodelistElement->length > 0){
		        $catalog = $nodelistElement->item(0)->nodeValue;
		    }		  
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'entry');
		    if ($nodelistElement->length > 0){
		        $entry = $nodelistElement->item(0)->nodeValue;
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
    <td align="right"><b><?php print_string("kind", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
  
        $options = array(); 
        $options["ispartof"] = get_string("ispartof", "webquestscorm"); 
        $options["haspart"] = get_string("haspart", "webquestscorm"); 
        $options["isversionof"] = get_string("isversionof", "webquestscorm"); 
        $options["hasversion"] = get_string("hasversion", "webquestscorm");
        $options["isformatof"] = get_string("isformatof", "webquestscorm"); 
        $options["hasformat"] = get_string("hasformat", "webquestscorm"); 
        $options["references"] = get_string("references", "webquestscorm"); 
        $options["isreferencedby"] = get_string("isreferencedby", "webquestscorm"); 
        $options["isbaseon"] = get_string("isbaseon", "webquestscorm"); 
        $options["inbasisfor"] = get_string("inbasisfor", "webquestscorm"); 
        $options["requires"] = get_string("requires", "webquestscorm"); 
        $options["isrequiredby"] = get_string("isrequiredby", "webquestscorm"); 
        choose_from_menu($options, "kind", $kind, "");
    ?>
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("resource", "webquestscorm"); ?></b></td>
</tr>
<tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("identifier", "webquestscorm"); ?></b></td>
    <td align="left">
        <?php helpbutton("identifier", get_string("identifier", "webquestscorm"), "webquestscorm"); ?>
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
<tr><td><p></td></tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("description", "webquestscorm") ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=38 name='description' maxlength="500"><?php  p($description) ?></textarea>
    </td>
</tr>

<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'relation'; ?>" />
</form>
</table>
