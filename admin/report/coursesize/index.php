<?php

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

/// This script may take some time to run so we need to extend the
/// PHP timeout value
set_time_limit(0);


$output = optional_param('output', 'html', PARAM_ALPHA); /// output type
$cat    = optional_param('cat', 0, PARAM_INT); /// include category names
$size   = optional_param('size', 'any', PARAM_ALPHA); /// display size type
$sort   = optional_param('sort', 'id_ASC', PARAM_TEXT);

/// Are we sorting the table by one of the columns?
switch ($sort) {
    case 'id_ASC':
    case 'id_DESC':
    case 'fullname_ASC':
    case 'fullname_DESC':
        $sortsql = str_replace('_', ' ', $sort);
        break;
    case 'size_ASC': /// special case, dealt with later
    case 'size_DESC': /// special case, dealt with later
    default:
        $sortsql = 'id ASC';
}

/// Get some strings
$strcourseid        = get_string('courseid', 'report_coursesize');
$strcoursename      = get_string('coursename', 'report_coursesize');
$strdisksize        = get_string('disksize', 'report_coursesize');
$strnothingtoreport = get_string('nothingtoreport', 'report_coursesize');
$strsite            = get_string('site', 'report_coursesize');
$strtotal           = get_string('total', 'report_coursesize');
$strdisplaysize     = get_string('displaysize', 'report_coursesize');
$strdisplaycategory = get_string('displaycategory', 'report_coursesize');
$strany             = get_string('any', 'report_coursesize');
$strgb              = get_string('sizegb');
$strmb              = get_string('sizemb');
$strkb              = get_string('sizekb');
$strb               = get_string('sizeb');
$stryes             = get_string('yes');
$strno              = get_string('no');

/// Size display options
$sizeoptions = array(
    'any' => $strany,
    'gb'  => $strgb,
    'mb'  => $strmb,
    'kb'  => $strkb,
    'b'   => $strb
);

/// Set the heading links
$catsize = '&size='.$size.'&cat='.$cat;
$hdrsort = ($sort == 'id_ASC') ? 'id_DESC' : 'id_ASC';
$hdrid   = '<a href="'.$ME.'?sort='.$hdrsort.$catsize.'">'.$strcourseid.'</a>';
$hdrsort = ($sort == 'fullname_ASC') ? 'fullname_DESC' : 'fullname_ASC';
$hdrname = '<a href="'.$ME.'?sort='.$hdrsort.$catsize.'">'.$strcoursename.'</a>';
$hdrsort = ($sort == 'size_ASC') ? 'size_DESC' : 'size_ASC';
$hdrsize = '<a href="'.$ME.'?sort='.$hdrsort.$catsize.'">'.$strdisksize.'</a>';

$sql = 'SELECT co.id, co.fullname, ct.name '.
       'FROM '.$CFG->prefix.'course co '.
       'LEFT JOIN '.$CFG->prefix.'course_categories ct '.
       'ON ct.id=co.category '.
       'ORDER BY co.'.$sortsql;

if ($courses = get_records_sql($sql)) {

    /// Set up table object
    $table = new stdclass;
    $table->head       = array($hdrid, $hdrname, $hdrsize);
    $table->size       = array('*', '*', '*');
    $table->align      = array('right', 'left', 'right');
    $table->width      = '80%';
    $table->data       = array();
    $table->tablealign = 'center';
    $table->rowclass   = array();

    $data  = array();
    $total = 0;

    foreach ($courses as $c) {

        if ($c->id == SITEID) {
            $c->fullname .= ' ('.$strsite.')';
        } elseif ($cat) {
            $c->fullname .= ' ('.$c->name.')';
        }

        $duraw = get_directory_size($CFG->dataroot.'/'.$c->id);
        $total += $duraw;
        $du = display_size($duraw);
        $courselink = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$c->id.'">'.$c->fullname.'</a>';
        $data[] = array($c->id, $courselink, $duraw);
        $table->rowclass[] = 'coursesize_data';

    }

    /// Sort the data by size if required
    if ($sort == 'size_ASC') {
        usort($data, 'coursesize_sort_by_size_asc');
    } elseif ($sort == 'size_DESC') {
        usort($data, 'coursesize_sort_by_size_desc');
    }

    /// Now we need to make the sizes human readable
    /// We can't do this earlier because the size sort won't work
    foreach ($data as $row) {
        $row[2] = coursesize_display_size($row[2], $size);
        $table->data[] = $row;
    }

    /// Add the total size
    $table->data[] = array('', $strtotal, coursesize_display_size($total, $size));
    $table->rowclass[] = 'coursesize_total';

}

