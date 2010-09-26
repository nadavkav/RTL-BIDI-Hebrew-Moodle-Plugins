Ordering Question
-----------------

Author: Adriane Boyd (adrianeboyd@gmail.com)

Description:

The teacher provides at least three items in order and the students are 
presented with a shuffled list of items to order.  By default, the items 
are displayed in a vertical list.

There are two versions of the question that can be displayed: a 
drag-and-drop version using javascript and a non-javascript version that 
looks like the matching question.  The javascript version is displayed for 
all YUI-compatible browsers with javascript enabled.  The non-javascript 
version is displayed in all other cases.

There is an option to display the items horizontally instead of 
vertically, which is only relevant for the javascript version.  It is only 
recommended for a small number of short items.

The question has been tested on Firefox 2, Opera 9, and Internet Explorer 
6.  Please let me know if you run into any problems.

Grading:

The grading is based on the absolute position of each item in the list.  
Identical items are equivalent for grading purposes.

In the non-javascript version, each item position answer can only be 
correct the first time it is used.  If an item position is used twice, the 
second and any following answers with that item position are always 
incorrect.
