<?php
class RestQueryGetSpecs extends RestQuery
{
  public function __construct(){
    global $core;
    $this->response_code = 200;  
    $this->required_perms = 'unauth'; 
    if($this->is_allowed() === false){
      return;
    }
    
    $specs = json_decode(file_get_contents(dirname(__FILE__).'/../documentation/swagger.json'),true);
    //change some parameters
    $url =  parse_url($core->blog->url.$core->url->getBase('rest'));
    $specs['host'] = $url['host'];
    $specs['schemes'] = $url['scheme'];
    preg_match('/https?:\/\/[^\/]*(\/.*)/',$core->blog->url.$core->url->getBase('rest'),$end);
    $specs['basePath'] = $end[1];
    $this->response_message = $specs;
    return;
    
  }
}