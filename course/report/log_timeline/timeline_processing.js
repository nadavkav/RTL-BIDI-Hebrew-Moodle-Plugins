var tl;

var eventSource = new Timeline.DefaultEventSource(); 
window.onload=onLoad;
window.onresize=onResize;

var theme = Timeline.ClassicTheme.create();
    theme.event.label.width = 300; // width in pixels of the label of timeline, it resolves the problem with the dots
    theme.event.bubble.width = 250; // width of the dialogue 
    theme.event.bubble.height = 200; // height of the dialogue

menuuser_everybody=false;
menuuser_filter="";

menudate_alldays=false;
menudate_filter="";

modaction_everything=false;
modaction_filter="";

function onLoad() {
 // For load events is important to create a event source and after load information in xml file

    var today = new Date();
    var a_today=new Array();
    today=today.toString();
    a_today=today.split(" ");

    if (navigator.appName == "Netscape" || navigator.appName == "Opera") // Format today = Month Day Year Time GMT
    {
    today = a_today[1]+" "+a_today[2]+" "+a_today[3]+" "+a_today[4]+" GMT+0100"; //  For Mozilla & Opera
    }
    else if (navigator.appName == "Microsoft Internet Explorer")
    {
    today = a_today[1]+" "+a_today[2]+" "+a_today[5]+" "+a_today[3]+" GMT+0100"; //  Only for Internet Explorer
    }

 var bandInfos = [
    Timeline.createBandInfo({
        eventSource:    eventSource,
        width:          "80%", 
        intervalUnit:   Timeline.DateTime.MINUTE, 
        intervalPixels: 60,
        date:           today, 
        theme:          theme,
        timeZone:       +1
    }),
    Timeline.createBandInfo({
        eventSource:    eventSource,
        showEventText:  false, 
        trackHeight:    0.2,
        trackGap:       0.2,
        width:          "10%", 
        intervalUnit:   Timeline.DateTime.DAY, 
        intervalPixels: 200,
        date:           today,
        theme:          theme,
        timeZone:       +1
    }),
    Timeline.createBandInfo({
        eventSource:    eventSource,
        showEventText:  false,
        trackHeight:    0.2,
        trackGap:       0.2,
        width:          "10%", 
        intervalUnit:   Timeline.DateTime.MONTH, 
        intervalPixels: 100,
        date:           today, 
        theme:          theme,
        timeZone:       +1

    })
  ];
  
  // Synchronization of the bands and activation the highlight
  bandInfos[1].syncWith=0;
  bandInfos[2].syncWith=1; 
  bandInfos[2].highlight=true;
  bandInfos[1].highlight=true;
  
  bandInfos[1].eventPainter.setLayout(bandInfos[0].eventPainter.getLayout());
  bandInfos[2].eventPainter.setLayout(bandInfos[1].eventPainter.getLayout());
  
  document.getElementById("menuuser").onchange=change_user;
  document.getElementById("menudate").onchange=change_date;
  document.getElementById("menumodid").onchange=change_modid;
  document.getElementById("menumodaction").onchange=change_modaction;

  var group = null;
  group = document.getElementById("menugroup");
    if (group != null) 
    {
        group.onchange=change_group;
        for (var i=0; i<group.options.length; i++)
        {
            if (group.options[i].selected) 
            {
                modify_file_xml("group", group.options[i].value);
                break;
            }
        }
    }
 
  tl = Timeline.create(document.getElementById("my-timeline"), bandInfos, Timeline.HORIZONTAL);

  if ((typeof file_xml == "string") && file_xml.length>0) 
    {
        tl.loadXML(file_xml, function(xml, url) { eventSource.loadXML(xml, url); });
    }
  else {alert("ERROR:file_xml isn't defined");}
  
  context_timelines_variable();
}


var resizeTimerID = null;
function onResize() {
    if (resizeTimerID == null) {
        resizeTimerID = window.setTimeout(function() {
            resizeTimerID = null;
            tl.layout();
        }, 500);
    }
}


