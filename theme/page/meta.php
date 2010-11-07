<!--[if IE 8]>
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->httpswwwroot ?>/theme/page/styles_ie8.css" />
<![endif]-->
<!--[if IE 7]>
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->httpswwwroot ?>/theme/page/styles_ie7.css" />
<![endif]-->
<!--[if IE 6]>
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->httpswwwroot ?>/theme/page/styles_ie6.css" />
<![endif]-->

<?php if (page_check_client('Firefox', 2, 'Macintosh') or page_check_client('Firefox', 3, 'Windows')) { ?>
<style type="text/css" media="screen">
    span.button {
        padding-top: 8px;
    }
    span.button a {
        padding-top: 7px;
    }
</style>
<?php } ?>