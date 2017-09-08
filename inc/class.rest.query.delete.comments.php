<?php
class RestQueryDeleteComments extends RestQuery
{
  public function __construct($args,$body){
     global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    
    $this->required_perms = 'none'; //To do
    
    if($core->auth === false){
      $core->auth = new restAuth($core);
      $unauth = true;
    }
    if($this->is_allowed() === false){
      return;
    }
    
    $core->blog = new dcBlog($core, $this->blog_id);
    if(!$core->blog->id){
      $this->is404('Resource '.$blog_id.' not found');
      return;      
    }
    
    if(isset($explodedArgs[2])){
      $listToDelete = array(intval($explodedArgs[2]));
      
    }else{
      //list To Delete is on the body
      $list=json_decode($body);
    }
    error_log(json_encode($listToDelete,true));
    foreach($listToDelete as $item){
      if(!is_int($item)){
        $this->response_code = 400;
        $this->response_message = array(
          "error"=> 400,
          "message"=> "items ids to delete must be integers values"
        );
        return;
      }
    }
    
    $sql = "DELETE comments.* 
            FROM ".$core->prefix."comment AS comments,
                 ".$core->prefix."post AS posts
            WHERE comments.post_id=posts.post_id
            AND posts.blog_id='".$core->con->escape($core->blog->id)."'
            AND comments.comment_id IN (".implode(",",$listToDelete).");";
    $core->con->execute($sql);
    
    $this->response_code = 201;
    $this->response_message = array(
      "code"=> 201,
      "message" => "delete made"
    );
  
  }


}
