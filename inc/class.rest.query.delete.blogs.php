<?php
class ResQueryDeleteBlogs extends RestQuery
{

  public function __construct($args){
  
   global $core;
    
    $this->blog_id = substr($args,6);

    $this->required_perms = 'admin'; 
    
    //Is allowed?
    if($this->is_allowed() === false){
      //need To be authentified
      return;
    }
    
    //does the blog exists?
    $core->blog = new dcBlog($core, $this->blog_id);
    $blog_settings = new dcSettings($core,$this->blog_id);
    
    if(!$core->blog->id){
      $this->response_code = 404;
      $this->response_message = array('code' => 404, 'error' => 'Resource '.$this -> blog_id.' not found');
      return;
    }
    try{
      $core->delBlog($this->blog_id);
      $this->response_code = 201;
      $this->response_message = array(
        'code'      => 201,
        'message'   => 'Successfully deleted blog '.$this->blog_id
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
  }
}