function change_user()
{
    var menuuser=document.getElementById("menuuser");

    for (var i=0; i<menuuser.options.length; i++)
    {
        if (menuuser.options[i].selected)
        {
            modify_file_xml("menuuser", menuuser.options[i].value);

            change_title(document.getElementById("title_timeline"), "user", menuuser.options[i].text);
     
            if (menuuser_everybody) // Filter
            { 
                if (menuuser.options[i].value != "0") 
                { menuuser_filter = menuuser.options[i].text; }
                else { menuuser_filter=""; }

                filter_timeline([0,1,2]);
            }
         
            else //Load the new xml file, when it selects all the users ->  it will start to filter 
            {
                if (menuuser.options[i].value == "0") {menuuser_everybody=true;}
                
                if ((typeof file_xml == "string") && file_xml.length>0) 
                {
                    eventSource.clear();
                    tl.loadXML(file_xml, function(xml, url) { eventSource.loadXML(xml, url); });
                    context_timelines_variable();
                    clean_filters([0,1,2]);
                }
                else {alert("ERROR:file_xml no esta definido o no es un string");}
            }
            
            break;
        }
    }
}

function change_date()
{
    var menudate = document.getElementById("menudate");
    
    for (var i=0; i<menudate.options.length; i++)
    {
        if (menudate.options[i].selected)
        {
            modify_file_xml("menudate", menudate.options[i].value);
            
            change_title(document.getElementById("title_timeline"), "date", menudate.options[i].text);

            if (menudate_alldays)
            {
                if (menudate.options[i].value != "0") 
                { 
                    var filter=new Array();
                    filter = menudate.options[i].id.split('_');
                    menudate_filter= filter[0]+' '+filter[1]+' '+filter[2]; // Format "Month day year"
                } 
                else { menudate_filter=""; }
                filter_timeline([0,1,2]);
            }
            else 
            {
                if ((typeof file_xml == "string") && file_xml.length>0) 
                {
                    eventSource.clear();
                    tl.loadXML(file_xml, function(xml, url) { eventSource.loadXML(xml, url); });
                    context_timelines_variable();
                    clean_filters([0,1,2]);
                }
                else {alert("ERROR:file_xml isn't defined");}
            }
        }
    }
}

function change_modid()
{
    var modid=document.getElementById("menumodid");
        
    for (var i=0; i<modid.options.length; i++)
    {
        if (modid.options[i].selected) 
        {
            modify_file_xml("modid", modid.options[i].value);

            if ((typeof file_xml == "string") && file_xml.length>0) 
            {
                eventSource.clear();
                tl.loadXML(file_xml, function(xml, url) { eventSource.loadXML(xml, url); });
                context_timelines_variable();
                clean_filters([0,1,2]);
            }
            else {alert("ERROR:file_xml isn't defined");}
            
            break;
        }
    }
}

function change_modaction()
{
    var modaction = document.getElementById("menumodaction");
    
    for (var i=0; i<modaction.options.length; i++)
    {
        if (modaction.options[i].selected)
        {
            modify_file_xml("modaction", modaction.options[i].value);
            
            if (modaction_everything)
            {
                if (modaction.options[i].value != "0") 
                { modaction_filter=modaction.options[i].value; } 
                else { modaction_filter=""; }
                filter_timeline([0,1,2]);
            }
            else 
            {
                if ((typeof file_xml == "string") && file_xml.length>0) 
                {
                    eventSource.clear();
                    tl.loadXML(file_xml, function(xml, url) { eventSource.loadXML(xml, url); });
                    context_timelines_variable();
                    clean_filters([0,1,2]);
                }
                else {alert("ERROR:file_xml isn't defined");}
            }
        }
    }
}

