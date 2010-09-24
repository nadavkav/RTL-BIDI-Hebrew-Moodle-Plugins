<?php include($CFG->dirroot.'/lib/yui/container/assets/skins/sam/container.css') ?>

#mod-ouwiki-edit .ouw_preview {
    border:1px solid #ddd;
    padding:10px;
    margin:1em;
}
#mod-ouwiki-view .ouw_recentchanges {
    font-size:0.85em;
    color:#636363;
    margin-top:0.5em;
}
#mod-ouwiki-view .ouw_commentsinfo {
    color:#636363;
}
#mod-ouwiki-view .ouw_recentchanges h2 {
    margin:0 1em 0 0;
    display:inline;
    font-size:1.0em;
    font-weight:normal;
}
#mod-ouwiki-view .ouw_recentchanges ul,
#mod-ouwiki-view .ouw_recentchanges li {
    display:inline;
    margin:0;
    padding:0;
}
#mod-ouwiki-view .ouw_recentchanges li {
    margin-right:0.5em;
}
#mod-ouwiki-view .tabrow0 {
    padding-bottom:0.75em;
}
#mod-ouwiki-view ul.ouw_comments .ouw_recentnot {
    color:#444;
} 

#mod-ouwiki-edit #ouw_countdown {
    float:right;
    margin-left:2em;
    border:1px solid #ddd;
    padding:4px;
}
#mod-ouwiki-edit form#ouw_edit {
    margin-top:1em;
}
#mod-ouwiki-edit #ouw_countdownurgent {
    font-weight:bold;
    color:red;
}

#mod-ouwiki-history table,
#mod-ouwiki-wikihistory table, 
#mod-ouwiki-contributions table {
    width:100%;
}

#mod-ouwiki-history .ouw_history th,
#mod-ouwiki-wikihistory th,
#mod-ouwiki-contributions th {
    text-align:left;
    padding: 5px 12px 5px 4px;
    background: #a7d2ff;
    border-left: 1px solid #a7d2ff;
    border-right: 1px solid #a7d2ff;
    border-top:1px solid #888;
    border-bottom:1px dotted #888;    
}
#mod-ouwiki-history .ouw_history td,
#mod-ouwiki-wikihistory td,
#mod-ouwiki-contributions td {
    padding: 3px 12px 3px 4px;
    border-bottom:1px dotted #888;    
}
#mod-ouwiki-wikihistory td.ouw_rightcol,
#mod-ouwiki-contributions td.ouw_rightcol,
#mod-ouwiki-history .ouw_history td.ouw_rightcol {
    border-right:1px dotted #888;
}
#mod-ouwiki-wikihistory td.ouw_leftcol,
#mod-ouwiki-contributions td.ouw_leftcol,
#mod-ouwiki-history .ouw_history td.ouw_leftcol {
    border-left:1px dotted #888;
}
#mod-ouwiki-history .ouw_history td.check,
#mod-ouwiki-history .ouw_history td.comparebutton {
    padding-right:4px;
}
#mod-ouwiki-history .ouw_history td.comparebutton {
    padding-top:6px;
    border-bottom:none;
}
#mod-ouwiki-history .ouw_history tr.current,
#mod-ouwiki-wikihistory tr.current {
    background:#dcedff;
}
#mod-ouwiki-history .ouw_history td.comparebutton {
    text-align:right;
    padding-bottom:6px;
}
#mod-ouwiki-history .ouw_history table,
#mod-ouwiki-wikihistory table{
    margin-top:1em;
}
#mod-ouwiki-contributions .ouw_contributionsgroups {
    background:#f0f0f0;
    padding:8px;
}


#ouwiki_belowtabs {
    max-width:55em;
    margin-left:auto;
    margin-right:auto;
}

#ouwiki_belowtabs_reports {
}

