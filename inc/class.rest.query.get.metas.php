<?php
class RestQueryGetMetas extends RestQuery
{
  public function getMetas($params){
    global $core;
    
    $rs = $core->meta->getMetadata($params, false);
    return $this->rs_to_array($rs);
  }

  public function __construct($args){
    global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    //check if user is allowed
    
    $core->blog = new dcBlog($core, $this->blog_id);
    
    if(!$core->blog->id){
      //Le blog n'existe pas
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$blog_id.' not found');
      return;      
    }
    
    $params = array();
    if (isset($_GET['post_id']))
      $params['post_id'] = $_GET['post_id'];
    if (isset($_GET['meta_id']))
      $params['meta_id'] = $_GET['meta_id'];
    if (isset($_GET['meta_type']))
      $params = $_GET['meta_type'];
      
    $this -> response_code = 200;
    $this -> response_message = $this -> getMetas($params);
  
  
  }
}