function change_group()
{
    var group=document.getElementById("menugroup");
        
    for (var i=0; i<group.options.length; i++)
    {
        if (group.options[i].selected) 
        {
            modify_file_xml("group", group.options[i].value);

            if ((typeof file_xml == "string") && file_xml.length>0) 
            {
                eventSource.clear();
                tl.loadXML(file_xml, function(xml, url) { eventSource.loadXML(xml, url); });
                context_timelines_variable();
                clean_filters([0,1,2]);
            }
            else {alert("ERROR:file_xml isn't defined");}
            
            break;
        }
    }
}

function modify_file_xml(select_name, s_value)
{
    switch (select_name)
    {
        case "menuuser":    var div_xml = new Array();
                            div_xml = file_xml.split("&");
                            div_xml[2] = "user="+s_value;
                            file_xml = div_xml.join("&");
                            break;
    
        case "menudate":    var div_xml = new Array();
                            div_xml = file_xml.split("&");
                            div_xml[3] = "date="+s_value;
                            file_xml = div_xml.join("&");
                            break;

        case "modid":       var div_xml = new Array();
                            div_xml = file_xml.split("&");
                            div_xml[5] = "modid="+s_value;
                            file_xml = div_xml.join("&");
                            break;

        case "modaction":   var div_xml = new Array();
                            div_xml = file_xml.split("&");
                            div_xml[6] = "modaction="+s_value;
                            file_xml = div_xml.join("&");
                            break;

        case "group":       var div_xml = new Array();
                            div_xml = file_xml.split("&");
                            div_xml[7] = "group="+s_value;
                            file_xml = div_xml.join("&");
                            break;

    default: alert("Error: modify_file_xml has one or more incorrect parameters.");
    }

}

function context_timelines_variable()
{
    if (file_xml.length > 0)
    {
        var div_xml = new Array();
        div_xml = file_xml.split("&");
        
        menuuser_filter="";
        if (div_xml[2]=="user=0") { menuuser_everybody = true; } // User
        else { menuuser_everybody=false; }

        menudate_filter="";
        if (div_xml[3]=="date=0") { menudate_alldays = true; } // Date
        else { menudate_alldays=false; }

        modaction_filter="";
        if (div_xml[6]=="modaction=0") { modaction_everything = true; } // Actions
        else { modaction_everything=false; }
    }
}

