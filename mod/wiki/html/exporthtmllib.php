<?php

/**
 * HTML Export
 * this file contains all functions needed to export to
 * a HTML file.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: exporthtmllib.php,v 1.5 2007/06/15 11:43:18 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package HTML_export
 */
 
 
require_once ('../../../backup/lib.php');
require_once ($CFG->libdir.'/filelib.php');
require_once ('sintax.php');

//global variables
global $CFG;
global $WS;

//Adjust some php variables to the execution of this script
@ini_set("max_execution_time","3000");
raise_memory_limit("memory_limit","256M");

function wiki_export_html(&$WS){

    global $CFG;

    check_dir_exists("$CFG->dataroot/temp",true);
    check_dir_exists("$CFG->dataroot/temp/html",true);
    check_dir_exists("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}",true);
    check_dir_exists("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}/atachments",true);
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}",true);
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata",true);
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata/dfwiki{$WS->cm->id}",true);

    //export contents
    wiki_export_html_content($WS);

    //export attached files
    $flist = list_directories_and_files ("$CFG->dataroot/{$WS->dfwiki->course}/moddata/dfwiki{$WS->cm->id}");
    if($flist != null){
        foreach ($flist as $fil) {
            $from_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/dfwiki{$WS->cm->id}/$fil";
            $to_file = "$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}/atachments/$fil";
            copy($from_file,$to_file);
        }
    }

    //zip file name
    $times = time();
    $name = $WS->dfwiki->name.'-'.$times.'.zip';
    $cleanzipname = clean_filename($name);
    //List of files and directories
    $filelist = list_directories_and_files ("$CFG->dataroot/temp/html");

    //Convert them to full paths
    $files = array();
    if($filelist != null){
        foreach ($filelist as $file) {
            $files[] = "$CFG->dataroot/temp/html/$file";
        }
    }

    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}", true);
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/exportedhtml", true);
    $destination = "$CFG->dataroot/{$WS->dfwiki->course}/exportedhtml/$cleanzipname";

    $status = zip_files($files, $destination);

    //delete the folder created in temp
    $filelist2 = list_directories_and_files ("$CFG->dataroot/temp/html");
    if ($filelist2 != null) $del = delete_dir_contents("$CFG->dataroot/temp/html");

	//show it all to be albe to download the file
    $prop = null;
    $prop->class = "textcenter";
    wiki_div_start($prop);
	wiki_size_text(get_string("exporthtmlcorrectly","wiki"),2);
	wiki_div_end();

	$prop = null;
	$prop->border = "0";
	$prop->class = "boxaligncenter";
	$prop->classtd = "nwikileftnow";
	wiki_table_start($prop);

		$wdir = '/exportedhtml';
	    $fileurl = "$wdir/$cleanzipname";
	    $ffurl = "/file.php?file=/{$WS->cm->course}$fileurl";
	    $icon = mimeinfo("icon", $cleanzipname);
	    link_to_popup_window ($ffurl, "display",
	                                  "<img src=\"$CFG->pixpath/f/$icon\" height=\"16\" width=\"16\" alt=\"File\" />", 480, 640);
	    echo "\n".'&nbsp;';
	    link_to_popup_window ($ffurl, "display", htmlspecialchars($cleanzipname), 480, 640);

	    $prop = null;
	    $prop->class = "nwikileftnow";
	    wiki_change_row($prop);
    	echo '&nbsp;';

	wiki_table_end();

	$prop = null;
	$prop->border = "0";
	$prop->class = "boxaligncenter";
	$prop->classtd = "nwikileftnow";
	wiki_table_start($prop);

		$prop = null;
		$prop->id = "form";
		$prop->method = "post";
		$prop->action = '../xml/index.php?id='.$WS->dfwiki->course.'&amp;wdir=/exportedhtml';
		wiki_form_start($prop);
			wiki_div_start();
				$prop = null;
				$prop->name = "dfform[viewexported]";
				$prop->value = get_string('viewexported','wiki');
				wiki_input_submit($prop);
			wiki_div_end();
		wiki_form_end();
		wiki_change_column();

		print_continue("$CFG->wwwroot/mod/wiki/view.php?id={$WS->cm->id}");

	wiki_table_end();
}


