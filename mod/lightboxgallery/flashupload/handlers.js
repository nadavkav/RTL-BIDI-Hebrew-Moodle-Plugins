
/* This is an example of how to cancel all the files queued up.  It's made somewhat generic.  Just pass your SWFUpload
object in to this method and it loops through cancelling the uploads. */
function cancelQueue(instance) {
	document.getElementById(instance.customSettings.cancelButtonId).disabled = true;
	instance.stopUpload();
	var stats;
	
	do {
		stats = instance.getStats();
		instance.cancelUpload();
	} while (stats.files_queued !== 0);
	
}

/* **********************
   Event Handlers
   These are my custom event handlers to make my
   web application behave the way I went when SWFUpload
   completes different tasks.  These aren't part of the SWFUpload
   package.  They are part of my application.  Without these none
   of the actions SWFUpload makes will show up in my application.

 hgmod:start
Modifications made by Daem0nX (03.29.08)

 Modification to uploadStart
 Captures start time (iTime)

 Modification to uploadSuccess
 Now shows "Completed in xx.yy minutes/seconds

 Additional vars that can be used in UploadProgress
 Default info format - 0.1 of 3 MB at 51 KB/sec; 01:39 remain (2%)
 Look in that section for more info
 Upload speed
 Time elapsed
 Time remaining
//hgmod:ende
**********************	*/

var iTime = "";	//intial time
var Timeleft = "";	//time left

