/*
This file is part of the Kaltura Collaborative Media Suite which allows users 
to do with audio, video, and animation what Wiki platfroms allow them to do with 
text.

Copyright (C) 2006-2008  Kaltura Inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

// initModalBox called from gotoCW - to open the contribution wizard as an iFrame in the 
// widget page
var PWIDTH = 320;
var PHEIGHT = 210;
var tmp_id = 0;
function kalturaInitModalBox ( url, options )
{
	var starta = new Date(); //create timestamped unique id for every time the iframe opens
	tmp_id = starta.getTime(); // timestamp to id
	
	if (document.getElementById("overlay"))
	{
		overlay_obj = document.getElementById("overlay");
		modalbox_obj = document.getElementById("modalbox");
		overlay_obj.parentNode.removeChild( overlay_obj );
		modalbox_obj.parentNode.removeChild( modalbox_obj );		
	}
	var objBody = document.getElementsByTagName("body").item(0);

	// create overlay div and hardcode some functional styles (aesthetic styles are in CSS file)
	var objOverlay = document.createElement("div");
	objOverlay.setAttribute('id','overlay');
	objBody.appendChild(objOverlay, objBody.firstChild);

	var width = 680;
	var height = 360;
	if (options)
	{
		if (options.width)
			width = options.width;//+10;
		if (options.height)
			height = options.height;//+16;
	}

	// create modalbox div, same note about styles as above
	var objModalbox = document.createElement("div");
	objModalbox.setAttribute('id','modalbox');
	//objModalbox.setAttribute('style', 'width:'+width+'px;height:'+height+'px;margin-top:'+(0-height/2)+'px;margin-left:'+(0-width/2)+'px;');
	objModalbox.style.width = width+'px';
	objModalbox.style.height = height+'px';
	objModalbox.style.marginTop = (0-height/2)+'px';
	objModalbox.style.marginLeft = (0-width/2)+'px';
	
	// create content div inside objModalbox
	var objModalboxContent = document.createElement("div");
	objModalboxContent.setAttribute('id','mbContent');
	if ( url != null )
	{
		thehtml = '<iframe allowtransparency="true" name="'+tmp_id+'" id="kaltura_modal_iframe" scrolling="no" width="' + width + '" height="' + height + '" frameborder="0" src="' + url + '">';
		objModalboxContent.innerHTML = thehtml;
	}
	objModalbox.appendChild(objModalboxContent, objModalbox.firstChild);
	
	objBody.appendChild(objModalbox, objOverlay.nextSibling);	
	return '';
	
	return objModalboxContent;
}

function kalturaCloseModalBox ()
{
	/*
	if ( this != window.top )
	{
		window.top.kalturaCloseModalBox();
		return false;
	}
	*/
	//alert ( "kalturaCloseModalBox" );
	// TODO - have some JS to close the modalBox without refreshing the page if there is no need
	overlay_obj = document.getElementById("overlay");
	modalbox_obj = document.getElementById("modalbox");
	if (overlay_obj) overlay_obj.parentNode.removeChild( overlay_obj );
	if (modalbox_obj) modalbox_obj.parentNode.removeChild( modalbox_obj );
	
	return false;
}

function kalturaRefreshTop ()
{
	/*
	if ( this != window.top )
	{
		window.top.kalturaRefreshTop();
		return false;
	}
	*/
	window.location.reload(true);
}

function get_field(field_name) {
	if (this != window.top)
	{
	    return window.top.get_field(field_name);
	}
	return document.getElementById(field_name).value;
}

function update_field(field_name, value, close_modal, presspost) {
	if (this != window.top)
	{
	    window.top.update_field(field_name, value, close_modal, presspost);
		return false;
	}
	document.getElementById(field_name).value = value;
//	if (presspost != '') window.top.document.getElementById(presspost).click();
	if (presspost != '') eval("window.top."+presspost+"();");
	if (close_modal) window.top.kalturaCloseModalBox();
}

function update_img(field_name, value, close_modal, presspost)
{
    if (this != window.top)
    {
        window.top.update_img(field_name, value, close_modal, presspost);
        return false;
    }
    document.getElementById(field_name).src = value;
    //	if (presspost != '') window.top.document.getElementById(presspost).click();
    if (presspost != '') eval("window.top." + presspost + "();");
    if (close_modal) window.top.kalturaCloseModalBox();
}

