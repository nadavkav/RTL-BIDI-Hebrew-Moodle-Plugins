<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez 
 * @version $Id: general.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
		
		$xpath = new DOMXpath($this->manifest);
		$catalog='';
		$entry='';
		$title='';
		$language='';
		$description='';
		$keyword='';
		$coverage='';
		$structure='';
		$aggregationLevel='';


		$strNode = "general";
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
		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'title');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $title = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'language');
		    if ($nodelistElement->length > 0){
		        $language = $nodelistElement->item(0)->nodeValue;
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
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'coverage');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'string');
		        if ($nodelistSubElement->length > 0){		    
		            $coverage = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }		    
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'structure');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $structure = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }		
		    $nodelistElement = $node->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'aggregationLevel');
		    if ($nodelistElement->length > 0){
		        $nodeSubElement = $nodelistElement->item(0);
		        $nodelistSubElement = $nodeSubElement->getElementsByTagNameNS('http://ltsc.ieee.org/xsd/LOM', 'value');
		        if ($nodelistSubElement->length > 0){		    
		            $aggregationLevel = $nodelistSubElement->item(0)->nodeValue;
		        }
		    }									 
    }

    
?>
<form name="form" method="post" action="editmetadata.php?cmid=<?php echo $this->cm->id; ?>&element=<?php echo $element; ?>"> 
<center>

<table cellpadding="5">
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
    <td align="right"><b><?php  print_string("title", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="title" size="75"  maxlength="255" value="<?php  p($title) ?>" />
        <?php helpbutton("title", get_string("title", "webquestscorm"), "webquestscorm"); ?>
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
    <td align="right"><b><?php  print_string("description", "webquestscorm") ?>:</b></td>
    <td align="left">
        <textarea rows=5 cols=56 name='description' maxlength="500"><?php  p($description) ?></textarea>
        <?php helpbutton("description", get_string("description", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("keyword", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="keyword" size="75"  maxlength="255" value="<?php  p($keyword) ?>" />
        <?php helpbutton("keyword", get_string("keyword", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("coverage", "webquestscorm") ?>:</b></td>
    <td align="left">
        <input type="text" name="coverage" size="75"  maxlength="255" value="<?php  p($coverage) ?>" />
        <?php helpbutton("coverage", get_string("coverage", "webquestscorm"), "webquestscorm"); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("structure", "webquestscorm") ?>:</b></td>
    <td align="left">
    <?PHP
        $options = array(); 
        //$options[] = get_string("atomic", "webquestscorm"); 
				//$options[] = get_string("branched", "webquestscorm");
        //$options[] = get_string("collection", "webquestscorm"); 
				//$options[] = get_string("hierarchical", "webquestscorm");
        $options["linear"] = get_string("linear", "webquestscorm"); 
				//$options[] = get_string("mixed", "webquestscorm");
        //$options[] = get_string("networked", "webquestscorm"); 
				//$options[] = get_string("parceled", "webquestscorm");												
        choose_from_menu($options, "structure", $structure, "");
    ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("aggregationlevel", "webquestscorm") ?>:</b></td>
    <td align="left">
        <?PHP
        $options = array(); 
        $options["1"] = 1; 
				//$options[] = 2;
        //$options[] = 3; 
				//$options[] = 4;												
        choose_from_menu($options, "aggregationLevel", $aggregationLevel, "");
    ?>

    </td>
</tr>

<tr valign="top">
    <td colspan="2" align="center" ><input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>

<input type="hidden" name="mode" value="<?php  echo 'metadata'; ?>" />
<input type="hidden" name="submode" value="<?php  echo 'general'; ?>" />
</form>
</table>
