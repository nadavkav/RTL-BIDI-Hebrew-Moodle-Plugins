<?php
//create a MediabirdAuthManager instance
include ('auth_manager.php');
$auth = new MediabirdAuthManager();

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
/*
 * File: MediabirdCaptchaImages.php
 * Author: Simon Jarvis
 * Copyright: 2006 Simon Jarvis
 * Date: 03/08/06
 * Updated: 07/02/07
 * Requirements: PHP 4/5 with GD and FreeType libraries
 * Link: http://www.white-hat-web-design.co.uk/articles/php-captcha.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * http://www.gnu.org/licenses/gpl.html
 *
 */
class MediabirdCaptchaImages {

	var $font = './captcha_font.ttf';
	var $code;

	/**
	 * Genertes captcha code
	 * @param int $characters Count of characters to generate
	 * @return string
	 */
	function generateCode($characters) {
		/* list all possible characters, similar looking characters and vowels have been removed */
		$possible = '23456789bcdfghjkmnpqrstvwxy';
		$code = '';
		$i = 0;
		while ($i < $characters) {
			$code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
			$i++;
		}
		return $code;
	}

	/**
	 * Generates a Mediabird captcha image and reads it back to the client
	 * @param int $width Width of image
	 * @param int $height Height of image
	 * @param int $characters Amount of characters to present
	 */
	function MediabirdCaptchaImages($width = '120', $height = '40', $characters = '5') {
		$code = $this->generateCode($characters);
		/* font size will be 75% of the image height */
		$font_size = $height*0.65;
		$image = imagecreate($width, $height) or die ('Cannot initialize new GD image stream');
		/* set the colours */
		$background_color = imagecolorallocate($image, 228, 228, 228);
		$text_color = imagecolorallocate($image, 96, 88, 143);
		$noise_color = imagecolorallocate($image, 154, 150, 180);
		/* generate random dots in background */
		for ($i = 0; $i < ($width*$height)/3; $i++) {
			imagefilledellipse($image, mt_rand(0, $width), mt_rand(0, $height), 1, 1, $noise_color);
		}
		/* generate random lines in background */
		for ($i = 0; $i < ($width*$height)/150; $i++) {
			imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $noise_color);
		}
		/* create textbox and add text */
		$textbox = imagettfbbox($font_size, 0, $this->font, $code) or die ('Error in imagettfbbox function');
		$x = ($width-$textbox[4])/2;
		$y = ($height-$textbox[5])/2-3;
		imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font, $code) or die ('Error in imagettftext function');
		/* output captcha image to browser */
		header('Content-Type: image/png');
		imagepng($image);
		imagedestroy($image);
		$this->code = $code;
	}

}

$captcha = new MediabirdCaptchaImages(120, 24, 6);
$auth->setSecurityCode($captcha->code);

?>
