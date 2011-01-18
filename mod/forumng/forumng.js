var forumng_pixpath, forumng_modpixpath;
var forumng_strings;
var forumng_ratingstars;
var forumng_cmid;
var forumng_select = {};
var forumng_discussionid, forumng_cloneparam, forumng_cloneid;
var forumng_quotaleft;
var forumng_mouseuser;
var forumng_viewportwidth;

function forumng_log(thing) {
    if (typeof(console) !== 'undefined') {
        console.log(thing);
    }
}

function forumng_init() {
    try {
        // b****y IE doesn't have a trim function
        if (!String.prototype.trim) {
            String.prototype.trim = function() {
                return this.replace(/^\s+/, '') . replace(/\s+$/, '');
            };
        }

        // Get clone param if it's in url
        forumng_cloneparam = window.location.search.match(/&(clone=([0-9]+))/);
        if (!forumng_cloneparam) {
            forumng_cloneparam = '';
            forumng_cloneid = 0;
        } else {
            forumng_cloneparam = forumng_cloneparam[0];
            forumng_log(forumng_cloneparam);
            forumng_cloneid = forumng_cloneparam.replace(/^.*clone=([0-9]+).*$/, '$1');
        }

        //Magicalise the hidden 'switch view mode' link
        if (document.getElementById('forumng-switchlinkid')) {
            var link = document.getElementById('forumng-switchlinkid');
            forumng_init_switchlink(link);
        }

        // Handle pages other than the discussion page
        if (document.getElementById('mod-forumng-subscribers')) {
            forumng_init_subscribers();
            return;
        }
        if (document.getElementById('mod-forumng-view')) {
            forumng_init_view();
            return;
        }
        if (document.getElementById('mod-forumng-editpost')) {
            forumng_init_editpost();
            return;
        }
        if (document.getElementById('mod-forumng-discuss')) {
            forumng_init_discuss();
            return;
        }
        if (document.getElementById('mod-forumng-feature-print-print')) {
            forumng_init_content(document);
            forumng_print_page();
            return;
        }
    } catch(exception) {
        forumng_log('init exception: ' + exception.message);
    }
}

function forumng_init_discuss() {

    // Get discussion id
    forumng_discussionid = window.location.search.replace(
        /^.*[?&]d=([0-9]+).*$/ , '$1');

    // Tell CSS that we have JS working
    YAHOO.util.Dom.removeClass('forumng-main', 'forumng-nojs');

    // Set up magic links
    document.forumng_expirelinks = [];
    forumng_init_content(document);

    // If form(s) are included, do tidying up
    YAHOO.util.Dom.getElementsByClassName('mform', 'form', null, forumng_init_form);

    // Hide 'save ratings' button if present
    var saveall = document.getElementById('forumng-saveallratings');
    if (saveall) {
        saveall.parentNode.removeChild(saveall);
    }

    // Init feature buttons
    forumng_init_feature_buttons();
    
    // Apply stop indents
    forumng_apply_stop_indents();
    var region = YAHOO.util.Dom.getRegion(document.getElementById('forumng-main'));
    forumng_viewportwidth = region.right - region.left;
    setInterval(function() { 
        var region = YAHOO.util.Dom.getRegion(document.getElementById('forumng-main'));
        var width = region.right - region.left;
        if (width != forumng_viewportwidth) {
            forumng_viewportwidth = width;
            forumng_apply_stop_indents();
        }
    }, 250);
}

function forumng_apply_stop_indents() {
    // Pick max indent level
    var region = YAHOO.util.Dom.getRegion(document.getElementById('forumng-main'));
    var width = region.right - region.left;
    var minwidth = 360;
    var maxindentpixels = width - minwidth;
    var stopIndent;

    // There are 5 indents of 40px then 5 of 30px, then all 20px
    if (maxindentpixels > 350) {
        stopIndent = 10 + Math.floor((maxindentpixels - 350) / 20);
    } else if(maxindentpixels > 200) {
        stopIndent = 5 + Math.floor((maxindentpixels - 200) / 30);
    } else {
        stopIndent = Math.floor(maxindentpixels / 40);
    }

    // sort indents
    forumng_replies = YAHOO.util.Dom.getElementsByClassName('forumng-replies', 'div');

    // build list of answers
    for (var i=0; i<forumng_replies.length; i++) {
        var reply = forumng_replies[i];
        var indent = forumng_get_reply_indent(reply);
        if(indent == stopIndent){
            YAHOO.util.Dom.addClass(reply, 'forumng-stop-indent');
        } else {
            YAHOO.util.Dom.removeClass(reply, 'forumng-stop-indent');
        }
    }
}

function forumng_get_reply_indent(reply) {
    if (reply.forumng_indent) {
        return reply.forumng_indent;
    }

    var indent = 1;
    // for a given reply go through each parent to find its nexting.
    var ancestor = YAHOO.util.Dom.getAncestorByClassName(reply, 'forumng-replies');
    if(ancestor){
        indent +=forumng_get_reply_indent(ancestor);
    }
    reply.forumng_indent = indent;
    return indent;
}

function forumng_init_editpost() {
    // If form(s) are included, do tidying up
    YAHOO.util.Dom.getElementsByClassName('mform', 'form', null, forumng_init_form);
}

