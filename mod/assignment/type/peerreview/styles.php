<?php

// Style information for peerreview assignment type

echo <<<END

<style>

.progressBar {
    margin-bottom: 20px;
}

.progressBox {
	float: left;
	width: 200px;
	height: 40px;
	margin: 10px 5px;
}

.progressNumber {
	font-weight: bold;
	font-size: 250%;
	padding: 0 5px;
	width: 20px;
	text-align: center;
    vertical-align: top;
}

.progressIcon {
	width: 30px;
	text-align: center;
}

.progressTitle {
	font-weight: bold;
	font-size: 120%;
}

.progressMessage {
	color: #000000;
    font-size: 80%;
}

.greyProgressBox .progressMessage {
	color: #999999;
}

.redStatusBox {
	width: 100%;
	border-collapse: collapse;
	border: 3px solid #ff3333;
	background: #ffe9e9 url("{$CFG->wwwroot}/mod/assignment/type/peerreview/images/cross.gif") no-repeat 95% 20%;
	color: #ff3333;
}

.blueProgressBox {
	width: 100%;
	border-collapse: collapse;
	border: 3px solid #3333cc;
	background: #e9e9ff url("{$CFG->wwwroot}/mod/assignment/type/peerreview/images/alert.gif") no-repeat 95% 20%;
	color: #3333cc;
}

.greyProgressBox {
	width: 100%;
	border-collapse: collapse;
	border: 3px solid #999999;
	background: #e9e9e9;
	color: #999999;
}

.greenProgressBox {
	width: 100%;
	border-collapse: collapse;
	border: 3px solid #339933;
	background: #e9ffe9 url("{$CFG->wwwroot}/mod/assignment/type/peerreview/images/tick.gif") no-repeat 95% 20%;
	color: #339933;
}

.errorStatus {
	color:#ff6600;
	background:#ffff00;
	padding:2px;
}

.goodStatus {
	color:#009900;
	background:#ccffcc;
	padding:2px;
}

.evenCriteriaRow {
    background:#f6f6f6;
}

.criteriaCheckboxColumn {
    vertical-align:top;
    width:20px;
}

.criteriaTextColumn {
    vertical-align:top;
}

.criteriaDisplayRow {
    border-top:1px dotted #e9e9e9;
}

.criteriaDisplayColumn {
    padding:0 0 10px 5px;
    text-align: left;
    vertical-align:top;
}

.reviewCommentRow {
    text-align:left;
    vertical-align:bottom;
    padding:5px;
}

.reviewDetailsRow {
    font-size:x-small;
}

.reviewDateColumn {
    text-align:right;
}

.commentTextBox {
    padding:3px;
    margin:0;
    border:none;
    width:99%;
}

.reviewStatus {
    padding:5px 0;
}

</style>

END;

?>