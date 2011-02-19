<?php
  if ($section == 0) $frontpage = true; else $frontpage = false;

  if (!$frontpage) {
    netpublish_print_sections($cm->id, $cm->instance);
  } else {
    echo "";
  }

  echo "<table border=\"0\" cellpadding=\"6\" cellspacing=\"3\" width=\"100%\" >";
  echo "<tr>";

  if ($frontpage) {
    echo "<td width=\"200\" valign=\"top\" style=\"background-color: white; -moz-border-radius: 8px;\">";
    echo '<p><strong>'.print_string("sections","netpublish").'</strong></p>';
    netpublish_print_sections($cm->id, $cm->instance);
    echo "</td>";
  } else {
    echo "";
  }

//      <td width="*" valign="top" style="background-color: white; -moz-border-radius: 8px;">
//      <td width="*" valign="top" style="background-image: url(http://www.tikshuv.org.il/moodle/file.php/141/paper-01.jpg); -moz-border-radius: 8px;">

  echo "<td width=\"*\" valign=\"top\" style=\"background-image: url(".$magtheme['articlesbackgroundimage']."); -moz-border-radius: 8px;\">";

  if (!empty($strnoarticles) and !$frontpage) {
      echo $strnoarticles;
  } else if (empty($article) and !$frontpage) {

      // Edit buttons
      netpublish_print_actionbuttons($cm, $articles[key($articles)], $USER->id, $course->id, true);
      echo "<h2>". stripslashes($articles[key($articles)]->title) ."</h2>\n";

      echo "<p><strong>". $strauthor ."</strong>: ".fullname($articles[key($articles)]);
      netpublish_print_authors($articles[key($articles)]->authors);
      echo "<br />\n";
      echo "<strong>". $strpublished . "</strong>: ". userdate($articles[key($articles)]->timepublished) . "</p>\n";

      echo stripslashes($articles[key($articles)]->intro);

      if (empty($mod->fullpage)) {
        echo "<p><a href=\"view.php?id=$cm->id&amp;section=$section&amp;article=". $articles[key($articles)]->id ."\">";
        echo $strreadmore . "</a></p>\n";
      } else {
        echo stripslashes($articles[key($articles)]->content);
        echo "<p style=\"font-size: x-small;\">(";
        echo $strcreated;
        echo ": " .userdate($articles[key($articles)]->timecreated);
        echo " ". $strmodified .": ". userdate($articles[key($articles)]->timemodified) .")</p>\n";
      }
  } else {
      if (!$frontpage) {
          /// display a single article on the page (the first in current section
          $thearticle = netpublish_get_article($article);

          // Edit buttons
          netpublish_print_actionbuttons($cm, $thearticle, $USER->id, $course->id, true);
          echo "<h2>". stripslashes($thearticle->title) ."</h2>\n";

//if ( $_GET['id'] != 15832 ) { // (nadavkav) orit tati hack. do not forget to remove after 9.3.2010
          echo "<div class=\"authors\"><strong>". $strauthor ."</strong>: ". s($thearticle->firstname) ." ". s($thearticle->lastname);
          netpublish_print_authors($thearticle->authors);
          echo "<br />\n";
          echo "<strong>". $strpublished . "</strong>: ". userdate($thearticle->timepublished) . "</div>\n";
//}

          echo stripslashes($thearticle->intro);
          echo stripslashes($thearticle->content);

          echo "<p style=\"font-size: x-small;\">(";
          echo $strcreated;
          echo ": " .userdate($thearticle->timecreated);
          echo " ". $strmodified .": ". userdate($thearticle->timemodified) .")</p>\n";
      } else {
          /// display all articles in current section
          ///  (on front page, display all articles in the Magazine/Newspapaer)
          $allsections = get_records("netpublish_sections", "publishid", $mod->id);
          foreach ($allsections as $section) {
              $sectionarticles = get_records_select("netpublish_articles", " publishid = $mod->id AND sectionid = $section->id AND statusid = 4 ");
              $newline = 0;
              if (!empty($sectionarticles )) {
                  echo "<div class=\"netpublish-section\">";
                  echo "<h1>".stripslashes($section->fullname)."</h1><hr>";
                  //$allarticles = netpublish_get_articles($section, $cm->instance, $mod->fullpage);
                  foreach ($sectionarticles as $article) {
                    echo "<div class=\"netpublish-article\">";
                    echo "<h2>".stripslashes($article->title)."</h2>";
                    echo stripslashes($article->intro);
                    echo "<a href=\"view.php?id=$cm->id&amp;section=$section->id&amp;article=$article->id\"><b><strong> ".get_string('more')."...</strong></b></a>";
                    //echo stripslashes($article->content);
                    echo "</div>";
                    //if (++$newline == $magtheme['frontpagecolums'] ) {$newline = 0; echo "<br/>";}
                  }
                  echo "</div>";
              }
          }
      }
  }
  echo "</td>";
  if (!$frontpage) {
        echo "<td width=\"200\" valign=\"top\" style=\"background-color: white; -moz-border-radius: 8px;\">"; // articles in this section
        if (is_array($articles)) {
            echo "<p><strong>". get_string("articlesinsection","netpublish") ."</strong></p>\n";
            $i = 0;
            foreach($articles as $article) {
                echo "<a href=\"view.php?id=$cm->id&amp;section=$section&amp;article=$article->id\">";
                print (!empty($_GET['article']) && $_GET['article'] == $article->id) ?
                "<strong>$article->title</strong>" :
                ((!empty($mod->fullpage) && empty($_GET['article']) && $i == 0) ?
                "<strong>$article->title</strong>" : $article->title);
                echo "</a><br />\n";
                $i++;
		//echo 'artice ='.$i;
            }
        }
	echo "</td>";
  }
  echo"</tr>";
  echo"</table>";

?>
