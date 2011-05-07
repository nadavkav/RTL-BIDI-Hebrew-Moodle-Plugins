/**
 * map.js
 * 
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.2
 * javascript functions for map module
 *
*/
google.load("maps", "2");
var map;
var bounds;
var GroupedLocations;
var reCenterPoint;

//var locations = new Array();
var maxZoomLevel = 14;
var mapPadFactor = .000001;
var MapControl;
var executeOnLoad = "";
var nedsCurrentLoc ={};
var nedsCurrentLocIndex = 0;
Event.observe(window, 'load', loadGoogleMap);

function createSimpleGMap(id){
    var map = new google.maps.Map2(document.getElementById(id));

    //map.disableDragging();
    //map.disableDoubleClickZoom();
    //map.disableScrollWheelZoom();

    return map;
}
function addMarkerToMap(marker,addToBounds){
    if(addToBounds){
        bounds.extend(marker.getLatLng());
    }
    map.addOverlay(marker);
}
function centerMapOnBounds(){
     var ne = bounds.getNorthEast();
     var sw = bounds.getSouthWest();
     var addSize=.5;
     var vertD = ne.distanceFrom(new google.maps.LatLng(sw.lat(),ne.lng()));
     var vertPad = vertD * mapPadFactor;
     var horiD = ne.distanceFrom(new google.maps.LatLng(ne.lat(),sw.lng()));
     var horiPad = horiD * mapPadFactor;
     
     //alert("b4=" +bounds.getNorthEast().distanceFrom(bounds.getSouthWest()));
     var newNE = new google.maps.LatLng(ne.lat()+vertPad,ne.lng()+horiPad);
     
     var newSW = new google.maps.LatLng(sw.lat()-vertPad,sw.lng()-horiPad);
     bounds.extend(newNE);
     bounds.extend(newSW);
     //alert("afta=" +bounds.getNorthEast().distanceFrom(bounds.getSouthWest()));
    
    var zoom = map.getBoundsZoomLevel(bounds);
    if(false){
        zoom = 12;
    }else if(zoom>maxZoomLevel){
        zoom=maxZoomLevel;
    }
    map.setCenter(bounds.getCenter(), zoom);
    if(bounds.length>10){
        alert("so many");
    }
}
function clearMap(){
    bounds = new google.maps.LatLngBounds();
    map.clearOverlays();
    
    if(MapControl){
        map.removeControl(MapControl);
    }
}




function createMapLocation (loc) {
    var point = new google.maps.LatLng(loc[0].latitude,loc[0].longitude);
    var mOptions = new Object();
    mOptions.title = "";
    for(var i = 0;i<loc.length;i++){
        mOptions.title += (i>0?",":"") + loc[i].title;
    }
    /*
    if(loc.length>1){
        mOptions.title = "Multiple Locations";
    }else{
        mOptions.title = loc[0].title;
    }*/

    if(loc[0].icon ){
        mOptions.icon = new google.maps.Icon(G_DEFAULT_ICON,loc[0].icon);
    }
    var marker = new google.maps.Marker(point,mOptions);
    google.maps.Event.addListener(marker, "click", function() {
        //remember center so can recenter after window closed
        reCenterPoint = map.getCenter();
        marker.openInfoWindowHtml(createPopUpHTML(loc));
    });

        bounds.extend(marker.getLatLng());


    map.addOverlay(marker);
    //addMarkerToMap(marker,true);

}
function reCenterMap(){
    map.panTo(reCenterPoint);
}
function loadGoogleMap () {
    if (google.maps.BrowserIsCompatible()) {
        $('map').addClassName("googlemap");
        map = createSimpleGMap("map");

        //map = new google.maps.Map2(document.getElementById("google_map"));
        map.addControl(new google.maps.SmallMapControl());
        map.addControl(new google.maps.MapTypeControl());
        bounds = new google.maps.LatLngBounds();
        //map.setCenter(new google.maps.LatLng(26.249083,-80.991211),7, google.maps._SATELLITE_MAP);
        map.setCenter(new google.maps.LatLng(26.249083,-80.991211),7);
        //showAllCounties();
        //makePropMarkers();
        loadLocations();
        centerMapOnBounds();
        GEvent.addListener(map,"infowindowclose", reCenterMap); 
        
    }
}
function loadLocations(){
    GroupedLocations = new Object();
    var newKey = "";
    var locObject;
    locations.each(function(l){
        //createMapLocationOld(l.latitude,l.longitude,makeLocationHTML(l),makeLocationTitle(l),true,getIcon(l));
        newKey = l.latitude + l.longitude + "";//force string
        locObject = {latitude: l.latitude,longitude:l.longitude,html: makeLocationHTML(l),title: makeLocationTitle(l),icon: getIcon(l),picHTML:l.picHTML};
        if(Object.isUndefined(GroupedLocations[newKey]) ){
            GroupedLocations[newKey] = new Array();
        }
        GroupedLocations[newKey].push(locObject);
    });
    Object.keys(GroupedLocations).each(function(k){
        createMapLocation(GroupedLocations[k]);
    });
    //GroupedLocations.each
}

