//Thanks to: http://www.quirksmode.org/js/detect.html

var BrowserDetect = new Class ({
	initialize: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent)
			|| this.searchVersion(navigator.appVersion)
			|| "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString: function (data) {
		for (var i=0;i<data.length;i++)	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},
	dataBrowser: [
		{
			string: navigator.userAgent,
			subString: "Chrome",
			identity: "Chrome"
		},
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari",
			versionSearch: "Version"
		},
		{
			prop: window.opera,
			identity: "Opera"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			   string: navigator.userAgent,
			   subString: "iPhone",
			   identity: "iPhone/iPod"
	    },
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]

});

var SwDragndrop = new Class({
    Extends: BrowserDetect,
    Implements: Options,
    options: {
		maxSize: 2,			// number of MB per file (in case of file drag and drop -> Firefox 3.6 and above)
		maxDropFile: 3,		// number of MB per upload (in case of fallback upload -> other browsers)
		url: 'upload.php<?php echo "?courseid=".$_GET['courseid']."&userid=".$_GET['userid']; ?>',
		content: "Drag and drop files to upload!"
    },
    el: null,
    fileListEl: null,
    index: 1,
    count: 0,
    height: 0,
    initialize: function(el, options){
    	this.parent();
        this.setOptions(options);
        this.el = $(el);
        this.height = this.el.getStyle('height').toInt();
		if (!this.el) {
			alert ('swDragndrop.js: div with id \''+el+'\' not found.');
			return;
		}
		this.init();
		if (this.browser=='Firefox' && parseFloat(this.version)>=3.6) {
			this.processDragNDrop();
		} else {
			this.processMultiUpload();
		}
    },
	init: function() {
		this.fileListEl = new Element('ul');
		// reflow
		this.el.empty().grab(this.fileListEl);
	},
	
	// multi-upload
	createInputEls: function () {
		var li = new Element('li', {'id':'li_'+this.index});
    	var formEl = new Element('form', {'id':'form_'+this.index, 'enctype': 'multipart/form-data', 'action':this.options.url, 'method':'post', 'target': 'upload_target_'+this.index});
    	var inputEl = new Element('input', {'id': 'file_'+this.index, 'type': 'file', 'name': 'file_'+this.index});
    	var spanEl = new Element('span', {'id': 'status_'+this.index});
    	// hack: form.send() would not send files...
    	// we need to use submit, but, we don't have onSuccess event anymore
    	// so we submit to a hidden iFrame and listen to the onload event of this element
    	var iFrameEl = new Element('iFrame', {'name': 'upload_target_'+this.index, styles: {'display': 'none'}});
    	iFrameEl.addEvent('load', function(){
    		var content = iFrameEl.contentWindow.document.body.innerHTML;
    		if (content) {
        		// read the response
    			var jsonResponse = JSON.decode(content);
    			if (jsonResponse.status=='success') {
    				this.updateFileList(jsonResponse.fileList[0][1], inputEl.getParent().getParent());
    			} else {
            		inputEl.set('value', '');
            		inputEl.disabled=0;
        			spanEl.removeClass('ajax-loader');
    				spanEl.set('text', jsonResponse.status);
    			}
    		}
    	}.bind(this));
    	return li.grab(formEl.grab(inputEl).grab(spanEl)).grab(iFrameEl);
	},
	processMultiUpload: function(){
		// create elements
    	var inputEls = this.createInputEls();
		var inputEl = inputEls.getElement('input'); 
    	// add files feature
    	this.initInput(inputEl);
    	// reflow!!
    	this.fileListEl.grab(inputEls);
	},
	initInput: function(inputEl) {
    	inputEl.addEvent('change', function() {
    		// TODO: type validation
    		// TODO: minmax validation
			// Empty the log and show the spinning indicator.
			inputEl.getNext().empty().addClass('ajax-loader').set('html', 'uploading '+inputEl.get('value')+'...');
    		// Display a new input element
			var currentForm=inputEl.getParent();
    		var currentLi=currentForm.getParent();
    		if (!currentLi.getNext() || currentLi.getNext().get('id').substring(0, 3)!="li_") {
        		this.index++;
        		if (this.index%2!=0) {
        			this.el.setStyle('height', this.height*((1+this.index)/2).toInt());
        		}
        		var newInputEls = this.createInputEls();
        		currentLi.grab(newInputEls, 'after');
        		this.initInput(newInputEls.getElement('input'));
    		}	
    		// submit!!
			currentForm.submit();
    		inputEl.disabled=1;
    	}.bind(this));
	},
	updateFileList: function(filename, liEl) {
		// scale and display image inside the li
		var imgEl = new Element('img', {'src':filename}),
			liWidth = liEl.getStyle('width').toInt(),
			liHeight = liEl.getStyle('height').toInt();
		if ((liHeight/liWidth)>(imgEl.get('height')/imgEl.get('width'))) {
			imgEl.set('width', liWidth);
		} else {
			imgEl.set('height', liHeight);
		}
		liEl.empty().grab(imgEl);
    // so we could push it back to the HTMLAREA editor
    this.uploadedfiles.push(filename);
	},
	
	// Drag and drop (FF3.6 and +)
	// the following functions are based on http://www.appelsiini.net/2009/10/html5-drag-and-drop-multiple-file-upload
	// and http://www.thecssninja.com/javascript/fileapi
	processDragNDrop: function() {
		var dropContainer = this.el;
		dropContainer.addClass('dragndrop');
		dropContainer.set('html', '<p>'+this.options.content+'</p>');
		dropContainer.addEventListener("dragenter", function(event){this.init(); event.stopPropagation();event.preventDefault();}.bind(this), false);
		dropContainer.addEventListener("dragover", function(event){event.stopPropagation();event.preventDefault();}, false);
		dropContainer.addEventListener("drop", this.handleDrop.bind(this), false);
	},
	displayNotif: function (msg) {
		var pEl = new Element('p'),
			liEl = new Element('li');
		pEl.set('text', msg);
		this.fileListEl.grab(liEl.grab(pEl));
	},
	handleDrop: function (event) {
		var dt = event.dataTransfer,
			files = dt.files,
			count = files.length,
			self = this;
		if  (count>this.options.maxDropFile) {
			count=this.options.maxDropFile;
			this.displayNotif('Maximum number file upload exceeded, only '+this.options.maxDropFile+' will be uploaded');
		}
		event.stopPropagation();
		event.preventDefault();
		this.el.setStyle('height', this.height*(1+(count/2).toInt()));
		this.el.tween('opacity', 1);
		for (var i = 0; i < count; i++) {
			if(files[i].size < (this.options.maxSize * 1048576)) {
				var file = files[i],
					droppedFileName = file.name,
					reader = new FileReader();
					reader.name = name,
					reader.index = i,
					reader.file = file;

				reader.addEventListener("loadend", function(event){
					var imgPreviewFragment = document.createDocumentFragment(),
						domElements = [ document.createElement('li'), document.createElement('img'), ];
					if (!this.file) { self.displayNotif('Error: Cannot load file'); return; }
					domElements[1].src = this.result // base64 encoded string of local file(s)
					domElements[1].height = self.height-80;
					domElements[0].id = "item"+this.index;
					domElements[0].appendChild(domElements[1]);
					imgPreviewFragment.appendChild(domElements[0]);

					self.fileListEl.appendChild(imgPreviewFragment);
					self.processXHR(this.file, this.index);
				}, false);
				reader.readAsDataURL(file);
			} else {
				this.displayNotif("file is too big, needs to be below "+this.options.maxSize+"mb");
			}
		}
	},
	processXHR: function (file, index) {
		var xhr = new XMLHttpRequest(),
			getBinaryDataReader = new FileReader(),
			container = document.getElementById("item"+index),
			fileUpload = xhr.upload,
			boundary = 'multipartformboundary' + (new Date).getTime(),
			filename = file.fileName,
			progressDomElements = [
					document.createElement('div'),
					document.createElement('p'),
          document.createElement('p')
				];
			
		progressDomElements[0].className = "progressBar";
		progressDomElements[1].textContent = "0%";
    progressDomElements[2].textContent = filename;
    uploadedfiles[index] = filename;
		progressDomElements[0].appendChild(progressDomElements[1]);
		
		container.appendChild(progressDomElements[0]);
		
		fileUpload.addEventListener("progress", this.uploadProgressXHR.bindWithEvent(container), false);
		fileUpload.addEventListener("load", function(event){ container.className = "loaded"; }.bindWithEvent(container), false);
		fileUpload.addEventListener("error", this.displayNotif.bind(this, 'upload failed'), false);

		xhr.open("POST", this.options.url, true);
		xhr.setRequestHeader('content-type', 'multipart/form-data; boundary='+ boundary);
		
		getBinaryDataReader.addEventListener("loadend", function(evt){
			var dashdash = '--',
		    crlf     = '\r\n',
		    builder  = '';
		    // set header
	        builder += dashdash;
		    builder += boundary;
	        builder += crlf; 
	        builder += 'Content-Disposition: form-data; name="user_file"';
	        builder += '; filename="' + filename + '"';
		    builder += crlf;
	        builder += 'Content-Type: application/octet-stream';
	        builder += crlf;
	        builder += crlf; 

	        // Append binary data.
	        builder += evt.target.result;

	        // Mark end of the request.
	        builder += crlf;
	        builder += dashdash;
	        builder += boundary;
	        builder += dashdash;
	        builder += crlf;
						    
			xhr.sendAsBinary(builder);
			}, false);
		getBinaryDataReader.readAsBinaryString(file);
	},
	uploadProgressXHR: function (event) {
		if (event.lengthComputable) {
			var percentage = Math.round((event.loaded * 100) / event.total),
				loaderIndicator = this.firstChild.nextSibling.firstChild;
			if (percentage < 100) {
				loaderIndicator.style.width = (percentage*2) + "px";
				loaderIndicator.textContent = percentage + "%";
			}
		}
	}
});
