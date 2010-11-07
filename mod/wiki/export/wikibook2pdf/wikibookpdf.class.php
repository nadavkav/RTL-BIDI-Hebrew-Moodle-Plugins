<?php

    require_once('../../../../config.php');

    global $CFG;

    require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');

class wikibookpdf extends TCPDF {
	
		/**
		* @var the current tag is div
		* @access protected
		*/
		protected $tagdiv;

		/**
		* @var array of internal links
		* @access protected
		*/
		protected $linksin;

		/**
		* @var array of internal linksname
		* @access protected
		*/
		protected $linksinname;

		/**
		 * @var HTML PARSER:  State of the current list, is a vector (<ol> = True or <ul> = False).
		 * @access private		 */
		private $liststate;

		/**
		 * @var HTML PARSER: Position of the current state, index of the vector
		 * @access private		 */
		private $currentstate = -1;

		/**
		 * @var HTML PARSER: List of indexes that we use to complet <ol> lists
		 * @access private
		 */
		private $listorder;

		/**
		 * @var HTML PARSER: Position of the current ordered list
		 * @access private
		 */
		private $currentorder = -1;

		/**
		 * @var HTML PARSER: Is it a internal link
		 * @access private
		 */
		private $internallink = false;

		/**
		 * @var HTML PARSER: Is it a current position of the internal link
		 * @access private
		 */
		private $currentlink;

		/**
		 * @var HTML PARSER: true at the beginning of definition list
		 * @access private
		 */
		private $deflist = false;

public function wikibookpdf($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding="UTF-8")
{
    $this->TCPDF($orientation, $unit, $format, $unicode, $encoding);
}


// --- HTML PARSER FUNCTIONS ---
		
/**
 * Allows to preserve some HTML formatting.<br />
 * Supports: h1, h2, h3, h4, h5, h6, b, u, i, a, img, p, br, strong, em, font, blockquote, li, ul, ol, hr, td, th, tr, table, sup, sub, small
 * @param string $html text to display
 * @param boolean $ln if true add a new line after text (default = true)
 * @param int $fill Indicates if the background must be painted (1) or transparent (0). Default value: 0.
 */
public function writeWikibookHTML($html, $ln=true, $fill=0, $internallinks = false) {
			
	// store some variables
$html=strip_tags($html,"<h1><h2><h3><h4><h5><h6><b><u><i><a><img><p><br><br/><strong><em><font><blockquote><li><ul><ol><hr><td><th><tr><table><sup><sub><small><dl><dt><dd><div>"); //remove all unsupported tags
	//replace carriage returns, newlines and tabs
	$repTable = array("\t" => " ", "\n" => " ", "\r" => " ", "\0" => " ", "\x0B" => " "); 
	$html = strtr($html, $repTable);
	$pattern = '/(<[^>]+>)/Uu';
	$a = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); //explodes the string
			
	if (empty($this->lasth)) {
		//set row height
		$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO; 
	}

        $this->liststate = array();
        $this->listorder = array();
        $this->currentstate = -1;
        $this->currentorder = -1;
			
