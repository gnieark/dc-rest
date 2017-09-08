<?php
class RestQueryGetComments extends RestQuery

{
  public function __construct($args){
  
    global $core;
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    $this->required_perms = 'none'; //To do
    
    $post_id = ($explodedArgs[1] == 'comments')? null:$explodedArgs[1];
    $comment_id = (isset($explodedArgs[2]))? $explodedArgs[2]:null;
    
    //check if blog exists
    $core->blog = new dcBlog($core, $this->blog_id);
    if(!$core->blog->id){
      $this->is404('Resource '.$blog_id.' not found');
      return;      
    }
    
    if(!is_null($post_id)){
      //check if post exists
      $post = $core->blog->getPosts(array('post_id' => $post_id));
      
      if ($post->isEmpty()) {
        $this->is404("No post with id: ".$post_id." found");
        return;
      }
    }
    $givenFilters = array();
    if(isset($_GET['filters'])){
      $availableFilters = array(
      'post_type','post_id','cat_id','comment_id','comment_site','comment_status','comment_trackback','comment_ip','post_url','user_id');
      
      $givenFilters = $this->getFilters($_GET['filters'],$availableFilters);
      if($givenFilters === false){
        return;
      }
    }
    
    //verifier s'il n'y a pas des incohérences... parceque c'est rigolo
    // de shooter l'user avec une erreur 409
    if(
        ((!is_null($post_id)) && (isset($givenFilters["post_id"])))
      || ((!is_null($comment_id) && (isset($givenFilters["comment_id"]))))
      ){
      $this->response_code = 409;
      $this->response_message = array(
        "error" => 409,
        "message" => "Post_id or Comment_id must not be filtered both by URL path AND URL filters var on query string"
      );
    }
    
    if(!is_null($post_id)){
      $givenFilters["post_id"] = $post_id;
    }
    if(!is_null($comment_id)){
      $givenFilters["comment_id"] = $comment_id;
    }
    
    //time to execute the query
    $commentsRecord = $core->blog->getComments($givenFilters);
    $this->response_code = 200;
    $this->response_message = $this->rs_to_array($commentsRecord);
    
  
  }

	
}
