<?php
$string['modulename'] = 'OU wiki';
$string['modulenameplural'] = 'OU wikis';

$string['subwikis'] = 'Sub-wikis';
$string['subwikis_single'] = 'Single wiki for course';
$string['subwikis_groups'] = 'One wiki per group';
$string['subwikis_individual'] = 'Separate wiki for every user';

$string['timeout']='Time allowed for edit';
$string['timeout_none']='No timeout';

$string['editbegin']='Allow editing from';
$string['editend']='Prevent editing from';

$string['wouldyouliketocreate']='Would you like to create it?';
$string['pagedoesnotexist']='This page does not yet exist in the wiki.';
$string['startpagedoesnotexist']='This wiki\'s start page has not yet been created.';
$string['createpage']='Create page';

$string['recentchanges']='Latest edits';
$string['seedetails']='full history';
$string['startpage']='Start page';

$string['tab_view']='View';
$string['tab_edit']='Edit';
$string['tab_annotate']='Annotate';
$string['tab_discuss']='Discuss';
$string['tab_history']='History';

$string['preview']='Preview';
$string['previewwarning']='The following preview of your changes has not yet been saved.
<strong>If you do not save changes, your work will be lost.</strong> Save using the button
at the end of the page.';

$string['wikifor']='Viewing wiki for: ';
$string['changebutton']='Change';

$string['advice_edit']='
<p>Edit the page below.</p>
<ul>
<li>Make a link to another page by typing the page name in double square brackets: [[page name]]. The link will become active once you save changes.</li>
<li>To create a new page, first make a link to it in the same way. $a</li>
</ul>
';

$string['advice_annotate']='
<p>Annotate the page below.</p>
<ul>
<li>To annotate click one of the annotation markers and enter the required text.</li>
<li>New and existing annotations can be deleted by removing all the text in the form below.</li>
</ul>
';

$string['pagelockedtitle']='This page is being edited by somebody else.';
$string['pagelockeddetails']='{$a->name} started editing this page at {$a->lockedat}, and was
still editing it as of {$a->seenat}. You cannot edit it until they finish. ';
$string['pagelockeddetailsnojs']='{$a->name} started editing this page at {$a->lockedat}. They
have until {$a->nojs} to edit. You cannot edit it until they finish.';
$string['pagelockedtimeout']='Their editing slot finishes at $a.';
$string['pagelockedoverride']='You have special access to cancel their edit and unlock the page.
If you do this, whatever they have entered will be lost! Please think carefully before clicking
the Override button.';
$string['tryagain']='Try again';
$string['overridelock']='Override lock';

$string['savefailtitle']='Page cannot be saved';
$string['savefaillocked']='While you were editing this page, somebody else obtained the page lock.
(This could happen in various situations such as if you are using an unusual browser or have
Javascript turned off.) Unfortunately, your changes cannot be saved at this time.';
$string['savefaildesynch']='While you were editing this page, somebody else managed to make a change.
(This could happen in various situations such as if you are using an unusual browser or have
Javascript turned off.) Unfortunately, your changes cannot be saved because that would overwrite the
other person\'s changes.';
$string['savefailcontent']='Your version of the page is shown below so that you can copy and paste
the relevant parts into another program. If you put your changes back on the wiki later, be careful
you don\'t overwrite somebody else\'s work.';
$string['returntoview']='View current page';

$string['lockcancelled'] = 'Your editing lock has been overridden and somebody else is now editing this page. If you wish to keep your changes, please select and copy them before clicking Cancel; then try to edit again.';
$string['nojsbrowser'] = 'Our apologies, but you are using a browser we do not fully support.';
$string['nojsdisabled'] = 'You have disabled JavaScript in your browser settings.';
$string['nojswarning'] = 'As a result, we can only hold this page for you for $a->minutes minutes. Please ensure that you save your changes by $a->deadline (it is currently $a->now). Otherwise, somebody else might edit the page and your changes could be lost';

$string['countdowntext'] = 'This wiki allows only $a minutes for editing. Make your changes and click Save or Cancel before the remaining time (to right) reaches zero.';
$string['countdownurgent'] = 'Please finish or cancel your edit now. If you do not save before time runs out, your changes will be saved automatically.';


$string['advice_history']='<p>The table below displays all changes that have been made to <a href=\"$a\">the current page</a>.</p>
<p>You can view old versions or see what changed in a particular version. If you want to compare any two versions, select the relevant checkboxes and click \'Compare selected\'.</p>';

$string['changedby']='Changed by';
$string['compare']='Compare';
$string['compareselected']='Compare selected';
$string['changes']='changes';
$string['actionheading']='Actions';

$string['mustspecify2']='You must specify exactly two versions to compare.';

