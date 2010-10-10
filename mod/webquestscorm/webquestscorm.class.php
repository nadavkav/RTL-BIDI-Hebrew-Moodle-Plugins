<?php 
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: webquestscorm.class.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

require_once("locallib.php");
require_once("../../lib/pclzip/pclzip.lib.php");

class webquestscorm {

    var $cm;
    var $course;
    var $webquestscorm;
    var $strwebquestscorm;
    var $strwebquestscorms;
    var $strsubmissions;
    var $strlastmodified;
    var $navigation;
    var $pagetitle;
    var $currentgroup;
    var $usehtmleditor;
    var $defaultformat;
    var $context;
    var $path;
    var $cmid;

    /**
     * Constructor for the webquestscorm class
     *
     * Constructor for the base assignment class.
     * If cmid is set create the cm, course, assignment objects.
     * If the assignment is hidden and the user is not a teacher then
     * this prints a page header and notice.
     *
     * @param cmid   integer, the current course module id - not set for new assignments
     * @param assignment   object, usually null, but if we have it we pass it to save db access
     * @param cm   object, usually null, but if we have it we pass it to save db access
     * @param course   object, usually null, but if we have it we pass it to save db access
     */
    function webquestscorm($cmid=0, $webquestscorm=NULL, $cm=NULL, $course=NULL) {
         global $CFG;
         
        if ($cmid) {

            if ($cm) {
                $this->cm = $cm;
            } else if (! $this->cm = get_coursemodule_from_id('webquestscorm', $cmid)) {
                error('Course Module ID was incorrect');
            }

            $this->context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
            

	    if ($course) {
                $this->course = $course;
            } else if (! $this->course = get_record('course', 'id', $this->cm->course)) {
                error('Course is misconfigured');
            }

            if ($webquestscorm) {
                $this->webquestscorm = $webquestscorm;
            } else if (! $this->webquestscorm = get_record('webquestscorm', 'id', $this->cm->instance)) {
                error('webquestscorm ID was incorrect');
            }
            
            $this->strwebquestscorm = get_string('modulename', 'webquestscorm');
            $this->strwebquestscorms = get_string('modulenameplural', 'webquestscorm');
            $this->strsubmissions = get_string('submissions', 'webquestscorm');
            $this->strlastmodified = get_string('lastmodified');
            if ($this->course->category) {
                $this->navigation = "<a target=\"{$CFG->framename}\" href=\"$CFG->wwwroot/course/view.php?id={$this->course->id}\">{$this->course->shortname}</a> -> ".
                                    "<a target=\"{$CFG->framename}\" href=\"index.php?id={$this->course->id}\">$this->strwebquestscorms</a> ->";
            } else {
                $this->navigation = "<a target=\"{$CFG->framename}\" href=\"index.php?id={$this->course->id}\">$this->strwebquestscorms</a> ->";
            }

            $this->pagetitle = strip_tags($this->course->shortname.': '.$this->strwebquestscorm.': '.format_string($this->webquestscorm->name,true));

            // visibility
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
            if (!$this->cm->visible and !has_capability('moodle/course:viewhiddenactivities', $this->context)) {
                $this->pagetitle = strip_tags($this->course->shortname.': '.$this->strwebquestscorm);
                print_header($this->pagetitle, $this->course->fullname, "$this->navigation $this->strwebquestscorm", 
                             "", "", true, '', navmenu($this->course, $this->cm));
                notice(get_string("activityiscurrentlyhidden"), "$CFG->wwwroot/course/view.php?id={$this->course->id}");
            }
            $this->currentgroup = get_current_group($this->course->id);
            $this->path = $CFG->dataroot.'/'.$this->cm->course.'/moddata/webquestscorm'.'/'.$this->cm->id;   
        }
        if ($this->usehtmleditor = can_use_html_editor()) {
            $this->defaultformat = FORMAT_HTML;
        } else {
            $this->defaultformat = FORMAT_MOODLE;
        }    						        
    }
    
