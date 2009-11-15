<?php
if ( !defined("_SQL_CLASS_LIB") ): // what about some C++ style :)
define("_SQL_CLASS_LIB",1);

class DatabaseException extends Exception {
  public function __construct($message = null, $code = 0) {
    parent::__construct($message, $code);
  }
}

class SQLinterface extends Singleton{

  protected $_SQLhost;
  protected $_SQLuser;
  protected $_SQLpass;
  protected $_SQLdatabase;
  protected $_link;
  public $query;
  public $affected_rows;
  public $rows_count;
  public $last_insert_id;
  public $conn_type;
  public $conn_info;
  
   protected function __construct(){
    $this->_SQLhost = SQL_DB_SERVER;
    $this->_SQLuser = SQL_DB_SERVER_USERNAME;
    $this->_SQLpass = SQL_DB_SERVER_PASSWORD;
    $this->_SQLdatabase = SQL_DB_DATABASE;
    $this->conn_info = "";
    $this->conn_type = "no connection";
    $this->_res = false;
    $this->connect();
    $this->exec_sql("SET NAMES '".DB_CONNECTION_ENCODING."' ;");
  }
  
  protected function exec_sql($query) {
    if( isset($query) && !empty($query) && $query )
      $this->query = $query;
  }
  
  protected function fetch_row() {}
  
  public function escape( $string ){
    switch( $this->conn_type ){
      case "mysql":
        return mysql_real_escape_string( $string, $this->_link );
      case "mysqli":
        return mysqli_real_escape_string($this->_link, $string );
      default:
        return addslashes($string);
    }
  }
  
  // prepares value for db input and adds slashesh when needed ( value becomes 'value' )
  public function input_value( $value ){
    return ( is_numeric($value) or in_array( strtolower($value), array(
              'now()', 'null', 'unix_timestamp()',
                ) )
          ) ? $value : "'".$this->escape($value)."'" ;
  }
};

//
//  My interface to the
//  mysql extension
//  just basic stuff for everyday use
//
class MysqlCon extends SQLinterface{
  
  protected function connect(){
    $flag = true;
    if ( $this->_link = mysql_connect($this->_SQLhost,$this->_SQLuser,$this->_SQLpass) ){
      if (!mysql_select_db( $this->_SQLdatabase, $this->_link ))
        throw new DatabaseException("Could not set database to `{$this->_SQLdatabase}`.\n".mysql_error());
    }
    else throw new DatabaseException("Could not connect to database with the supplied host/user/pass\n".mysql_error());
    
    //
    //  for security...
    //
    unset($this->_SQLpass);
    
    $this->conn_info = "MySQL -> " . mysql_get_host_info($this->_link);
    $this->conn_type = "mysql";
    
    return true;
  }
  
  public function exec_sql($query = false){
    parent::exec_sql( $query );
    if( !isset($this->query) or empty($this->query) or !$this->_link ){
      throw new DatabaseException("Trying to execute empty query or SQL object not connected.");
    }  
    $this->_res = mysql_query($this->query, $this->_link);
    $this->affected_rows = mysql_affected_rows( $this->_link );
    $this->rows_count = ($this->_res !== false and $this->_res !== true) ? mysql_num_rows( $this->_res ) : 0;
    $this->last_insert_id = mysql_insert_id( $this->_link );
    if ($this->_res){
      return true;
    } // else :  
    throw new DatabaseException("Error[".mysql_errno($this->_link)."]: ".mysql_error($this->_link)."\nQuery:\n\n{$this->query}");  
  }
  
  public function fetch_row(){
    if( !isset($this->_res) or !$this->_res ) {
      throw new DatabaseException("Fetching rows on empty resourse.\nDid you SELECT anything first?");
    }
    $this->row = mysql_fetch_assoc($this->_res);
    return $this->row;
  }
  
  public function __destruct(){
    mysql_close($this->_link);
  }
  
};

//
//  An other interface
//  This time for the mysqli
//
class MysqliCon extends SQLinterface{

  protected function connect(){
    $this->_link = new mysqli($this->_SQLhost,$this->_SQLuser,$this->_SQLpass,$this->_SQLdatabase);
    unset( $this->_SQLpass );
    if (mysqli_connect_errno()) {
      throw new DatabaseException("Error[".mysqli_connect_errno()."]: ".mysqli_connect_error());
    }
    $this->conn_info = "MySQLi -> " . $this->_link->host_info;
    $this->conn_type = "mysqli";
    
    return true;
  }
  
  public function exec_sql($query = false){
    parent::exec_sql( $query );
    if( !isset($this->query) or empty($this->query) or !$this->_link ){
      throw new DatabaseException("Trying to execute empty query or no db connection!");
    }
    if ( ! $this->_res = $this->_link->query($this->query) ){
      throw new DatabaseException("Error[{$this->_link->errno}]: {$this->_link->error}\nQuery:\n\n".$this->query);
    }
    $this->affected_rows = $this->_link->affected_rows;
    $this->rows_count = ($this->_res !== true) ? $this->_res->num_rows : 0 ;
    $this->last_insert_id = $this->_link->insert_id;
    return true;
  }
  
  public function fetch_row(){
    if( !isset($this->_res) or !$this->_res or !is_object($this->_res) ){
      throw new DatabaseException("Fetching rows on empty resourse.\nDid you SELECT anything first?");
    }
    $this->row = $this->_res->fetch_assoc();
    return $this->row;
  }
  
  public function __destruct(){
    $this->_link->close();
  }
  
};

function get_sql_object(){
  static $obj = null;
  if( $obj == null ) $obj = function_exists("mysqli_connect") ? MysqliCon::getInstance() : MysqlCon::getInstance() ;
  return $obj;
}

endif;
?>
