<?php
/*
*Methode permettant de retourner des informations sur un blog particulier
*/
class RestQueryGetBlog extends RestQuery
{
  public function __construct($args)
  {
    global $core;

  $this->blog_id = substr($args,6);
  
  $this->required_perms = 'unauth';
      if($this->is_allowed() === false){
        //need To be authentified
        $this->response_code = 403;
        $this->response_message = array('code' => 403, 'error' => 'This API is not open without KEY');
        return;
      }
    //instance
    if($core->auth === false){
      $core->auth = new dcAuth($core); //class dcBlog need it
      $unauth = true;
      if($core->blog->status == false){
        //le blog n'est pas publié (et l'user n'est pas authentifié)
        // on Sort en 404
        $this->response_code = 404;
        $this->response_message = array('code' => 404, 'error' => 'Resource '.$blog_id.' not found');
        return;
      }
    }
    
    $core->blog = new dcBlog($core, $this->blog_id);
    $blog_settings = new dcSettings($core,$this->blog_id);
  
    if(!$core->blog->id){
        $this->response_code = 404;
        $this->response_message = array('code' => 404, 'error' => 'Resource '.$this -> blog_id.' not found');
        return;
    }
  
    $response = array(
      'blog_id' => $core->blog->id,
      'blog_status' => $core->blog->status,
      'blog_name' => $core->blog->name,
      'blog_desc' => $core->blog->desc,
      'blog_url' => $core->blog->url
    );
    $this->response_code = 200;
    $this->response_message =  $response;
    return;
  }
}