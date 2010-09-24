<?php
    // Pick up graph data
    $roles = $param->roles;
    $edits = $param->edits;
    $comments = $param->comments;

    // Remove roles=/edits=/comments= at start of these strings
    // as no longer URL parameters
    if (($idx = strpos($roles, '=')) !== false) {
        $roles = substr($roles, ++$idx);
    }
    if (($idx = strpos($edits, '=')) !== false) {
        $edits = substr($edits, ++$idx);
    }
    if (($idx = strpos($comments, '=')) !== false) {
        $comments = substr($comments, ++$idx);
    }

    // Convert graph data to arrays
    $x_titles = explode(',', $roles);
    $y_data1 = explode(',', $edits);
    $y_data2 = explode(',', $comments);

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

    // Display graph container (all measurements in pixels)
    $graphwidth = X_SCALE_WIDTH*$x_points;
    $xwidth = Y_TITLE_WIDTH + $graphwidth;
    $yheight = X_TITLE_HEIGHT + (Y_SCALE_HEIGHT*Y_POINTS);
    print '<div style="position:relative; ' .
                      'padding:'.PADDING.'px; ' .
                      'width:'.$xwidth.'px; height:'.$yheight.'px; ' .
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
                          'left:'.round(PADDING + Y_TITLE_WIDTH + (X_SCALE_WIDTH/6) + (X_SCALE_WIDTH*$idx)).'px; ' .
                          'width:'.$barwidth.'px; height:'.$barheight.'px; ' .
                          'font-size:0px;"></div>';

        $barheight = round((Y_SCALE_HEIGHT*Y_POINTS*$y_data2[$idx])/$y_max);
        print '<div class="ouw_bargraph2'.($barheight==0 ? ' ouw_zero':'').'" ' .
                   'style="position:absolute; ' .
                          'top:'.(PADDING + ((Y_SCALE_HEIGHT*Y_POINTS) - $barheight) - BORDER).'px; ' .
                          'left:'.round(PADDING + Y_TITLE_WIDTH + (X_SCALE_WIDTH/2) + (X_SCALE_WIDTH*$idx) + BORDER).'px; ' .
                          'width:'.$barwidth.'px; height:'.$barheight.'px; ' .
                          'font-size:0px;"> ' .
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

    print '</div>';
?>