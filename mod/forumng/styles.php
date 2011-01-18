<?php
// CSS column layout
////////////////////
?>
#mod-forumng-view #left-column {
    width:12em;
    float:left;
}
#mod-forumng-view #right-column {
    width:12em;
    float:right;
}
#mod-forumng-view #middle-column.has-right-column {
    margin-right:13em;
}
#mod-forumng-view #middle-column.has-left-column {
    margin-left:13em;
}
#mod-forumng-view .block_adminblock select {
    max-width:12em;
}
#mod-forumng-view #footer {
    clear:left;
}
<?php
// Icon links
/////////////

// These have an icon combined in the link, so you can click the icon,
// but it isn't underlined. For accessibility reasons it's important this
// effect is not created using two separate links.
?>
a.forumng-iconlink:link,
a.forumng-iconlink:visited,
a.forumng-iconlink:active,
a.forumng-iconlink:hover {
    text-decoration: none;
    color: black;
}
.forumng-iconlink:hover .forumng-textbyicon {
    text-decoration: underline;
}

.forumng-iconlink img {
    position: relative;
    top: 4px;
}

.forumng-feedlinks a:link,
.forumng-feedlinks a:visited,
.forumng-feedlinks a:active {
    text-decoration: none;
    color: black;
}
.generaltable .forumng-feedlinks a:link,
.generaltable .forumng-feedlinks a:visited,
.generaltable .forumng-feedlinks a:active {
    color: black;
}

.forumng-feedlinks a:hover {
    text-decoration: underline;
}
.forumng-feedlinks a.forumng-iconlink:hover {
    text-decoration: none;
}

<?php
// Errors
/////////
?>

.forumng-errormessage {
    font-size:0.85em;
    color:#333;
    margin:1em 0;
}

<?php
// Index page styles
////////////////////
?>
.forumng-subscribecell form,
.forumng-subscribecell div {
    display:inline;
}
.forumng-subscribecell input,
.forumng-feedlinks {
    font-size:0.85em;
}
.forumng-subscribecell {
    white-space: nowrap;
}

#mod-forumng-index .generaltable {
    width: 100%;
    margin-top: 1em;
    margin-bottom: 1em;
}
.forumng-allsubscribe {
    text-align: center;
}
.forumng-allsubscribe form,
.forumng-allsubscribe div {
    display: inline;
}

<?php
// View page styles
///////////////////
?>

#mod-forumng-view .generaltable {
    width: 100%;
}
#mod-forumng-view .generaltable th {
    text-align: left;
}
.forumng-startedby img {
    margin-right: 8px;
}

.forumng-intro {
    margin: 1em 0;
}

form.forumng-paste-buttons {
    margin-left: 0.85em;
    padding: 0.7em 0.5em;
    background: #FFD991;
}
.ie7 form.forumng-paste-buttons {
    padding: 0.5em 0.5em;
}

#forumng-buttons {
    margin:0 0 1em;
}
#mod-forumng-view #forumng-buttons {
    margin-top:0.6em;
}

#mod-forumng-subscribers #forumng-buttons {
    margin-bottom: 1em;
}

.forumng-subscribe-options p,
.forumng-subscribe-options form,
.forumng-subscribe-options form div {
    display:inline;
}
.forumng-subscribe-options form {
    margin-left: 1em;
}
.forumng-subscribe-admin {
    font-size: 0.85em;
}

.forumng-subscribe-options {
    margin-top: 2em;
}
.forumng-subscribe-options h3 {
    margin: 0em;
    font-size: 1em;
}

.forumng-archivewarning {
    margin:1em 0;
    color: red;
}

.forumng-timeout td.cell,
.forumng-timeout td.cell a:link,
.forumng-timeout td.cell a:visited,
.forumng-timeout td.cell a:active,
.forumng-deleted td.cell,
.forumng-deleted td.cell a:link,
.forumng-deleted td.cell a:visited,
.forumng-deleted td.cell a:active {
    color:#888;
}
.forumng-deleted .forumng-subject {
    text-decoration:line-through;
}

#mod-forumng-view .groupselector,
#mod-forumng-subscribers .groupselector,
#mod-forumng-feature-readers-readers .groupselector,
#mod-forumng-feature-userposts .groupselector {
    float:none;
}

.forumng-subject img {
  float:left;
  margin-right:8px;
}

.forumng-divider {
    height:8px;
}

.forumng-unreadcount form,
.forumng-unreadcount form div {
    display:inline;
}

a.forumng-sortlink:link,
a.forumng-sortlink:visited {
    text-decoration:none;
}
a.forumng-sortlink:hover {
    text-decoration:underline;
}

