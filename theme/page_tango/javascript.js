/**
 * Theme JavaScript Library
 *
 **/

/**
 *  pageThemeFindParentNode (start, elementName, elementClass, elementId, limit)
 *
 *  Travels up the DOM hierarchy to find a parent element with the
 *  specified tag name and class. All conditions must be met,
 *  but any can be ommitted. Returns the BODY element if no match
 *  found.
 *
 *  This function also limits how far to look up the DOM.
 **/
function pageThemeFindParentNode(el, elName, elClass, elId, limit) {
    var i = 0;
    while(el.nodeName.toUpperCase() != 'BODY') {
        // Limit search to prevent false positives
        if (i == limit) {
            return el;
        }
        i++;

        if(
            (!elName || el.nodeName.toUpperCase() == elName) &&
            (!elClass || el.className.indexOf(elClass) != -1) &&
            (!elId || el.id == elId))
        {
            break;
        }
        el = el.parentNode;
    }
    return el;
}

/**
 * This function finds buttons
 * and then adds a single span for
 * rounded corners.
 *
 * What is built is the following:
 *
 * <span class="button">
 *      <!-- Original button -->
 * </span>
 *
 * IE7 has a problem with applying styles
 * to the button after it gets wrapped with
 * the span with the class button.   So,
 * those styles need to be added manually.
 * Define pageThemeButtonStyles(el) method
 * to add your own styles.
 *
 **/
function pageThemeRoundButtons() {
    // Grab all input tags
    var inputs = document.getElementsByTagName('input');

    for (var i = 0; i < inputs.length; i++) {
        var original = inputs[i];

    /// Only process input with type submit or type button
        if (original.type != "submit" && original.type != "button" && original.type != "reset") {
            continue;
        }

    /// Check to make sure that the original element is not already
    ///  wrapped with rounded classes
        var parentnode = pageThemeFindParentNode(original, 'SPAN', 'button', false, 1);
        if (/\bbutton\b/.exec(parentnode.className) || parentnode.nodeName.toUpperCase() == 'BODY') {
            continue;
        }
    /// Fix for #654 - disable for these buttons on assign roles page
        parentnode = pageThemeFindParentNode(original, 'P', 'arrow_button', false, 1);
        if (/\barrow_button\b/.exec(parentnode.className)) {
            continue;
        }

    /// Create our building blocks
        var button   = document.createElement('span');

    /// Set class name
        button.className = 'button';

    /// Put it all together (Order matters for IE)

        // Wrap original with the span
        original.parentNode.insertBefore(button, original);
        button.appendChild(original);

    /// IE7 Fix
        if (typeof pageThemeButtonStyles == "function") {
            pageThemeButtonStyles(original);
        } else {
            original.style.fontWeight = 'bold';
        }
    }
}

/**
 * Keep track of all rendered menus
 **/
 var pageThemeMenus = new Array();


/**
 * Maps menutreeX IDs to tabmenutreeX tab IDs where X is a number
 *
 * Define pageThemeAdjustMenuHeight() in order to override
 * menu positioning adjustments
 *
 **/
function pageThemeSetupMenu() {
    var id     = 'menutree';
    var n      = 0;
    var menuid = id + n
    var tabid  = 'tab' + menuid;

    while (document.getElementById(menuid)) {
        // Must set position before rendering the menu or iframe will not render correctly in IE
        var pos = YAHOO.util.Dom.getXY(tabid);

        if (typeof pageThemeAdjustMenuHeight == "function") {
            pos = pageThemeAdjustMenuHeight(pos);
        } else {
            pos[1] = pos[1] + 30; // Bring it down some
        }

        var oMenu = new YAHOO.widget.Menu(menuid, { lazyLoad: true, hidedelay: 750, iframe: true, zindex: 1000, xy: pos });

        // Subscribe to the keyUp event
        oMenu.subscribe("keyup", pageThemeKeyUpHideMenu);

        // Render and save menu
        oMenu.render();
        pageThemeMenus.push(oMenu);

        // Add event listeners that show the menu to the appropriate tab
        YAHOO.util.Event.addListener(tabid, "mouseover", pageThemeShowMenu, oMenu);
        YAHOO.util.Event.addListener(tabid, "keypress", pageThemeKeyUpShowMenu, oMenu);

        // Reconstruct our IDs
        n++;
        menuid = id + n
        tabid  = 'tab' + menuid;
    }
}

/**
 * Hide other menus when activating another.
 * Looking for better ideas, but this is 
 * very necessary.
 *
 **/
function pageThemeShowMenu(event, currentMenu) {
    currentMenu.show();
    currentMenu.setInitialFocus();
    for (var i = 0; i < pageThemeMenus.length; i++) {
        if (pageThemeMenus[i].id != currentMenu.id) {
            pageThemeMenus[i].hide();
        }
    }
}

/**
 * This method handles keyUp events on the tab.
 * It will detect when the down arrow key (keyCode == 40)
 * is pressed and it will show the menu and give it focus
 **/
function pageThemeKeyUpShowMenu(event, currentMenu) {
    if (event.type == 'keypress' && event.keyCode == 40) {
        pageThemeShowMenu(event, currentMenu);

        if (typeof event['preventDefault'] != 'undefined') {
            event.preventDefault();
        }
    }
}

/**
 * This method handles keyUp events on the menu.
 * It will detect when the escape key (keyCode == 27)
 * is pressed and it will hide the menu and return
 * focus to the tab
 **/
function pageThemeKeyUpHideMenu(eventType, eventArgs) {
    var oEvent = eventArgs[0];    // DOM Event
    var oMenuItem = eventArgs[1]; // YAHOO.widget.MenuItem instance

    if (oEvent.type == 'keyup' && oEvent.keyCode == 27 && oMenuItem) {
        // Get the tab through some "magic"
        var num = oMenuItem.parent.id.replace('menutree', '');
        var tab = document.getElementById('tabmenutree' + num);

        if (tab) {
            tab.focus();

            for (var i = 0; i < pageThemeMenus.length; i++) {
                pageThemeMenus[i].hide();
            }
        }
    }
}

/**
 * Switch all menus RTL after they are built.
 **/
function menusToRTL() {
    for (var i = 0; i < pageThemeMenus.length; i++) 
    {
        pageThemeMenus[i].cfg.setProperty("submenualignment",["tr","tl"]); 

        
        // Convert to a number Ex. "111px" to 111
        var mWidth = parseInt(pageThemeMenus[i].cfg.getProperty("width").replace(/px/,""));
        
        var x = pageThemeMenus[i].cfg.getProperty("x");
        var mId = pageThemeMenus[i].srcElement.id ;
        var pId = "tab"+mId;
        var pWidth = document.getElementById(pId).offsetWidth;
        
        // ReCalc Menu Placement
        var newX = x + (pWidth - mWidth) - 1; 
        pageThemeMenus[i].cfg.setProperty("x",newX );
        
    }  
}