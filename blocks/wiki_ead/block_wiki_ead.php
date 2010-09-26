<?php

/**
 * This file contains the wiki administrtion tool class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_ead.php,v 1.16 2008/02/29 13:45:56 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_ead extends block_base {

   var $pagesel = array();

    ////Function called when a module instance is activated
    function init() {
        $this->title = get_string('block_ead', 'wiki').helpbutton('ead', get_string('block_ead', 'wiki'), 'wiki', true, false, '', true);
        $this->version = 2004081200;
    }

    //applicable formats to the block, overrides block_base::applicable_formats()
    function applicable_formats() {
		return array('course-view-wiki' => true, 'mod-wiki' => true);
	}

    function get_content() {
    	global $CFG, $WS, $COURSE;

        $basedir = '/mod/wiki/images/';

    	if($this->content !== NULL) {
            return $this->content;
        }

		//If we are out of a dfwiki activity or in a different
		//dfwiki format course and we want to create a block:
		if(empty($WS->dfwiki)) {
       		$this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        //$this->content->footer = '<br />'.helpbutton ('ead', get_string('block_ead', 'wiki'), 'wiki', true, false, '', true).get_string('block_ead', 'wiki');

        // rtl / ltr CSS alignment support (nadavkav)
        if ( right_to_left() ) { $nwikialignment = 'nwikirightnow';} else { $nwikialignment = 'nwikileftnow';}

        //in case course wiki define the correct path for the url

        $tools = array (
                            array(get_string('mostviewed','wiki'),$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,"<input type=\"hidden\" name=\"dfcontent\" value='0' />"),//, 'template.gif'),
                array(get_string('updatest','wiki'),$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,"<input type=\"hidden\" name=\"dfcontent\" value='1' />"),//, 'template.gif'),
                array(get_string('newest','wiki'),$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,"<input type=\"hidden\" name=\"dfcontent\" value='2' />"),//, 'template.gif'),
                array(get_string('wanted','wiki'),$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,"<input type=\"hidden\" name=\"dfcontent\" value='3' />"),//, 'template.gif'),
                array(get_string('orphaned','wiki'),$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,"<input type=\"hidden\" name=\"dfcontent\" value='4' />"),//, 'template.gif'),
                array(get_string('activestusers','wiki'),$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,"<input type=\"hidden\" name=\"dfcontent\" value='5' />"),//, 'template.gif')
            );
        $text = "\n".'<table border="0" cellpadding="0" cellspacing="0">'."\n";

        //print public tools
        $i=0;
        foreach ($tools as $tool){
          $text.= '<tr><td class="'.$nwikialignment.'">'."\n";
          $text.= "<form id=\"form$i\" action=\"".$CFG->wwwroot.$WS->wikitype.$tool[1]."\" method=\"post\"><div>"."\n";
          //$text.= ' ';
          //$text.= '<img src="'.$CFG->wwwroot.$basedir.$tool[3].'" alt="" />';
          $text.= '<a href="javascript:document.forms[\'form'.$i.'\'].submit()">'.$tool[0].'</a>'.$tool[2]."\n";
          $text.= '</div></form>'.'</td></tr>'."\n";
          $i++;
        }
        $text.= '<tr><td><hr /></td></tr>'."\n";

        //teacher page dependant tools
        $tools = array (
                array(get_string('delpage','wiki'),$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;delpage='.urlencode($WS->pagedata->pagename),"<input type=\"hidden\" name=\"dfsetup\" value='0' />", 'deleteB.gif'),
                array(get_string('updatepage','wiki'),$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;updatepage='.urlencode($WS->pagedata->pagename),"<input type=\"hidden\" name=\"dfsetup\" value='1' />", 'refresh.gif'),
                array(get_string('cleanpage','wiki'),$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;cleanpage='.urlencode($WS->pagedata->pagename),"<input type=\"hidden\" name=\"dfsetup\" value='2' />", 'broom.gif')
            );

        //teacher non page dependant tools
        $tools_indep = array (
                array(get_string('exportxml','wiki'),$CFG->wwwroot.'/mod/wiki/xml/exportxml.php?id='.$WS->cm->id.'&amp;pageaction=exportxml', 'backup.gif'),
                array(get_string('importxml','wiki'),$CFG->wwwroot.'/mod/wiki/xml/importxml.php?id='.$WS->cm->id, 'restore.gif'),
                array(get_string('viewexported','wiki'),$CFG->wwwroot.'/mod/wiki/xml/index.php?id='.$WS->dfwiki->course.'&amp;wdir=/exportedfiles', 'files.gif'),
                array(get_string('exporthtml','wiki'),$CFG->wwwroot.'/mod/wiki/html/exporthtml.php?id='.$WS->cm->id, 'backup.gif'),
                array(get_string('dfwikitonewwiki','wiki'),$CFG->wwwroot.'/mod/wiki/dfwikitonewwiki.php?id='.$WS->cm->id, 'dfwiki.gif'),
                //array(get_string('wikitopdf','wiki'),$CFG->wwwroot.'/mod/wiki/wikitopdf.php?id='.$WS->cm->id.'&amp;cid='.$COURSE->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;version='.$WS->pagedata->version.'&amp;dfw='.$WS->dfwiki->id.'&amp;dfwn='.$WS->dfwiki->name.'&amp;pagename='.urlencode($WS->pagedata->pagename), 'template.gif'),
                array(get_string('wikitopdf','wiki'),$CFG->wwwroot.'/mod/wiki/wikitopdf.php?id='.$WS->cm->id.'&amp;cid='.$COURSE->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;version='.$WS->pagedata->version, 'pdf.gif'),
                array(get_string('wikibooktopdf','wiki'),$CFG->wwwroot.'/mod/wiki/export/wikibook2pdf/wikibooktopdf.php?cmid='.$WS->cm->id.'&amp;cid='.$COURSE->id.'&amp;gid='.$WS->groupmember->groupid, 'pdf.gif'),
                array(get_string('eval_reports','wiki'), $CFG->wwwroot.'/mod/wiki/grades/grades.evaluation.php?cid='.$COURSE->id.'&amp;cmid='.$WS->cm->id, 'template.gif')// Wiki Grades

                );
                //public tools
                $tools_sens = array (
                  array(($WS->pagedata->editable==0),get_string('en1page','wiki'),$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;enpage='.urlencode($WS->pagedata->pagename),"<input type=\"hidden\" name=\"dfsetup\" value='3' />", 'edit.gif'),
                  array(($WS->pagedata->editable==1),get_string('en0page','wiki'),$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;enpage='.urlencode($WS->pagedata->pagename),"<input type=\"hidden\" name=\"dfsetup\" value='3' />", 'edit.gif')
                );

        if (wiki_can_change($WS)){
          foreach ($tools as $tool){
          $text.= '<tr><td class="'.$nwikialignment.'">'."\n";
          $text.= "<form id=\"form$i\" action=\"".$tool[1]."\" method=\"post\"><span>"."\n";
          $text.= '<img src="'.$CFG->wwwroot.$basedir.$tool[3].'" alt="" />';
          $text.= ' ';
          $text.=	'<a href="javascript:document.forms[\'form'.$i.'\'].submit()" title="'.urlencode($tool[0].' '.$WS->pagedata->pagename).'">'.format_text($tool[0].' '.$this->trim_string($WS->pagedata->pagename,20),FORMAT_PLAIN).'</a>'.$tool[2]."\n";
          $text.= "</span></form>".'</td></tr>'."\n";
          $i++;
          }
          foreach ($tools_indep as $tool){
            $text.= '<tr><td class="'.$nwikialignment.'">';
                  $text.= '<img src="'.$CFG->wwwroot.$basedir.$tool[2].'" alt="" />';
                  $text.= ' ';
                  $text.= '<a href="'.$tool[1].'">'.$tool[0].'</a>';
                  $text.= '</td></tr>'."\n";
          }
          foreach ($tools_sens as $tool){
            if ($tool[0]) {
              //$text.= '<tr><td nowrap="nowrap"><a href="'.$tool[2].'" title="'.$tool[1].' '.$WS->pagedata->pagename.'">'.$tool[1].' '.$this->trim_string($WS->pagedata->pagename,20).'</a></td></tr>';
              $text.= '<tr><td class="'.$nwikialignment.'">'."\n";
              $text.= "<form id=\"form$i\" action=\"".$tool[2]."\" method=\"post\"><span>";
              $text.= '<img src="'.$CFG->wwwroot.$basedir.$tool[4].'" alt="" />';
              $text.= ' ';
              $text.= '<a href="javascript:document.forms[\'form'.$i.'\'].submit()" title="'.urlencode($tool[1].' '.$WS->pagedata->pagename).'">'.format_text($tool[1].' '.$this->trim_string($WS->pagedata->pagename,20),FORMAT_PLAIN).'</a>'.$tool[3]."\n";
              $text.= "</span></form>".'</td></tr>'."\n";
              $i++;
            }
          }

        }

        $text.='</table>'."\n";

        $this->content->text = $text;
        return $this->content;
    }

    //this function trims any given text and returns it with some dots at the end
    function trim_string($text, $limit) {
        mb_internal_encoding("UTF-8");
        if (mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit) . '...';
        }

        return $text;
    }



    /**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     */
    function specialization() {
        // Just to make sure that this method exists.
    }
}

?>