<?php 

    require_once("../../../../../config.php");

    $courseid = optional_param('courseid', SITEID, PARAM_INT);
    $userid = optional_param('userid', -1, PARAM_INT);

    require_login($id);
    require_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $courseid,$userid));

    @header('Content-Type: text/html; charset=utf-8');

    $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);

    if ($httpsrequired or (!empty($_SERVER['HTTPS']) and $_SERVER['HTTPS'] != 'off')) {
        $url = preg_replace('|https?://[^/]+|', '', $CFG->wwwroot).'/lib/editor/htmlarea/custom_plugins/insertimage/';
    } else {
        $url = $CFG->wwwroot.'/lib/editor/htmlarea/custom_plugins/insertimage/';
    }

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="author" content="The CSS Ninja" />
	<meta name="keywords" content="Css, CSS Ninja, The CSS Ninja, JavaScript, Web, xhtml, html, browsers, HTML5, CSS3" />
	<meta name="description" content="Using drag drop API, File API and XmlHttpRequest 2 to create a drag and drop upload system using only JavaScript" />
	<meta name="robots" content="all" />
	<meta name="copyright" content="The CSS Ninja" />
	
	<link rel="stylesheet" type="text/css" href="_styles.css" media="screen" />
	
	<script type="text/javascript">
		var TCNDDU = TCNDDU || {};
		
		(function(){
			var dropContainer,
				dropListing;
			
			TCNDDU.setup = function () {
				dropListing = document.getElementById("output-listing01");
				dropContainer = document.getElementById("output");
				
				dropContainer.addEventListener("dragenter", function(event){dropListing.innerHTML = '';event.stopPropagation();event.preventDefault();}, false);
				dropContainer.addEventListener("dragover", function(event){event.stopPropagation(); event.preventDefault();}, false);
				dropContainer.addEventListener("drop", TCNDDU.handleDrop, false);
			};
			
			TCNDDU.uploadProgressXHR = function (event) {
				if (event.lengthComputable) {
					var percentage = Math.round((event.loaded * 100) / event.total);
					if (percentage < 100) {
						event.target.log.firstChild.nextSibling.firstChild.style.width = (percentage*2) + "px";
						event.target.log.firstChild.nextSibling.firstChild.textContent = percentage + "%";
					}
				}
			};
			
			TCNDDU.loadedXHR = function (event) {
				var currentImageItem = event.target.log;
				
				currentImageItem.className = "loaded";
				console.log("xhr upload of "+event.target.log.id+" complete");
			};
			
			TCNDDU.uploadError = function (error) {
				console.log("error: " + error);
			};
			
			TCNDDU.processXHR = function (file, index) {
				var xhr = new XMLHttpRequest(),
					container = document.getElementById("item"+index),
					fileUpload = xhr.upload,
					progressDomElements = [
						document.createElement('div'),
						document.createElement('p')
					];

				progressDomElements[0].className = "progressBar";
				progressDomElements[1].textContent = "0%";
				progressDomElements[0].appendChild(progressDomElements[1]);
				
				container.appendChild(progressDomElements[0]);
				
				fileUpload.log = container;
				fileUpload.addEventListener("progress", TCNDDU.uploadProgressXHR, false);
				fileUpload.addEventListener("load", TCNDDU.loadedXHR, false);
				fileUpload.addEventListener("error", TCNDDU.uploadError, false);

				xhr.open("POST", "upload.php?courseid=<?php echo $courseid; ?>&userid=<?php echo $userid; ?>");
        //xhr.open("POST", "upload.php");
				xhr.overrideMimeType('text/plain; charset=x-user-defined-binary');
				xhr.sendAsBinary(file.getAsBinary());
			};
			
			TCNDDU.handleDrop = function (event) {
				var dt = event.dataTransfer,
					files = dt.files,
					imgPreviewFragment = document.createDocumentFragment(),
					count = files.length,
					domElements;
					
				event.stopPropagation();
				event.preventDefault();

				for (var i = 0; i < count; i++) {
					domElements = [
						document.createElement('li'),
						document.createElement('a'),
						document.createElement('img')
					];
				
					domElements[2].src = files[i].getAsDataURL(); // base64 encodes local file(s)
					domElements[2].width = 300;
					domElements[2].height = 200;
					domElements[1].appendChild(domElements[2]);
					domElements[0].id = "item"+i;
					domElements[0].appendChild(domElements[1]);
					
					imgPreviewFragment.appendChild(domElements[0]);
					
					dropListing.appendChild(imgPreviewFragment);
					
					TCNDDU.processXHR(files.item(i), i);
				}
			};
			
			window.addEventListener("load", TCNDDU.setup, false);
		})();
	</script>
	
	<title>Using the File API to upload files by dragging and dropping from desktop | The CSS Ninja</title>

</head>
<body>
	
	<div id="output" class="clearfix">
		<ul id="output-listing01"></ul>
	</div>
	
</body>
</html>