#mod-ouwiki-viewold .ouw_versionbox,
#mod-ouwiki-diff .ouw_versionbox {
    border-top:1px solid #888;
    background:#dcedff;
    padding:8px 8px 10px 8px;
}
#mod-ouwiki-diff .ouw_versionbox .ouw_date {
    font-weight:bold;
}
#mod-ouwiki-viewold .ouw_oldversion h1 {
    font-size:1em;
    margin:0;
}
#mod-ouwiki-viewold .ouw_oldversion .ouw_person {
    font-weight:normal;    
}

#mod-ouwiki-viewold .ouw_prev {
    float:left;
}
#mod-ouwiki-viewold .ouw_next {
    float:right;
}

#ouwiki_indexlinks {
    margin:0 0 8px;
    padding:0;
    text-align:right;    
}
#ouwiki_indexlinks ul {
    margin:0;
    padding:0;
    display:inline;
}
#ouwiki_indexlinks form,
#ouwiki_indexlinks form div {
    display:inline;
}
#ouwiki_indexlinks form input {
    font-size:0.85em;
}
#ouwiki_indexlinks #ouw_searchbox {
    width:10em;
}

#ouwiki_indexlinks li {
    margin:0 0.5em 0 0;
    padding:0;
    list-style-type:none;
    display:inline;
}
#ouwiki_noindexlink {
    height:1em;
}
#ouwiki_indexlinks a {
    border:1px solid #aaa;
    padding:2px;
    font-size:84%;
}
#ouwiki_indexlinks span {
    border:1px solid black;
    padding:2px;
    font-size:84%;
}

#mod-ouwiki-wikiindex ul.ouw_index,
#mod-ouwiki-wikiindex ul.ouw_index li,
#mod-ouwiki-wikiindex ul.ouw_indextree,
#mod-ouwiki-wikiindex ul.ouw_indextree li {
    list-style-type:none;
    margin:0;
    padding:0;
}
#mod-ouwiki-wikiindex ul.ouw_index,
#mod-ouwiki-wikiindex ul.ouw_indextree {
    margin-bottom:2em;
}
#mod-ouwiki-wikiindex .ouw_title {
    display:block;
    font-weight:bold;
    background:#dcedff;
    padding:4px 8px 8px;
    border-top:1px solid #888;
    margin-top:-1px;
}
#mod-ouwiki-wikiindex a.ouw_title:link,
#mod-ouwiki-wikiindex a.ouw_title:visited{
    color:black;
}
#mod-ouwiki-wikiindex .ouw_indexinfo {
    border:1px dotted #888;
    border-top:none;
    padding:4px 8px 6px;
    font-size:0.85em;
    color:#888;
}
#mod-ouwiki-wikiindex .ouw_index .ouw_index_startpage .ouw_indexinfo {
    border-bottom:1px dotted #888;
}
.ie#mod-ouwiki-wikiindex .ouw_index_startpage .ouw_title {
    border-top:2px solid #888;
}



#mod-ouwiki-wikiindex .ouw_missingfrom {
    font-size:0.85em;
}
#mod-ouwiki-wikiindex h2 {
    font-size:1.0em;
    margin-bottom:0.5em;
    margin-top:2em;
}
#mod-ouwiki-wikiindex h2.ouw_orphans {
    margin-bottom:0.75em;
}

#mod-ouwiki-wikiindex ul.ouw_indextree ul {
    margin-left:3em;
    padding-left:0;
    margin-top:0;
    margin-bottom:0;
}
#mod-ouwiki-wikiindex ul.ouw_indextree li ul {
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul {
    margin-left:2.75em;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul ul {
    margin-left:2.5em;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul ul ul {
    margin-left:2.25em;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul ul ul ul {
    margin-left:2em;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul ul ul ul ul {
    margin-left:1.75em;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul ul ul ul ul ul {
    margin-left:1.5em;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul ul ul ul ul ul ul {
    margin-left:1.25em;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul ul ul ul ul ul ul ul {
    margin-left:1em;
}

#mod-ouwiki-diff .ouw_left,.ouw_right {
    float:left;
    width:50%;
}
#mod-ouwiki-diff .ouw_diff {
    background:#f0f0f0;
    color:#636363;
    border-left:1px dotted #888;
    border-right:1px dotted #888;
    border-bottom:1px dotted #888;
    padding:8px;
}
#mod-ouwiki-diff .ouw_left .ouw_diff, 
#mod-ouwiki-diff .ouw_left .ouw_versionbox {
    margin-right:1em;
}
#mod-ouwiki-diff .ouw_right .ouw_diff, 
#mod-ouwiki-diff .ouw_right .ouw_versionbox {
    margin-left:1em;
}
#mod-ouwiki-diff .ouw_deleted {
    background:white;
    color:red;
    text-decoration:line-through;    
}
#mod-ouwiki-diff .ouw_added {
    background:white;
    color:green;
}

#mod-ouwiki-diff .ouw_advice {
    margin-bottom:1em;
}

