<?php //$Id: styles.php,v 1.3.2.2 2008/06/20 13:36:45 mchurch Exp $
/*
 * Extra styles for questionnaire interface.
 */
?>

.questionnaire_qbut {
    padding-right: 0.5em;
}

/** 
 ** Question editing formslib style changes:
 **/

#mod-questionnaire-questions div.qcontainer .fitemtitle {
    display: none;
}

#mod-questionnaire-questions .mform div.qcontainer fieldset.felement {
    width: 100%;
}

#mod-questionnaire-questions div.qcontainer div.qicons {
    display: block;
    width: 19%;
    float: left;
}

#mod-questionnaire-questions div.qcontainer div.qtype {
    display: block;
    width: 19%;
    float: left;
}

#mod-questionnaire-questions div.qcontainer div.qreq {
    display: block;
    width: 9%;
    float: left;
}

#mod-questionnaire-questions div.qcontainer div.qname {
    float: left;
    display: block;
    width: 50%;
}

#mod-questionnaire-questions div.qcontainer div.qheader {
    border-bottom: double #000000 4px;
    border-top: double #000000 4px;
    font-weight: bold;
    margin-top: 20px;
    padding-bottom:10px;
}

#mod-questionnaire-questions div.qcontainer div.fstatic {
    width: 97%;
    border-bottom: solid #000000 1px;
    margin-right: 1em;
    background-color: #FFFFFF;
}

#mod-questionnaire-questions div.qcontainer div.qcontent {
    margin-bottom:-1em;
}

div.qoptcontainer div.ftextarea {
    clear: all;
    float: none;
    width: 600px;
    margin: 0pt auto 10px;
}

div.qoptcontainer div.ftextarea textarea.qopts {
    width: 600px;
    height: 10em;
}