<?php
/*
Class by Vitor Gonçalves, 2006.
contact: vg@ipb.pt
Based on class by Adi Sieker, 2002.  All rights reserved.
contact: adi@l33tless.org
*/

require_once("../../../config.php");
require_once "DB.php";

class NodeDefinition
{
  var $SQLFieldName;
  var $XMLNodeName1;
  var $XMLNodeName2;  
  var $CDATANodeBeg;
  var $CDATANodeEnd;
  var $Operator;
  var $Value;
  var $CharField;

  function NodeDefinition($SQLField, $XMLNode1, $XMLNode2, $bCDATA = 0, $Char = 0)
  {
     global $__debug__;
     $this->SQLFieldName   = $SQLField;
     $this->XMLNodeName1    = $XMLNode1;
     $this->XMLNodeName2    = $XMLNode2;	 

     if( $bCDATA != 0 )
     {
      $this->CDATANodeBeg     = "<![CDATA[";
      $this->CDATANodeEnd     = "]]>";
     }
     else
     {
      $this->CDATANodeBeg     = "";
      $this->CDATANodeEnd     = "";
     }

     if( $Char != 0)
         $this->CharField = "'";
  }
  function AddRestriction( $Op, $Val)
  {
     global $__debug__;

     $this->Operator    = $Op;
     $this->Value       = $Val;
  }
}

class XMLDefinition
{
  var $UserName;
  var $Password;
  var $DataBaseHost;
  var $DataBaseName;
  var $SQLDataSource;
  var $XMLRootName;
  var $XMLRecordNodeName;
  var $NodeList;
  var $iNumNodes;
  var $SQLString;
  var $CDCid;  
  var $LODCid;  
  var $Encoding;
  private $crlf	= "\r\n";  

  function XMLDefinition( $DBHost, $DBName, $Usr, $Pwd, $SQLTable, $XMLRoot, $XMLRecord = "record", $cid, $loid, $dirmeta, $Enc = "utf-8")
  {
     global $__debug__;
/*
     $this->DataBaseHost      = $DBHost;
     $this->DataBaseName      = $DBName;
     $this->UserName          = $Usr;
     $this->Password          = $Pwd;
*/

     $this->DSN = "mysql://$Usr:$Pwd@$DBHost/$DBName";
     $this->SQLDataSource     = $SQLTable;
     $this->XMLRootName       = $XMLRoot;
     $this->XMLRecordNodeName = $XMLRecord;
     $this->Encoding          = $Enc;
     $this->iNumNodes = 0;
	 $this->LODCid = $loid;
	 $this->CDCid = $cid;
	 $this->moodledata = $dirmeta;		 
  }

  function AddNode( $SQLField, $XMLNode1, $XMLNode2, $bCDATA = 0, $Char = 0 )
  {
     global $__debug__;
     $this->NodeList[$this->iNumNodes] = new NodeDefinition($SQLField, $XMLNode1, $XMLNode2, $bCDATA, $Char);
     $this->iNumNodes++;
  }

  function AddRestriction( $XMLNode1, $Oper, $Valu )
  {
     global $__debug__;
      $CurNode = false;

      for($i=0; $i < $this->iNumNodes; $i++)
      {
         $CurNode = $this->NodeList[$i];
         if( $CurNode->XMLNodeName1 == $XMLNode1 )
         {
            if( $CurNode )
            {
               $CurNode->AddRestriction( $Oper, $Valu);
               $this->NodeList[$i] = $CurNode;
            }

            break;
         }
      }


  }

  function GenerateSQL()
  {
     global $__debug__;

     $this->SQLString = "";
     $SQLString = "SELECT ";
     $WhereString = " WHERE ";
      for($i=0; $i < $this->iNumNodes; $i++)
      {
         $CurNode = $this->NodeList[$i];
         $SQLString = $SQLString . $CurNode->SQLFieldName;
         if( $i < $this->iNumNodes-1 )
            $SQLString = $SQLString . ",";
         $SQLString = $SQLString . " ";

         if( strlen($CurNode->Operator) > 0 &&
               strlen($CurNode->Value) > 0 )
         {
            $WhereString = $WhereString . $CurNode->SQLFieldName .
                           " " . $CurNode->Operator . " " . $CurNode->CharField .
                           $CurNode->Value . $CurNode->CharField. " AND ";
         }
      }

	  $SQLString = $SQLString . "FROM " . $this->SQLDataSource . " WHERE id=" . $this->LODCid;

      if( substr($WhereString, strlen($WhereString)-5,5) ==" AND " )
      {
         $WhereString = substr($WhereString, 0,strlen($WhereString)-5);
         $SQLString = $SQLString . " " . $WhereString;
      }



      $this->SQLString = $SQLString;
     if( $__debug__ == 1)
      echo "<br>" . $this->SQLString;
  }

