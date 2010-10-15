/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 * 	Copyright (C) 2009 Frank Wolf <frankwolf@mediabird.net>
 *
 *	This file is part of Mediabird Web2.0-Learning.
 *
 *	Mediabird Web2.0-Learning is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	Mediabird Web2.0-Learning is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with Mediabird Web2.0-Learning.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/**
 * Creates a moveable overlay where Mediabird can be displayed in
 * @class Implementation of Mediabird overlay support
 * @extends client.Widget
 * The code here is for reference use only
 * It is directly integrated into the helper
 */
mbOverlay = function(args) {

	this.load = function() {
		init(args.container, args.bar, true);
		init(args.container, args.resizer, false);
		if (args.resizer2 !== undefined) {
			init(args.container, args.resizer2, false);
		}
	}
	
	var downEvent;
	function init(container, elem, move) {
		var obj = {
			element: container,
			move: move,
			direction: (elem.isRight === true ? true : false)
		};
		downEvent = new mbOverlay.event(elem, 'mousedown', mousedownHandler, obj);
	}
	function mousedownHandler(event) {
		event.data.x = mbOverlay.getInt(event.data.element, "left");
		event.data.y = mbOverlay.getInt(event.data.element, "top");
		event.data.w = mbOverlay.getInt(event.data.element, "width");
		event.data.h = mbOverlay.getInt(event.data.element, "height");
		event.data.dx = event.pageX;
		event.data.dy = event.pageY;
		if (args.startHandler !== undefined) {
			args.startHandler();
		}
		
		if (event.data.mousemoveEvent !== undefined) {
			killEvents(event.data);
		}
		
		
		event.data.mousemoveEvent = new mbOverlay.event(document, 'mousemove', mousemoveHandler, event.data);
		event.data.mouseupEvent = new mbOverlay.event(document, 'mouseup', mouseupHandler, event.data);
	}
	
	function mousemoveHandler(event) {
		var x = event.data.x;
		var y = event.data.y;
		var w = event.data.w;
		var h = event.data.h;
		if (event.data.move) {
			x += event.pageX - event.data.dx;
			y += event.pageY - event.data.dy;
		}
		else if (event.data.direction == true) {
			x += event.pageX - event.data.dx;
			w -= event.pageX - event.data.dx;
			h += event.pageY - event.data.dy;
		}
		else {
			w += event.pageX - event.data.dx;
			h += event.pageY - event.data.dy;
		}
		if (event.data.move || (w > 60 && h > 60)) {
			if (event.data.move) {
				event.data.element.style["left"] = x.toString() + 'px';
				event.data.element.style["top"] = y.toString() + 'px';
			}
			else if (event.data.direction == true) {
				event.data.element.style["left"] = x.toString() + 'px';
				event.data.element.style["width"] = w.toString() + 'px';
				event.data.element.style["height"] = h.toString() + 'px';
			}
			else {
				event.data.element.style["width"] = w.toString() + 'px';
				event.data.element.style["height"] = h.toString() + 'px';
			}
			if (args.changeHandler !== undefined) {
				args.changeHandler(event.data.move ? {
					"left": x,
					"top": y
				} : {
					"width": w,
					"height": h
				});
			}
		}
	}
	
	function mouseupHandler(event) {
		killEvents(event.data);
		
		if (args.stopHandler !== undefined) {
			args.stopHandler();
		}
	}
	
	function killEvents(ev){
		ev.mousemoveEvent.destroy();
		delete ev.mousemoveEvent;
		ev.mouseupEvent.destroy();
		delete ev.mouseupEvent;
	}
	
	this.destroy = function() {
		if (downEvent !== undefined) {
			if (downEvent.data.mousemoveEvent !== undefined) {
				downEvent.data.mousemoveEvent.destroy();
				delete downEvent.data.mousemoveEvent;
			}
			if (downEvent.data.mouseupEvent !== undefined) {
				downEvent.data.mouseupEvent.destroy();
				delete downEvent.data.mouseupEvent;
			}
			downEvent.destroy();
			downEvent = undefined;
		}
	}
}

/**
 * @param {Node} elem
 * @param {String} prop
 */
