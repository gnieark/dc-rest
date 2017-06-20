<?php
class ResQueryPatchBlogs extends RestQuery
{


 public function __construct($args,$body)
  {
  
    global $core;
    
    $this->blog_id = substr($args,6);
    $this->required_perms = 'admin'; 
    
    //Is allowed?
    if($this->is_allowed() === false){;
      return;
    }
    
    //Is JSON valid?
    $inputArray =  $this-> body_to_array($body);
    if ($inputArray === false){
      return;
    }
    
    //is it valid fields?
    if(!$this->check_for_required_fields( $inputArray, array(), 
      array('blog_id','blog_url','blog_name','blog_desc','lang','blog_timezone','url_scan')) ){
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
    
    $cur = $core->con->openCursor($core->prefix.'blog');
    
    if(isset($inputArray['blog_id']))
      $cur->blog_id = $inputArray['blog_id'];
    else
      $cur->blog_id = $core->blog->id;
      
    if(isset($inputArray['blog_url']))
      $cur->blog_url = preg_replace('/\?+$/','?', $inputArray['blog_url']);
    else
      $cur->blog_url = $core->blog->url;
    
    if(isset($inputArray['blog_name']))
      $cur->blog_name = $inputArray['blog_name'];
    if(isset($inputArray['blog_desc']))
      $cur->blog_desc = $inputArray['blog_desc'];
    
    $core->updBlog($this->blog_id,$cur);
    
    //$cur->blog_upddt = date('Y-m-d H:i:s');
    //$cur->update("WHERE blog_id = '".$core->con->escape($id)."'");
    
    $this -> response_code = 200;
    $this -> response_message = array(
      'code'  => 200,
      'message' => 'blog '.$this->blog_id.' Successfully updated'
    );
    return;
  }
}