<?php

/*
*
* if your model has `created_at` and/or `updated_at` fields
* you can make them be set/updated automatically by placing
  
  protected $timestamps = true; 

* in its variables. These fields must be of type int
*
*
* CALLBACKS:
* 
* on_create - initiated after the model is saved to the database for the first time
* on_update - initiated after every call on "save" on model that already exists in the db
* on_delete - initiated after a successful deletion from the db
*
*/


class BasicModel{
  
  protected $to_update_fields = array();  // those needing an update. Will be updated on save()
  protected $db_fields_values = array(); // initial values. They are synched with those in the database itself (at least I hope so ^^)
  protected $_id = null;
  
  // Inhariting classes MUST have those 3 defined
  protected $db_fields = array();
  protected $db_table = "";
  protected $primary_key = "";
  // Optional:
  protected $timestamps = false;
  
  public function __construct( $fields = array() ){
    if( is_array($fields) ){ // New model. Not saved to the db yet
      $this->init_from_array($fields);
    } else { // so we have a db record to load
      $id = $fields;
      $sql = get_sql_object();
      
      $sql->exec_sql("SELECT `".implode("`, `", $this->db_fields)."` FROM `".$this->db_table."`
                      WHERE `".$this->primary_key."` = ".$sql->input_value($id)." ;");
//       throw new Exception($sql->query);
      $row = $sql->fetch_row();
      if( is_array($row) ){
        $this->init_from_array( $row );
        $this->_id = $id;
      }
    }
  }
  
  protected function _exec_callback( $method ){
    if( method_exists($this, $method) ){ $this->$method(); }
  }
  
  protected function init_from_array( $fields ){
    foreach( $fields as $field => $value ){
      if( !in_array($field,$this->db_fields) ) throw new Exception("No field `$field` in `".$this->db_table."`");
      $this->db_fields_values[$field] = $value;
    }
  }
  
  //
  //  makes possible setting a fields that have names conflicting with object methods eg. "save"
  //  $obj->set_vield("save", "some stuff here");
  //
  public function set_field($name, $value){
    if( !in_array($name,$this->db_fields) ) throw new Exception("No field `$name` in `".$this->db_table."`");
    $this->to_update_fields[$name] = $value;
    return $this;
  }
  
  //
  //  getting a field with a name conficting with a object method
  //  $obj->get_field( "update" );
  //  or even
  //  $obj->get_field( "get_field" ); // :)))
  //
  public function get_field($name){
    if( !in_array($name,$this->db_fields) ) throw new Exception("No field `$name` in `".$this->db_table."`");
    $v = isset($this->to_update_fields[$name]) ? $this->to_update_fields[$name] : "" ;
    return ( $v != "0" and empty($v) and isset($this->db_fields_values[$name]) ) ? $this->db_fields_values[$name] : $v ;
  }
  
  public function __set($name,$value){
    if( $name == "id" ){ $this->_id = $value; } else { $this->set_field($name,$value); }
    return $value;
  }
  
  public function __get($name){
    if( $name == "id" ) return $this->_id;
    return $this->get_field($name);
  }
  
  public function __isset($name) {
    return isset($this->to_update_fields[$name]) or isset($this->db_fields_values[$name]);
  }
  
  public function __unset($name){
    unset($this->to_update_fields[$name]);
  }
  
  
  //
  //  instead of calling $obj->field1 = value1; $obj->field2 = value2; ... $obj->fieldn = valuen; you can use:
  //  $obj->update( array( field1=>value1, field2=>value2, ... fieldn=>valuen ) );
  //
  public function update( $array ){
    foreach( $array as $key => $value ) $this->set_field($key, $value);
    return $this;
  }
  
  //
  //  Inserts/updates the database row
  //
  public function save(){
    $sql = get_sql_object();
    $pk = $this->primary_key;
    $sql->exec_sql("SELECT `".$pk."` FROM `".$this->db_table."` WHERE `".$pk."` = ".$sql->input_value($this->_id)." ;");
    if (!empty($pk) and $sql->fetch_row()){
      $ret = $this->update_database();
      $callback = "on_update";
    } else {
      $ret = $this->insert_into_database() ;
      $callback = "on_create";
    }
    $this->_exec_callback($callback);
    return $ret;
  }
  
  //
  //  Removes the row for that current model from the database
  //
  public function delete(){
    if( $this->_id == null ) return false;
    $sql = get_sql_object();
    $ret = $sql->exec_sql("DELETE FROM `".$this->db_table."` WHERE `".$this->primary_key."` = ".$sql->input_value($this->_id)." ;");
    if( $ret ){ $this->_exec_callback("on_delete"); return true;  } else { return false; }
  }
  
  protected function update_database(){
    $sql = get_sql_object();
    if( $this->timestamps and in_array('updated_at', $this->db_fields) ){
      $this->updated_at = time();
    }
    $updated = array();
    foreach( $this->to_update_fields as $fname => $fvalue ){
      $updated[] = "`{$fname}`='".$sql->escape($fvalue)."'";
      $this->db_fields_values[$fname] = $fvalue;
    }
    $sql->exec_sql("UPDATE `".$this->db_table."` SET ".implode(", ",$updated)." 
                    WHERE `".$this->primary_key."` = ".$sql->input_value($this->_id)." ;");
    $this->to_update_fields = array();
    return $this;
  }
  
  protected function insert_into_database(){
    $sql = get_sql_object();
    if( $this->timestamps and in_array('created_at', $this->db_fields) ){
      $this->created_at = time();
    }
    $vals = array_merge( $this->db_fields_values, $this->to_update_fields );
    $this->db_fields_values = $vals;
    $this->to_update_fields = array();
    foreach( $vals as $key => $value ) $vals[$key] = $sql->input_value($value);
    $sql->exec_sql("INSERT INTO `".$this->db_table."` (`".implode("`, `", array_keys($vals))."`) 
                    VALUES (".implode(", ", array_values($vals)).") ;");
    $this->_id = $sql->last_insert_id;
    return $this;
  }
  
  //
  //  Static Methods:
  //  
  //  Hide in the corner in fear!
  //  

  //
  //  should be used this way:
  //  
  //  class Comment extends BasicModel{
  //  ...
  //  }
  //  $comments = Comment::find("Comment", "user_id = 5 AND 4 = 2*2");
  //  
  //  comments will be array
  //  
  public static function find( $model_class, $clauses = "" ){ // $model_class is case sensetive!
    $sql = get_sql_object();
    
    $dummy = new $model_class(); //Аугх :? Don't try this at home!
    $pk = $dummy->primary_key;
    
    if( is_array($clauses) ){
      $where_clause = isset($clauses['where']) ? $clauses['where'] : "";
      $order = isset($clauses['order']) ? $clauses['order'] : "";
      if( isset($clauses['order by']) and !empty($clauses['order by']) ) $order = $clauses['order by'];
      $limit = isset($clauses['limit']) ? $clauses['limit'] : "";
      $group_by = isset($clauses['group']) ? $clauses['group'] : "";
      if( isset($clauses['group by']) and !empty($clauses['group by']) ) $group_by = $clauses['group by'];
    } else {
      $group_by = $limit = $order = "";
      $where_clause = $clauses;
    }
    
    $ids = array();
    $sql->exec_sql("SELECT `{$pk}` FROM `".$dummy->db_table."`".(empty($where_clause)?"":" WHERE {$where_clause}").
                    (empty($group_by)?"":" GROUP BY {$group_by}").(empty($order)?"":" ORDER BY {$order}").(empty($limit)?"":" LIMIT {$limit}")." ; ");
                    
    while( $row = $sql->fetch_row() ){ $ids[] = $row[$pk]; }
    $ret = array();
    foreach( $ids as $id ){ $ret[] = new $model_class($id); }
    return $ret;
  }
  
  
};




?>
