<?php

//Wiki2PDF feature for NWiki Module (new DFWiki)
//Author: Manuel Carrasco Pacheco (DFWikiteam)
//Created on September 2006

    require_once('../../config.php');
    require_once('lib.php');
    require_once('locallib.php');
    require_once('../../lib/fpdf/fpdf.php');
	//html functions
	require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

    class wikitopdf extends FPDF {
        var $B;
        var $I;
        var $U;
        var $HREF;
        var $font;
        var $fontsize;
        var $wikilinks;
        var $actualPageNumber;
        var $openTable;
        var $openRow;
        var $openColumn;

        var $widths;
        var $aligns;

        var $textRow;
        var $amplada_columna;
        var $num_ol;
        var $num_ul;
        var $num_li;

        var $cm_id;
        var $course_id;
        var $num_TOC;
        var $num_DIV;
        var $is_text_DIV;
        var $page_num_TOC;


         function wikitopdf($orientation='P',$unit='mm',$format='A4',$FONT='arial',$FONTSIZE, $PAGENUMBER, $PAGENAME, $CM_ID, $COURSE_ID){
            //Llama al constructor de la clase padre
            $this->FPDF($orientation,$unit,$format);
            //Iniciaci?n de variables
            $this->B=0;
            $this->I=0;
            $this->U=0;
            $this->HREF='';
            $this->fontsize=$FONTSIZE;
            $this->font=$FONT;
            $this->wikilinks->pagenumber = $PAGENUMBER;
            $this->wikilinks->pagename = $PAGENAME;
            //Posiciones 0 sin valores (sino habra problemas en la funcion SearchLink())
            array_push($this->wikilinks->pagenumber, 0);
            array_push($this->wikilinks->pagename, null);
            $this->actualPageNumber = 1;

            $this->openTable = false;
            $this->openRow = false;
            $this->openColumn = false;
            $this->amplada_columna = array();
            $this->textRow = array();
            $this->num_ol = 0;
            $this->num_ul = 0;
            $this->num_li = array();
            $this->cm_id = $CM_ID;
            $this->course_id = $COURSE_ID;
            $this->num_TOC = array();
            $this->page_num_TOC = 0;
            $this->num_DIV = 0;
            $this->is_text_DIV = false;
        }

        function WriteHTML($html){

            //Int?rprete de HTML
            $html=str_replace("\n",' ',$html);
            $a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);

            //posem a 0 el num de TOCs
            $this->page_num_TOC = 0;

            foreach($a as $i=>$e){

                if($i%2==0){
                //Text
                    //eliminem les aparicions de TABLE OF CONTENTS en tags amb DIV
                    //(ja s'escriuran en el seu moment):
                    if ($this->is_text_DIV && $e=='TABLE OF CONTENTS:'){
                           $e = null;
                    }
                        //sustituyo los &nbsp de $e por espacios
                        $e = str_replace('&nbsp;',' ',$e);

                        //elimino $e que contenga apariciones de '="tocX'
                        if(strpos($e,'"toc') && is_numeric($e[5])){
                            $e = null;
                        }

                        if($this->openTable == true){
                        //A table is opened
                            $e = trim($e);
                            //No ponemos $e='?' en la tabla de los enlaces wiki.
                            //Falta programar enlaces wiki en la tabla
                            if($e != null && $e != '?'){
                                //guardo el texto
                                array_push($this->textRow, $e);
                            }

                            if($this->openRow == false){
                            //Se ha cerrado la fila, la pintamos:
                                   //tama?o de columnas
                                   $num_columnes = sizeOf($this->textRow);
                                   $amplada_foli = 150;
                                   for ($i=0; $i < $num_columnes; $i++){
                                        array_push($this->amplada_columna, $amplada_foli/$num_columnes);
                                   }
                                   $this->SetWidths($this->amplada_columna);

                                   $this->Row($this->textRow);

                                   //reinicio textRow i $amplada_columna
                                   $this->textRow = array();
                                   $this->amplada_columna = array();
                            }
                        }else {
                        //No table is opened
                            if($this->HREF){
                                if($e != '?'){
                                    $this->PutLink($this->HREF,$e);
                                }
                            }
                            else if($this->U){
                                //enlace wiki
                                $this->PutLink(false,$e);
                            }
                            else {
                                $this->Write($this->fontsize/2,$e);
                            }
                        }
                }else {
                //Etiqueta
                    if($e{0}=='/'){
                        $this->CloseTag(strtoupper(substr($e,1)));
                    }else {
                        //Extraer atributos
                        $a2=explode(' ',$e);
                        $tag=strtoupper(array_shift($a2));
                        $attr=array();
                        foreach($a2 as $v){
                            if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3)){
                                $attr[strtoupper($a3[1])]=$a3[2];
                            }
                        }
                        $this->OpenTag($tag,$attr);
                    }
                }
            }
        }

        function OpenTag($tag,$attr){
            global $CFG;
            //Etiqueta de apertura
            if($tag=='B' or $tag=='I' or $tag=='U')
                $this->SetStyle($tag,true);
            if($tag=='A'){
                if (isset($attr['HREF'])){
					$this->HREF=$attr['HREF'];
                }
                if (isset($attr['HREF'][0])){
                //si es un enlace '#toc':
				if($attr['HREF'][0] == '#'){
	                    //vector que guarda tots els TOCS
	                    array_push($this->num_TOC, $attr['HREF']);
	                }
                }
            }
            if($tag=='BR')
                $this->Ln($this->fontsize/4);
            if($tag=='P')
                $this->Ln($this->fontsize);
            if($tag=='H1') {
                $this->SetFontSize($this->fontsize+10);
                $this->Ln($this->fontsize);
            }
            if($tag=='H2') {
                $this->SetFontSize($this->fontsize+4);
                $this->Ln($this->fontsize);
            }
            if($tag=='H3') {
                $this->SetFontSize($this->fontsize*2);
                $this->Ln($this->fontsize);
            }
            if($tag=='HR'){
                $this->Ln($this->fontsize/1);
                $this->SetDrawColor(175,175,175);
                $this->SetLineWidth(0.7);
                $x0 = $this->GetX();
                $y0 = $this->GetY();
                //Pinto la linea recta horizontal
                $longitud = 150;
                $this->Line($x0,$y0,$x0+$longitud,$y0);
                //$this->Ln($this->fontsize);
            }
            if ($tag=='DIV'){
                $this->is_text_DIV = true;
                $this->Ln($this->fontsize/2);
                $this->SetFontSize($this->fontsize-4);
            }

            if ($tag=='TABLE'){
                $this->openTable = true;
            }
            if ($tag=='TR'){
                $this->openRow = true;
            }
            if ($tag=='TD'){
                $this->openColumn = true;
            }
            if ($tag=='OL'){
                $this->num_ol++;
            }
            if ($tag=='UL'){
                $this->num_ul++;
            }
            //llistes numerades
            if ($tag=='LI' && $this->num_ol > 0){
                $this->num_li[$this->num_ol]++;
                $this->Ln($this->fontsize/2);
                for ($i=0; $i < $this->num_ol-1; $i++){
                    $this->Write($this->fontsize/2,'     ');
                }
                $this->Write($this->fontsize/2,$this->num_li[$this->num_ol].'. ');

            }
            //llistes no numerades
            if ($tag=='LI' && $this->num_ul > 0){
                if(isset($this->num_li[$this->num_ul])){
					$this->num_li[$this->num_ul]++;
                }
                $this->Ln($this->fontsize/2);

                //canviem a font de simbols
                $this->SetFont('ZapfDingBats','','');
                $this->SetFontSize($this->fontsize - 4);

                for ($i=0; $i < $this->num_ul-1; $i++){
                    $this->Write($this->fontsize/2,'           ');
                }
                if ($this->num_ul == 1){
                    //pinta una rodona negra
                    $this->Write($this->fontsize/2,'l ');
                }
                else if ($this->num_ul == 2){
                    //pinta una rodona blanca
                    $this->Write($this->fontsize/2,'m ');
                }
                else if ($this->num_ul >= 3){
                    //pinta un quadrat negre
                    $this->Write($this->fontsize/2,'n ');
                }
                $this->SetFont($this->font,'','');
                $this->SetFontSize($this->fontsize);
            }
            if($tag=='IMG'){
                $this->Write($this->fontsize/2,' ');

                //emoticons
                $tam_emot = $this->fontsize/3;
                switch($attr['ALT']){
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
                $this->Write($this->fontsize/2,'   ');
            }
            if ($tag=='IMG'&& ($attr['SRC']==$attr['ALT'])){

                $this->DrawImage($attr['SRC']);

            }
            if (isset($attr['CLASS']) && $tag=='IMG'&& $attr['CLASS']){
                $this->Image($CFG->dirroot.'/mod/wiki/images/f2.png',$this->GetX(),$this->GetY(),$this->fontsize/2,$this->fontsize/2);
                //Desplazo la posicion actual
                $this->Write($this->fontsize/2,'    ');
            }
        }

        function CloseTag($tag){
            //Etiqueta de cierre
            if($tag=='B' or $tag=='I' or $tag=='U')
                $this->SetStyle($tag,false);
            if($tag=='A')
                $this->HREF='';
            if($tag=='H1')
                $this->SetFontSize($this->fontsize);
            if($tag=='H2')
                $this->SetFontSize($this->fontsize);
            if($tag=='H3')
                $this->SetFontSize($this->fontsize);
            if ($tag=='DIV'){
                $this->is_text_DIV = false;
                $this->SetFontSize($this->fontsize);
                //Si no hi ha tocs afegits a la pagina, pujem el cursor
                //per treure l'espai en blanc que deixaria el table contents
                if($this->page_num_TOC == 0){
                    $this->Ln(-$this->fontsize);
                }
            }
            if ($tag=='TABLE'){
                $this->openTable = false;
            }
            if ($tag=='TR'){
                $this->openRow = false;
            }
            if ($tag=='TD'){
                $this->openColumn = false;
            }
            if ($tag=='OL'){
                $this->num_li[$this->num_ol] = 0;
                $this->num_ol--;
            }
            if ($tag=='UL'){
                $this->num_li[$this->num_ul] = 0;
                $this->num_ul--;
            }
        }

        function SetStyle($tag,$enable){
            //Modificar estilo y escoger la fuente correspondiente
            //cursiva, italica,...
            $this->$tag+=($enable ? 1 : -1);
            $style='';
            foreach(array('B','I','U') as $s)
                if($this->$s>0)
                    $style.=$s;
            $this->SetFont('',$style);
        }

        function PutLink($URL,$txt){
            global $CFG;

            //Escribir un hiper-enlace
            $this->SetTextColor(0,0,255);
            $this->SetStyle('U',true);

            if ($URL != false && $URL[0] != '#'){
                //si es por url:
                $this->Write($this->fontsize/2,$txt,$URL);
            }
            else if($URL[0]=='#' && $URL[1]=='t'){
                //si es un enlace #toc
                //Cal que comprovem que el #toc nomes ha aparegut un cop
                $num_aparicions_toc = sizeOf(array_keys($this->num_TOC, $URL));
                if ($num_aparicions_toc == 1){

                    //escrivim el 'TABLE PAGE CONTENTS' quan no hi hagi cap toc a la pagina
                    if($this->page_num_TOC == 0){
                        $this->SetTextColor(0);
                        $this->SetStyle('U',false);
                        $this->SetX($this->lMargin);
                        $this->Write($this->fontsize/2,'TABLE OF CONTENTS:');
                        $this->Ln($this->fontsize/2);
                        $this->SetTextColor(0,0,255);
                        //tabulem 3 espais pel primer element de la taula
                        $this->Write($this->fontsize/2, '   ');
                        $this->SetStyle('U',true);
                    }

                    //incrementa el numero de TOCs a la pagina
                    $this->page_num_TOC++;

                    /*falta arreglar el numero que s'imprimeix
                    $pos =strpos ($txt,'.');
                    $txt = substr($txt, $pos+1);
                    $this->Write($this->fontsize/2,'-');
                    $this->Write($this->fontsize/2,$txt);
                    */

                    //escribim l'enlla? toc
                    $this->Write($this->fontsize/2,$txt);
                } else{
                    //Si no llavors no el posem i pujem cap amunt el cursor
                    $this->Ln(-$this->fontsize/4);
                }
            }
            else {
                //si es un attach:
                if($txt[0] == '/'){
                    $pos_name = strlen($txt)/2;
                    $name_file=substr($txt,$pos_name+1);
                    $pos=strrpos($name_file,'.');
                    $type=substr($name_file,$pos+1);
                    //si l'attach es una imatge jpeg o png:
                    if($type=='jpeg' or $type=='jpg' or $type=='png'){
                        //no funciona:$this->DrawImage($CFG->wwwroot.'/file.php/'.$this->course_id.'/moddata/dfwiki'.$this->cm_id.'/'.$name_file);
                        //ens surt el seguent error: FPDF error: Missing or incorrect image
                        //funciona:
                        $this->DrawImage($CFG->dataroot.'/'.$this->course_id.'/moddata/dfwiki'.$this->cm_id.'/'.$name_file);
                    }else {
                        //si es un altre tipus de fitxer diferent a imatges:
                        $this->Write($this->fontsize/2, $name_file, $CFG->wwwroot.'/file.php/'.$this->course_id.'/moddata/dfwiki'.$this->cm_id.'/'.$name_file);
                    }
                } else{
                //si es link interno:
                    //busco el link
                    $internal_link = $this->SearchLink($txt);

                    if ($internal_link != false){
                        //text amb link
                        $this->Write($this->fontsize/2,$txt,$internal_link);

                    }else{
                        //text sense link
                        $this->SetTextColor(0,0,0);
                        $this->Write($this->fontsize/2,$txt);
                        $this->SetTextColor(0,0,255);
                        $this->Write($this->fontsize/2,'?');
                    }
                }
            }
            $this->SetStyle('U',false);
            $this->SetTextColor(0);
        }

        function addLinkPage($pageNumber, $pagename){
            array_push($this->wikilinks->pagenumber, $pageNumber);
            array_push($this->wikilinks->pagename, $pagename);

            return $this->wikilinks;
        }

        function SearchLink($txt){
            //busca el texto $txt en el vector $this->wikilinks->pagename
            $posicion = array_search($txt, $this->wikilinks->pagename);
            $res = $this->wikilinks->pagenumber[$posicion];
            //Si array_search retorna false, $res cogeria la posicion 0 del vector,
            //con un determinado valor. Esto se ha solucionado asignando a null
            //la posicion 0 en la constructora, y el vector real comenzaria en la posicion 1.

            if ($res > 0){
                return $res;
            }else{
                //array_search ha devuelto false
                return false;
            }
        }
        function setPageZero(){
            return $this->page = 0;
        }

        function DrawImage($url_file){
                //agafem l'extensio del fitxer imatge
                $pos=strrpos($url_file,'.');
                $type=substr($url_file,$pos+1);
                //agafem el nom del fitxer
                $pos_name=strrpos($url_file,'/');
                $name_img=substr($url_file,$pos_name+1);
                //fpdf nomes suporta imatges png, jpeg i jpg
                if($type=='jpeg' || $type=='jpg' || $type=='png'){
                    $this->Image($url_file,$this->GetX(),$this->GetY(),'','');

                    //$this->k guarda el factor d'escala de la imatge en la classe pare
                    $amplada_img = $this->images[$url_file][w]/$this->k;
                    $altura_img = $this->images[$url_file][h]/$this->k;

                    //comprovar que no surt per sota de la pagina
                    if(($altura_img + $this->GetY()) > 279){
                        //Pintem un rectangle en blanc sobre la imatge
                        $this->SetFillColor(255,255,255);
                        $this->Rect($this->GetX(),$this->GetY(),$amplada_img, $altura_img, 'F');

                        //tornem a pintar la imatge en la seguent pagina
                        $this->SetY($altura_img + $this->GetY());
                        $this->Write($this->fontsize/2,' ');
                        $this->Image($url_file,$this->GetX(),$this->GetY(),'','');
                        //Ajustant la posicio actual
                        $this->SetXY($altura_img + $this->GetX() + $this->lMargin - $this->fontsize/4, $amplada_img + $this->GetY() - $this->tMargin - $this->fontsize/2);

                    }else{
                    //si no surt fora actualitzo la posicio actual
                        $this->SetXY($amplada_img + $this->GetX(), $altura_img + $this->GetY() - $this->fontsize/2);
                    }
                }else{
                 //sino posem la direccio url de la imatge:
                    $this->SetTextColor(0,0,255);
                    $this->SetStyle('U',true);
                    $this->Write($this->fontsize/2,$name_img,$url_file);
                    $this->SetStyle('U',false);
                    $this->SetTextColor(0);
                }
        }



        //Pie de p?gina
        function Footer()
        {
            //Posici?n: a 2 cm del final
            $this->SetY(-20);
            //Arial italic 8
            $this->SetFont('Arial','',8);
            //N?mero de p?gina
            $this->Cell(0,10,$this->PageNo(),0,0,'C');
            $this->actualPageNumber = $this->PageNo();

        }

        //-----------------FUNCIONES PARA TABLAS-----------///
        function SetWidths($w)
        {
            //Set the array of column widths
            $this->widths=$w;
        }

        function SetAligns($a)
        {
            //Set the array of column alignments
            $this->aligns=$a;
        }

        function Row($data)
        {
            //Calculate the height of the row
            $nb=0;
            for($i=0;$i<count($data);$i++)
                $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
            $h=5*$nb;
            //Issue a page break first if needed
            $this->CheckPageBreak($h);
            //Draw the cells of the row
            for($i=0;$i<count($data);$i++)
            {
                $w=$this->widths[$i];
                $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
                //Save the current position
                $x=$this->GetX();
                $y=$this->GetY();
                //Draw the border
                $this->Rect($x,$y,$w,$h);
                //Print the text
                $this->MultiCell($w,5,$data[$i],0,$a);
                //Put the position to the right of the cell
                $this->SetXY($x+$w,$y);
            }
            //Go to the next line
            $this->Ln($h);
        }

        function CheckPageBreak($h)
        {
            //If the height h would cause an overflow, add a new page immediately
            if($this->GetY()+$h>$this->PageBreakTrigger)
                $this->AddPage($this->CurOrientation);
        }

        function NbLines($w,$txt)
        {
            //Computes the number of lines a MultiCell of width w will take
            $cw=&$this->CurrentFont['cw'];
            if($w==0)
                $w=$this->w-$this->rMargin-$this->x;
            $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
            $s=str_replace("\r",'',$txt);
            $nb=strlen($s);
            if($nb>0 && $s[$nb-1]=="\n")
                $nb--;
            $sep=-1;
            $i=0;
            $j=0;
            $l=0;
            $nl=1;
            while($i<$nb)
            {
                $c=$s[$i];
                if($c=="\n")
                {
                    $i++;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                    continue;
                }
                if($c==' ')
                    $sep=$i;
                $l+=$cw[$c];
                if($l>$wmax)
                {
                    if($sep==-1)
                    {
                        if($i==$j)
                            $i++;
                    }
                    else
                        $i=$sep+1;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                }
                else
                    $i++;
            }
            return $nl;
        }
    }

    /****** Funcio  que converteix wikis (en format DFWiki-html) a pdf *************************************************/
    function wiki_convert_wiki_to_pdf(&$textwiki, $pagenamewiki, $id, $course_id, $uid, $gid, $dfw, $version, $pagename, $font, $size){
		global $WS;

        $formateditor = "dfwiki";
        $size_textwiki = sizeOf($textwiki);

        $wikilinks->pagenumber = array();
        $wikilinks->pagename = array();

        $pdf=new wikitopdf('P','mm','A4',$font, $size,$wikilinks->pagenumber, $wikilinks->pagename, $id, $course_id);
        $pdf->SetTopMargin(25);
        $pdf->SetLeftMargin(30);
        $pdf->SetRightMargin(30);


        //add a wikilinks array
        //links of every wiki page with his pagename and his initial number of pages
        for($i=0; $i < $size_textwiki; $i++){

            unset($texthtml);
            if($textwiki[$i] != null){


                //page text
                $texthtml = wiki_parse_text($textwiki[$i],$formateditor);
                $pdf->AddPage();

                //add texthtml to an array(for avoid another wiki_parse_text at the next 'for')
                $textos_wiki_html[$i]= $texthtml;

                 //add the internal wiki link for the initial wiki page
                $wikilinks = $pdf->addLinkPage($pdf->PageNo(), $pagenamewiki[$i]);

                $pdf->SetFont($font,'','');

                //modify title size to the choosen
                $pdf->SetFontSize($size*2);


                $pdf->Write('',$pagenamewiki[$i]);
                $pdf->Ln($size*1);

                //text size
                $pdf->SetFontSize($size);

                //parsing html in wikitopdf class
                $pdf->WriteHTML($texthtml);


            }else{
                $textos_wiki_html[$i]= null;
            }
            //save the number of pages of pdf
            $num_total_pags = $pdf->PageNo();
        }


        //Pdf with internal links calculated:
        $pdf->Close();

        //Create the new pdf to write in
        $pdf = new wikitopdf('P','mm','A4', $font, $size, $wikilinks->pagenumber, $wikilinks->pagename, $id, $course_id);
        $pdf->SetTopMargin(25);
        $pdf->SetLeftMargin(30);
        $pdf->SetRightMargin(30);

    	//Define in the pdf created the pages that will have a wiki link
        for($i=1; $i <= $num_total_pags; $i++){
            $pdf->AddPage();
            $pdf->SetLink($i);
            if (array_search($i, $wikilinks->pagenumber)){
                $pdf->SetLink($i);
            }
        }

        $pdf->setPageZero();

        //add to every wikilink a link to the page that is referring and print the final pdf
        for($i=0; $i < $size_textwiki; $i++){
            if($textos_wiki_html[$i] != null){

                //page text
                $texthtml = wiki_parse_text($textwiki[$i],$formateditor,$WS);

                $pdf->AddPage();

                $pdf->SetFont($font,'','');

                //modify title size to the choosen
                $pdf->SetFontSize($size*2);

                //write the title page
                $pdf->Write('',$pagenamewiki[$i]);
                $pdf->Ln($size*1);

                //text size
                $pdf->SetFontSize($size);

                //parsing html in wikitopdf class
                $pdf->WriteHTML($textos_wiki_html[$i]);
			}
        }

        //name of the pdf file with the name of the first page
        $pdf->Output($pagenamewiki[0].'.pdf','D');
    }