function filter_timeline( bandIndices )
{

 var filterMatcher = null;
 var menuuser_length = menuuser_filter.length; 
 var menudate_length = menudate_filter.length; 
 var modaction_length = modaction_filter.length; 
 
//1
if ((modaction_length==0) && (menudate_length==0) && (menuuser_length==0))
{filterMatcher = null;}
//2
if ((modaction_length==0) && (menudate_length==0) && (menuuser_length>0))
{
    var ruser = new RegExp(menuuser_filter, "i");
    filterMatcher = function(evt) 
    { return ruser.test(evt.getText()) || ruser.test(evt.getDescription()); };
}
//3
if ((modaction_length==0) && (menudate_length>0) && (menuuser_length==0))
{
    var mdy = menudate_filter.split(" ");
    var rdatedm = new RegExp(mdy[0]+" "+mdy[1], "i"); // Month Day
    var rdatey = new RegExp(mdy[2], "i"); // Year

    filterMatcher = function(evt) 
    { return rdatedm.test(evt.getStart()) && rdatey.test(evt.getStart()); };
}
//4
if ((modaction_length==0) && (menudate_length>0) && (menuuser_length>0))
{
    var ruser = new RegExp(menuuser_filter, "i");

    var mdy = menudate_filter.split(" ");
    var rdatedm = new RegExp(mdy[0]+" "+mdy[1], "i"); // Month Day
    var rdatey = new RegExp(mdy[2], "i"); // Year

    filterMatcher = function(evt) 
    { return (rdatedm.test(evt.getStart()) && rdatey.test(evt.getStart())) && (ruser.test(evt.getText()) || ruser.test(evt.getDescription())); };
}
//5
if ((modaction_length>0) && (menudate_length==0) && (menuuser_length==0))
{
    if (modaction_filter=="-view")
    {
        var raction = new RegExp("view","i");
        filterMatcher = function(evt) 
        { return !(raction.test(evt.getText())); };
    }
    else
    {
        var raction = new RegExp(modaction_filter,"i");
        filterMatcher = function(evt) 
        { return raction.test(evt.getText()); };
    }
}
//6
if ((modaction_length>0) && (menudate_length==0) && (menuuser_length>0))
{
    var ruser=new RegExp(menuuser_filter,"i");

    if (modaction_filter=="-view")
    {
        var raction = new RegExp("view","i");
        filterMatcher = function(evt) 
        { return (!raction.test(evt.getText())) && (ruser.test(evt.getText()) || ruser.test(evt.getDescription())); };
    }
    else
    {
        var raction = new RegExp(modaction_filter,"i");
        filterMatcher = function(evt) 
        { return (raction.test(evt.getText())) && (ruser.test(evt.getText()) || ruser.test(evt.getDescription())); };
    }
}
//7
if ((modaction_length>0) && (menudate_length>0) && (menuuser_length==0))
{
    var mdy = menudate_filter.split(" ");
    var rdatedm = new RegExp(mdy[0]+" "+mdy[1], "i"); // Month Day
    var rdatey = new RegExp(mdy[2], "i"); // Year

    if (modaction_filter=="-view")
    {
        var raction = new RegExp("view","i");
        filterMatcher = function(evt) 
        { return (!raction.test(evt.getText())) && (rdatedm.test(evt.getStart()) && rdatedy.test(evt.getStart())); };
    }
    else
    {
        var raction = new RegExp(modaction_filter,"i");
        filterMatcher = function(evt) 
        { return (raction.test(evt.getText())) && (rdatedm.test(evt.getStart()) && rdatedy.test(evt.getStart())); };
    }
}
//8
if ((modaction_length>0) && (menudate_length>0) && (menuuser_length>0))
{
    var mdy = menudate_filter.split(" ");
    var rdatedm = new RegExp(mdy[0]+" "+mdy[1], "i"); // Month Day
    var rdatey = new RegExp(mdy[2], "i"); // Year

    var ruser = new RegExp(menuuser_filter,"i");

    if (modaction_filter=="-view")
    {
        var raction = new RegExp("view","i");
        filterMatcher = function(evt) 
        { return (!raction.test(evt.getText())) && (ruser.test(evt.getText()) || ruser.test(evt.getDescription()))  
                && (rdatedm.test(evt.getStart()) && rdatedy.test(evt.getStart())); };
    }
    else
    {
        var raction = new RegExp(modaction_filter,"i");
        filterMatcher = function(evt) 
        { return (raction.test(evt.getText())) && (ruser.test(evt.getText())) && (rdatedm.test(evt.getStart()) && rdatedy.test(evt.getStart())); };
    }
}

    for (var i = 0; i < bandIndices.length; i++) 
        {
            var bandIndex = bandIndices[i];
            tl.getBand(bandIndex).getEventPainter().setFilterMatcher(filterMatcher);
        }
    tl.paint();

}


function clean_filters(bandIndices)
{
    filterMatcher=null;
    for (var i = 0; i < bandIndices.length; i++) 
        {
            var bandIndex = bandIndices[i];
            tl.getBand(bandIndex).getEventPainter().setFilterMatcher(filterMatcher);
        }
    tl.paint();
}

function func_high(){
    var botton=document.getElementById("button_highlight");

    if (botton.value=="HighlightOff") 
    {
        botton.value="HighlightOn";
        
        var div_time=document.getElementById("menu_highlight");
        setupHighlightControls(div_time, tl, [0,1,2], theme);
        
    }
    else {
    
        botton.value="HighlightOff";
        table = document.getElementById("table_high");
        clearAll(tl, [0,1,2], table);
        table.parentNode.removeChild(table);
    }
    
    return false;
}

