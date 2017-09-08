<?php
class RestQueryDeletePostMeta extends RestQuery
{
  public function __construct($args){
    global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    //preg_match('/^(.*)\/(.*)\/meta\/(.*)$/', $args ))
    $post_id = $explodedArgs[1];
    
    if(isset($explodedArgs[3])) {    
      $meta_id = $explodedArgs[3]; 
    }else {
      $meta_id = null;  
    }   
    $core->blog = new dcBlog($core, $this->blog_id);
    if(!$core->blog->id){
      //Le blog n'existe pas
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$blog_id.' not found');
      return;      
    }
    
    $core->meta->delPostMeta($post_id,null,$meta_id);


    
    $this->response_code = 200;
    $this->response_message = array(
      'code'  => 200,
      'message' => 'successfully remove meta'
    );
  }
}
