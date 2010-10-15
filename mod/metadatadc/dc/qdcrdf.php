<?php

/*
Class updated by Vitor Gonçalves, 2006.
contact: vg@ipb.pt
Original class by Adi Sieker, 2002.  All rights reserved.
contact: adi@l33tless.org

Class to generate an XML document from a mysql table. 
You define the database and table names and the xml tag names
to be used for each table field. Where clauses are also supported.
Updated to PHP4 and the PEAR DB Class.
*/

require_once("../../../config.php");

// xml.inc.php is required, so the whole thing works.
require_once("classmysql2qdcrdf.php");

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

$XMLGenerator = new XMLDefinition( "$CFG->dbhost", "$CFG->dbname", "$CFG->dbuser", "$CFG->dbpass", "{$CFG->prefix}metadatadc", "rdf:RDF", "rdf:Description", "$cid", "$loid", "$CFG->dataroot", "utf-8");

/*
Add Fields to select and which is added to the XML document.
The parameters are:
1. Field name in DB table.
2. Tag name in the returned XML.
*/

//$XMLGenerator->AddNode( "title", "<dc:title>", "</dc:title>" );
$XMLGenerator->AddNode( "title", "<rdfs:Class rdfs:label=\"", "\">" );
$XMLGenerator->AddNode( "creator", "<dc:creator>", "</dc:creator>" );
$XMLGenerator->AddNode( "alternative", "\r\n</rdfs:Class><dcterms:alternative>", "</dcterms:alternative>" );
//$XMLGenerator->AddNode( "creator", "<dc:creator>", "</dc:creator>" );
$XMLGenerator->AddNode( "subject", "<dc:subject>\r\n\t<dcterms:UDC>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t\t<rdfs:label>UDC</rdfs:label>\r\n\t</dcterms:UDC>\r\n</dc:subject>" );
$XMLGenerator->AddNode( "subject", "<dc:subject>\r\n\t<dcterms:UDC>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t\t<rdfs:label>UDC</rdfs:label>\r\n\t\t<rdfs:comment  xml:lang=\"en-US\">Universal Decimal Classification</rdfs:comment>\r\n\t\t<rdfs:isDefinedBy rdf:resource=\"http://dublincore.org/2005/06/13/dcq#UDC\"/>\r\n\t</dcterms:UDC>\r\n</dc:subject>" );
$XMLGenerator->AddNode( "description", "<dc:description>", "</dc:description>" );
$XMLGenerator->AddNode( "descriptionout", "<dc:description xml:lang=\"uk\">", "</dc:description>" ); 
$XMLGenerator->AddNode( "tableOfContents", "<dcterms:tableOfContents>", "</dcterms:tableOfContents>" );
$XMLGenerator->AddNode( "abstract", "<dcterms:abstract>", "</dcterms:abstract>" );
$XMLGenerator->AddNode( "publisher", "<dc:publisher>","</dc:publisher>" );
$XMLGenerator->AddNode( "contributor", "<dc:contributor>", "</dc:contributor>" );
$XMLGenerator->AddNode( "date", "<dc:date>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:date>" );
$XMLGenerator->AddNode( "created", "<dc:created>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:created>" );
$XMLGenerator->AddNode( "valid", "<dc:valid>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:valid>" );
$XMLGenerator->AddNode( "available", "<dc:available>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:available>" );
$XMLGenerator->AddNode( "issued", "<dc:issued>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:issued>" );
$XMLGenerator->AddNode( "modified", "<dc:modified>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:modified>" );
$XMLGenerator->AddNode( "dateAccepted", "<dc:dateAccepted>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:dateAccepted>" );
$XMLGenerator->AddNode( "dateCopyrighted", "<dc:dateCopyrighted>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:dateCopyrighted>" );
$XMLGenerator->AddNode( "dateSubmitted", "<dc:dateSubmitted>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t</dcterms:W3CDTF>\r\n</dc:dateSubmitted>" );
//$XMLGenerator->AddNode( "type", "<dc:type xsi:type=\"dcterms:DCMItype\">", "</dc:type>" );
$XMLGenerator->AddNode( "type", "<dctype:", ">" );
$XMLGenerator->AddNode( "creator", "\r\n\t<dc:creator>", "</dc:creator>" );
$XMLGenerator->AddNode( "type", "</dctype:", ">" );
//$XMLGenerator->AddNode( "type", "<dc:type xsi:type=\"dcmitype:DCMI\">", "</dc:type>" ); incorrecto
$XMLGenerator->AddNode( "format", "<dc:format>\r\n\t<dcterms:IMT>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t\t<rdfs:label>Internet Media Type</rdfs:label>\r\n\t</dcterms:IMT>\r\n</dc:format>" );
$XMLGenerator->AddNode( "extent", "<dcterms:extent>", "</dcterms:extent>" );
$XMLGenerator->AddNode( "medium", "<dcterms:medium>", "</dcterms:medium>" );
$XMLGenerator->AddNode( "identifier", "<dc:identifier rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "bibliographicCitation", "<dcterms:bibliographicCitation>", "</dcterms:bibliographicCitation>" );
$XMLGenerator->AddNode( "source", "<dc:source rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "language", "<dc:language>\r\n\t<dcterms:ISO639-1>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t\t<rdfs:label>Portuguese</rdfs:label>\r\n\t</dcterms:ISO639-1>\r\n</dc:language>" );
$XMLGenerator->AddNode( "relation", "<dc:relation rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "isVersionOf", "<dcterms:isVersionOf rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "hasVersion", "<dcterms:hasVersion rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "isReplacedBy", "<dcterms:isReplacedBy rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "replaces", "<dcterms:replaces rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "isRequiredBy", "<dcterms:isRequiredBy rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "requires", "<dcterms:requires rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "isPartOf", "<dcterms:isPartOf rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "hasPart", "<dcterms:hasPart rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "isReferencedBy", "<dcterms:isReferencedBy rdf:resource=\"", "\"/>" );
//este campo dava problemas quando se chamava references!
$XMLGenerator->AddNode( "reference", "<dcterms:references rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "isFormatOf", "<dcterms:isFormatOf rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "hasFormat", "<dcterms:hasFormat rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "conformsTo", "<dcterms:conformsTo rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "coverage", "<dc:coverage>\r\n\t<dcterms:ISO3166>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t\t<rdfs:label>Portugal</rdfs:label>\r\n\t</dcterms:ISO3166>\r\n</dc:coverage>" );
//este campo dava problemas quando se chamava spatial!
$XMLGenerator->AddNode( "espatial", "<dc:spatial>\r\n\t<dcterms:ISO3166>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t\t<rdfs:label>Portugal</rdfs:label>\r\n\t</dcterms:ISO3166>\r\n</dc:spatial>" );
$XMLGenerator->AddNode( "temporal", "<dc:temporal>\r\n\t<dcterms:W3CDTF>\r\n\t\t<rdf:value>", "</rdf:value>\r\n\t\t<rdfs:label>Portugal</rdfs:label>\r\n\t</dcterms:W3CDTF>\r\n</dc:temporal>" );
$XMLGenerator->AddNode( "rights", "<dc:rights>", "</dc:rights>" );
$XMLGenerator->AddNode( "accessRights", "<dcterms:accessRights>", "</dcterms:accessRights>" );
$XMLGenerator->AddNode( "license", "<dcterms:license rdf:resource=\"", "\"/>" );
$XMLGenerator->AddNode( "audience", "<dc:audience>", "</dc:audience>" );
$XMLGenerator->AddNode( "mediator", "<dcterms:mediator>", "</dcterms:mediator>" );
$XMLGenerator->AddNode( "educationLevel", "<dcterms:educationLevel>", "</dcterms:educationLevel>" );
$XMLGenerator->AddNode( "provenance", "<dc:provenance>", "</dc:provenance>" );
$XMLGenerator->AddNode( "rightsHolder", "<dc:rightsHolder>", "</dc:rightsHolder>" );
$XMLGenerator->AddNode( "instructionalMethod", "<dc:instrutionalMethod>", "</dc:instrutionalMethod>" );
$XMLGenerator->AddNode( "accrualMethod", "<dc:accrualMethod>", "</dc:accrualMethod>" );
$XMLGenerator->AddNode( "accrualPeriodicity", "<dc:accrualPeriodicity>", "</dc:accrualPeriodicity>" );
$XMLGenerator->AddNode( "accrualPolicy", "<dc:accrualPolicy>", "</dc:accrualPolicy>" );



/*
GetXML returns the XML for further processing
*/
$xml = $XMLGenerator->GetXML();

/*
EchoXML directly echos the XML out.
*/
//$XMLGenerator->EchoXML();
$XMLGenerator->SaveXML();

//O ficheiro xml a mostrar deve ter o mesmo nome do ficheiro xml em mysql2xml
echo "<br>";
echo print_string("metadatagenok","metadatadc");
echo "<hr>";
show_source($CFG->dataroot . "/" . $cid . "/metadata/qdcrdfmetadata_"  . $cid . "_" . $loid . ".rdf");
echo "<hr>";

}
?>