.forumng-sortcurrent {
    padding-left:0.3em;
}

#mod-forumng-view .forumng-feedlinks,
#mod-forumng-discuss .forumng-feedlinks {
    margin-top: 0.5em;
}

.forumng-draft-inreplyto {
    font-size: 0.85em;
}

.forumng-drafts {
    margin: 1em 0 2em;
}
.forumng-flagged {
    margin: 2em 0 1em;
}
.forumng-drafts h3,
.forumng-flagged h3 {
    margin:0 0 0.5em;
    font-size: 1em;
}

div.forumng-flag {
    display:inline;
}

.forumng-shareinfo {
    margin: 2em 0 2em;
}

<?php
// Discussion page styles
/////////////////////////
?>

#forumng-arrowback {
    margin: 1em 0;
}

.forumng-replies {
    margin-left: 40px;
}

h2.accesshide {
    margin: 0 0 5px 40px;
    padding-bottom: 5px;
    padding-left:0.5em;
    font-size: 0.85em;
    border-bottom: 1px dotted #aaa;
    top: auto;
    left: -10000px;
}

h2.forumng-author {
    font-size: 1.0em;
    padding-right:0.5em;
    display:inline;
}

.forumng-replies .forumng-replies .forumng-replies .forumng-replies .forumng-replies {
    margin-left: 30px;
}

.forumng-replies .forumng-replies .forumng-replies .forumng-replies .forumng-replies
.forumng-replies .forumng-replies .forumng-replies .forumng-replies .forumng-replies {
    margin-left: 20px;
}

#forumng-main .forumng-stop-indent .forumng-replies{
    margin-left: 0px;
}

.forumng-nojs
.forumng-replies .forumng-replies .forumng-replies .forumng-replies .forumng-replies
.forumng-replies .forumng-replies .forumng-replies .forumng-replies .forumng-replies
.forumng-replies .forumng-replies .forumng-replies .forumng-replies .forumng-replies
.forumng-replies .forumng-replies .forumng-replies .forumng-replies .forumng-replies {
    margin-left: 0px;
}

.forumng-post {
    margin-bottom:1em;
    border:1px solid #aaa;
    padding:0.5em;
    max-width:800px;
}

.forumng-pic {
    float:left;
    width:35px;
}
.forumng-subject,
.forumng-info,
.forumng-summary,
.forumng-deleted-info {
    margin-left:35px;
    padding-left:0.5em;
}

.forumng-info {
    position: relative;
    padding-right: 16px;
}
.forumng-info .forumng-flag {
    position: absolute;
    right: -0.5em;
}

.forumng-deleted .forumng-pic,
.forumng-deselected .forumng-pic{
    opacity:0.5;
    filter: alpha(opacity=50);
}
.forumng-deleted .forumng-info,
.forumng-deleted .forumng-summary .forumng-text,
.forumng-deleted .forumng-message,
.forumng-deleted .forumng-subject,
.forumng-deselected .forumng-info,
.forumng-deselected .forumng-summary .forumng-text,
.forumng-deselected .forumng-message,
.forumng-deselected .forumng-subject {
    color: #888;
}
.forumng-deselected a:link,
.forumng-deselected a:active,
.forumng-deselected a:visited,
.forumng-deselected a:hover {
    color: #7f98b2;
}

.forumng-summary .forumng-text {
    display:inline;
}

.forumng-info {
    font-size:0.85em;
}
.forumng-subject {
    margin-top:0.5em;
}
h3.forumng-subject {
    font-size:1em;
    margin-top:0.5em;
    margin-bottom:0;
}
.forumng-summary h3 {
    font-size:1em;
    margin:0;
    display:inline;
}
.forumng-summary {
    margin-top:0.5em;
}
.forumng-postmain {
    margin-left:35px;
    padding-left:0.5em;
    padding-top:0.5em;
}
.forumng-postfooter {
    margin:0.5em 0 0;
    text-align:right;
}
ul.forumng-commands {
    display: inline;
    margin: 0;
    padding: 0;
    white-space: nowrap;
}
ul.forumng-commands li {
    display:inline;
    list-style-type:none;
    margin:0 0 0 2em;
    padding:0;
}
ul.forumng-commands li {
    font-size: 0.85em;
}
ul.forumng-commands li.forumng-replylink {
    font-size: 1.0em;
}
.forumng-endpost {
    clear:left;
}

.forumng-post.forumng-important {
    background-color: #F0E1B3;
}

