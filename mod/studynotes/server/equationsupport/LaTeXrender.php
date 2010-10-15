<?php
/**
 * LaTeX Rendering Class
 * Copyright (C) 2003  Benjamin Zeiss <zeiss@math.uni-goettingen.de>
 * Copyright (C) 2008-2009  Fabian Gebert <fabiangebert@mediabird.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * --------------------------------------------------------------------
 * @author Benjamin Zeiss <zeiss@math.uni-goettingen.de>
 * @author Fabian Gebert <fabiangebert@mediabird.net>
 * @version v1.0
 * @package latexrender
 *
 */

class LatexRender {

	// ====================================================================================
	// Variable Definitions
	// ====================================================================================
	var $_picture_path = "";
	var $_picture_path_httpd = "";
	var $_tmp_dir = "";
	// i was too lazy to write mutator functions for every single program used
	// just access it outside the class or change it here if nescessary
	var $_latex_path = "/usr/bin/latex";
	var $_convert_path = "/usr/bin/dvipng";
	var $_formula_density = 120;
	var $_xsize_limit = 600;
	var $_ysize_limit = 500;
	var $_string_length_limit = 500;
	var $_font_size = 10;
	var $_latexclass = "article"; //install extarticle class if you wish to have smaller font sizes
	var $_tmp_filename;
	var $_image_format = "png"; //change to png if you prefer
	// this most certainly needs to be extended. in the long term it is planned to use
	// a positive list for more security. this is hopefully enough for now. i'd be glad
	// to receive more bad tags !
	var $_latex_tags_blacklist = array (
	"include", "def", "command", "loop", "repeat", "open", "toks", "output", "input",
	"catcode", "name", "^^",
	"\\every", "\\errhelp", "\\errorstopmode", "\\scrollmode", "\\nonstopmode", "\\batchmode",
	"\\read", "\\write", "csname", "\\newhelp", "\\uppercase", "\\lowercase", "\\relax", "\\aftergroup",
	"\\afterassignment", "\\expandafter", "\\noexpand", "\\special"
	);
	var $_errorcode = 0;
	var $_errorextra = "";
	var $_cachefiles = 1;
	var $_extraname = ""; //adds time to stop images being cached by IE

	var $destinationFile;

	// ====================================================================================
	// constructor
	// ====================================================================================

	/**
	 * Initializes the class
	 *
	 * @param string path where the rendered pictures should be stored
	 * @param string same path, but from the httpd chroot
	 */
	function LatexRender($picture_path, $picture_path_httpd, $tmp_dir) {
		$this->_picture_path = $picture_path;
		$this->_picture_path_httpd = $picture_path_httpd;
		$this->_tmp_dir = $tmp_dir;
		$this->_tmp_filename = md5(rand());
	}

	// ====================================================================================
	// public functions
	// ====================================================================================

	/**
	 * Picture path Mutator function
	 *
	 * @param string sets the current picture path to a new location
	 */
	function setPicturePath($name) {
		$this->_picture_path = $name;
	}

	/**
	 * Picture path Mutator function
	 *
	 * @returns the current picture path
	 */
	function getPicturePath() {
		return $this->_picture_path;
	}

	/**
	 * Picture path HTTPD Mutator function
	 *
	 * @param string sets the current httpd picture path to a new location
	 */
	function setPicturePathHTTPD($name) {
		$this->_picture_path_httpd = $name;
	}

	/**
	 * Picture path HTTPD Mutator function
	 *
	 * @returns the current picture path
	 */
	function getPicturePathHTTPD() {
		return $this->_picture_path_httpd;
	}

	/**
	 * Checks if a formula is available from cache
	 *
	 * @param string formula in LaTeX format
	 * @returns the file's path if successful, null otherwise
	 */
	function checkFormulaCache($latex_formula) {
		// circumvent certain security functions of web-software which
		// is pretty pointless right here
		$latex_formula = preg_replace("/&gt;/i", ">", $latex_formula);
		$latex_formula = preg_replace("/&lt;/i", "<", $latex_formula);

		$formula_hash = md5($latex_formula).$this->_extraname;

		$filename = $formula_hash.".".$this->_image_format;
		$full_path_filename = $this->getPicturePath().$filename;

		// check if there's a file with the same md5 hash (first 32 characters)
		// either delete it or use it
		$handler = opendir($this->_picture_path);
		// keep going until all files in directory have been read
		while ($file = readdir($handler)) {
			if (Substr($file, 0, 32).Substr($file, strlen($file)-4, 4) == Substr($filename, 0, 32).Substr($filename, strlen($filename)-4, 4)) {
				$filename = $file;
				$full_path_filename = $this->getPicturePath().$filename;
				break;
			}
		}
		// tidy up: close the handler
		closedir($handler);


		if (is_file($full_path_filename)) {
			return $filename;
		}
		else {
			return null;
		}
	}

