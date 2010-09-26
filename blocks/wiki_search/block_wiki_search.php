<?php
/**
 * This file contains the wiki search class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_search.php,v 1.11 2007/09/07 11:04:06 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_search extends block_base {

    // Function called when a module instance is activated
    function init() {

        $this->title = get_string('block_search','wiki'). helpbutton ('search', get_string('block_search', 'wiki'), 'wiki', true, false, '', true);;
        $this->version = 2004081200;
    }

    // applicable formats to the block, overrides block_base::applicable_formats()
    function applicable_formats() {
        return array('course-view-wiki' => true, 'mod-wiki' => true);
    }

    function get_content() {
        global $WS, $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;

        // If we are out of a dfwiki activity or in a different
        // dfwiki format course and we want to create a block:
        if (empty($WS->dfwiki)) {
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

        $this->content->items = array();
        $this->content->icons = array();
/*        $this->content->footer = '<br />'
            . helpbutton('search', $this->title, 'wiki', true, false, '', true)
            . get_string('search');*/
//         $this->content->footer = '<hr />'.get_string('block_helpaboutblock', 'wiki') .
//                 helpbutton ('search', get_string('block_search', 'wiki'), 'wiki', true, false, '', true);

        // Converts reserved chars for html to prevent chars misreading
        $pagetemp = stripslashes_safe($WS->page);

		$formurl = $CFG->wwwroot
            . '/mod/wiki/view.php?id=' . $WS->cm->id
            . '&amp;gid=' . $WS->groupmember->groupid
            . '&amp;uid=' . $WS->member->id
            . '&amp;page='. $WS->pageaction . '/' . urlencode($pagetemp);
        
        $formalturl = $CFG->wwwroot
            . '/mod/wiki/part/search/index.php?id=' . $WS->cm->id
            . '&amp;gid=' . $WS->groupmember->groupid
            . '&amp;uid=' . $WS->member->id
            . '&amp;page='. $WS->pageaction . '/' . urlencode($pagetemp);
		
		//reurl script
		?>
			<script language="JavaScript" type="text/javascript">
				function wiki_search_block_reulr (val) {
					wiki_form = document.getElementById ('wiki_search_block_form');
					if (val) {
						wiki_form.action = '<?php echo $formalturl; ?>';
					} else {
						wiki_form.action = '<?php echo $formurl; ?>';
					}
				}
			</script>
		<?php
		
        // mount the form
        $form = '<form id="wiki_search_block_form"method="post" action="'.$formurl.'">' .
        		'<div><input type="hidden" name="dfsetup" value="5" />'.
            '<input type="text" name="dfformfield" /><br />';

        // if this block is in a course it must submit info to /mod/wiki/view.php
        if ($this->instance->pagetype != 'mod-wiki-view') {
            $form .= '<input type="hidden" name="dfformmain" value="ch" />'
                . '<input type="submit" name="dfformbut" value="'
                . get_string('search') . '" /><br /></div></form>';
        } else {
            $form .= '<input id="wiki_search_block_check" type="checkbox" name="dfformmain" value="ch" ' .
            		'onClick="wiki_search_block_reulr (this.checked);"/>'
                . get_string('detailed','wiki')
                . '<input type="submit" name="dfformbut" value="'
                . get_string('search').'" /><br /></div></form>';
        }

        $form .= $this->get_results();

        $this->content->text = $form;

        return $this->content;
    }

    // this function returna table with the search result url's
    function get_results(){
        global $WS, $CFG;
		
        $res = '';

        if (isset($WS->dfform['result'])) {

            $res .= '<table border="0"><tr><td><b>'
                . get_string('searchresults','wiki') . ': '
                . $WS->dfform['field'] . '</b><hr /></td></tr>';

            if (count($WS->dfform['result']['pagename']) != 0) {
                foreach ($WS->dfform['result']['pagename'] as $result) {
                    $aux = $WS->dfform['field'];
                    $res .= '<tr> <td class="nwikileftnow">'
                        . '<script>var num=document.forms.length;'
                        . 'document.write(\'<form name="formsearch\' + num'
                        . " + '\" action=\"$CFG->wwwroot/mod/wiki/view.php?id="
                        . "{$WS->cm->id}&amp;page=" . urlencode($result)
                        . "&amp;gid={$WS->groupmember->groupid}"
                        . "&amp;uid={$WS->member->id}&amp;"
                        . "dfsearch=$aux\" method=\"post\"><div>"
                        . "<a href=\"javascript:document.formsearch' + num"
                        . " + '.submit()\">" . $this->trim_string($result, 20)
                        . "</a>"
                        . "<input type=\"hidden\" name=\"dfsetup\" value=5 />"
                        . "</div></form>');</script></td></tr>";
                }
            }else{
                $res .= '<tr><td>' . get_string('noresults') . '</td></tr>';
            }

            if (count($WS->dfform['result']['content']) != 0){
                $res .= '<tr><td><b>\'' .$WS->dfform['field']."' ". get_string('resultincontent', 'wiki')
                    . '</b><hr /></td></tr>';
                foreach ($WS->dfform['result']['content'] as $result) {
                    $aux = $WS->dfform['field'];
                    $res .= '<tr> <td nowrap="nowrap">'
                        . '<script>var num=document.forms.length;'
                        . "document.write('<form name=\"formsearch' + num"
                        . " + '\" action=\"$CFG->wwwroot/mod/wiki/view.php?id="
                        . $WS->cm->id . "&amp;page=" . urlencode($result)
                        . "&amp;gid={$WS->groupmember->groupid}"
                        . "&amp;uid={$WS->member->id}&amp;"
                        . "dfsearch=$aux\" method=\"post\">"
                        . "<a href=\"javascript:document.formsearch' + num"
                        . "+ '.submit()\">" . $this->trim_string($result, 20)
                        . "</a>"
                        . "<input type=\"hidden\" name=\"dfsetup\" value=5 />"
                        . "</form>');</script></td></tr>";
                }
            }

            $res .= '</table>';
        }

        return $res;
    }

    /**
     * Trims the given text and adds dots at the end, if necessary.
     *
     * @param String $text
     * @param Integer $limir
     *
     * @return String
     */
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
