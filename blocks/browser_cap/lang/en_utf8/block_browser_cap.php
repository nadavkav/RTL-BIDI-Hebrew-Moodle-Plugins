<?php
$string['browser_cap'] = 'Browser Capability';
$string['browser_support'] = 'Your browser <em>seems to</em> support these plugins:';
$string['browser_noscript'] = 'you dont have Javascript enabled which is required to detect plugin support';
$string['browser_cap_config_intro'] = 'Select the browser plug-ins you would like to be checked for in the block. Students in your course will see a tick or a cross against each one of these items indicating whether their browser seems to support the plug-in or not.
If you know you aren\'t using any of these files you may as well remove the tick against them.
<strong>Please Note:</strong> this block will only give you an <em>indication</em> that the plug-in is present. It won\'t tell you which version';
$string['adobe_desc'] = 'The Adobe Portable Document Format is a commonly used file format by web content creators. This format is easily viewable on multiple operating systems with the aid of a PDF plug-in such as Acrobat Reader.';
$string['cookies_desc'] = 'Cookies are an important requirement, and needed to login to Moodle so its not really necessary to check for them.';
$string['flash_desc'] = 'Like Java, Flash enables content authors to provide dynamic and interactive web content. You will need flash player to take advantage of these enhancements.';
$string['java_desc'] = 'The Java runtime environment enables users to launch and view web applications created using the Java programming language.';
$string['javascript_desc'] = 'Another important technology used in Moodle. As with Cookies however it is probably not necessary to check for this, and in fact this block wont operate without it.';
$string['quicktime_desc'] = 'If you have uploaded Quicktime files (.qt) into your class or are linking to them, the students will need to make sure they have this player installed.';
$string['real_desc'] = 'If you have uploaded Real media files (.rm) into your class or are linking to them, the students will need to make sure they have this player installed.';
$string['windows_media_desc'] = 'If you have uploaded Windows media files (.wm or .wav) into your class or are linking to them, the students will need to make sure they have this player installed.';
$string['tech_notes'] = 'Technical Notes';
$string['tech_notes_desc'] = '<p>This solution as implemented is to use the navigator.* methods supported by Mozilla, Firefox and early IE browsers to establish if support may be available. And to use the ActiveX object in later IE versions with some VBScript. Although not as reliable it gives a reasonable indication without the overhead of actually instantiating each individual object type.</p>
<p>Most comprehensive plug-in detection solutions achieve detection through attempting to instantiate an instance of the object in question by including an <object> tag in the output, then checking to see if that object exists or is able to be queried through its methods/properties. If not the detector assumes the plug-in is not available or is disabled.</p>
<p>This instantiation is effective, but also places a processing and network burden on the browser, and potentially the back-end server, which may not be appropriate on a page which is heavily or frequently visited.</p>';
?>
