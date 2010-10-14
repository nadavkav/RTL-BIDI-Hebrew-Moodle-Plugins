<?php  // $Id: register_form.php,v 1.1 2010/07/08 05:03:16 arborrow Exp $

require_once($CFG->libdir.'/formslib.php');

class wordtable_register_form extends moodleform {

    function definition() {
        global $COURSE;
        $mform    =& $this->_form;

        $defaultcategory   = $this->_customdata['defaultcategory'];
        $contexts   = $this->_customdata['contexts'];

//--------------------------------------------------------------------------------
        $mform->addElement('header', null, get_string("registrationinfotitle", 'qformat_wordtable'));

        $mform->addElement('hidden', 'lang', current_language());
        $mform->addElement('hidden', 'version', $CFG->version);
        $mform->addElement('hidden', 'release', $CFG->release);
        $mform->addElement('hidden', 'courseid', '');

        $mform->addElement('text', 'yolusername', get_string('username'));
        $mform->addRule('yolusername', get_string('required'), 'required', '', 'client');
        $mform->addRule('yolusername', get_string('invalidemail'), 'email', '', 'client');
        $mform->applyFilter('yolusername', 'trim');

        $mform->addElement('password', 'password', get_string("password"));
        $mform->addRule('password', get_string('required'), 'required', '', 'client');
        $mform->addElement('password', 'passwordconfirm', get_string("password"));
        $mform->addRule('passwordconfirm', get_string('required'), 'required', '', 'client');
        // This doesn't work, don't know why
        //$mform->addRule(array('password', 'passwordconfirm'), get_string('registrationpasswordsdonotmatch', 'qformat_wordtable'), 'compare', '', 'client');

        $mform->addElement('static', 'dummy2', '');

        $mform->addElement('text', 'sitename', get_string("fullsitename"));
        $mform->addRule('sitename', get_string('required'), 'required', '', 'client');
        $mform->applyFilter('sitename', 'trim');

        $mform->addElement('text', 'adminname', get_string("administrator"));
        $mform->addRule('adminname', get_string('required'), 'required', '', 'client');
        $mform->applyFilter('adminname', 'trim');

        $mform->addElement('text', 'adminemail', get_string("email"));
        $mform->addRule('adminemail', get_string('required'), 'required', '', 'client');
        $mform->addRule('adminemail', get_string('invalidemail'), 'email', '', 'client');
        $mform->applyFilter('adminemail', 'trim');

        $options[0] = get_string("publicdirectory0");
        $options[1] = get_string("publicdirectory1");
        $options[2] = get_string("publicdirectory2");
        $mform->addElement('select', 'public', get_string("publicdirectory"), $options );
        unset($options);

/*
        $options[0] = get_string("subtype_free", "qformat_wordtable");
        $options[1] = get_string("subtype_unlimited", "qformat_wordtable");
        $mform->addElement('select', 'subscription', get_string("subscription_type", "qformat_wordtable"), $options );
        unset($options);
*/

        $options[0] = "<500";
        $options[1] = "501-5,000";
        $options[2] = ">5,001";
        $mform->addElement('select', 'sitesize', get_string("users"), $options );
        unset($options);

        $mform->addElement('select', 'country', get_string("selectacountry"), get_list_of_countries() );

        $options[0] = get_string("registrationno");
        $options[1] = get_string("registrationyes");
        $mform->addElement('select', 'mailme', get_string("registrationemail"), $options );
//--------------------------------------------------------------------------------
        $mform->addElement('submit', 'submitbutton', get_string('registrationsend', 'qformat_wordtable'));

//--------------------------------------------------------------------------------
        $mform->addElement('static', 'dummy', '');
        $mform->closeHeaderBefore('dummy');
    }
}

?>