.ouw_subwiki {
    font-size:0.85em;
}
.ouw_subwiki form,
.ouw_subwiki div {
    display:inline;
}
#mod-ouwiki-view .ouw_summary {
    margin-top:1em;
}

#mod-ouwiki-view .ouw_byheading {
    display:inline;
    font-weight:normal;
    font-style:normal;
    font-size:0.85em;
    margin-left:1em;
}

#mod-ouwiki-view .ouw_editsection,
#mod-ouwiki-view .ouw_annotate,
#mod-ouwiki-view .ouw_makecomment {
    margin-right:1em;
}

.ouwiki_content h1,
.ouwiki_content h2 {
    font-size:1.5em;
    display:inline;
}
.ouwiki_content h3,
.ouwiki_content h4,
.ouwiki_content h5 {
    font-size:1em;
    display:inline;
}
.ouwiki_content h6 {
    font-size:0.85em;
    display:inline;
}

.ouwiki_content .ouw_heading1,
.ouwiki_content .ouw_heading2 {
    margin:0.6667em 0 0.3333em 0; 
}
.ouwiki_content .ouw_heading3,
.ouwiki_content .ouw_heading4,
.ouwiki_content .ouw_heading5 {
    margin:1em 0 0.5em 0;
}
.ouwiki_content .ouw_heading6 {
    margin:1.25em 0 0.625em 0; 
}

.ouwiki_content h1.ouw_fixedheading,
.ouwiki_content h2.ouw_fixedheading,
.ouwiki_content h3.ouw_fixedheading,
.ouwiki_content h4.ouw_fixedheading,
.ouwiki_content h5.ouw_fixedheading,
.ouwiki_content h6.ouw_fixedheading {
    font-size:1em;
    margin:1em 0 0.5em 0;
}

.ouwiki_content h1.ouw_fixedheading .ouw_headingtext,
.ouwiki_content h2.ouw_fixedheading .ouw_headingtext {
    font-size:1.5em;
}
.ouwiki_content h1 {
    padding-bottom:2px;
    border-bottom:1px dotted #888;
}
.ouwiki_content h6.ouw_fixedheading .ouw_headingtext {
    font-size:0.85em;
}
.ouwiki_content h3 {
    border-bottom:1px dotted #888;
    padding-bottom:2px;
}
.ouwiki_content h5,
.ouwiki_content h6 {
    font-weight:normal;
    font-style:italic;
}

.ouw_addcomment label {
    float:left;
    width:12em;
    padding-top:2px;
}
.ouw_addcomment .ouw_ac_field {
    margin-bottom:0.5em;
}
.ouw_addcomment .ouw_ac_input {
    width:40em;
}
.ouw_addcomment table {
    border:0;
    padding:0;
    margin:0;
    font-size:1em;
    width:40em;
}
.ouw_addcomment tr,.ouw_addcomment td {
    border:0;
    margin:0;
    padding:0;
    
}
.ouw_addcomment textarea {
    font-size:1em;
    font-family:inherit;
    width:40em;
}
.ouw_addcomment .ouw_ac_submit {
    margin-left:12em;
}

