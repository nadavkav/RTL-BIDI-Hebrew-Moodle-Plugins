// $Id: checkavailability.js,v 1.1 2009-03-23 14:42:35 jfilip Exp $

function checkAvailability(objid) {
    obj = document.getElementById(objid);

/// This function will return the query string to check seat availabilty.
    if (!(obj.seats.value = parseInt(obj.seats.value))) {
        obj.seats.value = 0;
    }

    if ((obj.seats.value == 0) || (obj.seats.value == '')) {
         return false;
    }

    var queryString = "";
    queryString += "startDay=" + obj["timestart[day]"].value;
    queryString += "&startMonth=" + obj["timestart[month]"].value;
    queryString += "&startYear=" + obj["timestart[year]"].value;
    queryString += "&startHour=" + obj["timestart[hour]"].value;
    queryString += "&startMinute=" + obj["timestart[minute]"].value;
    queryString += "&endDay=" + obj["timeend[day]"].value;
    queryString += "&endMonth=" + obj["timeend[month]"].value;
    queryString += "&endYear=" + obj["timeend[year]"].value;
    queryString += "&endHour=" + obj["timeend[hour]"].value;
    queryString += "&endMinute=" + obj["timeend[minute]"].value;
    queryString += "&reservedSeatCount=" + obj.seats.value;
    queryString += "&meetingName=" + escape(obj.name.value);
    queryString += "&cid=" + obj.course.value;
    queryString += "&cmid=" + obj.coursemodule.value;

    if ((obj.instance.value != '') && (obj.instance.value > 0)) {
        queryString += "&meetingID=" + obj.instance.value;
    }

    return openpopup('/mod/elluminate/checkseats.php?' + queryString, 'availability', 'scrollbars=yes,resizable=no,width=640,height=300');
}