$string['oldversion']='Old version';
$string['previousversion']='Previous: $a';
$string['nextversion']='Next: $a';
$string['currentversion']='Current version';
$string['savedby']='saved by $a';
$string['system']='the system';
$string['advice_viewold']='You are viewing an old version of this page.';

$string['index']='Wiki index';
$string['tab_index_alpha']='Alphabetical';
$string['tab_index_tree']='Structure';

$string['lastchange']='Last change: {$a->date} / {$a->userlink}';
$string['orphanpages']='Unlinked pages';

$string['missingpages']='Missing pages';
$string['advice_missingpages']='These pages are linked to, but have not yet been created.';
$string['advice_missingpage']='This page is linked to, but has not yet been created.';
$string['frompage']='from $a';
$string['frompages']='from $a...';

$string['changesnav']='Changes';
$string['advice_diff']='The older version is shown on the
left<span class=\'accesshide\'> under the heading Older version</span>, where
deleted text is highlighted. Added text is indicated in the newer version on
the right<span class=\'accesshide\'> under the heading Newer
version</span>.<span class=\'accesshide\'> Each change is indicated by a pair
of images before and after the added or deleted text, with appropriate
alternative text.</span>';
$string['diff_nochanges']='This edit did not make changes to the actual text, so no differences are
highlighted below. There may be changes to appearance.';
$string['diff_someannotations']='This edit did not make changes to the actual text, so no differences are
highlighted below, however annotations have been changed. There may also be changes to appearance.';
$string['returntohistory']='(<a href=\'$a\'>Return to history view</a>.)';
$string['addedbegins']='[Added text follows]';
$string['addedends']='[End of added text]';
$string['deletedbegins']='[Deleted text follows]';
$string['deletedends']='[End of deleted text]';


$string['ouwiki:edit']='Edit wiki pages';
$string['ouwiki:view']='View wikis';
$string['ouwiki:overridelock']='Override locked pages';
$string['ouwiki:viewgroupindividuals']='Per-user subwikis: view same group';
$string['ouwiki:viewallindividuals']='Per-user subwikis: view all';
$string['ouwiki:viewcontributions']='View list of contributions organised by user';
$string['commenton']='Comments on: ';
$string['commentcount']='{$a->count} comment{$a->plural}';
$string['commentinfo']='{$a->commentlink}; latest {$a->date}';
$string['commentbyyou']='by you';
$string['commentonsection']='Comment on section';
$string['commentonpage']='Comment on page';
$string['commentsonsection']='Comments on: $a';
$string['commentsonpage']='Comments';
$string['commentdeletedinfo']='Some deleted comments are shown in the list below. These are visible only to users with permission to delete comments. Ordinary users do not see them at all.';
$string['commentpostheader']='Add your own comment';
$string['commentsubject']='Subject (optional)';
$string['commenttext']='Comment';
$string['commentpost']='Post comment';
$string['commentdelete']='Delete comment';
$string['commentundelete']='Undelete comment';
$string['commentoriginalsection']='Originally about deleted section: $a';
$string['commentblank']='You must enter some text in the Comment box.';
$string['commentsolder']='{$a->count} most recent shown ({$a->link})';
$string['commentsviewall']='view all';
$string['commentsviewseparate']='View comments on separate page';
$string['commentdeleteconfirm']='Are you sure you want to delete your comment? Once a comment is deleted, you cannot get it back.';
$string['access_commenter']='Commenter';

$string['wikirecentchanges']='Wiki changes';
$string['wikirecentchanges_from']='Wiki changes (page $a)';
$string['advice_wikirecentchanges_changes']='<p>The table below lists all changes to any page on this wiki, beginning with the latest changes. The most recent version of each page is highlighted.</p>
<p>Using the links you can view a page as it looked after a particular change, or see what changed at that moment.</p>';
$string['advice_wikirecentchanges_changes_nohighlight']='<p>The table below lists all changes to any page on this wiki, beginning with the latest changes.</p>
<p>Using the links you can view a page as it looked after a particular change, or see what changed at that moment.</p>';
$string['advice_wikirecentchanges_pages']='<p>This table shows when each page was added to the wiki, beginning with the most recently-created page.</p>';
$string['wikifullchanges']='View full change list';
$string['tab_index_changes']='All changes';
$string['tab_index_pages']='New pages';
$string['page']='Page';
$string['next']='Older changes';
$string['previous']='Newer changes';

$string['newpage']='first version';
$string['current']='current';
$string['currentversionof']='Current version of ';

$string['linkedfrom']='Pages that link to this one';
$string['linkedfromsingle']='Page that links to this one';

$string['editpage']='Edit page';
$string['editsection']='Edit section';

$string['editingpage']='Editing page';
$string['editingsection']='Editing section: $a';

$string['annotatingpage']='Annotating page';

