/****************************************************************************************
                 Script to read and write cookies from JavaScript
                     Written by Mark Wilton-Jones, 31/12/2001
*****************************************************************************************

Please see http://www.howtocreate.co.uk/jslibs/ for details and a demo of this script
Please see http://www.howtocreate.co.uk/jslibs/termsOfUse.html for terms of use

This script allows you to store and retrieve cookies easily using JavaScript. Cookies
are variables that can be stored on a user's computer and be picked up by any other web
pages in the correct domain. Cookies are set to expire after a certain length of time.

Be warned that many users will not permit cookies on their computers. Do not make your
web sites rely on them. This script provides a return value that says if they accepted
or rejected a cookie.

To use:
_________________________________________________________________________

Inbetween the <head> tags, put:

	<script src="PATH TO SCRIPT/cookie.js" type="text/javascript" language="javascript"></script>

To store a cookie, use:

	var cookieAccepted = setCookie( cookieName, cookieValue[, lifeTime[, path[, domain[, isSecure]]]] )

		cookieName is the name of the cookie (as a string) and can contain any characters.

		cookieValue is the value that the cookie stores (as a string) and can contain any characters.

		lifeTime is the amount of time in seconds that you want the cookie to last for (after which
		the user's computer will delete it). The default is until the browser is closed.

		path gives the path or directories that the cookie should be accessible from. The default is
		the current path. Alter this using ../ (up one directory) / starting at the base directory
		and subdirectoryName/ to start from the currentDirectory/subdirectoryName/

		domain gives the domain that the cookie is accessible from. This must have at least one . in it
		and in many browsers it must have at least two. The default is the current domain.

		isSecure can be true or false and says whether or not the cookie is only accessible on sites
		with a secure (https) connection.

If the user rejected the cookie, cookieAccepted will be false. In Opera, if cookies are on prompt,
the failure responce is received immediately, even if the user then accepts the cookie.

To retrieve a cookie, use:

	var myCookie = retrieveCookie( cookieNameAsAString );

To modify a cookie, simply set it again with the new settings;

To delete a cookie, use:

	var cookieDeletePermitted = setCookie( cookieNameAsAString, '', 'delete' );

	or set lifeTime to a less than 0 value.

If the user rejected the attempt to delete the cookie, cookieDeletePermitted will be false.
_______________________________________________________________________________________*/

function retrieveCookie( cookieName ) {
	/* retrieved in the format
	cookieName4=value; cookieName3=value; cookieName2=value; cookieName1=value
	only cookies for this domain and path will be retrieved */
	var cookieJar = document.cookie.split( "; " );
	for( var x = 0; x < cookieJar.length; x++ ) {
		var oneCookie = cookieJar[x].split( "=" );
		if( oneCookie[0] == escape( cookieName ) ) { return oneCookie[1] ? unescape( oneCookie[1] ) : ''; }
	}
	return null;
}

function setCookie( cookieName, cookieValue, lifeTime, path, domain, isSecure ) {
	if( !cookieName ) { return false; }
	if( lifeTime == "delete" ) { lifeTime = -10; } //this is in the past. Expires immediately.
	/* This next line sets the cookie but does not overwrite other cookies.
	syntax: cookieName=cookieValue[;expires=dataAsString[;path=pathAsString[;domain=domainAsString[;secure]]]]
	Because of the way that document.cookie behaves, writing this here is equivalent to writing
	document.cookie = whatIAmWritingNow + "; " + document.cookie; */
	document.cookie = escape( cookieName ) + "=" + escape( cookieValue ) +
		( lifeTime ? ";expires=" + ( new Date( ( new Date() ).getTime() + ( 1000 * lifeTime ) ) ).toGMTString() : "" ) +
		( path ? ";path=" + path : "") + ( domain ? ";domain=" + domain : "") + 
		( isSecure ? ";secure" : "");
	//check if the cookie has been set/deleted as required
	if( lifeTime < 0 ) { if( typeof( retrieveCookie( cookieName ) ) == "string" ) { return false; } return true; }
	if( typeof( retrieveCookie( cookieName ) ) == "string" ) { return true; } return false;
}