  function EchoXML()
  {
     global $__debug__;
      if( strlen($this->SQLString) == 0 )
         $this->GenerateSQL();

      // open connection to mysql
      $db = DB::connect($this->DSN);
      if(DB::isError($db) ) {
         echo("error connecting to database reason:" . $db->getMessage());
         return;
      }

      $result = $db->query($this->SQLString);
      if(DB::isError($result)) {
         echo("error querying database reason:" . $result->getMessage());
         //err_write("failed to get credentials");
         return;
      }

      echo "<?xml version=\"1.0\" encoding=\"" . $this->Encoding . "\"?". ">".$this->crlf;
//      echo "<" . $this->XMLRootName . ">".$this->crlf;

	  echo "<!DOCTYPE " . $this->XMLRootName . " PUBLIC \"-//DUBLIN CORE//DCMES DTD 2002/07/31//EN\" 
			\"http://dublincore.org/documents/2002/07/31/dcmes-xml/dcmes-xml-dtd.dtd\">".$this->crlf;
	  echo "<" . $this->XMLRootName . " xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" 
		  xmlns:dc=\"http://purl.org/dc/elements/1.1/\" 
		  xmlns:dcterms=\"http://purl.org/dc/terms/\">".$this->crlf;  	  
      while( $row = $result->fetchrow(DB_FETCHMODE_ASSOC))
      {
//         Verifica o tipo de Identifier

         $CurNode = $this->NodeList[23];
		 $findme   = 'http://';
		 $pos = strpos($row[$CurNode->SQLFieldName], $findme);

		 if ($pos === false)
		 {
		echo "<" . $this->XMLRecordNodeName . ">".$this->crlf;		 
		 }
		else
		 {
         echo "<" . $this->XMLRecordNodeName . " rdf:about=\"" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd .  "\">".$this->crlf;
		 }
         for($i=0; $i < $this->iNumNodes; $i++)
         {
		 
		 	$CurNode = $this->NodeList[$i];


		    if ($subnodes = explode( ";; ", $row[$CurNode->SQLFieldName]))
			{
			echo  "<" . $CurNode->XMLNodeName1 . ">".$this->crlf . "<bag>".$this->crlf;
//			echo  "<bag>".$this->crlf;

			foreach ($subnodes as $row[$CurNode->SQLFieldName])
			{
            echo  "<rdf:li>" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</rdf:li>".$this->crlf;
			}
//			echo  "</bag>".$this->crlf;
			echo  "</bag>".$this->crlf . "</" . $CurNode->XMLNodeName2 . ">".$this->crlf;			
			} 
			else
			{
			
            echo  "<" . $CurNode->XMLNodeName1 . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName2 . ">".$this->crlf;
			}
         }
		 

         echo "</" . $this->XMLRecordNodeName . ">".$this->crlf;

      }
      echo "</" . $this->XMLRootName . ">".$this->crlf;
//  	  echo '<XML ID ="dso'.$this->tabelname.'" src ="'.$this->fileName.'"/>'.$this->crlf;
  }

