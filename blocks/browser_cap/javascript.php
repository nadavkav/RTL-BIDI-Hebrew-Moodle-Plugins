/**
 * ================================================================================
 * original source: http://developer.apple.com/internet/webcontent/examples/detectplugins_source.html
 * which as of 2008/06/06 included usage criteria stipulating that the code can be 
 * incorporated into your own code without restriction, and be redistributed, but 
 * is not to be distributed as "Apple sample code" after having made changes.
 * much thanks to Apple for this :-)
 *
 * subsequently modified for use as a block within Moodle courses
 *  by Dean Stringer @ Waikato University, New Zealand
 * changes:
 *  6th June 2008 - initial release
 *  o removed redirectCheck(), goURL(), detectQuickTimeActiveXControl() and detectDirector()
 *  o removed redirectURL and redirectIfFound params from all detect* subs
 *  o added detectAcrobat(), detectJava(), detectCookies() and showResult()
 * ================================================================================
 */

// initialize global variables
var detectableWithVB = false;
var pluginFound = false;

function canDetectPlugins() {
    if( detectableWithVB || (navigator.plugins && navigator.plugins.length > 0) ) {
	return true;
    } else {
	return false;
    }
}

function detectFlash() {
    pluginFound = detectPlugin('Shockwave','Flash'); 
    // if not found, try to detect with VisualBasic
    if(!pluginFound && detectableWithVB) {
	pluginFound = detectActiveXControl('ShockwaveFlash.ShockwaveFlash');
    }
    return pluginFound;
}

//function detectDirector() { 
// not including Director, Flash player is used typically to play shockwave files
// created in Adobe Director
//  pluginFound = detectPlugin('Shockwave','Director'); 
//    // if not found, try to detect with VisualBasic
//    if(!pluginFound && detectableWithVB) {
//	pluginFound = detectActiveXControl('SWCtl.SWCtl');
//    }
//    return pluginFound;
//}

function detectQuickTime() {
    pluginFound = detectPlugin('QuickTime');
    // if not found, try to detect with VisualBasic
    if(!pluginFound && detectableWithVB) {
	pluginFound = detectActiveXControl('QuickTime.QuickTime');
    }
    return pluginFound;
}

function detectReal() {
    pluginFound = detectPlugin('RealPlayer');
    // if not found, try to detect with VisualBasic
    if(!pluginFound && detectableWithVB) {
	pluginFound = (detectActiveXControl('rmocx.RealPlayer G2 Control') ||
		       detectActiveXControl('RealPlayer.RealPlayer(tm) ActiveX Control (32-bit)') ||
		       detectActiveXControl('RealVideo.RealVideo(tm) ActiveX Control (32-bit)'));
    }	
    return pluginFound;
}

function detectWindowsMedia() {
    pluginFound = detectPlugin('Windows Media');
    // if not found, try to detect with VisualBasic
    if(!pluginFound && detectableWithVB) {
	pluginFound = detectActiveXControl('MediaPlayer.MediaPlayer');
    }
    return pluginFound;
}

function detectPlugin() {
    // allow for multiple checks in a single pass
    var daPlugins = detectPlugin.arguments;
    // consider pluginFound to be false until proven true
    var pluginFound = false;
    // if plugins array is there and not fake
    if (navigator.plugins && navigator.plugins.length > 0) {
	var pluginsArrayLength = navigator.plugins.length;
	// for each plugin...
	for (pluginsArrayCounter=0; pluginsArrayCounter < pluginsArrayLength; pluginsArrayCounter++ ) {
	    // loop through all desired names and check each against the current plugin name
	    var numFound = 0;
	    for(namesCounter=0; namesCounter < daPlugins.length; namesCounter++) {
		// if desired plugin name is found in either plugin name or description
		if( (navigator.plugins[pluginsArrayCounter].name.indexOf(daPlugins[namesCounter]) >= 0) || 
		    (navigator.plugins[pluginsArrayCounter].description.indexOf(daPlugins[namesCounter]) >= 0) ) {
		    // this name was found
		    numFound++;
		}   
	    }
	    // now that we have checked all the required names against this one plugin,
	    // if the number we found matches the total number provided then we were successful
	    if(numFound == daPlugins.length) {
		pluginFound = true;
		// if we've found the plugin, we can stop looking through at the rest of the plugins
		break;
	    }
	}
    }
    return pluginFound;
} // detectPlugin


// Here we write out the VBScript block for MSIE Windows
if ((navigator.userAgent.indexOf('MSIE') != -1) && (navigator.userAgent.indexOf('Win') != -1)) {
    document.writeln('<script language="VBscript">');

    document.writeln('\'do a one-time test for a version of VBScript that can handle this code');
    document.writeln('detectableWithVB = False');
    document.writeln('If ScriptEngineMajorVersion >= 2 then');
    document.writeln('  detectableWithVB = True');
    document.writeln('End If');

    document.writeln('\'this next function will detect most plugins');
    document.writeln('Function detectActiveXControl(activeXControlName)');
    document.writeln('  on error resume next');
    document.writeln('  detectActiveXControl = False');
    document.writeln('  If detectableWithVB Then');
    document.writeln('     detectActiveXControl = IsObject(CreateObject(activeXControlName))');
    document.writeln('  End If');
    document.writeln('End Function');

    document.writeln('</scr' + 'ipt>');
}

// ================================================================================
// additions (June/2008 onwards)
// ================================================================================

function detectAcrobat() {
    pluginFound = detectPlugin('Adobe Acrobat'); 
    // if not found, try to detect with VisualBasic
    if(!pluginFound && detectableWithVB) {
    pluginFound = detectActiveXControl('AcroPDF.PDF');  // ver7+
    }
    if(!pluginFound && detectableWithVB) {
    pluginFound = detectActiveXControl('PDF.PdfCtrl');    // ver6-
    }
    return pluginFound;
}

function detectJava() {
    pluginFound = detectPlugin('Java Plug-in'); 
    // if not found, try to detect with VisualBasic
    if(!pluginFound && detectableWithVB) {
    pluginFound = navigator.javaEnabled();
    // note: navigator.javaEnabled() does not reveal whether Java is installed or not in IE. It merely tells you if 
    // the applet tag is enabled or disabled. You can disable the applet tag in the IE security settings 
    }
    return pluginFound;
}

function detectCookies() {
    pluginFound = navigator.cookieEnabled;
    return pluginFound;
}

function showResult(pluginName, status) {
    statusImg = 'cross_red_small.gif';
    if (status) statusImg = 'tick_green_small.gif';
    document.write('<br><img src="' + browserCapImgPath + statusImg + '">');
    document.write('&nbsp;' + pluginName);
}
