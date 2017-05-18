<?php

class RestQueryGetBlogs extends RestQuery
{
  public function __construct(){
  
    global $core;
    $this->blog_id = false; //this method doesn't depend on a blog_id
    $this->required_perms = 'none'; //I want user have an account 
    
    if($this->is_allowed() === false){
      return;
    }   
    //list the blogs the user can access
    $blgs = $core->auth->getAllPermissions();
    $ret = array();
    foreach($blgs as $key=>$value){
      $ret[] = $key;
    }
    $this->response_code = 200;
    $this->response_message = $ret;
  }
}