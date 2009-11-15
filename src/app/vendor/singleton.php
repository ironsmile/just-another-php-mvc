<?php
/*
  Usage:
  
  class DatabaseConnection extends Singleton {

    protected $connection;

    protected function __construct() {
        // @todo Connect to the database
    }

    public function __destruct() {
        // @todo Drop the connection to the database
    }
  }
  
  $oDbConn = new DatabaseConnection();  // Fatal error
  
  $oDbConn = DatabaseConnection::getInstance();  // Returns single instance

  //
  //  Notice: Not used at this point due to some bugs :/
  //
*/

if(!function_exists('get_called_class')) {
    class class_tools {
        static $i = 0;
        static $fl = null;
       
        static function get_called() {
            $bt = debug_backtrace();
           
            if(self::$fl == $bt[2]['file'].$bt[2]['line']) {
                self::$i++;
            } else {
                self::$i = 0;
                self::$fl = $bt[2]['file'].$bt[2]['line'];
            }
           
            $lines = file($bt[2]['file']);
           
            preg_match_all('/([a-zA-Z0-9\_]+)::'.$bt[2]['function'].'/',
                $lines[$bt[2]['line']-1],
                $matches);
           
            return $matches[1][self::$i];
        }
    }

    function get_called_class() {
        return class_tools::get_called();
    }
}

abstract class Singleton {

    protected function __construct() {
    }

    final public static function getInstance() {
        static $aoInstance = array();

        $calledClassName = get_called_class();

        if (! isset ($aoInstance[$calledClassName])) {
            $aoInstance[$calledClassName] = new $calledClassName();
        }

        return $aoInstance[$calledClassName];
    }

    final private function __clone() {
    }
}

?>
