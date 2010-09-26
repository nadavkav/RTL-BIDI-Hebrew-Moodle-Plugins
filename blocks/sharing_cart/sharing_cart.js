/**
 * Sharing Cart: Script
 *
 * @author VERSION2 Inc.
 * @version $Id: sharing_cart.js,v 1.3 2009/04/14 04:06:18 akiococom Exp $
 * @package sharingcart
 */

/**
	parameters = {
		wwwroot     : "'.$CFG->wwwroot.'",
		pixpath     : "'.$CFG->pixpath.'",
		instance_id : $this->instance->id,
		course_id   : $course_id,
		return_url  : "'.$return_url.'",
		directories : ['.implode(',', array_map(create_function('$dir',
		               'return "\"".addslashes($dir)."\"";'), $dirs)).'],
		str : {
			rootdir        : "'.$str->rootdir.'",
			notarget       : "'.$str->notarget.'",
			movehere       : "'.$str->movehere.'",
			copyhere       : "'.$str->copyhere.'",
			edit           : "'.$str->edit.'",
			cancel         : "'.$str->cancel.'",
			backup         : "'.$str->backup.'".
			clipboard      : "'.$str->clipboard.'",
			confirm_delete : "'.$str->confirm_delete.'",
			confirm_backup : "'.$str->confirm_backup.'"
		}
	}
 */