function forumng_init_content(el) {

    if (forumng_select.on && el.className && el.className.match(/forumng-post/)) {
        forumng_select_init_post(el, true);
    }

    // Get post id listed as URL anchor, if any (initial run only)
    var expandposts = new Object;
    if(el==document) {
        // Post from location bar
        if(window.location.hash) {
            var match = window.location.hash.match(/p([0-9]+)$/);
            if (match) {
                expandposts[parseInt(match[1])] = true;
            }
        }
        // Posts listed as expanded (from Back button)
        if (document.getElementById('expanded_posts')) {
            var posts = document.getElementById('expanded_posts').value.split(',');
        } else {
            var posts = new Array(); 
        }
        for(var i=0; i<posts.length; i++) {
            expandposts[posts[i]] = true;
        }
    }

    // Kill reply links if necessary
    if (forumng_quotaleft == 0) {
        forumng_kill_reply_links(el);
    }

    // Add JS to other links
    var links = el.getElementsByTagName('a');
    for (var i=0; i<links.length; i++) {
        var link = links[i];

        if(link.className == 'forumng-mobilepost-link'){
        	continue;
        }
        
        // Any link with &expires= will be hidden a bit before that time
        match = link.href.match(/[?&]expires=([0-9]+)(&|$)/);
        if (match) {
            new forumng_init_expiry(link, parseInt(match[1]));
        }

        // Magicalise 'Expand' links
        var match = link.href.match(
            /\/discuss\.php\?d=([0-9]+).*&expand=1#p([0-9]+)$/);
        if (match && link.className=='forumng-expandlink') {
            forumng_init_expand(link, match, expandposts[parseInt(match[2])]);
        }

        // Magicalise 'Reply' links
        match = link.href.match(/\/editpost\.php\?replyto=([0-9]+).*$/);
        if (match) {
            forumng_init_reply(link, parseInt(match[1]));
        }

        // Magicalise 'Edit' links
        match = link.href.match(/\/editpost\.php\?p=([0-9]+).*$/);
        if (match) {
            forumng_init_edit(link, parseInt(match[1]));
        }

        // Magicalise 'Delete' / 'Undelete' links
        match = link.href.match(/\/deletepost\.php\?p=([0-9]+)(?:&clone=[0-9]+)?(?:&delete=([0-9]+))?$/);
        if (match) {
            forumng_init_delete(link, parseInt(match[1]), match[2] && match[2]==0);
        }

        // Magicalise the hidden parent-post links
        if (link.className == 'forumng-parentlink') {
            forumng_init_parentlink(link);
        }

        // Magicalise the jump-to links
        if (link.parentNode.className == 'forumng-jumpto') {
            forumng_init_jumplink(link);
        }
    }
    
    // Magicalise rating sections
    var ratings = YAHOO.util.Dom.getElementsByClassName('forumng-ratings', 'div', el);
    for (var i=0; i<ratings.length; i++) {
        forumng_init_rating(ratings[i]);
    }

    // Find any inputs with the zero-disable feature
    var inputs = YAHOO.util.Dom.getElementsByClassName('forumng-zero-disable', 'input', el);
    for (var i=0; i<inputs.length; i++) {
        forumng_zero_disable(inputs[i]);
    }

    forumng_init_flags(el);
}

function forumng_expand_ok(o) {
    var newDiv = document.createElement('div');
    var scriptRegexp = /<script[^>]*>([\s\S]*?)<\/script>/g;
    var text = o.responseText;

    // Some browsers execute script tags when you add them to DOM but others
    // (IE7) do not, so I pull out the script tags here and execute later.
    var scriptCommands = [];
    while ((result = scriptRegexp.exec(text)) != null) {
        scriptCommands.push(result[1]);
    }
    text = text.replace(scriptRegexp, '');
    newDiv.innerHTML = text;
    var newPost = newDiv.firstChild;
    var focushandler = this.post.focushandler;

    // If in select mode, note previous selection value
    var previousSelect = false;
    if(forumng_select.on) {
        previousSelect = document.getElementById('id_selectp' + this.postid).checked;        
    }

    var expander = new forumng_expander(this.post);
    newDiv.removeChild(newPost);
    this.post.parentNode.insertBefore(newPost, this.post);
    this.post.parentNode.removeChild(this.post);

    // Run script commands
    for(var i=0; i<scriptCommands.length; i++) {
      eval(scriptCommands[i]);
    }

    forumng_init_content(newPost);
    if (previousSelect) {
        var checkbox = document.getElementById('id_selectp' + this.postid);
        checkbox.checked = true;
        checkbox.onclick();
    }
    if (document.body.linksdisabled) {
        forumng_links_disable(newPost);
        newPost.linksdisabled = false; // It isn't individually disabled really
    }

    var tracker = document.getElementById('expanded_posts');
    tracker.value = tracker.value + (tracker.value=='' ? '' : ',') + this.postid;

    if (!this.delay) {
        // Skip the expanding animation
        return;
    }

    expander.go(newPost);

    if (focushandler) {
        focushandler();
    } else {
        // Replace focus on expand element which got wiped.
        var authorspan = YAHOO.util.Dom.getElementsByClassName(
            'forumng-author', 'span', newPost);
        if(authorspan.length > 0) {
            // By default, focus on author name link.
            // The timeout here is because otherwise IE7 sometimes crashes
            setTimeout(function() { authorspan[0].firstChild.focus(); }, 0);
        } else {
            // If author name link is not present, focus on first link (which is usually
            // the 'this is post 3, parent is post N' link).
            var links = YAHOO.util.Dom.getElementsBy(
                function(e) { return e.href; }, 'a', newPost);
            if(links.length > 0) {
                links[0].focus();
            }
        }
    }
}

// Expands an object. Construct with original object (to determine the initial
// size) then add something into it or replace it, then call go() with the
// new object.
function forumng_expander(originalobj) {
    this.shrinkheight = originalobj==null ? 0 : forumng_remove_px(
            YAHOO.util.Dom.getStyle(originalobj, 'height'));
    this.lastheight = -1;

    this.go = function(newobj) {
        // Some browsers don't return current actual height, which means this
        // logic fails to work, so don't do the effect.
        if(isNaN(this.shrinkheight)) {
            return;
        }
        newobj.style.maxHeight = this.shrinkheight + 'px';
        newobj.style.overflow = 'hidden';
        var outer = this;

        var timeoutid = setInterval(function() {
            var currentheight = newobj.offsetHeight;
            if (outer.lastheight == currentheight) {
                newobj.style.maxHeight = '';
                newobj.style.overflow = 'visible';
                clearInterval(timeoutid);
                return;
            }
            outer.lastheight = currentheight;
            outer.shrinkheight += 20;
            newobj.style.maxHeight = outer.shrinkheight + 'px';
        }, 20);
    }
}

function forumng_expand_error(o) {
    this.inProcess = false;
    this.loader.src = this.loader.originalsrc;
    alert(forumng_strings.jserr_load);
}

function forumng_init_expand(link, matches, expandnow) {
    link.post = YAHOO.util.Dom.getAncestorByClassName(link, 'forumng-post');
    link.post.expandlink = link;
    link.loader = link.nextSibling.nextSibling;
    link.loader.originalsrc = link.loader.src;
    link.postid = matches[2];
    link.delay = true;

    // Replace 'expand all' text with 'expand this post'
    var postnum = link.post.className.replace(/^.*forumng-p([0-9]+).*$/, '$1');
    link.innerHTML = forumng_strings.expand.replace('#', postnum); 

    link.onclick = function() {
        if (link.inProcess) {
            return false;
        }
        link.post.focushandler = null;
        YAHOO.util.Connect.asyncRequest('POST','expandpost.php',
            {success:forumng_expand_ok,failure:forumng_expand_error,scope:link},
            'p=' + link.postid + forumng_cloneparam);
        if (forumng_pixpath) {
            link.loader.src = forumng_pixpath + '/i/ajaxloader.gif';
        }
        link.inProcess = true;
        return false;
    }

    // Automatically expand message listed in URL (if any)
    if (expandnow) {
        link.delay = false;
        link.onclick();
    }
}

function forumng_prepare_form(form) {
    // Cancel if already showing
    if (form.nowshowing) {
        return false;
    }
    form.nowshowing = true;
    form.timers = [];
    form.editover = false;

    // Add special style that marks links disabled
    forumng_links_disable(document.body);

    // Remove from whereever it was before
    form.parentNode.removeChild(form);

    // Make sure the buttons aren't greyed out (they can be if somebody
    // reloads a page from a position where they are)
    form.cancel.disabled = false;
    form.submitbutton.disabled = true;

    // Enable/disable the submit button based on message emptiness
    var submitenableinterval = setInterval(function()
    {
        var sourceText = form.message.value;
        if (form.usingeditor && form.message.style.display=='none') {
            if (form.mce) {
                sourceText = tinyMCE.activeEditor.getBody().innerHTML;
            } else {
                sourceText = form.htmlarea.getHTML(); 
            }
        }
        // Get rid of tags and nbsp as literal or entity, then trim
        var mungevalue = sourceText.replace(/<.*?>/g, '').replace(
            /&(nbsp|#160|#xa0);/g, '') . replace(
                new RegExp(String.fromCharCode(160), 'g'), ' ') .
            replace(/\s+/, ' ') . trim();

        // When editing discussion first post, subject must also be not blank
        if (mungevalue != '' && form.editpostid && form.isroot) {
            mungevalue = form.subject.value.trim();
        }

        form.submitbutton.disabled = (mungevalue == '') || form.editover;
        if (form.savedraft) {
            form.savedraft.disabled = form.submitbutton.disabled;
        }
    }, 250);

    // Cancel button handling
    form.cancel.onclick = function() {
        clearInterval(submitenableinterval);
        if (form.usingeditor) {
            if (form.mce) {
                tinyMCE.execCommand('mceRemoveControl', false, form.message.id);
            } else {
                form.message.parentNode.removeChild(form.message.previousSibling);
                form.message.style.display = 'block';
                form.message.style.width = 'auto';
                form.message.style.height = 'auto';
                forumng_htmlarea_progress_state(form.htmlarea, 0);
                form.htmlarea = null;
            }
        }

        form.style.display = 'none';
        if (form.draftNotice) {
            form.draftNotice.parentNode.removeChild(form.draftNotice);
            form.draftNotice = null;
        }
        form.parentNode.removeChild(form);
        document.getElementById('forumng-formhome').appendChild(form);

        for(var i=0; i<form.timers.length; i++) {
            clearTimeout(form.timers[i]);
        }
        form.timers = [];

        forumng_links_enable(document.body);
        form.nowshowing = false;
        return false;
    };

    return true;
}

function forumng_links_disable(root) {
    root.linksdisabled = true;
    var commandblocks = YAHOO.util.Dom.getElementsByClassName(
        'forumng-commands', 'ul', root);
    for(var i=0; i<commandblocks.length; i++) {
        var links = commandblocks[i].getElementsByTagName('a');
        for(var j=0; j<links.length; j++) {
            links[j].oldonclick = links[j].onclick;
            links[j].onclick = function() {
                return false;
            }
            links[j].style.cursor = 'default';
            links[j].tabIndex = -1;
            links[j].className += ' forumng-disabled';
        }
    }
}

function forumng_links_enable(root) {
    root.linksdisabled = false;
    var commandblocks = YAHOO.util.Dom.getElementsByClassName(
        'forumng-commands', 'ul', root);
    for(var i=0; i<commandblocks.length; i++) {
        var links = commandblocks[i].getElementsByTagName('a');
        for(var j=0; j<links.length; j++) {
            links[j].onclick = links[j].oldonclick;
            links[j].oldonclick = false; // Wanted to do 'delete' but it crashes ie
            links[j].style.cursor = 'auto';
            links[j].tabIndex = 0;
            links[j].className = links[j].className.replace(' forumng-disabled', '');
        }
    }
}

function forumng_init_reply(link, replytoid) {
    link.onclick = function() {
        // Get form and post
        var form = document.getElementById('mform1');
        form.post = YAHOO.util.Dom.getAncestorByClassName(link, 'forumng-post');

        // Cancel if an existing reply is in progress
        if (!forumng_prepare_form(form)) {
            return false;
        }

        // Put form as last thing in post (except the 'end post' marker)
        form.post.insertBefore(form, form.post.lastChild);

        // Mark that we've got a reply there
        form.replytoid = replytoid;
        form.attachmentplayspace.value = '';

        var draft = window.forumng_draft ? window.forumng_draft : false;
        window.forumng_draft = null;

        var quotaDiv = document.getElementById('id_postlimit1');
        if (quotaDiv) {
            var quotaItem = YAHOO.util.Dom.getAncestorByClassName(
                quotaDiv, 'fitem');
            if (forumng_quotaleft > 2 || forumng_quotaleft < 0) {
                quotaItem.style.display = 'none';
            } else {
                quotaItem.style.display = 'block';
                var text = (forumng_quotaleft == 1)
                    ? forumng_strings.quotaleft_singular
                    : forumng_strings.quotaleft_plural;
                text = text.replace('#', forumng_quotaleft);
                quotaDiv.innerHTML = text;
            }
        }

        // Initialise form HTML editor
        forumng_init_editor(form, draft ? draft.message : '');
        forumng_init_attachments(form, draft ? draft.attachments : null);

        if (draft) {
            form.subject.value = draft.subject;
            if(form.mailnow) {
                form.mailnow.checked = draft.mailnow ? true : false;
            }
            if(form.setimportant) {
                form.setimportant.checked = draft.setimportant ? true : false;
            }
            form.attachmentplayspace.value = draft.attachmentplayspace;
            form.draft.value = draft.id;
        } else {
            form.draft.value = 0;
        }

        // Post button handling
        form.submitbutton.onclick = function() {
            this.disabled = true;
            this.form.cancel.disabled = true;
            if (form.usingeditor) {
                if (form.mce) {
                    tinyMCE.triggerSave();
                    tinyMCE.get(form.message.id).setProgressState(1);
                } else {
                    this.form.message.value = form.htmlarea.getHTML();
                    forumng_htmlarea_progress_state(form.htmlarea, 1);
                }
            }
            forumng_save(form, 'replyto=' + form.replytoid + forumng_cloneparam,
                    forumng_save_ok_reply, forumng_save_error);
            return false;
        };

        form.savedraft.onclick = function() {
            this.disabled = true;
            this.form.cancel.disabled = true;
            if (form.usingeditor) {
                if (form.mce) {
                    tinyMCE.triggerSave();
                    tinyMCE.get(form.message.id).setProgressState(1);
                } else {
                    this.form.message.value = form.htmlarea.getHTML();
                    forumng_htmlarea_progress_state(form.htmlarea, 1);
                }
            }
            forumng_save(form, 'replyto=' + form.replytoid + forumng_cloneparam +
                    '&savedraft=1&keepplayspace=1',
                    forumng_save_ok_draft, forumng_save_error);
            return false;
        }

        // Make form visible
        form.style.display = 'block';
        return false;
    };

    // When we create the reply link that a draft post uses, make it click itself
    if (window.forumng_draft && window.forumng_draft.parentpostid==replytoid) {
        setTimeout( function() {link.onclick();}, 0);            
    }
}

function forumng_htmlarea_progress_state(htmlarea, progress) {
    if(progress && !htmlarea.cover) {
        // Fakes the TinyMCE progress state feature on an htmlarea
        var cover = document.createElement('div');
        cover.style.background = 'black';
        cover.style.position = 'absolute';
        YAHOO.util.Dom.setStyle(cover, 'opacity', '0.5');
        document.body.appendChild(cover);

        var region = YAHOO.util.Dom.getRegion(htmlarea._htmlArea);
        YAHOO.util.Dom.setXY(cover, region);
        var w = region.right-region.left;
        var h = region.bottom-region.top;
        cover.style.width = w + 'px';
        cover.style.height = h + 'px';

        var loader = document.createElement('img');
        loader.src = forumng_pixpath + '/i/ajaxloader.gif';
        loader.style.position = 'absolute';
        document.body.appendChild(loader);

        YAHOO.util.Dom.setXY(loader, [region.left + (w-16)/2, region.top + (h-16)/2]);

        htmlarea.cover = cover;
        htmlarea.coverLoader = loader;
    } else if(!progress && htmlarea.cover) {
        htmlarea.cover.parentNode.removeChild(htmlarea.cover);
        htmlarea.coverLoader.parentNode.removeChild(htmlarea.coverLoader);
        htmlarea.cover = null;
        htmlarea.coverLoader = null;
    }
}

function forumng_save_ok_draft(o) {
    var colon = o.responseText.indexOf(':');

    // Update draft id
    this.draft.value = o.responseText.substr(0, colon);

    // Show text
    if (!this.draftNotice) {
        this.draftNotice = document.createElement('div');
        this.draftNotice.className = 'forumng-draftexists';
        this.parentNode.insertBefore(this.draftNotice, this);
    } else {
        this.draftNotice.removeChild(this.draftNotice.firstChild);
    }
    this.draftNotice.appendChild(document.createTextNode(
        o.responseText.substr(colon+1)));

    // Enable editor again
    this.submitbutton.disabled = false;
    this.cancel.disabled = false;
    if (this.savedraft) {
        this.savedraft.disabled = false;
    }
    if (this.usingeditor) {
        if(this.mce) {
            tinyMCE.get(this.message.id).setProgressState(0);
        } else {
            forumng_htmlarea_progress_state(this.htmlarea, 0);
        }
    }
}


function forumng_init_attachments(form, attachments) {
    // Set up reference to list for convenience
    form.attachmentlist = YAHOO.util.Dom.getElementsByClassName(
            'forumng-form-attachments', 'ul', form)[0];

    // Clear existing data from list
    while(form.attachmentlist.firstChild) {
        form.attachmentlist.removeChild(form.attachmentlist.firstChild);
    }
    if(form.attachmentbutton) {
        form.attachmentbutton.parentNode.removeChild(form.attachmentbutton);
        form.attachmentbutton = null;
    }

    // If there are any existing attachments, add these
    if(attachments) {
        for(var i=0; i<attachments.length; i++) {
            forumng_add_attachment(form, attachments[i]);
        }
    }

    // Add the 'Add' button
    var button = document.createElement('input');
    button.type = 'button';
    button.value = forumng_strings.core_add;
    button.onclick = function() {
        button.disabled = true;
        window.currentform = form;
        var dialog = window.open('addattachment.php?id=' + forumng_cmid +
            forumng_cloneparam +
            (form.editpostid ? '&p=' + form.editpostid : '') +
            (form.attachmentplayspace.value
                    ? '&attachmentplayspace=' + form.attachmentplayspace.value : '' ),
            '_blank', 'resizable=yes,scrollbars=yes,' +
            'status=yes,width=450,height=100,dependent=yes,dialog=yes');

        var intervalid = setInterval(function() {
            if (dialog.closed) {
                button.disabled = false;
                clearInterval(intervalid);
            }
        },100);

        form.addattachment = function(name) {
            // Check it doesn't already exist
            var items = YAHOO.util.Dom.getChildren(form.attachmentlist);
            for (var i=0; i<items.length; i++) {
                if (items[i].firstChild.nodeValue.trim() == name) {
                    return;
                }
            }

            forumng_add_attachment(this, name);
        };
    };

    form.attachmentbutton = button;
    form.attachmentlist.parentNode.insertBefore(button,
            form.attachmentlist.nextSibling);
}


function forumng_add_attachment(form, attachment) {
    var li = document.createElement('li');
    li.appendChild(document.createTextNode(attachment + ' '));
    var img = document.createElement('img');
    img.src = forumng_pixpath + '/t/delete.gif';
    img.alt = forumng_strings.core_delete;
    img.title = img.alt;
    img.tabIndex = 0;
    img.form = form;
    img.oldsrc = img.src;
    li.appendChild(img);

    img.clickfunction = function() {
        if (form.attachmentlock) {
            return;
        }
        form.attachmentlock = true;
        img.src = forumng_pixpath + '/i/ajaxloader.gif';
        var data = 'id=' + forumng_cmid +  forumng_cloneparam +
            '&file=' + encodeURIComponent(attachment);
        if (form.editpostid) {
            data += '&p=' + form.editpostid;
        }
        if (form.attachmentplayspace.value) {
            data += '&attachmentplayspace=' + form.attachmentplayspace.value;
        }
        YAHOO.util.Connect.asyncRequest('POST','deleteattachment.php',
            {success:forumng_delete_attachment_ok,
                failure:forumng_delete_attachment_error, scope:img}, data);
    };
    YAHOO.util.Event.addListener(img, 'click', img.clickfunction);
    YAHOO.util.Event.addListener(img, 'keypress', function(e) {
        var code = e.which ? e.which : e.keyCode;
        if(code==32 || code==13) {
            img.clickfunction();
        }
    });

    form.attachmentlist.appendChild(li);
}

function forumng_delete_attachment_ok(o) {
    this.form.attachmentlock = false;
    this.parentNode.parentNode.removeChild(this.parentNode);
    this.form.attachmentplayspace.value = o.responseText;
}

function forumng_delete_attachment_error(o) {
    this.form.attachmentlock = false;
    this.src = this.oldsrc;
    alert(forumng_strings.jserr_alter);
}

function forumng_init_editor(form, value) {
    form.expectingeditor = form.tryinghtmleditor.value=='1'
    form.usingeditor = form.expectingeditor && (window.tinyMCE || window.HTMLArea) ;
    if (form.usingeditor) {
        form.mce = window.tinyMCE ? true :false;
    }

    if(form.expectingeditor) {
        if (!form.donetextarea) {
            var input = form.message;
            var textarea = document.createElement('textarea');
            textarea.name = input.name;
            textarea.cols = 50;
            textarea.rows = 20;
            input.parentNode.insertBefore(textarea, input);
            input.parentNode.removeChild(input);
            textarea.id = input.id;
            if (form.message != textarea) {
                form.message = textarea;
            }
            form.donetextarea = true;
        }
    }
    form.message.value = value;
    form.subject.value = '';
    if(form.setimportant) {
        form.setimportant.checked = false;
    }
    if(form.mailnow) {
        form.mailnow.checked = false;
    }
    form.attachmentplayspace.value = 0;

    if(form.usingeditor) {
        // This timeout required so that the editor has correct size
        setTimeout(function() {
            if(form.mce) {
                tinyMCE.execCommand('mceAddControl', false, form.message.id);
                // And this one required so that mceFocus works
                var focusFunction = function() {
                    tinyMCE.execCommand('mceFocus', false, form.message.id);
                };

                if(navigator.product != 'Gecko') {
                    setTimeout(focusFunction, 0);
                } else {
                    setTimeout(function() {
                        form.subject.focus();
                        setTimeout(focusFunction , 0);
                    }, 0);
                }
            } else {
                form.inithtmlarea();
            }
        },0);
        form.format.value = 1;
    }
}

function forumng_save(form, param, ok, error) {
    var data = 'ajax=1&' + param;
    var inputs = YAHOO.util.Dom.getElementsBy(
        function(e) { return !e.disabled; }, 'input', form);
    for (var i=0; i<inputs.length; i++) {
        var input = inputs[i];
        if (input.name=='replyto') {
            continue;
        }
        switch (input.type) {
        case 'checkbox':
            if (input.checked) {
                data += '&' + input.name + '=' + encodeURIComponent(input.value);
            }
            break;
        case 'text':
        case 'hidden':
            data += '&' + input.name + '=' + encodeURIComponent(input.value);
            break;
        }
    }
    var textareas = YAHOO.util.Dom.getElementsBy(
        function(e) { return !e.disabled; }, 'textarea', form);
    for (var i=0; i<textareas.length; i++) {
        var textarea = textareas[i];
        data += '&' + textarea.name + '=' + encodeURIComponent(textarea.value);
    }
    var selects = YAHOO.util.Dom.getElementsBy(
        function(e) { return !e.disabled; }, 'select', form);
    for (var i=0; i<selects.length; i++) {
        var select = selects[i];
        data += '&' + select.name + '=' + encodeURIComponent(select.value);
    }

    YAHOO.util.Connect.asyncRequest('POST','editpost.php',
        {success:ok,failure:error,scope:form}, data);
}

function forumng_save_ok_reply(o) {
    // Behave like cancelling form
    this.cancel.onclick();
    this.submitbutton.disabled = false;
    this.cancel.disabled = false;

    // Get replies div
    var replies;
    if (this.post.nextSibling
        && this.post.nextSibling.className=='forumng-replies') {
        replies = this.post.nextSibling;
    } else {
        replies = document.createElement('div');
        replies.className = 'forumng-replies';
        this.post.parentNode.insertBefore(replies, this.post.nextSibling);
        forumng_apply_stop_indents();
    }

    // Add item there
    var newDiv = document.createElement('div');
    newDiv.innerHTML = o.responseText;
    var newPost = newDiv.firstChild;
    newDiv.removeChild(newPost);
    replies.appendChild(newPost);

    forumng_init_content(newPost);

    // Scroll to it
    forumng_scroll_page(newPost, null);

    // Update quota left
    if (forumng_quotaleft > 0) {
        forumng_quotaleft--;

        // If out of quota, kill all the reply links
        if (forumng_quotaleft == 0) {
            forumng_kill_reply_links(document);
        }
    }
}

function forumng_kill_reply_links(root) {
    var links = root.getElementsByTagName('a');
    for (var i=links.length-1; i>=0; i--) {
        var link = links[i];
        if (link.href && link.href.match(/editpost\.php\?replyto=[0-9]+.*$/)) {
            link.parentNode.parentNode.removeChild(link.parentNode);
        }
    }
}

function forumng_scroll_page(target, after) {
    var scroll = {
        from : YAHOO.util.Dom.getDocumentScrollTop(),
        to : YAHOO.util.Dom.getXY(target)[1]
    };

    var time = Math.min(0.5, Math.abs(scroll.from-scroll.to)/200);
    var anim = new YAHOO.util.Anim(null, { 'scroll' : scroll }, time, 
        YAHOO.util.Easing.easeOut);
    anim.setAttribute = function(a, v, u) {
        window.scroll(0, v);
    };
    if (after) {
        anim.onComplete.subscribe(after);
    }
    anim.animate();
}

function forumng_save_error(o) {
    this.submitbutton.disabled = false;
    this.cancel.disabled = false;
    if (this.usingeditor) {
        if (this.mce) {
            tinyMCE.get(this.message.id).setProgressState(0);
        } else {
            forumng_htmlarea_progress_state(this.htmlarea, 0);
        }
    }
    alert(forumng_strings.jserr_save);
}

function forumng_init_edit(link, postid) {
    link.onclick = function() {
        // Root edit uses different form
        var isroot = YAHOO.util.Dom.getAncestorByClassName(
                link, 'forumng-replies')===null;

        // Get form and post
        var form = document.getElementById(isroot ? 'mform3' : 'mform2');
        form.isroot = isroot;
        form.post = YAHOO.util.Dom.getAncestorByClassName(link, 'forumng-post');
        form.editlimitnode = document.getElementById(
                isroot ? 'id_editlimit3' : 'id_editlimit2');
        form.editlimitfield = YAHOO.util.Dom.getAncestorByClassName(
                form.editlimitnode, 'fitem');

        // Cancel if an existing reply is in progress
        if (!forumng_prepare_form(form)) {
            return false;
        }

        // Set up form details for edit
        form.editpostid = postid;
        YAHOO.util.Connect.asyncRequest('GET',
            'expandpost.php?raw=1&playspace=1&p=' + postid + forumng_cloneparam,
            {success:forumng_editstart_ok,failure:forumng_editstart_error,scope:form});
        return false;
    }
}

function forumng_editstart_ok(o) {
    var form = this;

    // Get postdata variable
    eval(o.responseText);

    // Put form as last thing in post (except the 'end post' marker)
    this.post.insertBefore(this, this.post.lastChild);

    // Initialise form HTML editor and data
    forumng_init_editor(this, postdata.message);
    this.subject.value = postdata.subject;
    this.format.value = postdata.format;
    if (this.setimportant) {
        this.setimportant.checked = postdata.setimportant==1? true : false;
    }
    forumng_init_attachments(this, postdata.attachments);

    var seteditlimit = function(message, tag, classname) {
        while (form.editlimitnode.firstChild) {
            form.editlimitnode.removeChild(form.editlimitnode.firstChild);
        }
        var parent = form.editlimitnode;
        if (tag) {
            parent = document.createElement(tag);
            if(classname) {
                parent.className = classname;
            }
            form.editlimitnode.appendChild(parent);
        }
        parent.appendChild(document.createTextNode(message));
    };

    if(postdata.editlimit != 0) {
        seteditlimit(postdata.editlimitmsg, null, null);
        this.editlimitfield.style.display = 'block';
        var expiry = postdata.editlimit*1000;

        // Warning when timeout is near
        this.timers.push(setTimeout(function() {
            seteditlimit(postdata.editlimitmsg, 'strong', null);
        }, expiry-90000));

        // Disable submit when timeout is done (we allow 30s for server processing)
        this.timers.push(setTimeout(function() {
            form.editover = true;
            seteditlimit(forumng_strings.edit_timeout, 'strong', 'forumng-timeoutover');
        }, expiry-30000));
    } else {
        seteditlimit('', null, null);
        this.editlimitfield.style.display = 'none';
    }

    // Post button handling
    this.submitbutton.onclick = function() {
        this.disabled = true;
        this.form.cancel.disabled = true;
        if (this.form.usingeditor) {
            if (this.form.mce) {
                tinyMCE.triggerSave();
                tinyMCE.get(this.form.message.id).setProgressState(1);
            } else {
                this.form.message.value = this.form.htmlarea.getHTML();
                forumng_htmlarea_progress_state(this.form.htmlarea, 1);
            }
        }
        forumng_save(this.form, 'p=' + this.form.editpostid + forumng_cloneparam,
                forumng_save_ok_edit, forumng_save_error);
        return false;
    };

    // Make form visible
    this.style.display = 'block';
}

function forumng_editstart_error(o) {
    this.cancel.onclick();
    alert(forumng_strings.jserr_load);
}

function forumng_save_ok_edit(o) {
    // Behave like cancelling form
    this.cancel.onclick();
    this.submitbutton.disabled = false;
    this.cancel.disabled = false;

    // Add item just in front of existing post, then delete existing
    var newdiv = document.createElement('div');
    newdiv.innerHTML = o.responseText;
    var newpost = newdiv.firstChild;
    newdiv.removeChild(newpost);
    this.post.parentNode.insertBefore(newpost, this.post);
    this.post.parentNode.removeChild(this.post);

    // For discussion, do special handling
    if (this.isroot) {
        // Get subject and remove its node
        var subjectinput = YAHOO.util.Dom.getElementsBy(function(el) {
            return el.name == 'discussion_subject';
        }, 'input', newpost)[0];
        var subject = subjectinput.value;
        subjectinput.parentNode.removeChild(subjectinput);

        // Update discussion in breadcrumb (last <li>)
        var breadcrumb = YAHOO.util.Dom.getElementsBy(function(el) {
            return !el.nextSibling;
        }, 'li', document.getElementById('navbar'))[0];

        // Looking for a direct text child
        var lastText = null;
        for (var el=breadcrumb.firstChild; el; el=el.nextSibling) {
            if (el.nodeType == 3) {
                lastText = el;
            }
        }

        if(lastText) {
            var next = lastText.nextSibling;
            breadcrumb.removeChild(lastText);
            breadcrumb.insertBefore(document.createTextNode(' ' + subject), next);
        }
    }

    // Sort out links
    forumng_init_content(newpost);
}

function forumng_init_form(f) {
    // Hide the format item
    var formatItem = f.format.parentNode.parentNode;
    if (f.tryinghtmleditor.value=='1') {
        formatItem.style.display='none';
    }
    f.expectingeditor = f.tryinghtmleditor.value=='1';
    f.usingeditor = f.expectingeditor && (window.tinyMCE || window.HTMLArea) ;
    if (f.usingeditor) {
        f.format.value = 1;
        //alert('Set the value of the form to 1 in init_forum');
    }
}

function forumng_remove_px(string) {
    return parseInt(string.replace(/px$/, ''));
}

/**
 * Displays a fancy dialog box on a faded-out background in the middle of the
 * screen.
 * @param message Message to display (may include html; if heading is included,
 *     we recommend h4)
 * @param actiontext Name for action button(s). May be a single string or 
 *     array if you need multiple buttons
 * @param canceltext Name for cancel button
 * @param highlight HTML element that should be highlighted (with an orange 
 *     box), used e.g. to indicate which post is being deleted
 * @param action Function that gets run if user clicks the action button
 *     (if there are multiple action buttons, this too must be an array)
 */
function forumng_confirm(message, actiontext, canceltext, highlight, action) {
    if(typeof actiontext == 'string') {
        // There is only one action (text and functions); make it look like an array
        actiontext = [actiontext];
        action = [action];
    }

    var fadepanel = document.createElement('div');
    fadepanel.className = 'forumng-fadepanel';
    document.body.appendChild(fadepanel);
    fadepanel.style.position = 'absolute';
    fadepanel.style.top = '0';
    fadepanel.style.left = '0';
    fadepanel.style.width = YAHOO.util.Dom.getDocumentWidth() + "px";
    fadepanel.style.height = YAHOO.util.Dom.getDocumentHeight() + "px";
    fadepanel.style.zIndex = 10;
    YAHOO.util.Dom.setStyle(fadepanel, 'opacity', '0.0');

    var anim = new YAHOO.util.Anim(fadepanel, {
        'opacity' : {
            from : 0.0,
            to : 0.5
        } }, 0.25, YAHOO.util.Easing.easeNone);
    anim.animate();

    var highlightdiv = null;
    if (highlight) {
        var highlightregion = YAHOO.util.Dom.getRegion(highlight);

        highlightdiv = document.createElement('div');
        highlightdiv.className = 'forumng-highlightbox';
        document.body.appendChild(highlightdiv);
        highlightdiv.style.position = 'absolute';
        highlightdiv.style.top = highlightregion.top + 'px';
        highlightdiv.style.left = highlightregion.left + 'px';
        highlightdiv.style.zIndex = 15;

        var height = highlightregion.bottom - highlightregion.top -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'border-top-width')) -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'border-bottom-width')) -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'padding-top')) -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'padding-bottom'));
        var width = highlightregion.right - highlightregion.left -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'border-left-width')) -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'border-right-width')) -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'padding-left')) -
            forumng_remove_px(YAHOO.util.Dom.getStyle(highlightdiv, 'padding-right'));

        highlightdiv.style.height = height + 'px';
        highlightdiv.style.width = width + 'px';
    }

    var dialog = document.createElement('div');
    document.body.appendChild(dialog);
    dialog.className = 'forumng-confirmdialog';

    dialog.style.position = 'absolute';
    dialog.style.zIndex = 20;
    var region = YAHOO.util.Dom.getClientRegion();
    region.height = region.bottom - region.top;
    region.width = region.right - region.left;
    dialog.style.top = (region.top + region.height/3) + "px";
    // Dialog width is 350px, centre it
    dialog.style.left = (region.left + region.width/2 - 175) + "px";

    var messagediv = document.createElement('div');
    messagediv.className = 'forumng-message';
    messagediv.innerHTML = message;
    dialog.appendChild(messagediv);

    var buttondiv = document.createElement('div');
    buttondiv.className = 'forumng-buttons';
    dialog.appendChild(buttondiv);
    var cancel = document.createElement('input');
    cancel.type = 'button';
    cancel.value = canceltext;
    cancel.onclick = function() {
        dialog.parentNode.removeChild(dialog);
        fadepanel.parentNode.removeChild(fadepanel);
        if (highlightdiv) {
            highlightdiv.parentNode.removeChild(highlightdiv);
        }
    }

    for(var i=0; i<actiontext.length; i++) {
        buttondiv.appendChild(forumng_confirm_make_button( 
            actiontext[i], action[i], cancel));
    }

    buttondiv.appendChild(cancel);
}