    function exists_manifest(){
				$manifest=$this->path.'/imsmanifest.xml';
				return file_exists($manifest);
    }    
     // CREATE IMSMANIFEST.XML
    function create_manifest($cmid){
		global $CFG;
		$path=$CFG->dataroot;
    $dir = opendir($path);
    $exists=false;
    $file=readdir($dir);
    do {
        if ($file==$this->cm->course){
            $exists=true;
        }
    } while ($file=readdir($dir));
    if ($exists==false){
        mkdir($path.'/'.$this->cm->course);
        mkdir($path.'/'.$this->cm->course.'/moddata');
        mkdir($path.'/'.$this->cm->course.'/moddata/webquestscorm');
    } else {
        closedir($dir);  
        $path=$CFG->dataroot.'/'.$this->cm->course.'/moddata';
        $dir = opendir($path);
        $exists=false;
        $file=readdir($dir);
        do {
            if ($file=='webquestscorm'){
                $exists=true;
            }
        } while ($file=readdir($dir));
        if ($exists==false){
             mkdir($path.'/webquestscorm');
        }         
		}      
		closedir($dir);  
		
    $path=$CFG->dataroot.'/'.$this->cm->course.'/moddata/webquestscorm';
    $dir = opendir($path);
    $exists=false;
    $file=readdir($dir);
    do {
        if ($file==$cmid){
            $exists=true;
        }
    } while ($file=readdir($dir));
    closedir($dir);
    if ($exists==false){
        mkdir($path.'/'.$this->cm->id);

        $file=fopen($path.'/'.$this->cm->id.'/imsmanifest.xml',"w");
        fwrite($file,'<?xml version="1.0" encoding="UTF-8"?'.'>');
        fwrite($file,'<manifest xmlns="http://www.imsglobal.org/xsd/imscp_v1p1" xmlns:imsmd="http://ltsc.ieee.org/xsd/LOM" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3" xmlns:imsss="http://www.imsglobal.org/xsd/imsss" xmlns:adlseq="http://www.adlnet.org/xsd/adlseq_v1p3" xmlns:adlnav="http://www.adlnet.org/xsd/adlnav_v1p3" identifier="MANIFEST-4C1BD335CFC07E3929FCC907B7866EC7" xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://ltsc.ieee.org/xsd/LOM lom.xsd http://www.adlnet.org/xsd/adlcp_v1p3 adlcp_v1p3.xsd http://www.imsglobal.org/xsd/imsss imsss_v1p0.xsd http://www.adlnet.org/xsd/adlseq_v1p3 adlseq_v1p3.xsd http://www.adlnet.org/xsd/adlnav_v1p3 adlnav_v1p3.xsd">');
        
				fwrite($file,'<metadata>');
				fwrite($file,'<schema>ADL SCORM</schema>');
				fwrite($file,'<schemaversion>2004 3rd Edition</schemaversion>');
        fwrite($file,'<imsmd:lom>');      
        fwrite($file,'</imsmd:lom>');
        fwrite($file,'</metadata>');
        fwrite($file,'<organizations  default="ORG1">');
        fwrite($file,'<organization identifier="ORG1"  structure="hierarchical">');
        fwrite($file,'<title>Webquest</title>');
        fwrite($file,'<item identifier="ITEM1" identifierref="RES1" isvisible="true"><title>Introduction</title></item>');
        fwrite($file,'<item identifier="ITEM2" identifierref="RES2" isvisible="true"><title>Task</title></item>');
        fwrite($file,'<item identifier="ITEM3" identifierref="RES3" isvisible="true"><title>Process</title></item>');
        fwrite($file,'<item identifier="ITEM4" identifierref="RES4" isvisible="true"><title>Evaluation</title></item>');
        fwrite($file,'<item identifier="ITEM5" identifierref="RES5" isvisible="true"><title>Conclusion</title></item>');
	fwrite($file,'<item identifier="ITEM6" identifierref="RES6" isvisible="true"><title>CreditsReferences</title></item>');
        fwrite($file,'</organization>');        
        fwrite($file,'</organizations>');     
        fwrite($file,'<resources>');
        fwrite($file,'<resource identifier="RES1" adlcp:scormType="asset" type="webcontent" href="introduction.html">');
        fwrite($file,'<file href="introduction.html" />');
        fwrite($file,'</resource>');
        fwrite($file,'<resource identifier="RES2" adlcp:scormType="asset" type="webcontent" href="task.html">');
        fwrite($file,'<file href="task.html" />');
        fwrite($file,'</resource>');
        fwrite($file,'<resource identifier="RES3" adlcp:scormType="asset" type="webcontent" href="process.html">');
        fwrite($file,'<file href="process.html" />');
        fwrite($file,'</resource>');
        fwrite($file,'<resource identifier="RES4" adlcp:scormType="asset" type="webcontent" href="evaluation.html">');
        fwrite($file,'<file href="evaluation.html" />');
        fwrite($file,'</resource>');
        fwrite($file,'<resource identifier="RES5" adlcp:scormType="asset" type="webcontent" href="conclusion.html">');
        fwrite($file,'<file href="conclusion.html" />');
        fwrite($file,'</resource>');			
        fwrite($file,'<resource identifier="RES6" adlcp:scormType="asset" type="webcontent" href="credits.html">');
       fwrite($file,'<file href="credits.html" />');
        fwrite($file,'</resource>');												        
        fwrite($file,'</resources>');
        fwrite($file,'</manifest>'); 
				fclose($file);       
    }  
		      
    }
    
	
    function edit_data($element=NULL) {
        if (!isset($element)) {
            $element = 'introduction';
        }
        webquestscorm_print_header($this->webquestscorm->name, $element, $this->course,  $this->cm);
        
				$data = $this->webquestscorm->$element;
				require_once("introdata.php");
				print_footer(); 
    }
    function edit_template() {
        webquestscorm_print_header($this->webquestscorm->name, 'template', $this->course,  $this->cm);
				require_once("template.php");
				print_footer(); 
    } 
    		   
