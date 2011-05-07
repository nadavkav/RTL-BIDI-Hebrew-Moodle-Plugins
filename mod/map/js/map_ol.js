Event.observe(window, 'load', loadOL_map);
var map, layer,markers,icons,bounds;
var nedsCurrentLoc ={};
var nedsCurrentLocIndex = 0;
function loadOL_map(){
    $('map').addClassName("olmap");
    map = new OpenLayers.Map( 'map' , {controls:[
         new OpenLayers.Control.ArgParser(),
         new OpenLayers.Control.Attribution(),
         new OpenLayers.Control.LayerSwitcher(),
         new OpenLayers.Control.Navigation(),
         new OpenLayers.Control.PanZoomBar(),
         new OpenLayers.Control.ScaleLine()
      ],
            maxResolution: 156543.0339,
      numZoomLevels: 20
      }
    );
    // Define the map layer
    // Note that we use a predefined layer that will be
    // kept up to date with URL changes
    // Here we define just one layer, but providing a choice
    // of several layers is also quite simple
    // Other defined layers are OpenLayers.Layer.OSM.Mapnik, OpenLayers.Layer.OSM.Maplint and OpenLayers.Layer.OSM.CycleMap
    //layerTilesAtHome = new OpenLayers.Layer.OSM.Osmarender("Osmarender");
      //  layer = new OpenLayers.Layer.WMS( "OpenLayers WMS", "http://labs.metacarta.com/wms/vmap0",{layers: 'basic'} );
    addlayers();
    makeIcons();
     bounds = new OpenLayers.Bounds();
    loadLocations();
   
    map.zoomToExtent(bounds);
    //map.zoomToMaxExtent();
}
function addlayers(){
   markers = new OpenLayers.Layer.Markers( "Markers" );
   map.addLayer(markers);
  var mapnik = new OpenLayers.Layer.OSM.Mapnik("Mapnik", {
      displayOutsideMaxExtent: true,
      wrapDateLine: true
   });
   map.addLayer(mapnik);

   var osmarender = new OpenLayers.Layer.OSM.Osmarender("Osmarender", {
      displayOutsideMaxExtent: true,
      wrapDateLine: true
   });
   map.addLayer(osmarender);

   var cyclemap = new OpenLayers.Layer.OSM.CycleMap("Cycle Map", {
      displayOutsideMaxExtent: true,
      wrapDateLine: true
   });
   map.addLayer(cyclemap);

}
function makeIcons(){
    var size = new OpenLayers.Size(20,30);
    var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
    icons = new Object();
    icons['student'] = new OpenLayers.Icon(baseMapURL + 'images/marker.png',size,offset);
    icons['extra'] = new OpenLayers.Icon(baseMapURL + 'images/marker_green.png',size,offset);
}

function createMapLocation (loc) {
    var point = new OpenLayers.LonLat(loc[0].longitude ,loc[0].latitude ).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
    bounds.extend(point);
    var data = {}; 
    if(loc.length==1){
        data.popupSize = new OpenLayers.Size(300, 200);
    }else{
       data.popupSize = new OpenLayers.Size(300, 220);
    }
    
    data.icon = icons[loc[0].type].clone();
    data['popupContentHTML'] = createPopUpHTML(loc);
    data.overflow="hidden";
    
    var feature = new OpenLayers.Feature(markers, point,data);
    feature.closeBox = true;
    var marker = feature.createMarker();
    var markerClick = function (evt) {
                if (this.popup == null) {
                    this.popup = this.createPopup(this.closeBox);
                    map.addPopup(this.popup,true);
                    this.popup.show();
                } else {
                    this.popup.toggle();
                }
                currentPopup = this.popup;
                OpenLayers.Event.stop(evt);
            };
      marker.events.register("mousedown", feature, markerClick);
       markers.addMarker(marker);
   
    }