function forumng_confirm_make_button(actiontext, action, cancel) {
    var yes = document.createElement('input');
    yes.type = 'button';
    yes.value = actiontext;
    yes.onclick = function() {
        cancel.onclick();
        action();
    }
    forumng_focus(yes);
    return yes;
}

function forumng_init_delete(link, postid, undelete) {
    link.postid = postid;
    link.post = YAHOO.util.Dom.getAncestorByClassName(link, 'forumng-post');

    link.onclick = function() {
        forumng_confirm(undelete ? forumng_strings.confirmundelete
                : forumng_strings.confirmdelete,
            undelete ? forumng_strings.undeletepostbutton
                : forumng_strings.deletepostbutton,
                forumng_strings.core_cancel, link.post, function() {
            YAHOO.util.Connect.asyncRequest('POST','deletepost.php',
                {success:forumng_delete_ok,failure:forumng_delete_error,scope:link},
                'p=' + link.postid + forumng_cloneparam +
                '&delete=' + (undelete ? 0 : 1) + '&ajax=1');
            forumng_links_disable(link.post);
            if (forumng_pixpath) {
                link.loader = document.createElement('img');
                link.loader.alt = '';
                link.loader.src = forumng_pixpath + '/i/ajaxloader.gif';
                link.loader.style.position = 'absolute';
                link.parentNode.appendChild(link.loader);
                var linkregion = YAHOO.util.Dom.getRegion(link);
                YAHOO.util.Dom.setXY(link.loader, [linkregion.right + 3, linkregion.top]);
            }
        });
        return false;
    };
}