.forumng-post.forumng-read {
    color:#222;
}
.forumng-post.forumng-unread {
    background: #FFD991;
}

.forumng-post.forumng-unread .forumng-info {
    font-weight: bold;
}

.forumng-lockmessage .forumng-post {
    background: #FFBBBB;
    margin-bottom: 2em;
}

.forumng-attachments {
    display: block;
    text-align:right;
    font-size: 0.85em;
    margin: 0 0 0.5em;
    padding: 0;
}
.forumng-attachments li {
    display:inline;
    list-style-type:none;
    margin:0 0 0 2em;
    white-space: nowrap;
}
.forumng-attachments a:link,
.forumng-attachments a:visited,
.forumng-attachments a:hover {
    text-decoration: none;
}
.forumng-attachments img {
    vertical-align: -4px;
}
.forumng-attachments a:hover span {
    text-decoration: underline;
}


#forumng-expandall {
    text-align:right;
    margin-bottom:0.5em;
}

#forumng-saveallratings {
    font-size:0.85em;
}

.forumng-deleted-discussion .forumng-post {
    color: #888;
}

.forumng-bad-browser {
    font-size: 0.85em;
    margin-top: 2em;
}

.forumng-bad-browser h3 {
    display: inline;
    margin: 0;
    font-size: 1em;
}

.forumng-bad-browser p {
    display: inline;
    margin: 0;
}

.forumng-selectmode {
    background: #eee;
    padding: 0.5em 0 0;
}
.forumng-selectmode .forumng-feedlinks,
.forumng-selectmode #forumng-arrowback {
    display:none;
}

.forumng-selectintro {
    padding: 0.5em 0 0;
    margin: 0 0 1em;
}
.forumng-selectoutro {
    background:white;
    padding-top: 1em;
}


.forumng-selectmode .forumng-post {
    background-color:white;
}
.forumng-selectmode .forumng-post.forumng-deselected {
    background-color:transparent;
}

<?php
// Edit/reply form within discussion
////////////////////////////////////
?>

#mod-forumng-discuss .mform {
    width: 100%;
    padding: 0;
    display: none;
    margin-bottom: 1em;
}
.ie#mod-forumng-discuss .mform {
    margin-top: 0.5em;
}

#mod-forumng-discuss .mform fieldset {
    padding-top: 5px;
    margin-bottom: 0;
}
#mod-forumng-discuss .mform fieldset.hidden,
#mod-forumng-discuss .mform fieldset.hidden fieldset.fgroup {
    margin-top: 0;
    padding-top: 0;
}

#mod-forumng-discuss .mform fieldset .advancedbutton,
#mod-forumng-discuss .mform fieldset legend {
    display: none;
}

#mod-forumng-discuss fieldset#id_importance {
    margin-top: 0;
    padding-top: 0;
}
#mod-forumng-discuss fieldset#id_attachments {
    padding-bottom: 0;
}

#mod-forumng-discuss .mform .fhtmleditor {
    margin: 0;
    padding: 5px 14px 0 10px;
    width: auto;
}
#mod-forumng-discuss .mform .fhtmleditor textarea {
    width: 100%;
    margin: 0;
}
#mod-forumng-discuss .mform .fdescription.required {
    display: none;
}

a:link.forumng-disabled,
a:visited.forumng-disabled,
a:hover.forumng-disabled,
a:active.forumng-disabled {
    color: #888;
    text-decoration: none;
}

.forumng-timeoutover {
    color: red;
}

<?php
// Edit/reply form elsewhere
////////////////////////////
?>

.forumng-form-attachments {
    margin: 0;
    display: block;
    padding: 0;
}
.forumng-form-attachments li {
    display: block;
    list-style-type: none;
    margin: 0;
    padding: 0;
}
.forumng-deletefilecheck {
    font-size: 0.85em;
}

.forumng-draftexists {
    width: 80%;
    margin: 1em auto;
    font-weight: bold;
}


<?php
// Ratings within discussion
////////////////////////////
?>

.forumng-ratings {
    display:inline;
    font-size: 0.85em;
}
.forumng-ratings select,
.forumng-ratings input {
    font-size: 1.0em;
}

.forumng-ratings .forumng-rating {
    display:inline;
    margin-right: 0.5em;
}

.forumng-ratings .forumng-editrating {
    display:inline;
}

.forumng-ratings img {
    vertical-align:-3px;
}
form.markread input {
    font-size: 0.85em;
}
<?php
// Confirm dialog within discussion
///////////////////////////////////

// Note: Dialog width including padding etc. must be 350 pixels
?>

