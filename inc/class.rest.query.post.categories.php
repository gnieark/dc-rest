<?php
class RestQueryPostCategories extends RestQuery
{

  /**
  * IN $params array with keys
  * OUT integer, the new category id
  */
  public function createCategory($params){
    global $core;
    if(!isset($params['cat_title'])){  
      return false;
    }
    
    try
    {
      $cur = $core->con->openCursor($core->prefix.'category');
      $cur->cat_title =  $params['cat_title'];
      if (isset($params['cat_desc'])) {
        $cur->cat_desc = $params['cat_desc'];
      }

      if (isset($params['cat_url'])) {
        $cur->cat_url = $params['cat_url'];
      }else{
        $cur->cat_url = '';
      }
      
      if(isset($params['cat_parent_id'])){
        $cat_parent_id = $params['cat_parent_id'];
      }else{
        $cat_parent_id = null;
      }
        
      # --BEHAVIOR-- adminBeforeCategoryCreate
      $core->callBehavior('adminBeforeCategoryCreate',$cur);

      $id = $core->blog->addCategory($cur,(integer)$cat_parent_id);

      # --BEHAVIOR-- adminAfterCategoryCreate
      $core->callBehavior('adminAfterCategoryCreate',$cur,$id);
      
      return $id;
    }catch (Exception $e) {
      return false;
    }
  
  }
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

    $id = $this->createCategory($clientQueryArr);
    
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