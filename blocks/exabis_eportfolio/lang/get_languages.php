<?php

$defaultLang = 'en_utf8';

function getTranslations($language)
{
	$string = array();
	$stringNotUsed = array();

	if (file_exists($language.'/block_exabis_eportfolio.php')) {
		require ($language.'/block_exabis_eportfolio.php');
	} else {
		require ($language.'/block_exabis_eportfolio.orig.php');
	}

	return $string + $stringNotUsed;
}




$langPaths = glob('*_utf8');

// ignore these paths
// $langPaths = array_diff($langPaths, array('de_du_utf8', 'en_utf8'));

$translations = array();

foreach ($langPaths as $langPath) {
	$strings = getTranslations($langPath);

	$translations[] = $strings['translation:language'].' ('.str_replace('_utf8', '', $langPath).')';
}

echo 'Translations: '.join(', ', $translations);