ul.ouw_comments,    
ul.ouw_comments li.ouw_comment {
    list-style-type:none;
    display:block;
    margin:0;
    padding:0;
}
#mod-ouwiki-view .ouw_hiddencomments {
    margin:0 -9px 1em;
    background:#f0f0f0;
    font-size:0.85em;
    padding:4px 9px;
}
#mod-ouwiki-view #ouw_comments_ {
    margin-bottom:0;
}
#mod-ouwiki-comments ul.ouw_comments li.ouw_comment {
    margin:0 0 1em 0;
    border-top:1px dotted #888;
    padding-top:2px;
}
#mod-ouwiki-view ul.ouw_comments li.ouw_comment {
    margin-bottom:6px;
    border-bottom:1px dotted #888;
    padding-bottom:3px;
}
ul.ouw_comments .ouw_commentposter {
    float:left;
    padding-right:1em;
}
ul.ouw_comments h3.ouw_commenttitle {
    margin:0.5em 0;
    font-size:1em;
    display:block;
    border-bottom:none;
}
ul.ouw_comments .ouw_commentsection {
    font-style:italic;
    margin-top:0.3em;
}
#mod-ouwiki-view ul.ouw_comments h3.ouw_commenttitle {
    margin-bottom:0;
}
#mod-ouwiki-view h4.ouw_oldercomments {
    margin:0;
    margin-bottom:4px;
}
ul.ouw_comments .ouw_commentposter,
ul.ouw_comments .ouw_commentdate,
ul.ouw_comments .ouw_commentsubmit input {
    font-size:0.85em;
}
#mod-ouwiki-view ul.ouw_comments .ouw_commentposter,
#mod-ouwiki-view ul.ouw_comments .ouw_commentdate,
#mod-ouwiki-view ul.ouw_comments .ouw_commentsubmit input {
    font-size:1em;
}

ul.ouw_comments .ouw_commentsubmit {
    text-align:right;
}

#mod-ouwiki-comments h2 {
    margin-top:3em;
    font-size:1em;
}
.ouw_hiddencommentoptions span {
    margin-right:1em;
}
.ouw_hiddencomments {
    display:none;
}
#ouw_ac_formcontainer {
  margin-top:1.5em;
  clear:right;
}
.ouw_nocomments #ouw_ac_formcontainer {
    margin-top:0;
} 

#mod-ouwiki-comments .ouw_deletedcommentinfo {
    margin-bottom:1.5em;
}
#mod-ouwiki-comments .ouw_deletedcomment .ouw_commentxhtml,
#mod-ouwiki-comments .ouw_deletedcomment .ouw_commenttitle {
    text-decoration:line-through;
    color:#888;
}
.ouw_deletedrow {
    background-color:#cccccc;
}
.ouw_deleted {
    color:#cc0000;
}
#mod-ouwiki-comments .ouw_deletedcomment form {
    text-decoration:none !important;
}

.ouw_recenter {
    font-weight:bold;
    color:black;
}
.ouw_recent {
    color:black;
}
.ouw_recentnot {
    color:#636363;
}

#mod-ouwiki-wikiindex .ouw_wikirecentchanges ul,
#mod-ouwiki-wikiindex .ouw_wikirecentchanges li {
    list-style-type:none;
    margin:0;
    padding:0;
    
}


#mod-ouwiki-wikihistory .ouw_paging {
    margin-top:1em;
}
#mod-ouwiki-wikihistory .ouw_paging_prev {
    float:left;
    width:50%;
    text-align:right;
}
#mod-ouwiki-wikihistory .ouw_paging_prev a {
    margin-right:2em;
}
#mod-ouwiki-wikihistory .ouw_paging_next {
    float:left;
    width:50%;
}
#mod-ouwiki-wikihistory .ouw_paging_next a {
    margin-left:2em;
}


#mod-ouwiki-comments.ie .ouw_commentdate {
    line-height:1.2;
}
#mod-ouwiki-view.ie .ouw_commentdate {
    display:inline;
    line-height:1.2;
}

