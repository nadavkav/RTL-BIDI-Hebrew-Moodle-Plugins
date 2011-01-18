/* $Id: javascript.php,v 1.2.2.1 2006/12/05 10:05:29 janne Exp $ */
/* ImageGallery object's constructor */
function ImageGallery () {
    this.ratio = 1.3333333333;
};
/* Change width value */
ImageGallery.prototype.change_dim_width = function (hvalue, fieldname) {
    if ( document.getElementById) {
        var elem = document.getElementById('menu' + fieldname);
        if ( elem ) {
            var newWidth = Math.round(hvalue * this.ratio);
            elem.value = newWidth;
        }
    }
};
/* Change height value */
ImageGallery.prototype.change_dim_height = function (vvalue, fieldname) {
    if ( document.getElementById ) {
        var elem = document.getElementById('menu' + fieldname);
        if ( elem ) {
            var newHeight = Math.round(vvalue / this.ratio);
            elem.value = newHeight;
        }
    }
};

ImageGallery.prototype._newWidth =  function (height) {
};

/**
 * Make HTTP GET request and get ImageGallery's categories if any.
 *
 */
ImageGallery.prototype._HTTPGET = function (cmid, sesskey, url, galleryid, cmd) {

    var req;
    var fullurl = url +'?id='+ cmid +'&sesskey='+ sesskey +'&gallery='+ galleryid +'&cmd='+ cmd;

    // branch for native XMLHttpRequest object
    if (window.XMLHttpRequest) {
        req = new XMLHttpRequest();
        //req.onreadystatechange = processReqChange;
        //netscape.security.PrivilegeManager.enablePrivilege("UniversalBrowserRead");
        req.open("GET", fullurl, false);
        req.send(null);
    // branch for IE/Windows ActiveX version
    } else if (window.ActiveXObject) {
        req = new ActiveXObject("Microsoft.XMLHTTP");
        if (req) {
            //req.onreadystatechange = processReqChange;
            req.open("GET", fullurl, false);
            req.send();
        }
    }

    return req.responseXML;

};

/**
 * Clear category entries from category dropdown menu.
 *
 */
ImageGallery.prototype._clearEntries = function () {

    if ( document.getElementById ) {
        var elem = document.getElementById('cat_id');
        for ( var i = 0; i < elem.options.length; i++ ) {
            var curEntry = elem.options[i];
            if ( curEntry.getAttribute('value') > 0 ) {
                elem.removeChild(curEntry);
            }
        }
        // crappy hack to remove last entry.
        if ( elem.options.length > 1 ) {
            elem.removeChild(elem.options[1]);
        }
    }
};

/**
 * Add category entries into category dropdown menu.
 *
 */
ImageGallery.prototype._getCat = function ( cmid, sesskey, url, galleryid, cmd ) {

    this._clearEntries();
    if ( document.getElementById ) {
        var output = document.getElementById('cat_id');
        var content = this._HTTPGET(cmid, sesskey, url, galleryid, cmd);

        if ( content ) {
            var categories = content.getElementsByTagName('category');
            for ( var i = 0; i < categories.length; i++ ) {
                var curEntry = categories[i];
                var catId = curEntry.getAttribute('id');
                var catName = curEntry.firstChild.nodeValue;
                if ( catId && catName ) {
                    var optVal = document.createElement('option');
                    optVal.setAttribute("value", catId);
                    if ( i < 1 ) {
                        optVal.setAttribute('selected', 'selected');
                    }
                    var txt = document.createTextNode(catName);
                    optVal.appendChild(txt);
                    output.appendChild(optVal);
                }
            }
        }
    }
};

/* Initialize new object. */
iGallery = new ImageGallery();
