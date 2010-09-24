<?php
  /// http://www.twospy.com/galleriffic/index.html
  /// http://www.twospy.com/galleriffic/example-5.html#bigleaf

  require_once('../../../config.php');
  include('../lib.php');

  $id = optional_param('id', 0, PARAM_INT);
  $l = optional_param('l', 0, PARAM_INT);

  if ($id and $id >= 1) {
      if (! $cm = get_coursemodule_from_id('lightboxgallery', $id)) {
    error('Course module ID was incorrect');
      }

      if (! $course = get_record('course', 'id', $cm->course)) {
    error('Course is misconfigured');
      }
      if (! $gallery = get_record('lightboxgallery', 'id', $cm->instance)) {
    error('Course module is incorrect');
      }
  } else {
      if (! $gallery = get_record('lightboxgallery', 'id', $l)) {
    error('Course module is incorrect');
      }
      if (! $course = get_record('course', 'id', $gallery->course)) {
    error('Course is misconfigured');
      }
      if (! $cm = get_coursemodule_from_instance('lightboxgallery', $gallery->id, $course->id)) {
    error('Course module ID was incorrect');
      }
  }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="rtl" lang="he" xml:lang="he">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>Galleriffic | Custom layout with external controls</title>
    <link rel="stylesheet" href="css/basic.css" type="text/css" />
    <link rel="stylesheet" href="css/galleriffic-5.css" type="text/css" />

    <!-- <link rel="stylesheet" href="css/white.css" type="text/css" /> -->
    <link rel="stylesheet" href="css/black.css" type="text/css" />

    <script type="text/javascript" src="js/jquery-1.3.2.js"></script>
    <script type="text/javascript" src="js/jquery.history.js"></script>
    <script type="text/javascript" src="js/jquery.galleriffic.js"></script>
    <script type="text/javascript" src="js/jquery.opacityrollover.js"></script>
    <!-- We only want the thunbnails to display when javascript is disabled -->
    <script type="text/javascript">
      document.write('<style>.noscript { display: none; }</style>');
    </script>
  </head>
  <body>
    <div id="page">
      <div id="container">
        <h1><a href="index.html">Galleriffic</a></h1>
        <h2>הקליקו על התמונות הממוזערות כדי להציג את התמונה המקורית</h2>

        <!-- Start Advanced Gallery Html Containers -->
        <div class="navigation-container">
          <div id="thumbs" class="navigation">
            <a class="pageLink prev" style="visibility: hidden;" href="#" title="Previous Page"></a>

            <ul class="thumbs noscript">


<?php

  $dataroot = $CFG->dataroot . '/' . $course->id . '/' . $gallery->folder;
  $webroot = lightboxgallery_get_image_url($gallery->id);

  $allimages = lightboxgallery_directory_images($dataroot);

  foreach($allimages as $image) {
    $caption = get_record('lightboxgallery_image_meta', 'metatype', 'caption', 'gallery', $gallery->id, 'image', $image);
    echo '<li>';
        echo '<a class="thumb" name="'.$image.'" href="'.$webroot.'/'.$image.'" title="">';
          echo '<img width="64px" height="64px" src="'.$webroot.'/'.$image.'" alt="" title="">';
        echo '</a>';
        echo '<div class="caption">';
          echo '<div class="image-title">'.$image.'</div>';
          echo '<div class="image-desc">'.$caption->description.'</div>';
          //echo '<div class="download">';
            //echo '<a href="'.$webroot.'/'.$image.'">Download Original</a>';
          //echo '</div>';
        echo '</div>';
    echo '</li>';
  }
