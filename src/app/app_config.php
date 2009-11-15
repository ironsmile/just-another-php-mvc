<?php
/**
*
* This is included in the beginnig of the
* Application. Everything should see it
*
*/

define("DEV_ENVIROMENT", true);

define('SITE_ROOT', (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']) );
// You can force your root path:
// define('SITE_ROOT', 'http://www.example.com');
define("VENDOR_DIR", APP_DIR."/vendor");
define("HELPERS_DIR", APP_DIR."/helpers");
define("PLUGINS_DIR", APP_DIR."/plugins");
define("INCLUDE_JS_DIR", SITE_ROOT."/js");
define("INCLUDE_CSS_DIR", SITE_ROOT."/css");

define('DB_CONNECTION_ENCODING', "utf8");
// Database settings
// Edit to your own
if(DEV_ENVIROMENT){
  // development
  define('SQL_DB_SERVER', "127.0.0.1");
  define('SQL_DB_SERVER_USERNAME', "my_db_user");
  define('SQL_DB_SERVER_PASSWORD', "my_db_pass");
  define('SQL_DB_DATABASE', "my_db_name");
} else {
  // production
  define('SQL_DB_SERVER', "somehost");
  define('SQL_DB_SERVER_USERNAME', "prod_db_user");
  define('SQL_DB_SERVER_PASSWORD', "prod_db_pass");
  define('SQL_DB_DATABASE', "prod_db_name");
}

$error_reporting = DEV_ENVIROMENT ? E_ALL : 0;

error_reporting($error_reporting);

?>