	foreach($a as $key=>$element) {
		if (!preg_match($pattern, $element)) {
			//Text
			if($this->HREF) {
				$this->addHtmlLink($this->HREF, $element, $fill);
			}
			elseif($this->tdbegin) {
				if((strlen(trim($element)) > 0) AND ($element != "&nbsp;")) {
					$this->Cell($this->tdwidth, $this->tdheight, $this->unhtmlentities($element), $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
				}
				elseif($element == "&nbsp;") {
					$this->Cell($this->tdwidth, $this->tdheight, '', $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
				}
			}
                        elseif($this->internallink && $internallinks){

                                $iden = $this->AddLink();
                                $this->SetLink($iden, $this->linksin[$this->currentlink][1], $this->linksin[$this->currentlink][0]);
                                //id, y, page

                                $this->SetTextColor(0, 0, 255);
                                $this->setStyle('u', true);
                                $this->Write($this->lasth, stripslashes($this->unhtmlentities($element)), $iden, $fill);
                                $this->setStyle('u', false);
                                $this->SetTextColor(0);


                       }
                       elseif($this->deflist){
                                            
                               $this->Write($this->lasth, stripslashes($this->unhtmlentities($element)), '', $fill);
                       }

                       elseif($this->tagdiv){

                               $this->SetTextColor(1, 5, 250);
                               $this->setStyle('b', true);
                               $this->Write($this->lasth, stripslashes($this->unhtmlentities($element)), '', $fill);
                               $this->setStyle('b', false);
                               $this->SetTextColor(0);
                        }
                           
			else {
				$this->Write($this->lasth, stripslashes($this->unhtmlentities($element)), '', $fill); 
                                              
			}

		} else {
			$element = substr($element, 1, -1);
			//Tag
			if($element{0}=='/') {
				$this->wikibook_closedHTMLTagHandler(strtolower(substr($element, 1)));
			}
			else {
				//Extract attributes
				// get tag name
				preg_match('/([a-zA-Z0-9]*)/', $element, $tag);
				$tag = strtolower($tag[0]);
				// get attributes
				preg_match_all('/([^=\s]*)=["\']?([^"\']*)["\']?/', $element, $attr_array, PREG_PATTERN_ORDER);
				$attr = array(); // reset attribute array
				while(list($id,$name)=each($attr_array[1])) {
					$attr[strtolower($name)] = $attr_array[2][$id];
				}
				$this->wikibook_openHTMLTagHandler($tag, $attr, $fill);
			}
		}
	}
	if ($ln) {
                $this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
		$this->Ln();
	}
        $this->liststate = NULL;
        $this->currentstate = -1;
        $this->listorder = NULL;
        $this->currentorder = -1;

}
		
		
/**
 * Process opening tags.
 * @param string $tag tag name (in uppercase)
 * @param string $attr tag attribute (in uppercase)
 * @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
 * @access private
 */
private function wikibook_openHTMLTagHandler($tag, $attr, $fill=0) {  

  global $CFG, $USER;
  //Opening tag
  switch($tag) {
	case 'table': {
                $this->Ln();
		if ((isset($attr['border'])) AND ($attr['border'] != '')) {
			$this->tableborder = $attr['border'];
		}
		else {
                        if ($attr['class'] == 'nwikitable')
                        {
                            $this->tableborder = 1;
                        }
                        else {  $this->tableborder = "0"; }
		}
		break;
	}
	case 'tr': {
		break;
	}
	case 'td':
	case 'th': {
		if ((isset($attr['width'])) AND ($attr['width'] != '')) {
			$this->tdwidth = ($attr['width']/4);
		}
		else {
			$this->tdwidth = (($this->w - $this->lMargin - $this->rMargin) / $this->default_table_columns);
		}
		if ((isset($attr['height'])) AND ($attr['height'] != '')) {
			$this->tdheight=($attr['height'] / $this->k);
		}
		else {
			$this->tdheight = $this->lasth;
		}
		if ((isset($attr['align'])) AND ($attr['align'] != '')) {
			switch ($attr['align']) {
				case 'center': {
					$this->tdalign = "C";
					break;
				}
				case 'right': {
					$this->tdalign = "R";
					break;
				}
				default:
				case 'left': {
					$this->tdalign = "L";
					break;
				}
			}
		}
		if ((isset($attr['bgcolor'])) AND ($attr['bgcolor'] != '')) {
			$coul = $this->convertColorHexToDec($attr['bgcolor']);
			$this->SetFillColor($coul['R'], $coul['G'], $coul['B']);
			$this->tdbgcolor=true;
		}
		$this->tdbegin=true;

                if ($tag == 'th') { $this->setStyle('b', true); }
               
		break;
	}
	case 'hr': {
		$this->Ln();
		if ((isset($attr['width'])) AND ($attr['width'] != '')) {
			$hrWidth = $attr['width'];
		}
		else {
			$hrWidth = $this->w - $this->lMargin - $this->rMargin;
		}
		$x = $this->GetX();
		$y = $this->GetY();
		$this->SetLineWidth(0.2);
		$this->Line($x, $y, $x + $hrWidth, $y);
		$this->SetLineWidth(0.2);
		$this->Ln();
		break;
	}
	case 'strong': {
		$this->setStyle('b', true);
		break;
	}
	case 'em': {
		$this->setStyle('i', true);
		break;
	}
	case 'b':
	case 'i':
	case 'u': {
		$this->setStyle($tag, true);
		break;
	}
	case 'a': {
                if ( strpos($attr['href'], '&amp;page=') !== false ) // Is it a internal link
                {
                    $url = split('&amp;', $attr['href']);

                    $i=0; $find = false;
                    while ($i < count($url) && !$find)
                   {
                         $a = split('=', $url[$i]);
                         $j=0;

                         while ($j < count($a))
                         {
                              if ($a[$j] == "page") { $name = $a[$j+1]; $find = true; break;}
                              $j++;      
                         }
                                            
                         $i++;
                   }

                   if ( isset($name) ) { $i= 1; $n = count($this->linksinname);
                                         while ( $i <= $n )
                                         {
                                              if ($this->linksinname[$i] == $name) {$this->currentlink = $i; 
                                                                                    $this->internallink = true; 
                                                                                    break;}
                                               $i++;
                                          }
                     }

              }
              elseif (strpos($attr['href'], '#') === 0) // is a anchor
              {
                  $this->HREF = '';
              }
              else 
              { $this->HREF = $attr['href']; }

              break;
	}
	case 'img': { 
		if(isset($attr['src']) && !($this->is_emoticon($attr['alt']))) {

                        $this->Ln();
                        // Process to get the image

                        $pos = strrpos($attr['src'], '.'); // to calculate extension
                        $ext = substr($attr['src'], $pos+1);
                        $pos = strrpos($attr['src'], '/'); //to calculate complet name
                        $name_i = substr($attr['src'], $pos+1);

                        
                        $pathfile = $CFG->dataroot.'/wikibook/'.$USER->id.'/'.$name_i; 
                        
                        if (isset($this->images[$pathfile])) 
                        {
                            $w_image_mm = $this->pixelsToMillimeters($this->images[$pathfile]['w']);
                            $space = ($this->fwPt - $this->lMargin - $this->rMargin)/ $this->k;
                            $space = $space - 20;

                            if ($space < $w_image_mm ) { $this->imgscale = $w_image_mm / $space; }
                            else { $this->imgscale = 1; }

                            $h_image_mm = $this->pixelsToMillimeters($this->images[$pathfile]['h']);

                            $space = $this->GetY() - (($this->tMargin + $this->bMargin) /$this->k);
                            $space = ($this->fhPt/$this->k) - $space;
                        
                            if ($space < $h_image_mm ) { $this->AddPage(); }

                        $this->Image($pathfile, $this->GetX(),$this->GetY(), null, null);

                           $this->SetY($this->img_rb_y);
                      //      $this->Write($this->lasth,'   ');

                            $this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;

                            break;
                        }

                        $image = fopen($attr['src'], 'r');
                        
                        if ($image === false) 
                        { $this->Error('It can not open the image: '.$attr['src']); fclose($image);}
                        else 
                        {
                          $pathfile = $CFG->dataroot.'/wikibook';

                          if (!is_dir($pathfile)) { mkdir($pathfile, 0777); }

                          $pathfile = $CFG->dataroot.'/wikibook/'.$USER->id;

                          if (!is_dir($pathfile)) { mkdir($pathfile, 0777); }

                          $pathfile = $CFG->dataroot.'/wikibook/'.$USER->id.'/'.$name_i; 

                          $my_image = fopen($pathfile, 'w+');

                          if ($my_image === false ) {fclose($my_image); fclose($image); $this->Error('It can not create image in disk.');}

                          else {

                              while (!feof($image))
                              {
                                  $cont = fread($image, 4096);
 
                                  if (fwrite($my_image, $cont, 4096)===false) 
                                  {    fclose($my_image); fclose($image);
                                       if (unlink($pathfile)=== false) {$this->Error('It can not remove the image');} 
                                       $this->Error('It can not write the image in disk.');
                                  } 
                              }


                              if(!isset($attr['width'])) {
                                     $attr['width'] = 0;
                              }
                              if(!isset($attr['height'])) {
                                     $attr['height'] = 0;
                              }

                              fclose($image); fclose($my_image);

                              $a=GetImageSize($pathfile);

                              if(empty($a)) { $this->Error('Missing or incorrect image file: '.$pathfile); }

                              $w_image_mm = $this->pixelsToMillimeters($a[0]);
                              $space = ($this->fwPt - $this->lMargin - $this->rMargin)/ $this->k;
                              $space = $space - 25;

                              if ($space < $w_image_mm ) { $this->imgscale = $w_image_mm / $space; }
                              else { $this->imgscale = 1; }

                            $h_image_mm = $this->pixelsToMillimeters($a[1]);

                            $space = $this->GetY() + (($this->tMargin + $this->bMargin) /$this->k);
                            $space = ($this->fhPt/$this->k) - $space;

                            if ($space < $h_image_mm ) { $this->AddPage();}
                             
                        $this->Image($pathfile, $this->GetX(),$this->GetY(), $this->pixelsToMillimeters($attr['width']), $this->pixelsToMillimeters($attr['height']));
                            $this->SetY($this->img_rb_y);
                      //      $this->Write($this->lasth,'   ');


                            $this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;

                            if (unlink($pathfile)=== false) {$this->Error('It can not remove the image');} 
                            }

                        }
		}

              elseif (isset($attr['alt'])){
                $this->Write($this->FontSize,' ');

                //emoticons
                $tam_emot = $this->FontSize;

                switch($attr['alt']){
                    case get_string('angry','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/angry.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('approve','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/approve.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('biggrin','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/biggrin.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('blackeye','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/blackeye.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('blush','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/blush.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('clown','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/clown.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('cool','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/cool.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('dead','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/dead.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('evil','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/evil.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('kiss','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/kiss.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('mixed','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/mixed.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('sad','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/sad.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('shy','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/shy.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('sleepy','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/sleepy.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('smiley','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/smiley.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('surprise','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/surprise.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('thoughtful','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/thoughtful.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('tongueout','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/tongueout.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('wideeyes','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/wideeyes.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    case get_string('wink','pix'):
                        $this->Image($CFG->dirroot.'/mod/wiki/images/wink.png',$this->GetX(),$this->GetY(),$tam_emot,$tam_emot);
                        break;
                    default:
                        break;
                }
                //Desplazo la posicion actual
                $this->Write($this->FontSize,'   ');

                }

		break;
		} // END OF IMG
	case 'ul': {
			
                      $this->currentstate++;
                      $this->liststate[$this->currentstate] = False;

                      break;
	}
	case 'ol': {
                      $this->currentstate++;
                      $this->liststate[$this->currentstate] = True;
                      $this->currentorder++;
                      $this->listorder[$this->currentorder] = 1;

                      break;

	}
	case 'li': {
		$this->Ln($this->lasth);
		
                if ($this->liststate[$this->currentstate]) // Ordered list
                      {
                          $depthorder = 0;
                          $i = $this->currentstate;
                          $is_ordered=true;
                          while ( ($is_ordered) && ($i >= 0) ) // Calculate the depth of the indexes
                          {
                               if ($this->liststate[$i]) { $depthorder++; $i--;}
                               else { $is_ordered=false; }
                          }

                          $number = $this->currentorder;
                          $index = "";
                          while($depthorder>0)
                          {
                                $index = $this->listorder[$number].".".$index;
                                $number--;
                                $depthorder--;
                          }                                            
                                            
                          $spaces = "";

                          for ($i=0; $i <= $this->currentstate; $i++) // Calculate the spaces
                          {
                               $spaces = $spaces."   ";
                          }

                          $this->lispacer = $spaces.$index;
                    }

                    else // Unordered list

                    { 
                          $spaces = "";

                          for ($i=0; $i <= $this->currentstate; $i++) // Calculate the spaces
                          {
                               $spaces = $spaces."   ";
                          }

                          $this->lispacer = $spaces."- ";

                    }

                $this->Write($this->lasth, $this->lispacer, '', $fill);

		break;
	}
	case 'blockquote':
	case 'br': {
		$this->Ln($this->lasth);
		if(strlen($this->lispacer) > 0) {
			$this->x += $this->GetStringWidth($this->lispacer);
		}
		break;
	}
	case 'p': {
		$this->Ln();
		$this->Ln();
		break;
	}
	case 'sup': {
		$currentFontSize = $this->FontSize;
		$this->tempfontsize = $this->FontSizePt;
		$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
		$this->SetXY($this->GetX(), $this->GetY() - (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
		break;
	}
	case 'sub': {
		$currentFontSize = $this->FontSize;
		$this->tempfontsize = $this->FontSizePt;
		$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
		$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
		break;
	}
	case 'small': {
		$currentFontSize = $this->FontSize;
		$this->tempfontsize = $this->FontSizePt;
		$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
		$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)/3));
		break;
	}
	case 'font': {
		if (isset($attr['color']) AND $attr['color']!='') {
			$coul = $this->convertColorHexToDec($attr['color']);
			$this->SetTextColor($coul['R'],$coul['G'],$coul['B']);
			$this->issetcolor=true;
		}
		if (isset($attr['face']) and in_array(strtolower($attr['face']), $this->fontlist)) {
			$this->SetFont(strtolower($attr['face']));
			$this->issetfont=true;
		}
		if (isset($attr['size'])) {
			$headsize = intval($attr['size']);
		} else {
			$headsize = 0;
		}
		$currentFontSize = $this->FontSize;
		$this->tempfontsize = $this->FontSizePt;
		$this->SetFontSize($this->FontSizePt + $headsize);
		$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
		break;
	}
	case 'h1': 
	case 'h2': 
	case 'h3': 
	case 'h4': 
	case 'h5': 
	case 'h6': {
		$headsize = (4 - substr($tag, 1)) * 2;
		$currentFontSize = $this->FontSize;
		$this->tempfontsize = $this->FontSizePt;
		$this->SetFontSize($this->FontSizePt + $headsize);
		$this->setStyle('b', true);
		$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
		break;
	}
	case 'dl': {
                $this->Ln();
                $this->deflist = true;
		break;
	}

	case 'dt': {
		$this->setStyle('b', true);
		break;
	}

	case 'dd': {
		$this->Write($this->lasth, '    ');
		break;
	}
	case 'div': {
                    $this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
		    $this->Ln();
                    $this->tagdiv = true;

		break;
	}

    }
}

		
/**
 * Process closing tags.
 * @param string $tag tag name (in uppercase)
 * @access private
 */
private function wikibook_closedHTMLTagHandler($tag) {
//Closing tag
switch($tag) {
	case 'td':
	case 'th': {
		$this->tdbegin = false;
		$this->tdwidth = 0;
		$this->tdheight = 0;
		$this->tdalign = "L";
		$this->tdbgcolor = false;
		$this->SetFillColor($this->prevFillColor[0], $this->prevFillColor[1], $this->prevFillColor[2]);

                if ($tag == 'th') { $this->setStyle('b', false); }

		break;
	}
	case 'tr': {
		$this->Ln();
		break;
	}
	case 'table': {
		$this->tableborder=0;
		break;
		}
	case 'strong': {
		$this->setStyle('b', false);
		break;
	}
	case 'em': {
		$this->setStyle('i', false);
		break;
	}
	case 'b':
	case 'i':
	case 'u': {
		$this->setStyle($tag, false);
		break;
	}
	case 'a': {
                      $this->currentlink = null; 
                      $this->internallink = false;
                      $this->HREF = '';
                      break;
	}
	case 'sup': {
		$currentFontSize = $this->FontSize;
		$this->SetFontSize($this->tempfontsize);
		$this->tempfontsize = $this->FontSizePt;
		$this->SetXY($this->GetX(), $this->GetY() - (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
		break;
	}
	case 'sub': {
		$currentFontSize = $this->FontSize;
		$this->SetFontSize($this->tempfontsize);
		$this->tempfontsize = $this->FontSizePt;
		$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
		break;
	}
	case 'small': {
		$currentFontSize = $this->FontSize;
		$this->SetFontSize($this->tempfontsize);
		$this->tempfontsize = $this->FontSizePt;
		$this->SetXY($this->GetX(), $this->GetY() - (($this->FontSize - $currentFontSize)/3));
		break;
	}
	case 'font': {
		if ($this->issetcolor == true) {
			$this->SetTextColor($this->prevTextColor[0], $this->prevTextColor[1], $this->prevTextColor[2]);
		}
		if ($this->issetfont) {
			$this->FontFamily = $this->prevFontFamily;
			$this->FontStyle = $this->prevFontStyle;
			$this->SetFont($this->FontFamily);
			$this->issetfont = false;
		}
		$currentFontSize = $this->FontSize;
		$this->SetFontSize($this->tempfontsize);
		$this->tempfontsize = $this->FontSizePt;
		//$this->TextColor = $this->prevTextColor;
		$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
		break;
	}
	case 'ul': {
                       $this->currentstate -= 1;
                       if ($this->currentstate == -1) {$this->Ln($this->lasth); }
                       break;

	}
        case 'ol': {
                       $this->currentstate -= 1;
                       $this->currentorder -= 1;
                       if ($this->currentstate == -1) {$this->Ln($this->lasth); }
                       break;

	}
	case 'li': {
                       if ($this->liststate[$this->currentstate]) { $this->listorder[$this->currentorder] += 1;}

                       $this->lispacer = "";
                       break;

	}
	case 'h1': 
	case 'h2': 
	case 'h3': 
	case 'h4': 
	case 'h5': 
	case 'h6': {
		$currentFontSize = $this->FontSize;
		$this->SetFontSize($this->tempfontsize);
		$this->tempfontsize = $this->FontSizePt;
		$this->setStyle('b', false);
		$this->Ln();
		$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
		break;
	}

	case 'dl': {
                $this->Ln();
                $this->deflist = false;
		break;
	}

	case 'dt': {
                $this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
                $this->Ln();
		$this->setStyle('b', false);
		break;
	}

	case 'dd': {
                $this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
                $this->Ln();
                break;
	}

	case 'div': {

                    $this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
		    $this->Ln();
                    $this->tagdiv = false;
	}

	default : {
		break;
	}
    }
}



/**
 * Is it a emoticon?
 * @param string $alt the name of the emoticon if it is
 * @param string $src the url of the image
 * @access private
 */

private function is_emoticon( $alt )
{
if(($alt == get_string('angry','pix')) || ($alt == get_string('approve','pix')) ||($alt == get_string('biggrin','pix')) ||($alt == get_string('blackeye','pix')) ||     ($alt == get_string('blush','pix')) || ($alt == get_string('clown','pix')) || ($alt == get_string('cool','pix')) || ($alt == get_string('dead','pix')) ||($alt == get_string('evil','pix')) ||($alt == get_string('kiss','pix')) ||($alt == get_string('mixed','pix')) ||($alt == get_string('sad','pix')) ||($alt == get_string('shy','pix')) || ($alt == get_string('sleepy','pix')) || ($alt == get_string('smiley','pix')) || ($alt == get_string('surprise','pix')) || ($alt == get_string('thoughtful','pix')) || ($alt == get_string('tongueout','pix')) || ($alt == get_string('wideeyes','pix')) || ($alt == get_string('wink','pix')))

{$it_is = true;}

else {$it_is = false; }

  return $it_is;

}

/**
 * Add array of the images. Only it use with copy_images.
 * @param string $tag tag name (in lowercase)
 * @param boolean $enable
 * @access public
 */

public function setImages(&$a)
{

    $this->images = $a;

}


/**
 * Return the array of the images
 * @access public
 */

public function getImages()
{

   return $this->images;

}


/**
 * Creates a new internal link for associate to wiki page and returns its identifier. 
 * An internal link is a clickable area which directs to another place within the document.<br />
 * The identifier can then be passed to Cell(), Write(), Image() or Link(). The destination is defined with SetLink().
 * @since 1.5
 * @see Cell(), Write(), Image(), Link(), SetLink()
 */
    public function AddLinkin() {
        //Create a new internal link
        $n=count($this->linksin)+1;
        $this->linksin[$n]=array(0,0);
        return $n;
    }

/**
 * Defines the page and position a link points to and pointed nwiki page 
 * @param int $link The link identifier returned by AddLink()
 * @param int $namepage The name of the wiki page		
 * @param float $y Ordinate of target position; -1 indicates the current position. The default value is 0 (top of page)
 * @param int $page Number of target page; -1 indicates the current page. This is the default value
 * @since 1.5
 * @see AddLink()
 */
    public function SetLinkinName($link, $namepage, $y=0, $page=-1) {
        //Set destination of internal link
        if($y==-1) {
                $y=$this->y;
        }
        if($page==-1) {
                $page=$this->page;
        }
            $this->linksin[$link]=array($page,$y);
            $this->linksinname[$link]=$namepage;
        }

/**
 * Return the array of linksname 
 * @since 1.5
 * @see AddLink()
 */
    public function getLinkinName() {
        return $this->linksinname;
    }

/**
 * Return the array of links 
 * @since 1.5
 * @see AddLink()
 */
    public function getLinkin() {
        return $this->linksin;
    }


/**
 * Copy in our arrays the caculate arrays to do the final PDF 
 * @param array of strings $y Ordinate of target position; -1 indicates the current position. The default value is 0 (top of page)
 * @param array of int (page number)
 * @since 1.5
 * @see AddLink()
 */
    public function CopyLinkinName(&$a_names, &$a_pages) {

        unset($this->linksinname); unset($this->linksin);

        $this->linksinname = array();
        $this->linksin = array();

        $this->linksinname = $a_names;
        $this->linksin = $a_pages;
    }


/**
 * Set a flag to print page header.
 * @param boolean $val set to true to print the page header (default), false otherwise. 
*/
public function setPrintHeader($val=true) {
	$this->print_header = $val;
}

/**
 * Set a flag to print page footer.
 * @param boolean $value set to true to print the page footer (default), false otherwise. 
*/
public function setPrintFooter($val=true) {
	$this->print_footer = $val;
}


}
//============================================================+
// END OF FILE
//============================================================+
?>
