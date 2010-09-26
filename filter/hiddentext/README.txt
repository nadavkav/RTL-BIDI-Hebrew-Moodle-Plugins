$Id: README.txt,v 1.4 2008/04/08 06:57:13 dlnsk Exp $

INFORMATION
    Filter "HiddenText" designed for Moodle.
    Version 1.2
    Tested with Moodle 1.8.x
    Developed by Dmitry Pupinin (dlnsk[at]ngs[dot]ru)
    This filter may be distributed under the terms of the General Public License
        (see http://www.gnu.org/licenses/gpl.txt for details)


WHEREFORE THIS?
    - Using filter "HiddenText" teachers will be able to provide "hints" to students where the information is 
      initially hidden but can easily be revealed by student in any time when he need.



To Install it:
    - Copy folder "hiddentext" to directory "filter" of your Moodle instalation.
    - Copy language file in your "lang" directory.
    - Enable it from "Administration/Filters".
  
To Use it:
    - Create your content.
    - Enclose every part which you want initially hide between:
        <span filter="hiddentext">hidden_content_here</span>
        or
        <div filter="hiddentext">hidden_multiline_content_here</div>
    - If you use HTMLeditor you can type 'span' and 'div' tags inside square brackets straight in text
      without switching to HTML mode:
        [span filter="hiddentext"]hidden_content_here[/span]
    - Test it.

How it works:
    - After page loaded any content between tags hides. In this place will be dislpayed picture with eye.
    - When student will click on eye icon hidden text will be displayed.

Additional information about filter:
    - Use "span" tag if you want hide path of text inside paragraph and "div" if text can take more than one line.
    - You can use two optional parameters: "class" and "desc" (description):
        "class" - lets you change style of hidden text
        "desc"  - lets you change description which displayed after eye icon. If "desc" absent in "div" tag 
                  will be used description from lang file. If description absent in "span" tag will be 
                  displayed only eye icon.
    - You available following styles: hinline, htext, hcode and styles which you add themselves in css of 
      your theme or already there are!
    - Don't like embedded styles? You can change them in yuidomcollapse.css and send to me... ;-)

First example in action:
    - This text:
        This text is <span filter="hiddentext">very, very, very</span> long.

    - Will initially displayed as:
        This text is (eye icon) long.

Second example in action:
    - This text:
        The American holiday of Thanksgiving is celebrated 
        on the <span filter="hiddentext" desc="What?">fourth</span> Thursday of November.

    - Will initially displayed as:
        The American holiday of Thanksgiving is celebrated 
        on the (eye)What? Thursday of November.

Third example in action:
    - This text:
        Mention capitals of following countries: Canada, Italy, Japan
        <div filter="hiddentext" class="htext" desc="Answer here">Canada - Ottawa
        Italy - Rome
        Japan - Tokyo</div>

    - Will initially displayed as:
        Mention capitals of following countries: Canada, Italy, Japan
        (eye)Answer here
    
    - After click:
        Mention capitals of following countries: Canada, Italy, Japan
        (eye)Answer here
           -------------------
           | Canada - Ottawa | 
           | Italy - Rome    | 
           | Japan - Tokyo   |
           ------------------- 

Thanks for using!