<?php

class RestQueryGetBlogs extends RestQuery
{
  public function __construct(){
  
    global $core;
    $this->blog_id = false; //this method doesn't depend on a bolg_id
    $this->required_perms = 'none'; //I want user have an account 
    
    if($this->is_allowed() === false){
      //need To be authentified
      $this->response_code = 403;
      $this->response_message = array('code' => 403, 'error' => 'get Blogs methods requires to be authentified');
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