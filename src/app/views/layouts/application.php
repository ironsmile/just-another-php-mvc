<!DOCTYPE html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php // TITLE!
    if( isset($this->page_title) )
      print $this->page_title;
    else {
      print preg_replace("/controller/i", "", get_class($this));
      if( isset( $_GET['action'] ) ) print " :: " . $_GET['action'];
    } 
  ?></title>
  <base href="<?= SITE_ROOT ?>">
  <?= css_include_tag("main") ?>
  <?= javascript_include_tag(JS_DEFAULT) ?>
</head>
<body>
  
  <div style="margin:10px;">
    <?php
      if( isset($_SESSION['flash']) and !empty($_SESSION['flash']) ){
        print '<div id="flash_message">'.$_SESSION['flash'].'</div>';
        unset($_SESSION['flash']);
      } 
    ?>
    <?= $this->render_content(); ?>  
  <div>
  
</body>
</html>
