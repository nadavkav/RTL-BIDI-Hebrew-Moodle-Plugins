Simple tagging for Wikipedia Links. Filter is aware of the current language
of the session and points as a default to the same idiomatic Wikipedia.

Author : Valéry Frémaux. 11/2006 (vf@eisti.fr)

To install it:
    - deploy in <%%moodle_install%%>/filter
    - copy the relevant language files 
      into <%%moodle_install%%>/lang directories
    - Activate filter from "Administration/Filters".

How to use it:
    - Direct tagging: 

    	Tag a word apending [WP] will provide a direct
    	link to that article in Wikipedia when published. Example :

    	Ethnomethodology[WP]

    	to tag a sentence or expression, use insecable spaces to
    	separate words (Ctrl+Maj+Esp in most situations) within
    	the locution. Example :

    	Yoshua[^s]Bar-Hillel[WP]

    - Indirect tagging

      To point a distinct article than what the tagged word whould point to, 
      extend the [WP] tagging with an additional parameter. Separator is | 
      (pipe). Example :

      Ethnological[WP|Ethnology]

    - Pointing another language

      You may use a third parameter to change the idiomatic space of
      the Wikipedia response. Example :

      Cheese[WP|Fromage|fr]

Parameters:

	   The filter allows enabling or disabling a report of all collected keys
	   within a content block. If enabled, The content is appended with
	   the recollection of all inserted keys. You will provided a link
	   to a test popup for all these links. In all cases, only teachers
	   can see the report.

Additional features:

	 - Automated test of links

	 A popup for testing globally all the links in a content block has been provided. 
	 this popup allows launching a test sequence of any key collected. 

	 Click on "Start the test" to initiate test sequence.

	 Beware: this test is implemented client-side (Ajax). You must hav enabled 
	 multi-domain content access to run it. This option is available on some
	 browsers (ie: IE -> Tools -> Internet Options -> Security -> Customize level -> Access to multi-source resources).

	 Better set this option to "Prompt".
