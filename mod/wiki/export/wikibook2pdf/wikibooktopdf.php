<?php

    require_once('../../../../config.php');

    global $USER, $CFG;

    require_once($CFG->dirroot.'/mod/wiki/locallib.php');
    require_once($CFG->dirroot.'/mod/wiki/lib.php'); 
    require_once ($CFG->dirroot.'/mod/wiki/wiki/nwikiparser.php');

    //Wikibookpdf Class
    require_once ($CFG->dirroot.'/mod/wiki/export/wikibook2pdf/wikibookpdf.class.php');

    //html functions
    require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

    $cid      = required_param('cid', PARAM_INT); // Course Id
    $cmid     = required_param('cmid', PARAM_INT); // Course Module Id
    $uid      = optional_param('uid', NULL, PARAM_INT); // User Id
    $gid      = optional_param('gid',NULL,PARAM_INT); // Group Id
    $namebook = optional_param('yourbook', NULL, PARAM_PATH); // Wikibook wikipage

    require_login($cid);

//only for theachers and admins:
$context = get_context_instance(CONTEXT_MODULE, $cmid);
require_capability('mod/wiki:canexporttopdf', $context);


if (! $course = get_record("course", "id", $cid)) {
      error("Course ID is incorrect");
}

if (!isset($uid)) { $uid = $USER->id; }


if (!isset($gid)) { error("Group id isn\'t defined");}


////////  CONTINUE  ///////////////////////////////////////