function setupHighlightControls(div, timeline, bandIndices, theme1) 
{
    var table = document.createElement("table");
    table.id = "table_high";

    var tr = table.insertRow(0);
    
    td = tr.insertCell(0);
    td.innerHTML = "Highlight:";
    
    var handler = function(elmt, evt, target) {
        onKeyPress(timeline, bandIndices, table);
    };
    
    tr = table.insertRow(1);
    tr.style.verticalAlign = "top";
    
    for (var i = 0; i < theme1.event.highlightColors.length; i++) {
        td = tr.insertCell(i);
        
        input = document.createElement("input");
        input.type = "text";
        Timeline.DOM.registerEvent(input, "keypress", handler);
        td.appendChild(input);
        
        var divColor = document.createElement("div");
        divColor.style.height = "0.5em";
        divColor.style.background = theme1.event.highlightColors[i];
        td.appendChild(divColor);
    }
    
    td = tr.insertCell(tr.cells.length);
    var button = document.createElement("button");
    button.innerHTML = "Clear All";
    Timeline.DOM.registerEvent(button, "click", function() {
        clearAll(timeline, bandIndices, table);
    });
    td.appendChild(button);

    div.appendChild(table);
}

var timerID = null;
function onKeyPress(timeline, bandIndices, table) {
    if (timerID != null) {
        window.clearTimeout(timerID);
    }
    timerID = window.setTimeout(function() {
        performFiltering(timeline, bandIndices, table);
    }, 300);
}
function cleanString(s) {
    return s.replace(/^\s+/, '').replace(/\s+$/, '');
}
function performFiltering(timeline, bandIndices, table) {
    timerID = null;
    
    var tr = table.rows[1];

    var regexes = [];
    var hasHighlights = false;
    for (var x = 0; x < tr.cells.length - 1; x++) {
        var input = tr.cells[x].firstChild;
        var text2 = cleanString(input.value);
        if (text2.length > 0) {
            hasHighlights = true;
            regexes.push(new RegExp(text2, "i"));
        } else {
            regexes.push(null);
        }
    }
    var highlightMatcher = hasHighlights ? function(evt) {
        var text = evt.getText();
        var description = evt.getDescription();
        for (var x = 0; x < regexes.length; x++) {
            var regex = regexes[x];
            if (regex != null && (regex.test(text) || regex.test(description))) {
                return x;
            }
        }
        return -1;
    } : null;
    
    for (var i = 0; i < bandIndices.length; i++) {
        var bandIndex = bandIndices[i];
        timeline.getBand(bandIndex).getEventPainter().setHighlightMatcher(highlightMatcher);
    }
    timeline.paint();
}
function clearAll(timeline, bandIndices, table) {
    var tr = table.rows[1];
    for (var x = 0; x < tr.cells.length - 1; x++) {
        tr.cells[x].firstChild.value = "";
    }
    
    for (var i = 0; i < bandIndices.length; i++) {
        var bandIndex = bandIndices[i];
        timeline.getBand(bandIndex).getEventPainter().setHighlightMatcher(null);
    }
    timeline.paint();
}

function count_events(url)
{
    var query = new Array();
    var url = window.location.href;
    url = url.substr(0, url.search(/index.php/i));
    query = file_xml.split("?");
    var newwin =window.open(url+"count.php?"+query[1], "Events", "width=300, height=50, resizable=yes, status=no");
    newwin.focus();
}

function change_title(title, select, txt)
{
    var atitle = new Array(); 
    atitle = title.innerHTML.split(":");
    
    switch( select )
    {
        case 'user':  atitle[1] = " " + txt + " ";
                      break;

        case 'date':  var aux = new Array();
                      aux = atitle[2].split("(");
                      aux[0] = txt+" ";
                      atitle[2] = aux.join("(");
                      atitle[2] = " " + atitle[2] + " ";
                      break;
        
        default: alert('Error: timeline_processing.js -> change_title.');
    }

    title.innerHTML = atitle.join(":");
}