<?php
class RestQueryGetSpecs extends RestDocumentation //some common functions betwin this 2 class
{

  protected function getSwaggerArray(){
    /*
    * need $this -> chapter initied
    * return the swagger doc (as an array)
    * with the real basePath and the URL 
    * if chapter is 'all' it will concat all swagger definitions.
    */
    
    global $core;
    
    if(!ctype_alpha($this->chapter)){
      return false;
    }
    
    if($this->chapter == 'all'){
      //concat all specs array in one
      
      $url =  parse_url($core->blog->url.$core->url->getBase('rest'));
      preg_match('/https?:\/\/[^\/]*(\/.*)/',$core->blog->url.$core->url->getBase('rest'),$end);
      
      $specs = array(
        "swagger"   => "2.0",
        "info:"     => array(
          "title"       => "Dotclear API",
          "description" => "Manage yours blogs with this API",
          "version"     => "0.1"
        ),
        "host"      => $url['host'],
        "schemes"   => array(
          $url['scheme']
        ),
        "basePath"  =>$end[1],
        "produces"  => array(
          "application/json"
        ),
        "paths"     =>array(),
        "definitions" => array(),
        
      );

      $files = scandir(path::real(dirname(__FILE__).'/../documentation/'));
      foreach($files as $file){
        if (preg_match('/^swagger\-(.*)\.json$/',$file)){
           $swag = json_decode(
              file_get_contents(
                path::real(dirname(__FILE__).'/../documentation/').'/'.$file
              ),true
            );
            $specs["paths"] = array_merge($specs["paths"], $swag["paths"]);
            $specs["definitions"] = array_merge($specs["definitions"], $swag["definitions"]);
        }
      }
      
      return $specs;
    }
    

    $files = scandir(path::real(dirname(__FILE__).'/../documentation/'));
    
    foreach($files as $file){
      if($file == 'swagger-'.$this->chapter.'.json'){
        //return path::real(dirname(__FILE__).'/../documentation/').'/'.$file;
        
        $specs = json_decode(
                  file_get_contents(
                      path::real(dirname(__FILE__).'/../documentation/').'/'.$file
                  ),true
                 );
        //change some parameters
        $url =  parse_url($core->blog->url.$core->url->getBase('rest'));
        $specs['host'] = $url['host'];
        $specs['schemes'] = $url['scheme'];
        preg_match('/https?:\/\/[^\/]*(\/.*)/',$core->blog->url.$core->url->getBase('rest'),$end);
        $specs['basePath'] = $end[1];
        return $specs;
      }
    }
    
    return false;
  
  }
  public function __construct($args){
    global $core;
    $this->response_code = 200;  
    $this->required_perms = 'unauth'; 
    if($this->is_allowed() === false){
      return;
    }
    
    if($args == "specs"){
      $this->chapter = 'all';
    }else{
      list($osef,$this->chapter) = explode("/",$args);
    }
    
    $specs = $this->getSwaggerArray();
    if($specs === false){
      $this->response_code = 404;
        $this->response_message = array(
        'error' => 'Not found'.$body,
        'code'  => 404
      );
      return;
    }
    
    $this->response_message = $specs;
    return;
  }
}
