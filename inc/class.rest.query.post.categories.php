<?php
class RestQueryPostCategories extends RestQuery
{


  public function __construct($args,$body){
  
    global $core;
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    $this->required_perms = 'none'; //To do
    
    if($core->auth === false){
      $core->auth = new restAuth($core); //class dcBlog need it
      $unauth = true;
    }
    $core->blog = new dcBlog($core, $this->blog_id);
    if(!$core->blog->id){
      //Le blog n'existe pas
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$blog_id.' not found');
      return;      
    }
    $blog_settings = new dcSettings($core,$this->blog_id);
    
    if($this->is_allowed() === false){
      return;
    }
    
    
    $clientQueryArr = json_decode($body, true);
    if(empty($clientQueryArr)){
      $this->response_code = 400;
      $this->response_message = array(
        'error' => 'Can\'t parse input JSON'.$body,
        'code'  => 400
      );
      return;
    }
    
    if(!$this->check_for_required_fields( 
      $clientQueryArr,
      array('cat_title'), //required fields
      array('cat_url','cat_desc','cat_parent_id','cat_position','temporary') //facultatives fields
    )){ 
      return;
    }
    
    //$id = $this->createCategory($clientQueryArr);
    $cats = new RestCategories($core);
    if($cats->cat_titleExists($clientQueryArr['cat_title'])){
      $this->response_code = 409;
      $this->response_message = array(
        'error' => 409,
        'message' => 'a cat with the same cat_title allready exists.'
      );
      return;
    }
    
    $id = $cats->addCategory($clientQueryArr);
    
    
    
    if($id === false){
      $this->response_code = 500;
      $this->response_message = array(
        "error"  => "Something is wrong",
        "code"       => 500
      );
    }else{
      $this->response_code = 200;
      $this->response_message = array(
        "message"  => "Successfully create category",
        "id"       => $id
      );
    }
    
  }
}
