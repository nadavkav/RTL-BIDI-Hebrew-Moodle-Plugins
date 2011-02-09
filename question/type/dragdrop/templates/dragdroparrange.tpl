<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en">
<html>
<head>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<meta http-equiv="imagetoolbar" content="no">
<title>{$title}</title>


<!-- added by Harry - Begin Drag and Drop and Resize Dependencies -->
{$yuilibs}
<!-- added by Harry - End Drag and Drop and Resize Dependencies -->


<script type="text/javascript">
{literal}
// Our custom hotspot implementation, extending YAHOO.util.Resize  YAHOO.util.DD
YAHOO.util.DDHotSpot = function(id, sGroup, config) {
    YAHOO.util.DDHotSpot.superclass.constructor.apply(this, arguments);
};

YAHOO.extend(YAHOO.util.DDHotSpot, YAHOO.util.Resize, {
     origZ: 0,
     startPos: false,
     backgroundId: false,
     id: false,

     initDDHotSpot: function() {
         // Get background position
         // Can not be stored statically becaus background can move while window resizing
         backgroundXY = YAHOO.util.Dom.getXY(this.backgroundId);
         // Set hotspot position
         XY    = new Array();
         if (this.startPos[0] == -1 && this.startPos[1] == -1) {
            // if no hotspot is defined position a new one on the gapImage
            objXY = YAHOO.util.Dom.getXY(this.gapImage.id);
            xDiff = parseInt(YAHOO.util.Dom.getStyle(this.gapImage.id, 'width')) / 4;
            yDiff = parseInt(YAHOO.util.Dom.getStyle(this.gapImage.id, 'height')) / 4;
            XY[0] = parseInt(objXY[0]) + xDiff;
            XY[1] = parseInt(objXY[1]) + yDiff;
         }
         else {
            // if hotspot is already defined
            XY[0] = parseInt(backgroundXY[0]) + parseInt(this.startPos[0]);
            XY[1] = parseInt(backgroundXY[1]) + parseInt(this.startPos[1]);
         }
         // Set position of the wrapper element (handels,...)
         YAHOO.util.Dom.setXY(this.getWrapEl().id, XY);
     },

     toggleDisplay: function(type) {
        // get id of the html element
        if (type == 'DDGapImage')
            objId = this.gapImage.id;
        else
            objId = this.id;
        // get current style and define the new one
        if (YAHOO.util.Dom.getStyle(objId, 'visibility') == 'hidden')
            newStyle = 'visible';
        else
            newStyle = 'hidden';
        // set new style
        // resize objekts have two html representations
        YAHOO.util.Dom.setStyle(objId, 'visibility', newStyle);
        if (type == 'DDHotSpot')
            YAHOO.util.Dom.setStyle(this.getWrapEl().id, 'visibility', newStyle);
     },

     snapElement: function(type) {
        // predefine ids
        if (type == 'DDHotSpot') {
            fixObjId  = this.gapImage.id;
            moveObjId = this.getWrapEl().id;
        }
        else {
            fixObjId  = this.getWrapEl().id;
            moveObjId = this.gapImage.id;
        }
        // get position
        objXY = YAHOO.util.Dom.getXY(fixObjId);
        // compute new position
        fixWidth   = parseInt(YAHOO.util.Dom.getStyle(fixObjId, 'width'));
        fixHeight  = parseInt(YAHOO.util.Dom.getStyle(fixObjId, 'height'));
        moveWidth  = parseInt(YAHOO.util.Dom.getStyle(moveObjId, 'width'));
        moveHeight = parseInt(YAHOO.util.Dom.getStyle(moveObjId, 'height'));
        objXY[0] = objXY[0] + parseInt((fixWidth - moveWidth) / 2)
        objXY[1] = objXY[1] + parseInt((fixHeight - moveHeight) / 2)
        // set position
        YAHOO.util.Dom.setXY(moveObjId, objXY);
     },

     computeData: function() {
        validHsPos = false;  // HotSpot position
        validGiPos = false;  // GapImage position

        // are the positions of hotspots and gapimages valid positions
        // Get hotspot position data
        hsXY     = YAHOO.util.Dom.getXY(this.id);
        hsWidth  = parseInt(YAHOO.util.Dom.getStyle(this.id, 'width'));
        hsHeight = parseInt(YAHOO.util.Dom.getStyle(this.id, 'height'));

        // Get gapimage position data
        giXY     = YAHOO.util.Dom.getXY(this.gapImage.id);
        giWidth  = parseInt(YAHOO.util.Dom.getStyle(this.gapImage.id, 'width'));
        giHeight = parseInt(YAHOO.util.Dom.getStyle(this.gapImage.id, 'height'));

        // Get background position data
        backgroundXY     = YAHOO.util.Dom.getXY(this.backgroundId);
        backgroundWidth  = parseInt(YAHOO.util.Dom.getStyle(this.backgroundId, 'width'));
        backgroundHeight = parseInt(YAHOO.util.Dom.getStyle(this.backgroundId, 'height'));

        // check if hotspot position is over the background
        if (hsXY[0] >= backgroundXY[0] && hsXY[0] <= backgroundXY[0] + backgroundWidth)
            if (hsXY[1] >= backgroundXY[1] && hsXY[1] <= backgroundXY[1] + backgroundHeight)
                validHsPos = true;

        // check if gapimage position is over the background
        if (giXY[0] >= backgroundXY[0] && giXY[0] <= backgroundXY[0] + backgroundWidth)
            if (giXY[1] >= backgroundXY[1] && giXY[1] <= backgroundXY[1] + backgroundHeight)
                validGiPos = true;

        // fill form values if hotspot position is valid
        if (validHsPos) {
            this.hotspot_x.value      = hsXY[0] - backgroundXY[0];
            this.hotspot_y.value      = hsXY[1] - backgroundXY[1];
            this.hotspot_width.value  = hsWidth;
            this.hotspot_height.value = hsHeight;
        }

        // fill form values if gapimage position is valid
        if (validGiPos) {
            this.gapimage_x.value      = giXY[0] - backgroundXY[0];
            this.gapimage_y.value      = giXY[1] - backgroundXY[1];
            this.gapimage_width.value  = giWidth;
            this.gapimage_height.value = giHeight;
        }
     }

});

