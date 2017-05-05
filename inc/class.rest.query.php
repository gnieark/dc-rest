<?php

class RestQuery{
  public $response_code;
  public $response_message; //array
  public $blog_id;
  protected $required_perms = 'admin'; //must be changed by the childs class
  /*
    should be:
      'admin'
      'usage'
      'publish'
      'delete'
      'contentadmin'
      'categories'
      'media'
      'media_admin'
      'none' //must be have an account (without any rights)
      'unauth' //Open to the world
    */
    
  public function __construct()
  {
  
        $this->response_code = RestQuery::get_full_code_header(400);
        $this->response_message = array(
          "error"  => "Unrecoknized method",
          "code"  => 400
        );
  }
  /**
  * Check if required fields are set
  * $strict => Go on error if a additionnal field is given
  */
  protected function check_for_required_fields($arrayToCheck,$fieldsRequired,$fieldsOptionals = '')
  {
    if ($fieldsOptionals == ''){
      $fieldsOptionals == array();
    }
    
    $fieldsSetted = array_keys($arrayToCheck);
    
    if($fieldsOptionals == ''){
      if(empty(array_diff($fieldsSetted,$fieldsRequired))){
        return true;
      }else{
        $this->response_code = RestQuery::get_full_code_header(400);
        $this->response_message = array(
          "error"  => "Only and each of following parameters ".
            implode(", ",$fieldsRequired)." are required",
           "code"  => 400
        );
        return false;
      }
    }else{
      //check if all required fields are set
      foreach($fieldsRequired as $key){
        if(!isset($arrayToCheck[$key])){
          $this->response_code = RestQuery::get_full_code_header(400);
          $this->response_message = array(
            "error"  => "field ".$key." is needed",
            "code"  => 400
          );         
          return false;
        }
      }
      //check if a field is not in required and in fieldsOptionals
      foreach($fieldsSetted as $keyToTest){
        if((!in_array($keyToTest,$fieldsRequired)) && (!in_array($keyToTest,$fieldsOptionals))){
          $this->response_message = array(
            "error"  => "Unwanted field '".$keyToTest."'",
            "code"  => 400
           );  
           return false;
        }
      }
      
      return true;
    }
  
  
  }
  protected function body_to_array($body){
    if($ret = json_decode($body,true)){
      return $ret;
    }else{
      $this->response_code = 301;
      $this->response_message = array(
        'error' => 'Can\'t parse input JSON',
        'code'  => 400
      );
      return false;
    }
  }
  protected function is_allowed()
  {
    global $core;
    if($core->auth){
      $perms = $core->auth->getAllPermissions();
    }
    
    
    switch($this->required_perms){
      case 'unauth':
      
        
        //on verifie quand même que l'API est ouverte
        if((!$core->blog->settings->rest->rest_is_open) && ($core->auth === false)){
          return false;
        }else{
          return true;
        }
      
        break;
      //to do
      case 'none':
        //user must be valid
        if($core->auth === false){
          return false;
        }else{
          return true;
        }
        break;
      case 'media_admin':
        break;
      case 'media':
        break;
      case 'categories':
        break;
      case 'contentadmin':
        break;
      case 'delete':
        break;
      case 'publish':
        break;
      case 'usage':
        break;
      case 'admin':
        if($core->auth === false){
          return false;
        }
        if ($core->auth->isSuperAdmin()){
          return true;
        }else{
          return false;
        }
        break;
    }
  }
  public function get_full_code_header($code){
    static $codes = array(
      100 =>"Continue",
      101 =>"Switching Protocols",
      102 =>"Processing",
      200 =>"OK",
      201 =>"Created",
      202 =>"Accepted",
      203 =>"Non-Authoritative Information",
      204 =>"No Content",
      205 =>"Reset Content",
      206 =>"Partial Content",
      207 =>"Multi-Status",
      210 =>"Content Different",
      226 =>"IM Used",
      300 =>"Multiple Choices",
      301 =>"Moved Permanently",
      302 =>"Moved Temporarily",
      303 =>"See Other",
      304 =>"Not Modified",
      305 =>"Use Proxy",
      306 =>"(aucun)",
      307 =>"Temporary Redirect",
      308 =>"Permanent Redirect",
      310 =>"Too many Redirects",
      400 =>"Bad Request",
      401 =>"Unauthorized",
      402 =>"Payment Required",
      403 =>"Forbidden",
      404 =>"Not Found",
      405 =>"Method Not Allowed",
      406 =>"Not Acceptable",
      407 =>"Proxy Authentication Required",
      408 =>"Request Time-out",
      409 =>"Conflict",
      410 =>"Gone",
      411 =>"Length Required",
      412 =>"Precondition Failed",
      413 =>"Request Entity Too Large",
      414 =>"Request-URI Too Long",
      415 =>"Unsupported Media Type",
      416 =>"Requested range unsatisfiable",
      417 =>"Expectation failed",
      418 =>"I’m a teapot",
      421 =>"Bad mapping / Misdirected Request",
      422 =>"Unprocessable entity",
      423 =>"Locked",
      424 =>"Method failure",
      425 =>"Unordered Collection",
      426 =>"Upgrade Required",
      428 =>"Precondition Required",
      429 =>"Too Many Requests",
      431 =>"Request Header Fields Too Large",
      449 =>"Retry With",
      450 =>"Blocked by Windows Parental Controls",
      451 =>"Unavailable For Legal Reasons",
      456 =>"Unrecoverable Error"
    );
    
    if(isset($codes[$code])){
      return "HTTP/1.0 ".$code." ".$codes[$code];
    }else{
      return "HTTP/1.0 ".$code." Something wrong happened";
    }
  }
}
