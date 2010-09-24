Documentation: http://docs.moodle.org/en/Map_module
Created by Ted Bowman(ted@tedbow.com). http://www.tedbow.com 
Map module
From MoodleDocs



This is a map activity module. It uses Google maps to make in course maps. It can be used for student locations and/or other course content.

After installing this module the Google Map API Key will need to be set in the module settings page. If you don't have a Google Map Key then you can get on here.
Contents


    * 1 Description
    * 2 Compatibility
    * 3 Locations
          o 3.1 User Locations
          o 3.2 Other/Extra Locations
    * 4 Settings
          o 4.1 Show Student Locations on Map?
          o 4.2 Require students consent to appearing on this map?
          o 4.3 Allow Stundents to add extra map locations?
          o 4.4 Show address field for extra locations?
    * 5 Links

1.Description

This module generates geographic maps from Google Maps within Moodle course.
 
2.Compatibility

This module requires php5. It also requires the cURL library: http://us.php.net/curl
It has been tested with Moodle 1.8 and 1.9.

Browsers must be supported by Google Maps API. A list is here: http://code.google.com/apis/maps/faq.html#browsersupport

3.Locations
 
3.1 User Locations

These locations correspond to where the users live. If the students are not required to constent to being shown on this map then the module will attempt to determine their location from their profile information. The user's picture and description will be shown from their profile.
 
3.2 Other/Extra Locations

These locations are for any other course content.

4. Settings
 
4.1 Show Student Locations on Map?

This setting determines whether students personal locations will show up on this map.
 
4.2 Require students consent to appearing on this map?

If student locations will be shown on this map then this setting determines whether students will need to explicitly set their location. If this is set to "No" then teachers and administrators will be able to update all user locations from their profile location.
 
4.3 Allow Stundents to add extra map locations?

This setting determines if students will be able to add locations to this map that are not their own personal locations. Teachers and administrators can always add extra locations to maps.

4.4 Show address field for extra locations?

This setting determines if an address field will be shown for the extra locations. If the address field is not shown then locations will only be able to be shown to the city level. Personal locations never have the address field for privacy reasons. 