var arrOfHotspots{$background->id}  = new Array();

function toggleDisplay(type, button) {
    // toggle object display
    for (var i = 0; i < arrOfHotspots.length; ++i) {
        arrOfHotspots[i].toggleDisplay(type);
    }
    // toggle button value
    if (type == 'DDHotSpot')
        if (button.value == showHotSpot)
            value = hideHotSpot;
        else
            value = showHotSpot;
    else
        if (button.value == showImages)
            value = hideImages;
        else
            value = showImages;

    button.value = value;
}

function snapElements(type, button) {
    for (var i = 0; i < arrOfHotspots.length; ++i) {
        arrOfHotspots[i].snapElement(type);
    }
}

function computeAllData(type) {
    for (var i = 0; i < arrOfHotspots.length; ++i) {
        arrOfHotspots[i].computeData();
    }
}

{/literal}
var hideHotSpot = '{$hidehotspots}';
var showHotSpot = '{$showhotspots}';
var hideImages  = '{$hideimages}';
var showImages  = '{$showimages}';
</script>

{$script1}

</head>
<body class="yui-skin-sam">
<!--<script type="text/javascript" src="{$ddscriptsource}"></script>-->






<form name="ddform" method="post" action="{$formaction}">
    <input type="hidden" name="id" value="{$id}">
    <input type="hidden" name="courseid" value="{$courseid}">
    <input type="hidden" name="returnurl" value="{$returnurl}">
    <input type="hidden" name="cmid" value="{$cmid}">
    <input type="hidden" name="process" value="savereturn">
{section name=img loop=$gapimages}
    <input type="hidden" name="gapimage{$gapimages[img].key}_x" id="gapimage{$gapimages[img].key}_x" value="" />
    <input type="hidden" name="gapimage{$gapimages[img].key}_y" id="gapimage{$gapimages[img].key}_y" value="" />
    <input type="hidden" name="gapimage{$gapimages[img].key}_width" id="gapimage{$gapimages[img].key}_width" value="" />
    <input type="hidden" name="gapimage{$gapimages[img].key}_height" id="gapimage{$gapimages[img].key}_height" value="" />
    <input type="hidden" name="gapimage{$gapimages[img].key}_positioned" value="" />
    <input type="hidden" name="hotspot{$gapimages[img].key}_x" id="hotspot{$gapimages[img].key}_x" value="" />
    <input type="hidden" name="hotspot{$gapimages[img].key}_y" id="hotspot{$gapimages[img].key}_y" value="" />
    <input type="hidden" name="hotspot{$gapimages[img].key}_width" id="hotspot{$gapimages[img].key}_width" value="" />
    <input type="hidden" name="hotspot{$gapimages[img].key}_height" id="hotspot{$gapimages[img].key}_height" value="" />
{/section}
<div id="border" style="border:solid medium black;">
    <div id="top">
        <table>

        {* placing the additional buttons in dependance of placing the medias *}
        {if $question->options->placemedia != 0}
            <tr>
                <td valign="top">
                        <input name="togglehotspots" type="button" value="{$hidehotspots}" onClick="toggleDisplay('DDHotSpot', this)" style="width:220px;" />
                        <input name="snaphotspots" type="button" value="{$snaphotspots}" onClick="snapElements('DDHotSpot')" style="width:220px;" />
                </td>
            </tr>
            <tr>
                <td valign="top">
                        <input name="toggleimages" type="button" value="{$hideimages}" onClick="toggleDisplay('DDGapImage', this)" style="width:220px;" />
                        <input name="snapimages" type="button" value="{$snapimages}" onClick="snapElements('DDGapImage')" style="width:220px;" />
                </td>
            </tr>
            <tr>
                <td>

        {else}

            <tr>
                <td valign="top">
                        <div style="padding:2em 0em 1em 0em;"><input name="togglehotspots" type="button" value="{$hidehotspots}" onClick="toggleDisplay('DDHotSpot', this)" style="width:220px;" /></div>
                        <div style="padding:0em 0em 1em 0em;"><input name="toggleimages" type="button" value="{$hideimages}" onClick="toggleDisplay('DDGapImage', this)" style="width:220px;" /></div>
                        <div style="padding:0em 0em 1em 0em;"><input name="snaphotspots" type="button" value="{$snaphotspots}" onClick="snapElements('DDHotSpot')" style="width:220px;" /></div>
                        <div style="padding:0em 0em 0em 0em;"><input name="snapimages" type="button" value="{$snapimages}" onClick="snapElements('DDGapImage')" style="width:220px;" /></div>
                </td>
                <td>

        {/if}


                <table>
                    <tr><td>

                    <div id="topright" style="">
                        <div id="toprighttop">
                            <input type="submit" value="{$submitsavereturn}" onClick="computeAllData();" />
                            <input type="submit" value="{$submitsavecontinue}" onClick="document.ddform.process.value='savecontinue'; computeAllData();" />
                            <input type="submit" value="{$submitcancel}" onClick="document.ddform.process.value='cancel'" />
                        </div>
                        <div id="toprightbottom" style="padding:4px;">
                            <div style="height:{$background->height}px;width:{$background->width}px;">
                                   {$background->mediatag}
                            </div>

                            <script type="text/javascript">
                            var backgroundId = "background{$background->id}";
                            </script>

                        </div>
                    </div>

                    </td>

                    {* placing the media below or beside the background *}
                    {if $question->options->placemedia == 0}
                        </tr>
                        <tr>
                    {/if}

                    <td>
                    <div id="bottom" style="">

            <table border="1">
            {* Number of columns per line *}
            {assign var="cols" value=$question->options->arrangemedia}
            {if $cols == 0}
                {assign var="cols" value="999"}
            {/if}

            {section name=img loop=$gapimages}

                {if $smarty.section.img.index % $cols == 0}
                    <tr>
                {/if}

                <td bgcolor="#CCCCCC" valign="top">
                <table width="100%">
                    <tr>
                        <td bgcolor="#CCCCCC">
                            {$gapimages[img].tag}

                            <script type="text/javascript">
                            arrOfGapimageObjects[{$smarty.section.img.index}]                   = new Object();
                            arrOfGapimageObjects[{$smarty.section.img.index}]["name"]           = "{$gapimages[img].name}";
                            arrOfGapimageObjects[{$smarty.section.img.index}]["backgroundId"]   = backgroundId;
                            arrOfGapimageObjects[{$smarty.section.img.index}]["width"]          = {$gapimages[img].width};
                            arrOfGapimageObjects[{$smarty.section.img.index}]["height"]         = {$gapimages[img].height};
                            arrOfGapimageObjects[{$smarty.section.img.index}]["targetx"]        = {$gapimages[img].targetx};
                            arrOfGapimageObjects[{$smarty.section.img.index}]["targety"]        = {$gapimages[img].targety};
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#CCCCCC">{$gapimages[img].text}</td>
                    </tr>
                </table>
                </td>

                {if $smarty.section.img.index % $cols == $cols - 1 || $cols == 1 || $smarty.section.img.last}
                    </tr>
                {/if}

            {/section}
            </table>

                    </div>
                    </td>

                    </tr>
                </table>

                </td>
            </tr>
        </table>
    </div>