/* Hack because font-family:inherit doesn't work in IE */
#mod-ouwiki-view.ie textarea,
#mod-ouwiki-comments.ie textarea {
    font-family: Verdana,sans-serif;
}

#mod-ouwiki-view .ouw_linkedfrom {
    margin-top:1.5em;
    font-size:0.85em;
}
#mod-ouwiki-view .ouw_linkedfrom h3 {
    margin:0;
    font-size:1em;
    font-weight:normal;
}
#mod-ouwiki-view .ouw_linkedfrom ul,
#mod-ouwiki-view .ouw_linkedfrom li {
    margin:0;
    padding:0;
    list-style-type:none;
    display:inline;
}

#mod-ouwiki-wikiindex .ouw_missingpages ul,
#mod-ouwiki-wikiindex .ouw_missingpages li {
    margin:0;
    padding:0;
    list-style-type:none;
    display:inline;
}


.ouw_subwiki {
    float:left;
}
.ouwiki_notabs.ouwiki_gotselector {
    margin-top:1em;
}

.ouwiki_lockinfobuttons form {
    display:inline;
}

a.ouwiki_noshow:link,a.ouwiki_noshow:visited {
    color:inherit;
}

.ouw_index .ouw_index_startpage {
   margin-bottom:1em;
}

.ouw_timelocked {
    margin-top:3em;
    font-style:italic;
}

#mod-ouwiki-comments .ouw_returnlink {
    margin-top:2em;
}

#mod-ouwiki-wikiindex .ouw_savetemplate, 
#mod-ouwiki-wikihistory .ouw_contributionslink {
    margin-top:2em;
}

#mod-ouwiki-contributions h2, #mod-ouwiki-contributions h3 {
    font-size:1em;
    margin:1em 0 0.5em;
}
#mod-ouwiki-contributions small {
    font-size:1em;
    font-weight:normal;
}
#mod-ouwiki-contributions table {
    margin-bottom:2em;
}

#mod-ouwiki-entirewiki h1 a {
    color:black;
}
#mod-ouwiki-entirewiki .ouw_entry {
    margin-bottom:2em;
}

.ouw_subscribe {
    margin-top:2em;
    font-size:0.85em;
}
.ouw_subscribe img {
    vertical-align:bottom;
}
.ouw_subscribe span {
    position:relative;
    top:-1px;
}

.ouw_belowmainhead {
    border:1px dotted #888;
    border-top:none;
    padding:0px 8px 4px;
}
.ouw_topspacer {
     padding-top:4px;
}
.ouw_topheading {
    background:#dcedff;    
    padding:4px 8px 8px;
    border-top:1px solid #888;
    margin-top:1.5em;
}

.ouw_topheading h1 {
    border:none;
}

#mod-ouwiki-comments .ouwiki_allcomments {
    background:#f0f0f0;
    padding:0 8px 8px;
}

#mod-ouwiki-entirewiki .ouw_entry h1.ouw_entry_heading {
    background:#dcedff;
    padding:4px 8px 8px;
    border-top:1px solid #888;
    border-bottom:none;
    display:block;
    margin:0.5em 0 0;
}
#mod-ouwiki-entirewiki .ouw_entry .ouwiki_content {
    border:1px dotted #888;
    border-top:none;
    padding:4px 8px;
    margin-top:0;
}

#mod-ouwiki-reportsgroup .ouw_bar {    
	  float:left; 
    position:relative;
    margin-right:1px;         
    overflow:hidden; /* sigh IE6 */
}

#mod-ouwiki-reportsgroup .ouw_chartcontainer {
	position:relative; 	
}

#mod-ouwiki-reportsgroup .ouw_yaxis {
	position:absolute; 	
    text-align:right;          
}


#mod-ouwiki-reportsgroup .ouw_graphtitle {
	float:left; 
    position:relative;             	
}


#mod-ouwiki-reportsgroup div.ouw_groupreport {
    margin:2em 0;
    width:250px;
    float:left;
}

