
<?php
// slideshow.php v2.6
// ------------------------------------------------------------------------
// Copyright (c) 2004-2006, maani.us
// ------------------------------------------------------------------------
// This file is part of "PHP/SWF Slideshow"
//
// PHP/SWF Slideshow is a shareware. See http://www.maani.us/slideshow/ for
// more information.
// ------------------------------------------------------------------------


function Insert_Slideshow( $flash_file, $php_source, $width=320, $height=240, $license=null ){
	
	$php_source=urlencode($php_source);
	//$protocol = (strtolower($_SERVER['HTTPS']) != 'on')? 'http': 'https';
	if (isset($_SERVER['HTTPS'])) { $protocol = (strtolower($_SERVER['HTTPS']) != 'on')? 'http': 'https'; } else { $protocol = 'http'; }

	$html="<OBJECT classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='".$protocol."://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0' ";
	if(strpos ($flash_file,"slideshow_id=")===false){$id="slideshow";}
	else{
		$id=substr($flash_file,strpos($flash_file,"slideshow_id=")+strlen("slideshow_id="));
		$id=substr($id,0,strpos($id,"&"));
	}
	$html.="WIDTH='".$width."' HEIGHT='".$height."' id='".$id."' />";
	$u=(strpos ($flash_file,"?")===false)? "?" : ((substr($flash_file, -1)==="&")? "":"&");
	$html.="<PARAM NAME='movie' VALUE='".$flash_file.$u."php_source=".$php_source."&stage_width=".$width."&stage_height=".$height;
	if($license!=null){$html.="&license=".$license;}
	$html.="' /> <PARAM NAME='quality' VALUE='high' /><PARAM NAME='bgcolor' VALUE='#000000' /><PARAM NAME='wmode' VALUE='transparent' /><PARAM NAME='AllowScriptAccess' value='sameDomain' />";
	$html.="<EMBED src='".$flash_file.$u."php_source=".$php_source."&stage_width=".$width."&stage_height=".$height;
	if($license!=null){$html.="&license=".$license;}
	$html.="' quality='high' bgcolor='#000000' WIDTH='".$width."' HEIGHT='".$height."' NAME='".$id."' wmode='transparent' swLiveConnect='true' AllowScriptAccess='sameDomain' ";
	$html.="TYPE='application/x-shockwave-flash' PLUGINSPAGE='".$protocol."://www.macromedia.com/go/getflashplayer'></EMBED></OBJECT>";
	return $html;
}

//====================================
function Send_Slideshow_Data( $slideshow=array() ){
	
	$xml="<slideshow>\r\n\r\n";
	$index=0;
	$aList=array("slide","transition","motion","sound","navigation","action","link","draw_circle","draw_line","draw_rect","draw_text");
	
	do {

		$continue=false;
		for($i=0;$i<count($aList);$i++){
			if(isset($slideshow[$aList[$i]][$index])){
				$continue=true;
				break;
			}
		}
		if($continue){
			$xml.="\t<slide>\r\n";
			
			for($i1=0;$i1<6;$i1++){
				if(isset($slideshow[$aList[$i1]][$index])){
					if($aList[$i1]=="slide"){$xml.="\t\t<image";}
					else{$xml.="\t\t<".$aList[$i1];}
					$keys=array_keys((array) $slideshow[$aList[$i1]][$index]);
					for($i2=0;$i2<count($keys);$i2++){
						$xml.=" ".$keys[$i2]."=\"".$slideshow[$aList[$i1]][$index][$keys[$i2]]."\"";
					}
					$xml.=" />\r\n";
				}
			}
			
			for($i1=6;$i1<count($aList);$i1++){
				if(isset($slideshow[$aList[$i1]][$index])){
					$xml.="\t\t<".$aList[$i1].">\r\n";
					for($i2=0;$i2<count($slideshow[$aList[$i1]][$index]);$i2++){
						if($aList[$i1]=="link"){$xml.="\t\t\t<area";}
						else{$xml.="\t\t\t<".substr($aList[$i1],5);}
						$keys=array_keys((array) $slideshow[$aList[$i1]][$index][$i2]);
						for($i3=0;$i3<count($keys);$i3++){
							if($keys[$i3]!="text"){$xml.=" ".$keys[$i3]."=\"".$slideshow[$aList[$i1]][$index][$i2][$keys[$i3]]."\"";}
						}
						if($aList[$i1]=="draw_text"){$xml.=">".$slideshow[$aList[$i1]][$index][$i2]['text']."</text>\r\n";}
						else{$xml.=" />\r\n";}
					}
					$xml.="\t\t</".$aList[$i1].">\r\n";
				}
			}
				
			$xml.="\t</slide>\r\n\r\n";
			$index++;
		}
	} while ($continue);
	
	for($i=0;$i<count($aList);$i++){
		unset($slideshow[$aList[$i]]);
	}
	
	$keys1= array_keys((array) $slideshow);
	for ($i1=0;$i1<count($keys1);$i1++){
		if($keys1[$i1]=="license"){$xml.="\t<".$keys1[$i1].">".$slideshow[$keys1[$i1]]."</".$keys1[$i1].">\r\n";}
		else{
			$keys2=array_keys((array) $slideshow[$keys1[$i1]]);
			$xml.="\t<".$keys1[$i1];
			for($i2=0;$i2<count($keys2);$i2++){
				$xml.=" ".$keys2[$i2]."=\"".$slideshow[$keys1[$i1]][$keys2[$i2]]."\"";
			}
			$xml.=" />\r\n";
		}
	}

	$xml.="</slideshow>\r\n";
	echo $xml;
}
//====================================
?>
