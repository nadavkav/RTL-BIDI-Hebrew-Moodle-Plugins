<?php
/**
 *	file: inline_renderer.php
 *
 *	This file is a modified renderer for the Text_Diff PEAR package.
 *
 *	The inline_diff code was written by Ciprian Popovici in 2004,
 *	as a hack building upon the Text_Diff PEAR package.
 *	It is released under the GPL.
 *
 *	This code was adapted by Jordi Piguillem Poch in 2006 for Moodle.
 * @package Text_Diff
 * @author  DFWikiLabs
 */

	include_once 'Text/Diff/Renderer.php';
	class Text_Diff_Renderer_inline extends Text_Diff_Renderer {

	var $ins_prefix = '<ins>';
	var $ins_suffix = '</ins>';
	var $del_prefix = '<del>';
	var $del_suffix = '</del>';
	
	function Text_Diff_Renderer_inline($context_lines = 10000, $ins_prefix = '<ins>', $ins_suffix = '</ins>', $del_prefix = '<del>', $del_suffix = '</del>')
    {
		$this->$ins_prefix = $ins_prefix;
		$this->$ins_suffix = $ins_suffix;
		$this->$del_prefix = $del_prefix;
		$this->$del_suffix = $del_suffix;
		
        $this->_leading_context_lines = $context_lines;
        $this->_trailing_context_lines = $context_lines;
    }

    function _lines($lines)
    {
        foreach ($lines as $line) {
			echo "$line ";
			
			// FIXME: don't output space if it's the last line.
        }
    }

    function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
		return '';
    }

    function _startBlock($header)
    {
        echo $header;
    }

    function _added($lines)
    {
		echo '<span style="background:#cfc">';
		echo $this->ins_prefix;
        $this->_lines($lines);
		echo $this->ins_suffix;
		echo '</span>';
    }

    function _deleted($lines)
    {
    	echo '<span style="background:#fcc">';
		echo $this->del_prefix;
        $this->_lines($lines);
		echo $this->del_suffix;
		echo  '</span>';
    }

    function _changed($orig, $final)
    {
        $this->_deleted($orig);
        $this->_added($final);
    }

}
?>