	// ====================================================================================
	// private functions
	// ====================================================================================

	/**
	 * wraps a minimalistic LaTeX document around the formula and returns a string
	 * containing the whole document as string. Customize if you want other fonts for
	 * example.
	 *
	 * @param string formula in LaTeX format
	 * @returns minimalistic LaTeX document containing the given formula
	 */
	function wrap_formula($latex_formula) {
		$string = "\documentclass[".$this->_font_size."pt]{".$this->_latexclass."}\n";
		$string .= "\usepackage[latin1]{inputenc}\n";
		$string .= "\usepackage{amsmath}\n";
		$string .= "\usepackage{amsfonts}\n";
		$string .= "\usepackage{amssymb}\n";
		$string .= "\usepackage[all]{xy}\n";
		$string .= "\pagestyle{empty}\n";
		$string .= "\begin{document}\n";
		$string .= $latex_formula."\n";
		$string .= "\end{document}\n";

		return $string;
	}

	/**
	 * Renders a LaTeX formula by the using the following method:
	 *  - write the formula into a wrapped tex-file in a temporary directory
	 *    and change to it
	 *  - Create a DVI file using latex (tetex)
	 *  - Convert DVI file to PNG using dvipng, trim and add transparancy
	 *  - Save the resulting image to the picture cache directory using an
	 *    md5 hash as filename. Already rendered formulas can be found directly
	 *    this way.
	 *
	 * @param string LaTeX formula
	 * @returns path to picture or null if no success
	 */
	function renderLatex($latex_formula) {
		$latex_document = $this->wrap_formula($latex_formula);

		$current_dir = getcwd();

		chdir($this->_tmp_dir);

		// create temporary latex file
		$fp = fopen($this->_tmp_filename.".tex", "w");
		fputs($fp, $latex_document);
		fclose($fp);


		// create temporary dvi file
		$command = '"'.$this->_latex_path.'" --interaction=nonstopmode '.$this->_tmp_filename.'.tex';
		$status_code = exec($command);

		if (!$status_code) {
			chdir($current_dir);
			$this->cleanTemporaryDirectory();
			$this->_errorcode = 4;
			$this->_errorextra = ": Cannot create DVI file";
			return null;
		}

		// imagemagick convert ps to image and trim picture
		$command = '"'.$this->_convert_path.'" -D '.$this->_formula_density.
		' -T tight -z 6 -bg Transparent '.$this->_tmp_filename.'.dvi -o '.
		$this->_tmp_filename.'.'.$this->_image_format;

		$status_code = exec($command);

		// copy temporary formula file to cached formula directory
		$latex_hash = md5($latex_formula).$this->_extraname;
		$filename = $this->getPicturePath().$latex_hash.".".$this->_image_format;

		$this->destinationFile = $filename;

		chdir($current_dir);

		return $this->_tmp_dir.$this->_tmp_filename.".".$this->_image_format;
	}

	/**
	 * Cleans the temporary directory up
	 */
	function cleanTemporaryDirectory() {
		$this->unlinkIf($this->_tmp_dir.$this->_tmp_filename.".tex");
		$this->unlinkIf($this->_tmp_dir.$this->_tmp_filename.".aux");
		$this->unlinkIf($this->_tmp_dir.$this->_tmp_filename.".log");
		$this->unlinkIf($this->_tmp_dir.$this->_tmp_filename.".dvi");
		$this->unlinkIf($this->_tmp_dir.$this->_tmp_filename.".".$this->_image_format);
	}

	/**
	 * Helper function to unlink a file only if it exists
	 * @param string $file
	 */
	function unlinkIf($file) {
		if (file_exists($file)) {
			unlink($file);
		}
	}
}
?>
