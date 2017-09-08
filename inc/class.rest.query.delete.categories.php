<?php
class RestQueryDeleteCategories extends RestQuery
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
    
    if(!$cats->getCatProperties($cat_id)){
      $this->is404($cat_id.' category not found');
      return;
    }
    
    if(empty($body)){
      $params = array();
    }elseif(empty(json_decode($body,true))){
      $params = array();
    }else{
      
      $params = $this->body_to_array($body);
      if($params === false){
        return;
      }
     
 
      if(!$this->check_for_required_fields( 
        $params,
        array(), //required fields
        array("move_childs_on_cat_id","delete_childs") //facultatives fields
      )){
        return;
      }
    }
    
    if(isset($params["move_childs_on_cat_id"])){
      if(!$cats->getCatProperties($params["move_childs_on_cat_id"])){
        $this->is404($params["move_childs_on_cat_id"].' category not found');
        return;
      }
    
      //déplacer les posts
      $sql = " UPDATE ".$core->prefix."post
               SET cat_id='".$core->con->escape($params["move_childs_on_cat_id"])."'
               WHERE blog_id='".$core->con->escape($core->blog->id)."'
               AND cat_id='".$core->con->escape($cat_id)."';";

      $core->con->execute($sql);
      
      //déplacer les sous catégories 
      $cats->moveChilds($cat_id,$params["move_childs_on_cat_id"]);
    }
    
    if((isset($params["delete_childs"])) && ($params["delete_childs"])){
      //delete posts 
      $sql = "DELETE
                post.*
              FROM
                ".$core->prefix."post AS post,
                ".$core->prefix."category AS category,
                ".$core->prefix."category AS parentcategory
              WHERE
                post.cat_id = category.cat_id
                AND parentcategory.cat_id ='".$core->con->escape($cat_id)."'
                AND category.cat_lft BETWEEN parentcategory.cat_lft AND  parentcategory.cat_rgt
                AND post.blog_id='".$core->con->escape($core->blog->id)."';";
      $core->con->execute($sql);
      $deleteSubs = true;
    }else{
      $deletSubs = false;
    }
    
    
    //OK to delete cat
    if($cats->deleteCategory($cat_id,$deleteSubs))  {
      $this->response_code = 200;
      $this->response_message = array(
        "code"  => 200,
        "message" => "Successfully delete Category"
      );
    }else{
      $this->response_code = 500;
      $this->response_message = array(
        "code"  => 500,
        "message" => "Something wrong is happened while trying to delete the category."
      );
    
    }
  
  
  }

}