?>


            </ul>
           <a class="pageLink next" style="visibility: hidden;" href="#" title="Next Page"></a>
          </div>
        </div>
        <div class="content">
          <div class="slideshow-container">
            <div id="controls" class="controls"></div>
            <div id="loading" class="loader"></div>
            <div id="slideshow" class="slideshow"></div>
          </div>
          <div id="caption" class="caption-container">
            <div class="photo-indexz"></div>
          </div>
        </div>
        <!-- End Gallery Html Containers -->
        <div style="clear: both;"></div>
      </div>
    </div>
    <div id="footer">Galleryffic Slideshow</div>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        // We only want these styles applied when javascript is enabled
        $('div.content').css('display', 'block');

        // Initially set opacity on thumbs and add
        // additional styling for hover effect on thumbs
        var onMouseOutOpacity = 0.67;
        $('#thumbs ul.thumbs li, div.navigation a.pageLink').opacityrollover({
          mouseOutOpacity:   onMouseOutOpacity,
          mouseOverOpacity:  1.0,
          fadeSpeed:         'fast',
          exemptionSelector: '.selected'
        });

        // Initialize Advanced Galleriffic Gallery
        var gallery = $('#thumbs').galleriffic({
          delay:                     2500,
          numThumbs:                 10,
          preloadAhead:              10,
          enableTopPager:            false,
          enableBottomPager:         false,
          imageContainerSel:         '#slideshow',
          controlsContainerSel:      '#controls',
          captionContainerSel:       '#caption',
          loadingContainerSel:       '#loading',
          renderSSControls:          true,
          renderNavControls:         true,
          playLinkText:              'הפעלת מצגת',
          pauseLinkText:             'עצירת מצגת',
          prevLinkText:              '&lsaquo; תמונה קודמת',
          nextLinkText:              'התמונה הבאה &rsaquo;',
          nextPageLinkText:          'הבאה &rsaquo;',
          prevPageLinkText:          '&lsaquo; הקודמת',
          enableHistory:             true,
          autoStart:                 false,
          syncTransitions:           true,
          defaultTransitionDuration: 900,
          onSlideChange:             function(prevIndex, nextIndex) {
            // 'this' refers to the gallery, which is an extension of $('#thumbs')
            this.find('ul.thumbs').children()
              .eq(prevIndex).fadeTo('fast', onMouseOutOpacity).end()
              .eq(nextIndex).fadeTo('fast', 1.0);

            // Update the photo index display
            this.$captionContainer.find('div.photo-index')
              .html('Photo '+ (nextIndex+1) +' of '+ this.data.length);
          },
          onPageTransitionOut:       function(callback) {
            this.fadeTo('fast', 0.0, callback);
          },
          onPageTransitionIn:        function() {
            var prevPageLink = this.find('a.prev').css('visibility', 'hidden');
            var nextPageLink = this.find('a.next').css('visibility', 'hidden');

            // Show appropriate next / prev page links
            if (this.displayedPage > 0)
              prevPageLink.css('visibility', 'visible');

            var lastPage = this.getNumPages() - 1;
            if (this.displayedPage < lastPage)
              nextPageLink.css('visibility', 'visible');

            this.fadeTo('fast', 1.0);
          }
        });

        /**************** Event handlers for custom next / prev page links **********************/

        gallery.find('a.prev').click(function(e) {
          gallery.previousPage();
          e.preventDefault();
        });

        gallery.find('a.next').click(function(e) {
          gallery.nextPage();
          e.preventDefault();
        });

        /****************************************************************************************/

        /**** Functions to support integration of galleriffic with the jquery.history plugin ****/

        // PageLoad function
        // This function is called when:
        // 1. after calling $.historyInit();
        // 2. after calling $.historyLoad();
        // 3. after pushing "Go Back" button of a browser
        function pageload(hash) {
          // alert("pageload: " + hash);
          // hash doesn't contain the first # character.
          if(hash) {
            $.galleriffic.gotoImage(hash);
          } else {
            gallery.gotoIndex(0);
          }
        }

        // Initialize history plugin.
        // The callback is called at once by present location.hash.
        $.historyInit(pageload, "advanced.html");

        // set onlick event for buttons using the jQuery 1.3 live method
        $("a[rel='history']").live('click', function(e) {
          if (e.button != 0) return true;

          var hash = this.href;
          hash = hash.replace(/^.*#/, '');

          // moves to a new page.
          // pageload is called at once.
          // hash don't contain "#", "?"
          $.historyLoad(hash);

          return false;
        });

        /****************************************************************************************/
      });
    </script>
  </body>
</html>