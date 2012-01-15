Moodle-FlashUpload by Helmut Geppl
helmut.geppl@donau-uni.ac.at
(enables multiple file-upload in your browser using Flash)
The software is based on SWFUpload v2.2.0 from http://www.swfupload.org
(Open Source MIT License)

Installation Instrucions:

1. Extract the folder "flashupload" of the zip-file 
   into the folder $moodledir/files (e.g. /srv/www/htdocs/moodle/files )

2. Copy the file "flashupload.php" from folder "en_utf8" to $dataroot/lang/en_utf8/ 
   (e.g. /srv/www/moodledata/lang/en_utf8 under Linux or "c:\xampp\moodledata\lang\en_utf8" under Windows )
   For German Language Translation copy "flashupload.php" form folder "de_utf8" to $dataroot/lang/de_utf8/
   There are no other translations until now. So if you're using other Language-Packages, copy the file 
   "flashupload.php" from folder "en_utf8" to the appropriate subfolder in $dataroot/lang/ (e.g. ru_utf8 for Russian) and
   translate the words.   

3. Backup the file $moodledir/files/index.php (e.g.: create a copy called "index.php.orig")

4. Modify $moodledir/files/index.php:

   a) open the file index.php in folder $moodledir/files for editing
   b) search for "function html_header"
   c) before the line beginning with 
      echo "<table border=\"0\" ....
	  insert the following code:
	  
	  //MOD: FLASHUPLOAD - START
	  require('flashupload/flashupload.php');
	  //MOD: FLASHUPLOAD - END		  

You're Done!