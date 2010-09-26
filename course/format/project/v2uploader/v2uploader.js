/**
 *    Javascript library for v2uploader
 *
 *    Copyright(c) 2008 VERSION2. All Rights Reserved.
 */
if ( typeof(Uploader) == 'undefined' ) Uploader = function() {};
Uploader.prototype = {
    swf_element:'',
    swf_source:'',
    swf_width:16,
    swf_height:16,
    targetId:undefined,
    
    /**
     *    put uploader plugin on html
     *
     *    @param    int    id    (エレメントのユニークID)
     *    @param    string    seeekey    (PHP等のセッションキー)
     *    @param    string    sessid    (PHP等のセッション値)
     *    @param    array    array    (キー=値でGETフォーム値を生成)
     *    @param	int		targetId	(=section_id)
     */
    putUploader:function(id,sesskey,sessid,array,targetId) {
        var param = '';
        var count = 1;
        var html;
        for (var i in array) {
            param += '&pkey' + count + '=' + i + '&pval' + count + '=' + array[i];
            count++;
        }
        param += '&targetId='+targetId;
        this.targetId = targetId;
        
        if (array['isregister']) {
        	param += '&resourcemode=1';
        }
        
        html = '<object id="'+this.swf_element+id+'" width="'+this.swf_width+'" height="'+this.swf_height+'" align="top" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0">';
        html += '<param name="allowScriptAccess" value="sameDomain" />';
        html += '<param name="movie" value="'+this.swf_source+'&sesskey='+sesskey+'&sessid='+sessid+'&id='+id+param+'" />';
        html += '<param name="quality" value="high" />';
		html += '<param name="wmode" value="transparent" />';
        html += '<embed id="'+this.swf_element+id+'" name="'+this.swf_element+id+'" src="'+this.swf_source+'&sesskey='+sesskey+'&sessid='+sessid+'&id='+id+param+'" wmode="transparent" quality="high" width="'+this.swf_width+'" height="'+this.swf_height+'" align="top" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />';
        html += '</object>';
        document.write(html);
    },
    uploadItems:function(targetId, resourcemode) {
    	var previd;
    	if (resourcemode) {
    		previd = 'ruswf';
    	} else {
    		previd = 'cfuswf';
    	}
        this._swf(previd + targetId).uploadItems();
    },
    ExternalError:function(msg,url) {
        alert(msg);
    },
    ExternalAddFile:function(file) {
    },
    ExternalOnAddFile:function(targetId, resourcemode) {
    	this.uploadItems(targetId, resourcemode);
    },
    ExternalComplete:function() {
        location.reload();
    },
    ExternalOnLoad:function() {
    },
    ExternalProgress:function(value,targetId,resourcemode) {
    	var progressid;
    	if (resourcemode) {
    		progressid = 'ruprgs';
    	} else {
    		progressid = 'cfuprgs';
    	}
    	
    	html = value + '%';
    	this.get(progressid + targetId).innerHTML= html;
    },
    get:function(objectName) {
        if (document.getElementById(objectName)) {
            return document.getElementById(objectName);
        } else {
            return false;
        }
    },
	_swf:function(movieName) {
		if (navigator.appName.indexOf("Microsoft") != -1) {
			return window[movieName]
		} else {
			return document[movieName]
		}
	}
}
var uploader = new Uploader();