<?php

class ResQueryPatchMeta extends RestQuery
{

  public function __construct($args,$body){
    global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    $post_id = $explodedArgs[1];
    $meta_id = $explodedArgs[3];
    
    $this->required_perms = 'none'; //To do

    if($core->auth === false){
      $core->auth = new restAuth($core);
      $unauth = true;
    }
    $core->blog = new dcBlog($core, $this->blog_id);
    
    if(!$core->blog->id){
      //Le blog n'existe pas
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$blog_id.' not found');
      return;      
    }do
    
    $blog_settings = new dcSettings($core,$this->blog_id);
    
    if($this->is_allowed() === false){
      
      return;
    }
   
    $clientQueryArr = json_decode($body, true);
    
    //memorise the old one attributes before delete it
    $oldMeta = RestQueryGetMetas::getMetas(
      array(
        'post_id' => $post_id,
        'meta_id'  => $meta_id      
      )
    );
    
    if(count($oldMeta) == 0){
      //error 404
      $this -> response_code = 404;
      $this -> response_message = array(
        'code'  => 404,
        'message' =>'No existing meta with this post_id and this meta_id'
      );
      return
    }
    
    
    
    
    //delete the old meta    
    $r = $core->meta->delPostMeta($post_id,null,$meta_id);

    //create the new one
    if(isset($clientQueryArr['post_id'])){
      $new_post_id = $clientQueryArr['post_id'];  
    }else {
      $new_post_id = $oldMeta[0]['post_id']; 
    }
    if(isset($clientQueryArr['meta_id'])){
      $new_post_id = $clientQueryArr['meta_id'];  
    }else {
      $new_post_id = $oldMeta[0]['meta_id']; 
    }
    if(isset($clientQueryArr['meta_type'])){
      $new_post_id = $clientQueryArr['meta_type'];  
    }else {
      $new_post_id = $oldMeta[0]['meta_type']; 
    }
    
    
  
  
  }

}