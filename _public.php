<?php
if (!defined('DC_RC_PATH')) { return; }

$core->url->register('rest','rest','^rest(?:/(.*))?$',array('rest','getResponse'));
class rest extends dcUrlHandlers
{

  /**
  * Create the "good" RestQuery instance
  * Input: $httpMethod: String (POST/GET/PATCH etc...)
  * $args Url arguments
  * $user dcAuth object
  * $body Body of the input query. String
  * Output: object RestQuery
  */
  private function restFactoryQuery($httpMethod,$args,$body){
    //définir la methode API (pas HTML) appelée
    switch($httpMethod){
      case "GET":
        if($args == 'blogs')
          return new RestQueryGetBlogs();
        elseif( $args == "specs")
          return new RestQueryGetSpecs($args);
        elseif(preg_match('/^specs\/(.*)$/', $args ))
          return new RestQueryGetSpecs($args);
        elseif(preg_match('/^blogs\/(.*)$/', $args ))
          return new RestQueryGetBlog($args);
        elseif($args == "documentation")
          return new RestDocumentation($args);
        elseif(preg_match('/^documentation\/(.*)$/', $args ))  
          return new RestDocumentation($args);
        elseif(preg_match('/^(.*)\/settings$/', $args ))
          return new RestQueryGetBlogSettings($args);
        elseif(preg_match('/^(.*)\/settings\/(.*)$/', $args ))
          return new RestQueryGetBlogSettings($args);
        elseif(preg_match('/^(.*)\/posts$/', $args ))
          return new RestQueryGetPosts($args);
        elseif(preg_match('/^(.*)\/post\/(.*)$/', $args ))
          return new RestQueryGetPost($args);
        elseif(preg_match('/^(.*)\/metas$/', $args ))
          return new RestQueryGetMetas($args); 
        elseif(preg_match('/^(.*)\/categories$/', $args ))
          return new RestQueryGetCategories($args); 
        elseif(preg_match('/^(.*)\/categories\/(.*)$/', $args ))
          return new RestQueryGetCategories($args);
        elseif(preg_match('/^(.*)\/metas\/(.*)$/', $args ))
          return new RestQueryGetPostMetas($args);
        elseif(preg_match('/^(.*)\/comments\/(.*)$/', $args ))
          return new RestQueryGetComments($args);
        elseif(preg_match('/^(.*)\/comments$/', $args ))
          return new RestQueryGetComments($args);
          
          
        break;
      case "POST":
        if($args == 'blogs')
          return new RestQueryPostBlogs($body);
        elseif(preg_match('/^(.*)\/settings\/(.*)$/', $args ))
          return new RestQueryPostBlogSettings($args,$body);
        elseif(preg_match('/^(.*)\/post$/', $args ))
          return new RestQueryPostPost($args,$body);
        elseif(preg_match('/^(.*)\/categories$/', $args )) 
          return new RestQueryPostCategories($args,$body);
        elseif(preg_match('/^(.*)\/metas$/', $args ))
          return new RestQueryPostMetas($args,$body);
        break;
      case "PUT":
        if(preg_match('/^blogs\/(.*)$/', $args )){
          return new ResQueryPutBlogs($args,$body);
          break;
        }      
        break;
        
      case "PATCH":
        if(preg_match('/^blogs\/(.*)$/', $args ))
          return new ResQueryPatchBlogs($args,$body);
        elseif(preg_match('/^(.*)\/(.*)\/meta\/(.*)$/', $args ))
          return new ResQueryPatchMeta($args,$body);
        elseif(preg_match('/^(.*)\/categories\/(.*)$/', $args ))
          return new RestQueryPatchCategories($args,$body);
        break;
        
      case "DELETE":
        if(preg_match('/^blogs\/(.*)$/', $args ))
          return new ResQueryDeleteBlogs($args,$body);
        elseif(preg_match('/^(.*)\/settings\/(.*)$/', $args ))
          return new RestQueryDeleteBlogSettings($args);
        elseif(preg_match('/^(.*)\/(.*)\/metas$/', $args ))
          return new RestQueryDeletePostMeta($args);
        elseif(preg_match('/^(.*)\/(.*)\/meta\/(.*)$/', $args ))
          return new RestQueryDeletePostMeta($args);
        elseif(preg_match('/^(.*)\/categories\/(.*)$/', $args ))
          return new RestQueryDeleteCategories($args,$body);
         elseif(preg_match('/^(.*)\/comments\/(.*)$/', $args )) 
          return new RestQueryDeleteComments($args,$body);
        elseif(preg_match('/^(.*)\/comments$/', $args )) 
          return new RestQueryDeleteComments($args,$body);
        
        break;
      default:
        break;
    }
    return new RestQuery(); //will return a 404
  
  }

  /*
  * Serve the Query response With the headers and the body
  */
  public static function getResponse($args)
  {
    global $core;
    $active = (boolean)$core->blog->settings->rest->rest_active;
    if (!$active){
      self::p404();
      return;
    }
         
    //coors headers
    if($core->blog->settings->rest->rest_send_cors_headers){
      header('Access-Control-Allow-Origin: *');
      header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE'); 
      header('Access-Control-Allow-Headers: Content-Type, authorization, x_dc_key');
    }

    
    //user authentification (facultative at this step)
    $apiKey = rest::get_api_key_sended();
    //$user = false;
    if($apiKey){
      $core->auth = new restAuth($core);
      if($core->auth->checkUser('','',$apiKey) === false){
        header('Content-Type: application/json');
        header(RestQuery::get_full_code_header(403));
        echo json_encode(array(
          "error" => "Wrong API Key",
          "code"  => 403
        ));
        return;
      }
    }else{
      $core->auth = false;
    }
    $r = rest::restFactoryQuery($_SERVER['REQUEST_METHOD'],$args,file_get_contents('php://input'));
    header($r->get_full_code_header());
    if(is_array($r->response_message)){
      header('Content-Type: application/json');
      echo json_encode($r->response_message);
    }else{
      echo $r->response_message;
    }
    
  }
  
  private function get_api_key_sended()
  {
    //to do: test it with nginx
    $headers = apache_request_headers();
    if(isset($headers['x_dc_key'])){
      return $headers['x_dc_key'];
    }else{
      return false;
    }
  }
  
}  