  function GetXML()
  {
     global $__debug__;
      if( strlen($this->SQLString) == 0 )
         $this->GenerateSQL();

      // open connection to mysql
      $db = DB::connect($this->DSN);
      if(DB::isError($db) ) {
         echo("error connecting to database reason:" . $db->getMessage());
         return;
      }

      $result = $db->query($this->SQLString);
      if(DB::isError($result)) {
         echo("error querying database reason:" . $result->getMessage());
         //err_write("failed to get credentials");
         return;
      }

      $ret = "<?xml version=\"1.0\" encoding=\""  . $this->Encoding . "\"?". ">";
      $ret = $ret . "<!DOCTYPE " . $this->XMLRootName . " PUBLIC \"-//DUBLIN CORE//DCMES DTD 2002/07/31//EN\" 
			\"http://dublincore.org/documents/2002/07/31/dcmes-xml/dcmes-xml-dtd.dtd\">";
	  $ret = $ret . "<" . $this->XMLRootName . " xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" 
		  xmlns:dc=\"http://purl.org/dc/elements/1.1/\" 
		  xmlns:dcterms=\"http://purl.org/dc/terms/\">";
      while( $row = $result->fetchrow(DB_FETCHMODE_ASSOC))
      {

//         Verifica o tipo de Identifier

         $CurNode = $this->NodeList[23];
		 $findme   = 'http://';
		 $pos = strpos($row[$CurNode->SQLFieldName], $findme);

		 if ($pos === false)
		 {
			$ret .= "<" . $this->XMLRecordNodeName . ">".$this->crlf;		 
		 }
		else
		 {
         $ret .= "<" . $this->XMLRecordNodeName . " rdf:about=\"" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd .  "\">".$this->crlf;
		 }


         for($i=0; $i < $this->iNumNodes; $i++)
         {
		 
            $CurNode = $this->NodeList[$i];

		    if ($subnodes = explode( ";; ", $row[$CurNode->SQLFieldName]))
			{
			$ret .= "<" . $CurNode->XMLNodeName1 . ">".$this->crlf . "<bag>".$this->crlf;
//			$ret = "<bag>".$this->crlf;
			foreach ($subnodes as $row[$CurNode->SQLFieldName])
			{
			
            $ret .= "<rdf:li>" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</rdf:li>".$this->crlf;
				  
			}
//			$ret = "</bag>".$this->crlf;
			$ret .= "</bag>".$this->crlf . "</" . $CurNode->XMLNodeName2 . ">".$this->crlf;			
			} 
			else
			{
			
            $ret .= "<" . $CurNode->XMLNodeName1 . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName2 . ">";
			}
         }
		 
		 
        $ret .= "</" . $this->XMLRecordNodeName . ">";

      }
      $ret .= "</" . $this->XMLRootName . ">";
	  
      return $ret;
  }
//}

