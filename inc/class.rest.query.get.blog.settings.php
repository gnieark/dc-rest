<?php
class RestQueryGetBlogSettings extends RestQuery
{

  public function __construct($args)
  {
    global $core;
    
    
    $this->blog_id = explode("/",$args)[0];
    //check if user is allowed
    $this->required_perms = 'admin';
    if($this->is_allowed() === false){
      $this->response_code = 403;
      $this->response_message = array('code' => 403, 'error' => 'No enough privileges');
      return;
    } 

    $core->blog = new dcBlog($core, $this->blog_id);
    $blog_settings = new dcSettings($core,$this->blog_id);
    
    if(!$core->blog->id){
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$this -> blog_id.' not found');
      return;
    }
    try{
      $settings = array();
      foreach ($core->blog->settings->dumpNamespaces() as $ns => $namespace) {
        foreach ($namespace->dumpSettings() as $k => $v) {
          $settings[$ns][$k] = $v;
        }
      }
      $this->response_code = 200;
      $this->response_message =  $settings;
    }catch (Exception $e){
      $this->response_code = 500;
      $this->response_message = array(
        'code'      => 500,
        'message'   => $e->getMessage()
      );
    }
    
     return; 
  }
  
    
}