$string['historyfor']= 'History for';
$string['historycompareaccessibility']='Select {$a->lastdate} {$a->createdtime}';

$string['timelocked_before']='This wiki is currently locked. It can be edited from $a.';
$string['timelocked_after']='This wiki is currently locked and can no longer be edited.';

$string['returntopage']='Return to wiki page';

$string['savetemplate']='Save wiki as template';
$string['template']='Template';

$string['contributionsbyuser']='Contributions by user';
$string['changebutton']='Change';
$string['contributionsgrouplabel']='Group';
$string['nousersingroup']='The selected group contains no users.';
$string['nochanges']='Users who made no contribution';
$string['contributions']='<strong>{$a->pages}</strong> new page{$a->pagesplural}, <strong>{$a->changes}</strong> other change{$a->changesplural}.';

$string['entirewiki']='Entire wiki';
$string['onepageview']='You can view all pages of this wiki at once for convenient printing or permanent reference.';
$string['format_html']='View online';
$string['format_rtf']='Download in word processor format';
$string['format_template']='Download as wiki template file';
$string['savedat']='Saved at $a';

$string['feedtitle']='{$a->course} wiki: {$a->name} - {$a->subtitle}';
$string['feeddescriptionchanges']='Lists all changes made to the wiki. Subscribe to this feed if you want to be updated whenever the wiki changes.';
$string['feeddescriptionpages']='Lists all new pages on the wiki. Subscribe to this feed if you want to be updated whenever someone creates a new page.';
$string['feeddescriptionhistory']='Lists all changes to this individual wiki page. Subscribe to this feed if you want to be updated whenever someone edits this page.';
$string['feedchange']='Changed by {$a->name} (<a href=\'{$a->url}\'>view change</a>)';
$string['feednewpage']='Created by {$a->name}';
$string['feeditemdescriptiondate']='{$a->main} on {$a->date}.';
$string['feeditemdescriptionnodate']='{$a->main}.';
$string['feedsubscribe']='You can subscribe to a feed containing this information: <a href=\'{$a->atom}\'>Atom</a> or <a href=\'{$a->rss}\'>RSS</a>.';
$string['feedalt']='Subscribe to Atom feed';


$string['olderversion']='Older version';
$string['newerversion']='Newer version';


// reports pages
$string['reports']='Wiki reports';

$string['report_summaryreports']='Summary report';
$string['report_groupreports']='Group report';
$string['report_userreports']='User report';
$string['report_grouplabel']='Choose group for report';

$string['report_grouptabletitle']='Group report';
$string['report_group']='Group';
$string['report_coursenum']='Course';
$string['report_total']='Total';
$string['report_active']='Active';
$string['report_inactive']='Inactive';
$string['report_percentageparticipation']='Participation';
$string['report_totalpages']='Total pages';
$string['report_editedpages']='Edited pages';
$string['report_uneditedpages']='Unedited pages';
$string['report_edits']='Edits';
$string['report_comments']='Comments';

$string['report_grouptabletitle']='Group activity';
$string['report_user']='User activity';
$string['report_username']='Name';
$string['report_timeonwiki']='Days';
$string['report_createdpages']='Created pages';
$string['report_additions']='Additions';
$string['report_deletes']='Deletes';
$string['report_otheredits']='Other edits';
$string['report_contributions']='Total contributions';
$string['report_userstabletitle']='Activity by user';
$string['report_compareversions']='Compare versions';
$string['report_compare']='Changes';

$string['report_editscommentsgraphtitle']='$a->ouw_bargraph1key Edits and $a->ouw_bargraph2key comments';
$string['report_editedpagesgraphtitle']='$a->ouw_bargraph1key Edited pages by role';
$string['report_timelinetitle']='Timeline of edit activity by page';
$string['report_timelinepage']='Page';
$string['report_datetime']='Date and time';
$string['report_type']='Type';
$string['report_new']='New';
$string['report_existing']='Existing';
$string['report_activitybydate']='Activity by date';
$string['report_date']='Date';

$string['report_pagetabletitle']='Page details';
$string['report_pagename']='Page';
$string['report_contributorcount']='Contributors';
$string['report_intensity']='Intensity';
$string['report_startday']='First edit';
$string['report_lastday']='Last edit';
$string['report_wordcount']='Words';
$string['report_linkcount']='Links';
$string['report_user_is_inactive']='$a has not done anything in this wiki.';
$string['report_emptywiki']='This wiki is completely empty (no pages), so there is no information to report.';

$string['report_viewallgroups']='View detail for users across all groups';
$string['report_timelinebar']='$a->date: $a->edits edits';

$string['report_intensityexplanation']='Intensity counts the number of edits that are &ldquo;in response to&rdquo; another user&rsquo;s edit, divided by the number of users that edited the page. An edit counts as &ldquo;in response to&rdquo; if the previous edit was by somebody else, so if you make two edits in a row that only counts as one edit for this calculation.';

