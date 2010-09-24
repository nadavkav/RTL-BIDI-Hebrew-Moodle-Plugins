<?php
require_once(dirname(__FILE__).'/../../config.php');
global $CFG;
require_once($CFG->dirroot. '/lib/graphlib.php');

    $roles=required_param('roles',PARAM_RAW);
    $edited=required_param('edited',PARAM_RAW);
    $pages=required_param('pages',PARAM_RAW);
    
    $graph_title = "";
    $graph_x_data = explode(',', $roles);
    $graph_y_data2 = explode(',', $edited);
    $graph_y_data1 = explode(',', $pages);
     
    // init graph to size
    $graph = new graph(500,250);
     
     //fonts
     /// this line doesn't work due to the $graph->init() function which resets the variable
     ///$graph->parameter['path_to_fonts']=$CFG->dirroot. '/lang/en_oc_utf8/fonts/';  
     $graph->parameter['title_font']="default.ttf";
     $graph->parameter['label_font']="default.ttf";
     $graph->parameter['axis_font']="default.ttf";
     $graph->parameter['legend_font']="default.ttf";
     
     
         
     //The format of the image file it produces.  This can be PNG, JPEG or GIF
     $graph->parameter['output_format']="PNG";
     
     // The title that apppears at the top of the image
     $graph->parameter['title']=$graph_title;

     // bar size and spacing
     $graph->parameter['bar_size']=2;
     $graph->parameter['bar_spacing']=150;
     
     
     // Number of horizonal gridlines
     ///$graph->parameter['y_axis_gridlines'] = max(max($graph_y_data1), max($graph_y_data2));
     
     // The label on the y axis
     $graph->parameter['y_label_left'] = 'Pages';

     // The label on the x axis
     $graph->parameter['x_label'] = '';
     
     // The angle of the text.  Default is 90degrees which puts in verically, 0 is horizontal and looks better most o fthe time.
     $graph->parameter['x_label_angle'] = '0';
     
     // The angle of the text on the x axis.  Change the angle depending on how much text you have.  Too much text and it will overlap if you leave to at 0
     $graph->parameter['x_axis_angle']     = 0;
     
     $graph->parameter['y_resolution_left']= 1;
     
     $graph->parameter['y_decimal_left']   = 0;
          
     
     $graph->parameter['shadow'] = 'green';
     $graph->parameter['shadow_below_axis'] = false;
     
     $graph->y_tick_labels = null;
     
     $graph->offset_relation = null;
     
     // Styling for the ledgend
     $graph->parameter['legend']        = 'outside-left';
     $graph->parameter['legend_border'] = 'black';
     $graph->parameter['legend_offset'] = 4;

    // The data on the y axis, eg 2 draws a bar 2 high.  For each $graph->y_data you have you will get that number of of different bars
    $graph->y_data['bar1'] = $graph_y_data1;
    $graph->y_data['bar2'] = $graph_y_data2;
    
    // The data for the x axis, must match the same nuimber of array elemnts as y_data
   	$graph->x_data = $graph_x_data;

	// The order in which the bars will be displayed.  
	$graph->y_order = array('bar1','bar2');  
	
	
	// The format of each bar, you must have one entry for each bar listed under y_data or you will get an error.
	$graph->y_format['bar1'] = array('colour' => 'oumoodlegray', 'bar' => 'open', 'shadow_offset' => 0.1, 'legend' => 'All', 'bar_size' => $graph->parameter['bar_size']);
	$graph->y_format['bar2'] = array('colour' => 'oumoodleblue', 'bar' => 'fill', 'shadow_offset' => 0.1, 'legend' => 'Edited', 'bar_size' => $graph->parameter['bar_size']);

	///error_reporting(5); // ignore most warnings such as font problems etc    
	
	// Draw the graph
    $graph->draw();
 
?>