  function SaveXML()
	{
	$dirmetadata = $this->moodledata . "/" . $this->CDCid;
	if (!is_dir($dirmetadata)) {
	mkdir($dirmetadata);	
	opendir($dirmetadata);
	mkdir($dirmetadata . "/metadata");
	//echo "Directoria " . $dirmetadata . "/metadata/ criada com sucesso.\n";
	//echo "Directory " . $dirmetadata . "/metadata/ created with success.\n";	
	} elseif (!is_dir($dirmetadata . "/metadata/")) {
	opendir($dirmetadata);	
	mkdir($dirmetadata . "/metadata");
	//echo "Directoria " . $dirmetadata . "/metadata/ criada com sucesso.\n";	
	//echo "Directory " . $dirmetadata . "/metadata/ created with success.\n";	
	}
	$filename = $this->moodledata . "/" . $this->CDCid . "/metadata/qdcrdfmetadata_" . $this->CDCid . "_" . $this->LODCid . ".rdf";		
	$this->handle = fopen($filename,"w");
     global $__debug__;
      if( strlen($this->SQLString) == 0 )
         $this->GenerateSQL();

      // open connection to mysql
      $db = DB::connect($this->DSN);
      if(DB::isError($db) ) {
         echo("error connecting to database reason:" . $db->getMessage());
         return;
      }

      $result = $db->query($this->SQLString);
      if(DB::isError($result)) {
         echo("error querying database reason:" . $result->getMessage());
         //err_write("failed to get credentials");
         return;
      }
		fputs($this->handle,"<?xml version=\"1.0\" encoding=\""  . $this->Encoding . "\"?". ">".$this->crlf);
		fputs($this->handle,"<!DOCTYPE " . $this->XMLRootName . " PUBLIC \"-//DUBLIN CORE//DCMES DTD 2002/07/31//EN\" 
			\"http://dublincore.org/documents/2002/07/31/dcmes-xml/dcmes-xml-dtd.dtd\">".$this->crlf);
		fputs($this->handle,"<" . $this->XMLRootName . " xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" 
		  xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\" 
		  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance#\" 
		  xmlns:dc=\"http://purl.org/dc/elements/1.1/\" 
		  xmlns:dcterms=\"http://purl.org/dc/terms/\" 
		  xmlns:dctype=\"http://purl.org/dc/dcmitype/\">".$this->crlf);			
      while( $row = $result->fetchrow(DB_FETCHMODE_ASSOC))
      {
	  
	  //ATENÇÃO: mudanças na tabela implicam verificar se o num no array Nodelist se mantem
	  
//         Verifica o tipo de Identifier

         $CurNode = $this->NodeList[23];
		 $findme   = 'http://';
		 $pos = strpos($row[$CurNode->SQLFieldName], $findme);

		 if ($pos === false)
		 {
 			fputs($this->handle,"<" . $this->XMLRecordNodeName . ">".$this->crlf);
		 }
		else
		{
		 fputs($this->handle,"<" . $this->XMLRecordNodeName . " rdf:about=\"" . $CurNode->CDATANodeBeg .
         $row[$CurNode->SQLFieldName] .
         $CurNode->CDATANodeEnd .  "\">".$this->crlf);
		}

		 
         for($i=0; $i < $this->iNumNodes; $i++)
         {

            $CurNode = $this->NodeList[$i];
			 $findme1   = '; ';
			 $pos1 = strpos($row[$CurNode->SQLFieldName], $findme1);
			 $findme2   = 'http://';			 
			 $pos2 = strpos($row[$CurNode->SQLFieldName], $findme2);			 

// Verifica se existem campos iniciados por ;+(espaço)				
			 if ($pos1 == true)
				{

			    $subnodes = explode( ";; ", $row[$CurNode->SQLFieldName]);
				
				fputs($this->handle, $CurNode->XMLNodeName1 . $this->crlf . "<rdf:Bag>".$this->crlf);
				
				foreach ($subnodes as $row[$CurNode->SQLFieldName])
					{
			
	            	fputs($this->handle,"<rdf:li>" . $CurNode->CDATANodeBeg .
                  		$row[$CurNode->SQLFieldName] .
                  		$CurNode->CDATANodeEnd . "</rdf:li>" .$this->crlf);			
					}
				fputs($this->handle,"</rdf:Bag>" .$this->crlf . $CurNode->XMLNodeName2 . $this->crlf);

				}

/* Verifica se existem campos iniciados por http://	Não funciona!			
				elseif ($pos2 == true)
				{
	            fputs($this->handle,"<" . $CurNode->XMLNodeName1 . " rdf:resource=\"" 
				. $CurNode->CDATANodeBeg . $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "\"/>".$this->crlf);			
				}				
*/							
			    else
				{
				
	            fputs($this->handle, $CurNode->XMLNodeName1 . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . $CurNode->XMLNodeName2 . $this->crlf);

				}
			
         }

							  
        fputs($this->handle,"</" . $this->XMLRecordNodeName . ">".$this->crlf);

      }
	  
// Descrever que abstract e tableOfContents são subclasses de DC descrição 		 
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/abstract\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/description\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/tableOfContents\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/description\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/alternative\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/title\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/created\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/valid\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/available\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/issued\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/modified\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);						
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/dateAccepted\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/dateCopyrighted\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/dateSubmitted\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/extent\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/format\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/medium\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/date\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/bibliographicCitation\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/identifier\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/isVersionOf\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/hasVersion\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/isReplacedBy\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/Replaces\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/isRequiredBy\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);	
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/Requires\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/isPartOf\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/hasPart\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/isReferencedBy\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/References\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/isFormatOf\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/hasFormat\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/conformsTo\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/relation\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/spacial\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/coverage\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/temporal\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/coverage\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/accessRights\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/rights\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);			
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/licence\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/rights\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/mediator\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/audience\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);		
		fputs($this->handle,"<rdf:Description rdf:about=\"http://purl.org/dc/terms/educationLevel\">".$this->crlf);
		fputs($this->handle,"<rdfs:subPropertyOf rdf:resource=\"http://purl.org/dc/elements/1.1/audience\"/>".$this->crlf);
		fputs($this->handle,"</rdf:Description>".$this->crlf);			
						

      fputs($this->handle,"</" . $this->XMLRootName . ">".$this->crlf);
	  //echo '<XML ID ="dso'.$this->tabelname.'" src ="'.$filename.'"/>'.$this->crlf;
	  fclose($this->handle);
	}
}
?>