$string['reportroles']='Roles included in reports';
$string['configreportroles']='Only users with these roles are counted and displayed on the reports screens.';
$string['configreportroles_text']='Only users with these roles are counted and displayed on the reporting screens. This field must be a comma-separated list of role ID numbers. (To find the numbers, use the links from the \'Define roles\' screen and look in the URL.)';

$string['completionpagesgroup']='Require new pages';
$string['completionpages']='User must create new pages:';
$string['completionpageshelp']='requiring new pages to complete';
$string['completioneditsgroup']='Require edits';
$string['completionedits']='User must make edits:';
$string['completioneditshelp']='requiring edits to complete';

$string['ouwiki:comment']='Comment on wiki pages';
$string['ouwiki:deletecomments']='Delete wiki page comments';
$string['ouwiki:deletepage']='Delete wiki page versions';

$string['reverterrorversion'] = 'Cannot revert to nonexistent page version';
$string['reverterrorcapability'] = 'You do not have permission to revert to an earlier version';
$string['revert'] = 'Revert';
$string['revertversion'] = 'Revert';
$string['revertversionconfirm']='<p>This page will be returned to the state it was in as of $a, discarding all changes made since then. However, the discarded changes
will still be available in the page history.</p><p>Are you sure you want to revert to this version of the page?</p>';

$string['deleteversionerrorversion'] = 'Cannot delete nonexistent page version';
$string['viewdeletedversionerrorcapability'] = 'Error viewing page version';
$string['deleteversionerror'] = 'Error deleting page version';
$string['pagedeletedinfo']='Some deleted versions are shown in the list below. These are visible only to users with permission to delete versions. Ordinary users do not see them at all.';
$string['undelete'] = 'Undelete';
$string['advice_viewdeleted']='You are viewing a deleted version of this page.';

$string['csvdownload']='Download in spreadsheet format (UTF-8 .csv)';
$string['excelcsvdownload']='Download in Excel-compatible format (.csv)';

$string['closecomments']='Close comments';
$string['closecommentform']='Close comment form';

$string['create']='Create';
$string['createnewpage']='Create new page';
$string['typeinpagename']='Type page name here';
$string['add']='Add';
$string['typeinsectionname']='Type section title here';
$string['addnewsection']='Add new section to this page';
$string['createdbyon'] = 'created by {$a->name} on {$a->date}';

$string['numedits'] = '$a edit(s)';
$string['overviewnumentrysince1'] = 'new wiki entry since last login.';
$string['overviewnumentrysince'] = 'new wiki entries since last login.';

$string['pagenametoolong'] = 'The page name is too long. Use a shorter page name.';
$string['pagenameisstartpage'] = 'The page name is the same as the start page. Use a different page name.';

$string['nocommentsystem'] = 'No comment system';
$string['annotationsystem'] = 'Annotation system';
$string['persectionsystem'] = 'Per-section comment system';
$string['bothcommentsystems'] = 'Both systems';
$string['commenting'] = 'Comment system';
$string['commentsystemdesc'] = 'Choose the default comment system to be used thoughout this site. Each wiki is able
 to override this setting.';

$string['ouwiki:lock'] = 'Allowed to lock and unlock pages';
$string['ouwiki:annotate'] = 'Allowed to annotate';
$string['orphanedannotations'] = 'Lost annotations';
$string['addannotation'] = 'Add annotation';
$string['annotations'] = 'Annotations';
$string['deleteorphanedannotations'] = 'Delete lost annotations';
$string['lockediting'] = 'Lock wiki - no editing';
$string['lockpage'] = 'Lock page';
$string['unlockpage'] = 'Unlock page';
$string['annotate'] = 'Annotate';
$string['annotation'] = 'Annotation';
$string['annotationmarker'] = 'Annotation marker';
$string['cannotlockpage'] = 'The page could not be locked, your changes have not been saved.';
$string['thispageislocked'] = 'This wiki page is locked and cannot be edited';


//computing guide
$string['computingguide']='Guide to OU wiki';
$string['computingguideurl']='Computing guide URL';
$string['computingguideurlexplained']='Enter the URL for the OU wiki omputing guide';

$string['search'] = 'Search wiki';

$string['sizewarning'] = 'This wiki page is very large and may operate slowly. 
If possible, please split the content into logical chunks and 
place it on separate linked pages.';

$string['displayversion'] = 'OU wiki version: <strong>$a</strong>';

// OU only
$string['externaldashboardadd'] = 'Add wiki to dashboard';
$string['externaldashboardremove'] = 'Remove wiki from dashboard';

$string['showallannotations'] = 'Show all annotations';
$string['hideallannotations'] = 'Hide all annotations';

?>