</div>
</form>


{section name=id loop=$sethotspots}
{$sethotspots[id].tag}
<script type="text/javascript">
    var {$sethotspots[id].id} = new YAHOO.util.DDHotSpot("{$sethotspots[id].id}",
                                    {literal}{{/literal}
                                    width: '{$sethotspots[id].width}px',
                                    height:'{$sethotspots[id].height}px',
                                    handles: ['br'],
                                    knobHandles: true,
                                    draggable: true,
                                    minWidth: 10,
                                    minHeight: 10
                                    {literal}}{/literal});
    {$sethotspots[id].id}.backgroundId = 'background{$background->id}';
    {$sethotspots[id].id}.startPos = [{$sethotspots[id].x}, {$sethotspots[id].y}];
    {$sethotspots[id].id}.id = '{$sethotspots[id].id}';
    {$sethotspots[id].id}.hotspot_x       = YAHOO.util.Dom.get("hotspot{$gapimages[id].key}_x");
    {$sethotspots[id].id}.hotspot_y       = YAHOO.util.Dom.get("hotspot{$gapimages[id].key}_y");
    {$sethotspots[id].id}.hotspot_width   = YAHOO.util.Dom.get("hotspot{$gapimages[id].key}_width");
    {$sethotspots[id].id}.hotspot_height  = YAHOO.util.Dom.get("hotspot{$gapimages[id].key}_height");
    {$sethotspots[id].id}.gapimage_x      = YAHOO.util.Dom.get("gapimage{$gapimages[id].key}_x");
    {$sethotspots[id].id}.gapimage_y      = YAHOO.util.Dom.get("gapimage{$gapimages[id].key}_y");
    {$sethotspots[id].id}.gapimage_width  = YAHOO.util.Dom.get("gapimage{$gapimages[id].key}_width");
    {$sethotspots[id].id}.gapimage_height = YAHOO.util.Dom.get("gapimage{$gapimages[id].key}_height");
    arrOfHotspots.push({$sethotspots[id].id});
</script>
{/section}


{$script2}

</body>
</html>