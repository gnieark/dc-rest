<?php
class RestQueryGetBlogSettings extends RestQuery
{

  public function __construct($args)
  {
    global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    //check if user is allowed
    $this->required_perms = 'admin';
    if($this->is_allowed() === false){
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
      
      if(isset($explodedArgs[3])){
        if(isset($settings[$explodedArgs[2]][$explodedArgs[3]])){
          $this->response_message =  $settings[$explodedArgs[2]][$explodedArgs[3]];
        }else{
            $this->response_code = 404;
            $this->response_message = array('code' => 404, 'error' => 'Namespace or setting not found');     
        }
      }elseif(isset($explodedArgs[2])){
        if(isset($settings[$explodedArgs[2]])){
            $this->response_message =  $settings[$explodedArgs[2]];
        }else{
            $this->response_code = 404;
            $this->response_message = array('code' => 404, 'error' => 'Namespace found');     
        }  
      }else{
        $this->response_message =  $settings;
      }
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