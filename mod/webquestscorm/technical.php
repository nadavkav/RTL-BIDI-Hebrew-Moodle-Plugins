<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: technical.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

		$xpath = new DOMXpath($this->manifest);
		$format='';
		$size='';
		$location='';
		$type='';
		$name='';
		$installationRemarks='';
		$otherPlatformRequirements='';

		$strNode = "technical";
    $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    $q = $q.'/*[local-name()="'.$strNode.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	
    
    $nodelist = $xpath->query($q);
    if ($nodelist->length > 0){
		    $node = $nodelist->item(0);
		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'size');
		    if ($nodelistElement->length > 0){
		        $size = $nodelistElement->item(0)->nodeValue;
		    }			

		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'location');
		    if ($nodelistElement->length > 0){
		        $location = $nodelistElement->item(0)->nodeValue;
		    }			
											
				$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'installationRemarks');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $installationRemarks = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }		

				$nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'otherPlatformRequirements');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $otherPlatformRequirements = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }			    
    }				    

?>
<form name="form" method="post" action="editmetadata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>
<table cellpadding="5">
<tr>
    <td align="right"><b><?php print_string("format", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["text/html"] = get_string("formatTextHtml", "webquestscorm"); 
        choose_from_menu($options, "format", $format, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("size", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="size" size="50"  maxlength="50" value="<?php  p($size) ?>" />
        <?php helpbutton("size", get_string("size", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("location", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <input type="text" name="location" size="50"  maxlength="100" value="<?php  p($location) ?>" />
        <?php helpbutton("tlocation", get_string("location", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("requirement", "webquestscorm"); ?></b></td>
    <td align="left">
        <?php helpbutton("trequirement", get_string("requirement", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("type", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["browser"] = get_string("browser", "webquestscorm"); 
        choose_from_menu($options, "type", $type, "");
    ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("name", "webquestscorm"); ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        $options["any"] = get_string("any", "webquestscorm"); 
        choose_from_menu($options, "name", $name, "");
    ?>
    </td>
</tr>

<tr><td><p></td></tr><tr><td><p></td></tr>
<tr>
    <td align="right"><b><?php print_string("installationRemarks", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=38 name='installationRemarks' maxlength="500"><?php  p($installationRemarks) ?></textarea>
        <?php helpbutton("tinstallationremarks", get_string("installationRemarks", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr>
    <td align="right"><b><?php print_string("otherPlatformRequirements", "webquestscorm"); ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=38 name='otherPlatformRequirements' maxlength="500"><?php  p($otherPlatformRequirements) ?></textarea>
        <?php helpbutton("tplatform", get_string("otherPlatformRequirements", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'technical'; ?>" />
</form>
</table>
