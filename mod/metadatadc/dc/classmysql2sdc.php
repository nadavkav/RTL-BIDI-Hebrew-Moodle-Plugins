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
  var $XMLNodeName;
  var $CDATANodeBeg;
  var $CDATANodeEnd;
  var $Operator;
  var $Value;
  var $CharField;
  

  function NodeDefinition($SQLField, $XMLNode, $bCDATA = 0, $Char = 0)
  {
     global $__debug__;
/*	 if ($SQLField = explode(";",$SQLFieldiv)) 
	 {
	 foreach($SQLFieldiv as $SQLFieldstr)
//	 	{
	     $this->SQLFieldName   = $SQLFieldstr;
	     $this->XMLNodeName    = $XMLNode;
//		}
	 }
	 else
	 { */
     $this->SQLFieldName   = $SQLField;
     $this->XMLNodeName    = $XMLNode; 

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
//  }
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
//  function XMLDefinition( $DBHost, $DBName, $Usr, $Pwd, $SQLTable, $XMLRoot, $XMLRecord = "record", $cid, $loid, $dirmeta, $Enc = "iso-8859-1")  
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
  
  function AddNode( $SQLField, $XMLNode, $bCDATA = 0, $Char = 0 )
  {
     global $__debug__;
     $this->NodeList[$this->iNumNodes] = new NodeDefinition($SQLField, $XMLNode, $bCDATA, $Char);
     $this->iNumNodes++;
  }

  function AddRestriction( $XMLNode, $Oper, $Valu )
  {
     global $__debug__;
      $CurNode = false;

      for($i=0; $i < $this->iNumNodes; $i++)
      {
         $CurNode = $this->NodeList[$i];
         if( $CurNode->XMLNodeName == $XMLNode )
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
         echo("error connecting to database reason: " . $db->getMessage());
         return;
      }

      $result = $db->query($this->SQLString);
      if(DB::isError($result)) {
         echo("error querying database reason: " . $result->getMessage());
         //err_write("failed to get credentials");
         return;
      }

      echo "<?xml version=\"1.0\" encoding=\"" . $this->Encoding . "\"?". ">".$this->crlf;
//      echo "<" . $this->XMLRootName . ">".$this->crlf;
	  echo "<" . $this->XMLRootName . " xmlns:dc=\"http://purl.org/dc/elements/1.1/\" 
		xmlns:dcterms=\"http://purl.org/dc/terms/\" 
		xmlns:dcmitype=\"http://purl.org/dc/dcmitype/\" 
		xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">".$this->crlf;  	  
      while( $row = $result->fetchrow(DB_FETCHMODE_ASSOC))
      {
         //echo "<" . $this->XMLRecordNodeName . ">".$this->crlf;
         for($i=0; $i < $this->iNumNodes; $i++)
         {

            $CurNode = $this->NodeList[$i];

		    if ($subnodes = explode( ";; ", $row[$CurNode->SQLFieldName]))
			{
			foreach ($subnodes as $row[$CurNode->SQLFieldName])
            echo "<" . $CurNode->XMLNodeName . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName . ">".$this->crlf;
			}
			else
			{
			
            echo "<" . $CurNode->XMLNodeName . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName . ">".$this->crlf;
			}
         }

         //echo "</" . $this->XMLRecordNodeName . ">".$this->crlf;

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
         echo("error connecting to database reason: " . $db->getMessage());
         return;
      }

      $result = $db->query($this->SQLString);
      if(DB::isError($result)) {
         echo("error querying database reason: " . $result->getMessage());
         //err_write("failed to get credentials");
         return;
      }

      $ret = "<?xml version=\"1.0\" encoding=\""  . $this->Encoding . "\"?". ">";
      $ret = $ret . "<" . $this->XMLRootName . ">";
      while( $row = $result->fetchrow(DB_FETCHMODE_ASSOC))
      {
         //$ret .= "<" . $this->XMLRecordNodeName . ">";
         for($i=0; $i < $this->iNumNodes; $i++)
         {

            $CurNode = $this->NodeList[$i];

		    if ($subnodes = explode( ";; ", $row[$CurNode->SQLFieldName]))
			{
			foreach ($subnodes as $row[$CurNode->SQLFieldName])
            $ret .= "<" . $CurNode->XMLNodeName . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName . ">".$this->crlf;
			}
			else
			{
			
            $ret .= "<" . $CurNode->XMLNodeName . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName . ">".$this->crlf;
			}
         }

        //$ret .= "</" . $this->XMLRecordNodeName . ">";

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
	$filename = $this->moodledata . "/" . $this->CDCid . "/metadata/sdcmetadata_" . $this->CDCid . "_" . $this->LODCid . ".xml";
	$this->handle = fopen($filename,"w");
     global $__debug__;
      if( strlen($this->SQLString) == 0 )
         $this->GenerateSQL();

      // open connection to mysql
      $db = DB::connect($this->DSN);
      if(DB::isError($db) ) {
         echo("error connecting to database reason: " . $db->getMessage());
         return;
      }

      $result = $db->query($this->SQLString);
      if(DB::isError($result)) {
         echo("error querying database reason: " . $result->getMessage());
         //err_write("failed to get credentials");
         return;
      }
		fputs($this->handle,"<?xml version=\"1.0\" encoding=\""  . $this->Encoding . "\"?". ">".$this->crlf);
		fputs($this->handle,"<" . $this->XMLRootName . " xmlns:dc=\"http://purl.org/dc/elements/1.1/\" 
		xmlns:dcterms=\"http://purl.org/dc/terms/\" 
		xmlns:dcmitype=\"http://purl.org/dc/dcmitype/\" 
		xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">".$this->crlf);
      while( $row = $result->fetchrow(DB_FETCHMODE_ASSOC))
      {
         //fputs($this->handle,"<" . $this->XMLRecordNodeName . ">".$this->crlf);
         for($i=0; $i < $this->iNumNodes; $i++)
         {

            $CurNode = $this->NodeList[$i];

		    if ($subnodes = explode( ";; ", $row[$CurNode->SQLFieldName]))
			{
			foreach ($subnodes as $row[$CurNode->SQLFieldName])
            fputs($this->handle,"<" . $CurNode->XMLNodeName . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName . ">".$this->crlf);
			}
			else
			{
			
            fputs($this->handle,"<" . $CurNode->XMLNodeName . ">" . $CurNode->CDATANodeBeg .
                  $row[$CurNode->SQLFieldName] .
                  $CurNode->CDATANodeEnd . "</" . $CurNode->XMLNodeName . ">".$this->crlf);
			}
         }

        //fputs($this->handle,"</" . $this->XMLRecordNodeName . ">".$this->crlf);

      }
      fputs($this->handle,"</" . $this->XMLRootName . ">".$this->crlf);
//	  echo '<XML ID ="dso'.$this->tabelname.'" src ="'.$filename.'"/>'.$this->crlf;
	  fclose($this->handle);
	}
}
?>
