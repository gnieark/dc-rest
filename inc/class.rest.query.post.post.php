<?php
class RestQueryPostPost extends RestQuery{
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
    
    //
    
    //tester si plusieurs posts 
    $allPosts = $this->arrayOfObjetsOrNot($clientQueryArr,'post_title');
 
   //is it valid fields?
    foreach($allPosts as $p){
      if(!$this->check_for_required_fields( 
        $p,
        array('post_title','post_format','post_content','post_status'), //required fields
        array('cat_id','new_cat_title','new_cat_parent_id','new_cat_position','post_dt','post_password',
          'post_lang','post_excerpt','post_excerpt_xhtml','post_content_xhtml',
          'post_notes','post_selected','post_open_comment','post_open_tb','post_url','post_tags') //facultatives fields
      )){      
        return;
      }
    }
    
    $createdIds = array();
    //end of checks; lets submit new posts
    foreach($allPosts as $p){
    
      //gestion de la categorie
      if(isset($p['new_cat_title'])){
        $params = array();
        $params['cat_title'] = $p['new_cat_title'];
        if(isset($p['new_cat_parent_id']))
          $params['cat_parent_id'] = $p['new_cat_parent_id'];
        else
          $params['cat_parent_id'] = null;
        if(isset($p['new_cat_position']))
          $params['cat_position'] = $p['new_cat_position'];
        else
          $params['cat_position'] = null;
        if(isset($p['new_cat_url']))
          $params['cat_url'] = $p['new_cat_url'];
        if(isset($p['new_cat_desc']))
          $params['cat_desc'] = $p['new_cat_desc'];
        
        $cats = new RestCategories($core);
        
        $cat_id = $cats->addCategory($params);

        
        if($cat_id === false){
          $this->response_message = 400;
          $this->response_message = array(
          "error"  => "ERROR when creating the new category.",
          "code"  => 400
          ); 
          return;
          
        }
      }elseif(isset($p['cat_id'])){
        $cat_id = $core->con->escape($p['cat_id']);
      }else{
        $cat_id = null;
      }
    
    
      $cur = $core->con->openCursor($core->prefix.'post');
      $cur->post_title = $core->con->escape($p['post_title']);
      $cur->cat_id = $cat_id;
      if(isset($p['post_dt']))
        $cur->post_dt = $core->con->escape($p['post_dt']);
      else
        $cur->post_dt =  '';
      $cur->post_format = $core->con->escape($p['post_format']); //mandatory field
      if(isset($p['post_password']))
        $cur->post_password = $core->con->escape($p['post_password']);
      else
        $cur->post_password = null;

      if(isset($p['post_lang']))
        $cur->post_lang = $core->con->escape($p['post_lang']);
      else
        $cur->post_lang = '';
        
      $cur->post_title = $core->con->escape($p['post_title']); //mandatory field
      
      if(isset($p['post_excerpt']))
        $cur->post_excerpt = $core->con->escape($p['post_excerpt']);
      else
        $cur->post_excerpt = '';

        
      if(isset($p['post_excerpt_xhtml'])){
      
        $cur->post_excerpt_xhtml = $core->con->escape($p['post_excerpt_xhtml']);
        
      }elseif(($p['post_format'] == 'wiki') && (isset($p['post_excerpt']))) {
        $cur->post_excerpt_xhtml = $core->wikiTransform($p['post_excerpt']);
      }elseif(($p['post_format'] <> 'wiki') && (!isset($p['post_excerpt']))){
          $this->response_message = 400;
          $this->response_message = array(
          "error"  => "ERROR. If not wiki format, give me post_exerpt_xhtml please.",
          "code"  => 400
        ); 
        return;
      }
      
      $cur->post_content = $p['post_content']; //mandatory field
      
      if($p['post_format'] == 'xhtml'){
        $cur->post_content_xhtml = $core->con->escape($p['post_content']);
      }elseif(isset($p['post_content_xhtml'])){
        $cur->post_content_xhtml = $core->con->escape($p['post_content_xhtml']);
      }elseif($p['post_format'] == 'wiki'){
        //convertir le format wiki en html
        $cur->post_content_xhtml = $core->wikiTransform($p['post_content']);
      }else{
        //sortir en erreur
        $this->response_code = 400;
        $this->response_message = array(
          "error"  => "ERROR. If not wiki format, give me post_content_xhtml please.",
          "code"  => 400
        ); 
        return;
        
      }     
      //$cur->post_notes = $post_notes; TO DO
      
      $cur->post_status = $core->con->escape($p['post_status']); //mandatory field
      
      if(isset($p['post_selected']))
        $cur->post_selected = (integer) $core->con->escape($p['post_selected']);
      else
        $cur->post_selected = 0;
        
      if(isset($p['post_open_comment']))
        $cur->post_open_comment = (integer)$core->con->escape($p['post_open_comment']);
      else
        $cur->post_open_comment = 0;
        
      if(isset($p['post_open_tb']))
        $cur->post_open_tb = (integer) $core->con->escape($p['post_open_tb']);
      else
        $cur->post_open_tb = 0;
        
      if(isset($p['post_notes']))
        $cur->post_notes = $core->con->escape($p['post_notes']);
        
      $cur->user_id = $core->auth->userID();

      try {
        # --BEHAVIOR-- adminBeforePostCreate
        $core->callBehavior('adminBeforePostCreate',$cur);

        $return_id = $core->blog->addPost($cur);
        $createdIds[] = $return_id;
        # --BEHAVIOR-- adminAfterPostCreate
        $core->callBehavior('adminAfterPostCreate',$cur,$return_id);
        
        //les eventuels tags
        if(isset($p['post_tags'])){
          foreach($p['post_tags'] as $tag){
            RestQueryPostMetas::add_meta($tag,'tag',$return_id);      
          }
        }

      } catch (Exception $e) {
          $this->response_code = 500;
          $this->response_message = array(
            "code"  => 500,
            "message" => $e->getMessage(),
          );
          return;
      }
      

  
    }   
    $this->response_code = 200;
    if(count($createdIds) == 1){
      $id = (integer)$createdIds[0];
    }else{
      $id = $createdIds;
    }
    $this->response_message = array(
    "message"  => "Successfully create post(s)",
    "id"  => $id);
  }

}
