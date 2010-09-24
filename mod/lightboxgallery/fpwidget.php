<?php
/// http://github.com/ginger/slideGallery
    global $galleryinstancecount;

    if(!$galleryinstancecount) {
        $galleryinstancecount=1;
    }

    if (!$mod->visible) {
        echo "<span class=\"dimmed_text\">";
    }

    // Include JavaScript YUI, only once per course page
    if ($galleryinstancecount<=1) {
      //<!--link media="all" type="text/css" rel="stylesheet" href="../Source/Css/all.css" /-->
      //echo '<STYLE TYPE="text/css" MEDIA="screen, projection"> <!--  @import url('.$CFG->wwwroot.'/mod/lightboxgallery/gingerslides/css/all.css); --> </STYLE>';
      echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/lightboxgallery/gingerslides/mootools-1.2.4-core.js"></script>';
      echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/lightboxgallery/gingerslides/mootools.slideGallery.pack.js"></script>';
?>
<script type="text/javascript">

    window.addEvent("domready", function() {
      /* Example 1  */
      var gallery1 = new slideGallery($$("div.gallerywidget")[0], {
        steps: 2,
        paging: false,
        mode: "circle",
        duration: 3000,
        direction: "horizontal"
      });

//     var gallery12 = new fadeGallery($$("div.gallery")[0], {
//         speed: 400,
//         paging: true,
//         autoplay: true,
//         duration: 2000,
//         onStart: function(current, visible, length) {
//           $$("span.info")[0].innerHTML = parseInt(current+1) + " from " + length;
//         },
//         onPlay: function(current, visible, length) {
//           $$("span.info")[0].innerHTML = parseInt(current+1) + " from " + length;
//         }
//       });

    });
  </script>
<style>
.gallerywidget { max-width: 420px; width:100%; margin:auto; } /* you can change */
.gallerywidget .holder {
    width: 100%;
    position: relative;
    overflow: hidden;
}
.gallerywidget .holder ul {
    margin: 0;
    padding: 0;
    list-style: none;
    width: 99999px;
}
.gallerywidget .holder ul li { float: right; }
.gallerywidget .image { padding: 2px; }
.gallerywidget .prev,
.gallerywidget .next {
background-color:beige;
border:1px outset;
padding:2px;
}
</style>
<?php
    }

    // Get Tab instance data from Module ID
   if ($mod->id) {
        if (! $cm = get_record("course_modules", "id", $mod->id)) {
            error("Course Module ID was incorrect");
        }

        if (! $lightboxgallery = get_record("lightboxgallery", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    }
    // Should we display on Course's Frontpage?
    if ($lightboxgallery->coursefp == 1 ) {
        $donotshowacitivity = true;

        $dataroot = $CFG->dataroot . '/' . $course->id . '/' . $lightboxgallery->folder;
        $webroot = lightboxgallery_get_image_url($lightboxgallery->id);

        $allimages = lightboxgallery_directory_images($dataroot);

        
          //$caption = get_record('lightboxgallery_image_meta', 'metatype', 'caption', 'gallery', $gallery->id, 'image', $image);
          echo '<div class="gallerywidget">';
            echo '<div class="holder">';
            echo '<ul>';
            foreach($allimages as $image) {
              echo '<li>';
                echo '<img class="image" width="140px" height="100px" src="'.$webroot.'/'.$image.'" alt="'.$image.'" title="'.$image.'">';
              echo '</li>';
            }
            echo '</ul>';
            echo '</div>';

            echo '<a href="#" class="prev">'.get_string('previous').'</a>&nbsp;';
            echo '<a href="#" class="next">'.get_string('next').'</a>';
            echo '<div class="control">';
              echo '<span class="info"></span>';
            echo '</div>';
          echo '</div>';
        

        // TAB instance counter. to supress more then one JS include of YUI code.
        $galleryinstancecount++;

        add_to_log($course->id, "lightboxgallery", "view", "view.php?id=$cm->id", "$lightboxgallery->id");

        if (!$mod->visible) {
            echo "</span>";
        }

    }
?>