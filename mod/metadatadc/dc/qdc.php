<?php

/*
Class updated by Vitor Gonçalves, 2006.
contact: vg@ipb.pt
Based on class by Adi Sieker, 2002.  All rights reserved.
contact: adi@l33tless.org

Class to generate an XML document from a mysql table. 
You define the database and table names and the xml tag names
to be used for each table field. Where clauses are also supported.
Updated to PHP4 and the PEAR DB Class.
*/

require_once("../../../config.php");

// xml.inc.php is required, so the whole thing works.
require_once("classmysql2qdc.php");

$cid = optional_param('cid', 0, PARAM_INT); // Course Module ID
$loid  = optional_param('loid', 0, PARAM_INT);  // metadatadc ID	

require_login($course->id);

if (! (isteacher($course->id) or ($course->showreports and $USER->id == $user->id))) {
        echo "<br><div ='center'><hr>";
		$unauthorized = get_string("Comment_student","metadatadc");
		error('Opss! - ' . $unauthorized);
		echo "<br><hr></div>";
}
else {

$XMLGenerator = new XMLDefinition( "$CFG->dbhost", "$CFG->dbname", "$CFG->dbuser", "$CFG->dbpass", "{$CFG->prefix}metadatadc", "metadata", "resource", "$cid", "$loid", "$CFG->dataroot", "utf-8");

/*
Add Fields to select and which is added to the XML document.
The parameters are:
1. Field name in DB table.
2. Tag name in the returned XML.
*/

//$XMLGenerator->AddNode( "FieldinTable", "1Tag2xml", "2Tag2xml");

$XMLGenerator->AddNode( "title", "dc:title", "dc:title" );
$XMLGenerator->AddNode( "alternative", "dcterms:alternative", "dcterms:alternative" );
$XMLGenerator->AddNode( "creator", "dc:creator", "dc:creator" );
$XMLGenerator->AddNode( "subject", "dc:subject xsi:type=\"dcterms:UDC\"", "dc:subject" );
$XMLGenerator->AddNode( "description", "dc:description", "dc:description" );
$XMLGenerator->AddNode( "descriptionout", "dc:description xml:lang=\"uk\"", "dc:description" ); 
$XMLGenerator->AddNode( "tableOfContents", "dcterms:tableOfContents", "dcterms:tableOfContents" );
$XMLGenerator->AddNode( "abstract", "dcterms:abstract", "dcterms:abstract" );
$XMLGenerator->AddNode( "publisher", "dc:publisher","dc:publisher" );
$XMLGenerator->AddNode( "contributor", "dc:contributor", "dc:contributor" );
$XMLGenerator->AddNode( "date", "dc:date xsi:type=\"dcterms:W3CDTF\"", "dc:date" );
$XMLGenerator->AddNode( "created", "dcterms:created xsi:type=\"dcterms:W3CDTF\"", "dcterms:created" );
$XMLGenerator->AddNode( "valid", "dcterms:valid xsi:type=\"dcterms:W3CDTF\"", "dcterms:valid" );
$XMLGenerator->AddNode( "available", "dcterms:available xsi:type=\"dcterms:W3CDTF\"", "dcterms:available" );
$XMLGenerator->AddNode( "issued", "dcterms:issued xsi:type=\"dcterms:W3CDTF\"", "dcterms:issued" );
$XMLGenerator->AddNode( "modified", "dcterms:modified xsi:type=\"dcterms:W3CDTF\"", "dcterms:modified" );
$XMLGenerator->AddNode( "dateAccepted", "dcterms:dateAccepted xsi:type=\"dcterms:W3CDTF\"", "dcterms:dateAccepted" );
$XMLGenerator->AddNode( "dateCopyrighted", "dcterms:dateCopyrighted xsi:type=\"dcterms:W3CDTF\"", "dcterms:dateCopyrighted" );
$XMLGenerator->AddNode( "dateSubmitted", "dcterms:dateSubmitted xsi:type=\"dcterms:W3CDTF\"", "dcterms:dateSubmitted" );
$XMLGenerator->AddNode( "type", "dc:type xsi:type=\"dcterms:DCMItype\"", "dc:type" );
//$XMLGenerator->AddNode( "type", "dc:type xsi:type=\"dcmitype:DCMI\"", "dc:type" ); incorrecto
$XMLGenerator->AddNode( "format", "dc:format xsi:type=\"dcterms:IMT\"", "dc:format" );
$XMLGenerator->AddNode( "extent", "dcterms:extent", "dcterms:extent" );
$XMLGenerator->AddNode( "medium", "dcterms:medium", "dcterms:medium" );
$XMLGenerator->AddNode( "identifier", "dc:identifier xsi:type=\"dcterms:URI\"", "dc:identifier" );
$XMLGenerator->AddNode( "bibliographicCitation", "dcterms:bibliographicCitation", "dcterms:bibliographicCitation" );
$XMLGenerator->AddNode( "source", "dc:source xsi:type=\"dcterms:URI\"", "dc:source" );
$XMLGenerator->AddNode( "language", "dc:language xsi:type=\"dcterms:ISO639-1\"", "dc:language" );
$XMLGenerator->AddNode( "relation", "dc:relation xsi:type=\"dcterms:URI\"", "dc:relation" );
$XMLGenerator->AddNode( "isVersionOf", "dcterms:isVersionOf xsi:type=\"dcterms:URI\"", "dcterms:isVersionOf" );
$XMLGenerator->AddNode( "hasVersion", "dcterms:hasVersion xsi:type=\"dcterms:URI\"", "dcterms:hasVersion" );
$XMLGenerator->AddNode( "isReplacedBy", "dcterms:isReplacedBy xsi:type=\"dcterms:URI\"", "dcterms:isReplacedBy" );
$XMLGenerator->AddNode( "replaces", "dcterms:replaces xsi:type=\"dcterms:URI\"", "dcterms:replaces" );
$XMLGenerator->AddNode( "isRequiredBy", "dcterms:isRequiredBy xsi:type=\"dcterms:URI\"", "dcterms:isRequiredBy" );
$XMLGenerator->AddNode( "requires", "dcterms:requires xsi:type=\"dcterms:URI\"", "dcterms:requires" );
$XMLGenerator->AddNode( "isPartOf", "dcterms:isPartOf xsi:type=\"dcterms:URI\"", "dcterms:isPartOf" );
$XMLGenerator->AddNode( "hasPart", "dcterms:hasPart xsi:type=\"dcterms:URI\"", "dcterms:hasPart" );
$XMLGenerator->AddNode( "isReferencedBy", "dcterms:isReferencedBy xsi:type=\"dcterms:URI\"", "dcterms:isReferencedBy" );
//este campo dava problemas quando se chamava references!
$XMLGenerator->AddNode( "reference", "dcterms:references xsi:type=\"dcterms:URI\"", "dcterms:references" );
$XMLGenerator->AddNode( "isFormatOf", "dcterms:isFormatOf xsi:type=\"dcterms:URI\"", "dcterms:isFormatOf" );
$XMLGenerator->AddNode( "hasFormat", "dcterms:hasFormat xsi:type=\"dcterms:URI\"", "dcterms:hasFormat" );
$XMLGenerator->AddNode( "conformsTo", "dcterms:conformsTo xsi:type=\"dcterms:URI\"", "dcterms:conformsTo" );
$XMLGenerator->AddNode( "isVersionOf", "dcterms:isVersionOf xsi:type=\"dcterms:URI\"", "dcterms:isVersionOf" );
$XMLGenerator->AddNode( "isVersionOf", "dcterms:isVersionOf xsi:type=\"dcterms:URI\"", "dcterms:isVersionOf" );
$XMLGenerator->AddNode( "coverage", "dc:coverage", "dc:coverage" );
//este campo dava problemas quando se chamava spatial!
$XMLGenerator->AddNode( "espatial", "dcterms:spatial xsi:type=\"dcterms:ISO3166\"", "dcterms:spatial" );
$XMLGenerator->AddNode( "temporal", "dcterms:temporal xsi:type=\"dcterms:W3CDTF\"", "dcterms:temporal" );
$XMLGenerator->AddNode( "rights", "dc:rights", "dc:rights" );
$XMLGenerator->AddNode( "accessRights", "dcterms:accessRights", "dcterms:accessRights" );
$XMLGenerator->AddNode( "license", "dcterms:license xsi:type=\"dcterms:URI\"", "dcterms:license" );
$XMLGenerator->AddNode( "audience", "dc:audience", "dc:audience" );
$XMLGenerator->AddNode( "mediator", "dcterms:mediator", "dcterms:mediator" );
$XMLGenerator->AddNode( "educationLevel", "dcterms:educationLevel", "dcterms:educationLevel" );
$XMLGenerator->AddNode( "provenance", "dc:provenance", "dc:provenance" );
$XMLGenerator->AddNode( "rightsHolder", "dc:rightsHolder", "dc:rightsHolder" );
$XMLGenerator->AddNode( "instructionalMethod", "dc:instrutionalMethod", "dc:instrutionalMethod" );
$XMLGenerator->AddNode( "accrualMethod", "dc:accrualMethod", "dc:accrualMethod" );
$XMLGenerator->AddNode( "accrualPeriodicity", "dc:accrualPeriodicity", "dc:accrualPeriodicity" );
$XMLGenerator->AddNode( "accrualPolicy", "dc:accrualPolicy", "dc:accrualPolicy" );




/*
GetXML returns the XML for further processing
*/
$xml = $XMLGenerator->GetXML();

/*
EchoXML directly echos the XML out.
*/
$XMLGenerator->EchoXML();
$XMLGenerator->SaveXML();
/*
//O ficheiro xml a mostrar deve ter o mesmo nome do ficheiro xml da linha 253 do mysql2xml.inc.php
echo "<br>";
echo print_string("metadatagenok","metadatadc");
echo "<hr>";
show_source($CFG->dataroot . "/" . $cid . "/metadata/qdcmetadata_"  . $cid . "_" . $loid . ".xml");
echo "<hr>";
*/
}
?>
