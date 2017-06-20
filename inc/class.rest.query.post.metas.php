<?php
/*
*Methode permettant de créer des métas (dont les tags)
*/
class RestQueryPostMetas extends RestQuery
{

  /*
  * Add a meta
  * if already exists, do nothing, does not generate warnings
  */
  public function add_meta($meta_id,$meta_type,$post_id){
      global $core;
      //check if meta already exists
      $params = array('meta_id'  => $meta_id, 'meta_type'  => $meta_type, 'post_id'  => $post_id);
                      
      $rs = $core->meta->getMetadata($params, false);
      if($rs->fetch()){
        return $rs->meta_id; //the meta already exists
      }elseif($core->meta->setPostMeta($post_id,$meta_type,$meta_id) === false){   
         return false;
      }else{
        return $meta_id;
      }
  }
  
  public function __construct($args,$body)
  {
    global $core;
    $explodedArgs = explode("/",$args);
    $this->blog_id = $explodedArgs[0];
    $this->required_perms = 'none'; //To do
    
    if($core->auth === false){
      $core->auth = new restAuth($core);
      $unauth = true;
    }
    $core->blog = new dcBlog($core, $this->blog_id);
    $blog_settings = new dcSettings($core,$this->blog_id);
    
    if($this->is_allowed() === false){
      return;
    }
    
    
    $clientQueryArr = json_decode($body, true);
    
    $createdIds = array();
    $clientQueryArr = $this->arrayOfObjetsOrNot($clientQueryArr,'meta_id');
    foreach($clientQueryArr as $query){
    
      //check for needed fields
      if(!$this->check_for_required_fields($query,
                      array('meta_id','meta_type','post_id'), //required fields
                      array() //facultatives fields
      )){
        return;
      }
      
      $id = $this -> add_meta($query['meta_id'],$query['meta_type'],$query['post_id']);
      
      if($id === false){
        $this->response_code = 500;
        $this->response_message = array("code"  => 500,
          "message" => "An error occured while setting meta ".$query['meta_id']);
        return;
      }
      $createdIds[] = $id;
      
    }
    
    $this->response_code = 201;
    if(count($createdIds) == 1){
      $this->response_message = array(
        "code"  => 200,
        "message" => "Successfully insert meta",
        "id"  => $createdIds[0]
      );
    }else{
        $this->response_message = array(
        "code"  => 200,
        "message" => "Successfully insert metas",
        "id"  => $createdIds
      );
    
    }
    return;
  }
}