function forumng_delete_ok(o) {
    var newDiv = document.createElement('div');
    newDiv.innerHTML = o.responseText;
    var newPost = newDiv.firstChild;
    // Post may be blank when deleting (if not admin)
    if (newPost) {
        this.post.parentNode.insertBefore(newPost, this.post);
    }
    this.post.parentNode.removeChild(this.post);
    if (newPost) {
        forumng_init_content(newPost);
    }
}

function forumng_delete_error(o) {
    this.loader.parentNode.removeChild(this.loader);
    forumng_links_enable(this.post);
    alert(forumng_strings.jserr_alter);
}

function forumng_set_stars(div) {
    var userpos, publicpos;
    var clearing = false;
    if (div.hastemprating) {
        if (div.hasuserrating && div.temprating == div.userrating) {
            clearing = true;
            userpos = -1;
        } else {
            userpos = div.temprating;
        }
    } else {
        userpos = div.hasuserrating ? div.userrating : -1;
    }
    publicpos = div.haspublicrating ? div.publicrating : -1;

    for(var i=0; i< div.stars.length; i++) {
        var user = i==userpos, pub = i<=publicpos;
        div.stars[i].src = forumng_modpixpath + (i==0 ? '/circle-' : '/star-') +
            (user ? 'y' : 'n') + "-" + (pub ? 'y' : 'n') + ".png";
    }

    while(div.countspan.firstChild) {
        div.countspan.removeChild(div.countspan.firstChild);
    }
    if(div.ratingcount) {
        div.countspan.appendChild(document.createTextNode(
                ' ' + (div.ratingcount == 1 ? forumng_strings.js_nratings1 :
                    forumng_strings.js_nratings.replace(/#/, div.ratingcount))));
    }

    var title = clearing ? forumng_strings.js_clicktoclearrating :
        div.temprating==1 ? forumng_strings.js_clicktosetrating1 :
        forumng_strings.js_clicktosetrating.replace(/#/, div.temprating);
    if (div.canview) {
        if (!div.haspublicrating) {
            title += ' ' + forumng_strings.js_nopublicrating;
        } else {
            title += ' ' + forumng_strings.js_publicrating.replace(/#/, div.publicrating);
        }
    }
    if (div.canrate) {
        if (!div.hasuserrating) {
            title += ' ' + forumng_strings.js_nouserrating;
        } else {
            title += ' ' + forumng_strings.js_userrating.replace(/#/, div.userrating);
        }
    }
    title += ' ' + forumng_strings.js_outof.replace(/#/, forumng_ratingstars);

    for (var i=0; i<div.stars.length; i++) {
        div.stars[i].title = title.replace(/^\s*/, '');
    }
}

function forumng_star_ok(o) {
    forumng_links_enable(this.post);
    this.userrating = this.newrating;
    this.hasuserrating = this.newrating != 999;
    var re = /<strong id="rating_for_[0-9]+">([0-9]+) \//;
    var match = re.exec(o.responseText);
    if (match) {
        this.publicrating = match[1];
        this.haspublicrating = true;
    } else {
        this.haspublicrating = false;
        this.ratingcount = 0;
    }
    var re = /<span class="forumng-count">([0-9]+)<\/span>/;
    var match = re.exec(o.responseText);
    if (match) {
        this.ratingcount = parseInt(match[1]);
    }
    forumng_set_stars(this);
}

function forumng_init_rating(div) {
    div.style.display = 'inline';
    div.post = YAHOO.util.Dom.getAncestorByClassName(div, 'forumng-post');
    div.ratingcount = 0;

    var selects = div.getElementsByTagName('select');
    if (selects.length > 0) {
        div.select = selects[0];
        div.postid = parseInt(div.select.name.replace(/^rating/, ''));
        div.userrating = selects[0].value;
        div.canrate = true;
        div.hasuserrating = div.userrating != 999;
    }
    var strongs = div.getElementsByTagName('strong');
    if (strongs.length > 0) {
        div.publicratingvalue = strongs[0].firstChild.nodeValue;
        div.publicrating = parseInt(div.publicratingvalue.replace(/\s*\/.*$/,''));
        div.postid = parseInt(strongs[0].id.replace(/^rating_for_/, ''));
        div.haspublicrating = true;
        div.ratingcount = parseInt(strongs[0].parentNode.getElementsByTagName(
                'span')[0].firstChild.nodeValue);
    }
    div.canview = div.className.indexOf('forumng-canview') != -1;

    if (forumng_ratingstars && true) {
        // Get rid of everything inside the area and replace it with magic stars
        while (div.firstChild) {
            div.removeChild(div.firstChild);
        }
        div.starspan = document.createElement('span');
        div.appendChild(div.starspan);
        div.stars = [];
        for (var i=0; i<=forumng_ratingstars; i++) {
            var star = document.createElement('img');
            star.rating = i;
            star.width = 16;
            star.height = 16;
            star.alt = i;
            if (div.canrate) {
                star.tabIndex = 0;

                star.clickfunction = function() {
                    div.newrating = this.rating;
                    if (div.hasuserrating && div.userrating == div.newrating) {
                        div.newrating = 999;
                    }
                    YAHOO.util.Connect.asyncRequest('POST','rate.php',
                        {success:forumng_star_ok,failure:forumng_delete_error,scope:div},
                        'p=' + div.postid  + forumng_cloneparam
                        + '&rating=' + div.newrating + '&ajax=1');
                    forumng_links_disable(div.post);

                    // Use the current star as a loader icon place
                    this.src = forumng_pixpath + '/i/ajaxloader.gif';
                };

                YAHOO.util.Event.addListener(star, 'click', star.clickfunction);
                YAHOO.util.Event.addListener(star, 'keypress', function(e) {
                    var code = e.which ? e.which : e.keyCode;
                    if(code==32 || code==13) {
                        this.clickfunction();
                    }
                });

                star.onfocus = function() { this.className = 'forumng-starfocus'; };
                star.onblur = function() { this.className = ''; };

                YAHOO.util.Event.addListener(star, 'mouseover', function() {
                    div.hastemprating = true;
                    div.temprating = this.rating;
                    forumng_set_stars(div);
                });
                YAHOO.util.Event.addListener(star, 'mouseout', function() {
                    div.hastemprating = false;
                    forumng_set_stars(div);
                });
            }
            div.starspan.appendChild(star);
            div.stars[i] = star;
        }

        // Set up number of votes
        div.countspan = document.createElement('span');
        div.appendChild(div.countspan);

        // Set star initial value
        forumng_set_stars(div);
    } else {
        // No stars, just add AJAX to dropdown
        if (!div.select) {
            return;
        }

        var newbutton = document.createElement('input');
        newbutton.type = 'button';
        newbutton.value = forumng_strings.rate;
        div.select.parentNode.insertBefore(newbutton, div.select.nextSibling);

        newbutton.onclick = function() {
            newbutton.disabled = true;

            YAHOO.util.Connect.asyncRequest('POST','rate.php',
                {success:forumng_delete_ok,failure:forumng_delete_error,scope:div},
                'p=' + div.postid  + forumng_cloneparam +
                '&rating=' + div.select.value + '&ajax=1');
            forumng_links_disable(div.post);

            if (forumng_pixpath) {
                div.loader = document.createElement('img');
                div.loader.alt = '';
                div.loader.src = forumng_pixpath + '/i/ajaxloader.gif';
                div.loader.style.position = 'absolute';
                div.parentNode.appendChild(div.loader);
                var byregion = YAHOO.util.Dom.getRegion(newbutton);
                YAHOO.util.Dom.setXY(div.loader, [byregion.right + 3, byregion.top + 2]);
            }
        };
    }
}

function forumng_zero_disable(submit) {
    var select = YAHOO.util.Dom.getPreviousSibling(submit);
    if (!select || select.nodeName.toLowerCase() != 'select') {
        forumng_log('Warning: Zero-disable feature incorrectly applied.');
        return;
    }
    select.onchange = function() {
        submit.disabled = select.value == 0;
    };
    select.onchange();
}

var forumng_init_expiry = function(link, seconds) {
    // Actually expires a bit early
    this.javatime = seconds * 1000 - 45000 + new Date().getTime();
    this.target = link;
    link.href = link.href.replace(/[?&]expires=[0-9]+/, '');

    document.forumng_expirelinks.push(this);
    if(document.forumng_expirelinks.length == 1) {
        var timerid = setInterval(function() {
            var current = new Date().getTime();
            for(var i=document.forumng_expirelinks.length-1; i>=0; i--) {
                if (current > document.forumng_expirelinks[i].javatime) {
                    var deadlink = document.forumng_expirelinks[i].target;
                    deadlink.parentNode.removeChild(deadlink);
                    document.forumng_expirelinks.splice(i, 1);
                }
            }
            if(document.forumng_expirelinks.length == 0) {
                clearInterval(timerid);
            }
        }, 15000);
    }
}

function forumng_init_parentlink(link) {
    link.onfocus = function() {
        link.parentNode.style.position = 'static';
    };
    link.onblur = function() {
        link.parentNode.style.position = 'absolute';
    };
}

function forumng_select_init_post(post, on, selectChanged) {
    if(on) {
        var info = YAHOO.util.Dom.getElementsByClassName('forumng-info', 'div', post)[0];
        var span = document.createElement('span');
        info.appendChild(span);
        post.extraSpan = span;
        post.className += ' forumng-deselected';
        var postid = post.getElementsByTagName('a')[0].id;

        span.appendChild(document.createTextNode(' \u2022 '));
        var check = document.createElement('input');
        post.check = check;
        check.type = 'checkbox';
        span.appendChild(check);
        var label = document.createElement('label');
        label.className = 'accesshide';
        label.setAttribute('for', check.id);
        span.appendChild(label);
        label.appendChild(document.createTextNode(forumng_strings.selectlabel));
        forumng_links_disable(post);

        var hidden = document.createElement('input');
        post.hidden = hidden;
        hidden.type = 'hidden';
        hidden.name = 'select' + postid;
        hidden.value = 0;
        forumng_select.form.appendChild(hidden);

        check.onclick = function() {
            if(check.checked) {
                post.className = post.className.replace(' forumng-deselected', '');
                post.hidden.value = 1;
            } else {
                post.className += ' forumng-deselected';
                post.hidden.value = 0;
            }
            selectChanged();
        };
    } else {
        post.extraSpan.parentNode.removeChild(post.extraSpan);
        post.className = post.className.replace(' forumng-deselected', '');
        forumng_links_enable(post);
    }
}

function forumng_select_init(target) {
    forumng_select.on = target ? true : false;

    var posts = YAHOO.util.Dom.getElementsByClassName('forumng-post', 'div');
    var confirm = document.createElement('input');

    var extraneousDisplay = forumng_select.on ? 'none' : 'block';
    document.getElementById('forumng-expandall').style.display = extraneousDisplay;
    document.getElementById('forumng-features').style.display = extraneousDisplay;
    var subscribeOptions = document.getElementById('forumng-subscribe-options');
    if(subscribeOptions) {
        subscribeOptions.style.display = extraneousDisplay;
    }

    var main = document.getElementById('forumng-main');
    if (forumng_select.on) {
        // Make form around main elements
        var form = document.createElement('form');
        forumng_select.form = form;
        form.method = 'post';
        form.action = target.form.action;        
        main.className += ' forumng-selectmode';

        form.inputs = document.createElement('div');
        form.appendChild(form.inputs);
        var field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'd';
        field.value = forumng_discussionid;
        form.inputs.appendChild(field);
        field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'fromselect';
        field.value = 1;
        form.inputs.appendChild(field);
        if (forumng_cloneid) {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = 'clone';
            field.value = forumng_cloneid;
            form.inputs.appendChild(field);
        }

        // Make intro
        form.intro = document.createElement('div');
        form.intro.className = 'forumng-selectintro';
        main.parentNode.insertBefore(form.intro, main);
        var introText = document.createElement('p');
        form.intro.appendChild(introText);
        introText.innerHTML = forumng_strings.selectintro;

        // Make buttons to select all/none
        var selectButtons = document.createElement('div');
        selectButtons.className = 'forumng-selectbuttons';
        form.intro.appendChild(selectButtons);
        var all = document.createElement('input');
        all.type = 'button';
        selectButtons.appendChild(all);
        all.value = forumng_strings.core_selectall;
        all.onclick = function() {
            for (var i=0; i<posts.length; i++) {
                if(!posts[i].check.checked) {
                    posts[i].check.checked = true;
                    posts[i].check.onclick();
                }
            }
        };
        selectButtons.appendChild(document.createTextNode(' '));
        var none = document.createElement('input');
        none.type = 'button';
        selectButtons.appendChild(none);
        none.value = forumng_strings.core_deselectall;
        none.onclick = function() {
            for (var i=0; i<posts.length; i++) {
                if(posts[i].check.checked) {
                    posts[i].check.checked = false;
                    posts[i].check.onclick();
                }
            }
        };

        main.appendChild(form);

        // Make outro
        form.outro = document.createElement('div');
        form.outro.className = 'forumng-selectoutro';
        form.appendChild(form.outro);

        confirm.type = 'submit';
        confirm.value = forumng_strings.confirmselection;
        form.outro.appendChild(confirm);

        form.outro.appendChild(document.createTextNode(' '));

        var cancel = document.createElement('input');
        cancel.type = 'button';
        cancel.id = 'forumng-cancel-select';
        cancel.value = forumng_strings.core_cancel;
        form.outro.appendChild(cancel);
        cancel.onclick = function() {
            forumng_select_init(null);
        };

        forumng_scroll_page(form.intro, null);
    } else {
        var form = forumng_select.form;
        form.parentNode.removeChild(form);
        form.intro.parentNode.removeChild(form.intro);
        form.outro.parentNode.removeChild(form.outro);
        main.className = main.className.replace(/ forumng-selectmode/, '');
        forumng_select.form = null;
    }

    var selectChanged = function() {
        var ok = false;
        for (var i=0; i<posts.length; i++) {
            if (posts[i].check.checked) {
                ok = true;
                break;
            }
        }
        confirm.disabled = !ok;
    };
    for (var i=0; i<posts.length; i++) {
        forumng_select_init_post(posts[i], forumng_select.on, selectChanged);
    }
    if(forumng_select.on) {
        selectChanged();
    }

}

function forumng_init_select_button(submit) {
    submit.onclick = function() {
        try {
            forumng_confirm("<h4>" + submit.value + "</h4><p>" +
                forumng_strings.selectorall + "</p>",
                [forumng_strings.discussion, forumng_strings.selectedposts],
                forumng_strings.core_cancel,
                null, [function() {
                    location.href = submit.form.action + '?d=' +
                        forumng_discussionid + forumng_cloneparam + '&all=1';
                }, function() {
                    forumng_select_init(submit);
                }]);
        } catch (e) {
            forumng_log(e);
        } return false;
    };
}

function forumng_init_feature_buttons() {
    var featureForms = YAHOO.util.Dom.getElementsBy(
        function(e) { return e.action.match(/feature\//); }, 'form');
    for (var i=0; i<featureForms.length; i++) {
        if (featureForms[i].action.match(/feature\/forward\/forward.php$/)) {
            var submit = YAHOO.util.Dom.getElementsBy(
                function(e) { return e.type=='submit'; }, 'input', 
                    featureForms[i])[0];
            forumng_init_select_button(submit);
        }
        if (featureForms[i].action.match(/feature\/export\/export.php$/)) {
            var submit = YAHOO.util.Dom.getElementsBy(
                function(e) { return e.type=='submit'; }, 'input', 
                    featureForms[i])[0];
            forumng_init_select_button(submit);
        }
        if (featureForms[i].action.match(/feature\/print\/print.php$/)) {
            var submit = YAHOO.util.Dom.getElementsBy(
                function(e) { return e.type=='submit'; }, 'input', 
                    featureForms[i])[0];
            forumng_init_select_button(submit);
        }
        if (featureForms[i].action.match(/feature\/portfolio\/savetoportfolio.php$/)) {
            var submit = YAHOO.util.Dom.getElementsBy(
                function(e) { return e.type=='submit'; }, 'input', 
                    featureForms[i])[0];
            forumng_init_select_button(submit);
        }
    }
}

function forumng_urgent_init() {
    // Create new stylesheet in head
    var newstyle = document.createElement("style");
    newstyle.setAttribute("type", "text/css");

    var selector = '.forumng-ratings';
    var rules = 'display:none';

    if (document.styleSheets && document.styleSheets.length > 0 &&
        document.styleSheets[0].addRule) {
        // Internet Explorer addRule usage
        document.getElementsByTagName("head")[0].appendChild(newstyle);
        document.styleSheets[document.styleSheets.length - 1].addRule(
            selector, rules);
    } else {
        // Other browsers, just add stylesheet into DOM
        newstyle.appendChild(
            document.createTextNode(selector + " {" + rules + "}"));
        document.getElementsByTagName("head")[0].appendChild(newstyle);
    }
}

function forumng_init_subscribers() {
    var buttonsDiv = document.getElementById('forumng-buttons');
    var selectAll = document.createElement('input');
    selectAll.type = 'button';
    selectAll.value = forumng_strings.core_selectall;
    buttonsDiv.appendChild(document.createTextNode(' '));
    buttonsDiv.appendChild(selectAll);
    var deselectAll = document.createElement('input');
    deselectAll.type = 'button';
    deselectAll.value = forumng_strings.core_deselectall;
    buttonsDiv.appendChild(document.createTextNode(' '));
    buttonsDiv.appendChild(deselectAll);

    var unsubscribe; 
    var inputs = selectAll.form.getElementsByTagName('input');
    var all = [];
    for(var i=0; i<inputs.length; i++) {
        if(inputs[i].name.indexOf('user')==0) {
            all.push(inputs[i]);
        }        
        if(inputs[i].name=='unsubscribe') {
            unsubscribe = inputs[i];
        }
    }

    var update = function() {
        var allSelected=true, noneSelected=true;
        for(var i=0; i<all.length; i++) {
            if(all[i].checked) {
                noneSelected = false;
            } else {
                allSelected = false;
            }
        }
        selectAll.disabled = allSelected;
        deselectAll.disabled = noneSelected;
        unsubscribe.disabled = noneSelected;
    };
    update();

    for(var i=0; i<all.length; i++) {
        all[i].onclick = function() {
            update();
        }
    };

    selectAll.onclick = function() {        
        for(var i=0; i<all.length; i++) {
            all[i].checked = true;
        }
        update();
    };

    deselectAll.onclick = function() {        
        for(var i=0; i<all.length; i++) {
            all[i].checked = false;
        }
        update();
    };
}

function forumng_init_flag_div(div) {
    // Get on state from image icon
    div.icon = div.lastChild;
    div.on = div.icon.src.match(/on.png$/);
    // Get id from p value
    div.postid = div.icon.name.replace(/^.*p_([^.]*)\..*$/, '$1');
    div.icon.onclick = function() {
        YAHOO.util.Connect.asyncRequest('POST', 'flagpost.php', {
            success:function(o) {
                div.on = !div.on;
                div.icon.src = div.icon.src.replace(/o(n|ff)\.png$/, 
                    (div.on ? 'on' : 'off') + '.png');
                div.icon.title = 
                    div.on ? forumng_strings.clearflag : forumng_strings.setflag;
                div.icon.alt = 
                    div.on ? forumng_strings.flagon : forumng_strings.flagoff;
            },
            failure:function(o) {
                alert(forumng_strings.jserr_alter);
            }}, 
            'p='+div.postid  + forumng_cloneparam +
            '&flag=' + (div.on ? 0 : 1) + '&ajax=1');

        return false;
    };
}

function forumng_init_flags(el) {
    var divs = YAHOO.util.Dom.getElementsByClassName('forumng-flag', 'div', el);
    for (var i=0; i<divs.length; i++) {
        forumng_init_flag_div(divs[i]);
    }
}

function forumng_focus(x) {
    setTimeout(function() { x.focus(); }, 0);
}

function forumng_init_jumplink(link) {
    link.onmousedown = function() {
        forumng_mouseuser = true;
        return true;
    };
    link.onclick = function() {
        var id = link.href.substring(link.href.indexOf('#')+1);

        // Function to set up focus
        var focuser = function() {
            var targetpost = document.getElementById(id).parentNode;
            var jumptos = YAHOO.util.Dom.getElementsByClassName(
                    'forumng-jumpto', 'li', targetpost);
            if (forumng_mouseuser && jumptos.length>0) {
                // For mouse (~= visual) users, go to the next link so they can
                // repeatedly press return
                var jumps = jumptos[0];
                var equivalent = YAHOO.util.Dom.getElementsByClassName(
                    link.className, 'a', jumps)[0];
                if (equivalent) {
                    forumng_focus(equivalent);
                } else {
                    forumng_focus(jumps.getElementsByTagName('a')[0]);
                }
            } else {
                // For keyboard-only users, go to the start of the post (makes more sense)
                var author = YAHOO.util.Dom.getElementsByClassName(
                    'forumng-author', 'span', targetpost)[0];
                forumng_focus(author.getElementsByTagName('a')[0]);
            }
        }; 

        // Get link target and expand it if required
        var targetpost2 = document.getElementById(id).parentNode;
        if(targetpost2.expandlink) {
            targetpost2.expandlink.onclick();
            targetpost2.focushandler = focuser;
        }

        // Scroll to it
        forumng_scroll_page(targetpost2, function() {
            var targetpost3 = document.getElementById(id).parentNode;
            // If post has already been expanded, focus it now
            if (!targetpost3.focushandler) {
                focuser();
            }
        });
        return false;
    };
}

function forumng_init_view() {
    //set the focus on the sort links when clicked
    forumng_focus_sort_links();
    var links = document.getElementsByTagName('a');
    for (var i=0; i<links.length; i++) {
        var link = links[i];
        var match = link.className.match(/^forumng-draftreply-([0-9]+)-([0-9]+)$/);
        if (match) {
            var linkmatch = link.href.match(/draft=([0-9]+)$/);
            link.href = 'discuss.php?d=' + match[1] + forumng_cloneparam +
                '&draft=' + linkmatch[1] + '#p' + match[2];
        }
    }
    forumng_init_flags(document.body);

    // Change selected buttons into links with text in brackets
    var specialbuttons = YAHOO.util.Dom.getElementsByClassName(
            'forumng-button-to-link', 'input');
    for (var i=0; i<specialbuttons.length; i++) {
        forumng_turn_button_into_link(specialbuttons[i]);
    }
}

function forumng_turn_button_into_link(button) {
    var span = document.createElement('span');
    span.appendChild(document.createTextNode('('));
    var link = document.createElement('a');
    link.appendChild(document.createTextNode(button.value));
    link.href = '#';
    link.onclick = function() { button.click(); return false; }
    span.appendChild(link);
    span.appendChild(document.createTextNode(') '));
    button.parentNode.insertBefore(span, button);
    button.style.display = 'none';
}

function forumng_init_switchlink(link) {
    link.onfocus = function() {
        link.parentNode.style.position = 'static';
    };
    link.onblur = function() {
        link.parentNode.style.position = 'absolute';
    };
}

function forumng_print_page() {
    window.print();
}

function forumng_focus_sort_links() {
    var url = window.location.href;
    var searchindex = url.search(/&sortlink=/);
    if (searchindex != -1) {
        var sortlinkid = "sortlink_" + url.substr(searchindex + 10, 1);
        forumng_focus(document.getElementById(sortlinkid));
    }
}

YAHOO.util.Event.onDOMReady(forumng_init);
forumng_urgent_init();