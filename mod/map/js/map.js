function loadLocations(){
    GroupedLocations = new Object();
    var newKey = "";
    var locObject;
    locations.each(function(l){
        newKey = l.latitude + l.longitude + "";//force string
        locObject = {latitude: l.latitude,longitude:l.longitude,html: makeLocationHTML(l),title: makeLocationTitle(l),icon: getIcon(l),picHTML:l.picHTML,type:l.type};
        if(Object.isUndefined(GroupedLocations[newKey]) ){
            GroupedLocations[newKey] = new Array();
        }
        GroupedLocations[newKey].push(locObject);
    });
    Object.keys(GroupedLocations).each(function(k){
        createMapLocation(GroupedLocations[k]);
    });
}
function makeLocationHTML(l){
    if(l.type == "student"){
        return "<div class='student-pic' >" + l.picHTML + "</div><div class='map_info_text'><span class='student-title' ><a href=\""+getDetailURL(l.picHTML)+"\" >"+ l.firstname + " " + l.lastname + "</a></span><br />"+ l.city + ", " + l.state+ ", " + l.country + "<p>" + l.description + "</p></div>";
        //return "<div>" + l.picHTML + "</div><div class='map_info_text'><p>"+ l.firstname + " " + l.lastname + "</p><p>"+ l.city + ", " + l.state+ ", " + l.country + "</p><p>" + l.description + "</p></div>";
    }else{
        return "<div class='map_info_text'><h2>"+ l.title + "</h2><p>" + l.description + "</p><p>Posted by: " + l.firstname + " " + l.lastname + "</p></div>";
    }
}
function makeLocationTitle(l){
    if(l.type == "student"){
        return l.firstname + " " + l.lastname;
    }else{
        return l.title;
    }
}
function createPopUpHTML(loc){
    if(loc.length>1){
                var newContent ="";
                for(var i = 0;i<loc.length;i++){
                   
                    nedsCurrentLoc[nedsCurrentLocIndex]  = loc;
                    
                    var imageTag = "";
                    if(loc[i].picHTML && loc[i].picHTML!='')
                    {
                        var imageURL = getImageURL(loc[i].picHTML);
                        imageTag = "<img src=\"" + imageURL + "\" width=\"15px\" height=\"15px\" border=\"0\" />";
                    }
                    //newContent += "<a href=\"javascript:showUserDetails("+ i +","+nedsCurrentLocIndex+");\" >"+ imageTag + " "+ loc[i].title+ "</a><br />";//showUserDetails(loc,"+i+");
                    newContent += "<option value='"+ i + "' >"+ loc[i].title+ "</option>";
                }
                newContent = "<select name='localSector' onchange='$(\"nedsInnerDiv"+ nedsCurrentLocIndex + "\").innerHTML = nedsCurrentLoc[" + nedsCurrentLocIndex + "][this.options[this.selectedIndex].value].html;' ><option>(Choose a location)</option>" + newContent + "</select>";
                newContent ="<div class='multi-loc'>"+newContent+"<div id='nedsInnerDiv"+ nedsCurrentLocIndex++ + "' class='inner-loc'  ><img src='" + baseMapURL + "img/blank.gif' height='190'></div>";
                return newContent;
    }
    return "<div>" + loc[0].html + "</div>";
}
function getDetailURL(picHTML)
{
    return  picHTML.replace( /^.*href="(.*?)".*$/, "$1");
    //<img class=\"userpicture defaultuserpic\" src=\"http://www.ned.ca/area51/coder5/moodlefn/pix/u/f1.png\" alt=\"Picture of Coder5 Coder5\" />
    
    
}
function getImageURL(picHTML)
{
    return  picHTML.replace( /^.*src="(.*?)".*$/, "$1");
    //<img class=\"userpicture defaultuserpic\" src=\"http://www.ned.ca/area51/coder5/moodlefn/pix/u/f1.png\" alt=\"Picture of Coder5 Coder5\" />
    
    
}
function getIcon(l){
    if(l.type != "student"){
        return "images/marker_green.png";
    }else{
        
    }
}