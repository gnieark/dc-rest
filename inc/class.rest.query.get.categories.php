<?php
class RestQueryGetCategories extends RestQuery
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
   
    if(isset($_GET['filters'])){
      $filtersArr = $this->getFilters(
                                $_GET['filters'],
                                array('cat_title','cat_url','cat_desc',
                                  'cat_level','cat_parent_id','cat_position')
                            );
      if($filtersArr === false){
        return;
      }
    }else{
      $filtersArr = array();
    }
    $cats = new RestCategories($core);
    if(isset($explodedArgs[2])){
      //une categorie est fournie
        $this->response_message =  $cats -> getCatProperties($explodedArgs[2]);
    }else{
        $this->response_message =  $cats -> getCats($filtersArr);
    }
    $this->response_code = 200;
    
  }
}