#mod-ouwiki-reportssummary th,
#mod-ouwiki-reportsusers th,
#mod-ouwiki-reportsuser th,
#mod-ouwiki-reportsgroup th {
    text-align:left;
    padding: 5px 12px 5px 4px;
    background: #a7d2ff;
    border-left: 1px solid #a7d2ff;
    border-right: 1px solid #a7d2ff;
    border-top:1px solid #888;
    border-bottom:1px dotted #888;    
}

#mod-ouwiki-reportssummary td,
#mod-ouwiki-reportsusers td,
#mod-ouwiki-reportsuser td,
#mod-ouwiki-reportsgroup td {
    padding: 3px 12px 3px 4px;
    border-bottom:1px dotted #888;    
}

#mod-ouwiki-reportssummary td.ouw_rightcol,
#mod-ouwiki-reportsusers td.ouw_rightcol,
#mod-ouwiki-reportsuser td.ouw_rightcol,
#mod-ouwiki-reportsgroup td.ouw_rightcol {
    border-right:1px dotted #888;
}
#mod-ouwiki-reportssummary td.ouw_leftcol,
#mod-ouwiki-reportsusers td.ouw_leftcol,
#mod-ouwiki-reportsuser td.ouw_leftcol,
#mod-ouwiki-reportsgroup td.ouw_leftcol {
    border-left:1px dotted #888;
}
#mod-ouwiki-reportsgroup tr.ouw_lastingroup {
    border-bottom:2px dotted #a7d2ff;
}
#mod-ouwiki-reportsgroup table h4 {
    margin:0;
}

#mod-ouwiki-reportsgroup .ouw_graphs {
    width:200px;
    margin-top:2em;
    margin-left:20px;
    float:left;
}

#mod-ouwiki-reportsgroup .ouw_pagelist {
    clear:left;   
}

 
#mod-ouwiki-reportsgroup .ouw_pagelist,
#mod-ouwiki-reportssummary .ouw_grouplist,
#mod-ouwiki-reportsgroup .ouw_userlist,
#mod-ouwiki-reportsuser .ouw_userlist,
#mod-ouwiki-reportsuser .ouw_usereditslist,
#mod-ouwiki-reportsuser .ouw_usercommentslist,
#mod-ouwiki-reportsuser .ouw_useractivitybydatelist {
	margin-top:2em;		
	margin-right:2em;
}

#mod-ouwiki-reportssummary .ouw_dodgyextrarow td,
#mod-ouwiki-reportsgroup .ouw_dodgyextrarow td {
    padding: 5px 12px 5px 4px;
    background: #a7d2ff;
    border-left: 1px solid #a7d2ff;
    border-right: 1px solid #a7d2ff;
    border-top:1px solid #888;
    border-bottom:none;
    font-weight:bold;
}

#mod-ouwiki-reportssummary .ouw_grouplist th,
#mod-ouwiki-reportsgroup .ouw_pagelist th {
    border-top:none;
}
#mod-ouwiki-reportsgroup .ouw_firstingroup,
#mod-ouwiki-reportsusers .ouw_firstingroup,
#mod-ouwiki-reportsuser .ouw_firstingroup,
#mod-ouwiki-reportssummary .ouw_firstingroup {
    border-left:2px dotted #a7d2ff;
}

#mod-ouwiki-reportssummary th.ouw_firstingroup, 
#mod-ouwiki-reportssummary .ouw_dodgyextrarow td.ouw_firstingroup,
#mod-ouwiki-reportsusers th.ouw_firstingroup, 
#mod-ouwiki-reportsuser th.ouw_firstingroup, 
#mod-ouwiki-reportsgroup th.ouw_firstingroup, 
#mod-ouwiki-reportsgroup .ouw_dodgyextrarow td.ouw_firstingroup {
    border-left:2px dotted #888;
}

#mod-ouwiki-reportsgroup .ouw_pagecolumn { 
    height:1px;
    width:8em;
}

#mod-ouwiki-reportssummary .ouw_groupcolumn { 
    height:1px;
    width:8em;
}

