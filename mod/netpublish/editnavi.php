<?php // $Id: editnavi.php,v 1.2 2006/03/24 19:46:43 janne Exp $

    if (! defined('MOODLE_INTERNAL') ) die ("You cannot view this page by itself!");

?>
<p style="text-align: center"><?php
if ($isteacher) {
    ?>
[ <a href="sections.php?id=<?php p($cm->id) ?>"><?php print_string("managesections","netpublish");?></a> |
    <?php
} else {
    echo '[ ';
}
    ?>
<a href="addarticle.php?id=<?php echo $cm->id ?>"><?php print_string("addnewarticle","netpublish");?></a> |
 <a href="drafts.php?id=<?php echo $cm->id ?>"><?php print_string("pendingarticles","netpublish");?> (<?php echo $pending; ?>)</a>
<?php
if (! empty($mod->scale) && $mod->scale != 0 && $isteacher ) {
    ?>
 | <a href="grades.php?id=<?php p($cm->id) ?>&amp;sesskey=<?php p($USER->sesskey) ?>"><?php print_string("grades") ?></a>
    <?php
}

if (isteacheredit($course->id) and $canbepublished) {
    $stroutpublished = empty($mod->published) ? get_string('outpublish','netpublish') : get_string('outunpublish','netpublish');
        ?>
 | <a href="outpublish.php?id=<?php p($cm->id) ?>&amp;sesskey=<?php p($USER->sesskey)?>"><?php echo $stroutpublished ?></a> ]
        <?php
} else {
    echo " ]";
}
?></p>