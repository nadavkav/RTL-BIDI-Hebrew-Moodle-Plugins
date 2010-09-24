<?php
    // Pick up graph data
    $roles = $param->roles;
    $pages = $param->pages;
    $edited = $param->edited;

    // Remove roles=/pages=/edited= at start of these strings
    // as no longer URL parameters
    if (($idx = strpos($roles, '=')) !== false) {
        $roles = substr($roles, ++$idx);
    }
    if (($idx = strpos($pages, '=')) !== false) {
        $pages = substr($pages, ++$idx);
    }
    if (($idx = strpos($edited, '=')) !== false) {
        $edited = substr($edited, ++$idx);
    }

    // Convert graph data to arrays
    $x_titles = explode(',', $roles);
    $y_data1 = explode(',', $edited);
    $y_data2 = explode(',', $pages);

    // Validate number of graph sections
    $x_points = count($x_titles);
    if ($x_points != count($y_data1) ||
        $x_points != count($y_data2)) {
        error('invalid graph data');
    }

    // Determine maximum y value (assume always +ve)
    $y_max = 0;
    foreach ($y_data1 as $key => $value) {
        if ($y_max < $value) {
            $y_max = $value;
        }
        if ($y_max < $y_data2[$key]) {
            $y_max = $y_data2[$key];
        }
    }

    // y max is number of y scale number of pages
    $y_pages = $y_max;

    // Determine y scale (assume always +ve)
    $y_scale = 0;
    $scales = array(1, 2, 5);
    $adj = 1;
    while (!$y_scale) {
        reset($scales);
        while (!$y_scale && list(, $scale) = each($scales)) {
            if ($y_max <= ($scale*$adj) * Y_POINTS) {
                $y_scale = $scale*$adj;
            }
        }
        $adj *= 10;
    }

    // Adjust maximum y value (assume always +ve)
    $y_max = $y_scale*Y_POINTS;

    $graph_title = "";
    $graph_x_data = explode(',', $roles);
    $graph_y_data2 = explode(',', $edited);
    $graph_y_data1 = explode(',', $pages);
     
    // Display graph container (all measurements in pixels)
    $graphwidth = X_SCALE_WIDTH*$x_points;
    $xwidth = Y_TITLE_WIDTH + $graphwidth;
    $yheight = X_TITLE_HEIGHT + (Y_SCALE_HEIGHT*Y_POINTS);
    print '<div style="position:relative; ' .
                      'padding:'.PADDING.'px; ' .
                      'width:'.($xwidth + MAX_PAGES_WIDTH).'px; height:'.$yheight.'px; ' .
                      'border:1px solid black">';

    // Display y-axis scale & scale markers
    for ($i = Y_POINTS; $i >= 0; $i--) {
        print '<div class="ouw_graph_y_mark" ' .
                   'style="position:absolute; ' .
                          'top:'.(PADDING + (Y_POINTS - $i)*Y_SCALE_HEIGHT).'px; left:'.(Y_TITLE_WIDTH - PADDING).'px; ' .
                          'width:'.(PADDING*2).'px; height:'.Y_TITLE_HEIGHT.'px">' .
              '</div>';
        print '<div style="position:absolute; ' .
                          'top:'.(PADDING + (Y_POINTS - $i)*Y_SCALE_HEIGHT).'px; left:'.PADDING.'px; ' .
                          'width:'.(Y_TITLE_WIDTH - PADDING).'px; height:'.Y_TITLE_HEIGHT.'px; ' .
                          'text-align:right; ' .
                          'padding-right:'.PADDING.'px">' .
              $i*$y_scale .
              '</div>';
    }

    // Display graph itself (all measurements in pixels)
    // do we need this?
    print '<div class="ouw_graph" ' .
               'style="position:absolute; ' .
                      'top:'.PADDING.'px; left:'.(PADDING + Y_TITLE_WIDTH).'px; ' .
                      'width:'.$graphwidth.'px; height:'.(Y_SCALE_HEIGHT*Y_POINTS).'px">' .
          '</div>';

    // Display bars
    $barwidth = round(X_SCALE_WIDTH/3);
    foreach ($y_data1 as $idx => $value) {

        $barheight = round((Y_SCALE_HEIGHT*Y_POINTS*$value)/$y_max);
        print '<div class="ouw_bargraph1'.($barheight==0 ? ' ouw_zero':'').'" ' .
                   'style="position:absolute; ' .
                          'top:'.(PADDING + ((Y_SCALE_HEIGHT*Y_POINTS) - $barheight) - BORDER).'px; ' .
                          'left:'.round(PADDING + Y_TITLE_WIDTH + (X_SCALE_WIDTH/3) + (X_SCALE_WIDTH*$idx)).'px; ' .
                          'width:'.$barwidth.'px; height:'.$barheight.'px; ' .
                          'font-size:0">' .
              '</div>';

        $barheight = round((Y_SCALE_HEIGHT*Y_POINTS*$y_data2[$idx])/$y_max);
        print '<div class="ouw_bargraph2'.($barheight==0 ? ' ouw_zero':'').'" ' .
                   'style="position:absolute; ' .
                          'top:'.(PADDING + ((Y_SCALE_HEIGHT*Y_POINTS) - $barheight) - BORDER).'px; ' .
                          'left:'.round(PADDING + Y_TITLE_WIDTH + (X_SCALE_WIDTH/3) + (X_SCALE_WIDTH*$idx)).'px; ' .
                          'width:'.$barwidth.'px; height:'.$barheight.'px; ' .
                          'font-size:0">' .
              '</div>';
    }

    // Display x-axis titles and scale markers
    foreach ($x_titles as $idx => $role) {
        print '<div class="ouw_graph_x_mark" ' .
                   'style="position:absolute; ' .
                          'top:'.(PADDING + (Y_SCALE_HEIGHT*Y_POINTS)).'px; ' .
                          'left:'.round(PADDING + Y_TITLE_WIDTH + (X_SCALE_WIDTH*$idx)).'px; ' .
                          'width:'.X_SCALE_WIDTH.'px; height:'.(PADDING*2).'px">' .
                          '</div>';
        print '<div style="position:absolute; ' .
                          'top:'.(PADDING*2 + (Y_SCALE_HEIGHT*Y_POINTS)).'px; ' .
                          'left:'.round(PADDING + Y_TITLE_WIDTH + (X_SCALE_WIDTH*$idx)).'px; ' .
                          'width:'.X_SCALE_WIDTH.'px; height:'.X_TITLE_HEIGHT.'px; ' .
                          'text-align:center">' .
                          $role .
                          '</div>';
    }
    print '<div class="ouw_graph_x_mark" ' .
               'style="position:absolute; ' .
                      'top:'.(PADDING + (Y_SCALE_HEIGHT*Y_POINTS)).'px; ' .
                      'left:'.round(PADDING + Y_TITLE_WIDTH + $graphwidth).'px; ' .
                      'width:'.PADDING.'px; height:'.(PADDING*2).'px">' .
                      '</div>';

    // Display horizontal line for number of pages
    if ($y_pages != 0) {
        $lineheight = round((Y_SCALE_HEIGHT*Y_POINTS*$y_pages)/$y_max);
        print '<div class="ouw_graph_max_pages" ' .
                   'style="position:absolute; ' .
                          'top:'.(PADDING + ((Y_SCALE_HEIGHT*Y_POINTS) - $lineheight) - BORDER).'px; ' .
                          'left:'.(PADDING + Y_TITLE_WIDTH).'px; ' .
                          'width:'.($graphwidth + PADDING*2).'px; height:'.BORDER.'px">' .
              '</div>';
        print '<div style="position:absolute; ' .
                          'top:'.(PADDING + ((Y_SCALE_HEIGHT*Y_POINTS) - $lineheight) - BORDER).'px; ' .
                          'left:'.($xwidth + PADDING*2).'px; ' .
                          'width:'.MAX_PAGES_WIDTH.'px; height:'.Y_TITLE_HEIGHT.'px; ' .
                          'xxxtext-align:right; ' .
                          'xxxpadding-right:'.PADDING.'px">' .
              $y_pages .
              '</div>';
    }

    print '</div>';
?>