    function preview($element) {
        if ($element=='') {
            $element = 'introduction';
        }
        webquestscorm_print_header($this->webquestscorm->name, 'preview', $this->course,  $this->cm);
				require_once("./templates/page.php");
				print_footer(); 
    }       
    function edit_export() {
        webquestscorm_print_header($this->webquestscorm->name, 'export', $this->course,  $this->cm);
				require_once("export.php");
				print_footer(); 
    }
		 		
    function export(){
	    global $CFG;
            $dir = opendir($this->path);
            $exists=false;
            $file=readdir($dir);
	   
            do {
                 if ($file=='pif'.$this->cm->id.'.zip'){
                     $exists=true;
                 }
            } while (($exists==false)&&($file=readdir($dir)));
            closedir($dir);				
            if ($exists==true) {
  		unlink($this->path.'/'.'pif'.$this->cm->id.'.zip');
	     }
						
	     // create introduction.html, task.html, ...
				    
	     $this->create_webquestscorm('introduction');
	     $this->create_webquestscorm('task');
	     $this->create_webquestscorm('process');
	     $this->create_webquestscorm('evaluation');
	     $this->create_webquestscorm('conclusion');
	     $this->create_webquestscorm('credits');

				    
	     // create pif.zip
	   
	     $pif = new PclZip('pif'.$this->cm->id.'.zip');
 	   

	    $source= $CFG->dirroot.'/mod/webquestscorm/xsd/';
	    $dir = opendir($source);

	    $route =$this->path.'/pif'.$this->cm->id.'.zip';
	    while($fxsd=readdir($dir)) {
           
	     if ($fxsd!= '.' && $fxsd != '..') {       
	       if(is_dir($source.$fxsd)){
			mkdir($this->path.'/'.$fxsd);
			$dir2=opendir($source.$fxsd);
			while($fxsd2=readdir($dir2)){
				if ($fxsd2!= '.' && $fxsd2!= '..') {
					copy($source.$fxsd.'/'.$fxsd2, $this->path.'/'.$fxsd.'/'.$fxsd2);
				}
			}
		}else{
                    	copy($source.$fxsd, $this->path.'/'.$fxsd);
		}
              }       
     
	    }
	    

	    $dir = opendir($this->path);
            $i = 0;
            while ($fxsd=readdir($dir)) {
		
                if ($fxsd!= '.' && $fxsd != '..') {
                    $files[$i++] = $this->path.'/'.$fxsd;
                }       
		
             }

            closedir($dir);


            if (!zip_files($files,"$route")) {
                    print_error("zipfileserror","error");
            }

	    $dir = opendir($this->path);
            while ($fxsd=readdir($dir)) {
		
             if ($fxsd!= '.' && $fxsd != '..') {
		    if((strstr($fxsd,'.xsd')!=false)||(strstr($fxsd,'.dtd')!=false)){
  		        unlink($this->path.'/'.$fxsd);
		    }else{
	      	   if(is_dir($this->path.'/'.$fxsd)){
	    			$dir2 = opendir($this->path.'/'.$fxsd);
           			 while ($fxsd2=readdir($dir2)) {
                			if ($fxsd2!= '.' && $fxsd2!= '..') {
					    unlink($this->path.'/'.$fxsd.'/'.$fxsd2);
				 	}
 				 }
				 closedir($dir2);
				 rmdir($this->path.'/'.$fxsd);
		         }
                 }
              }       
		
             }

            closedir($dir);
} 


function create_webquestscorm($element){
    $file=fopen($this->path.'/'.$element.'.html',"w");
    fwrite($file,'<html><head><title>'.$this->webquestscorm->name.'</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">');
    fwrite($file,'<meta name="description" content='.$this->webquestscorm->name.'><meta name="keywords" content="WebQuest, lesson, constructivist, lesson plan, template1-020308">');
    fwrite($file, '<h1>'.$this->webquestscorm->name.'<br/></h1>');
    $pathCSS='templates/css'.'/';  
    $var = file($pathCSS.$this->webquestscorm->template);
    $i=0;
    while (!empty($var[$i])){
        fwrite($file, $var[$i]);
        $i++;
    }
    fwrite($file, '<div id="wrapper"><div id="navcontainer"><ul id="navlist">');   
		fwrite($file, '<li '); 
    if ($element=='introduction') {
	          fwrite($file, 'id="current"');
	  }    
	  fwrite($file, '><a href="introduction.html" title="Introduction to the webquestscorm.">'.get_string('introduction','webquestscorm').'</a></li>');  
		fwrite($file, '<li '); 
    if ($element=='task') {
	          fwrite($file, 'id="current"');
	  }    
	  fwrite($file, '><a href="task.html" title="Description of the major task of the webquestscorm.">'.get_string('task','webquestscorm').'</a></li>');  
		fwrite($file, '<li '); 
    if ($element=='process') {
	          fwrite($file, 'id="current"');
	  }    
	  fwrite($file, '><a href="process.html" title="How you are going to go about completing the webquestscorm.">'.get_string('process','webquestscorm').'</a></li>');  
		fwrite($file, '<li '); 
    if ($element=='evaluation') {
	          fwrite($file, 'id="current"');
	  }    
	  fwrite($file, '><a href="evaluation.html" title="How your teacher will evaluate your progress and performance.">'.get_string('evaluation','webquestscorm').'</a></li>');  
		fwrite($file, '<li '); 
    if ($element=='conclusion') {
	          fwrite($file, 'id="current"');
	  }    
	  fwrite($file, '><a href="conclusion.html" title="Final remarks about the webquestscorm.">'.get_string('conclusion','webquestscorm').'</a></li>');  
    	  	fwrite($file, '<li ');
    if ($element=='credits') {
	          fwrite($file, 'id="current"');
	  }    
	  fwrite($file, '><a href="credits.html" title="Credits and references used through the WebQuest.">'.get_string('credits','webquestscorm').'</a></li>');  
		



	  fwrite($file, '</ul></div><div id="maincontent"><h2>'.get_string($element,'webquestscorm').'</h2>');
	  fwrite($file, '<p>'.$this->webquestscorm->$element.'</p></div></div><br/><br/><br/>');
	  fwrite($file, '<p id="footer"><center><a href="http://www.educationaltechnology.ca/webquestscorm">Original webquestscorm template</a> design by <a href="http://www.educationaltechnology.ca/dan">Dan Schellenberg</a> using valid <a href="http://validator.w3.org/check/referer">XHTML</a> and <a href="http://jigsaw.w3.org/css-validator/validator?uri=http://www.educationaltechnology.ca/webquestscorm/css/basic.css">CSS</a>.</center></p>');
	  fwrite($file, '	</body></html>');
	  fclose($file);
}		

	    
     
}

?>