function wiki_export_html_content(&$WS){

    global $CFG;

    //generate a list with all dfwiki groups
    if ($grouppages = get_records_sql('SELECT *
    			FROM '. $CFG->prefix.'wiki_pages
    			WHERE dfwiki='.$WS->dfwiki->id.' GROUP BY groupid')){

        //gets every single group
        foreach ($grouppages as $grouppage){

            //generate a list with every latest page version
            if ($contentspages = get_records_sql('SELECT *
    			FROM '. $CFG->prefix.'wiki_pages
    			WHERE dfwiki='.$WS->dfwiki->id.'
                AND groupid='.$grouppage->groupid.' GROUP BY pagename')){

                $WS->groupmember->groupid = $grouppage->groupid;

                if(!$groupname = get_record_sql('SELECT * FROM '. $CFG->prefix.'groups WHERE id='.$grouppage->groupid)){
                    $groupname->name = 'no_groups';
                }

                //list_pages
                check_dir_exists("$CFG->dataroot/temp",true);
                check_dir_exists("$CFG->dataroot/temp/html",true);
                check_dir_exists("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}",true);
                check_dir_exists("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}/$groupname->name",true);
                $html_file_list = fopen("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}/$groupname->name/block_list_pages.html", "w");
                $contentlist_parsed = wiki_treat_list_pages($contentspages, $grouppage->groupid);
                $l_pages = get_string('block_list_pages', 'dfwiki');
                fwrite($html_file_list, "<html><header></header><body><center><h1>$l_pages</h1></center><table align='center' border='1' cellpadding='5' cellspacing='0'><tr><td>$contentlist_parsed</td></tr></table></body></html>");
                fclose($html_file_list);


                foreach ($contentspages as $contentpage){

                    $max = get_record_sql('SELECT MAX(version) AS maxim
    				    FROM '. $CFG->prefix.'wiki_pages
    				    WHERE pagename=\''.addslashes($contentpage->pagename).'\' AND dfwiki='.$WS->dfwiki->id.'
                        AND groupid='.$grouppage->groupid);

                    $WS->pagedata = get_record_sql('SELECT *
    				   FROM '. $CFG->prefix.'wiki_pages
    				   WHERE pagename=\''.addslashes($contentpage->pagename).'\' AND dfwiki='.$WS->dfwiki->id.'
                       AND groupid='.$grouppage->groupid.' AND version='.$max->maxim);

                    //en $cont->content tenim el contingut que hem de parsear
                    check_dir_exists("$CFG->dataroot/temp",true);
                    check_dir_exists("$CFG->dataroot/temp/html",true);
                    check_dir_exists("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}",true);
                    check_dir_exists("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}/$groupname->name",true);

                    $cleanpagename = clean_filename($WS->pagedata->pagename);

                    $html_file = fopen("$CFG->dataroot/temp/html/dfwiki{$WS->cm->id}/$groupname->name/$cleanpagename.html", "w");
                    $content_parsed = wiki_page_content($WS->pagedata->content,$WS);
                    fwrite($html_file, "<html><header></header><body><center><h1>{$WS->pagedata->pagename}</h1></center><table align='center' border='1' cellpadding='5' cellspacing='0' width='95%'><tr><td>$content_parsed</td></tr></table></body></html>");
                    fclose($html_file);

                }

            }

        }

    }

}

function wiki_treat_list_pages($listpages, $group){

    $list = '';
    foreach($listpages as $listpage){

        $cleanpagename = clean_filename($listpage->pagename);
        $list.= '<li><a href="'.$cleanpagename.'.html">'.$listpage->pagename.'</a></br>';

    }

    return $list;
}

//this function prints a well formated page content
function wiki_page_content (&$text,&$WS){
    //WS->wiki_format is the wiki parser configuration array.

    //configure wiki parse with the editor
    switch ($WS->pagedata->editor) {
    	case 'dfwiki':
    		//dfwiki parse is already configured for use
    		//the dfwiki editor, logically.
    		return wiki_sintax_html_bis($text);
    		break;

		case 'nwiki':
    		//nwiki parse is already configured for use
    		//the nwiki editor, logically.
    		return parse_nwiki_text($text);
    		break;
    	case 'ewiki': //configure parse for emulate ewiki format.
    		//del all format
    		foreach ($WS->wiki_format as $key=>$type){
    			unset($WS->wiki_format[$key]);
    		}
    		//put new parser params
    		$WS->wiki_format['line'] = array (
    						'-----' => "<hr noshade=\"noshade\" />\n",
    						'----' => "<hr noshade=\"noshade\" />\n",
    						'---' => "<hr noshade=\"noshade\" />\n"
    						);
    		$WS->wiki_format['start-end'] = array (
    										"**" => array ("<b>","</b>"),
    										"__" => array ("<b>","</b>"),
    										"'''" => array ("<b>","</b>"),
    										"''" => array ("<i>","</i>"),
    										"��" => array ("<BIG>","</BIG>"),
    										"##" => array ("<SMALL>","</SMALL>"),
    										"==" => array ("<tt>","</tt>")
    									);
    		$WS->wiki_format['line-start'] = array (
    										" " => "&nbsp;",
    										"	" => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
    									);
    		$WS->wiki_format['lists'] = array(
    									'*'=> 'ul',
    									'#'=> 'ol'
    								);
    		$WS->wiki_format['links'] = array (
    									'internal' => array ("[[","]]"),
    									'external' => array ("[","]")
    								);
    		$WS->wiki_format['table'] = array (
    									'|' => 'td'
    								);
    		$WS->wiki_format['nowiki'] = array('<nowiki>','</nowiki>');
    		$WS->wiki_format['line-start-enc'] = array (
    											'!!!' => array ('<h1>','</h1>'),
    											'!!' => array ('<h2>','</h2>'),
    											'!' => array ('<h3>','</h3>')
    										);
			return wiki_sintax_html_bis($text);
    		break;

    	case 'htmleditor':
    		//the parse will only recognize nowiki and links markup.
    		$aux = $WS->wiki_format['links'];
    		$aux2 = $WS->wiki_format['nowiki'];
    		//del all format
    		foreach ($WS->wiki_format as $key=>$type){
    			unset($WS->wiki_format[$key]);
    		}
    		//restore links and nowiki.
    		$WS->wiki_format['links'] = $aux;
    		$WS->wiki_format['nowiki'] = $aux2;
			return wiki_sintax_html_bis($text);
    		break;
    	default:
			return wiki_sintax_html_bis($text);
    		break;
    }
    //return wiki_sintax_html_bis($text);
    //end table
}

?>