mbOverlay.getInt = function(elem, prop) {
	var style = elem.currentStyle || elem.ownerDocument.defaultView.getComputedStyle(elem, null);
	return parseInt(style[prop]);
}

mbOverlay.event = function(elem, type, handler, data) {
	function _handler(e) {
		if (e.preventDefault !== undefined) {
			e.preventDefault();
		}
		var	event = {};
		
		event.pageX = e.pageX;
		event.pageY = e.pageY;
		event.e = e;
		event.stopPropagation = function() {
			if(this.e.stopPropagation!==undefined) {
				this.e.stopPropagation();
			}
		}
		
		// Calculate pageX/Y if missing and clientX/Y available
		if (e.pageX == null && e.clientX != null) {
			var doc = document.documentElement, body = document.body;
			event.pageX = e.clientX + (doc && doc.scrollLeft || body && body.scrollLeft || 0) - (doc.clientLeft || 0);
			event.pageY = e.clientY + (doc && doc.scrollTop || body && body.scrollTop || 0) - (doc.clientTop || 0);
		}
		event.data = arguments.callee.data; //arguments.call provides a self reference to the function object that
		//this statment is written in.
		handler.call(arguments.callee.elem, event);
		return false;
	}
	_handler.elem = elem;
	_handler.data = data;
	
	if (elem.addEventListener) {
		elem.addEventListener(type, _handler, false);
	}
	else if (elem.attachEvent) {
		elem.attachEvent("on" + type, _handler);
	}
	this.data = data;
	this.destroy = function() {
		if (elem.removeEventListener) {
			elem.removeEventListener(type, _handler, false);
		}
		else if (elem.detachEvent) {
			elem.detachEvent("on" + type, _handler);
		}
	}
}

