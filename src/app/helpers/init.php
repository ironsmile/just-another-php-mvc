<?php
  
  if(!defined("APP_DIR")) define("APP_DIR", realpath(dirname(__FILE__)) );
  if(!defined("HELPERS_DIR")) define("HELPERS_DIR",APP_DIR."/helpers");
  include_once(HELPERS_DIR."/application.php"); // loads the application helpers in all controllers
  $specific_helper = HELPERS_DIR."/".strtolower(basename($_SERVER['PHP_SELF'])); // any controller may have specific helper file for itself
  if( is_file(basename($specific_helper)) and is_readable($specific_helper) ) include_once($specific_helper);
?>
