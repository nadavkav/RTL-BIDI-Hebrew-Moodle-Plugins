With this filter, you could, on your moodle site first page only, print the title of discussions of many of your forums, like a feed reader.

- Only on the first page for security reasons (only admin can do this), other users can't read forums discussions without right
- This filter use experimental Moodle part wich let you to apply a diffusion date to your discussions (must be activated in forum setting by admin)
- This filter didn't use RSS feed wich do not rescpect this experimental fonction
- Title are printed wiht a link to the discussions
- Discussions are ordered in DESC order regarding diffusion date (if exist) and modification date
- Hidden discussions aren't showed in the list




HOW TO
1) Install this fitler
2) Copy/Paste the news.css file in your theme directory and change it if you want (if this file doesn't exist in your theme directory, the filter use the on in moodle/filter/news/news.css)
3) Activate this filter
4) Try it...

For using this filter, make a label in wich you write : [-NEWS(id,group,nb)-]

- "id" is the forum id, you can find it regarding forum URL : http://www.mymoodle.org/mod/forum/view.php?id=8
- "group" is group id, use 0 if forum doesn't have groups, or if you want the "all user" messages, you can find it regarding forum URL : http://www.mymoodle.org/mod/forum/view.php?id=8&group=1
- "nb" is the max discussions number to show

For exemple :
[-NEWS(2,0,5)-] print the 5 last discussions from forum 2
[-NEWS(14,2,7)-] print the 7last discussions of group 2 from forum 14


You can add many instance in a label, that allow you to make a news table for exemple.

The update rythme didn't depends of your cache setting.

Tell me if something is wrong.

Éric Bugnet
