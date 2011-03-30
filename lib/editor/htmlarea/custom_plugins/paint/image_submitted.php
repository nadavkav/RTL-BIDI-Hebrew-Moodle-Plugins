<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Image was saved successfuly</title>

    <script>
    function submit_image(){
      var param = new Object();
      param['f_url'] = '<?php echo "{$_GET['image']}"; ?>';
      //alert(param['f_url']);
      parent.window.opener.nbWin.retFunc(param);
      parent.window.close();
      return false;
    }
    </script>

  </head>

  <body onload="submit_image()">
      Closing window...
  </body>

</html>