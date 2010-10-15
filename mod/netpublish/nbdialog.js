var nbWin = new Object();

function nbDialog(url, width, height, retFunc) {
    if (!nbWin.win || (nbWin.win && nbWin.win.closed)) {
        nbWin.url = url;
        nbWin.width = width;
        nbWin.height = height;
        nbWin.retFunc = retFunc;

        // Keep name unique.
        nbWin.name = (new Date()).getSeconds().toString();
        // Assemble window attributes and try to center the dialog.
        if (window.screenX) {              // Netscape 4+
            // Center on the main window.
            nbWin.left = window.screenX +
               ((window.outerWidth - nbWin.width) / 2);
            nbWin.top = window.screenY +
               ((window.outerHeight - nbWin.height) / 2);
            var attr = "screenX=" + nbWin.left +
               ",screenY=" + nbWin.top + ",resizable=no,scrollbars=no,width=" +
               nbWin.width + ",height=" + nbWin.height;
        } else if (window.screenLeft) {    // IE 5+/Windows
            // Center (more or less) on the IE main window.
            // Start by estimating window size,
            // taking IE6+ CSS compatibility mode into account
            var CSSCompat = (document.compatMode && document.compatMode != "BackCompat");
            window.outerWidth = (CSSCompat) ? document.body.parentElement.clientWidth : document.body.clientWidth;
            window.outerHeight = (CSSCompat) ? document.body.parentElement.clientHeight : document.body.clientHeight;
            window.outerHeight -= 80;
            nbWin.left = parseInt(window.screenLeft+
               ((window.outerWidth - nbWin.width) / 2));
            nbWin.top = parseInt(window.screenTop +
               ((window.outerHeight - nbWin.height) / 2));
            var attr = "left=" + nbWin.left +
               ",top=" + nbWin.top + ",resizable=no,scrollbars=no,width=" +
               nbWin.width + ",height=" + nbWin.height;
        } else {                           // all the rest
            // The best we can do is center in screen.
            nbWin.left = (screen.width - nbWin.width) / 2;
            nbWin.top = (screen.height - nbWin.height) / 2;
            var attr = "left=" + nbWin.left + ",top=" +
               nbWin.top + ",resizable=no,scrollbars=no,width=" + nbWin.width +
               ",height=" + nbWin.height;
        }

        // Generate the nb and make sure it has focus.
        nbWin.win=window.open(nbWin.url, nbWin.name, attr);
        nbWin.win.focus();
    } else {
        nbWin.win.focus();
    }
}
