<?php
class RestQueryGetPosts extends RestQuery
{

  public function __construct($args)
  {
  
    global $core;
    
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    $this->required_perms = 'unauth'; //les niveaux d'acces aux contenus sont 
                                      //gérés dans la function $core->blog->getPosts($params) 
    
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
   
   
  $params['where'] = ''; 
  if((isset($_GET['limit-start'])) && (isset($_GET['limit-count']))){
    $params['limit'] = array($_GET['limit-start'],$_GET['limit-count']);
  }elseif((isset($_GET['limit-start'])) xor (isset($_GET['limit-count']))){
    $this->response_code = 400;
    $this->response_message = array(
      'code'      => 400,
      'message'   => 'If you set limits both of limit-start and limit-cout must be setted'
    );
    return;  
  }
  
  if(isset($_GET['cat_id'])){
    $params['cat_id'] = $_GET['cat_id'];
  }
  
  if(isset($_GET['post_status'])){
    $params['post_status'] = $_GET['post_status'];
  }
  
  if(isset($_GET['password'])){
    $params['where'] .= ' AND post_password IS '.($_GET['password'] ? 'NOT ' : '').'NULL ';
  }
  
  if(isset($_GET['post_selected'])){
    $params['post_selected'] = $_GET['post_selected']; //to do, vérifier, si c'est pris correctement comment un boolean
  }
   
  if(isset($_GET['post_open_comment'])){
    $params['where'] .= " AND post_open_comment = '".$_GET['post_open_comment']."' ";
  }
  
  if(isset($_GET['post_open_tb'])){
    $params['where'] .= " AND post_open_tb = '".$_GET['post_open_tb']."' ";
  }
  
  //date
  if((isset($_GET['post_month'])) && (isset ($_GET['post_year']))){
      $params['post_month'] = $_GET['post_month'];
      $params['post_year'] = $_GET['post_year'];
  }elseif((isset($_GET['post_month'])) xor (isset ($_GET['post_year']))){
    $this -> response_code = 400;
    $this -> response_message = array(
      'code'      => 400,
      'message'   => 'If you set date parameters both of post_month and post_year must be setted'
    );
    return;
  }
 
  if(isset($_GET['format'])){
    $params['where'] .= " AND post_format = '".$_GET['format']."' ";
  }
  if(isset($_GET['sortby'])){
    $params['order'] = $_GET['sortby'];
  }
   
      
   $rs = $core->blog->getPosts($params);
    
   $fieldsKeys= $rs->columns();
   $response = array();
   while ($rs->fetch()) {
      $post = array();
      foreach($fieldsKeys as $key){
        $post[$key] = $rs->$key;
      }
      $response[] = $post;
    }
 
    $this -> response_code = 200;
    $this -> response_message = $response;
  
  }

}
