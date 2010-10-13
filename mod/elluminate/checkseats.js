// $Id: checkseats.js,v 1.1 2009-03-23 14:42:35 jfilip Exp $

function checkAvailability(obj) {
/// This function will return the query string to check seat availabilty.
    if (!(obj.seats.value = parseInt(obj.seats.value))) {
        obj.seats.value = 0;
    }

    if ((obj.seats.value == 0) || (obj.seats.value == '')) {
        return false;
    }

    var queryString = "";
    queryString += "meetingID=" + obj.meetingid.value;
    queryString += "&startTime=" + obj.starttime.value;
    queryString += "&endTime=" + obj.endtime.value;
    queryString += "&reservedSeatCount=" + obj.seats.value;
    queryString += "&meetingName=" + escape(obj.name.value);
    queryString += "&cid=" + obj.course.value;
    queryString += "&cmid=" + obj.id.value;

    return openpopup('/mod/elluminate/checkseats.php?' + queryString, 'availability', 'scrollbars=yes,resizable=no,width=640,height=300');
}
