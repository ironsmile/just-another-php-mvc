<?php

class NoPageException extends Exception {
  public function __construct($message = null, $code = 0) {
    parent::__construct($message, $code);
  }
}

class InternelErrorException extends Exception {
  public function __construct($message = null, $code = 0) {
    parent::__construct($message, $code);
  }
}


class Controller{

  protected $CONFIGS = array( 'layout'=>'application', 
                            'use_layout'=>true, 
                            'custom_view'=>false, 
                            'already_rendered'=>false );
                            
  protected $content = "NO_VIEW_NOR_LAYOUT_RENDERED";
  
  protected $FILTERS = array( 'before'=>array(), 
                            'after'=>array(), 
                            'before_all' => array(), 
                            'after_all' => array() );
  //
  //  PUBLIC functions
  //
  
  public function execute(){
    try {
      ob_start();
    
      $action = $this->get_action();
      $this->invoke_filters("before", $action);
      
      if( in_array( $action, get_class_methods($this) ) ){ // method_exists and is_callable do not handle private and protected methods well
        $this->$action();
      } else { 
        throw new NoPageException("No action (`$action`) found! ");
      }
      
      $this->view = $view = APP_DIR.'/views/'.strtolower(preg_replace("/controller/i","",get_class($this))).'/'.(($this->CONFIGS['custom_view']) ? $this->CONFIGS['view'] : $action).'.php';
      
      if( $this->CONFIGS['use_layout'] ){
        $layout = APP_DIR.'/views/layouts/'.$this->get_current_layout().'.php';
        if( is_file( $layout ) )
          include_once( $layout );
        else {
          throw new InternelErrorException("Layout `{$layout}` is missing!");
        }
     } else {
      print $this->render_content();
     }
     
     $this->invoke_filters("after", $action); 
     ob_end_flush();
    } catch(DatabaseException $e){
      ob_end_clean();
      header("HTTP/1.1 500 Internal Server Error");
      print show_exception_trace($e, "SQL Exeception:\n");
    } catch (NoPageException $e){
      ob_end_clean();
      header("HTTP/1.1 400 Bad Request");
      print show_exception_trace($e, "Page not found!\n\nMessage: ");
    } catch(Exception $e) { // all exceptions
      ob_end_clean();
      header("HTTP/1.1 500 Internal Server Error");
      print show_exception_trace($e);
    }
  }
  
  //
  //  PROTECTED functions
  //
  
  protected function layout($name){
    if( !empty($name) ) $this->CONFIGS['layout'] = $name;
    if( $name === false or $name === true ) $this->CONFIGS['use_layout'] = $name;
  }
  
  protected function get_current_layout(){
    return $this->CONFIGS['layout'];
  }
  
  protected function render_view($vname){
    $this->CONFIGS["view"] = $vname;
    $this->CONFIGS["custom_view"] = true;
  }
  
  protected function render_text($text){
    $this->assert_not_already_rendered();
    $this->content = $text;
  }
  
  protected function assert_not_already_rendered(){
    if( $this->CONFIGS["already_rendered"] ){
      throw new InternelErrorException("You can render only once!");
    }
  }
  
  // invokes $filtes before all actions which are in the $actions array
  // if $actions is null or false or non-array apply the filter to all actions
  // $actions must be methods callable for the current object.
  // If any filter on the chain returns FALSE the rest won't be invoked
  // filters will be called by the order they are registered.
  // global filters (for all actions) are prioritisized
  protected function add_before_filter( $filters, $actions = null ){
    $this->add_filter("before", $filters, $actions);
  }
  
  // same as above except that it invokes the actions in question 
  protected function add_after_filter( $filters, $actions = null ){
    $this->add_filter("after", $filters, $actions);
  }
  
  //
  //  ignores previously defined $filters for $actions
  //
  protected function ignore_before_filter( $filters, $actions = null ){
    $this->ignore_filter("before", $filters, $actions);
  }
  
  protected function ignore_after_filter( $filters, $actions = null ){
    $this->ignore_filter("after", $filters, $actions);
  }
  
  protected function redirect_to($where){
    $this->CONFIGS["already_rendered"] = true;
    $this->CONFIGS["use_layout"] = false;
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: $where");
    exit();
  }
  
  protected function render_content(){
    if( ! $this->CONFIGS['already_rendered'] and is_file( $this->view ) ){ 
      include_once($this->view);
    } else {
      print $this->content;
    }
  }
  
  //
  //  variables in $vars ( 'variable_name' => <variable_value> ) will be visible in the partial
  //
  protected function partial( $partial, $vars = array() ){
    if( is_object( $partial ) ){ // makes possible calls like $this->partial( $event ); where $event is a model or whatever
      $obj = $partial;
      $cls = strtolower(get_class($partial));
      $partial = $cls."s/".$cls;
      $vars = array_merge( array( "{$cls}" => $obj ), $vars );
    }
    
    $_pfile = APP_DIR.'/views/';
    $partial = preg_split( "/\//", $partial, 2 );
    if( count( $partial ) > 1 ){
      $_pfile .= $partial[0]."/";
      $partial[0] = $partial[1];
    }
    else
      $_pfile .= strtolower(preg_replace("/controller/i","",get_class($this))).'/';
    
    $_pfile .= "_".$partial[0].".php";
    
    foreach( $vars as $key => $value )
      $$key = $value;
      
    $_ret = "";
    if( is_file($_pfile) ){
      ob_start();
        include( $_pfile );
        $_ret = ob_get_contents();
      ob_end_clean();
      return $_ret;
    }
    throw new InternelErrorException("Partial `{$_pfile}` is missing!");
  }
  
  //
  //  PRIVATE functions
  //
  
  private function get_action(){
    return (isset($_REQUEST['action']) and !empty($_REQUEST['action'])) ? $_REQUEST['action'] : 'index' ;
  }
  
  private function add_filter( $type, $filters, $actions = null ){
    if( !is_array($filters) ) $filters = array( $filters );
    if( $actions !== null and !is_array($actions) ) $actions = array( $actions );
    foreach( $filters as $filter ){
      if( $actions === null )
        $this->FILTERS["{$type}_all"][] = $filter;
      else foreach( $actions as $action ){
        if( !isset($this->FILTERS[$type][$action]) ) $this->FILTERS[$type][$action] = array();
        $this->FILTERS[$type][$action][] = $filter;
      }
    }
  }
  
  private function ignore_filter($type, $filters, $actions = null){
    if( !is_array($filters) ) $filters = array( $filters );
    if( $actions !== null and !is_array($actions) ) $actions = array( $actions );    
    foreach( $filters as $filter ){
      if( $actions === null ){
        $filter_key = array_search($filter, $this->FILTERS["{$type}_all"]);
        if($filter_key !== false){ unset($this->FILTERS["{$type}_all"][$filter_key]); }
      } else foreach( $actions as $action ){
        if( !isset($this->FILTERS[$type][$action]) ) continue;
        $filter_key = array_search($filter, $this->FILTERS[$type][$action]);
        if( $filter_key !== false ){ unset($this->FILTERS[$type][$action][$filter_key]); }
      }
    }
  }
  
  private function invoke_filters($type, $action){
    if( !isset($this->FILTERS[$type][$action]) ) $this->FILTERS[$type][$action] = array();
    foreach( array_merge($this->FILTERS["{$type}_all"], $this->FILTERS[$type][$action] ) as $filter ){
      $ret = $this->$filter();
      if( $ret === false ) return;
    }
  }

}
?>