function make_preview(thumb_div, field) {
	field_elem = document.getElementById(field);
	if (field_elem) {
		if (!document.getElementById('kfield_preview')) {
			new_div = document.createElement('div');
			new_div.setAttribute('id','kfield_preview');
			field_elem.parentNode.insertBefore(new_div, field_elem);
		}
		document.getElementById('kfield_preview').innerHTML = '';
		wrap_div = document.createElement('div');
		wrap_div.className = 'kthumb';
		img_elem = document.createElement('img');
		img_elem.src = thumb_div;
		wrap_div.appendChild(img_elem);
		
		document.getElementById('kfield_preview').appendChild(wrap_div);
		
	}	
}


function insert_into_post()
{
    var design = document.getElementById('slctDesign');

    update_field('id_design', design.options[design.selectedIndex].value , false, '');
    update_field('id_custom_width', document.getElementById('inpCustomWidth').value, false, '');
    update_field('id_title', document.getElementById('inpTitle').value, true, 'show_wait');
}


function clear_field(field_elem, preview_div) {
	document.getElementById(field_elem).value = '';
	document.getElementById(preview_div).innerHTML = '';
}

function scrollright(divid,amount) {
	scroller = document.getElementById(divid);
	if(scroller) {
		scroller.scrollLeft += amount;
	}
}

function scrollleft(divid,amount) {
	scroller = document.getElementById(divid);
	if(scroller) {
		if (amount)
			scroller.scrollLeft -= amount;
		else
			scroller.scrollLeft = 0;
	}
}

var current_item = '';

function load_item_to_view(entry_id,url,type) {
	switch_item_in_player(entry_id,type);
	if (current_item != '') {
		document.getElementById(current_item).className = 'kobj';
	}
	document.getElementById(entry_id).className = 'kobj active';
	current_item = entry_id;
	switch_data(url);
}
var img_src = '';
function set_img_src(str) {
	img_src = str;
}
function switch_item_in_player(entry_id, type) {
	if (!type || type == 2) {
		$('#kplayer').hide();
		thumb_url = $.ajax(
			{url: wwwroot+"/kaltura/kthumb_url.php?id="+entry_id+"&type=1&height=364&width=410",
			success: function(result){
					if(result.isOk == false) {
						//alert(result.message);
					}
					else set_img_src(result);
				},
			async:   false
			}
		);
	
		if (!document.getElementById('static_library_img')) {
			img = document.createElement('img');
			img.id = 'static_library_img';
			document.getElementById('static_library_player_div').appendChild(img);
		}
		$('#static_library_img').attr('src',img_src);
		$('#static_library_img').show();
	} else {
		$('#static_library_player').show();
		kdp = new KalturaPlayerController('static_library_player');
		kdp.insertEntry(entry_id);
		$('#static_library_img').hide();
	}
}

function switch_data(url, entry) {
	if (!entry) {
		$('#kitem_metadata').load(url);
	} else {
		entry_title = $('div[@id=kitem_metadata] :input[name=title]').val();
		entry_description = $('div[@id=kitem_metadata] :input[name=description]').val();
		entry_tags = $('div[@id=kitem_metadata] :input[name=tags]').val();
		entry_id = $('div[@id=kitem_metadata] :input[name=entry_id]').val();
		field_name = $('div[@id=kitem_metadata] :input[name=field]').val();
		clone_field = $('div[@id=kitem_metadata] :input[name=clone]').val();
		skipp_field = $('div[@id=kitem_metadata] :input[name=skippreview]').val();
		fullinj_field = $('div[@id=kitem_metadata] :input[name=fullinject]').val();
		pressp_field = $('div[@id=kitem_metadata] :input[name=presspost]').val();
		$('#kitem_metadata').load(url, {
			entry_id: entry,
			title: entry_title,
			tags: entry_tags,
			description: entry_description,
			field: field_name,
			clone: clone_field,
			skippreview: skipp_field,
			fullinject: fullinj_field,
			presspost: pressp_field
			});
	}
}

function update_other_elements(entry_id,entry_title,type){
	//$("#"+entry_id+" span[@class=title]").html(entry_title);
	//setTimeout('switch_item_in_player("'+entry_id+'", '+type+')',100);
}

