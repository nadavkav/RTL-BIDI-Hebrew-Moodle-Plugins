<?php

    // This page produces xml out put of gallery and gategory names
    // used via ajax to create dropdown menus.

    require_once("../../config.php");
    require_once("lib.php");

    $sesskey  = required_param('sesskey', PARAM_ALPHANUM);
    $command  = optional_param('cmd', 'gallerylist', PARAM_ALPHA);

    $gallery = new modImagegallery(); // Instantiate imagegallery object.

    if ( !$gallery->user_allowed_editing() ) {
        error("You're not allowed to use this page!!!",
              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    if ( !confirm_sesskey($sesskey) ) {
        error("Session key error!!!");
    }

    switch ( $command ) {
        case 'gallerylist':
            if ( $galleries = get_records("imagegallery", "", "", "", "id, name") ) {
                header("Content-Type: text/xml");
                echo '<?xml version="1.0" encoding="'. get_string('thischarset') .'"?>'."\n";
                echo '<galleries>'."\n";
                foreach ( $galleries as $gallery ) {
                    echo "\t".'<gallery id="'. s($gallery->id) .'">';
                    echo s($gallery->name);
                    echo '</gallery>'."\n";
                }
                echo '</galleries>'."\n";
            }
        break;
        case 'categorylist':
            $galleryid = required_param('gallery', PARAM_INT);

            if ( $categories = get_records("imagegallery_categories", "galleryid", $galleryid, "", "id, name") ) {
                header("Content-Type: text/xml");
                echo '<?xml version="1.0" encoding="'. get_string('thischarset') .'"?>'."\n";
                echo '<categories>'."\n";
                foreach ( $categories as $category ) {
                    echo "\t".'<category id="'. s($category->id) .'">';
                    echo s($category->name);
                    echo '</category>'."\n";
                }
                echo '</categories>'."\n";
            }

        break;
        default:
            echo '<?xml version="1.0" encoding="'. get_string('thischarset') .'"?>'."\n";
            echo '<error>Unknown command</error>'."\n";
    }
?>