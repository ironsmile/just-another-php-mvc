<?php
  //
  // Includes all init scripts for all
  // plugin directories
  //
  $mdh = dir(PLUGINS_DIR);
  while( false !== ($plugin_path = $mdh->read()) ){
    $mpath = $mdh->path."/".$plugin_path;
    if(!is_dir($mpath) or "." == $plugin_path or ".." == $plugin_path ) continue;
    $pfile = $mpath."/init.php";
    if(is_readable($pfile)) include_once($pfile);
  }
?>