#mod-ouwiki-reportsusers .ouw_datecolumn,
#mod-ouwiki-reportsuser .ouw_datecolumn,
#mod-ouwiki-reportsgroup .ouw_datecolumn {
    height:1px;
    width:10em;
}
#mod-ouwiki-reportsusers .ouw_namecolumn,
#mod-ouwiki-reportsuser .ouw_namecolumn {
    height:1px;
    width:15em;
}

#mod-ouwiki-reportsgroup .ouw_lastdate {
	text-align:right;
}

#mod-ouwiki-reportsgroup .ouw_timelines_page {
	margin-top:2em;	
}

#mod-ouwiki-reportsgroup .ouw_timelines_page td {
    border-bottom:none;
    vertical-align:top;
}
#mod-ouwiki-reportsgroup .ouw_timelines_page tr.ouw_lastrow td {
    border-bottom: 1px dotted #888;
}
#mod-ouwiki-reportsgroup .ouw_bargraph1,
#mod-ouwiki-reportsgroup .ouw_bargraph2,
.ouw_bargraph1key,
.ouw_bargraph2key {
    border-top: 1px solid #888888;
    border-left: 1px solid #888888;
    border-right: 1px solid #888888;
    border-bottom: none;
}
.ie6#mod-ouwiki-reportsgroup .ouw_bargraph1.ouw_zero,
.ie6#mod-ouwiki-reportsgroup .ouw_bargraph2.ouw_zero {
    background:transparent !important;
    border-left:none !important;
    border-right:none !important;
}
#mod-ouwiki-reportsgroup .ouw_bargraph1,
.ouw_bargraph1key {
    background-color: #a7d2ff;
}
#mod-ouwiki-reportsgroup .ouw_bargraph2,
.ouw_bargraph2key {
    background-color: transparent;
}
.ouw_bargraph1key,
.ouw_bargraph2key {
    padding-left:5px;
    font-size:8px;
    border: 1px solid #888888;
    position:relative;
    top:-3px;
}

#mod-ouwiki-reportsgroup .ouw_graph {
    border-left: 1px solid #888888;
    border-bottom: 1px solid #888888;
}
#mod-ouwiki-reportsgroup .ouw_graph_y_mark,
#mod-ouwiki-reportsgroup .ouw_graph_max_pages {
    border-top: 1px solid #888888;
}
#mod-ouwiki-reportsgroup .ouw_graph_x_mark {
    border-left: 1px solid #888888;
}

#mod-ouwiki_addnew {
    display:inline;
}


#mod-ouwiki_addnew ul,
#mod-ouwiki_addnew li {
    margin:0;
    padding:0;
    display:inline;
    list-style-type:none;
}


.ouwiki_addnew_class {
    display:inline;
}

.ouwiki-annotation,
.ouwiki-orphaned-annotation {
    display:block;
    width:50%;
    min-height:42px;
    margin:0.3em;
    padding:0.3em;
    background-color:#F7F0D9;
}

.ouwiki-annotation h3,
.ouwiki-orphaned-annotation h3 {
    display:block;
    font-weight:bold;
    border:0;
    margin:0 0 0.2em;
}

.ouwiki-annotation .userpicture,
.ouwiki-orphaned-annotation .userpicture {
    float:left;
    margin: 0.2em 0.1em;
}

.ouwiki-annotation .ouwiki-annotation-content,
.ouwiki-orphaned-annotation .ouwiki-annotation-content {
    display:block;
    margin-left:40px;
    padding-left:0.3em;
    font-weight:normal;
}

.ouwiki-annotation .ouwiki-annotation-content .ouwiki-annotation-content-title,
.ouwiki-orphaned-annotation .ouwiki-annotation-content .ouwiki-annotation-content-title {
    display:block;
    padding-bottom:0.3em;
    font-weight:bold;
}

.ouwiki-annotation-tag {
    padding:1px 1px 0 1px;
    margin:0;
    cursor:default;
}