//Check if either we're comming from the form or this is the first time
if (optional_param('continue',NULL,PARAM_ALPHA) == get_string('continue') && isset($namebook)){

    //Form has already been visited
    $font_base = optional_param('font_base',NULL,PARAM_ALPHA);
    $font_base_size = optional_param('font_base_size',NULL,PARAM_INT);
    $withheader = optional_param('with_header', false, PARAM_BOOL);
    $withfooter = optional_param('with_footer', false, PARAM_BOOL);
    $doc_title = optional_param('doc_title', '', PARAM_ALPHA);
    $doc_subtitle = optional_param('doc_subtitle', '', PARAM_ALPHA);
    $font_header = optional_param('font_header', NULL, PARAM_ALPHA);
    $groupmode = optional_param('groupmode',NULL, PARAM_INT);
    $studentmode = optional_param('studentmode',NULL, PARAM_INT);

    if ($withheader != false) { $withheader= true; }
    if ($withfooter != false) { $withfooter= true; }

    $namebook = explode('_',$namebook); // Format namebook(wiki_pages): id(0), name(1), userid(2), groupid(3), ownerid(4), dfwiki(5), version(6)

    // We catch the index of the wikibook

    if (! $wikibook = get_record_sql('SELECT wp.pagename, wp.content
                                      FROM '. $CFG->prefix.'wiki_pages wp
                                      WHERE wp.id ='.$namebook[0]))

    { error('wikibooktopdf: We do not have the document.'); }


    if ($groupmode==0 && $studentmode==0)
    {$sql_where='AND wp.groupid=0 AND wp.ownerid=0 ';}

    elseif ($groupmode==0 && ($studentmode==1 || $studentmode==2))
    {$sql_where='AND wp.groupid=0 AND wp.ownerid='.$namebook[4].' ';}

    elseif (($groupmode==1 || $groupmode==2) && $studentmode==0)
    {$sql_where='AND wp.groupid='.$namebook[3].' ';}

    elseif ( ($groupmode==1 && ($studentmode==1 || $studentmode==2)) || ($groupmode==2 && ($studentmode==1 || $studentmode==2)) )
    {$sql_where='AND wp.ownerid='.$namebook[4].' AND wp.groupid='.$namebook[3].' ';}

    else {$sql_where='';} // It won't be

    $sql_where2 = str_replace(' wp.', ' wp2.', $sql_where);

    preg_match_all('/\[\[.*\]\]/', $wikibook->content, $wikipagesrefs); // The order depends of the index or main page 

    $wikipagesrefs = $wikipagesrefs[0];

    $i = 0;  
    $long = count($wikipagesrefs);
    while ($i < $long)
    {
        $wikipagesrefs[$i] = str_replace('[[', '', $wikipagesrefs[$i]);
        $wikipagesrefs[$i] = str_replace(']]', '', $wikipagesrefs[$i]);
        
        // To delete the labels
        if ( FALSE !== strpos($wikipagesrefs[$i], '|')) { $wikipagesrefs[$i] = substr($wikipagesrefs[$i], 0, strpos($wikipagesrefs[$i], '|'));}
       
        $i++;
    }

    // The first page or index of the wikibook
    $bookname[0] = $wikibook->pagename;
    $booktext[0] = $wikibook->content;

    // Wikipagesrefs is a vector of strings, each string means a wiki reference
    // Format namebook(wiki_pages): id(0), name(1), userid(2), groupid(3), ownerid(4), dfwiki(5), version(6)

    foreach ($wikipagesrefs as $page)
    {
        if ($text = get_record_sql('SELECT wp.content 
                                    FROM '.$CFG->prefix.'wiki_pages wp 
                                    WHERE wp.pagename="'.$page.'" AND wp.editor="nwiki" AND wp.dfwiki='.$namebook[5].' '.$sql_where
                                                      .'AND wp.version = (SELECT MAX(wp2.version) 
                                                                          FROM '.$CFG->prefix.'wiki_pages wp2
                                                                          WHERE wp.pagename = wp2.pagename AND wp.editor="nwiki" '.$sql_where2.'AND wp2.dfwiki='.$namebook[5].' '.$sql_where.
                                                                          'GROUP BY wp2.pagename)'))

        { array_push($bookname, $page); array_push($booktext, $text->content); }
    }

wikibook_to_tcpdf($bookname, $booktext, $wikipagesrefs, $font_base, $font_base_size, $withheader, $withfooter, $doc_title, $doc_subtitle, $font_header);

} 
else //I have to find a wikibooks 
{

    // Wiki of the course module
    if (! $wikimain = get_record_sql('SELECT w.id, w.name, w.pagename, w.studentmode
                              FROM '.$CFG->prefix.'wiki w, '.$CFG->prefix.'course_modules cm
                              WHERE w.course=cm.course AND cm.course='.$cid.' AND cm.instance=w.id AND cm.id='.$cmid.''))
    { error('wikibooktopdf: There isn\'t any wiki'); }

    $cm_instance = get_coursemodule_from_instance('wiki', $wikimain->id, $cid);

    // If user is a teacher, he can view everything
    if (!has_capability('mod/wiki:adminactions', $context))
    {
        if ($cm_instance->groupmode==0 && $wikimain->studentmode==0)
        {$sql_where='AND wp.groupid=0 AND wp.ownerid=0 ';}

        elseif ($cm_instance->groupmode==0 && $wikimain->studentmode==1)
        {$sql_where='AND wp.groupid=0 AND wp.ownerid='.$uid.' ';}

        elseif ($cm_instance->groupmode==0 && $wikimain->studentmode==2)
        {$sql_where='AND wp.groupid=0 ';}

        elseif ($cm_instance->groupmode==1 && $wikimain->studentmode==0)
        {$sql_where='AND wp.groupid='.$groupid;}

        elseif ($wikimain->studentmode==1 && $cm_instance->groupmode==1)
        {$sql_where='AND wp.ownerid='.$uid.' AND wp.groupid='.$groupid.' ';}

        elseif ($wikimain->studentmode==1 && $cm_instance->groupmode==2)
        {$sql_where='AND (( wp.ownerid='.$uid.' AND wp.groupid='.$groupid.') OR ( wp.groupid<>'.$groupid.'))'.' ';}

        else {$sql_where='';}
    }
    else 
    {$sql_where='';}

    $sql_where2 = str_replace(' wp.', ' wp2.', $sql_where);

    // All wiki pages of this Wiki and after we search the wikibooks
    if (! $wikis = get_records_sql('SELECT wp.id, wp.pagename, wp.userid, wp.author, wp.groupid, wp.ownerid, wp.dfwiki, wp.version
                                    FROM '.$CFG->prefix.'wiki_pages wp
                                    WHERE wp.dfwiki='.$wikimain->id.' AND wp.editor="nwiki" AND wp.pagename LIKE "%wikibook:%" '.$sql_where
                                                                   .'AND wp.version = (SELECT MAX(wp2.version)
                                                                                        FROM '.$CFG->prefix.'wiki_pages wp2
                                                                                        WHERE wp2.pagename = wp.pagename '.$sql_where2.'AND wp2.editor="nwiki" AND wp2.dfwiki='.$wikimain->id.
                                                                                        ' GROUP BY wp2.pagename)'))
    { error('wikibooktopdf: There isn\'t any wikibook in this wiki'); }


    // First time
    //---------------Form------------------//

    //Adjust some php variables to the execution of this script
    //@ini_set("max_execution_time","3000");
    //raise_memory_limit("memory_limit","128M");

    $strwikis = get_string("modulenameplural", 'wiki');
    //$strwikis  = get_string("modulename", 'wiki');

    /// Print the page header
// Old print header
/*    print_header($course->shortname .': '. get_string('wikibooks', 'wiki'), $course->fullname, 
                 "<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">{$course->shortname}</a> ->
                  <a href=\"{$CFG->wwwroot}/mod/wiki/index.php?id={$course->id}\">$strwikis</a> ->
                  <a href=\"{$CFG->wwwroot}/mod/wiki/view.php?id=$cmid\">{$wikimain->name}</a> ->"
                  .get_string('wikibooktopdf', 'wiki'));*/

//New print header

    $navlinks[] = array('name' => $strwikis, 'link' => "{$CFG->wwwroot}/mod/wiki/index.php?id={$course->id}", 'type' => 'misc');
    $navlinks[] = array('name' => $wikimain->name, 'link' => "{$CFG->wwwroot}/mod/wiki/view.php?id=$cmid", 'type' => 'misc');
    $navlinks[] = array('name' => get_string('wikibooktopdf', 'wiki'), 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header($course->shortname .': '. get_string('wikibooks', 'wiki'), $course->fullname, $navigation);



    $prop = null;
    $prop->class = "textcenter";
    wiki_div_start($prop);
    wiki_size_text(get_string('wikibookselect', 'wiki'), 2);
    wiki_div_end();

	echo '<!-- Start of the Form -->'."\n";
	echo '<!-- SELECT the WikiBooks to convert -->'."\n";

        unset($prop);
	$prop->id = "form";
	$prop->method = "post";
	$prop->action = 'wikibooktopdf.php?cid='.$cid.'&amp;cmid='.$cmid.'&amp;uid='.$uid.'&amp;gid='.$gid;
	wiki_form_start($prop); // FORM

        // Input hidden studentmode and groupmode
        // STUDENT
        unset($prop);
        $prop->id = 'student';
        $prop->name = 'studentmode';
        $prop->value = $wikimain->studentmode;
        wiki_input_hidden($prop);

        // GROUPMODE
        unset($prop);
        $prop->id = 'group';
        $prop->name = 'groupmode';
        $prop->value = $cm_instance->groupmode;
        wiki_input_hidden($prop);

		unset($prop);
		$prop->class = "box generalbox generalboxcontent boxaligncenter";
		wiki_div_start($prop);

                 unset($prop);
                 $prop->class = "box boxaligncenter";
                 wiki_table_start($prop); // Table General

                        unset($prop);
                        $prop->class = "box generalboxcontent";
                        wiki_div_start($prop);

                        unset($prop);
                        $prop->class = "box";
                        wiki_table_start($prop); // Table Wikibooks

				print_string('wikibooks', 'wiki');
				wiki_change_row();
                                $nwikibooks = 0;
				unset($opt);
		      	        foreach ($wikis as $wiki_page) {

                                    unset($prop);
                                    $iswikibook = str_replace('wikibook:', '', $wiki_page->pagename);
		        	    $prop->value = $wiki_page->id.'_'.$iswikibook.'_'.$wiki_page->userid.'_'.$wiki_page->groupid.'_'.
                                                   $wiki_page->ownerid.'_'.$wiki_page->dfwiki.'_'.$wiki_page->version;
		        	    $opt .= wiki_option ($iswikibook.' (author='.$wiki_page->author.', groupid='.$wiki_page->groupid.', ownerid='.$wiki_page->ownerid.')', $prop, true);
		        	    $nwikibooks += 1;

                                }
                                
                                if ($nwikibooks == 0) { $prop->value = ""; $opt = wiki_option('There isn not any WikiBooks', $prop, true); }
                                
                                $prop = null;
				$prop->name = "yourbook";
				$prop->size = "10";
				$prop->id = "your_wikibooks";
				wiki_select($opt,$prop);

                      wiki_table_end(); // Table Wikibooks
                      wiki_div_end();

                 wiki_change_column(); // General

                 unset($prop);
                 $prop->class = "box";
                 wiki_table_start($prop);

                 wiki_b("&nbsp;&nbsp;&lt;&lt;&nbsp;&nbsp;&nbsp;");
                 wiki_change_row();
                 wiki_b("&nbsp;&nbsp;&lt;&lt;&nbsp;&nbsp;&nbsp;");
                 wiki_change_row();
                 wiki_b("&nbsp;&nbsp;&lt;&lt;&nbsp;&nbsp;&nbsp;");
                 wiki_change_row();
                 wiki_b("&nbsp;&nbsp;&lt;&lt;&nbsp;&nbsp;&nbsp;");

                 wiki_table_end();

                 wiki_change_column(); // General

                      unset($prop);
                      $prop->class = "box generalbox generalboxcontent";
                      wiki_div_start($prop); // Div parameter

                                // Table to complet with the correct parameters  
                                unset($prop);
			        $prop->class = "box";
                                wiki_table_start($prop); // Table parameter

///////////////////////////////////////////////////////////////////////////////////////// FONTS

                                echo('<!-- SELECT FONTS -->');
                                wiki_b("Font section");
                                wiki_hr();

                                wiki_change_row(); // Table parameter

                                       unset($prop);
                                       $prop->class = "boxalignleft";
                                       wiki_table_start($prop); // Table font 
                                       wiki_b(get_string('wikibooks_font','wiki').':');

                                       unset($prop);
                                       $prop->class = "nwikileftnow";
                                       wiki_change_column($prop);

                                       $font = 'Vera';
                                       $fonts = array('Vera', 'Veramo', 'Freesans', 'Freeserif', 'Freemono');
                                       $opt=null;
                                       foreach ($fonts as $fontop){
                                           unset($prop);
                                           $prop->value = $fontop;
                                           if ($font === $fontop) {$prop->selected = "selected"; }
                                           $opt .= wiki_option($fontop, $prop, true);
                                       }
                                       $prop = null;
                                       $prop->name = "font_base";
                                       $prop->size = "1";
                                       $prop->class = "boxalignright";
                                       wiki_select($opt,$prop);
 
                                wiki_table_end(); // Table font

                                wiki_change_row(); // Table parameter

                                    unset($prop);
                                    $prop->class = "boxalignleft";
                                    wiki_table_start($prop); // Table font 
                                    wiki_b(get_string('wikibooks_font_size','wiki').':');
                                    unset($prop);
                                    $prop->class = "nwikileftnow";
                                    wiki_change_column($prop);

                                    $size_font_default = '12';
                                    $size_fonts = array('8','9','10','11','12','14','16','18','20','22','24','26','28','36');
                                    unset($opt);
                                    foreach ($size_fonts as $size_fontop){
                                        $prop = null;
                                        $prop->value = $size_fontop;
                                        if ($size_fontop == $size_font_default ) {$prop->selected = "selected"; }
                                        $opt .= wiki_option($size_fontop, $prop, true);
                                    }

                                    unset($prop);
                                    $prop->name = "font_base_size";
                                    $prop->size = "1";
                                    wiki_select($opt,$prop); 

                                    wiki_table_end(); // Table Font
 
                                wiki_change_row(); // Table parameter

///////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////// Header & Footer Section
                                 wiki_br(2);
 

                                    echo('<!-- SELECT FOOTERS -->');
                                    wiki_b("Header and Footer section");
                                    wiki_hr();

                                    unset($prop);
                                    $prop->class = "box";
                                    wiki_table_start($prop); // Table quetion wikibook header? & footer?

                                    wiki_paragraph(get_string('wikibooks_header_question','wiki'));

                                    wiki_change_column();
                                  
                                    unset($prop);
                                    $prop->name = "with_header";
                                    wiki_input_checkbox($prop);

                                    wiki_table_end(); // Table quetion wikibook header?

                                wiki_change_row(); // Table parameter

                                    unset($prop); /////// Title
                                    $prop->class = "box";
                                    $prop->border = 1;
                                    wiki_table_start($prop);    //////////////////////Table form information pdf

                                    wiki_paragraph(get_string('title','wiki'));

                                    wiki_change_column();

                                    unset($prop);
                                    $prop->name = "doc_title";
                                    $pro->size = 20;
                                    wiki_input_text($prop);

                                    wiki_change_row();

                                    wiki_paragraph(get_string('subtitle','wiki'));

                                    wiki_change_column();

                                    unset($prop);
                                    $prop->name = "doc_subtitle";
                                    $pro->size = 20;
                                    wiki_input_text($prop);

                                    wiki_table_end();//Table form information pdf


                                   wiki_change_row(); // Table parameter

                                       unset($prop);
                                       $prop->class = "boxalignleft";
                                       wiki_table_start($prop); // Table font header
                                       wiki_b(get_string('wikibooks_font_header','wiki').':');

                                       unset($prop);
                                       $prop->class = "nwikileftnow";
                                       wiki_change_column($prop);

                                       $font = 'Vera';
                                       $fonts = array('Vera', 'Veramo', 'Freesans', 'Freeserif', 'Freemono');
                                       $opt=null;
                                       foreach ($fonts as $fontop){
                                           unset($prop);
                                           $prop->value = $fontop;
                                           if ($font === $fontop) {$prop->selected = "selected"; }
                                           $opt .= wiki_option($fontop, $prop, true);
                                       }
                                       $prop = null;
                                       $prop->name = "font_header";
                                       $prop->size = "1";
                                       $prop->class = "boxalignright";
                                       wiki_select($opt,$prop);
 
                                       wiki_table_end(); // Table font header

                                wiki_change_row(); // Table parameter

                                    unset($prop);
                                    $prop->class = "box";
                                    wiki_table_start($prop); // Table question footer

                                    unset($prop);
                                    $prop->class = "boxalignleft";
                                    wiki_paragraph(get_string('wikibooks_footer_question','wiki'));

                                    wiki_change_column();

                                    unset($prop);
                                    $prop->name = "with_footer";
                                    wiki_input_checkbox($prop);
                               
                                    wiki_table_end(); // Table question footer

                                wiki_change_row(); // Table parameter

                                wiki_br();

//////////////////////////////////////////////////////////////////////////////////////////////////////////

                                wiki_change_row(); // Table parameter

                                wiki_br();

                        unset($prop);
                        $prop->name = "continue";
                        $prop->id = "continue_pdf";
                        $prop->value = get_string('continue');
                        wiki_input_submit($prop);

                           wiki_table_end(); // Table parameter
                         wiki_div_end();  // Div parameter   

                 wiki_table_end(); // Table General
             wiki_div_end();
       wiki_form_end(); // FORM


    /// Finish the page
    print_footer($course);
}

/////////////////////////////////////////
///// WIKIBOOK TO PDF (the function)
////////////////////////////////////////

///// WITH TCPDF

function wikibook_to_tcpdf (&$bookname, &$booktext, &$mainrefs, $font_base, $font_base_size, $withheader, $withfooter, $doc_title, $doc_subtitle, $font_header)
{

    global $USER;

    $tcpdf = new wikibookpdf(PDF_PAGE_ORIENTATION, 'mm', PDF_PAGE_FORMAT, true);

    $font_base = strtolower($font_base);
    $font_header = strtolower($font_header);

    configure_tcpdf($tcpdf, $withheader, $withfooter, $font_base, $font_base_size, $doc_title, $doc_subtitle, $font_header);

    $i=0;
    while ($i < sizeof($booktext)) // Parser the nwiki documents
    {
        $tcpdf->AddPage();

        $tcpdf->SetLinkinName($tcpdf->AddLinkin(), $bookname[$i], '', $tcpdf->PageNo());

        $text[$i] = parse_nwiki_text($booktext[$i]);  
        $text[$i] = '<p><b>'.$bookname[$i].'</b><br></p>'.$text[$i];

        $text[$i] = str_replace(' %%% ', '<br>', $text[$i]);
        $text[$i] = str_replace("\n", '<br>', $text[$i]);

        $tcpdf->writeWikibookHTML($text[$i]);

        $i++;
    }
    // To copy inside the definitive "PDF"
    $array_name = $tcpdf->getLinkinName(); 
    $array_link = $tcpdf->getLinkin();
    $array_images = $tcpdf->getImages();

    if (count($text) > 0) // The first unordered list is the index of wikibook
    {  
        $tag_text = preg_split('/(<[^>]+>)/Uu', $text[0], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); //explodes the string
        $i = 0;
        $find = False;
        $first_ul = False;
        $ul = 0;
        $n = count($tag_text);
        while (!$find && ($i < $n))
        {
            if (strtolower($tag_text[$i]) == '<ul>') { $ul++; $tag_text[$i]='<ol>';} 
            else if (strtolower($tag_text[$i]) == '</ul>') {$ul--; $tag_text[$i]='</ol>';}
            
            if ($ul == 1 && !$first_ul) { $first_ul = True; }
            if ($first_ul && ($ul == 0)) { $find = True; }
            $i++;
        }
            
        $text[0] = implode("", $tag_text);
    }

    $tcpdf = new wikibookpdf(PDF_PAGE_ORIENTATION, 'mm', PDF_PAGE_FORMAT, true); // The "PDF"

    configure_tcpdf($tcpdf, $withheader, $withfooter, $font_base, $font_base_size, $doc_title, $doc_subtitle, $font_header);

    // The internal links
    $tcpdf->CopyLinkinName($array_name, $array_link);
    $tcpdf->setImages($array_images);
    unset($array_name);
    unset($array_link); 
    unset($array_images); 

    $i=0;
    while ($i < sizeof($text)) 
    {
        $tcpdf->AddPage();

        $tcpdf->writeWikibookHTML($text[$i], '', '', true);

        $i++;
    }

    $tcpdf->Close(); // It doesn't be necessary

    $tcpdf->Output(str_replace(':', '_', $bookname[0]).".pdf", "D");

}

/****************************************************
*
*  CONFIGURE_TCPDF
*****************************************************/

function configure_tcpdf(&$pdf, $withheader, $withfooter,$font_base, $font_base_size, $doc_title='Wikibook', $doc_subject='', $font_header = null,$doc_keywords='keywords')
{
    // set document information
    $pdf->SetCreator('DFWikiLabs');
    $pdf->SetAuthor(PDF_AUTHOR);
    $pdf->SetTitle($doc_title); // The title of the pdf
    $pdf->SetSubject($doc_subject); // The subject
    $pdf->SetKeywords($doc_keywords); 

    //set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_HEADER + 15, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    //Fonts available: vera, freesans, dejavusans , dejavusansi, veramo, dejavuserif, dejavuserifcondensed, freeserif, veramo
    $pdf->SetFont($font_base, "", $font_base_size);

    if (!isset($font_header)) { $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN)); }

    else { $pdf->setHeaderFont(Array($font_header, '', PDF_FONT_SIZE_MAIN)); }

    // add page header/footer (yes or no)
    $pdf->setPrintHeader($withheader);
    $pdf->setPrintFooter($withfooter);


    $pdf->setHeaderData('', '', $doc_title, $doc_subject); // Title and Subtitle to write in header (These can be differents)

    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    //initialize document
    $pdf->AliasNbPages();
}

?>