function fix_window_size(options) {
	if (this != window.top)
	{
		window.top.fix_window_size(options);
		return false;
	}
	var width = 680;
	var height = 360;
	if (options)
	{
		if (options.width)
			width = options.width;
		if (options.height)
			height = options.height;
	}
	$('#kaltura_modal_iframe').width(width).height(height);
	$('#modalbox').width(width).height(height);
	objModalbox = document.getElementById('modalbox');
	if(objModalbox) {
		objModalbox.style.marginTop = (0-height/2)+'px';
		objModalbox.style.marginLeft = (0-width/2)+'px';
	}
}

function delete_entry(entry_id,delete_url) {
	var r=confirm("Are you sure you want to delete this item ?");
	if (r==true) {
		$.ajax({url: delete_url+"?entry_id="+entry_id, async: false});
		kalturaRefreshTop();
	} else {
		alert('whooo... that was a close one :)');
	}
}

function test(id) {
	kdp = new KalturaPlayerController('kaltura-static-player');
	kdp.getMediaSeekTime();
	kdp.getPlayheadTime();
}

function openLightBox(entryId,objId) {
 if (this == window.top)
 {
  //if($.browser.msie)
  kdps_ie = $('div.kaltura_wrapper').find('object');
  //else
  kdps_ff = $('div.kaltura_wrapper').find('embed');
  for(i=0;i<kdps_ie.length;i++)
  {
	kdp_id = kdps_ie[i].id;
	kdp = new KalturaPlayerController(kdp_id);
	kdp.pause();
  }
  for(i=0;i<kdps_ff.length;i++)
  {
	kdp_id = kdps_ff[i].id;
	kdp = new KalturaPlayerController(kdp_id);
	kdp.pause();
  }  
  if (objId != 'null')
  {
	kdp_id = objId;
  }
  else
  {
	kdp_id = 'kplayer_'+entryId;
	if (!document.getElementById(kdp_id))
	{
		// assume only one player in page (like portfolio)
		kdp_id = 'kplayer';
	}
  }
  time = '';
  if (document.getElementById(kdp_id))
  {
	kdp = new KalturaPlayerController(kdp_id);
	try{
		time = kdp.getMediaSeekTime();
	}
	catch(err)
	{
		// do nothing
	}
	
  }
  url = wwwroot+"/kaltura/kdp.php?entry="+entryId+"&seekto="+time;
  kalturaInitModalBox(url, {width:824, height:630});
 }
}

function toggle_wall_pic_size(pic_id, img_id)
{ 
   myimg = document.getElementById(img_id);
  orig_source = myimg.src;
  if (orig_source.indexOf('width/120') > 0) {
   new_src = orig_source.replace('width/120','width/'+PWIDTH);
   new_src = new_src.replace('height/65','height/'+PHEIGHT);
   document.getElementById(pic_id).className = 'wall_photo_minus';
  } else if(orig_source.indexOf('width/'+PWIDTH) > 0) {
   new_src = orig_source.replace('width/'+PWIDTH,'width/120');
   new_src = new_src.replace('height/'+PHEIGHT,'height/65');
    document.getElementById(pic_id).className = 'wall_photo_plus';
  }
     myimg.src = new_src;
}

function assignment_remove_video(div_id, input_id,key,detach_link) {
 $('#'+div_id).hide();
 $('#'+input_id).val('');
 $.ajax({url: detach_link+"?entry="+div_id+"&key="+key});
}

function remove_kaltura_video(div_id,key,detach_link) {
	 $('#'+div_id).hide();
	 $('#replace_link_'+div_id).html('Add Image');
	 $('#remove_link_'+div_id).html('');
	$.ajax({url: detach_link+"&entry="+div_id+"&key="+key});
}

var vid_div_id = '';
function get_vid_div_id()
{
	return vid_div_id;
}
function replace_resource_video(tag,vid_id)
{
	vid_div_id = "replace_"+vid_id;
	var action ='video';
	var url = wwwroot+"/kaltura/html_resource_ajax/replace_tags.php";
	$.ajax({url: url+"?action="+action+"&tag="+escape(tag), success:function(msg){
		var divid = get_vid_div_id();
		$("#"+divid).html(msg);
	}});
}

var swf_replacement_div_id = '';
function get_swf_replacement_div_id()
{
	return swf_replacement_div_id;
}
function replace_resource_swf(tag,div_id)
{
	var action ='swf';
	swf_replacement_div_id = div_id;
	var url = wwwroot+'/kaltura/html_resource_ajax/replace_tags.php';
	$.ajax({url: url+"?action="+action+"&tag="+escape(tag), success:function(msg){
		var divid = get_swf_replacement_div_id();
		$("#"+divid).html(msg);
	}});
}