//roundNumber found via google
function roundNumber(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

//minsec created by Daem0nX (03.29.08)
function minsec(time, tempTime) {
var ztime;
	if (time == "m") {
		ztime = Math.floor(tempTime/60);
		if (ztime < 10) {
			ztime = "0" + ztime;	
		}
	} else if (time == "s") {
		ztime = Math.ceil(tempTime % 60);
		if (ztime < 10) {
			ztime = "0" + ztime;	
		}
	} else {
		ztime = "minsec error...";
	}
return ztime;
}   
   
function fileDialogStart() {
	/* I don't need to do anything here */
}
function fileQueued(file) {
	try {
		// You might include code here that prevents the form from being submitted while the upload is in
		// progress.  Then you'll want to put code in the Queue Complete handler to "unblock" the form
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus(strpending); //hgmod		
		progress.toggleCancel(true, this);

	} catch (ex) {
		this.debug(ex);
	}

}

function fileQueueError(file, errorCode, message) {
	try {
		if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
			alert("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
			return;
		}

		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);

		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			progress.setStatus(strfile_toobig); //hgmod
			this.debug("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			progress.setStatus(strzero_byte); //hgmod
			this.debug("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
			progress.setStatus("Invalid File Type.");
			this.debug("Error Code: Invalid File Type, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
			alert("You have selected too many files.  " +  (message > 1 ? "You may only add " +  message + " more files" : "You cannot add any more files."));
			break;
		default:
			if (file !== null) {
				progress.setStatus("Unhandled Error");
			}
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function fileDialogComplete(numFilesSelected, numFilesQueued) {
	try {
		if (this.getStats().files_queued > 0) {
			document.getElementById(this.customSettings.cancelButtonId).disabled = false;
		}
		
		/* I want auto start and I can do that here */
		this.startUpload();
	} catch (ex)  {
        this.debug(ex);
	}
}

function uploadStart(file) {
	try {
	    //hgmod
		//Capture start time
		var currentTime = new Date()
		iTime = currentTime;
		//Set Timeleft to estimating
		//hgmod
		
		Timeleft = strestimating;	
		/* I don't want to do any file validation or anything,  I'll just update the UI and return true to indicate that the upload should start */
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("Uploading...");
		progress.toggleCancel(true, this);
	}
	catch (ex) {
	}
	
	return true;
}

function uploadProgress(file, bytesLoaded, bytesTotal) {

	try {
		var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);

		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setProgress(percent);
		
		//hgmod:start
		var currentTime = new Date()		
		
		var tempTime = 0;
		//rndfilesize = round file size		
		var rndfilesize = roundNumber(((file.size/1024)/1024),1);
		//uploaded = how much has been uploaded
		var uploaded = roundNumber(((bytesLoaded/1024)/1024),1);
		//uTime = uploadTime (time spent uploading)
		var uTime = (Math.ceil(currentTime-iTime)/1000);
		//uSpeed = uploadSpeed (40 kB/s)
		var uSpeed = Math.floor(roundNumber(((bytesLoaded/uTime)/1024),2));
		//tempTime = store time for following functions
		var tempTime = uTime;
		//uploadTime in min:sec
		uTime = minsec("m", tempTime) + ":" + minsec("s", tempTime) + " elapsed";
		//tempTime = reassign val
		tempTime = roundNumber(((((bytesTotal-bytesLoaded)/uSpeed)/60)/10),2);
		if (tempTime != "Infinity") {
			if (tempTime > 0) {
				//if greater than 0
				//Timeleft in min:sec
				Timeleft = minsec("m", tempTime) + ":" + minsec("s", tempTime) + " " + strtimeremain;
			} else {
				Timeleft = strestimating;
			}
		} else {
			Timeleft = strestimating;
		}
		
		//Variables available
		//uSpeed = the rate of upload (40 kB/s)
		//uploaded = how much of the file has upload in MB
		//rndfilesize = file size in MB
		//uTime = how much time has been spent uploading in min:sec (xx:yy elapsed)
		//Timeleft = how much time is left in min:sec (xx:yy remain)
		progress.setStatus(uploaded + ' ' + strof + ' ' + rndfilesize + ' MB ' + strat + ' ' + uSpeed + ' KB/sec; ' + Timeleft + ' (' + percent + '%)');
		//hgmod:ende
		
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadSuccess(file, serverData) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		
		//hgmod:start
	    //Calculate upload time
		var cTime = (Math.ceil(currentTime-iTime)/1000);
		var zmin = 0;
		var zsec = 0;
		zmin = Math.floor(cTime/60);
		if (zmin < 10) {
			zmin = "0" + zmin;	
		}
		zsec = Math.ceil(cTime % 60);
		if (zsec < 10) {
			zsec = "0" + zsec;	
		}
		//Show how long the upload took
		progress.setStatus(struploadsuccess + zmin + ":" + zsec);		
        //hgmod:ende		
		
		progress.toggleCancel(false);

	} catch (ex) {
		this.debug(ex);
	}
}

function uploadComplete(file) {
	try {
		/*  I want the next upload to continue automatically so I'll call startUpload here */
		if (this.getStats().files_queued === 0) {
			document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			//hgmod
		    refresh_folder();
			//hgmod
		} else {	
			this.startUpload();
		}
	} catch (ex) {
		this.debug(ex);
	}

}

function uploadError(file, errorCode, message) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);

		switch (errorCode) {
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			progress.setStatus("Upload Error: " + message);
			this.debug("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.MISSING_UPLOAD_URL:
			progress.setStatus("Configuration Error");
			this.debug("Error Code: No backend file, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			progress.setStatus("Upload Failed.");
			this.debug("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			progress.setStatus("Server (IO) Error");
			this.debug("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			progress.setStatus("Security Error");
			this.debug("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			progress.setStatus("Upload limit exceeded.");
			this.debug("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SPECIFIED_FILE_ID_NOT_FOUND:
			progress.setStatus("File not found.");
			this.debug("Error Code: The file was not found, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			progress.setStatus("Failed Validation.  Upload skipped.");
			this.debug("Error Code: File Validation Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			if (this.getStats().files_queued === 0) {
				document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			}
			progress.setStatus(strcancelled); //hgmod
			progress.setCancelled();
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			progress.setStatus("Stopped");
			break;
		default:
			progress.setStatus("Unhandled Error: " + error_code);
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}