<?php

include_once('../app/boot.php');

class IndexController extends ApplicationController{
  
  public function index(){

  }
  
  public function about(){
    $this->our_name = "Dummy";
  }
  
}

$cnt = new IndexController();
$cnt->execute();

?>