function sharing_cart_handler(parameters)
{
	parameters.block_root = parameters.wwwroot + "/blocks/sharing_cart/";
// private:
	var createLink = function (title, href, params)
	{
		href = href
			? parameters.block_root + href
				+ (params ? "?" + params.join("&") : "")
			: "javascript:void(0);";
		var link   = document.createElement("a");
		link.title = title;
		link.href  = href;
		return link;
	};
	var createIcon = function (src, alt, cls)
	{
		var icon       = document.createElement("img");
		icon.src       = parameters.pixpath + "/" + src + ".gif";
		icon.alt       = alt;
		icon.className = cls;
		return icon;
	};
	var createIndent = function (cx)
	{
		var indent       = document.createElement("img");
		indent.src       = parameters.wwwroot + "/pix/spacer.gif";
		indent.alt       = "";
		indent.className = "icon";
		indent.width     = cx;
		indent.height    = 10;
		return indent;
	};
	var createHidden = function (name, value)
	{
		var hidden   = document.createElement("input");
		hidden.type  = "hidden";
		hidden.name  = name;
		hidden.value = value;
		return hidden;
	};
	
	var folder = {
		state : (function()
		{
			// make Array [ 0, 0, 0, ... ]
			var a = new Array(parameters.directories.length);
			for (var i = 0; i < a.length; i++)
				a[i] = 0;
			return a;
		})(),
		change : function (i, open)
		{
			var icon = document.getElementById("sharing_cart_" + i + "_icon");
			var item = document.getElementById("sharing_cart_" + i + "_item");
			icon.src = parameters.pixpath + "/i/" + (open ? "open" : "closed") + ".gif";
			item.style.display = open ? "block" : "none";
			folder.state[i] = open;
		},
		get_cookie : function ()
		{
			// cookie value: "sharing_cart_folder_state=0,1,0,0,1,1,...;other=...;"
			var cookie = document.cookie.split(";");
			for (var i = 0; i < cookie.length; i++) {
				cookie[i].match(/^\s*(.+)?\s*=\s*(.+)\s*$/);
				var key = RegExp.$1;
				var val = RegExp.$2;
				if (key == "sharing_cart_folder_state") {
					var state = val.split(",");
					for (var k = 0; k < state.length; k++) {
						if (k >= folder.state.length)
							break;
						folder.state[k] = parseInt(state[k]) ? 1 : 0;
					}
					break;
				}
			}
			for (var i = 0; i < folder.state.length; i++)
				folder.change(i, folder.state[i]);
		}
	};
	
	var restore_target = new Array();
	
// public:
	this.a2id = function (a)
	{
		// <div id="shared_item_***">
		//   <span>
		//       <a onclick="sharing_cart.command(this)">command</a>
		//   </span>
		// </div>
		return parseInt(a.parentNode.parentNode.id.split("_").pop());
	};
	
	this.toggle = function (a, i)
	{
		var open = folder.state[i] ? 0 : 1;
		folder.change(i, open);
		// save state to cookie
		var time = new Date();
		time.setDate(time.getDate() + 30);
		document.cookie = "sharing_cart_folder_state=" + folder.state.join(",") + ";"
		                + "expires=" + time.toGMTString() + ";";
		return false;
	};
	
	this.restore = function (a)
	{
		if (restore_target.length == 0) {
			alert(parameters.str.notarget);
			return false;
		}
		
		var cancel = function ()
		{
			if (clipbd) {
				clipbd.parentNode.removeChild(clipbd);
				clipbd = null;
			}
			for (var i = 0; i < restore_target.length; i++) {
				var el = restore_target[i].elm;
				while (el.hasChildNodes())
					el.removeChild(el.firstChild);
				el.style.display = "none";
			}
			return false;
		};
		cancel();
		
		for (var i = 0; i < restore_target.length; i++) {
			var link = createLink(parameters.str.copyhere, "restore.php", [
				"id="      + this.a2id(a),
				"course="  + parameters.course_id,
				"section=" + restore_target[i].sec,
				"return="  + parameters.return_url
			]);
			link.appendChild(createIcon("movehere", link.title, "movetarget"));
			restore_target[i].elm.appendChild(link);
			restore_target[i].elm.style.display = "block";
		}
		var clipbd = document.createElement("div");
		clipbd.appendChild(document.createTextNode(parameters.str.clipboard + ": "));
		clipbd.appendChild(a.parentNode.previousSibling.firstChild.cloneNode(true));
		clipbd.appendChild(document.createTextNode("  ("));
		var link     = createLink(parameters.str.cancel);
		link.onclick = cancel;
		link.appendChild(document.createTextNode(link.title));
		clipbd.appendChild(link);
		clipbd.appendChild(document.createTextNode(")"));
		clipbd.style.padding = "0px 2px 4px 2px";
		var outlines = getElementsByClassName(document.body, "h2", "outline");
		if (outlines.length) {
			// course
			var outline = outlines[0];
			outline.parentNode.insertBefore(clipbd, outline.nextSibling);
		} else {
			// frontpage
			var as = document.getElementsByTagName("a");
			for (var i = 0; i < as.length; i++) {
				var a = as[i];
				if (a.href && a.href.indexOf("course/editsection.php") >= 0) {
					a.parentNode.insertBefore(clipbd, a);
					return false;
				}
			}
			var mc = document.getElementById("maincontent");
			mc.parentNode.insertBefore(clipbd, mc.nextSibling);
		}
		return false;
	};
	
	this.remove = function (a)
	{
		if (confirm(parameters.str.confirm_delete)) {
			location.href = parameters.block_root + "delete.php?" + [
				"id="     + this.a2id(a),
				"return=" + parameters.return_url
			].join("&");
		}
		return false;
	};
	
	this.move = function (a, to)
	{
		var moving_item = null;
		var move_target = null;
		var move_cancel = function ()
		{
			if (moving_item) {
				for (var i = 0; i < move_target.length; i++)
					move_target[i].parentNode.removeChild(move_target[i]);
				moving_item.style.display = "block";
				move_target = null;
				moving_item = null;
			}
		}
		move_cancel();
		
		var id = this.a2id(a);
		var ul = a.parentNode.parentNode.parentNode;
		var li = ul.getElementsByTagName("li");
		move_target = new Array();
		var insert_b4 = new Array();
		var indent_cx = 0;
		for (var i = 0; i < li.length; i++) {
			if (li[i].parentNode != ul || !li[i].id || li[i].id.indexOf("shared_item_") != 0)
				continue;
			if (!indent_cx && li[i].firstChild.firstChild.className == "spacer")
				indent_cx = li[i].firstChild.firstChild.width;
			if (move_target.length == 0) {
				var cancel = document.createElement("li");
				cancel.appendChild(createIndent(indent_cx));
				var link = createLink(parameters.str.cancel);
				link.onclick = move_cancel;
				link.appendChild(document.createTextNode(link.title));
				cancel.appendChild(link);
				move_target.push(cancel);
				insert_b4.push(li[i]);
			}
			var insert = parseInt(li[i].id.split("_").pop());
			if (insert == id) {
				moving_item = li[i];
				moving_item.style.display = "none";
				continue;
			}
			var target = document.createElement("li");
			target.appendChild(createIndent(indent_cx));
			var link = createLink(parameters.str.movehere, "move.php", [
				"id="     + id,
				"to="     + insert,
				"return=" + parameters.return_url
			]);
			link.appendChild(createIcon("movehere", link.title, "movetarget"));
			target.appendChild(link);
			move_target.push(target);
			insert_b4.push(li[i]);
		}
		for (var i = 0; i < insert_b4.length; i++)
			ul.insertBefore(move_target[i], insert_b4[i]);
		var target = document.createElement("li");
		target.appendChild(createIndent(indent_cx));
		var link = createLink(parameters.str.movehere, "move.php", [
			"id="     + id,
			"to="     + 0,
			"return=" + parameters.return_url
		]);
		link.appendChild(createIcon("movehere", link.title, "movetarget"));
		target.appendChild(link);
		ul.appendChild(target);
		move_target.push(target);
		return false;
	};
	
	this.movedir = function (a, to)
	{
		var movedir_form = null;
		var movedir_hide = null;
		var movedir_cancel = function ()
		{
			if (movedir_form) {
				movedir_form.parentNode.removeChild(movedir_form);
				movedir_hide.style.display = "block";
				movedir_form = null;
				movedir_hide = null;
			}
		};
		movedir_cancel();
		
		var form    = document.createElement("form");
		form.action = parameters.block_root + "movedir.php";
		form.appendChild(createHidden("id", this.a2id(a)));
		form.appendChild(createHidden("return", parameters.return_url));
		
		var list = (function ()
		{
			var select  = document.createElement("select");
			select.name = "to";
			var option   = document.createElement("option");
			option.value = "";
			option.appendChild(document.createTextNode(parameters.str.rootdir));
			select.appendChild(option);
			for (var i = 0; i < parameters.directories.length; i++) {
				var option   = document.createElement("option");
				option.value = parameters.directories[i];
				option.appendChild(document.createTextNode(parameters.directories[i]));
				select.appendChild(option);
				if (option.value == to)
					select.selectedIndex = 1/*rootdir*/ + i;
			}
			select.onchange = function ()
			{
				form.submit();
			};
			return select;
		})();
		form.appendChild(list);
		
		var edit = (function ()
		{
			var link     = createLink(parameters.str.edit);
			link.onclick = function ()
			{
				var text   = document.createElement("input");
				text.type  = "text";
				text.size  = 20;
				text.name  = "to";
				text.value = to;
				if (typeof YAHOO != "undefined")
					text.onclick = text.focus;
				form.replaceChild(text, list);
				form.removeChild(link);
				text.focus();
			};
			link.appendChild(createIcon("t/edit", link.title, "iconsmall"));
			return link;
		})();
		form.appendChild(edit);
		
		var hide = (function ()
		{
			var link     = createLink(parameters.str.cancel);
			link.onclick = movedir_cancel;
			link.appendChild(createIcon("t/delete", link.title, "iconsmall"));
			return link;
		})();
		form.appendChild(hide);
		
		form.style.marginTop = 0;
		movedir_form = form;
		movedir_hide = a.parentNode;
		movedir_hide.style.display = "none";
		movedir_hide.parentNode.insertBefore(movedir_form, movedir_hide);
		list.focus();
		if (list.options.length <= 1)
			edit.onclick();
		return false;
	};
	
	this.init = function ()
	{
		var insert = function (sec, sec_i)
		{
			var list = getElementsByClassName(sec, "ul", "section");
			if (list && list.length) {
				// activities exist - append after them
				var dest           = document.createElement("li");
				dest.className     = "activity";
				dest.style.display = "none";
				list[0].appendChild(dest);
				restore_target.push({ sec: sec_i, elm: dest });
			} else {
				// no activities - insert before menu
				var menu = getElementsByClassName(sec, "div", "section_add_menus");
				if (menu && menu.length) {
					var dest           = document.createElement("div");
					dest.className     = "activity";
					dest.style.display = "none";
					menu[0].parentNode.insertBefore(dest, menu[0]);
					restore_target.push({ sec: sec_i, elm: dest });
				}
			}
			var cmds = getElementsByClassName(sec, "span", "commands");
			for (var i = 0; i < cmds.length; i++) {
				var mod_id = cmds[i].parentNode.id.split("-")[1];
				var link = createLink(parameters.str.backup, "backup.php", [
					"course="  + parameters.course_id,
					"section=" + sec_i,
					"module="  + mod_id,
					"return="  + parameters.return_url
				]);
				link.onclick = function ()
				{
					return confirm(parameters.str.confirm_backup);
				};
				link.appendChild(createIcon("i/backup", link.title, "iconsmall"));
				cmds[i].appendChild(link);
			}
		};
		if (document.getElementById("section-0")) {
			// course
			for (var sec_i = 0, sec = null;
				sec = document.getElementById("section-" + sec_i);
				sec_i++)
			{
				insert(sec, sec_i);
			}
		} else {
			// frontpage
			var menus = getElementsByClassName(document.body, "div", "section_add_menus");
			for (var i = 0; i < menus.length; i++)
				insert(menus[i].parentNode, i);
		}
		
		// move command icons into block header
		var header = document.getElementById("sharing_cart_header");
		if (header) {
			var block = document.getElementById("inst" + parameters.instance_id);
			var commands = getElementsByClassName(block, "div", "commands")[0];
			while (header.hasChildNodes())
				commands.appendChild(header.firstChild);
			header.style.display = "none";
		}
		
		// set folder states from cookie
		folder.get_cookie();
	};
	
	// for plugins
	this.getParam = function (name)
	{
		return parameters[name];
	}
	this.setParam = function (name, value)
	{
		parameters[name] = value;
	}
}
