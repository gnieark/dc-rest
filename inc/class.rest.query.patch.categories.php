<?php
class RestQueryPatchCategories extends RestQuery
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
    
    if(!isset($explodedArgs[2])){
      $this->is404("no category given");
      return;
    }else{
      $cat_id = $explodedArgs[2];
    }
    
    
    $core->blog = new dcBlog($core, $this->blog_id);
    if(!$core->blog->id){
      $this->is404('Resource '.$blog_id.' not found');
      return;      
    }
    
    $cats = new RestCategories($core);
    
    $cat = $cats->getCatProperties($cat_id);
  
    if($cat === false){
      $this->is404($cat_id.' category not found');
      return;
    }
    
    //les valeurs envoyées par l'user
    $queryArr = $this->body_to_array($body);
    if($queryArr === false){
      return;
    }

    if(!$this->check_for_required_fields( 
      $queryArr ,
      array(), //required fields
      array('cat_title','cat_url','cat_desc','cat_parent_id','cat_position','temporary', 'permute') //facultatives fields
    )){
      return;
    }
    $cats->updateCategory($cat_id,$queryArr);
    $this->response_code = 201;
    $this->response_message = array(
      'code'  => 201,
      'message' => 'Successfully update category',
      'id'  => $cat_id
    );
  }
}