mbOverlay.handleLink = function(link, panel, nestHandler, closeHandler, startHandler, stopHandler, changeHandler) {
	var clickEvent = new mbOverlay.event(link, "click", function(event) {
		var container = event.data;
		container.style.display = "block";
		if (clickEvent.onceHandled === undefined) {
			clickEvent.onceHandled = true;
			
			
			var bar;
			var closer, expander;
			var resizer;
			var resizer2;
			
			var containerNodes = [];
			retrieveNodes(container, containerNodes);
			
			
			for (var i = 0; i < containerNodes.length; i++) {
				var node = containerNodes[i];
				if (node.className.search("bar") > -1) {
					bar = node;
				}
				if (node.className.search("closer") > -1) {
					closer = node;
				}
				if (node.className.search("expander") > -1) {
					expander = node;
				}
				if (node.className.search("resize-handle") > -1) {
					if (resizer !== undefined) {
						resizer2 = node;
					}
					else {
						resizer = node;
					}
					node.isRight = node.className.search("right") == -1;
				}
			}
			
			if (closer !== undefined) {
				var closeClickHandler = new mbOverlay.event(closer, "click", function(event) {
					if (event.stopPropagation !== undefined) {
						event.stopPropagation();
					}
					var data = event.data;
					data.container.style.display = "none";
					data.closeHandler.call(this);
					return false;
				}, {
					container: container,
					closeHandler: closeHandler
				});
			}
			
			if (expander !== undefined) {
				var expandClickEvent = new mbOverlay.event(expander, "click", function(event) {
					var container = event.data;
					var classArray = this.className.split(" ");
					
					var expandedBool = false;
					for (var i = 0; i < classArray.length; i++) {
						if (classArray[i] == "expanded") {
							expandedBool = true;
							classArray.splice(i, 1);
							break;
						};
											}
					var w, h;
					var bounds = {
						left: mbOverlay.getInt(container, "borderLeftWidth"),
						right: mbOverlay.getInt(container, "borderRightWidth"),
						top: mbOverlay.getInt(container, "borderTopWidth"),
						bottom: mbOverlay.getInt(container, "borderBottomWidth")
					}
					if (!expandedBool) {
						classArray.push("expanded");
						w = mbOverlay.MAX_WIDTH;
						h = mbOverlay.MAX_HEIGHT;
						currPosLeft = mbOverlay.getInt(container, "left");
						container.style.left = (currPosLeft - (w - container.offsetWidth)) + "px";
						container.style.width = (container.offsetWidth + (w - container.offsetWidth - (bounds.left + bounds.right))) + "px";
						container.style.height = (container.offsetHeight + (h - container.offsetHeight - (bounds.top + bounds.bottom))) + "px";
					}
					else {
						w = 300;
						h = 200;
						currPosLeft = mbOverlay.getInt(container, "left");
						container.style.left = (currPosLeft - (w - container.offsetWidth)) + "px";
						container.style.width = (w - (bounds.left + bounds.right)) + "px";
						container.style.height = (h - (bounds.top + bounds.bottom)) + "px";
					}
					var size = {
						width: w - (bounds.left + bounds.right),
						height: h - (bounds.top + bounds.bottom)
					};
					
					className = classArray.join(" ");
					
					this.className = className;
					
					mbOverlay.adjustSize(size);
				}, container);
			}
			
			var overlay = new mbOverlay({
				container: container,
				bar: bar,
				resizer: resizer,
				resizer2: resizer2,
				startHandler: startHandler,
				stopHandler: stopHandler,
				changeHandler: changeHandler
			});
			overlay.load();
			nestHandler(container);
			
			var pos = getPosition(link);
			pos.top += link.offsetHeight;
			pos.left -= container.offsetWidth - link.offsetWidth;
			container.style.left = pos.left + "px";
			container.style.top = pos.top + "px";
			
		}
		function retrieveNodes(container, containerNodes) {
			var ELEMENT_NODE = 1;
			for (var i = 0; i < container.childNodes.length; i++) {
				var node = container.childNodes[i];
				if (node.nodeType == ELEMENT_NODE) {
					containerNodes.push(node);
					retrieveNodes(node, containerNodes);
				}
			}
		}
		function getPosition(node) {
			var pos;
			pos = {
				left: node.offsetLeft - node.scrollLeft,
				top: node.offsetTop - node.scrollTop
			};
			
			var tempNode;
			if (node !== null) {
				tempNode = node.offsetParent;
				while (tempNode && (tempNode !== null)) {
					pos.left += tempNode.offsetLeft - tempNode.scrollLeft;
					pos.top += tempNode.offsetTop - tempNode.scrollTop;
					tempNode = tempNode.offsetParent;
				}
			}
			return pos;
		};
	}, panel);
}
mbOverlay.MAX_HEIGHT = 550;
mbOverlay.MAX_WIDTH = 700;
mbOverlay.SIZE_SECURE = 40;
mbOverlay.doIframe = function(url, link, container, initialSize, useFrame) {
	mbOverlay.adjustSize = function(size) {
		if (size.width !== undefined) {
			mbOverlay.flipContainer.style.width = mbOverlay.frame.style.width = (size.width) + "px";
			mbOverlay.flipContainer.style.height = mbOverlay.frame.style.height = (size.height - mbOverlay.SIZE_SECURE) + "px";
		}
	};
	
	mbOverlay.handleLink(link, container, function(container) {
		var flipContainer = document.createElement("div");
		flipContainer.className = "flipContainer";
		mbOverlay.flipContainer = flipContainer;
		
		var iframe;
		if (useFrame !== undefined) {
			iframe = useFrame;
		}
		else {
			iframe = document.createElement("iframe");
			iframe.setAttribute("frameBorder", "0");
		}
		iframe.setAttribute("src", url);
		mbOverlay.frame = iframe;
		
		container.appendChild(flipContainer);
		container.appendChild(iframe);
		var size;
		if (initialSize !== undefined) {
			size = initialSize;
		}
		else {
			size = {
				width: 300,
				height: 300
			}
		};
		container.style.width = size.width + "px";
		container.style.height = size.height + "px";
		mbOverlay.adjustSize(size);
	}, function() {
		try {
			var doc = mbOverlay.frame.contentDocument || mbOverlay.frame.contentWindow.document;
			var win = doc.defaultView || doc.parentWindow;
			if (win.utility.globalSave !== undefined) {
				win.utility.globalSave();
			}
		} 
		catch (e) {
			//ignore, most possibly no access to the iframe document
		}
	}, function() {
		mbOverlay.flipContainer.style.zIndex = 101;
	}, function() {
		mbOverlay.flipContainer.style.zIndex = 99;
	}, mbOverlay.adjustSize);
}
