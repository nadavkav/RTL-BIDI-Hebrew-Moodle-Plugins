    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot ?>/lib/yui/menu/assets/menu-core.css" /> 
    <script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/lib/yui/yahoo-dom-event/yahoo-dom-event.js"></script>
    <script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/lib/yui/container/container_core.js"></script>
    <script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/lib/yui/menu/menu.js"></script>
    <script type="text/javascript">
      YAHOO.util.Event.onContentReady("dropdown", function () {
        var oMenuBar = new YAHOO.widget.MenuBar("dropdown", { 
          autosubmenudisplay: true, 
          hidedelay: 750, 
          lazyload: true });
        oMenuBar.render();
      });
    </script>
