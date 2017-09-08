<?php
class RestQueryGetPostMetas extends RestQuery
{

  public function __construct($args){
    global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    
    $core->blog = new dcBlog($core, $this->blog_id);
    
    if(!$core->blog->id){
      //Le blog n'existe pas
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$blog_id.' not found');
      return;      
    }
    
    $this->response_message = RestQueryGetMetas::getMetas(array('post_id'=>$explodedArgs[1]));
    $this->response_code = 200;
  
  
  }


}