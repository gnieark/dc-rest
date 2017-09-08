<?php
class RestQueryGetPost extends RestQuery
{

  public function __construct($args){
    global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    $post_id = $explodedArgs[2];
    $post = $core->blog->getPosts(array('post_id' => $post_id));
        
    if ($post->isEmpty()) {
      $this->response_code = 404;
      $this->response_message = array(
        'code'  => 404,
        'error' => 'POST '.$post_id.' does not exists'
      );
      return;
    }
    
     $fieldsKeys= $post->columns();
     
     $postArr = array();
     foreach($fieldsKeys as $key){
        $postArr[$key] = $post -> $key;
     }
     //les metas
     
     //getMetadata($params);
      $metas = array();
      $rs = $core->meta->getMetadata(array('post_id'  => $post_id));
      //('meta_id'  => $meta_id, 'meta_type'  => $meta_type, 'post_id'  => $post_id);
      while($rs->fetch()){
        $metas[] = array(
          'meta_id' => $rs->meta_id,
          'meta_type' => $rs->meta_type
        );
      }
      
      $postArr['metas'] = $metas;
      
      $this -> response_message = $postArr;
      $this -> response_code = 200;
  
      
  
  }
}