/// Set up as one of the admin pages
admin_externalpage_setup('reportcoursesize');
admin_externalpage_print_header();

if (!empty($table)) {
    
    echo '<center>';

    /// Size display selector
    echo $strdisplaysize;
    $common = $ME.'?output='.$output.'&cat='.$cat.'&sort='.$sort.'&size=';
    popup_form($common, $sizeoptions, 'sizedisplayform', $size);

    echo '&nbsp;&nbsp;&nbsp;';

    /// Category name display
    echo $strdisplaycategory;
    $common = $ME.'?output='.$output.'&sort='.$sort.'&size='.$size.'&cat=';
    popup_form($common, array(0 => $strno, 1 => $stryes), 'categorydisplayform', $cat);

    print_table($table);

    echo '</center>';

} else {
    echo $strnothingtoreport;
}

admin_externalpage_print_footer();




/**
 * coursesize_sort_by_size_asc()
 * Callback function to sort 2 rows of the tables object by comparing the size column
 * Sort is done in ascending order
 *
 * @param array $a  a single row from the table object
 * @param array $b  a single row from the table object
 * @param boolean $sortascending  sort by ascending values or descending
 * @return integer
 */
function coursesize_sort_by_size_asc($a, $b) {
    
    /// A sanity check
    if (!isset($a[2]) or !isset($b[2])) {
        return 0;
    }

    if ($a[2] == $b[2]) {
        return 0;
    }

    return ($a[2] < $b[2]) ? -1 : 1;

}

/**
 * coursesize_sort_by_size_desc()
 * Callback function to sort 2 rows of the tables object by comparing the size column
 * Sort is done in descending order
 *
 * @param array $a  a single row from the table object
 * @param array $b  a single row from the table object
 * @param boolean $sortascending  sort by ascending values or descending
 * @return integer
 */
function coursesize_sort_by_size_desc($a, $b) {
    
    /// A sanity check
    if (!isset($a[2]) or !isset($b[2])) {
        return 0;
    }

    if ($a[2] == $b[2]) {
        return 0;
    }

    return ($a[2] < $b[2]) ? 1 : -1;

}

/**
 * Converts bytes into display form.
 * Extends core function display_size.
 *
 * @param string $size
 * @param string $type  preferred size suffix
 * @return string
 * @staticvar string $gb Localized string for size in gigabytes
 * @staticvar string $mb Localized string for size in megabytes
 * @staticvar string $kb Localized string for size in kilobytes
 * @staticvar string $b Localized string for size in bytes
 * @todo Finish documenting this function. Verify return type.
 */
function coursesize_display_size($size, $type='any') {

    static $gb, $mb, $kb, $b;

    if (empty($gb)) {
        $gb = get_string('sizegb');
        $mb = get_string('sizemb');
        $kb = get_string('sizekb');
        $b  = get_string('sizeb');
    }

    switch ($type) {
        case 'gb':
            $size = round($size / 1073741824 * 10) / 10 . $gb;
            break;
        case 'mb':
            $size = round($size / 1048576 * 10) / 10 . $mb;
            break;
        case 'kb':
            $size = round($size / 1024 * 10) / 10 . $kb;
            break;
        case 'b':
            /// do nothng - it should already be in bytes
            break;
        case 'any':
        default:

            if ($size >= 1073741824) {
                $size = round($size / 1073741824 * 10) / 10 . $gb;
            } else if ($size >= 1048576) {
                $size = round($size / 1048576 * 10) / 10 . $mb;
            } else if ($size >= 1024) {
                $size = round($size / 1024 * 10) / 10 . $kb;
            } else {
                $size = $size .' '. $b;
            }
    }

    return $size;
}




?>
