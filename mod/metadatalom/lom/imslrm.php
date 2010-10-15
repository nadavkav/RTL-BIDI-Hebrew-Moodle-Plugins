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
require_once("classmysql2imslrm.php");

$cid = optional_param('cid', 0, PARAM_INT); // Course Module ID
$loid  = optional_param('loid', 0, PARAM_INT);  // metadatadc ID	

require_login($course->id);

if (! (isteacher($course->id) or ($course->showreports and $USER->id == $user->id))) {
        echo "<br><div ='center'><hr>";
		$unauthorized = get_string("Comment_student","metadatalom");
		error('Opss! - ' . $unauthorized);
		echo "<br><hr></div>";
}
else {
	
$XMLGenerator = new XMLDefinition( "$CFG->dbhost", "$CFG->dbname", "$CFG->dbuser", "$CFG->dbpass", "{$CFG->prefix}metadatalom", "lom", "resource", "$cid", "$loid", "$CFG->dataroot", "utf-8");

/*
Add Fields to select and which is added to the XML document.
The parameters are:
1. Field name in DB table.
2. Tag name in the returned XML.
*/


$XMLGenerator->AddNode( "General_Identifier_Catalog", "<general>\r\n\t<identifier>\r\n\t\t<catalog>", "</catalog>" );
$XMLGenerator->AddNode( "General_Identifier_Entry", "\r\n\t\t<entry>", "</entry>\r\n\t</identifier>" );
$XMLGenerator->AddNode( "General_Title", "\r\n\t<title>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</title>" );
$XMLGenerator->AddNode( "General_Language", "\r\n\t<language>", "</language>" );
$XMLGenerator->AddNode( "General_Description", "\r\n\t<description>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</description>" );
$XMLGenerator->AddNode( "General_Keyword", "\r\n\t<keyword>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</keyword>" );
$XMLGenerator->AddNode( "General_Coverage", "\r\n\t<coverage>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</coverage>" );
$XMLGenerator->AddNode( "General_Structure", "\r\n\t<structure>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</structure>" );
$XMLGenerator->AddNode( "General_AggregationLevel", "\r\n\t<aggregationlevel>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</aggregationlevel>" );
$XMLGenerator->AddNode( "LifeCycle_Version", "\r\n</general>\r\n<lifecycle>\r\n\t<version>\r\n\t\t<langstring>", "</langstring>\r\n\t</version>" );
$XMLGenerator->AddNode( "LifeCycle_Status", "\r\n\t<status>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</status>" );
$XMLGenerator->AddNode( "LifeCycle_Contribute_Role", "\r\n\t<contribute>\r\n\t\t<role>\r\n\t\t\t<source>\r\n\t\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t\t</source>\r\n\t\t\t<value>\r\n\t\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t\t</value>\r\n\t\t</role>" );
$XMLGenerator->AddNode( "LifeCycle_Contribute_Entity", "\r\n\t\t<centity>\r\n\t\t\t<vcard>BEGIN:vCARD", "END:vCARD</vcard>\r\n\t\t</centity>" );
$XMLGenerator->AddNode( "LifeCycle_Contribute_Date", "\r\n\t\t<date>\r\n\t\t\t<datetime>", "</datetime>\r\n\t\t</date>\r\n\t</contribute>" );
$XMLGenerator->AddNode( "MetaMetadata_Identifier_Catalog", "\r\n</lifecycle>\r\n<metametadata>\r\n\t<identifier>\r\n\t<catalog>", "</catalog>" );
$XMLGenerator->AddNode( "MetaMetadata_Identifier_Entry", "\r\n\t\t<entry>", "</entry>\r\n\t</identifier>" );
$XMLGenerator->AddNode( "MetaMetadata_Contribute_Role", "\r\n\t<contribute>\r\n\t\t<role>\r\n\t\t\t<source>\r\n\t\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t\t</source>\r\n\t\t\t<value>\r\n\t\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t\t</value>\r\n\t\t</role>" );
$XMLGenerator->AddNode( "MetaMetadata_Contribute_Entity", "\r\n\t\t<centity>\r\n\t\t\t<vcard>BEGIN:vCARD", "END:vCARD</vcard>\r\n\t\t</centity>" );
$XMLGenerator->AddNode( "MetaMetadata_Contribute_Date", "\r\n\t\t<date>\r\n\t\t\t<datetime>", "</datetime>\r\n\t\t</date>\r\n\t</contribute>" );
$XMLGenerator->AddNode( "MetaMetadata_MetadataScheme", "\r\n\t<metametadatascheme>", "</metametadatascheme>" );
$XMLGenerator->AddNode( "MetaMetadata_Language", "\r\n\t<language>", "</language>" );
$XMLGenerator->AddNode( "Technical_Format", "\r\n</metametadata>\r\n<technical>\r\n\t<format>", "</format>" );
$XMLGenerator->AddNode( "Technical_Size", "\r\n\t<size>", "</size>" );
$XMLGenerator->AddNode( "Technical_Location", "\r\n\t<location type=\"URI\">", "</location>" );
$XMLGenerator->AddNode( "Technical_Requirement_Type", "\r\n\t<requirement>\r\n\t\t<orcomposite>\r\n\t\t\t<type>\r\n\t\t\t\t<source>\r\n\t\t\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t\t\t</source>\r\n\t\t\t\t<value>\r\n\t\t\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t\t\t</value>\r\n\t\t\t</type>" );
$XMLGenerator->AddNode( "Technical_Requirement_Name", "\r\n\t\t\t<name>\r\n\t\t\t\t<source>\r\n\t\t\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t\t\t</source>\r\n\t\t\t\t<value>\r\n\t\t\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t\t\t</value>\r\n\t\t\t</name>" );
$XMLGenerator->AddNode( "Technical_Requirement_MinimumVersion", "\r\n\t\t\t<minimumversion>", "</minimumversion>" );
$XMLGenerator->AddNode( "Technical_Requirement_MaximumVersion", "\r\n\t\t\t<maximumversion>", "</maximumversion>\r\n\t\t</orcomposite>\r\n\t</requirement>" );
$XMLGenerator->AddNode( "Technical_InstalationRemarks", "\r\n\t<installationremarks>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</installationremarks>" );
$XMLGenerator->AddNode( "Technical_OtherPlatformRequirements", "\r\n\t<otherplatformrequirements>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</otherplatformrequirements>" );
$XMLGenerator->AddNode( "Technical_Duration", "\r\n\t<duration>", "</duration>" );
$XMLGenerator->AddNode( "Educational_InteractivityType", "\r\n</technical>\r\n<educational>\r\n\t<interactivitytype>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</interactivitytype>" );
$XMLGenerator->AddNode( "Educational_LearningResourceType", "\r\n\t<learningresourcetype>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</learningresourcetype>" );
$XMLGenerator->AddNode( "Educational_InteractivityLevel", "\r\n\t<interactivitylevel>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</interactivitylevel>" );
$XMLGenerator->AddNode( "Educational_SemanticDensity", "\r\n\t<semanticdensity>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</semanticdensity>" );
$XMLGenerator->AddNode( "Educational_IntendedEndUserRole", "\r\n\t<intendedenduserrole>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</intendedenduserrole>" );
$XMLGenerator->AddNode( "Educational_Context", "\r\n\t<context>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</context>" );
$XMLGenerator->AddNode( "Educational_TypicalAgeRange", "\r\n\t<typicalagerange>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</typicalagerange>" );
$XMLGenerator->AddNode( "Educational_Difficulty", "\r\n\t<difficulty>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</difficulty>" );
$XMLGenerator->AddNode( "Educational_TypicalLearningTime", "\r\n\t<typicallearningtime>\r\n\t\t<duration>", "</duration>\r\n\t</typicallearningtime>" );
$XMLGenerator->AddNode( "Educational_Description", "\r\n\t<description>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</description>" );
$XMLGenerator->AddNode( "Educational_Language", "\r\n\t<language>", "</language>" );
$XMLGenerator->AddNode( "Rights_Cost", "</educational>\r\n<rights>\r\n\t<cost>\r\n\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</cost>" );
$XMLGenerator->AddNode( "Rights_CopyrightAndOtherRestrictions", "\r\n\t<copyrightandotherrestrictions>\r\n\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</copyrightandotherrestrictions>" );
$XMLGenerator->AddNode( "Rights_Description", "\r\n\t<description>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</description>" );
$XMLGenerator->AddNode( "Relation_Kind", "</rights>\r\n<relation>\r\n\t<kind>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</kind>" );
$XMLGenerator->AddNode( "Relation_Resource_Identifier_Catalog", "\r\n\t<resource>\r\n\t\t<identifier>\r\n\t\t\t<catalog>", "</catalog>" );
$XMLGenerator->AddNode( "Relation_Resource_Identifier_Entry", "\r\n\t\t\t<entry>", "</entry>\r\n\t\t</identifier>" );
$XMLGenerator->AddNode( "Relation_Resource_Description", "\r\n\t\t<description>\r\n\t\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t\t</description>\r\n\t</resource>" );
$XMLGenerator->AddNode( "Annotation_Entity", "\r\n</relation>\r\n<annotation>\r\n\t<entity>\r\n\t\t\t<vcard>BEGIN:vCARD", "END:vCARD</vcard>\r\n\t</entity>" );
$XMLGenerator->AddNode( "Annotation_Date", "\r\n\t<date>\r\n\t\t<datetime>", "</datetime>\r\n\t</date>" );
$XMLGenerator->AddNode( "Annotation_Description", "\r\n\t<description>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</description>" );
$XMLGenerator->AddNode( "Classification_Purpose", "\r\n</annotation>\r\n<classification>\r\n\t<purpose>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">LOMv1.0</langstring>\r\n\t\t</source>\r\n\t\t<value>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</value>\r\n\t</purpose>" );
$XMLGenerator->AddNode( "Classification_TaxonPath_Source", "\r\n\t<taxonpath>\r\n\t\t<source>\r\n\t\t\t<langstring xml:lang=\"x-none\">", "</langstring>\r\n\t\t</source>" );
$XMLGenerator->AddNode( "Classification_TaxonPath_Taxon_ID", "\r\n\t\t<taxon>\r\n\t\t\t<id>", "</id>" );
$XMLGenerator->AddNode( "Classification_TaxonPath_Taxon_Entry", "\r\n\t\t\t<entry>\r\n\t\t\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t\t\t</entry>\r\n\t\t</taxon>\r\n\t</taxonpath>" );
$XMLGenerator->AddNode( "Classification_Keyword", "\r\n\t<keyword>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</keyword>" );
$XMLGenerator->AddNode( "Classification_Description", "\r\n\t<description>\r\n\t\t<langstring xml:lang=\"pt\">", "</langstring>\r\n\t</description>\r\n</classification>" );


/*
GetXML returns the XML for further processing
*/
$xml = $XMLGenerator->GetXML();

/*
EchoXML directly echos the XML out.
*/
//$XMLGenerator->EchoXML();
$XMLGenerator->SaveXML();

//O ficheiro xml a mostrar deve ter o mesmo nome do ficheiro xml em mysql2imslrm
echo "<br>";
echo print_string("metadatagenok","metadatalom");
echo "<hr>";
show_source($CFG->dataroot . "/" . $cid . "/metadata/imslrmmetadata_"  . $cid . "_" . $loid . ".xml");
echo "<hr>";
}

?>