.forumng-confirmdialog {
    width: 328px;
    background: white;
    border: 1px solid #aaa;
    padding: 10px;
}

.forumng-confirmdialog .forumng-message {
    margin-bottom: 10px;
}

.forumng-confirmdialog input {
    margin-right: 10px;
}

.forumng-confirmdialog h4 {
    margin: 0 0 0.5em;
    font-size: 100%;
}

.forumng-fadepanel {
    background: black;
}

.forumng-highlightbox {
    border: 2px solid yellow;
}

<?php
// Features section (discussion)
////////////////////////////////
?>

#forumng-features {
    font-size: 0.85em;
    margin-top: 2em;
    line-height: 3;
}
#mod-forumng-view #forumng-features {
    margin-top: 1em;
}

#forumng-features form {
    margin-right: 1em;
}
#forumng-features form,
#forumng-features div,
#forumng-buttons form,
#forumng-buttons div {
    display:inline;
}
#forumng-features .forumng-highlight {
    background: #FFD991;
    padding: 0.7em 0.5em;
}

<?php
// Split posts form
///////////////////
?>

.forumng-exampleposts {
    margin-top:2em;
}

<?php
// History view
///////////////
?>

#mod-forumng-history h2 {
    font-size: 1.0em;
    margin:1em 0;
}

<?php
// Readers list
///////////////
?>

#forumng-groupselector {
    margin-bottom: 1em;
    font-size: 0.85em
}
#forumng-groupselector input,
#forumng-groupselector select {
    font-size: 1.0em;
}
#mod-forumng-feature-readers-readers .generaltable {
    margin-top: 1em;
    margin-bottom: 1em;
}

<?php
// Subscribers list
///////////////////
?>

#mod-forumng-subscribers .generaltable {
    margin-top: 1em;
    margin-bottom: 1em;
}

#mod-forumng-subscribers #forumng-buttons {
    margin-bottom: 1em;
}

<?php
// Add attachment
/////////////////
?>

#mod-forumng-addattachment,
#mod-forumng-addattachment #page {
    min-width: 370px;
}

.ie#mod-forumng-addattachment #page {
    width: auto;
}

#mod-forumng-addattachment h1 {
    font-size: 0.85em;
    margin: 0;
}

#mod-forumng-addattachment form {
  margin:0;
}

#mod-forumng-addattachment #content {
    margin: 4px;
}

.forumng-addattachment-file {
    float:left;
}

.forumng-addattachment-submit {
    float:left;
    margin-left: 8px;
}

.forumng-addattachment-max {
    clear:both;
    font-size:0.85em;
}

#mod-forumng-addattachment .notifyproblem {
    margin: 0 0 0.5em;
    padding: 0;
    text-align: left;
}

#mod-forumng-addattachment .notifyproblem br {
    display:none;
}

<?php
// Forward email
////////////////
?>

#mod-forumng-feature-forward-forward .generalbox {
    width:50%;
    margin:1em auto;
}
#mod-forumng-feature-forward-forward .generalbox h2 {
    margin:0;
    font-size:1em;
}
.forumng-showemail {
    margin: 2em 0;
}

<?php
// Printable view
/////////////////
?>
#mod-forumng-feature-print-print .forumng-showprintable {
    margin: 2em 0;
}
#mod-forumng-feature-print-print .forumng-printable-header {
    border-bottom:1px dotted #aaa;
    padding-bottom:4px;
    margin-bottom:2em;
}
#mod-forumng-feature-print-print .forumng-printable-backlink {
    float:left;
}
#mod-forumng-feature-print-print .forumng-printable-date {
    float:right;
}

<?php
// Settings form
////////////////
?>

#mod-forumng-mod .forumng-convertoffer {
    margin-top: 2em;
}

.forumng-show-dates {
    margin-bottom: 0.5em;
    padding: 5px;
    border: 1px dotted #555;
}

<?php
// Search page
//////////////
?>

#mod-forumng-search h2 {
clear: left;
margin-top: 0;
padding-top: 1em;
}

<?php
// discussion_list_feature
/////////////////////////
?>
.forumng-userpostsheading {
    margin-bottom: 0.5em;
}
.forumng-userpoststable {
    margin-bottom: 0.5em;
}
.forumng-userposts {
    margin-bottom: 0.5em;
}

#mod-forumng-feature-userposts-user .forumng-post {
    margin: 0.5em 0 1em;
}

.ie7 .forumng-manualmark {
    position: relative;
    top: -0.4em;
}
.forumng-manualmark .iconhelp {
    vertical-align: -3px;
}