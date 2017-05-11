<?php
class RestQueryPostBlogs extends RestQuery
{
  public function __construct($body)
  {
  
    global $core;
    
    $this->blog_id = false; //this method doesn't depend on a bolg_id
    $this->required_perms = 'admin'; //I want user have an account 
    
    if($this->is_allowed() === false){
      //need To be authentified
      $this->response_code = 403;
      $this->response_message = array('code' => 403, 'error' => 'You need to be admin to create a new blog');
      return;
    }
    
    $inputArray =  $this-> body_to_array($body);
    if ($inputArray === false){
      return false;
    }
    
    //permit optional description
    if(!isset($inputArray['blog_desc'])){
      $inputArray["blog_desc"] = '';
    }
    //check if parameters are set
    
    if(!$this->check_for_required_fields( $inputArray, array('blog_id','blog_url','blog_name','blog_desc'), 
    array('lang','blog_timezone','url_scan')) ){
     return; 
    }
    //Following lines are same as admin/blog.php
    
    $cur = $core->con->openCursor($core->prefix.'blog');
    $blog_id = $cur->blog_id = $inputArray['blog_id'];
    $blog_url = $cur->blog_url = $inputArray['blog_url'];
    $blog_name = $cur->blog_name = $inputArray['blog_name'];
    $blog_desc = $cur->blog_desc = $inputArray['blog_desc'];
      
    try
    {
      # --BEHAVIOR-- adminBeforeBlogCreate
      $core->callBehavior('adminBeforeBlogCreate',$cur,$blog_id);

      $core->addBlog($cur);

      # Default settings and override some
      $core->blogDefaults($cur->blog_id);
      $blog_settings = new dcSettings($core,$cur->blog_id);
      $blog_settings->addNamespace('system');
      
      if(isset($inputArray['lang'])){
        $blog_settings->system->put('lang',$inputArray['lang']);
      }else{
        $blog_settings->system->put('lang',$core->auth->getInfo('user_lang'));
      }
      
      if(isset($inputArray['blog_timezone'])){
        $blog_settings->system->put('blog_timezone',$inputArray['blog_timezone']);
      }else{
        $blog_settings->system->put('blog_timezone',$core->auth->getInfo('user_tz'));
      }
      
      if(isset($inputArray['url_scan'])){
        $blog_settings->system->put('url_scan',$inputArray['url_scan']);
      }elseif(substr($blog_url,-1) == '?') {
        $blog_settings->system->put('url_scan','query_string');
      } else {
        $blog_settings->system->put('url_scan','path_info');
      }

      # --BEHAVIOR-- adminAfterBlogCreate
      $core->callBehavior('adminAfterBlogCreate',$cur,$blog_id,$blog_settings);
      
      //cool
      $this->response_code = 201;
      $this->response_message = array(
        'code'      => 201,
        'id'        => $blog_id
        'message'   => 'Successfully created blog'.$blog_id
      );

    }
    catch (Exception $e)
    {
      $this->response_code = 500;
      $this->response_message = array(
        'code'      => 500,
        'message'   => $e->getMessage()
      );
    }
    return;
  
  }

}