function getStringWeekDay(date){
	day = new Array("Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sab");
	weekDay = day[date.getDay()];
	return weekDay;
}
	   
function getStringMonth(date){
	monthName = "";
	month = new Array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
	monthName = month[date.getMonth()];
    return monthName;
}

function getDateTime(){
	var currentDate = new Date();
	var difference = currentDate.getTime() - <?=time()*1000;?>;
	var time = new Date(currentDate.getTime() + difference);		
//	var weekDay = getStringWeekDay(currentDate);
	var day = currentDate.getDate(time);
	var month = getStringMonth(currentDate);
	var year = currentDate.getFullYear(time);
	var hour = currentDate.getHours(time);
	var minute = currentDate.getMinutes(time);
		
	if(day < 10){
		day = "0" + day;
	}

	if(minute < 10){
	   minute = "0" + minute;
	}

	var second = currentDate.getSeconds(time);

	if(second < 10){
	   second = "0" + second;
	}

	stringDate = day + " / " + month + " / " + year + " - " + hour + ":" + minute + ":" + second;
	return stringDate;
}

function putTime(){
	document.getElementById('time').innerHTML=getDateTime();
	setTimeout("putTime()", 1000);
}

function replaceImage(object, src){
	object.src = src;	
}

function showTitleRight(event, title, description){
	document.getElementById("title").innerHTML = title;
	document.getElementById("description").innerHTML = description;
	document.getElementById("boxTitle").style.visibility = "visible";
	document.getElementById("boxTitle").style.top = event.clientY + document.body.scrollTop;
	document.getElementById("boxTitle").style.left = event.clientX + document.body.scrollLeft + 15;
}

function showTitleLeft(event, title, description){
	var length = title.length < description.length ? description.length : title.length;
	document.getElementById("title").innerHTML = title;
	document.getElementById("description").innerHTML = description;
	document.getElementById("boxTitle").style.visibility = "visible";
	document.getElementById("boxTitle").style.top = event.clientY + document.body.scrollTop - 5;
	document.getElementById("boxTitle").style.left = event.clientX + document.body.scrollLeft - length*9;
}

function hideTitle(){
	document.getElementById("boxTitle").style.visibility = "hidden";
}

function changeDimensions(object, incrementWidth, incrementHeight){
	object.style.width= eval(object.style.width.replace('px', '') + incrementWidth) + "px";
	object.style.height= eval(object.style.height.replace('px', '') + incrementHeight) + "px";
}