.ouwiki-annotation-marker {
    padding:0;
    margin:0;
    cursor:default;
}

.ouwiki-annotation-marker img {padding:1px;}

.ouwiki-annotation-tag img {padding:0;}

.ouwiki-annotation-marker:hover{background-color:#99ccff;}

#showhideannotations {display:none;}
#showallannotations {display:inline;}
#hideallannotations {display:none;}

#ouwiki_addannotation label {
    display:block;
}

#ouwiki_addannotation_buttons {
}

#mod-ouwiki-annotate #mform1 {
    margin-top:2em;
}

.ouwiki_lock_div {margin-top:2em;}


/* Ugh, problems when resized, I can't get it to work */
.ie7 #wikiselect {
    font-size:11px;
}

#ouwiki-annotation-dialog label { 
    display:block;
    float:left;
    width:30%;
    clear:left;
}

.clear {
    clear:both;
}

.yui-pe .yui-pe-content {
    display:none;
}

#yui-gen0-button {color:#000000;}

.ouwiki-notifyproblem {
    color: #ffffff;
    background-color: #ff0000;
    padding: 0 2em;
}

.ouwiki-sizewarning img {
    margin-right: 4px;
    position:relative;
    top: 3px;
}

.ouwiki-sizewarning {
    margin-top: 10px;
    margin-left: 20px;
    text-indent: -20px;
}

#mod-ouwiki-view .ouw_editsection,
#mod-ouwiki-view .ouw_makecomment,
#mod-ouwiki-view .ouw_annotate,
#mod-ouwiki-view .ouw_revealcomment,
#hideallannotations, 
#showallannotations {
background-color:beige;
border:2px outset;
font-size:0.85em;
padding-left:3px;
padding-right:3px;
margin-right:1em;
}

.ouw_missingpages{
background-color:#FFE8E8;
}

.dir-rtl #ouwiki_belowtabs th {
text-align:right;
}

.dir-rtl #mod-ouwiki-reportsgroup .ouw_graphs {
float:right;
}
#mod-ouwiki-reportsgroup div.ouw_groupreport,
#mod-ouwiki-reportsgroup .ouw_graphs {
float:right;
}

// for some styles... i had to use php right_to_left() function (nadavkav patch)
<?php if (right_to_left()) { ?>
.ouw_grouplist th  {
text-align:right;
}

#mod-ouwiki-reportsgroup .ouw_timelines_page,
#mod-ouwiki-reportsgroup .ouw_pagelist,
#mod-ouwiki-reportsgroup .ouw_userlist,
#mod-ouwiki-reportsgroup .csv-links {
float:right;
}

#mod-ouwiki-reportssummary th,
#mod-ouwiki-reportsusers th,
#mod-ouwiki-reportsuser th,
#mod-ouwiki-reportsgroup th {
text-align:right;
}

#mod-ouwiki-wikiindex ul.ouw_indextree ul {
margin-right:1em;
margin-left:0;
}
#mod-ouwiki-wikiindex ul.ouw_indextree ul ul {
margin-right:2.75em;
margin-left:0;
}

#ouwiki-annotation-dialog label {
clear:right;
float:right;
width:auto;
}

#ouwiki-annotation-dialog {
width:17em !important;
}

.yui-skin-sam .yui-panel .hd {
text-align:left;
}

.yui-skin-sam .yui-dialog .ft span.default button {
color:#09007A !important;
}

.ouw_addcomment label {
float:right;
}
<?php } ?>

.ouw_recentchanges_list a.seedetails,
#ouwiki_indexlinks a {
background-color:beige;
border:2px outset #AAAAAA;
font-decoration:none;
}

#ouwiki_indexlinks span {
background-color:gold;
border:2px inset Beige;
}

.ouw_hiddencommentoptions span,
.ouw_makecomment2 {
background-color:beige;
border:2px outset gray;
margin-right:1em;
padding:2px;
}

#wrapper {
background:none repeat scroll 0 0 #FFFFFF !important;
}