<?php
  
  if( !defined("VENDOR_DIR") ) define("VENDOR_DIR", dirname(__FILE__));
  
  include_once(VENDOR_DIR."/core_functions.php");
  include_once(VENDOR_DIR."/singleton.php");
  include_once(VENDOR_DIR."/sqlclass.php");
  include_once(VENDOR_DIR."/controller.php");
  include_once(VENDOR_DIR."/model.php");
  
  define( "MODELS_DIR", realpath(VENDOR_DIR."/../models/") );
  
  $mdh = dir(MODELS_DIR);
  while( false !== ($model = $mdh->read()) ){
    $mpath = $mdh->path."/".$model;
    if( is_file($mpath) and is_readable($mpath) and preg_match('/\.php$/',$mpath) ) include_once($mpath);
  }
?>
