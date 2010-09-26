Birthday Block:

This is an attempt at developing a birthday block. Suggestions for improvement are welcome on the tracker (tracker.moodle.org).  Currently the block allows for the admin to configure the name of the custom user profile field, the date format, whether to show or hide the block if there are no birthdays, and the number of days in the future to display upcoming birthdays. The older version (prior to 2008041601) stored the configuration data in mdl_config; however, it is preferred that such data be stored in mdl_config_plugins. 

This block will alphabettically (lastname, firstname) display users who are celebrating a birthday (or anniversary) on a given day (namely, today). The block makes use of the custom user profile fields and assumes that you have created a field with a default shortname of DOB (i.e. date of birth). However, the field's shortname is configurable so you could use Anniversary or any other name you choose. The block further assumes, since the data stored in the custom user profile fields is a text value (and not an actual date) that it is entered in a correct date format (either the default ISO format of YYYY-MM-DD format, the USA format of m.d.Y, or the EUR format of d.m.Y). There is no data validation when the user enters the data and therefore I would recommend that the user not be able to edit their own record. 

Thanks to the generosity of translators the birthday block is now available in English, French, German, Hungarian, Italian, Japanese, Spanish, and Swedish. Other languages are welcome, simply create an issue in the tracker and upload the file and then I can add it to CVS.

 If you have any questions about the Birthday block please send me (Anthony Borrow, S.J.) a message via Moodle.org. 

Installation:

01) Copy the files into the \blocks folder so that you have a \blocks\birthday folder. 
02) Go to the \admin page and allow the block to be installed
03) Go to Users, Accounts, User profile fields
04) Create a new profile field of type text input
05) Input the Shortname (for this block the default is DOB), Name (for example, Birthday), and a description for the user profile field
06) I recommend that the field not be required, that it be locked, and visible to user
07) I have used the default category; however, you are free to organize these fields however you wish and it should not effect the block.
08) I recommend a blank default value, display size of 10, and maximum length of 10.
09) Click on the Save Changes button
10) In the Site Administration Block, click on Modules, Blocks, and the settings link for the Birthday block
11) Input the shortname of the profile field you created to store the birth dates and select the format type (ISO, USA, or EUR) which you will use to populate the user profile field.
12) Enter your data (i.e. the dates into the created user profile field using the chosen format).
13) Add the Birthday block to your page to see a list of those celebrating birthdays or anniversaries. If you want to customize or change the greetings, for example to Happy Anniversary! instead of Happy Birthday! simply edit the language file.

Peace - Anthony