//----------- Here starts the execution------------------- //

$WS = new storage();
$id = optional_param('id',NULL,PARAM_INT);
$WS->gid = optional_param('gid',NULL,PARAM_INT);
$cid = optional_param('cid',NULL,PARAM_INT);
$WS->pagedata->version = optional_param('version',NULL,PARAM_INT);
$WS->page = optional_param('page',NULL, PARAM_CLEAN);
$WS->page = stripslashes($WS->page);
//WS class is initialized
$WS->set_info($id);
$WS->cm->id = $id;

require_login($cid);

global $COURSE;

//only for theachers and admins:
$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
require_capability('mod/wiki:canexporttopdf',$context);

//check Moodle version(higher than 1.5)
if($CFG->version < 2006050500){
    error("Error: Wiki to PDFs only for Moodle 1.6 version or higher.");
}

$fform = data_submitted();

$textwiki = get_record_sql('
SELECT wp.id, wp.content, wp.refs
            FROM '. $CFG->prefix.'wiki_pages wp, '. $CFG->prefix.'course_modules cm, '. $CFG->prefix.'wiki w
            WHERE cm.id = '.$id.' AND cm.course = w.course AND wp.dfwiki = '.$WS->dfwiki->id.' AND wp.dfwiki = w.id
            AND wp.ownerid = '.$WS->member->id.' AND wp.groupid='.$WS->groupmember->groupid.' AND wp.pagename="'.$WS->page.'" AND version ='.$WS->pagedata->version.'
');

$references = split('\|', isset($textwiki->refs)?$textwiki->refs:"");

$contents = array();
$original = array();
$pagenamewiki = array();

//first page. we add it to $contents and $pagenamewiki
$contents[0] = $WS->page;
$original[0] = $WS->page;
$pagenamewiki[0] = $WS->page;

//add the referenciated pages

for($i=0; $i < sizeOf($references); $i++){
    array_push($contents, $references[$i]);
    array_push($original,$references[$i]);
}

//obtain the form variables
//form right box

if(isset($fform->addall)){
//Nothing needs to be done
} elseif(isset($fform->removeall)){
    $contents = null;
    $nocontents = $original;
} else if(isset($fform->remove) & isset($fform->removeselect)){
        $tamanyremove = sizeOf($fform->removeselect);
        $indice = 0;
        //delete in contents
        $indice = array_search($fform->removeselect, $fform->contents);
        $fform->contents[$indice] = null;
        //add in no-contents
        if(!isset($fform->nocontents)){
            $fform->nocontents = array();
        }
        $fform->nocontents[] = $fform->removeselect;
		$tamany_fformnocontents = sizeOf($fform->nocontents);
		$ind = 0;
		$nocontents = array();
		for ($r= 0; $r < $tamany_fformnocontents; $r++){
		    if($fform->nocontents[$r] != null){
		        $nocontents[$ind] = $fform->nocontents[$r];
		        $ind++;
		    }
		}
		$tamany_fformcontents = sizeOf($fform->contents);
		$ind = 0;
		$contents = array();
		for ($r= 0; $r < $tamany_fformcontents; $r++){
		    if($fform->contents[$r] != null){
		        $contents[$ind] = $fform->contents[$r];
		        $ind++;
		    }
		}        
} else if(isset($fform->add) & isset($fform->addselect)){
        $tamanyadd = sizeOf($fform->addselect);
        $indice = 0;
        //delete in no-contents
        $indice = array_search($fform->addselect, $fform->nocontents);
        $fform->nocontents[$indice] = null;
        //$nocontents = $fform->nocontents;
        //add in contents
        if(!isset($fform->contents)){
            $fform->contents = array();
        }
        $fform->contents[] = $fform->addselect;
		$tamany_fformnocontents = sizeOf($fform->nocontents);
		$ind = 0;
		$nocontents = array();
		for ($r= 0; $r < $tamany_fformnocontents; $r++){
		    if($fform->nocontents[$r] != null){
		        $nocontents[$ind] = $fform->nocontents[$r];
		        $ind++;
		    }
		}
		$tamany_fformcontents = sizeOf($fform->contents);
		$ind = 0;
		$contents = array();
		for ($r= 0; $r < $tamany_fformcontents; $r++){
		    if($fform->contents[$r] != null){
		        $contents[$ind] = $fform->contents[$r];
		        $ind++;
		    }
		}
} else {
	if(isset($fform->contents)){
	    $contents = $fform->contents;
	} else {
		$contents = null;
	}
	if(isset($fform->nocontents)){
	    $nocontents = $fform->nocontents;
	} else {
		$nocontents = null;
	}
}

$tam_nocontents = isset($nocontents)?sizeOf($nocontents):0;

$textwiki = array();
//first page
$textwiki[0] = isset($textwiki->content)?$textwiki->content:"";

//take the text of every wiki page and put on array
for($h = 0; $h < $tam_nocontents; $h++){
	$text = null;

    //last version of the page
    $lastversion = get_record_sql ('SELECT MAX(version) AS maxim
                FROM '. $CFG->prefix.'wiki_pages
                WHERE pagename="'.$nocontents[$h].'" AND dfwiki='.$WS->dfwiki->id.' AND groupid='.$WS->groupmember->groupid.' AND ownerid='.$WS->member->id
                );
	if (isset($lastversion->maxim)){
	    $text = get_record_sql('
	            SELECT wp.id, wp.content, wp.refs
	            FROM '. $CFG->prefix.'wiki_pages wp
	            WHERE wp.dfwiki = '.$WS->dfwiki->id.'
	            AND wp.ownerid = '.$WS->member->id.' AND wp.groupid='.$WS->groupmember->groupid.' AND wp.pagename="'.$nocontents[$h].'" AND version = '.$lastversion->maxim
	        );
	}
	//save the title of every page on array
	if(isset($text->content)){
        array_push($textwiki, $text->content);
        array_push($pagenamewiki, stripslashes($nocontents[$h]));
	}
	
}

//---------------Formulari------------------//

//Check if either we're coming from the form or this is the first time
if(optional_param('continue',NULL,PARAM_ALPHA) == get_string('continue')){

    //Form has already been visited
        $font = optional_param('font',NULL,PARAM_ALPHA);
        $size = optional_param('size',NULL,PARAM_INT);

        //call the function that creates the pdf
        wiki_convert_wiki_to_pdf($textwiki, $pagenamewiki, $id, $cid, $WS->member->id, $WS->groupmember->groupid, $WS->dfwiki->id, $WS->pagedata->version, $WS->page, $font, $size);
}else {
    // First time

   /// Print the page header
    if ($COURSE->category) {
         $navigation = "<a href=\"../../course/view.php?id=$cid\">$COURSE->shortname</a> ->";
    }

   //Adjust some php variables to the execution of this script
    @ini_set("max_execution_time","3000");
    raise_memory_limit("memory_limit","128M");

    //get mod plural and singlar name
    $strwikis = get_string("modulenameplural", 'wiki');
    $strwiki  = get_string("modulename", 'wiki');
    
    $navlinks[] = array('name' => $strwikis, 'link' => "{$CFG->wwwroot}/mod/wiki/index.php?id={$course->id}", 'type' => 'misc');
    $navlinks[] = array('name' => $WS->dfwiki->name, 'link' => "{$CFG->wwwroot}/mod/wiki/view.php?id={$WS->id}", 'type' => 'misc');
    $navlinks[] = array('name' => get_string('wikitopdf', 'wiki'), 'link' => null, 'type' =>'misc');
    $navigation = build_navigation($navlinks);

	print_header("$COURSE->shortname: {$WS->dfwiki->name}", "$COURSE->fullname", $navigation,"", "", true);
	
	$prop = null;
	$prop->class = "textcenter";
	wiki_div_start($prop);
	wiki_size_text(get_string('selectwikitopdf','wiki'), 2);
	wiki_div_end();

	echo '<!-- Inici del Formulari -->'."\n";
	echo '<!-- SELECCIONAR PAGINES WIKI A PASAR -->'."\n";

	$prop = null;
	$prop->id = "form";
	$prop->method = "post";
	$prop->action = 'wikitopdf.php?id='.$id.'&amp;cid='.$cid.'&amp;gid='.$WS->groupmember->groupid.'&amp;page='.$WS->page.'&amp;version='.$WS->pagedata->version;
	wiki_form_start($prop);
		$prop = null;
		$prop->class = "box generalbox generalboxcontent boxaligncenter";
		wiki_div_start($prop);
			$prop = null;
			$prop->name = "sesskey";
			if (!isset($sesskey)) $sesskey = sesskey();
			$prop->value = $sesskey;
			wiki_input_hidden($prop);

			$sizecontents = count($contents);
			$prop = null;
			$prop->name = "sizecontents";
			$prop->value = $sizecontents;
			wiki_input_hidden($prop);

			if(isset($contents)){
			   $i = 0;
			   foreach ($contents as $content){
				    $prop = null;
				    $prop->name = "contents[$i]";
				    $prop->value = stripslashes($content);
				    wiki_input_hidden($prop);
				    $i++;
			    }
			}

			$sizenocontents = isset($nocontents)?count($nocontents):0;
			$prop = null;
			$prop->name = "sizenocontents";
			$prop->value = $sizenocontents;
			wiki_input_hidden($prop);

			if(isset($nocontents)){
                $j = 0;
                foreach ($nocontents as $nocontent){
    				$prop = null;
				    $prop->name = "nocontents[$j]";
				    $prop->value = stripslashes($nocontent);
				    wiki_input_hidden($prop);
				    $j++;
			    }
			}

			$prop = null;
			$prop->class = "boxaligncenter";
			wiki_table_start($prop);
				print_string('nopagestopdf', 'wiki');
				wiki_change_column();
				wiki_change_column();
				print_string('pagestopdf', 'wiki');

				wiki_change_row();

				$opt = null;
				$noselec = 0;
				if(isset($contents) & is_array($contents)){
		      	    foreach ($contents as $cont) {
		        	    $prop = null;
		        	    $prop->value = stripslashes($cont);
		        	    $opt .= wiki_option (stripslashes($cont),$prop,true);
		        	    $noselec++;
		            }
				}
				if ($noselec==0) {
					$prop = null;
		        	$prop->value = "";
		        	$opt = wiki_option (get_string('nonoselectwiki', 'wiki'),$prop,true);
				}

				$prop = null;
				$prop->name = "removeselect";
				$prop->size = "10";
				$prop->id = "removeselect";
				$prop->multiple = "multiple";
				$prop->events = "onfocus=\"document.forms['form'].add.disabled=true;document.forms['form'].remove.disabled=false;document.forms['form'].addselect.selectedIndex=-1;\"";
				wiki_select($opt,$prop);
				wiki_change_column();
				wiki_br();
				$prop = null;
				$prop->name = "addall";
				$prop->id = "addall";
				$prop->value = "&lt;&lt;";
				wiki_input_submit($prop);
				wiki_br(2);
				$prop = null;
				$prop->name = "add";
				$prop->id = "add";
				$prop->value = "&larr;";
				wiki_input_submit($prop);
				wiki_br();
				$prop = null;
				$prop->name = "remove";
				$prop->id = "remove";
				$prop->value = "&rarr;";
				wiki_input_submit($prop);
				wiki_br(2);
				$prop = null;
				$prop->name = "removeall";
				$prop->id = "removeall";
				$prop->value = ">>";
				wiki_input_submit($prop);
				wiki_br();
				wiki_change_column();

				$opt = null;
				$selec = 0;
				if (isset($nocontents) && is_array($nocontents)) {
			      	foreach ($nocontents as $nocont) {
			        	$prop = null;
			        	$prop->value = stripslashes($nocont);
			        	$opt .= wiki_option (stripslashes($nocont),$prop,true);
			        	$selec++;
			        }
				}
				if ($selec==0) {
					$prop = null;
		        	$prop->value = "";
		        	$opt = wiki_option (get_string('noselectwiki', 'wiki'),$prop,true);
				}

				$prop = null;
				$prop->name = "addselect";
				$prop->size = "10";
				$prop->id = "addselect";
				$prop->multiple = "multiple";
				$prop->events = "onfocus=\"document.forms['form'].add.disabled=false;document.forms['form'].remove.disabled=true;document.forms['form'].removeselect.selectedIndex=-1;\"";
				wiki_select($opt,$prop);
			wiki_table_end();

			echo "<!-- SELECCIONAR TIPUS DE LLETRA -->"."\n";
			wiki_br(2);
			$prop = null;
			$prop->class = "boxaligncenter";
			wiki_table_start($prop);

				wiki_b(get_string('pdfFont','wiki').':');
				$prop = null;
				$prop->class = "nwikileftnow";
				wiki_change_column($prop);
				//if (isset($WS->dfwiki->evaluation)){
				//    print_string($WS->dfwiki->evaluation,'wiki');//<!-- corregir -->
			    //}else{
			   	$font = 'Arial';
			    $fonts = array('Arial','Courier','Times', 'Symbol', 'ZapfDingbats');
				$opt=null;
			   	foreach ($fonts as $fontop){
			   		$prop = null;
			   		$prop->value = $fontop;
			   		if ((isset($form->evaluation)?$form->evaluation:'')==$fontop) {$prop->selected = "selected"; }
			   		$opt .= wiki_option($fontop, $prop, true);
			   	}
			   	$prop = null;
				$prop->name = "font";
				$prop->size = "1";
				$prop->events = "onchange=\"javascript:view_evaluations(event)\"";
				wiki_select($opt,$prop);
				//}
				$prop = null;
				$prop->align = "right";
				wiki_change_row($prop);

				echo "<!-- SELECCIONAR TAMANY LLETRA -->"."\n";
				wiki_b(get_string('pdfFontSize','wiki').':');
				$prop = null;
				$prop->class = "nwikileftnow";
				wiki_change_column($prop);

				if (isset($dfwiki)){
				    print_string($dfwiki->evaluation,'wiki');//<!-- corregir -->
				}else{
				    $size_font_default = '12';
				    $size_fonts = array('8','9','10','11','12','14','16','18','20','22','24','26','28','36');
					$opt=null;
				    foreach ($size_fonts as $size_fontop){
				   		$prop = null;
				   		$prop->value = $size_fontop;
				   		if ($size_fontop == $size_font_default ) {$prop->selected = "selected"; }
				   		$opt .= wiki_option($size_fontop, $prop, true);
					}

				    $prop = null;
					$prop->name = "size";
					$prop->size = "1";
					$prop->events = "onchange=\"javascript:view_evaluations(event)\"";
					wiki_select($opt,$prop);
				}

				$prop = null;
				$prop->colspan = '2';
				$prop->class = "textcenter";
				wiki_change_row($prop);
				wiki_br();
				$prop = null;
				$prop->name = "continue";
				$prop->value = get_string('continue');
				wiki_input_submit($prop);
			wiki_table_end();
		wiki_div_end();
	wiki_form_end();
	echo "<!-- Fi del Formulari -->"."\n";

    /// Finish the page
    print_footer($COURSE);
}

?>
