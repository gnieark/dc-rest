<?php

class RestQuery{
  protected $response_code;
  public $response_message; //array
  protected $blog_id;
  protected $required_perms = 'admin'; //must be changed by the childs class
  /*
    should be:
      'admin' administrateur
      'usage' gérer ses propres billets et commentaires
      'publish' publier des billets et des commentaires
      'delete'  supprimer des billets et des commentaires
      'contentadmin' gérer tous les billets et commentaires
      'categories'   gérer les catégories
      'media' gérer ses propres médias
      'media_admin' gérer tous les médias
      'none' //must have an account (without any rights)
      'unauth' //Open to the world
    */
    
  
  public function __construct()
  {
  
    $this->response_code = 404;
    $this->response_message = array(
      "error"  => "Method not found",
      "code"  => 404
    );
  }

  protected function is404($customMessage = '')
  {
    $this->response_code = 404;
    if (empty($customMessage))
      $this->response_message = array('code' => 404, 'error' => 'Resource  not found');
    else
      $this->response_message = array('code' => 404, 'error'  => $customMessage);
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
        $this->response_code = 400;
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
          $this->response_code = 400;
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
          $this->response_code = 400;
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
  /*
  * IN $RawFilters string urlencoded corresponding to $_GET['filters']
  * IN $permittedKeys array listing the keys the filter can Used
  * OUT array ('KeyName' => 'expectedValue')
  */
  protected function getFilters($rawFilters,$permittedKeys)
  {
 
      
    $subject = urldecode($rawFilters);
    $matchExpr = '/(?<=^|\\s)([^=\\s]+)="((?:[^\\\\"]|\\\\.)*)"/';
    $replaceExpr = '/\\\\./';

    $replaceCallback = function($match) {
      switch ($match[0][1]) {
        case 'r': return "\r";
        case 'n': return "\n";
        default: return $match[0][1];
      }
    };

    preg_match_all($matchExpr, $subject, $matches);

    $result = array();
    foreach ($matches[1] as $i => $key) {
      if(!in_array($key,$permittedKeys)){
        $this->response_code = 400;
        $this->response_message = array("code" => 400,
                                        "message" => "UnAllowed filter ".$key);
        return false;
      }
      $result[$key] = preg_replace_callback($replaceExpr, $replaceCallback, $matches[2][$i]);
    }  
    
    return $result;
  }
  
  protected function body_to_array($body){
    if($ret = json_decode($body,true)){
      return $ret;
    }else{
      $this->response_code = 400;
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
    
    $is_allowed = false;
    switch($this->required_perms){
      
      case 'unauth':
        //on verifie quand même que l'API est ouverte
        if(
            (($core->blog->settings->rest->rest_is_open) && ($core->auth === false))
            ||($core->auth !== false)
        ){
              $is_allowed = true;
        }
        
        break;      
      case 'none':
        //user must be valid
        if($core->auth){
          $is_allowed = true;
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
        if (($core->auth !== false) && $core->auth->isSuperAdmin()){
          $is_allowed = true;
        }
        break;
    }
    if($is_allowed){
      return true;
    }else{
      $this->response_code = 403;
      $this->response_message = array('code' => 403, 'error' => 'Unauthorized');
      return false;
    }
  }
  
  /*
  * Quand l'API permet à la fois une structure
  * { 
  *    key1 : value1,
  *    key2 : value2
  *  }
  *
  * et une structure avec plusieurs enregistrements
  * [
  *   { 
  *     key1 : value1.1,
  *     key2 : value2.1
  *   },
  *   { 
  *     key1 : value1.2,
  *     key2 : value2.2
  *   }
  *]
  *
  * Cette function permet de tester quelle structure a un array, et retourne un array sous la deuxième
  * structure
  *
  * IN: $arr L'array à tester
  * $keyToTest: string Un nom de clef obligatoire qui servira à tester le type de structrure
  */
  
  public function arrayOfObjetsOrNot($arr,$keyToTest){
  
    try{
      if(isset($arr[$keyToTest])){
        return array($arr);
      }elseif(isset($arr[0][$keyToTest])){
        return $arr;
      }
    }catch (Exception $e){
      //parfois ça déconne
      if(isset($arr[0][$keyToTest])){
        return $arr;
      }
    }
    return false;
  }
  
  
  public function get_full_code_header($code=''){
    if($code == ''){
      $code = $this->response_code;
    }
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
  
  function rs_to_array($rs){

    $arr = array();

    while($rs->fetch()){
      $r = array();
      $columns =  $rs->columns();
      foreach($columns as $key){
          $r[$key] = $rs->$key;
      }  
      $arr[] = $r;
    }
    return $arr;  
  }
}
