
<?php

/*
Class updated by Vitor Gonçalves, 2006.
contact: vg@ipb.pt
Original class by Adi Sieker, 2002.  All rights reserved.
contact: adi@l33tless.org

Class to generate an XML document from a mysql table. 
You define the xml tag names to be used for each table field.
Where clauses are also supported.
Updated to PHP4 and the PEAR DB Class.
*/

require_once("../../../config.php");

// class_x.php is required, so the whole thing works.

require_once("classmysql2sdc.php");

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
//$XMLGenerator = new XMLDefinition( "$CFG->dbhost", "$CFG->dbname", "$CFG->dbuser", "$CFG->dbpass", "{$CFG->prefix}metadatadc", "metadata", "resource", "$cid", "$loid", "$CFG->dataroot", "iso-8859-1");


/*
Add Fields to select and which is added to the XML document.
The parameters are:
1. Field name in DB table.
2. Tag name in the returned XML.
*/


$XMLGenerator->AddNode( "title", "dc:title" );
$XMLGenerator->AddNode( "creator", "dc:creator" );
$XMLGenerator->AddNode( "subject", "dc:subject" );
$XMLGenerator->AddNode( "description", "dc:description" );
$XMLGenerator->AddNode( "publisher", "dc:publisher" );
$XMLGenerator->AddNode( "contributor", "dc:contributor" );
$XMLGenerator->AddNode( "date", "dc:date" );
$XMLGenerator->AddNode( "type", "dc:type" );
$XMLGenerator->AddNode( "format", "dc:format" );
$XMLGenerator->AddNode( "identifier", "dc:identifier" );
$XMLGenerator->AddNode( "source", "dc:source" );
$XMLGenerator->AddNode( "language", "dc:language" );
$XMLGenerator->AddNode( "relation", "dc:relation" );
$XMLGenerator->AddNode( "coverage", "dc:coverage" );
$XMLGenerator->AddNode( "rights", "dc:rights" );


/*
GetXML returns the XML for further processing
*/
$xml = $XMLGenerator->GetXML();

/*
EchoXML directly echos the XML out.
*/
$XMLGenerator->EchoXML();
$XMLGenerator->SaveXML();

}
//O ficheiro xml a mostrar deve ter o mesmo nome do ficheiro xml em mysql2xml
//echo "<br>";
//echo print_string("metadatagenok","metadatadc");
//echo "<hr>";

//mb_convert_encoding(show_source($CFG->dataroot . "/" . $cid . "/metadata/sdcmetadata_"  . $cid . "_" . $loid . ".xml"), "utf-8");
//echo "<hr>";


?>

