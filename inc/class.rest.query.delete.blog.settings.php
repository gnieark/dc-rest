<?php
class RestQueryDeleteBlogSettings extends RestQuery
{

  public function __construct($args){
    global $core;
    $explodedArgs = explode("/",$args);
    $nameSpace = $explodedArgs[2];
    $this->blog_id = $explodedArgs[0];
    
    
    if($core->auth === false){
      $core->auth = new restAuth($core); //class dcBlog need it
      $unauth = true;
    }
    $this->required_perms = 'admin'; 
    
    //Is allowed?
    if($this->is_allowed() === false){
      //need To be authentified
      return;
    }
    
    //does the blog exists?
    $core->blog = new dcBlog($core, $this->blog_id);
    $blog_settings = new dcSettings($core,$this->blog_id);
    
    if(!$core->blog->id){
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$this -> blog_id.' not found');
      return;
    }
    
    //Delete namespace or just a setting?
    $nS = new restDcNameSpace($core, $this->blog_id,$explodedArgs[2]);
    
    if(isset($explodedArgs[3])){
      if($nS->settingExists($explodedArgs[3])){
        $nS->drop($explodedArgs[3]);
        //OK
        $this->response_code = 200;
        $this->response_message = array('code' => 200, 'message' => 'Setting '.$explodedArgs[3].' deleted.');
        return;
      }else{ 
        $this->response_code = 404;
        $this->response_message = array('code' => 404, 'error' => 'Setting '.$explodedArgs[3].' not found');
        return;
      }
    }else{
      //delete nameSpace
      $core->blog->settings->delNamespace($explodedArgs[2]);
      $this->response_code = 200;
      $this->response_message = array('code' => 200, 'message' => 'NameSpace '.$explodedArgs[2].' deleted.');
      return;   
    }  
  }
}